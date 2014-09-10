<div itemscope itemtype="http://schema.org/Article">
  <h2 itemprop="name">{$post.title}</h2></hr>
  <div itemprop="articleBody">{$post.content}</div>
  {foreach $post.tags as $tag => $link}
    {if $link@first} <p>Tagged: {/if}
    &nbsp;<a href="{$link}" itemprop="keywords">{$tag}</a>
    {if $link@last} </p> {/if}
  {/foreach}
  <p>
    {if !empty($post.published)} Published: <a href="{$post.archive}" itemprop="datePublished">{$post.published|date_format:'%B %e, %Y'}</a> {/if}
    {if !empty($post.author)} by <a href="{$post.author.url}" itemprop="author">{$post.author.name}</a> {/if}
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