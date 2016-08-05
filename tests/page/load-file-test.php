<?php

use BootPress\Page\Component as Page;
use Symfony\Component\HttpFoundation\Request;

extract($params);

if (isset($echo)) {
    echo $echo;
} elseif (isset($null)) {
    $export = null;
} elseif (isset($bool)) {
    $export = $bool;
} elseif (isset($numeric)) {
    $export = $numeric;
} elseif (isset($array)) {
    $export = $array;
} elseif (isset($class)) {
    $export = new Page(new Request());
}
