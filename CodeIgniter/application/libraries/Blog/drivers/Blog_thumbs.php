<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog_thumbs extends CI_Driver {

  public function form ($folder, $id) { // this method is undocumented as it has limited usefulness outside of the blog
    global $bp, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'blog_thumb');
    if ($thumb = $this->url($folder, $id)) {
      $form->values('thumb', 'delete');
      $form->validate('thumb', 'Thumb', '', 'Click on \'Delete\' to remove this thumbnail.');
      if ($form->submitted()) {
        $this->delete($folder, $id);
        $page->eject($form->eject);
      }
      $html .= $form->header();
      $html .= $form->label('thumb', '<img class="pull-left" style="padding-right:20px;" src="' . $thumb . '">' . $bp->button('xs danger', $bp->icon('trash') . ' Delete', array('type'=>'submit', 'data-loading-text'=>'Deleting...', 'title'=>'Remove this thumbnail')) . $form->field('thumb', 'hidden'));
      $html .= $form->close();
    } else {
      $form->validate('thumb', 'Thumb', 'required', 'Enter the number dot type of image from Admin Resources eg: 3.jpg');
      if ($form->submitted() && empty($form->errors)) {
        $this->save($folder, $id, BASE_URI . 'blog/resources/' . $form->vars['thumb']);
        $page->eject($form->eject);
      }
      $html .= $this->url($folder, $id);
      $html .= $form->header();
      $html .= $form->label_field('thumb', 'text', array('prepend' => '{$blog[\'img\']}', 'append' => $bp->button('primary', 'Submit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))));
      $html .= $form->close();
    }
    unset($form);
    return $html;
  }
  
  public function delete ($folder, $id) {
    $thumb = $this->uri($folder, $id);
    if (file_exists($thumb)) unlink($thumb);
  }
  
  public function save ($folder, $id, $uri) {
    global $page;
    if ($image = $page->plugin('Image', 'uri', $uri)) {
      $image->square(200, 'enforce');
      $image->save($this->uri($folder, $id), 60);
      unset($image);
    }
  }
  
  public function url ($folder, $id, $rename='') {
    global $page;
    $thumb = $this->uri($folder, $id);
    if (!file_exists($thumb)) return '';
    $thumb = str_replace(BASE_URI, BASE_URL, $thumb);
    if (!empty($rename)) $thumb .= (strpos($rename, ' ') !== false) ? '#' . $page->seo($rename) : '#' . $rename;
    return $thumb;
  }
  
  private function uri ($folder, $id) {
    return BASE_URI . 'blog/thumbs/' . trim(preg_replace('/[^a-z_\-\/]/', '', strtolower($folder)), '/') . '/' . (int) $id . '.jpg';
  }
  
}

/* End of file Blog_thumbs.php */
/* Location: ./application/libraries/Blog/drivers/Blog_thumbs.php */