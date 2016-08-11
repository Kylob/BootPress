<?php

namespace BootPress\Validator;

use BootPress\Page\Component as Page;

class Component
{
    /**
     * Custom validation rules that you would like to apply.
     * 
     * @var callable[]
     */
    public $rules = array();

    /**
     * Before you check if ``$this->certified()``, these are the error messages associated with each validation rule.  After ``$this->certified()``, these are all the errors we encountered (if any).  You can customize and add as you see fit.
     * 
     * @var string[]
     */
    public $errors = array(
        'remote' => 'Please fix this field.',
        'required' => 'This field is required.',
        'equalTo' => 'Please enter the same value again.',
        'notEqualTo' => 'Please enter a different value, values must not be the same.',
        'number' => 'Please enter a valid number.',
        'integer' => 'A positive or negative non-decimal number please.',
        'digits' => 'Please enter only digits.',
        'min' => 'Please enter a value greater than or equal to {0}.',
        'max' => 'Please enter a value less than or equal to {0}.',
        'range' => 'Please enter a value between {0} and {1}.',
        'alphaNumeric' => 'Letters, numbers, and underscores only please.',
        'minLength' => 'Please enter at least {0} characters.',
        'maxLength' => 'Please enter no more than {0} characters.',
        'rangeLength' => 'Please enter a value between {0} and {1} characters long.',
        'minWords' => 'Please enter at least {0} words.',
        'maxWords' => 'Please enter {0} words or less.',
        'rangeWords' => 'Please enter between {0} and {1} words.',
        'pattern' => 'Invalid format.',
        'date' => 'Please enter a valid date.',
        'email' => 'Please enter a valid email address.',
        'url' => 'Please enter a valid URL.',
        'ipv4' => 'Please enter a valid IP v4 address.',
        'ipv6' => 'Please enter a valid IP v6 address.',
        'inList' => 'Please make a valid selection.',
        'noWhiteSpace' => 'No white space please.',
    );

    /**
     * This is where we save all of the information ``$this->set()``ed for each field.
     * 
     * @var array
     */
    protected $data = array();

    /**
     * These are the user submitted values for each field.
     * 
     * @var array
     */
    protected $values = array();

    /**
     * Whether the form has been submitted or not.  Null if we don't know.
     * 
     * @var null|bool
     */
    protected $submitted = null;

    /**
     * Either false or an array of all the submitted values.
     * 
     * @var false|array
     */
    protected $certified = false;

    /**
     * The rules we reserve for validation until the end.
     * 
     * @var string[]
     */
    protected $reserved = array('default', 'required', 'equalTo', 'notEqualTo');

    /**
     * The rules we define in-house.
     * 
     * @var string[]
     */
    protected $methods = array('number', 'integer', 'digits', 'min', 'max', 'range', 'alphaNumeric', 'minLength', 'maxLength', 'rangeLength', 'minWords', 'maxWords', 'rangeWords', 'pattern', 'date', 'email', 'url', 'ipv4', 'ipv6', 'inList', 'yesNo', 'trueFalse', 'noWhiteSpace', 'singleSpace');

    /**
     * So that we have something to work with no matter what happens to ``$this->errors`` (anything can happen) public property.
     * 
     * @var string[]
     */
    protected $default_errors = array();

    /**
     * Creates a BootPress\Validator\Component object.
     * 
     * @param array $values The user submitted variables you want to validate.
     * 
     * ```php
     * $validator = new Validator($_POST);
     * ```
     */
    public function __construct(array $values = array())
    {
        $this->values = $values;
        $this->default_errors = $this->errors;
    }

    /**
     * This allows you to set individually (or all at once) the validation rules and filters for each form field.  The value of every $field you set here is automatically ``trim()``ed and returned when ``$this->submittted()``.
     * 
     * @param string $field The name of your form field.  If this is an ``array($field => $rules, $field, ...)`` then we loop through each one and call this method ourselves over and over.
     * 
     * Your $field names can be an array by adding brackets to the end ie. 'name[]'.  They can also be multi-dimensional arrays such as 'name[first]', or 'name[players][]', or 'name[parent][child]', etc.  The important thing to remember is that you must always use the exact name given here when referencing them in other methods.
     * @param string|array $rules A pipe delimited set (or an array) of rules to validate and filter the $field with.  You can also specify custom messages by making this an ``array($rule => $message, ...)``.  Parameters are comma-delimited, and placed within '**[]**' two brackets.  The available options are:
     *
     * - '**remote[rule]**' - Set ``$form->rules['rule'] = function($value){}`` to determine the validity of a submitted value.  The function should return a boolean true or false.
     * - '**default**' - A default value if the field is empty, or not even set.
     * - '**required**' - This field must have a value, and cannot be empty.
     * - '**equalTo[field]**' - Must match the same value as contained in the other form field.
     * - '**notEqualTo[field]**' - Must NOT match the same value as contained in the other form field.
     * - Numbers:
     *   - '**number**' - Must be a valid decimal number, positive or negative, integer or float, commas okay.  Defaults to 0.
     *   - '**integer**' - Must be a postive or negative integer number, no commas.  Defaults to 0.
     *   - '**digits**' - Must be a positive integer number, no commas.  Defaults to 0.
     *   - '**min[number]**' - Must be greater than or equal to [number].
     *   - '**max[number]**' - Must be less than or equal to [number].
     *   - '**range[min, max]**' - Must be greater than or equal to [min], and less than or equal to [max].
     * - Strings:
     *   - '**alphaNumeric**' - Alpha (a-z), numeric (0-9), and underscore (_) characters only.
     *   - '**minLength[integer]**' - String length must be greater than or equal to [integer].
     *   - '**maxLength[integer]**' - String length must be less than or equal to [integer].
     *   - '**rangeLength[minLength, maxLength]**' - String length must be greater than or equal to [minLength], and less than or equal to [maxLength].
     *   - '**minWords[integer]**' - Number of words must be greater than or equal to [integer].
     *   - '**maxWords[integer]**' - Number of words must be less than or equal to [integer].
     *   - '**rangeWords[minWords, maxWords]**' - Number of words must be greater than or equal to [minWords], and less than or equal to [maxWords].
     *   - '**pattern[regex]**' - Must match the supplied [ECMA Javascript](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/RegExp) compatible [regex].
     *   - '**date**' - Must be a valid looking date.  No particular format is enforced.
     *   - '**email**' - Must be a valid looking email.
     *   - '**url**' - Must be a valid looking url.
     *   - '**ipv4**' - Must be a valid looking ipv4 address.
     *   - '**ipv6**' - Must be a valid looking ipv6 address.
     *   - '**inList[1,2,3]**' - Must be one of a comma-separated list of acceptable values.
     *   - '**noWhiteSpace**' - Must contain no white space.
     * - Filters:
     *   - '**singleSpace**' - Removes any doubled-up whitespace so that you only have single spaces between words.
     *   - '**trueFalse**' - Returns a **1** (true) or **0** (false) integer.
     *   - '**yesNo**' - Returns a '**Y**' or '**N**' value.
     * 
     * ```php
     * $validator->set('name', 'required');
     * 
     * $validator->set('email', 'required|email');
     * 
     * $validator->set(array(
     *     'password' => 'required|alphaNumeric|minLength[5]|noWhiteSpace',
     *     'confim' => array('required', 'matches[password]'),
     * ));
     * 
     * $validator->set('field', array('required' => 'Do this or else.'));
     * ```
     */
    public function set($field, $rules = '')
    {
        if (is_array($field)) {
            foreach ($field as $name => $rules) {
                if (is_numeric($name) && is_string($rules)) {
                    $this->set($rules);
                } else {
                    $this->set($name, $rules);
                }
            }

            return;
        }
        $page = Page::html();
        $process = (is_array($rules)) ? $rules : array_map('trim', explode('|', $rules));
        $custom = array();
        $rules = array();
        foreach ($process as $rule => $message) {
            if (is_numeric($rule)) {
                $rule = $message;
                $message = false;
            }
            $param = true;
            if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
                if (strpos($param, ',') !== false) {
                    $param = array_map('trim', explode(',', $param));
                }
            }
            if ($message) {
                $custom[$rule] = $message;
            }
            $rules[$rule] = $param;
        }
        $validate = array();
        foreach ($rules as $rule => $param) {
            switch ($rule) {
                case 'remote':
                    if ($value = $page->get($this->base($field))) {
                        return $page->sendJson($this->remote($value, $param));
                    }
                    $validate['remote'] = $page->url();
                    break;
                case 'required':
                    $validate['required'] = 'true';
                    break;
                case 'equalTo':
                    $validate['equalTo'] = '#'.$this->id($param);
                    break;
                case 'notEqualTo':
                    $validate['notEqualTo'] = '#'.$this->id($param);
                    break;
                case 'number':
                    $validate['number'] = 'true';
                    $rules['default'] = 0;
                    break;
                case 'integer':
                    $validate['integer'] = 'true';
                    $rules['default'] = 0;
                    break;
                case 'digits':
                    $validate['digits'] = 'true';
                    $rules['default'] = 0;
                    break;
                case 'min':
                    $validate['min'] = $param;
                    break;
                case 'max':
                    $validate['max'] = $param;
                    break;
                case 'range':
                    $validate['range'] = $param;
                    break;
                case 'alphaNumeric':
                    $validate['alphanumeric'] = 'true';
                    break;
                case 'minLength':
                    $validate['minlength'] = $param;
                    break;
                case 'maxLength':
                    $validate['maxlength'] = $param;
                    break;
                case 'rangeLength':
                    $validate['rangelength'] = $param;
                    break;
                case 'minWords':
                    $validate['minWords'] = $param;
                    break;
                case 'maxWords':
                    $validate['maxWords'] = $param;
                    break;
                case 'rangeWords':
                    $validate['rangeWords'] = $param;
                    break;
                case 'pattern':
                    $validate['pattern'] = $param;
                    break; // must use javascript ecma regex
                case 'date':
                    $validate['date'] = 'true';
                    break;
                case 'email':
                    $validate['email'] = 'true';
                    break;
                case 'url':
                    $validate['url'] = 'true';
                    break;
                case 'ipv4':
                    $validate['ipv4'] = 'true';
                    break;
                case 'ipv6':
                    $validate['ipv6'] = 'true';
                    break;
                case 'inList':
                    if (!is_array($param)) {
                        $param = array($param);
                        $rules[$rule] = $param;
                    }
                    $validate['inList'] = implode(',', $param);
                    // json string arrays do not play nicely with data-rule-... attributes
                    $page->jquery('jQuery.validator.addMethod("inList", function(value, element, params) { return this.optional(element) || $.inArray(value, params.split(",")) !== -1; }, "Please make a valid selection.");');
                    break;
                case 'noWhiteSpace':
                    $validate['nowhitespace'] = 'true';
                    break;
                case 'trueFalse':
                    $rules['default'] = 0;
                    break;
                case 'yesNo':
                    $rules['default'] = 'N';
                    break;
            }
        }
        foreach ($validate as $rule => $param) {
            if (is_array($param)) {
                $validate[$rule] = implode(',', $param);
            }
        }
        $messages = array();
        foreach ($rules as $rule => $param) {
            if (isset($custom[$rule]) || isset($this->errors[$rule])) {
                $error = (isset($custom[$rule])) ? $custom[$rule] : $this->errors[$rule];
                if (!isset($this->default_errors[$rule]) || $this->default_errors[$rule] != $error) {
                    $messages[$rule] = $error;
                }
            }
        }
        $indexes = array();
        sscanf($field, '%[^[][', $indexes[0]);
        $value = null;
        if ((bool) preg_match_all('/\[(.*?)\]/', $field, $matches)) {
            foreach ($matches[1] as $key) {
                if ($key !== '') {
                    $indexes[] = $key;
                }
            }
            $value = $this->reduce($this->values, $indexes);
        } elseif (isset($this->values[$field])) {
            $value = $this->values[$field];
        }
        $error = null;
        if (!is_null($value)) {
            $value = (is_array($value)) ? array_map('trim', $value) : trim($value);
            foreach ($rules as $rule => $param) {
                list($value, $error) = $this->validate($value, $rule, $param);
                if (empty($value) || !empty($error)) {
                    break;
                }
            }
        }
        $this->data[$field] = array(
            'id' => '#'.$this->id($field),
            'field' => $indexes,
            'rules' => $rules,
            'validate' => (!empty($validate)) ? $validate : null,
            'messages' => (!empty($messages)) ? $messages : null,
            'value' => $value,
            'error' => $error,
        );
    }

    /**
     * This method goes through all of the fields you set above, determines if the form has been sent, and picks out any errors.
     * 
     * @return false|array Returns an array of validated and filtered form values for every ``$validator->set('field')`` IF the form was submitted (ie. at least one field has it's $_GET or $_POST counterpart), AND there were no errors.
     * 
     * ```php
     * if ($vars = $validator->certified()) {
     *     // process $vars
     * }
     * ```
     */
    public function certified()
    {
        if (!is_null($this->submitted)) {
            return $this->certified; // so we don't overwrite error messages
        }
        $errors = array();
        $this->values = array();
        $this->submitted = false;
        foreach ($this->data as $field => $data) {
            if (!is_null($data['value'])) {
                $this->submitted = true;
            }
            if (!empty($data['value'])) {
                $this->values[$field] = $data['value'];
            } elseif (strpos($field, '[]') !== false) {
                $this->values[$field] = array();
            } else {
                $this->values[$field] = (isset($data['rules']['default'])) ? $data['rules']['default'] : '';
            }
            if (!is_null($data['error'])) {
                $errors[$field] = $data['error'];
            }
        }
        if ($this->submitted) {
            $submitted = array();
            foreach ($this->data as $field => $data) {
                $value = &$submitted;
                foreach ($data['field'] as $index) {
                    $value = &$value[$index];
                }
                $value = $this->values[$field];
                if (isset($data['rules']['required']) && empty($value)) {
                    $errors[$field] = $this->errorMessage('required');
                } elseif (!isset($errors[$field])) {
                    if (isset($data['rules']['equalTo']) && $value != $this->value($data['rules']['equalTo'])) {
                        $errors[$field] = $this->errorMessage('equalTo');
                    } elseif (isset($data['rules']['notEqualTo']) && $value == $this->value($data['rules']['notEqualTo'])) {
                        $errors[$field] = $this->errorMessage('notEqualTo');
                    }
                }
            }
            $this->certified = (!empty($errors)) ? false : $submitted;
        }
        $this->errors = $errors;

        return $this->certified;
    }

    /**
     * Allows you to know if a form $field has been required, or not.
     * 
     * @param string $field
     * 
     * @return bool Whether the field is required or not.
     */
    public function required($field)
    {
        return (isset($this->data[$field]['rules']['required'])) ? true : false;
    }

    /**
     * Returns the submitted value of the $field that should be used when displaying the form.
     * 
     * The array feature comes in handy when you want to save the values to your database.
     * 
     * @param string|array $field
     * 
     * @return mixed
     */
    public function value($field)
    {
        if (is_array($field)) {
            $values = array();
            foreach ($field as $key => $value) {
                $values[$key] = $this->value($value);
            }

            return $values;
        }

        return ($this->submitted && isset($this->values[$field])) ? $this->values[$field] : null;
    }

    /**
     * Returns an error message (if any) that should be used when displaying the form.
     * 
     * @param string $field
     * 
     * @return null|string
     */
    public function error($field)
    {
        return ($this->submitted && isset($this->errors[$field])) ? $this->errors[$field] : null;
    }

    /**
     * Returns all of the rules set up for the $field.
     * 
     * @param string $field
     * 
     * @return string[]
     * 
     * ```php
     * foreach ($validator->rules($field) as $validate => $param) {
     *     $attributes["data-rule-{$validate}"] = htmlspecialchars($param);
     * }
     * ```
     */
    public function rules($field)
    {
        return (isset($this->data[$field]) && $validate = $this->data[$field]['validate']) ? $validate : array();
    }

    /**
     * Returns a $field's rules and associated error messages.
     * 
     * @param string $field
     * 
     * @return string[]
     * 
     * ```php
     * foreach ($validator->messages($field) as $rule => $message) {
     *     $attributes["data-msg-{$rule}"] = htmlspecialchars($message);
     * }
     * ```
     */
    public function messages($field)
    {
        return (isset($this->data[$field]) && $messages = $this->data[$field]['messages']) ? $messages : array();
    }

    /**
     * Includes Jörn's jQuery Validation code that this component was meant to sync perfectly with.
     * 
     * @param string   $form    The jQuery identifier of your form.
     * @param string[] $options The rules and custom messages should be added to each inputs data-... attributes using ``$this->rules()`` and ``$this->messages()``.  Any other fine-tuning can be done here.  The $options values must be pre json encoded ie. quotes around strings ('"string"'), brackets for arrays ('[]'), quoted bools ('false').  The reason for this is because we cannot json_encode functions properly ('function(){}').
     * 
     * ```php
     * $validator->jquery('#form', array('debug'=>'true'));
     * ```
     * 
     * @see https://jqueryvalidation.org/validate/
     */
    public function jquery($form, array $options = array())
    {
        $page = Page::html();
        foreach ($options as $key => $value) {
            $options[$key] = $key.':'.$value;
        }
        $page->jquery('$("'.$form.'").validate({'.implode(', ', $options).'});');
        $page->link('https://cdn.jsdelivr.net/jquery.validation/1.15.0/jquery.validate.min.js');
        $page->link('https://cdn.jsdelivr.net/jquery.validation/1.15.0/additional-methods.min.js');
    }

    /**
     * Returns the unique id assigned to the $field.
     * 
     * @param string $field
     * 
     * @return string
     */
    public function id($field)
    {
        static $ids = array();
        if (!isset($ids[$field])) {
            $ids[$field] = Page::html()->id($this->base($field));
        }

        return $ids[$field];
    }

    /**
     * Determines if the $value is a valid decimal number.  Can be positive or negative, integer or float, and commas to separate thousandths are okay.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function number($value)
    {
        return (bool) preg_match('/^(?:-?\d+|-?\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/', (string) $value);
    }

    /**
     * Deterimes if the $value is a positive or negative integer number, no commas.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function integer($value)
    {
        return (bool) preg_match('/^-?\d+$/', $value);
    }

    /**
     * Deterimes if the $value is a positive integer number, no commas.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function digits($value)
    {
        return (bool) preg_match('/^\d+$/', $value);
    }

    /**
     * Determines if the $value is greater than or equal to $param.
     * 
     * @param float $value
     * @param float $param
     * 
     * @return bool
     */
    public static function min($value, $param)
    {
        return is_numeric($value) ? ($value >= $param) : false;
    }

    /**
     * Determines if the $value is less than or equal to $param.
     * 
     * @param float $value
     * @param float $param
     * 
     * @return bool
     */
    public static function max($value, $param)
    {
        return is_numeric($value) ? ($value <= $param) : false;
    }

    /**
     * Determines if the $value is greater than or equal to $param[0], and less than or equal to $param[1].
     * 
     * @param float   $value
     * @param float[] $param
     * 
     * @return bool
     */
    public static function range($value, array $param)
    {
        return is_numeric($value) ? ($value >= $param[0] && $value <= $param[1]) : false;
    }

    /**
     * Deterimes if the $value has alpha (a-z), numeric (0-9), and underscore (_) characters only.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function alphaNumeric($value)
    {
        return (bool) preg_match('/^\w+$/', $value);
    }

    /**
     * Determines if the $value's length is greater than or equal to $param.
     * 
     * @param string $value
     * @param int    $param
     * 
     * @return bool
     */
    public static function minLength($value, $param)
    {
        $length = is_array($value) ? count($value) : mb_strlen($value);

        return $length >= $param;
    }

    /**
     * Determines if the $value's length is less than or equal to $param.
     * 
     * @param string $value
     * @param int    $param
     * 
     * @return bool
     */
    public static function maxLength($value, $param)
    {
        $length = is_array($value) ? count($value) : mb_strlen($value);

        return $length <= $param;
    }

    /**
     * Determines if the $value's length is greater than or equal to $param[0], and less than or equal to $param[1].
     * 
     * @param string $value
     * @param int[]  $param
     * 
     * @return bool
     */
    public static function rangeLength($value, array $param)
    {
        $length = is_array($value) ? count($value) : mb_strlen($value);

        return $length >= $param[0] && $length <= $param[1];
    }

    /**
     * Determines if the number of $value's words are greater than or equal to $param.
     * 
     * @param string $value
     * @param int    $param
     * 
     * @return bool
     */
    public static function minWords($value, $param)
    {
        $count = self::numWords($value);

        return $count >= $param;
    }

    /**
     * Determines if the number of $value's words are less than or equal to $param.
     * 
     * @param string $value
     * @param int    $param
     * 
     * @return bool
     */
    public static function maxWords($value, $param)
    {
        $count = self::numWords($value);

        return $count <= $param;
    }

    /**
     * Determines if the number of $value's words are greater than or equal to $param[0], and less than or equal to $param[1].
     * 
     * @param string $value
     * @param int[]  $param
     * 
     * @return bool
     */
    public static function rangeWords($value, array $param)
    {
        $count = self::numWords($value);

        return $count >= $param[0] && $count <= $param[1];
    }

    /**
     * Determines if the $value matches the supplied regex ($param).
     * 
     * @param string $value
     * @param string $param
     * 
     * @return bool
     */
    public static function pattern($value, $param)
    {
        return (bool) preg_match($param, $value);
    }

    /**
     * Determines if the $value is a parseable date.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function date($value)
    {
        return (bool) strtotime($value);
    }

    /**
     * Determines if the $value is a valid looking email.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function email($value)
    {
        return (bool) preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $value);
    }

    /**
     * Determines if the $value is a valid looking url.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function url($value)
    {
        return (bool) preg_match('_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$_iuS', $value);
    }

    /**
     * Determines if the $value is a valid looking ipv4 address.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function ipv4($value)
    {
        return (bool) preg_match('/^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/i', $value);
    }

    /**
     * Determines if the $value is a valid looking ipv6 address.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function ipv6($value)
    {
        return (bool) preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i', $value);
    }

    /**
     * Determines if the $value exists in the $param array.
     * 
     * @param string $value
     * @param array  $param
     * 
     * @return bool
     */
    public static function inList($value, array $param)
    {
        return in_array($value, $param);
    }

    /**
     * Determines if the $value contains any white space.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function noWhiteSpace($value)
    {
        return (empty($value)) ? true : (bool) preg_match('/^\S+$/i', $value);
    }

    /**
     * Removes any doubled-up whitespace from the $value.
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function singleSpace($value)
    {
        return preg_replace('/\s(?=\s)/', '', $value);
    }

    /**
     * Returns a **1** (true) or **0** (false) integer depending on the $value.
     * 
     * @param mixed $value
     * 
     * @return int
     */
    public static function trueFalse($value)
    {
        if ((is_numeric($value) && $value > 0) || strtoupper($value) == 'Y') {
            return 1;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /**
     * Returns a '**Y**' or '**N**' string depending on the $value.
     * 
     * @param mixed $value
     * 
     * @return string
     */
    public static function yesNo($value)
    {
        if ((is_numeric($value) && $value > 0) || strtoupper($value) == 'Y') {
            return 'Y';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Y' : 'N';
    }

    /**
     * Returns the number of words in $value.
     * 
     * @param string $value
     * 
     * @return int
     */
    private static function numWords($value)
    {
        $value = preg_replace('/<.[^<>]*?>/', ' ', $value);
        $value = preg_replace('/&nbsp;|&#160;/i', ' ', $value);
        $value = preg_replace('/[.(),;:!?%#$\'\"_+=\/\-“”’]*/', '', $value);
        preg_match_all('/\b\w+\b/', $value, $words);

        return count($words[0]);
    }

    /**
     * A helper method to validate a $value via a callable $param.
     * 
     * @param string $value
     * @param string $param
     * 
     * @return string
     */
    private function remote($value, $param)
    {
        $value = (isset($this->rules[$param]) && is_callable($this->rules[$param])) ? $this->rules[$param]($value) : false;
        if (!is_string($value)) {
            $value = ($value) ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * A helper method to determine the value in an array based on a string eg. 'array[value]'.
     * 
     * @param mixed $array
     * @param array $indexes
     * @param int   $i
     * 
     * @return mixed
     */
    private function reduce($array, array $indexes, $i = 0)
    {
        if (is_array($array) && isset($indexes[$i])) {
            return isset($array[$indexes[$i]]) ? $this->reduce($array[$indexes[$i]], $indexes, ($i + 1)) : null;
        }

        return ($array === '') ? null : $array;
    }

    /**
     * A helper method to remove any reference to an array ie. 'array[value]' would be just 'array'.
     * 
     * @param string $field
     * 
     * @return string
     */
    private function base($field)
    {
        return ($split = strpos($field, '[')) ? substr($field, 0, $split) : $field;
    }

    /**
     * A helper method to validate a string or an array of values based on it's $rule's and $param's.
     * 
     * @param mixed  $value
     * @param string $rule
     * @param mixed  $param
     * 
     * @return array The derived value, and error (if any).
     */
    private function validate($value, $rule, $param)
    {
        if (in_array($rule, $this->reserved)) {
            return array($value, null);
        }
        if (is_array($value) && !in_array($rule, array('minLength', 'maxLength', 'rangeLength'))) {
            $values = $value;
            $errors = array();
            foreach ($values as $key => $value) {
                list($value, $error) = $this->validate($value, $rule, $param);
                $values[$key] = $value;
                if ($error) {
                    $errors[$key] = $error;
                }
            }

            return array($values, !empty($errors) ? array_shift($errors) : null);
        }
        $error = null;
        if ($rule == 'remote') {
            if ('true' != $result = $this->remote($value, $param)) {
                $error = ($result == 'false') ? $this->errorMessage($rule, $param) : $result;
            }
        } elseif (isset($this->rules[$rule]) && is_callable($this->rules[$rule])) {
            if (!is_bool($result = $this->rules[$rule]($value, $param))) {
                $value = $result;
            }
            if ($result === false) {
                $error = $this->errorMessage($rule, $param);
            }
        } elseif (in_array($rule, $this->methods)) {
            if (!is_bool($result = self::$rule($value, $param))) {
                $value = $result;
            }
            if ($result === false) {
                $error = $this->errorMessage($rule, $param);
            }
        }

        return array($value, $error);
    }

    /**
     * A helper method to retrieve the associated error message when something goes wrong.
     * 
     * @param string $rule
     * @param mixed  $param
     * 
     * @return null|string
     */
    private function errorMessage($rule, $param = null)
    {
        if ($rule == 'remote' && isset($this->errors[$param])) {
            return $this->errorMessage($param);
        }
        if (is_null($param)) {
            $param = '';
        }
        $params = array_pad((array) $param, 2, '');

        return (isset($this->errors[$rule])) ? str_replace(array('{0}', '{1}'), $params, $this->errors[$rule]) : null;
    }
}
