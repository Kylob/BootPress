<?php

class Atom {

  public $encoding = 'utf-8';
  private $updated = array();
  private $feed = array();
  private $entries = array();
  
  public function __construct ($title, $id, $elements) {
    $this->feed['title'] = $title;
    $this->feed['id'] = $id;
    $this->feed['updated'] = 0;
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
  
  public function display ($cache=24) {
    global $ci;
    $entries = '';
    foreach ($this->entries as $entry) {
      $entries .= "  <entry>\n";
      foreach ($entry as $key => $value) $entries .= '    ' . $this->tag($key, $value) . "\n";
      $entries .= "  </entry>\n";
    }
    if (!empty($this->updated)) $this->feed['updated'] = max($this->updated); // from all of the entries above
    $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n";
    $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
    foreach ($this->feed as $key => $value) $xml .= '  ' . $this->tag($key, $value) . "\n";
    $xml .= $entries . '</feed>';
    $ci->output->set_content_type('application/atom+xml');
    if (!empty($cache)) $ci->sitemap->cache($cache);
    return $xml;
  }
  
  private function tag ($key, $values) {
    $tag = '';
    list($value, $attributes) = $this->values($values);
    switch ($key) {
      case 'author':
      case 'contributor':
        $tag .= '<' . $key . '>';
          $tag .= '<name>' . $values['name'] . '</name>'; // either the feed, or all of the entries mush have an author element
          if (isset($values['email'])) $tag .= '<email>' . $values['email'] . '</email>';
          if (isset($values['uri'])) $tag .= '<uri>' . $values['uri'] . '</uri>';
        $tag .= '</' . $key . '>';
        break;
      case 'category':
        $tag .= '<' . $key . ' term="';
        $tag .= (isset($values['term'])) ? $values['term'] : $value;
        $tag .= '"';
        if (isset($values['scheme'])) $tag .= ' scheme="' . $values['scheme'] . '"';
        if (isset($values['label'])) $tag .= ' label="' . $values['label'] . '"';
        $tag .= ' />';
        break;
      case 'source':
        $tag .= '<' . $key . '>';
          $tag .= '<id>' . $values['id'] . '</id>';
          $tag .= '<title>' . $values['email'] . '</title>';
          $tag .= '<updated>' . $values['uri'] . '</updated>';
          $tag .= '<rights>' . $values['rights'] . '</rights>';
        $tag .= '</' . $key . '>';
        break;
      case 'updated':
      case 'published':
        $value = $this->date($value, $key);
      default:
        if (!empty($value)) {
          $tag .= '<' . $key . $attributes . '>' . $value . '</' . $key . '>';
        } else {
          $tag .= '<' . $key . $attributes . ' />';
        }
        break;
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
  
  private function date ($date, $type) {
    if (!is_numeric($date)) $date = strtotime($date);
    if ($type == 'updated') $this->updated[] = $date;
    return date(DATE_ATOM, $date);
  }

}

?>