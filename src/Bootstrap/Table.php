<?php

namespace BootPress\Bootstrap;

class Table extends \BootPress\Table\Component
{
    use Base;

    /**
     * Create a ``<table>``.
     * 
     * @param string|array $vars    ``<table>`` attributes.  Any '**responsive**', '**bordered**', '**striped**', '**hover**', and/or '**condensed**' class will be prefixed with a '**table-...**', and include the '**table** class as well.
     * @param string       $caption Table ``<caption>``.
     * 
     * @return string
     *
     * ```php
     * echo $bp->table->open('class=responsive striped');
     *     echo $bp->table->head();
     *     echo $bp->table->cell('', 'One');
     *     echo $bp->table->row();
     *     echo $bp->table->cell('', 'Two');
     *     echo $bp->table->foot();
     *     echo $bp->table->cell('', 'Three');
     * echo $bp->table->close();
     * ```
     */
    public function open($vars = '', $caption = '')
    {
        $vars = $this->values($vars);
        if (isset($vars['class'])) {
            $vars['class'] = $this->prefixClasses('table', array('responsive', 'bordered', 'striped', 'hover', 'condensed'), $vars['class']);
        }

        return parent::open($vars, $caption);
    }
}
