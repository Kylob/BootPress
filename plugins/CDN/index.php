<?php

$get = $page->get('params');

$localhost = (substr($page->get('domain'), 0, 9) == 'localhost') ? true : false;

$path = 'jsdelivr/files/';

if (isset($get['file'])) $get['files'] = array($get['file']);

// trigger_error('<pre>' . print_r($get, true) . '</pre>');

if (isset($get['load'])) {
  if (!file_exists($get['plugin-uri'] . $path . $get['load'])) {
    trigger_error('The CDN file: "' . $get['load'] . '" does not exist');
  } else {
    $export = file_get_contents($get['plugin-uri'] . $path . $get['load']);
  }
}

if (isset($get['link'])) {
  if (!file_exists($get['plugin-uri'] . $path . $get['link'])) {
    trigger_error('The CDN file: "' . $get['link'] . '" does not exist');
  } elseif ($localhost) {
    $export = $get['plugin-url'] . $path . $get['link'];
  } else {
    $export = '//cdn.jsdelivr.net/' . $file;
  }
}

if (isset($get['files'])) {
  $files = (array) $get['files'];
  foreach ($files as $key => $file) {
    if (!file_exists($get['plugin-uri'] . $path . $file)) {
      trigger_error('The CDN file: "' . $file . '" does not exist');
      unset($files[$key]);
    } elseif ($localhost) {
      $files[$key] = $get['plugin-url'] . $path . $file;
    } else {
      $files[$key] = '//cdn.jsdelivr.net/' . $file;
    }
  }
  $page->link($files);
}

?>