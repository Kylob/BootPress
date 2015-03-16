{$page->set([
  'title' => '',
  'description' => '',
  'keywords' => '',
  'published' => false
])}

{capture assign='markdown'}



{/capture}

{$bp->md($markdown)}