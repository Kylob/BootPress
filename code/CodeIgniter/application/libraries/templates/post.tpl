{if $post.page}

  {$post.content}

{else}

  <div itemscope itemtype="http://schema.org/Article">
    <div class="page-header"><h1 itemprop="name">{$post.title}</h1></div><br>
    
    <div itemprop="articleBody" style="padding-bottom:40px;">{$post.content}</div>
    
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
  
  {$bp->listings->symbols($bp->icon('chevron-left'), $bp->icon('chevron-right'))}
  {$bp->listings->pager($bp->listings->previous($post.previous.title, $post.previous.url), $bp->listings->next($post.next.title, $post.next.url), 'sides')}
  
{/if}  