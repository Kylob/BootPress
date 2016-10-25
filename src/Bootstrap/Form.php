<?php

namespace BootPress\Bootstrap;

class Form extends \BootPress\Form\Component
{
    use Base;

    private $bp;
    private $prompt = array(
        'info' => 'glyphicon glyphicon-question-sign',
    );
    private $input = '';
    private $align = 'form-horizontal';
    private $collapse = 'sm';
    private $indent = 2;

    /**
     * {@inheritdoc}
     */
    public function __construct($name, $method, Common $bp)
    {
        parent::__construct($name, $method);
        $this->bp = $bp;
    }

    /**
     * A private property getter.
     * 
     * @param string $name
     * 
     * @return null|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'prompt':
            case 'input':
            case 'align':
            case 'collapse':
            case 'indent':
                return $this->$name;
                break;
        }
    }

    /**
     * Use to display a message on the retrun trip after you have ``$page->eject()``ed them.  The Bootstrap alert status message will be displayed at the top of the form when you return ``$form->header()``.
     * 
     * @param string $status  Either '**success**', '**info**', '**warning**', or '**danger**'.  If this is '**html**', then the $message will be delivered as is.
     * @param string $message The message you would like to get across to your user.  ``<h1-6>`` headers and ``<a>`` links may be used.
     * 
     * ```php
     * if ($vars = $form->validator->certified()) {
     *     $form->message('success', 'Good job, you are doing great!');
     *     $page->eject($form->eject);
     * }
     * ```
     */
    public function message($status, $message)
    {
        $this->page->session->getFlashBag()->add($this->header['name'], array(
            'status' => $status,
            'msg' => $message,
        ));
    }

    /**
     * This is to add html tags, or semicolons, or asterisks, or whatever you would like to all of the form's prompts.
     * 
     * @param string      $place    Either '**info**', '**append**', or '**prepend**' to the prompt.  You only have one shot at each.
     * @param string      $html     Whatever you would like to add.  For '**info**', this will be the icon class you want to use.
     * @param false|mixed $required If $place == 'prepend' and this is anything but (bool) false, then the $html will only be prepended if the field is required per the ``$form->validator``.
     * 
     * ```php
     * $form->prompt('prepend', '<font color="red">*</font> ', 'required'); // If the field is required it will add a red asterisk to the front.
     * 
     * $form->prompt('append', ':'); // Adds a semicolon to all of the prompts.
     * ```
     */
    public function prompt($place, $html, $required = false)
    {
        switch ($place) {
            case 'info':
            case 'append':
                $this->prompt[$place] = $html;
                break;
            case 'prepend':
                $this->prompt['prepend'] = array('html' => $html, 'required' => (bool) $required);
                break;
        }
    }

    /**
     * Supersize or undersize your input fields.
     * 
     * @param string $input Either '**lg**' (large), '**md**' (medium - the default), or '**sm**' (small).
     * 
     * ```php
     * $form->size('lg');
     * ```
     */
    public function size($input)
    {
        $this->input = (in_array($input, array('lg', 'sm'))) ? 'input-'.$input : '';
    }

    /**
     * Utilize any Bootstrap form style.
     * 
     * @param string $direction The options are:
     *
     * - '**collapse**' - This will display the form prompt immediately above the field.
     * - '**inline**' - All of the fields will be inline with each other, and the form prompts will be removed.
     * - '**horizontal**' - Vertically aligns all of the fields with the prompt immediately preceding, and right aligned.
     *
     * @param string $collapse Either '**xs**', '**sm**', '**md**', or '**lg**'.  This is the breaking point so to speak for a '**horizontal**' form.  It is the device size on which the form will '**collapse**'.
     * @param int    $indent   The number of columns (up to 12) that you would like to indent the field in a '**horizontal**' form.
     * 
     * ```php
     * $form->align('collapse');
     * ```
     */
    public function align($direction = 'horizontal', $collapse = 'sm', $indent = 2)
    {
        if ($direction == 'collapse') {
            $this->align = '';
        } elseif ($direction == 'inline') {
            $this->align = 'form-inline';
        } else {
            $this->align = 'form-horizontal';
            $this->collapse = (in_array($collapse, array('xs', 'sm', 'md', 'lg'))) ? $collapse : 'sm';
            $this->indent = (is_numeric($indent) && $indent > 0 && $indent < 12) ? $indent : 2;
        }
    }

    /**
     * Creates the ``<form>``, invokes the Validator jQuery, and displays your message (if any).
     *
     * @param array $validate Override the custom validator settings we have created for Bootstrap.
     *
     * @return string
     */
    public function header(array $validate = array())
    {
        $this->validator->jquery('form[name='.$this->header['name'].']', array_merge(array(
            'ignore' => '[]',
            'errorClass' => '"has-error"',
            'validClass' => '""',
            'errorElement' => '"span"',
            'highlight' => 'function(element, errorClass, validClass){ $(element).closest("div.form-group").addClass(errorClass).removeClass(validClass).find("p.validation").show(); }',
            'unhighlight' => 'function(element, errorClass, validClass){ $(element).closest("div.form-group").removeClass(errorClass).addClass(validClass).find("p.validation").text("").hide(); }',
            'errorPlacement' => 'function(error, element){ $(element).closest("div.form-group").find("p.validation").html(error); }',
            'submitHandler' => 'function(form, event){ event.preventDefault(); $(form).find("button[type=submit]").button("loading"); form.submit(); }',
            'onkeyup' => 'false',
        ), $validate));
        $html = "\n";
        if ($this->align == 'form-horizontal') {
            $html .= '<div class="row"><div class="col-'.$this->collapse.'-12">';
        }
        if ($flash = $this->page->session->getFlashBag()->get($this->header['name'])) {
            $html .= ($flash[0]['status'] == 'html') ? $flash[0]['msg'] : $this->bp->alert($flash[0]['status'], $flash[0]['msg']);
        }
        $html .= trim(parent::header());
        if (!empty($this->align)) {
            $html = $this->addClass($html, array('form' => $this->align));
        }

        return $html;
    }

    /**
     * Creates checkboxes from the ``$form->menu($field)`` you set earlier.
     * 
     * @param string      $field      The checkbox's name.
     * @param string[]    $attributes Anything else you would like to add besides the 'name', 'value', 'checked', and data validation attributes.
     * @param false|mixed $inline     This tells us if you want the checkboxes to be inline (any value but false), or not (false).
     * 
     * @return string A checkbox ``<label><input type="checkbox" ...></label>`` html tag.
     * 
     * ```php
     * $form->menu('remember', array('Y'=>'Remember Me'));
     * 
     * echo $form->checkbox('remember');
     * ```
     */
    public function checkbox($field, array $attributes = array(), $inline = false)
    {
        $disabled = in_array('disabled', $attributes) ? 'disabled' : '';
        if ($inline !== false) {
            $wrap = $this->page->tag('label', array('class' => array('checkbox-inline', $this->input, $disabled)), '%s');
        } else {
            $wrap = $this->page->tag('div', array('class' => array('checkbox', $this->input, $disabled)), '<label>%s</label>');
        }

        return parent::checkbox($field, $attributes, $wrap);
    }

    /**
     * Creates radio buttons from the ``$form->menu($field)`` you set earlier.
     * 
     * @param string      $field      The radio button's name.
     * @param string[]    $attributes Anything else you would like to add besides the 'name', 'value', 'checked', and data validation attributes.
     * @param false|mixed $inline     This tells us if you want the radio buttons to be inline (any value but false), or not (false).
     * 
     * @return string Radio ``<label><input type="radio" ...></label>`` html tags.
     * 
     * ```php
     * $gender = $form->menu('gender', array('M'=>'Male', 'F'=>'Female')); // A radio menu
     * $form->validator->set('gender', "required|inList[{$gender}]");
     * 
     * echo $form->radio('gender');
     * ```
     */
    public function radio($field, array $attributes = array(), $inline = false)
    {
        $disabled = in_array('disabled', $attributes) ? 'disabled' : '';
        if ($inline !== false) {
            $wrap = $this->page->tag('label', array('class' => array('radio-inline', $this->input, $disabled)), '%s');
        } else {
            $wrap = $this->page->tag('div', array('class' => array('radio', $this->input, $disabled)), '<label>%s</label>');
        }

        return parent::radio($field, $attributes, $wrap);
    }

    /**
     * Group an input field with addons.  You can prepend and/or append a ``$bp->button(...)``, ``$bp->icon('...')``, or just a string of text.  To prepend or append multiple elements, then make it an ``array($html, ...)`` of addons.
     * 
     * @param string|array $prepend An element to place before the $input.
     * @param string|array $append  An element to place after the $input.
     * @param string       $input   The form field to wrap.    
     * 
     * @return string A ``<div class="input-group">...</div>`` html string.
     * 
     * ```php
     * echo $form->group('$', '.00', $form->text('amount'));
     * ```
     */
    public function group($prepend, $append, $input)
    {
        if (!empty($prepend)) {
            foreach ((array) $prepend as $html) {
                $class = (strpos($html, 'btn') !== false) ? 'input-group-btn' : 'input-group-addon';
                $input = $this->page->tag('div', array('class' => $class), $html).$input;
            }
        }
        if (!empty($append)) {
            foreach ((array) $append as $html) {
                $class = (strpos($html, 'btn') !== false) ? 'input-group-btn' : 'input-group-addon';
                $input = $input.$this->page->tag('div', array('class' => $class), $html);
            }
        }
        $group = array('input-group');
        if (!empty($this->input)) {
            $group[] = str_replace('-', '-group-', $this->input);
        }

        return $this->page->tag('div', array('class' => $group), $input);
    }

    /**
     * Used by ``$this->field()`` to create a ``<label>`` prompt.
     * 
     * @param string|array $prompt The form label reference.  If you want to include additional information relative to the field, then you can make this an ``array($prompt => $info)``, or an ``array($prompt, $info)`` that will appear when cliked or hovered over.  To customize the icon set ``$form->prompt('info', 'fa fa-info-circle')``.
     * @param string       $name   The name of the associated input field.
     * @param string       $id     The id of the associated input field.
     * 
     * @return string The generated HTML ``<label>``.
     *
     * ```php
     * $form->label('Email Address', 'email', $form->validator->id('email'));
     * ```
     *
     * @todo Why is this public?  This should be made private.
     */
    public function label($prompt, $name, $id)
    {
        if (empty($prompt)) {
            return '';
        }
        if (is_array($prompt)) {
            list($prompt, $info) = (count($prompt) > 1) ? array_values($prompt) : each($prompt);
        }
        if (empty($prompt) || strpos($prompt, '<label') !== false) {
            return $prompt;
        }
        if (isset($this->prompt['prepend'])) {
            if (!$this->prompt['prepend']['required'] || $this->validator->required($name)) {
                $prompt = $this->prompt['prepend']['html'].$prompt;
            }
        }
        if (isset($this->prompt['append'])) {
            $prompt .= $this->prompt['append'];
        }
        if (isset($info)) {
            $prompt .= ' '.$this->page->tag('i', array(
                'title' => htmlspecialchars($info),
                'class' => $this->prompt['info'],
                'style' => 'cursor:pointer;',
                'data-html' => 'true',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'data-container' => 'form[name='.$this->header['name'].']',
            ), '');
            $this->page->jquery('$(\'[data-toggle="tooltip"]\').tooltip();');
        }
        switch ($this->align) {
            case 'form-inline':
                $class = 'sr-only';
                break;
            case 'form-horizontal':
                $class = array(
                    "col-{$this->collapse}-{$this->indent}",
                    'control-label',
                    $this->input,
                );
                break;
            default:
                $class = $this->input;
                break;
        }

        return $this->page->tag('label', array(
            'class' => $class,
            'for' => $id,
        ), $prompt);
    }

    /**
     * Adds a (properly formatted) $prompt to your $input field, and manages any error messages.
     * 
     * @param string $prompt The $input's name.
     * @param string $input  A form field.
     * @param string $error  An optional error to override, or include with the field.
     * 
     * @return string A ``<div class="form-group">...</div>`` html string.
     * 
     * ```php
     * echo $form->field('Amount', $form->group('$', '.00', $form->text('amount')));
     * ```
     */
    public function field($prompt, $input, $error = null)
    {
        foreach (array('input', 'select', 'textarea', 'button', 'p') as $tag) {
            if ($this->firstTagAttributes($input, $matches, '<'.$tag)) {
                break;
            }
        }
        list($first, $tag, $attributes) = $matches;
        $type = (isset($attributes['type'])) ? $attributes['type'] : '';
        $name = (isset($attributes['name'])) ? $attributes['name'] : '';
        $id = (isset($attributes['id'])) ? $attributes['id'] : '';
        $prompt = $this->label($prompt, $name, $id);
        switch ($tag) {
            case 'input':
            case 'select':
            case 'textarea':
                $input = $this->addClass($input, array('p' => 'help-block'));
                if ($tag != 'input' || !in_array($type, array('checkbox', 'radio', 'file', 'submit', 'reset', 'button'))) {
                    $input = $this->addClass($input, array($tag => 'form-control '.$this->input));
                }
                break;
            case 'p':
                $input = $this->addClass($input, array('p' => 'form-control-static'));
                break;
        }
        $group = array('form-group');
        $msg = (empty($name)) ? null : $this->validator->error($name);
        if (!is_null($error)) {
            $msg = $error; // override all
        }
        if (!empty($msg)) {
            $group[] = 'has-error';
            $error = '<p class="validation help-block">'.$msg.'</p>';
        } elseif (!empty($name)) { // only include this when needed for validation
            $error = '<p class="validation help-block" style="display:none;"></p>';
        }
        if ($this->align == 'form-horizontal') {
            $class = array('col-'.$this->collapse.'-'.(12 - $this->indent));
            if (empty($prompt)) {
                $class[] = 'col-'.$this->collapse.'-offset-'.$this->indent;
            }
            $html = $prompt.$this->page->tag('div', array('class' => $class), $error.$input);
        } else {
            $html = $prompt.$error.$input;
        }

        return "\n\t".$this->page->tag('div', array('class' => $group), $html);
    }

    /**
     * Quickly adds a submit button to your form.
     * 
     * @param string $submit What you would like the submit button to say.  If it starts with a '**<**', then we assume you have spelled it all out for us.
     * @param string $reset  This will add a reset button if you give it a value, and if it starts with a '**<**' then it can be whatever you want it to be.  You can keep adding args until you run out of ideas for buttons to include.
     * 
     * @return string A ``<div class="form-group">...</div>`` html string with buttons.
     * 
     * ```php
     * echo $form->submit();
     * ```
     */
    public function submit($submit = 'Submit', $reset = '')
    {
        // never use name="submit" per: http://jqueryvalidation.org/reference/#developing-and-debugging-a-form
        $buttons = func_get_args();
        if (substr($submit, 0, 1) != '<') {
            $buttons[0] = $this->page->tag('button', array(
                'type' => 'submit',
                'class' => array('btn', 'btn-primary', str_replace('input', 'btn', $this->input)),
                'data-loading-text' => 'Submitting...',
            ), $submit);
        }
        if (isset($buttons[1]) && substr($reset, 0, 1) != '<') {
            $buttons[1] = $this->page->tag('button', array(
                'type' => 'reset',
                'class' => array('btn', 'btn-default', str_replace('input', 'btn', $this->input)),
            ), $reset);
        }

        return $this->field('', implode(' ', $buttons));
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $html = parent::close();
        if ($this->align == 'form-horizontal') {
            $html .= '</div></div>';
        }

        return $html;
    }
}
