<?php

class Image {

  private $type;
  private $width;
  private $height;
  private $data = false; // the original - never changes
  private $crop = false; // applied to $this->image
  private $image; // the currently modified image
  private $location; // either the original or last saved location
  
  public function __construct ($image) { // uri or url
    $this->location = str_replace(' ', '%20', trim($image));
    list($width, $height, $type, $attr) = @getimagesize($this->location);
    switch ($type) {
      case 1: $this->type = 'gif'; break;
      case 2: $this->type = 'jpg'; break;
      case 3: $this->type = 'png'; break;
      case 17: $this->type = 'ico'; break;
    }
    if (!empty($this->type)) { // continue
      $this->width = $width;
      $this->height = $height;
      $this->data = file_get_contents($this->location);
    }
  }
  
  public function get ($var) {
    return (in_array($var, array('type', 'width', 'height'))) ? $this->$var : '';
  }
  
  public function retrieved () { // perform this sanity check before anything else
    return ($this->data !== false) ? true : false;
  }
  
  public function convert ($type) {
    if (in_array($type, array('jpg', 'gif', 'png'))) $this->type = $type;
  }
  
  public function crop ($coords='') {
    $this->crop = false;
    if (!is_array($coords)) $coords = explode(',', $coords);
    if (count($coords) == 4) {
      list($x1, $y1, $x2, $y2) = $coords;
      $width = $x2 - $x1;
      $height = $y2 - $y1;
      $this->crop = array('width'=>$width, 'height'=>$height, 'x1'=>$x1, 'y1'=>$y1, 'x2'=>$x2, 'y2'=>$y2);
    }
  }
  
  public function resize ($width, $height='') { // constrains proportions within $width and $height
    if ($this->crop) { // basically all of the math should have been done by now, so ...
      if (empty($height)) $height = $this->crop['height'] / $this->crop['width'] * $width; // ie. keep the same aspect ratio
    } else {
      if (empty($height)) $height = $width * .8; // ie. 80% of the width
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
    }
    $src_x = ($this->crop) ? $this->crop['x1'] : 0;
    $src_y = ($this->crop) ? $this->crop['y1'] : 0;
    return $this->resample($width, $height, $src_x, $src_y, $this->max('width'), $this->max('height'));
  }
  
  public function square ($pixels) { // generates a square image of $pixels size
    if ($this->max('width') < $pixels || $this->max('height') < $pixels) $pixels = min($this->max('width'), $this->max('height'));
    if ($this->max('height') > $this->max('width')) { // keep the width
      $src_x = ($this->crop) ? $this->crop['x1'] : 0;
      $src_y = round(($this->max('height') - $this->max('width')) / 2);
      if ($this->crop) $src_y += $this->crop['y1'];
      $src_w = $src_h = $this->max('width');
    } else { // keep the height
      $src_x = round(($this->max('width') - $this->max('height')) / 2);
      if ($this->crop) $src_x += $this->crop['x1'];
      $src_y = ($this->crop) ? $this->crop['y1'] : 0;
      $src_w = $src_h = $this->max('height');
    }
    return $this->resample($pixels, $pixels, $src_x, $src_y, $src_w, $src_h);
  }
  
  public function save ($location, $quality=80) {
    $this->location = $location;
    if (!is_dir(dirname($location))) mkdir(dirname($location), 0755, true);
    if (!empty($this->image)) {
      if ($this->type == 'jpg') {
        return imagejpeg ($this->image, $location, $quality);
      } elseif ($this->type == 'gif') {
        return imagegif ($this->image, $location);
      } elseif ($this->type == 'png') {
        if ($quality >= 90) {
          $quality = 0;
        } else {
          $quality = abs(round($quality / 10) - 9);
        }
        return imagepng ($this->image, $location, $quality);
      }
    } elseif (!empty($this->data)) {
      if (file_put_contents ($location, $this->data)) return true;
    }
    return false;
  }
  
  public function data_uri () {
    $mime = ($this->type == 'jpg') ? 'jpeg' : $this->type;
    return "data:{$mime};base64," . base64_encode(file_get_contents($this->location));
  }
  
  public function display ($quality=80) {
    if (!empty($this->image)) {
      if ($this->type == 'jpg') {
        header ('Content-type: image/jpeg');
        return imagejpeg ($this->image, NULL, $quality);
      } elseif ($this->type == 'gif') {
        header ('Content-type: image/gif');
        return imagegif ($this->image);
      } elseif ($this->type == 'png') {
        if ($quality >= 90) {
          $quality = 0;
        } else {
          $quality = abs(round($quality / 10) - 9);
        }
        header ('Content-type: image/png');
        return imagepng ($this->image, NULL, $quality);
      }
    } elseif (!empty($this->data)) {
      switch ($this->type) {
        case 'jpg': header ('Content-type: image/jpeg'); break;
        case 'gif': header ('Content-type: image/gif'); break;
        case 'png': header ('Content-type: image/png'); break;
      }
      echo $this->data;
      return true;
    }
    return false;
  }
  
  private function max ($dimension) {
    if ($dimension == 'width') return ($this->crop) ? $this->crop['width'] : $this->width;
    if ($dimension == 'height') return ($this->crop) ? $this->crop['height'] : $this->height;
  }
  
  private function resample ($width, $height, $src_x, $src_y, $src_w, $src_h) {
    $this->image = ''; // to clean the slate if necessary
    $this->image = @imagecreatefromstring($this->data);
    $image = imagecreatetruecolor ($width, $height);
    if ($this->type == 'png') {
      imagealphablending ($image, false);
      imagesavealpha ($image, true);
      $transparent = imagecolorallocatealpha ($image, 255, 255, 255, 127);
      imagefilledrectangle ($image, 0, 0, $width, $height, $transparent);
    } else {
      $white = imagecolorallocate ($image, 255, 255, 255);
      imagefilledrectangle ($image, 0, 0, $width, $height, $white);
    }
    imagecopyresampled ($image, $this->image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);
    $this->image = $image;
    return true;
  }
  
}

?>