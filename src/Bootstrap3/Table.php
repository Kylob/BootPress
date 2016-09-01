<?php

namespace BootPress\Bootstrap3;

class Table extends \BootPress\Table\Component
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
