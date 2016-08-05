<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Form\Component as Form;
use BootPress\Validator\Component as Validator;
use Symfony\Component\HttpFoundation\Request;

class FormTest extends HTMLUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/path/to/folder.html', 'GET', array('foo' => 'bar'));
        Page::html(array('dir' => __DIR__.'/page', 'suffix' => '.html'), $request, 'overthrow');
    }

    public function testConstructor()
    {
        $form = new Form('test', 'get');
        $this->assertEquals('test', $form->header['name']);
        $this->assertEquals('get', $form->header['method']);
        $this->assertNotEmpty($form->header['action']);
        $this->assertEquals($form->header['action'], $form->eject);
        $this->assertEquals('utf-8', $form->header['accept-charset']);
        $this->assertEquals('off', $form->header['autocomplete']);
        $this->assertInstanceOf('BootPress\Validator\Component', $form->validator);
        $this->assertAttributeInstanceOf('BootPress\Page\Component', 'page', $form);
        $form = new Form('test');
        $this->assertEquals('test', $form->header['name']);
        $this->assertEquals('post', $form->header['method']);
        $this->assertNotEmpty($form->header['action']);
        $this->assertNotEquals($form->header['action'], $form->eject);
        $this->assertEquals('utf-8', $form->header['accept-charset']);
        $this->assertEquals('off', $form->header['autocomplete']);
        $this->assertInstanceOf('BootPress\Validator\Component', $form->validator);
        $this->assertAttributeInstanceOf('BootPress\Page\Component', 'page', $form);
    }

    public function testMenuMethod()
    {
        $form = new Form('test');
        $this->assertEquals('1,2,3', $form->menu('transport', array('Fast' => array(1 => 'Airplane'), 'Slow' => array(2 => 'Boat', 3 => 'Submarine')), '&nbsp;'));
        $this->assertAttributeEquals(array('transport' => '&nbsp;'), 'prepend', $form);
        $this->assertEquals(array('Fast' => array(1 => 'Airplane'), 'Slow' => array(2 => 'Boat', 3 => 'Submarine')), $form->menu('transport'));
    }

    public function testHeaderAndCloseMethod()
    {
        $form = new Form('test', 'get');
        $this->assertEqualsRegExp('<form name="test" method="get" action="http://website.com/path/to/folder.html" accept-charset="utf-8" autocomplete="off">', $form->header());
        $form = new Form('test');
        $form->header['upload'] = 10;
        $this->assertEqualsRegExp('<form name="test" method="post" action="http://website.com/path/to/folder.html?foo=bar&amp;submitted=test" accept-charset="utf-8" autocomplete="off" enctype="multipart/form-data">', $form->header());
        $form->footer[] = $form->input('submit', array('name' => 'Submit'));
        $this->assertEqualsRegExp('<input type="submit" name="Submit"><input type="hidden" name="MAX_FILE_SIZE" value="10485760"></form>', $form->close());
    }

    public function testFieldsetMethod()
    {
        $form = new Form('test');
        $html = '<fieldset><legend>Legend</legend>OneTwoThree</fieldset>';
        $this->assertEqualsRegExp($html, $form->fieldset('Legend', array('One', 'Two', 'Three')));
        $this->assertEqualsRegExp($html, $form->fieldset('Legend', 'One', 'Two', 'Three'));
        $this->assertEqualsRegExp($html, $form->fieldset('Legend', array('One', 'Two'), 'Three'));
    }

    public function testDefaultValueMethod()
    {
        $form = new Form('test');
        $this->assertEquals('', $form->defaultValue('field'));
        $form->values['field'] = '"default"';
        $this->assertEquals('"default"', $form->defaultValue('field'));
        $this->assertEquals('&quot;default&quot;', $form->defaultValue('field', 'escape'));
    }

    public function testValidateMethod()
    {
        $form = new Form('test');
        $form->validator->set('field', array('required' => 'Do this or else.'));
        $attributes = $form->validate('field', array('name' => 'field'));
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('data-rule-required', $attributes);
        $this->assertArrayHasKey('data-msg-required', $attributes);
        $this->assertEquals('field', $attributes['name']);
        $this->assertEquals('true', $attributes['data-rule-required']);
        $this->assertEquals('Do this or else.', $attributes['data-msg-required']);
    }

    public function testInputMethod()
    {
        $form = new Form('test');
        $html = $form->input('hidden', array('name' => 'field', 'value' => 'default'));
        $this->assertEquals('<input type="hidden" name="field" value="default">', trim($html));
    }

    public function testTextMethod()
    {
        $form = new Form('test');
        $form->values['field'] = '"default"';
        $form->validator->set('field', array('required' => 'Do this or "else".'));
        $this->assertEqualsRegExp('<input type="text" class="example" name="field" id="field{[A-Z]}" value="&quot;default&quot;" data-rule-required="true" data-msg-required="Do this or &quot;else&quot;.">', $form->text('field', array('class' => 'example')));
    }

    public function testPasswordMethod()
    {
        $form = new Form('test');
        $form->values['field'] = '"default"';
        $form->validator->set('field', array('required' => 'Do this or "else".'));
        $this->assertEqualsRegExp('<input type="password" class="example" name="field" id="field{[A-Z]}" data-rule-required="true" data-msg-required="Do this or &quot;else&quot;.">', $form->password('field', array('class' => 'example')));
    }

    public function testCheckboxMethod()
    {
        $form = new Form('test');
        $form->values['remember'] = 'Y';
        $form->menu('remember', array('Y' => 'Remember Me', 'N' => 'Accept Terms and Conditions'));
        $form->validator->set('remember', 'yesNo');
        $this->assertEquals('<label><input type="checkbox" class="example" name="remember" value="Y" checked="checked"> Remember Me</label> <label><input type="checkbox" class="example" name="remember" value="N"> Accept Terms and Conditions</label>', $form->checkbox('remember', array('class' => 'example')));
        $this->assertEquals(array(
            '<input type="checkbox" class="example" name="remember" value="Y" checked="checked"> Remember Me',
            '<input type="checkbox" class="example" name="remember" value="N"> Accept Terms and Conditions'
        ), $form->checkbox('remember', array('class' => 'example'), array()));
    }

    public function testRadioMethod()
    {
        $form = new Form('test');
        $form->values['gender'] = 'M';
        $gender = $form->menu('gender', array('M' => 'Male', 'F' => 'Female'));
        $form->validator->set('gender', "required|inList[{$gender}]");
        $this->assertEquals('<label><input type="radio" class="example" name="gender" value="M" checked="checked" data-rule-required="true" data-rule-inList="M,F"> Male</label> <label><input type="radio" class="example" name="gender" value="F"> Female</label>', $form->radio('gender', array('class' => 'example')));
        $this->assertEquals(array(
            '<input type="radio" class="example" name="gender" value="M" checked="checked" data-rule-required="true" data-rule-inList="M,F"> Male',
            '<input type="radio" class="example" name="gender" value="F"> Female'
        ), $form->radio('gender', array('class' => 'example'), array()));
    }

    public function testSelectMethod()
    {
        $form = new Form('test');
        $form->values['save[]'] = array(8, 15);
        $save = $form->menu('save[]', array(
            4 => 'John Locke',
            8 => 'Hugo Reyes',
            15 => 'James Ford',
            16 => 'Sayid Jarrah',
            23 => 'Jack Shephard',
            42 => 'Jin & Sun Kwon',
        ));
        $form->validator->set('save[]', "inList[{$save}]");
        $this->assertEqualsRegExp('<select class="example" name="save[]" id="save{[A-Z]}" multiple="multiple" size="6" data-rule-inList="4,8,15,16,23,42"><option value="4">John Locke</option><option value="8" selected="selected">Hugo Reyes</option><option value="15" selected="selected">James Ford</option><option value="16">Sayid Jarrah</option><option value="23">Jack Shephard</option><option value="42">Jin & Sun Kwon</option></select>', $form->select('save[]', array('class' => 'example')));

        $form->values['save[]'] = 8;
        $this->assertEqualsRegExp('<select name="save[]" id="save{[A-Z]}" data-rule-inList="4,8,15,16,23,42"><option value="4">John Locke</option><option value="8" selected="selected">Hugo Reyes</option><option value="15">James Ford</option><option value="16">Sayid Jarrah</option><option value="23">Jack Shephard</option><option value="42">Jin & Sun Kwon</option></select>', $form->select('save[]', array('multiple' => false)));

        $form->values['transport'] = 2;
        $form->menu('transport', array('Fast' => array(1 => 'Airplane'), 'Slow' => array(2 => 'Boat', 3 => 'Submarine')), '&nbsp;');
        $this->assertEqualsRegExp('<select name="transport" id="transport{[A-Z]}"><option value="">&nbsp;</option><optgroup label="Fast"><option value="1">Airplane</option></optgroup><optgroup label="Slow"><option value="2" selected="selected">Boat</option><option value="3">Submarine</option></optgroup></select>', $form->select('transport'));

        $form->values['vehicle'] = 11;
        $vehicles = $form->menu('vehicle', array(
            'hier' => 'transport',
            1 => array('Boeing' => array(4 => '777', 5 => '737'), 'Lockheed' => array(6 => 'L-1011', 7 => 'HC-130'), 8 => 'Douglas DC-3', 9 => 'Beechcraft'),
            2 => array(11 => 'Black Rock', 12 => 'Kahana', 13 => 'Elizabeth', 14 => 'Searcher'),
            3 => array(15 => 'Galaga', '16' => 'Yushio'),
        ), '&nbsp;');
        $form->validator->set('vehicle', "inList[{$vehicles}]");
        $this->assertEqualsRegExp('<select name="vehicle" id="vehicle{[A-Z]}" data-rule-inList="4,5,6,7,8,9,11,12,13,14,15,16"><option value="">&nbsp;</option><option value="11" selected="selected">Black Rock</option><option value="12">Kahana</option><option value="13">Elizabeth</option><option value="14">Searcher</option></select>', $form->select('vehicle'));

        $html = Page::html()->display('<p>Content</p>');
        $this->assertContains('$.fn.hierSelect', $html);
        $this->assertContains('jQuery.validator.addMethod("inList"', $html);
    }

    public function testTextareaMethod()
    {
        $form = new Form('test');
        $form->values['field'] = '"default"';
        $form->validator->set('field', array('required' => 'Do this or "else".'));
        $this->assertEqualsRegExp('<textarea class="example" name="field" id="field{[A-Z]}" cols="40" rows="10" data-rule-required="true" data-msg-required="Do this or &quot;else&quot;.">&quot;default&quot;</textarea>', $form->textarea('field', array('class' => 'example')));
    }
}
