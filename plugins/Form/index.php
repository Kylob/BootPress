<?php

extract($params);

if (isset($name)) {
  include_once($plugin['uri'] . 'Form.php');
  $export = new Form($name, $plugin);
}

?>