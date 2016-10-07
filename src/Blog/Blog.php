<?php

namespace BootPress\Blog;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use BootPress\Sitemap\Component as Sitemap;
use BootPress\Hierarchy\Component as Hierarchy;
use BootPress\Pagination\Component as Pagination;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Spartz\TextFormatter\TextFormatter;
use URLify; // jbroadway/urlify

class Blog
{
    /** @var string The current version. */
    const VERSION = '1.0';

    /** @var BootPress\SQLite\Component The Blog's SQLite Database. */
    protected $db;

    /** @var BootPress\Blog\Theme For creating the layout, and fetching Twig templates. */
    protected $theme;

    /** @var string Where the Blog directory resides. */
    protected $folder;

    /** @var array All of the Blog's saved config values. */
    private $config;

    /** @var array Saved id's so we don't have to look them up twice. */
    private $ids;

    /**
     * Magic getter for the '**db**', '**theme**', and '**folder**' protected properties.
     * 
     * @param string $name
     * 
     * @return null|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'db':
            case 'theme':
            case 'folder':
                return $this->$name;
                break;
            default:
                return $this->db->settings($name);
                break;
        }
    }

    /**
     * Gets the Blog all set up and ready to go.
     * 
     * @param string $folder Where you want the blog to go, relative to ``$page->dir()``.
     */
    public function __construct($folder = 'blog')
    {
        $page = Page::html();

        // $this->folder
        $this->folder = $page->dir($folder);
        if (!is_dir($this->folder.'content')) {
            mkdir($this->folder.'content', 0755, true);
        }

        // set 'blog/listings' and 'blog/config' url's
        $blog = $page->url['base'];
        $listings = $this->config('blog', 'listings');
        $page->url('set', 'blog/listings', ($listings ? $blog.$listings.'/' : $blog));
        $page->url('set', 'blog/config', $blog.'page/'.substr($this->folder, strlen($page->dir['page'])));

        // $this->theme
        $this->theme = new Theme($this);
        $this->theme->globalVars('blog', $this->config('blog'));
        $this->theme->addPageMethod('blog', array($this, 'query'));

        // $this->db
        $this->db = new SQLite($this->folder.'Blog.db');
        if ($this->db->created || version_compare(self::VERSION, $this->db->settings('version'), '>')) {
            $this->db->settings('version', self::VERSION);
            $this->db->create('blog', array(
                'id' => 'INTEGER PRIMARY KEY',
                'page' => 'TEXT NOT NULL DEFAULT "'.serialize(array()).'"',
                'path' => 'TEXT UNIQUE COLLATE NOCASE',
                'title' => 'TEXT NOT NULL DEFAULT ""',
                'featured' => 'INTEGER NOT NULL DEFAULT 0',
                'published' => 'INTEGER NOT NULL DEFAULT 0',
                'updated' => 'INTEGER NOT NULL DEFAULT 0',
                'author_id' => 'INTEGER NOT NULL DEFAULT 0',
                'category_id' => 'INTEGER NOT NULL DEFAULT 0',
                'search' => 'INTEGER NOT NULL DEFAULT 1',
                'content' => 'TEXT NOT NULL DEFAULT ""',
            ), array('featured, published, updated, author_id', 'category_id'));
            $this->db->create('authors', array(
                'id' => 'INTEGER PRIMARY KEY',
                'path' => 'TEXT UNIQUE COLLATE NOCASE',
                'name' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""',
            ));
            $this->db->create('categories', array(
                'id' => 'INTEGER PRIMARY KEY',
                'path' => 'TEXT UNIQUE COLLATE NOCASE',
                'name' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""',
                'parent' => 'INTEGER NOT NULL DEFAULT 0',
                'level' => 'INTEGER NOT NULL DEFAULT 0',
                'lft' => 'INTEGER NOT NULL DEFAULT 0',
                'rgt' => 'INTEGER NOT NULL DEFAULT 0',
            ));
            $this->db->create('tags', array(
                'id' => 'INTEGER PRIMARY KEY',
                'path' => 'TEXT UNIQUE COLLATE NOCASE',
                'name' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""',
            ));
            $this->db->create('tagged', array(
                'blog_id' => 'INTEGER NOT NULL DEFAULT 0',
                'tag_id' => 'INTEGER NOT NULL DEFAULT 0',
            ), array('unique' => 'blog_id, tag_id'));
            if ($this->db->created) {
                $this->updateDatabase();
            }
        }
        if (($next = $this->db->settings('future_post')) && $next < time()) {
            $this->db->exec(array(
                'UPDATE blog SET published = published * -1',
                'WHERE featured <= 0 AND published > 1 AND published < ?',
            ), time());
            $this->db->settings('future_post', $this->db->value(array(
                'SELECT published FROM blog',
                'WHERE featured <= 0 AND published > 1',
                'ORDER BY published ASC LIMIT 1',
            )));
        }
    }

    /**
     * Executes common queries on the Blog database.
     *
     * If ``$type`` is:
     * 
     * - An ``array()`` - We will return an array of listings (ie. Blog posts or "The Loop") IF ``$params`` is a BootPress\Pagination\Component object (so we can know how many you want at a time), otherwise this will return the total number of listings.  If your array has one of the following keys, then it will only return the applicable listing's array (posts) or integer (count).
     *   - '**archives**' - An ``array($from, $to)`` of UNIX timestamps.
     *   - '**authors**' - An authors path (url) string eg. 'joe-bloggs'.
     *   - '**tags**' - A tag path (url) string eg. 'tagged'.
     *   - '**categories**' - A category path (url) string eg. 'category/subcategory'.
     *   - '**search**' - A search term eg. 'search'.  This does not apply to 'archives', 'authors', or 'tags'.
     *     - You "Loop" will also now contain a '**snippet**' string, and '**words**' array so that you can show the relevancy of your results.
     *     - If you really want to get fancy, then include a ``$type['weights']`` array of numbers to give more or less "weight" to the following (in order now): 'path', 'title', 'description', 'keywords', and 'content'.  The default weights are ``array(1,1,1,1,1)``, every field being of equal importance.
     *   - If by chance you already have the total count and want to save yourself a heap of time, you can include ``$type['count']`` with a total to help us out.
     * - '**similar**' - Returns an array of similarly tagged listings for the current, comma-separated ``$page->keywords`` string.  Ordered by rank.
     *   - (required) Set ``$params`` to the maximum number you want to return.
     *   - To specify the keywords, then set the $params to an ``array((int) 3, (string) 'custom, tags')`` for example.
     * - '**featured**' - Returns an array of featured blog posts.  Ordered by published date descending.
     *   - (optional) Set ``$params`` to the maximum number you want to return.
     * - '**recent**' - Returns an array of recent blog posts, and excludes any featured posts.  Ordered by published date descending.
     *   - (optional) Set ``$params`` to the maximum number you want to return.  The default is 3.
     * - '**posts**' - Get an array of ``$params`` listings, where $params is an array of posts (blog url paths) that you want.  Limit and order inherent.
     * - '**archives**' - Returns an array of archive information for creating a menu of links.  Ordered by year descending (then months in order), and only includes years if count is greater than 0.
     *   - (optional) Set ``$params`` to an array of 'Y' years eg. ``array(2015, 2016)``
     * - '**authors**' - Returns an array of author information for creating a menu of links.  Ordered by count descending, then author name ascending.
     *   - (optional) Set ``$params`` to the maximum number you want to return, or to a single author's url path eg. 'joe-bloggs'
     * - '**tags**' - Returns an array of tag information for creating a menu of links.  Ordered by count descending, then tag name ascending.
     *   - (optional) Set ``$params`` to the maximum number you want to return, or to a single tag's url path eg. 'tagged'
     * - '**categories**' - Returns an array of category information for creating a menu of links.  Ordered by category name ascending.
     *
     * @param array|string $type
     * @param mixed        $params
     * 
     * @return mixed
     */
    public function query($type, $params = null)
    {
        $posts = array();
        if (is_array($type)) { // listings
            $vars = array();
            foreach (array('archives', 'authors', 'tags', 'categories', 'default') as $query) {
                if (isset($type[$query])) {
                    $vars = $type[$query];
                    switch ($query) {
                        case 'archives':
                            $vars = (is_array($vars) && count($vars) == 2) ? $vars : false; // array(from, to)
                            break;
                        case 'authors':
                        case 'tags':
                            $vars = (is_string($vars)) ? $vars : false; // path
                            break;
                        case 'categories':
                            $vars = (is_string($vars) || (is_array($vars) && ctype_digit(implode('', $vars)))) ? $vars : false;
                            break; // path (string) or category_id's (array)
                    }
                    break;
                }
            }
            if ($vars === false) {
                return;
            }
            // $count = (is_object($params) && empty($params->limit)) ? false : true; // $params instanceof Pagination
            $count = (is_object($params) && !empty($params->limit)) ? false : true; // $params instanceof Pagination
            if ($count && isset($type['count'])) {
                return $type['count'];
            }
            switch ($query) {
                case 'archives':
                    list($from, $to) = $vars;
                    $vars = array($to * -1, $from * -1);
                    if ($count) {
                        return $this->db->value(array(
                            'SELECT COUNT(*) FROM blog',
                            'WHERE featured <= 0 AND published >= ? AND published <= ?',
                        ), $vars);
                    } else {
                        $posts = $this->db->ids(array(
                            'SELECT id FROM blog',
                            'WHERE featured <= 0 AND published >= ? AND published <= ?',
                            'ORDER BY featured, published, updated ASC'.$params->limit,
                        ), $vars);
                    }
                    break;

                case 'authors':
                    if ($count) {
                        return $this->db->value(array(
                            'SELECT COUNT(*) FROM blog AS b',
                            'INNER JOIN authors ON b.author_id = authors.id',
                            'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND authors.path = ?',
                        ), $vars);
                    } else {
                        $posts = $this->db->ids(array(
                            'SELECT b.id FROM blog AS b',
                            'INNER JOIN authors ON b.author_id = authors.id',
                            'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND authors.path = ?',
                            'ORDER BY b.featured, b.published, b.updated ASC'.$params->limit,
                        ), $vars);
                    }
                    break;

                case 'tags':
                    if ($count) {
                        return $this->db->value(array(
                            'SELECT COUNT(*) FROM tagged AS t',
                            'INNER JOIN blog AS b ON t.blog_id = b.id',
                            'INNER JOIN tags ON t.tag_id = tags.id',
                            'WHERE b.featured <= 0 AND b.published < 0 AND tags.path = ?',
                            'GROUP BY tags.id',
                        ), $vars);
                    } else {
                        $posts = $this->db->ids(array(
                            'SELECT b.id FROM tagged AS t',
                            'INNER JOIN blog AS b ON t.blog_id = b.id',
                            'INNER JOIN tags ON t.tag_id = tags.id',
                            'WHERE b.featured <= 0 AND b.published < 0 AND tags.path = ?',
                            'ORDER BY b.featured, b.published, b.updated ASC'.$params->limit,
                        ), $vars);
                    }
                    break;

                case 'categories':
                    if (isset($type['search'])) {
                        if (!is_string($vars)) {
                            $vars = $this->db->value('SELECT path FROM categories WHERE id = ?', array_shift($vars));
                        }
                        if (empty($vars)) {
                            return;
                        }
                        $phrase = $type['search'];
                        $category = 'blog/'.$vars;
                        $weights = (isset($type['weights']) && is_array($type['weights'])) ? $type['weights'] : array();
                        if ($count) {
                            $sitemap = new Sitemap();
                            $count = $sitemap->count($phrase, $category);
                            unset($sitemap);

                            return $count;
                        } else {
                            $sitemap = new Sitemap();
                            $includes = array();
                            foreach ($sitemap->search($phrase, $category, $params->limit, $weights) as $row) {
                                $posts[] = $row['id'];
                                $includes[$row['id']] = array('snippet' => $row['snippet'], 'words' => $row['words']);
                            }
                            unset($sitemap);
                            $posts = $this->info($posts);
                            foreach ($posts as $id => $row) {
                                $posts[$id] = array_merge($row, $includes[$id]);
                            }

                            return $posts;
                        }
                    }
                    if (!is_array($vars)) {
                        $hier = new Hierarchy($this->db, 'categories');
                        $vars = array_keys($hier->tree(array('path'), 'path', $vars));
                        unset($hier);
                    }
                    if (empty($vars)) {
                        return;
                    }
                    $categories = implode(', ', $vars);
                    if ($count) {
                        return $this->db->value(array(
                            'SELECT COUNT(*) FROM blog',
                            'WHERE featured <= 0 AND published < 0 AND category_id IN('.$categories.')',
                        ), $vars);
                    } else {
                        $posts = $this->db->ids(array(
                            'SELECT id FROM blog',
                            'WHERE featured <= 0 AND published < 0 AND category_id IN('.$categories.')',
                            'ORDER BY featured, published, updated ASC'.$params->limit,
                        ), $vars);
                    }
                    break;

                default:
                    if (isset($type['search'])) {
                        $phrase = $type['search'];
                        $weights = (isset($type['weights']) && is_array($type['weights'])) ? $type['weights'] : array();
                        if ($count) {
                            $sitemap = new Sitemap();
                            $count = $sitemap->count($phrase, 'blog');
                            unset($sitemap);

                            return $count;
                        } else {
                            $sitemap = new Sitemap();
                            $includes = array();
                            foreach ($sitemap->search($phrase, 'blog', $params->limit, $weights) as $row) {
                                $posts[] = $row['id'];
                                $includes[$row['id']] = array('snippet' => $row['snippet'], 'words' => $row['words']);
                            }
                            unset($sitemap);
                            $posts = $this->info($posts);
                            foreach ($posts as $id => $row) {
                                $posts[$id] = array_merge($row, $includes[$id]);
                            }

                            return $posts;
                        }
                    }
                    if ($count) {
                        return $this->db->value(array(
                            'SELECT COUNT(*) FROM blog',
                            'WHERE featured <= 0 AND published < 0',
                        ), $vars);
                    } else {
                        $posts = $this->db->ids(array(
                            'SELECT id FROM blog',
                            'WHERE featured <= 0 AND published < 0',
                            'ORDER BY featured, published, updated ASC'.$params->limit,
                        ), $vars);
                    }
                    break;
            }

            return $this->info($posts);
        }
        switch ($type) {
            case 'similar': // optional (string|array) keywords (ordered by RANK) - default Page::html()->keywords
                $keywords = Page::html()->keywords;
                $limit = $params;
                if (is_array($params)) {
                    list($limit, $keywords) = (count($params) == 1) ? each($params) : $params;
                }
                if (!empty($keywords) && !empty($limit) && is_numeric($limit)) {
                    if (!is_array($keywords)) {
                        $keywords = array_map('trim', explode(',', $keywords));
                    }
                    if (!empty($keywords)) {
                        $current = Page::html()->url['path'];
                        $sitemap = new Sitemap();
                        foreach ($sitemap->search('"'.implode('" OR "', $keywords).'"', 'blog', $limit, array(0, 0, 0, 1, 0), 'AND m.path != "'.$current.'"') as $row) {
                            $posts[] = $row['id'];
                        }
                        unset($sitemap);
                        $posts = $this->info($posts);
                    }
                }
                break;

            case 'featured':
                $limit = (is_numeric($params)) ? ' LIMIT '.$params : '';
                $posts = $this->info($this->db->ids(array(
                    'SELECT id FROM blog',
                    'WHERE featured < 0 AND published < 0',
                    'ORDER BY featured, published, updated ASC'.$limit,
                )));
                break;

            case 'recent':
                $limit = (is_numeric($params)) ? $params : 3;
                $posts = $this->info($this->db->ids(array(
                    'SELECT id FROM blog',
                    'WHERE featured = 0 AND published < 0',
                    'ORDER BY featured, published, updated ASC LIMIT '.$limit,
                )));
                break;

            case 'posts': // (array) paths (limit and order inherent)
                if (!empty($params)) {
                    foreach ((array) $params as $path) {
                        $posts[$path] = '';
                    }
                    foreach ($this->db->all(array(
                        'SELECT path, id',
                        'FROM blog',
                        'WHERE path IN('.implode(', ', array_fill(0, count($posts), '?')).')',
                    ), array_keys($posts), 'assoc') as $row) {
                        $posts[$row['path']] = $row['id'];
                    }
                    $posts = $this->info(array_values(array_filter($posts)));
                }
                break;

            case 'archives': // optional (array) years (ordered by year DESC, and only starts if count > 0) - default all, no limit
                $years = (is_array($params)) ? $params : array();
                if (empty($years)) {
                    $times = $this->db->row('SELECT ABS(MAX(published)) AS begin, ABS(MIN(published)) AS end FROM blog WHERE featured <= 0 AND published < 0', '', 'assoc');
                    if (!is_null($times['end'])) {
                        $years = range(date('Y', $times['begin']), date('Y', $times['end']));
                    }
                }
                $months = array('Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12);
                $archives = array();
                foreach ($years as $Y) {
                    foreach ($months as $M => $n) {
                        $to = mktime(23, 59, 59, $n + 1, 0, $Y) * -1;
                        $from = mktime(0, 0, 0, $n, 1, $Y) * -1;
                        $archives[] = "SUM(CASE WHEN featured <= 0 AND published >= {$to} AND published <= {$from} THEN 1 ELSE 0 END) AS {$M}{$Y}";
                    }
                }
                if (!empty($archives) && $archives = $this->db->row(array('SELECT', implode(",\n", $archives), 'FROM blog'), '', 'assoc')) {
                    $page = Page::html();
                    foreach ($archives as $date => $count) {
                        $time = mktime(0, 0, 0, $months[substr($date, 0, 3)], 15, substr($date, 3));
                        list($Y, $M, $m) = explode(' ', date('Y M m', $time));
                        if (!isset($posts[$Y])) {
                            $posts[$Y] = array('count' => 0, 'url' => $page->url('blog/listings', 'archives', $Y));
                        }
                        $posts[$Y]['months'][$M] = array('url' => $page->url('blog/listings', 'archives', $Y, $m), 'count' => $count, 'time' => $time);
                        $posts[$Y]['count'] += $count;
                    }
                }
                break;

            case 'authors': // optional (int) limit or (string) path (ordered by count DESC, then author name ASC)
                $path = (!is_int($params) && !empty($params)) ? (string) $params : '';
                $operator = (!empty($path)) ? '=' : '!=';
                $authors = $this->db->all(array(
                    'SELECT COUNT(*) AS count, authors.path, authors.name, ABS(MIN(b.published)) AS latest',
                    'FROM blog AS b',
                    'INNER JOIN authors ON b.author_id = authors.id',
                    'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND authors.path '.$operator.' ?',
                    'GROUP BY authors.id',
                    'ORDER BY authors.name ASC',
                ), $path, 'assoc');
                $authored = array();
                foreach ($authors as $author) {
                    $authored[$author['path']] = $author['count'];
                }
                arsort($authored);
                if (is_int($params)) {
                    $authored = array_slice($authored, 0, $params, true);
                }
                foreach ($authors as $author) {
                    if (isset($authored[$author['path']])) {
                        $info = $this->configInfo('authors', $author['path'], $author['name']);
                        $info['latest'] = $author['latest'];
                        $info['count'] = $author['count'];
                        $posts[] = $info;
                    }
                }
                if (!empty($path) && !empty($posts)) {
                    $posts = array_shift($posts);
                }
                break;

            case 'tags': // optional (int) limit or (string) path (ordered by count DESC, then tag name ASC)
                $path = (!is_int($params) && !empty($params)) ? (string) $params : '';
                $operator = (!empty($path)) ? '=' : '!=';
                $tags = $this->db->all(array(
                    'SELECT COUNT(*) AS count, tags.path, tags.name, ABS(MIN(b.published)) AS latest',
                    'FROM tagged AS t',
                    'INNER JOIN blog AS b ON t.blog_id = b.id',
                    'INNER JOIN tags ON t.tag_id = tags.id',
                    'WHERE b.featured <= 0 AND b.published < 0 AND tags.path '.$operator.' ?',
                    'GROUP BY tags.id',
                    'ORDER BY tags.name ASC',
                ), $path, 'assoc');
                $tagged = array();
                foreach ($tags as $tag) {
                    $tagged[$tag['path']] = $tag['count'];
                }
                arsort($tagged);
                if (is_int($params)) {
                    $tagged = array_slice($tagged, 0, $params, true);
                }
                if (count($tagged) > 0) {
                    // http://en.wikipedia.org/wiki/Tag_cloud
                    // http://stackoverflow.com/questions/18790677/what-algorithm-can-i-use-to-sort-tags-for-tag-cloud?rq=1
                    // http://stackoverflow.com/questions/227/whats-the-best-way-to-generate-a-tag-cloud-from-an-array-using-h1-through-h6-fo
                    $min = min($tagged);
                    $range = max(.01, max($tagged) - $min) * 1.0001;
                    foreach ($tags as $tag) {
                        if (isset($tagged[$tag['path']])) {
                            $info = $this->configInfo('tags', $tag['path'], $tag['name']);
                            $info['latest'] = $tag['latest'];
                            $info['count'] = $tag['count'];
                            if (!empty($path)) {
                                $posts = $info;
                                break;
                            }
                            $info['rank'] = ceil(((4 * ($tag['count'] - $min)) / $range) + 1);
                            $posts[] = $info;
                        }
                    }
                }
                break;

            case 'categories': // (ordered by category name ASC) - no limit
                if (is_array($params) && isset($params['nest']) && isset($params['tree'])) {
                    foreach ($params['nest'] as $id => $subs) {
                        $category = $params['tree'][$id];
                        $posts[$id] = $this->configInfo('categories', $category['path'], $category['name']);
                        $posts[$id]['count'] = $category['count'];
                        if (!empty($subs)) {
                            $posts[$id]['subs'] = $this->query('categories', array(
                                'nest' => $subs,
                                'tree' => $params['tree'],
                            ));
                        }
                    }

                    return array_values($posts);
                }
                $hier = new Hierarchy($this->db, 'categories');
                $tree = $hier->tree(array('path', 'name'));
                $counts = $hier->counts('blog', 'category_id');
                foreach ($tree as $id => $fields) {
                    $tree[$id]['count'] = $counts[$id];
                }
                $nest = $hier->nestify($tree);
                $slice = array();
                foreach ($nest as $id => $subs) {
                    if ($tree[$id]['count'] > 0) {
                        $slice[$id] = $tree[$id]['count'];
                    }
                }
                arsort($slice);
                if (is_int($params)) {
                    $slice = array_slice($slice, 0, $params, true);
                }
                foreach ($nest as $id => $subs) {
                    if (!isset($slice[$id])) {
                        unset($nest[$id]);
                    }
                }
                if (!empty($slice)) {
                    $posts = $this->query('categories', array(
                        'nest' => $nest,
                        'tree' => $tree,
                    ));
                }
                break;
        }

        return $posts;
    }

    /**
     * Analyzes the blog file $path and performes any database CRUD operations that may be needed.  We do this every time a blog page is visited, but this helps to speed up the process if you are doing things programatically.  If you are making lots of changes, then just delete the Blog.db file and everything will be updated.
     * 
     * @param string $path Either a folder (html) or file (txt|json|xml|rdf|rss|atom).
     * 
     * @return false|int False if the $path does not exist, or the database id if it does.
     */
    public function file($path)
    {
        $html = (strpos($path, '.') === false) ? true : false;
        $blog = $this->db->row('SELECT id, path, updated, search, content FROM blog WHERE path = ?', $path, 'assoc');
        if (!$current = $this->blogInfo($path)) {
            if ($blog) { // then remove
                $this->db->exec('DELETE FROM blog WHERE id = ?', $blog['id']);
                if ($html) {
                    $this->db->exec('DELETE FROM tagged WHERE blog_id = ?', $blog['id']);
                    $sitemap = new Sitemap();
                    $sitemap->delete($blog['path']);
                    unset($sitemap);
                }
            }

            return false;
        }

        if ($blog) { // update maybe
            foreach (array('updated', 'search', 'content') as $field) {
                if ($current[$field] != $blog[$field]) {
                    $updated = $this->db->update('blog', 'id', array($blog['id'] => $current));
                    $this->db->exec('DELETE FROM tagged WHERE blog_id = ?', $blog['id']);
                    break;
                }
            }
            if (!isset($updated)) {
                return $blog['id'];
            }
        } else { // insert
            $blog = $current;
            $blog['id'] = $this->db->insert('blog', $current);
        }

        if ($html) {
            $page = unserialize($current['page']);
            if (isset($page['keywords'])) {
                $tags = array_filter(array_map('trim', explode(',', $page['keywords'])));
                foreach ($tags as $tag) {
                    $this->db->insert('tagged', array(
                        'blog_id' => $blog['id'],
                        'tag_id' => $this->getId('tags', $tag),
                    ));
                }
            }
            $sitemap = new Sitemap();
            if (!$current['search']) {
                $sitemap->delete($blog['path']);
            } else {
                $category = 'blog';
                if ($current['category_id'] > 0) {
                    $category .= '/'.array_search($current['category_id'], $this->ids['categories']);
                }
                $sitemap->upsert($category, array(
                    'id' => $blog['id'],
                    'path' => $blog['path'],
                    'title' => $current['title'],
                    'description' => (isset($page['description'])) ? (string) $page['description'] : '',
                    'keywords' => (isset($page['keywords'])) ? (string) $page['keywords'] : '',
                    'image' => (isset($page['image'])) ? (string) $page['image'] : '',
                    'content' => $current['content'],
                    'updated' => $current['updated'],
                ));
            }
            unset($sitemap);
            $this->updateConfig();
        }

        return $blog['id'];
    }

    /**
     * Gets all of the information we have for the blog $id(s) you supply.
     * 
     * @param int|int[] $ids That correspond to the database.
     * 
     * @return array A single row of information if $ids is not an array, or multiple rows foreach id in the same order given that you can loop through.
     */
    public function info($ids)
    {
        $page = Page::html();
        $single = (is_array($ids)) ? false : true;
        if (empty($ids)) {
            return array();
        }
        $ids = (array) $ids;
        $posts = array_flip($ids);
        foreach ($this->db->all(array(
            'SELECT b.id, b.page, b.path, b.title, b.content, ABS(b.updated) AS updated, ABS(b.featured) AS featured, ABS(b.published) AS published,',
            '  a.id AS author_id, a.path AS author_path, a.name AS author_name,',
            '  (SELECT p.path || "," || p.title FROM blog AS p WHERE p.featured = b.featured AND p.published > b.published AND p.published < 0 ORDER BY p.featured, p.published ASC LIMIT 1) AS previous,',
            '  (SELECT n.path || "," || n.title FROM blog AS n WHERE n.featured = b.featured AND n.published < b.published AND n.published < 0 ORDER BY n.featured, n.published DESC LIMIT 1) AS next,',
            '  (SELECT GROUP_CONCAT(p.path, "<!--delimiter-->") FROM categories AS c INNER JOIN categories AS p WHERE c.lft BETWEEN p.lft AND p.rgt AND c.id = b.category_id ORDER BY c.lft) AS category_paths,',
            '  (SELECT GROUP_CONCAT(p.name, "<!--delimiter-->") FROM categories AS c INNER JOIN categories AS p WHERE c.lft BETWEEN p.lft AND p.rgt AND c.id = b.category_id ORDER BY c.lft) AS category_names,',
            '  (SELECT GROUP_CONCAT(t.path, "<!--delimiter-->") FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_paths,',
            '  (SELECT GROUP_CONCAT(t.name, "<!--delimiter-->") FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_names',
            'FROM blog AS b',
            'LEFT JOIN authors AS a ON b.author_id = a.id',
            'WHERE b.id IN('.implode(', ', $ids).')',
        ), '', 'assoc') as $row) {
            $post = array(
                'page' => unserialize($row['page']),
                'path' => $row['path'],
                'url' => $page->url('base', $row['path']),
                'title' => $row['title'],
                'content' => $row['content'],
                'updated' => $row['updated'],
                'featured' => ($row['featured'] > 0) ? true : false,
                'published' => ($row['published'] > 1) ? $row['published'] : (($row['published'] == 1) ? true : false),
                'categories' => array(),
                'tags' => array(),
            );
            if (!empty($row['category_paths'])) {
                $cats = array_combine(
                    explode('<!--delimiter-->', $row['category_paths']),
                    explode('<!--delimiter-->', $row['category_names'])
                );
                foreach ($cats as $path => $name) {
                    $post['categories'][] = $this->configInfo('categories', $path, $name);
                }
            }
            if (!empty($row['tag_paths'])) {
                $tags = array_combine(
                    explode('<!--delimiter-->', $row['tag_paths']),
                    explode('<!--delimiter-->', $row['tag_names'])
                );
                foreach ($tags as $path => $name) {
                    $post['tags'][] = $this->configInfo('tags', $path, $name);
                }
            }
            if ($row['published'] > 1) {
                $post['author'] = $this->configInfo('authors', $row['author_path'], $row['author_name']);
                $post['archive'] = $page->url('blog/listings', 'archives', date('Y/m/d/', $row['published']));
                $post['previous'] = $row['previous'];
                $post['next'] = $row['next'];
                if ($post['previous']) {
                    $previous = explode(',', $post['previous']);
                    $post['previous'] = array(
                        'url' => $page->url('base', array_shift($previous)),
                        'title' => implode(',', $previous),
                    );
                }
                if ($post['next']) {
                    $next = explode(',', $post['next']);
                    $post['next'] = array(
                        'url' => $page->url('base', array_shift($next)),
                        'title' => implode(',', $next),
                    );
                }
            }
            $posts[$row['id']] = $post;
        }

        return ($single) ? array_shift($posts) : $posts;
    }

    /**
     * Slugifies a folder path suitable for urls.
     * 
     * @param string $path    The path you would like to slugify
     * @param mixed  $slashes If anything but false, it will allow your path to have slashes.
     * 
     * @return string
     */
    public function url($path, $slashes = false)
    {
        $path = ($slashes !== false) ? explode('/', $path) : array($path);
        foreach ($path as $key => $value) {
            $path[$key] = URLify::filter($value);
        }

        return implode('/', $path);
    }

    /**
     * Properly formats a title string.
     * 
     * @param string $string
     * 
     * @return string
     */
    public function title($string)
    {
        $string = explode(' ', $string);
        foreach ($string as $key => $value) {
            if (!empty($value) && mb_strtoupper($value) == $value) {
                $string[$key] = mb_strtolower($value);
            }
        }

        return TextFormatter::titleCase(implode(' ', $string));
    }

    /**
     * Retrieves any config value found in the Blog's config.yml file.
     * 
     * @param string $key The config array key whose value you would like to retrieve.  For every arg you include we will keep working our way up the config array to find just what you are looking for.
     * 
     * @return mixed The config key(s) value, or null if not found.
     */
    public function config($key = null)
    {
        $args = func_get_args(); // In PHP7, func_get_args() are changed by the time we use them, so we call it now
        if (is_null($this->config)) {
            $file = $this->folder.'config.yml';
            $this->config = (is_file($file)) ? (array) Yaml::parse(file_get_contents($file)) : array();
            $current = true;
            foreach (array(
                'blog' => array(
                    'name' => 'Another { BootPress } Site',
                    'image' => '',
                    'summary' => '',
                    'listings' => 'blog',
                    'breadcrumb' => 'Blog',
                    'theme' => 'default',
                ),
            ) as $name => $config) {
                foreach ($config as $key => $val) {
                    if (!isset($this->config[$name][$key]) || !is_string($this->config[$name][$key]) || (empty($this->config[$name][$key]) && !empty($val))) {
                        $this->config[$name][$key] = $val;
                        $current = false;
                    }
                }
            }
            $page = Page::html();
            foreach (array('authors', 'categories', 'tags') as $param) {
                if (!isset($this->config[$param]) || !is_array($this->config[$param])) {
                    $this->config[$param] = array();
                    $current = false;
                }
                foreach ($this->config[$param] as $key => $val) {
                    if (is_string($val)) {
                        $this->config[$param][$key] = array('name' => $val);
                    }
                }
            }
            if (!$current) {
                file_put_contents($file, Yaml::dump($this->config, 3));
            }
        }
        $value = $this->config;
        foreach ($args as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return;
            }
        }

        return $value;
    }

    /**
     * Compiles an array of useful information.
     * 
     * @param string $table Either 'authors', 'categories', or 'tags'.
     * @param string $path  The ``$table``'s key (a url path).
     * @param string $name  The default ``$table[$path]``'s name if one is not specified.
     * 
     * @return array Either an empty one if not found, or the 'name', 'path', 'url', and 'image' of the ``$table[$path]``.
     */
    private function configInfo($table, $path, $name)
    {
        if (empty($path)) {
            return array();
        }
        if (!$config = $this->config($table, $path)) {
            $config = array();
        }
        unset($config['path'], $config['url']);
        $page = Page::html();
        $config = array_merge(array(
            'name' => $name,
            'path' => $path,
            'url' => ($table == 'categories') ? $page->url('base', $path) : $page->url('blog/listings', $table, $path),
            'image' => '',
        ), $config);
        if (!empty($config['image'])) {
            $config['image'] = $page->url('blog/config', $config['image']);
        }

        return $config;
    }

    /**
     * Looks up a file, and gleans the information in it.
     * 
     * @param string $path Either a folder (html) or file (txt|json|xml|rdf|rss|atom).
     * 
     * @return array|bool An array if the $path was found, or false if not.
     */
    private function blogInfo($path)
    {
        $page = Page::html();
        if (preg_match('/\.(txt|json|xml|rdf|rss|atom)$/', $path)) {
            $file = $this->folder.'content/'.$path.'.twig';

            return (is_file($file)) ? array(
                'page' => serialize(array()),
                'path' => $path,
                'title' => '',
                'featured' => 0,
                'published' => 1,
                'updated' => filemtime($file) * -1,
                'author_id' => 0,
                'category_id' => 0,
                'search' => 0,
                'content' => trim($this->theme->renderTwig($file)),
            ) : false;
        }
        $dir = $this->folder.'content/';
        if (preg_match('/[^'.$page->url['chars'].'\/]/', $path)) {
            $seo = $this->url($path, 'slashes');
            if (is_dir($dir.$path)) {
                rename($dir.$path, $dir.$seo);
            }
            $path = $seo;
        }
        $file = (empty($path)) ? $dir.'index.html.twig' : $dir.$path.'/index.html.twig';
        if (!is_file($file)) {
            return false;
        }
        $page->set(array(), 'reset');
        $default = $page->html;
        if (preg_match('/^\s*{#(?P<meta>.*)#}/sU', file_get_contents($file), $matches)) {
            $values = Yaml::parse($matches['meta']);
            if (is_array($values)) {
                $page->set($values);
            }
        }
        $content = trim($this->theme->renderTwig($file));
        // Urlify $page->image, and any other assets we want to pass along
        $page->set($this->theme->asset($page->html));
        $set = $page->html;
        foreach ($default as $key => $value) {
            if (isset($set[$key]) && $set[$key] == $value) {
                unset($set[$key]); // no need to save the same thing over and over again
            }
        }
        $published = $page->published;
        if (is_string($published) && ($date = strtotime($published))) {
            if ($date > time()) {
                $published = $date; // a future post
                $next = ($date = $this->db->settings('future_post')) ? min($date, $published) : $published;
                $this->db->settings('future_post', $next);
            } else {
                $published = $date * -1; // a post
            }
        } elseif ($published === true) {
            $published = 1; // a page
        } else {
            $published = 0; // unpublished
        }

        return array(
            'page' => serialize($set),
            'path' => $path,
            'title' => (string) $page->title,
            'featured' => ($page->featured === true) ? -1 : 0,
            'published' => $published,
            'updated' => filemtime($file) * -1,
            'author_id' => $this->getId('authors', (string) $page->author),
            'category_id' => $this->getId('categories', $path),
            'search' => ($published === 0 || $page->robots === false) ? 0 : 1,
            'content' => $content,
        );
    }

    /**
     * Puts everything into the database at once.  It's only called if ``$this->db->created``.
     */
    private function updateDatabase()
    {
        set_time_limit(0);
        $blog = $this->db->insert('blog', array('page', 'path', 'title', 'featured', 'published', 'updated', 'author_id', 'category_id', 'search', 'content'));
        $tagged = $this->db->insert('tagged', array('blog_id', 'tag_id'));
        $sitemap = new Sitemap();
        $sitemap->reset('blog');
        $this->normalizeFolders();
        $finder = new Finder();
        $finder->files()->in($this->folder.'content')->name('index.html.twig')->name('/^['.Page::html()->url['chars'].']+\.(txt|json|xml|rdf|rss|atom)\.twig$/')->sortByName();
        foreach ($finder as $file) {
            $html = (substr($file->getRelativePathname(), -15) == 'index.html.twig') ? true : false;
            $path = ($html) ? $file->getRelativePath() : substr($file->getRelativePathname(), 0, -5); // remove .twig
            if ($info = $this->blogInfo(str_replace('\\', '/', $path))) {
                $id = $this->db->insert($blog, array_values($info));
                if ($html) {
                    $page = unserialize($info['page']);
                    if (isset($page['keywords']) && !empty($page['keywords'])) {
                        $tags = array_filter(array_map('trim', explode(',', $page['keywords'])));
                        foreach ($tags as $tag) {
                            $this->db->insert($tagged, array($id, $this->getId('tags', $tag)));
                        }
                    }
                    $category = 'blog';
                    if ($info['category_id'] > 0) {
                        $category .= '/'.array_search($info['category_id'], $this->ids['categories']);
                    }
                    if ($info['search']) {
                        $sitemap->upsert($category, array(
                            'id' => $id,
                            'path' => $info['path'],
                            'title' => $info['title'],
                            'description' => (isset($page['description'])) ? (string) $page['description'] : '',
                            'keywords' => (isset($page['keywords'])) ? (string) $page['keywords'] : '',
                            'image' => (isset($page['image'])) ? (string) $page['image'] : '',
                            'content' => $info['content'],
                            'updated' => $info['updated'],
                        ));
                    }
                }
            }
        }
        $sitemap->delete();
        unset($sitemap);
        $this->db->close($blog);
        $this->db->close($tagged);
        $this->updateConfig();
    }

    /**
     * Recursively goes through the blog's 'content' folder, and fixes any malnamed directories.
     * 
     * @param string $path There is nothing to see here.  This value gets passed within the method itself.
     */
    private function normalizeFolders($path = null)
    {
        $page = Page::html();
        $dir = $this->folder.'content/';
        // normalize
        if ($path && preg_match('/[^'.$page->url['chars'].'\/]/', $path)) {
            $seo = $this->url($path, 'slashes');
            if (is_dir($dir.$path)) {
                rename($dir.$path, $dir.$seo);
            }
            $path = $seo;
        }
        // folders
        foreach (glob($page->dir($dir, $path).'*', GLOB_ONLYDIR) as $folder) {
            $this->normalizeFolders(substr($folder, strlen($dir)));
        }
    }

    /**
     * Gets the ID of the ``$table``'s ('categories', 'authors', or 'tags') ``$value``.  If the ``$value`` had not yet been set, then it will be now.  You can know if that is the case by calling ``$this->getId('updated', $table)``.  If the ``$table`` doesn't exist, then we'll let you know if anything has been updated.
     * 
     * @param string $table
     * @param string $value
     * 
     * @return int|bool
     */
    private function getId($table, $value)
    {
        if (is_null($this->ids)) {
            $this->ids = array(
                'updated' => array(
                    'categories' => false,
                    'authors' => false,
                    'tags' => false,
                ),
            );
        }
        if ($table == 'updated') {
            if (isset($this->ids['updated'][$value])) {
                return $this->ids['updated'][$value];
            }

            return (in_array(true, $this->ids['updated'])) ? true : false;
        }
        if ($table == 'categories') {
            $value = (($slash = strrpos($value, '/')) !== false) ? substr($value, 0, $slash) : '';
        }
        if (!isset($this->ids['updated'][$table]) || empty($value)) {
            return 0;
        }
        if (!isset($this->ids[$table])) {
            $this->ids[$table] = array('' => 0);
            foreach ($this->db->all('SELECT path, id FROM '.$table, '', 'assoc') as $row) {
                $this->ids[$table][$row['path']] = $row['id'];
            }
        }
        $page = Page::html();
        $path = $value;
        if (preg_match('/[^'.$page->url['chars'].'\/]/', $value)) {
            // Categories should never get here as folder names have already been enforced
            $path = $this->url($value);
        }
        if (!isset($this->ids[$table][$path])) {
            $this->ids['updated'][$table] = true;
            if ($table == 'categories') {
                $parent = 0;
                $previous = '';
                foreach (explode('/', $path) as $uri) {
                    if (!isset($this->ids['categories'][$previous.$uri])) {
                        $category = ($name = $this->config($table, $previous.$uri, 'name')) ? $name : ucwords(str_replace('-', ' ', $uri));
                        $this->ids['categories'][$previous.$uri] = $this->db->insert('categories', array(
                            'path' => $previous.$uri,
                            'name' => $category,
                            'parent' => $parent,
                        ));
                    }
                    $parent = $this->ids['categories'][$previous.$uri];
                    $previous .= $uri.'/';
                }
            } else {
                if ($name = $this->config($table, $path, 'name')) {
                    $value = $name;
                } elseif (strtolower($value) == $value) { // no uppercase characters
                    $value = $this->title($value);
                }
                $this->ids[$table][$path] = $this->db->insert($table, array('path' => $path, 'name' => $value));
            }
        }

        return $this->ids[$table][$path];
    }

    /**
     * Updates the Blog's config.yml file if anything has changed.
     */
    private function updateConfig()
    {
        if ($this->getId('updated', 'anything') === false) {
            return;
        }
        $yaml = array();

        // Blog
        $yaml['blog'] = $this->config('blog');

        // Authors
        $yaml['authors'] = array();
        $authors = $this->config('authors');
        foreach ($this->db->all(array(
            'SELECT authors.path, authors.name',
            'FROM blog AS b',
            'INNER JOIN authors ON b.author_id = authors.id',
            'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND b.author_id != 0',
            'GROUP BY authors.id',
            'ORDER BY authors.name ASC',
        ), '', 'assoc') as $row) {
            $merge = (isset($authors[$row['path']])) ? $authors[$row['path']] : array();
            $yaml['authors'][$row['path']] = array_merge(array(
                'name' => $row['name'],
                'image' => '',
            ), $merge);
            unset($authors[$row['path']]);
        }
        foreach ($authors as $path => $values) {
            $yaml['authors'][$path] = $values;
        }

        // Categories
        $yaml['categories'] = array();
        $categories = $this->config('categories');
        $hier = new Hierarchy($this->db, 'categories');
        if ($this->getId('updated', 'categories')) {
            $hier->refresh('name');
        }
        $tree = $hier->tree(array('path', 'name'));
        unset($hier);
        foreach ($tree as $row) {
            $merge = (isset($categories[$row['path']])) ? $categories[$row['path']] : array();
            $yaml['categories'][$row['path']] = array_merge(array(
                'name' => $row['name'],
            ), $merge);
            unset($categories[$row['path']]);
        }
        foreach ($categories as $path => $values) {
            $yaml['categories'][$path] = $values;
        }

        // Tags
        $yaml['tags'] = array();
        $tags = $this->config('tags');
        foreach ($this->db->all(array(
            'SELECT tags.path, tags.name',
            'FROM tagged AS t',
            'INNER JOIN tags ON t.tag_id = tags.id',
            'GROUP BY tags.id',
            'ORDER BY tags.name ASC',
        ), '', 'assoc') as $row) {
            $merge = (isset($tags[$row['path']])) ? $tags[$row['path']] : array();
            $yaml['tags'][$row['path']] = array_merge(array(
                'name' => $row['name'],
            ), $merge);
            unset($tags[$row['path']]);
        }
        foreach ($tags as $path => $values) {
            $yaml['tags'][$path] = $values;
        }

        file_put_contents($this->folder.'config.yml', Yaml::dump($yaml, 3));
    }
}
