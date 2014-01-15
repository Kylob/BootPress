<?php

class Sitemap {

  private $compress;
  private $page = 'index';
  private $index = 1;
  private $count = 1;
  private $urls = array();
  
  public function __construct ($compress=true) {
    ini_set('memory_limit', '75M'); // 50M required per tests
    $this->compress = ($compress) ? '.gz' : '';
  }
  
  public function page ($name) {
    $this->save();
    $this->page = $name;
    $this->index = 1;
  }
  
  public function url ($url, $lastmod='', $changefreq='', $priority='') {
    $url = htmlspecialchars(BASE_URL . $url);
    $lastmod = (!empty($lastmod)) ? date('Y-m-d', strtotime($lastmod)) : false;
    $changefreq = (!empty($changefreq) && in_array(strtolower($changefreq), array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'))) ? strtolower($changefreq) : false;
    $priority = (!empty($priority) && is_numeric($priority) && abs($priority) <= 1) ? round(abs($priority), 1) : false;
    if (!$lastmod && !$changefreq && !$priority) {
      $this->urls[] = $url;
    } else {
      $url = array('loc'=>$url);
      if ($lastmod !== false) $url['lastmod'] = $lastmod;
      if ($changefreq !== false) $url['changefreq'] = $changefreq;
      if ($priority !== false) $url['priority'] = ($priority < 1) ? $priority : '1.0';
      $this->urls[] = $url;
    }
    if ($this->count == 50000) {
      $this->save();
    } else {
      $this->count++;
    }
  }
  
  public function close() {
    $this->save();
  }
  
  private function save () {
    if (empty($this->urls)) return;
    $file = "sitemap-{$this->page}-{$this->index}.xml{$this->compress}";
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($this->urls as $url) {
      $xml .= '  <url>' . "\n";
      if (is_array($url)) {
        foreach ($url as $key => $value) $xml .= "    <{$key}>{$value}</{$key}>\n";
      } else {
        $xml .= "    <loc>{$url}</loc>\n";
      }
      $xml .= '  </url>' . "\n";
    }
    $xml .= '</urlset>' . "\n";
    $this->urls = array();
    if (!empty($this->compress)) $xml = gzencode($xml, 9);
    $fp = fopen(BASE_URI . $file, 'wb');
    fwrite($fp, $xml);
    fclose($fp);
    $this->index++;
    $this->count = 1;
    $num = $this->index; // should have already been incremented
    while (file_exists(BASE_URI . "sitemap-{$this->page}-{$num}.xml{$this->compress}")) {
      unlink(BASE_URI . "sitemap-{$this->page}-{$num}.xml{$this->compress}");
      $num++;
    }
    $this->index($file);
  }
  
  private function index ($file) {
    $sitemaps = array();
    $index = "sitemap-index.xml{$this->compress}";
    if (file_exists(BASE_URI . $index)) {
      $xml = (!empty($this->compress)) ? gzfile(BASE_URI . $index) : file(BASE_URI . $index);
      $tags = $this->xml_tag(implode('', $xml), array('sitemap'));
      foreach ($tags as $xml) {
        $loc = str_replace(BASE_URL, '', $this->xml_tag($xml, 'loc'));
        $lastmod = $this->xml_tag($xml, 'lastmod');
        $lastmod = ($lastmod) ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d');
        if (file_exists(BASE_URI . $loc)) $sitemaps[$loc] = $lastmod;
      }
    }
    $sitemaps[$file] = date('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($sitemaps as $loc => $lastmod) {
      $xml .= '  <sitemap>' . "\n";
      $xml .= '    <loc>' . BASE_URL . $loc . '</loc>' . "\n";
      $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
      $xml .= '  </sitemap>' . "\n";
    }
    $xml .= '</sitemapindex>' . "\n";
    if (!empty($this->compress)) $xml = gzencode($xml, 9);
    $fp = fopen(BASE_URI . $index, 'wb');
    fwrite($fp, $xml);
    fclose($fp);
  }
  
  private function xml_tag ($xml, $tag, &$end='') {
    if (is_array($tag)) {
      $tags = array();
      while ($value = $this->xml_tag($xml, $tag[0], $end)) {
        $tags[] = $value;
        $xml = substr($xml, $end);
      }
      return $tags;
    }
    $pos = strpos($xml, "<{$tag}>");
    if ($pos === false) return false;
    $start = strpos($xml, '>', $pos) + 1;
    $length = strpos($xml, "</{$tag}>", $start) - $start;
    $end = strpos($xml, '>', $start + $length) + 1;
    return ($end !== false) ? substr($xml, $start, $length) : false;
  }
  
  public function __destruct () {
    $this->save();
  }

}

?>