<?php

class BootPress {

  public function container ($name, $content) {
    $html = '<div id="' . $name . '-out">';
      $html .= '<div id="' . $name . '" class="container">';
        $html .= '<div class="row">';
          $html .= '<div class="col-xs-12"><div id="' . $name . '-in">' . $content . '</div></div>';
        $html .= '</div>';
      $html .= '</div>';
    $html .= '</div>';
    return "\n  " . $html;
  }
  
  public function row () {
    $html = '';
    $sizes = func_get_args();
    $columns = array_pop($sizes);
    $prefix = array();
    $grid = range(1, 12);
    foreach ($grid as $num) {
      $prefix[] = 'offset-' . $num;
      $prefix[] = 'push-' . $num;
      $prefix[] = 'pull-' . $num;
      $prefix[] = $num;
    }
    foreach ($columns as $cols) {
      if (is_string($cols)) {
        $html .= $cols;
      } else {
        $content = array_pop($cols);
        foreach ($cols as $key => $value) {
          $cols[$key] = $this->classes('col-' . $sizes[$key], $value, $prefix, true);
        }
        $html .= "\n    " . '<div class="' . implode(' ', $cols) . '">' . $content . '</div>';
      }
    }
    return "\n  " . '<div class="row">' . $html . '</div>';
  }
  
  public function col () {
    return func_get_args();
  }
  
  public function icon ($symbol, $prefix='glyphicon') {
    return '<i class="' . $this->classes($prefix, $symbol) . '"></i>';
  }
  
  public function label ($class, $text) {
    $class = $this->classes('label', $class, array('default', 'primary', 'success', 'info', 'warning', 'danger'));
    return '<span class="' . $class . '">' . $text . '</span>';
  }
  
  public function badge ($count, $align='') {
    if (is_numeric($count) && $count == 0) $count = '';
    $class = (!empty($align)) ? 'badge pull-' . $align : 'badge';
    return '<span class="' . $class . '">' . $count . '</span>';
  }
  
  public function group ($class, $buttons, $form='') {
    $attributes = array('class'=>$this->classes('btn-group', $class, array('xs', 'sm', 'lg', 'justified', 'vertical')));
    if ($form == 'checkbox' || $form == 'radio') $attributes['data-toggle'] = 'buttons-' . $form;
    return '<div ' . $this->attributes($attributes) . '>' . implode('', $buttons) . '</div>';
  }
  
  public function button ($class, $name, $options=array()) {
    $attributes = array('type'=>'button');
    foreach ($options as $key => $value) {
      if (!in_array($key, array('dropdown', 'dropup', 'active', 'disabled', 'pull'))) $attributes[$key] = $value;
    }
    $attributes['class'] = $this->classes('btn', $class, array('block', 'xs', 'sm', 'lg', 'default', 'primary', 'success', 'info', 'warning', 'danger', 'link'));
    if (isset($options['dropdown']) || isset($options['dropup'])) {
      $html = '';
      unset($attributes['href']);
      $class = (isset($options['dropup'])) ? 'btn-group dropup' : 'btn-group';
      $links = (isset($options['dropup'])) ? $options['dropup'] : $options['dropdown'];
      $html .= '<div class="' . $class . '">';
        list($dropdown, $id) = $this->dropdown($links, $options);
        if (is_array($name) && isset($name['split'])) {
          $html .= '<button ' . $this->attributes($attributes) . '>' . $name['split'] . '</button>';
          $attributes['class'] .= ' dropdown-toggle';
          $html .= '<button type="button" class="' . $attributes['class'] . '" id="' . $id . '" data-toggle="dropdown"><span class="caret"></span> <span class="sr-only">Toggle Dropdown</span></button>';
        } else {
          $attributes['data-toggle'] = 'dropdown';
          $attributes['class'] .= ' dropdown-toggle';
          $attributes['id'] = $id;
          $html .= '<button ' . $this->attributes($attributes) . '>' . $name . ' <span class="caret"></span></button>';
        }
        $html .= $dropdown;
      $html .= '</div>';
      return $html;
    } elseif (isset($options['href'])) {
      unset($attributes['type']);
      return '<a ' . $this->attributes($attributes) . '>' . $name . '</a>';
    } else {
      return '<button ' . $this->attributes($attributes) . '>' . $name . '</button>';
    }
  }
  
  public function breadcrumbs ($links) {
    if (empty($links)) return '';
    foreach ($links as $name => $href) {
      $links[$name] = '<li><a href="' . $href . '">' . $name . '</a> <span class="divider"></span></li>';
      if (is_numeric($name)) $name = $href; // this should only happen to the last breadcrumb
    }
    array_pop($links);
    return '<ul class="breadcrumb">' . implode(' ', $links) . ' <li class="active">' . $name . '</li></ul>';
  }
  
  public function list_group ($links, $active=false) {
    $html = '';
    $count = 1;
    $unordered = false;
    foreach ($links as $name => $href) {
      if (empty($html)) { // then this is the first go-around
        if (empty($name)) {
          $unordered = true;
        } elseif ($name == '#' || substr($name, 0, 4) == 'http') {
          return $this->list_group(array_flip($links), $active);
        }
      }
      if ($unordered) $name = $href;
      $name = $this->add_class(array('h([1-6]){1}'=>'list-group-item-heading', 'p'=>'list-group-item-text'), $name);
      if ($unordered) {
        $html .= '<li class="list-group-item">' . $name . '</li>';
      } else {
        $class = ($active == $count || $active == $name || $active == $href) ? 'list-group-item active' : 'list-group-item';
        $html .= '<a href="' . $href . '" class="' . $class . '">' . $name . '</a>';
      }
      $count++;
    }
    return ($unordered) ? '<ul class="list-group">' . $html . '</ul>' : '<div class="list-group">' . $html . '</div>';
  }
  
  public function lister ($tag, $array, $class='') {
    if (!isset($array[0])) {
      $html = '';
      $level = (!is_numeric($class)) ? 1 : (int) $class;
      if (!empty($array)) {
        $html .= (is_string($class) && !empty($class)) ? '<' . $tag . ' class="' . $class . '">' : '<' . $tag . '>';
        $children = array();
        foreach ($array as $num => $list) {
          if ($tag == 'dl') {
            if ($list['depth'] == 1) {
              $html .= '<dt>' . $list['value'] . '</dt>';
            } elseif ($list['depth'] == 2) {
              $html .= '<dd>' . $list['value'] . '</dd>';
            }
          } elseif ($level == $list['depth']) {
            if (!empty($children)) { // stick these to the parent then
              $parent = $this->lister($tag, $children, $level + 1);
              if (substr($html, -5) == '</li>') {
                $html = substr($html, 0, -5) . $parent . '</li>';
              } else {
                $html .= '<li>&nbsp;' . $parent . '</li>';
              }
              $children = array(); // start all over
            }
            $html .= '<li>' . $list['value'] .'</li>';
          } else {
            $children[$num] = array('depth'=>$list['depth'], 'value'=>$list['value']);
          }
        }
        if (!empty($children)) {
          $parent = $this->lister($tag, $children, $level + 1);
          if (substr($html, -5) == '</li>') {
            $html = substr($html, 0, -5) . $parent . '</li>';
          } else {
            $html .= '<li>&nbsp;' . $parent . '</li>';
          }
        }
        $html .= '</' . $tag . '>';
      }
      return $html;
    }
    $list = array();
    foreach ($array as $key => $value) {
      $depth = 1;
      if (is_array($value)) {
        while (is_array($value)) {
          list($k, $value) = each($value);
          $depth++;
        }
      }
      $list[$key+1] = array('depth'=>$depth, 'value'=>$value);
    }
    return $this->lister($tag, $list, $class);
  }
  
  public function listings () {
    static $instance = null;
    if (is_null($instance)) $instance = new BootPressListings;
    return $instance;
  }
  
  public function navbar () {
    return new BootPressNavbar;
  }
  
  public function pills ($links, $options=array()) {
    $class = 'nav nav-pills';
    if (isset($options['align'])) {
      switch ($options['align']) {
        case 'justified': $class .= ' nav-justified'; break;
        case 'vertical':
        case 'stacked': $class .= ' nav-stacked'; break;
        case 'left':
        case 'right': $class .= ' pull-' . $options['align']; break;
      }
    }
    return '<ul class="' . $class . '">' . $this->links($links, $options) . '</ul>';
  }
  
  public function search ($url, $placeholder='Search', $button='') {
    global $page;
    $html = '';
    if (is_array($url)) {
      list($method, $url) = each($url);
    } else {
      $method = 'get';
    }
    $hidden = '';
    if ($method == 'get') {
      $params = $page->url('params', $url);
      $url = $page->url('delete', $url, '?');
      foreach ($params as $key => $value) {
        if ($key != 'search') $hidden .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
      }
    }
    if (is_array($placeholder)) {
      list($size, $placeholder) = each($placeholder);
    } else {
      $size = '';
    }
    if (is_array($button)) {
      $button = implode('', $button);
    } else {
      if (empty($button)) $button = '<i class="glyphicon glyphicon-search"></i>';
      $button = '<button type="submit" class="btn btn-default" title="Search">' . $button . '</button>';
    }
    if (isset($_GET['search'])) $placeholder = $_GET['search'];
    $html .= '<form class="form-inline" method="' . $method . '" action="' . $url . '" autocomplete="off" role="search">' . $hidden;
      if (!empty($button)) {
        $html .= '<div class="' . $this->classes('input-group', $size, array('sm', 'md', 'lg')) . '">';
          $html .= '<input type="text" name="search" class="form-control" placeholder="' . $placeholder . '">';
          $html .= '<div class="input-group-btn">' . $button . '</div>';
        $html .= '</div>';
      } else {
        // add size here
        $html .= '<input type="text" name="search" class="form-control" placeholder="' . $placeholder . '">';
      }
    $html .= '</form>';
    return $html;
  }
  
  public function table ($vars='') {
    return new BootPressTable($vars);
  }
  
  public function tabs ($links, $options=array()) {
    $class = 'nav nav-tabs';
    if (isset($options['align'])) {
      switch ($options['align']) {
        case 'justified': $class .= ' nav-justified'; break;
        case 'left':
        case 'right': $class .= ' pull-' . $options['align']; break;
      }
    }
    return '<ul class="' . $class . '">' . $this->links($links, $options) . '</ul>';
  }
  
  public function toggle ($type, $links, $options=array()) {
    $count = 1; // to help determine active tab
    $toggle = array(); // to send to $this->links()
    $content = '';
    $class = (in_array('fade', $options)) ? 'tab-pane fade' : 'tab-pane';
    $active = (isset($options['active'])) ? $options['active'] : '';
    $disabled = (isset($options['disabled'])) ? $options['disabled'] : '';
    foreach ($links as $name => $html) {
      if (is_array($html)) {
        foreach ($html as $drop => $down) { // cannot be an array, but can be disabled, active, or empty
          if (is_numeric($drop)) { // then it is either a header or a divider
            $toggle[$name][$drop] = $down;
          } else {
            $id = $this->id('tabs');
            $toggle[$name][$drop] = '#' . $id;
            if ($active == $drop || $active == $count) {
              $options['active'] = '#' . $id;
              $content .= '<div class="' . $class . ' in active" id="' . $id . '">' . $down . '</div>';
            } else {
              if ($disabled == $drop || $disabled == $count) $options['disabled'] = '#' . $id;
              $content .= '<div class="' . $class . '" id="' . $id . '">' . $down . '</div>';
            }
            $count++;
          }
        }
      } else { // $name (a tab) cannot be empty
        $id = $this->id('tabs');
        $toggle[$name] = '#' . $id;
        if ($active == $name || $active == $count) {
          $options['active'] = '#' . $id;
          $content .= '<div class="' . $class . ' in active" id="' . $id . '">' . $html . '</div>';
        } else {
          if ($disabled == $name || $disabled == $count) $options['disabled'] = '#' . $id;
          $content .= '<div class="' . $class . '" id="' . $id . '">' . $html . '</div>';
        }
        $count++;
      }
    }
    if (substr($type, 0, 4) == 'pill') {
      $options['toggle'] = 'pill';
      $class = 'nav nav-pills';
    } else { // tabs
      $options['toggle'] = 'tab';
      $class = 'nav nav-tabs';
    }
    if (isset($options['align']) && $options['align'] == 'justified') $class .= ' nav-justified';
    $toggle = $this->links($toggle, $options);
    return '<ul class="' . $class . '">' . $toggle . '</ul><br><div class="tab-content">' . $content . '</div>';
  }
  
  public function media ($list, $related=0) {
    $html = '';
    if (is_numeric($related) && isset($list[0]) && is_array($list[0])) { // a multi-dimensional array
      foreach ($list[$related] as $child => $display) {
        if (is_array($display)) $display = $this->media($display); // else we already called this method on it
        $html .= (isset($list[$child])) ? substr($display, 0, -12) . $this->media($list, $child) . '</div></div>' : $display;
      }
    } else { // is_string($list[0])
      $siblings = func_get_args();
      $parent = array_shift($siblings);
      $children = array();
      foreach ($parent as $key => $value) {
        if (is_array($value)) {
          $children[] = $value;
          unset($parent[$key]);
        }
      }
      $id = (isset($parent['id'])) ? ' id="' . $parent['id'] . '" ' : ' ';
      $class = (isset($parent['class'])) ? 'media ' . $parent['class'] : 'media';
      unset($parent['id'], $parent['class']);
      list($left, $body, $right) = array_pad($parent, 3, '');
      $html .= '<div' . $id . 'class="' . $class . '">';
        if (!empty($left)) $html .= '<span class="pull-left">' . $this->add_class(array('img'=>'media-object'), $left) . '</span>';
        $html .= '<div class="media-body">';
          $html .= '<div class="clearfix">';
            if (!empty($right)) $html .= '<span class="pull-right">' . $this->add_class(array('img'=>'media-object'), $right) . '</span>';
            if (!empty($body)) $html .= $this->add_class(array('h([1-6]){1}'=>'media-heading'), $body);
          $html .= '</div>';
          if (!empty($children)) $html .= call_user_func_array(array($this, 'media'), $children);
        $html .= '</div>';
      $html .= '</div>';
      if (!empty($siblings)) $html .= call_user_func_array(array($this, 'media'), $siblings);
    }
    return $html;
  }
  
  public function panel ($class, $sections) {
    $html = '<div class="' . $this->classes('panel', $class, array('default', 'primary', 'success', 'info', 'warning', 'danger')) . '">';
    foreach ($sections as $panel => $content) {
      if (!is_numeric($panel)) $panel = substr($panel, 0, 4);
      switch ($panel) {
        case 'head': $html .= '<div class="panel-heading">' . $this->add_class(array('h([1-6]){1}'=>'panel-title'), $content) . '</div>'; break;
        case 'body': $html .= '<div class="panel-body">' . $content . '</div>'; break;
        case 'foot': $html .= '<div class="panel-footer">' . $content . '</div>'; break;
        default: $html .= $content; break; // a table, or list group, or ...
      }
    }
    $html .= '</div>';
    return $html;
  }
  
  public function alert ($type, $alert, $dismissable=true) {
    $html = '';
    $class = 'alert alert-' . $type;
    if ($dismissable) $class .= ' alert-dismissable';
    $html .= '<div class="' . $class . '">';
      if ($dismissable) $html .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
      $html .= $this->add_class(array('h([1-6]){1}'=>'alert-heading', 'a'=>'alert-link'), $alert);
    $html .= '</div>';
    return $html;
  }
  
  public function progress ($percent, $class='', $progress='') {
    $html = '';
    $stacked = (is_array($percent) && is_array($class) && $progress !== false) ? true : false;
    if ($progress !== false) $html .= '<div class="' . $this->classes('progress', $progress, array('striped')) . '">';
    if ($stacked) {
      foreach ($percent as $key => $value) {
        $classes = (isset($class[$key])) ? $class[$key] : '';
        $html .= $this->progress($value, $classes, false);
      }
    } else {
      $class = $this->classes('progress-bar', $class, array('success', 'info', 'warning', 'danger'));
      $html .= '<div class="' . $class . '" style="width:' . $percent . '%;" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">';
        $html .= '<span class="sr-only">' . $percent . '% Complete</span>';
      $html .= '</div>';
    }
    if ($progress !== false) $html .= '</div>';
    return $html;
  }
  
  public function accordion ($class, $sections, $open=1) {
    $id = $this->id('accordion');
    $html = '<div class="panel-group" id="' . $id . '">';
    $count = 0;
    foreach ($sections as $head => $body) {
      $count++;
      $collapse = $this->id('collapse');
      $toggle = ' data-toggle="collapse" data-parent="#' . $id . '" href="#' . $collapse . '"';
      $head = preg_replace('/(>){1}([^<]*){1}(<\/){1}/', "$1<a{$toggle}>$2</a>$3", $head);
      $in = ($open == $count) ? ' collapse in' : ' collapse';
      $html .= substr($this->panel($class, array('head'=>$head)), 0, -6); // </div>
        $html .= '<div id="' . $collapse . '" class="panel-collapse' . $in . '">';
          $html .= '<div class="panel-body">' . $body . '</div>';
        $html .= '</div>';
      $html .= '</div>'; // the one we removed up top
    }
    $html .= '</div>';
    return $html;
  }
  
  public function carousel ($images, $options=array()) {
    $id = $this->id('carousel');
    $options = array_merge(array(
      'interval' => 5000, // ie. 5 seconds in between frame changes
      'indicators' => true, // set to false if you don't want them
      'controls' => true // set to false if you don't want them
    ), $options);
    $html = '<div id="' . $id . '" class="carousel slide" data-ride="carousel" data-interval="' . $options['interval'] . '">';
      if ($options['indicators']) {
        $html .= '<ol class="carousel-indicators">';
        foreach ($images as $key => $img) {
          $class = ($key == 0) ? ' class="active"' : '';
          $html .= '<li data-target="#' . $id . '" data-slide-to="' . $key . '"' . $class . '></li>';
        }
        $html .= '</ol>';
      }
      $html .= '<div class="carousel-inner">';
      foreach ($images as $key => $img) {
        $class = ($key == 0) ? 'active item' : 'item';
        if (is_array($img)) {
          list($img, $caption) = $img;
          $html .= '<div class="' . $class . '">' . $img . '<div class="carousel-caption">' . $caption . '</div></div>';
        } else {
          $html .= '<div class="' . $class . '">' . $img . '</div>';
        }
      }
      $html .= '</div>';
      if ($options['controls']) {
        if (is_array($options['controls'])) {
          list($left, $right) = $options['controls'];
        } else {
          $left = $this->icon('chevron-left');
          $right = $this->icon('chevron-right');
        }
        $html .= '<a class="carousel-control left" href="#' . $id . '" data-slide="prev">' . $left . '</a>';
        $html .= '<a class="carousel-control right" href="#' . $id . '" data-slide="next">' . $right . '</a>';
      }
    $html .= '</div>';
    return $html;
  }
  
  protected function classes ($base, $names, $prefix=array(), $exclude_base=false) {
    $attributes = ($exclude_base) ? array() : array($base => $base);
    if (is_string($names) || is_numeric($names)) $names = explode(' ', $names);
    $prefix = array_flip($prefix); // to check if isset rather than in_array - if empty then prefix all
    foreach ($names as $name) {
      if (empty($prefix) || isset($prefix[$name])) $name = $base . '-' . $name;
      $attributes[$name] = $name;
    }
    return implode(' ', $attributes); // without any duplicates or empty values
  }
  
  protected function links ($links, $options=array()) {
    global $page;
    $html = '';
    $count = 1;
    $toggle = (isset($options['toggle'])) ? ' data-toggle="' . $options['toggle'] . '"' : '';
    if (isset($options['active'])) {
      if ($options['active'] == 'url') $options['active'] = $page->url('delete', '', '?');
      elseif ($options['active'] == 'urlquery') $options['active'] = $page->url(); // with query string
    }
    foreach ($links as $name => $href) {
      if (is_array($href)) {
        list($dropdown, $id) = $this->dropdown($href, $options, $count);
        $link = '<a href="#" data-toggle="dropdown" id="' . $id . '">' . $name . ' <b class="caret"></b></a>';
        $class = (strpos($dropdown, 'class="active"') !== false) ? 'dropdown active' : 'dropdown';
        $html .= '<li class="' . $class . '">' . $link . $dropdown . '</li>';
      } else {
        $link = (is_numeric($name)) ? $href : '<a href="' . $href . '"' . $toggle . '>' . $name . '</a>';
        $html .= $this->list_item($link, $options, $name, $href, $count);
        $count++;
      }
    }
    return $html;
  }
  
  protected function dropdown ($links, $options=array(), &$count=1) {
    $html = '';
    $id = $this->id('dropdown');
    $align = (isset($options['pull'])) ? ' pull-' . $options['pull'] : '';
    $toggle = (isset($options['toggle'])) ? ' data-toggle="' . $options['toggle'] . '"' : '';
    foreach ($links as $name => $href) {
      $link = (is_numeric($name)) ? $href : '<a role="menuitem" tabindex="-1" href="' . $href . '"' . $toggle . '>' . $name . '</a>';
      if ($link != $href) {
        $html .= $this->list_item($link, $options, $name, $href, $count);
        $count++;
      } elseif (!empty($link)) {
        $html .= '<li role="presentation" class="dropdown-header">' . $link . '</li>';
      } else {
        $html .= '<li role="presentation" class="divider"></li>';
      }
    }
    $html = '<ul class="dropdown-menu' . $align . '" role="menu" aria-labelledby="' . $id . '">' . $html . '</ul>';
    return array($html, $id);
  }
  
  protected function id ($prefix='') {
    static $id = 0;
    $id++;
    $result = '';
    $lookup = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
    $number = $id;
    if ($number < 100) $lookup = array_slice($lookup, 4);
    foreach ($lookup as $roman => $value) {
      $matches = intval($number / $value);
      $result .= str_repeat($roman, $matches);
      $number = $number % $value;
    }
    return $prefix . $result;
  }
  
  protected function add_class ($add, $string) {
    $rnr = array();
    foreach ($add as $tag => $class) {
      preg_match_all('/(\<' . $tag . '([^\>]*)\>)/i', $string, $matches);
      foreach ($matches[0] as $match) {
        if ($begin = strpos($match, 'class="')) {
          $begin += 7;
          if ($end = strpos($match, '"', $begin)) {
            $classes = substr($match, $begin, $end - $begin);
            if (strpos(' ' . $classes . ' ', ' ' . $class . ' ') === false) {
              $rnr[$match] = substr($match, 0, $end) . ' ' . $class . substr($match, $end);
            }
          } else { // this is screwed up, but...
            $rnr[$match] = substr($match, 0, $begin) . $class . ' ' . substr($match, $begin);
          }
        } else { // just add the class to the end then
          $rnr[$match] = substr($match, 0, -1) . ' class="' . $class . '">';
        }
      }
    }
    return (!empty($rnr)) ? str_replace(array_keys($rnr), array_values($rnr), $string) : $string;
  }
  
  private function list_item ($link, $options, $name, $href, $count) {
    $name = trim(preg_replace('/(\<[^\<]+\<\/[^\>]+\>)/i', '', $name)); // remove tags and their contents
    $check = array_flip(array($name, $href, $count));
    if (isset($options['active']) && isset($check[$options['active']])) {
      return '<li role="presentation" class="active">' . $link . '</li>';
    } elseif (isset($options['disabled']) && isset($check[$options['disabled']])) {
      return '<li role="presentation" class="disabled">' . $link . '</li>';
    } else {
      return '<li role="presentation">' . $link . '</li>';
    }
  }
  
  private function attributes ($array) {
    foreach ($array as $key => $value) {
      $array[$key] = $key . '="' . $value . '"';
    }
    return implode(' ' , $array);
  }
  
}

?>