<?php

namespace BootPress\Tests;

use BootPress\Pagination\Component as Pagination;

class PaginationTest extends \BootPress\HTMLUnit\Component
{
    public function testBootstrapLinks()
    {
        $pagination = new Pagination('bootstrap');
        $this->assertEquals('', $pagination->links());
        $this->assertFalse($pagination->set('page', 10, 'http://example.com'));
        $pagination->total(100);
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li class="active"><span>1</span></li>',
                '<li><a href="http://example.com?page=2of10">2</a></li>',
                '<li><a href="http://example.com?page=3of10">3</a></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li class="disabled"><span>&hellip;</span></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=2of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertEqualsRegExp('<ul class="pager"><li class="next"><a href="http://example.com?page=2of10">Next &raquo;</a></li></ul>', $pagination->pager());
        $this->assertEquals(' LIMIT 0, 10', $pagination->limit);
        $this->assertFalse($pagination->last_page);
        $this->assertEquals(1, $pagination->current_page);
        $this->assertEquals(10, $pagination->number_pages);
        $this->assertEquals('', $pagination->previous_url);
        $this->assertEquals('http://example.com?page=2of10', $pagination->next_url);
        $this->assertNull($pagination->start);
        $this->assertFalse(isset($pagination->start));
        
    }

    public function testZurbFoundationLinks()
    {
        $pagination = new Pagination('zurb_foundation');
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=2of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="current"><a href="">2</a></li>',
                '<li><a href="http://example.com?page=3of10">3</a></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li class="unavailable"><a href="">&hellip;</a></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=3of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertEquals(' LIMIT 10, 10', $pagination->limit);
        $this->assertFalse($pagination->last_page);
        $this->assertEquals(2, $pagination->current_page);
        $this->assertEquals(10, $pagination->number_pages);
        $this->assertEquals('http://example.com', $pagination->previous_url);
        $this->assertEquals('http://example.com?page=3of10', $pagination->next_url);
    }

    public function testSemanticUiLinks()
    {
        $pagination = new Pagination('semantic_ui');
        $this->assertFalse($pagination->set('page', 10, 'http://example.com?page=3'));
        $pagination->total(100);
        $this->assertEqualsRegExp(array(
            '<div class="ui pagination menu">',
                '<a class="item" href="http://example.com?page=2of10"><i class="left arrow icon"></i></a>',
                '<a class="item" href="http://example.com">1</a>',
                '<a class="item" href="http://example.com?page=2of10">2</a>',
                '<div class="active item">3</div>',
                '<a class="item" href="http://example.com?page=4of10">4</a>',
                '<a class="item" href="http://example.com?page=5of10">5</a>',
                '<a class="item" href="http://example.com?page=6of10">6</a>',
                '<a class="item" href="http://example.com?page=7of10">7</a>',
                '<div class="disabled item">&hellip;</div>',
                '<a class="item" href="http://example.com?page=10of10">10</a>',
                '<a class="item" href="http://example.com?page=4of10"><i class="right arrow icon"></i></a>',
            '</div>',
        ), $pagination->links());
        $this->assertEquals(' LIMIT 20, 10', $pagination->limit);
        $this->assertFalse($pagination->last_page);
        $this->assertEquals(3, $pagination->current_page);
        $this->assertEquals(10, $pagination->number_pages);
        $this->assertEquals('http://example.com?page=2of10', $pagination->previous_url);
        $this->assertEquals('http://example.com?page=4of10', $pagination->next_url);
    }

    public function testMaterializeLinks()
    {
        $pagination = new Pagination('materialize');
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=4of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li class="waves-effect"><a href="http://example.com?page=3of10"><i class="material-icons">keyboard_arrow_left</i></a></li>',
                '<li class="waves-effect"><a href="http://example.com">1</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=2of10">2</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=3of10">3</a></li>',
                '<li class="active"><a href="#!">4</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=5of10">5</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=6of10">6</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=7of10">7</a></li>',
                '<li class="disabled"><a href="#!">&hellip;</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=10of10">10</a></li>',
                '<li class="waves-effect"><a href="http://example.com?page=5of10"><i class="material-icons">keyboard_arrow_right</i></a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertEquals(' LIMIT 30, 10', $pagination->limit);
        $this->assertFalse($pagination->last_page);
        $this->assertEquals(4, $pagination->current_page);
        $this->assertEquals(10, $pagination->number_pages);
        $this->assertEquals('http://example.com?page=3of10', $pagination->previous_url);
        $this->assertEquals('http://example.com?page=5of10', $pagination->next_url);
    }

    public function testUIKitLinks()
    {
        $pagination = new Pagination('uikit');
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=5of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="uk-pagination">',
                '<li><a href="http://example.com?page=4of10"><i class="uk-icon-angle-double-left"></i></a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li><a href="http://example.com?page=2of10">2</a></li>',
                '<li><a href="http://example.com?page=3of10">3</a></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li class="uk-active"><span>5</span></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li><a href="http://example.com?page=8of10">8</a></li>',
                '<li><a href="http://example.com?page=9of10">9</a></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=6of10"><i class="uk-icon-angle-double-right"></i></a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertEqualsRegExp(array(
            '<ul class="uk-pagination">',
                '<li class="uk-pagination-previous"><a href="http://example.com?page=4of10"><i class="uk-icon-angle-double-left"></i> Previous</a></li>',
                '<li class="uk-pagination-next"><a href="http://example.com?page=6of10">Next <i class="uk-icon-angle-double-right"></i></a></li>',
            '</ul>',
        ), $pagination->pager());
    }

    public function testDotLinks()
    {
        $pagination = new Pagination();
        $pagination->set('page', 10, 'http://example.com?page=2of5');
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="active"><span>2</span></li>',
                '<li><a href="http://example.com?page=3of5">3</a></li>',
                '<li><a href="http://example.com?page=4of5">4</a></li>',
                '<li><a href="http://example.com?page=5of5">5</a></li>',
                '<li><a href="http://example.com?page=3of5">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=6of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com?page=5of10">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li><a href="http://example.com?page=2of10">2</a></li>',
                '<li><a href="http://example.com?page=3of10">3</a></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li class="active"><span>6</span></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li><a href="http://example.com?page=8of10">8</a></li>',
                '<li><a href="http://example.com?page=9of10">9</a></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=7of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=7of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com?page=6of10">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="disabled"><span>&hellip;</span></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li class="active"><span>7</span></li>',
                '<li><a href="http://example.com?page=8of10">8</a></li>',
                '<li><a href="http://example.com?page=9of10">9</a></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=8of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=8of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com?page=7of10">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="disabled"><span>&hellip;</span></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li class="active"><span>8</span></li>',
                '<li><a href="http://example.com?page=9of10">9</a></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=9of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertTrue($pagination->set('page', 10, 'http://example.com?page=9of10'));
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com?page=8of10">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="disabled"><span>&hellip;</span></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li><a href="http://example.com?page=8of10">8</a></li>',
                '<li class="active"><span>9</span></li>',
                '<li><a href="http://example.com?page=10of10">10</a></li>',
                '<li><a href="http://example.com?page=10of10">&raquo;</a></li>',
            '</ul>',
        ), $pagination->links());
        $this->assertFalse($pagination->set('page', 10, 'http://example.com?page=10of10'));
        $pagination->total(100);
        $this->assertEqualsRegExp(array(
            '<ul class="pagination">',
                '<li><a href="http://example.com?page=9of10">&laquo;</a></li>',
                '<li><a href="http://example.com">1</a></li>',
                '<li class="disabled"><span>&hellip;</span></li>',
                '<li><a href="http://example.com?page=4of10">4</a></li>',
                '<li><a href="http://example.com?page=5of10">5</a></li>',
                '<li><a href="http://example.com?page=6of10">6</a></li>',
                '<li><a href="http://example.com?page=7of10">7</a></li>',
                '<li><a href="http://example.com?page=8of10">8</a></li>',
                '<li><a href="http://example.com?page=9of10">9</a></li>',
                '<li class="active"><span>10</span></li>',
            '</ul>',
        ), $pagination->links());
    }

    public function testLinksArray()
    {
        $pagination = new Pagination();
        $pagination->set('page', 10, 'http://example.com?page=3of10');
        $this->assertEquals(array(
            '<li><a href="http://example.com">1</a></li>',
            '<li><a href="http://example.com?page=2of10">2</a></li>',
            '<li class="active"><span>3</span></li>',
            '<li><a href="http://example.com?page=4of10">4</a></li>',
            '<li><a href="http://example.com?page=5of10">5</a></li>',
            '<li><a href="http://example.com?page=6of10">6</a></li>',
            '<li><a href="http://example.com?page=7of10">7</a></li>',
            '<li class="disabled"><span>&hellip;</span></li>',
            '<li><a href="http://example.com?page=10of10">10</a></li>',
        ), $pagination->links(3, 'array'));
    }

    public function testPagerTitles()
    {
        $pagination = new Pagination();
        $this->assertEqualsRegExp(array(
            '<ul class="pager">',
                '<li class="previous"><a href="http://example.com/previous.html">&laquo; Previously</a></li>',
                '<li class="next"><a href="http://example.com/next.html">Next Up &raquo;</a></li>',
            '</ul>',
        ), $pagination->pager(
            array('url' => 'http://example.com/previous.html', 'title' => 'Previously'),
            array('url' => 'http://example.com/next.html', 'title' => 'Next Up')
        ));
    }
}
