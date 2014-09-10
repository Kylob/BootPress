<?php

class BootPressTable extends BootPress {
  
  private $table = ''; // an opening <table> tag
  private $head = false; // or an opening <tr> tag
  private $body = false; // or an opening <tr> tag
  private $rows = array(); // when $this->wrap_up() $this->cells (either head or body)
  private $cells = array(); // to be put into $this->rows when $this->wrap_up()
  private $vars = ''; // to include in every cell
  
  public function __construct ($vars) {
    $this->table = '<table' . $this->values($vars, 'table') . '>';
  }
  
  public function head ($vars='', $cells='') {
    $this->wrap_up();
    $this->head = '<tr' . $this->values($vars) . '>';
    $this->vars = (!empty($cells)) ? '|' . $cells : '';
    return $this;
  }
  
  public function row ($vars='', $cells='') {
    $this->wrap_up();
    $this->body = '<tr' . $this->values($vars) . '>';
    $this->vars = (!empty($cells)) ? '|' . $cells : '';
    return $this;
  }
  
  public function cell ($vars='', $content='') {
    if ($this->head) {
      $this->cells[] = '<th' . $this->values($vars . $this->vars) . '>' . $content . '</th>';
    } elseif ($this->body) {
      $this->cells[] = '<td' . $this->values($vars . $this->vars) . '>' . $content . '</td>';
    }
    return $this;
  }
  
  public function close () {
    $this->wrap_up();
    $html = '';
    $previous = '';
    $rows = array();
    foreach ($this->rows as $row) {
      $section = (strpos($row, '</th>') !== false) ? 'thead' : 'tbody';
      if (!empty($rows) && $previous != $section) {
        $html .= '<' . $previous . '>' . implode('', $rows) . '</' . $previous . '>';
        $rows = array();
      }
      $previous = $section;
      $rows[] = $row;
    }
    if (!empty($rows)) $html .= '<' . $previous . '>' . implode('', $rows) . '</' . $previous . '>';
    return $this->table . $html . '</table>';
  }
  
  private function wrap_up () {
    if (!empty($this->cells)) {
      if ($this->head) {
        $this->rows[] = $this->head . implode('', $this->cells) . '</tr>';
      } elseif ($this->body) {
        $this->rows[] = $this->body . implode('', $this->cells) . '</tr>';
      }
      $this->cells = array();
    }
    $this->head = false;
    $this->body = false;
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
  
}

?>