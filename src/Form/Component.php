<?php

namespace BootPress\Form;

use BootPress\Page\Component as Page;
use BootPress\Validator\Component as Validator;

class Component
{
    /**
     * @var \BootPress\Validator\Component 
     */
    public $validator;
    
    /**
     * If a form is submitted successfully then you should ``$page->eject()`` them using this value.
     * 
     * @var string
     */
    public $eject = '';
    
    /**
     * You should set this ``array($field => $value)`` to all of the default values for the form you are going to create.  Once the form has been submitted and passed validation, then these will be all of your filtered and validated values.
     * 
     * @var string[]
     */
    public $values = array();
    
    /**
     * An array of attributes and their values that will be included in the opening ``<form>`` tag.
     * 
     * @var string[]
     */
    public $header = array();
    
    /**
     * Any additional html that you want to be included just before the ``</form>`` tag.
     * 
     * @var string[]
     */
    public $footer = array();
    
    /**
     * All of your hidden form inputs that we put after ``$this->footer``.
     * 
     * @var string[]
     */
    public $hidden = array();
    
    /**
     * Used by select menus to prepend a default value at the beginning eg. &nbsp;
     * 
     * @var array
     */
    protected $prepend = array();
    
    /**
     * Stores all of the values you submitted in ``$this->menu()`` for radio, checkbox, and select menus.
     * 
     * @var array
     */
    protected $menus = array();
    
    /**
     * @var \BootPress\Page\Component 
     */
    protected $page;

    /**
     * Creates a BootPress\Form\Component object.
     * 
     * @param string $name    The name of your form.
     * @param string $method  How you would like the form to be sent ie. '**post**' or '**get**'
     */
    public function __construct($name = 'form', $method = 'post')
    {
        $this->page = Page::html();
        $headers = (is_array($name)) ? $name : array('name' => $name, 'method' => $method);
        $this->header['name'] = (isset($headers['name'])) ? $headers['name'] : 'form';
        if (isset($headers['method']) && strtolower($headers['method']) == 'get') {
            $this->header['method'] = 'get';
            $this->header['action'] = (isset($headers['action'])) ? $headers['action'] : $this->page->url();
            $this->eject = $this->header['action'];
            $values = (strpos($this->header['action'], $this->page->url()) === 0) ? $this->page->request->query->all() : array();
        } else {
            $this->header['method'] = 'post';
            $this->header['action'] = $this->page->url('add', '', 'submitted', $name);
            $this->eject = $this->page->url('delete', $this->header['action'], 'submitted');
            $values = (strpos($this->header['action'], $this->page->url()) === 0) ? $this->page->request->request->all() : array();
        }
        $this->header['accept-charset'] = $this->page->charset;
        $this->header['autocomplete'] = 'off';
        $this->header = array_merge($this->header, $headers);
        $this->validator = new Validator($values);
    }

    /**
     * This establishes the options for a checkbox, radio, or select menu field.
     * 
     * @param string $field    The name of the field.
     * @param array  $menu     An ``array($value => $options), ...)`` of options to display in the menu.
     * @param string $prepend  An optional non-value to prepend to the menu eg. '&nbsp;'.  This is used for select menus when you would like a blank option up top.
     * 
     * @return string  A comma-separated list of values from your menu that is useful for using inList Validation.
     * 
     * ```php
     * $form->menu('save[]', array(
     *     4 => 'John Locke',
     *     8 => 'Hugo Reyes',
     *     15 => 'James Ford',
     *     16 => 'Sayid Jarrah',
     *     23 => 'Jack Shephard',
     *     42 => 'Jin & Sun Kwon'
     * )); // A multiselect menu
     * 
     * $form->menu('transport', array(1=>'Airplane', 2=>'Boat', 3=>'Submarine'), '&nbsp;'); // A select menu
     * 
     * $form->menu('vehicle', array(
     *     'hier' => 'transport',
     *     1 => array('Boeing'=>array(4=>'777', 5=>'737'), 'Lockheed'=>array(6=>'L-1011', 7=>'HC-130'), 8=>'Douglas DC-3', 9=>'Beechcraft'),
     *     2 => array(11=>'Black Rock', 12=>'Kahana', 13=>'Elizabeth', 14=>'Searcher'),
     *     3 => array(15=>'Galaga', '16'=>'Yushio')
     * ), '&nbsp;'); // A hierselect menu
     * 
     * $gender = $form->menu('gender', array('M'=>'Male', 'F'=>'Female')); // A radio menu
     * $form->validator->set('gender', "required|inList[{$gender}]");
     * 
     * $form->menu('remember', array('Y'=>'Remember Me')); // A checkbox
     * ```
     */
    public function menu($field, array $menu = array(), $prepend = null)
    {
        $args = func_get_args();
        $field = array_shift($args);
        if (empty($args)) {
            return (isset($this->menus[$field])) ? $this->menus[$field] : array();
        }
        $this->menus[$field] = array_shift($args);
        if (!empty($args)) {
            $this->prepend[$field] = array_shift($args);
        }

        return implode(',', array_keys($this->flatten($this->menus[$field])));
    }

    /**
     * This will begin the form with all of the attributes you have established in ``$this->headers`` array.  The values we have already set (but may be overridden) are:
     *
     * - '**name**' - The name of your form.
     * - '**method**' - Either 'get' or 'post'.
     * - '**action**' - If 'post' then the current page with a 'submitted' query parameter added.  If 'get' then the current page with all it's query parameters moved to hidden input fields.
     * - '**accept-charset**' - ``$this->page->charset``
     * - '**autocomplete**' - Set to 'off'.
     * 
     * If you add a numeric (megabytes) 'upload' field then then we convert the megabytes to bytes, add the enctype="multipart/form-data" to the header, and set a 'MAX_FILE_SIZE' hidden input with the number of bytes allowed.
     * 
     * @return string  The opening ``<form>`` tag.
     * 
     * ```php
     * echo $form->header();
     * ```
     */
    public function header()
    {
        if (isset($this->header['upload']) && is_numeric($this->header['upload'])) {
            $this->header['enctype'] = 'multipart/form-data';
            if ($this->header['upload'] <= 100) {
                $this->header['upload'] *= 1048576; // megabytes to bytes
            }
            $this->hidden['MAX_FILE_SIZE'] = $this->header['upload'];
            unset($this->header['upload']);
        }
        if ($this->header['method'] == 'get') {
            $params = $this->page->url('params', $this->header['action']);
            foreach ($params as $key => $value) {
                $this->hidden[$key] = $value;
            }
            $this->header['action'] = $this->page->url('delete', $this->header['action'], '?');
        }

        return "\n".$this->page->tag('form', $this->header);
    }

    /**
     * This will wrap a ``<fieldset>`` around the included $html, and place a nice ``<legend>`` up top.  This is not very difficult to do by hand, but it does look nice with all of the $html ``$form->field()``'s nicely indented and looking like they belong where they are.
     * 
     * @param string $legend  The fieldset's legend value.
     * @param string $html    The html you would like this fieldset to enclose (if any).  These args can go on forever, and they are all included as additional $html (strings) to place in the fieldset just after the legend.  If this is an array then we ``implode('', $html)`` and include that.
     * 
     * @return string
     * 
     * ```php
     * echo $form->fieldset('Sign In',
     *     $form->field('text', 'username'),
     *     $form->field('password', 'password')
     * );
     * ```
     */
    public function fieldset($legend, $html = '')
    {
        $args = func_get_args();
        $legend = array_shift($args);
        $html = array_shift($args);
        if (is_array($html)) {
            $html = implode('', $html);
        }
        if (!empty($args)) {
            $html .= implode('', $args);
        }

        return "\n<fieldset><legend>{$legend}</legend>{$html}\n</fieldset>";
    }

    /**
     * Retrieves an input's default value to display using the Validator::value method.  This is used internally when creating form fields using this class.
     * 
     * @param string      $field   The input's name.
     * @param false|mixed $escape  If set to anything but false, then we run the value(s) through ``htmlspecialchars``.
     * 
     * @return array|string  The field's default value.
     */
    public function defaultValue($field, $escape = false)
    {
        if (null === $value = $this->validator->value($field)) {
            $value = (isset($this->values[$field])) ? $this->values[$field] : '';
        }
        if ($escape === false) {
            return $value;
        }

        return (is_array($value)) ? array_map('htmlspecialchars', $value) : htmlspecialchars($value);
    }

    /**
     * This adds the jQuery Validation rules and messages set earlier to the input field's submitted attributes.  This is used internally when creating form fields using this class.
     * 
     * @param string $field       The input's name.
     * @param array  $attributes  The currently constituted attributes.
     * 
     * @return array  The submitted attributes with the data rules and messages applied.
     * 
     * @see http://johnnycode.com/2014/03/27/using-jquery-validate-plugin-html5-data-attribute-rules/
     * 
     * ```php
     * $form->validator->set('field', array('required' => 'Do this or else.'));
     * $attributes = $form->validate('field', array('name' => 'field'));
     * ```
     */
    public function validate($field, array $attributes = array())
    {
        foreach ($this->validator->rules($field) as $validate => $param) {
            $attributes["data-rule-{$validate}"] = htmlspecialchars($param);
        }
        foreach ($this->validator->messages($field) as $rule => $message) {
            $attributes["data-msg-{$rule}"] = htmlspecialchars($message);
        }

        return $attributes;
    }

    /**
     * Creates an input field from an array of attributes.  This is used internally when creating form fields using this class.
     * 
     * @param string   $type        The type of input.
     * @param string[] $attributes  The input's other attributes.
     * 
     * @return string  An html input tag.
     * 
     * ```php
     * $form->footer[] = $form->input('submit', array('name' => 'Submit'));
     * 
     * echo $form->input('hidden', array('name' => 'field', 'value' => 'default'));
     * ```
     */
    public function input($type, array $attributes)
    {
        unset($attributes['type']);

        return $this->page->tag('input type="'.$type.'"', $attributes);
    }

    /**
     * Creates a text input field.
     * 
     * @param string   $field       The text input's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'id', 'value', and data validation attributes.
     * 
     * @return string  An ``<input type="text" ...>`` html tag.
     * 
     * ```php
     * $form->validator->set('name', 'required');
     * $form->validator->set('email', 'required|email');
     * 
     * echo $form->field('name');
     * echo $form->field('email');
     * ```
     */
    public function text($field, array $attributes = array())
    {
        $attributes['name'] = $field;
        $attributes['id'] = $this->validator->id($field);
        $attributes['value'] = $this->defaultValue($field, 'escape');

        return $this->input('text', $this->validate($field, $attributes));
    }

    /**
     * Creates a password input field.
     * 
     * @param string   $field       The password input's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'id', 'value', and data validation attributes.
     * 
     * @return string  An ``<input type="password" ...>`` html tag.
     * 
     * ```php
     * $form->validator->set('password', 'required|alphaNumeric|minLength[5]|noWhiteSpace');
     * $form->validator->set('confirm', 'required|matches[password]');
     * 
     * echo $form->field('password');
     * echo $form->field('confirm');
     * ```
     */
    public function password($field, array $attributes = array())
    {
        $attributes['name'] = $field;
        $attributes['id'] = $this->validator->id($field);
        unset($attributes['value']);

        return $this->input('password', $this->validate($field, $attributes));
    }

    /**
     * Creates checkboxes from the ``$form->menu($field)`` you set earlier.
     * 
     * @param string   $field       The checkbox's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'value', 'checked', and data validation attributes.
     * @param string   $wrap        The html that surrounds each checkbox.
     * 
     * @return string  A checkbox ``<label><input type="checkbox" ...></label>`` html tag.
     * 
     * ```php
     * $form->menu('remember', array('Y'=>'Remember Me'));
     * 
     * echo $form->checkbox('remember');
     * ```
     */
    public function checkbox($field, array $attributes = array(), $wrap = '<label>%s</label>')
    {
        $boxes = array();
        $checked = (array) $this->defaultValue($field);
        foreach ($this->menu($field) as $value => $description) {
            $attributes['name'] = $field;
            $attributes['value'] = $value;
            unset($attributes['checked']);
            if (in_array($value, $checked)) {
                $attributes['checked'] = 'checked';
            }
            if (empty($boxes)) {
                $boxes[] = $this->input('checkbox', $this->validate($field, $attributes)).' '.$description;
            } else {
                $boxes[] = $this->input('checkbox', $attributes).' '.$description;
            }
        }
        if (is_array($wrap)) {
            return $boxes;
        }
        if (!empty($wrap)) {
            foreach ($boxes as $key => $value) {
                $boxes[$key] = sprintf($wrap, $value);
            }
        }

        return implode(' ', $boxes);
    }

    /**
     * Creates radio buttons from the ``$form->menu($field)`` you set earlier.
     * 
     * @param string   $field       The radio button's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'value', 'checked', and data validation attributes.
     * @param string   $wrap        The html that surrounds each radio button.
     * 
     * @return string  Radio ``<label><input type="radio" ...></label>`` html tags.
     * 
     * ```php
     * $gender = $form->menu('gender', array('M'=>'Male', 'F'=>'Female')); // A radio menu
     * $form->validator->set('gender', "required|inList[{$gender}]");
     * 
     * echo $form->radio('gender');
     * ```
     */
    public function radio($field, array $attributes = array(), $wrap = '<label>%s</label>')
    {
        $radios = array();
        $checked = (array) $this->defaultValue($field);
        foreach ($this->menu($field) as $value => $description) {
            $attributes['name'] = $field;
            $attributes['value'] = $value;
            unset($attributes['checked']);
            if (in_array($value, $checked)) {
                $attributes['checked'] = 'checked';
            }
            if (empty($radios)) {
                $radios[] = $this->input('radio', $this->validate($field, $attributes)).' '.$description;
            } else {
                $radios[] = $this->input('radio', $attributes).' '.$description;
            }
        }
        if (is_array($wrap)) {
            return $radios;
        }
        if (!empty($wrap)) {
            foreach ($radios as $key => $value) {
                $radios[$key] = sprintf($wrap, $value);
            }
        }

        return implode(' ', $radios);
    }

    /**
     * Creates a select menu from the ``$form->menu($field)`` you set earlier.
     * 
     * If the $field is an array (identified by '[]' at the end), then this will be a multiple select menu unless you set ``$attributes['multiple'] = false``.  You can optionally include a 'size' attribute to override our sensible defaults.
     *
     * You can get fairly fancy with these creating optgroups and hier menus.  We'll let the examples speak for themselves.
     * 
     * @param string   $field       The select menu's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'id', and data validation attributes.
     * 
     * @return string  A ``<select ...>`` tag with all it's ``<option>``'s.
     * 
     * ```php
     * $save = $form->menu('save[]', array(
     *     4 => 'John Locke',
     *     8 => 'Hugo Reyes',
     *     15 => 'James Ford',
     *     16 => 'Sayid Jarrah',
     *     23 => 'Jack Shephard',
     *     42 => 'Jin & Sun Kwon'
     * )); // A multiselect menu
     * 
     * $form->menu('transport', array(1=>'Airplane', 2=>'Boat', 3=>'Submarine'), '&nbsp;'); // A select menu
     * 
     * $vehicles = $form->menu('vehicle', array(
     *     'hier' => 'transport',
     *     1 => array('Boeing'=>array(4=>'777', 5=>'737'), 'Lockheed'=>array(6=>'L-1011', 7=>'HC-130'), 8=>'Douglas DC-3', 9=>'Beechcraft'),
     *     2 => array(11=>'Black Rock', 12=>'Kahana', 13=>'Elizabeth', 14=>'Searcher'),
     *     3 => array(15=>'Galaga', '16'=>'Yushio')
     * ), '&nbsp;'); // A hierselect menu
     * 
     * $form->validator->set(array(
     *     'save' => 'required|inList[{$save}]|minLength[2]',
     *     'vehicle' => "required|inList[{$vehicles}]",
     * ));
     * 
     * echo $form->fieldset('LOST',
     *     $form->select('save[]'),
     *     $form->select('transport'),
     *     $form->select('vehicle')
     * );
     * ```
     */
    public function select($field, array $attributes = array())
    {
        $select = $this->menu($field);
        $attributes['name'] = $field;
        $attributes['id'] = $this->validator->id($field);
        if (strpos($field, '[]') !== false) {
            if (isset($attributes['multiple']) && $attributes['multiple'] === false) {
                unset($attributes['multiple'], $attributes['size']);
            } else {
                $attributes['multiple'] = 'multiple';
                $max = (isset($attributes['size'])) ? $attributes['size'] : 15;
                $attributes['size'] = min(count($this->flatten($select)), $max);
            }
        }
        if (isset($select['hier']) && !isset($attributes['multiple'])) {
            $hier = $select['hier'];
            $selected = $this->defaultValue($select['hier']);
            unset($select['hier']);
            $json = $select;
            if (isset($this->prepend[$field])) {
                foreach ($json as $key => $value) {
                    array_unshift($json[$key], $this->prepend[$field]);
                }
            }
            $this->page->jquery('$("#'.$this->validator->id($hier).'").hierSelect("#'.$this->validator->id($field).'", '.json_encode($json).');');
            $this->page->script('
                (function($) {
                    $.fn.hierSelect = function(select, options) {
                        $(this).change(function() {
                            var id = $(this).val();
                            var hier = $(select);
                            var preselect = hier.val();
                            hier.each(function(){
                                hier.children().remove();
                                if (id != "") {
                                    $.each(options[id], function(key,value){
                                        if (typeof value === "object") {
                                            var optgroup = $("<optgroup />", {label:key});
                                            $.each(value, function(key,value){
                                                if (key == 0) key = "";
                                                var option = $("<option />").val(key).html(value);
                                                if (preselect == key) option.attr("selected", "selected");
                                                optgroup.append(option);
                                            });
                                            hier.append(optgroup);
                                        } else {
                                            if (key == 0) key = "";
                                            var option = $("<option />").val(key).html(value);
                                            if (preselect == key) option.attr("selected", "selected");
                                            hier.append(option);
                                        }
                                    });
                                } // end if id
                            }); // end each hier
                        }); // end this change
                    };
                })(jQuery);
            ');
            $select = (isset($select[$selected])) ? $select[$selected] : array();
        }
        $values = '';
        if (!empty($select)) {
            $selected = (array) $this->defaultValue($field);
            if (isset($this->prepend[$field])) {
                $values .= '<option value="">'.$this->prepend[$field].'</option>';
            }
            foreach ($select as $key => $value) {
                if (is_array($value)) {
                    $values .= '<optgroup label="'.htmlspecialchars($key).'">';
                    foreach ($value as $optgroup_key => $optgroup_value) {
                        $values .= '<option value="'.$optgroup_key.'"';
                        if (in_array($optgroup_key, $selected)) {
                            $values .= ' selected="selected"';
                        }
                        $values .= '>'.$optgroup_value.'</option>';
                    }
                    $values .= '</optgroup>';
                } else {
                    $values .= '<option value="'.$key.'"';
                    if (in_array($key, $selected)) {
                        $values .= ' selected="selected"';
                    }
                    $values .= '>'.$value.'</option>';
                }
            }
        }
        
        return $this->page->tag('select', $this->validate($field, $attributes), $values);
    }

    /**
     * Creates a textarea field.
     * 
     * @param string   $field       The textarea's name.
     * @param string[] $attributes  Anything else you would like to add besides the 'name', 'id', and data validation attributes.  If you don't set the 'cols' and 'rows' then we will.
     * 
     * @return string  A ``<textarea ...>`` html tag.
     * 
     * ```php
     * $form->values['description'] = '"default"';
     * 
     * echo $form->textarea('description');
     * ```
     */
    public function textarea($field, array $attributes = array())
    {
        $attributes['name'] = $field;
        $attributes['id'] = $this->validator->id($field);
        if (!isset($attributes['cols'])) {
            $attributes['cols'] = 40;
        }
        if (!isset($attributes['rows'])) {
            $attributes['rows'] = 10;
        }

        return $this->page->tag('textarea', $this->validate($field, $attributes), $this->defaultValue($field, 'escape'));
    }

    /**
     * Closes and cleans up shop.
     * 
     * @return string  The closing ``</form>`` tag with ``$this->footer`` and ``$this->hidden`` form fields preceding it.
     * 
     * ```php
     * echo $form->close();
     * ```
     */
    public function close()
    {
        $html = implode('', $this->footer);
        foreach ($this->hidden as $key => $value) {
            $html .= "\n\t".$this->input('hidden', array(
                'name' => $key,
                'value' => htmlspecialchars((string) $value),
            ));
        }

        return $html."\n</form>";
    }

    /**
     * This is used with menus for getting to the bottom of multi-dimensional arrays, and determining it's root keys and values
     * 
     * @param array $array 
     * 
     * @return array  A single-dimensional ``array($key => $value, ...)``'s.
     */
    private function flatten(array $array)
    {
        $single = array();
        if (isset($array['hier'])) {
            unset($array['hier']);
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach ($this->flatten($value) as $key => $value) {
                    $single[$key] = $value;
                }
            } else {
                $single[$key] = $value;
            }
        }

        return $single;
    }
}
