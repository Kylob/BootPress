<?php

namespace BootPress\Table;

use BootPress\Page\Component as Page;

class Component
{
    private $page;
    private $head = false; // or true
    private $foot = false; // or true
    private $body = false; // or true
    private $cell = ''; // or a closing </th> or </td> tag
    private $vars = array(); // to include in every cell

    public function __construct()
    {
        $this->page = Page::html();
    }

    public function open($vars = '', $caption = '')
    {
        if (!empty($caption)) {
            $caption = '<caption>'.$caption.'</caption>';
        }

        return "\n".$this->page->tag('table', $this->values($vars)).$caption;
    }

    public function head($vars = '', $cells = '')
    {
        $html = $this->wrapUp('head')."\n\t";
        $this->head = true;
        $this->vars = (!empty($cells)) ? $this->values($cells) : array();

        return $html.$this->page->tag('tr', $this->values($vars));
    }

    public function foot($vars = '', $cells = '')
    {
        $html = $this->wrapUp('foot')."\n\t";
        $this->foot = true;
        $this->vars = (!empty($cells)) ? $this->values($cells) : array();

        return $html.$this->page->tag('tr', $this->values($vars));
    }

    public function row($vars = '', $cells = '')
    {
        $html = $this->wrapUp('row')."\n\t";
        $this->body = true;
        $this->vars = (!empty($cells)) ? $this->values($cells) : array();

        return $html.$this->page->tag('tr', $this->values($vars));
    }

    public function cell($vars = '', $content = '')
    {
        $html = $this->wrapUp('cell');
        $tag = ($this->head) ? 'th' : 'td';
        $this->cell = '</'.$tag.'>';
        $vars = $this->values($vars);
        if (!empty($this->vars)) {
            $vars = array_merge($this->vars, $vars);
        }

        return $html.$this->page->tag($tag, $vars).$content;
    }

    public function close()
    {
        $html = $this->wrapUp('table')."\n";
        $this->head = false;
        $this->foot = false;
        $this->body = false;
        $this->cell = '';
        $this->vars = '';

        return $html.'</table>';
    }

    protected function values($vars)
    {
        if (is_array($vars)) {
            return $vars;
        }
        $attributes = array();
        foreach (explode('|', $vars) as $value) {
            if (strpos($value, '=')) {
                list($key, $value) = explode('=', $value);
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    private function wrapUp($section)
    {
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
