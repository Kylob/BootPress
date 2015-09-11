{$bp->breadcrumbs($breadcrumbs)}

{$page->set([
  'title' => "Tag Cloud at {$blog.name}",
  'description' => "View the most frequently used tags at {$blog.name}"
])}

<div class="page-header"><h2>Tag Cloud</h2></div><br>

<p>

  {foreach $tags as $tag => $links}
    {if $links.rank == 1}
      <a class="text-primary" style="font-size:15px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
    {elseif $links.rank == 2}
      <a class="text-info" style="font-size:18px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
    {elseif $links.rank == 3}
      <a class="text-success" style="font-size:21px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
    {elseif $links.rank == 4}
      <a class="text-warning" style="font-size:24px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
    {else}
      <a class="text-danger" style="font-size:27px; padding:0px 5px;" href="{$links.url}">{$tag}</a>
    {/if}
  {/foreach}
  
</p>