<?php

class Image {

  public $magick;
  public $type;
  public $width;
  public $height;
  public $source = ''; // the original file uri
  public $saved = ''; // the last saved file uri
  private $crop = ''; // -crop command line
  private $cropped = false; // an array of data if image is cropped
  private $original = false; // to reset the coords as needed
  private $resize = false; // an array(width, height) if image is resized
  
  public function __construct ($uri) {
    if (file_exists($uri)) {
      $this->magick = (function_exists('ImageMagick')) ? true : false;
      list($width, $height, $type) = @getimagesize($uri);
      switch ($type) {
        case 1: $this->type = 'gif'; break; // do not crop and resize well from jpegs using ImageMagick
        case 2: $this->type = 'jpg'; break;
        case 3: $this->type = 'png'; break;
        case 17: if ($this->magick) $this->type = 'ico'; break; // 255 pixels is the max width and height
      }
      if ($this->type) { // continue
        $this->width = $width;
        $this->height = $height;
        $this->source = $uri;
      }
    }
  }
  
  public function crop ($coords='', $user=true) {
    if (!is_array($coords)) $coords = explode(',', $coords);
    if (count($coords) == 4) {
      list($x1, $y1, $x2, $y2) = $coords;
      $width = $x2 - $x1;
      $height = $y2 - $y1;
      // See http://www.imagemagick.org/Usage/crop/#crop_repage for why gif's are screwed up if you don't +repage
      $this->crop = " -crop {$width}x{$height}+{$x1}+{$y1} +repage";
      $this->cropped = array('width'=>$width, 'height'=>$height, 'x1'=>$x1, 'y1'=>$y1, 'x2'=>$x2, 'y2'=>$y2);
      if ($user) $this->original = $coords;
    }
  }
  
  // Returns the entire image within the width and height maximum boundaries
  public function constrain ($width, $height=null) {
    $scale = $this->max('width') / $this->max('height');
    if (empty($height)) $height = round($this->max('height') / $this->max('width') * $width); // ie. keep the same aspect ratio
    if ($scale <= ($width / $height)) { // keep the height
      $width = $scale * $height;
    } else { // keep the width
      $height = $width / $scale;
    }
    return $this->resize($width, $height);
  }
  
  // Returns a cropped (as necessary) image of width and height dimensions
  // Enforce will enlarge (and NOT distort) the image if smaller than the original
  public function resize ($width, $height=null, $enforce=false) {
    if (empty($height)) $height = round($this->max('height') / $this->max('width') * $width); // ie. keep the same aspect ratio
    $requested = array($width, $height);
    $scale = $width / $height;
    if ($width > $this->max('width')) {
      $width = $this->max('width');
      $height = round($width / $scale);
    }
    if ($height > $this->max('height')) {
      $height = $this->max('height');
      $width = round($height * $scale);
    }
    $x = $y = 0;
    if ($scale <= ($this->max('width') / $this->max('height'))) { // crop the width, keep the height
      $factor = $this->max('height') * $scale;
      $x = round(($this->max('width') - $factor) / 2);
      if ($this->cropped) {
        $x += $this->cropped['x1'];
        $y = $this->cropped['y1'];
      }
      $this->crop(array($x, $y, $x+$factor, $y+$this->max('height')), false);
    } else { // crop the height, keep the width
      $factor = $this->max('width') / $scale;
      $y = round(($this->max('height') - $factor) / 2);
      if ($this->cropped) {
        $y += $this->cropped['y1'];
        $x = $this->cropped['x1'];
      }
      $this->crop(array($x, $y, $x+$this->max('width'), $y+$factor), false);
    }
    if ($enforce !== false) list($width, $height) = $requested;
    $this->resize = array($width, $height);
  }
  
  // Returns a cropped (as necessary) square image of pixels width and height
  public function square ($pixels, $enforce=false) {
    return $this->resize($pixels, $pixels, $enforce);
  }
  
  
  /*
  public function resize ($width, $height=0, $enforce=false) {
    if (empty($height)) $height = round($this->max('height') / $this->max('width') * $width); // ie. keep the same aspect ratio
    if ($enforce === false) { // We want to constrain proportions within $width and $height
      $use = (($width / $height) <= ($this->max('width') / $this->max('height'))) ? 'width' : 'height';
      $max_dimension = ($use == 'width') ? $width : $height;
      $ratio = ($this->max('width') >= $this->max('height')) ? $max_dimension / $this->max('width') : $max_dimension / $this->max('height');
      if($this->max('width') > $max_dimension || $this->max('height') > $max_dimension) {
        $width = round($this->max('width') * $ratio);
        $height = round($this->max('height') * $ratio);
      } else { // the resize is larger than the original
        $width = $this->max('width');
        $height = $this->max('height');
      }
    } // Otherwise we want exactly $width and $height
    $this->resize = array($width, $height);
  }
  
  public function square ($pixels, $enforce=false) {
    $requested = $pixels;
    $width = $this->max('width');
    $height = $this->max('height');
    if ($pixels > $width || $pixels > $height) $pixels = min($width, $height);
    $x = $y = 0;
    if ($width > $height) { // keep the height
      $x = round(($width - $height) / 2);
      if ($this->cropped) {
        $x += $this->cropped['x1'];
        $y = $this->cropped['y1'];
      }
      $this->crop(array($x, $y, $x+$height, $y+$height), false);
    } else { // keep the width
      $y = round(($height - $width) / 2);
      if ($this->cropped) {
        $y += $this->cropped['y1'];
        $x = $this->cropped['x1'];
      }
      $this->crop(array($x, $y, $x+$width, $y+$width), false);
    }
    if ($enforce !== false) $pixels = $requested;
    $this->resize($pixels, $pixels, 'enforce');
  }
  */
  
  public function save ($uri, $quality=80) {
    if (empty($this->source)) return false;
    if (!is_dir(dirname($uri))) mkdir(dirname($uri), 0755, true);
    $saved = false;
    if ($this->magick) {
      $convert = $this->crop;
      if ($this->resize) {
        list($width, $height) = $this->resize;
        $convert .= " -resize {$width}x{$height}!";
      }
      $image = ImageMagick('convert', "\"{$this->source}\"{$convert} -quality {$quality} -strip -verbose \"{$uri}\"");
      if ($image['return'] == 0) $saved = true;
    } elseif ($this->type != 'ico') { // in case $this->magick was set to false
      if (!empty($this->resize)) {
        list($width, $height) = $this->resize;
      } elseif (!empty($this->cropped)) {
        list($width, $height) = array_values($this->cropped);
      } else {
        $width = $this->width;
        $height = $this->height;
      }
      $image = imagecreatetruecolor($width, $height);
      if ($this->type == 'png') {
        imagealphablending ($image, false);
        imagesavealpha ($image, true);
        $transparent = imagecolorallocatealpha ($image, 255, 255, 255, 127);
        imagefilledrectangle ($image, 0, 0, $width, $height, $transparent);
      } else {
        $white = imagecolorallocate ($image, 255, 255, 255);
        imagefilledrectangle ($image, 0, 0, $width, $height, $white);
      }
      switch ($this->type) {
        case 'gif': $source = imagecreatefromgif($this->source); break;
        case 'jpg': $source = imagecreatefromjpeg($this->source); break;
        case 'png': $source = imagecreatefrompng($this->source); break;
      }
      $src_x = ($this->cropped) ? $this->cropped['x1'] : 0;
      $src_y = ($this->cropped) ? $this->cropped['y1'] : 0;
      imagecopyresampled ($image, $source, 0, 0, $src_x, $src_y, $width, $height, $this->max('width'), $this->max('height'));
      switch (substr($uri, strrpos($uri, '.') + 1)) {
        case 'gif': $saved = imagegif($image, $uri); break;
        case 'jpeg':
        case 'jpg': $saved = imagejpeg($image, $uri, $quality); break;
        case 'png':
          if ($quality >= 90) {
            $quality = 0;
          } else {
            $quality = abs(round($quality / 10) - 9);
          }
          $saved = imagepng ($image, $uri, $quality);
          break;
      }
      unset($image, $source);
    }
    if ($saved) $this->saved = $uri;
    $this->crop($this->original ? $this->original : array(0, 0, $this->width, $this->height), false);
    return $saved;
  }
  
  private function max ($dimension) {
    switch ($dimension) {
      case 'width': return ($this->cropped) ? $this->cropped['width'] : $this->width; break;
      case 'height': return ($this->cropped) ? $this->cropped['height'] : $this->height; break;
    }
  }
  
  static function data ($uri) {
    if (!file_exists($uri)) return '';
    $type = substr($uri, strrpos($uri, '.') + 1);
    $mime = ($type == 'jpg') ? 'jpeg' : $type;
    return "data:{$mime};base64," . base64_encode(file_get_contents($uri));
  }
  
}

?>