<?php

namespace BootPress\Blog;

use BootPress\Page\Component as Page;
use BootPress\Sitemap\Component as Sitemap;
use BootPress\Hierarchy\Component as Hierarchy;

class Component extends Blog
{
    /**
     * Takes the current path and determines if there is any Blog content associated with it.
     * 
     * 
     * @return array|bool False if there is not, or a ``$template`` array suitable for passing to ``$this->fetchTwig($template)`` with the following keys:
     *
     * - '**file**' - The appropriate TWIG template to process the 'vars'.
     * - '**type**' - The kind of Blog page you are working with.  Either 'page', 'txt', 'json', 'xml', 'rdf', 'rss', 'atom', 'post', 'category', 'index', 'archives', 'authors', or 'tags'
     * - '**vars**' - 
     * - '**default**' - 
     */
    public function page()
    {
        $page = Page::html();
        $listings = $this->config('blog', 'listings');
        if ($id = $this->file($page->url['path'])) {
            $params = array('id' => $id, 'path' => $page->url['path']);
            $method = 'blog';
        } elseif ($route = $page->routes(array(
            $listings,
            $listings.'/[archives:path]/[i:year]?/[i:month]?/[i:day]?',
            $listings.'/[authors|tags:path]/[:name]?',
            '[**:category]',
        ))) {
            $params = $route['params'];
            $method = (isset($params['path'])) ? $params['path'] : 'listings';
            if ($method == 'listings' && $search = $page->request->query->get('search')) {
                $params['search'] = $search;
            }
        }
        if (!isset($method) || (isset($params['category']) && !is_dir($this->folder.'content/'.$params['category']))) {
            $template = false;
        } elseif ($template = $this->$method($params)) {
            $template = array_combine(array('file', 'type', 'vars'), $template);
            $template['default'] = __DIR__.'/theme/';
            $this->theme->globalVars('blog', array('page' => $template['type']));
            if (empty($template['file']) && $template['type'] != 'html') {
            }
        }

        return $template;
    }

    private function blog($params, array $vars = array())
    {
        $page = Page::html();
        extract($params); // 'id' and 'path'
        $page->enforce($path);
        $info = $this->info($id);
        if (is_bool($info['published'])) { // a page of some sort
            if ($dot = strpos($path, '.')) {
                return array('', substr($path, $dot + 1), array('content' => $info['content']));
            } else {
                return array('blog-page.html.twig', 'post', $info);
            }
        }
        $vars['post'] = $info;
        if ($search = $page->request->query->get('search')) {
            $sitemap = new Sitemap();
            if ($docid = $sitemap->db->value('SELECT docid FROM sitemap WHERE path = ?', $path)) {
                $words = $sitemap->words($search, $docid);
                if (!empty($words)) {
                    $vars['search'] = $words;
                }
            }
            unset($sitemap);
        }
        $vars['breadcrumbs'] = $this->breadcrumbs();
        foreach ($vars['post']['categories'] as $category) {
            $vars['breadcrumbs'][$category['name']] = $category['url'];
        }
        $vars['breadcrumbs'][$vars['post']['title']] = $vars['post']['url'];

        return array('blog-post.html.twig', 'post', $vars);
    }

    private function listings($params, array $vars = array())
    {
        $page = Page::html();
        extract($params); // 'path'?, 'category'? and 'search'?
        if (isset($category)) {
            $url = $page->url('base', $category);
        } else {
            $url = $page->url('blog/listings');
        }
        $page->enforce($url);
        $type = (isset($category)) ? 'category' : 'index';
        $vars['listings'] = array();
        $vars['breadcrumbs'] = $this->breadcrumbs();
        if (isset($category)) {
            $hier = new Hierarchy($this->db, 'categories');
            $path = $hier->path('path', $category, array('path', 'name'));
            if (empty($path)) {
                return false;
            }
            $tree = $hier->tree(array('path', 'name'), 'path', $category);
            $counts = $hier->counts('blog', 'category_id');
            foreach ($tree as $id => $fields) {
                $tree[$id]['count'] = $counts[$id];
            }
            foreach ($path as $row) {
                $vars['category'][] = $row['name'];
                $vars['breadcrumbs'][$row['name']] = $page->url('base', $row['path']);
            }
            $vars['categories'] = $this->query('categories', array('nest' => $hier->nestify($tree), 'tree' => $tree));
            $vars['listings']['categories'] = array_keys($tree);
        }
        if (isset($search) && !empty($search)) {
            $vars['search'] = $search;
            $vars['breadcrumbs']['Search'] = $page->url('add', $url, 'search', $search);
            $vars['listings']['search'] = $search;
        }

        return array('blog-listings.html.twig', $type, $vars);
    }

    private function archives($params, array $vars = array())
    {
        $page = Page::html();
        list($path, $Y, $m, $d) = array_pad(array_values($params), 4, '');
        if (!empty($d)) {
            list($from, $to) = $this->range($Y, $m, $d);
            $page->enforce($page->url('blog/listings', $path, date('/Y/m/d', $from)));
            $vars['archive'] = array_combine(array('date', 'year', 'month', 'day'), explode(' ', date($from.' Y F j', $from)));
            $vars['breadcrumbs'] = $this->breadcrumbs(array(
                'Archives' => $path,
                $vars['archive']['year'] => $Y,
                $vars['archive']['month'] => $m,
                $vars['archive']['day'] => $d,
            ));
        } elseif (!empty($m)) {
            list($from, $to) = $this->range($Y, $m);
            $page->enforce($page->url('blog/listings', $path, date('/Y/m', $from)));
            $vars['archive'] = array_combine(array('date', 'year', 'month'), explode(' ', date($from.' Y F', $from)));
            $vars['breadcrumbs'] = $this->breadcrumbs(array(
                'Archives' => $path,
                $vars['archive']['year'] => $Y,
                $vars['archive']['month'] => $m,
            ));
        } elseif (!empty($Y)) {
            list($from, $to) = $this->range($Y);
            $page->enforce($page->url('blog/listings', $path, date('/Y', $from)));
            $vars['archive'] = array_combine(array('date', 'year'), explode(' ', date($from.' Y', $from)));
            $vars['breadcrumbs'] = $this->breadcrumbs(array(
                'Archives' => $path,
                $vars['archive']['year'] => $Y,
            ));
        } else {
            $page->enforce($page->url('blog/listings', $path));
            $vars['archives'] = $this->query('archives');
            $vars['breadcrumbs'] = $this->breadcrumbs(array(
                'Archives' => $path,
            ));

            return array('blog-archives.html.twig', 'archives', $vars);
        }
        $vars['listings']['archives'] = array($from, $to);

        return array('blog-listings.html.twig', 'archives', $vars);
    }

    private function authors($params, array $vars = array())
    {
        $page = Page::html();
        extract($params); // 'path' and 'name'?
        $vars['breadcrumbs'] = $this->breadcrumbs(array('Authors' => $path));
        if (!isset($name)) { // just authors, no posts
            $page->enforce($page->url('blog/listings', $path));
            $vars['authors'] = $this->query('authors');

            return array('blog-authors.html.twig', 'authors', $vars);
        }
        $vars['author'] = $this->query('authors', $name);
        if (empty($vars['author'])) {
            $page->eject($page->url('blog/listings', $path));

            return false;
        }
        $vars['breadcrumbs'][$vars['author']['name']] = $page->url('blog/listings', $path, $vars['author']['path']);
        $vars['listings']['count'] = $vars['author']['count'];
        $vars['listings']['authors'] = $name;

        return array('blog-listings.html.twig', 'authors', $vars);
    }

    private function tags($params, array $vars = array())
    {
        $page = Page::html();
        extract($params); // 'path' and 'name'?
        $vars['breadcrumbs'] = $this->breadcrumbs(array('Tags' => $path));
        if (!isset($name)) { // search all tags and get a frequency count
            $page->enforce($page->url('blog/listings', $path));
            $vars['tags'] = $this->query('tags');

            return array('blog-tags.html.twig', 'tags', $vars);
        }
        $vars['tag'] = $this->query('tags', $name);
        if (empty($vars['tag'])) {
            $page->eject($page->url('blog/listings', $path));

            return false;
        }
        $vars['breadcrumbs'][$vars['tag']['name']] = $page->url('blog/listings', $path, $vars['tag']['path']);
        $vars['listings']['count'] = $vars['tag']['count'];
        $vars['listings']['tags'] = $vars['tag']['path'];

        return array('blog-listings.html.twig', 'tags', $vars);
    }

    private function breadcrumbs(array $links = array())
    {
        $breadcrumbs = array();
        $breadcrumbs[$this->config('blog', 'breadcrumb')] = Page::html()->url('blog/listings');
        $previous = '';
        foreach ($links as $name => $path) {
            $breadcrumbs[$name] = Page::html()->url('blog/listings', $previous.$path);
            $previous .= $path.'/';
        }

        return $breadcrumbs;
    }

    private function range($Y, $m = null, $d = null)
    {
        if (!empty($d)) {
            $from = mktime(0, 0, 0, (int) $m, (int) $d, (int) $Y);
            $to = mktime(23, 59, 59, (int) $m, (int) $d, (int) $Y);
        } elseif (!empty($m)) {
            $from = mktime(0, 0, 0, (int) $m, 1, (int) $Y);
            $to = mktime(23, 59, 59, (int) $m + 1, 0, (int) $Y);
        } else {
            $from = mktime(0, 0, 0, 1, 1, (int) $Y);
            $to = mktime(23, 59, 59, 1, 0, (int) $Y + 1);
        }

        return array($from, $to);
    }
}
