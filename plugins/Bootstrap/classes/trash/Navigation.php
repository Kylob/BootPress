<?php

class BootstrapNavigation {

  public function brand ($name, $link='') { // for $this->navbar()
    if (empty($link)) $link = BASE_URL;
    return '<a class="navbar-brand" href="' . $link . '">' . $name . '</a>';
  }
  
  public function text ($string, $pull=false) { // for $this->navbar()
    $align = (in_array($pull, array('left', 'right'))) ? ' navbar-' . $pull : '';
    return '<p class="navbar-text' . $align . '">' . $string . '</p>';
  }
  
  public function link ($name, $href) { // for $this->navbar() links within $this->text()
    return '<a href="' . $href . '" class="navbar-link">' . $name . '</a>';
  }
  
  ##
  # Navs: Tabs, Pills, and Lists oh my!
  # 
  # $html .= $this->menu('list', array(
  #   'Home' => array(
  #     'Header',
  #     'About' => $url . 'published/',
  #     'Search' => array(
  #       'This' => '#',
  #       'That' => array('And The Other'=>'#')
  #     )
  #   ),
  #   'Profile' => '#',
  #   'Messages' => '#'
  # ), array('align'=>'horizontal', 'active'=>'Home'));
  # 
  # @access public
  # @param  string $type    [list] Either 'list', 'tabs', 'pills', or 'navbar'
  # @param  array  $links   The $name => $href array of links.  Can be multidimensional for unlimited submenus, but beware - they look somewhat funky, and will not be a part of Bootstrap 3.0.
  #                         If $type == 'list' AND empty($name), then it will create either a header or a divider.
  #                         If $type == 'navbar' AND empty($name), then it will create either a vertical divider or just another <li>.
  # @param  array  $params  [array()] Supports the following menu options:
  #                         'pull' => false, // can be 'left' or 'right' if $type != 'list'
  #                         'align' => 'vertical', // or can be 'horizontal' if $type == 'tabs' or 'pills'
  #                         'active' => '', // $name or $href (which can also checked for in the dropdown menu)
  #                         'disable' => '' // $name or $href
  # @param  bool   $justify To make 'tabs' and 'pills' of equal widths
  # @return string          Unordered list(s) of Bootstrapped menus.
  ##
  public function menu ($type, $links, $options=array(), $justify=false) {
    $html = '';
    $options = array_merge(array(
      'active' => '',
      'align' => 'vertical', // only for 'pills'
      'disable' => '', // not for 'list'
      'pull' => false // not for 'list'
    ), $options);
    if ($type == 'list') {
      $html .= '<div class="list-group">';
      foreach ($links as $name => $href) {
        $class = 'list-group-item';
        if (!empty($options['active']) && in_array($options['active'], array($name, $href))) $class .= ' active';
        $html .= '<a class="' . $class . '" href="' . $href . '">' . $name . '</a>';
      }
      $html .= '</div>';
      return $html;
    }
    #-- Classes --#
    $classes = array('nav');
    if ($type == 'tabs' || $type == 'pills') {
      $classes[] = 'nav-' . $type;
      if ($justify) {
        $classes[] = 'nav-justified';
      } elseif ($type == 'pills' && $options['align'] == 'vertical') {
        $classes[] = 'nav-stacked';
      } elseif (in_array($options['pull'], array('left', 'right'))) {
        $classes[] = 'pull-' . $options['pull'];
      }
    } elseif ($type == 'navbar') {
      $classes[] = 'navbar-nav';
      if (in_array($options['pull'], array('left', 'right'))) {
        $classes[] = 'navbar-' . $options['pull'];
      }
    }
    #-- List --#
    $html .= '<ul class="' . implode(' ', $classes) . '">';
    foreach ($links as $name => $href) {
      if (is_numeric($name)) {
        $html .= '<li>' . $href . '</li>';
      } elseif (is_array($href)) {
        $class = 'dropdown';
        if ($options['active'] == $name || in_array($options['active'], $href)) $class .= ' active';
        $html .= '<li class="' . $class . '">' . call_user_func(array($this, 'dropdown'), $name, $href, $options) . '</li>';
      } elseif (!empty($options['active']) && in_array($options['active'], array($name, $href))) {
        $html .= '<li class="active"><a href="' . $href . '">' . $name . '</a></li>';
      } elseif (!empty($options['disable']) && in_array($options['disable'], array($name, $href))) {
        $html .= '<li class="disabled"><a href="#">' . $name . '</a></li>';
      } else {
        $html .= '<li><a href="' . $href . '">' . $name . '</a></li>';
      }
    }
    $html .= '</ul>';
    return $html;
  }
  
  ##
  # Creates a dropdown <button> of links - also used in $this->menu() for tabs, pills, and navbars
  #
  # $html .= $this->dropdown('Button', array(
  #   'Action' => '#',
  #   '', // divider
  #   'Another Action' => '#'
  # ), array('button'=>'btn-success btn-mini', 'href'=>'split', 'drop'=>'up'));
  # 
  # @access public
  # @param  string $name    Then name of your dropdown button
  # @param  array  $links   $name => $href links
  #                         If $name is a number { // ie. []='item'
  #                           If $href is also blank it will be a divider ie. []=''
  #                           Else it will be a regular list item header
  #                         }
  # @param  array  $options [array()] Dropdown options:
  #                         'drop' => 'down', // or 'up' if 'button'
  #                         'button' => false, // or class name eg. 'btn-success' or ''
  #                         'href' => '#', // if it's a 'button' then it will be split
  #                         'active' => '', // $name or $href
  #                         'caret' => true,
  #                         'pull' => false // 'left', 'right'
  # @return string          A dropdown menu
  ##
  public function dropdown ($name, $links, $options=array()) {
    $html = '';
    $options = array_merge(array(
      'drop' => 'down', // or 'up' if 'button'
      'button' => false,
      'href' => '#',
      'active' => '', // $name or $href (which can also checked for in the dropdown menu)
      'caret' => true,
      'pull' => false // 'left', 'right'
    ), $options);
    if ($options['button'] !== false) {
      if ($options['href'] == '#') {
        if ($options['caret']) $name .= ' <span class="caret"></span>';
        $html .= '<button class="btn ' . $options['button'] . ' dropdown-toggle" data-toggle="dropdown" href="' . $options['href'] . '">' . $name . '</button>';
      } else { // a split button
        $html .= '<button class="btn ' . $options['button'] . '" href="' . $options['href'] . '">' . $name . '</button>';
        $html .= '<button class="btn ' . $options['button'] . ' dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
      }
    } else {
      $target = ($options['href'] != '#') ? ' data-target="#" ' : ' ';
      if ($options['caret']) $name .= ' <b class="caret"></b>';
      $html .= '<a class="dropdown-toggle" data-toggle="dropdown"' . $target . 'href="' . $options['href'] . '">' . $name . '</a>';
    }
    $align = (in_array($options['pull'], array('left', 'right'))) ? ' pull-' . $options['pull'] : '';
    $html .= '<ul class="dropdown-menu' . $align . '">';
    foreach ($links as $name => $href) {
      if (is_numeric($name)) $name = false; // ie. empty
      if (!empty($name)) { // then this is a standard list item
        if (is_array($href)) {
          $sub = array();
          $sub['drop'] = $options['drop'];
          $sub['href'] = '#'; // something else here?
          $sub['caret'] = false;
          $class = 'dropdown-submenu';
          if ($options['pull'] == 'right') {
            $class .= ' pull-left';
          }
          $html .= '<li class="' . $class . '">' . $this->dropdown($name, $href, $sub) . '</li>';
        } elseif (!empty($options['active']) && in_array($options['active'], array($name, $href))) {
          $html .= '<li class="active"><a href="' . $href . '">' . $name . '</a></li>';
        } else {
          $html .= '<li><a href="' . $href . '">' . $name . '</a></li>';
        }
      } elseif (!empty($href)) {
        $html .= '<li class="nav-header">' . $href . '</li>';
      } else {
        $html .= '<li class="divider"></li>';
      }
    }
    $html .= '</ul>';
    if ($options['button'] !== false) {
      $dir = ($options['drop'] == 'up') ? ' dropup' : '';
      $html = '<div class="btn-group' . $dir . '">' . $html . '</div>';
    }
    return $html;
  }
  
  public function breadcrumbs ($links) {
    $html = '<ul class="breadcrumb">';
    $last = array_pop($links);
    foreach ($links as $name => $href) {
      $html .= '<li><a href="' . $href . '">' . $name . '</a> <span class="divider"></span></li>';
    }
    $html .= '<li class="active">' . $last . '</li>';
    $html .= '</ul>';
    return $html;
  }
  
  public function panel ($type='default', $content=array()) {
    $panel = '<div class="panel panel-' . $type . '">';
    foreach ($content as $type => $html) {
      switch ($type) {
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
          $panel .= '<div class="panel-heading"><' . $type . ' class="panel-title">' . $html . '</' . $type . '></div>';
          break;
        case 'heading':
        case 'body':
        case 'footer':
          $panel .= '<div class="panel-' . $type . '">' . $html . '</div>';
          break;
        default:
          $panel .= $html;
          break;
      }
    }
    $panel .= '</div>';
    return $panel;
  }
  
  public function buttons ($links, $params=array()) {
    $html = '';
    $options = array_merge(array(
      'pull' => false, // 'left', 'right'
      
    ), $params);
    $html .= '<div class="btn-toolbar">';
    
    $html .= '</div>';
    return $html;
  }
  
  public function group_buttons ($buttons, $params=array()) {
    $html = '';
    $options = array_merge(array(
      'vertical' => false,
      'toggle' => false, // 'checkbox', 'radio'
    ), $params);
    $align = '';
    if ($options['vertical'] === true) $align .= ' btn-group-vertical';
    $toggle = (in_array($options['toggle'], array('checkbox', 'radio'))) ? ' data-toggle="buttons-' . $options['toggle'] . '"' : '';
    if (is_array($buttons)) $buttons = implode('', $buttons);
    $html .= '<div class="btn-group' . $align . '"' . $toggle . '>' . $buttons . '</div>';
    return $html;
    
  }
  
  public function search ($action, $params=array()) {
    $html = '';
    $class = (in_array('rounded', $params)) ? 'navbar-search' : 'navbar-form';
    if (isset($params['pull']) && in_array($params['pull'], array('left', 'right'))) $class .= ' pull-' . $params['pull'];
    $html .= '<form action="' . $action . '" class="' . $class . '">';
    
    // $class .= (isset($params['span'])) ? ' span' . $params['span'] : ' span4';
    
    $html .= '</form>';
    return $html;
  }
  
  public function tabbable ($links, $params=array()) {
    $html = '';
    $options = array_merge(array(
      'fade' => true,
      'active' => 0,
      'type' => 'tabs', // or 'pills'
      'place' => 'top' // or 'right', 'bottom', 'below', 'left'
    ), $params);
    $count = 0;
    $tabs = array();
    foreach ($links as $name => $content) {
      $id = 'tab' . $count . rand(10,99);
      $class = "tab-pane";
      // if ($options['fade'] !== false) $class .= ' fade';
      if ($options['active'] == $count || $options['active'] == $name) {
        $class .= ' active';
        $options['active'] = $name;
      }
      $html .= "\n\t" . '<div class="' . $class . '" id="' . $id . '">' . $content . '</div>';
      $tabs[$name] = '#' . $id;
      $count++;
    }
    $tabs = $this->menu($options['type'], $tabs, array('active'=>$options['active']));
    $tabs = str_replace('<a', '<a data-toggle="' . substr($options['type'], 0, -1) . '"', $tabs);
    $content = '<div class="tab-content">' . $html . '</div>';
    $dir = '';
    switch ($options['place']) {
      case 'right': $dir = ' tabs-right'; break;
      case 'bottom':
      case 'below': $dir = ' tabs-below'; break;
      case 'left': $dir = ' tabs-left'; break;
    }
    $tabbable = ($dir == ' tabs-below') ? $content . $tabs : $tabs . $content;
    return "\n  " . '<div class="tabbable' . $dir . '">' . $tabbable . "\n  </div>";
  }
  
  public function navbar ($brand, $navigation, $params=array()) {
    $html = '<nav class="navbar navbar-';
      $html .= (in_array('inverted', $params)) ? 'inverse' : 'default';
      if (isset($params['fixed']) && in_array($params['fixed'], array('top', 'bottom', 'static'))) {
       $html .= ($params['fixed'] == 'static') ? ' navbar-static-top' : ' navbar-fixed-' . $params['fixed'];
      }
    $html .= '">';
      $html .= '<div class="navbar-header">';
        $html .= '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#R&RNavbarID">';
          $html .= '<span class="icon-bar"></span>';
          $html .= '<span class="icon-bar"></span>';
          $html .= '<span class="icon-bar"></span>';
        $html .= '</button>';
        $html .= $brand;
      $html .= '</div>';
      $html .= '<div class="collapse navbar-collapse" id="R&RNavbarID">';
        $html .= $navigation;
      $html .= '</div>';
    $html .= '</nav>';
    return $html;
  }
  
}

?>