{$page->plugin('CDN', ['prepend', 'links'=>[
  "bootstrap/{$blog.bootstrap}/css/bootstrap.min.css",
  "bootstrap/{$blog.bootstrap}/js/bootstrap.min.js"
]])}

{$page->link('<link rel="alternate" type="application/atom+xml" href="'|cat:{$blog.url.listings}|cat:'atom.xml" title="'|cat:{$blog.name}|cat:'">')}
{$page->link('<link rel="alternate" type="application/rss+xml" href="'|cat:{$blog.url.listings}|cat:'rss.xml" title="'|cat:{$blog.name}|cat:'">')}

{$page->link('<style>.navbar-form.navbar-right:last-child { margin-right:0; }</style>')}

{$bp->navbar->open([$blog.name => $blog.url.listings], 'top')}
{if (!empty($blog.slogan))} {$bp->navbar->text($blog.slogan)} {/if}
{$bp->navbar->search($blog.url.listings, 'Search', [])}
<div id="users"></div>
{$bp->navbar->close()}

<div class="container" style="margin-top:70px; margin-bottom:40px;">

  {$bp->row('sm', [
    $bp->col(12, $content)
  ])}
  
</div>