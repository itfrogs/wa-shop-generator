<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 24.10.2023
 * Time: 14:39
 */

try {
    $path = wa()->getAppPath(null, 'shop') . '/plugins/generator/js/generator.min.min.js';
    waFiles::delete($path);

    $path = wa()->getAppPath(null, 'shop') . '/plugins/generator/js/generator.min.min.min.js';
    waFiles::delete($path);
}
catch (waException $e) {

}