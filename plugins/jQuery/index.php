<?php

extract($page->get('params'));

include_once($plugin['uri'] . 'functions.php'); // don't use $page->load() because we want to use our extracted params here

if (isset($code)) $page->save($plugin['name'], 'code', array($code));

if (isset($debug)) $page->save($plugin['name'], 'debug', true);

if (isset($version)) $page->save($plugin['name'], 'version', $version);

if (isset($ui)) {
  $jquery = $page->get('info', $plugin['name']);
  if (!isset($jquery['ui']) || is_bool($jquery['ui']) || !is_bool($ui)) {
    $page->save($plugin['name'], 'ui', $ui);
  }
}

?>