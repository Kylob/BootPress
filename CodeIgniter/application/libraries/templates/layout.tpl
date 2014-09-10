<div class="container">

  {$bp->row('sm', [
    $bp->col(9, "<h1>{$blog['name']}</h1>
                {if !empty($blog['slogan'])}
                  <p class=\"lead\">{$blog['slogan']}</p>
                {/if}"),
    $bp->col(3, "<br>{$bp->search($blog['url'])}")
  ])}
  
  {$bp->row('sm', [
    $bp->col(12, $content)
  ])}
  
</div>