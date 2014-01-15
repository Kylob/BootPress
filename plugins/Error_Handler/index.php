<?php

$get = $page->get('params');

$page->load($get['plugin-uri'], 'Error_Handler.php');

if (isset($get['Admin']) && is_admin(1)) {

  $page->plugin('Bootstrap', 'css');
  $html = '<div class="container">';

  if (isset($_GET['file']) && file_exists(BASE . $_GET['file'])) {
  
    $page->load($get['plugin-uri'], 'classes/EditFile.php');
    $view = new EditFile;
    $html .= $view->page();
    unset($view);
    
  } else { // view errors
    
    $page->load($get['plugin-uri'], 'classes/ViewErrors.php');
    $view = new ViewErrors;
    $html .= $view->page();
    unset($view);
    
  }
  
  $html .= '</div>';
  $page->display($html);
  exit;

}

?>