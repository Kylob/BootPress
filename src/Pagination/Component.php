<?php

namespace BootPress\Pagination;

use BootPress\Page\Component as Page;

class Component
{
    private $page;
    private $get;
    private $url;
    private $start;
    private $limit; // formerly $display
    private $total; // formerly $num_pages
    private $current;
    private $links = array();
    private $pager = array();

    public function __construct($framework = 'bootstrap')
    {
        $this->page = Page::html();
        $this->get = false;
        $this->set();
        $this->html($framework);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'limit':
                return ($this->get) ? ' LIMIT '.$this->start.', '.$this->limit : '';
                break;
            case 'last_page':
                return ($this->get && $this->current == $this->total) ? true : false;
                break;
            case 'current_page':
                return ($this->get) ? $this->current : 1;
                break;
            case 'number_pages':
                return ($this->get) ? $this->total : 1;
                break;
            case 'previous_url':
                return ($this->get && $this->current > 1) ? $this->page($this->current - 1) : '';
                break;
            case 'next_url':
                return ($this->get && $this->current < $this->total) ? $this->page($this->current + 1) : '';
                break;
        }

        return;
    }

    /**
     * This is here for the sake of Twig templates, and also because ``empty($this->limit)`` was returning true while ``$this->limit`` would return " LIMIT 0, 10" when accessed directly.
     * 
     * @param string $name Of the dynamic property
     * 
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'limit':
            case 'last_page':
            case 'current_page':
            case 'number_pages':
            case 'previous_url':
            case 'next_url':
                return true;
                break;
        }

        return false;
    }

    public function set($page = 'page', $limit = 10, $url = null)
    {
        if (is_null($url)) {
            $url = $this->page->url();
        }
        $params = $this->page->url('params', $url);
        $this->get = $page;
        $this->url = $url;
        $this->start = 0;
        $this->limit = $limit;
        $this->total = 1;
        $this->current = 1;
        if (isset($params[$page])) {
            $page = array_map('intval', explode('of', $params[$page]));
            if (($current = array_shift($page)) && $current > 1) { // not the first page
                $this->current = $current;
                $this->start = ($current - 1) * $this->limit;
                if (($total = array_shift($page)) && $current < $total) { // and not the last page
                    $this->total = $total;

                    return true;
                }
            }
        }

        return false;
    }

    public function total($count)
    {
        if ($this->get) {
            $this->total = ($count > $this->limit) ? ceil($count / $this->limit) : 1;
        }
    }

    public function html($type = 'bootstrap', array $options = array())
    {
        if ($type == 'links' && !empty($this->links)) {
            $this->links = array_merge($this->links, $options);
        } elseif ($type == 'pager' && !empty($this->pager)) {
            $this->pager = array_merge($this->pager, $options);
        } else {
            // http://getbootstrap.com/components/#pagination
            $this->links = array(
                'wrapper' => '<ul class="pagination">%s</ul>',
                'link' => '<li><a href="%s">%s</a></li>',
                'active' => '<li class="active"><span>%s</span></li>',
                'disabled' => '<li class="disabled"><span>%s</span></li>',
                'previous' => '&laquo;',
                'next' => '&raquo;',
                'dots' => '&hellip;',
            );
            $this->pager = array(
                'wrapper' => '<ul class="pager">%s</ul>',
                'previous' => '<li class="previous"><a href="%s">&laquo; %s</a></li>',
                'next' => '<li class="next"><a href="%s">%s &raquo;</a></li>',
            );
            switch ($type) {
                case 'zurb_foundation': // http://foundation.zurb.com/docs/components/pagination.html
                    $this->html('links', array(
                        'active' => '<li class="current"><a href="">%s</a></li>',
                        'disabled' => '<li class="unavailable"><a href="">%s</a></li>',
                    ));
                    break;
                case 'semantic_ui': // http://semantic-ui.com/collections/menu.html#pagination
                    $this->html('links', array(
                        'wrapper' => '<div class="ui pagination menu">%s</div>',
                        'link' => '<a class="item" href="%s">%s</a>',
                        'active' => '<div class="active item">%s</div>',
                        'disabled' => '<div class="disabled item">%s</div>',
                        'previous' => '<i class="left arrow icon"></i>',
                        'next' => '<i class="right arrow icon"></i>',
                    ));
                    break;
                case 'materialize': // http://materializecss.com/pagination.html
                    $this->html('links', array(
                        'link' => '<li class="waves-effect"><a href="%s">%s</a></li>',
                        'active' => '<li class="active"><a href="#!">%s</a></li>',
                        'disabled' => '<li class="disabled"><a href="#!">%s</a></li>',
                        'previous' => '<i class="material-icons">keyboard_arrow_left</i>',
                        'next' => '<i class="material-icons">keyboard_arrow_right</i>',
                    ));
                    break;
                case 'uikit': // http://getuikit.com/docs/pagination.html
                    $this->html('links', array(
                        'wrapper' => '<ul class="uk-pagination">%s</ul>',
                        'active' => '<li class="uk-active"><span>%s</span></li>',
                        'disabled' => '<li class="uk-disabled"><span>%s</span></li>',
                        'previous' => '<i class="uk-icon-angle-double-left"></i>',
                        'next' => '<i class="uk-icon-angle-double-right"></i>',
                    ));
                    $this->html('pager', array(
                        'wrapper' => '<ul class="uk-pagination">%s</ul>',
                        'previous' => '<li class="uk-pagination-previous"><a href="%s"><i class="uk-icon-angle-double-left"></i> %s</a></li>',
                        'next' => '<li class="uk-pagination-next"><a href="%s">%s <i class="uk-icon-angle-double-right"></i></a></li>',
                    ));
                    break;
            }
        }
    }

    public function links($pad = 3, $array = false)
    {
        if ($this->get === false || $this->total === 1) {
            return '';
        }
        $begin = $this->current - $pad;
        $end = $this->current + $pad;
        if ($begin < 1) {
            $begin = 1;
            $end = $pad * 2 + 1;
        }
        if ($end > $this->total) {
            $end = $this->total;
            $begin = $end - ($pad * 2);
            if ($begin < 1) {
                $begin = 1;
            }
        }
        $links = array();
        if (!empty($this->links['dots']) && $begin > 1) {
            $links[] = sprintf($this->links['link'], $this->page(1), 1);
            if ($begin == 3) {
                $links[] = sprintf($this->links['link'], $this->page(2), 2);
            } elseif ($begin != 2) {
                $links[] = sprintf($this->links['disabled'], $this->links['dots']);
            }
        }
        for ($num = $begin; $num <= $end; ++$num) {
            if ($num == $this->current) {
                $links[] = sprintf($this->links['active'], $num);
            } else {
                $links[] = sprintf($this->links['link'], $this->page($num), $num);
            }
        }
        if (!empty($this->links['dots']) && $end < $this->total) {
            if ($end == ($this->total - 2)) {
                $links[] = sprintf($this->links['link'], $this->page($this->total - 1), $this->total - 1);
            } elseif ($end != ($this->total - 1)) {
                $links[] = sprintf($this->links['disabled'], $this->links['dots']);
            }
            $links[] = sprintf($this->links['link'], $this->page($this->total), $this->total);
        }
        if ($array === false) {
            if (!empty($this->links['previous']) && $this->current > 1) {
                array_unshift($links, sprintf($this->links['link'], $this->page($this->current - 1), $this->links['previous']));
            }
            if (!empty($this->links['next']) && $this->current < $this->total) {
                $links[] = sprintf($this->links['link'], $this->page($this->current + 1), $this->links['next']);
            }

            return (!empty($links)) ? "\n".sprintf($this->links['wrapper'], "\n\t".implode("\n\t", $links)."\n") : '';
        }

        return $links;
    }

    public function pager($previous = 'Previous', $next = 'Next')
    {
        $links = '';
        if (!empty($previous)) {
            if (is_array($previous)) {
                if (isset($previous['url']) && isset($previous['title'])) {
                    $links .= sprintf($this->pager['previous'], $previous['url'], $previous['title']);
                }
            } elseif (is_string($previous)) {
                if ($this->get && $this->total > 1 && $this->current > 1) {
                    $links .= sprintf($this->pager['previous'], $this->page($this->current - 1), $previous);
                }
            }
        }
        if (!empty($next)) {
            if (is_array($next)) {
                if (isset($next['url']) && isset($next['title'])) {
                    $links .= sprintf($this->pager['next'], $next['url'], $next['title']);
                }
            } elseif (is_string($next)) {
                if ($this->get && $this->current < $this->total) {
                    $links .= sprintf($this->pager['next'], $this->page($this->current + 1), $next);
                }
            }
        }

        return (!empty($links)) ? "\n".sprintf($this->pager['wrapper'], $links) : '';
    }

    private function page($num)
    {
        if ($num == 1) {
            return $this->page->url('delete', $this->url, $this->get);
        }

        return $this->page->url('add', $this->url, $this->get, $num.'of'.$this->total);
    }
}
