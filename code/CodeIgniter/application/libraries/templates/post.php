<?php

if (is_admin(2)) {
  $export['#users'] = $bp->navbar->menu(array($bp->icon('user') . ' ' . $ci->session->userdata('name') => BASE_URL . ADMIN));
}

$export['.container'] = $ci->sitemap->uri('views');

?>