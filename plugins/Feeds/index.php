<?php

$get = $page->get('params');

if (isset($get['Atom'])) {

  $page->load($get, 'classes/Atom.php');
  
} elseif (isset($get['RSS'])) {

  $page->load($get, 'classes/RSS.php');
  
} elseif (isset($get['Sitemap'])) {

  $page->load($get, 'classes/Sitemap.php');
  
} elseif (isset($get['ping'])) { // sitemap url
  
  $sitemap = urlencode($get['ping']);
  $status = array();
  $engines = array();
  $engines['www.google.com'] = '/webmasters/tools/ping?sitemap=' . $sitemap;
  $engines['www.bing.com'] = '/webmaster/ping.aspx?siteMap=' . $sitemap;
  $engines['submissions.ask.com'] = '/ping?sitemap=' . $sitemap;
  foreach ($engines as $host => $path) {
    if ($fp = fsockopen($host, 80)) {
      fwrite($fp, "HEAD {$path} HTTP/1.1\r\nHOST: {$host}\r\nCONNECTION: Close\r\n\r\n");
      $status[$host] = fgets($fp, 128);
      fclose($fp);
    }
  }
  $export = $status;
  
}

?>