<?php

// Re-write Piwik Pro configuration to default http configuration
if ($this->isConfigured() && self::$settings->getGlobalOption ( 'piwik_mode' ) == 'pro') {
    self::$settings->setGlobalOption ( 'piwik_url', 'https://' . self::$settings->getGlobalOption ( 'piwik_user' ) . '.piwik.pro/');
    self::$settings->setGlobalOption ( 'piwik_mode', 'http' );
}
self::$settings->save ();