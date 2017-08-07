<?php

// Re-write Piwik Pro configuration to default http configuration
if ($this->isConfigured() && self::$settings->getGlobalOption ( 'piwik_mode' ) == 'pro') {
    self::$settings->setGlobalOption ( 'piwik_url', 'https://' . self::$settings->getGlobalOption ( 'piwik_user' ) . '.piwik.pro/');
    self::$settings->setGlobalOption ( 'piwik_mode', 'http' );
}

// If post annotations are already enabled, choose all existing post types
if (self::$settings->getGlobalOption('add_post_annotations'))
    self::$settings->setGlobalOption('add_post_annotations', get_post_types());

self::$settings->save ();