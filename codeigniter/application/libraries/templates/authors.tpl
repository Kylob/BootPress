{$bp->breadcrumbs($breadcrumbs)}

{$page->set([
  'title' => "Authors at {$blog.name}",
  'description' => "View all of the authors who have submitted blog posts at {$blog.name}"
])}

<div class="page-header"><h2>Authors</h2></div><br>

{foreach $authors as $author}
  <p><a href="{$author.url}">{$author.name} {$bp->badge($author.count)}</a></p>
{/foreach}