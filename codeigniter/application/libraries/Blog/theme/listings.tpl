{if $blog.page != 'index'} {$bp->breadcrumbs($breadcrumbs)} {/if}

{if $blog.page == 'index'}
  
  {$page->set([
    'title' => "{$blog.name}",
    'description' => "{if !empty($blog.summary)} {$blog.summary} {else} View all of the posts at {$blog.name} {/if}"
  ])}
  
  <div class="page-header"><h2>Blog Posts</h2></div><br>
  
{elseif $blog.page == 'search'}

  {$page->set([
    'title' => "Search: {$search} at {$blog.name}",
    'description' => "All of the search results at {$blog.name} for {$search}"
  ])}
  
  <div class="page-header"><h2>Search for '{$search}'</h2></div><br>
  
{elseif $blog.page == 'tag'}

  {$page->set([
    'title' => "Posts Tagged '{$tag}' at {$blog.name}",
    'description' => "View all posts at {$blog.name} that have been tagged with '{$tag}'"
  ])}
  
  <div class="page-header"><h2>Posts tagged '{$tag}'</h2></div><br>
  
{elseif $blog.page == 'category'}

  {$page->set([
    'title' => "{implode(' &raquo; ', $category)} at {$blog.name}",
    'description' => "View all of the posts at {$blog.name} that have been categorized under {implode(' &raquo; ', $category)}"
  ])}
  
  <div class="page-header"><h2>{implode(' &raquo; ', $category)} Category</h2></div><br>
  
{elseif $blog.page == 'author'}

  {$page->set([
    'title' => "Author: {$author.name} at {$blog.name}",
    'description' => "All of the blog posts at {$blog.name} that have been submitted by {$author.name}"
  ])}
  
  <div class="page-header"><h2>Author: {$author.name}</h2></div><br>
  
{elseif $blog.page == 'archive'}

  {if count($archive) == 4}
    {$date = $archive.date|date_format:'F j, Y'}
  {elseif count($archive) == 3}
    {$date = $archive.date|date_format:'F Y'}
  {elseif count($archive) == 2}
    {$date = $archive.date|date_format:'Y'}
  {/if}

  {$page->set([
    'title' => "{$date} Archives at {$blog.name}",
    'description' => "All of the blog posts at {$blog.name} that were published{if count($archive) == 4} on {else} in {/if}{$date}"
  ])}
  
  <div class="page-header"><h2>{$date} Archives</h2></div><br>
  
{/if}

{foreach $posts as $id => $post}
  <p itemscope itemtype="http://schema.org/Article">
    <a href="{$post.url}"><big itemprop="name">{$post.title}</big></a>
    <br>
    <span itemprop="headline">{($blog.page == 'search') ? $post.snippet : $post.description}</span>
  </p>
{/foreach}

{$bp->listings->pager($bp->listings->previous(), $bp->listings->next(), 'sides')}