{if $blog.page != 'index'} {$bp->breadcrumbs($breadcrumbs)} {/if}

{if $search}

    {if $blog.page == 'index'}
        
        {$page->set([
            'title' => "Search for '{$search}' at {$blog.name}",
            'description' => "All of the search results at {$blog.name} for '{$search}'"
        ])}
        
    {elseif $blog.page == 'category'}
        
        {$page->set([
            'title' => "Search for '{$search}' in "|cat:implode(' &raquo; ', $category),
            'description' => "All of the search results for '{$search}' in "|cat:implode(' &raquo; ', $category)
        ])}
        
    {/if}

    <h2>Search Results for '{$search}'</h2>

{elseif $blog.page == 'index'}

    {$page->set([
        'title' => "{$blog.name}",
        'description' => "{if !empty($blog.summary)} {$blog.summary} {else} View all of the posts at {$blog.name} {/if}"
    ])}
    
    <h2>Blog Posts</h2>

{elseif $blog.page == 'category'}

    {$page->set([
        'title' => "{implode(' &raquo; ', $category)} at {$blog.name}",
        'description' => "View all of the posts at {$blog.name} that have been categorized under {implode(' &raquo; ', $category)}"
    ])}
    
    <h2>{implode(' &raquo; ', $category)} Posts</h2>

{elseif $blog.page == 'archives'}
    
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
    
    <h2>{$date} Archives</h2>
    
{elseif $blog.page == 'authors'}

    {$page->set([
        'title' => "{$author.name} at {$blog.name}",
        'description' => "All of the blog posts at {$blog.name} that have been submitted by {$author.name}"
    ])}
    
    <h2>Author: {$author.name}</h2>

{elseif $blog.page == 'tags'}

    {$page->set([
        'title' => "{$tag.name} at {$blog.name}",
        'description' => "Everything at {$blog.name} that has been tagged with '{$tag.name}'"
    ])}
    
    <h2>Tag: {$tag.name}</h2>

{/if}

{if !$bp->pagination->set('page', 10)}
    {$bp->pagination->total($page->blog($listings, 'count'))}
{/if}

{foreach $page->blog($listings, $bp->pagination) as $post}
    <p itemscope itemtype="http://schema.org/Article">
        <big itemprop="name"><a href="{$post.url}">{$post.title}</a></big>
        {if $search}
            <br>{$post.snippet}
        {elseif !empty($post.description)}
            <br><span itemprop="headline">{$post.description}</span>
        {/if}
    </p>
{/foreach}

{$bp->pagination->pager()}
