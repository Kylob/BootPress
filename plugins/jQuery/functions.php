<?php

function add_jquery_scripts ($js, $plugin) {
  global $page;
  $scripts = array();
  if (!$version = $page->info($plugin, 'version')) $version = '1.11.0';
  $scripts[] = $page->plugin('CDN', 'url', 'jquery/' . $version . '/jquery.min.js');
  if ($ui = $page->info($plugin, 'ui')) {
    $scripts[] = $page->plugin('CDN', 'url', 'jquery.ui/' . (is_string($ui) ? $ui : '1.10.4') . '/jquery-ui.min.js');
  }
  return array_merge($scripts, $js);
}

function add_jquery_code ($html, $plugin) {
  global $ci, $page;
  $code = array_unique($page->info($plugin));
  if (empty($code)) return $html;
  foreach ($code as $key => $value) $code[$key] = $page->indent($value);
  return $html . "\n  <script>" . '$(document).ready(function(){' . "\n" . implode("\n", $code) . "\n  });</script>";
}

$page->filter('javascript', 'add_jquery_scripts', array('this', $plugin['name']));
$page->filter('scripts', 'add_jquery_code', array('this', $plugin['name']));

?>