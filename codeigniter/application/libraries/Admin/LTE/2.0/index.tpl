{$page->plugin('jQuery', ['version'=>'2.1.3', 'ui'=>'1.11.2', 'code'=>'$.widget.bridge("uibutton", $.ui.button);'])}

{$page->plugin('CDN', 'links', [
  'slimscroll/1.3.3/jquery.slimscroll.min.js',
  'fastclick/1.0.3/fastclick.min.js',
  'fontawesome/4.3.0/css/font-awesome.min.css'
])}

{$logo = ($page->logo) ? $page->logo : '<a href="https://www.bootpress.org/">'|cat:$bp->img($page->url('theme', 'bootpress.png'), 'height="30" style="margin-top:-3px;" alt="BootPress"', 'BootPress')|cat:'</a>'}
{$skin = ($page->skin) ? $page->skin : 'blue'}

{$page->link([
  $page->url('theme', 'bootstrap/css/bootstrap.min.css'),
  $page->url('theme', 'bootstrap/js/bootstrap.min.js'),
  $page->url('theme', 'dist/css/AdminLTE.min.css'),
  $page->url('theme', "dist/css/skins/skin-{$skin}.min.css"),
  $page->url('theme', 'dist/js/app.min.js'),
  '<!--[if lt IE 9]>
      <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
  <![endif]-->'
], 'prepend')}

{$page->meta('content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport"')}

{$page->style([
  'ul.sidebar-menu, div.navbar-custom-menu { font-size:15px; }',
  'ul.sidebar-menu .fa, ul.sidebar-menu .glyphicon { margin-right:10px; }'
])}
{$page->set('body', 'class="fixed skin-'|cat:$skin|cat:{($page->collapse == 'sidebar') ? ' sidebar-collapse"' : '"'})}

<div class="wrapper">
  
  <header class="main-header">
    <div class="logo">{$logo}</div>
    <nav class="navbar navbar-static-top" role="navigation">
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"><span class="sr-only">Toggle navigation</span></a>
      {$page->navbar}
    </nav>
  </header>
  
  <aside class="main-sidebar"><section class="sidebar">{$page->sidebar}</section></aside>
  
  <div class="content-wrapper">
    <section class="content-header">{$page->header}</section>
    <section class="content">{$content}</section>
  </div>
  
  {$page->footer}
  
</div>
