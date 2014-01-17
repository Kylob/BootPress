{$bp->row('sm', [
  $bp->col(9, "<h1>{$blog['name']}</h1>
              {if !empty($blog['slogan'])}
                <p class=/"lead/">{$blog['slogan']}</p>
              {/if}"),
  $bp->col(3, "<br>{$bp->search($blog['url'])}")
])}

{if isset($breadcrumbs)} {$bp->breadcrumbs($breadcrumbs)} {/if}

{if $blog['page'] == 'page'}

  <h2>{$page.title}</h2><hr>
  {$page.content}

{elseif $blog['page'] == 'post'}

  <div itemscope itemtype="http://schema.org/Article">
    <h2 itemprop="name">{$post.title}</h2></hr>
    <div itemprop="articleBody">{$post.content}</div>
    {foreach $post.tags as $tag => $link}
      {if $link@first} <p>Tagged: {/if}
      &nbsp;<a href="{$link}" itemprop="keywords">{$tag}</a>
      {if $link@last} </p> {/if}
    {/foreach}
    <p>
      Published: <a href="{$post.links.archive}" itemprop="datePublished">{$post.published|date_format:'%B %e, %Y'}</a>
      {if !empty($post.author)} by <a href="{$post.links.author}" itemprop="author">{$post.author}</a> {/if}
    </p>
  </div>
  
  <hr>
  
  {$list = $bp->listings()->symbols($bp->icon('chevron-left'), $bp->icon('chevron-right'))}
  {$list->pager($list->previous($previous.title, $previous.url), $list->next($next.title, $next.url), 'sides')}
  
  {if !empty($similar)}
    <h4>Similar Posts</h4>
    {foreach $similar as $posts}
      <p itemscope itemtype="http://schema.org/Article">
        <a href="{$posts.url}"><big itemprop="name">{$posts.title}</big></a>
        <br>
        <span itemprop="headline">{$posts.summary}</span>
      </p>
    {/foreach}
  {/if}

{elseif isset($posts)}

  {if $blog['page'] == 'index'}
  
  {else}
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
    {elseif $blog['page'] == 404}
      <h2>404 Not Found</h2>
      <p>Sorry, this page does not exist.</p>
      {if !empty($posts)}
        <p>You may, however, be able to find what you are looking for amongst the wild guesses we made below.</p>
      {/if}
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
  
{elseif $blog['page'] == 'archives'}

  {foreach $archives as $Y => $years}
    <h3><a href="{$years.link}">{$Y}</a> {$bp->label('primary', $years.count)}</h3>
    {$columns = []}
    {foreach $years.months as $M => $months}
      {$columns[] = $bp->col('1 text-center', $bp->button(
        'link block',
        "$M {if $months.count > 0} <br> {$bp->label('primary', $months.count)} {/if}",
        ['href' => $months.link]
      ))}
    {/foreach}
    {$bp->row('sm', $columns)}
    <br>
  {/foreach}

{elseif $blog['page'] == 'authors'}

  <h2>Authors</h2><hr>
  {foreach $authors as $author}
    <p><a href="{$author.url}">{$author.name} {$bp->badge($author.count)}</a></p>
  {/foreach}
  
{elseif $blog['page'] == 'tags'}

  <h2>Tag Cloud</h2><hr>
  <div style="max-width:500px; margin:20px auto; text-align:center;">
    {foreach $tags as $tag => $links}
      {if $links.rank == 1}
        <a style="color:#0744c0; font-size:15px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
      {elseif $links.rank == 2}
        <a style="color:#2b3aa3; font-size:18px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
      {elseif $links.rank == 3}
        <a style="color:#503186; font-size:21px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
      {elseif $links.rank == 4}
        <a style="color:#74276a; font-size:24px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
      {else}
        <a style="color:#991e4d; font-size:27px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
      {/if}
    {/foreach}
  </div>
  
{/if}

<p class="text-right">Built with <a href="http://bootpress.org">BootPress</a></p>