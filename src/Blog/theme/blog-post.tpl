{if $post.page}

    {$post.content}

{else}

    <div itemscope itemtype="http://schema.org/Article">
    
        <div class="page-header"><h1 itemprop="name">{$post.title}</h1></div><br>
        
        <div itemprop="articleBody" style="padding-bottom:40px;">{$post.content}</div>
        
        {foreach $post.tags as $tag}
            {if $tag@first} <p>Tagged: {/if}
            &nbsp;<a href="{$tag.url}" itemprop="keywords">{$tag.name}</a>
            {if $tag@last} </p> {/if}
        {/foreach}
        
        <p>
            {if !empty($post.published)} Published: <a href="{$post.archive}" itemprop="datePublished">{$post.published|date_format:'%B %e, %Y'}</a> {/if}
            {if !empty($post.author)} by <a href="{$post.author.url}" itemprop="author">{$post.author.name}</a> {/if}
        </p>
        
    </div>
    
    {$bp->pagination->pager($post.previous, $post.next)}
    
{/if}
