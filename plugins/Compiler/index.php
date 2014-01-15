<?php

$get = $page->get('params');

$page->load($get, 'Compiler.php');

if (isset($get['CombineFiles'])) $page->load($get, 'CombineFiles.php');

if (isset($get['combine']) && is_array($get['combine'])) { // urls

  $urls = $get['combine'];
  $page->load($get, 'CombineFiles.php');
  $cache = new CombineFiles;
  $export = $cache->combine($urls);
  unset($cache);
  
} elseif (isset($get['files'])) { // [mixed] urls - returns what you gave it, and in the same order

  $urls = $get['files'];
  $page->load($get, 'CombineFiles.php');
  $cache = new CombineFiles;
  $export = $cache->files($urls);
  unset($cache);
  
} elseif (isset($get['cache'])) {

  $page->load($get, 'CombineFiles.php', 'CacheFile.php');
  $file = new CacheFile($get['cache']);
  unset($file);
  
} elseif (isset($get['analyze']) && is_array($get['analyze'])) {

  list($type, $code) = each($get['analyze']);
  $compiler = new Compiler($type, $code);
  echo $compiler->analyze();
  unset($compiler);
  
} elseif (isset($get['html']) && is_string($get['html'])) {

  $compiler = new Compiler('html', $get['html']);
  echo $compiler->google()->code();
  unset($compiler);
  
} elseif (isset($get['xml']) && is_string($get['xml'])) {

  $compiler = new Compiler('xml', $get['xml']);
  echo $compiler->google()->code();
  unset($compiler);
  
} elseif (isset($get['less']) && is_string($get['less'])) {

  $compiler = new Compiler('less', $get['less']);
  echo $compiler->code();
  unset($compiler);
  
} elseif (isset($get['css']) && is_string($get['css'])) {

  $compiler = new Compiler('css', $get['css']);
  echo $compiler->css_min()->code();
  unset($compiler);
  
} elseif (isset($get['js']) && is_string($get['js'])) {

  $compiler = new Compiler('js', $get['js']);
  echo $compiler->yui()->code();
  unset($compiler);
  
} elseif (isset($get['yui']) && is_array($get['yui'])) {

  list($type, $code) = each($get['yui']);
  $compiler = new Compiler($type, $code);
  echo $compiler->yui()->code();
  unset($compiler);
  
} elseif (isset($get['google']) && is_array($get['google'])) {

  list($type, $code) = each($get['google']);
  $compiler = new Compiler($type, $code);
  echo $compiler->google()->code();
  unset($compiler);
  
} elseif (isset($get['jslint']) && is_string($get['jslint'])) {

  $compiler = new Compiler('js', $get['jslint']);
  echo $compiler->jslint()->code();
  unset($compiler);
  
} elseif (isset($get['packer']) && is_string($get['packer'])) {

  $compiler = new Compiler('js', $get['packer']);
  echo $compiler->packer()->code();
  unset($compiler);
  
}

?>