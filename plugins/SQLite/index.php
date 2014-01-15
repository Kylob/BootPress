<?php

$get = $page->get('params');

$page->load($get, 'SQLite.php');

if (isset($get['FTS'])) {
  $page->load($get, 'classes/FTS.php');
  if (is_array($get['FTS']) && isset($get['search'])) {
    $values = $export = array();
    foreach ($get['FTS'] as $docid => $value) $values[$docid] = array($value);
    $db = new SQLite;
    $fts = new FTS($db);
    $fts->create('results', 'search', 'porter');
    $fts->upsert('results', 'search', $values);
    $db->query('SELECT docid, search FROM results WHERE search MATCH ?', array($get['search']));
    while (list($docid, $value) = $db->fetch('row')) $export[$docid] = $value;
    unset($db);
  }
}

if (isset($get['adminer']) && $get['adminer'] == 'edit' && is_admin(1)) include $get['plugin-uri'] . 'adminer/index.php';

?>