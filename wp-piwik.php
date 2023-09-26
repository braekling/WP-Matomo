<?php
/*
Plugin Name: Connect Matomo

Plugin URI: http://wordpress.org/extend/plugins/wp-piwik/

Description: Adds Matomo statistics to your dashboard and is able to add the Matomo Tracking Code to your blog.

Version: 1.1.0
Author: Andr&eacute; Br&auml;kling
Author URI: https://www.braekling.de
Text Domain: wp-piwik
Domain Path: /languages
License: GPL3

******************************************************************************************
    Copyright (C) 2009-today Andre Braekling (email: webmaster@braekling.de)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************************/

if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit ();
}

/**
 * Define Connect Matomo autoloader
 * @param string $class
 * @return void
 */
function connectMatomoAutoloader(string $class): void
{
    if (substr($class, 0, 9) == 'Connect_Matomo' . NAMESPACE_SEPARATOR) {
        $class = str_replace('.', '', str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, substr($class, 9)));
        require_once 'classes' . DIRECTORY_SEPARATOR . 'ConnectMatomo' . DIRECTORY_SEPARATOR . $class . '.php';
    }
}

/**
 * Show notice about outdated PHP version
 * @return void
 */
function connectMatomoPhpError(): void
{
    echo '<div class="error"><p>';
    printf(__('Connect Matomo requires at least PHP 7.0. ' .
        'You are using the deprecated version %s. Please update PHP to use Connect Matomo.', 'wp-piwik'), PHP_VERSION);
    echo '</p></div>';
}

/**
 * Load text domain
 * @return void
 */
function connectMatomoLoadTextDomain(): void
{
    load_plugin_textdomain(
        'wp-piwik',
        false,
        plugin_basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR
    );
}

add_action('plugins_loaded', 'connectMatomoLoadTextDomain');

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    add_action('admin_notices', 'connectMatomoPhpError');
} else {
    require_once CONNECT_MATOMO_PATH . 'config.php';
    require_once CONNECT_MATOMO_PATH . 'classes' . DIRECTORY_SEPARATOR . 'ConnectMatomo.php';
    spl_autoload_register('connectMatomoAutoloader');
    $GLOBALS ['connect_matomo_debug'] = false;
    if (class_exists('ConnectMatomo')) {
        add_action('init', 'connectMatomoLoader');
    }
}

function connectMatomoLoader(): void
{
    $GLOBALS ['connectMatomo'] = ConnectMatomo::getInstance();
}
