<?php

class BootPress {
  
  private $table = null;
  private $navbar = null;
  private $listings = null;
  
  public function __get ($name) {
    switch ($name) {
      case 'table':
      case 'navbar':
      case 'listings':
        if (is_null($this->$name)) {
          $file = ucfirst($name) . '.php';
          $class = 'BootPress' . ucfirst($name);
          include_once $file;
          $this->$name = new $class;
        }
        return $this->$name;
        break;
    }
    return null;
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
  
  public function md ($markdown, $tag=array(), $class='') {
    static $parser = null;
    if (is_null($parser)) {
      include_once('Parsedown.php');
      $parser = new Parsedown;
    }
    $markdown = $parser->parse($markdown);
    if (empty($tag)) return $markdown;
    if (!is_array($tag)) $tag = array($tag => $class);
    return $this->add_class($tag, $markdown);
  }
  
  public function lister ($tag, $array) {
    $class = '';
    if ($space = strpos($tag, ' ')) {
      $class = ' class="' . trim(substr($tag, $space)) . '"';
      $tag = substr($tag, 0, $space);
    }
    $html = '';
    foreach ($array as $key => $value) {
      if ($tag == 'dl') {
        $html .= '<dt>' . $key . '</dt>';
        $html .= '<dd>' . (is_array($value) ? implode('</dd><dd>', $value) : $value) . '</dd>';
      } else {
        $html .= '<li>' . (is_array($value) ? $key . $this->lister($tag, $value) : $value) . '</li>';
      }
    }
    return '<' . $tag . $class . '>' . $html . '</' . $tag . '>';
  }
  
  public function search ($url, $form=array()) {
    global $ci, $page;
    $form = array_merge(array(
      'method' => 'get',
      'name' => 'search',
      'placeholder' => 'Search',
      'button' => $this->icon('search'),
      'class' => 'form-horizontal',
      'size' => ''
    ), (array) $form);
    $input = array(
      'type' => 'text',
      'name' => $form['name'],
      'placeholder' => ($search = $ci->input->get($form['name'])) ? $search : $form['placeholder'],
      'class' => 'form-control'
    );
    $html = '<form ' . $this->attributes(array(
      'action' => ($form['method'] == 'get') ? $page->url('delete', $url, '?') : $url,
      'method' => $form['method'],
      'class' => $form['class'],
      'autocomplete' => 'off',
      'role' => 'search'
    )) . '>';
    if ($form['method'] == 'get') {
      foreach ($page->url('params', $url) as $name => $value) {
        if ($name != $form['name']) $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
      }
    }
    if (!empty($form['button'])) {
      if (strpos($form['button'], '<button') === false) {
        $form['button'] = '<button type="submit" class="btn btn-default" title="Search">' . $form['button'] . '</button>';
      }
      $html .= '<div class="' . $this->classes('input-group', $form['size'], array('sm', 'md', 'lg')) . '">';
        $html .= '<input ' . $this->attributes($input) . '>';
        $html .= '<div class="input-group-btn">' . $form['button'] . '</div>';
      $html .= '</div>';
    } else {
      if (!empty($form['size']) && in_array($form['size'], array('sm', 'md', 'lg'))) {
        $input['class'] .= ' input-' . $form['size'];
      }
      $html .= '<input ' . $this->attributes($input) . '>';
    }
    $html .= '</form>';
    return $html;
  }
  
  public function img ($src, $params='', $alt='', $frag='') {
    if (empty($src)) return $alt;
    if (!empty($frag)) $src .= '#' . trim($frag, '#');
    return trim('<img src="' . $src . '" ' . $params) . '>';
  }
  
  public function icon ($symbol, $prefix='glyphicon', $tag='i') {
    $base = $prefix;
    $names = explode(' ', $symbol);
    $prefix = array($names[0]); // ie. only prefix the first class
    $params = '';
    if ($space = strpos($tag, ' ')) {
      $params = ' ' . trim(substr($tag, $space));
      $tag = substr($tag, 0, $space);
    }
    if ($prefix == 'glyphicon') $tag = 'span';
    return $this->add_class(array($tag => $this->classes($base, $names, $prefix)), '<' . $tag . $params . '></' . $tag . '>');
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
  
  public function group ($class, $buttons, $form='') {
    $attributes = array('class'=>$this->classes('btn-group', $class, array('xs', 'sm', 'lg', 'justified', 'vertical')));
    if ($form == 'checkbox' || $form == 'radio') $attributes['data-toggle'] = 'buttons-' . $form;
    if (strpos($class, 'justified') !== false) {
      $buttons = '<div class="btn-group">' . implode('</div><div class="btn-group">', $buttons) . '</div>';
    } else {
      $buttons = implode('', $buttons);
    }
    return '<div ' . $this->attributes($attributes) . '>' . $buttons . '</div>';
  }
  
  public function links ($tag, $links, $options=array()) {
    global $page;
    $html = '';
    $class = null;
    if ($space = strpos($tag, ' ')) {
      $class = ' ' . trim(substr($tag, $space));
      $tag = substr($tag, 0, $space);
    }
    if ($tag != 'li') $tag = 'a';
    $count = 1;
    if (isset($options['active'])) {
      if ($options['active'] == 'url') $options['active'] = $page->url('delete', '', '?');
      elseif ($options['active'] == 'urlquery') $options['active'] = $page->url(); // with query string
    }
    foreach ($links as $name => $href) {
      if (is_array($href)) {
        list($dropdown, $id) = $this->dropdown($href, $options, $count);
        $active = (strpos($dropdown, 'class="active"') !== false) ? ' active' : null;
        $attributes = array(
          'id' => $id,
          'data-target' => '#',
          'href' => '#', // $page->url(),
          'data-toggle' => 'dropdown',
          'aria-haspopup' => 'true',
          'role' => 'button',
          'aria-expanded' => ($active ? 'true' : 'false')
        );
        $link = '<a ' . $this->attributes($attributes) . '>' . $name . ' <span class="caret"></span></a>';
        if ($tag == 'li') {
          $html .= '<li class="dropdown' . $active . $class . '">' . $link . $dropdown . '</li>';
        } else {
          if ($active || $class) $link = $this->add_class(array('a'=>$active . $class), $link);
          $html .= '<span class="dropdown' . $active . '">' . $link . $dropdown . '</span>';
        }
      } else {
        if (is_numeric($name)) {
          $link = $href;
        } else {
          $attributes = array('href'=>$href);
          if (isset($options['toggle'])) {
            $attributes['role'] = $options['toggle'];
            $attributes['data-toggle'] = $options['toggle'];
            if ($href[0] == '#') $attributes['aria-controls'] = substr($href, 1);
          }
          $link = '<a ' . $this->attributes($attributes) . '>' . $name . '</a>';
        }
        $li = $this->list_item($link, $options, $name, $href, $count);
        if ($tag == 'li') {
          if ($class) $li = $this->add_class(array('li'=>$class), $li);
          $html .= $li;
        } else {
          if ($class) $link = $this->add_class(array('a'=>$class), $link);
          if (strpos($li, 'class="active"') !== false) {
            $link = $this->add_class(array('a'=>'active'), $link);
          } elseif (strpos($li, 'class="disabled"') !== false) {
            $link = $this->add_class(array('a'=>'disabled'), $link);
          }
          $html .= $link;
        }
        $count++;
      }
    }
    return $html;
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
    return '<ul class="' . $class . '">' . $this->links('li', $links, $options) . '</ul>';
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
    return '<ul class="' . $class . '">' . $this->links('li', $links, $options) . '</ul>';
  }
  
  public function breadcrumbs ($links) {
    if (empty($links)) return '';
    foreach ($links as $name => $href) {
      if (is_array($href)) {
        list($dropdown, $id) = $this->dropdown($href);
        $link = '<a href="#" data-toggle="dropdown" id="' . $id . '">' . $name . ' <b class="caret"></b></a>';
        $links[$name] = '<li class="dropdown">' . $link . $dropdown . '</li>';
      } else {
        $links[$name] = '<li><a href="' . $href . '">' . $name . '</a></li>';
      }
      if (is_numeric($name)) $name = $href; // this should only happen to the last breadcrumb
    }
    array_pop($links);
    return '<ul class="breadcrumb">' . implode(' ', $links) . ' <li class="active">' . $name . '</li></ul>';
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
  
  public function alert ($type, $alert, $dismissable=true) {
    $html = '';
    $class = 'alert alert-' . $type;
    if ($dismissable) $class .= ' alert-dismissable';
    $html .= '<div class="' . $class . '" role="alert">';
      if ($dismissable) $html .= '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>';
      $html .= $this->add_class(array('h([1-6]){1}'=>'alert-heading', 'a'=>'alert-link'), $alert);
    $html .= '</div>';
    return $html;
  }
  
  public function progress ($percent, $class='', $display=false) {
    $html = '';
    $classes = (array) $class;
    foreach ((array) $percent as $key => $progress) {
      $class = (isset($classes[$key])) ? $classes[$key] : '';
      $class = $this->classes('progress-bar', $class, array('success', 'info', 'warning', 'danger', 'striped')); // also 'active'
      $html .= '<div class="' . $class . '" style="width:' . $progress . '%;" role="progressbar" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100">';
        $html .= ($display !== false) ? $progress . '%' : '<span class="sr-only">' . $progress . '% Complete</span>';
      $html .= '</div>';
    }
    return '<div class="progress">' . $html . '</div>';
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
        if (!empty($left)) $html .= '<span class="media-left">' . $this->add_class(array('img'=>'media-object'), $left) . '</span>';
        $html .= '<div class="media-body">';
          if (!empty($body)) $html .= $this->add_class(array('h([1-6]){1}'=>'media-heading'), $body);
          if (!empty($children)) $html .= call_user_func_array(array($this, 'media'), $children);
        $html .= '</div>';
        if (!empty($right)) $html .= '<span class="media-right">' . $this->add_class(array('img'=>'media-object'), $right) . '</span>';
      $html .= '</div>';
      if (!empty($siblings)) $html .= call_user_func_array(array($this, 'media'), $siblings);
    }
    return $html;
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
  
  public function panel ($class, $sections) {
    $html = '<div class="' . $this->classes('panel', $class, array('default', 'primary', 'success', 'info', 'warning', 'danger')) . '">';
    foreach ($sections as $panel => $content) {
      if (!is_numeric($panel)) $panel = substr($panel, 0, 4);
      switch ((string) $panel) {
        case 'head': $html .= '<div class="panel-heading">' . $this->add_class(array('h([1-6]){1}'=>'panel-title'), $content) . '</div>'; break;
        case 'body': $html .= '<div class="panel-body">' . $content . '</div>'; break;
        case 'foot': $html .= '<div class="panel-footer">' . $content . '</div>'; break;
        default: $html .= $content; break; // a table, or list group, or ...
      }
    }
    $html .= '</div>';
    return $html;
  }
  
  public function toggle ($type, $links, $options=array()) {
    global $page;
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
            if ($frag = strpos($drop, '#')) { // if it is the first character then it doesn't count
              $id = substr($drop, $frag + 1);
              $drop = substr($drop, 0, $frag);
            } else {
              $id = $page->id('tabs');
            }
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
        if ($frag = strpos($name, '#')) { // if it is the first character then it doesn't count
          $id = substr($name, $frag + 1);
          $name = substr($name, 0, $frag);
        } else {
          $id = $page->id('tabs');
        }
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
    return '<ul class="' . $class . '">' . $this->links('li', $toggle, $options) . '</ul><br><div class="tab-content">' . $content . '</div>';
  }
  
  public function accordion ($class, $sections, $open=1) {
    global $page;
    $id = $page->id('accordion');
    $html = '<div class="panel-group" id="' . $id . '" role="tablist" aria-multiselectable="true">';
    $count = 0;
    foreach ($sections as $head => $body) {
      $count++;
      $heading = $page->id('heading');
      $collapse = $page->id('collapse');
      $in = ($open == $count) ? ' in' : '';
      $attributes = array(
        'data-toggle' => 'collapse',
        'data-parent' => '#' . $id,
        'href' => '#' . $collapse,
        'aria-expanded' => (!empty($in)) ? 'true' : 'false',
        'aria-controls' => $collapse
      );
      $begin = strpos($head, '>') + 1;
      $end = strrpos($head, '</');
      $head = substr($head, 0, $begin) . '<a ' . $this->attributes($attributes) . '>' . substr($head, $begin, $end - $begin) . '</a>' . substr($head, $end);
      $head = substr($this->panel($class, array('head'=>$head)), 0, -6); // </div>
      $html .= substr_replace($head, ' role="tab" id="' . $heading . '"', strpos($head, 'class="panel-heading"') + 21, 0);
        $html .= '<div id="' . $collapse . '" class="panel-collapse collapse' . $in . '" role="tabpanel" aria-labelledby="' . $heading . '">';
          $html .= (strpos($body, 'class="list-group"')) ? $body : '<div class="panel-body">' . $body . '</div>';
        $html .= '</div>';
      $html .= '</div>'; // the one we removed from the $head up top
    }
    $html .= '</div>';
    return $html;
  }
  
  public function carousel ($images, $options=array()) {
    global $page;
    $id = $page->id('carousel');
    $options = array_merge(array(
      'interval' => 5000, // ie. 5 seconds in between frame changes
      'indicators' => true, // set to false if you don't want them
      'controls' => true // set to false if you don't want them
    ), $options);
    $html = '<div id="' . $id . '" class="carousel slide" data-ride="carousel" data-interval="' . $options['interval'] . '">';
      if ($options['indicators']) {
        $indicators = array_keys(array_values($images));
        $html .= '<ol class="carousel-indicators">';
          $html .= '<li data-target="#' . $id . '" data-slide-to="' . array_shift($indicators) . '" class="active"></li>';
          foreach ($indicators as $num) $html .= '<li data-target="#' . $id . '" data-slide-to="' . $num . '"></li>';
        $html .= '</ol>';
      }
      $html .= '<div class="carousel-inner" role="listbox">';
      foreach ($images as $key => $value) {
        $class = (isset($class)) ? 'item' : 'item active'; // ie. the first one is active
        $img = (!is_numeric($key)) ? $key : $value;
        $caption = (!is_numeric($key)) ? '<div class="carousel-caption">' . $value . '</div>' : '';
        $html .= '<div class="' . $class . '">' . $img . $caption . '</div>';
      }
      $html .= '</div>';
      if ($options['controls']) {
        if (is_array($options['controls'])) {
          list($left, $right) = $options['controls'];
        } else {
          $left = $this->icon('chevron-left', 'glyphicon', 'span aria-hidden="true"');
          $right = $this->icon('chevron-right', 'glyphicon', 'span aria-hidden="true"');
        }
        $html .= '<a class="left carousel-control" href="#' . $id . '" role="button" data-slide="prev">' . $left . '<span class="sr-only">Previous</span></a>';
        $html .= '<a class="right carousel-control" href="#' . $id . '" role="button" data-slide="next">' . $right . '<span class="sr-only">Next</span></a>';
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
  
  protected function dropdown ($links, $options=array(), &$count=1) {
    global $page;
    $html = '';
    $id = $page->id('dropdown');
    $align = (isset($options['pull'])) ? ' dropdown-menu-' . $options['pull'] : '';
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
  
  protected function add_class ($add, $string) {
    $rnr = array();
    foreach (array_map('trim', $add) as $tag => $class) {
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
      if (empty($value)) {
        unset($array[$key]);
      } else {
        $array[$key] = $key . '="' . $value . '"';
      }
    }
    return implode(' ', $array);
  }
  
}

?>