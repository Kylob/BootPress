<?php

if ( (isset($_GET['file']) && in_array($_GET['file'], array('default.css', 'functions.js', 'favicon.ico'))) ||
     (isset($_GET['db']) && file_exists($_GET['db']) && is_file($_GET['db'])) ||
     (isset($_GET['db']) && isset($_GET['server'])) ) {

  include $get['plugin-uri'] . 'adminer/3.6.2.php';
  exit;

} else {

  $directory = (isset($_GET['db']) && is_dir($_GET['db'])) ? $_GET['db'] : SQLITE_URI;
  $links = array();
  $scan = scandir($directory);
  $sqlite = $page->url('add', '', 'sqlite', '');
  foreach ($scan as $name) {
    if ($name != "." && $name != "..") {
      if (is_dir($directory . $name)) {
        $links['folders'][] = '<a href="' . $page->url('add', '', 'db', $directory . $name . '/') . '">' . $name . '/</a>';
      } elseif (substr($name, -4) == '.db3') {
        $links['files'][] = '<a href="' . $page->url('add', $sqlite, 'db', $directory . $name) . '">' . $name . '</a>';
      }
    }
  }
  krsort($links);
  if (function_exists('db_query')) {
    $db = MySQL::Database();
    $url = $page->url('add', '', 'server', $db->info['server']);
    $url = $page->url('add', $url, 'username', $db->info['username']);
    $url = $page->url('add', $url, 'db', $db->info['database']);
    $links['database'] = '<a href="' . $url . '">' . $db->info['database'] . '</a> (' . $db->info['password'] . ')';
  }
  $html = '';
  $html .= '<table align="center"><tr><td>';
    $html .= '<pre><a href="' . $page->url('add', '', 'db', SQLITE_URI) . '">' . SQLITE_URI . '</a>' . "\n" . print_r($links, true) . '</pre>';
  $html .= '</td></tr></table>';
  $page->title = 'Select A Database';
  $page->display($html);
  exit;
  
}

?>