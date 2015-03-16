<?php

function add_jquery_scripts ($js, $plugin) {
  global $page;
  $plugin = $page->get('info', $plugin);
  $scripts = array();
  $version = (isset($plugin['version'])) ? $plugin['version'] : '1.11.0';
  $scripts[] = $page->plugin('CDN', 'url', 'jquery/' . $version . '/jquery.min.js');
  if (isset($plugin['ui'])) {
    if (is_bool($plugin['ui'])) $plugin['ui'] = '1.10.4';
    $scripts[] = $page->plugin('CDN', 'url', 'jquery.ui/' . $plugin['ui'] . '/jquery-ui.min.js');
  }
  return array_merge($scripts, $js);
}

function add_jquery_code ($html, $plugin) {
  global $ci, $page;
  $plugin = $page->get('info', $plugin);
  if (!isset($plugin['code'])) return $html;
  $code = array_unique($plugin['code']);
  foreach ($code as $key => $value) $code[$key] = $page->indent($value);
  return $html . "\n  <script>" . '$(document).ready(function(){' . "\n" . implode("\n", $code) . "\n  });</script>";
}

$page->filter('javascript', 'add_jquery_scripts', array('this', $plugin['name']));
$page->filter('scripts', 'add_jquery_code', array('this', $plugin['name']));

?>