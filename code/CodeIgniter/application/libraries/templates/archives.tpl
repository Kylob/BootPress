{$bp->breadcrumbs($breadcrumbs)}

{$page->set([
  'title' => "The Archives at {$blog.name}",
  'description' => "All of the blog posts at {$blog.name}.  Archived in the order they were received."
])}

<div class="page-header"><h2>The Archives</h2></div><br>

{foreach $archives as $Y => $years}
  <h3><a href="{$years.url}">{$Y}</a> {$bp->label('primary', $years.count)}</h3>
  {$columns = []}
  {foreach $years.months as $M => $months}
    {$columns[] = $bp->col('1 text-center', $bp->button(
      'link block',
      "$M {if $months.count > 0} <br> {$bp->label('primary', $months.count)} {/if}",
      ['href' => $months.url]
    ))}
  {/foreach}
  {$bp->row('sm', $columns)}
  <br>
{/foreach}