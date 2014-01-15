<?php

include_once(BASE . 'params.php');

if (IMAGEMAGICK_PATH != '') {

  $get = $page->get('params');
  $page->load($get['plugin-uri'], 'ImageMagick.php');
  $export = true;

} else {

  $export = false;

}

?>