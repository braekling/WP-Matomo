<?php
	
	namespace WP_Piwik\Logger;

	class Screen extends \WP_Piwik\Logger {
	
		private $logs = array();
		
		private function formatMicrotime($loggerTime) {
			return sprintf('[%6s sec]',number_format($loggerTime,3));
		}
		
		public function __construct($loggerName) {
			add_action(is_admin()?'admin_footer':'wp_footer', array($this, 'echoResults'));
			parent::__construct($loggerName);
		}
		
		public function loggerOutput($loggerTime, $loggerMessage) {
			$this->logs[] = $this->formatMicrotime($loggerTime).' '.$loggerMessage;
		}
		
		public function echoResults() {
			echo '<pre>';
			print_r($this->logs);
			echo '</pre>';			
		} 
    }