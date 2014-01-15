<?php

class BlogAdminImages extends BlogAdmin {

  private $resource_uri;

  public function view () {
    global $page, $bp;
    $html = '';
    $this->resource_uri = $this->dir . 'resources/';
    if (isset($_POST['search'])) {
      $eject = (!empty($_POST['search'])) ? $page->url('add', '', 'search', $_POST['search']) : $page->url('delete', '', 'search');
      $page->eject($eject);
    }
    if (isset($_POST['delete']) && is_numeric($_POST['delete']) && $_POST['delete'] > 0) {
      $this->delete($_POST['delete']);
      echo 'success';
      exit;
    }
    $page->link('<style type="text/css">.media-body span { margin-right:25px; }</style>');
    $page->plugin('jQuery', array('code'=>'
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
    '));
    #-- Menu --#
    $url = $this->blog['url'] . 'admin/images/';
    $html .= $bp->row('sm', array(
      $bp->col(8, $bp->pills(array(
        'View ' . $bp->badge($this->db->value('SELECT COUNT(*) FROM resources WHERE parent = 0')) => $url,
        $bp->icon('upload') . ' Upload' => $url . '?upload=resources'
      ), array('active'=>$page->url('delete', '', 'search')))),
      $bp->col(4, $bp->search(array('post'=>$url)))
    ));
    $html .= '<br>';
    #-- Method --#
    $page->plugin('Form_Validation', array('Upload', 'Image'));
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
      $page->title = 'Edit ' . $this->blog['name'] . ' Resource';
      $resource = $this->db->row('SELECT * FROM resources WHERE id = ?', array($_GET['edit']));
      if (!empty($resource)) $html .= $this->edit($resource);
    } elseif (isset($_GET['upload']) && $_GET['upload'] == 'resources') {
      $page->title = 'Upload ' . $this->blog['name'] . ' Resources';
      $html .= $this->upload();
    } else {
      $page->title = 'View ' . $this->blog['name'] . ' Resources';
      $html .= $this->view_resources();
    }
    return $this->admin($html);
  }
  
  private function edit ($resource) {
    global $page, $bp;
    $html = '';
    $image = (in_array($resource['type'], array('jpg', 'gif', 'png', 'ico'))) ? true : false;
    $form = new Form('edit_form');
    $form->required(array('name'), false);
    $form->info(array(
      'name' => 'The file name - make it descriptive and seo friendly.  The search engines love that.',
      'tags' => 'These are here to help you when searching to find a particular resource you would like to link to.',
      'type' => 'This will convert the image to a different format.',
      'width' => 'Set the new width of your image.',
      'height' => 'Set the new height of your image.',
      'quality' => 'Why not 100%, right? The higher the quality, the bigger the file, the longer it takes to download. 80% seems to be the best compromise between quality and size, but you are free to choose for yourself.'
    ));
    $resource['quality'] = 80;
    $form->values($resource);
    $form->check(array('name'=>'', 'tags'=>'an', 'new'=>'YN', 'type'=>array('jpg', 'gif', 'png', 'ico'), 'width'=>array('intrange', 0, $resource['width']), 'height'=>array('intrange', 0, $resource['height']), 'quality'=>array('intrange', 0, 100), 'coords'=>''));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      #-- Update Parent Resource --#
      $parent = array();
      $parent['name'] = $this->seo_name($vars['name']);
      $parent['tags'] = (is_array($vars['tags'])) ? implode(',', $vars['tags']) : '';
      $this->db->update('resources', $parent, 'id', $resource['id']);
      #-- Create New Image --#
      if ($vars['new'] == 'Y' && !empty($vars['type']) && !empty($vars['width']) && !empty($vars['height']) && !empty($vars['coords'])) {
        $insert = array();
        $insert['type'] = $vars['type'];
        $insert['parent'] = $resource['id'];
        $id = $this->db->insert('resources', $insert);
        #-- Create New Image --#
        $page->plugin('ImageMagick');
        $image = new ImageMagick($this->dir . 'resources/' . $resource['id'] . '.' . $resource['type']);
        $image->crop($vars['coords']);
        $image->resize($vars['width'], $vars['height'], true);
        $image->save($this->dir . 'resources/' . $id . '.' . $vars['type'], $vars['quality']);
        #-- Create Thumb Data URI --#
        $thumb = new ImageMagick($this->dir . 'resources/' . $id . '.' . $vars['type']);
        $update = array();
        $update['size'] = filesize($this->dir . 'resources/' . $id . '.' . $vars['type']);
        $update['width'] = $thumb->get('width');
        $update['height'] = $thumb->get('height');
        $thumb->resize(50, 50);
        $convert = ($thumb->get('type') == 'ico') ? 'png' : $vars['type'];
        $thumb->save($this->dir . 'resources/' . $id . '.thumb.' . $convert);
        $update['thumb'] = $thumb->data_uri();
        unlink($this->dir . 'resources/' . $id . '.thumb.' . $convert);
        $this->db->update('resources', $update, 'id', $id);
        unset($image, $thumb);
      }
      #-- Update Research --#
      $this->research($resource['id']);
      $eject = $page->url('delete', $eject, 'edit');
      $eject = $page->url('add', $eject, '#', 'image' . $resource['id']);
      $page->eject($eject);
    }
    $html .= '<div class="delete' . $resource['id'] . '">';
    $link = $this->blog['img'] . $resource['id'] . '.' . $resource['type'];
    $html .= '<p>';
      $html .= '<span style="margin-right:20px;"><a href="' . $link . '"><img src="' . $resource['thumb'] . '" /></a></span>';
      $html .= '<span style="margin-right:20px;"><strong><big>Edit</big></strong></span>';
      $html .= '<span style="margin-right:20px;"><a href="' . $link . '">' . $resource['name'] . '.' . $resource['type'] . '</a></span>';
      $html .= '<span style="margin-right:20px;">' . Upload::convert($resource['size']) . '</span>';
      $html .= '<span style="margin-right:20px;">' . $resource['width'] . ' x ' . $resource['height'] . ' px</span>';
      $html .= '<span style="margin-right:20px;">' . $resource['id'] . '.' . $resource['type'] . '</span>';
      $html .= '<span style="margin-right:20px;"><a class="delete" href="#" data-id="' . $resource['id'] . '" title="Delete This Resource">' . $bp->icon('trash') . '</a></span>';
    $html .= '</p>';
    $html .= $form->header();
    $html .= $form->field('text', 'name', 'Name', array('append'=>'.' . $resource['type'], 'input'=>'col-sm-8', 'maxlength'=>100));
    $html .= $form->field('tags', 'tags', 'Tags', array('input'=>'col-sm-8'));
    if ($image) {
      $related = '';
      $this->db->query('SELECT id, type, size, width, height, thumb FROM resources WHERE parent = ?', array($resource['id']));
      while (list($id, $type, $size, $width, $height, $thumb) = $this->db->fetch('row')) {
        $link = $this->blog['img'] . $id . '.' . $type;
        $related .= '<p class="delete' . $id . '">';
          $related .= '<span style="margin-right:20px;"><a href="' . $link . '"><img src="' . $thumb . '" /></a></span>';
          // $related .= '<span style="margin-right:20px;"><a href="' . $link . '">' . $id . '.' . $type . '</a></span>';
          $related .= '<span style="margin-right:20px;">' . Upload::convert($size) . '</span>';
          $related .= '<span style="margin-right:20px;">' . $width . ' x ' . $height . ' px</span>';
          $related .= '<span style="margin-right:20px;">' . $id . '.' . $type . '</span>';
          $related .= '<span style="margin-right:20px;"><a class="delete" href="#" data-id="' . $id . '" title="Delete This Resource">' . $bp->icon('trash') . '</a></span>';
        $related .= '</p>';
      }
      $related .= $form->field('checkbox', 'new', 'return', array('Y'=>'<strong>Create A New Image Based On The Original</strong>'));
      $html .= $form->label('text', 'related', '', $related);
      $html .= '<div id="child" style="display:none;">';
        $html .= $form->field('select', 'type', 'Type', array('jpg'=>'jpg', 'gif'=>'gif', 'png'=>'png', 'ico'=>'ico'), array('input'=>'col-sm-2'));
        $html .= $form->field('text', 'width', 'Width', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
        $html .= $form->field('text', 'height', 'Height', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
        $html .= $form->field('text', 'quality', 'Quality', array('append'=>'%', 'input'=>'col-sm-2', 'maxlength'=>3));
        $html .= '<input type="hidden" id="coords" name="coords" value="" />';
        $html .= '<img id="crop" class="img-responsive" src="' . $this->blog['img'] . $resource['id'] . '.' . $resource['type'] . '" width="' . $resource['width'] . '" height="' . $resource['height'] . '" alt="">';
        $html .= '<br>';
      $html .= '</div>';
      
      // removed: if (!$(".imgareaselect-selection").is(":visible")) reCrop(); from ias, onSelectEnd, after $(#coords")
      
      $page->plugin('jQuery', array('plugin'=>array('imagesloaded', 'imgareaselect'), 'code'=>'
        $("#crop").imagesLoaded({
        
          done: function ($image) {
          
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
                  $("#coords").val(selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
                }
              });
              $("#child").hide(0);
            });
            
            $("#new").change(function(){
              if ($(this).is(":checked")) {
                $("#child").show(0, reCrop);
              } else {
                ias.setOptions({hide:true});
                $("#child").hide();
              }
            });
            
            $("#width").change(function(){
              var width = parseInt($("#width").val());
              if (isNaN(width) || width > originalWidth) width = originalWidth;
              $("#width").val(width);
              $("#height").val(parseInt(originalHeight / originalWidth * width));
              reCrop();
            });
            
            $("#height").change(function(){
              var height = parseInt($("#height").val());
              if (isNaN(height) || height > originalHeight) height = originalHeight;
              $("#height").val(height);
              reCrop();
            });
            
            function reCrop () {
              var width = $("#width").val();
              var height = $("#height").val();
              ias.setSelection(0, 0, width, height);
              ias.setOptions({
                aspectRatio: width + ":" + height,
                minWidth: width,
                minHeight: height,
                show: true
              });
              ias.update();
              $("#coords").val("0,0," + width + "," + height);
            }
            
          }
          
        });
      '));
    }
    $html .= $form->buttons('Submit');
    $html .= $form->close();
    $html .= '</div>'; // .delete{$resource['id']}
    return $html;
  }
  
  private function upload () {
    global $page;
    $html = '';
    $upload = new Upload;
    $form = new Form('upload_form');
    $form->info(array(
      'website' => 'If the image is already on the web somewhere, then just enter that address here to save yourself a few extra steps.',
      'resources' => 'You may upload jpg, gif, png, and ico images up to 10 MB.',
      'tags' => 'These will help when you are searching to find a particular image you have uploaded.'
    ));
    $form->check(array('website'=>'url', 'tags'=>'an'));
    $form->upload(array('resources'=>array('jpg', 'jpeg', 'gif', 'png', 'ico')), 10);
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      if (!is_dir($this->resource_uri)) mkdir($this->resource_uri, 0755, true);
      $delete = array();
      $tags = (is_array($vars['tags'])) ? implode(',', $vars['tags']) : '';
      if (!empty($vars['resources'])) {
        foreach ($vars['resources'] as $id => $resource) {
          $delete[] = $id;
          $insert = array();
          $insert['type'] = $resource['ext'];
          $insert['name'] = $this->seo_name(substr($resource['name'], 0, strrpos($resource['name'], '.')));
          $insert['tags'] = $tags;
          $insert['size'] = $resource['size'];
          $insert['width'] = $resource['width'];
          $insert['height'] = $resource['height'];
          $insert['thumb'] = $resource['thumb'];
          $id = $this->db->insert('resources', $insert);
          copy($resource['uri'], $this->resource_uri . $id . '.' . $insert['type']);
          $this->research($id);
        }
      }
      if (!empty($delete)) $upload->delete($delete);
      $page->eject($page->url('delete', $eject, 'upload'));
    }
    $html .= '<div class="row"><div class="col-sm-10">';
    $html .= $form->header();
    $html .= $form->field('text', 'website', 'Website');
    $html .= $form->field('file', 'resources', 'Files');
    $html .= $form->field('tags', 'tags', 'Tags');
    $html .= $form->buttons('Submit');
    $html .= $form->close();
    $html .= '</div></div>';
    unset($form);
    return $html;
  }
  
  private function view_resources () {
    global $page, $bp;
    $html = '';
    $page->plugin('Form_Validation', array('Upload'));
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    if (isset($_GET['search'])) {
      $search = str_replace(array('-', '/', '.'), ' ', $_GET['search']);
      if (!$list->count()) $list->count($this->db->value('SELECT COUNT(*) FROM research WHERE resource MATCH ?', array($search)));
      $this->db->query('SELECT id
                        FROM resources AS r
                        INNER JOIN research AS s ON s.docid = r.id
                        WHERE resource MATCH ? ORDER BY id DESC' . $list->limit(), array($search));
    } else {
      if (!$list->count()) $list->count($this->db->value('SELECT COUNT(*) FROM resources WHERE parent = 0'));
      $this->db->query('SELECT id FROM resources WHERE parent = 0 ORDER BY id DESC' . $list->limit());
    }
    $parents = array(); // a multidimensional array
    while (list($id) = $this->db->fetch('row')) $parents[$id] = array();
    $ids = array_keys($parents); // each id's information
    $this->db->query('SELECT parent, id FROM resources WHERE parent IN(' . implode(', ', $ids) . ')');
    $ids = array_flip($ids);
    while (list($parent, $id) = $this->db->fetch('row')) {
      $parents[$parent][$id] = '';
      $ids[$id] = '';
    }
    $this->db->query('SELECT id, parent, type, name, tags, size, width, height, thumb FROM resources WHERE id IN(' . implode(', ', array_keys($ids)) . ')');
    while ($row = $this->db->fetch('row')) {
      $ids[$row[0]] = $row;
    }
    #-- Get Used Resources --#
    $used = array();
    $this->db->query('SELECT u.resource_id, u.blog_id, b.url, b.title FROM images AS u LEFT JOIN blog AS b ON u.blog_id = b.id WHERE u.resource_id IN (' . implode(', ', array_keys($ids)) . ') ORDER BY u.blog_id DESC');
    while (list($resource, $id, $url, $title) = $this->db->fetch('row')) {
      if (is_numeric($id)) {
        $edit = '<a href="' . $this->blog['url'] . 'admin/blog/?edit=' . $id . '">' . $bp->icon('pencil') . '</a>';
        $edit .= ' <a href="' . $this->blog['url'] . $url . '/">' . $title . '</a>';
      } elseif ($id == 'css') {
        $edit = '<a href="' . $this->blog['url'] . 'admin/bootstrap/">' . $id . '</a>';
      } else {
        $edit = '<a href="' . $this->blog['url'] . 'admin/layout/">' . $id . '</a>';
      }
      $used[$resource][] = $edit;
    }
    foreach ($used as $id => $links) {
      if (!isset($parents[$id])) { // then this is a child
        $used[$id] = '<p><span style="margin-left:18px;"><em>Used In</em>:</span><span>' . implode('</span> <span>', $links) . '</span></p>';
      } else {
        $used[$id] = '<p><span><em>Used In</em>:</span><span>' . implode('</span> <span>', $links) . '</span></p>';
      }
    }
    $media = array(); // multidimensional
    foreach ($parents as $parent => $child) {
      $media[0][$parent] = $this->media($ids[$parent], $used);
      if (!empty($child)) {
        list($child) = each($child);
        $media[$parent][$child] = $this->media($ids[$child], $used);
      }
    }
    $html .= $bp->media($media);
    $html .= '<div class="text-center">' . $list->pagination() . '</div>';
    return $html;
  }
  
  private function media ($info, $used) {
    global $page, $bp;
    list($id, $parent, $type, $name, $tags, $size, $width, $height, $thumb) = $info;
    $link = $this->blog['img'] . $id . '.' . $type;
    if (!empty($thumb)) $thumb = '<a href="' . $link . '"><img src="' . $thumb . '"></a>';
    $info = '';
    if (!empty($name)) {
      $info .= '<span>' . $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'edit', $id))) . '</span>';
      $info .= '<span><code>{$blog[\'img\']}' . $id . '.' . $type . '</code></span>';
      $info .= '<span><a href="' . $link . '"><strong>' . $name . '.' . $type . '</strong></a></span>';
    } else { // a child image
      $info .= '<span style="margin-left:13px;"><code>{$blog[\'img\']}' . $id . '.' . $type . '</code></span>';
    }
    $info .= '<span>' . Upload::convert($size) . '</span>';
    if (!empty($thumb)) $info .= '<span>' . $width . ' x ' . $height . ' px</span>';
    $info = '<p>' . $info . '</p>';
    if (!empty($tags)) {
      $info .= '<p><span><em>Tagged</em>:</span><span>' . implode('</span> <span>', explode(',', $tags)) . '</span></p>';
    }
    if (isset($used[$id])) $info .= $used[$id];
    $delete = '<a class="delete" href="#" data-id="' . $id . '" title="Delete This Resource">' . $bp->icon('trash') . '</a>';
    return $bp->media(array('id'=>"image{$id}", $thumb, $info, $delete));
  }
  
  private function seo_name ($path) {
    $path = preg_replace('/[^a-z0-9\-\/]/', '', str_replace(array(' ', '_'), '-', strtolower($path)));
    $path = preg_replace('/[\-\/](?=[\-\/])/', '', $path);
    $path = trim($path, '/');
    $path = trim($path, '-');
    return $path;
  }
  
  private function delete ($id) {
    $row = $this->db->row('SELECT id, parent, type FROM resources WHERE id = ?', array($id));
    if (empty($row)) return;
    $this->db->query('SELECT id, type FROM resources WHERE id = ? OR parent = ?', array($row['id'], $row['id']));
    while (list($id, $type) = $this->db->fetch('row')) {
      $file = $this->resource_uri . $id . '.' . $type;
      if (file_exists($file)) unlink($file);
    }
    $this->db->statement('DELETE FROM resources WHERE id = ? OR parent = ?', array($row['id'], $row['id']), 'delete');
    $child = (!empty($row['parent'])) ? true : false;
    if ($child) {
      $this->research($row['parent']); // update the parent's search info
    } else {
      $this->db->delete('research', 'docid', $row['id']);
    }
  }
  
}

?>