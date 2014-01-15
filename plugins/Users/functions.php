<?php

function is_admin ($level=1) {
  if (isset($_SESSION['admin']) && !empty($_SESSION['admin']) && $_SESSION['admin'] <= $level) return true;
  return false;
}

function is_user ($user_id='') {
  if (empty($user_id)) return (isset($_SESSION['user_id'])) ? true : false;
  if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) return true;
  return false;
}

if (isset($_COOKIE['user']) && !isset($_SESSION['user_id'])) {
  $user = new UsersDatabase;
  $user->persistent_login();
  unset($user);
}

?>