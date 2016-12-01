<?php

namespace BootPress\Forms;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\Bootstrap\Form as Form;

class Component
{
    
    /** @var string Where you can access your uploaded files.  Everything in this folder more than 2 hours old is removed. */
    public static $folder;
    
    /** @var object A BootPress\Bootstrap\Form instance. */
    private $form;
    
    /**
     * Returns an instance of this class for you to immediately call ``$this->file()``.
     * 
     * @param object A \BootPress\Bootstrap\Form instance.
     * 
     * @return object $this
     */
    public static function bootstrap(Form $form)
    {
        if (is_null(self::$folder)) {
            $dir = str_replace('\\', '/', __DIR__).'/uploads/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            self::$folder = $dir;
        }
        $upload = new static();
        $upload->form = $form;
        return $upload;
    }
    
    /**
     * This method uses the blueimp jQuery File Uploader which makes uploading files intuitive, seamless, and immediate.  The user knows what the status is while the file is uploading, and receives immediate feedback on the progress of the situation.  We also use a little Bootstrap magic to make the natively ugly upload fields beautiful.
     * 
     * When the form has been submitted you will receive either a string, or an array of filenames that are located in the Upload::$folder.  You should move them somewhere else because we delete everything that has sat there for over two hours.  Every file name consists of a 32 hexadecimal string preceding the actual filename submitted by the user.
     * 
     * If you want the form to be pre-populated with (previously uploaded?) files, then set the ``$form->values[$field]['filename'] = 'filepath'`` for each file.  Upon submission these files will obviously not be in the Upload::$folder, they'll be in the values array you gave us.  This is useful for allowing users to add, sort, and delete files when editing whatever you have going on.
     * 
     * You must include jQuery (v1.6+), and [Bootbox](https://cdn.jsdelivr.net/bootbox/4.4.0/bootbox.min.js) to be alerted of any errors.
     * 
     * @param string   $field   The name of your form's field.
     * @param string[] $options The validation parameters for your $field.  The options are:
     * 
     * - int '**size**' - Human readable file sizes (eg."10K" or "3M") are converted into bytes.
     * - int '**limit**' - The maximum number of files to upload.  If your $field does not have '**[]**' brackets at the end (it's not an array) then this number is 1, otherwise the default is 0 (unlimited) which you can change here.
     * - string '**types**' - The file extensions you will allow.  The values are '**|**' pipe-delimited, and must all be spelled out ie. 'jpeg|jpg|gif|png' NOT 'jpe?g|gif|png'.  The default is no restrictions (bad).
     * 
     * @return string An upload form field.
     * 
     * ```php
     * $upload = Upload::bootstrap($bp, $form)->file('images[]', array(
     *     'size' => '2M', // megabytes
     *     'limit' => 5, // count
     *     'types' => 'jpeg|jpg', // extensions
     * ));
     * 
     * if ($vars = $form->validator->certified()) {
     *     foreach ($vars['images'] as $file) {
     *         if (is_file(Upload::$folder.$file)) {
     *             rename(Upload::$folder.$file, $page->dir(substr($file, 32)));
     *         }
     *     }
     * }
     * 
     * echo $form->field('Images', $upload);
     * ```
     */
    public function file($field, array $options = array())
    {
        $page = Page::html();
        
        // View
        if ($file = trim($page->get('uploaded'), '\\/.')) {
            $file = isset($this->form->values[$field][$file]) ? $this->form->values[$field][$file] : static::$folder.$file;
            
            return (is_file($file)) ? $page->send(Asset::dispatch($file)) : exit;
        }
        
        // Options
        $options = array_merge(array(
            'size' => '5M', // megabytes
            'limit' => 0, // unlimited
            'types' => '', // pipe-delimited extensions
        ), $options);
        $size = (is_numeric($options['size'])) ? $options['size'] : \FileUpload\Util::humanReadableToBytes($options['size']);
        $limit = (strpos($field, '[]')) ? $options['limit'] : 1;
        $types = (!empty($options['types'])) ? explode('|', $options['types']) : array();
        
        // Upload
        if ($blueimp = $page->request->files->get('blueimp') && $page->get('submitted') == $this->form->header['name']) {
            
            // Upload file - https://packagist.org/packages/gargron/fileupload
            $file = new \FileUpload\FileUpload($_FILES['blueimp'], $_SERVER, new FileName());
            $file->setPathResolver(new \FileUpload\PathResolver\Simple(rtrim(self::$folder, '/')));
            $file->setFileSystem(new \FileUpload\FileSystem\Simple());
            $file->addValidator(new \FileUpload\Validator\SizeValidator($size));
            $range = $page->request->server->get('HTTP_CONTENT_RANGE');
            if (empty($range) || substr($range, 0, 8) == 'bytes 0-') {
                $mimes = Asset::mime(array(pathinfo($_FILES['blueimp']['name'], PATHINFO_EXTENSION)));
                if (!empty($mimes)) {
                    $file->addValidator(new \FileUpload\Validator\MimeTypeValidator($mimes));
                }
            }
            list($files, $headers) = $file->processAll();
            foreach ($headers as $header => $value) {
                header($header.': '.$value);
            }
            $file = array_shift($files); // there's only one here to working with
            $md5 = substr($file->name, 0, 32);
            $file->name = substr($file->name, 32);
            
            // Prepare json response
            $upload = array();
            if (!empty($file->error)) {
                $upload['error'] = $file->error;
            } elseif ($file->completed) {
                $upload['success'] = $this->preview($md5.$file->name, $file->name, $field, $md5.$file->name);
            }
            $upload['status'] = $file;
            
            // Remove old uploaded files
            $expired = time() - 7200; // 2 hours ago
            foreach (glob(static::$folder.'*') as $file) {
                if (is_file($file) && $expired > filemtime($file)) {
                    unlink($file);
                }
            }
            
            return $page->sendJson($upload);
        }
        
        // Setup
        $this->form->validator->set($field);
        $this->form->header['upload'] = $size;
        $id = $this->form->validator->id($field);
        $page->link(array(
            'https://cdn.jsdelivr.net/sortable/1.4.2/Sortable.min.js',
            'https://cdn.jsdelivr.net/jquery.fileupload/9.9.0/js/vendor/jquery.ui.widget.js',
            'https://cdn.jsdelivr.net/jquery.fileupload/9.9.0/js/jquery.iframe-transport.js',
            'https://cdn.jsdelivr.net/jquery.fileupload/9.9.0/js/jquery.fileupload.js',
            $page->url($page->dirname(__CLASS__), 'uploader.js'),
        ));
        $page->jquery('
            $("#'.$id.'").blueimpFileUploader({
                "size": '.$size.',
                "limit": '.$limit.',
                "accept": "'.implode('|', $types).'"
            });
            $("#'.$id.'Field").click(function(e){
                e.preventDefault();
                $("#'.$id.'Field button").focus();
                $("#'.$id.'").click();
                return false;
            });
            Sortable.create(document.getElementById("'.$id.'Messages"), {
                draggable: ".alert-success"
            });
            $("body").on("click", "#'.$id.'Messages span[class*=glyphicon-trash]", function(){
                var upload = $(this).closest("div[id^='.$id.']");
                if (upload.hasClass("alert-success")) $("#'.$id.'Upload").css("display", "block");
                upload.remove();
            });
        ');
        
        // Field
        $html = $this->form->input('file', array(
            'name' => 'blueimp',
            'id' => $id,
            ($limit != 1) ? 'multiple' : '',
            'style' => 'display:none;',
        ));
        $html .= implode('', array('<div id="'.$id.'Field" title="Click to Upload">',
            '<div class="input-group">',
                '<input type="text" class="form-control'.$this->form->input.'">',
                '<span class="input-group-btn">',
                    $this->form->bp->button('success'.$this->form->input, $this->form->bp->icon('folder-open', 'glyphicon', 'span style="margin-right:5px;"').' Choose File &hellip;'),
                '</span>',
            '</div>',
        '</div>'));
        
        // Messages
        $html .= '<div id="'.$id.'Messages">';
        if (isset($this->form->values[$field]) && is_array($this->form->values[$field])) {
            foreach ($this->form->values[$field] as $file => $path) {
                if (is_file($path)) {
                    $html .= $page->tag('div', array(
                        'id' => $id.preg_replace('/[^a-z0-9]/i', '', $file),
                        'class' => 'alert alert-success '.$id.'Upload',
                        'style' => 'margin:10px 0 0; padding:8px; cursor:move;',
                    ), $this->preview($file, $file, $field, $file));
                }
            }
        }
        $html .= '</div>';
        
        return $html;
    }
    
    private function preview ($url, $name, $field, $value) {
        $page = Page::html();
        $preview = $page->tag('span', array(
            'class' => 'glyphicon glyphicon-ok',
            'style' => 'margin-right:15px;',
        ), '');
        $preview .= $page->tag('a', array(
            'target' => 'preview',
            'class' => 'alert-link',
            'href' => $page->url('add', $page->url('delete', '', 'submitted'), 'uploaded', $url),
        ), $name);
        $preview .= $page->tag('input', array(
            'type' => 'hidden',
            'name' => $field,
            'value' => $value,
        ));
        $preview .= $page->tag('span', array(
            'title' => 'Delete File',
            'class' => 'glyphicon glyphicon-trash',
            'style' => 'cursor:pointer; margin-right:0; float:right;',
        ), '');

        return $preview;
    }
    
    private function __construct()
    {
    }
}
