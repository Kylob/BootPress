<?php

namespace BootPress\DebugBar;

use BootPress\Blog\Twig\Theme;
use DebugBar\DataFormatter\DataFormatterInterface;

class DataFormatter implements DataFormatterInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public function formatVar($data)
    {
        return Theme::dumper($data, array(
            'dumper' => 'cli',
            'indent' => "\t",
        ));
    }

    /**
     * @param float $seconds
     *
     * @return string
     */
    public function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000).' μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 1).' ms';
        }

        return round($seconds, 2).' s';
    }

    /**
     * @param string $size
     * @param int    $precision
     *
     * @return string
     */
    public function formatBytes($size, $precision = 1)
    {
        if ($size === 0 || $size === null) {
            return '0 B';
        }

        $sign = $size < 0 ? '-' : '';
        $size = abs($size);

        $base = log($size) / log(1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return $sign.round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)];
    }
}
