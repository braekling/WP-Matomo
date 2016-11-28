<?php

	namespace WP_Piwik;

	abstract class Request {
		
		protected static $wpPiwik, $settings, $debug, $lastError = '', $requests = array(), $results = array(), $isCacheable = array(), $piwikVersion;
		
		public function __construct($wpPiwik, $settings) {
			self::$wpPiwik = $wpPiwik;
			self::$settings = $settings;
			self::register('API.getPiwikVersion', array());
		}
		
		public function reset() {
			self::$debug = null;
			self::$requests = array();
			self::$results = array();
			self::$isCacheable = array();
			self::$piwikVersion = null;
		}
		
		public static function register($method, $parameter) {
			if ($method == 'API.getPiwikVersion')
				$id = 'global.getPiwikVersion';
			else
				$id = 'method='.$method.self::parameterToString($parameter);
			if ( 
				in_array( $method, array( 'API.getPiwikVersion', 'SitesManager.getJavascriptTag', 'SitesManager.getSitesWithAtLeastViewAccess', 'SitesManager.getSitesIdFromSiteUrl', 'SitesManager.addSite', 'SitesManager.updateSite', 'SitesManager.getSitesWithAtLeastViewAccess' ) ) ||
				!isset( $parameter['date'] ) ||
				!isset( $parameter['period'] ) ||
				substr($parameter['date'], 0, 4) == 'last' ||
				$parameter['date'] == 'today' ||
				( $parameter['period'] == 'day' && $parameter['date'] == date('Ymd') ) ||
				( $parameter['period'] == 'month' && $parameter['date'] == date('Ym') ) ||
				( $parameter['period'] == 'week' && $parameter['date'] == date( 'Ymd', strtotime( "last Monday" ) ) ) 
			) self::$isCacheable[$id] = false;
			else self::$isCacheable[$id] = $method.'-'.serialize($parameter);
			if (!isset(self::$requests[$id]))
				self::$requests[$id] = array('method' => $method, 'parameter' => $parameter);
			return $id;
		}

		private static function parameterToString($parameter) {
			$return = '';
			if (is_array($parameter))
				foreach ($parameter as $key => $value)
					$return .= '&'.$key.'='.$value;
			return $return;
		}

		public function perform($id) {
			if ( self::$settings->getGlobalOption('cache') && false !== ( $cached = get_transient( 'wp-piwik_c_'.md5(self::$isCacheable[$id] ) ) ) ) {
				if (!empty ( $cached ) && !(! empty ( $cached['result'] ) &&  $cached['result'] == 'error') ) { 
					self::$wpPiwik->log("Deliver cached data: ".$id);
					return $cached;
				}
			}
			self::$wpPiwik->log("Perform request: ".$id);
			if (!isset(self::$requests[$id]))
				return array('result' => 'error', 'message' => 'Request '.$id.' was not registered.');
			elseif (!isset(self::$results[$id])) {
				$this->request($id);
			}
			if ( isset ( self::$results[$id] ) ) {
				if ( self::$settings->getGlobalOption('cache') && self::$isCacheable[$id] ) {
					set_transient( 'wp-piwik_c_'.md5(self::$isCacheable[$id]) , self::$results[$id], WEEK_IN_SECONDS );
				}
				return self::$results[$id];
			} else return false;
		}
		
		public function getDebug($id) {
			return isset( self::$debug[$id] )? self::$debug[$id] : false;
		}
		
		protected function buildURL($config, $urlDecode = false) {
			$url = 'method='.($config['method']).'&idSite='.self::$settings->getOption('site_id');
			foreach ($config['parameter'] as $key => $value)
				$url .= '&'.$key.'='.($urlDecode?urldecode($value):$value);
			return $url;
		}
		
		protected function unserialize($str) {
			self::$wpPiwik->log("Result string: ".$str);
		    return ($str == json_decode(false, true) || @json_decode($str, true) !== false)?json_decode($str, true):array();
		}

		public static function getLastError() {
			return self::$lastError;
		}			
		
		abstract protected function request($id);
			
	}