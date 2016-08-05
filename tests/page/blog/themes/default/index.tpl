{foreach $config as $key => $value}
    <p>{$key}: {$value}</p>
{/foreach}

<p>{$content} <a href="{$page->url('folder', $bp->framework|cat:'-'|cat:$bp->version|cat:'.css')}">{$bp->icon('file')}</a></p>

