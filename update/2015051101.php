<?php

// Get & delete old version's options
if (self::$settings->checkNetworkActivation ()) {
	$oldGlobalOptions = get_site_option ( 'wp-piwik_global-settings', array () );
	delete_site_option('wp-piwik_global-settings');
} else {
	$oldGlobalOptions = get_option ( 'wp-piwik_global-settings', array () );
	delete_option('wp-piwik_global-settings');
}

$oldOptions = get_option ( 'wp-piwik_settings', array () );
delete_option('wp-piwik_settings');
	
if (self::$settings->checkNetworkActivation ()) {
	global $wpdb;
	$aryBlogs = \WP_Piwik\Settings::getBlogList();
	if (is_array($aryBlogs))
		foreach ($aryBlogs as $aryBlog) {
			$oldOptions = get_blog_option ( $aryBlog['blog_id'], 'wp-piwik_settings', array () );
			if (!$this->isConfigured())
				foreach ( $oldOptions as $key => $value )
					self::$settings->setOption ( $key, $value, $aryBlog['blog_id'] );
			delete_blog_option($aryBlog['blog_id'], 'wp-piwik_settings');
		}
}

if (!$this->isConfigured()) {
	if (!$oldGlobalOptions['add_tracking_code']) $oldGlobalOptions['track_mode'] = 'disabled';
	elseif (!$oldGlobalOptions['track_mode']) $oldGlobalOptions['track_mode'] = 'default';
	elseif ($oldGlobalOptions['track_mode'] == 1) $oldGlobalOptions['track_mode'] = 'js';
	elseif ($oldGlobalOptions['track_mode'] == 2) $oldGlobalOptions['track_mode'] = 'proxy';

	// Store old values in new settings
	foreach ( $oldGlobalOptions as $key => $value )
		self::$settings->setGlobalOption ( $key, $value );
	foreach ( $oldOptions as $key => $value )
		self::$settings->setOption ( $key, $value );
}

self::$settings->save ();