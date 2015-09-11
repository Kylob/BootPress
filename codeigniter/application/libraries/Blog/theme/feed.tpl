<?xml version="1.0"?>
<rss version="2.0">
<channel>
  <title>{$blog.name}</title>
  <link>{$page->url('blog')}</link>
  <description>{$blog.summary}</description>
  {foreach $posts as $post}
    <item>
      <title>{$post.title}</title>
      <link>{$post.url}</link>
      <description><![CDATA[{$post.content}]]></description>
      <pubDate>{date(DateTime::RFC2822, $post.published)}</pubDate>
      <guid isPermaLink="true">{$post.url}</guid>
    </item>
  {/foreach}
</channel>
</rss>