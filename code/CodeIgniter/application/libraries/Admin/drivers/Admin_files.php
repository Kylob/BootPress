<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_files extends CI_Driver {
  
  private $folder;
  
  public function view ($type, $path) {
    global $ci;
    $html = '';
    if ($ci->blog->controller == '#admin#') {
      switch ($type) {
        case 'authors': $html .= $this->folder($path, false, array('images'=>'jpg|jpeg|gif|png', 'files'=>false, 'resources'=>false)); break;
        case 'blog': $html .= $this->folder($path, false, array('exclude'=>'index.tpl', 'files'=>'tpl|js|css')); break;
        case 'themes': $html .= $this->folder($path, true, array('exclude'=>array('index.tpl', 'variables.less', 'custom.less'), 'files'=>'tpl|js|css', 'resources'=>'less|ttf|otf|svg|eot|woff|swf|zip', 'unzip'=>BASE_URI . 'themes')); break;
        case 'plugins': $html .= $this->folder($path, 2, array('exclude'=>'index.php')); break;
        case 'folders': $html .= $this->folder($path, false, array('exclude'=>'index.php')); break;
      }
    }
    return $html;
  }
  
  public function filter ($file, $slashes=false) {
    $file = str_replace(array('\\', '_'), array('/', '-'), $file);
    $file = preg_replace('/[^0-9a-z.\-\/]/', '-', strtolower($file)); // lowercase alphanumeric . - /
    $file = preg_replace('/[.\-\/](?=[.\-\/])/', '', $file); // no doubled up punctuation
    $file = trim($file, '.-/'); // no trailing (or preceding) dots, dashes, and slashes
    if (is_int($slashes) && $slashes > 0) {
      $file = explode('/', $file);
      $parts = implode('/', array_slice($file, 0, $slashes));
      if (count($file) > $slashes) $parts .= '-' . implode('-', array_slice($file, $slashes));
      $file = $parts;
    } elseif ($slashes === false) {
      $file = str_replace('/', '-', $file);
    }
    return $file;
  }
  
  public function folder ($path, $recursive=false, $types=array()) {
    $this->folder = func_get_args();
    global $ci;
    $path = rtrim($path, '/') . '/';
    if (is_array($recursive)) return $this->manage($path, $recursive, $recursive);
    $files = array_merge(array(
      'main' => array(), // The files we don't want to rename or delete
      'exclude' => array(), // The files we don't want to include at all
      'files' => 'tpl|js|css' . (is_admin(1) ? '|php' : null),
      'images' => 'jpg|jpeg|gif|png|ico',
      'resources' => 'pdf|ttf|otf|svg|eot|woff|swf|tar|gz|tgz|zip|csv|xl|xls|xlsx|word|doc|docx|ppt|mp3|ogg|wav|mpe|mpeg|mpg|mov|qt|psd',
    ), $types);
    $types = array();
    foreach (array('files', 'images', 'resources') as $type) {
      if (!empty($files[$type])) $types[] = trim($files[$type], '|');
    }
    $types = implode('|', $types);
    if (empty($types)) return;
    $main = (array) $files['main'];
    $exclude = (array) $files['exclude'];
    $form = array('files'=>$files['files'], 'images'=>$files['images'], 'resources'=>$files['resources'], 'types'=>$types, 'unzip'=>isset($files['unzip']) ? $files['unzip'] : false);
    list($dirs, $files) = $ci->blog->folder($path, $recursive, $types);
    $files = array_diff($files, $exclude);
    return $this->manage($path, $files, $main, $form, $recursive);
  }
      
  public function save ($files, array $remove=array(), $function=null, array $params=array()) {
    global $ci;
    if (($retrieve = $ci->input->post('retrieve')) && (is_string($files) || isset($files[$retrieve]))) {
      if (is_string($files)) exit(is_file($files . $retrieve) ? file_get_contents($files . $retrieve) : '');
      foreach ((array) $files[$retrieve] as $content) {
        if (is_file($content)) exit(file_get_contents($content));
      }
      exit;
    }
    if (($save = $ci->input->post('field')) && (is_string($files) || isset($files[$save])) && !is_null($ci->input->post('wyciwyg'))) {
      $code = str_replace("\r\n", "\n", base64_decode(base64_encode($_POST['wyciwyg'])));
      if (is_string($files)) {
        $file = $files . $save;
      } else {
        $file = (is_array($files[$save])) ? array_shift($files[$save]) : $files[$save]; // the first (main) one
        if (empty($code) && is_array($files[$save])) { // we have a backup plan
          foreach ($files[$save] as $content) {
            if (is_file($content)) {
              $code = file_get_contents($content);
              break;
            }
          }
        }
      }
      if (empty($code) && in_array($save, $remove)) {
        if (is_file($file)) unlink($file);
        if (is_callable($function)) call_user_func_array($function, $params);
        exit('Saved');
      }
      if (!empty($code)) {
        switch(substr($file, -4)) {
          case '.php':
            if (defined('PHP_PATH') && constant('PHP_PATH') != '') {
              $linter = BASE_URI . 'blog/' . md5($file) . '.php';
              file_put_contents($linter, $code);
              exec(PHP_PATH . ' -l ' . escapeshellarg($linter) . ' 2>&1', $output);
              unlink($linter);
              $output = trim(implode("\n", $output));
              if (!empty($output) && strpos($output, 'No syntax errors') === false) {
                exit(preg_replace('#' . str_replace('/', '[\\\\//]{1}', preg_quote($linter)) . '#', str_replace('.php', '', $save) . '.php', $output));
              }
            }
            break;
          case '.ini':
            // http://stackoverflow.com/questions/1241728/can-i-try-catch-a-warning
            set_error_handler(function($errno, $errstr){throw new Exception($errstr);});
            $linter = BASE_URI . 'blog/' . md5($file) . '.ini';
            file_put_contents($linter, $code);
            try {
              $output = parse_ini_file($linter);
              unlink($linter);
            } catch (Exception $e) {
              exit(preg_replace('#' . str_replace('/', '[\\\\//]{1}', preg_quote($linter)) . '#', str_replace('.ini', '', $save) . '.ini', $e->getMessage()));
            }
            restore_error_handler();
            break;
          case '.tpl':
            $linter = BASE_URI . 'blog/' . md5($file) . '.tpl';
            file_put_contents($linter, $code);
            $output = $ci->blog->smarty($linter, array(), 'testing');
            unlink($linter);
            if ($output !== true) {
              exit(preg_replace('#' . str_replace('/', '[\\\\//]{1}', preg_quote($linter)) . '#', str_replace('.tpl', '', $save) . '.tpl', $output));
            }
            break;
        }
      }
      if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
      file_put_contents($file, $code);
      if (is_callable($function)) call_user_func_array($function, $params);
      exit('Saved');
    }
    if (is_array($files)) {
      $values = array(); // textarea
      foreach (array_keys($files) as $file) {
        if (strpos($file, '.') !== false) continue;
        foreach ((array) $files[$file] as $content) {
          if (!is_file($content)) continue;
          $values[$file] = file_get_contents($content);
          break;
        }
      }
      return $values;
    }
  }
  
  public function image ($uri, $eject='') {
    global $bp, $ci, $page;
    $html = '';
    if (!$resource = $this->resource($uri)) return $html;
    $types = array('jpg', 'gif', 'png', 'ico');
    if (!$page->plugin('Image', 'Magick')) array_pop($types);
    if (!in_array($resource['ext'], $types)) return $html;
    $form = $page->plugin('Form', 'name', 'admin_image_resize');
    $form->menu('type', array_combine($types, $types));
    $form->values(array(
      'type' => $resource['ext'],
      'width' => $resource['width'],
      'height' => $resource['height'],
      'quality' => 80
    ));
    $form->validate(
      array('type', 'Type', 'required|inarray[menu]', 'This will convert the image to a different format.'),
      array('width', 'Width', 'required|integer|gte[0]|lte[' . $resource['width'] . ']', 'Set the new width of your image.'),
      array('height', 'Height', 'required|integer|gte[0]|lte[' . $resource['height'] . ']', 'Set the new height of your image.'),
      array('quality', 'Quality', 'required|integer|gte[0]|lte[100]', 'Why not 100%, right? The higher the quality, the bigger the file, the longer it takes to download. 80% seems to be the best compromise between quality and size, but you are free to experiment for yourself.'),
      array('coords')
    );
    if ($form->submitted() && empty($form->errors)) {
      if ($image = $page->plugin('Image', 'uri', $resource['uri'])) {
        if (!empty($form->vars['coords'])) {
          $image->crop($form->vars['coords']);
          $image->resize($form->vars['width'], $form->vars['height'], 'enforce');
        }
        $count = 1;
        while (is_file($resource['path'] . $resource['name'] . '-' . $count . '.' . $form->vars['type'])) $count++;
        $image->save($resource['path'] . $resource['name'] . '-' . $count . '.' . $form->vars['type'], $form->vars['quality']);
      }
      $page->eject($page->url('delete', $eject, 'submitted'));
    }
    $html .= $form->header();
    $html .= $form->field('type', 'select', array('input'=>'col-sm-2'));
    $html .= $form->field('width', 'text', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
    $html .= $form->field('height', 'text', array('append'=>'px', 'input'=>'col-sm-2', 'maxlength'=>4));
    $html .= $form->field('quality', 'text', array('append'=>'%', 'input'=>'col-sm-2', 'maxlength'=>3));
    $html .= $form->field('coords', 'hidden');
    $html .= $form->field(false, '<img id="crop" class="img-responsive" src="' . str_replace(array(BASE_URI, BASE), BASE_URL, $resource['uri']) . '" width="' . $resource['width'] . '" height="' . $resource['height'] . '" alt="">');
    $page->plugin('CDN', 'links', array(
      'imagesloaded/2.1.0/jquery.imagesloaded.js',
      'wordpress/3.8/js/imgareaselect/jquery.imgareaselect.min.js',
      'wordpress/3.8/js/imgareaselect/imgareaselect.css'
    ));
    $page->plugin('jQuery', 'code', '$("#crop").imagesLoaded({done:function(){
      
      var originalWidth = ' . $resource['width'] . ';
      var originalHeight = ' . $resource['height'] . ';
      var ias = $("img#crop").attr("width", $("img#crop").width()).attr("height", $("img#crop").height()).imgAreaSelect({
        instance: true,
        handles: "corners",
        imageWidth: ' . $resource['width'] . ',
        imageHeight: ' . $resource['height'] . ',
        aspectRatio: false,
        onSelectEnd: function(img, selection){
          $("input[name=coords]").val(selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
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
    $html .= $form->submit('Resize');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function resource ($uri) {
    if (preg_match('/\.(jpg|jpeg|gif|png|ico)$/i', $uri) && is_file($uri) && ($dimensions = getimagesize($uri))) {
      list($width, $height, $type) = $dimensions;
      switch ($type) {
        case 1: $type = 'gif'; break;
        case 2: $type = 'jpg'; break;
        case 3: $type = 'png'; break;
        case 17: $type = 'ico'; break;
      }
      if (!is_int($type)) {
        $info = pathinfo($uri);
        return array(
          'uri' => $uri,
          'path' => $info['dirname'] . '/',
          'name' => $info['filename'],
          'ext' => $type,
          'width' => $width,
          'height' => $height
        );
      }
    }
    return false;
  }
  
  private function manage ($path, $files, $main=array(), $form=false, $recursive=false) {
    global $bp, $ci, $page;
    if (count($files) > 200) return '<h4 class="text-center">Sorry, this directory has 200+ files which is more than we can reasonably manage here.</h4>';
    if (($delete = $ci->input->post('delete-file')) && in_array($delete, $files) && !in_array($delete, $main)) {
      if (is_file($path . $delete) && unlink($path . $delete)) exit('success');
      exit('error');
    }
    if ($image = $ci->input->get('image')) {
      $eject = $page->url('delete', '', 'image');
      if (!in_array($image, $files)) $page->eject($eject);
      return $this->image($path . $image, $eject);
    }
    if ($oldname = $ci->input->post('oldname')) {
      $newname = $this->filter($ci->input->post('newname'), $recursive);
      $type = $ci->input->post('type');
      if (!empty($newname) && is_file($path . $oldname . $type) && in_array($oldname . $type, $files) && !in_array($oldname . $type, $main)) {
        if (is_file($path . $newname . $type)) {
          $data = array('success'=>false, 'msg'=>'This file already exists.');
          exit(json_encode($data));
        } else {
          if ($recursive && !is_dir(dirname($path . $newname . $type))) mkdir(dirname($path . $newname . $type), 0755, true);
          rename($path . $oldname . $type, $path . $newname . $type);
          if (($key = array_search($oldname . $type, $files)) !== false) $files[$key] = $newname . $type;
          $data = array('success'=>true, 'newValue'=>$newname);
        }
      } else {
        $data = array('success'=>true, 'newValue'=>$oldname);
      }
    }
    $save = array_flip(preg_grep('/\.(js|css|less|tpl' . (is_admin(1) ? '|php' : null) . ')$/', $files));
    foreach ($save as $file => $uri) $save[$file] = $path . $file;
    $this->save($save, $main);
    $ci->load->helper('number');
    $images = $links = array();
    $url = str_replace(array(BASE_URI, BASE), BASE_URL, $path);
    foreach ($files as $file) {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $view = $bp->button('xs link', 'view ' . $bp->icon('new-window'), array('href'=>$url . $file, 'target'=>'_blank'));
      $rename = '<a class="rename-file text-nowrap" href="#" data-name=".' . $ext . '">' . substr($file, 0, -(strlen($ext) + 1)) . '</a>.' . $ext;
      $size = (is_file($path . $file)) ? str_replace('Bytes', 'bytes', byte_format(filesize($path . $file), 1)) : '0 bytes';
      $delete = '<a class="delete-file pull-right" href="#" data-uri="' . $file . '">' . $bp->icon('trash') . '</a>';
      switch ($ext) {
        case 'php':
        case 'tpl':
          $view = '';
          if (in_array($file, $main)) {
            $rename = $file;
            $delete = '';
          }
        case 'css':
        case 'js':
          $edit = $bp->button('xs warning wyciwyg ' . $ext, $bp->icon('pencil') . ' edit', array('href'=>'#', 'data-retrieve'=>$file, 'data-file'=>$file));
          $files[$ext][] = array($view, $edit, $rename, $size, $delete);
          break;
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'png':
        case 'ico':
          if ($dimensions = getimagesize($path . $file)) {
            $view = '<a href="' . $url . $file . '" target="_blank"><img src="' . $url . $file . '#~50x50" class="img-responsive"></a>';
            $edit = $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'image', $file)));
            $files['images'][] = array($view, $edit, $rename . '|' . $dimensions[0] . 'x' . $dimensions[1], $size, $delete);
          }
          break;
        default:
          $files['links'][] = array($view, '', $rename, $size, $delete);
          break;
      }
    }
    $html = '';
    if (!empty($files)) {
      $html .= $bp->table->open('class=table responsive striped condensed');
      foreach (array('php'=>'PHP', 'tpl'=>'Smarty', 'css'=>'CSS', 'js'=>'JavaScript', 'images'=>'Images', 'links'=>'Links') as $type => $name) {
        if (isset($files[$type])) {
          $html .= $bp->table->head();
          $html .= $bp->table->cell('colspan=6|style=padding-top:10px; padding-bottom:10px;', $name);
          foreach ($files[$type] as $values) {
            list($link, $edit, $file, $size, $delete) = $values;
            $html .= $bp->table->row();
            $html .= $bp->table->cell('class=col-sm-1', $link);
            $html .= $bp->table->cell('class=col-sm-1', $edit);
            if ($type == 'images') {
              list($file, $dimensions) = explode('|', $file);
              $html .= $bp->table->cell('class=text-nowrap', $file);
              $html .= $bp->table->cell('style=width:100px; text-align:center;', $dimensions);
            } else {
              $html .= $bp->table->cell('colspan=2|class=text-nowrap', $file);
            }
            $html .= $bp->table->cell('style=width:100px; text-align:center;|class=text-nowrap', $size);
            $html .= $bp->table->cell('style=width:30px;', $delete);
          }
        }
      }
      $html .= $bp->table->close();
    }
    if ($oldname) {
      $data['html'] = $ci->filter_links($html);
      exit(json_encode($data));
    }
    $files = $html;
    if ($form) {
      $html = '';
      $ext = $form;
      $form = $page->plugin('Form', 'name', 'admin_file_upload');
      if (!empty($ext['images']) && empty($ext['files']) && empty($ext['resources'])) {
        $form->upload('upload[]', 'Images', $ext['images'], array('filesize'=>10, 'info'=>'Upload additional images that you would like to include.'));
      } else {
        $form->upload('upload[]', 'Upload', $ext['types'], array('filesize'=>10, 'info'=>'Upload additional files that you would like to include.'));
      }
      if (!empty($ext['files']) && $types = explode('|', $ext['files'])) {
        $form->validate('file', 'File', '', 'Enter the name of the file that you would like to create.  The only file types allowed are: .' . implode(', .', $types));
      }
      if ($recursive) $form->validate('directory', 'Directory', '', 'Enter the directory (if any) where you would like your uploaded files to go.');
      if ($form->submitted() && empty($form->errors)) {
        if (!empty($ext['files']) && !empty($form->vars['file'])) {
          $file = $this->filter($form->vars['file'], $recursive);
          if (preg_match('/^.+\.(' . $ext['files'] . ')$/', $file)) {
            if (!is_dir(dirname($path . $file))) mkdir(dirname($path . $file), 0755, true);
            file_put_contents($path . $file, '');
          }
        }
        if (!empty($form->vars['upload'])) {
          $dir = ($recursive && !empty($form->vars['directory'])) ? $this->filter($form->vars['directory'], true) . '/' : '';
          if (!empty($dir) && !is_dir(dirname($path . $this->filter($dir . 'bogus.file', $recursive)))) {
            mkdir(dirname($path . $this->filter($dir . 'bogus.file', $recursive)), 0755, true);
          }
          foreach ($form->vars['upload'] as $uploaded => $file) {
            $file = $this->filter($dir . $file, $recursive);
            rename($uploaded, $path . $file);
            if ($ext['unzip'] && substr($file, -4) == '.zip') {
              $ci->load->library('unzip');
              $ci->unzip->allow(explode('|', $ext['types']));
              $ci->unzip->extract($path . $file, $path . $dir);
              $ci->unzip->close();
              unlink($path . $file);
            }
          }
        }
        $page->eject($form->eject);
      }
      $html .= $form->header();
      if (!empty($ext['files'])) $html .= $form->field('file', 'text');
      if ($recursive) $html .= $form->field('directory', 'text');
      $html .= $form->field('upload[]', 'file');
      $html .= $form->submit();
      $html .= $form->close();
      unset($form);
      $form = $html;
    } else {
      $form = '';
    }
    $page->plugin('CDN', 'links', array(
      'bootstrap.editable/1.5.1/css/bootstrap-editable.min.css',
      'bootstrap.editable/1.5.1/js/bootstrap-editable.min.js'
    ));
    $page->link('<script>function xEditable () {
      $("#admin_manage_files .rename-file").editable({
        pk: "rename",
        type: "text",
        title: "Rename File",
        url: window.location.href,
        savenochange: true,
        ajaxOptions: {dataType:"json"},
        validate: function(value) { if($.trim(value) == "") return "This field is required"; },
        params: function(params) { return {oldname:$(this).text(), newname:params.value, type:params.name}; },
        success: function(response, newValue) {
          if(!response.success) return response.msg;
          $("#admin_manage_files").html(response.html);
          xEditable();
        }
      });
    }</script>');
    $page->plugin('jQuery', 'code', 'xEditable();
      $("#admin_manage_files").on("click", "a.delete-file", function(){
        var file = $(this).data("uri");
        var row = $(this).closest("tr");
        if (confirm("Are you sure you would like to delete this file?")) {
          row.hide();
          $.post(location.href, {"delete-file":file}, function(data){
            if (data != "success") row.show();
          }, "text");
        }
        return false;
      });
    ');
    return $form . '<div id="admin_manage_files">' . $files . '</div>';
  }
  
}

/* End of file Admin_files.php */
/* Location: ./application/libraries/Admin/drivers/Admin_files.php */