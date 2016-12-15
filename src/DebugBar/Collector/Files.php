<?php

namespace BootPress\DebugBar\Collector;

use BootPress\DebugBar\Component as Profiler;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class Files extends DataCollector implements Renderable
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'files';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return array(
            "$name" => array(
                'icon' => 'files-o',
                'widget' => 'PhpDebugBar.Widgets.MessagesWidget',
                'map' => $name.'.messages',
                'default' => '{}',
            ),
            "$name:badge" => array(
                'map' => $name.'.count',
                'default' => 'null',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $messages = array();
        $files = array_values(Profiler::get('files'));
        $offset = 0;
        foreach (Profiler::get('breakpoints') as $bp) {
            if (is_array($bp)) { // all but the last
                list($name, $slice) = $bp;
                $length = $slice - $offset;
                if ($length > 0) {
                    $messages[] = array(
                        'message' => '"'.$name.'" BreakPoint ['.$length.' Files]',
                        'is_string' => true,
                    );
                    $loaded = array_slice($files, $offset, $length);
                    sort($loaded);
                    foreach ($loaded as $file) {
                        $messages[] = array(
                            'message' => '.../'.$file,
                            'is_string' => true,
                        );
                    }
                    $offset += $length;
                }
            }
        }

        return array(
            'messages' => $messages,
            'count' => $slice,
        );
    }
}
