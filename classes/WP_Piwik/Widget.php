<?php

namespace WP_Piwik;

/**
 * Abstract widget class
 *
 * @author Andr&eacute; Br&auml;kling
 * @package WP_Piwik
 */
abstract class Widget {
	
	/**
	 *
	 * @var Environment variables
	 */
	protected static $wpPiwik, $settings;
	
	/**
	 *
	 * @var Configuration parameters
	 */
	protected $isShortcode = false, $method = '', $title = '', $context = 'side', $priority = 'core', $parameter = array (), $apiID = array (), $pageId = 'dashboard', $blogId = null, $name = 'Value', $limit = 10, $content = '';
	
	/**
	 * Widget constructor
	 *
	 * @param WP_Piwik $wpPiwik
	 *        	current WP-Piwik object
	 * @param WP_Piwik\Settings $settings
	 *        	current WP-Piwik settings
	 * @param string $pageId
	 *        	WordPress page ID (default: dashboard)
	 * @param string $context
	 *        	WordPress meta box context (defualt: side)
	 * @param string $priority
	 *        	WordPress meta box priority (default: default)
	 * @param array $params
	 *        	widget parameters (default: empty array)
	 * @param boolean $isShortcode
	 *        	is the widget shown inline? (default: false)
	 */
	public function __construct($wpPiwik, $settings, $pageId = 'dashboard', $context = 'side', $priority = 'default', $params = array(), $isShortcode = false) {
		self::$wpPiwik = $wpPiwik;
		self::$settings = $settings;
		$this->pageId = $pageId;
		$this->context = $context;
		$this->priority = $priority;
		if (self::$settings->checkNetworkActivation () && function_exists ( 'is_super_admin' ) && is_super_admin () && isset ( $_GET ['wpmu_show_stats'] )) {
			switch_to_blog ( ( int ) $_GET ['wpmu_show_stats'] );
			$this->blogId = get_current_blog_id ();
			restore_current_blog ();
		}
		$this->isShortcode = $isShortcode;
		$prefix = ($this->pageId == 'dashboard' ? self::$settings->getGlobalOption ( 'plugin_display_name' ) . ' - ' : '');
		$this->configure ( $prefix, $params );
		if (is_array ( $this->method ))
			foreach ( $this->method as $method ) {
				$this->apiID [$method] = \WP_Piwik\Request::register ( $method, $this->parameter );
				self::$wpPiwik->log ( "Register request: " . $this->apiID [$method] );
			}
		else {
			$this->apiID [$this->method] = \WP_Piwik\Request::register ( $this->method, $this->parameter );
			self::$wpPiwik->log ( "Register request: " . $this->apiID [$this->method] );
		}
		if ($this->isShortcode)
			return;
		add_meta_box ( $this->getName (), $this->title, array (
				$this,
				'show' 
		), $pageId, $this->context, $this->priority );
	}
	
	/**
	 * Conifguration dummy method
	 *
	 * @param string $prefix
	 *        	metabox title prefix (default: empty)
	 * @param array $params
	 *        	widget parameters (default: empty array)
	 */
	protected function configure($prefix = '', $params = array()) {
	}
	
	/**
	 * Default show widget method, handles default Piwik output
	 */
	public function show() {
		$response = self::$wpPiwik->request ( $this->apiID [$this->method] );
		if (! empty ( $response ['result'] ) && $response ['result'] == 'error')
			$this->out( '<strong>' . __ ( 'Piwik error', 'wp-piwik' ) . ':</strong> ' . htmlentities ( $response ['message'], ENT_QUOTES, 'utf-8' ) );
		else {
			if (isset ( $response [0] ['nb_uniq_visitors'] ))
				$unique = 'nb_uniq_visitors';
			else
				$unique = 'sum_daily_nb_uniq_visitors';
			$tableHead = array (
					'label' => __ ( $this->name, 'wp-piwik' ) 
			);
			$tableHead [$unique] = __ ( 'Unique', 'wp-piwik' );
			if (isset ( $response [0] ['nb_visits'] ))
				$tableHead ['nb_visits'] = __ ( 'Visits', 'wp-piwik' );
			if (isset ( $response [0] ['nb_hits'] ))
				$tableHead ['nb_hits'] = __ ( 'Hits', 'wp-piwik' );
			if (isset ( $response [0] ['nb_actions'] ))
				$tableHead ['nb_actions'] = __ ( 'Actions', 'wp-piwik' );
			$tableBody = array ();
			$count = 0;
			foreach ( $response as $rowKey => $row ) {
				$count ++;
				$tableBody [$rowKey] = array ();
				foreach ( $tableHead as $key => $value )
					$tableBody [$rowKey] [] = isset ( $row [$key] ) ? $row [$key] : '-';
				if ($count == 10)
					break;
			}
			$this->table ( $tableHead, $tableBody, null );
		}
	}
	
	/**
	 * Display or store shortcode output
	 */
	protected function out($output) {
		if ($this->isShortcode)
			$this->output .= $output;
		else echo $output;
	}

	/**
	 * Return shortcode output
	 */
	public function get() {
		return $this->output;
	}
	
	/**
	 * Display a HTML table
	 *
	 * @param array $thead
	 *        	table header content (array of cells)
	 * @param array $tbody
	 *        	table body content (array of rows)
	 * @param array $tfoot
	 *        	table footer content (array of cells)
	 * @param string $class
	 *        	CSSclass name to apply on table sections
	 * @param string $javaScript
	 *        	array of javascript code to apply on body rows
	 */
	protected function table($thead, $tbody = array(), $tfoot = array(), $class = false, $javaScript = array(), $classes = array()) {
		$this->out( '<div class="table"><table class="widefat wp-piwik-table">' );
		if ($this->isShortcode && $this->title) {
			$colspan = !empty ( $tbody ) ? count( $tbody[0] ) : 2 ;
			$this->out( '<tr><th colspan="'.$colspan.'">' . $this->title . '</th></tr>' );
		}			
		if (! empty ( $thead ))
			$this->tabHead ( $thead, $class );
		if (! empty ( $tbody ))
			$this->tabBody ( $tbody, $class, $javaScript, $classes );
		else
			$this->out( '<tr><td colspan="10">' . __ ( 'No data available.', 'wp-piwik' ) . '</td></tr>' );
		if (! empty ( $tfoot ))
			$this->tabFoot ( $tfoot, $class );
		$this->out( '</table></div>' );
	}
	
	/**
	 * Display a HTML table header
	 *
	 * @param array $thead
	 *        	array of cells
	 * @param string $class
	 *        	CSS class to apply
	 */
	private function tabHead($thead, $class = false) {
		$this->out( '<thead' . ($class ? ' class="' . $class . '"' : '') . '><tr>' );
		$count = 0;
		foreach ( $thead as $value )
			$this->out( '<th' . ($count ++ ? ' class="right"' : '') . '>' . $value . '</th>' );
		$this->out( '</tr></thead>' );
	}
	
	/**
	 * Display a HTML table body
	 * 
	 * @param array $tbody
	 *        	array of rows, each row containing an array of cells
	 * @param string $class
	 *        	CSS class to apply
	 * @param unknown $javaScript
	 *        	array of javascript code to apply (one item per row)
	 */
	private function tabBody($tbody, $class = false, $javaScript = array(), $classes = array()) {
		$this->out( '<tbody' . ($class ? ' class="' . $class . '"' : '') . '>' );
		foreach ( $tbody as $key => $trow )
			$this->tabRow ( $trow, isset( $javaScript [$key] ) ?$javaScript [$key] : '', isset ( $classes [$key] ) ?$classes [$key] : '');
		$this->out( '</tbody>' );
	}
	
	/**
	 * Display a HTML table footer
	 *
	 * @param array $tfoor
	 *        	array of cells
	 * @param string $class
	 *        	CSS class to apply
	 */
	private function tabFoot($tfoot, $class = false) {
		$this->out( '<tfoot' . ($class ? ' class="' . $class . '"' : '') . '><tr>' );
		$count = 0;
		foreach ( $tfoot as $value )
			$this->out( '<td' . ($count ++ ? ' class="right"' : '') . '>' . $value . '</td>' );
		$this->out( '</tr></tfoot>' );
	}
	
	/**
	 * Display a HTML table row
	 *
	 * @param array $trow
	 *        	array of cells
	 * @param string $javaScript
	 *        	javascript code to apply
	 */
	private function tabRow($trow, $javaScript = '', $class = '') {
		$this->out( '<tr' . (! empty ( $javaScript ) ? ' onclick="' . $javaScript . '"' : '') . (! empty ( $class ) ? ' class="' . $class . '"' : '') . '>' );
		$count = 0;
		foreach ( $trow as $tcell )
			$this->out( '<td' . ($count ++ ? ' class="right"' : '') . '>' . $tcell . '</td>' );
		$this->out( '</tr>' );
	}
	
	/**
	 * Get the current request's Piwik time settings
	 *
	 * @return array time settings: period => Piwik period, date => requested date, description => time description to show in widget title
	 */
	protected function getTimeSettings() {
		switch (self::$settings->getGlobalOption ( 'default_date' )) {
			case 'today' :
				$period = 'day';
				$date = 'today';
				$description = __('today', 'wp-piwik' );
				break;
			case 'current_month' :
				$period = 'month';
				$date = 'today';
				$description = __('current month', 'wp-piwik' );
				break;
			case 'last_month' :
				$period = 'month';
				$date = date ( "Y-m-d", strtotime ( "last day of previous month" ) );
				$description = __('last month', 'wp-piwik' );
				break;
			case 'current_week' :
				$period = 'week';
				$date = 'today';
				$description = __('current week', 'wp-piwik' );
				break;
			case 'last_week' :
				$period = 'week';
				$date = date ( "Y-m-d", strtotime ( "-1 week" ) );
				$description = __('last week', 'wp-piwik' );
				break;
			case 'yesterday' :
				$period = 'day';
				$date = 'yesterday';
				$description = __('yesterday', 'wp-piwik' );
				break;
			default :
				break;
		}
		return array (
				'period' => $period,
				'date' => isset ( $_GET ['date'] ) ? ( int ) $_GET ['date'] : $date,
				'description' => isset ( $_GET ['date'] ) ? $this->dateFormat ( $_GET ['date'], $period ) : $description 
		);
	}
	
	/**
	 * Format a date to show in widget
	 *
	 * @param string $date
	 *        	date string
	 * @param string $period
	 *        	Piwik period
	 * @return string formatted date
	 */
	protected function dateFormat($date, $period = 'day') {
		$prefix = '';
		switch ($period) {
			case 'week' :
				$prefix = __ ( 'week', 'wp-piwik' ) . ' ';
				$format = 'W/Y';
				break;
			case 'short_week' :
				$format = 'W';
				break;
			case 'month' :
				$format = 'F Y';
				$date = date ( 'Y-m-d', strtotime ( $date ) );
				break;
			default :
				$format = get_option ( 'date_format' );
		}
		return $prefix . date_i18n ( $format, strtotime ( $date ) );
	}
	
	/**
	 * Format time to show in widget
	 *
	 * @param int $time
	 *        	time in seconds
	 * @return string formatted time
	 */
	protected function timeFormat($time) {
		return floor ( $time / 3600 ) . 'h ' . floor ( ($time % 3600) / 60 ) . 'm ' . floor ( ($time % 3600) % 60 ) . 's';
	}
	
	/**
	 * Convert Piwik range into meaningful text
	 *
	 * @return string range description
	 */
	public function rangeName() {
		switch ($this->parameter ['date']) {
			case 'last30' :
				return __('last 30 days', 'wp-piwik' );
			case 'last12' :
				return __('last 12 ' . $this->parameter ['period'] . 's', 'wp-piwik' );
			default :
				return $this->parameter ['date'];
		}
	}
	
	/**
	 * Get the widget name
	 *
	 * @return string widget name
	 */
	public function getName() {
		return str_replace ( '\\', '-', get_called_class () );
	}
	
	/**
	 * Display a pie chart
	 *
	 * @param
	 *        	array chart data array(array(0 => name, 1 => value))
	 */
	public function pieChart($data) {
		$this->out( '<div id="wp-piwik_stats_' . $this->getName () . '_graph" style="height:310px;width:100%"></div>' );
		$this->out( '<script type="text/javascript">$plotBrowsers = $j.jqplot("wp-piwik_stats_' . $this->getName () . '_graph", [[' );
		$list = '';
		foreach ( $data as $key => $dataSet ) {
			$list .= '["' . $dataSet [0] . '", ' . $dataSet [1] . '],';
			if ($key == 'Others') break;
		}
		$this->out( substr ( $list, 0, - 1 ) );
		$this->out( ']], {seriesDefaults:{renderer:$j.jqplot.PieRenderer, rendererOptions:{sliceMargin:8}},legend:{show:true}});</script>' );
	}
	
	/**
	 * Return an array value by key, return '-' if not set
	 *
	 * @param array $array
	 *        	array to get a value from
	 * @param string $key
	 *        	key of the value to get from array
	 * @return string found value or '-' as a placeholder
	 */
	protected function value($array, $key) {
		return (isset ( $array [$key] ) ? $array [$key] : '-');
	}
}