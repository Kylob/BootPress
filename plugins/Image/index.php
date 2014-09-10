<?php

extract($page->get('params'));

if (defined('IMAGEMAGICK_PATH') && constant('IMAGEMAGICK_PATH') != '') {
  $page->load($plugin, 'Image.php', 'Magick.php');
} else {
  $page->load($plugin, 'Image.php');
}

if (isset($Magick)) {
  $export = (function_exists('ImageMagick')) ? true : false;
} elseif (isset($uri)) {
  $image = new Image($uri);
  if (!empty($image->source)) {
    $export = $image;
  } else {
    unset($image);
    $export = false;
  }
}

?>