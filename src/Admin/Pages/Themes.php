<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Files;
use BootPress\Admin\Component as Admin;
use BootPress\Upload\Component as Upload;
use BootPress\Unzip\Component as Unzip;

class Themes
{
    public static function setup($auth, $path)
    {
        return ($auth->isAdmin(2)) ? Admin::$bp->icon('desktop', 'fa').' Themes' : false;
    }

    public static function page()
    {
        extract(Admin::params('page', 'blog', 'bp'));
        if ($edit = $page->get('edit')) {
            // enforce only one folder path ie. no subfolders
            $filtered = Files::format($edit, false, 'capitals');
            if ($edit != $filtered) {
                $page->eject($page->url('add', '', 'edit', $filtered));
            } elseif (is_dir($blog->folder.'themes/'.$edit)) {
                $edit = $page->dir($blog->folder, 'themes', $edit);
                $media = Files::view($edit, array(
                    'exclude' => 'index.html.twig',
                    'files' => 'yml|twig|js|css|less|scss',
                    'resources' => 'ttf|otf|svg|eot|woff|woff2|swf',
                ), 'recursive');
            } else {
                $page->eject($page->url('delete', '', 'edit'));
            }
        } else {
            $media = '';
        }
        if ($page->get('image')) {
            return Admin::box('default', array(
                'head with-border' => $bp->icon('image', 'fa').' Image',
                'body' => $media,
            ));
        }
        $form = ($edit) ? self::theme($edit) : self::create();
        if ($page->get('delete')) {
            $page->eject($page->url('delete', '', 'delete'));
        }

        return Admin::box('default', array(
            'head with-border' => array(
                $bp->icon('desktop', 'fa').' Themes',
                $bp->button('md link', 'Documentation '.$bp->icon('new-window'), array(
                    'href' => 'https://www.bootpress.org/docs/themes/',
                    'target' => '_blank',
                )),
            ),
            'body' => $form.$media,
        ));
    }

    /**
     * @return string A form for creating a new theme
     */
    private static function create()
    {
        $html = '';
        extract(Admin::params('bp', 'page', 'blog'));
        $form = $bp->form('admin_theme_create');
        $upload = Upload::bootstrap($form)->file('upload', array(
            'size' => '20M',
            'types' => 'zip',
        ));
        $form->validator->set('create', 'required');
        if ($vars = $form->validator->certified()) {
            $save = Files::format($vars['create'], false, 'capitals');
            if (is_dir($blog->folder.'themes/'.$save)) {
                $form->message('info', 'The "'.$save.'" theme already exists.  We didn\'t do anything.');
            } else {
                $dir = $blog->folder.'themes/'.$save.'/';
                mkdir($dir, 0755, true);
                if (!empty($vars['upload']) && is_file(Upload::$folder.$vars['upload'])) {
                    $zip = new Unzip(Upload::$folder.$vars['upload'], $dir);
                    $zip->extract('yml|twig|js|css|less|scss|ttf|otf|svg|eot|woff|woff2|swf|jpg|jpeg|gif|png|ico', 'remove_common_dir');
                    $zip->close();
                    unset($zip);
                    unlink(Upload::$folder.$vars['upload']);
                }
                if (!is_file($dir.'index.html.twig')) {
                    file_put_contents($dir.'index.html.twig', '');
                }
                $form->eject = $page->url('add', $form->eject, 'edit', $save);
            }
            $page->eject($form->eject);
        }

        return implode('', array(
            $form->header(),
            self::select($form),
            $form->field(array('Create',
                'Enter the name of the <b>new</b> theme you would like to create.  If the theme already exists, then nothing will happen.',
            ), $form->text('create')),
            $form->field(array('Upload',
                'Submit a zipped file to extract for your theme.',
            ), $upload),
            $form->submit(),
            $form->close(),
        ));
    }

    /**
     * @return string A form for editing an existing theme
     */
    private static function theme($folder)
    {
        extract(Admin::params('bp', 'page', 'blog'));
        if ($page->get('delete') == 'theme') {
            list($dirs, $files) = Files::iterate($folder, 'recursive');
            arsort($dirs);
            foreach ($files as $file) {
                unlink($folder.$file);
            }
            foreach ($dirs as $dir) {
                rmdir($folder.$dir);
            }
            rmdir($folder);
            $page->eject($page->url('delete', '', 'delete'));
        }
        if ($preview = $page->post('preview') && $page->request->isXmlHttpRequest()) {
            if ($preview == 'true') {
                $page->session->set('preview_layout', $page->get('edit'));
            } else { // $preview == 'false'
                $page->session->remove('preview_layout');
            }
            exit;
        }
        $form = $bp->form('admin_theme_manage');
        $index = Files::textarea($form, 'index', $folder.'index.html.twig');
        $config = Files::textarea($form, 'config', $folder.'config.yml');
        $form->values['preview'] = $page->session->get('preview_layout') ? 'Y' : 'N';
        $form->values['action'] = 'copy';
        $form->menu('preview', array('Y' => 'Preview the selected theme'));
        $form->menu('action', array(
              'copy' => '<b>Copy</b> will make a duplicate of this theme if it does not already exist',
              'rename' => '<b>Rename</b> will change the name of this theme as long as it does not already exist',
              'swap' => '<b>Swap</b> will exchange this theme with the one you want to save as long as it actually exists',
        ));
        $form->validator->set(array(
            'preview' => 'yesNo',
            'save' => 'required',
            'action' => 'required|inList[copy,rename,swap]',
            'setup' => '',
        ));
        if ($vars = $form->validator->certified()) {
            $save = Files::format($vars['save'], false, 'capitals');
            if (!empty($save)) {
                $exists = (is_dir($blog->folder.'themes/'.$save)) ? true : false;
                switch ($vars['action']) {
                    case 'copy':
                        if (!$exists) {
                            mkdir($blog->folder.'themes/'.$save, 0755, true);
                            list($dirs, $files) = Files::iterate($folder, 'recursive');
                            foreach ($dirs as $dir) {
                                mkdir($blog->folder.'themes/'.$save.'/'.$dir, 0755, true);
                            }
                            foreach ($files as $file) {
                                copy($folder.$file, $blog->folder.'themes/'.$save.'/'.$file);
                            }
                            $form->eject = $page->url('add', $form->eject, 'edit', $save);
                        } else {
                            $form->message('info', 'The theme name ("'.$save.'") that you are trying to <b>Save As</b> a <b>Copy</b> already exists.');
                        }
                        break;
                    case 'rename':
                        if (!$exists) {
                            rename($folder, $blog->folder.'themes/'.$save); // current theme to other
                            $form->eject = $page->url('add', $form->eject, 'edit', $save);
                        } else {
                            $form->message('info', 'You cannot <b>Rename</b> and <b>Save As</b> a theme ("'.$save.'") that already exists.');
                        }
                        break;
                    case 'swap':
                        if ($exists) {
                            $temp = $blog->folder.'themes/'.md5($folder).microtime();
                            rename($folder, $temp); // current theme to temp
                            rename($blog->folder.'themes/'.$save, $folder); // other theme to current
                            rename($temp, $blog->folder.'themes/'.$save); // temp theme to other
                            $form->eject = $page->url('add', $form->eject, 'edit', $save);
                        } else {
                            $form->message('info', 'The <b>Save As</b> theme name ("'.$save.'") you are <b>Swap</b>ping with does not exist.');
                        }
                        break;
                }
            }
            $page->eject($form->eject);
        }
        $page->jquery('
            $("input[name=preview]").change(function(){
                var checked = $(this).is(":checked") ? "true" : "false";
                $.post(location.href, {preview:checked});
            });
            $(".delete").click(function(){
                bootbox.confirm({
                    size: "large",
                    backdrop: false,
                    message: "Are you sure you would like to delete this theme?",
                    callback: function (result) {
                        if (result) {
                            window.location = "'.str_replace('&amp;', '&', $page->url('add', '', 'delete', 'theme')).'";
                        }
                    }
                });
            });
        ');
        $preview = str_replace('class="checkbox"', 'class="checkbox pull-left"', $form->checkbox('preview'));
        $preview .= $bp->button('danger delete pull-right', $bp->icon('trash'), array('title' => 'Click to delete this theme'));

        return implode('', array(
            $form->header(),
            $form->field('', $preview),
            self::select($form),
            $form->field(array('Save As',
                'Enter the name of the theme for which you would like to Copy, Rename, or Swap.',
            ), $form->text('save')),
            $form->field('', $form->radio('action')),
            $form->submit(),
            $form->field(array('config.yml',
                'This file establishes any local vars within the theme.',
            ), $config),
            $form->field(array('index.html.twig',
                'This is the main theme file that receives the {$content} of your page.',
            ), $index),
            $form->close(),
        ));
    }

    /**
     * Creates a select field for editing themes.
     *
     * @param object $form A Bootstrap form object
     *
     * @return string
     */
    private static function select($form)
    {
        $html = '';
        extract(Admin::params('page', 'blog'));
        $themes = array();
        list($dirs) = Files::iterate($blog->folder.'themes/');
        foreach ($dirs as $theme) {
            $themes[$page->url('add', '', 'edit', $theme)] = $theme;
        }
        $form->menu('themes', $themes, '&nbsp;'); // the select options
        $form->values['themes'] = $page->url(); // preselect the currently selected theme if any
        $page->jquery('$("#'.$form->validator->id('themes').'").change(function(){ window.location = $(this).val(); });');

        return $form->field(array(
            $page->get('edit') ? 'Edit' : 'Select',
            'Select the theme you would like to edit.',
        ), $form->select('themes'));
    }
}
