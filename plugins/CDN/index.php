<?php

$get = $page->get('params');

if (isset($get['link'])) $get['links'] = array($get['link']);

if (isset($get['links'])) {
  $path = 'jsdelivr/files/';
  $localhost = ($_SERVER['HTTP_HOST'] == 'localhost') ? true : false;
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

if (isset($get['url'])) {
  $path = 'jsdelivr/files/';
  $localhost = ($_SERVER['HTTP_HOST'] == 'localhost') ? true : false;
  if (!file_exists($get['plugin-uri'] . $path . $get['url'])) {
    trigger_error('The CDN file: "' . $get['url'] . '" does not exist');
  } elseif ($localhost) {
    $export = $get['plugin-url'] . $path . $get['url'];
  } else {
    $export = '//cdn.jsdelivr.net/' . $get['url'];
  }
}

?>