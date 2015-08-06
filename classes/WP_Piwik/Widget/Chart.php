<?php

	namespace WP_Piwik\Widget;

	use WP_Piwik\Widget;

	class Chart extends Widget {
	
		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();			
			$this->parameter = array(
				'idSite' => self::$wpPiwik->getPiwikSiteId($this->blogId),
				'period' => isset($params['period'])?$params['period']:$timeSettings['period'],
				'date'  => 'last'.($timeSettings['period']=='day'?'30':'12'),
				'limit' => null
			);
			$this->title = $prefix.__('Visitors', 'wp-piwik').' ('.__($this->rangeName(),'wp-piwik').')';
			$this->method = array('VisitsSummary.getVisits', 'VisitsSummary.getUniqueVisitors', 'VisitsSummary.getBounceCount', 'VisitsSummary.getActions');
			$this->context = 'normal';
			wp_enqueue_script('wp-piwik', self::$wpPiwik->getPluginURL().'js/wp-piwik.js', array(), self::$wpPiwik->getPluginVersion(), true);
			wp_enqueue_script('wp-piwik-jqplot',self::$wpPiwik->getPluginURL().'js/jqplot/wp-piwik.jqplot.js',array('jquery'));
			wp_enqueue_style('wp-piwik', self::$wpPiwik->getPluginURL().'css/wp-piwik.css',array(),self::$wpPiwik->getPluginVersion());
			add_action('admin_head-index.php', array($this, 'addHeaderLines'));
		}
		
		public function addHeaderLines() {
			echo '<!--[if IE]><script language="javascript" type="text/javascript" src="'.self::$wpPiwik->getPluginURL().'js/jqplot/excanvas.min.js"></script><![endif]-->';
			echo '<link rel="stylesheet" href="'.self::$wpPiwik->getPluginURL().'js/jqplot/jquery.jqplot.min.css" type="text/css"/>';
			echo '<script type="text/javascript">var $j = jQuery.noConflict();</script>';			
		}
		
		public function show() {
			$response = array();
			$success = true;
			foreach ($this->method as $method) {
				$response[$method] = self::$wpPiwik->request($this->apiID[$method]);
				if (!empty($response[$method]['result']) && $response[$method]['result'] ='error')
					$success = false;
			}
			if (!$success)
				echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response[$method]['message'], ENT_QUOTES, 'utf-8');
			else {
				$values = $labels = $bounced =  $unique = '';
				$count = $uniqueSum = 0;
				if (is_array($response['VisitsSummary.getVisits']))
					foreach ($response['VisitsSummary.getVisits'] as $date => $value) {
						$count++;
						$values .= $value.',';
						$unique .= $response['VisitsSummary.getUniqueVisitors'][$date].',';
						$bounced .= $response['VisitsSummary.getBounceCount'][$date].',';
						if ($this->parameter['period'] == 'week') {
							preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/", $date, $dateList);
							$textKey = $this->dateFormat($dateList[0], 'short_week');
						} else $textKey = substr($date, -2);
						$labels .= '['.$count.',"'.$textKey.'"],';
						$uniqueSum += $response['VisitsSummary.getActions'][$date];
					} 
				else {
					$values = '0,';
					$labels = '[0,"-"],';
					$unique = '0,';
					$bounced = '0,';
				}
				$average = round($uniqueSum/30,0);
				$values = substr($values, 0, -1);
				$unique = substr($unique, 0, -1);
				$labels = substr($labels, 0, -1);
				$bounced = substr($bounced, 0, -1);
				echo '<div id="wp-piwik_stats_vistors_graph" style="height:220px;" title="'.__('The graph contains the values shown in the table below (visitors / unique / bounces). The red line show a linear trendline (unique).', 'wp-piwik').'"></div>';
				echo '<script type="text/javascript">';
				echo '$j.jqplot("wp-piwik_stats_vistors_graph", [['.$values.'],['.$unique.'],['.$bounced.']],{axes:{yaxis:{min:0, tickOptions:{formatString:"%.0f"}},xaxis:{min:1,max:30,ticks:['.$labels.']}},seriesDefaults:{showMarker:false,lineWidth:1,fill:true,fillAndStroke:true,fillAlpha:0.9,trendline:{show:false,color:"#C00",lineWidth:1.5,type:"exp"}},series:[{color:"#90AAD9",fillColor:"#D4E2ED"},{color:"#A3BCEA",fillColor:"#E4F2FD",trendline:{show:true,label:"Unique visitor trend"}},{color:"#E9A0BA",fillColor:"#FDE4F2"}],});';
				echo '</script>';
			}
		}
		
	}