<?php
$wpRootDir = isset($wpRootDir)?$wpRootDir:'../../../../';
require ($wpRootDir.'wp-load.php');

require_once ('../classes/WP_Piwik/Settings.php');
require_once ('../classes/WP_Piwik/Logger.php');
require_once ('../classes/WP_Piwik/Logger/Dummy.php');

$logger = new WP_Piwik\Logger\Dummy ( __CLASS__ );
$settings = new WP_Piwik\Settings ( $logger );

$protocol = (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] != 'off') ? 'https' : 'http';

switch ($settings->getGlobalOption ( 'piwik_mode' )) {
	case 'php' :
		$PIWIK_URL = $protocol . ':' . $settings->getGlobalOption ( 'proxy_url' );
		break;
	case 'pro' :
		$PIWIK_URL = 'https://' . $settings->getGlobalOption ( 'piwik_user' ) . '.piwik.pro/';
		break;
	default :
		$PIWIK_URL = $settings->getGlobalOption ( 'piwik_url' );
}

if (substr ( $PIWIK_URL, 0, 2 ) == '//')
	$PIWIK_URL = (isset ( $_SERVER ['HTTPS'] ) ? 'https:' : 'http:') . $PIWIK_URL;

$TOKEN_AUTH = $settings->getGlobalOption ( 'piwik_token' );
$timeout = $settings->getGlobalOption ( 'connection_timeout' );
$useCurl = (
	(function_exists('curl_init') && ini_get('allow_url_fopen') && $settings->getGlobalOption('http_connection') == 'curl') || (function_exists('curl_init') && !ini_get('allow_url_fopen'))
);

$settings->getGlobalOption ( 'http_connection' );

ini_set ( 'display_errors', 0 );