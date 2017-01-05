<?php

namespace BootPress\DebugBar\Collector;

use BootPress\Database\Component as Database;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class Queries extends DataCollector implements Renderable, AssetProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'queries';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return array(
            $name => array(
                'icon' => 'database',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => $name,
                'default' => '[]',
            ),
            $name.':badge' => array(
                'map' => $name.'.nb_statements',
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
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $logs = array(
            'nb_databases' => 0,
            'nb_statements' => 0,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0,
        );
        foreach (Database::logs() as $key => $db) {
            if (empty($db['queries'])) {
                continue;
            } // driver, duration, queries
            ++$logs['nb_databases'];
            $logs['accumulated_duration'] += $db['duration'];
            $driver = (is_file($db['driver'])) ? basename($db['driver']) : $db['driver'];
            foreach ($db['queries'] as $query) { // sql, count, error?, duration
                $log = array(
                    'sql' => $query['sql'],
                    'duration' => $query['duration'],
                    'duration_str' => $this->formatDuration($query['duration']),
                    'connection' => $driver,
                );
                if ($query['count'] > 1) {
                    // $each = round(($query['duration'] / $query['count']) * 1000);
                    $log['duration_str'] .= '/'.$query['count'].'x';
                }
                if (isset($query['error'])) {
                    $log['is_success'] = false;
                    $errors = array();
                    foreach ($query['error'] as $count => $error) {
                        $errors = '('.$count.') '.$error;
                    }
                    $log['error_message'] = implode("\n", $errors);
                    ++$logs['nb_failed_statements'];
                }
                ++$logs['nb_statements'];
                $logs['statements'][] = $log;
            }
        }
        $logs['accumulated_duration_str'] = $this->formatDuration($logs['accumulated_duration']);

        return $logs;
    }
}
