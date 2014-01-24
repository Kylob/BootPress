<?php

function imagemagick ($command, $params) {
  $cmd = array();
  $cmd[] = escapeshellarg(IMAGEMAGICK_PATH . $command);
  foreach ($params as $key => $value) {
    if (strpos($value, BASE) !== false) $value = escapeshellarg($value);
    if (!is_numeric($key)) {
      $cmd[] = '-' . $key . ' ' . $value;
    } else {
      $cmd[] = $value;
    }
  }
  $return = null;
  $output = array();
  $cmd = implode(' ', $cmd);
  exec($cmd . ' 2>&1', $output, $return);
  return array('cmd'=>$cmd, 'output'=>$output, 'return'=>$return);
}

class ImageMagick {

  private $type;
  private $width;
  private $height;
  private $cropped = false; // an array of data if image is cropped
  private $crop = ''; // -crop command line
  private $resize = ''; // -resize command line
  private $source = ''; // the original file uri
  private $destination = ''; // the last saved file uri
  public $debug = array();
  
  public function __construct ($source) {
    $this->source = str_replace(' ', '%20', trim($source));
    list($width, $height, $type) = @getimagesize($this->source);
    switch ($type) {
      case 1: $this->type = 'gif'; break; // do not crop and resize well from jpegs
      case 2: $this->type = 'jpg'; break;
      case 3: $this->type = 'png'; break;
      case 17: $this->type = 'ico'; break; // 255 pixels is the max width and height
      default: $this->type = false; break;
    }
    if ($this->type) { // continue
      $this->width = $width;
      $this->height = $height;
    }
  }
  
  public function get ($var) {
    return (in_array($var, array('type', 'width', 'height'))) ? $this->$var : '';
  }
  
  public function retrieved () { // perform this sanity check before anything else
    return ($this->type) ? true : false;
  }
  
  public function crop ($coords='') {
    if (!is_array($coords)) $coords = explode(',', $coords);
    if (count($coords) == 4) {
      list($x1, $y1, $x2, $y2) = $coords;
      $width = $x2 - $x1;
      $height = $y2 - $y1;
      $this->crop = " -crop {$width}x{$height}+{$x1}+{$y1}";
      $this->cropped = array('width'=>$width, 'height'=>$height, 'x1'=>$x1, 'y1'=>$y1, 'x2'=>$x2, 'y2'=>$y2);
    }
  }
  
  public function resize ($width, $height=0, $enforce=false) {
    if ($width == 0) $width = '';
    if ($height == 0) $height = '';
    $modifier = ($enforce) ? '!' : '^';
    $this->resize = " -resize {$width}x{$height}{$modifier}"; // -scale?
  }
  
  public function square ($pixels) {
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
      $this->crop(array($x, $y, $x+$height, $y+$height));
    } else { // keep the width
      $y = round(($height - $width) / 2);
      if ($this->cropped) {
        $y += $this->cropped['y1'];
        $x = $this->cropped['x1'];
      }
      $this->crop(array($x, $y, $x+$width, $y+$width));
    }
    $this->resize($pixels, $pixels, true);
  }
  
  public function save ($destination, $quality=80) {
    if (!$this->retrieved()) return false;
    $this->destination = $destination;
    $cmd = IMAGEMAGICK_PATH . "convert \"{$this->source}\"{$this->crop}{$this->resize} -quality {$quality} -strip -verbose \"{$this->destination}\"";
    return $this->execute($cmd);
  }
  
  public function data_uri () {
    if (empty($this->destination)) return '';
    $type = substr($this->destination, strrpos($this->destination, '.') + 1);
    $mime = ($type == 'jpg') ? 'jpeg' : $type;
    return "data:{$mime};base64," . base64_encode(file_get_contents($this->destination));
  }
  
  public function execute ($cmd) {
    $return = null;
    $output = array();
    exec ($cmd . ' 2>&1', $output, $return);
    $log = array('cmd'=>$cmd, 'return'=>$return, 'output'=>$output);
    $this->debug[] = $log;
    if ($return != 0) {
      trigger_error('<pre>' . print_r($log, true) . '</pre>');
      return false;
    }
    return true;
  }
  
  private function max ($dimension) {
    switch ($dimension) {
      case 'width': return ($this->cropped) ? $this->cropped['width'] : $this->width; break;
      case 'height': return ($this->cropped) ? $this->cropped['height'] : $this->height; break;
    }
  }
  
}

?>