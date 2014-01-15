<?php

/**
 * atom.xml
 * rss.xml
 * sitemap.xml
 * admin/[setup|blog|layout|bootstrap|images]/
 * archives/[YYYY]/[mm]/[dd]/
 * authors/[author]/
 * tags/[tag]/
 * users/
 * errors/
 * [$page->get('file')]/
 * [category]
 * [page or post]
 * []?search=terms - index
 * 404
 */

$get = $page->get('params');

$view = $page->next_uri();
if (strpos($view, '.xml') && in_array(substr($view, 0, strpos($view, '.xml')), array('atom', 'rss', 'sitemap'))) {
  $view = substr($view, 0, strpos($view, '.xml'));
}
switch ($view) {
  case 'admin':
    $admin = $page->next_uri('admin');
    $file = ucwords($admin);
    if (empty($file) || !file_exists($get['plugin-uri'] . 'classes/Blog/Admin/' . $file . '.php')) {
      $page->load($get, 'classes/', 'Blog.php', 'Blog/', 'Admin.php');
      $view = new BlogAdmin($get); // This will eject them mailman
    } else {
      $page->load($get, 'classes/', 'Blog.php', 'Blog/', 'Admin.php', "Admin/{$file}.php");
      $class = 'BlogAdmin' . $file;
      $admin = new $class($get, $file);
      $export = $admin->view();
    }
    break;
  case 'archives':
  case 'authors':
  case 'atom':
  case 'rss':
  case 'sitemap':
  case 'tags':
    $page->load($get, 'classes/', 'Blog.php', 'Blog/Page.php');
    $blog = new BlogPage($get);
    $export = $blog->$view();
    break;
  default:
    $page->load($get, 'classes/', 'Blog.php', 'Blog/Page.php');
    $blog = new BlogPage($get);
    $export = $blog->view();
    break;
}
unset($blog);

?>