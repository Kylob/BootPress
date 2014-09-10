<?php

extract($page->get('params'));

$path = 'jsdelivr/files/';
if (isset($link)) $links = array($link);

if (isset($links)) {
  foreach ((array) $links as $key => $file) {
    if (file_exists($plugin['uri'] . $path . $file)) {
      $links[$key] = $plugin['url'] . $path . $file;
    } else {
      $links[$key] = '//cdn.jsdelivr.net/' . $file;
    }
  }
  $prepend = (isset($prepend)) ? true : false;
  $page->link($links, $prepend);
}

if (isset($url)) {
  if (file_exists($plugin['uri'] . $path . $url)) {
    $export = $plugin['url'] . $path . $url;
  } else {
    $export = '//cdn.jsdelivr.net/' . $url;
  }
}

?>