<?php

	namespace WP_Piwik;
	
	abstract class Admin {
		
		protected static $wpPiwik, $pageID, $settings;
		
		public function __construct($wpPiwik, $settings) {
			self::$wpPiwik = $wpPiwik;
			self::$settings = $settings;
		}

		abstract public function show();
		
		abstract public function printAdminScripts();
				
		public function printAdminStyles() {
			wp_enqueue_style('wp-piwik', self::$wpPiwik->getPluginURL().'css/wp-piwik.css', array(), self::$wpPiwik->getPluginVersion());
		}
		
		public function onLoad() {}

	}