<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Files;
use BootPress\Admin\Component as Admin;
use BootPress\Upload\Component as Upload;

class Folders
{
    
    public static function setup($auth, $path)
    {
        return ($auth->isAdmin(1)) ? Admin::$bp->icon('folder', 'fa').' Folders' : false;
    }
    
    public static function page()
    {
        extract(Admin::params('bp', 'page'));
        $html = '';
        $media = '';
        $dir = $page->dir('folders');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $form = $bp->form('admin_folders');
        if ($edit = $page->get('edit')) {
            if (!is_dir($dir.$edit)) {
                $page->eject($page->url('delete', '', '?'));
            }
        }
        if ($page->get('delete')) {
            if ($edit) {
                list($dirs, $files) = Files::iterate($dir.$edit);
                foreach ($files as $file) {
                    unlink($dir.$edit.$file);
                }
                if (empty($dirs)) {
                    rmdir($dir.$edit);
                }
            }
            $page->eject($page->url('delete', '', '?'));
        }
        if ($edit) {
            $form->values['path'] = $page->get('folder');
            $index = Files::textarea($form, 'index', $dir.$edit.'/index.php');
            $media = Files::view($dir.$edit, array('exclude'=>'index.php'));
            if ($page->get('image')) {
                return Admin::box('default', array(
                    'head with-border' => $bp->icon('image', 'fa').' Image',
                    'body' => $media,
                ));
            }
        }
        $folders = array();
        list($dirs) = Files::iterate($dir, 'recursive');
        foreach ($dirs as $folder) {
            if (is_file($dir.$folder.'/index.php')) {
                $folders[$folder] = $folder;
            }
        }
        if (!empty($folders)) {
            $form->menu('edit', $folders, $edit ? null : '&nbsp;');
            if ($edit) {
                $form->values['folder'] = $edit;
                $form->values['edit'] = $edit;
            }
        }
        $form->validator->set(array(
            'folder' => 'required',
            'edit' => '',
        ));
        if ($vars = $form->validator->certified()) {
            $folder = Files::format($vars['folder'], 'slashes');
            if (!empty($folder)) {
                if ($edit) { // renaming
                    if ($edit != $folder) {
                        if (is_file($dir.$folder.'/index.php')) {
                            $form->validator->errors['folder'] = 'Sorry, this folder has already been taken.';
                        } else {
                            $path = $dir.$edit.'/';
                            $rename = $dir.$folder.'/';
                            if (!is_dir($rename)) {
                                mkdir($rename, 0755, true);
                            }
                            list($dirs, $files) = Files::iterate($path);
                            foreach ($files as $file) {
                                rename($path.$file, $rename.$file);
                            }
                            if (empty($dirs) && strpos($rename, $path) === false) {
                                rmdir($path);
                            }
                            $form->eject = $page->url('add', $form->eject, 'edit', $folder);
                        }
                    }
                } else { // creating
                    if (!is_dir($dir.$folder)) {
                        mkdir($dir.$folder, 0755, true);
                    }
                    if (!is_file($dir.$folder.'/index.php')) {
                        file_put_contents($dir.$folder.'/index.php', '');
                    }
                    $form->eject = $page->url('add', $form->eject, 'edit', $folder);
                }
            }
            if (empty($form->validator->errors)) {
                $page->eject($form->eject);
            }
        }
        
        // Open Form
        $html .= $form->header();
        
        // Link to Folder
        if ($edit) {
            $delete = $bp->button('sm danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this folder', 'style'=>'margin-left:20px;'));
            $html .= '<p class="lead"><a href="'.$page->url('base', $edit).'" target="_blank">'.$page->url('base', $edit).' '.$bp->icon('new-window').'</a> '.$delete.'</p><br>';
            $page->jquery('
                $(".delete").click(function(){
                    bootbox.confirm({
                        size: "large",
                        backdrop: false,
                        message: "Are you sure you would like to delete this folder?",
                        callback: function (result) {
                            if (result) {
                                window.location = "'.str_replace('&amp;', '&', $page->url('add', '', 'delete', 'folder')).'";
                            }
                        }
                    });
                });
            ');
        }
        
        // Select a Folder to 'edit'
        if (!empty($folders)) {
            $html .= $form->field(array(($edit ? 'Folder' : 'Select'),
                'Select a folder that you would like to edit.',
            ), $form->select('edit'));
        }
        
        // Edit or Create a 'folder'
        $prepend = $page->url('base');
        $append = array();
        if (!empty($page->url['suffix'])) {
            $append[] = $page->url['suffix'];
        }
        $append[] = $bp->button('primary', 'Submit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'));
        if ($edit) {
            $html .= $form->field(array('Save As',
                'Use only lowercase letters, dashes (-), and slashes (/).',
            ), $form->group($prepend, $append, $form->text('folder')));
        } else {
            $html .= $form->field(array('Create',
                'Use only lowercase letters, dashes (-), and slashes (/).  The folder you create here will be directly accessible at: '.$page->url('base').'[folder]/...  You, of course, will have to deal with the dot dot dot\'s.  Alternatively, you can create any url rule structure that you like in the .htaccess file, and direct it to the main index.php file with the additional parameter: ?page=[folder]',
            ), $form->group($prepend, $append, $form->text('folder')));
        }
        
        // 'index.php' wyciwyg
        if ($edit) {
            $html .= $form->field(array('index.php', 
                'This is the main file where you can manage the content of your folder.',
            ), $index);
        }
        
        // Close Form
        $html .= $form->close();
        
        // jQuery
        $page->jquery('
            $("#'.$form->validator->id('edit').'").change(function(){
                window.location = "'.$page->url('delete', '', '?').'?edit=" + $(this).val();
            });
        ');
        
        return Admin::box('default', array(
            'head with-border' => array(
                $bp->icon('folder', 'fa').' Folders',
                $bp->button('md link', 'Documentation '.$bp->icon('new-window'), array(
                    'href' => 'https://www.bootpress.org/docs/folders/',
                    'target' => '_blank',
                )),
            ),
            'body' => $html.$media,
        ));
    }
}
