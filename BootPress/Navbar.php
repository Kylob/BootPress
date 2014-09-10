<?php

class BootPressNavbar extends BootPress {
  
  private $inverse = false;
  private $fixed = '';
  private $brand = '';
  private $html = '';
  
  public function inverse () {
    $this->inverse = true;
    return $this;
  }
  
  public function fixed ($alignment) {
    if (in_array($alignment, array('top', 'bottom', 'static'))) $this->fixed = $alignment;
    return $this;
  }
  
  public function insert ($content) {
    $this->html .= $content;
    return $this;
  }
  
  public function brand ($name, $link='') {
    if (empty($link)) $link = BASE_URL;
    $this->brand = '<a class="navbar-brand" href="' . $link . '">' . $name . '</a>';
    return $this;
  }
  
  public function text ($string, $pull=false) {
    $align = (in_array($pull, array('left', 'right'))) ? ' navbar-' . $pull : '';
    $this->html .= '<p class="navbar-text' . $align . '">' . $this->add_class(array('a'=>'navbar-link'), $string) . '</p>';
    return $this;
  }
  
  public function button ($class, $name, $options=array()) {
    $class .= ' navbar-btn';
    $this->html .= parent::button($class, $name, $options);
    return $this;
  }
  
  public function search ($url, $placeholder='Search', $button='') {
    $search = parent::search($url, $placeholder, $button);
    $this->html .= str_replace('form-inline', 'navbar-form navbar-right', $search);
    return $this;
  }
  
  public function menu ($links, $options=array()) { // array('active'=>'name or url', 'pull'=>'left or right')
    $align = (isset($options['pull'])) ? ' navbar-' . $options['pull'] : '';
    unset($options['pull']);
    $this->html .= '<ul class="nav navbar-nav' . $align . '">' . $this->links($links, $options) . '</ul>';
    return $this;
  }
  
  public function close () {
    global $page;
    $id = $page->id('navbar');
    $class = 'navbar';
    switch ($this->fixed) {
      case 'top':
      case 'bottom': $class .= ' navbar-fixed-' . $this->fixed; break;
      case 'static': $class .= ' navbar-static-top'; break;
    }
    $class .= ($this->inverse) ? ' navbar-inverse' : ' navbar-default';
    $html = '<nav class="' . $class . '" role="navigation">';
      $html .= '<div class="navbar-header">';
        $html .= '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#' . $id . '">';
          $html .= '<span class="sr-only">Toggle navigation</span>';
          $html .= '<span class="icon-bar"></span>';
          $html .= '<span class="icon-bar"></span>';
          $html .= '<span class="icon-bar"></span>';
        $html .= '</button>';
        $html .= $this->brand;
      $html .= '</div>';
      $html .= '<div class="collapse navbar-collapse" id="' . $id . '">' . $this->html . '</div>';
    $html .= '</nav>';
    return $html;
  }
  
}

?>