<?php

namespace BootPress\DebugBar\Collector;

use BootPress\Blog\Twig\Theme;
use BootPress\DebugBar\Component as DebugBar;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class Twig extends DataCollector implements Renderable, AssetProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return array(
            $name => array(
                'icon' => 'leaf',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => $name.'.templates',
                'default' => '[]',
            ),
            $name.':badge' => array(
                'map' => $name.'.count',
                'default' => 0,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets()
    {
        return array(
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $time = DebugBar::$debugbar->hasCollector('time') ? DebugBar::$debugbar['time'] : false;
        $templates = Theme::$templates;
        $data = array(
            'Total' => 0,
            'Rendered' => array(),
            'Global' => Theme::$instance ? Theme::$instance->vars : array(),
            'Vars' => array(),
            'Output' => array(),
            'Errors' => array(),
        );
        foreach ($templates as $twig) {
            if ($time) {
                $time->addMeasure('twig.render('.$twig['template'].')', $twig['start'], $twig['end']);
            }
            $data['Total'] += $twig['time'];
            $data['Rendered'][] = $twig['template'].' ('.$this->formatDuration($twig['time']).')';
            $data['Vars'][$twig['template']] = $twig['vars'];
            if (isset($twig['output'])) {
                $data['Output'][$twig['template']] = $twig['output'];
            } else {
                $data['Errors'][] = array($twig['template'] => $this->formatVar($twig['error']));
            }
        }
        $data['Total'] = count($templates).' templates were rendered ('.$this->formatDuration($data['Total']).')';
        foreach ($data as $key => $var) {
            if (empty($var)) {
                unset($data[$key]);
            } elseif (!is_string($var)) {
                $data[$key] = $this->formatVar($var);
            }
        }

        return array(
            'count' => count($templates),
            'templates' => $data,
        );
    }
}
