<?php

$get = $page->get('params');

$page->load($get, 'classes/UsersDatabase.php', 'functions.php'); // is_admin(), is_user(), enables persistent login

if (isset($get['forms'])) {
  switch ($page->next_uri('users')) {
    case 'view':
      $page->access('admin');
      $page->load($get, 'classes/UsersView.php');
      $users = new UsersView;
      $export = $users->view();
      unset($users);
      break;
    default:
      $page->load($get, 'classes/UsersForms.php');
      $update = (isset($get['update'])) ? $get['update'] : false;
      $users = new UsersForms($get['forms'], $update);
      $export = $users->forms();
      unset($users);
      break;
  }
} elseif (isset($get['info'])) {
  $users = new UsersDatabase;
  $export = $users->info($get['info']);
  unset($users);
}

?>