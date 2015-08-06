<?php

	namespace WP_Piwik\Widget;

	class Post extends \WP_Piwik\Widget {
	
		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			global $post;
			$this->parameter = array(
				'idSite' => self::$wpPiwik->getPiwikSiteId($this->blogId),
				'period' => 'range',
				'date'  => isset($params['range'])?$params['range']:'last30',
				'key' => isset($params['key'])?$params['key']:null,
				'pageUrl' => isset($params['url'])?$params['url']:urlencode(get_permalink($post->ID)),
			);
			$this->title = $prefix.__('Overview', 'wp-piwik').' ('.__($this->parameter['date'],'wp-piwik').')';
			$this->method = 'Actions.getPageUrl';
		}
		
		public function show() {
			$response = self::$wpPiwik->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				if (isset($response[0]))
					$response = $response[0];
				if ($this->parameter['key']) {
					echo isset($response[$this->parameter['key']])?$response[$this->parameter['key']]:'<em>not defined</em>';
					return;
				}
				$time = isset($response['entry_sum_visit_length'])?$this->timeFormat($response['entry_sum_visit_length']):'-';
				$avgTime = isset($response['avg_time_on_page'])?$this->timeFormat($response['avg_time_on_page']):'-';
				$tableHead = null;
				$tableBody = array(
					array(__('Visitors', 'wp-piwik').':', $this->value($response, 'nb_visits')),
					array(__('Unique visitors', 'wp-piwik').':', $this->value($response, 'sum_daily_nb_uniq_visitors')),
					array(__('Page views', 'wp-piwik').':', $this->value($response, 'nb_hits').' (&#216; '.$this->value($response, 'entry_nb_actions').')'),
					array(__('Total time spent', 'wp-piwik').':', $time),
					array(__('Time/visit', 'wp-piwik').':', $avgTime),
					array(__('Bounce count', 'wp-piwik').':', $this->value($response, 'entry_bounce_count').' ('.$this->value($response, 'bounce_rate').')'),
					array(__('Min. generation time', 'wp-piwik').':', $this->value($response, 'min_time_generation')),
					array(__('Max. generation time', 'wp-piwik').':', $this->value($response, 'max_time_generation'))
				); 
				$tableFoot = (self::$settings->getGlobalOption('piwik_shortcut')?array(__('Shortcut', 'wp-piwik').':', '<a href="'.self::$settings->getGlobalOption('piwik_url').'">Piwik</a>'.(isset($aryConf['inline']) && $aryConf['inline']?' - <a href="?page=wp-piwik_stats">WP-Piwik</a>':'')):null);
				$this->table($tableHead, $tableBody, $tableFoot);
			}
		}
		
	}