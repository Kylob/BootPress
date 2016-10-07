<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Blog\Component as Blog;
use BootPress\Sitemap\Component as Sitemap;
use BootPress\Pagination\Component as Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Aptoma\Twig\Extension\MarkdownEngine\PHPLeagueCommonMarkEngine;

class BlogTest extends \BootPress\HTMLUnit\Component
{
    protected static $blog;
    protected static $config;
    protected static $folder;

    public static function tearDownAfterClass()
    {
        $dir = __DIR__.'/page/';
        foreach (array(
            $dir.'blog/content/category/unpublished-post',
            $dir.'blog/content/category/future-post',
            $dir.'blog/content/undefined',
            $dir.'blog/cache',
            $dir.'blog/plugins',
            $dir.'blog/themes',
            $dir.'blog/Blog.db',
            $dir.'blog/config.yml',
            $dir.'temp',
        ) as $target) {
            self::remove($target);
        }
    }

    public function testConstructorWithoutDir()
    {
        $request = Request::create('http://website.com/');
        $page = Page::html(array('dir' => __DIR__.'/page', 'suffix' => '.html'), $request, 'overthrow');
        $folder = $page->dir('temp');
        self::remove($folder);

        $blog = new Blog($folder);
        $this->assertNull($blog->missing);
        $this->assertEquals($folder, $blog->folder);
        $this->assertEquals(0, $blog->query(array())); // the number of blog posts and pages
        $this->assertNull($blog->query(array('archives' => array(1, 2, 3)))); // should only be 2 values
        $this->assertNull($blog->query(array('authors' => array(1, 2, 3)))); // should be a string
        $this->assertNull($blog->query(array('tags' => array(1, 2, 3)))); // should be a string
        $this->assertNull($blog->query(array(
            'categories' => array('string'),
        ))); // should either be a string, or an array of id's
        $this->assertNull($blog->query(array(
            'categories' => '',
            'search' => 'term',
        ))); // the category doesn't exist
        $this->assertNull($blog->query(array(
            'categories' => '',
        ))); // the category doesn't exist

        $blog->db->connection()->close(); // releases Blog.db so it can be deleted

        // Test url() method
        $this->assertEquals('lo-siento-no-hablo-espanol', $blog->url('Lo siento, no hablo español.'));

        // Test title() method
        $this->assertEquals('This is a Messy Title. [Can You Fix It?]', $blog->title('this IS a messy title. [can you fix it?]'));

        unset($blog);
        self::remove($folder);
    }

    public function testConstructorAndDestructor()
    {
        $request = Request::create('http://website.com/');
        $page = Page::html(array('dir' => __DIR__.'/page', 'suffix' => '.html'), $request, 'overthrow');

        // Remove files that may be lingering from previous tests, and set up for another round
        self::remove($page->file('Sitemap.db'));

        // set irrelevant config values
        static::$config = $page->file('blog/config.yml');
        self::remove(static::$config);
        file_put_contents(static::$config, Yaml::dump(array(
            'authors' => array(
                'joe-bloggs' => array(
                    'image' => 'user.jpg',
                ),
                'anonymous' => 'anonymous',
            ),
            'categories' => array('unknown' => 'UnKnown'),
            'tags' => array('not-exists' => 'What are you doing?'),
        ), 3));
        $this->assertEquals(implode("\n", array(
            'authors:',
            '    joe-bloggs:',
            '        image: user.jpg',
            '    anonymous: anonymous',
            'categories:',
            '    unknown: UnKnown',
            'tags:',
            "    not-exists: 'What are you doing?'",
        )), trim(file_get_contents(static::$config)));

        static::$folder = $page->dir('blog/content');
        $unpublished = static::$folder.'category/unpublished-post/index.html.twig';
        self::remove(dirname($unpublished));

        $db = $page->file('blog/Blog.db');
        self::remove($db);

        @rename($page->dir('blog/content/category'), $page->dir('blog/content/Category'));
        $themes = $page->dir('blog/themes');
        self::remove($themes);

        // Test Blog constructor, properties, and destructor
        $blog = new Blog($page->dir('blog'));
        $this->assertInstanceOf('BootPress\Database\Component', $blog->db);
        $this->assertAttributeInstanceOf('BootPress\Blog\Theme', 'theme', $blog);
        $this->assertAttributeEquals($page->dir('blog'), 'folder', $blog);
        $this->assertEquals($page->url['base'].'blog.html', $page->url('blog'));
        $this->assertEquals($page->url['base'].'blog/listings.html', $page->url('blog', 'listings'));
        unset($blog);
    }

    public function testAboutPage()
    {
        $template = $this->blogPage('about.html');
        $file = static::$folder.'about/index.html.twig';
        ##
        #  {#
        #  title: About
        #  published: true
        #  #}
        #  
        #  This is my website.
        ##
        $this->assertEqualsRegExp('This is my website.', static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-page.html.twig', $template['file']);
        $this->assertEquals(array(
            'page' => array(
                'title' => 'About',
                'published' => true,
            ),
            'path' => 'about',
            'url' => 'http://website.com/about.html',
            'title' => 'About',
            'content' => 'This is my website.',
            'updated' => filemtime($file),
            'featured' => false,
            'published' => true,
            'categories' => array(),
            'tags' => array(),
        ), $template['vars']);
    }

    public function testCategorySimplePost()
    {
        $template = $this->blogPage('category/simple-post.html');
        $file = static::$folder.'category/simple-post/index.html.twig';
        ##
        #  {#
        #  title: A Simple Post
        #  keywords: Simple, Markdown
        #  published: Aug 3, 2010
        #  author: Joe Bloggs
        #  #}
        #  
        #  {% markdown %}
        #  
        #  ### Header
        #  
        #  Paragraph
        #  
        #  {% endmarkdown %}
        ##
        $this->assertEqualsRegExp(array(
            '<div itemscope itemtype="http://schema.org/Article">',
                '<div class="page-header"><h1 itemprop="name">A Simple Post</h1></div><br>',
                '<div itemprop="articleBody" style="padding-bottom:40px;">',
                    '<h3>Header</h3>',
                    '<p>Paragraph</p>',
                '</div>',
                '<p>Tagged:',
                    '&nbsp;<a href="http://website.com/blog/tags/simple.html" itemprop="keywords">Simple</a>',
                    '&nbsp;<a href="http://website.com/blog/tags/markdown.html" itemprop="keywords">Markdown</a>',
                '</p>',
                '<p>',
                    'Published:',
                    '<a href="http://website.com/blog/archives/2010/08/03.html" itemprop="datePublished">August 3, 2010</a>',
                    'by <a href="http://website.com/blog/authors/joe-bloggs.html" itemprop="author">Joe Bloggs</a>',
                '</p>',
            '</div>',
            '<ul class="pager">',
                '<li class="next"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post &raquo;</a></li>',
            '</ul>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-post.html.twig', $template['file']);
        $this->assertEqualsRegExp('<h3>Header</h3><p>Paragraph</p>', $template['vars']['post']['content']);
        unset($template['vars']['post']['content']);
        $this->assertEquals(array(
            'post' => array(
                'page' => array(
                    'title' => 'A Simple Post',
                    'keywords' => 'Simple, Markdown',
                    'published' => 'Aug 3, 2010',
                    'author' => 'Joe Bloggs',
                ),
                'path' => 'category/simple-post',
                'url' => 'http://website.com/category/simple-post.html',
                'title' => 'A Simple Post',
                'updated' => filemtime($file),
                'featured' => false,
                'published' => strtotime('Aug 3, 2010'),
                'categories' => array(
                    array(
                        'name' => 'Category',
                        'path' => 'category',
                        'url' => 'http://website.com/category.html',
                        'image' => '',
                    ),
                ),
                'tags' => array(
                    array(
                        'name' => 'Simple',
                        'path' => 'simple',
                        'url' => 'http://website.com/blog/tags/simple.html',
                        'image' => '',
                    ),
                    array(
                        'name' => 'Markdown',
                        'path' => 'markdown',
                        'url' => 'http://website.com/blog/tags/markdown.html',
                        'image' => '',
                    ),
                ),
                'author' => array(
                    'name' => 'Joe Bloggs',
                    'path' => 'joe-bloggs',
                    'url' => 'http://website.com/blog/authors/joe-bloggs.html',
                    'image' => 'http://website.com/page/blog/user.jpg',
                ),
                'archive' => 'http://website.com/blog/archives/2010/08/03.html',
                'previous' => null,
                'next' => array(
                    'url' => 'http://website.com/category/subcategory/flowery-post.html',
                    'title' => 'A Flowery Post',
                ),
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
                'A Simple Post' => 'http://website.com/category/simple-post.html',
            ),
        ), $template['vars']);
    }

    public function testCategorySubcategoryFeaturedPost()
    {
        $template = $this->blogPage('category/subcategory/featured-post.html');
        $file = static::$folder.'category/subcategory/featured-post/index.html.twig';
        ##
        #  {#
        #  title: A Featured Post
        #  keywords: Featured, markdown
        #  published: Sep 12, 2010
        #  author: jOe bLoGgS
        #  featured: true
        #  #}
        #  
        #  {% markdown %}
        #  
        #  1. One
        #  2. Two
        #  3. Three
        #  
        #  {% endmarkdown %}
        ##
        $this->assertEqualsRegExp(array(
            '<div itemscope itemtype="http://schema.org/Article">',
                '<div class="page-header"><h1 itemprop="name">A Featured Post</h1></div><br>',
                '<div itemprop="articleBody" style="padding-bottom:40px;">',
                    '<ol>',
                        '<li>One</li>',
                        '<li>Two</li>',
                        '<li>Three</li>',
                    '</ol>',
                '</div>',
                '<p>Tagged:',
                    '&nbsp;<a href="http://website.com/blog/tags/markdown.html" itemprop="keywords">Markdown</a>',
                    '&nbsp;<a href="http://website.com/blog/tags/featured.html" itemprop="keywords">Featured</a>',
                '</p>',
                '<p>',
                    'Published:',
                    '<a href="http://website.com/blog/archives/2010/09/12.html" itemprop="datePublished">September 12, 2010</a>',
                    'by <a href="http://website.com/blog/authors/joe-bloggs.html" itemprop="author">Joe Bloggs</a>',
                '</p>',
            '</div>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-post.html.twig', $template['file']);
        $this->assertEqualsRegExp('<ol><li>One</li><li>Two</li><li>Three</li></ol>', $template['vars']['post']['content']);
        unset($template['vars']['post']['content']);
        $this->assertEquals(array(
            'post' => array(
                'page' => array(
                    'title' => 'A Featured Post',
                    'keywords' => 'Featured, markdown',
                    'published' => 'Sep 12, 2010',
                    'author' => 'jOe bLoGgS',
                    'featured' => true,
                ),
                'path' => 'category/subcategory/featured-post',
                'url' => 'http://website.com/category/subcategory/featured-post.html',
                'title' => 'A Featured Post',
                'updated' => filemtime($file),
                'featured' => true,
                'published' => strtotime('Sep 12, 2010'),
                'categories' => array(
                    array(
                        'name' => 'Category',
                        'path' => 'category',
                        'url' => 'http://website.com/category.html',
                        'image' => '',
                    ),
                    array(
                        'name' => 'Subcategory',
                        'path' => 'category/subcategory',
                        'url' => 'http://website.com/category/subcategory.html',
                        'image' => '',
                    ),
                ),
                'tags' => array(
                    array(
                        'name' => 'Markdown',
                        'path' => 'markdown',
                        'url' => 'http://website.com/blog/tags/markdown.html',
                        'image' => '',
                    ),
                    array(
                        'name' => 'Featured',
                        'path' => 'featured',
                        'url' => 'http://website.com/blog/tags/featured.html',
                        'image' => '',
                    ),
                ),
                'author' => array(
                    'name' => 'Joe Bloggs',
                    'path' => 'joe-bloggs',
                    'url' => 'http://website.com/blog/authors/joe-bloggs.html',
                    'image' => 'http://website.com/page/blog/user.jpg',
                ),
                'archive' => 'http://website.com/blog/archives/2010/09/12.html',
                'previous' => null,
                'next' => null,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
                'Subcategory' => 'http://website.com/category/subcategory.html',
                'A Featured Post' => 'http://website.com/category/subcategory/featured-post.html',
            ),
        ), $template['vars']);
    }

    public function testCategorySubcategoryFloweryPost()
    {
        $template = $this->blogPage('category/subcategory/flowery-post.html');
        $file = static::$folder.'category/subcategory/flowery-post/index.html.twig';
        ##
        #  {#
        #  Title: A Flowery Post
        #  Description: Aren't they beautiful?
        #  Keywords: Flowers, nature
        #  Published: Sep 12, 2010
        #  #}
        #  
        #  {{ page.title }}
        #  
        #  <img src="{{ 'flowers.jpg'|asset }}">
        #  
        #  Aren't they beautiful?
        ##
        $image = Page::html()->url('page', 'blog/content/category/subcategory/flowery-post/flowers.jpg');
        $this->assertEqualsRegExp(array(
            '<div itemscope itemtype="http://schema.org/Article">',
                '<div class="page-header"><h1 itemprop="name">A Flowery Post</h1></div><br>',
                '<div itemprop="articleBody" style="padding-bottom:40px;">',
                    'A Flowery Post',
                    '<img src="'.$image.'">',
                    "Aren't they beautiful?",
                '</div>',
                '<p>Tagged:',
                    '&nbsp;<a href="http://website.com/blog/tags/flowers.html" itemprop="keywords">Flowers</a>',
                    '&nbsp;<a href="http://website.com/blog/tags/nature.html" itemprop="keywords">Nature</a>',
                '</p>',
                '<p>',
                    'Published:',
                    '<a href="http://website.com/blog/archives/2010/09/12.html" itemprop="datePublished">September 12, 2010</a>',
                '</p>',
            '</div>',
            '<ul class="pager">',
                '<li class="previous"><a href="http://website.com/category/simple-post.html">&laquo; A Simple Post</a></li>',
                '<li class="next"><a href="http://website.com/uncategorized-post.html">Uncategorized Post &raquo;</a></li>',
            '</ul>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-post.html.twig', $template['file']);
        $image = Page::html()->url('page', 'blog/content/category/subcategory/flowery-post/flowers.jpg');
        $this->assertEqualsRegExp(array(
            'A Flowery Post',
            '<img src="'.$image.'">',
            "Aren't they beautiful?",
        ), $template['vars']['post']['content']);
        unset($template['vars']['post']['content']);
        $this->assertEquals(array(
            'post' => array(
                'page' => array(
                    'title' => 'A Flowery Post',
                    'description' => 'Aren\'t they beautiful?',
                    'keywords' => 'Flowers, nature',
                    'published' => 'Sep 12, 2010',
                ),
                'path' => 'category/subcategory/flowery-post',
                'url' => 'http://website.com/category/subcategory/flowery-post.html',
                'title' => 'A Flowery Post',
                'updated' => filemtime($file),
                'featured' => false,
                'published' => strtotime('Sep 12, 2010'),
                'categories' => array(
                    array(
                        'name' => 'Category',
                        'path' => 'category',
                        'url' => 'http://website.com/category.html',
                        'image' => '',
                    ),
                    array(
                        'name' => 'Subcategory',
                        'path' => 'category/subcategory',
                        'url' => 'http://website.com/category/subcategory.html',
                        'image' => '',
                    ),
                ),
                'tags' => array(
                    array(
                        'name' => 'Flowers',
                        'path' => 'flowers',
                        'url' => 'http://website.com/blog/tags/flowers.html',
                        'image' => '',
                    ),
                    array(
                        'name' => 'Nature',
                        'path' => 'nature',
                        'url' => 'http://website.com/blog/tags/nature.html',
                        'image' => '',
                    ),
                ),
                'author' => array(),
                'archive' => 'http://website.com/blog/archives/2010/09/12.html',
                'previous' => array(
                    'url' => 'http://website.com/category/simple-post.html',
                    'title' => 'A Simple Post',
                ),
                'next' => array(
                    'url' => 'http://website.com/uncategorized-post.html',
                    'title' => 'Uncategorized Post',
                ),
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
                'Subcategory' => 'http://website.com/category/subcategory.html',
                'A Flowery Post' => 'http://website.com/category/subcategory/flowery-post.html',
            ),
        ), $template['vars']);
        $template = $this->blogPage('category/subcategory/flowery-post.html?search=beauty');
        $this->assertEquals(array('beautiful'), $template['vars']['search']);
    }

    // http://wpcandy.s3.amazonaws.com/resources/postsxml.zip
    // https://wpcom-themes.svn.automattic.com/demo/theme-unit-test-data.xml
    public function testIndexPage()
    {
        $template = $this->blogPage('');
        $file = static::$folder.'index.html.twig';
        ##
        #  {#
        #  title: Welcome to My Website
        #  keywords: simple, markDown
        #  published: true
        #  #}
        #  
        #  {% markdown %}
        #  
        #  This is the index page.
        #  
        #  {% endmarkdown %}
        ##
        $this->assertEqualsRegExp('<p>This is the index page.</p>', static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-page.html.twig', $template['file']);
        $this->assertEquals(array(
            'page' => array(
                'title' => 'Welcome to My Website',
                'keywords' => 'simple, markDown',
                'published' => true,
            ),
            'path' => '',
            'url' => 'http://website.com/',
            'title' => 'Welcome to My Website',
            'content' => '<p>This is the index page.</p>',
            'updated' => filemtime($file),
            'featured' => false,
            'published' => true,
            'categories' => array(),
            'tags' => array(
                array(
                    'name' => 'Simple',
                    'path' => 'simple',
                    'url' => 'http://website.com/blog/tags/simple.html',
                    'image' => '',
                ),
                array(
                    'name' => 'Markdown',
                    'path' => 'markdown',
                    'url' => 'http://website.com/blog/tags/markdown.html',
                    'image' => '',
                ),
            ),
        ), $template['vars']);
    }

    public function testUncategorizedPost()
    {
        $template = $this->blogPage('uncategorized-post.html');
        $file = static::$folder.'uncategorized-post/index.html.twig';
        ##
        #  {#
        #  Title: Uncategorized Post
        #  Published: Oct 3, 2010
        #  #}
        #  
        #  A post without a category
        ##
        $this->assertEqualsRegExp(array(
            '<div itemscope itemtype="http://schema.org/Article">',
                '<div class="page-header"><h1 itemprop="name">Uncategorized Post</h1></div><br>',
                '<div itemprop="articleBody" style="padding-bottom:40px;">',
                    'A post without a category',
                '</div>',
                '<p>',
                    'Published:',
                    '<a href="http://website.com/blog/archives/2010/10/03.html" itemprop="datePublished">October 3, 2010</a>',
                '</p>',
            '</div>',
            '<ul class="pager">',
                '<li class="previous"><a href="http://website.com/category/subcategory/flowery-post.html">&laquo; A Flowery Post</a></li>',
            '</ul>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-post.html.twig', $template['file']);
        $this->assertEquals(array(
            'post' => array(
                'page' => array(
                    'title' => 'Uncategorized Post',
                    'published' => 'Oct 3, 2010',
                ),
                'path' => 'uncategorized-post',
                'url' => 'http://website.com/uncategorized-post.html',
                'title' => 'Uncategorized Post',
                'content' => 'A post without a category',
                'updated' => filemtime($file),
                'featured' => false,
                'published' => strtotime('Oct 3, 2010'),
                'categories' => array(),
                'tags' => array(),
                'author' => array(),
                'archive' => 'http://website.com/blog/archives/2010/10/03.html',
                'previous' => array(
                    'url' => 'http://website.com/category/subcategory/flowery-post.html',
                    'title' => 'A Flowery Post',
                ),
                'next' => null,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Uncategorized Post' => 'http://website.com/uncategorized-post.html',
            ),
        ), $template['vars']);
    }

    public function testBlogListings()
    {
        $template = $this->blogPage('blog.html');
        $this->assertEqualsRegExp(array(
            '<h2>Blog Posts</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/uncategorized-post.html">Uncategorized Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br><span itemprop="headline">Aren\'t they beautiful?</span>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'listings' => array(),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(4, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 7, 5, 3), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 7 - uncategorized (Oct 3 2010)
        // 5 - flowery (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category
    }

    public function testBlogListingsSearch()
    {
        $page = Page::html();
        $search = '"simple post"';
        $template = $this->blogPage('blog.html', array('search' => $search));
        $this->assertEqualsRegExp(array(
            '<h2>Search Results for ""simple post""</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
                '<br>A <b>Simple</b> <b>Post</b>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'listings' => array(
                'search' => $search,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Search' => $page->url('add', 'http://website.com/blog.html', 'search', $search),
            ),
            'search' => $search,
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        unset($template['vars']['listings']['count']); // to test the actual query
        $this->assertEquals(1, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(3), array_keys($listings));
        // 3 - simple (Aug 3 2008) category
        $this->assertEquals('A <b>Simple</b> <b>Post</b>', $listings[3]['snippet']);
        $this->assertEquals(array('simple post'), $listings[3]['words']);
    }

    public function testBlogCategoriesSearch()
    {
        $page = Page::html();
        $search = 'beauty';
        $template = $this->blogPage('category.html', array('search' => $search));
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/category.html">Category</a></li>',
                '<li class="active">Search</li>',
            '</ul>',
            '<h2>Search Results for "beauty"</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br>Aren\'t they <b>beautiful</b>?',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'listings' => array(
                'categories' => array(1, 2),
                'search' => $search,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
                'Search' => $page->url('add', 'http://website.com/category.html', 'search', $search),
            ),
            'category' => array(
                'Category',
            ),
            'categories' => array(
                array(
                    'name' => 'Category',
                    'path' => 'category',
                    'url' => 'http://website.com/category.html',
                    'image' => '',
                    'count' => 3,
                    'subs' => array(
                        array(
                            'name' => 'Subcategory',
                            'path' => 'category/subcategory',
                            'url' => 'http://website.com/category/subcategory.html',
                            'image' => '',
                            'count' => 2,
                        ),
                    ),
                ),
            ),
            'search' => $search,
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        unset($template['vars']['listings']['count']); // to test the actual query
        $this->assertEquals(1, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(5), array_keys($listings));
        // 5 - flowery (Sep 12 2008) category/subcategory
        $this->assertEquals('Aren\'t they <b>beautiful</b>?', $listings[5]['snippet']);
        $this->assertEquals(array('beautiful'), $listings[5]['words']);
    }

    public function testSimilarQuery()
    {
        $template = $this->blogPage(''); // keywords: simple, markDown
        $posts = static::$blog->query('similar', 10); // determined via $page->keywords
        $this->assertEquals(array(3, 4), array_keys($posts));
        // 3 - simple (Aug 3 2008) category - keywords: Simple, Markdown
        // 4 - featured (Sep 12 2008) category/subcategory - keywords: Featured, markdown

        // manual query
        $posts = static::$blog->query('similar', array(5, 'simple')); // specify keywords to use
        $this->assertEquals(array(3), array_keys($posts));
        $posts = static::$blog->query('similar', array(5 => 'simple')); // specify keywords to use
        $this->assertEquals(array(3), array_keys($posts));
        $posts = static::$blog->query('similar', array(5 => 'not-exists'));
        $this->assertEquals(array(), $posts); // no results
    }

    public function testFeaturedQuery()
    {
        $posts = static::$blog->query('featured');
        $this->assertEquals(array(4), array_keys($posts));
        // 4 - featured (Sep 12 2008) category/subcategory - keywords: Featured, markdown
    }

    public function testRecentQuery()
    {
        $posts = static::$blog->query('recent');
        $this->assertEquals(array(7, 5, 3), array_keys($posts));
        // 7 - uncategorized (Oct 3 2010)
        // 5 - flowery (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category - keywords: Simple, Markdown
    }

    public function testPostsQuery()
    {
        $posts = static::$blog->query('posts', array(
            'uncategorized-post',
            'nonexistant-post',
            'category/subcategory/flowery-post',
            'category/subcategory/featured-post',
        ));
        $this->assertEquals(array(7, 5, 4), array_keys($posts));
        // 7 - uncategorized (Oct 3 2010)
        // 5 - flowery (Sep 12 2008) category/subcategory
        // 4 - featured (Sep 12 2008) category/subcategory
    }

    public function testCategoriesQuery()
    {
        $this->assertEquals(array(
            array(
                'name' => 'Category',
                'path' => 'category',
                'url' => 'http://website.com/category.html',
                'image' => '',
                'count' => 3,
                'subs' => array(
                    array(
                        'name' => 'Subcategory',
                        'path' => 'category/subcategory',
                        'url' => 'http://website.com/category/subcategory.html',
                        'image' => '',
                        'count' => 2,
                    ),
                ),
            ),
        ), static::$blog->query('categories', 5));
        $this->assertEquals(array(), static::$blog->query('categories', 0)); // limit 0
    }

    public function testBlogCategoryListings()
    {
        self::remove(static::$folder.'undefined');
        mkdir(static::$folder.'undefined', 0755, true); // to bypass preliminary folder check
        $this->assertFalse($this->blogPage('undefined.html'));

        $template = $this->blogPage('category.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li class="active">Category</li>',
            '</ul>',
            '<h2>Category Posts</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br><span itemprop="headline">Aren\'t they beautiful?</span>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'listings' => array(
                'categories' => array(1, 2),
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
            ),
            'category' => array(
                'Category',
            ),
            'categories' => array(
                array(
                    'name' => 'Category',
                    'path' => 'category',
                    'url' => 'http://website.com/category.html',
                    'image' => '',
                    'count' => 3,
                    'subs' => array(
                        array(
                            'name' => 'Subcategory',
                            'path' => 'category/subcategory',
                            'url' => 'http://website.com/category/subcategory.html',
                            'image' => '',
                            'count' => 2,
                        ),
                    ),
                ),
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(3, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(3, static::$blog->query(array('categories' => 'category'), 'count')); // to test string conversion
        $this->assertEquals(array(4, 5, 3), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 5 - flowery (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category
    }

    public function testBlogCategorySubcategoryListings()
    {
        $template = $this->blogPage('category/subcategory.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/category.html">Category</a></li>',
                '<li class="active">Subcategory</li>',
            '</ul>',
            '<h2>Category &raquo; Subcategory Posts</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br><span itemprop="headline">Aren\'t they beautiful?</span>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'listings' => array(
                'categories' => array(2),
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Category' => 'http://website.com/category.html',
                'Subcategory' => 'http://website.com/category/subcategory.html',
            ),
            'category' => array(
                'Category',
                'Subcategory',
            ),
            'categories' => array(
                array(
                    'name' => 'Subcategory',
                    'path' => 'category/subcategory',
                    'url' => 'http://website.com/category/subcategory.html',
                    'image' => '',
                    'count' => 2,
                ),
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(2, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 5), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 5 - flowery (Sep 12 2008) category/subcategory
    }

    public function testArchivesListings()
    {
        $template = $this->blogPage('blog/archives.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li class="active">Archives</li>',
            '</ul>',
            '<h2>The Archives</h2>',
            '<h3><a href="http://website.com/blog/archives/2010.html">2010</a> <span class="label label-primary">4</span></h3>',
            '<div class="row">',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/01.html" class="btn btn-link btn-block">Jan', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/02.html" class="btn btn-link btn-block">Feb', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/03.html" class="btn btn-link btn-block">Mar', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/04.html" class="btn btn-link btn-block">Apr', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/05.html" class="btn btn-link btn-block">May', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/06.html" class="btn btn-link btn-block">Jun', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/07.html" class="btn btn-link btn-block">Jul', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/08.html" class="btn btn-link btn-block">Aug', '<br> <span class="label label-primary">1 </span>', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/09.html" class="btn btn-link btn-block">Sep', '<br> <span class="label label-primary">2 </span>', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/10.html" class="btn btn-link btn-block">Oct', '<br> <span class="label label-primary">1 </span>', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/11.html" class="btn btn-link btn-block">Nov', '</a>',
                '</div>',
                '<div class="col-sm-1 text-center">',
                    '<a href="http://website.com/blog/archives/2010/12.html" class="btn btn-link btn-block">Dec', '</a>',
                '</div>',
            '</div>',
            '<br>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-archives.html.twig', $template['file']);
        $this->assertEquals(array(
            'archives' => array(
                2010 => array(
                    'count' => 4,
                    'url' => 'http://website.com/blog/archives/2010.html',
                    'months' => array(
                        'Jan' => array(
                            'url' => 'http://website.com/blog/archives/2010/01.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 1, 15, 2010),
                        ),
                        'Feb' => array(
                            'url' => 'http://website.com/blog/archives/2010/02.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 2, 15, 2010),
                        ),
                        'Mar' => array(
                            'url' => 'http://website.com/blog/archives/2010/03.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 3, 15, 2010),
                        ),
                        'Apr' => array(
                            'url' => 'http://website.com/blog/archives/2010/04.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 4, 15, 2010),
                        ),
                        'May' => array(
                            'url' => 'http://website.com/blog/archives/2010/05.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 5, 15, 2010),
                        ),
                        'Jun' => array(
                            'url' => 'http://website.com/blog/archives/2010/06.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 6, 15, 2010),
                        ),
                        'Jul' => array(
                            'url' => 'http://website.com/blog/archives/2010/07.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 7, 15, 2010),
                        ),
                        'Aug' => array(
                            'url' => 'http://website.com/blog/archives/2010/08.html',
                            'count' => 1,
                            'time' => mktime(0, 0, 0, 8, 15, 2010),
                        ),
                        'Sep' => array(
                            'url' => 'http://website.com/blog/archives/2010/09.html',
                            'count' => 2,
                            'time' => mktime(0, 0, 0, 9, 15, 2010),
                        ),
                        'Oct' => array(
                            'url' => 'http://website.com/blog/archives/2010/10.html',
                            'count' => 1,
                            'time' => mktime(0, 0, 0, 10, 15, 2010),
                        ),
                        'Nov' => array(
                            'url' => 'http://website.com/blog/archives/2010/11.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 11, 15, 2010),
                        ),
                        'Dec' => array(
                            'url' => 'http://website.com/blog/archives/2010/12.html',
                            'count' => 0,
                            'time' => mktime(0, 0, 0, 12, 15, 2010),
                        ),
                    ),
                ),
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Archives' => 'http://website.com/blog/archives.html',
            ),
        ), $template['vars']);
    }

    public function testArchivesYearlyListings()
    {
        $template = $this->blogPage('blog/archives/2010.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/blog/archives.html">Archives</a></li>',
                '<li class="active">2010</li>',
            '</ul>',
            '<h2>2010 Archives</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/uncategorized-post.html">Uncategorized Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br><span itemprop="headline">Aren\'t they beautiful?</span>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'archive' => array(
                'date' => 1262304000,
                'year' => 2010,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Archives' => 'http://website.com/blog/archives.html',
                '2010' => 'http://website.com/blog/archives/2010.html',
            ),
            'listings' => array(
                'archives' => array(1262304000, 1293839999), // from, to
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(4, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 7, 5, 3), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 7 - uncategorized (Oct 3 2010)
        // 5 - flowery (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category
    }

    public function testArchivesMonthlyListings()
    {
        $template = $this->blogPage('blog/archives/2010/09.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/blog/archives.html">Archives</a></li>',
                '<li><a href="http://website.com/blog/archives/2010.html">2010</a></li>',
                '<li class="active">September</li>',
            '</ul>',
            '<h2>September 2010 Archives</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/flowery-post.html">A Flowery Post</a></big>',
                '<br><span itemprop="headline">Aren\'t they beautiful?</span>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'archive' => array(
                'date' => mktime(0, 0, 0, 9, 1, 2010),
                'year' => 2010,
                'month' => 'September',
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Archives' => 'http://website.com/blog/archives.html',
                '2010' => 'http://website.com/blog/archives/2010.html',
                'September' => 'http://website.com/blog/archives/2010/09.html',
            ),
            'listings' => array(
                'archives' => array(mktime(0, 0, 0, 9, 1, 2010), mktime(23, 59, 59, 10, 0, 2010)), // from, to
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(2, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 5), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 5 - flowery (Sep 12 2008) category/subcategory
    }

    public function testArchivesDailyListings()
    {
        $template = $this->blogPage('blog/archives/2010/10/03.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/blog/archives.html">Archives</a></li>',
                '<li><a href="http://website.com/blog/archives/2010.html">2010</a></li>',
                '<li><a href="http://website.com/blog/archives/2010/10.html">October</a></li>',
                '<li class="active">3</li>',
            '</ul>',
            '<h2>October 3, 2010 Archives</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/uncategorized-post.html">Uncategorized Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'archive' => array(
                'date' => mktime(0, 0, 0, 10, 3, 2010),
                'year' => 2010,
                'month' => 'October',
                'day' => 3,
            ),
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Archives' => 'http://website.com/blog/archives.html',
                '2010' => 'http://website.com/blog/archives/2010.html',
                'October' => 'http://website.com/blog/archives/2010/10.html',
                '3' => 'http://website.com/blog/archives/2010/10/03.html',
            ),
            'listings' => array(
                'archives' => array(mktime(0, 0, 0, 10, 3, 2010), mktime(23, 59, 59, 10, 3, 2010)), // from, to
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        $this->assertEquals(1, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(7), array_keys($listings));
        // 7 - uncategorized (Oct 3 2010)
    }

    public function testAuthorsListings()
    {
        $template = $this->blogPage('blog/authors.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li class="active">Authors</li>',
            '</ul>',
            '<h2>Authors</h2>',
            '<p><a href="http://website.com/blog/authors/joe-bloggs.html">Joe Bloggs <span class="badge">2</span></a></p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-authors.html.twig', $template['file']);
        $this->assertEquals(array(
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Authors' => 'http://website.com/blog/authors.html',
            ),
            'authors' => array(
                array(
                    'name' => 'Joe Bloggs',
                    'path' => 'joe-bloggs',
                    'url' => 'http://website.com/blog/authors/joe-bloggs.html',
                    'image' => 'http://website.com/page/blog/user.jpg',
                    'latest' => strtotime('Sep 12, 2010'),
                    'count' => 2,
                ),
            ),
        ), $template['vars']);

        // manual query
        $authors = static::$blog->query('authors', 5); // limit 5 authors
        $this->assertEquals(array(
            array(
                'name' => 'Joe Bloggs',
                'path' => 'joe-bloggs',
                'url' => 'http://website.com/blog/authors/joe-bloggs.html',
                'image' => 'http://website.com/page/blog/user.jpg',
                'latest' => strtotime('Sep 12, 2010'),
                'count' => 2,
            ),
        ), $authors);
    }

    public function testAuthorsIndividualListings()
    {
        $this->assertFalse($this->blogPage('blog/authors/kyle-gadd.html'));

        $template = $this->blogPage('blog/authors/joe-bloggs.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/blog/authors.html">Authors</a></li>',
                '<li class="active">Joe Bloggs</li>',
            '</ul>',
            '<h2>Author: Joe Bloggs</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Authors' => 'http://website.com/blog/authors.html',
                'Joe Bloggs' => 'http://website.com/blog/authors/joe-bloggs.html',
            ),
            'author' => array(
                'name' => 'Joe Bloggs',
                'path' => 'joe-bloggs',
                'url' => 'http://website.com/blog/authors/joe-bloggs.html',
                'image' => 'http://website.com/page/blog/user.jpg',
                'latest' => strtotime('Sep 12, 2010'),
                'count' => 2,
            ),
            'listings' => array(
                'count' => 2,
                'authors' => 'joe-bloggs',
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        unset($template['vars']['listings']['count']); // to test the actual query
        $this->assertEquals(2, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 3), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category
    }

    public function testTagsListings()
    {
        $template = $this->blogPage('blog/tags.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li class="active">Tags</li>',
            '</ul>',
            '<h2>Tag Cloud</h2>',
            '<p>',
                '<a class="text-primary" style="font-size:15px; padding:0px 5px;" href="http://website.com/blog/tags/featured.html">Featured</a>',
                '<a class="text-primary" style="font-size:15px; padding:0px 5px;" href="http://website.com/blog/tags/flowers.html">Flowers</a>',
                '<a class="text-danger" style="font-size:27px; padding:0px 5px;" href="http://website.com/blog/tags/markdown.html">Markdown</a>',
                '<a class="text-primary" style="font-size:15px; padding:0px 5px;" href="http://website.com/blog/tags/nature.html">Nature</a>',
                '<a class="text-primary" style="font-size:15px; padding:0px 5px;" href="http://website.com/blog/tags/simple.html">Simple</a>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-tags.html.twig', $template['file']);
        $this->assertEquals(array(
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Tags' => 'http://website.com/blog/tags.html',
            ),
            'tags' => array(
                array(
                    'name' => 'Featured',
                    'path' => 'featured',
                    'url' => 'http://website.com/blog/tags/featured.html',
                    'image' => '',
                    'latest' => strtotime('Sep 12, 2010'),
                    'count' => 1,
                    'rank' => 1,
                ),
                array(
                    'name' => 'Flowers',
                    'path' => 'flowers',
                    'url' => 'http://website.com/blog/tags/flowers.html',
                    'image' => '',
                    'latest' => strtotime('Sep 12, 2010'),
                    'count' => 1,
                    'rank' => 1,
                ),
                array(
                    'name' => 'Markdown',
                    'path' => 'markdown',
                    'url' => 'http://website.com/blog/tags/markdown.html',
                    'image' => '',
                    'latest' => strtotime('Sep 12, 2010'),
                    'count' => 2,
                    'rank' => 5,
                ),
                array(
                    'name' => 'Nature',
                    'path' => 'nature',
                    'url' => 'http://website.com/blog/tags/nature.html',
                    'image' => '',
                    'latest' => strtotime('Sep 12, 2010'),
                    'count' => 1,
                    'rank' => 1,
                ),
                array(
                    'name' => 'Simple',
                    'path' => 'simple',
                    'url' => 'http://website.com/blog/tags/simple.html',
                    'image' => '',
                    'latest' => strtotime('Aug 3, 2010'),
                    'count' => 1,
                    'rank' => 1,
                ),
            ),
        ), $template['vars']);

        // manual query
        $this->assertEquals(array(
            array(
                'name' => 'Markdown',
                'path' => 'markdown',
                'url' => 'http://website.com/blog/tags/markdown.html',
                'image' => '',
                'latest' => strtotime('Sep 12, 2010'),
                'count' => 2,
                'rank' => 1,
            ),
        ), static::$blog->query('tags', 1)); // limit 1 tag - for 2 or more the rank becomes ambiguous
    }

    public function testTagsIndividualListings()
    {
        $this->assertFalse($this->blogPage('blog/tags/undefined.html'));

        $template = $this->blogPage('blog/tags/markdown.html');
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="http://website.com/blog.html">Blog</a></li>',
                '<li><a href="http://website.com/blog/tags.html">Tags</a></li>',
                '<li class="active">Markdown</li>',
            '</ul>',
            '<h2>Tag: Markdown</h2>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/subcategory/featured-post.html">A Featured Post</a></big>',
            '</p>',
            '<p itemscope itemtype="http://schema.org/Article">',
                '<big itemprop="name"><a href="http://website.com/category/simple-post.html">A Simple Post</a></big>',
            '</p>',
        ), static::$blog->theme->renderTwig($template));
        $this->assertEquals('blog-listings.html.twig', $template['file']);
        $this->assertEquals(array(
            'breadcrumbs' => array(
                'Blog' => 'http://website.com/blog.html',
                'Tags' => 'http://website.com/blog/tags.html',
                'Markdown' => 'http://website.com/blog/tags/markdown.html',
            ),
            'tag' => array(
                'name' => 'Markdown',
                'path' => 'markdown',
                'url' => 'http://website.com/blog/tags/markdown.html',
                'image' => '',
                'latest' => strtotime('Sep 12, 2010'),
                'count' => 2,
            ),
            'listings' => array(
                'count' => 2,
                'tags' => 'markdown',
            ),
        ), $template['vars']);
        $pagination = new Pagination();
        $listings = static::$blog->query($template['vars']['listings'], $pagination);
        unset($template['vars']['listings']['count']); // to test the actual query
        $this->assertEquals(2, static::$blog->query($template['vars']['listings'], 'count'));
        $this->assertEquals(array(4, 3), array_keys($listings));
        // 4 - featured (Sep 12 2008) category/subcategory
        // 3 - simple (Aug 3 2008) category
    }

    public function testFeedListings()
    {
        $template = $this->blogPage('blog/feed.rss');
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0"?>',
            '<rss version="2.0">',
            '<channel>',
                '<title>{{ .* }}</title>',
                '<link>http://website.com/blog.html</link>',
                '<description></description>',
                    '<item>',
                        '<title>A Featured Post</title>',
                        '<link>http://website.com/category/subcategory/featured-post.html</link>',
                        '<description><![CDATA[',
                            '<ol>',
                                '<li>One</li>',
                                '<li>Two</li>',
                                '<li>Three</li>',
                            '</ol>',
                        ']]></description>',
                        '<pubDate>'.date(\DATE_RFC2822, strtotime('Sep 12, 2010')).'</pubDate>',
                        '<guid isPermaLink="true">http://website.com/category/subcategory/featured-post.html</guid>',
                    '</item>',
                    '<item>',
                        '<title>Uncategorized Post</title>',
                        '<link>http://website.com/uncategorized-post.html</link>',
                        '<description><![CDATA[',
                            'A post without a category',
                        ']]></description>',
                        '<pubDate>'.date(\DATE_RFC2822, strtotime('Oct 3, 2010')).'</pubDate>',
                        '<guid isPermaLink="true">http://website.com/uncategorized-post.html</guid>',
                    '</item>',
                    '<item>',
                        '<title>A Flowery Post</title>',
                        '<link>http://website.com/category/subcategory/flowery-post.html</link>',
                        '<description><![CDATA[',
                            'A Flowery Post',
                            '<img src="http://website.com/page/blog/content/category/subcategory/flowery-post/flowers.jpg">',
                            'Aren\'t they beautiful?',
                        ']]></description>',
                        '<pubDate>'.date(\DATE_RFC2822, strtotime('Sep 12, 2010')).'</pubDate>',
                        '<guid isPermaLink="true">http://website.com/category/subcategory/flowery-post.html</guid>',
                    '</item>',
                    '<item>',
                        '<title>A Simple Post</title>',
                        '<link>http://website.com/category/simple-post.html</link>',
                        '<description><![CDATA[',
                            '<h3>Header</h3>',
                            '<p>Paragraph</p>',
                        ']]></description>',
                        '<pubDate>'.date(\DATE_RFC2822, strtotime('Aug 3, 2010')).'</pubDate>',
                        '<guid isPermaLink="true">http://website.com/category/simple-post.html</guid>',
                    '</item>',
                '</channel>',
            '</rss>',
        ), static::$blog->theme->renderTwig($template));
        unset($template['vars']['content']);
        $this->assertEquals('', $template['file']);
        $this->assertEquals('rss', $template['type']);
        $this->assertEquals(array(), $template['vars']);
        $this->assertFileExists($template['default']);
    }

    public function testNewPageInsertUpdateDelete()
    {
        $file = str_replace('/', DIRECTORY_SEPARATOR, static::$folder.'category/unpublished-post/index.html.twig');
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        self::remove($file);
        file_put_contents($file, implode("\n", array(
            '{#',
            'title: Unpublished Post',
            'keywords: Unpublished',
            'published: true', // will add to sitemap
            'author: anonymous',
            '#}',
            '',
            'Twig {{ file_get_contents("../undefined.txt") }} Error',
        )));
        $template = $this->blogPage('category/unpublished-post.html');
        $sitemap = new Sitemap();
        $this->assertEquals(1, $sitemap->db->value('SELECT COUNT(*) FROM sitemap WHERE path = ?', 'category/unpublished-post'));
        $this->assertEquals('blog-page.html.twig', $template['file']);
        $this->assertEquals(array(
            'page' => array(
                'title' => 'Unpublished Post',
                'keywords' => 'Unpublished',
                'published' => true,
                'author' => 'anonymous',
            ),
            'path' => 'category/unpublished-post',
            'url' => 'http://website.com/category/unpublished-post.html',
            'title' => 'Unpublished Post',
            'content' => '<p>Unknown "file_get_contents" function in "blog/content/category/unpublished-post/index.html.twig" at line 8.</p>',
            'updated' => filemtime($file),
            'featured' => false,
            'published' => true,
            'categories' => array(
                array(
                    'name' => 'Category',
                    'path' => 'category',
                    'url' => 'http://website.com/category.html',
                    'image' => '',
                ),
            ),
            'tags' => array(
                array(
                    'name' => 'Unpublished',
                    'path' => 'unpublished',
                    'url' => 'http://website.com/blog/tags/unpublished.html',
                    'image' => '',
                ),
            ),
        ), $template['vars']);
        file_put_contents($file, implode("\n", array(
            '{#',
            'title: Unpublished Post',
            'keywords: Unpublished',
            'published: false', // will remove from sitemap
            '#}',
            '',
            'The "{{ _self.getTemplateName() }}" {{ strtoupper("Template") }}',
        )));
        $template = $this->blogPage('category/unpublished-post.html');
        $this->assertEquals(0, $sitemap->db->value('SELECT COUNT(*) FROM sitemap WHERE path = ?', 'category/unpublished-post'));
        unset($sitemap);
        $this->assertEquals('blog-page.html.twig', $template['file']);
        $this->assertEquals(array(
            'page' => array(
                'title' => 'Unpublished Post',
                'keywords' => 'Unpublished',
                'published' => false,
            ),
            'path' => 'category/unpublished-post',
            'url' => 'http://website.com/category/unpublished-post.html',
            'title' => 'Unpublished Post',
            'content' => 'The "blog/content/category/unpublished-post/index.html.twig" TEMPLATE',
            'updated' => filemtime($file),
            'featured' => false,
            'published' => false,
            'categories' => array(
                array(
                    'name' => 'Category',
                    'path' => 'category',
                    'url' => 'http://website.com/category.html',
                    'image' => '',
                ),
            ),
            'tags' => array(
                array(
                    'name' => 'Unpublished',
                    'path' => 'unpublished',
                    'url' => 'http://website.com/blog/tags/unpublished.html',
                    'image' => '',
                ),
            ),
        ), $template['vars']);
        $template = $this->blogPage('category/unpublished-post.html'); // is not updated

        // verify seo folders enforced on a single access
        rename(static::$folder.'category/unpublished-post', static::$folder.'category/Unpublished--post');
        $this->assertFileExists(static::$folder.'category/Unpublished--post');
        $this->assertFalse(static::$blog->file('category/Unpublished--post'));
        $this->assertFileNotExists(static::$folder.'category/Unpublished--post');
        $this->assertFileExists(static::$folder.'category/unpublished-post');

        self::remove(dirname($file));
        $this->assertFalse($this->blogPage('category/unpublished-post.html')); // an orphaned directory
    }

    public function testFuturePost()
    {
        $file = str_replace('/', DIRECTORY_SEPARATOR, static::$folder.'category/future-post/index.html.twig');
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        self::remove($file);

        // Set a post published date an hour into the future
        $now = time();
        file_put_contents($file, implode("\n", array(
            '{#',
            'title: Future Post',
            'published: '.date('M j Y h:i:s a', $now + 3600),
            '#}',
            '',
            "I'm from 'da future",
        )));
        touch($file, $now - 3600); // so the file will re-update itself
        $template = $this->blogPage('category/future-post.html');
        $this->assertEquals($now + 3600, static::$blog->future_post);
        $this->assertNull($template['vars']['post']['previous']);

        // Reset it to now
        file_put_contents($file, implode("\n", array(
            '{#',
            'title: Future Post',
            'published: '.date('M j Y h:i:s a', $now),
            '#}',
            '',
            "I'm from 'da future",
        )));
        $template = $this->blogPage('category/future-post.html');

        // Set the future_post time to an hour past
        static::$blog->db->settings('future_post', $now - 3600);
        $template = $this->blogPage('category/future-post.html');
        $this->assertFalse(static::$blog->future_post);
        $this->assertFalse(static::$blog->db->settings('future_post'));
        $this->assertEquals('Uncategorized Post', $template['vars']['post']['previous']['title']);
        self::remove(dirname($file));
        $this->assertFalse($this->blogPage('category/future-post.html')); // an orphaned directory
    }

    public function testUpdatedConfigFile()
    {
        $this->assertFileExists(static::$config);
        $this->assertEquals(implode("\n", array(
            'blog:',
            "    name: 'Another { BootPress } Site'",
            "    image: ''",
            "    summary: ''",
            '    listings: blog',
            '    breadcrumb: Blog',
            '    theme: default',
            'authors:',
            '    joe-bloggs:',
            "        name: 'Joe Bloggs'",
            '        image: user.jpg',
            '    anonymous:',
            '        name: anonymous',
            'categories:',
            '    category:',
            '        name: Category',
            '    category/subcategory:',
            '        name: Subcategory',
            '    unknown:',
            '        name: UnKnown',
            'tags:',
            '    featured:',
            '        name: Featured',
            '    flowers:',
            '        name: Flowers',
            '    markdown:',
            '        name: Markdown',
            '    nature:',
            '        name: Nature',
            '    simple:',
            '        name: Simple',
            '    unpublished:',
            '        name: Unpublished',
            '    not-exists:',
            "        name: 'What are you doing?'",
        )), trim(file_get_contents(static::$config)));
        $this->assertEquals(array(
            'blog' => array(
                'name' => 'Another { BootPress } Site',
                'image' => '',
                'summary' => '',
                'listings' => 'blog',
                'breadcrumb' => 'Blog',
                'theme' => 'default',
            ),
            'authors' => array(
                'joe-bloggs' => array(
                    'name' => 'Joe Bloggs',
                    'image' => 'user.jpg',
                ),
                'anonymous' => array(
                    'name' => 'anonymous',
                ),
            ),
            'categories' => array(
                'category' => array(
                    'name' => 'Category',
                ),
                'category/subcategory' => array(
                    'name' => 'Subcategory',
                ),
                'unknown' => array(
                    'name' => 'UnKnown',
                ),
            ),
            'tags' => array(
                'featured' => array(
                    'name' => 'Featured',
                ),
                'flowers' => array(
                    'name' => 'Flowers',
                ),
                'markdown' => array(
                    'name' => 'Markdown',
                ),
                'nature' => array(
                    'name' => 'Nature',
                ),
                'simple' => array(
                    'name' => 'Simple',
                ),
                'unpublished' => array(
                    'name' => 'Unpublished',
                ),
                'not-exists' => array(
                    'name' => 'What are you doing?',
                ),
            ),
        ), static::$blog->config());
        $this->assertEquals(array(
            'name' => 'Another { BootPress } Site',
            'image' => '',
            'summary' => '',
            'listings' => 'blog',
            'breadcrumb' => 'Blog',
            'theme' => 'default',
        ), static::$blog->config('blog'));
        $this->assertEquals('blog', static::$blog->config('blog', 'listings'));
        $this->assertEquals('Joe Bloggs', static::$blog->config('authors', 'joe-bloggs', 'name'));
        $this->assertNull(static::$blog->config('authors', 'anonymous', 'image'));
    }

    public function testThemeGlobalVarsMethod()
    {
        $this->blogPage('theme.html');
        static::$blog->theme->globalVars('foo', array('bar'));
        static::$blog->theme->globalVars(array(
            'foo' => array('baz', 'qux'),
            'hodge' => 'podge',
        ));
        $this->assertAttributeEquals(array(
            'foo' => array('bar', 'baz', 'qux'),
            'hodge' => 'podge',
            'blog' => array(
                'name' => 'Another { BootPress } Site',
                'image' => '',
                'summary' => '',
                'listings' => 'blog',
                'breadcrumb' => 'Blog',
                'theme' => 'default',
            ),
        ), 'vars', static::$blog->theme);
        static::$blog->theme->globalVars('foo', 'bar');
        $this->assertAttributeEquals(array(
            'foo' => 'bar',
            'hodge' => 'podge',
            'blog' => array(
                'name' => 'Another { BootPress } Site',
                'image' => '',
                'summary' => '',
                'listings' => 'blog',
                'breadcrumb' => 'Blog',
                'theme' => 'default',
            ),
        ), 'vars', static::$blog->theme);
    }

    public function testThemeAddPageMethodMethod()
    {
        static::$blog->theme->addPageMethod('hello', function () {return 'World';});
        $this->setExpectedException('\LogicException');
        static::$blog->theme->addPageMethod('amigo', 'Hello');
    }

    public function testThemeFetchTwigBlogFoldersException()
    {
        $this->setExpectedException('\LogicException');
        static::$blog->theme->renderTwig('');
    }

    public function testThemeFetchTwigMissingFileException()
    {
        $this->setExpectedException('\LogicException');
        static::$blog->theme->renderTwig(static::$folder.'missing.html.twig');
    }

    public function testThemeFetchTwigDefaultFile()
    {
        $page = Page::html();
        $default = $page->file('default.html.twig');
        file_put_contents($default, 'Default {% template %}');

        // Syntax Error
        $this->assertEquals('<p>Unknown "template" tag in "blog/themes/default/default.html.twig" at line 1.</p>', static::$blog->theme->renderTwig(array(
            'default' => $page->dir(),
            'vars' => array('syntax' => 'error'),
            'file' => 'default.html.twig',
        )));

        // Testing Mode
        unlink($default);
        $default = $page->file('blog/themes/default/default.html.twig');
        $this->assertFileExists($default);
        $this->assertEquals('<p>Unknown "template" tag in "blog/themes/default/default.html.twig" at line 1.</p>', static::$blog->theme->renderTwig($default, array('syntax' => 'error'), 'testing'));
        unlink($default);
    }

    public function testThemeMarkdownMethod()
    {
        $this->assertEquals('<p>There is no &quot;I&quot; in den<strong>i</strong>al</p>', trim(static::$blog->theme->markdown('There is no "I" in den**i**al')));
        $engine = new \BootPress\Blog\Markdown(static::$blog->theme);
        $this->assertEquals('Blog\Markdown', $engine->getName());
        $this->assertNull(static::$blog->theme->markdown(new PHPLeagueCommonMarkEngine()));
    }

    public function testThemeAssetAndThisMethods()
    {
        $asset = array(
            'image.jpg?query=string' => '.jpg image',
            'png image.png' => 'path/image.png',
        );
        $this->assertEquals(array(
            'http://website.com/page/blog/themes/default/image.jpg?query=string' => '.jpg image',
            'png image.png' => 'http://website.com/page/blog/themes/default/path/image.png',
        ), static::$blog->theme->asset($asset));

        // Twig_Template instance
        $twig = static::$blog->theme->getTwig()->loadTemplate('blog/content/index.html.twig');
        $this->assertInstanceOf('\Twig_Template', $twig);
        $this->assertEquals(array(
            'http://website.com/page/blog/content/image.jpg?query=string' => '.jpg image',
            'png image.png' => 'http://website.com/page/blog/content/path/image.png',
        ), static::$blog->theme->asset($asset, $twig));

        // Test "this"
        $this->assertEquals(array(), static::$blog->theme->this($twig)); // return all values
        $this->assertNull(static::$blog->theme->this($twig, 'key', 'value')); // set a single value
        $this->assertNull(static::$blog->theme->this($twig, array(
            'one' => 1,
            'two' => 2,
            'three' => 3,
        ))); // set multiple values
        $this->assertEquals(2, static::$blog->theme->this($twig, 'two')); // return a single value
        $this->assertNull(static::$blog->theme->this($twig, 'two', null)); // remove a single value
        $this->assertAttributeEquals(array(
            'blog/content/index.html.twig' => array(
                'one' => 1,
                'three' => 3,
                'key' => 'value',
            ),
        ), 'plugin', static::$blog->theme);
        $this->assertEquals(array(
            'one' => 1,
            'three' => 3,
            'key' => 'value',
        ), static::$blog->theme->this($twig));
        $this->assertNull(static::$blog->theme->this($twig, null)); // remove all values
        $this->assertEquals(array(), static::$blog->theme->this($twig));
    }

    public function testThemeDumpMethod()
    {
        $this->assertContains('Sfdump = window.Sfdump', static::$blog->theme->dump());
        $this->assertContains('BootPress\Blog\Component Object', static::$blog->theme->dump(static::$blog));
        $this->assertContains('BootPress\Blog\Component Object', static::$blog->theme->dump(array(
            'blog' => static::$blog,
            'vars' => array(),
        )));
    }

    public function testThemeLayoutMethod()
    {
        $page = Page::html();
        $layout = $page->dir('blog/themes/default');
        $this->assertFileExists($layout); // should be created automatically
        $this->assertEquals('<p>Content</p>', static::$blog->theme->layout('<p>Content</p>'));
        file_put_contents($layout.'index.html.twig', implode("\n", array(
            '{{ page.amigo }} {{ page.hello() }} War',
            '{{ content }}',
        )));
        $this->assertEqualsRegExp(array(
            'World War',
            '<p>Content</p>',
        ), static::$blog->theme->layout('<p>Content</p>'));

        // test default theme selection
        mkdir($layout.'child', 0755, true);
        $page->theme = 'default/child';
        file_put_contents($layout.'config.yml', 'default: theme'."\n".'name: Parent');
        file_put_contents($layout.'child/config.yml', 'name: Child');
        file_put_contents($layout.'child/index.html.twig', '{{ config.name }} {{ config.default }}');
        $this->assertEquals('Child theme', static::$blog->theme->layout(''));

        // No Theme
        $page->theme = false;
        $this->assertEquals('HTML', static::$blog->theme->layout('HTML'));

        // Callable Theme
        $page->theme = function ($content, $vars) {
            return 'Callable '.$content;
        };
        $this->assertEquals('Callable HTML', static::$blog->theme->layout('HTML'));

        // File Theme
        $page->theme = $layout.'theme.php';
        file_put_contents($page->theme, implode("\n", array(
            '<?php',
            'extract($params);',
            'echo "File {$content}";',
        )));
        $this->assertEquals('File HTML', static::$blog->theme->layout('HTML'));
    }

    public function testTwigUndefinedFunctionCalls()
    {
        $request = Request::create('http://website.com/');
        Page::html(array(
            'testing' => true,
            'dir' => __DIR__.'/page',
            'url' => 'http://website.com/',
            'suffix' => '.html',
        ), $request, 'overthrow');
        $blog = new Blog('blog');
        $twig = $blog->theme->getTwig(array('cache' => false));
        $twig->setLoader(new \Twig_Loader_Array(array(
            // Array Functions
            'array_change_key_case' => '{% set array = {FirSt: 1, SecOnd: 4} %} {{ array_change_key_case(array)|keys|join(",") }},{{ array_change_key_case(array, constant("CASE_UPPER"))|keys|join(",") }}',
            'array_chunk' => '{{ array_chunk(["a", "b", "c", "d", "e"], 3)|first|join(",") }}',
            'array_column' => '{{ array_column([{id:1, name:"John"}, {id:2, name:"Sally"}], "name")|join(",") }}',
            'array_combine' => '{% set fruit = array_combine(["red", "yellow"], ["apple", "banana"]) %} {{ fruit.red }},{{ fruit.yellow }}',
            'array_count_values' => '{% set count = array_count_values([1, "hello", 1, "world", "hello"]) %} {{ count.hello }}',
            'array_diff_assoc' => '{{ array_diff_assoc({a:"green", b:"brown", c:"blue", 0:"red"}, {a:"green", 0:"yellow", 1:"red"})|join(",") }}',
            'array_diff_key' => '{{ array_diff_key({blue:1, red:2, green:3, purple:4}, {green:5, blue:6, yellow:7, cyan:8})|keys|join(",") }}',
            'array_diff' => '{{ array_diff({a:"green", 0:"red", 1:"blue", 2:"red"}, {b:"green", 0:"yellow", 1:"red"})|first }}',
            'array_fill_keys' => '{% set array = array_fill_keys(["foo", 5, 10, "bar"], "banana") %} {{ array.foo }},{{ array.5 }},{{ array.10 }},{{ array.bar }}',
            'array_fill' => '{% set array = array_fill(5, 2, "banana") %} {{ array.5 }},{{ array.6 }},{{ array|join(",") }}',
            'array_filter' => '{{ array_filter(["foo", false, -1, null, ""])|join(",") }}',
            'array_flip' => '{{ array_flip({a:1, b:1, c:2})|join(",") }}',
            'array_intersect_assoc' => '{{ array_intersect_assoc({a:"green", b:"brown", c:"blue", 0:"red"}, {a:"green", b:"yellow", 0:"blue", 1:"red"})|join(",") }}',
            'array_intersect_key' => '{{ array_intersect_key({blue:1, red:2, green:3, purple:4}, {green:5, blue:6, yellow:7, cyan:8})|keys|join(",") }}',
            'array_intersect' => '{{ array_intersect({a:"green", 0:"red", 1:"blue"}, {b:"green", 0:"yellow", 1:"red"})|join(",") }}',
            'array_key_exists' => '{{ array_key_exists("first", {first:1, second:4}) ? "true" : "false" }}',
            'array_keys' => '{{ array_keys({0:100, color:"red"})|join(",") }}',
            'array_map' => '{{ array_map("sqrt", [1,4,9])|join(",") }}',
            'array_merge_recursive' => '{% set array = array_merge_recursive({color:{favorite:"red"}, 0:5}, {0:10, color:{favorite:"green", 0:"blue"}}) %} {{ array.color.favorite|join(",") }}',
            'array_merge' => '{{ array_merge({color:"red", 0:2, 1:4}, {0:"a", 1:"b", color:"green", shape:"trapezoid", 2:4})|join(",") }}',
            'array_pad' => '{{ array_pad([12, 10, 9], 5, 0)|join(",") }}',
            'array_product' => '{{ array_product([2, 4, 6, 8]) }}',
            'array_rand' => '{% set array = ["Neo", "Morpheus", "Trinity", "Cypher", "Tank"] %} {% set key = array_rand(array, 1) %} {{ array[key] ? "true" : key }}',
    'array_replace_recursive' => '{% set basket = array_replace_recursive({citrus:["orange"], berries:["blackberry", "raspberry"]}, {citrus:["pineapple"], berries:["blueberry"]}) %} {{ basket.berries|join(",") }}',
            'array_replace' => '{{ array_replace(["orange", "banana", "apple", "raspberry"], {0:"pineapple", 4:"cherry"}, ["grape"])|join(",") }}',
            'array_reverse' => '{{ array_reverse(["php", 4.0, "version"])|join(",") }}',
            'array_search' => '{{ array_search("green", ["blue", "red", "green", "red"]) }}',
            'array_slice' => '{{ array_slice(["a", "b", "c", "d", "e"], 2)|join(",") }}',
            'array_sum' => '{{ array_sum([2, 4, 6, 8]) }}',
            'array_unique' => '{{ array_unique({a:"green", 0:"red", b:"green", 1:"blue", 2:"red"})|join(",") }}',
            'array_values' => '{{ array_values({size:"XL",color:"gold"})|join(",") }}',
            'count' => '{{ count([1, 3, 5]) }}',
            'in_array' => '{{ in_array("Irix", ["Mac", "NT", "Irix", "Linux"]) ? "true" : "false" }}',

            // Date/Time Functions
            'date_parse' => '{% set date = date_parse("2006-12-12 10:00:00.5 +1 week +1 hour") %} {{ date.year }}-{{ date.month }}-{{ date.day }}',
            'date_sun_info' => '{% set date = date_sun_info(strtotime("2006-12-12"), 31.7667, 35.2333) %} {{ date.sunset ? "yes" : "no" }}',
            'getdate' => '{% set date = getdate(strtotime("2006-12-12")) %} {{ date.weekday }}',
            'gettimeofday' => '{% set date = gettimeofday() %} {{ date.sec ? "yes" : "no" }}',
            'gmdate' => '{{ gmdate("M d Y H:i:s", mktime(0, 0, 0, 1, 1, 1998)) }}',
            'gmmktime' => '{{ gmmktime(0, 0, 0, 7, 1, 2000)|date("l") }}',
            'microtime' => '{{ microtime(true) > 0 ? "yes" : "no" }}',
            'mktime' => '{{ mktime(0, 0, 0, 12, 32, 1997)|date("M-d-Y") }}',
            'strtotime' => '{{ strtotime("next Thursday") > 0 ? "yes" : "no" }}',
            'time' => '{{ time() > 0 ? "yes" : "no" }}',

            // JSON Functions
            'json_decode' => '{{ json_decode(\'{"a":1,"b":2,"c":3,"d":4,"e":5}\', true)|keys|join(",") }}',
            'json_encode' => '{{ json_encode({a:1, b:2, c:3, d:4, e:5}) }}',

            // Mail Functions
            'mail' => '{{ mail("nobody@example.com", "Subject", "Message") ? "Sent" : "Sent" }}',

            // Math Functions '{{ (5) > 0 ? "yes" : "no" }}',
            'abs' => '{{ abs(-5) }}',
            'acos' => '{{ is_nan(acos(5)) ? "yes" : "no" }}',
            'acosh' => '{{ acosh(5) > 0 ? "yes" : "no" }}',
            'asin' => '{{ asin(5) > 0 ? "yes" : "no" }}',
            'asinh' => '{{ asinh(5) > 0 ? "yes" : "no" }}',
            'atan2' => '{{ atan2(5, 5) > 0 ? "yes" : "no" }}',
            'atan' => '{{ atan(5) > 0 ? "yes" : "no" }}',
            'atanh' => '{{ atanh(5) > 0 ? "yes" : "no" }}',
            'base_convert' => '{{ base_convert("a37334", 16, 2) }}',
            'bindec' => '{{ bindec("000110011") }}',
            'ceil' => '{{ ceil(4.3) }}',
            'cos' => '{{ cos(constant("M_PI")) }}',
            'cosh' => '{{ cosh(constant("M_PI")) > 0 ? "yes" : "no" }}',
            'decbin' => '{{ decbin(26) }}',
            'dechex' => '{{ dechex(47) }}',
            'decoct' => '{{ decoct(264) }}',
            'deg2rad' => '{{ deg2rad(45) > 0 ? "yes" : "no" }}',
            'exp' => '{{ exp(5.7) > 0 ? "yes" : "no" }}',
            'expm1' => '{{ expm1(5.7) > 0 ? "yes" : "no" }}',
            'floor' => '{{ floor(4.3) }}',
            'fmod' => '{{ fmod(5.7, 1.3) > 0 ? "yes" : "no" }}',
            'getrandmax' => '{{ getrandmax() > 0 ? "yes" : "no" }}',
            'hexdec' => '{{ hexdec("See") }}',
            'hypot' => '{{ hypot(30, 40) > 0 ? "yes" : "no" }}',
            'is_finite' => '{{ is_finite(3.4) ? "true" : "false" }}',
            'is_infinite' => '{{ is_infinite(3.4) ? "true" : "false" }}',
            'is_nan' => '{{ is_nan(acos(8)) ? "true" : "false" }}',
            'lcg_value' => '{{ lcg_value() < 2 ? "true" : "false" }}',
            'log10' => '{{ log10(3.5) > 0 ? "yes" : "no" }}',
            'log1p' => '{{ log1p(3.5) > 0 ? "yes" : "no" }}',
            'log' => '{{ log(3.5) > 0 ? "yes" : "no" }}',
            'mt_getrandmax' => '{{ mt_getrandmax() > 0 ? "yes" : "no" }}',
            'mt_rand' => '{{ mt_rand(5, 15) < 20 ? "yes" : "no" }}',
            'mt_srand' => '{{ mt_srand(40) }}',
            'octdec' => '{{ octdec("77") }}',
            'pi' => '{{ floor(pi()) }}',
            'pow' => '{{ pow(-1, 20) }}',
            'rad2deg' => '{{ rad2deg(constant("M_PI_4")) }}',
            'rand' => '{{ rand(5, 15) < 20 ? "yes" : "no" }}',
            'round' => '{{ round(3.4) }}',
            'sin' => '{{ sin(deg2rad(60)) > 0 ? "yes" : "no" }}',
            'sinh' => '{{ sinh(3.4) > 0 ? "yes" : "no" }}',
            'sqrt' => '{{ sqrt(9) }}',
            'srand' => '{{ srand(40) }}',
            'tan' => '{{ tan(constant("M_PI_4")) }}',
            'tanh' => '{{ tanh(constant("M_PI_4")) > 0 ? "yes" : "no" }}',

            // Misc Functions
            'pack' => '{{ unpack("c", pack("c", 5))|join(",") }}',
            'unpack' => '{{ unpack("cchars/nint", "\x04\x00\xa0\x00")|join(",") }}',

            // Multibyte String Functions
            'mb_convert_case' => '{{ mb_convert_case("mary had a Little lamb", constant("MB_CASE_UPPER"), "UTF-8") }}',
            'mb_convert_encoding' => '{{ mb_convert_encoding("Índia", "ASCII", "UTF-8") }}',
            'mb_strimwidth' => '{{ mb_strimwidth("Hello World", 0, 10, "...") }}',
            'mb_stripos' => '{{ mb_stripos("ABC", "b") }}',
            'mb_stristr' => '{{ mb_stristr("USER@EXAMPLE.com", "e") }}',
            'mb_strlen' => '{{ mb_strlen("abcdef") }}',
            'mb_strpos' => '{{ mb_strpos("abc", "b") }}',
            'mb_strrchr' => '{{ mb_strrchr("/www/public_html/index.html", "/") }}',
            'mb_strrichr' => '{{ mb_strrichr("/www/public_html/index.html", "/") }}',
            'mb_strripos' => '{{ mb_strripos("/www/public_html/index.html", "/") }}',
            'mb_strrpos' => '{{ mb_strrpos("/www/public_html/index.html", "/") }}',
            'mb_strstr' => '{{ mb_strstr("user@example.com", "e") }}',
            'mb_strtolower' => '{{ mb_strtolower("Mary Had A Little Lamb") }}',
            'mb_strtoupper' => '{{ mb_strtoupper("Mary Had A Little Lamb") }}',
            'mb_strwidth' => '{{ mb_strwidth("Hello World") }}',
            'mb_substr_count' => '{{ mb_substr_count("This is a test", "is") }}',
            'mb_substr' => '{{ mb_substr("abcdef", 2, -1) }}',

            // String Functions
            'addcslashes' => '{{ addcslashes("foo[ ]", "A..z") }}',
            'addslashes' => '{{ addslashes("Is your name O\'Reilly?") }}',
            'bin2hex' => '{{ bin2hex("binary") }}',
            'chr' => '{{ chr(-159) }}',
            'chunk_split' => '{{ chunk_split("abcdef", 3, ",") }}',
            'explode' => '{{ explode(",", "hello,there")|join(" ") }}',
            'hex2bin' => '{{ hex2bin("6578616d706c65206865782064617461") }}',
            'htmlspecialchars' => '{{ htmlspecialchars("<a href=\'test\'>Test</a>", constant("ENT_QUOTES")) }}',
            'implode' => '{{ implode(" ", ["hello", "there"]) }}',
            'lcfirst' => '{{ lcfirst("HELLO WORLD!") }}',
            'ltrim' => 'a{{ ltrim(" b ") }}c',
            'nl2br' => '{{ nl2br("foo is not\n bar") }}',
            'number_format' => '{{ number_format(1234.56) }}',
            'ord' => '{{ ord("\n") }}',
            'rtrim' => 'a{{ rtrim(" b ") }}c',
            'str_ireplace' => '{{ str_ireplace("%body%", "black", "<body text=%BODY%>") }}',
            'str_pad' => '{{ str_pad("Alien", 10, "_", constant("STR_PAD_BOTH")) }}',
            'str_repeat' => '{{ str_repeat("-=", 10) }}',
            'str_replace' => '{{ str_replace("%body%", "black", "<body text=\'%body%\'>") }}',
            'str_rot13' => '{{ str_rot13("PHP 4.3.0") }}',
            'str_shuffle' => '{{ str_shuffle("abcdef")|length }}',
            'str_split' => '{{ str_split("Hello Friend", 4)|first }}',
            'str_word_count' => '{{ str_word_count("Hello fri3nd") }}',
            'strip_tags' => '{{ strip_tags(\'<p>Test paragraph.</p><!-- Comment --> <a href="#fragment">Other text</a>\') }}',
            'stripos' => '{{ stripos("ABC", "b") }}',
            'stristr' => '{{ stristr("USER@EXAMPLE.com", "e") }}',
            'strlen' => '{{ strlen("abcdef") }}',
            'strpos' => '{{ strpos("abc", "b") }}',
            'strrchr' => '{{ strrchr("/www/public_html/index.html", "/") }}',
            'strrev' => '{{ strrev("Hello world!") }}',
            'strripos' => '{{ strripos("/www/public_html/index.html", "/") }}',
            'strrpos' => '{{ strrpos("/www/public_html/index.html", "/") }}',
            'strstr' => '{{ strstr("user@example.com", "e") }}',
            'strtok' => '{{ strtok("/something", "/") }}',
            'strtolower' => '{{ strtolower("Mary Had A Little Lamb") }}',
            'strtoupper' => '{{ strtoupper("Mary Had A Little Lamb") }}',
            'strtr' => '{{ strtr("baab", "ab", "01") }}',
            'substr_count' => '{{ substr_count("This is a test", "is") }}',
            'substr' => '{{ substr("abcdef", 2, -1) }}',
            'trim' => 'a{{ trim(" b ") }}c',
            'ucfirst' => '{{ ucfirst("hello world!") }}',
            'ucwords' => '{{ ucwords("hello world!") }}',
            'wordwrap' => '{{ wordwrap("A very long woooooooooooord.", 8, "-", true) }}',

            // Variable handling Functions
            'gettype' => '{{ gettype(1) }}',
            'is_array' => '{{ is_array(["one", "two", "three"]) ? "yes" : "no" }}',
            'is_bool' => '{{ is_bool(false) ? "yes" : "no" }}',
            'is_float' => '{{ is_float(3.4) ? "yes" : "no" }}',
            'is_int' => '{{ is_int(1) ? "yes" : "no" }}',
            'is_null' => '{{ is_null(null) ? "yes" : "no" }}',
            'is_numeric' => '{{ is_numeric("123") ? "yes" : "no" }}',
            'is_string' => '{{ is_string("abc") ? "yes" : "no" }}',
            'serialize' => '{{ serialize(["a", "b", "c"]) }}',
            'unserialize' => '{{ unserialize(\'a:3:{i:0;s:1:"a";i:1;s:1:"b";i:2;s:1:"c";}\')|join(",") }}',

            // Return by reference, so we return directly
            'array_pop' => '{{ array_pop(["one", "two", "three"])|join(",") }}',
            'array_shift' => '{{ array_shift(["one", "two", "three"])|join(",") }}',
            'array_splice' => '{{ array_splice(["red", "green", "blue", "yellow"], -1, 1, ["black", "maroon"])|join(",") }}',
            'arsort' => '{{ arsort({d:"lemon", a:"orange", b:"banana", c:"apple"})|join(",") }}',
            'asort' => '{{ asort({d:"lemon", a:"orange", b:"banana", c:"apple"})|join(",") }}',
            'krsort' => '{{ krsort({d:"lemon", a:"orange", b:"banana", c:"apple"})|join(",") }}',
            'ksort' => '{{ ksort({d:"lemon", a:"orange", b:"banana", c:"apple"})|join(",") }}',
            'natcasesort' => '{{ natcasesort(["IMG0.png", "img12.png", "img10.png", "img2.png", "img1.png", "IMG3.png"])|join(",") }}',
            'natsort' => '{{ natsort(["img12.png", "img10.png", "img2.png", "img1.png"])|join(",") }}',
            'rsort' => '{{ rsort(["lemon", "orange", "banana", "apple"])|join(",") }}',
            'shuffle' => '{{ shuffle(range(1, 20))|length }}',
            'sort' => '{{ sort(["IMG0.png", "img12.png", "img10.png", "img2.png", "img1.png", "IMG3.png"])|join(",") }}',
            'settype' => '{{ settype("5bar", "integer") }}',

            // PCRE Functions
            'preg_filter' => '{{ preg_filter(["/[0-9]/", "/[a-z]/", "/[1a]/"], ["A:$0", "B:$0", "C:$0"], ["1", "a", "2", "b", "3", "A", "B", "4"])|join(",") }}',
            'preg_grep' => '{{ preg_grep("/^([0-9]+)?\.[0-9]+$/", ["1.3", 200, ".78", "string"])|join(",") }}',
            'preg_match_all' => '{% set match = preg_match_all("|<[^>]+>(.*)</[^>]+>|U", "<b>example: </b><div align=left>this is a test</div>") %} {{ match[1][0] }}, {{ match[1][1] }}',
            'preg_match' => '{{ preg_match("/^def/", "def")|join(",") }}',
            'preg_quote' => '{{ preg_quote("$40 for a g3/400", "/") }}',
            'preg_replace' => '{{ preg_replace(["/[0-9]/", "/[a-z]/", "/[1a]/"], ["A:$0", "B:$0", "C:$0"], ["1", "a", "2", "b", "3", "A", "B", "4"])|join(",") }}',
            'preg_split' => '{{ preg_split("/[\\\s,]+/", "hypertext language, programming")|join(",") }}',
        )));
        foreach (array(
            'array_change_key_case' => 'first,second,FIRST,SECOND',
            'array_chunk' => 'a,b,c',
            'array_column' => 'John,Sally',
            'array_combine' => 'apple,banana',
            'array_count_values' => '2',
            'array_diff_assoc' => 'brown,blue,red',
            'array_diff_key' => 'red,purple',
            'array_diff' => 'blue',
            'array_fill_keys' => 'banana,banana,banana,banana',
            'array_fill' => 'banana,banana,banana,banana',
            'array_filter' => 'foo,-1',
            'array_flip' => 'b,c',
            'array_intersect_assoc' => 'green',
            'array_intersect_key' => 'blue,green',
            'array_intersect' => 'green,red',
            'array_key_exists' => 'true',
            'array_keys' => '0,color',
            'array_map' => '1,2,3',
            'array_merge_recursive' => 'red,green',
            'array_merge' => 'green,2,4,a,b,trapezoid,4',
            'array_pad' => '12,10,9,0,0',
            'array_product' => '384',
            'array_rand' => 'true',
            'array_replace_recursive' => 'blueberry,raspberry',
            'array_replace' => 'grape,banana,apple,raspberry,cherry',
            'array_reverse' => 'version,4,php',
            'array_search' => '2',
            'array_slice' => 'c,d,e',
            'array_sum' => '20',
            'array_unique' => 'green,red,blue',
            'array_values' => 'XL,gold',
            'count' => '3',
            'in_array' => 'true',
            'date_parse' => '2006-12-12',
            'date_sun_info' => 'yes',
            'getdate' => 'Tuesday',
            'gettimeofday' => 'yes',
            'gmdate' => 'Jan 01 1998 00:00:00',
            'gmmktime' => 'Saturday',
            'microtime' => 'yes',
            'mktime' => 'Jan-01-1998',
            'strtotime' => 'yes',
            'time' => 'yes',
            'json_decode' => 'a,b,c,d,e',
            'json_encode' => '{"a":1,"b":2,"c":3,"d":4,"e":5}',
            'mail' => 'Sent',
            'abs' => '5',
            'acos' => 'yes',
            'acosh' => 'yes',
            'asin' => 'no',
            'asinh' => 'yes',
            'atan2' => 'yes',
            'atan' => 'yes',
            'atanh' => 'no',
            'base_convert' => '101000110111001100110100',
            'bindec' => '51',
            'ceil' => '5',
            'cos' => '-1',
            'cosh' => 'yes',
            'decbin' => '11010',
            'dechex' => '2f',
            'decoct' => '410',
            'deg2rad' => 'yes',
            'exp' => 'yes',
            'expm1' => 'yes',
            'floor' => '4',
            'fmod' => 'yes',
            'getrandmax' => 'yes',
            'hexdec' => '238',
            'hypot' => 'yes',
            'is_finite' => 'true',
            'is_infinite' => 'false',
            'is_nan' => 'true',
            'lcg_value' => 'true',
            'log10' => 'yes',
            'log1p' => 'yes',
            'log' => 'yes',
            'mt_getrandmax' => 'yes',
            'mt_rand' => 'yes',
            'mt_srand' => '',
            'octdec' => '63',
            'pi' => '3',
            'pow' => '1',
            'rad2deg' => '45',
            'rand' => 'yes',
            'round' => '3',
            'sin' => 'yes',
            'sinh' => 'yes',
            'sqrt' => '3',
            'srand' => '',
            'tan' => '1',
            'tanh' => 'yes',
            'pack' => '5',
            'unpack' => '4,160',
            'mb_convert_case' => 'MARY HAD A LITTLE LAMB',
            'mb_convert_encoding' => '?ndia',
            'mb_strimwidth' => 'Hello W...',
            'mb_stripos' => '1',
            'mb_stristr' => 'ER@EXAMPLE.com',
            'mb_strlen' => '6',
            'mb_strpos' => '1',
            'mb_strrchr' => '/index.html',
            'mb_strrichr' => '/index.html',
            'mb_strripos' => '16',
            'mb_strrpos' => '16',
            'mb_strstr' => 'er@example.com',
            'mb_strtolower' => 'mary had a little lamb',
            'mb_strtoupper' => 'MARY HAD A LITTLE LAMB',
            'mb_strwidth' => '11',
            'mb_substr_count' => '2',
            'mb_substr' => 'cde',
            'addcslashes' => '\f\o\o\[ \]',
            'addslashes' => "Is your name O\'Reilly?",
            'bin2hex' => '62696e617279',
            'chr' => 'a',
            'chunk_split' => 'abc,def,',
            'explode' => 'hello there',
            'hex2bin' => 'example hex data',
            'htmlspecialchars' => '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;',
            'implode' => 'hello there',
            'lcfirst' => 'hELLO WORLD!',
            'ltrim' => 'ab c',
            'nl2br' => "foo is not<br />\n bar",
            'number_format' => '1,235',
            'ord' => '10',
            'rtrim' => 'a bc',
            'str_ireplace' => '<body text=black>',
            'str_pad' => '__Alien___',
            'str_repeat' => '-=-=-=-=-=-=-=-=-=-=',
            'str_replace' => '<body text=\'black\'>',
            'str_rot13' => 'CUC 4.3.0',
            'str_shuffle' => '6',
            'str_split' => 'Hell',
            'str_word_count' => '3',
            'strip_tags' => 'Test paragraph. Other text',
            'stripos' => '1',
            'stristr' => 'ER@EXAMPLE.com',
            'strlen' => '6',
            'strpos' => '1',
            'strrchr' => '/index.html',
            'strrev' => '!dlrow olleH',
            'strripos' => '16',
            'strrpos' => '16',
            'strstr' => 'er@example.com',
            'strtok' => 'something',
            'strtolower' => 'mary had a little lamb',
            'strtoupper' => 'MARY HAD A LITTLE LAMB',
            'strtr' => '1001',
            'substr_count' => '2',
            'substr' => 'cde',
            'trim' => 'abc',
            'ucfirst' => 'Hello world!',
            'ucwords' => 'Hello World!',
            'wordwrap' => 'A very-long-wooooooo-ooooord.',
            'gettype' => 'integer',
            'is_array' => 'yes',
            'is_bool' => 'yes',
            'is_float' => 'yes',
            'is_int' => 'yes',
            'is_null' => 'yes',
            'is_numeric' => 'yes',
            'is_string' => 'yes',
            'serialize' => 'a:3:{i:0;s:1:"a";i:1;s:1:"b";i:2;s:1:"c";}',
            'unserialize' => 'a,b,c',
            'array_pop' => 'one,two',
            'array_shift' => 'two,three',
            'array_splice' => 'red,green,blue,black,maroon',
            'arsort' => 'orange,lemon,banana,apple',
            'asort' => 'apple,banana,lemon,orange',
            'krsort' => 'lemon,apple,banana,orange',
            'ksort' => 'orange,banana,apple,lemon',
            'natcasesort' => 'IMG0.png,img1.png,img2.png,IMG3.png,img10.png,img12.png',
            'natsort' => 'img1.png,img2.png,img10.png,img12.png',
            'rsort' => 'orange,lemon,banana,apple',
            'shuffle' => '20',
            'sort' => 'IMG0.png,IMG3.png,img1.png,img10.png,img12.png,img2.png',
            'settype' => '5',
            'preg_filter' => 'A:C:1,B:C:a,A:2,B:b,A:3,A:4',
            'preg_grep' => '1.3,200,.78',
            'preg_match_all' => 'example: , this is a test',
            'preg_match' => 'def',
            'preg_quote' => '\$40 for a g3\/400',
            'preg_replace' => 'A:C:1,B:C:a,A:2,B:b,A:3,A,B,A:4',
            'preg_split' => 'hypertext,language,programming',
        ) as $function => $value) {
            echo "\n".$function;
            $this->assertEquals($function.': '.$value, $function.': '.trim($twig->render($function)));
        }
    }

    protected function blogPage($path, array $query = array())
    {
        $request = Request::create('http://website.com/'.$path, 'GET', $query);
        Page::html(array(
            'testing' => true,
            'dir' => __DIR__.'/page',
            'url' => 'http://website.com/',
            'suffix' => '.html',
        ), $request, 'overthrow');
        static::$blog = new Blog('blog');

        return static::$blog->page();
    }

    private static function remove($target)
    {
        if (is_dir($target)) {
            $files = glob(rtrim($target, '/\\').'/*');
            foreach ($files as $file) {
                self::remove($file);
            }
            @rmdir($target);
        } elseif (is_file($target)) {
            @unlink($target);
        }
    }
}
