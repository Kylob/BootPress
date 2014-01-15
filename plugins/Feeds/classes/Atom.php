<?php

/*
 *    author:           Kyle Gadd
 *    documentation:    http://www.php-ease.com/classes/atom-feed.html
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Atom {

  public $encoding = 'utf-8';
  private $feed = array();
  private $entries = array();

  public function __construct ($title, $id, $updated) {
    $this->feed['title'] = $title;
    $this->feed['id'] = $id;
    $this->feed['updated'] = $updated;
  }
  
  public function feed ($elements) {
    foreach ($elements as $key => $value) $this->feed[$key] = $value;
  }
  
  public function entry ($title, $id, $updated, $elements) {
    $entry = array();
    $entry['title'] = $title;
    $entry['id'] = $id;
    $entry['updated'] = $updated;
    foreach ($elements as $key => $value) $entry[$key] = $value;
    $this->entries[] = $entry;
  }
  
  public function display () {
    $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n";
    $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
    foreach ($this->feed as $key => $value) $xml .= '  ' . $this->tag($key, $value) . "\n";
    foreach ($this->entries as $entry) {
      $xml .= "  <entry>\n";
      foreach ($entry as $key => $value) $xml .= '    ' . $this->tag($key, $value) . "\n";
      $xml .= "  </entry>\n";
    }
    $xml .= '</feed>';
    header("Content-Type: application/atom+xml");
    return $xml;
  }
  
  private function tag ($key, $values) {
    $tag = '';
    list($value, $attributes) = $this->values($values);
    if (in_array($key, array('updated', 'published'))) $value = $this->date($value);
    if ($key == 'author' || $key == 'contributor') {
      $tag .= '<' . $key . '>';
        $tag .= '<name>' . $values['name'] . '</name>'; // either the feed, or all of the entries mush have an author element
        if (isset($values['email'])) $tag .= '<email>' . $values['email'] . '</email>';
        if (isset($values['uri'])) $tag .= '<uri>' . $values['uri'] . '</uri>';
      $tag .= '</' . $key . '>';
    } elseif ($key == 'category') {
      $tag .= '<' . $key . ' term="';
      $tag .= (isset($values['term'])) ? $values['term'] : $value;
      $tag .= '"';
      if (isset($values['scheme'])) $tag .= ' scheme="' . $values['scheme'] . '"';
      if (isset($values['label'])) $tag .= ' label="' . $values['label'] . '"';
      $tag .= ' />';
    } elseif ($key == 'source') {
      $tag .= '<' . $key . '>';
        $tag .= '<id>' . $values['id'] . '</id>';
        $tag .= '<title>' . $values['email'] . '</title>';
        $tag .= '<updated>' . $values['uri'] . '</updated>';
        $tag .= '<rights>' . $values['rights'] . '</rights>';
      $tag .= '</' . $key . '>';
    } else {
      if (!empty($value)) {
        $tag .= '<' . $key . $attributes . '>' . $value . '</' . $key . '>';
      } else {
        $tag .= '<' . $key . $attributes . ' />';
      }
    }
    return $tag;
  }
  
  private function values ($array) {
    if (!is_array($array)) return array($array, '');
    $value = (isset($array['value'])) ? $array['value'] : '';
    unset ($array['value']);
    $attributes = '';
    foreach ($array as $k => $v) {
      $attributes .= " {$k}=\"" . addslashes($v) . '"';
    }
    return array($value, $attributes);
  }
  
  private function date ($date) {
    if (!is_numeric($date)) $date = strtotime($date);
    return date(DATE_ATOM, $date);
  }

}

?>