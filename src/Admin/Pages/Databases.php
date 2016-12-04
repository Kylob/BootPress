<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Files;
use BootPress\Admin\Component as Admin;
use BootPress\Asset\Component as Asset;
use BootPress\Page\Component as Page;
use Symfony\Component\Yaml\Yaml;

class Databases
{
    
    public static function setup($auth, $path)
    {
        return ($auth->isAdmin(1)) ? Admin::$bp->icon('database', 'fa').' Databases' : false;
    }
    
    public static function page()
    {
        $page = Page::html();
        $get = $page->request->query->all();
        if ($page->get('adminer') == 'css') {
            $file = __DIR__.'/databases/adminer.css';
            if (is_file($file)) {
                $page->send(Asset::dispatch($file, 86400));
            } else {
                $page->send(Asset::dispatch('css', ''));
            }
        }
        if (isset($get['file']) || (isset($get['db']) && (isset($get['mssql']) || isset($get['server']) || isset($get['sqlite']) || isset($get['oracle']) || isset($get['pgsql'])))) {
            include 'databases/adminer_object.php';
            include 'databases/adminer-4.2.5-en.php';
            exit;
        }
        $databases = $page->file('databases.yml');
        $yaml = (is_file($databases)) ? Yaml::parse(file_get_contents($databases)) : array();
        $display = array();
        $url = $page->url('delete', '', '?');
        foreach ($yaml as $driver => $config) {
            $params = array('db'=>$driver);
            if (isset($config['username'])) {
                $params['username'] = $config['username'];
            }
            if (isset($config['dsn']) && $driver = strstr($config['dsn'], ':', true)) {
                switch ($driver) {
                    case 'mysql': 
                        $group = 'MySQL';
                        $adminer = 'server';
                        break;
                    case 'pgsql':
                        $group = 'PostgreSQL';
                        $adminer = 'pgsql';
                        break;
                    case 'oci':
                        $group = 'Oracle';
                        $adminer = 'oracle';
                        break;
                    case 'mssql':
                        $group = 'MS SQL';
                        $adminer = 'mssql';
                        break;
                    default:
                        $adminer = false;
                        break;
                }
                if ($adminer) {
                    if (preg_match('/host=([a-z0-9._\-]+);?/i', $config['dsn'], $matches)) {
                        $params[$adminer] = $matches[1];
                    }
                    $link = '<a href="' . $page->url('add', $url, $params) . '">' . $params['db'] . '</a>';
                    if (isset($config['password']) && !empty($config['password'])) {
                        $link .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $config['password'];
                    }
                    $display[$group][] = $link;
                }
            }
        }
        if (isset($yaml['sqlite']) && is_array($yaml['sqlite'])) {
            $files = array_unique(str_replace('\\', '/', $yaml['sqlite']));
            sort($files);
            $base = $page->commonDir(array_merge(array($page->dir()), $files));
            foreach ($files as $num => $file) {
                if (is_file($file)) {
                    $link = $page->url('add', $url, array('sqlite'=>'', 'db'=>$file, 'username'=>'adminer'));
                    $display['SQLite'][] = '.../<a href="'.$link.'">'.str_replace($base, '', $file).'</a>';
                } else {
                    unset($files[$num]);
                }
            }
            if (count($yaml['sqlite']) > count($files)) {
                    ksort($yaml);
                    $yaml['sqlite'] = array_values($files);
                    $yaml = Yaml::dump($yaml, 3);
                    file_put_contents($databases, $yaml);
            }
        }
        ksort($display);
        $html = '';
        foreach ($display as $driver => $database) {
            $html .= Admin::$bp->lister('dl dl-horizontal', array($driver=>$database));
        }
        
        return Admin::box('default', array(
            'head with-border' => Admin::$bp->icon('database', 'fa') . ' Databases',
            'body' => $html,
        ));
    }
}
