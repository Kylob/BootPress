<?php

namespace BootPress\Bootstrap;

use BootPress\Page\Component as Page;
use BootPress\Form\Component as BPForm;

class Common
{
    use Base;

    protected $page;
    private $table;
    private $navbar;
    private $pagination;

    public function __construct()
    {
        $this->page = Page::html();
    }

    /**
     * Instantiates objects upon demand.
     *
     * @param string $name The private property object.
     *
     * @return object
     */
    public function __get($name)
    {
        switch ($name) {
            case 'table':
            case 'navbar':
            case 'pagination':
                if (is_null($this->$name)) {
                    $class = 'BootPress\\Bootstrap\\'.ucfirst($name);
                    $this->$name = new $class();
                }

                return $this->$name;
                break;
        }

        return;
    }

    /**
     * Determines if the private property is available.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'table':
            case 'navbar':
            case 'pagination':

                return true;
                break;
        }

        return false;
    }

    /**
     * This method works in conjunction with ``$bp->col()`` below.  It makes things a little less verbose, but much easier to edit, modify, and see at a glance what in the world is going on.
     * 
     * @param string $size    This value can be either '**xs**' < 768px, '**sm**' >= 768px, '**md**' >= 992 , or '**lg**' >= 1200. This is the point at which your grid will break, if no smaller size is indicated. With this method you can indicate multiple sizes by simply inserting another argument. All of your ``$size``'s must correspond with the values given in the ``$bp->col()``'s or ``$columns`` below.
     * @param array  $columns An array of ``$bp->col()``'s.  This argument does not need to be the second one in line.  It is merely the last one given.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->row('sm', array(
     *     $bp->col(3, 'left'),
     *     $bp->col(6, 'center'),
     *     $bp->col(3, 'right'),
     * ));
     * ```
     */
    public function row()
    {
        $html = '';
        $prefix = array();
        for ($i = 1; $i <= 12; ++$i) {
            $prefix[] = 'offset-'.$i;
            $prefix[] = 'push-'.$i;
            $prefix[] = 'pull-'.$i;
            $prefix[] = $i;
        }
        $sizes = func_get_args();
        $columns = array_pop($sizes);
        foreach ($columns as $cols) {
            if (is_string($cols)) {
                $html .= $cols;
            } else {
                $content = array_pop($cols);
                foreach ($cols as $key => $classes) {
                    $cols[$key] = $this->prefixClasses("col-{$sizes[$key]}", $prefix, $classes, 'exclude_base');
                }
                $html .= '<div class="'.implode(' ', $cols).'">'.$content.'</div>';
            }
        }

        return '<div class="row">'.$html.'</div>';
    }

    /**
     * This is a helper method for ``$bp->row()`` above.  It only returns it's own arguments, but it helps to keep things straight.  Including arrays within arrays can get to be a little unwieldly, just take a look at the ``$bp->media()`` method.
     * 
     * @param mixed  $number This parameter must correspond with it's parent ``$bp->row($size)``. It can be an integer between 1 and 12, as long as all of the ``$bp->col()``'s respective numbers add up to 12 or less. To get fancy you can add a space, then an '**offset-**', '**push-**', or '**pull-**' followed by the number of columns that you would like to affect. All of these will be preceded by ``col-{$size}-...``. To include additional classes just keep on going with a space in between each like you normally would.
     * @param string $column The actual html content you would like to be placed in this column.
     * 
     * @return array The parameters passed to it.
     * 
     * ```php
     * echo $bp->row('sm', 'md', 'lg', array(
     *     $bp->col(12, '9 push-3', '10 push-2', 'content'),
     *     $bp->col('6 offset-3 clearfix', '3 pull-9', '2 pull-10', 'sidebar'),
     * ));
     * ```
     */
    public function col()
    {
        return func_get_args();
    }

    /**
     * This assists you in making Ordered, Unordered, and Definition lists. It is especially useful when you are nesting lists within lists. Your code almost looks exactly like you would expect to see it on the big screen. It would have been nice if we could have named this method 'list', but someone has taken that already.
     * 
     * @param string $tag  Either an '**ol**' (Ordered list), '**ul**' (Unordered list), or a '**dl**' (Definition list). You can add any other classes you like (or not), but the special ones that Bootstrap has blessed us with are:
     * 
     * - '**list-inline**' - For an unordered list to be displayed horizontally.
     * - '**list-unstyled**' - For an unordered list to be unbulleted.
     * - '**dl-horizontal**' - For a definition to be displayed beside it's title rather than below.
     *
     * @param array  $list For Ordered and Unordered lists this is an ``array($li, $li, ...)``, and to nest another list just make the ``$li`` another array.
     * 
     * For Definition Lists this is an ``array($title => $definition, ...)``. If you have multiple ``$definition``'s, then just make ``$title`` an array of them.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->lister('ol', array(
     *     'Coffee',
     *     'Tea' => array(
     *         'Black tea',
     *         'Green tea',
     *     ),
     *     'Milk',
     * ));
     * 
     * echo $bp->lister('ul list-inline', array('Coffee', 'Tea', 'Milk'));
     * 
     * echo $bp->lister('dl dl-horizontal', array(
     *     'Coffee' => array(
     *         'Black hot drink',
     *         'Caffeinated beverage',
     *     ),
     *     'Milk' => 'White cold drink',
     * ));
     * ```
     */
    public function lister($tag, array $list)
    {
        $html = '';
        $class = '';
        if ($space = strpos($tag, ' ')) {
            $class = trim(substr($tag, $space));
            $tag = substr($tag, 0, $space);
        }
        foreach ($list as $key => $value) {
            if ($tag == 'dl') {
                $html .= '<dt>'.$key.'</dt>';
                $html .= '<dd>'.(is_array($value) ? implode('</dd><dd>', $value) : $value).'</dd>';
            } else {
                $html .= '<li>'.(is_array($value) ? $key.$this->lister($tag, $value) : $value).'</li>';
            }
        }

        return $this->page->tag($tag, array('class' => $class), $html);
    }

    /**
     * This will assist you in creating a search bar for your site.
     * 
     * @param string $url  This is the url that you would like the search term to be sent to.
     * @param array  $form To customize the form, you can submit an array with any of the following keys:
     * 
     * - '**name**' - The name of the input field. The default is '**search**'.
     * - '**placeholder**' - Subtle text to indicate what sort of field it is. The default is '**Search**'.
     * - '**button**' - The button itself with tags and all, or just a name. The default is ``$bp->icon('search')``.
     *   - If you don't want a button at all then just give this an empty value.
     * - '**class**' - Any special class(es) to give the ``<form>`` tag. The default is '**form-horizontal**'.
     * - '**size**' - Either '**sm**', '**md**' (the default), or '**lg**'.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->search('http://example.com/search');
     * ```
     */
    public function search($url, array $form = array())
    {
        $html = '';
        $form = array_merge(array(
            'name' => 'search',
            'role' => 'search',
            'class' => 'form-horizontal',
            'placeholder' => 'Search',
            'button' => $this->icon('search'),
            'size' => '',
        ), $form);
        $form['method'] = 'get';
        $form['action'] = $url;
        $input = array('class' => 'form-control', 'placeholder' => $form['placeholder']);
        $button = $form['button'];
        $size = $form['size'];
        unset($form['placeholder'], $form['button'], $form['size']);
        $form = new BPForm($form);
        $form->validator->set($form->header['name'], 'required');
        if (!empty($button)) {
            if (strpos($button, '<button') === false) {
                $button = '<button type="submit" class="btn btn-default" title="Search">'.$button.'</button>';
            }
            $html .= '<div class="'.$this->prefixClasses('input-group', array('sm', 'md', 'lg'), $size).'">';
            $html .= $form->text($form->header['name'], $input);
            $html .= '<div class="input-group-btn">'.$button.'</div>';
            $html .= '</div>';
        } else {
            if (!empty($size) && in_array($size, array('sm', 'md', 'lg'))) {
                $input['class'] .= " input-{$size}";
            }
            $html .= $form->text($form->header['name'], $input);
        }

        return $form->header().$html.$form->close();
    }

    /**
     * Returns a BootPress\Form\Component instance with a few methods added for some Bootstrapped good looks.
     * 
     * @param string $name   The name of the form.
     * @param string $method The form's method for sending.
     * 
     * @return object
     * 
     * ```php
     * $form = $bp->form('example');
     * ```
     */
    public function form($name, $method = 'post')
    {
        return new Form($name, $method, $this);
    }

    /**
     * Though not particularly a Bootstrap feature, this method is a natural for Smarty templates. It returns an ``<img>`` tag along with it's ``$params`` if the ``$src`` is not empty. If the ``$src`` is empty, then it skips the ``$params`` and heads for the ``$alt`` door.
     * 
     * @param string $src    The image src location.
     * @param string $params Additional parameters you would like included in the ``<img>`` tag.
     * @param string $alt    What to display if the ``$src`` is empty.
     * @param string $frag   A #fragment to append to the end of ``$src`` (if any)
     * 
     * @return string
     * 
     * ```php
     * echo $bp->img($image, 'width="75" class="pull-right" style="margin-left:10px;"', 'Sorry, I guess there is no authors image here.', '50x50/seo-name');
     * ```
     */
    public function img($src, $params = '', $alt = '', $frag = '')
    {
        if (empty($src)) {
            return $alt;
        }
        if (!empty($frag)) {
            $src .= '#'.trim($frag, '#');
        }

        return trim('<img src="'.$src.'" '.$params).'>';
    }

    /**
     * This method helps you cut down on verbosity, but do you remember migrating from icon in v.2 to glyphicon in v.3? If that happens again, then we'll just change the ``$prefix`` default and that will be that.
     * 
     * @param string $symbol The icon you would like to display without the base and icon class prefix.
     * @param string $prefix The base and icon class prefix. The default is a Bootstrap icon, but this can be used with any icon font by simply entering their value here.
     * @param string $tag    The tag to use for displaying your font. Everyone uses the <i> tag, so that is the default. If $prefix == 'glyphicon' (the default for Bootstrap) then we will use a span element. Why? I don't know, but since v.2 that seems to be what they prefer to use now. If you want to style an icon further then you can do so here. eg. 'i style="font-size:16px;"'
     * 
     * @return string
     * 
     * ```php
     * echo $bp->icon('asterisk'); // <span class="glyphicon glyphicon-asterisk"></span>
     * ```
     */
    public function icon($symbol, $prefix = 'glyphicon', $tag = 'i')
    {
        $base = $prefix;
        $classes = explode(' ', $symbol);
        $prefix = array($classes[0]); // ie. only prefix the first class
        $params = '';
        if ($space = strpos($tag, ' ')) {
            $params = ' '.trim(substr($tag, $space));
            $tag = substr($tag, 0, $space);
        }
        if ($base == 'glyphicon') {
            $tag = 'span';
        }

        return $this->addClass("<{$tag}{$params}></{$tag}>", array(
            $tag => $this->prefixClasses($base, $prefix, $classes),
        ));
    }

    /**
     * A button by itself is easy enough, but when you start including dropdowns and groups your markup can get ugly quick. Follow the examples. We'll start simple and go from there.
     * 
     * @param string $class   The classes: '**xs**', '**sm**', '**lg**', '**block**', '**default**', '**primary**', '**success**', '**info**', '**warning**', '**danger**', and '**link**' will all be prefixed with 'btn', and we include the 'btn' class too. Notice how we left out the 'btn-group' option? Don't worry about that one. Feel free to add any more that you like such as '**disabled**'.
     * @param string $name    The text of your button. You may also include badges, labels, icons, etc, but leave the caret up to us. If you are including a dropdown menu and you would like to split the button from the menu, then you can make this an ``array('split' => $name)``.
     * @param array  $options These are all of the attributes that you would like to include in the ``<button>`` tag, except if you include an 'href' key then it will be an ``<a>`` tag. Other potential options include: 'id', 'style', 'title', 'type', 'data-...', etc, but the ones we take notice of and do special things with are:
     * 
     * - '**dropdown**' - This is an ``array($name => $link, ...)`` of names and their associated links.
     *   - If the $name is numeric (ie. not specified) then the $link will be a header (if it is not empty), or a divider if it is.
     * - '**dropup**' - The same as dropdown, only the caret and menu goes up instead of down.
     * - '**active**' - This is to specify a $link that will receive the "active" class. You can set this value to either the $name or the $link of your dropdown menu, or a number (starting from 1). If you just want it to select the current page then you can specify '**url**' which will match the current url and uri, or '**urlquery**' which will match the current url, uri, and query string.
     * - '**disabled**' - This is to specify a link that will receive the "disabled" class. You can set this value to either the $name or the $link of your dropdown menu.
     * - '**pull**' - Either 'left' (default) or 'right'. Where you would like the dropdown to be relative to the parent.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->button('primary', 'Primary'); // <button class="btn btn-primary" type="button">Primary</button>
     * 
     * echo $bp->button('xs success', 'Link', array('href'=>'#')); // <a class="btn btn-xs btn-success" href="#">Link</a>
     * 
     * echo $bp->button('danger', 'Dropdown', array(
     *     'dropdown' => array(
     *         'Dropdown Header',
     *         'Action' => '#',
     *         'Another action' => '#',
     *         'Active Link' => '#', 
     *         '',
     *         'Separated Link' => '#',
     *         'Disabled Link' => '#'
     *     ),
     *     'active' => 'Active Link',
     *     'disabled' => 'Disabled Link'
     * ));
     * ```
     */
    public function button($class, $name, array $options = array())
    {
        $attributes = array('type' => 'button');
        foreach ($options as $key => $value) {
            if (!in_array($key, array('dropdown', 'dropup', 'active', 'disabled', 'pull'))) {
                $attributes[$key] = $value;
            }
        }
        $attributes['class'] = $this->prefixClasses('btn', array('block', 'xs', 'sm', 'lg', 'default', 'primary', 'success', 'info', 'warning', 'danger', 'link'), $class);
        if (isset($options['dropdown']) || isset($options['dropup'])) {
            $html = '';
            unset($attributes['href']);
            $class = (isset($options['dropup'])) ? 'btn-group dropup' : 'btn-group';
            $links = (isset($options['dropup'])) ? $options['dropup'] : $options['dropdown'];
            $html .= '<div class="'.$class.'">';
            list($dropdown, $id) = $this->dropdown($links, $options);
            if (is_array($name) && isset($name['split'])) {
                $html .= $this->page->tag('button', $attributes, $name['split']);
                $attributes['id'] = $id;
                $attributes['class'] .= ' dropdown-toggle';
                $attributes['data-toggle'] = 'dropdown';
                $attributes['aria-haspopup'] = 'true';
                $attributes['aria-expanded'] = 'false';
                $html .= $this->page->tag('button', $attributes, '<span class="caret"></span>', '<span class="sr-only">Toggle Dropdown</span>');
            } else {
                $attributes['id'] = $id;
                $attributes['class'] .= ' dropdown-toggle';
                $attributes['data-toggle'] = 'dropdown';
                $attributes['aria-haspopup'] = 'true';
                $attributes['aria-expanded'] = 'false';
                $html .= $this->page->tag('button', $attributes, $name, '<span class="caret"></span>');
            }
            $html .= $dropdown;
            $html .= '</div>';

            return $html;
        } elseif (isset($options['href'])) {
            unset($attributes['type']);

            return $this->page->tag('a', $attributes, $name);
        } else {
            return $this->page->tag('button', $attributes, $name);
        }
    }

    /**
     * This method will group together your buttons.
     * 
     * @param string $class   The classes: '**xs**', '**sm**', '**lg**', '**justified**', and '**vertical**' will all be prefixed with 'btn-group', and we include the 'btn-group' class too. When you size a group up, then don't size the individual buttons.
     * @param array  $buttons An ``array($bp->button(), ...)`` of buttons.
     * @param string $form    This can be either '**checkbox**' or '**radio**' and your button group will act accordingly.
     * 
     * @return string
     * 
     * ```php
     * $bp->group('', array(
     *     $bp->button('primary', 'Btn'),
     *     $bp->button('primary', 'Group'),
     *     $bp->button('primary', array('split'=>'Split'), array(
     *         'dropdown' => array(
     *             'Works' => '#',
     *             'Here' => '#',
     *             'Too' => '#',
     *         ),
     *         'pull'=>'right'
     *     )),
     *     $bp->button('primary', 'Middle'),
     * ));
     * ```
     */
    public function group($class, array $buttons, $form = '')
    {
        $attributes = array('class' => $this->prefixClasses('btn-group', array('xs', 'sm', 'lg', 'justified', 'vertical'), $class));
        if ($form == 'checkbox' || $form == 'radio') {
            $attributes['data-toggle'] = 'buttons-'.$form;
        }
        if (strpos($class, 'justified') !== false) {
            $buttons = '<div class="btn-group" role="group">'.implode('</div><div class="btn-group" role="group">', $buttons).'</div>';
        } else {
            $buttons = implode('', $buttons);
        }
        $attributes['role'] = 'group';

        return $this->page->tag('div', $attributes, $buttons);
    }

    /**
     * This used to be a private method that we only used internally for tabs and pills and buttons and so forth, but it is just so useful. Now you can make your own dropdowns with regular ``<a>`` links as will.
     * 
     * @param string $tag     If this isn't 'li', then it will be an '**a**'. If you specify 'li' tags then you will need to surround this method's output with your own ``<ul>`` or ``<ol>`` tags. Otherwise you can just use the returned ``<a>`` $links (with dropdowns if any) as is. The ``<a>``'s with dropdowns will be surrounded by a ``<span class="dropdown">``. If one of those dropdown links are active then the ``<span>`` and ``<a>`` tags will receive an additional 'active' class as well. To add any other class(es) to the ``<a>`` or ``<li>`` tags just add them after the $tag here eg. '**a special-class**'.
     * @param array  $links   An ``array($name => $href, ...)`` of links. If $href is an array unto itself, then it will be turned into a dropdown menu with the same header and divider rules applied as with ``$bp->buttons()``.
     * @param array  $options The available options are:
     * 
     * - '**active**' => $name, $href, 'url', 'urlquery', or number (starting from 1)
     * - '**disabled**' => $name or $href or number (starting from 1)
     * - '**align**' => '**left**' (default) or '**right**' - the direction you would like to pull them towards
     * 
     * @return string
     * 
     * ```php
     * echo $bp->links('a special-class', array(
     *     'Home' => BASE_URL,
     *     'Dropdown' => array(
     *         'Header',
     *         'Action' => '#',
     *         'Another Action' => '#',
     *     ),
     * ), array('active'=>'url'));
     * ```
     */
    public function links($tag, array $links, array $options = array())
    {
        $html = '';
        $class = null;
        if ($space = strpos($tag, ' ')) {
            $class = ' '.trim(substr($tag, $space));
            $tag = substr($tag, 0, $space);
        }
        if ($tag != 'li') {
            $tag = 'a';
        }
        $count = 1;
        if (isset($options['active'])) {
            if ($options['active'] == 'url') {
                $options['active'] = $this->page->url('delete', '', '?');
            } elseif ($options['active'] == 'urlquery') {
                $options['active'] = $this->page->url();
            }
        }
        foreach ($links as $name => $href) {
            if (is_array($href)) {
                list($dropdown, $id) = $this->dropdown($href, $options, $count);
                $active = (strpos($dropdown, 'class="active"') !== false) ? ' active' : null;
                $link = $this->page->tag('a', array(
                    'id' => $id,
                    'data-target' => '#',
                    'href' => '#',
                    'role' => 'button',
                    'data-toggle' => 'dropdown',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => $active ? 'true' : 'false',
                ), $name, '<span class="caret"></span>');
                if ($tag == 'li') {
                    $html .= $this->page->tag('li', array('class' => 'dropdown'.$active.$class), $link, $dropdown);
                } else {
                    if ($active || $class) {
                        $link = $this->addClass($link, array('a' => $active.$class));
                    }
                    $html .= $this->page->tag('span', array('class' => 'dropdown'.$active), $link, $dropdown);
                }
            } else {
                if (is_numeric($name)) {
                    $link = $href;
                } else {
                    $attributes = array('href' => $href);
                    if (isset($options['toggle'])) {
                        if ($href[0] == '#') {
                            $attributes['aria-controls'] = substr($href, 1);
                        }
                        $attributes['role'] = $options['toggle'];
                        $attributes['data-toggle'] = $options['toggle'];
                    }
                    $link = $this->page->tag('a', $attributes, $name);
                }
                $li = $this->listItem($link, $options, $name, $href, $count);
                if ($tag == 'li') {
                    if ($class) {
                        $li = $this->addClass($li, array('li' => $class));
                    }
                    $html .= $li;
                } else {
                    if ($class) {
                        $link = $this->addClass($link, array('a' => $class));
                    }
                    if (strpos($li, 'class="active"') !== false) {
                        $link = $this->addClass($link, array('a' => 'active'));
                    } elseif (strpos($li, 'class="disabled"') !== false) {
                        $link = $this->addClass($link, array('a' => 'disabled'));
                    }
                    $html .= $link;
                }
                ++$count;
            }
        }

        return $html;
    }

    /**
     * Creates a Bootstrap tabs nav menu.
     * 
     * @param array $links   An ``array($name => $href, ...)`` of links. If $href is an array unto itself, then it will be turned into a dropdown menu with the same header and divider rules applied as with ``$bp->buttons()``.
     * @param array $options The available options are:
     * 
     * - '**active**' => $name, $href, 'url', 'urlquery', or number (starting from 1)
     * - '**disabled**' => $name or $href or number (starting from 1)
     * - '**align**' =>
     *   - '**justified**' - The tabs will horizontally extend the full width
     *   - '**left**' (default) or '**right**' - The direction you would like to pull them towards
     * 
     * @return string
     * 
     * ```php
     * echo $bp->tabs(array(
     *     'Nav' => '#',
     *     'Tabs' => '#',
     *     'Justified' => '#',
     * ), array('active'=>1, 'align'=>'justified'));
     * ```
     */
    public function tabs(array $links, array $options = array())
    {
        $class = 'nav nav-tabs';
        if (isset($options['align'])) {
            switch ($options['align']) {
                case 'justified':
                    $class .= ' nav-justified';
                    break;
                case 'left':
                case 'right':
                    $class .= ' pull-'.$options['align'];
                    break;
            }
        }

        return $this->page->tag('ul', array('class' => $class), $this->links('li', $links, $options));
    }

    /**
     * Creates a Bootstrap pills nav menu.
     * 
     * @param array $links   An ``array($name => $href, ...)`` of links. If $href is an array unto itself, then it will be turned into a dropdown menu with the same header and divider rules applied as with ``$bp->buttons()``.
     * @param array $options The available options are:
     * 
     * - '**active**' => $name, $href, 'url', 'urlquery', or number (starting from 1)
     * - '**disabled**' => $name or $href or number (starting from 1)
     * - '**align**' =>
     *   - '**justified**' - The pills will horizontally extend the full width
     *   - '**vertical**' or '**stacked**' - Each pill will be stacked on top of the other
     *   - '**left**' (default) or '**right**' - The direction you would like to pull them towards
     * 
     * @return string
     * 
     * ```php
     * echo $bp->pills(array(
     *     'Home ' . $bp->badge(42) => '#',
     *     'Profile' . $bp->badge(0) => '#',
     *     'Messages' . $bp->badge(3) => array(
     *         'New! ' . $bp->badge(1) => '#',
     *         'Read ' => '#',
     *         'Trashed ' => '#',
     *         '',
     *         'Spam ' . $bp->badge(2) => '#'
     *     ),
     *     'Disabled link' => '#'
     * ), array('active'=>'Home', 'disabled'=>'Disabled link'));
     * 
     * echo $bp->pills(array(
     *     'Nav' => '#',
     *     'Pills' => '#',
     *     'Justified' => '#'
     * ), array('active'=>'Nav', 'align'=>'justified'));
     * ```
     */
    public function pills(array $links, array $options = array())
    {
        $class = 'nav nav-pills';
        if (isset($options['align'])) {
            switch ($options['align']) {
                case 'justified':
                    $class .= ' nav-justified';
                    break;
                case 'vertical':
                case 'stacked':
                    $class .= ' nav-stacked';
                    break;
                case 'left':
                case 'right':
                    $class .= ' pull-'.$options['align'];
                    break;
            }
        }

        return $this->page->tag('ul', array('class' => $class), $this->links('li', $links, $options));
    }

    /**
     * This creates a Bootstrap styled breadcrumb trail. The last link is automatically activated.
     * 
     * @param array $links An ``array($name => $href)`` of links to display. The ``$href`` may also be another ``array($name => $href)`` of dropdown links.
     * 
     * @return string
     * 
     * ```php
     * $bp->breadcrumbs(array(
     *     'Home' => '#',
     *     'Library' => '#',
     *     'Data' => '#'
     * )); // <ul class="breadcrumb"><li><a href="#">Home</a></li> <li><a href="#">Library</a></li> <li class="active">Data</li></ul>
     * ```
     */
    public function breadcrumbs(array $links)
    {
        if (empty($links)) {
            return '';
        }
        foreach ($links as $name => $href) {
            if (is_array($href)) {
                list($dropdown, $id) = $this->dropdown($href);
                $link = $this->page->tag('a', array('href' => '#', 'data-toggle' => 'dropdown', 'id' => $id), $name, '<b class="caret"></b>');
                $links[$name] = '<li class="dropdown">'.$link.$dropdown.'</li>';
            } else {
                $links[$name] = '<li><a href="'.$href.'">'.$name.'</a></li>';
            }
            if ($name === 0) {
                $name = $href; // this should only happen to the last breadcrumb
            }
        }
        array_pop($links);

        return '<ul class="breadcrumb">'.implode(' ', $links).' <li class="active">'.$name.'</li></ul>';
    }

    /**
     * This makes a Bootstrap label, and saves you having to type label twice. Awesome, right? We didn't build the class for this, but it was simple enough to include so I did. I use it.
     * 
     * @param string $class Either '**default**', '**primary**', '**success**', '**info**', '**warning**', or '**danger**'. The label class and prefix are automatically included. You can add more classes to it if you like.
     * @param string $text  The label's text.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->label('primary', 'New'); // <span class="label label-primary">New</span>
     * ```
     */
    public function label($class, $text)
    {
        return $this->page->tag('span', array(
            'class' => $this->prefixClasses('label', array('default', 'primary', 'success', 'info', 'warning', 'danger'), $class),
        ), $text);
    }

    /**
     * This method is a little bit more useful than label, so I am not as embarrassed to present it. If ``$count`` equals 0, or if it's not numeric (null?), then it still includes the tag but leaves the value empty.
     * 
     * @param int    $count The number you would like to display.
     * @param string $align This will pull your badge 'right' or 'left' or not (default). In a list group, badges are automatically positioned on the right.
     * 
     * @return string
     * 
     * ```php
     * $count = 13;
     * echo $bp->badge($count, 'right'); // <span class="badge pull-right">13</span>
     * ```
     */
    public function badge($count, $align = '')
    {
        return $this->page->tag('span', array(
            'class' => !empty($align) ? 'badge pull-'.$align : 'badge',
        ), (is_numeric($count) && $count == 0) ? '' : $count);
    }

    /**
     * Creates Bootstrap alert messages.
     * 
     * @param string $type        Either '**success**', '**info**', '**warning**', or '**danger**'.
     * @param string $alert       The status message. All ``<h1-6>`` headers and ``<a>`` links will be classed appropriately.
     * @param bool   $dismissable If you set this to false, then the alert will not be dismissable.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->row('sm', array(
     *     $bp->col(4, $bp->alert('danger', '<strong>Danger</strong> Change a few things up and try submitting again. ', false)),
     *     $bp->col(4,  $bp->alert('success', '<strong>Success</strong> You successfully read this <a href="#">important alert message</a>. ', false)),
     *     $bp->col(4, $bp->alert('info', '<strong>Info</strong> This alert needs your attention, but it\'s not super important. '))
     * ));
     * ```
     */
    public function alert($type, $alert, $dismissable = true)
    {
        $html = '';
        $class = 'alert alert-'.$type;
        if ($dismissable) {
            $class .= ' alert-dismissable';
        }
        $html .= '<div class="'.$class.'" role="alert">';
        if ($dismissable) {
            $html .= '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>';
        }
        $html .= $this->addClass($alert, array('h([1-6]){1}' => 'alert-heading', 'a' => 'alert-link'));
        $html .= '</div>';

        return $html;
    }

    /**
     * This allows you to create any flavor of progress bar that Bootstrap has to offer.
     * 
     * @param int    $percent The amount of progress from 0 to 100. In order to stack multiple values then turn this into an array.
     * @param string $class   You can include one of the four contextual classes: '**success**', '**info**', '**warning**' or '**danger**'. Also '**striped**' and '**active**' if you like the looks of those. These will all be properly prefixed. If you are stacking multiple bars, then turn this into an array and make sure your classes correspond with your percentages.
     * @param mixed  $display If $display !== false, then the percentage will be displayed in the progress bar.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->progress(60, 'info', 'display');
     * 
     * echo $bp->progress(array(25, 25, 25, 25), array('', 'warning', 'success', 'danger'));
     * ```
     */
    public function progress($percent, $class = '', $display = false)
    {
        $html = '';
        $classes = (array) $class;
        foreach ((array) $percent as $key => $progress) {
            $class = (isset($classes[$key])) ? $classes[$key] : '';
            $class = $this->prefixClasses('progress-bar', array('success', 'info', 'warning', 'danger', 'striped'), $class);
            $html .= $this->page->tag('div', array(
                'class' => $class,
                'style' => 'width:'.$progress.'%;',
                'role' => 'progressbar',
                'aria-valuenow' => $progress,
                'aria-valuemin' => 0,
                'aria-valuemax' => 100,
            ), $display !== false ? $progress.'%' : '<span class="sr-only">'.$progress.'% Complete</span>');
        }

        return '<div class="progress">'.$html.'</div>';
    }

    /**
     * This is the easiest way I could devise of making Bootstrap media objects as manageable as possible. ``<h1-6>`` Headers and ``<img>`` images will automatically be classed appropriately.
     * 
     * @param array $list A media array row that looks like this: ``array($left, $body, $right);``
     * 
     * - If you don't have an image or whatever for the left side, then set an empty value
     * - If you have nothing to right align then you can either leave it off, or set an empty value
     * - If you have a special class and / or id to assign, then you can include them in the array like so:
     *   - ``array('id' => $id, 'class' => 'custom', $left, $body, $right);``
     * - You can pack unlimited $list's (arguments) into this method, each $list being a sibling of the other:
     *   - ``$bp->media(array($left, $body, $right), array($left, $body, $right), array($left, $body, $right));``
     * - To nest media comments in a parent / child relationship, just add another media array row to the parent:
     *   - ``array($left, $body, $right, array($left, $body, $right, array($left, $body, $right)));`` - this would be a grandparent, parent, child setup
     *   - ``array($left, $body, $right, array($left, $body, $right), array($left, $body, $right));`` - a parent, child, child arrangement
     *   - ``array($left, $body, $right, array($left, $body, $right, array($left, $body, $right), array($left, $body, $right)), array($left, $body, $right)), array($left, $body, $right));`` - now I'm just messing with you, but I think you've got the picture (a parent, child, grandchild, grandchild, child, sibling)
     *   - This could go on ad infinitum, but soon your content will become pretty scrunched up if you take it too far.
     * 
     * @return string
     * 
     * ```php
     * $image = '<img alt="64x64" style="width: 64px; height: 64px;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAABWUlEQVR4nO2VMY7CMBBFuf9RfAMfwL17t67nCkM1aHBIASF5QvziSbtLGD0/T7Q3M/N/5kYL0CgALUCjALQAjQLQAjQKQAvQKAAtQKMAtACNAtACNApAC9AoAC1AowC0AI0C0AI0CkAL0HwlQCnlQe998/kYY/ezK2eeEqDW6q01NzNvrXkpxeecm2fekT1j5ikB5pxeSvExxu4zvfeNbHwvDtl7fxzy05lIgFjDkFnF82FW2bhZM3uKcWTm5QHi5kJiXddY5ZBeZfN7/q2ZyAbEDeXfX631KhuHy38/OvPSAOv7mmXzCmdi1eO78Vzc8JGZlwfIK5lvdC9Uvq1aq9daNz8fmYkEMHt+l9d/V69kY5XXW86HeXcmGuCXUQBagEYBaAEaBaAFaBSAFqBRAFqARgFoARoFoAVoFIAWoFEAWoBGAWgBGgWgBWgUgBagUQBagEYBaAGaO387LYipKEKVAAAAAElFTkSuQmCC">';
     * 
     * $content = '<h4>Media Heading</h4><p>Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis.</p>';
     * 
     * echo $bp->media(
     *     array('class'=>'special', $image, $content, $image, array($image, $content, array($image, $content, $image)), array('class'=>'content', $image, $content)), // parent, child, grandchild, child
     *     array($image, $content, $image) // sibling
     * );
     * ```
     */
    public function media(array $list, $parent = 0)
    {
        if (is_numeric($parent) && isset($list[0]) && is_array($list[0])) {
            $media = array();
            foreach ($list[$parent] as $child => $display) {
                if (isset($list[$child])) {
                    $display[] = $this->media($list, $child)[0];
                }
                $media[] = $display;
            }

            return ($parent === 0) ? call_user_func_array(array($this, 'media'), $media) : $media;
        } else {
            $html = '';
            $siblings = func_get_args();
            $parent = array_shift($siblings);
            $children = array();
            foreach ($parent as $key => $value) {
                if (is_array($value)) {
                    $children[] = $value;
                    unset($parent[$key]);
                }
            }
            $div = $parent;
            unset($parent['id'], $parent['class']);
            list($left, $body, $right) = array_pad($parent, 3, '');
            $media = '';
            if (!empty($left)) {
                $media .= '<div class="media-left">'.$this->addClass($left, array('img' => 'media-object')).'</div>';
            }
            $media .= '<div class="media-body">';
            if (!empty($body)) {
                $media .= $this->addClass($body, array('h([1-6]){1}' => 'media-heading'));
            }
            if (!empty($children)) {
                $media .= call_user_func_array(array($this, 'media'), $children);
            }
            $media .= '</div>';
            if (!empty($right)) {
                $media .= '<div class="media-right">'.$this->addClass($right, array('img' => 'media-object')).'</div>';
            }

            $html .= $this->page->tag('div', array(
                'id' => isset($div['id']) ? $div['id'] : '',
                'class' => isset($div['class']) ? 'media '.$div['class'] : 'media',
            ), $media);
            if (!empty($siblings)) {
                $html .= call_user_func_array(array($this, 'media'), $siblings);
            }

            return $html;
        }
    }

    /**
     * Displays a Bootstrap list group. ``<h1-6>`` Headers and ``<p>`` paragraphs will automatically be classed appropriately.
     * 
     * @param array $links  If you would like to create an unordered list, then this is just an array of values. Otherwise this will be an ``array($name => $href, ...)`` of links. $name badges will automatically be positioned on the right.
     * @param mixed $active This value can be either the $name, $href (link), or number (starting from 1) that you would like to be selected as "active".
     * 
     * @return string
     * 
     * ```php
     * $bp->list_group(array(
     *     'Unordered',
     *     'List',
     *     $bp->badge(1) . 'Group'
     * ));
     * 
     * $bp->list_group(array(
     *     'Anchor' => '#',
     *     'List' => '#',
     *     'Group' . $bp->badge(2) => '#'
     * ), 'Anchor');
     * 
     * $bp->list_group(array(
     *     '<h4>Custom Content</h4><p>Donec id elit non mi porta gravida.</p>' => '#',
     *     $bp->badge(3) . '<h4>Linked List Group</h4><p>Donec id elit non mi porta gravida.</p>' => '#'
     * ), 1);
     * ```
     */
    public function listGroup(array $links, $active = false)
    {
        $html = '';
        $tag = 'a';
        $count = 1;
        foreach ($links as $name => $href) {
            if (empty($html) && empty($name)) {
                $tag = 'li';
            }
            $attributes = array('class' => 'list-group-item');
            if ($tag == 'li') {
                $name = $href;
            } else {
                $attributes['href'] = $href;
                if (in_array($active, array($count, $name, $href))) {
                    $attributes['class'] .= ' active';
                }
            }
            $html .= $this->page->tag($tag, $attributes, $this->addClass($name, array(
                'h([1-6]){1}' => 'list-group-item-heading',
                'p' => 'list-group-item-text',
            )));
            ++$count;
        }

        return $this->page->tag($tag == 'a' ? 'div' : 'ul', array('class' => 'list-group'), $html);
    }

    /**
     * Creates a Bootstrap panel component.
     * 
     * @param string $class    Either '**default**', '**primary**', '**success**', '**info**', '**warning**', or '**danger**'. The panel class and prefix are automatically included. You can add more classes to it if you like.
     * @param array  $sections An ``array($panel => $content, ...)`` of sections. If $panel equals:
     * 
     * - '**head**', '**header**', or '**heading**' - The panel heading $content. All ``<h1-6>`` headers will be classed appropriately
     * - '**body**' - The panel body $content
     * - '**foot**', '**footer**', or '**footing**' - The panel footer $content
     * - Anything else will just be inserted as is. It could be a table, or list group, or ...
     * 
     * @return string
     * 
     * ```php
     * echo $bp->row('sm', array(
     *     $bp->col(2, $bp->panel('default', array('header'=>'Header', 'body'=>'Default', 'footer'=>'Footer'))),
     *     $bp->col(2, $bp->panel('primary', array('header'=>'Header', 'body'=>'Primary', 'footer'=>'Footer'))),
     *     $bp->col(2, $bp->panel('success', array('header'=>'Header', 'body'=>'Success', 'footer'=>'Footer'))),
     *     $bp->col(2, $bp->panel('info', array('header'=>'Header', 'body'=>'Info', 'footer'=>'Footer'))),
     *     $bp->col(2, $bp->panel('warning', array('header'=>'Header', 'body'=>'Warning', 'footer'=>'Footer'))),
     *     $bp->col(2, $bp->panel('danger', array('header'=>'Header', 'body'=>'Danger', 'footer'=>'Footer'))),
     * ));
     * ```
     */
    public function panel($class, array $sections)
    {
        $html = '';
        foreach ($sections as $panel => $content) {
            if (!is_numeric($panel)) {
                $panel = substr($panel, 0, 4);
            }
            switch ((string) $panel) {
                case 'head':
                    $html .= '<div class="panel-heading">'.$this->addClass($content, array('h([1-6]){1}' => 'panel-title')).'</div>';
                    break;
                case 'body':
                    $html .= '<div class="panel-body">'.$content.'</div>';
                    break;
                case 'foot':
                    $html .= '<div class="panel-footer">'.$content.'</div>';
                    break;
                default:
                    $html .= $content;
                    break; // a table, or list group, or ...
            }
        }

        return $this->page->tag('div', array(
            'class' => $this->prefixClasses('panel', array('default', 'primary', 'success', 'info', 'warning', 'danger'), $class),
        ), $html);
    }

    /**
     * This is for creating toggleable tabs and pills for transitioning through panes of local content.
     * 
     * @param string $type    Specify either '**tabs**' or '**pills**'.
     * @param array  $links   An ``array($name => $html, ...)`` of content to toggle through. If $html is an array unto itself, then it will be turned into a dropdown menu with the same header and divider rules applied as with ``$bp->buttons()``.
     * @param array  $options The available options are:
     * 
     * - '**fade**' - No key, just the value. Will give your panes a fade in effect while toggling.
     * - '**active**' and '**disabled**' => $name, $html (if you dare), or number (starting from 1)
     * - 'align' => '**justified**', '**left**', or '**right**'
     * 
     * @return string
     * 
     * ```php
     * echo $bp->toggle('tabs', array(
     *     'Toggle' => 'Raw denim you probably haven\'t heard of them jean shorts Austin. Nesciunt tofu stumptown aliqua, retro synth master cleanse. Mustache cliche tempor, williamsburg carles vegan helvetica. Reprehenderit butcher retro keffiyeh dreamcatcher synth. Cosby sweater eu banh mi, qui irure terry richardson ex squid. Aliquip placeat salvia cillum iphone. Seitan aliquip quis cardigan american apparel, butcher voluptate nisi qui.',
     *     'Profile' => 'Food truck fixie locavore, accusamus mcsweeney\'s marfa nulla single-origin coffee squid. Exercitation +1 labore velit, blog sartorial PBR leggings next level wes anderson artisan four loko farm-to-table craft beer twee. Qui photo booth letterpress, commodo enim craft beer mlkshk aliquip jean shorts ullamco ad vinyl cillum PBR. Homo nostrud organic, assumenda labore aesthetic magna delectus mollit. Keytar helvetica VHS salvia yr, vero magna velit sapiente labore stumptown. Vegan fanny pack odio cillum wes anderson 8-bit, sustainable jean shorts beard ut DIY ethical culpa terry richardson biodiesel. Art party scenester stumptown, tumblr butcher vero sint qui sapiente accusamus tattooed echo park.',
     *     'Dropdown' => array(
     *         'This' => 'Etsy mixtape wayfarers, ethical wes anderson tofu before they sold out mcsweeney\'s organic lomo retro fanny pack lo-fi farm-to-table readymade. Messenger bag gentrify pitchfork tattooed craft beer, iphone skateboard locavore carles etsy salvia banksy hoodie helvetica. DIY synth PBR banksy irony. Leggings gentrify squid 8-bit cred pitchfork. Williamsburg banh mi whatever gluten-free, carles pitchfork biodiesel fixie etsy retro mlkshk vice blog. Scenester cred you probably haven\'t heard of them, vinyl craft beer blog stumptown. Pitchfork sustainable tofu synth chambray yr.',
     *         'That' => 'Trust fund seitan letterpress, keytar raw denim keffiyeh etsy art party before they sold out master cleanse gluten-free squid scenester freegan cosby sweater. Fanny pack portland seitan DIY, art party locavore wolf cliche high life echo park Austin. Cred vinyl keffiyeh DIY salvia PBR, banh mi before they sold out farm-to-table VHS viral locavore cosby sweater. Lomo wolf viral, mustache readymade thundercats keffiyeh craft beer marfa ethical. Wolf salvia freegan, sartorial keffiyeh echo park vegan.'
     *     )
     * ), array('active'=>1, 'fade'));
     * ```
     */
    public function toggle($type, array $links, array $options = array())
    {
        $count = 1;
        $toggle = array();
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
                        $id = $this->page->id('tabs');
                    }
                    $toggle[$name][$drop] = '#'.$id;
                    if ($active == $drop || $active == $count) {
                        $options['active'] = '#'.$id;
                        $content .= '<div role="tabpanel" class="'.$class.' in active" id="'.$id.'">'.$down.'</div>';
                    } else {
                        if ($disabled == $drop || $disabled == $count) {
                            $options['disabled'] = '#'.$id;
                        }
                        $content .= '<div role="tabpanel" class="'.$class.'" id="'.$id.'">'.$down.'</div>';
                    }
                    ++$count;
                }
            } else { // $name (a tab) cannot be empty
                if ($frag = strpos($name, '#')) { // if it is the first character then it doesn't count
                    $id = substr($name, $frag + 1);
                    $name = substr($name, 0, $frag);
                } else {
                    $id = $this->page->id('tabs');
                }
                $toggle[$name] = '#'.$id;
                if ($active == $name || $active == $count) {
                    $options['active'] = '#'.$id;
                    $content .= '<div role="tabpanel" class="'.$class.' in active" id="'.$id.'">'.$html.'</div>';
                } else {
                    if ($disabled == $name || $disabled == $count) {
                        $options['disabled'] = '#'.$id;
                    }
                    $content .= '<div role="tabpanel" class="'.$class.'" id="'.$id.'">'.$html.'</div>';
                }
                ++$count;
            }
        }
        if (substr($type, 0, 4) == 'pill') {
            $options['toggle'] = 'pill';
            $class = 'nav nav-pills';
        } else { // tabs
            $options['toggle'] = 'tab';
            $class = 'nav nav-tabs';
        }
        if (isset($options['align']) && $options['align'] == 'justified') {
            $class .= ' nav-justified';
        }

        return '<ul class="'.$class.'" role="tablist">'.$this->links('li', $toggle, $options).'</ul><div class="tab-content">'.$content.'</div>';
    }

    /**
     * Bootstrap accordions are basically collapsible panels, so keep that in mind. That is essentially what you are creating here.
     * 
     * @param string $class    Either '**default**', '**primary**', '**success**', '**info**', '**warning**', or '**danger**'. These only apply to the head section, and are passed directly by us into ``$this->panel()``.
     * @param array  $sections An ``array($heading => $body, ...)`` of sections that will become your accordion. The ``<h1-6>`` headers in the $heading will be automatically classed appropriately. Accordions are definitely nestable, but we don't create them via nested arrays through this method. Just add a pre-made accordion to the $body you would like it to reside in ie. the $body should never be an array.
     * @param int    $open     This is the panel number you would like be open from the get-go (starting at 1). If you don't want any to be open initially, then set this to 0.
     * 
     * @return string
     * 
     * ```php
     * echo $bp->accordion('info', array(
     *     '<h4>Collapsible Group Item #1</h4>' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.',
     *     '<h4>Collapsible Group Item #2</h4>' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.',
     *     '<h4>Collapsible Group Item #3</h4>' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.'
     * ), 0);
     * ```
     */
    public function accordion($class, array $sections, $open = 1)
    {
        $html = '';
        $count = 0;
        $id = $this->page->id('accordion');
        foreach ($sections as $head => $body) {
            ++$count;
            $heading = $this->page->id('heading');
            $collapse = $this->page->id('collapse');
            $in = ($open == $count) ? ' in' : '';
            $attributes = array(
                'role' => 'button',
                'data-toggle' => 'collapse',
                'data-parent' => '#'.$id,
                'href' => '#'.$collapse,
                'aria-expanded' => !empty($in) ? 'true' : 'false',
                'aria-controls' => $collapse,
            );
            $begin = strpos($head, '>') + 1;
            $end = strrpos($head, '</');
            $head = substr($head, 0, $begin).$this->page->tag('a', $attributes, substr($head, $begin, $end - $begin)).substr($head, $end);
            $head = substr($this->panel($class, array('head' => $head)), 0, -6); // </div>
            $html .= substr_replace($head, ' role="tab" id="'.$heading.'"', strpos($head, 'class="panel-heading"') + 21, 0);
            $html .= $this->page->tag('div', array(
                    'id' => $collapse,
                    'class' => 'panel-collapse collapse'.$in,
                    'role' => 'tabpanel',
                    'aria-labelledby' => $heading,
                ), strpos($body, 'class="list-group"') ? $body : '<div class="panel-body">'.$body.'</div>');
            $html .= '</div>'; // the one we removed from the $head up top
        }

        return $this->page->tag('div', array(
            'class' => 'panel-group',
            'id' => $id,
            'role' => 'tablist',
            'aria-multiselectable' => 'true',
        ), $html);
    }

    /**
     * Creates a Bootstrap carousel for cycling through elements. Those elements don't necessarily need to be images, but pretty much they always are.
     * 
     * @param array $images  An ``array($image, ...)`` of images to cycle through, starting with the first (logically). To get fancy and add captions, then make this an ``array($image => $caption, ...)`` of images with captions to cycle through. If you have some images with captions and others without, then you can merge these two concepts no problem. Remember, the $image is not just a location, it is the entire ``<img>`` tag src and all.
     * @param array $options The available option keys are:
     * 
     * - '**interval**' - The time delay in thousandths of a second between cycles (or frame changes). The default is 5000 ie. 5 seconds.
     * - '**indicators**' - Those little circle things at the bottom to give you an idea of where you are at. If you don't want them, then set this to false. The default is true ie. include them.
     * - '**controls**' - The clickable arrows on the side that you can click to get to what you are interested in. If you don't want them, then set this to false. The default is true ie. include them. Also by default we use ``array($bp->icon('chevron-left'), $bp->icon('chevron-right'))`` for the left and right arrows. If you would like something else, then you can make this an array of your preferences.
     * 
     * @return string
     */
    public function carousel(array $images, array $options = array())
    {
        $html = '';
        $id = $this->page->id('carousel');
        $options = array_merge(array(
            'interval' => 5000, // ie. 5 seconds in between frame changes
            'indicators' => true, // set to false if you don't want them
            'controls' => true, // set to false if you don't want them
        ), $options);
        if ($options['indicators']) {
            $indicators = array_keys(array_values($images));
            $html .= '<ol class="carousel-indicators">';
            $html .= '<li data-target="#'.$id.'" data-slide-to="'.array_shift($indicators).'" class="active"></li>';
            foreach ($indicators as $num) {
                $html .= '<li data-target="#'.$id.'" data-slide-to="'.$num.'"></li>';
            }
            $html .= '</ol>';
        }
        $html .= '<div class="carousel-inner" role="listbox">';
        foreach ($images as $key => $value) {
            $class = (isset($class)) ? 'item' : 'item active'; // ie. the first one is active
            $img = (!is_numeric($key)) ? $key : $value;
            $caption = (!is_numeric($key)) ? '<div class="carousel-caption">'.$value.'</div>' : '';
            $html .= '<div class="'.$class.'">'.$img.$caption.'</div>';
        }
        $html .= '</div>';
        if ($options['controls']) {
            if (is_array($options['controls'])) {
                list($left, $right) = $options['controls'];
                if (strpos($left, '<') === false) {
                    $left = $this->icon($left, 'glyphicon', 'span aria-hidden="true"');
                }
                if (strpos($right, '<') === false) {
                    $right = $this->icon($right, 'glyphicon', 'span aria-hidden="true"');
                }
            } else {
                $left = $this->icon('chevron-left', 'glyphicon', 'span aria-hidden="true"');
                $right = $this->icon('chevron-right', 'glyphicon', 'span aria-hidden="true"');
            }
            $html .= $this->page->tag('a', array(
                'class' => 'left carousel-control',
                'href' => '#'.$id,
                'role' => 'button',
                'data-slide' => 'prev',
            ), $left, '<span class="sr-only">Previous</span>');
            $html .= $this->page->tag('a', array(
                'class' => 'right carousel-control',
                'href' => '#'.$id,
                'role' => 'button',
                'data-slide' => 'next',
            ), $right, '<span class="sr-only">Next</span>');
        }

        return $this->page->tag('div', array(
            'id' => $id,
            'class' => 'carousel slide',
            'data-ride' => 'carousel',
            'data-interval' => $options['interval'],
        ), $html);
    }

    protected function dropdown(array $links, array $options = array(), &$count = 1)
    {
        $html = '';
        $toggle = (isset($options['toggle'])) ? ' data-toggle="'.$options['toggle'].'"' : '';
        foreach ($links as $name => $href) {
            if (empty($href)) {
                $html .= '<li role="presentation" class="divider"></li>';
            } elseif (is_numeric($name)) {
                $html .= '<li role="presentation" class="dropdown-header">'.$href.'</li>';
            } else {
                $link = '<a role="menuitem" tabindex="-1" href="'.$href.'"'.$toggle.'>'.$name.'</a>';
                $html .= $this->listItem($link, $options, $name, $href, $count);
                ++$count;
            }
        }
        $class = 'dropdown-menu';
        if (isset($options['pull'])) {
            $class .= ' dropdown-menu-'.$options['pull'];
        }
        $id = $this->page->id('dropdown');
        $html = $this->page->tag('ul', array(
            'class' => $class,
            'aria-labelledby' => $id,
        ), $html);

        return array($html, $id);
    }

    protected function listItem($link, $options, $name, $href, $count)
    {
        $name = trim(preg_replace('/(\<[^\<]+\<\/[^\>]+\>)/i', '', $name)); // remove tags and their contents
        $check = array_flip(array($name, $href, $count));
        if (isset($options['active']) && isset($check[$options['active']])) {
            return '<li role="presentation" class="active">'.$link.'</li>';
        } elseif (isset($options['disabled']) && isset($check[$options['disabled']])) {
            return '<li role="presentation" class="disabled">'.$link.'</li>';
        } else {
            return '<li role="presentation">'.$link.'</li>';
        }
    }
}
