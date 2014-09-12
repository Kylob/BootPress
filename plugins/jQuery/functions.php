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
  if (!isset($plugin['debug'])) {
    foreach ($code as $key => $value) $code[$key] = $ci->output->minify($value, 'text/javascript');
    $code = "\n\t" . implode("\n\t", $code) . "\n  ";
  } else {
    $code = "\n" . implode("\n\n", $code) . "\n  ";
  }
  return $html . "\n  " . '<script type="text/javascript">$(document).ready(function(){' . $code . '})</script>';
}

$page->filter('javascript', 'add_jquery_scripts', array('this', $plugin['name']));
$page->filter('scripts', 'add_jquery_code', array('this', $plugin['name']));

?>