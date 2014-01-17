<?php

$get = $page->get('params');

$page->load($get, 'smarty/libs/Smarty.class.php');

if (isset($get['class'])) {

  $page->load($get, 'SmartyPlugin.php');
  $export = new SmartyPlugin($get['plugin-uri']);

} elseif (isset($get['assign']) && is_array($get['assign'])) {

  $page->load($get, 'SmartyPlugin.php');
  $smarty = new SmartyPlugin($get['plugin-uri']);
  $smarty->assign($get['assign']); // key => value pairs
  if (isset($get['file'])) {
    $smarty->setTemplateDir(dirname($get['file']) . '/');
    $smarty->display(basename($get['file']));
  } elseif (isset($get['string'])) {
    $smarty->display('string:' . $get['string']);
  } elseif (isset($get['eval'])) {
    $smarty->display('eval:' . $get['eval']);
  }
  unset($smarty);
  
}

?>
