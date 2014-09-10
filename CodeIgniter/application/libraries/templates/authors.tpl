{$bp->breadcrumbs($breadcrumbs)}

<h2>Authors</h2><hr>
{foreach $authors as $author}
  <p><a href="{$author.url}">{$author.name} {$bp->badge($author.count)}</a></p>
{/foreach}