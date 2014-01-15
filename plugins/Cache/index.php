<?php

// $page->plugin('Cache', array('urls'=>array()));
// $page->plugin('Cache', array('combine'=>$urls));
// $page->plugin('Cache', array('deliver'=>$file));

$get = $page->get('params');

if (isset($get['urls']) && is_array($get['urls'])) {

  $page->load($get, 'Cache/', 'URLs.php');
  $cache = new CacheURLs;
  $export = $cache->urls($get['urls']);
  unset($cache);
  
} elseif (isset($get['combine']) && is_array($get['combine'])) {

  $page->load($get, 'Cache/', 'URLs.php');
  $cache = new CacheURLs;
  $export = $cache->combine($get['combine']);
  unset($cache);
  
} elseif (isset($get['deliver']) && is_string($get['deliver'])) {

  $page->load($get, 'Cache/', 'URLs.php', 'Deliverer.php');
  $file = new CacheDeliverer($get['deliver']);
  unset($file);
  
}

?>