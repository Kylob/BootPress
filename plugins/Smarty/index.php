<?php

extract($page->get('params'));

$page->load($plugin, 'Smarty-3.1.19/libs/Smarty.class.php');

if (isset($class)) {

  $page->load($plugin, 'SmartyPlugin.php');
  $export = new SmartyPlugin($plugin['uri']);

} elseif (isset($assign) && is_array($assign)) {

  $page->load($plugin, 'SmartyPlugin.php');
  $smarty = new SmartyPlugin($plugin['uri']);
  $smarty->assign($assign); // key => value pairs
  if (isset($file)) {
    $smarty->setTemplateDir(dirname($file) . '/');
    $smarty->display(basename($file));
  } elseif (isset($string)) {
    $smarty->display('string:' . $string);
  } elseif (isset($eval)) {
    $smarty->display('eval:' . $eval);
  }
  unset($smarty);
  
}

?>