<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Files;
use BootPress\Admin\Component as Admin;
use BootPress\Sitemap\Component as Sitemap;
use BootPress\Upload\Component as Upload;
use BootPress\Unzip\Component as Unzip;
use BootPress\Page\Component as Page;
use ZipStream; // maennchen/zipstream-php

class Blog
{
    public static function setup($auth, $path)
    {
        if (!$auth->isAdmin(2)) {
            return false;
        }
        extract(Admin::params('bp', 'blog', 'page'));
        $links = array();
        $links[$bp->icon('pencil-square-o', 'fa').' New'] = '';
        if ($unpublished = $blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND (published = 0 OR published > 1)')) {
            $links['<i class="fa fa-exclamation-triangle text-red"></i> <b class="text-red">Unpublished</b> '.$bp->badge($unpublished, 'right')] = 'unpublished';
        }
        if (strpos($page->url['path'], '/published') !== false) {
            if ($search = $page->get('search')) {
                $sitemap = new Sitemap();
                $count = $sitemap->count($search, 'blog');
                unset($sitemap);
            } else {
                $count = $blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published != 0');
            }
            $links[$bp->icon('search').' Search '.$bp->badge($count, 'right')] = 'published';
        }
        if ($posts = $blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published < 0')) {
            $links[$bp->icon('thumb-tack', 'fa').' Posts '.$bp->badge($posts, 'right')] = 'posts';
        }
        if ($pages = $blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published = 1')) {
            $links[$bp->icon('files-o', 'fa').' Pages '.$bp->badge($pages, 'right')] = 'pages';
        }
        if ($auth->isAdmin(1)) {
            $links[$bp->icon('cog', 'fa').' Config'] = 'config';
            if ($unpublished || $posts || $pages) {
                $links[$bp->icon('download', 'fa').' Backup'] = 'backup';
            }
            $links[$bp->icon('upload', 'fa').' Restore'] = 'restore';
        }
        if ($posts || $pages) {
            Admin::sidebar($bp->search($page->url('admin', $path, 'published'), array(
                'class' => 'sidebar-form',
                'button' => $bp->button('btn-flat', $bp->icon('search', 'fa'), array(
                    'title' => 'Search',
                    'type' => 'submit',
                )),
            )), 'prepend');
        }

        return array($bp->icon('globe', 'fa').' Blog' => $links);
    }

    public static function page()
    {
        extract(Admin::params('bp', 'blog', 'auth', 'page', 'admin', 'path', 'method'));
        if ($method) {
            switch ($method) {
                case 'unpublished':
                    $header = $bp->icon('exclamation-triangle', 'fa');
                    $header .= ' Unpublished';
                    break;
                case 'published':
                    $header = $bp->icon('search', 'fa', 'i sytle="margin-right:10px;"');
                    $header .= ($search = $page->get('search')) ? " Search for '{$search}'" : ' Search';
                    break;
                case 'posts':
                    $header = $bp->icon('thumb-tack', 'fa').' Posts';
                    break;
                case 'pages':
                    $header = $bp->icon('files-o', 'fa').' Pages';
                    break;
                case 'config':
                    $header = $bp->icon('cog', 'fa').' Config';
                    break;
                case 'backup':
                    $header = $bp->icon('download', 'fa').' Backup';
                    break;
                case 'restore':
                    $header = $bp->icon('upload', 'fa').' Restore';
                    break;
            }
            $html = self::$method();
        } else {
            $header = $bp->icon('pencil-square-o', 'fa');
            $header .= ($page->get('edit')) ? ' Edit' : ' New';
            // $html = self::form();
            list($html, $media) = self::form();
            if ($page->get('image')) {
                return Admin::box('default', array(
                    'head with-border' => $bp->icon('image', 'fa').' Image',
                    'body' => $html,
                ));
            }
        }
        $docs = $bp->button('md link', 'Documentation '.$bp->icon('new-window'), array(
            'href' => 'https://www.bootpress.org/docs/blog/',
            'target' => '_blank',
        ));
        $bp->pagination->html('links', array('wrapper' => '<ul class="pagination pagination-sm no-margin">{{ value }}</ul>'));
        $html = Admin::box('default', array(
            'head with-border' => array($header, $docs),
            'body' => $html,
            'foot clearfix' => $bp->pagination->links(),
        ));
        if (isset($media)) {
            $html .= Admin::box('default', array(
                'head with-border' => array($bp->icon('globe', 'fa').' Blog'),
                'body' => $media,
            ));
        }

        return $html;
    }

    private static function unpublished()
    {
        extract(Admin::params(array('page', 'website')));
        $page->title = 'Unpublished Posts and Pages at '.$website;

        return static::blog(array(
            'WHERE featured <= 0 AND (published = 0 OR published > 1)',
            'ORDER BY featured, published, updated ASC',
        ));
    }

    private static function published()
    {
        extract(Admin::params(array('bp', 'blog', 'page', 'website')));
        if ($search = $page->get('search')) {
            $page->title = 'Search Published Posts and Pages at '.$website;
            $sitemap = new Sitemap();
            if (!$bp->pagination->set('page', 20)) {
                $bp->pagination->total($sitemap->count($search, 'blog'));
            }
            $results = array();
            foreach ($sitemap->search($search, 'blog', $bp->pagination->limit) as $row) {
                $results[$row['id']] = $row['snippet'];
            }
            unset($sitemap);

            return static::ids(array_keys($results), $results);
        }
        $page->title = 'Published Posts and Pages at '.$website;

        return static::blog(array(
            'WHERE featured <= 0 AND published != 0',
            'ORDER BY published, updated ASC',
        ));
    }

    private static function posts()
    {
        extract(Admin::params(array('page', 'website')));
        $page->title = 'Published Posts at '.$website;

        return static::blog(array(
            'WHERE featured <= 0 AND published < 0',
            'ORDER BY featured, published, updated ASC',
        ));
    }

    private static function pages()
    {
        extract(Admin::params(array('page', 'website')));
        $page->title = 'Published Pages at '.$website;

        return static::blog(array(
            'WHERE featured <= 0 AND published = 1',
            'ORDER BY featured, published, updated ASC',
        ));
    }

    private static function config()
    {
        extract(Admin::params(array('bp', 'blog', 'page', 'plugin', 'website')));
        $page->title = 'Config Blog at '.$website;
        $media = Files::view($blog->folder, array(
            'exclude' => 'config.yml',
            'files' => 'yml',
            'images' => 'jpg|jpeg|gif|png',
            'resources' => false,
        ));
        if ($page->get('image')) {
            return $media;
        }
        $html = '';
        $form = $bp->form('blog_config');
        $config = Files::textarea($form, 'config', $blog->folder.'config.yml');
        $html .= $form->header();
        $html .= $form->field(array('config.yml',
            'Manage your blog\'s authors, categories, and tags.',
        ), $config);
        $html .= $form->close();

        return $html.$media;
    }

    private static function backup()
    {
        extract(Admin::params('blog', 'page', 'website'));
        $page->title = 'Backup Blog at '.$website;
        $path = $blog->folder;
        list($dirs, $files) = Files::iterate($path, false, 'yml|jpg|jpeg|gif|png');
        list($dirs, $content) = Files::iterate($path.'content/', 'recursive', 'yml|twig|js|css|jpg|jpeg|gif|png|pdf|zip|mp3|mp4');
        $content = preg_filter('/^/', 'content/', $content);
        $zip = new ZipStream\ZipStream('backup_'.$blog->url($website).'_blog-content_'.date('Y-m-d').'.zip');
        foreach (array_merge($files, $content) as $file) {
            $zip->addFileFromPath($file, $path.$file, array('time' => filemtime($path.$file)));
        }
        $zip->finish();
    }

    private static function restore()
    {
        extract(Admin::params('bp', 'page', 'blog', 'website'));
        $page->title = 'Restore Blog at '.$website;
        $html = '';
        $form = $bp->form('restore_blog');
        $upload = array(
            Upload::bootstrap($form)->file('upload', array(
                'size' => '200M',
                'types' => 'zip',
            )),
            'Upload a zipped archive of your blog\'s config files, and content folder.',
        );
        $form->validator->set('database');
        if ($vars = $form->validator->certified()) {
            if (!empty($vars['upload']) && is_file(Upload::$folder.$vars['upload'])) {
                $zip = new Unzip(Upload::$folder.$vars['upload'], $blog->folder);
                $files = $zip->files();
                $common = $page->commonDir($files);
                $start = strlen($common);
                $config = array();
                foreach ($files as $file) {
                    $file = substr($file, $start);
                    if (strpos($file, '/') === false && preg_match('/.+\.(yml|jpg|jpeg|gif|png)$/', $file)) {
                        $config[] = $common.$file;
                    }
                }
                $zip->extractFiles($config);
                $zip->extractFolders($common.'content', 'yml|twig|js|css|jpg|jpeg|gif|png|pdf|zip|mp3|mp4', 'remove_common_dir');
                $zip->close();
                unset($zip);
                unlink(Upload::$folder.$vars['upload']);
                $form->message('info', 'Thank you!  Your blog has been restored, and the database reset.');
            } else {
                $form->message('info', 'Thank you!  Your blog database has been reset.');
            }
            if (is_file($blog->folder.'Blog.db')) {
                $blog->db->connection()->close();
                unlink($blog->folder.'Blog.db');
            }
            $page->eject($form->eject);
        }
        $html .= $form->header();
        list($field, $info) = $upload;
        $html .= $form->field(array('Upload', $info), $field, $form->validator->error('upload'));
        $html .= $form->submit();
        $html .= $form->field('', '<p class="form-control-static">Submitting this form will also recreate your blog\'s database.</p>');
        $form->hidden['database'] = 'delete';
        $html .= $form->close();

        return $html;
    }

    /**
     * A form to either edit or create a new blog post.
     *
     * @return string
     */
    private static function form()
    {
        extract(Admin::params(array('bp', 'blog', 'page', 'plugin', 'website')));
        $html = '';
        $media = '';
        $form = $bp->form('blog_entry');
        if ($edit = $page->get('edit')) {
            $page->title = 'Edit Blog Post or Page at '.$website;
            if (!$path = $blog->db->value('SELECT path FROM blog WHERE id = ?', $edit)) {
                $page->eject($page->url('delete', '', '?'));
            }
            if ($page->get('delete') == 'post') {
                $base = $blog->folder.'content/'.$path;
                list($dirs, $files) = Files::iterate($base);
                foreach ($files as $file) {
                    unlink($base.$file);
                }
                if (empty($dirs) && is_dir($base)) {
                    rmdir($base);
                }
                $blog->file($path);
                $page->eject($page->url('delete', '', '?'));
            }
            $index = Files::textarea($form, 'index', array(
                $blog->folder.'content/'.$path.'/index.html.twig',
                $blog->folder.'template.twig',
                $page->file($plugin, 'Pages/blog/template.twig'),
            ));
            $form->values['url'] = $path;
            $media = Files::view($blog->folder.'content/'.$path, array(
                'exclude' => 'index.html.twig',
                'files' => 'twig|js|css',
                'resources' => 'pdf|zip|mp3|mp4',
            ));
            if ($page->get('image')) {
                return array($html, $media);
            }
            $form->validator->set(array(
                'url' => 'required',
                'index' => '',
            ));
        } else {
            $page->title = 'New Blog Post or Page at '.$website;
            $form->validator->set('blog', 'required');
            $template = Files::textarea($form, 'template', array(
                $blog->folder.'template.twig',
                $page->file($plugin, 'Pages/blog/template.twig'),
            ));

            // Breadcrumb links
            $dir = $page->dir($blog->folder, 'content');
            $url = $page->url('delete', '', 'content');
            $links = array('content' => $url);
            $start = strlen($dir);
            if ($folder = $page->get('content')) {
                foreach (explode('/', trim($folder, '/')) as $name) {
                    $name = trim($name, '.');
                    if (!is_dir($dir.$name)) {
                        break;
                    }
                    $dir .= $name.'/';
                    $links[$name] = $page->url('add', '', 'content', substr($dir, $start, -1));
                }
                $current = substr($dir, $start, -1);
                if ($current != $folder) {
                    $page->eject($page->url('add', '', 'content', $current));
                }
            }
            $pulldown = array();
            list($dirs) = Files::iterate($dir);
            foreach ($dirs as $name) {
                $pulldown[$name] = $page->url('add', '', 'content', substr($dir.$name, $start));
            }
            if (!empty($pulldown)) {
                $links[''] = $pulldown;
            }
            $links[] = '';

            // Media files
            $media = Files::view($dir, array(
                'files' => 'twig|js|css',
                'resources' => 'pdf|zip|mp3|mp4',
            ));
            if ($page->get('image')) {
                return array($html, $media);
            }
            $media = $bp->breadcrumbs($links).$media;
        }

        if ($vars = $form->validator->certified()) {
            if ($edit) {
                $rename = self::seo($vars['url'], $path, $edit);
                $old = $blog->folder.'content/'.$path.'/';
                $new = $blog->folder.'content/'.$rename.'/';
                if ($rename != $path && !is_file($new.'index.html.twig')) {
                    if (!is_dir($new)) {
                        mkdir($new, 0755, true);
                    }
                    list($dirs, $files) = Files::iterate($old);
                    foreach ($files as $file) {
                        rename($old.$file, $new.$file);
                    }
                    if (empty($dirs) && is_dir($old)) {
                        rmdir($old);
                    }
                    $blog->file($path); // To remove
                    $id = $blog->file($rename);
                    $form->eject = $page->url('add', $form->eject, 'edit', $id);
                } else {
                    $form->validator->errors['url'] = 'Sorry, this URL has already been taken.';
                }
            } else {
                $title = $blog->title($vars['blog']);
                $path = self::seo($vars['blog']);
                $dir = $blog->folder.'content/'.$path.'/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (is_file($blog->folder.'template.twig')) {
                    $template = $blog->folder.'template.twig';
                } else {
                    $template = $page->file($plugin, 'Pages/blog/template.twig');
                }
                $template = preg_replace('/(\stitle:)(\s)/', '$1 \''.str_replace("'", "''", $title).'\'$2', file_get_contents($template));
                file_put_contents($dir.'index.html.twig', $template);
                $id = $blog->file($path);
                $form->eject = $page->url('add', $form->eject, 'edit', $id);
            }
            if (empty($form->validator->errors)) {
                $page->eject($form->eject);
            }
        }

        if ($edit) {
            $post = $blog->db->row('SELECT path, title, published FROM blog WHERE id = ?', $edit, 'assoc');
            if (empty($post['title'])) {
                $post['title'] = $post['path'];
            }
            $title = '<a href="'.$page->url('base', $post['path']).'" target="_blank">'.$post['title'].' '.$bp->icon('new-window').'</a>';
            $type = ($post['published'] < 0) ? 'post' : 'page';
            $delete = $bp->button('sm danger delete pull-right', $bp->icon('trash'), array('title' => 'Click to delete this '.$type));
            $html .= '<p class="lead">'.$title.' '.$delete.'</p>';
            $page->jquery('
                $("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = window.location.href; });
                $(".delete").click(function(){
                    if (confirm("Are you sure you would like to delete this ' .$type.'?")) {
                        window.location = "' .str_replace('&amp;', '&', $page->url('add', '', 'delete', 'post')).'";
                    }
                });
            ');
        }

        // Form begin
        $html .= $form->header();

        if ($edit) {

            // Category
            $select = array();
            $suffix = ($edit) ? (($slash = strrpos($post['path'], '/')) ? substr($post['path'], $slash) : '/'.$post['path']) : '/';
            foreach ($blog->db->all('SELECT path FROM categories ORDER BY path ASC', '', 'assoc') as $category) {
                $select[$category['path'].$suffix] = $category['path'].$suffix;
            }
            if (!empty($select)) {
                if ($edit && isset($select[$post['path']])) {
                    $select[$post['path']] .= ' (current url)';
                }
                $form->menu('category', array($post['path'] => '&nbsp;') + $select);
                $html .= $form->field(array('Category',
                    'Optionally select an established category for your path.',
                ), $form->select('category'));
                $page->jquery('
                    $("select[name=category]").change(function(){
                        $("input[name=url]").focus().val($(this).val());
                    });
                ');
            }
            // URL
            $append = array();
            if ($page->url['suffix'] != '') {
                $append[] = $page->url['suffix'];
            }
            $append[] = $bp->button('primary', 'Submit', array(
                'type' => 'submit',
                'data-loading-text' => 'Submitting...',
            ));
            $html .= $form->field(array('Path',
                'A unique URL path to this post that should never change once it has been published.',
            ), $form->group('/', $append, $form->text('url')));

            // Index
            $html .= $form->field(array('Template',
                'This is the index.html.twig file that contains the content of your page or post.',
            ), $index);
        } else {

            // Blog
            $append = $bp->button('primary', 'Submit', array(
                'type' => 'submit',
                'data-loading-text' => 'Submitting...',
            ));
            $html .= $form->field(array('Blog',
                'Enter the title of your new blog page or post.',
            ), $form->group('', $append, $form->text('blog')));

            // Template
            $html .= $form->field(array('Template',
                'This template will kick start your new blog page or post.',
            ), $template);
        }

        // Form end
        $html .= $form->close();

        return array($html, $media);
    }

    private static function blog(array $query)
    {
        extract(Admin::params(array('bp', 'blog')));
        if (!$bp->pagination->set('page', 20)) {
            $bp->pagination->total($blog->db->value('SELECT COUNT(*) FROM blog '.$query[0]));
        }

        return static::ids($blog->db->ids(array(
            'SELECT id FROM blog',
            array_shift($query),
            array_shift($query).$bp->pagination->limit,
        )));
    }

    /**
     * This method ensures a unique path for creating a new blog post, or when moving an old one.
     *
     * @param string $new The submitted path
     * @param string $old The former path
     * @param int    $id  The current blog id
     *
     * @return string The unique blog path
     */
    private static function seo($new, $old = '', $id = 0)
    {
        extract(Admin::params('blog', 'page'));
        if (!empty($new) && $new == $old) {
            return $old; // no changes were made
        }
        $seo = $blog->url($new, 'slashes');
        if (!empty($seo) && !$blog->db->value('SELECT id FROM blog WHERE path = ? AND id != ?', array($seo, $id))) {
            return $seo;
        }
        if (!empty($seo)) {
            $seo .= '-';
        }
        $increment = 1;
        while ($blog->db->value('SELECT id FROM blog WHERE path = ?', $seo.$increment)) {
            ++$increment;
        }

        return $seo.$increment;
    }

    private static function ids(array $ids, array $snippets = array())
    {
        $html = '';
        extract(Admin::params(array('bp', 'blog', 'page')));
        foreach ($blog->info($ids) as $id => $row) {
            if ($row['published'] === false) { // unpublished
                $reference = '<span class="timeago" title="'.date('c', $row['updated']).'">'.$row['updated'].'</span>';
            } elseif ($row['published'] === true) { // page
                $reference = $bp->icon('refresh', 'fa').' <span class="timeago" title="'.date('c', $row['updated']).'">'.$row['updated'].'</span>';
            } else { // post
                $reference = $bp->icon('tack', 'fa').' '.date('M j, Y', $row['published']);
            }
            $thumb = '';
            if (isset($row['page']['image'])) {
                $thumb = '<img src="'.$row['page']['image'].'?w=75&h=75" width="75" height="75">';
            }
            $listing = '<h4>';
            $listing .= '<a href="'.$page->url('base', $row['path']).'">';
            $listing .= (!empty($row['title'])) ? $row['title'] : $row['path'];
            $listing .= '</a> <small class="pull-right">'.$reference.'</small>';
            $listing .= '</h4>';
            $listing .= '<p>';
            $listing .= '<span class="text-danger">';
            $listing .= '<small>'.$page->url('base', $row['path']).'</small>';
            $listing .= '</span><br>';
            if (isset($snippets[$id])) {
                $listing .= $snippets[$id];
            } elseif (isset($row['page']['description'])) {
                $listing .= $row['page']['description'];
            } else {
                $content = strip_tags($row['content']);
                $listing .= (mb_strlen($content) > 500) ? mb_substr($content, 0, 500).'&hellip;' : $content;
            }
            $listing .= '</p>';
            $html .= $bp->row('sm', array(
                $bp->col(1, '<p>'.$bp->button('xs warning', $bp->icon('pencil').' edit', array(
                    'href' => $page->url('admin', Admin::$path.'?edit='.$id),
                )).'</p>'),
                $bp->col(11, $bp->media(array($thumb, $listing))),
            )).'<br>';
        }

        return $html;
    }
}
