<?php

namespace BootPress\Admin;

use BootPress\Admin\Component as Admin;
use BootPress\Upload\Component as Upload;
use BootPress\Unzip\Component as Unzip;
use BootPress\Asset\Component as Asset;
use BootPress\Page\Component as Page;
use ZipStream; // maennchen/zipstream-php
use Intervention\Image\ImageManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Files
{
    /**
     * Iterates over the $path and returns an array of all the directories and files contained within it.
     *
     * @param string      $path      Where to begin.  If it has a trailing slash, then all dirs and files will not have a leading slash.  If it does not have a trailing slash, the all dirs and files will have a leading slash
     * @param false|mixed $recursive If anything but false then parent directories and files will also be returned
     * @param string      $types     A pipe delimited list of acceptable file extensions eg. php|txt|yml
     *
     * @return array Dirs first, and files second.
     *
     * ```php
     * $path = $blog->folder.'content/index';
     * list($dirs, $files) = Files::iterate($path);
     * foreach ($files as $file) unlink($path.$file);
     * if (empty($dirs)) rmdir($path);
     * ```
     */
    public static function iterate($path, $recursive = false, $types = null)
    {
        $dirs = $files = array();
        if (is_dir($path)) {
            $cut = strlen($path);
            $regex = ($types) ? '/^.+\.('.$types.')$/i' : false;
            $dir = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
            if ($recursive) {
                $dir = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD);
                if (is_int($recursive)) {
                    $dir->setMaxDepth($recursive);
                }
            }
            foreach ($dir as $file) {
                $path = str_replace('\\', '/', substr($file->getRealpath(), $cut));
                if ($file->isDir()) {
                    if (iterator_count($dir->getChildren()) === 0) {
                        rmdir($file->getRealpath()); // might as well do some garbage collection while we are at it
                    } else {
                        $dirs[] = $path;
                    }
                } elseif ($types !== false) {
                    if ($regex) {
                        if (preg_match($regex, $file->getFilename())) {
                            $files[] = $path;
                        }
                    } else {
                        $files[] = $path;
                    }
                }
            }
        }

        return array($dirs, $files);
    }

    /**
     * Makes a folder's $path pretty with no screwed up characters, doubled up punctuation, or useless dots, dashes, and slashes.
     *
     * @param string      $path     The name of the file
     * @param false|mixed $slashes  Whether to allow slashes (directories) in the $file name (anything but false), or not
     * @param false|mixed $capitals Whether to allow capitals in the $file name (anything but false), or not
     *
     * @return string The filtered $file name
     */
    public static function format($path, $slashes = false, $capitals = false)
    {
        if ($capitals === false) {
            $path = strtolower($path);
        }
        $path = str_replace(array('\\', '_'), array('/', '-'), $path);
        $path = preg_replace('/[^0-9a-z.\-\/]/i', '-', $path); // alphanumeric . - /
        $path = preg_replace('/[.\-\/](?=[.\-\/])/', '', $path); // no doubled up punctuation
        $path = trim($path, '.-/'); // no trailing (or preceding) dots, dashes, and slashes
        if (is_int($slashes) && $slashes > 0) {
            $path = explode('/', $path);
            $parts = implode('/', array_slice($path, 0, $slashes));
            if (count($path) > $slashes) {
                $parts .= '-'.implode('-', array_slice($path, $slashes));
            }
            $path = $parts;
        } elseif ($slashes === false) {
            $path = str_replace('/', '-', $path);
        }

        return $path;
    }

    /**
     * Formats bytes to a human readable string.
     *
     * @param int $size
     * @param int $precision
     *
     * @return string
     */
    private static function bytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'kB', 'MB', 'GB', 'TB');

        return ($size > 0) ? round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)] : '0 B';
    }

    /**
     * Creates a textarea $field for your $form that incorporates the wyciwyg to edit your $file, and will delete the file if saved as an empty string.
     *
     * @param object       $form  The form you are working with
     * @param string       $field The name of the textarea
     * @param string|array $files If this is an array, then the first one is the main file, and the others are backup plans that we glean the contents from if it exists
     *
     * @return string A wyciwyg textarea
     */
    public static function textarea($form, $field, $files)
    {
        extract(Admin::params('page'));
        foreach ((array) $files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $form->values[$field] = file_get_contents($file);
            break;
        }
        $form->validator->set($field, '');
        $file = (is_array($files)) ? array_shift($files) : $files;
        self::save(array($field => $file), array($field));
        $path = pathinfo($file);

        return $form->textarea($field, array(
            'rows' => 8,
            'data-file' => $path['basename'],
            'class' => 'wyciwyg input-sm '.$path['extension'],
        ));
    }

    /**
     * Creates a directory listing of files with links to edit, and a form to add more.
     *
     * @param string      $dir        The directory whose $files we want to manage
     * @param array       $extensions An array of file types to manage in the $dir, grouped by '**files**', '**images**', and '**resources**' array keys.  You can '**exclude**' an array of files, and if you want to unzip uploaded zip files, then put '**unzip**' ``in_array()``
     * @param false|mixed $recursive  Whether you want to limit files to this folder (false) or not (anything else), or (int) how many folders above the current you want to work with
     *
     * @return string
     *
     * @todo Unzip files in dir - actual code
     */
    public static function view($dir, array $extensions = array(), $recursive = false)
    {
        extract(Admin::params(array('bp', 'blog', 'auth', 'page', 'website')));
        $dir = rtrim($dir, '/').'/';

        // Setup the form
        $form = array_merge(array(
            'exclude' => array(), // the files we don't want to include at all
            'files' => ($auth->isAdmin(1) ? 'php|' : '').'ini|yml|twig|js|css|less|scss',
            'images' => 'jpg|jpeg|gif|png|ico',
            'resources' => 'pdf|ttf|otf|svg|eot|woff|woff2|swf|tar|gz|tgz|zip|csv|xl|xls|xlsx|word|doc|docx|ppt|ogg|wav|mp3|mp4|mpe|mpeg|mpg|mov|qt|psd',
        ), $extensions);
        $extensions = array();
        foreach (array('files', 'images', 'resources') as $type) {
            if (!empty($form[$type])) {
                $extensions[] = trim($form[$type], '|');
            }
        }
        $extensions = implode('|', $extensions);
        if (empty($extensions)) {
            return;
        }

        // Get the files
        list($dirs, $files) = self::iterate($dir, $recursive, $extensions);

        // Download (backup) all of the $files if using our link.
        if ($page->get('download') == 'files') {
            $zip = new ZipStream\ZipStream(implode('_', array(
                'backup',
                $blog->url($website),
                trim(str_replace(array($page->dir(), '/'), array('', '-'), $dir), '-'),
                date('Y-m-d'),
            )).'.zip');
            foreach ($files as $file) {
                $zip->addFileFromPath($file, $dir.$file, array('time' => filemtime($dir.$file)));
            }
            $zip->finish();
        }

        // Remove any excluded files
        if (!empty($form['exclude'])) {
            $files = array_diff($files, (array) $form['exclude']);
        }

        // Return if there are more than 200 files to manage.
        if (count($files) > 200) {
            return '<h4 class="text-center">Sorry, this directory has 200+ files which is more than we can reasonably manage here.</h4>';
        }

        // Delete file if called for
        if (($delete = $page->post('delete-file')) && in_array($delete, $files)) {
            if (is_file($dir.$delete) && unlink($dir.$delete)) {
                exit('success');
            }
            exit('error');
        }

        // Display image form if using one of our edit image links
        if ($image = $page->get('image')) {
            $eject = $page->url('delete', '', 'image');
            if (!in_array($image, $files)) {
                $page->eject($eject);
            }

            return self::image($dir.$image, $eject);
        }

        // Rename a file if it doesn't already exist.
        if ($oldname = $page->post('oldname')) {
            $type = $page->post('type');
            $newname = self::format($page->post('newname'), $recursive, ($type == '.php'));
            if (!empty($newname) && is_file($dir.$oldname.$type) && in_array($oldname.$type, $files)) {
                if (is_file($dir.$newname.$type)) {
                    return $page->sendJson(array('success' => false, 'msg' => 'This file already exists.'));
                } else {
                    if ($recursive && !is_dir(dirname($dir.$newname.$type))) {
                        mkdir(dirname($dir.$newname.$type), 0755, true);
                    }
                    rename($dir.$oldname.$type, $dir.$newname.$type);
                    if (($key = array_search($oldname.$type, $files)) !== false) {
                        $files[$key] = $newname.$type;
                    }
                    $data = array('success' => true, 'newValue' => $newname);
                }
            } else {
                $data = array('success' => true, 'newValue' => $oldname);
            }
        }

        // Save a file if it is being summoned.
        $save = array_flip(preg_grep('/\.(php|ini|yml|twig|js|css|less|scss)$/', $files));
        foreach ($save as $file => $uri) {
            $save[$file] = $dir.$file;
        }
        self::save($save);

        // Group the $files for displaying in chunks, and make each an ``array($link, $edit, $file, $size, $delete)``
        $images = $links = array();
        $url = str_replace($page->dir(), $page->path('dir'), $dir);
        sort($files);
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $view = $bp->button('xs link', 'view '.$bp->icon('new-window'), array('href' => $url.$file, 'target' => '_blank'));
            $rename = '<a class="rename-file text-nowrap" href="#" data-name=".'.$ext.'">'.substr($file, 0, -(strlen($ext) + 1)).'</a>.'.$ext;
            $size = (is_file($dir.$file)) ? str_replace('Bytes', 'bytes', self::bytes(filesize($dir.$file), 1)) : '0 bytes';
            $delete = '<a class="delete-file pull-right" href="#" data-uri="'.$file.'">'.$bp->icon('trash').'</a>';
            switch ($ext) {
                case 'php':
                case 'ini':
                case 'yml':
                case 'twig':
                    $view = '';
                    // We don't break here for the $edit button below.
                case 'css':
                case 'js':
                    $edit = $bp->button('xs warning wyciwyg '.$ext, $bp->icon('pencil').' edit', array(
                        'href' => '#',
                        'data-retrieve' => $file,
                        'data-file' => $file,
                    ));
                    $files[$ext][] = array($view, $edit, $rename, $size, $delete);
                    break;
                case 'less':
                case 'scss':
                    $edit = $bp->button('xs warning wyciwyg '.$ext, $bp->icon('pencil').' edit', array(
                        'href' => '#',
                        'data-retrieve' => $file,
                        'data-file' => $file,
                    ));
                    $files['css'][] = array('', $edit, $rename, $size, $delete);
                    break;
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                case 'ico':
                    if ($dimensions = getimagesize($dir.$file)) {
                        $view = '<a href="'.$url.$file.'" target="_blank"><img src="'.$url.$file.'#~50x50" class="img-responsive"></a>';
                        $edit = $bp->button('xs warning', $bp->icon('pencil').' edit', array('href' => $page->url('add', '', 'image', $file)));
                        $files['images'][] = array($view, $edit, $rename.'|'.$dimensions[0].'x'.$dimensions[1], $size, $delete);
                    }
                    break;
                default:
                    $files['links'][] = array($view, '', $rename, $size, $delete);
                    break;
            }
        }

        // Create the $files manager html.
        $html = '';
        if (!empty($files)) {
            $html .= $bp->table->open('class=table responsive striped condensed');
            foreach (array('php' => 'PHP', 'ini' => 'INI', 'yml' => 'YAML', 'twig' => 'TWIG', 'css' => 'CSS', 'js' => 'JavaScript', 'images' => 'Images', 'links' => 'Links') as $type => $name) {
                if (isset($files[$type])) {
                    $html .= $bp->table->head();
                    $html .= $bp->table->cell('colspan=6|style=padding-top:10px; padding-bottom:10px;', $name);
                    foreach ($files[$type] as $values) {
                        list($link, $edit, $file, $size, $delete) = $values;
                        $html .= $bp->table->row();
                        $html .= $bp->table->cell('class=col-sm-1', $link);
                        $html .= $bp->table->cell('class=col-sm-1', $edit);
                        if ($type == 'images') {
                            list($file, $dimensions) = explode('|', $file);
                            $html .= $bp->table->cell('class=text-nowrap', $file);
                            $html .= $bp->table->cell('style=width:100px; text-align:center;', $dimensions);
                        } else {
                            $html .= $bp->table->cell('colspan=2|class=text-nowrap', $file);
                        }
                        $html .= $bp->table->cell('style=width:100px; text-align:center;|class=text-nowrap', $size);
                        $html .= $bp->table->cell('style=width:30px;', $delete);
                    }
                }
            }
            $html .= $bp->table->close();
        }

        // Update the html when renaming a file.
        if ($oldname) {
            $data['html'] = Asset::urls($html);

            return $page->sendJson($data);
        }

        // Create a form to upload and create new files
        $files = $html; // They have all been converted over to something we can work with now.
        if ($form) {
            $html = '';
            $ext = $form;
            $form = $bp->form('admin_file_upload');
            if (!empty($ext['images']) && empty($ext['files']) && empty($ext['resourcees'])) {
                $upload = array(
                    Upload::bootstrap($form)->file('upload[]', array(
                        'size' => '10M',
                        'types' => $ext['images'],
                    )),
                    'Upload additional images that you would like to include.',
                );
            } else {
                $upload = array(
                    Upload::bootstrap($form)->file('upload[]', array(
                        'size' => '10M',
                        'types' => $extensions,
                    )),
                    'Upload additional files that you would like to include.',
                );
            }
            if (!empty($ext['files'])) {
                $form->validator->set('file');
            }
            if ($recursive) {
                $form->validator->set('directory');
            }
            if ($vars = $form->validator->certified()) {
                if (!empty($ext['files']) && !empty($vars['file'])) {
                    $file = self::format($vars['file'], $recursive, (substr($vars['file'], -4) == '.php'));
                    if (preg_match('/^.+\.('.$ext['files'].')$/', $file)) {
                        if (!is_dir(dirname($dir.$file))) {
                            mkdir(dirname($dir.$file), 0755, true);
                        }
                        file_put_contents($dir.$file, '');
                    }
                }
                if (!empty($vars['upload'])) {
                    $temp = ($recursive && !empty($vars['directory'])) ? self::format($vars['directory'], true).'/' : '';
                    if (!empty($temp) && !is_dir(dirname($dir.self::format($temp.'bogus.file', $recursive)))) {
                        mkdir(dirname($dir.self::format($temp.'bogus.file')), 0755, true);
                    }
                    foreach ($vars['upload'] as $file) {
                        if (is_file(Upload::$folder.$file)) {
                            $safe = self::format($temp.substr($file, 32), $recursive, (substr($file, -4) == '.php'));
                            rename(Upload::$folder.$file, $dir.$safe);
                            if (in_array('unzip', $ext) && substr($file, -4) == '.zip') {
                                // unzip
                            }
                        }
                    }
                }
                $page->eject($form->eject);
            }
            $html .= $form->header();
            if (!empty($ext['files'])) {
                $html .= $form->field(array('File',
                    'Enter the name of the file that you would like to create.  The only file types allowed are: .'.implode(', .', explode('|', $ext['files'])),
                ), $form->text('file'));
            }
            if ($recursive) {
                $html .= $form->field(array('Directory',
                    'Enter the directory (if any) where you would like your uploaded files to go.',
                ), $form->text('directory'));
            }
            list($field, $info) = $upload;
            $html .= $form->field(array('Upload', $info), $field);
            $html .= $form->submit('Submit', $bp->button('link pull-right', $bp->icon('download').' Backup', array(
                'href' => $page->url('add', '', 'download', 'files'),
                'title' => 'Click to Download',
            )));
            $html .= $form->close();
            $form = $html;
        } else {
            $form = '';
        }
        $page->link(array(
            'https://cdn.jsdelivr.net/bootstrap.editable/1.5.1/css/bootstrap-editable.min.css',
            'https://cdn.jsdelivr.net/bootstrap.editable/1.5.1/js/bootstrap-editable.min.js',
            '<script>function xEditable () {
                $("#admin_manage_files .rename-file").editable({
                    pk: "rename",
                    type: "text",
                    title: "Rename File",
                    url: window.location.href,
                    savenochange: true,
                    ajaxOptions: {dataType:"json"},
                    validate: function(value) { if($.trim(value) == "") return "This field is required"; },
                    params: function(params) { return {oldname:$(this).text(), newname:params.value, type:params.name}; },
                    success: function(response, newValue) {
                        if(!response.success) return response.msg;
                        $("#admin_manage_files").html(response.html);
                        xEditable();
                    }
                });
            }</script>',
        ));
        $page->jquery('xEditable();
            $("#admin_manage_files").on("click", "a.delete-file", function(){
                var file = $(this).data("uri");
                var row = $(this).closest("tr");
                bootbox.confirm({
                    size: "large",
                    backdrop: false,
                    message: "Are you sure you would like to delete this file?",
                    callback: function (result) {
                        if (result) {
                            row.hide();
                            $.post(location.href, {"delete-file":file}, function(data){
                                if (data != "success") row.show();
                            }, "text");
                        }
                    }
                });
                return false;
            });
        ');

        return '<div id="admin_manage_files">'.$files.'</div><br>'.$form;
    }

    /**
     * Coordinates with the wyciwyg to retrieve and save files.
     *
     * @param array $files  An ``array('field|file'=>'path')``
     * @param array $remove The $file's keys whose path's you would like to remove if saved empty
     */
    public static function save(array $files, array $remove = array())
    {
        $page = Page::html();
        if (($retrieve = $page->post('retrieve')) && isset($files[$retrieve])) {
            exit(is_file($files[$retrieve]) ? file_get_contents($files[$retrieve]) : '');
        }
        if (($save = $page->post('field')) && isset($files[$save]) && !is_null($page->post('wyciwyg'))) {
            $file = $files[$save];
            $code = str_replace("\r\n", "\n", base64_decode(base64_encode($_POST['wyciwyg'])));
            if (empty($code) && in_array($save, $remove)) {
                if (is_file($file)) {
                    unlink($file);
                }
                exit('Saved');
            }
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            if (!empty($code)) {
                extract(Admin::params('page', 'blog'));
                switch (pathinfo($file, PATHINFO_EXTENSION)) {
                    case 'php':
                        $linter = $page->file(md5($file).'.php');
                        file_put_contents($linter, $code);
                        // exec(PHP_BINARY.' -l '.escapeshellarg($linter).' 2>&1', $output);
                        exec('php -l '.escapeshellarg($linter).' 2>&1', $output);
                        unlink($linter);
                        $output = trim(implode("\n", $output));
                        if (!empty($output) && strpos($output, 'No syntax errors') === false) {
                            exit(preg_replace('#'.str_replace('/', '[\\\\//]{1}', preg_quote($linter)).'#', str_replace('.php', '', $save).'.php', $output));
                        }
                        break;
                    case 'twig':
                        $twig = $blog->theme->getTwig();
                        try {
                            $twig->parse($twig->tokenize(new \Twig_Source($code, basename($file))));
                        } catch (\Twig_Error_Syntax $e) {
                            exit($e->getMessage());
                        }
                        break;
                    case 'yml':
                        try {
                            $yaml = Yaml::parse($code);
                        } catch (ParseException $e) {
                            exit($e->getMessage());
                        }
                        break;
                    case 'ini':
                        // http://stackoverflow.com/questions/1241728/can-i-try-catch-a-warning
                        set_error_handler(function ($errno, $errstr) {
                            throw new Exception($errstr);
                        });
                        $linter = $page->file(md5($file).'.ini');
                        file_put_contents($linter, $code);
                        try {
                            $output = parse_ini_file($linter);
                            unlink($linter);
                        } catch (Exception $e) {
                            exit(preg_replace('#'.str_replace('/', '[\\\\//]{1}', preg_quote($linter)).'#', str_replace('.ini', '', $save).'.ini', $e->getMessage()));
                        }
                        restore_error_handler();
                        break;
                }
            }
            file_put_contents($file, $code);
            exit('Saved');
        }
    }

    /**
     * Creates a form for editing an image $path.
     *
     * @param string $path  The filepath to the image
     * @param string $eject Where to eject after editing
     *
     * @return string
     *
     * @used-by Files::view()
     */
    private static function image($path, $eject = '')
    {
        $html = '';
        $imagick = (extension_loaded('imagick') && class_exists('Imagick')) ? true : false;
        $types = array('jpg', 'gif', 'png');
        if ($imagick) {
            $types[] = 'ico';
        }
        if ((!$resource = self::resource($path)) || !in_array($resource['ext'], $types)) {
            return $html;
        }
        extract(Admin::params(array('bp', 'page')));
        $form = $bp->form('admin_image_resize');
        $form->menu('type', array_combine($types, $types));
        $form->values = array(
            'type' => $resource['ext'],
            'width' => $resource['width'],
            'height' => $resource['height'],
            'quality' => 90,
        );
        $form->validator->set(array(
            'type' => 'required|inList['.implode(',', $types).']',
            'width' => 'required|digits|max['.$resource['width'].']',
            'height' => 'required|digits|max['.$resource['height'].']',
            'quality' => 'required|digits|max[100]',
            'coords',
        ));
        if ($vars = $form->validator->certified()) {
            $coords = explode(',', $vars['coords']);
            if (count($coords) == 4) {
                list($x1, $y1, $x2, $y2) = $coords;
                $count = 1;
                while (is_file($resource['dir'].$resource['name'].'-'.$count.'.'.$vars['type'])) {
                    ++$count;
                }
                $name = $resource['dir'].$resource['name'].'-'.$count.'.'.$vars['type'];
                $manager = new ImageManager(array('driver' => $imagick ? 'imagick' : 'gd'));
                $image = $manager->make($resource['path']);
                $image->crop(($x2 - $x1), ($y2 - $y1), $x1, $y1);
                $image->resize($vars['width'], $vars['height']);
                $image->save($name, $vars['quality']);
            }
            $page->eject($page->url('delete', $eject, 'submitted'));
        }
        $form->validator->jquery($form->header['name']);
        $html .= $form->header();
        $div = '<div class="col-sm-4" style="padding:0px;">';
        $html .= $form->field(array('Type',
            'This will convert the image to the selected format.',
        ), $div.$form->select('type', array()).'</div>');
        $html .= $form->field(array('Width',
            'Set the new width of your image.',
        ), $div.$form->group('', 'px', $form->text('width', array('maxlength' => 4))).'</div>');
        $html .= $form->field(array('Height',
            'Set the new height of your image.',
        ), $div.$form->group('', 'px', $form->text('height', array('maxlength' => 4))).'</div>');
        $html .= $form->field(array('Quality',
            'The image quality from 0 (poor quality, small file) to 100 (best quality, big file).',
        ), $div.$form->group('', '%', $form->text('quality', array('maxlength' => 3))).'</div>');
        $form->hidden['coords'] = '';
        $html .= $form->field(false, $page->tag('img', array(
            'id' => 'crop',
            'class' => 'img-responsive',
            'src' => str_replace($page->dir(), $page->path('dir'), $resource['path']),
            'width' => $resource['width'],
            'height' => $resource['height'],
            'alt' => '',
        )));
        $page->link(array(
            'https://cdn.jsdelivr.net/imagesloaded/3.2.0/imagesloaded.pkgd.min.js', // Use imagesLoaded v3 for IE8 support.
            'https://cdn.jsdelivr.net/wordpress/3.8/js/imgareaselect/jquery.imgareaselect.min.js',
            'https://cdn.jsdelivr.net/wordpress/3.8/js/imgareaselect/imgareaselect.css',
        ));
        $page->jquery('$("#crop").imagesLoaded().done(function(){
            var originalWidth = ' .$resource['width'].';
            var originalHeight = ' .$resource['height'].';
            var ias = $("img#crop").attr("width", $("img#crop").width()).attr("height", $("img#crop").height()).imgAreaSelect({
                instance: true,
                handles: "corners",
                imageWidth: ' .$resource['width'].',
                imageHeight: ' .$resource['height'].',
                aspectRatio: false,
                onSelectEnd: function(img, selection){
                    $("input[name=coords]").val(selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
                }
            });

            $("input[name=width]").change(function(){
                var width = parseInt($("input[name=width]").val());
                if (isNaN(width) || width > originalWidth) width = originalWidth;
                $("input[name=width]").val(width);
                $("input[name=height]").val(parseInt(originalHeight / originalWidth * width));
                reCrop();
            });

            $("input[name=height]").change(function(){
                var height = parseInt($("input[name=height]").val());
                if (isNaN(height) || height > originalHeight) height = originalHeight;
                $("input[name=height]").val(height);
                reCrop();
            });

            function reCrop () {
                var width = $("input[name=width]").val();
                var height = $("input[name=height]").val();
                ias.setSelection(0, 0, width, height);
                ias.setOptions({
                    aspectRatio: width + ":" + height,
                    minWidth: width,
                    minHeight: height,
                    show: true
                });
                ias.update();
                $("input[name=coords]").val("0,0," + width + "," + height);
            }
        });');
        $html .= $form->submit('Resize');
        $html .= $form->close();

        return $html;
    }

    /**
     * Determines if the $path is an editable image resource.
     *
     * @param string $path
     *
     * @return false|array
     *
     * @used-by Files::image()
     */
    private static function resource($path)
    {
        if (preg_match('/\.(jpg|jpeg|gif|png|ico)$/i', $path) && is_file($path) && ($dimensions = getimagesize($path))) {
            list($width, $height, $type) = $dimensions;
            switch ($type) {
                case 1: $type = 'gif'; break;
                case 2: $type = 'jpg'; break;
                case 3: $type = 'png'; break;
                case 17: $type = 'ico'; break;
            }
            if (!is_int($type)) {
                $info = pathinfo($path);

                return array(
                    'path' => $path,
                    'dir' => $info['dirname'].'/',
                    'name' => $info['filename'],
                    'ext' => $type,
                    'width' => $width,
                    'height' => $height,
                );
            }
        }

        return false;
    }
}
