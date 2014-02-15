<?php

$get = $page->get('params');

$page->load($get, 'Bootstrap.php');

if (isset($get['load'])) {
  $files = array_merge((array) $get['load'], array('', ''));
  $theme = new Bootstrap;
  $theme->load(array_shift($files), array_shift($files));
  unset($theme);
}

if (isset($get['preview'])) include $get['plugin-uri'] . 'preview.html';

if (isset($get['variables'])) { // used by 'preview' above
  $file = $get['plugin-uri'] . 'less/themes/' . $get['variables'] . '.less';
  echo (file_exists($file)) ? file_get_contents($file) : file_get_contents($get['plugin-uri'] . 'less/3.0.3/variables.less');
}

?>