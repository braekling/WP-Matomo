<?php
$aryRemoveOptions = array (
		'wp-piwik_siteid',
		'wp-piwik_404',
		'wp-piwik_scriptupdate',
		'wp-piwik_dashboardid',
		'wp-piwik_jscode' 
);
foreach ( $aryRemoveOptions as $strRemoveOption )
	delete_option ( $strRemoveOption );