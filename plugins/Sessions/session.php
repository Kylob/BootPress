<?php

if (isset($get['mySQL'])) {

  include $get['plugin-uri'] . 'mySQL.php';
  
} elseif (isset($get['SQLite'])) {

  include $get['plugin-uri'] . 'SQLite.php';
  
}

session_start();

?>