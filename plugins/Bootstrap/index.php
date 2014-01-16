<?php

$get = $page->get('params');

if (isset($get['preview'])) include $get['plugin-uri'] . 'preview.html';

if (isset($get['variables'])) { // used by 'preview' above
  $file = $get['plugin-uri'] . 'less/themes/' . $get['variables'] . '.less';
  echo (file_exists($file)) ? file_get_contents($file) : file_get_contents($get['plugin-uri'] . 'less/3.0.3/variables.less');
}

if (isset($get['css'])) $page->link($get['plugin-url'] . 'css/3.0.3/bootstrap.css');

if (isset($get['js'])) $page->link($get['plugin-url'] . 'js/bootstrap.3.0.3.js');

if (isset($get['Theme'])) $page->load($get, 'classes/Theme.php');

?>