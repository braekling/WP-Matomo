<?php
$aryWPMUConfig = get_site_option ( 'wpmu-piwik_global-settings', false );
if (self::$settings->checkNetworkActivation () && $aryWPMUConfig) {
	foreach ( $aryWPMUConfig as $key => $value )
		self::$settings->setGlobalOption ( $key, $value );
	delete_site_option ( 'wpmu-piwik_global-settings' );
	self::$settings->setGlobalOption ( 'auto_site_config', true );
} else
	self::$settings->setGlobalOption ( 'auto_site_config', false );