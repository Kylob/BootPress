<?php

namespace BootPress\Asset;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\ServerFactory;
use MatthiasMullie\Minify;
use phpUri;

class Component
{
    const PREG_TYPES = 'jpe?g|gif|png|ico|js|css|pdf|ttf|otf|svg|eot|woff2?|swf|tar|t?gz|g?zip|csv|xls?x?|word|docx?|pptx?|psd|ogg|wav|mp3|mp4|mpeg?|mpg|mov|qt';
    public static $instance; // made public to facilitate testing
    public static $not_found = array();
    public static $urls = array();
    private $cached = null;
    private $db = null;

    /**
     * Prepares a Symfony Response for you to send.
     * 
     * @param string       $file     Either a file location, or the type of file you are sending eg. html, txt, less, scss, json, xml, rdf, rss, atom, js, css
     * @param array|string $options  An array of options if ``$file`` is a location, or the string of data you want to send.  The available options are:
     * 
     * - '**name**' - changes a downloadable asset's file name
     * - 
     * - 
     * 
     * @return object  A Symfony\Component\HttpFoundation\Response
     */
    public static function dispatch($file, $options = array())
    {
        $set = array_merge(array(
            'name' => '',
            'expires' => 0,
            'xsendfile' => false,
        ), (array) $options);
        $page = Page::html();
        if (preg_match('/^(html|txt|less|scss|json|xml|rdf|rss|atom|js|css)$/', $file)) {
            if (is_array($options)) {
                foreach ($options as $updated => $content) {
                    if (is_numeric($updated)) {
                        break;
                    }
                }
            } else {
                $content = (string) $options;
            }
            $response = new Response($content, Response::HTTP_OK, array(
                'Content-Type' => static::mime($file),
                'Content-Length' => mb_strlen($content),
            ));
            if (isset($updated) && (int) $updated > 631152000) { // 01-01-1990
                $response->setCache(array(
                    'public' => true,
                    'max_age' => $set['expires'],
                    's_maxage' => $set['expires'],
                    'last_modified' => \DateTime::createFromFormat('U', (int) $updated),
                ))->isNotModified($page->request);
            }

            return $response;
        }
        $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (is_null($file) || !is_file($file)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
        if (null === $mime = static::mime($type)) {
            return new Response('', Response::HTTP_NOT_IMPLEMENTED);
        }
        if (preg_match('/^('.implode('|', array(
            '(?P<download>tar|t?gz|g?zip|csv|xls?x?|word|docx?|pptx?|psd)',
            '(?P<stream>ogg|wav|mp3|mp4|mpeg?|mpg|mov|qt)',
        )).')$/', $type, $matches)) {
            $response = new BinaryFileResponse($file);
            $response->headers->set('Content-Type', $mime);
            $response->setContentDisposition(isset($matches['download']) ? 'attachment' : 'inline', $set['name']);
            if ($set['xsendfile']) {
                BinaryFileResponse::trustXSendfileTypeHeader();
            }

            return $response;
        }
        $file = new \SplFileInfo($file);
        $response = new StreamedResponse(function () use ($file) {
            if ($fp = fopen($file->getPathname(), 'rb')) {
                rewind($fp);
                fpassthru($fp);
                fclose($fp);
            }
        }, 200, array(
            'Content-Type' => $mime,
            'Content-Length' => $file->getSize(),
        ));
        $response->setCache(array(
            'public' => true,
            'max_age' => $set['expires'],
            's_maxage' => $set['expires'],
            'last_modified' => \DateTime::createFromFormat('U', $file->getMTime()),
        ));
        $response->isNotModified($page->request);

        return $response;
    }

    public static function cached($dir, array $glide = array())
    {
        $page = Page::html();
        static::$instance = new static();
        $asset = static::$instance;
        $asset->cached = $page->dir($dir);
        $type = strtolower($page->url['format']);
        if ($type == 'html') {
            $page->filter('page', array($asset, 'urls'), 'this');

            return false;
        } elseif (!preg_match('/^'.implode('', array(
            '(?P<paths>([1-9a-z]{5}[0]?)+)',
            '(\/.*)?',
            '\.('.self::PREG_TYPES.')',
        )).'$/i', $page->url['path'], $matches)) {
            return false;
        }
        $paths = explode('0', rtrim($matches['paths'], '0'));
        foreach ($paths as $key => $value) {
            $paths[$key] = '"'.$value.'"';
        }
        $minify = array();
        $image = null;
        $file = null;
        $asset->openDatabase();
        if ($stmt = $asset->db->query(array(
            'SELECT p.tiny, f.file, f.query',
            'FROM paths as p',
            'INNER JOIN files AS f ON p.file_id = f.id',
            'WHERE p.tiny IN('.implode(', ', $paths).')',
            $asset->db->orderIn('p.tiny', $paths),
        ), '', 'assoc')) {
            while ($row = $asset->db->fetch($stmt)) {
                if ($type == strtolower(pathinfo($row['file'], PATHINFO_EXTENSION))) {
                    switch ($type) {
                        case 'js':
                        case 'css':
                            if (is_file($row['file'])) {
                                $minify[$row['file']] = $row;
                            }
                            break 1;
                        case 'jpeg':
                        case 'jpg':
                        case 'gif':
                        case 'png':
                            if (!empty($row['query'])) {
                                parse_str($row['query'], $params);
                                $setup = $glide;
                                $glide = array(
                                    'cache' => $asset->cached.'glide/',
                                    'source' => dirname($row['file']),
                                    'response' => new SymfonyResponseFactory($page->request),
                                );
                                if (isset($setup['group_cache_in_folders']) && is_bool($setup['group_cache_in_folders'])) {
                                    $glide['group_cache_in_folders'] = $setup['group_cache_in_folders'];
                                }
                                if (isset($setup['watermarks'])) {
                                    $glide['watermarks'] = rtrim(str_replace('\\', '/', $setup['watermarks']), '/');
                                }
                                if (isset($setup['driver']) && in_array($setup['driver'], array('gd', 'imagick'))) {
                                    $glide['driver'] = $setup['driver'];
                                }
                                if (isset($setup['max_image_size']) && is_numeric($setup['max_image_size'])) {
                                    $glide['max_image_size'] = (int) $setup['max_image_size'];
                                }
                                $glide = ServerFactory::create($glide);
                                $image = $glide->getImageResponse(basename($row['file']), $params);
                                break 2;
                            }
                        default:
                            $file = $row['file'];
                            break 2;
                    }
                }
            }
            $asset->db->close($stmt);
        }
        $asset->closeDatabase();
        if (!empty($minify)) {
            $paths = array();
            foreach ($minify as $row) {
                $paths[] = $row['tiny'];
            }
            $file = implode('/', array(
                $asset->cached.'minify',
                md5($page->url['base']),
                implode('0', $paths),
            )).'.'.$type;
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            if (!is_file($file)) {
                switch ($type) {
                    case 'js':
                        $minifier = new Minify\JS();
                        foreach ($minify as $js => $row) {
                            $minifier->add($js);
                        }
                        $minifier->minify($file);
                        break;
                    case 'css':
                        $minifier = new Minify\CSS();
                        foreach ($minify as $css => $row) {
                            $minifier->add($asset->css($css, $row));
                        }
                        $minifier->minify($file);
                        break;
                }
            }
        }

        return ($image) ? $image : static::dispatch($file, array('expires' => 31536000));
    }

    public static function urls($html)
    {
        if (is_null(static::$instance) || empty($html)) {
            return $html;
        }
        $asset = static::$instance;
        $page = Page::html();
        $array = (is_array($html)) ? $html : false;
        if ($array) {
            $html = array();
            array_walk_recursive($array, function ($value) use (&$html) {
                $html[] = $value;
            });
            $html = implode(' ', $html);
        }
        preg_match_all('/'.implode('', array(
            '('.$page->url['preg'].')',
            '(?P<dir>['.$page->url['chars'].']+)\/',
            '(?P<file>['.$page->url['chars'].'.\/]+\.(?P<ext>'.self::PREG_TYPES.'))',
            '(?P<query>\?['.$page->url['chars'].'&;=.\/]+)?',
            '(#(?P<frag>['.$page->url['chars'].'.\/#]+))?',
        )).'/i', $html, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return $array ? $array : $html;
        }
        /*
        if ($array) {
            print_r([$array, $matches, array(
                '('.$page->url['preg'].')',
                '(?P<dir>['.$page->url['chars'].']+)\/',
                '(?P<file>['.$page->url['chars'].'.\/]+\.(?P<ext>'.self::PREG_TYPES.'))',
                '(?P<query>\?['.$page->url['chars'].'&;=.\/]+)?',
                '(#(?P<frag>['.$page->url['chars'].'.\/#]+))?',
            )]);
        }
        */
        $asset->openDatabase();
        $cache = array();
        $assets = array();
        
        $find = $page->dir;
        
        foreach ($matches as $url) {
            $dir = $url['dir'];
            if (!isset($find[$dir])) {
                echo "\n".$dir;
                static::$not_found[] = substr($url[0], strlen($page->url['base']));
                continue;
            }
            $ext = strtolower($url['ext']);
            $file = $url['file'];
            if (isset($url['query']) && in_array($ext, array('jpeg', 'jpg', 'gif', 'png'))) {
                $file .= $url['query'];
            }
            $files = array();
            $files[$file] = pathinfo($url['file'], PATHINFO_FILENAME);
            if (isset($url['frag'])) {
                $frag = explode('#', $url['frag']);
                if ($ext == 'js' || $ext == 'css') {
                    $base = phpUri::parse($page->dir[$dir].$file);
                    foreach ($frag as $file) {
                        $file = $base->join($file);
                        $info = pathinfo($file);
                        if (is_file($file) && $info['extension'] == $ext) {
                            $file = substr($file, strlen($page->dir[$dir]));
                            $files[$file] = $info['filename'];
                        }
                    }
                } else {
                    $files[$file] = pathinfo(array_shift($frag), PATHINFO_FILENAME);
                }
            }
            foreach ($files as $file => $name) {
                $cache[$dir][$file] = array();
            }
            $assets[$url[0]] = array(
                'dir' => $dir,
                'file' => array_keys($files),
                'name' => implode('-', $files),
                'ext' => '.'.$ext,
            );
        }
        $asset->paths($cache);
        $asset->closeDatabase();
        $rnr = array();
        $base = strlen($page->url['base']);
        
        if ($array) {
            print_r([$matches, $page->dir, static::$not_found, $cache, $assets]);
        }
        
        foreach ($assets as $match => $url) {
            $cached = array();
            foreach ($url['file'] as $file) {
                $cached[] = $cache[$url['dir']][$file];
            }
            $cached = implode(0, $cached);
            if (!is_numeric($url['name'])) {
                $cached .= '/'.$url['name'];
            }
            $cached .= $url['ext'];
            $rnr[$page->url['base'].$cached] = $match; // replace => remove
            static::$urls[substr($match, $base)] = $cached;
        }
        ksort(static::$urls);
        uasort($rnr, function($a, $b) { // ORDER BY strlen(remove) DESC so we don't step on any toes
            return mb_strlen($b) - mb_strlen($a);
        });
        $rnr = array_flip($rnr); // remove => replace
        return str_replace(array_keys($rnr), array_values($rnr), $array ? $array : $html);
    }

    public static function mime($type)
    {
        $mime = null;
        $single = (is_array($type)) ? false : true;
        if (is_array($type)) {
            $type = array_shift($type);
        }
        switch (strtolower($type)) {
            case 'html':
                $mime = array('text/html', 'application/xhtml+xml', 'text/plain');
                break;
            case 'txt':
                $mime = array('text/plain');
                break;
            case 'less':
                $mime = array('text/x-less', 'text/css', 'text/plain', 'application/octet-stream');
                break;
            case 'scss':
                $mime = array('text/css', 'text/plain', 'application/octet-stream');
                break;
            case 'json':
                $mime = array('application/json', 'application/x-json', 'text/json', 'text/plain');
                break;
            case 'xml':
                $mime = array('application/xml', 'application/x-xml', 'text/xml', 'text/plain');
                break;
            case 'rdf':
                $mime = array('application/rdf+xml');
                break;
            case 'rss':
                $mime = array('application/rss+xml');
                break;
            case 'atom':
                $mime = array('application/atom+xml');
                break;
            case 'jpeg':
            case 'jpg':
                $mime = array('image/jpeg', 'image/pjpeg');
                break;
            case 'gif':
                $mime = array('image/gif');
                break;
            case 'png':
                $mime = array('image/png',  'image/x-png');
                break;
            case 'ico':
                $mime = array('image/x-icon', 'image/vnd.microsoft.icon');
                break;
            case 'js':
                $mime = array('application/javascript', 'application/x-javascript', 'text/javascript', 'text/plain');
                break;
            case 'css':
                $mime = array('text/css', 'text/plain');
                break;
            case 'pdf':
                $mime = array('application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream');
                break;
            case 'ttf':
                $mime = array('application/font-sfnt', 'application/font-ttf', 'application/x-font-ttf', 'font/ttf', 'font/truetype', 'application/octet-stream');
                break;
            case 'otf':
                $mime = array('application/font-sfnt', 'application/font-otf', 'application/x-font-otf', 'font/opentype', 'application/octet-stream');
                break;
            case 'svg':
                $mime = array('image/svg+xml', 'application/xml', 'text/xml');
                break;
            case 'eot':
                $mime = array('application/vnd.ms-fontobject', 'application/octet-stream');
                break;
            case 'woff':
                $mime = array('application/font-woff', 'application/x-woff', 'application/x-font-woff', 'font/x-woff', 'application/octet-stream');
                break;
            case 'woff2':
                $mime = array('application/font-woff2', 'font/woff2', 'application/octet-stream');
                break;
            case 'swf':
                $mime = array('application/x-shockwave-flash');
                break;
            case 'tar':
                $mime = array('application/x-tar');
                break;
            case 'tgz':
                $mime = array('application/x-tar', 'application/x-gzip-compressed');
                break;
            case 'gz':
            case 'gzip':
                $mime = array('application/x-gzip');
                break;
            case 'zip':
                $mime = array('application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/s-compressed', 'multipart/x-zip');
                break;
            case 'csv':
                $mime = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
                break;
            case 'xl':
                $mime = array('application/excel');
                break;
            case 'xls':
                $mime = array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword');
                break;
            case 'xlsx':
                $mime = array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/msword', 'application/x-zip');
                break;
            case 'word':
                $mime = array('application/msword', 'application/octet-stream');
                break;
            case 'doc':
                $mime = array('application/msword', 'application/vnd.ms-office');
                break;
            case 'docx':
                $mime = array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip');
                break;
            case 'ppt':
                $mime = array('application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword');
                break;
            case 'pptx':
                $mime = array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip');
                break;
            case 'psd':
                $mime = array('application/x-photoshop', 'image/vnd.adobe.photoshop');
                break;
            case 'ogg':
                $mime = array('audio/ogg');
                break;
            case 'wav':
                $mime = array('audio/x-wav', 'audio/wave', 'audio/wav');
                break;
            case 'mp3':
                $mime = array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3');
                break;
            case 'mp4':
                $mime = array('video/mp4');
                break;
            case 'mpe':
            case 'mpeg':
            case 'mpg':
                $mime = array('video/mpeg');
                break;
            case 'mov':
            case 'qt':
                $mime = array('video/quicktime');
                break;
        }

        return ($mime && $single) ? array_shift($mime) : $mime;
    }

    private function css($file, $row)
    {
        $page = Page::html();
        $css = file_get_contents($file);
        if (substr($css, 0, 3) == "\xef\xbb\xbf") {
            $css = substr($css, 3); // strip BOM, if any
        }
        $matches = array();
        foreach (array(
            '/url\(\s*(?P<quotes>["\'])?(?P<path>(?!(\s?["\']?(data:|https?:|\/\/))).+?)(?(quotes)(?P=quotes))\s*\)/ix', // url(xxx)
            '/@import\s+(?P<quotes>["\'])(?P<path>(?!(["\']?(data:|https?:|\/\/))).+?)(?P=quotes)/ix', // @import "xxx"
        ) as $regex) {
            if (preg_match_all($regex, $css, $match, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $match);
            }
        }
        $rnr = array();
        $base = phpUri::parse($row['file']);
        $common = dirname($row['file']) . '/';
        foreach ($matches as $match) {
            if (preg_match('/(?P<file>[^#\?]*)(?P<extra>.*)/', ltrim($match['path'], '/'), $path)) {
                if (static::mime(pathinfo($path['file'], PATHINFO_EXTENSION))) {
                    $file = $base->join($path['file']).$path['extra'];
                    if ($dir = $page->commonDir(array($common, $file))) {
                        $common = $dir;
                        if (strpos($match[0], '@import') === 0) {
                            $rnr[$match[0]] = '@import "'.$file.'"';
                        } else {
                            $rnr[$match[0]] = 'url("'.$file.'")';
                        }
                    }
                }
            }
        }
        if (!empty($rnr)) {
            $page->dir('set', 'css-dir', $common);
            $url = $page->url['base'].'css-dir/';
            foreach ($rnr as $remove => $replace) {
                $rnr[$remove] = str_replace($common, $url, $replace);
            }
            $css = static::urls(str_replace(array_keys($rnr), array_values($rnr), $css));
        }
        
        return $css;
    }

    private function paths(&$cache)
    {
        $page = Page::html();
        $count = 0;
        foreach ($cache as $dir => $files) {
            $count += count($files);
        }
        $ids = $this->ids($count);
        $insert = array();
        $update = array();
        $stmt = $this->db->prepare(array(
            'SELECT f.id AS file_id, f.updated, p.tiny, p.id AS path_id',
            'FROM files AS f INNER JOIN paths AS p ON f.id = p.file_id',
            'WHERE f.file = ? AND f.query = ?',
        ), 'assoc');
        foreach ($cache as $dir => $files) {
            foreach ($files as $path => $tiny) {
                list($file, $query) = explode('?', $path.'?');
                $file = $page->dir[$dir].$file;
                $updated = filemtime($file);
                $this->db->execute($stmt, array($file, $query));
                if ($row = $this->db->fetch($stmt)) {
                    if ($row['updated'] == $updated) {
                        $tiny = $row['tiny'];
                    } else {
                        list($path_id, $tiny) = each($ids);
                        $update[$row['file_id']] = array($path_id, $updated);
                    }
                } else {
                    list($path_id, $tiny) = each($ids);
                    $insert[] = array($path_id, $file, $query, $updated);
                }
                $cache[$dir][$path] = $tiny;
            }
        }
        $this->db->close($stmt);
        if (empty($insert) && empty($update)) {
            return;
        }
        $this->db->exec('BEGIN IMMEDIATE');
        $paths = array();
        if (!empty($insert)) {
            $stmt = $this->db->insert('files', array('path_id', 'file', 'query', 'updated'));
            foreach ($insert as $array) {
                $paths[$array[0]] = $this->db->insert($stmt, $array);
            }
            $this->db->close($stmt);
        }
        if (!empty($update)) {
            $stmt = $this->db->update('files', 'id', array('path_id', 'updated'));
            foreach ($update as $file_id => $array) {
                $this->db->update($stmt, $file_id, $array);
                $paths[$array[0]] = $file_id;
            }
            $this->db->close($stmt);
        }
        if (!empty($paths)) {
            $stmt = $this->db->update('paths', 'id', array('file_id'));
            foreach ($paths as $path_id => $file_id) {
                $this->db->update($stmt, $path_id, array($file_id));
            }
            $this->db->close($stmt);
        }
        $this->db->exec('COMMIT');

        return;
    }

    private function ids($count)
    {
        $ids = array();
        if ($stmt = $this->db->query(array(
            'SELECT id, tiny',
            'FROM paths',
            'WHERE file_id = ?',
            'ORDER BY id DESC LIMIT '.$count,
        ), 0, 'row')) {
            while (list($id, $tiny) = $this->db->fetch($stmt)) {
                $ids[$id] = $tiny;
            }
            $this->db->close($stmt);
        }
        if (count($ids) == $count) {
            return $ids;
        }
        $this->db->exec('BEGIN IMMEDIATE');
        $stmt = $this->db->insert('OR IGNORE INTO paths', array('tiny'));
        $string = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $count + 100; ++$i) {
            $tiny_id = ''; // 60 (characters) ^ 5 (length) gives 777,600,000 possible combinations
            while (strlen($tiny_id) < 5) {
                $tiny_id .= $string[mt_rand(0, 60)];
            }
            $this->db->insert($stmt, array($tiny_id));
        }
        $this->db->close($stmt);
        $this->db->exec('COMMIT');

        return $this->ids($count);
    }

    private function openDatabase()
    {
        if (is_null($this->db)) {
            $this->db = new SQLite($this->cached.'Assets.db');
            if ($this->db->created) {
                $this->db->create('paths', array(
                    'id' => 'INTEGER PRIMARY KEY',
                    'tiny' => 'TEXT UNIQUE NOT NULL DEFAULT ""',
                    'file_id' => 'INTEGER NOT NULL DEFAULT 0',
                ));
                $this->db->create('files', array(
                    'id' => 'INTEGER PRIMARY KEY',
                    'path_id' => 'INTEGER NOT NULL DEFAULT 0',
                    'file' => 'TEXT NOT NULL DEFAULT ""',
                    'query' => 'TEXT NOT NULL DEFAULT ""',
                    'updated' => 'INTEGER NOT NULL DEFAULT 0',
                ), array('unique' => 'file, query'));
            }
        }
    }

    private function closeDatabase()
    {
        if (!is_null($this->db)) {
            $this->db->connection()->close();
        }
        $this->db = null;
    }

    private function __construct()
    {
    }
}
