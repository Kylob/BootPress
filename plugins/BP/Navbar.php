<?php

class BootstrapNavbar extends Bootstrap {
  
  public function open ($brand, $align='', $inverse=false) {
    global $page;
    if (is_array($brand)) {
      list($brand, $link) = each($brand);
    } else {
      $link = BASE_URL;
    }
    $id = $page->id('navbar');
    $class = 'navbar';
    switch ($align) {
      case 'top':
      case 'bottom': $class .= ' navbar-fixed-' . $align; break;
      case 'static': $class .= ' navbar-static-top'; break;
      case 'inverse': $inverse = 'inverse'; break; // so that you can skip the $align param
    }
    $class .= ($inverse !== false) ? ' navbar-inverse' : ' navbar-default';
    $html = '<nav class="' . $class . '">';
      $html .= '<div class="container-fluid">';
        $html .= '<div class="navbar-header">';
          $html .= '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#' . $id . '">';
            $html .= '<span class="sr-only">Toggle navigation</span>';
            $html .= '<span class="icon-bar"></span>';
            $html .= '<span class="icon-bar"></span>';
            $html .= '<span class="icon-bar"></span>';
          $html .= '</button>';
          $html = $html . "\n\t" . '<a class="navbar-brand" href="' . $link . '">' . $brand . '</a>';
        $html .= '</div>';
        $html .= '<div class="collapse navbar-collapse" id="' . $id . '">';
    return "\n  " . $html;
  }
  
  public function menu ($links, $options=array()) { // array('active'=>'name or url', 'pull'=>'left or right')
    $align = (isset($options['pull'])) ? ' navbar-' . $options['pull'] : '';
    unset($options['pull']);
    return "\n\t" . '<ul class="nav navbar-nav' . $align . '">' . $this->links('li', $links, $options) . '</ul>';
  }
  
  public function button ($class, $name, $options=array()) {
    $class .= ' navbar-btn';
    return "\n\t" . parent::button($class, $name, $options);
  }
  
  public function search ($url, $form=array()) {
    if (!isset($form['class'])) $form['class'] = 'navbar-form navbar-right';
    return "\n\t" . parent::search($url, $form);
  }
  
  public function text ($string, $pull=false) {
    $align = (in_array($pull, array('left', 'right'))) ? ' navbar-' . $pull : '';
    return "\n\t" . '<p class="navbar-text' . $align . '">' . $this->add_class(array('a'=>'navbar-link'), $string) . '</p>';
  }
  
  public function close () {
    return "</div></div>\n  </nav>";
  }
  
}

?>