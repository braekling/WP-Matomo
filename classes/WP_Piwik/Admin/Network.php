<?php

	namespace WP_Piwik\Admin;

	class Network extends \WP_Piwik\Admin\Statistics {

		public function show() {
			parent::show();
		}
		
		public function printAdminScripts() {
			wp_enqueue_script('wp-piwik', self::$wpPiwik->getPluginURL().'js/wp-piwik.js', array(), self::$wpPiwik->getPluginVersion(), true);
            wp_enqueue_script ( 'wp-piwik-chartjs', self::$wpPiwik->getPluginURL() . 'js/chartjs/chart.min.js', "3.4.1" );
		}
		
		public function onLoad() {
			self::$wpPiwik->onloadStatsPage(self::$pageID);
		}
	}