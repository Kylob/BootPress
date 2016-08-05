<?php

namespace BootPress\Bootstrap;

use BootPress\Table\Component as Table;

class Bootstrap3Table extends Table
{
    use Base;

    public function open($vars = '', $caption = '')
    {
        $vars = $this->values($vars);
        if (isset($vars['class'])) {
            $vars['class'] = $this->prefixClasses('table', array('responsive', 'bordered', 'striped', 'hover', 'condensed'), $vars['class']);
        }

        return parent::open($vars, $caption);
    }
}
