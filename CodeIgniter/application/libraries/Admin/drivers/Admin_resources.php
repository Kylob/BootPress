<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_resources extends CI_Driver {

  public function view () {
    global $bp, $ci, $page;
    $html = '';
    if (isset($_POST['search'])) {
      $eject = (!empty($_POST['search'])) ? $page->url('add', '', 'search', $_POST['search']) : $page->url('delete', '', 'search');
      $page->eject($eject);
    }
    if (isset($_POST['delete']) && is_numeric($_POST['delete']) && $_POST['delete'] > 0) {
      $this->delete($_POST['delete']);
      echo 'success';
      exit;
    }
    $page->plugin('jQuery', 'code', '
      $(".delete").click(function(){
        var id = $(this).data("id");
        if (confirm("Are you sure you would like to delete this image?")) {
          $("#image" + id).hide();
          $.post(location.href, {"delete":id}, function(data){
            if (data != "success") $("#image" + id).show();
          }, "text");
        }
        return false;
      });
    ');
    #-- Menu --#
    $url = BASE_URL . ADMIN . '/resources';
    $html .= $bp->row('sm', array(
      $bp->col(8, $bp->pills(array(
        'View ' . $bp->badge($this->blog->db->value('SELECT COUNT(*) FROM resources WHERE parent = 0')) => $url,
        $bp->icon('upload') . ' Upload' => $url . '?upload=resources'
      ), array('active'=>$page->url('delete', '', 'search')))),
      $bp->col(4, $bp->search(array('post'=>$url)))
    ));
    $html .= '<br>';
    #-- Method --#
    $ci->load->helper('number');
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
      $page->title = 'Edit ' . $this->blog->get('name') . ' Resource';
      $resource = $this->blog->db->row('SELECT * FROM resources WHERE id = ?', array($_GET['edit']));
      if (!empty($resource)) $html .= $this->edit($resource);
    } elseif (isset($_GET['upload']) && $_GET['upload'] == 'resources') {
      $page->title = 'Upload ' . $this->blog->get('name') . ' Resources';
      $html .= $this->upload();
    } else {
      $page->title = 'View ' . $this->blog->get('name') . ' Resources';
      $html .= $this->view_resources();
    }
    return $this->admin($html);
  }
  
  private function edit ($resource) {
    global $bp, $page;
    $html = '';
    $types = array('jpg', 'gif', 'png', 'ico');
    if (!$page->plugin('Image', 'Magick')) array_pop($types);
    $image = (in_array($resource['type'], $types)) ? true : false;
    $form = $page->plugin('Form', 'name', 'edit_form');
    $form->menu('new', array('Y'=>'<strong>Create A New Image Based On The Original</strong>'));
    $form->menu('type', array_combine($types, $types));
    $resource['quality'] = 80;
    $form->values($resource);
    $form->validate('name', 'Name', 'required', 'The file name - make it descriptive and seo friendly.  The search engines love that.');
    $form->validate('tags', 'Tags', '', 'These are here to help you when searching to find a particular resource you would like to link to.');
    $form->validate('new', '', 'YN');
    $form->validate('type', 'Type', 'inarray[menu]', 'This will convert the image to a different format.');
    $form->validate('width', 'Width', 'integer|gte[0]|lte[' . $resource['width'] . ']', 'Set the new width of your image.');
    $form->validate('height', 'Height', 'integer|gte[0]|lte[' . $resource['height'] . ']', 'Set the new height of your image.');
    $form->validate('quality', 'Quality', 'integer|gte[0]|lte[100]', 'Why not 100%, right? The higher the quality, the bigger the file, the longer it takes to download. 80% seems to be the best compromise between quality and size, but you are free to choose for yourself.');
    $form->validate('coords');
    if ($form->submitted() && empty($form->errors)) {
      #-- Update Parent Resource --#
      $parent = array();
      $parent['name'] = $this->image_filter($form->vars['name']);
      $parent['tags'] = $form->vars['tags'];
      $this->blog->db->update('resources', 'id', array($resource['id'] => $parent));
      #-- Create New Image --#
      if ($form->vars['new'] == 'Y' && !empty($form->vars['type']) && !empty($form->vars['width']) && !empty($form->vars['height']) && !empty($form->vars['coords'])) {
        $insert = array();
        $insert['type'] = $form->vars['type'];
        $insert['parent'] = $resource['id'];
        $id = $this->blog->db->insert('resources', $insert);
        #-- Create New Image --#
        if ($image = $page->plugin('Image', 'uri', BASE_URI . 'blog/resources/' . $resource['id'] . '.' . $resource['type'])) {
          $image->crop($form->vars['coords']);
          $image->resize($form->vars['width'], $form->vars['height'], 'enforce');
          if ($image->save(BASE_URI . 'blog/resources/' . $id . '.' . $form->vars['type'], $form->vars['quality'])) {
            if ($thumb = $page->plugin('Image', 'uri', $image->saved)) {
              $update = array();
              $update['size'] = filesize($image->saved);
              $update['width'] = $thumb->width;
              $update['height'] = $thumb->height;
              $thumb->resize(50, 50);
              $convert = ($thumb->type == 'ico') ? 'png' : $form->vars['type'];
              if ($thumb->save(BASE_URI . 'blog/resources/' . $id . '.thumb.' . $convert)) {
                $update['thumb'] = Image::data($thumb->saved);
                unlink($thumb->saved);
              }
              $this->blog->db->update('resources', 'id', array($id => $update));
            }
          }
        }
        #-- Create Thumb Data URI --#
        unset($image, $thumb);
      }
      #-- Update Research --#
      $this->research($resource['id']);
      $form->eject = $page->url('delete', $form->eject, 'edit');
      $form->eject = $page->url('add', $form->eject, '#', 'image' . $resource['id']);
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $html .= $form->field('name', 'text', array('append'=>'.' . $resource['type'], 'input'=>'col-sm-8', 'maxlength'=>100));
    $html .= $form->field('tags', 'tags');
    if ($image) {
      $html .= $form->field('new', 'checkbox');
      $html .= '<div id="child" style="display:none;">';
        $html .= $form->field('type', 'select', array('input'=>'col-sm-2'));
        $html .= $form->field('width', 'text', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
        $html .= $form->field('height', 'text', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
        $html .= $form->field('quality', 'text', array('append'=>'%', 'input'=>'col-sm-2', 'maxlength'=>3));
        $html .= $form->field('coords', 'hidden');
        $html .= '<img id="crop" class="img-responsive" src="' . $this->blog->get('img') . $resource['id'] . '.' . $resource['type'] . '" width="' . $resource['width'] . '" height="' . $resource['height'] . '" alt="">';
        $html .= '<br>';
      $html .= '</div>';
      $page->plugin('CDN', 'links', array(
        'imagesloaded/2.1.0/jquery.imagesloaded.js',
        'wordpress/3.8/js/imgareaselect/jquery.imgareaselect.min.js',
        'wordpress/3.8/js/imgareaselect/imgareaselect.css'
      ));
      $page->plugin('jQuery', 'code', '$("#crop").imagesLoaded({done:function(){
        
        var ias;
        var originalWidth = ' . $resource['width'] . ';
        var originalHeight = ' . $resource['height'] . ';
        
        $("#child").show(0, function(){
          ias = $("img#crop").attr("width", $("img#crop").width()).attr("height", $("img#crop").height()).imgAreaSelect({
            instance: true,
            handles: "corners",
            imageWidth: ' . $resource['width'] . ',
            imageHeight: ' . $resource['height'] . ',
            aspectRatio: false,
            onSelectEnd: function(img, selection){
              $("input[name=coords]").val(selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
            }
          });
          $("#child").hide(0);
        });
        
        $("input[name=new]").change(function(){
          if ($(this).is(":checked")) {
            $("#child").show(0, reCrop);
          } else {
            ias.setOptions({hide:true});
            $("#child").hide();
          }
        });
        
        $("input[name=width]").change(function(){
          var width = parseInt($("input[name=width]").val());
          if (isNaN(width) || width > originalWidth) width = originalWidth;
          $("input[name=width]").val(width);
          $("input[name=height]").val(parseInt(originalHeight / originalWidth * width));
          reCrop();
        });
        
        $("input[name=height]").change(function(){
          var height = parseInt($("input[name=height]").val());
          if (isNaN(height) || height > originalHeight) height = originalHeight;
          $("input[name=height]").val(height);
          reCrop();
        });
        
        function reCrop () {
          var width = $("input[name=width]").val();
          var height = $("input[name=height]").val();
          ias.setSelection(0, 0, width, height);
          ias.setOptions({
            aspectRatio: width + ":" + height,
            minWidth: width,
            minHeight: height,
            show: true
          });
          ias.update();
          $("input[name=coords]").val("0,0," + width + "," + height);
        }
        
      }});');
    }
    $html .= $form->submit();
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function upload () {
    global $page;
    $html = '';
    $types = array('jpeg', 'jpg', 'gif', 'png', 'ico', 'js', 'css', 'pdf', 'ttf', 'otf', 'svg', 'eot', 'woff', 'swf', 'tar', 'tgz', 'gz', 'zip', 'csv', 'xlsx', 'xls', 'xl', 'word', 'docx', 'doc', 'ppt', 'mp3', 'mpeg', 'mpe', 'mpg', 'mov', 'qt', 'psd');
    $form = $page->plugin('Form', 'name', 'upload_form');
    $form->validate('website', 'Website', 'url', 'If the image is already on the web somewhere, then just enter that address here to save yourself a few extra steps.');
    $form->upload('resources[]', 'Files', implode('|', $types), array('filesize'=>10, 'info'=>'You may upload files up to 10 MB with the extension: ' . implode(', ', $types)));
    $form->validate('tags', 'Tags', '', 'These will help when you are searching to find a particular image you have uploaded.');
    if ($form->submitted() && empty($form->errors)) {
      $images = str_replace(BASE_URL, BASE_URI, $this->blog->get('img'));
      if (!is_dir($images)) mkdir($images, 0755, true);
      if (!empty($form->vars['resources'])) {
        foreach ($form->vars['resources'] as $uri => $name) {
          if ($file = Form::fileinfo($uri)) {
            $insert = array();
            $insert['name'] = $this->image_filter(substr($name, 0, strrpos($name, '.')));
            $insert['tags'] = $form->vars['tags'];
            $insert['size'] = $file['size'];
            $insert['type'] = $file['type'];
            if ($file['image']) {
              $insert['width'] = $file['width'];
              $insert['height'] = $file['height'];
              $insert['thumb'] = '';
              if ($thumb = $page->plugin('Image', 'uri', $file['path'] . $file['name'] . $file['ext'])) {
                $thumb->resize(50, 50);
                if ($thumb->save($file['path'] . $file['name'] . '.thumb' . $file['ext'])) {
                  $insert['thumb'] = Image::data($thumb->saved);
                  unlink($thumb->saved);
                }
              }
            }
            $id = $this->blog->db->insert('resources', $insert);
            copy($uri, $images . $id . '.' . $insert['type']);
            $this->research($id);
          }
        }
      }
      $page->eject($page->url('delete', $form->eject, 'upload'));
    }
    $html .= $form->header();
    $html .= $form->field('website', 'text');
    $html .= $form->field('resources[]', 'file');
    $html .= $form->field('tags', 'tags');
    $html .= $form->submit();
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function view_resources () {
    global $bp, $ci, $page;
    $html = '';
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    if (isset($_GET['search'])) {
      $search = str_replace(array('-', '/', '.'), ' ', $_GET['search']);
      if (!$list->count()) $list->count($this->blog->db->value('SELECT COUNT(*) FROM research WHERE resource MATCH ?', array($search)));
      $this->blog->db->query('SELECT id
                        FROM resources AS r
                        INNER JOIN research AS s ON s.docid = r.id
                        WHERE resource MATCH ? ORDER BY id DESC' . $list->limit(), array($search));
    } else {
      if (!$list->count()) $list->count($this->blog->db->value('SELECT COUNT(*) FROM resources WHERE parent = 0'));
      $this->blog->db->query('SELECT id FROM resources WHERE parent = 0 ORDER BY id DESC' . $list->limit());
    }
    $parents = array(); // a multidimensional array
    while (list($id) = $this->blog->db->fetch('row')) $parents[$id] = array();
    $ids = array_keys($parents); // each id's information
    $this->blog->db->query('SELECT parent, id FROM resources WHERE parent IN(' . implode(', ', $ids) . ')');
    $ids = array_flip($ids);
    while (list($parent, $id) = $this->blog->db->fetch('row')) {
      $parents[$parent][$id] = '';
      $ids[$id] = '';
    }
    $this->blog->db->query('SELECT id, parent, type, name, tags, size, width, height, thumb FROM resources WHERE id IN(' . implode(', ', array_keys($ids)) . ')');
    while ($row = $this->blog->db->fetch('assoc')) {
      $ids[$row['id']] = $row;
    }
    #-- Get Used Resources --#
    $used = array();
    $ci->sitemap->db()->query(array(
      'SELECT r.resource_id, u.uri',
      'FROM resources AS r INNER JOIN uris AS u ON r.docid = u.docid',
      'WHERE r.docid > 0 AND r.resource_id IN (' . implode(', ', array_keys($ids)) . ')',
      'ORDER BY u.uri ASC'
    ));
    while (list($id, $uri) = $ci->sitemap->db()->fetch('row')) $used[$id][] = '<a href="' . BASE_URL . $uri . '">' . $uri . '</a>';
    foreach ($used as $id => $links) {
      $links = (count($links) > 10) ? '<span class="space">More than 10 pages</span>' : '<span class="space">' . implode('</span> <span class="space">', $links) . '</span>';
      if (!isset($parents[$id])) { // then this is a child
        $used[$id] = '<p><span class="space" style="margin-left:18px;"><em>Used In</em>:</span>' . $links . '</p>';
      } else {
        $used[$id] = '<p><span class="space"><em>Used In</em>:</span>' . $links . '</p>';
      }
    }
    $media = array(); // multidimensional
    foreach ($parents as $parent => $child) {
      $media[0][$parent] = $this->media($ids[$parent], $used);
      if (!empty($child)) {
        foreach (array_keys($child) as $related) {
          $media[$parent][$related] = $this->media($ids[$related], $used);
        }
      }
    }
    $html .= $bp->media($media);
    $html .= '<div class="text-center">' . $list->pagination() . '</div>';
    return $html;
  }
  
  private function media ($info, $used=array()) {
    global $page, $bp;
    // list($id, $parent, $type, $name, $tags, $size, $width, $height, $thumb) = $info;
    $link = $this->blog->get('img') . $info['id'] . '.' . $info['type'];
    if (!empty($info['thumb'])) $info['thumb'] = '<a href="' . $link . '"><img src="' . $info['thumb'] . '"></a>';
    $html = '';
    if (!empty($info['name'])) {
      $html .= '<span class="space">' . $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'edit', $info['id']))) . '</span>';
      $html .= '<span class="space"><code>{$blog[\'img\']}' . $info['id'] . '.' . $info['type'] . '</code></span>';
      $html .= '<span class="space"><a href="' . $link . '"><strong>' . $info['name'] . '.' . $info['type'] . '</strong></a></span>';
    } else { // a child image
      $html .= '<span class="space" style="margin-left:13px;"><code>{$blog[\'img\']}' . $info['id'] . '.' . $info['type'] . '</code></span>';
    }
    $html .= '<span class="space">' . byte_format($info['size']) . '</span>';
    if (!empty($info['thumb'])) $html .= '<span class="space">' . $info['width'] . ' x ' . $info['height'] . ' px</span>';
    $html = '<p>' . $html . '</p>';
    if (!empty($info['tags'])) {
      $html .= '<p><span class="space"><em>Tagged</em>:</span><span class="space">' . implode('</span> <span class="space">', explode(',', $info['tags'])) . '</span></p>';
    }
    if (isset($used[$info['id']])) $html .= $used[$info['id']];
    $delete = '<a class="delete" href="#" data-id="' . $info['id'] . '" title="Delete This Resource">' . $bp->icon('trash') . '</a>';
    return $bp->media(array('id'=>'image'.$info['id'], $info['thumb'], $html, $delete));
  }
  
  private function image_filter ($file) {
    $file = preg_replace('/[^0-9a-z.\-]/', '-', strtolower($file)); // lowercase alphanumeric . -
    $file = preg_replace('/[.\-](?=[.\-])/', '', $file); // no doubled up punctuation
    return trim($file, '-'); // no trailing (or preceding) dashes
  }
  
  private function research ($id) { // used when searching for images
    $docid = $id;
    $this->blog->db->query('SELECT type, parent, name, tags FROM resources WHERE id = ?', array($docid));
    list($type, $parent, $name, $tags) = $this->blog->db->fetch('row');
    if ($parent != 0) return $this->research($parent);
    $ids = array($id);
    $types = array($type);
    $this->blog->db->query('SELECT id, type FROM resources WHERE parent = ?', array($docid));
    while (list($id, $type) = $this->blog->db->fetch('row')) {
      $ids[] = $id;
      $types[] = $type;
    }
    $search = array();
    $search[] = preg_replace('/[^0-9a-z]/i', ' ', $name);
    $search[] = implode(' ', $ids);
    $search[] = implode(' ', array_unique($types));
    if (!empty($tags)) $search[] = str_replace(',', ' ', $tags);
    $search = implode(' ', $search);
    $this->blog->db->delete('research', 'docid', $docid);
    $this->blog->db->insert('research', array('docid'=>$docid, 'resource'=>$search));
  }
  
  private function delete ($id) {
    $row = $this->blog->db->row('SELECT id, parent, type FROM resources WHERE id = ?', array($id));
    if (empty($row)) return;
    $this->blog->db->query('SELECT id, type FROM resources WHERE id = ? OR parent = ?', array($row['id'], $row['id']));
    while (list($id, $type) = $this->blog->db->fetch('row')) {
      $file = str_replace(BASE_URL, BASE_URI, $this->blog->get('img')) . $id . '.' . $type;
      if (file_exists($file)) unlink($file);
    }
    $this->blog->db->query('DELETE FROM resources WHERE id = ? OR parent = ?', array($row['id'], $row['id']), 'delete');
    $child = (!empty($row['parent'])) ? true : false;
    if ($child) {
      $this->research($row['parent']); // update the parent's search info
    } else {
      $this->blog->db->delete('research', 'docid', $row['id']);
    }
  }
  
}

/* End of file Admin_resources.php */
/* Location: ./application/libraries/Admin/drivers/Admin_resources.php */