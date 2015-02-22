{$page->link($page->url('theme', 'blog.css'))}

{$page->plugin('CDN', ['prepend', 'links'=>[
  "bootstrap/{$blog.bootstrap}/css/bootstrap.min.css",
  "bootstrap/{$blog.bootstrap}/js/bootstrap.min.js"
]])}

{$page->plugin('jQuery')}

{capture assign='sidebar'}
  
  {$bp->search($page->url('blog'))}
  
{/capture}

<div id="masthead">
  <div class="container">
    <nav class="blog-nav">
      {$bp->links('a blog-nav-item', [
        $blog.name => $page->url('base')
      ], ['active'=>'url'])}
    </nav>
  </div>
</div>

<br>

<div class="container">
  {$bp->row('sm', [
    $bp->col('9', $content),
    $bp->col('3', $sidebar)
  ])}
</div>

<br>

<div id="footer">
  <p>Built with <a href="http://bootpress.org">BootPress</a></p>
</div>