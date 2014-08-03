<?php

namespace FizzFuzz\Utils;

use Symfony\Component\Yaml\Yaml;

class YamlRuleset
{
    public static function validate($path)
    {
        try {
            Yaml::parse($path);
            return true;
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            return false;
        }
    }

    public static function parse($path)
    {
        return Yaml::parse($path);
    }
}
