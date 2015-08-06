<?php

	namespace WP_Piwik\Widget;

	class Seo extends \WP_Piwik\Widget {
	
		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$this->parameter = array(
				'url' => get_bloginfo('url')
			);
			$this->title = $prefix.__('SEO', 'wp-piwik');
			$this->method = 'SEO.getRank';
		}
		
		public function show() {
			$response = self::$wpPiwik->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				echo '<div class="table"><table class="widefat"><tbody>';
				if (is_array($response))
					foreach ($response as $val)
						echo '<tr><td>'.(isset($val['logo_link']) && !empty($val['logo_link'])?'<a href="'.$val['logo_link'].'" title="'.$val['logo_tooltip'].'">'.$val['label'].'</a>':$val['label']).'</td><td>'.$val['rank'].'</td></tr>';
				else echo '<tr><td>SEO module currently not available.</td></tr>';
				echo '</tbody></table></div>';
			}
		}
		
	}