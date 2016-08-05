<?php

namespace BootPress\Bootstrap;

class Component
{
    public static function version($number)
    {
        $version = (int) substr($number, 0, 1);
        switch ($version) {
            case 3:
                return new Bootstrap3($number);
            break;
            default:
                throw new \Exception("Bootstrap version {$version} is not currently supported.");
        }
    }
}
