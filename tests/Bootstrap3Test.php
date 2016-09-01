<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Bootstrap3\Component as Bootstrap;
use Symfony\Component\HttpFoundation\Request;

class Bootstrap3Test extends HTMLUnit_Framework_TestCase
{
    use \BootPress\Bootstrap3\Base;

    protected static $page;

    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/path/to/folder.html', 'GET', array('foo' => 'bar'));
        self::$page = Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
    }

    public function testBaseTrait()
    {
        $this->assertEquals('table table-responsive', $this->prefixClasses('table', array('responsive', 'striped'), 'responsive'));
        $this->assertEquals('table-responsive', $this->prefixClasses('table', array('responsive', 'striped'), 'responsive', 'exclude'));
        $this->assertEquals('<p class="new">Paragraph</p>', $this->addClass('<p>Paragraph</p>', array('p' => 'new')));
        $this->assertEquals('<p class="brand brand-new another">Paragraph</p>', $this->addClass('<p class="new">Paragraph</p>', array(
            'p' => array('brand', array('new'), 'another'),
        )));
        $this->assertTrue($this->firstTagAttributes('<p class="lead"></p>', $matches));
        $this->assertEquals(array(
            '<p class="lead">',
            'p',
            array('class' => 'lead'),
        ), $matches);
        $this->assertTrue($this->firstTagAttributes('<p class="lead" attribute></p>', $matches));
        $this->assertTrue($this->firstTagAttributes('<p class="lead"</p>', $matches));
        $this->assertEquals(array(
            '<p class="lead"</p>',
            'p',
            array('class' => 'lead'),
        ), $matches);
        $this->assertFalse($this->firstTagAttributes('<p class="lead"', $matches));
        $this->assertFalse($this->firstTagAttributes('', $matches));
    }

    public function testConstructor()
    {
        $bp = new Bootstrap();
        $this->assertAttributeInstanceOf('BootPress\Page\Component', 'page', $bp);
        $this->assertNull($bp->attribute);
    }

    public function testFormObject()
    {
        $bp = new Bootstrap();
        $form = $bp->form('example', 'post');
        
        // private getters default values
        $this->assertEquals(array('info'=>'glyphicon glyphicon-question-sign'), $form->prompt);
        $this->assertEquals('', $form->input);
        $this->assertEquals('form-horizontal', $form->align);
        $this->assertEquals('sm', $form->collapse);
        $this->assertEquals(2, $form->indent);
        $this->assertNull($form->missing);
        
        // form message, header, and close methods
        $form->align();
        $form->message('success', 'Custom message');
        $this->assertEqualsRegExp(array(
            '<div class="row"><div class="col-sm-12">',
                '<div class="alert alert-success alert-dismissable" role="alert">',
                    '<button type="button" class="close" data-dismiss="alert">',
                        '<span aria-hidden="true">&times;</span>',
                    '</button>',
                    'Custom message',
                '</div>',
                '<form name="example" method="post" action="http://website.com/path/to/folder.html?foo=bar&submitted=example" accept-charset="utf-8" autocomplete="off" class="form-horizontal">',
        ), $form->header());
        $this->assertEqualsRegExp(array(
                '</form>',
            '</div></div>',
        ), $form->close());
        
        // prepend and append prompts
        $form->prompt('prepend', '* ', 'required');
        $form->prompt('append', ':');
        
        // checkbox method
        $form->values['remember'] = 'Y';
        $form->menu('remember', array('Y' => 'Remember Me', 'N' => 'Accept Terms and Conditions'));
        $form->validator->set('remember', 'yesNo');
        $this->assertEqualsRegExp(array(
            '<div class="checkbox">',
                '<label><input type="checkbox" name="remember" value="Y" checked="checked"> Remember Me</label>',
            '</div>',
            ' <div class="checkbox">',
                '<label><input type="checkbox" name="remember" value="N"> Accept Terms and Conditions</label>',
            '</div>',
        ), $form->checkbox('remember'));
        
        $form->size('lg');
        $this->assertEqualsRegExp(array(
            '<label class="checkbox-inline input-lg"><input type="checkbox" name="remember" value="Y" checked="checked"> Remember Me</label>',
            ' <label class="checkbox-inline input-lg"><input type="checkbox" name="remember" value="N"> Accept Terms and Conditions</label>',
        ), $form->checkbox('remember', array(), 'inline'));
        $form->size('md');

        // radio method
        $form->values['gender'] = 'M';
        $gender = $form->menu('gender', array('M' => 'Male', 'F' => 'Female'));
        $form->validator->set('gender', "required|inList[{$gender}]");
        $this->assertEqualsRegExp(array(
            '<div class="radio">',
                '<label><input type="radio" name="gender" value="M" checked="checked" data-rule-required="true" data-rule-inList="M,F"> Male</label>',
            '</div>',
            ' <div class="radio">',
                '<label><input type="radio" name="gender" value="F"> Female</label>',
            '</div>',
        ), $form->radio('gender'));
        
        $form->size('sm');
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<label class="col-sm-2 control-label input-sm">* Prompt:</label>',
                '<div class="col-sm-10">',
                    '<p class="validation help-block" style="display:none;"></p>',
                    '<label class="radio-inline input-sm">',
                        '<input type="radio" name="gender" value="M" checked="checked" data-rule-required="true" data-rule-inList="M,F"> Male',
                    '</label>',
                    ' <label class="radio-inline input-sm">',
                        '<input type="radio" name="gender" value="F"> Female',
                    '</label>',
                '</div>',
            '</div>',
        ), $form->field('Prompt', $form->radio('gender', array(), 'inline')));
        $form->size('md');

        // group method
        $form->size('lg');
        $this->assertEqualsRegExp(array(
            '<div class="input-group input-group-lg">',
                '<div class="input-group-addon">$</div>',
                '<input type="text" name="field" id="field{{ [A-Z]+ }}">',
                '<div class="input-group-addon">.00</div>',
            '</div>',
        ), $form->group('$', '.00', $form->text('field')));
        
        $form->size('sm');
        $this->assertEqualsRegExp(array(
            '<div class="input-group input-group-sm">',
                '<div class="input-group-btn"><button type="button" class="btn btn-default">Go</button></div>',
                '<input type="text" name="field" id="field{{ [A-Z]+ }}">',
            '</div>',
        ), $form->group($bp->button('default', 'Go'), '', $form->text('field')));
        
        $form->size('md');
        $this->assertEqualsRegExp(array(
            '<div class="input-group">',
                '<div class="input-group-btn"><button type="button" class="btn btn-default">Go</button></div>',
                '<input type="text" name="field" id="field{{ [A-Z]+ }}">',
            '</div>',
        ), $form->group($bp->button('default', 'Go'), '', $form->text('field')));

        // field method
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<label>Prompt</label>',
                '<div class="col-sm-10">',
                    '<>',
                '</div>',
            '</div>',
        ), $form->field('<label>Prompt</label>', '<>'));
        
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<label class="col-sm-2 control-label" for="field{{ [A-Z]+ }}">Prompt:</label>',
                '<div class="col-sm-10">',
                    '<p class="validation help-block" style="display:none;"></p>',
                    '<input type="text" name="field" id="field{{ [A-Z]+ }}" class="form-control">',
                '</div>',
            '</div>',
        ), $form->field('Prompt', $form->text('field')));
        
        $form->align('inline');
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<label class="sr-only" for="field{{ [A-Z]+ }}">Prompt:</label>',
                '<p class="validation help-block" style="display:none;"></p>',
                '<select name="field" id="field{{ [A-Z]+ }}" class="form-control"></select>',
            '</div>',
        ), $form->field('Prompt', $form->select('field')));
        
        $form->align('collapse');
        $this->assertFalse($form->validator->certified());
        $form->validator->errors['field'] = 'Fix me'; // has no effect because the form was not submitted
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<label for="field{{ [A-Z]+ }}">Prompt: <i title="Toggle Info" class="glyphicon glyphicon-question-sign" style="cursor:pointer;" data-html="true" data-toggle="tooltip" data-placement="bottom" data-container="form[name=example]"></i></label>',
                '<p class="validation help-block" style="display:none;"></p>',
                '<textarea name="field" id="field{{ [A-Z]+ }}" cols="40" rows="10" class="form-control"></textarea>',
            '</div>',
        ), $form->field(array('Prompt' => 'Toggle Info'), $form->textarea('field')));
        
        $form->align('horizontal', 'md', 4);
        $form->prompt('info', 'fa fa-info-circle');
        $this->assertEqualsRegExp(array(
            '<div class="form-group has-error">',
                '<label class="col-md-4 control-label">Prompt: <i title="Toggle Info" class="fa fa-info-circle" style="cursor:pointer;" data-html="true" data-toggle="tooltip" data-placement="bottom" data-container="form[name=example]"></i></label>',
                '<div class="col-md-8">',
                    '<p class="validation help-block">What are you thinking?</p>',
                    '<p class="form-control-static">Helpful text</p>',
                '</div>',
            '</div>',
        ), $form->field(array('Prompt', 'Toggle Info'), '<p>Helpful text</p>', 'What are you thinking?'));

        // label and prompt methods()
        
        // submit method
        $this->assertEqualsRegExp(array(
            '<div class="form-group">',
                '<div class="col-md-8 col-md-offset-4">',
                    '<button type="submit" class="btn btn-primary" data-loading-text="Submitting...">Submit</button>',
                    ' <button type="reset" class="btn btn-default">Reset</button>',
                '</div>',
            '</div>',
        ), $form->submit('Submit', 'Reset'));
        
        // prompt(s)
        
        
    }

    public function testTableObject()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp('<table class="table table-responsive">', $bp->table->open('class=responsive'));
    }

    public function testNavbarObject()
    {
        $bp = new Bootstrap();

        // open and close
        $html = array(
            '<nav class="navbar navbar-fixed-top navbar-inverse">',
                '<div class="container-fluid">',
                    '<div class="navbar-header">',
                        '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar{{ [A-Z]+ }}">',
                        '<span class="sr-only">Toggle navigation</span>',
                        '<span class="icon-bar"></span>',
                        '<span class="icon-bar"></span>',
                        '<span class="icon-bar"></span>',
                        '</button>',
                        '<a class="navbar-brand" href="http://website.com/">Brand</a>',
                    '</div>',
                    '<div class="collapse navbar-collapse" id="navbar{{ [A-Z]+ }}"></div>',
                '</div>',
            '</nav>',
        );
        $this->assertEqualsRegExp($html, $bp->navbar->open('Brand', 'top', 'inverse').$bp->navbar->close());
        $this->assertEqualsRegExp(str_replace('navbar-fixed-top', 'navbar-static-top', $html), $bp->navbar->open(array('Brand' => 'http://website.com/'), 'static', 'inverse').$bp->navbar->close());
        $this->assertEqualsRegExp(str_replace(' navbar-fixed-top', '', $html), $bp->navbar->open(array('Brand' => 'http://website.com/'), 'inverse').$bp->navbar->close());

        // navbar
        $this->assertEqualsRegExp(array(
            '<ul class="nav navbar-nav">',
                '<li role="presentation" class="active">',
                    '<a href="#">Home</a>',
                '</li>',
                '<li role="presentation">',
                    '<a href="#">Work</a>',
                '</li>',
            '</ul>',
        ), trim($bp->navbar->menu(array(
            'Home' => '#',
            'Work' => '#',
        ), array('active' => 'Home'))));
        $this->assertEquals('<a href="http://website.com/sign-in.html" class="btn btn-default navbar-btn navbar-right">Sign In</a>', trim($bp->navbar->button('default', 'Sign In', array('href' => self::$page->url('base', 'sign-in'), 'pull' => 'right'))));

        // search
        $search = $bp->navbar->search(self::$page->url('base', 'search'));
        $this->assertContains('action="http://website.com/search.html"', $search);
        $this->assertContains('class="navbar-form navbar-right"', $search);

        // text
        $this->assertEquals('<p class="navbar-text">You <a href="#" class="navbar-link">link</a> me</p>', trim($bp->navbar->text('You <a href="#">link</a> me')));
    }

    public function testPaginationObject()
    {
        $bp = new Bootstrap();
        $this->assertInstanceOf('BootPress\Pagination\Component', $bp->pagination);
    }

    public function testRowColMethods()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="row">',
                '<div class="col-sm-3">left</div>',
                '<div class="col-sm-6">center</div>',
                '<div class="col-sm-3">right</div>',
            '</div>',
        ), $bp->row('sm', array(
            $bp->col(3, 'left'),
            $bp->col(6, 'center'),
            $bp->col(3, 'right'),
        )));
        
        $this->assertEqualsRegExp(array(
            '<div class="row">',
                '<div class="col-sm-6 col-md-4 col-md-push-8">left</div>',
                '<div class="clearfix visible-sm-block"></div>',
                '<div class="col-sm-6 col-md-8 col-md-pull-4">right</div>',
            '</div>',
        ), $bp->row('sm', 'md', array(
            $bp->col(6, '4 push-8', 'left'),
            '<div class="clearfix visible-sm-block"></div>',
            $bp->col(6, '8 pull-4', 'right'),
        )));
    }

    public function testListerMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<ul class="list-unstyled">',
                '<li>one</li>',
                '<li>two',
                    '<ul>',
                        '<li>three</li>',
                        '<li>four</li>',
                    '</ul>',
                '</li>',
                '<li>five</li>',
            '</ul>',
        ), $bp->lister('ul list-unstyled', array(
            'one',
            'two' => array('three', 'four'),
            'five',
        )));
        
        $this->assertEqualsRegExp(array(
            '<dl>',
                '<dt>one</dt>',
                '<dd>two</dd>',
                '<dt>three</dt>',
                '<dd>four</dd>',
                '<dd>five</dd>',
            '</dl>',
        ), $bp->lister('dl', array(
            'one' => 'two',
            'three' => array('four', 'five'),
        )));
    }

    public function testSearchMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<form name="search" method="get" action="http://website.com/" accept-charset="utf-8" autocomplete="off" role="search" class="form-horizontal">',
                '<div class="input-group">',
                    '<input type="text" class="form-control" placeholder="Search" name="search" id="search{{ [A-Z]+ }}" data-rule-required="true">',
                    '<div class="input-group-btn">',
                        '<button type="submit" class="btn btn-default" title="Search"><span class="glyphicon glyphicon-search"></span></button>',
                    '</div>',
                '</div>',
            '</form>',
        ), $bp->search('http://website.com/'));
        
        $this->assertEqualsRegExp(array(
            '<form name="search" method="get" action="http://website.com/" accept-charset="utf-8" autocomplete="off" role="search" class="form-horizontal">',
                '<input type="text" class="form-control input-lg" placeholder="Search" name="search" id="search{{ [A-Z]+ }}" data-rule-required="true">',
            '</form>',
        ), $bp->search('http://website.com/', array(
            'button' => false,
            'size' => 'lg',
        )));
    }

    public function testImgMethod()
    {
        $bp = new Bootstrap();
        $this->assertEquals('No Image', $bp->img('', 'width="75"', 'No Image', '#seo.jpg'));
        $this->assertEquals('<img src="/image.jpg#seo.jpg" width="75">', $bp->img('/image.jpg', 'width="75"', 'No Image', '#seo.jpg'));
    }

    public function testIconMethod()
    {
        $bp = new Bootstrap();
        $this->assertEquals('<span class="glyphicon glyphicon-search"></span>', $bp->icon('search'));
        $this->assertEquals('<span class="large fa fa-search"></span>', $bp->icon('search', 'fa', 'span class="large"'));
    }

    public function testButtonMethod()
    {
        $bp = new Bootstrap();
        $this->assertEquals('<button type="button" class="btn btn-primary">Primary</button>', $bp->button('primary', 'Primary'));
        
        $this->assertEquals('<a href="#" class="btn btn-xs btn-success">Link</a>', $bp->button('xs success', 'Link', array('href' => '#')));

        $this->assertEqualsRegExp(array(
            '<div class="btn-group">',
                '<button type="button" class="btn btn-danger dropdown-toggle" id="dropdown{{ [A-Z]+ }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></button>',
                '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                    '<li role="presentation" class="dropdown-header">Header</li>',
                    '<li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">Link</a></li>',
                    '<li role="presentation" class="divider"></li>',
                    '<li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" href="#">Separated</a></li>',
                '</ul>',
            '</div>',
        ), $bp->button('danger', 'Dropdown', array(
            'dropdown' => array(
                'Header',
                'Link' => '#',
                '',
                'Separated' => '#',
            ),
            'active' => 'Link',
            'disabled' => 'Separated',
        )));

        $this->assertEqualsRegExp(array(
            '<div class="btn-group">',
                '<button type="button" class="btn btn-danger">Dropdown</button>',
                '<button type="button" class="btn btn-danger dropdown-toggle" id="dropdown{{ [A-Z]+ }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span> <span class="sr-only">Toggle Dropdown</span></button>',
                '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                    '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Link</a></li>',
                '</ul>',
            '</div>',
        ), $bp->button('danger', array('split' => 'Dropdown'), array(
            'dropdown' => array('Link' => '#'),
        )));
    }

    public function testGroupMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="btn-group" role="group">',
                '<button type="button" class="btn btn-primary">Btn</button>',
                '<button type="button" class="btn btn-primary">Group</button>',
                '<div class="btn-group">',
                    '<button type="button" class="btn btn-primary">Split</button>',
                    '<button type="button" class="btn btn-primary dropdown-toggle" id="dropdown{{ [A-Z]+ }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span> <span class="sr-only">Toggle Dropdown</span></button>',
                    '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                        '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Works</a></li>',
                        '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Here</a></li>',
                        '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Too</a></li>',
                    '</ul>',
                '</div>',
                '<button type="button" class="btn btn-primary">Middle</button>',
            '</div>',
        ), $bp->group('', array(
            $bp->button('primary', 'Btn'),
            $bp->button('primary', 'Group'),
            $bp->button('primary', array('split' => 'Split'), array(
                'dropdown' => array(
                    'Works' => '#',
                    'Here' => '#',
                    'Too' => '#',
                ),
                'pull' => 'right',
            )),
            $bp->button('primary', 'Middle'),
        )));
        $this->assertEqualsRegExp(array(
            '<div class="btn-group btn-group-xs btn-group-justified" data-toggle="buttons-checkbox" role="group">',
                '<div class="btn-group" role="group">',
                    '<button type="button" class="btn btn-default">One</button>',
                '</div>',
                '<div class="btn-group" role="group">',
                    '<button type="button" class="btn btn-default">Two</button>',
                '</div>',
            '</div>',
        ), $bp->group('xs justified', array(
            $bp->button('default', 'One'),
            $bp->button('default', 'Two'),
        ), 'checkbox'));
    }

    public function testLinksMethod()
    {
        $bp = new Bootstrap();
        $url = self::$page->url('delete', '', '?');
        $urlquery = self::$page->url();
        $this->assertEqualsRegExp(array(
            '<a href="'.$url.'" class="special-class active">Home</a>',
            '<a href="http://website.com" class="special-class">Spelled out</a>',
            '<span class="dropdown">',
                '<a id="dropdown{{ [A-Z]+ }}" data-target="#" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="special-class">Dropdown <span class="caret"></span></a> ',
                '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                    '<li role="presentation" class="dropdown-header">Header</li>',
                    '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Action</a></li>',
                    '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Another Action</a></li>',
                '</ul>',
            '</span>',
            '<a href="'.$urlquery.'" class="special-class disabled">Disabled</a>',
        ), $bp->links('a special-class', array(
            'Home' => $url,
            '<a href="http://website.com">Spelled out</a>',
            'Dropdown' => array(
                'Header',
                'Action' => '#',
                'Another Action' => '#',
            ),
            'Disabled' => $urlquery,
        ), array('active' => 'url', 'disabled' => $urlquery)));
        
        $this->assertEqualsRegExp(array(
            '<li role="presentation" class="special">One</li>',
            '<li role="presentation" class="special">Two</li>',
            '<li role="presentation" class="special">Three</li>',
        ), $bp->links('li special', array('One', 'Two', 'Three')));
    }

    public function testTabsMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-tabs nav-justified">',
                '<li role="presentation" class="active"><a href="#">Nav</a></li>',
                '<li role="presentation" class="disabled"><a href="#">Tabs</a></li>',
                '<li role="presentation"><a href="#">Justified</a></li>',
            '</ul>',
        ), $bp->tabs(array(
            'Nav' => '#',
            'Tabs' => '#',
            'Justified' => '#',
        ), array('active' => 1, 'disabled' => 'Tabs', 'align' => 'justified')));

        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-tabs pull-right">',
                '<li role="presentation"><a href="#">Nav</a></li>',
                '<li role="presentation" class="active"><a href="#">Tabs</a></li>',
                '<li role="presentation"><a href="#">Justified</a></li>',
            '</ul>',
        ), $bp->tabs(array(
            'Nav' => '#',
            'Tabs' => '#',
            'Justified' => '#',
        ), array('active' => 'Tabs', 'align' => 'right')));
    }

    public function testPillsMethod()
    {
        $bp = new Bootstrap();
        
        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-pills nav-justified">',
                '<li role="presentation">',
                    '<a href="http://website.com/path/to/folder.html?foo=bar">Nav</a>',
                '</li>',
                '<li role="presentation"><a href="#">Pills</a></li>',
                '<li role="presentation"><a href="#">Justified</a></li>',
            '</ul>',
        ), $bp->pills(array(
            'Nav' => self::$page->url(),
            'Pills' => '#',
            'Justified' => '#',
        ), array('align' => 'justified')));
        
        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-pills nav-stacked">',
                '<li role="presentation" class="active">',
                    '<a href="http://website.com/path/to/folder.html?foo=bar">Nav</a>',
                '</li>',
                '<li class="dropdown">',
                    '<a id="dropdown{{ [A-Z]+ }}" data-target="#" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Pills <span class="caret"></span></a>',
                    '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                        '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Action</a></li>',
                    '</ul>',
                '</li>',
                '<li role="presentation"><a href="#">Justified</a></li>',
            '</ul>',
        ), $bp->pills(array(
            'Nav' => self::$page->url(),
            'Pills' => array(
                'Action' => '#',
            ),
            'Justified' => '#',
        ), array('active' => 'urlquery', 'align' => 'stacked')));

        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-pills pull-left">',
                '<li role="presentation">',
                    '<a href="http://website.com/path/to/folder.html?foo=bar">Nav</a>',
                '</li>',
                '<li role="presentation"><a href="#">Pills</a></li>',
                '<li role="presentation"><a href="#">Justified</a></li>',
            '</ul>',
        ), $bp->pills(array(
            'Nav' => self::$page->url(),
            'Pills' => '#',
            'Justified' => '#',
        ), array('align' => 'left')));
    }

    public function testBreadcrumbsMethod()
    {
        $bp = new Bootstrap();
        
        $this->assertEquals('', $bp->breadcrumbs(array()));
        
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="#">Home</a></li>',
                '<li class="dropdown">',
                    '<a href="#" data-toggle="dropdown" id="dropdown{{ [A-Z]+ }}">Library <b class="caret"></b></a>',
                    '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                        '<li role="presentation" class="dropdown-header">Drop</li>',
                        '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Down</a></li>',
                    '</ul>',
                '</li> ',
                '<li class="active">Data</li>',
            '</ul>',
        ), $bp->breadcrumbs(array(
            'Home' => '#',
            'Library' => array(
                'Drop',
                'Down' => '#',
            ),
            'Data' => '#',
        )));
        
        $this->assertEqualsRegExp(array(
            '<ul class="breadcrumb">',
                '<li><a href="#">Home</a></li>',
                '<li><a href="#">Link</a></li>',
                '<li class="active">Last</li>',
            '</ul>',
        ), $bp->breadcrumbs(array(
            'Home' => '#',
            'Link' => '#',
            'Last'
        )));
    }

    public function testLabelMethod()
    {
        $bp = new Bootstrap();
        $this->assertEquals('<span class="label label-primary">New</span>', $bp->label('primary', 'New'));
    }

    public function testBadgeMethod()
    {
        $bp = new Bootstrap();
        $this->assertEquals('<span class="badge pull-right">13</span>', $bp->badge(13, 'right'));
    }

    public function testAlertMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="alert alert-danger" role="alert">',
                '<strong>Danger</strong> Alert',
            '</div>',
        ), $bp->alert('danger', '<strong>Danger</strong> Alert', false));
        $this->assertEqualsRegExp(array(
            '<div class="alert alert-success" role="alert">',
                'New <a href="#" class="alert-link">message</a>',
            '</div>',
        ), $bp->alert('success', 'New <a href="#">message</a>', false));
        $this->assertEqualsRegExp(array(
            '<div class="alert alert-info alert-dismissable" role="alert">',
                '<button type="button" class="close" data-dismiss="alert">',
                    '<span aria-hidden="true">&times;</span>',
                '</button>',
                '<h1 class="alert-heading">Info</h1> status',
            '</div>',
        ), $bp->alert('info', '<h1>Info</h1> status'));
    }

    public function testProgressMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="progress">',
                '<div class="progress-bar progress-bar-info" style="width:60%;" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>',
            '</div>',
        ), $bp->progress(60, 'info', 'display'));
        $this->assertEqualsRegExp(array(
            '<div class="progress">',
                '<div class="progress-bar progress-bar-warning" style="width:25%;" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">',
                    '<span class="sr-only">25% Complete</span>',
                '</div>',
                '<div class="progress-bar progress-bar-success" style="width:50%;" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">',
                    '<span class="sr-only">50% Complete</span>',
                '</div>',
            '</div>',
        ), $bp->progress(array(25, 50), array('warning', 'success')));
    }

    public function testMediaMethod()
    {
        $bp = new Bootstrap();
        // parent => child => array()
        $multi = $bp->media(array(
            0 => array(
                1 => array('class' => 'special', 'Parent Image', '<h1>Parent</h1>', 'Parent Image'),
                4 => array('Sibling Image', '<h4>Sibling</h4>', 'Sibling Image'),
            ),
            1 => array(
                2 => array('Child Image', '<h2>Child</h2>', 'Child Image'),
            ),
            2 => array(
                3 => array('<img src="image.jpg" alt="Grandchild Image">', '<h3>Grandchild</h3>', 'Grandchild Image'),
            ),
        ));
        $media = $bp->media(
            array('class' => 'special', 'Parent Image', '<h1>Parent</h1>', 'Parent Image', array('Child Image', '<h2>Child</h2>', 'Child Image', array('<img src="image.jpg" alt="Grandchild Image">', '<h3>Grandchild</h3>', 'Grandchild Image'))),
            array('Sibling Image', '<h4>Sibling</h4>', 'Sibling Image')
        );
        
        $html = array(
            '<div class="media special">',
                '<div class="media-left">Parent Image</div>',
                '<div class="media-body">',
                    '<h1 class="media-heading">Parent</h1>',
                    '<div class="media">',
                        '<div class="media-left">Child Image</div>',
                        '<div class="media-body">',
                            '<h2 class="media-heading">Child</h2>',
                            '<div class="media">',
                                '<div class="media-left"><img src="image.jpg" alt="Grandchild Image" class="media-object"></div>',
                                '<div class="media-body"><h3 class="media-heading">Grandchild</h3></div>',
                                '<div class="media-right">Grandchild Image</div>',
                            '</div>',
                        '</div>',
                        '<div class="media-right">Child Image</div>',
                    '</div>',
                '</div>',
                '<div class="media-right">Parent Image</div>',
            '</div>',
            '<div class="media">',
                '<div class="media-left">Sibling Image</div>',
                '<div class="media-body"><h4 class="media-heading">Sibling</h4></div>',
                '<div class="media-right">Sibling Image</div>',
            '</div>',
        );
        $this->assertEqualsRegExp($html, $multi);
        $this->assertEqualsRegExp($html, $media);
    }

    public function testListGroupMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<ul class="list-group">',
                '<li class="list-group-item">Unordered</li>',
                '<li class="list-group-item">List</li>',
                '<li class="list-group-item"><span class="badge"></span>Group</li>',
            '</ul>',
        ), $bp->listGroup(array(
            'Unordered',
            'List',
            $bp->badge(0).'Group',
        )));
        $this->assertEqualsRegExp(array(
            '<div class="list-group">',
                '<a class="list-group-item active" href="#">Anchor</a>',
                '<a class="list-group-item" href="#">List</a>',
                '<a class="list-group-item" href="#">Group<span class="badge">1</span></a>',
            '</div>',
        ), $bp->listGroup(array(
            'Anchor' => '#',
            'List' => '#',
            'Group'.$bp->badge(1) => '#',
        ), 'Anchor'));
        $this->assertEqualsRegExp(array(
            '<div class="list-group">',
                '<a class="list-group-item active" href="#">',
                    '<h4 class="list-group-item-heading">Custom Content</h4>',
                    '<p class="list-group-item-text">Paragraph</p>',
                '</a>',
                '<a class="list-group-item" href="#">',
                    '<span class="badge">2</span>',
                    '<h4 class="list-group-item-heading">Linked List Group</h4>',
                    '<p class="list-group-item-text">Paragraph</p>',
                '</a>',
            '</div>',
        ), $bp->listGroup(array(
            '<h4>Custom Content</h4><p>Paragraph</p>' => '#',
            $bp->badge(2).'<h4>Linked List Group</h4><p>Paragraph</p>' => '#',
        ), 1));
    }

    public function testPanelMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="panel panel-success">',
                '<div class="panel-heading">Header</div>',
                '<div class="panel-body">Default</div>',
                '<div class="panel-footer">Footer</div>',
            '</div>',
        ), $bp->panel('success', array(
            'header' => 'Header',
            'body' => 'Default',
            'footer' => 'Footer',
        )));
        $this->assertEqualsRegExp(array(
            '<div class="panel panel-default">',
                '<div class="panel-heading">Header</div>',
                '<ul class="list-group">',
                    '<li class="list-group-item">Unordered</li>',
                    '<li class="list-group-item">List</li>',
                '</ul>',
            '</div>',
        ), $bp->panel('default', array(
            'header' => 'Header',
            $bp->listGroup(array('Unordered', 'List')),
        )));
    }

    public function testToggleMethod()
    {
        $bp = new Bootstrap();
        $toggle = array(
            'Toggle#toggle' => 'Toggle Content',
            'Profile' => 'Profile Content',
            'Dropdown' => array(
                'Header',
                'This' => 'This Content',
                'That' => 'That Content',
            ),
        );

        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-tabs" role="tablist">',
                '<li role="presentation">',
                    '<a href="#toggle" aria-controls="toggle" role="tab" data-toggle="tab">Toggle</a>',
                '</li>',
                '<li role="presentation" class="disabled">',
                    '<a href="#tabs{{ [A-Z]+ }}" aria-controls="tabs{{ [A-Z]+ }}" role="tab" data-toggle="tab">Profile</a>',
                '</li>',
                '<li class="dropdown active">',
                    '<a id="dropdown{{ [A-Z]+ }}" data-target="#" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Dropdown <span class="caret"></span></a> ',
                    '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                        '<li role="presentation" class="dropdown-header">#tabs{{ [A-Z]+ }}</li>',
                        '<li role="presentation" class="active">',
                            '<a role="menuitem" tabindex="-1" href="#tabs{{ [A-Z]+ }}" data-toggle="tab">This</a>',
                        '</li>',
                        '<li role="presentation">',
                            '<a role="menuitem" tabindex="-1" href="#tabs{{ [A-Z]+ }}" data-toggle="tab">That</a>',
                        '</li>',
                    '</ul>',
                '</li>',
            '</ul>',
            '<div class="tab-content">',
                '<div role="tabpanel" class="tab-pane fade" id="toggle">Toggle Content</div>',
                '<div role="tabpanel" class="tab-pane fade" id="tabs{{ [A-Z]+ }}">Profile Content</div>',
                '<div role="tabpanel" class="tab-pane fade" id="tabs{{ [A-Z]+ }}">Header</div>',
                '<div role="tabpanel" class="tab-pane fade in active" id="tabs{{ [A-Z]+ }}">This Content</div>',
                '<div role="tabpanel" class="tab-pane fade" id="tabs{{ [A-Z]+ }}">That Content</div>',
            '</div>',
        ), $bp->toggle('tabs', $toggle, array('active' => 4, 'fade')));

        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-pills" role="tablist">',
                '<li role="presentation" class="active">',
                    '<a href="#toggle" aria-controls="toggle" role="pill" data-toggle="pill">Toggle</a>',
                '</li>',
                '<li role="presentation" class="disabled">',
                    '<a href="#tabs{{ [A-Z]+ }}" aria-controls="tabs{{ [A-Z]+ }}" role="pill" data-toggle="pill">Profile</a>',
                '</li>',
                '<li class="dropdown">',
                    '<a id="dropdown{{ [A-Z]+ }}" data-target="#" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a> ',
                    '<ul class="dropdown-menu" aria-labelledby="dropdown{{ [A-Z]+ }}">',
                        '<li role="presentation" class="dropdown-header">#tabs{{ [A-Z]+ }}</li>',
                        '<li role="presentation">',
                            '<a role="menuitem" tabindex="-1" href="#tabs{{ [A-Z]+ }}" data-toggle="pill">This</a>',
                        '</li>',
                        '<li role="presentation">',
                            '<a role="menuitem" tabindex="-1" href="#tabs{{ [A-Z]+ }}" data-toggle="pill">That</a>',
                        '</li>',
                    '</ul>',
                '</li>',
            '</ul>',
            '<div class="tab-content">',
                '<div role="tabpanel" class="tab-pane in active" id="toggle">Toggle Content</div>',
                '<div role="tabpanel" class="tab-pane" id="tabs{{ [A-Z]+ }}">Profile Content</div>',
                '<div role="tabpanel" class="tab-pane" id="tabs{{ [A-Z]+ }}">Header</div>',
                '<div role="tabpanel" class="tab-pane" id="tabs{{ [A-Z]+ }}">This Content</div>',
                '<div role="tabpanel" class="tab-pane" id="tabs{{ [A-Z]+ }}">That Content</div>',
            '</div>',
        ), $bp->toggle('pills', $toggle, array('active' => 1)));
        
        unset($toggle['Dropdown']);
        $this->assertEqualsRegExp(array(
            '<ul class="nav nav-tabs nav-justified" role="tablist">',
                '<li role="presentation" class="disabled">',
                    '<a href="#toggle" aria-controls="toggle" role="tab" data-toggle="tab">Toggle</a>',
                '</li>',
                '<li role="presentation">',
                    '<a href="#tabs{{ [A-Z]+ }}" aria-controls="tabs{{ [A-Z]+ }}" role="tab" data-toggle="tab">Profile</a>',
                '</li>',
            '</ul>',
            '<div class="tab-content">',
                '<div role="tabpanel" class="tab-pane" id="toggle">Toggle Content</div>',
                '<div role="tabpanel" class="tab-pane" id="tabs{{ [A-Z]+ }}">Profile Content</div>',
            '</div>',
        ), $bp->toggle('tabs', $toggle, array(
            'align' => 'justified',
            'disabled' => 1,
        )));
    }

    public function testAccordionMethod()
    {
        $bp = new Bootstrap();
        $this->assertEqualsRegExp(array(
            '<div class="panel-group" id="accordion{{ [A-Z]+ }}" role="tablist" aria-multiselectable="true">',
                '<div class="panel panel-info">',
                    '<div class="panel-heading" role="tab" id="heading{{ [A-Z]+ }}">',
                        '<h4 class="panel-title">',
                            '<a role="button" data-toggle="collapse" data-parent="#accordion{{ [A-Z]+ }}" href="#collapse{{ [A-Z]+ }}" aria-expanded="true" aria-controls="collapse{{ [A-Z]+ }}">',
                                '<span>Group</span> #1',
                            '</a>',
                        '</h4>',
                    '</div>',
                    '<div id="collapse{{ [A-Z]+ }}" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading{{ [A-Z]+ }}">',
                        '<div class="panel-body">Content One</div>',
                    '</div>',
                '</div>',
                '<div class="panel panel-info">',
                    '<div class="panel-heading" role="tab" id="heading{{ [A-Z]+ }}">',
                        '<h4 class="panel-title">',
                            '<a role="button" data-toggle="collapse" data-parent="#accordion{{ [A-Z]+ }}" href="#collapse{{ [A-Z]+ }}" aria-expanded="false" aria-controls="collapse{{ [A-Z]+ }}">',
                                'Group #2',
                            '</a>',
                        '</h4>',
                    '</div>',
                    '<div id="collapse{{ [A-Z]+ }}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading{{ [A-Z]+ }}">',
                        '<div class="panel-body">Content Two</div>',
                    '</div>',
                '</div>',
            '</div>',
        ), $bp->accordion('info', array(
            '<h4><span>Group</span> #1</h4>' => 'Content One',
            '<h4>Group #2</h4>' => 'Content Two',
        ), 1));
    }

    public function testCarouselMethod()
    {
        $bp = new Bootstrap();
        $html = array(
            '<div id="carousel{{ [A-Z]+ }}" class="carousel slide" data-ride="carousel" data-interval="2000">',
                '<ol class="carousel-indicators">',
                    '<li data-target="#carousel{{ [A-Z]+ }}" data-slide-to="0" class="active"></li>',
                    '<li data-target="#carousel{{ [A-Z]+ }}" data-slide-to="1"></li>',
                '</ol>',
                '<div class="carousel-inner" role="listbox">',
                    '<div class="item active">',
                        '<img src="#" alt="one">',
                        '<div class="carousel-caption"><h3>Header</h3></div>',
                    '</div>',
                    '<div class="item">',
                        '<img src="#" alt="two">',
                        '<div class="carousel-caption"><p>Paragraph</p></div>',
                    '</div>',
                '</div>',
                '<a class="left carousel-control" href="#carousel{{ [A-Z]+ }}" role="button" data-slide="prev">',
                    '<span aria-hidden="true" class="glyphicon glyphicon-chevron-left"></span> <span class="sr-only">Previous</span>',
                '</a>',
                '<a class="right carousel-control" href="#carousel{{ [A-Z]+ }}" role="button" data-slide="next">',
                    '<span aria-hidden="true" class="glyphicon glyphicon-chevron-right"></span> <span class="sr-only">Next</span>',
                '</a>',
            '</div>',
        );

        $this->assertEqualsRegExp($html, $bp->carousel(array(
            '<img src="#" alt="one">' => '<h3>Header</h3>',
            '<img src="#" alt="two">' => '<p>Paragraph</p>',
        ), array('interval' => 2000)));

        $this->assertEqualsRegExp(str_replace('chevron', 'hand', $html), $bp->carousel(array(
            '<img src="#" alt="one">' => '<h3>Header</h3>',
            '<img src="#" alt="two">' => '<p>Paragraph</p>',
        ), array(
            'interval' => 2000,
            'controls' => array('hand-left', 'hand-right'),
        )));
    }
}
