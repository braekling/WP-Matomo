<?php

	namespace WP_Piwik\Widget;

	class Plugins extends \WP_Piwik\Widget {
	
		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpPiwik->getPiwikSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Plugins', 'wp-piwik').' ('.__($timeSettings['description'],'wp-piwik').')';
			$this->method = 'DevicePlugins.getPlugin';
		}
		
		public function show() {
			$response = self::$wpPiwik->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				$tableHead = array(__('Plugin', 'wp-piwik'), __('Visits', 'wp-piwik'), __('Percent', 'wp-piwik'));
				$tableBody = array();
				$count = 0;
				if (is_array($response))
				    foreach ($response as $row) {
					    $count++;
					    $tableBody[] = array($row['label'], $row['nb_visits'], $row['nb_visits_percentage']);
					    if ($count == 10) break;
				    }
				$this->table($tableHead, $tableBody, null);
			}
		}
		
	}