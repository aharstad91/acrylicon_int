<?php
// components.php

if (!function_exists('register_component')) {
    function register_component($name, $callback) {
        global $wp_components;
        if (!isset($wp_components)) {
            $wp_components = array();
        }
        $wp_components[$name] = $callback;
    }
}

if (!function_exists('get_component')) {
    function get_component($name, $args = array()) {
        global $wp_components;
        if (isset($wp_components[$name])) {
            return call_user_func($wp_components[$name], $args);
        }
        return '';
    }
}