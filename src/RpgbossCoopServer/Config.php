<?php
/**
 * Created by PhpStorm.
 * User: hendrikweiler
 * Date: 20.02.15
 * Time: 18:01
 */

namespace RpgbossCoopServer;


class Config {

    protected static $data;

    public static function Load() {

        if(file_exists("../src/config.json")) {
            $string = file_get_contents("../src/config.json");
        } else {
            $string = file_get_contents(realpath(dirname(__FILE__)) . "/../config.json");
        }
        static::$data=json_decode($string,true);
        return static::$data;
    }

}