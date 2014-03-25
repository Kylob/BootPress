<?php

// crop functionality requires: imgAreaSelect.js and imagesLoaded.js from the jQuery plugin

class Upload {

  private $db;
  private $url = '';
  private $uri = '';
  private $upload = array(); // required for $this->validate() and $this->field()
  
  public function __construct ($upload=array()) {
    global $page;
    $page->plugin('SQLite');
    $this->db = new SQLite(BASE_URI . 'uploads');
    if ($this->db->created) $this->create_tables();
    $this->url = BASE_URL . 'uploads/';
    $this->uri = BASE_URI . 'uploads/';
    if (!is_dir($this->uri)) mkdir($this->uri, 0755, true);
    if (!empty($upload) && isset($upload['name'])) {
      $this->upload['name'] = $upload['name'];
      $this->upload['extensions'] = (isset($upload['extensions'])) ? array_map("strtolower", $upload['extensions']) : array();
      $this->upload['size'] = (isset($upload['size'])) ? (int) $upload['size'] : self::bytes(ini_get('upload_max_filesize'));
      $this->upload['limit'] = (isset($upload['limit'])) ? (int) $upload['limit'] : false;
      $this->upload['crop'] = (isset($upload['crop'])) ? $upload['crop'] : false;
      $this->upload['aspectRatio'] = (isset($upload['aspectRatio'])) ? $upload['aspectRatio'] : '1:1';
      $this->upload['minWidth'] = (isset($upload['minWidth'])) ? $upload['minWidth'] : 300;
    }
  }
  
  public function get ($info) {
    return (isset($this->upload[$info])) ? $this->upload[$info] : '';
  }
  
  public function info ($ids) {
    $ids = array_values((array) $ids); // make sure they are all indexed numerically for the $order below
    $info = array();
    if (empty($ids)) return $info;
    $case = array();
    foreach ($ids as $order => $id) $case[] = "WHEN {$id} THEN {$order}";
    $order = 'ORDER BY CASE id ' . implode(' ', $case) . ' END';
    $this->db->query('SELECT id, code, ext, name, size, width, height, thumb, crop FROM uploads WHERE id IN(' . implode(',', $ids) . ') ' . $order);
    while ($row = $this->db->fetch('assoc')) {
      $row['url'] = $this->url . $row['code'] . $row['id'] . '.' . $row['ext'];
      $row['uri'] = $this->uri . $row['code'] . $row['id'] . '.' . $row['ext'];
      $info[$row['id']] = $row;
    }
    return $info;
  }
  
  public function keep ($ids) {
    $ids = (array) $ids;
    if (!empty($ids)) $this->db->exec('UPDATE uploads SET keep = 1 WHERE id IN(' . implode(',', $ids) . ')');
  }
  
  public function delete ($ids=array()) {
    $ids = (array) $ids;
    if (empty($ids)) {
      $this->db->query("SELECT id, code, ext FROM uploads WHERE date < datetime('now', '-1 day') AND keep = 0");
      while (list($id, $code, $ext) = $this->db->fetch('row')) {
        $ids[] = $id;
        if (file_exists($this->uri . $code . $id . '.' . $ext)) unlink($this->uri . $code . $id . '.' . $ext);
      }
    } else {
      $this->db->query('SELECT id, code, ext FROM uploads WHERE id IN(' . implode(',', $ids) . ')');
      while (list($id, $code, $ext) = $this->db->fetch('row')) {
        unlink($this->uri . $code . $id . '.' . $ext);
      }
      $this->db->exec('DELETE FROM uploads WHERE id IN(' . implode(',', $ids) . ')');
    }
  }
  
  public function validate ($form) {
    $uploads = array();
    if (empty($this->upload)) return $uploads;
    if (isset($_GET['submitted']) && $_GET['submitted'] == $form) {
      if (isset($_GET['qqfile']) || isset($_FILES['qqfile'])) {
        $result = $this->upload_file();
        $result['html'] = $this->table($result);
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        exit;
      } elseif (isset($_POST['x1']) && isset($_POST['y1']) && isset($_POST['x2']) && isset($_POST['y2']) && isset($_POST['file']) && file_exists($this->uri . $_POST['file'])) {
        $crop = array('x1'=>(int) $_POST['x1'], 'y1'=>(int) $_POST['y1'], 'x2'=>(int) $_POST['x2'], 'y2'=>(int) $_POST['y2']);
        $image = $this->image($_POST['file'], $crop);
        echo $image['thumb'];
        exit;
      } elseif (isset($_POST[$this->upload['name']]) && is_array($_POST[$this->upload['name']])) {
        foreach ($_POST[$this->upload['name']] as $file) {
          if (file_exists($this->uri . $file)) $uploads[] =  $this->file_parts($file, 'id');
        }
        $this->delete(); // get rid of any floaters
        $uploads = $this->info($uploads);
      }
    }
    return $uploads;
  }
  
  public function field ($options=array()) {
    global $page;
    $html = '';
    if (empty($this->upload)) return $html;
    #-- Plugin jQuery --#
    
    $get = $page->plugin('info');
    $page->link($get['url'] . 'js/fineuploader.js');
    $page->link($get['url'] . 'js/oneFineUploader.js');
    $jquery = '$("#' . $this->upload['name'] . '").show().oneFineUploader({
      "limit": "' . $this->upload['limit'] . '",
      "allowedExtensions": "' . implode(',', $this->upload['extensions']) . '",
      "sizeLimit": "' . $this->upload['size'] . '"
    });';
    $jquery .= '$("#' . $this->upload['name'] . 'Messages").sortable({items:"div"});';
    if ($this->upload['crop']) {
      $page->link($get['url'] . 'js/modalCropImage.js');
      $jquery .= '$("body").on("click", "#' . $this->upload['name'] . 'Messages span[class*=glyphicon-picture]", function(){
        var img = $(this).siblings("img");
        img.modalCropImage(img.data("url"), $(this).closest("form").attr("action"), img.data("options"));
      });';
    }
    $jquery .= '$("body").on("click", "#' . $this->upload['name'] . 'Messages span[class*=glyphicon-trash]", function(){
      var upload = $(this).closest("div[id^=' . $this->upload['name'] . ']");
      if (upload.hasClass("alert-success")) $("#' . $this->upload['name'] . 'Upload").css("display", "block");
      upload.remove();
    });';
    $page->plugin('jQuery', array('plugin'=>array('ui', 'imagesloaded', 'imgareaselect', 'bootbox'), 'code'=>$jquery));
    #-- Upload Field --#
    $html .= '<div id="' . $this->upload['name'] . '" style="display:none;" title="Click to Upload">';
      $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control" style="cursor:pointer;">';
        $html .= '<span class="input-group-btn">';
          $html .= '<button class="btn btn-success"><span class="glyphicon glyphicon-upload"></span> Upload</button>';
        $html .= '</span>';
      $html  .= '</div>';
    $html .= '</div>';
    #-- Upload Messages --#
    $html .= '<div id="' . $this->upload['name'] . 'Messages">';
    if (isset($options['preselect'])) {
      $data = $this->info($options['preselect']);
      foreach ($data as $id => $info) {
        $data[$id]['success'] = true;
        $data[$id]['file'] = $info['code'] . $info['id'] . '.' . $info['ext'];
      }
      foreach ($options['preselect'] as $id) {
        $html .= '<div id="' . $this->upload['name'] . $id . 'pre" class="alert alert-success" style="margin:10px 0 0; padding:8px;">';
          $html .= $this->table($data[$id]);
          $html .= '<input type="hidden" name="' . $this->upload['name'] . '[]" value="' . $data[$id]['file'] . '" />';
        $html .= '</div>';
      }
    }
    $html .= '</div>';
    return $html;
  }
  
  private function upload_file () { // used in $this->validate()
    $data = array();
    if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') === 0) { // Handle via regular form post using the $_FILES array
      $upload = 'post';
      $data['name'] = $_FILES['qqfile']['name'];
      $data['size'] = $_FILES['qqfile']['size'];
    } else { // Handle via XMLHttpRequest (XHR)
      $upload = 'xhr';
      $data['name'] = $_GET['qqfile'];
      if (!isset($_SERVER['CONTENT_LENGTH'])) {
        $data['error'] = 'Getting content length is not supported.';
        return $data;
      }
      $data['size'] = (int) $_SERVER['CONTENT_LENGTH'];
    }
    if ($data['size'] == 0) $data['error'] = 'File is empty';
    if ($data['size'] > $this->upload['size']) $data['error'] = 'File is too large.';
    $data['ext'] = strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    if (!empty($this->upload['extensions']) && !in_array($data['ext'], $this->upload['extensions'])) {
      $data['error'] = 'File has an invalid extension, it should be one of ' . implode(', ', $this->upload['extensions']) . '.';
    } elseif (empty($data['ext'])) {
      $data['error'] = 'File has no extension.';
    }
    if (!is_writable($this->uri)) $data['error'] = 'Server error. Upload directory isn\'t writable.';
    if (self::bytes(ini_get('post_max_size')) < $this->upload['size'] || self::bytes(ini_get('upload_max_filesize')) < $this->upload['size']) {
      $size = max(1, $this->upload['size'] / 1024 / 1024) . 'M';
      $data['error'] = 'Increase post_max_size and upload_max_filesize to ' . $size . '.';
    }
    if (isset($data['error'])) return $data;
    $code = md5(uniqid()); // 32 chars long
    $id = $this->db->insert('uploads', array('code'=>$code, 'ext'=>$data['ext'], 'name'=>$data['name'], 'size'=>$data['size']));
    $data['file'] = $code . $id . '.' . $data['ext'];
    if ($upload == 'post') {
      if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $this->uri . $data['file'])) {
        switch ($_FILES['qqfile']['error']) {
          case 'UPLOAD_ERR_OK':
          case 0: $data['error'] = 'The file uploaded with success, but had an unknown error.'; break;
          case 'UPLOAD_ERR_INI_SIZE':
          case 1: $data['error'] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.'; break;
          case 'UPLOAD_ERR_FORM_SIZE':
          case 2: $data['error'] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'; break;
          case 'UPLOAD_ERR_PARTIAL':
          case 3: $data['error'] = 'The uploaded file was only partially uploaded.'; break;
          case 'UPLOAD_ERR_NO_FILE':
          case 4: $data['error'] = 'No file was uploaded.'; break;
          case 'UPLOAD_ERR_NO_TMP_DIR':
          case 6: $data['error'] = 'Missing a temporary folder.'; break;
          case 'UPLOAD_ERR_CANT_WRITE':
          case 7: $data['error'] = 'Failed to write file to disk.'; break;
          case 'UPLOAD_ERR_EXTENSION':
          case 8: $data['error'] = 'A PHP extension stopped the file upload.'; break;
          default: $data['error'] = 'Unknown File Error.'; break;
        }
        return $data;
      }
    } else { // $upload == 'xhr'
      $input = fopen("php://input", "r");
      $temp = tmpfile();
      $size = stream_copy_to_stream($input, $temp);
      fclose($input);
      if ($size != $data['size']) {
        $data['error'] = 'Could not save uploaded file. The upload was cancelled, or server error encountered.';
        return $data;
      }
      $target = fopen($this->uri . $data['file'], "w");
      fseek($temp, 0, SEEK_SET);
      stream_copy_to_stream($temp, $target);
      fclose($target);
    }
    $data['success'] = true;
    return $data;
  }
  
  private function table ($data) { // used in $this->validate() and $this->field(); data should include success or error, name, ext, size, file
    $html = '<table style="width:100%"><tr>';
    $result = '';
    if (isset($data['success'])) {
      $result .= '<span class="glyphicon glyphicon-ok"></span>';
      $size = self::convert($data['size']);
      if (in_array($data['ext'], array('jpg', 'jpeg', 'gif', 'png', 'ico'))) {
        if (!isset($data['thumb'])) $data = array_merge($data, $this->image($data['file']));
        $options = array();
        if ($this->upload['crop']) {
          $options['width'] = $data['width'];
          $options['height'] = $data['height'];
          $options['file'] = $data['file'];
          $options['aspectRatio'] = $this->upload['aspectRatio'];
          $options['minWidth'] = $this->upload['minWidth'];
        }
        foreach ($options as $key => $value) $options[$key] = '"' . $key . '":"' . $value . '"';
        $result .= '<img src="' . $data['thumb'] . '" data-url="' . $this->url . $data['file'] . '" data-options=\'{' . implode(',', $options) . '}\' style="margin:0 15px;" />';
        if ($this->upload['crop']) {
          $result .= '<span class="glyphicon glyphicon-picture" title="Crop Image" style="cursor:pointer;"></span>';
        }
        $size .= ' (' . $data['width'] . ' x ' . $data['height'] . ' px)';
      }
      $html .= '<td>' . $result . '<span style="margin:0 15px;">&ldquo;' . $data['name'] . '&rdquo;</span>' . $size . '</td>';
      $html .= '<td align="right"><span class="glyphicon glyphicon-trash" title="Delete File" style="cursor:pointer;"></span></td>';
    } else { // error
      $result .= '<span class="glyphicon glyphicon-exclamation-sign"></span>';
      $result .= '<span style="margin:0 15px;">&ldquo;' . $data['name'] . '&rdquo;</span>';
      $result .= '<strong>Error</strong>: ' . $data['error'];
      $html .= '<td>' . $result . '</td>';
      $html .= '<td align="right" style="width:20px;"><span class="glyphicon glyphicon-trash" title="Delete File" style="cursor:pointer;"></span></td>';
    }
    $html .= '</tr></table>';
    return $html;
  }
  
  private function image ($file, $crop=array()) { // used in $this->validate() and $this->table()
    global $page;
    $info = array();
    $src = $this->uri . $file;
    $thumb = $this->uri . 'thumb.' . $file;
    if ($page->plugin('ImageMagick')) {
      $image = new ImageMagick($src);
    } else {
      $image = new Image($src);
    }
    if ($image->retrieved()) {
      if ($image->get('type') == 'ico') $thumb = substr($thumb, 0, -3) . 'png';
      $update = array();
      $update['width'] = $image->get('width');
      $update['height'] = $image->get('height');
      if (!empty($crop)) {
        $update['crop'] = implode(',', array($crop['x1'], $crop['y1'], $crop['x2'], $crop['y2']));
        $image->crop($update['crop']);
      }
      $image->resize(50, 50);
      $image->save($thumb);
      $update['thumb'] = $image->data_uri();
      $this->db->update('uploads', $update, 'id', $this->file_parts($file, 'id'));
      unlink($thumb);
      $info = $update;
    }
    unset($image, $info['crop']);
    return $info;
  }
  
  private function file_parts ($file, $part='') {
    $path = pathinfo($file);
    $file = array();
    $file['ext'] = $path['extension'];
    $file['code'] = substr($path['filename'], 0, 32);
    $file['id'] = substr($path['filename'], 32);
    return (isset($file[$part])) ? $file[$part] : array_reverse($file); // ie. id, code, ext
  }
  
  private function create_tables () {
    $table = 'uploads';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['code'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['ext'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['name'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['size'] = 'INTEGER NOT NULL DEFAULT 0'; // bytes
    $columns['width'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['height'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['thumb'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['crop'] = 'TEXT NOT NULL DEFAULT ""'; // 	x1,y1,x2,y2
    $columns['keep'] = 'INTEGER NOT NULL DEFAULT 0'; // ie. false
    $columns['date'] = 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
    $this->db->create($table, $columns);
    $this->db->index($table, 1, array('date', 'keep'));
  }
  
  public static function bytes ($str) { // converts php ini values
    $bytes = (int) $str;
    $str = str_split(strtolower($str));
    $size = array_pop($str);
    if ($size == 'b') $size = array_pop($str);
    switch ($size) {
      case 'p': $bytes *= 1024; // pitabytes
      case 't': $bytes *= 1024; // terabytes
      case 'g': $bytes *= 1024; // gigabytes
      case 'm': $bytes *= 1024; // megabytes
      case 'k': $bytes *= 1024; // kilobytes
    }
    return $bytes;
  }
  
  public static function convert ($bytes) {
    if ($bytes > 1125899906842624) {
      $bytes = round($bytes * .00000000000000088818, 2) . ' PB';
    } elseif ($bytes > 1099511627776) {
      $bytes = round($bytes * .00000000000090949, 2) . ' TB';
    } elseif ($bytes > 1073741824) {
      $bytes = round($bytes * .00000000093132, 2) . ' GB';
    } elseif ($bytes > 1048576) {
      $bytes = round($bytes * .00000095367, 1) . ' MB';
    } elseif ($bytes > 1024) {
      $bytes = round($bytes * .000976563) . ' kB';
    } else {
      $bytes .= ' B';
    }
    return $bytes;
  }
  
  public static function mime_type ($extension) {
    $single = (!is_array($extension)) ? true : false;
    if (!is_array($extension)) $extension = array($extension);
    $mime = array();
    foreach ($extension as $type) {
      switch (strtolower($type)) {
        #-- Web --#
        case 'js':
          $mime[] = 'application/javascript';
          $mime[] = 'application/x-javascript';
          $mime[] = 'text/javascript';
          break;
        case 'json': $mime[] = 'application/json'; break;
        case 'css': $mime[] = 'text/css'; break;
        case 'htm':
        case 'html': $mime[] = 'text/html'; break;
        #-- Images --#
        case 'jpeg':
        case 'jpe':
        case 'jpg':
          $mime[] = 'image/jpeg';
          $mime[] = 'image/jpg';
          $mime[] = 'image/pjpeg';
          break;
        case 'gif': $mime[] = 'image/gif'; break;
        case 'png': $mime[] = 'image/png'; break;
        case 'bmp':
          $mime[] = 'image/bmp';
          $mime[] = 'image/x-windows-bmp';
          $mime[] = 'image/x-ms-bmp';
          break;
        case 'ico':
          $mime[] = 'image/x-icon'; // supported
          $mime[] = 'image/vnd.microsoft.icon'; // official
          break;
        case 'tif':
        case 'tiff':
          $mime[] = 'image/tiff';
          $mime[] = 'image/x-tiff';
          $mime[] = 'image/tiff-fx';
          break;
        #-- Text --#
        case 'rtf':
          $mime[] = 'text/rtf';
          $mime[] = 'application/rtf';
          $mime[] = 'application/x-rtf';
          $mime[] = 'text/richtext';
          break;
        case 'csv':
          $mime[] = 'text/csv';
          $mime[] = 'text/comma-separated-values';
          $mime[] = 'application/csv';
          break;
        case 'tab':
        case 'tsv': $mime[] = 'text/tab-separated-values'; break;
        case 'xml':
          $mime[] = 'application/xml';
          $mime[] = 'text/xml';
          break;
        case 'txt':
          $mime[] = 'text/plain';
          $mime[] = 'plain/text';
          break;
        case 'ods': $mime[] = 'application/x-vnd.oasis.opendocument.spreadsheet'; break;
        case 'odt': $mime[] = 'application/vnd.oasis.opendocument.text'; break;
        case 'sxw': $mime[] = 'application/vnd.sun.xml.writer'; break;
        #-- GDocs --#
        case 'doc': $mime[] = 'application/msword'; break;
        case 'docx': $mime[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
        case 'xla':
        case 'xlb':
        case 'xlc':
        case 'xld':
        case 'xlk':
        case 'xll':
        case 'xlm':
        case 'xlt':
        case 'xlv':
        case 'xlw':
        case 'xls':
          $mime[] = 'application/vnd.ms-excel';
          $mime[] = 'application/excel';
          $mime[] = 'application/msexcel';
          $mime[] = 'application/x-excel';
          $mime[] = 'application/x-msexcel';
          $mime[] = 'application/octet-stream';
          break;
        case 'xlsx': $mime[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
        case 'pps':
        case 'ppt':
          $mime[] = 'application/vnd.ms-powerpoint';
          $mime[] = 'application/x-mspowerpoint';
          $mime[] = 'application/powerpoint';
          $mime[] = 'application/mspowerpoint';
          break;
        case 'pptx': $mime[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation'; break;
        case 'pdf': $mime[] = 'application/pdf'; break;
        case 'pages': $mime[] = ''; break;
        case 'ai':
        case 'ps':
        case 'eps': $mime[] = 'application/postscript'; break; // PostScript
        case 'psd': $mime[] = 'application/octet-stream'; break; // Photoshop
        case 'dxf': $mime[] = 'application/dxf'; break; // AutoCAD
        case 'svg': $mime[] = 'image/svg+xml'; break; // Scalable Vector Graphics
        case 'ttf': $mime[] = 'application/octet-stream'; break; // True Type
        case 'xps': $mime[] = 'application/vnd.ms-xpsdocument'; break; // XML Paper Specs
        case 'zip':
          $mime[] = 'application/zip';
          $mime[] = 'application/x-zip-compressed';
          $mime[] = 'multipart/x-zip';
          $mime[] = 'application/x-compressed';
          break;
        case 'rar':
          $mime[] = 'application/x-rar-compressed';
          $mime[] = 'application/octet-stream';
          break;
        #-- Archives --#
        case 'tar': $mime[] = 'application/x-tar'; break;
        case 'gz': $mime[] = 'application/x-gzip'; break;
        #-- Audio --#
        case 'mid':
        case 'midi':
          $mime[] = 'audio/midi';
          $mime[] = 'audio/x-mid';
          $mime[] = 'application/x-midi';
          $mime[] = 'music/crescendo';
          $mime[] = 'x-music/x-midi';
          break;
        case 'wav':
          $mime[] = 'audio/vnd.wav';
          $mime[] = 'audio/wav';
          $mime[] = 'audio/wave';
          $mime[] = 'audio/x-wav';
          break;
        case 'wma': $mime[] = 'audio/x-ms-wma'; break;
        case 'mpga':
        case 'mp3':
          $mime[] = 'audio/mpeg';
          $mime[] = 'audio/x-mpeg';
          $mime[] = 'audio/mp3';
          $mime[] = 'audio/x-mp3';
          $mime[] = 'audio/mpeg3';
          $mime[] = 'audio/x-mpeg-3';
          $mime[] = 'audio/mpg';
          $mime[] = 'audio/x-mpeg';
          $mime[] = 'audio/x-mpegaudio';
          break;
        case 'ra':
        case 'ram':
          $mime[] = 'audio/vnd.rn-realaudio';
          $mime[] = 'audio/x-pn-realaudio';
          break;
        case 'aiff':
        case 'aif':
          $mime[] = 'audio/x-aiff';
          $mime[] = 'audio/aiff';
          break;
        #-- Video --#
        case 'rm':
          $mime[] = 'application/vnd.rn-realmedia';
          $mime[] = 'audio/x-pn-realaudio';
          break;
        case 'avi':
          $mime[] = 'video/msvideo';
          $mime[] = 'video/x-msvideo';
          $mime[] = 'video/avi';
          $mime[] = 'application/x-troff-msvideo';
          break;
        case 'wmv': $mime[] = 'video/x-ms-wmv'; break;
        case 'mpe':
        case 'mpg':
        case 'mpeg': $mime[] = 'video/mpeg'; break;
        case 'mp4': $mime[] = 'video/mp4'; break;
        case 'mov': $mime[] = 'video/quicktime'; break;
        case 'swf': $mime[] = 'application/x-shockwave-flash'; break;
        case 'flv': $mime[] = 'video/x-flv'; break;
      } // end switch
    } // end foreach
    return ($single) ? array_shift($mime) : array_unique($mime);
  }
  
}

?>