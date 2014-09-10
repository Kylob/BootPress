{$bp->breadcrumbs($breadcrumbs)}

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