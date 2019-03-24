<?php

	namespace WP_Piwik\Widget;

	class OptOut extends \WP_Piwik\Widget {
	
		public $className = __CLASS__;
		
		protected function configure($prefix = '', $params = array()) {
			$this->parameter = $params;
		}

		public function show() {
			$protocol = (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] != 'off') ? 'https' : 'http';
			switch (self::$settings->getGlobalOption ( 'piwik_mode' )) {
				case 'php' :
					$PIWIK_URL = $protocol . ':' . self::$settings->getGlobalOption ( 'proxy_url' );
					break;
				case 'cloud' :
					$PIWIK_URL = 'https://' . self::$settings->getGlobalOption ( 'piwik_user' ) . '.innocraft.cloud/';
					break;
                case 'cloud-matomo':
                    $PIWIK_URL = 'https://' . self::$settings->getGlobalOption ( 'matomo_user' ) . '.matomo.cloud/';
                    break;
				default :
					$PIWIK_URL = self::$settings->getGlobalOption ( 'piwik_url' );
			}
			$this->out ( '<iframe frameborder="no" width="'.(isset($this->parameter['width'])?$this->parameter['width']:'').'" height="'.(isset($this->parameter['height'])?$this->parameter['height']:'').'" src="'.$PIWIK_URL.'index.php?module=CoreAdminHome&action=optOut&'.(isset($this->parameter['idsite'])?'idsite='.$this->parameter['idsite'].'&':'').'language='.(isset($this->parameter['language'])?$this->parameter['language']:'en').'"></iframe>' );
		}
		
	}