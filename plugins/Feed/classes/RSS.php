<?php

class RSS {

  public $encoding = 'utf-8';
  private $published = array();
  private $channel = array();
  private $items = array();

  public function __construct ($title, $link, $description, $elements) {
    $this->channel['title'] = $title;
    $this->channel['link'] = $link;
    $this->channel['description'] = $description;
    foreach ($elements as $key => $value) $this->channel[$key] = $value;
  }
  
  public function item ($title, $elements) {
    $item = array();
    $item['title'] = $title;
    foreach ($elements as $key => $value) $item[$key] = $value;
    $this->items[] = $item;
  }
  
  public function display ($cache=24) {
    global $ci;
    $items = '';
    foreach ($this->items as $item) {
      $items .= "  <item>\n";
      foreach ($item as $key => $value) $items .= '    ' . $this->tag($key, $value) . "\n";
      $items .= "  </item>\n";
    }
    $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n";
    $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $xml .= '<channel>' . "\n";
    if (!empty($this->published)) $xml .= '  ' . $this->tag('lastBuildDate', max($this->published)) . "\n";
    foreach ($this->channel as $key => $value) $xml .= '  ' . $this->tag($key, $value) . "\n";
    $xml .= $items . '</channel>' . "\n";
    $xml .= '</rss>';
    $ci->output->set_content_type('application/rss+xml');
    if (!empty($cache)) $ci->sitemap->cache($cache);
    return $ci->filter_links($xml);
  }
  
  private function tag ($key, $values) {
    $tag = '';
    list($value, $attributes) = $this->values($values);
    switch ($key) {
      case 'image':
        $tag .= '<' . $key . '>';
          $tag .= '<url>' . $values['url'] . '</url>';
          $tag .= '<title>' . $values['title'] . '</title>';
          $tag .= '<link>' . $values['link'] . '</link>';
          if (isset($values['width'])) $tag .= '<width>' . $values['width'] . '</width>';
          if (isset($values['height'])) $tag .= '<height>' . $values['height'] . '</height>';
          if (isset($values['description'])) $tag .= '<description>' . $values['description'] . '</description>';
        $tag .= '</' . $key . '>';
        break;
      case 'textInput':
        $tag .= '<' . $key . '>';
          $tag .= '<title>' . $values['title'] . '</title>';
          $tag .= '<description>' . $values['description'] . '</description>';
          $tag .= '<name>' . $values['name'] . '</name>';
          $tag .= '<link>' . $values['link'] . '</link>';
        $tag .= '</' . $key . '>';
        break;
      case 'skipHours':
        $tab .= '<' . $key . '>';
        if (!is_array($values)) $values = array($values);
        foreach ($values as $hour) $tab .= '<hour>' . $hour . '</hour>';
        $tab .= '</' . $key . '>';
        break;
      case 'skipDays':
        $tab .= '<' . $key . '>';
        if (!is_array($values)) $values = array($values);
        foreach ($values as $day) $tab .= '<day>' . $day . '</day>';
        $tab .= '</' . $key . '>';
        break;
      case 'pubDate':
      case 'lastBuildDate':
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
    if ($type == 'pubDate') $this->published[] = $date;
    return date(DATE_RFC2822, $date);
  }

}

?>