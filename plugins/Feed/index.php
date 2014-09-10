<?php

extract($page->get('params'));

if (isset($Atom) && is_array($Atom)) {

  $title = array_shift($Atom);
  $id = array_shift($Atom);
  $elements = (array) array_shift($Atom);
  $page->load($plugin, 'classes/Atom.php');
  $export = new Atom($title, $id, $elements);
  
} elseif (isset($RSS) && is_array($RSS)) {

  $title = array_shift($RSS);
  $link = array_shift($RSS);
  $description = array_shift($RSS);
  $elements = (array) array_shift($RSS);
  $page->load($plugin, 'classes/RSS.php');
  $export = new RSS($title, $link, $description, $elements);
  
} elseif (isset($ping)) { // sitemap url
  
  $sitemap = urlencode($ping);
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