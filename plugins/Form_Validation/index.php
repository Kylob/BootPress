<?php

$get = $page->get('params');

$page->load($get['plugin-uri'] . 'classes/', 'Form.php', 'Validation.php');

if (isset($get['Upload'])) {

  $page->load($get['plugin-uri'] . 'classes/', 'Upload.php', 'Image.php');

}

if (isset($get['filter']) && is_array($get['filter'])) {

  $export = array();
  $validate = new Validation;
  foreach ($get['filter'] as $filter => $data) {
    $export[] = $validate->data($filter, $data);
  }
  if (count($export) == 1) $export = array_shift($export);
  unset($validate);
  
} elseif (isset($get['seo'])) {

  $filter = new Validation;
  echo $filter->seo($get['seo']);
  unset($filter);
  
}


?>