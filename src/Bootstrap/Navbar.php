<?php

namespace BootPress\Bootstrap;

class Navbar extends Common
{
    /**
     * Create a new navbar.
     * 
     * @param mixed  $brand   The name of your website. If this is a string then it will automatically link to your ``$page->url['base']``. If you want to override that, then make this an ``array($brand => $link)``.
     * @param string $align   Either '**top**', '**bottom**', or '**static**' if you want to fix the alignment. If you're just trying to get to the next arg then you can declare '**inverse**' here, and we'll know what you're talking about.
     * @param mixed  $inverse If this is anything but false (eg. '**inverse**') then we will display the inverted or alternate navbar.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->navbar->open(array('Website' => 'http://example.com'));
     * ```
     */
    public function open($brand, $align = '', $inverse = false)
    {
        if (is_array($brand)) {
            list($brand, $link) = (count($brand) > 1) ? $brand: each($brand);
        } else {
            $link = $this->page->url['base'];
        }
        $id = $this->page->id('navbar');
        $class = 'navbar';
        switch ($align) {
            case 'top':
            case 'bottom':
                $class .= ' navbar-fixed-'.$align;
                break;
            case 'static':
                $class .= ' navbar-static-top';
                break;
            case 'inverse':
                $inverse = 'inverse';
                break;
        }
        $class .= ($inverse !== false) ? ' navbar-inverse' : ' navbar-default';
        $html = '<nav class="'.$class.'">';
        $html .= '<div class="container-fluid">';
        $html .= '<div class="navbar-header">';
        $html .= $this->page->tag('button', array(
                        'type' => 'button',
                        'class' => 'navbar-toggle collapsed',
                        'data-toggle' => 'collapse',
                        'data-target' => '#'.$id,
                    ), '<span class="sr-only">Toggle navigation</span>'.str_repeat('<span class="icon-bar"></span>', 3));
        $html .= "\n\t".$this->page->tag('a', array('class' => 'navbar-brand', 'href' => $link), $brand);
        $html .= '</div>';
        $html .= '<div class="collapse navbar-collapse" id="'.$id.'">';

        return "\n".$html;
    }

    /**
     * Create a menu of links across your navbar.
     * 
     * @param array $links   An ``array($name => $href, ...)`` of links. If ``$href`` is an array unto itself, then it will be turned into a dropdown menu with the same header and divider rules applied as with buttons.
     * @param array $options The options available here are:
     * 
     * - '**active**' => **$name**, **$href**, '**url**', '**urlquery**', or number (starting from 1)
     * - '**disabled**' => **$name** or **$href** or number (starting from 1)
     * - '**pull**' => '**left**' (default) or '**right**'
     * 
     * @return string
     * 
     * ```php
     * echo $bp->navbar->menu(array(
     *     'Home' => '#',
     *     'Work' => '#',
     *     'Dropdown' => array(
     *         'Action' => '#',
     *         'More' => '#',
     *     ),
     * ), array(
     *     'active' => 'Home',
     * ));
     * ```
     */
    public function menu(array $links, array $options = array())
    {
        $align = (isset($options['pull'])) ? ' navbar-'.$options['pull'] : '';
        unset($options['pull']);

        return "\n\t".'<ul class="nav navbar-nav'.$align.'">'.$this->links('li', $links, $options).'</ul>';
    }

    /**
     * Exactly the same as ``$bp->button()``, except that it will receive a '**navbar-btn**' class.
     * 
     * @param string $class   To pull it one way or the other, you can add the class '**navbar-right** or '**navbar-left**'.
     * @param string $name    The text of your button.
     * @param array  $options You can also pull the button here by adding ``array('pull' => 'right')`` or ``array('pull' => 'left')``
     * 
     * @return string
     *
     * ```php
     * echo $bp->navbar->button('primary', 'Sign In', array(
     *     'pull' => 'right',
     * ));
     * ```
     */
    public function button($class, $name, array $options = array())
    {
        $class .= ' navbar-btn';
        if (isset($options['pull'])) {
            $class .= ' navbar-'.$options['pull'];
        }
        unset($options['pull']);

        return "\n\t".parent::button($class, $name, $options);
    }

    /**
     * Exactly the same as ``$bp->search()``, except the ``<form>`` will receive a **'navbar-form navbar-right'** class.
     * 
     * @param string $url  Where to send the search term.
     * @param array  $form You can pull the form left by adding ``array('class' => 'navbar-form navbar-left')``.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->navbar->search('http://example.com/', array(
     *     'button' => false,
     * ));
     * ```
     */
    public function search($url, array $form = array())
    {
        if (!isset($form['class'])) {
            $form['class'] = 'navbar-form navbar-right';
        }

        return "\n\t".parent::search($url, $form);
    }

    /**
     * Add a string of text to your navbar.
     * 
     * @param string $string The message you would like to get across.  It will be wrapped in a ``<p class="navbar-text">`` tag, and any ``<a>``'s  will be classed with a '**navbar-link**'.
     * @param string $pull   Either '**left**' or '**right**'.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->navbar->text('You <a href="#">link</a> me');
     * ```
     */
    public function text($string, $pull = false)
    {
        $align = (in_array($pull, array('left', 'right'))) ? ' navbar-'.$pull : '';

        return "\n\t".'<p class="navbar-text'.$align.'">'.$this->addClass($string, array('a' => 'navbar-link')).'</p>';
    }

    /**
     * Add the final ``</div>``'s and ``</nav>``.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->navbar->close();
     * ```
     */
    public function close()
    {
        return "</div></div>\n</nav>";
    }
}
