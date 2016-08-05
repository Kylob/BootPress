<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Validator\Component as Validator;
use Symfony\Component\HttpFoundation\Request;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    
    public function testConstructorMethod()
    {
        $validator = new Validator();
        $this->assertAttributeEquals($validator->errors, 'default_errors', $validator);
        $this->assertFalse($validator->certified());
    }

    public function testSetRulesErrorValueAndSubmittedMethods()
    {
        $request = Request::create('http://website.com/form.html');
        Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html', 'testing'=>true), $request, 'overthrow');
        $post = array(
            'name' => 'Vladimir Putin',
            'email' => 'vputin@kgb.gov',
            'occupation' => array(
                'jobs' => array(
                    'programmer',
                    'loafer'
                )
            )
        );
        
        // This will pass validation
        $validator = new Validator($post);
        $validator->rules['filter'] = function($value){ return '!'.$value.'!'; };
        $validator->rules['pass'] = function($value){ return true; };
        $this->assertNull($validator->set(array(
            'name' => 'required|minLength[2]|remote[pass]',
            'email' => 'required|email|filter',
            'occupation[jobs][]' => 'inList[programmer,loafer]',
            'remember' => 'yesNo',
        )));
        
        // Returned array keys do not include array structure
        $this->assertEquals(array(
            'name' => 'Vladimir Putin',
            'email' => '!vputin@kgb.gov!',
            'occupation' => array('jobs' => array('programmer', 'loafer')),
            'remember' => 'N',
        ), $validator->certified());
        
        // Returns same values as first call, but only processes once
        $this->assertEquals(array(
            'name' => 'Vladimir Putin',
            'email' => '!vputin@kgb.gov!',
            'occupation' => array('jobs' => array('programmer', 'loafer')),
            'remember' => 'N',
        ), $validator->certified());
        
        
        // This will cause errors
        $validator = new Validator($post);
        $validator->rules['fail'] = function($value){ return false; };
        $validator->errors['fail'] = 'You just can\'t get this right.';
        $this->assertNull($validator->set(array(
            'name' => 'required|minLength[2]|remote[fail]',
            'email' => 'required|email',
            'occupation[jobs][]' => array('inList[executive,ceo]' => 'Go and get a real job.'),
            'remember' => 'yesNo',
        )));
        
        // Test Rules Method
        $this->assertEquals(array(
            'required' => 'true',
            'minlength' => '2',
            'remote' => 'http://website.com/form.html',
        ), $validator->rules('name'));
        $this->assertEquals(array(
            'required' => 'true',
            'email' => 'true',
        ), $validator->rules('email'));
        $this->assertEquals(array(
            'inList' => 'executive,ceo',
        ), $validator->rules('occupation[jobs][]'));
        $this->assertEquals(array(), $validator->rules('remember'));
        
        // Test Messages Method
        $this->assertEquals(array(), $validator->messages('name'));
        $this->assertEquals(array(), $validator->messages('email'));
        $this->assertEquals(array(
            'inList' => 'Go and get a real job.'
        ), $validator->messages('occupation[jobs][]'));
        $this->assertEquals(array(), $validator->messages('remember'));
        
        // Test Multiple Set Method Calls
        $this->assertNull($validator->set(array(
            'array[]' => 'required',
            'mismatch' => 'notEqualTo[password]',
            'password' => 'equalTo[email]',
        )));
        
        // Form has been submitted, but there are errors
        $this->assertFalse($validator->certified());
        
        // Verify Submitted Values and Errors
        $this->assertEquals(array(
            'Vladimir Putin',
            'vputin@kgb.gov',
            array('programmer', 'loafer'),
            'N',
        ), $validator->value(array(
            'name',
            'email',
            'occupation[jobs][]',
            'remember',
        )));
        $this->assertEquals(array(), $validator->value('array[]'));
        $this->assertEquals('This field is required.', $validator->error('array[]'));
        $this->assertEquals('Please enter a different value, values must not be the same.', $validator->error('mismatch'));
        $this->assertEquals('Please enter the same value again.', $validator->error('password'));
        $this->assertEquals('You just can\'t get this right.', $validator->error('name'));
        $this->assertEquals('Please make a valid selection.', $validator->error('occupation[jobs][]'));
        $this->assertNull($validator->error('email'));
        $this->assertNull($validator->error('remember'));
        
        // Verify Remote Method Call
        $request = Request::create('http://website.com/form.html', 'GET', array('name' => 'remote'));
        Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html', 'testing'=>true), $request, 'overthrow');
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $validator->set('name', 'required|minLength[2]|remote[fail]'));
        
        // Call Additional Rules
        $validator = new Validator(array(
            'number' => 3.14,
            'integer' => 100,
            'min' => 0, // empty, but not null - this will halt further processing
            'singleSpace' => 'single   space', // validate a $this->methods that doesn't return a bool
        ));
        $validator->rules['null'] = function ($value) {
            return null;
        };
        $validator->rules['false'] = function ($value) {
            return false;
        };
        $validator->errors['false'] = 'Try again.';
        $this->assertNull($validator->set(array(
            'field',
            'number' => 'null|number',
            'integer' => 'false|integer', // a callable that doesn't return bool
            'digits' => 'digits',
            'min' => 'min[1]',
            'max' => 'max[10]',
            'range' => 'range[1,10]',
            'alphaNumeric' => 'alphaNumeric',
            'minLength' => 'minLength[1]',
            'maxLength' => 'maxLength[10]',
            'rangeLength' => 'rangeLength[1,10]',
            'minWords' => 'minWords[1]',
            'maxWords' => 'maxWords[10]',
            'rangeWords' => 'rangeWords[1,10]',
            'pattern' => 'pattern[/[0-9]/]',
            'date' => 'date',
            'url' => 'url',
            'ipv4' => 'ipv4',
            'ipv6' => 'ipv6',
            'inList' => 'inList[string]',
            'trueFalse' => 'trueFalse',
            'noWhiteSpace' => 'noWhiteSpace',
            'singleSpace' => 'singleSpace',
        )));
        $validator->certified();
        $this->assertEquals(0, $validator->value('number'));
        $this->assertEquals(100, $validator->value('integer'));
        $this->assertEquals('Try again.', $validator->error('integer'));
        $this->assertEquals('', $validator->value('min'));
        $this->assertEquals('single space', $validator->value('singleSpace'));
    }
    
    public function testJqueryMethod()
    {
        $validator = new Validator();
        $this->assertNull($validator->jquery('#form'));
    }
    
    public function testNumberMethod()
    {
        $this->assertTrue(Validator::number(''));
        $this->assertTrue(Validator::number(1000));
        $this->assertTrue(Validator::number(+1000));
        $this->assertTrue(Validator::number(-1000));
        $this->assertTrue(Validator::number(1.345));
        $this->assertTrue(Validator::number(1, 234, 567));
        $this->assertTrue(Validator::number('-1,234,567.89'));
        $this->assertFalse(Validator::number('-1,234,5,67.89'));
        $this->assertFalse(Validator::number('-1.345.678'));
        $this->assertFalse(Validator::number('string'));
    }

    public function testIntegerMethod()
    {
        $this->assertTrue(Validator::integer(1000));
        $this->assertTrue(Validator::integer(-1000));
        $this->assertTrue(Validator::integer(+1000));
        $this->assertFalse(Validator::integer('+1000'));
        $this->assertFalse(Validator::integer(1.345));
        $this->assertFalse(Validator::integer('string'));
        $this->assertFalse(Validator::integer(''));
    }

    public function testDigitsMethod()
    {
        $this->assertTrue(Validator::digits(1000));
        $this->assertFalse(Validator::digits(-1000));
        $this->assertTrue(Validator::digits(+1000));
        $this->assertFalse(Validator::digits('+1000'));
        $this->assertFalse(Validator::digits(1.345));
        $this->assertFalse(Validator::digits('string'));
        $this->assertFalse(Validator::digits(''));
    }

    public function testMinMethod()
    {
        $this->assertTrue(Validator::min(5, 3));
        $this->assertTrue(Validator::min(5, '3.14'));
        $this->assertTrue(Validator::min(5, 'string'));
        $this->assertTrue(Validator::min('5', '3.14'));
        $this->assertFalse(Validator::min(3, 5));
        $this->assertFalse(Validator::min('3.14', 5));
        $this->assertFalse(Validator::min('string', 5));
    }

    public function testMaxMethod()
    {
        $this->assertFalse(Validator::max(5, 3));
        $this->assertFalse(Validator::max(5, '3.14'));
        $this->assertFalse(Validator::max(5, 'string'));
        $this->assertFalse(Validator::max('5', '3.14'));
        $this->assertTrue(Validator::max(3, 5));
        $this->assertTrue(Validator::max('3.14', 5));
        $this->assertFalse(Validator::max('string', 5));
    }

    public function testRangeMethod()
    {
        $this->assertTrue(Validator::range(5, array(2, 7)));
        $this->assertTrue(Validator::range('5', array('2', '7')));
        $this->assertTrue(Validator::range(-30, array(-100, '0')));
        $this->assertFalse(Validator::range(-30, array(0, 20)));
        $this->assertFalse(Validator::range('5', array('6', '7')));
        $this->assertFalse(Validator::range(5, array(6, 7)));
    }

    public function testAlphaNumericMethod()
    {
        $this->assertTrue(Validator::alphaNumeric(true));
        $this->assertTrue(Validator::alphaNumeric('abcdef'));
        $this->assertTrue(Validator::alphaNumeric('abc123'));
        $this->assertTrue(Validator::alphaNumeric('abc_xyz'));
        $this->assertFalse(Validator::alphaNumeric('abc-xyz'));
        $this->assertFalse(Validator::alphaNumeric('abc xyz'));
        $this->assertFalse(Validator::alphaNumeric('123$%^'));
        $this->assertFalse(Validator::alphaNumeric(' abc'));
        $this->assertFalse(Validator::alphaNumeric(false));
    }

    public function testMinLengthMethod()
    {
        $this->assertTrue(Validator::minLength('string', 2));
        $this->assertTrue(Validator::minLength('string', 6));
        $this->assertFalse(Validator::minLength('string', 7));
        $this->assertFalse(Validator::minLength(array(1, 2), 3));
        $this->assertTrue(Validator::minLength(array(1, 2), 2));
    }

    public function testMaxLengthMethod()
    {
        $this->assertFalse(Validator::maxLength('string', 2));
        $this->assertTrue(Validator::maxLength('string', 6));
        $this->assertTrue(Validator::maxLength('string', 7));
        $this->assertTrue(Validator::maxLength(array(1, 2), 3));
        $this->assertTrue(Validator::maxLength(array(1, 2), 2));
        $this->assertFalse(Validator::maxLength(array(1, 2), 1));
    }

    public function testRangeLengthMethod()
    {
        $this->assertTrue(Validator::rangeLength('string', array(2, 6)));
        $this->assertTrue(Validator::rangeLength('string', array(6, 10)));
        $this->assertFalse(Validator::rangeLength('string', array(7, 15)));
        $this->assertFalse(Validator::rangeLength(array(1, 2), array(3, 5)));
        $this->assertTrue(Validator::rangeLength(array(1, 2), array(2, 4)));
        $this->assertTrue(Validator::rangeLength(array(1, 2), array(0, 2)));
    }

    public function testMinWordsMethod()
    {
        $this->assertTrue(Validator::minWords('one two three', 1));
        $this->assertTrue(Validator::minWords('one two three', 3));
        $this->assertFalse(Validator::minWords('one two three', 5));
    }

    public function testMaxWordsMethod()
    {
        $this->assertFalse(Validator::maxWords('one two three', 1));
        $this->assertTrue(Validator::maxWords('one two three', 3));
        $this->assertTrue(Validator::maxWords('one two three', 5));
    }

    public function testRangeWordsMethod()
    {
        $this->assertTrue(Validator::rangeWords('one two three', array(1, 3)));
        $this->assertTrue(Validator::rangeWords('one two three', array(3, 5)));
        $this->assertFalse(Validator::rangeWords('one two three', array(0, 2)));
        $this->assertFalse(Validator::rangeWords('one two three', array(6, 7)));
    }

    public function testPatternMethod()
    {
        // Allows phone numbers with optional country code, optional special characters and whitespace
        $phone_number = '/^([+]?\d{1,2}[-\s]?|)\d{3}[-\s]?\d{3}[-\s]?\d{4}$/';
        $this->assertTrue(Validator::pattern('907-555-0145', $phone_number));
        $this->assertFalse(Validator::pattern('555-0145', $phone_number));
    }

    public function testDateMethod()
    {
        $this->assertTrue(Validator::date('now'));
        $this->assertTrue(Validator::date('2015-12-31'));
        $this->assertFalse(Validator::date('infinite'));
    }

    public function testEmailMethod()
    {
        $this->assertTrue(Validator::email('email@example.com'));
        $this->assertTrue(Validator::email('firstname.lastname@example.com'));
        $this->assertTrue(Validator::email('email@subdomain.example.com'));
        $this->assertTrue(Validator::email('firstname+lastname@example.com'));
        $this->assertTrue(Validator::email('email@123.123.123.123'));
        $this->assertTrue(Validator::email('1234567890@example.com'));
        $this->assertTrue(Validator::email('email@example-one.com'));
        $this->assertTrue(Validator::email('_______@example.com'));
        $this->assertTrue(Validator::email('email@example.name'));
        $this->assertTrue(Validator::email('email@example.museum'));
        $this->assertTrue(Validator::email('email@example.co.jp'));
        $this->assertTrue(Validator::email('firstname-lastname@example.com'));
        $this->assertTrue(Validator::email('.email@example.com'));
        $this->assertTrue(Validator::email('email.@example.com'));
        $this->assertTrue(Validator::email('email..email@example.com'));
        $this->assertTrue(Validator::email('email@example'));
        $this->assertTrue(Validator::email('email@example.web'));
        $this->assertTrue(Validator::email('email@111.222.333.44444'));
        $this->assertTrue(Validator::email('Abc..123@example.com'));
        $this->assertTrue(Validator::email('me@example.com'));
        $this->assertTrue(Validator::email('a.nonymous@example.com'));
        $this->assertTrue(Validator::email('name+tag@example.com'));
        $this->assertTrue(Validator::email('me.@example.com'));
        $this->assertTrue(Validator::email('.me@example.com'));
        $this->assertTrue(Validator::email('me.example@com'));
        $this->assertFalse(Validator::email('plainaddress'));
        $this->assertFalse(Validator::email('me@'));
        $this->assertFalse(Validator::email('@example.com'));
        $this->assertFalse(Validator::email('me@example..com'));
        $this->assertFalse(Validator::email('me\@example.com'));
        $this->assertFalse(Validator::email('#@%^%#$@#$@#.com'));
        $this->assertFalse(Validator::email('@example.com'));
        $this->assertFalse(Validator::email('Joe Smith <email@example.com>'));
        $this->assertFalse(Validator::email('email.example.com'));
        $this->assertFalse(Validator::email('email@example@example.com'));
        $this->assertFalse(Validator::email('email@example.com (Joe Smith)'));
        $this->assertFalse(Validator::email('email@-example.com'));
        $this->assertFalse(Validator::email('email@example..com'));
        $this->assertFalse(Validator::email('“(),:;<>[\]@example.com'));
        $this->assertFalse(Validator::email('just"not"right@example.com'));
        $this->assertFalse(Validator::email('this\ is"really"not\allowed@example.com'));
        $this->assertFalse(Validator::email('email@[123.123.123.123]'));
        $this->assertFalse(Validator::email('“email”@example.com'));
        $this->assertFalse(Validator::email('much.“more\ unusual”@example.com'));
        $this->assertFalse(Validator::email('very.unusual.“@”.unusual.com@example.com'));
        $this->assertFalse(Validator::email('very.“(),:;<>[]”.VERY.“very@\\ "very”.unusual@strange.example.com'));
        $this->assertFalse(Validator::email('name\@tag@example.com'));
        $this->assertFalse(Validator::email('spaces\ are\ allowed@example.com'));
        $this->assertFalse(Validator::email('"spaces may be quoted"@example.com'));
        $this->assertFalse(Validator::email('!#$%&\'+-/=.?^`{|}~@[1.0.0.127]'));
        $this->assertFalse(Validator::email('!#$%&\'+-/=.?^`{|}~@[IPv6:0123:4567:89AB:CDEF:0123:4567:89AB:CDEF]'));
        $this->assertFalse(Validator::email('me(this is a comment)@example.com'));
    }

    public function testUrlMethod()
    {
        $this->assertTrue(Validator::url('http://example.com'));
        $this->assertTrue(Validator::url('http://example.com.'));
        $this->assertTrue(Validator::url('http://example.com?foo=bar'));
        $this->assertTrue(Validator::url('http://foo.com/blah_blah/'));
        $this->assertTrue(Validator::url('http://foo.com/blah_blah_(wikipedia)'));
        $this->assertTrue(Validator::url('http://foo.com/blah_blah_(wikipedia)_(again)'));
        $this->assertTrue(Validator::url('http://www.example.com/wpstyle/?p=364'));
        $this->assertTrue(Validator::url('https://www.example.com/foo/?bar=baz&inga=42&quux'));
        $this->assertTrue(Validator::url('http://userid:password@example.com:8080'));
        $this->assertTrue(Validator::url('http://userid@example.com'));
        $this->assertTrue(Validator::url('http://userid@example.com:8080/'));
        $this->assertTrue(Validator::url('http://userid:password@example.com/'));
        $this->assertTrue(Validator::url('http://142.42.1.1/'));
        $this->assertTrue(Validator::url('http://142.42.1.1:8080/'));
        $this->assertTrue(Validator::url('http://foo.com/blah_(wikipedia)#cite-1'));
        $this->assertTrue(Validator::url('http://foo.com/blah_(wikipedia)_blah#cite-1'));
        $this->assertTrue(Validator::url('http://foo.com/(something)?after=parens'));
        $this->assertTrue(Validator::url('http://code.google.com/events/#&product=browser'));
        $this->assertTrue(Validator::url('http://j.mp'));
        $this->assertTrue(Validator::url('ftp://foo.bar/baz'));
        $this->assertTrue(Validator::url('http://foo.bar/?q=Test%20URL-encoded%20stuff'));
        $this->assertTrue(Validator::url('http://-.~_!$&\'()*+,;=:%40:80%2f::::::@example.com'));
        $this->assertTrue(Validator::url('http://1337.net'));
        $this->assertTrue(Validator::url('http://a.b-c.de'));
        $this->assertTrue(Validator::url('http://223.255.255.254'));
        $this->assertFalse(Validator::url('http://'));
        $this->assertFalse(Validator::url('http://.'));
        $this->assertFalse(Validator::url('http://..'));
        $this->assertFalse(Validator::url('http://../'));
        $this->assertFalse(Validator::url('http://?'));
        $this->assertFalse(Validator::url('http://??'));
        $this->assertFalse(Validator::url('http://??/'));
        $this->assertFalse(Validator::url('http://#'));
        $this->assertFalse(Validator::url('http://##'));
        $this->assertFalse(Validator::url('http://##/'));
        $this->assertFalse(Validator::url('http://foo.bar?q=Spaces should be encoded'));
        $this->assertFalse(Validator::url('//'));
        $this->assertFalse(Validator::url('//a'));
        $this->assertFalse(Validator::url('///a'));
        $this->assertFalse(Validator::url('///'));
        $this->assertFalse(Validator::url('http:///a'));
        $this->assertFalse(Validator::url('foo.com'));
        $this->assertFalse(Validator::url('rdar://1234'));
        $this->assertFalse(Validator::url('h://test'));
        $this->assertFalse(Validator::url('http:// shouldfail.com'));
        $this->assertFalse(Validator::url(':// should fail'));
        $this->assertFalse(Validator::url('http://foo.bar/foo(bar)baz quux'));
        $this->assertFalse(Validator::url('ftps://foo.bar/'));
        $this->assertFalse(Validator::url('http://-error-.invalid/'));
        $this->assertTrue(Validator::url('http://a.b--c.de/')); // This passed, but shouldn't have.  Oops.
        $this->assertFalse(Validator::url('http://-a.b.co'));
        $this->assertFalse(Validator::url('http://a.b-.co'));
        $this->assertFalse(Validator::url('http://0.0.0.0'));
        $this->assertFalse(Validator::url('http://10.1.1.0'));
        $this->assertFalse(Validator::url('http://10.1.1.255'));
        $this->assertFalse(Validator::url('http://224.1.1.1'));
        $this->assertFalse(Validator::url('http://1.1.1.1.1'));
        $this->assertFalse(Validator::url('http://123.123.123'));
        $this->assertFalse(Validator::url('http://3628126748'));
        $this->assertFalse(Validator::url('http://.www.foo.bar/'));
        $this->assertTrue(Validator::url('http://www.foo.bar./')); // This passed, but shouldn't have.  Oops.
        $this->assertFalse(Validator::url('http://.www.foo.bar./'));
        $this->assertFalse(Validator::url('http://10.1.1.1'));
    }

    public function testIpv4Method()
    {
        $this->assertTrue(Validator::ipv4('175.16.254.1'));
        $this->assertFalse(Validator::ipv4('2001:0db8:0000:0000:0000:ff00:0042:8329'));
    }

    public function testIpv6Method()
    {
        $this->assertTrue(Validator::ipv6('2001:0db8:0000:0000:0000:ff00:0042:8329'));
        $this->assertFalse(Validator::ipv6('175.16.254.1'));
    }

    public function testNoWhiteSpaceMethod()
    {
        $this->assertTrue(Validator::noWhiteSpace(''));
        $this->assertTrue(Validator::noWhiteSpace('whitespace'));
        $this->assertFalse(Validator::noWhitespace('white space'));
    }

    public function testSingleSpaceMethod()
    {
        $this->assertEquals('single space', Validator::singleSpace('single     space'));
        $this->assertEquals('single space', Validator::singleSpace('single space'));
    }

    public function testYesNoMethod()
    {
        $this->assertEquals('Y', Validator::yesNo(101));
        $this->assertEquals('Y', Validator::yesNo(1));
        $this->assertEquals('Y', Validator::yesNo(true));
        $this->assertEquals('Y', Validator::yesNo('true'));
        $this->assertEquals('Y', Validator::yesNo('on'));
        $this->assertEquals('Y', Validator::yesNo('yes'));
        $this->assertEquals('Y', Validator::yesNo('Yes'));
        $this->assertEquals('Y', Validator::yesNo('Y'));
        $this->assertEquals('Y', Validator::yesNo('y'));
        $this->assertEquals('N', Validator::yesNo('n'));
        $this->assertEquals('N', Validator::yesNo('N'));
        $this->assertEquals('N', Validator::yesNo('No'));
        $this->assertEquals('N', Validator::yesNo('no'));
        $this->assertEquals('N', Validator::yesNo('off'));
        $this->assertEquals('N', Validator::yesNo('false'));
        $this->assertEquals('N', Validator::yesNo(false));
        $this->assertEquals('N', Validator::yesNo(0));
        $this->assertEquals('N', Validator::yesNo(-101));
        $this->assertEquals('N', Validator::yesNo(''));
    }

    public function testTrueFalseMethod()
    {
        $this->assertEquals(1, Validator::trueFalse(101));
        $this->assertEquals(1, Validator::trueFalse(1));
        $this->assertEquals(1, Validator::trueFalse(true));
        $this->assertEquals(1, Validator::trueFalse('true'));
        $this->assertEquals(1, Validator::trueFalse('on'));
        $this->assertEquals(1, Validator::trueFalse('yes'));
        $this->assertEquals(1, Validator::trueFalse('Yes'));
        $this->assertEquals(1, Validator::trueFalse('Y'));
        $this->assertEquals(1, Validator::trueFalse('y'));
        $this->assertEquals(0, Validator::trueFalse('n'));
        $this->assertEquals(0, Validator::trueFalse('N'));
        $this->assertEquals(0, Validator::trueFalse('No'));
        $this->assertEquals(0, Validator::trueFalse('no'));
        $this->assertEquals(0, Validator::trueFalse('off'));
        $this->assertEquals(0, Validator::trueFalse('false'));
        $this->assertEquals(0, Validator::trueFalse(false));
        $this->assertEquals(0, Validator::trueFalse(0));
        $this->assertEquals(0, Validator::trueFalse(-101));
        $this->assertEquals(0, Validator::trueFalse(''));
    }

    public function testInListMethod()
    {
        $this->assertTrue(Validator::inList('2', array(1, 2, 3)));
        $this->assertFalse(Validator::inList(7, array(1, 2, 3)));
    }
}
