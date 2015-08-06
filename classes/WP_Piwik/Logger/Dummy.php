<?php

	namespace WP_Piwik\Logger;
	
	class Dummy extends \WP_Piwik\Logger {

		public function loggerOutput($loggerTime, $loggerMessage) {}
		
    }