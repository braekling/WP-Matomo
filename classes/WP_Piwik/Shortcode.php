<?php
	
	namespace WP_Piwik;
	
	class Shortcode {
		
		private $available = array(
			'opt-out' => 'OptOut',
			'post' => 'Post',
			'overview' => 'Overview'
		), $content;
		
		public function __construct($attributes, $wpPiwik, $settings) {
			$wpPiwik->log('Check requested shortcode widget '.$attributes['module']);
			if (isset($attributes['module']) && isset($this->available[$attributes['module']])) {
				$wpPiwik->log('Add shortcode widget '.$this->available[$attributes['module']]);
				$class = '\\WP_Piwik\\Widget\\'.$this->available[$attributes['module']];
				$widget = new $class($wpPiwik, $settings, null, null, null, $attributes, true);
				$widget->show();
				$this->content = $widget->get();
			}
		}
		
		public function get() {
			return $this->content;
		}
		
	}