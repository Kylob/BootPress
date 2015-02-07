{$page->link("{$blog.url.media}blog.css")}

{$page->plugin('CDN', ['prepend', 'links'=>[
  "bootstrap/{$blog.bootstrap}/css/bootstrap.min.css",
  "bootstrap/{$blog.bootstrap}/js/bootstrap.min.js"
]])}

{$page->plugin('jQuery')}

{capture assign='header'}
  <div id="header">
    <h1>{$blog.name}</h1>
    {if !empty($blog.slogan)} <p class="lead">{$blog.slogan}</p> {/if}
  </div>
{/capture}

{capture assign='sidebar'}
  <div id="sidebar" style="margin:25px auto;">
    {$bp->search($blog.url.listings)}<br>
    {if !empty($blog.summary)} <h4>About</h4><p>{$blog.summary}</p><br> {/if}
  </div>
{/capture}

<div id="masthead">
  <div class="container">
    <nav class="blog-nav">
      {$bp->links('a blog-nav-item', [
        'Home' => "{$blog.url.base}"
      ], ['active'=>'url'])}
    </nav>
  </div>
</div>

<div class="container">
  {$bp->row('sm', [
    $bp->col('9', $header|cat:$content),
    $bp->col('3', $sidebar)
  ])}
</div>

<br>

<div id="footer">
  <p>Built with <a href="http://bootpress.org">BootPress</a></p>
</div>