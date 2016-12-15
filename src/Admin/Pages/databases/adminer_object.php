<?php

if (!function_exists('adminer_object')) {

    /** @see https://github.com/vrana/adminer/commit/7a33661b721714a8b266bf57c0065ae653bb8097 */
    function adminer_object()
    {
        class AdminerObject extends Adminer
        {
            public function head()
            {
                $file = __DIR__.'/adminer.css';
                if (is_file($file)) {
                    echo '<link rel="stylesheet" type="text/css" href="'.h(preg_replace('~\\?.*~', '', ME)).'?adminer=css&amp;updated='.filemtime($file).'">';
                }

                return false;
            }

            public function login($login, $password)
            {
                return true;
            }
        }

        return new AdminerObject();
    }
}
