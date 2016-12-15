<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Files;
use BootPress\Admin\Component as Admin;
use phpUri;

class Code
{
    public static function setup($auth, $path)
    {
        return ($auth->isAdmin(1)) ? Admin::$bp->icon('code', 'fa').' Code' : false;
    }

    public static function page()
    {
        extract(Admin::params('bp', 'page'));
        $html = '';

        // The folder we are currently viewing
        $folder = $page->dir();
        if ($dir = $page->get('dir')) {
            $relative = phpUri::parse($page->dir())->join($dir);
            if (is_dir($relative)) {
                $folder = rtrim($relative, '/').'/';
            }
        }

        // Files contained therein
        $media = Files::view($folder, array('files' => 'yml|twig|js|css|less|scss|php'));
        if ($page->get('image')) {
            return Admin::box('default', array(
                'head with-border' => $bp->icon('image', 'fa').' Image',
                'body' => $media,
            ));
        }

        // Breadcrumb links
        $links = array();
        $relative = array(); // dot **../** syntax
        $base = explode('/', rtrim($page->dir(), '/'));
        foreach ($base as $length => $path) {
            $key = $length + 1;
            $relative[str_repeat('../', count($base) - $key)] = implode('/', array_slice($base, 0, $key));
        }
        $folders = array();
        $search = true;
        $url = $page->url('delete', '', '?');
        foreach (explode('/', rtrim($folder, '/')) as $name) {
            $folders[] = $name;
            if ($search && (false !== $dots = array_search(implode('/', $folders), $relative))) {
                if ($dots == '') {
                    $links['<b>'.$name.'</b>'] = $url;
                } else {
                    $links[$name] = $url.'?dir='.$dots;
                }
                $previous = $dots;
            } else {
                if ($search) {
                    $search = false;
                }
                $previous .= $name.'/';
                $links[$name] = $url.'?dir='.trim($previous, '/');
            }
        }
        $pulldown = array();
        list($dirs) = Files::iterate($folder);
        foreach ($dirs as $name) {
            $pulldown[$name] = $url.'?dir='.trim($previous.$name, '/');
        }
        if (!empty($pulldown)) {
            $links[''] = $pulldown;
        }
        $links[] = '';
        $html .= $bp->breadcrumbs($links);

        // Create a new folder
        $form = $bp->form('admin_code_folders');
        $form->validator->set('folder', 'required');
        if ($vars = $form->validator->certified()) {
            $new = Files::format($vars['folder'], false, 'capitals');
            if (is_dir($folder.$new)) {
                $form->validator->errors['folder'] = 'The "'.$new.'" folder already exists.';
            } else {
                mkdir($folder.$new, 0755, true);
                $page->eject($page->url('add', $form->eject, 'dir', trim($previous.$new, '/')));
            }
        }
        $html .= $form->header();
        $html .= $form->field(array('Folder',
            'Enter the name of the new folder you would like to create.',
        ), $form->group('', $bp->button('primary', 'Submit', array('type' => 'submit', 'data-loading-text' => 'Submitting...')), $form->text('folder')));
        $html .= $form->close();

        return Admin::box('default', array(
            'head with-border' => $bp->icon('code', 'fa').' Code',
            'body' => $html.$media,
        ));
    }
}
