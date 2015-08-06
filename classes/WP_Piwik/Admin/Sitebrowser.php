<?php

namespace WP_Piwik\Admin;

if (! class_exists ( 'WP_List_Table' ))
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Sitebrowser extends \WP_List_Table {
	
	private $data = array (), $wpPiwik;
	
	public function __construct($wpPiwik) {
		$this->wpPiwik = $wpPiwik;
		$cnt = $this->prepare_items ();
		global $status, $page;
		parent::__construct ( array (
				'singular' => __ ( 'site', 'wp-piwik' ),
				'plural' => __ ( 'sites', 'wp-piwik' ),
				'ajax' => false 
		) );
		if ($cnt > 0)
			$this->display ();
		else
			echo '<p>' . __ ( 'No site configured yet.', 'wp-piwik' ) . '</p>';
	}
	
	public function get_columns() {
		$columns = array (
				'id' => __ ( 'Blog ID', 'wp-piwik' ),
				'name' => __ ( 'Title', 'wp-piwik' ),
				'siteurl' => __ ( 'URL', 'wp-piwik' ),
				'piwikid' => __ ( 'Site ID (Piwik)', 'wp-piwik' ) 
		);
		return $columns;
	}
	
	public function prepare_items() {
		$current_page = $this->get_pagenum ();
		$per_page = 10;
		global $blog_id;
		global $wpdb;
		global $pagenow;
		if (is_plugin_active_for_network ( 'wp-piwik/wp-piwik.php' )) {
			$total_items = $wpdb->get_var ( 'SELECT COUNT(*) FROM ' . $wpdb->blogs );
			$blogs = \WP_Piwik\Settings::getBlogList($per_page, $current_page);
			foreach ( $blogs as $blog ) {
				$blogDetails = get_blog_details ( $blog['blog_id'], true );
				$this->data [] = array (
						'name' => $blogDetails->blogname,
						'id' => $blogDetails->blog_id,
						'siteurl' => $blogDetails->siteurl,
						'piwikid' => $this->wpPiwik->getPiwikSiteId ( $blogDetails->blog_id ) 
				);
			}
		} else {
			$blogDetails = get_bloginfo ();
			$this->data [] = array (
					'name' => get_bloginfo ( 'name' ),
					'id' => '-',
					'siteurl' => get_bloginfo ( 'url' ),
					'piwikid' => $this->wpPiwik->getPiwikSiteId () 
			);
			$total_items = 1;
		}
		$columns = $this->get_columns ();
		$hidden = array ();
		$sortable = array ();
		$this->_column_headers = array (
				$columns,
				$hidden,
				$sortable 
		);
		$this->set_pagination_args ( array (
				'total_items' => $total_items,
				'per_page' => $per_page 
		) );
		foreach ( $this->data as $key => $dataset ) {
			if (empty ( $dataset ['piwikid'] ) || $dataset ['piwikid'] == 'n/a')
				$this->data [$key] ['piwikid'] = __ ( 'Site not created yet.', 'wp-piwik' );
			if ($this->wpPiwik->isNetworkMode ())
				$this->data [$key] ['name'] = '<a href="index.php?page=wp-piwik_stats&wpmu_show_stats=' . $dataset ['id'] . '">' . $dataset ['name'] . '</a>';
		}
		$this->items = $this->data;
		return count ( $this->items );
	}
	
	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'id' :
			case 'name' :
			case 'siteurl' :
			case 'piwikid' :
				return $item [$column_name];
			default :
				return print_r ( $item, true );
		}
	}
}