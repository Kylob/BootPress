<?php

function add_jquery_styles ($css, $plugin) {
  global $page;
  $plugin = $page->get('info', $plugin);
  if (!isset($plugin['theme']) && !isset($plugin['css'])) return $css;
  $styles = array();
  if (isset($plugin['theme'])) $styles[] = $plugin['theme'];
  if (isset($plugin['css'])) foreach ($plugin['css'] as $link) $styles[] = $link;
  return array_merge($css, array_unique($styles)); // append
}

function add_jquery_scripts ($js, $plugin, $url) {
  global $page;
  $plugin = $page->get('info', $plugin);
  $scripts = array($url . 'jquery.1.8.3.js');
  if (isset($plugin['theme']) || isset($plugin['ui'])) $scripts[] = $url . 'ui.1.9.2.js';
  if (isset($plugin['plugins'])) foreach ($plugin['plugins'] as $javascript) $scripts[] = $javascript;
  return array_merge(array_unique($scripts), $js); // prepend
}

function add_jquery_code ($html, $plugin) {
  global $page;
  $plugin = $page->get('info', $plugin);
  if (!isset($plugin['code'])) return $html;
  $plugin['code'] = implode("\n\t", array_unique($plugin['code']));
  return $html . "\n  " . '<script type="text/javascript">$(document).ready(function(){' . "\n\t{$plugin['code']}\n  " . '})</script>';
}

$page->filter('css', 'add_jquery_styles', array('this', $get['plugin-name']));
$page->filter('javascript', 'add_jquery_scripts', array('this', $get['plugin-name'], $get['plugin-url']));
$page->filter('scripts', 'add_jquery_code', array('this', $get['plugin-name']));

?>