<?php

	namespace WP_Piwik\Widget;

	class Noresult extends \WP_Piwik\Widget {
	
		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpPiwik->getPiwikSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Site Search', 'wp-piwik').' ('.__($timeSettings['description'],'wp-piwik').')';
			$this->method = 'Actions.getSiteSearchNoResultKeywords';
		}
		
		public function show() {
			$response = self::$wpPiwik->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				$tableHead = array(__('Keyword', 'wp-piwik'), __('Requests', 'wp-piwik'), __('Bounced', 'wp-piwik'));
				$tableBody = array();
				$count = 0;
				if (is_array($response))
				    foreach ($response as $row) {
					    $count++;
					    $tableBody[] = array($row['label'], $row['nb_visits'], $row['bounce_rate']);
					    if ($count == 10) break;
				    }
				$this->table($tableHead, $tableBody, null);
			}
		}
		
	}