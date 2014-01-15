<div class="row">
  <div class="col-sm-9">
    <h1>{$blog['name']}</h1>
    {if !empty($blog['slogan'])}
      <p class="lead">{$blog['slogan']}</p>
    {/if}
  </div>
  <div class="col-sm-3">
    <br>
    <form class="form-inline" method="get" action="{$blog['url']}" autocomplete="off">
      <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="{if isset($search)}{$search}{else}Search{/if}">
        <div class="input-group-btn">
          <button type="submit" class="btn btn-default" title="Submit"><i class="glyphicon glyphicon-search"></i></button>
        </div>
      </div>
    </form>
  </div>
</div>

{if isset($breadcrumbs)}
  <ol class="breadcrumb">
    {foreach $breadcrumbs as $name => $link}
      {if $link@last}
        <li class="active">{$name}</li>
      {else}
        <li><a href="{$link}">{$name}</a></li>
      {/if}
    {/foreach}
  </ol>
{/if}

{if $blog['page'] == 'page'}

  <h2>{$page.title}</h2><hr>
  {$page.content}

{elseif $blog['page'] == 'post'}

  <div itemscope itemtype="http://schema.org/Article">
    <h2 itemprop="name">{$post.title}</h2></hr>
    <div itemprop="articleBody">{$post.content}</div>
    {foreach $post.tags as $tag => $link}
      {if $link@first} <p>Tagged: {/if}
      &nbsp;<a href="{$link}" itemprop="keywords">{$tag}</a>
      {if $link@last} </p> {/if}
    {/foreach}
    <p>
      Published: <a href="{$post.links.archive}" itemprop="datePublished">{$post.published|date_format:'%B %e, %Y'}</a>
      {if !empty($post.author)} by <a href="{$post.links.author}" itemprop="author">{$post.author}</a> {/if}
    </p>
  </div>
  
  <hr>
	
  {if !empty($previous) OR !empty($next)}
    <ul class="pager">
      {if !empty($previous)}
        <li class="previous"><a title="Previous Post" href="{$previous.url}">&larr; {$previous.title}</a></li>
      {/if}
      {if !empty($next)}
        <li class="next"><a title="Next Post" href="{$next.url}">{$next.title} &rarr;</a></li>
      {/if}
    </ul>
  {/if}
  
  {if !empty($similar)}
    <h4>Similar Posts</h4>
    {foreach $similar as $posts}
      <p itemscope itemtype="http://schema.org/Article">
        <a href="{$posts.url}"><big itemprop="name">{$posts.title}</big></a>
        <br>
        <span itemprop="headline">{$posts.summary}</span>
      </p>
    {/foreach}
  {/if}

{elseif isset($posts)}

  {if $blog['page'] == 'index'}
  
  {else}
    {if $blog['page'] == 'search'}
      <h2>Search: {$search}</h2>
    {elseif $blog['page'] == 'archive'}
      <h2>Archive: {$archive}</h2>
    {elseif $blog['page'] == 'author'}
      <h2>Author: {$author.name}</h2>
    {elseif $blog['page'] == 'category'}
      <h2>Category: {$category}</h2>
    {elseif $blog['page'] == 'tag'}
      <h2>Tag: {$tag}</h2>
    {elseif $blog['page'] == 404}
      <h2>404 Not Found</h2>
      <p>Sorry, this page does not exist.</p>
      {if !empty($posts)}
        <p>You may, however, be able to find what you are looking for amongst the wild guesses we made below.</p>
      {/if}
    {/if}
    <hr>
  {/if}
  
  {foreach $posts as $id => $post}
    <p itemscope itemtype="http://schema.org/Article">
      <a href="{$post.url}"><big itemprop="name">{$post.title}</big></a>
      <br>
      <span itemprop="headline">{$post.summary}</span>
    </p>
  {/foreach}
  
  {if !empty($pagination)}
    <div class="text-center">
      <ul class="pagination">
        {if !empty($pagination.first)}
          <li><a title="First" href="{$pagination.first}">&laquo;</a></li>
        {else}
          <li class="disabled"><span title="First">&laquo;</span></li>
        {/if}
        {if !empty($pagination.previous)}
          <li><a title="Previous" href="{$pagination.previous}">Previous</a></li>
        {else}
          <li class="disabled"><span title="Previous">Previous</span></li>
        {/if}
        {foreach $pagination.links as $num => $url}
          {if !empty($link)}
            <li><a title="{$num}" href="{$url}">{$num}</a></li>
          {else}
            <li class="active"><span title="{$num}">{$num}</span></li>
          {/if}
        {/foreach}
        {if !empty($pagination.next)}
          <li><a title="Next" href="{$pagination.next}">Next</a></li>
        {else}
          <li class="disabled"><span title="Next">Next</span></li>
        {/if}
        {if !empty($pagination.last)}
          <li><a title="Last" href="{$pagination.last}">&raquo;</a></li>
        {else}
          <li class="disabled"><span title="Last">&raquo;</span></li>
        {/if}
      </ul>
    </div>
  {/if}
  
  
{elseif $blog['page'] == 'archives'}

  {foreach $archives as $Y => $years}
    <h3><a href="{$years.link}">{$Y}</a> <span class="label label-primary">{$years.count}</span></h3>
    <div class="row text-center">
      {foreach $years.months as $M => $months}
        <div class="col-sm-1">
          <a class="btn btn-link btn-block" href="{$months.link}">
            {$M}
            {if $months.count > 0} <br><span class="label label-primary">{$months.count}</span> {/if}
          </a>
        </div>
      {/foreach}
    </div>
    <br>
  {/foreach}

{elseif $blog['page'] == 'authors'}

  <h2>Authors</h2><hr>
  {foreach $authors as $author}
    <p><a href="{$author.url}">{$author.name} <span class="badge">{$author.count}</span></a></p>
  {/foreach}
  
{elseif $blog['page'] == 'tags'}

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
  
{/if}

<p class="text-right">Built with <a href="http://bootpress.org">BootPress</a></p>