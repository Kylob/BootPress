<?php

$get = $page->get('params');

if (isset($get['link'])) $get['links'] = array($get['link']);

if (isset($get['links'])) {
  $path = 'jsdelivr/files/';
  $localhost = (substr($page->get('domain'), 0, 9) == 'localhost') ? true : false;
  $links = (array) $get['links'];
  foreach ($links as $key => $file) {
    if (!file_exists($get['plugin-uri'] . $path . $file)) {
      trigger_error('The CDN file: "' . $file . '" does not exist');
      unset($links[$key]);
    } elseif ($localhost) {
      $links[$key] = $get['plugin-url'] . $path . $file;
    } else {
      $links[$key] = '//cdn.jsdelivr.net/' . $file;
    }
  }
  $prepend = (isset($get['prepend'])) ? true : false;
  $page->link($links, $prepend);
}

?>