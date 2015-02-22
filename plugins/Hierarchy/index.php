<?php

extract($page->get('params'));

$page->load($plugin, 'Hierarchy.php');

if ($table = array_shift($plugin['params'])) { // A Database object
  
  $export = new Hierarchy($$table, $table);
  
}

?>