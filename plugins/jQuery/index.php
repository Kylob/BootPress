<?php

$get = $page->get('params');

include_once($get['plugin-uri'] . 'functions.php'); // don't use $page->load() because we want to use $get here

if (isset($get['code'])) {
  if (isset($get['oneliner']) && $get['oneliner'] === false) {
    $code = $get['code'];
  } else { // a one-liner by default
    $code = preg_replace('/\s(?=\s)/', '', str_replace(array("\r\n", "\r", "\n", "\t"), ' ', trim($get['code'])));
  }
  $page->save($get['plugin-name'], 'code', array($code));
}

if (isset($get['plugin'])) {
  $plugins = (array) $get['plugin'];
  foreach ($plugins as $plugin) {
    if ($plugin == 'ui') {
      $page->save($get['plugin-name'], 'ui', '');
    } elseif (is_dir($get['plugin-uri'] . 'plugins/' . $plugin . '/')) {
      foreach (scandir($get['plugin-uri'] . 'plugins/' . $plugin . '/') as $file) {
        if (substr($file, -3) == '.js') {
          $page->save($get['plugin-name'], 'plugins', array($get['plugin-url'] . 'plugins/' . $plugin . '/' . $file));
        } elseif (substr($file, -4) == '.css') {
          $page->save($get['plugin-name'], 'css', array($get['plugin-url'] . 'plugins/' . $plugin . '/' . $file));
        }
      }
    } elseif (file_exists($get['plugin-uri'] . 'plugins/' . $plugin . '.js')) {
      $page->save($get['plugin-name'], 'plugins', array($get['plugin-url'] . 'plugins/' . $plugin . '.js'));
    } else {
      trigger_error('The jQuery plugin: "' . $plugin . '" does not exist.');
    }
  }
}

if (isset($get['theme'])) {
  if (file_exists($get['plugin-uri'] . 'themes/' . $get['theme'] . '/' . $get['theme'] . '.css')) {
    $page->save($get['plugin-name'], 'theme', $get['plugin-url'] . 'themes/' . $get['theme'] . '/' . $get['theme'] . '.css');
  } else {
    trigger_error('The jQuery theme: "' . $get['theme'] . '" does not exist.');
  }
}

?>