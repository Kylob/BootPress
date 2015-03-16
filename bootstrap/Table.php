<?php

class BootPressTable extends BootPress {
  
  private $head = false; // or true
  private $foot = false; // or true
  private $body = false; // or true
  private $cell = ''; // or a closing </th> or </td> tag
  private $vars = ''; // to include in every cell
  
  public function open ($vars='', $caption='') {
    if (!empty($caption)) $caption = '<caption>' . $caption . '</caption>';
    return "\n  " . '<table' . $this->values($vars, 'table') . '>' . $caption;
  }
  
  public function head ($vars='', $cells='') {
    $html = $this->wrap_up('head') . "\n\t";
    $this->head = true;
    $this->vars = (!empty($cells)) ? '|' . $cells : '';
    return $html . '<tr' . $this->values($vars) . '>';
  }
  
  public function foot ($vars='', $cells='') {
    $html = $this->wrap_up('foot') . "\n\t";
    $this->foot = true;
    $this->vars = (!empty($cells)) ? '|' . $cells : '';
    return $html . '<tr' . $this->values($vars) . '>';
  }
  
  public function row ($vars='', $cells='') {
    $html = $this->wrap_up('row') . "\n\t";
    $this->body = true;
    $this->vars = (!empty($cells)) ? '|' . $cells : '';
    return $html . '<tr' . $this->values($vars) . '>';
  }
  
  public function cell ($vars='', $content='') {
    $html = $this->wrap_up('cell');
    $tag = ($this->head) ? 'th' : 'td';
    $this->cell = '</' . $tag . '>';
    return $html . '<' . $tag . $this->values($vars . $this->vars) . '>' . $content;
  }
  
  public function close () {
    $html = $this->wrap_up('table') . "\n  ";
    $this->head = false;
    $this->foot = false;
    $this->body = false;
    $this->cell = '';
    $this->vars = '';
    return $html . '</table>';
  }
  
  private function values ($vars, $table=false) {
    $put = array();
    if (!empty($vars)) {
      $values = explode('|', $vars);
      foreach ($values as $value) {
        if (!empty($value)) {
          list($attribute, $value) = explode('=', $value);
          $put[$attribute][] = $value;
        }
      }
    }
    $vars = '';
    if (!empty($put)) {
      foreach ($put as $attribute => $values) {
        $values = implode(' ', $values);
        if ($table && $attribute == 'class') {
          $values = $this->classes('table', $values, array('responsive', 'bordered', 'striped', 'hover', 'condensed'));
        }
        $vars .= ' ' . $attribute . '="' . $values . '"';
      }
    }
    return $vars;
  }
  
  private function wrap_up ($section) {
    $html = $this->cell;
    $this->cell = '';
    switch ($section) {
      case 'head':
        if ($this->head) {
          $html .= '</tr>';
        } else {
          if ($this->foot) {
            $html .= '</tr></tfoot>';
            $this->foot = false;
          } elseif ($this->body) {
            $html .= '</tr></tbody>';
            $this->body = false;
          }
          $html .= '<thead>';
        }
        break;
      case 'foot':
        if ($this->foot) {
          $html .= '</tr>';
        } else {
          if ($this->head) {
            $html .= '</tr></thead>';
            $this->head = false;
          } elseif ($this->body) {
            $html .= '</tr></tbody>';
            $this->body = false;
          }
          $html .= '<tfoot>';
        }
        break;
      case 'row':
        if ($this->body) {
          $html .= '</tr>';
        } else {
          if ($this->head) {
            $html .= '</tr></thead>';
            $this->head = false;
          } elseif ($this->foot) {
            $html .= '</tr></tfoot>';
            $this->foot = false;
          }
          $html .= '<tbody>';
        }
        break;
      case 'table':
        if ($this->head) {
          $html .= '</tr></thead>';
        } elseif ($this->foot) {
          $html .= '</tr></tfoot>';
        } elseif ($this->body) {
          $html .= '</tr></tbody>';
        }
        break;
    }
    return $html;
  }
  
}

?>