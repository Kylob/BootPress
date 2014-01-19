<?php

if ( (isset($_GET['file']) && in_array($_GET['file'], array('default.css', 'functions.js', 'favicon.ico'))) ||
     (isset($_GET['db']) && file_exists($_GET['db']) && is_file($_GET['db'])) ||
     (isset($_GET['db']) && isset($_GET['server'])) ) {

  include $get['plugin-uri'] . 'adminer/3.6.2.php';
  exit;

} else {

  $page->title = 'Select A Database';
  $page->plugin('CDN', 'link', 'bootstrap/3.0.3/css/bootstrap.min.css');
  $page->link('<style>.dl-horizontal dt { width:120px; }</style>');
  $html = '<div class="container">';
  $html .= '<div class="page-header"><h3 class="text-center">' . $page->title . '</h3></div>';
  
  $sqlite = $page->url('add', '', 'sqlite', '');
  $tb = $bp->table('align=center');
  
  #-- Website --#
  $dl = array('Website Files:');
  $links = array();
  $directory = BASE_URI;
  $scan = scandir($directory);
  foreach ($scan as $name) {
    if (substr($name, -4) == '.db3') {
      $dl[][] = '<a href="' . $page->url('add', $sqlite, 'db', $directory . $name) . '">' . $name . '</a>';
    }
  }
  $tb->row()->cell('', $bp->lister('dl', $dl, 'dl-horizontal'));
  $website = $bp->lister('dl', $dl, 'dl-horizontal');
  
  #-- BootPress --#
  $dl = array('BootPress Files:');
  $links = array();
  $directory = (isset($_GET['db']) && is_dir($_GET['db'])) ? $_GET['db'] : SQLITE_URI;
  $scan = scandir($directory);
  foreach ($scan as $name) {
    if ($name != "." && $name != "..") {
      if (is_dir($directory . $name)) {
        $links['folders'][] = '<a href="' . $page->url('add', '', 'db', $directory . $name . '/') . '">' . $name . '/</a>';
      } elseif (substr($name, -4) == '.db3') {
        $dl[][] = '<a href="' . $page->url('add', $sqlite, 'db', $directory . $name) . '">' . $name . '</a>';
      }
    }
  }
  if (isset($links['folders'])) {
    $dl[] = '<a href="' . $page->url('add', '', 'db', SQLITE_URI) . '">Folders:</a>';
    foreach ($links['folders'] as $url) $dl[][] = $url;
  }
  $tb->row()->cell('', $bp->lister('dl', $dl, 'dl-horizontal'));
  
  #-- MySQL --#
  if (function_exists('db_query')) {
    $dl = array('MySQL:');
    $db = MySQL::Database();
    $url = $page->url('add', '', 'server', $db->info['server']);
    $url = $page->url('add', $url, 'username', $db->info['username']);
    $url = $page->url('add', $url, 'db', $db->info['database']);
    $dl[][] = '<a href="' . $url . '">' . $db->info['database'] . '</a> (' . $db->info['password'] . ')';
    $tb->row()->cell('', $bp->liseter('dl', $dl, 'dl-horizontal'));
  }
  
  $html .= $tb->close();
  unset($tb);
  
  $html .= '</div>';
  $page->display($html);
  exit;

}

?>