<?php

extract($page->get('params'));

include_once($plugin['uri'] . 'functions.php'); // don't use $page->load() because we want to use our extracted params here

if (isset($code)) $page->save($plugin['name'], $code);

if (isset($debug)) $page->save($plugin['name'], 'debug', true);

if (isset($version)) $page->save($plugin['name'], 'version', $version);

if (isset($ui)) { // either true or a version number
  if (!$page->get('info', $plugin['name'], 'ui') || is_string($ui)) { // an updated version number
    $page->save($plugin['name'], 'ui', $ui);
  }
}

?>