<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 1/10/18
 * Time: 10:37 PM
 */

try {
    $path = wa()->getAppPath(null, 'shop') . '/plugins/generator/js/generator.min.min.js';
    waFiles::delete($path);
}
catch (waException $e) {

}