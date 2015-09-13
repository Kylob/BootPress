<?php

extract($params);

if (isset($bootstrap)) {
  $page->load($plugin, 'Bootstrap.php');
  $export = new Bootstrap($bootstrap);
}

?>