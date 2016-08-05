<?php

namespace BootPress\Tests;

use BootPress\Table\Component as Table;

class TableTest extends HTMLUnit_Framework_TestCase
{
    public function testTableClass()
    {
        $tb = new Table();
        $this->assertEqualsRegExp('<table class="responsive"><caption>caption</caption>', $tb->open('class=responsive', 'caption'));
        $this->assertEqualsRegExp('<thead><tr>', $tb->head());
        $this->assertEqualsRegExp('</tr><tr>', $tb->head());
        $this->assertEqualsRegExp('</tr></thead><tfoot><tr>', $tb->foot());
        $this->assertEqualsRegExp('</tr><tr>', $tb->foot());
        $this->assertEqualsRegExp('</tr></tfoot><tbody><tr>', $tb->row());
        $this->assertEqualsRegExp('<td align="center">', $tb->cell(array('align'=>'center')));
        $this->assertEqualsRegExp('</td><td>', $tb->cell());
        $this->assertEqualsRegExp('</td></tr><tr>', $tb->row('', 'align=center'));
        $this->assertEqualsRegExp('<td align="center"><p>Some content</p>', $tb->cell('align=center', '<p>Some content</p>'));
        $this->assertEqualsRegExp('</td><td align="center"><p>Even more content</p>', $tb->cell('', '<p>Even more content</p>'));
        $this->assertEqualsRegExp('</td></tr></tbody></table>', $tb->close());

        $this->assertEqualsRegExp('<table>', $tb->open());
        $this->assertEqualsRegExp('<thead><tr>', $tb->head());
        $this->assertEqualsRegExp('</tr></thead></table>', $tb->close());

        $this->assertEqualsRegExp('<table>', $tb->open());
        $this->assertEqualsRegExp('<tfoot><tr>', $tb->foot());
        $this->assertEqualsRegExp('</tr></tfoot></table>', $tb->close());

        // test wrapUp('row') head first
        $this->assertEqualsRegExp('<thead><tr>', $tb->head());
        $this->assertEqualsRegExp('</tr></thead><tbody><tr>', $tb->row());
        $tb->close();

        // test wrapUp('foot') body first
        $this->assertEqualsRegExp('<tbody><tr>', $tb->row());
        $this->assertEqualsRegExp('</tr></tbody><tfoot><tr>', $tb->foot());
        $tb->close();

        // test wrapUp('head') body first
        $this->assertEqualsRegExp('<tbody><tr>', $tb->row());
        $this->assertEqualsRegExp('</tr></tbody><thead><tr>', $tb->head());
        $tb->close();

        // test wrapUp('head') foot first
        $this->assertEqualsRegExp('<tfoot><tr>', $tb->foot());
        $this->assertEqualsRegExp('</tr></tfoot><thead><tr>', $tb->head());
        $tb->close();
    }
}
