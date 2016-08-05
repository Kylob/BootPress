{$bp->breadcrumbs($breadcrumbs)}

{$page->set([
    'title' => "Tag Cloud at {$blog.name}",
    'description' => "View the most frequently used tags at {$blog.name}"
])}

<h2>Tag Cloud</h2>

<p>

    {foreach $tags as $tag}
        {if $tag.rank == 1}
            <a class="text-primary" style="font-size:15px; padding:0px 5px;" href="{$tag.url}">{$tag.name}</a>
        {elseif $tag.rank == 2}
            <a class="text-info" style="font-size:18px; padding:0px 5px;" href="{$tag.url}">{$tag.name}</a>
        {elseif $tag.rank == 3}
            <a class="text-success" style="font-size:21px; padding:0px 5px;" href="{$tag.url}">{$tag.name}</a>
        {elseif $tag.rank == 4}
            <a class="text-warning" style="font-size:24px; padding:0px 5px;" href="{$tag.url}">{$tag.name}</a>
        {else}
            <a class="text-danger" style="font-size:27px; padding:0px 5px;" href="{$tag.url}">{$tag.name}</a>
        {/if}
    {/foreach}
  
</p>
