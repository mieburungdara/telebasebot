<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('get_config_values')) {
    function get_config_values() {
        $config_file_path = FCPATH . '../config/config.php';

        if (!file_exists($config_file_path)) {
            return false;
        }

        $config_file_content = file_get_contents($config_file_path);

        $config = [];
        $pattern = '/define\s*\(\s*\'([A-Z_]+)\'\s*,\s*\'(.*?)\'\s*\)/';
        preg_match_all($pattern, $config_file_content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $config[$match[1]] = $match[2];
        }

        return $config;
    }
}
