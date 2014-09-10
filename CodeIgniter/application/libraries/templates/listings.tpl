{if $blog['page'] == 'index'}

{else}

  {$bp->breadcrumbs($breadcrumbs)}

  {if $blog['page'] == 'search'}
    <h2>Search: {$search}</h2>
  {elseif $blog['page'] == 'archive'}
    <h2>Archive: {$archive}</h2>
  {elseif $blog['page'] == 'author'}
    <h2>Author: {$author.name}</h2>
  {elseif $blog['page'] == 'category'}
    <h2>Category: {$category}</h2>
  {elseif $blog['page'] == 'tag'}
    <h2>Tag: {$tag}</h2>
  {/if}
  <hr>
{/if}

{foreach $posts as $id => $post}
  <p itemscope itemtype="http://schema.org/Article">
    <a href="{$post.url}"><big itemprop="name">{$post.title}</big></a>
    <br>
    <span itemprop="headline">{$post.summary}</span>
  </p>
{/foreach}

<div class="text-center">{$bp->listings()->pagination()}</div>