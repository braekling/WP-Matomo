<?php

namespace WP_Piwik\Admin;

/**
 * WordPress Admin settings page
 *
 * @package WP_Piwik\Admin
 * @author Andr&eacute; Br&auml;kling <webmaster@braekling.de>
 */
class Settings extends \WP_Piwik\Admin {

	/**
	 * Builds and displays the settings page
	 */
	public function show() {
		if (isset($_GET['sitebrowser']) && $_GET['sitebrowser']) {
			new \WP_Piwik\Admin\Sitebrowser(self::$wpPiwik);
			return;
		}
		if (isset($_GET['clear']) && $_GET['clear']) {
			$this->clear($_GET['clear'] == 2);
			self::$wpPiwik->resetRequest();
			echo '<form method="post" action="?page='.htmlentities($_GET['page']).'"><input type="submit" value="'.__('Reload', 'wp-piwik').'" /></form>';
			return;
		} elseif (self::$wpPiwik->isConfigSubmitted()) {
			$this->showBox ( 'updated', 'yes', __ ( 'Changes saved.' ) );
			self::$wpPiwik->resetRequest();
			self::$wpPiwik->updateTrackingCode();
		}
		global $wp_roles;
		?>
<div id="plugin-options-wrap" class="widefat">
	<?php
		echo $this->getHeadline ( 1, 'admin-generic', 'Settings', true );
		if (isset($_GET['testscript']) && $_GET['testscript'])
			$this->runTestscript();
	?>
	<?php
		if (self::$wpPiwik->isConfigured ()) {
			$piwikVersion = self::$wpPiwik->request ( 'global.getPiwikVersion' );
			if (is_array ( $piwikVersion ) && isset( $piwikVersion['value'] ))
				$piwikVersion = $piwikVersion['value'];
			if (! empty ( $piwikVersion ) && !is_array( $piwikVersion ))
				$this->showDonation();
		}
	?>
	<form method="post" action="?page=<?php echo htmlentities($_GET['page']); ?>">
		<input type="hidden" name="wp-piwik[revision]" value="<?php echo self::$settings->getGlobalOption('revision'); ?>" />
		<?php wp_nonce_field('wp-piwik_settings'); ?>
		<table class="wp-piwik-form">
			<tbody>
			<?php
		$submitButton = '<tr><td colspan="2"><p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__ ( 'Save Changes' ) . '" /></p></td></tr>';
		printf ( '<tr><td colspan="2">%s</td></tr>', __ ( 'Thanks for using WP-Piwik!', 'wp-piwik' ) );
		if (self::$wpPiwik->isConfigured ()) {
			if (! empty ( $piwikVersion ) && !is_array( $piwikVersion )) {
				$this->showText ( sprintf ( __ ( 'WP-Piwik %s is successfully connected to Piwik %s.', 'wp-piwik' ), self::$wpPiwik->getPluginVersion (), $piwikVersion ) . ' ' . (! self::$wpPiwik->isNetworkMode () ? sprintf ( __ ( 'You are running WordPress %s.', 'wp-piwik' ), get_bloginfo ( 'version' ) ) : sprintf ( __ ( 'You are running a WordPress %s blog network (WPMU). WP-Piwik will handle your sites as different websites.', 'wp-piwik' ), get_bloginfo ( 'version' ) )) );
			} else {
				$errorMessage = \WP_Piwik\Request::getLastError();
				if ( empty( $errorMessage ) )
					$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-Piwik %s was not able to connect to Piwik using your configuration. Check the &raquo;Connect to Piwik&laquo; section below.', 'wp-piwik' ), self::$wpPiwik->getPluginVersion () ) );
				else
					$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-Piwik %s was not able to connect to Piwik using your configuration. During connection the following error occured: <br /><code>%s</code>', 'wp-piwik' ), self::$wpPiwik->getPluginVersion (), $errorMessage ) );
			}
		} else
			$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-Piwik %s has to be connected to Piwik first. Check the &raquo;Connect to Piwik&laquo; section below.', 'wp-piwik' ), self::$wpPiwik->getPluginVersion () ) );

		$tabs ['connect'] = array (
				'icon' => 'admin-plugins',
				'name' => __('Connect to Piwik', 'wp-piwik')
		);
		if (self::$wpPiwik->isConfigured ()) {
			$tabs ['statistics'] = array (
					'icon' => 'chart-pie',
					'name' => __('Show Statistics', 'wp-piwik')
			);
			$tabs ['tracking'] = array (
					'icon' => 'location-alt',
					'name' => __('Enable Tracking', 'wp-piwik')
			);
		}
		$tabs ['expert'] = array (
				'icon' => 'shield',
				'name' => __('Expert Settings', 'wp-piwik')
		);
		$tabs ['support'] = array (
				'icon' => 'lightbulb',
				'name' => __('Support', 'wp-piwik')
		);
		$tabs ['credits'] = array (
				'icon' => 'groups',
				'name' => __('Credits', 'wp-piwik')
		);

		echo '<tr><td colspan="2"><h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $details ) {
			$class = ($tab == 'connect') ? ' nav-tab-active' : '';
			echo '<a style="cursor:pointer;" id="tab-' . $tab . '" class="nav-tab' . $class . '" onclick="javascript:$j(\'table.wp-piwik_menu-tab\').addClass(\'hidden\');$j(\'#' . $tab . '\').removeClass(\'hidden\');$j(\'a.nav-tab\').removeClass(\'nav-tab-active\');$j(\'#tab-' . $tab . '\').addClass(\'nav-tab-active\');">';
			$this->showHeadline ( 0, $details ['icon'], $details ['name'] );
			echo "</a>";
		}
		echo '</h2></td></tr></tbody></table><table id="connect" class="wp-piwik_menu-tab"><tbody>';

		if (! self::$wpPiwik->isConfigured ())
			$this->showBox ( 'updated', 'info', sprintf ( '%s <a href="%s">%s</a> %s <a href="%s">%s</a>.', __ ( 'WP-Piwik is a WordPress plugin to show a selection of Piwik stats in your WordPress admin dashboard and to add and configure your Piwik tracking code. To use this you will need your own Piwik instance. If you do not already have a Piwik setup, you have two simple options: use either', 'wp-piwik' ), 'http://piwik.org/', __ ( 'Self-hosted', 'wp-piwik' ), __ ( 'or', 'wp-piwik' ), 'http://piwik.org/hosting/', __ ( 'Cloud-hosted', 'wp-piwik' ) ) );

		if (! function_exists ( 'curl_init' ) && ! ini_get ( 'allow_url_fopen' ))
			$this->showBox ( 'error', 'no', __ ( 'Neither cURL nor fopen are available. So WP-Piwik can not use the HTTP API and not connect to Piwik Pro.' ) . ' ' . sprintf ( '<a href="%s">%s.</a>', 'https://wordpress.org/plugins/wp-piwik/faq/', __ ( 'More information', 'wp-piwik' ) ) );

		$description = sprintf ( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s', __ ( 'You can choose between three connection methods:', 'wp-piwik' ), __ ( 'Self-hosted (HTTP API, default)', 'wp-piwik' ), __ ( 'This is the default option for a self-hosted Piwik and should work for most configurations. WP-Piwik will connect to Piwik using http(s).', 'wp-piwik' ), __ ( 'Self-hosted (PHP API)', 'wp-piwik' ), __ ( 'Choose this, if your self-hosted Piwik and WordPress are running on the same machine and you know the full server path to your Piwik instance.', 'wp-piwik' ), __ ( 'Cloud-hosted (Piwik Pro)', 'wp-piwik' ), __ ( 'If you are using a cloud-hosted Piwik by Piwik Pro, you can simply use this option.', 'wp-piwik' ) );
		$this->showSelect ( 'piwik_mode', __ ( 'Piwik Mode', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled (WP-Piwik will not connect to Piwik)', 'wp-piwik' ),
				'http' => __ ( 'Self-hosted (HTTP API, default)', 'wp-piwik' ),
				'php' => __ ( 'Self-hosted (PHP API)', 'wp-piwik' ),
				'pro' => __ ( 'Cloud-hosted (Piwik Pro)', 'wp-piwik' )
		), $description, '$j(\'tr.wp-piwik-mode-option\').addClass(\'hidden\'); $j(\'#wp-piwik-mode-option-\' + $j(\'#piwik_mode\').val()).removeClass(\'hidden\');', false, '', self::$wpPiwik->isConfigured () );

		$this->showInput ( 'piwik_url', __ ( 'Piwik URL', 'wp-piwik' ), __( 'Enter your Piwik URL. This is the same URL you use to access your Piwik instance, e.g. http://www.example.com/piwik/.', 'wp-piwik' ), self::$settings->getGlobalOption ( 'piwik_mode' ) != 'http', 'wp-piwik-mode-option', 'http', self::$wpPiwik->isConfigured (), true );
		$this->showInput ( 'piwik_path', __ ( 'Piwik path', 'wp-piwik' ), __( 'Enter the file path to your Piwik instance, e.g. /var/www/piwik/.', 'wp-piwik' ), self::$settings->getGlobalOption ( 'piwik_mode' ) != 'php', 'wp-piwik-mode-option', 'php', self::$wpPiwik->isConfigured (), true );
		$this->showInput ( 'piwik_user', __ ( 'Piwik user', 'wp-piwik' ), __( 'Enter your Piwik Pro username. It is also part of your URL: http://USERNAME.piwik.pro.', 'wp-piwik' ), self::$settings->getGlobalOption ( 'piwik_mode' ) != 'pro', 'wp-piwik-mode-option', 'pro', self::$wpPiwik->isConfigured () );
		$this->showInput ( 'piwik_token', __ ( 'Auth token', 'wp-piwik' ), __( 'Enter your Piwik auth token here. It is an alphanumerical code like 0a1b2c34d56e78901fa2bc3d45678efa.', 'wp-piwik' ).' '.sprintf ( __ ( 'See %sWP-Piwik FAQ%s.', 'wp-piwik' ), '<a href="https://wordpress.org/plugins/wp-piwik/faq/">', '</a>' ), false, '', '', self::$wpPiwik->isConfigured (), true );

		// Site configuration
		$piwikSiteId = self::$wpPiwik->isConfigured () ? self::$wpPiwik->getPiwikSiteId () : false;
		if (! self::$wpPiwik->isNetworkMode() ) {
			$this->showCheckbox ( 'auto_site_config', __ ( 'Auto config', 'wp-piwik' ), __ ( 'Check this to automatically choose your blog from your Piwik sites by URL. If your blog is not added to Piwik yet, WP-Piwik will add a new site.', 'wp-piwik' ), false, '$j(\'tr.wp-piwik-auto-option\').toggle(\'hidden\');' . ($piwikSiteId ? '$j(\'#site_id\').val(' . $piwikSiteId . ');' : '') );
			if (self::$wpPiwik->isConfigured ()) {
				$piwikSiteList = self::$wpPiwik->getPiwikSiteDetails ();
				if (is_array($piwikSiteList))
					foreach ($piwikSiteList as $details)
						$piwikSiteDetails[$details['idsite']] = $details;
				unset($piwikSiteList);
				if ( $piwikSiteId != 'n/a' && isset( $piwikSiteDetails ) && is_array( $piwikSiteDetails ) )
					$piwikSiteDescription = $piwikSiteDetails [$piwikSiteId] ['name'] . ' (' . $piwikSiteDetails [$piwikSiteId] ['main_url'] . ')';
				else
					$piwikSiteDescription = 'n/a';
				echo '<tr class="wp-piwik-auto-option' . (! self::$settings->getGlobalOption ( 'auto_site_config' ) ? ' hidden' : '') . '"><th scope="row">' . __ ( 'Determined site', 'wp-piwik' ) . ':</th><td>' . $piwikSiteDescription . '</td></tr>';
				if (isset ( $piwikSiteDetails ) && is_array ( $piwikSiteDetails ))
					foreach ( $piwikSiteDetails as $key => $siteData )
						$siteList [$siteData['idsite']] = $siteData ['name'] . ' (' . $siteData ['main_url'] . ')';
					if (isset($siteList))
						$this->showSelect ( 'site_id', __ ( 'Select site', 'wp-piwik' ), $siteList, 'Choose the Piwik site corresponding to this blog.', '', self::$settings->getGlobalOption ( 'auto_site_config' ), 'wp-piwik-auto-option', true, false );
			}
		} else echo '<tr class="hidden"><td colspan="2"><input type="hidden" name="wp-piwik[auto_site_config]" value="1" /></td></tr>';

		echo $submitButton;

		echo '</tbody></table><table id="statistics" class="wp-piwik_menu-tab hidden"><tbody>';
		// Stats configuration
		$this->showSelect ( 'default_date', __ ( 'Piwik default date', 'wp-piwik' ), array (
				'today' => __ ( 'Today', 'wp-piwik' ),
				'yesterday' => __ ( 'Yesterday', 'wp-piwik' ),
				'current_month' => __ ( 'Current month', 'wp-piwik' ),
				'last_month' => __ ( 'Last month', 'wp-piwik' ),
				'current_week' => __ ( 'Current week', 'wp-piwik' ),
				'last_week' => __ ( 'Last week', 'wp-piwik' )
		), __ ( 'Default date shown on statistics page.', 'wp-piwik' ) );

		$this->showCheckbox ( 'stats_seo', __ ( 'Show SEO data', 'wp-piwik' ), __ ( 'Display SEO ranking data on statistics page.', 'wp-piwik' ) . ' (' . __ ( 'Slow!', 'wp-piwik' ) . ')' );

		$this->showSelect ( 'dashboard_widget', __ ( 'Dashboard overview', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled', 'wp-piwik' ),
				'yesterday' => __ ( 'Yesterday', 'wp-piwik' ),
				'today' => __ ( 'Today', 'wp-piwik' ),
				'last30' => __ ( 'Last 30 days', 'wp-piwik' )
		), __ ( 'Enable WP-Piwik dashboard widget &quot;Overview&quot;.', 'wp-piwik' ) );

		$this->showCheckbox ( 'dashboard_chart', __ ( 'Dashboard graph', 'wp-piwik' ), __ ( 'Enable WP-Piwik dashboard widget &quot;Graph&quot;.', 'wp-piwik' ) );

		$this->showCheckbox ( 'dashboard_seo', __ ( 'Dashboard SEO', 'wp-piwik' ), __ ( 'Enable WP-Piwik dashboard widget &quot;SEO&quot;.', 'wp-piwik' ) . ' (' . __ ( 'Slow!', 'wp-piwik' ) . ')' );

		$this->showCheckbox ( 'toolbar', __ ( 'Show graph on WordPress Toolbar', 'wp-piwik' ), __ ( 'Display a last 30 days visitor graph on WordPress\' toolbar.', 'wp-piwik' ) );

		echo '<tr><th scope="row"><label for="capability_read_stats">' . __ ( 'Display stats to', 'wp-piwik' ) . '</label>:</th><td>';
		$filter = self::$settings->getGlobalOption ( 'capability_read_stats' );
		foreach ( $wp_roles->role_names as $key => $name ) {
			echo '<input type="checkbox" ' . (isset ( $filter [$key] ) && $filter [$key] ? 'checked="checked" ' : '') . 'value="1" onchange="$j(\'#capability_read_stats-' . $key . '-input\').val(this.checked?1:0);" />';
			echo '<input id="capability_read_stats-' . $key . '-input" type="hidden" name="wp-piwik[capability_read_stats][' . $key . ']" value="' . ( int ) (isset ( $filter [$key] ) && $filter [$key]) . '" />';
			echo $name . ' &nbsp; ';
		}
		echo '<span class="dashicons dashicons-editor-help" onclick="$j(\'#capability_read_stats-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="capability_read_stats-desc">' . __ ( 'Choose user roles allowed to see the statistics page.', 'wp-piwik' ) . '</p></td></tr>';

		$this->showCheckbox ( 'perpost_stats', __ ( 'Show per post stats', 'wp-piwik' ), __ ( 'Show stats about single posts at the post edit admin page.', 'wp-piwik' ) );

		$this->showCheckbox ( 'piwik_shortcut', __ ( 'Piwik shortcut', 'wp-piwik' ), __ ( 'Display a shortcut to Piwik itself.', 'wp-piwik' ) );

		$this->showInput ( 'plugin_display_name', __ ( 'WP-Piwik display name', 'wp-piwik' ), __ ( 'Plugin name shown in WordPress.', 'wp-piwik' ) );

		$this->showCheckbox ( 'shortcodes', __ ( 'Enable shortcodes', 'wp-piwik' ), __ ( 'Enable shortcodes in post or page content.', 'wp-piwik' ) );

		echo $submitButton;

		echo '</tbody></table><table id="tracking" class="wp-piwik_menu-tab hidden"><tbody>';

		// Tracking Configuration
		$isNotTracking = self::$settings->getGlobalOption ( 'track_mode' ) == 'disabled';
		$isNotGeneratedTracking = $isNotTracking || self::$settings->getGlobalOption ( 'track_mode' ) == 'manually';
		$fullGeneratedTrackingGroup = 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-proxy';

		$description = sprintf ( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s', __ ( 'You can choose between four tracking code modes:', 'wp-piwik' ), __ ( 'Disabled', 'wp-piwik' ), __ ( 'WP-Piwik will not add the tracking code. Use this, if you want to add the tracking code to your template files or you use another plugin to add the tracking code.', 'wp-piwik' ), __ ( 'Default tracking', 'wp-piwik' ), __ ( 'WP-Piwik will use Piwik\'s standard tracking code.', 'wp-piwik' ), __ ( 'Use js/index.php', 'wp-piwik' ), __ ( 'You can choose this tracking code, to deliver a minified proxy code and to avoid using the files called piwik.js or piwik.php.', 'wp-piwik' ).' '.sprintf( __( 'See %sreadme file%s.', 'wp-piwik' ), '<a href="http://demo.piwik.org/js/README">', '</a>'), __ ( 'Use proxy script', 'wp-piwik' ), __ ( 'Use this tracking code to not reveal the Piwik server URL.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik FAQ%s.', 'wp-piwik' ), '<a href="http://piwik.org/faq/how-to/#faq_132">', '</a>' ) , __ ( 'Enter manually', 'wp-piwik' ), __ ( 'Enter your own tracking code manually. You can choose one of the prior options, pre-configure your tracking code and switch to manually editing at last.', 'wp-piwik' ).( self::$wpPiwik->isNetworkMode() ? ' '.__ ( 'Use the placeholder {ID} to add the Piwik site ID.', 'wp-piwik' ) : '' ) );
		$this->showSelect ( 'track_mode', __ ( 'Add tracking code', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled', 'wp-piwik' ),
				'default' => __ ( 'Default tracking', 'wp-piwik' ),
				'js' => __ ( 'Use js/index.php', 'wp-piwik' ),
				'proxy' => __ ( 'Use proxy script', 'wp-piwik' ),
				'manually' => __ ( 'Enter manually', 'wp-piwik' )
		), $description, '$j(\'tr.wp-piwik-track-option\').addClass(\'hidden\'); $j(\'tr.wp-piwik-track-option-\' + $j(\'#track_mode\').val()).removeClass(\'hidden\'); $j(\'#tracking_code, #noscript_code\').prop(\'readonly\', $j(\'#track_mode\').val() != \'manually\');' );

		$this->showTextarea ( 'tracking_code', __ ( 'Tracking code', 'wp-piwik' ), 15, 'This is a preview of your current tracking code. If you choose to enter your tracking code manually, you can change it here.', $isNotTracking, 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-proxy wp-piwik-track-option-manually', true, '', (self::$settings->getGlobalOption ( 'track_mode' ) != 'manually'), false );

		$this->showSelect ( 'track_codeposition', __ ( 'JavaScript code position', 'wp-piwik' ), array (
				'footer' => __ ( 'Footer', 'wp-piwik' ),
				'header' => __ ( 'Header', 'wp-piwik' )
		), __ ( 'Choose whether the JavaScript code is added to the footer or the header.', 'wp-piwik' ), '', $isNotTracking, 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-proxy wp-piwik-track-option-manually' );

		$this->showTextarea ( 'noscript_code', __ ( 'Noscript code', 'wp-piwik' ), 2, 'This is a preview of your &lt;noscript&gt; code which is part of your tracking code.', self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-manually', true, '', (self::$settings->getGlobalOption ( 'track_mode' ) != 'manually'), false );

		$this->showCheckbox ( 'track_noscript', __ ( 'Add &lt;noscript&gt;', 'wp-piwik' ), __ ( 'Adds the &lt;noscript&gt; code to your footer.', 'wp-piwik' ) . ' ' . __ ( 'Disabled in proxy mode.', 'wp-piwik' ), self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'track_nojavascript', __ ( 'Add rec parameter to noscript code', 'wp-piwik' ), __ ( 'Enable tracking for visitors without JavaScript (not recommended).', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik FAQ%s.', 'wp-piwik' ), '<a href="http://piwik.org/faq/how-to/#faq_176">', '</a>' ) . ' ' . __ ( 'Disabled in proxy mode.', 'wp-piwik' ), self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-piwik-track-option wp-piwik-track-option-default wp-piwik-track-option-js wp-piwik-track-option-manually' );

		$this->showSelect ( 'track_content', __ ( 'Enable content tracking', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled', 'wp-piwik' ),
				'all' => __ ( 'Track all content blocks', 'wp-piwik' ),
				'visible' => __ ( 'Track only visible content blocks', 'wp-piwik' )
		), __ ( 'Content tracking allows you to track interaction with the content of a web page or application.' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="https://developer.piwik.org/guides/content-tracking">', '</a>' ), '', $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'track_search', __ ( 'Track search', 'wp-piwik' ), __ ( 'Use Piwik\'s advanced Site Search Analytics feature.' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="http://piwik.org/docs/site-search/#track-site-search-using-the-tracking-api-advanced-users-only">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'track_404', __ ( 'Track 404', 'wp-piwik' ), __ ( 'WP-Piwik can automatically add a 404-category to track 404-page-visits.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik FAQ%s.', 'wp-piwik' ), '<a href="http://piwik.org/faq/how-to/faq_60/">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'add_post_annotations', __ ( 'Add annotation on new post', 'wp-piwik' ), __ ( 'Add a Piwik annotation on each new post.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="http://piwik.org/docs/annotations/">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'add_customvars_box', __ ( 'Show custom variables box', 'wp-piwik' ), __ ( ' Show a &quot;custom variables&quot; edit box on post edit page.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="http://piwik.org/docs/custom-variables/">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showInput ( 'add_download_extensions', __ ( 'Add new file types for download tracking', 'wp-piwik' ), __ ( 'Add file extensions for download tracking, divided by a vertical bar (&#124;).', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="https://developer.piwik.org/guides/tracking-javascript-guide#file-extensions-for-tracking-downloads">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'disable_cookies', __ ( 'Disable cookies', 'wp-piwik' ), __ ( 'Disable all tracking cookies for a visitor.', 'wp-piwik' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'limit_cookies', __ ( 'Limit cookie lifetime', 'wp-piwik' ), __ ( 'You can limit the cookie lifetime to avoid tracking your users over a longer period as necessary.', 'wp-piwik' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup, true, '$j(\'tr.wp-piwik-cookielifetime-option\').toggleClass(\'wp-piwik-hidden\');' );

		$this->showInput ( 'limit_cookies_visitor', __ ( 'Visitor timeout (seconds)', 'wp-piwik' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup.' wp-piwik-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-piwik-hidden') );

		$this->showInput ( 'limit_cookies_session', __ ( 'Session timeout (seconds)', 'wp-piwik' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup .' wp-piwik-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-piwik-hidden') );

		$this->showInput ( 'limit_cookies_referral', __ ( 'Referral timeout (seconds)', 'wp-piwik' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup .' wp-piwik-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-piwik-hidden') );

		$this->showCheckbox ( 'track_admin', __ ( 'Track admin pages', 'wp-piwik' ), __ ( 'Enable to track users on admin pages (remember to configure the tracking filter appropriately).', 'wp-piwik' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		echo '<tr class="' . $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' . ($isNotTracking ? ' hidden' : '') . '">';
		echo '<th scope="row"><label for="capability_stealth">' . __ ( 'Tracking filter', 'wp-piwik' ) . '</label>:</th><td>';
		$filter = self::$settings->getGlobalOption ( 'capability_stealth' );
		foreach ( $wp_roles->role_names as $key => $name )
			echo '<input type="checkbox" ' . (isset ( $filter [$key] ) && $filter [$key] ? 'checked="checked" ' : '') . 'value="1" name="wp-piwik[capability_stealth][' . $key . ']" /> ' . $name . ' &nbsp; ';
		echo '<span class="dashicons dashicons-editor-help" onclick="$j(\'#capability_stealth-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="capability_stealth-desc">' . __ ( 'Choose users by user role you do <strong>not</strong> want to track.', 'wp-piwik' ) . '</p></td></tr>';

		$this->showCheckbox ( 'track_across', __ ( 'Track subdomains in the same website', 'wp-piwik' ), __ ( 'Adds *.-prefix to cookie domain.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="https://developer.piwik.org/guides/tracking-javascript-guide#tracking-subdomains-in-the-same-website">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'track_across_alias', __ ( 'Do not count subdomains as outlink', 'wp-piwik' ), __ ( 'Adds *.-prefix to tracked domain.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="https://developer.piwik.org/guides/tracking-javascript-guide#outlink-tracking-exclusions">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'track_feed', __ ( 'Track RSS feeds', 'wp-piwik' ), __ ( 'Enable to track posts in feeds via tracking pixel.', 'wp-piwik' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually' );

		$this->showCheckbox ( 'track_feed_addcampaign', __ ( 'Track RSS feed links as campaign', 'wp-piwik' ), __ ( 'This will add Piwik campaign parameters to the RSS feed links.' . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="http://piwik.org/docs/tracking-campaigns/">', '</a>' ), 'wp-piwik' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-piwik-track-option-manually', true, '$j(\'tr.wp-piwik-feed_campaign-option\').toggle(\'hidden\');' );

		$this->showInput ( 'track_feed_campaign', __ ( 'RSS feed campaign', 'wp-piwik' ), __ ( 'Keyword: post name.', 'wp-piwik' ), $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'track_feed_addcampaign' ), $fullGeneratedTrackingGroup . ' wp-piwik-feed_campaign-option' );

		$this->showInput ( 'track_heartbeat', __ ( 'Enable heartbeat timer', 'wp-piwik' ), __ ( 'Enable a heartbeat timer to get more accurate visit lengths by sending periodical HTTP ping requests as long as the site is opened. Enter the time between the pings in seconds (Piwik default: 15) to enable or 0 to disable this feature. <strong>Note:</strong> This will cause a lot of additional HTTP requests on your site.', 'wp-piwik' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showSelect ( 'track_user_id', __ ( 'User ID Tracking', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled', 'wp-piwik' ),
				'uid' => __ ( 'WP User ID', 'wp-piwik' ),
				'email' => __ ( 'Email Address', 'wp-piwik' ),
				'username' => __ ( 'Username', 'wp-piwik' ),
				'displayname' => __ ( 'Display Name (Not Recommended!)', 'wp-piwik' )
		), __ ( 'When a user is logged in to WordPress, track their &quot;User ID&quot;. You can select which field from the User\'s profile is tracked as the &quot;User ID&quot;. When enabled, Tracking based on Email Address is recommended.', 'wp-piwik' ), '', $isNotTracking, $fullGeneratedTrackingGroup );

		echo $submitButton;
		echo '</tbody></table><table id="expert" class="wp-piwik_menu-tab hidden"><tbody>';

		$this->showText ( __ ( 'Usually, you do not need to change these settings. If you want to do so, you should know what you do or you got an expert\'s advice.', 'wp-piwik' ) );

		$this->showCheckbox ( 'cache', __ ( 'Enable cache', 'wp-piwik' ), __ ( 'Cache API calls, which not contain today\'s values, for a week.', 'wp-piwik' ) );

		if (function_exists('curl_init') && ini_get('allow_url_fopen'))
			$this->showSelect ( 'http_connection', __ ( 'HTTP connection via', 'wp-piwik' ), array (
				'curl' => __ ( 'cURL', 'wp-piwik' ),
				'fopen' => __ ( 'fopen', 'wp-piwik' )
			), __('Choose whether WP-Piwik should use cURL or fopen to connect to Piwik in HTTP or Pro mode.', 'wp-piwik' ) );

		$this->showSelect ( 'http_method', __ ( 'HTTP method', 'wp-piwik' ), array (
				'post' => __ ( 'POST', 'wp-piwik' ),
				'get' => __ ( 'GET', 'wp-piwik' )
		), __('Choose whether WP-Piwik should use POST or GET in HTTP or Pro mode.', 'wp-piwik' ) );

		$this->showCheckbox ( 'disable_timelimit', __ ( 'Disable time limit', 'wp-piwik' ), __ ( 'Use set_time_limit(0) if stats page causes a time out.', 'wp-piwik' ) );

		$this->showInput ( 'connection_timeout', __ ( 'Connection timeout', 'wp-piwik' ), 'Define a connection timeout for all HTTP requests done by WP-Piwik in seconds.' );

		$this->showCheckbox ( 'disable_ssl_verify', __ ( 'Disable SSL peer verification', 'wp-piwik' ), '(' . __ ( 'not recommended', 'wp-piwik' ) . ')' );
		$this->showCheckbox ( 'disable_ssl_verify_host', __ ( 'Disable SSL host verification', 'wp-piwik' ), '(' . __ ( 'not recommended', 'wp-piwik' ) . ')' );

		$this->showSelect ( 'piwik_useragent', __ ( 'User agent', 'wp-piwik' ), array (
				'php' => __ ( 'Use the PHP default user agent', 'wp-piwik' ) . (ini_get ( 'user_agent' ) ? '(' . ini_get ( 'user_agent' ) . ')' : ' (' . __ ( 'empty', 'wp-piwik' ) . ')'),
				'own' => __ ( 'Define a specific user agent', 'wp-piwik' )
		), 'WP-Piwik can send the default user agent defined by your PHP settings or use a specific user agent below. The user agent is send by WP-Piwik if HTTP requests are performed.', '$j(\'tr.wp-piwik-useragent-option\').toggleClass(\'hidden\');' );
		$this->showInput ( 'piwik_useragent_string', __ ( 'Specific user agent', 'wp-piwik' ), 'Define a user agent description which is send by WP-Piwik if HTTP requests are performed.', self::$settings->getGlobalOption ( 'piwik_useragent' ) != 'own', 'wp-piwik-useragent-option' );

		$this->showCheckbox ( 'track_datacfasync', __ ( 'Add data-cfasync=false', 'wp-piwik' ), __ ( 'Adds data-cfasync=false to the script tag, e.g., to ask Rocket Loader to ignore the script.' . ' ' . sprintf ( __ ( 'See %sCloudFlare Knowledge Base%s.', 'wp-piwik' ), '<a href="https://support.cloudflare.com/hc/en-us/articles/200169436-How-can-I-have-Rocket-Loader-ignore-my-script-s-in-Automatic-Mode-">', '</a>' ), 'wp-piwik' ) );

		$this->showInput ( 'track_cdnurl', __ ( 'CDN URL', 'wp-piwik' ).' http://', 'Enter URL if you want to load the tracking code via CDN.' );

		$this->showInput ( 'track_cdnurlssl', __ ( 'CDN URL (SSL)', 'wp-piwik' ).' https://', 'Enter URL if you want to load the tracking code via a separate SSL CDN.' );

		$this->showSelect ( 'force_protocol', __ ( 'Force Piwik to use a specific protocol', 'wp-piwik' ), array (
				'disabled' => __ ( 'Disabled (default)', 'wp-piwik' ),
				'http' => __ ( 'http', 'wp-piwik' ),
				'https' => __ ( 'https (SSL)', 'wp-piwik' )
		), __ ( 'Choose if you want to explicitly force Piwik to use HTTP or HTTPS. Does not work with a CDN URL.', 'wp-piwik' ) );

		$this->showSelect ( 'update_notice', __ ( 'Update notice', 'wp-piwik' ), array (
				'enabled' => __ ( 'Show always if WP-Piwik is updated', 'wp-piwik' ),
				'script' => __ ( 'Show only if WP-Piwik is updated and settings were changed', 'wp-piwik' ),
				'disabled' => __ ( 'Disabled', 'wp-piwik' )
		), __ ( 'Choose if you want to get an update notice if WP-Piwik is updated.', 'wp-piwik' ) );

		$this->showInput ( 'set_download_extensions', __ ( 'Define all file types for download tracking', 'wp-piwik' ), __ ( 'Replace Piwik\'s default file extensions for download tracking, divided by a vertical bar (&#124;). Leave blank to keep Piwik\'s default settings.', 'wp-piwik' ) . ' ' . sprintf ( __ ( 'See %sPiwik documentation%s.', 'wp-piwik' ), '<a href="https://developer.piwik.org/guides/tracking-javascript-guide#file-extensions-for-tracking-downloads">', '</a>' ) );

		echo $submitButton;
		?>
			</tbody>
		</table>
		<table id="support" class="wp-piwik_menu-tab hidden">
			<tbody>
				<tr><td colspan="2"><?php
					echo $this->showSupport();
				?></td></tr>
			</tbody>
		</table>
		<table id="credits" class="wp-piwik_menu-tab hidden">
			<tbody>
				<tr><td colspan="2"><?php
					echo $this->showCredits();
				?></td></tr>
			</tbody>
		</table>
		<input type="hidden" name="wp-piwik[proxy_url]"
			value="<?php echo self::$settings->getGlobalOption('proxy_url'); ?>" />
	</form>
</div>
<?php
	}

	/**
	 * Show an option's description
	 *
	 * @param string $id option id
	 * @param string $description option description
	 * @param boolean $hideDescription set to false to show description initially (default: true)
	 * @return string full description HTML
	 */
	private function getDescription($id, $description, $hideDescription = true) {
		return sprintf ( '<span class="dashicons dashicons-editor-help" onclick="$j(\'#%s-desc\').toggleClass(\'hidden\');"></span> <p class="description' . ($hideDescription ? ' hidden' : '') . '" id="%1$s-desc">%s</p>', $id, $description );
	}

	/**
	 * Show a checkbox option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 */
	private function showCheckbox($id, $name, $description, $isHidden = false, $groupName = '', $hideDescription = true, $onChange = '') {
		printf ( '<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><input type="checkbox" value="1"' . (self::$settings->getGlobalOption ( $id ) ? ' checked="checked"' : '') . ' onchange="$j(\'#%s\').val(this.checked?1:0);%s" /><input id="%2$s" type="hidden" name="wp-piwik[%2$s]" value="' . ( int ) self::$settings->getGlobalOption ( $id ) . '" /> %s</td></tr>', $name, $id, $onChange, $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a textarea option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param int $rows number of rows to show
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isReadonly set textarea to read only (default: false)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	private function showTextarea($id, $name, $rows, $description, $isHidden, $groupName, $hideDescription = true, $onChange = '', $isReadonly = false, $global = true) {
		printf (
			'<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><textarea cols="80" rows="' . $rows . '" id="%s" name="wp-piwik[%2$s]" onchange="%s"' . ($isReadonly ? ' readonly="readonly"' : '') . '>%s</textarea> %s</td></tr>', $name, $id, $onChange, ($global ? self::$settings->getGlobalOption ( $id ) : self::$settings->getOption ( $id )), $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a simple text
	 *
	 * @param string $text Text to show
	 */
	private function showText($text) {
		printf ( '<tr><td colspan="2"><p>%s</p></td></tr>', $text );
	}

	/**
	 * Show an input option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param string $rowName define a class name to access the specific option row by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $wide Create a wide box (default: false)
	 */
	private function showInput($id, $name, $description, $isHidden = false, $groupName = '', $rowName = false, $hideDescription = true, $wide = false) {
		printf ( '<tr class="%s%s"%s><th scope="row"><label for="%5$s">%s:</label></th><td><input '.($wide?'class="wp-piwik-wide" ':'').'name="wp-piwik[%s]" id="%5$s" value="%s" /> %s</td></tr>', $isHidden ? 'hidden ' : '', $groupName ? $groupName : '', $rowName ? ' id="' . $groupName . '-' . $rowName . '"' : '', $name, $id, htmlentities(self::$settings->getGlobalOption( $id ), ENT_QUOTES, 'UTF-8', false), !empty($description) ? $this->getDescription ( $id, $description, $hideDescription ) : '' );
	}

	/**
	 * Show a select box option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param array $options list of options to show array[](option id => descriptive name)
	 * @param string $description option description
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	private function showSelect($id, $name, $options = array(), $description = '', $onChange = '', $isHidden = false, $groupName = '', $hideDescription = true, $global = true) {
		$optionList = '';
		$default = $global ? self::$settings->getGlobalOption ( $id ) : self::$settings->getOption ( $id );
		if (is_array ( $options ))
			foreach ( $options as $key => $value )
				$optionList .= sprintf ( '<option value="%s"' . ($key == $default ? ' selected="selected"' : '') . '>%s</option>', $key, $value );
		printf ( '<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s:</label></th><td><select name="wp-piwik[%s]" id="%2$s" onchange="%s">%s</select> %s</td></tr>', $name, $id, $onChange, $optionList, $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show an info box
	 *
	 * @param string $type box style (e.g., updated, error)
	 * @param string $icon box icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $content box message
	 */
	private function showBox($type, $icon, $content) {
		printf ( '<tr><td colspan="2"><div class="%s"><p><span class="dashicons dashicons-%s"></span> %s</p></div></td></tr>', $type, $icon, $content );
	}

	/**
	 * Show headline
	 * @param int $order headline order (h?-tag), set to 0 to avoid headline-tagging
	 * @param string $icon headline icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $headline headline text
	 * @param string $addPluginName set to true to add the plugin name to the headline (default: false)
	 */
	private function showHeadline($order, $icon, $headline, $addPluginName = false) {
		echo $this->getHeadline ( $order, $icon, $headline, $addPluginName = false );
	}

	/**
	 * Get headline HTML
	 *
	 * @param int $order headline order (h?-tag), set to 0 to avoid headline-tagging
	 * @param string $icon headline icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $headline headline text
	 * @param string $addPluginName set to true to add the plugin name to the headline (default: false)
	 */
	private function getHeadline($order, $icon, $headline, $addPluginName = false) {
		echo ($order > 0 ? "<h$order>" : '') . sprintf ( '<span class="dashicons dashicons-%s"></span> %s%s', $icon, ($addPluginName ? self::$settings->getGlobalOption ( 'plugin_display_name' ) . ' ' : ''), __ ( $headline, 'wp-piwik' ) ) . ($order > 0 ? "</h$order>" : '');
	}

	/**
	 * Show donation info
	 */
	private function showDonation() {
		?>
<div class="wp-piwik-donate">
	<p>
		<strong><?php _e('Donate','wp-piwik'); ?></strong>
	</p>
	<p>
		<?php _e('If you like WP-Piwik, you can support its development by a donation:', 'wp-piwik'); ?>
	</p>
	<script type="text/javascript">
	/* <![CDATA[ */
	window.onload = function() {
		FlattrLoader.render({
        	'uid': 'flattr',
            'url': 'http://wp.local',
            'title': 'Title of the thing',
            'description': 'Description of the thing'
		}, 'element_id', 'replace');
	}
	/* ]]> */
	</script>
	<div>
		<a class="FlattrButton" style="display:none;" title="WordPress Plugin WP-Piwik" rel="flattr;uid:braekling;category:software;tags:wordpress,piwik,plugin,statistics;" href="https://www.braekling.de/wp-piwik-wpmu-piwik-wordpress">This WordPress plugin adds a Piwik stats site to your WordPress dashboard. It's also able to add the Piwik tracking code to your blog using wp_footer. You need a running Piwik installation and at least view access to your stats.</a>
	</div>
	<div>
		Paypal
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="hosted_button_id" value="6046779" />
			<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online." />
			<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
		</form>
	</div>
	<div>
		<a href="bitcoin:32FMBngRne9wQ7XPFP2CfR25tjp3oa4roN">Bitcoin<br />
		<img style="border:none;" src="<?php echo self::$wpPiwik->getPluginURL(); ?>bitcoin.png" width="100" height="100" alt="Bitcoin Address" title="32FMBngRne9wQ7XPFP2CfR25tjp3oa4roN" /></a>
	</div>
	<div>
		<a href="http://www.amazon.de/gp/registry/wishlist/111VUJT4HP1RA?reveal=unpurchased&amp;filter=all&amp;sort=priority&amp;layout=standard&amp;x=12&amp;y=14"><?php _e('My Amazon.de wishlist', 'wp-piwik'); ?></a>
	</div>
	<div>
		<?php _e('Please don\'t forget to vote the compatibility at the','wp-piwik'); ?> <a href="http://wordpress.org/extend/plugins/wp-piwik/">WordPress.org Plugin Directory</a>.
	</div>
</div><?php
	}

	/**
	 * Register admin scripts
	 *
	 * @see \WP_Piwik\Admin::printAdminScripts()
	 */
	public function printAdminScripts() {
		wp_enqueue_script ( 'jquery' );
	}

	/**
	 * Extend admin header
	 *
	 * @see \WP_Piwik\Admin::extendAdminHeader()
	 */
	public function extendAdminHeader() {
		echo '<script type="text/javascript">var $j = jQuery.noConflict();</script>';
		echo '<script type="text/javascript">/* <![CDATA[ */(function() {var s = document.createElement(\'script\');var t = document.getElementsByTagName(\'script\')[0];s.type = \'text/javascript\';s.async = true;s.src = \'//api.flattr.com/js/0.6/load.js?mode=auto\';t.parentNode.insertBefore(s, t);})();/* ]]> */</script>';
	}

	/**
	 * Show credits
	 */
	public function showCredits() {
		?>
		<p><strong><?php _e('Thank you very much for your donation', 'wp-piwik'); ?>:</strong> Marco L., Rolf W., Tobias U., Lars K., Donna F., Kevin D., Ramos S., Thomas M., John C., Andreas G., Ben M., Myra R. I., Carlos U. R.-S., Oleg I., M. N., Daniel K., James L., Jochen K., Cyril P., Thomas K., Patrik K., Zach, Sebastian W., Peakkom, Patrik K., Kati K., Helmut O., <?php _e('the Piwik team itself','wp-piwik');?><?php _e(', and all people flattering this','wp-piwik'); ?>!</p>
		<p><?php _e('Graphs powered by <a href="http://www.jqplot.com/">jqPlot</a> (License: GPL 2.0 and MIT) and <a href="http://omnipotent.net/jquery.sparkline/">jQuery Sparklines</a> (License: New BSD License).','wp-piwik'); ?></p>
		<p><?php _e('Thank you very much','wp-piwik'); ?> <a href="https://www.transifex.com/projects/p/wp-piwik/">Transifex Translation Community</a> <?php _e('for your translation work','wp-piwik'); ?>!</p>
		<p><?php _e('Thank you very much, all users who send me mails containing criticism, commendation, feature requests and bug reports! You help me to make WP-Piwik much better.','wp-piwik'); ?></p>
		<p><?php _e('Thank <strong>you</strong> for using my plugin. It is the best commendation if my piece of code is really used!','wp-piwik'); ?></p>
		<?php
	}

	/**
	 * Show support information
	 */
	public function showSupport() {
		?><ul>
			<li><?php _e('The best place to get help:', 'wp-piwik'); ?> <a href="https://wordpress.org/support/plugin/wp-piwik"><?php _e('WP-Piwik support forum','wp-piwik'); ?></a></li>
			<li><?php _e('Please don\'t forget to vote the compatibility at the','wp-piwik'); ?> <a href="http://wordpress.org/extend/plugins/wp-piwik/">WordPress.org Plugin Directory</a>.</li>
		</ul>
		<h3><?php _e('Debugging', 'wp-piwik'); ?></h3>
		<p><?php _e('Either allow_url_fopen has to be enabled <em>or</em> cURL has to be available:', 'wp-piwik'); ?></p>
		<ol>
			<li><?php
				_e('cURL is','wp-piwik');
				echo ' <strong>'.(function_exists('curl_init')?'':__('not','wp-piwik')).' ';
				_e('available','wp-piwik');
			?></strong>.</li>
			<li><?php
				_e('allow_url_fopen is','wp-piwik');
				echo ' <strong>'.(ini_get('allow_url_fopen')?'':__('not','wp-piwik')).' ';
				_e('enabled','wp-piwik');
			?></strong>.</li>
			<li><strong><?php echo (((function_exists('curl_init') && ini_get('allow_url_fopen') && self::$settings->getGlobalOption('http_connection') == 'curl') || (function_exists('curl_init') && !ini_get('allow_url_fopen')))?__('cURL', 'wp-piwik'):__('fopen', 'wp-piwik')).' ('.(self::$settings->getGlobalOption('http_method')=='post'?__('POST','wp-piwik'):__('GET','wp-piwik')).')</strong> '.__('is used.', 'wp-piwik'); ?></li>
			<?php if (self::$settings->getGlobalOption('piwik_mode') == 'php') { ?><li><?php
				_e('Determined Piwik base URL is', 'wp-piwik');
				echo ' <strong>'.(self::$settings->getGlobalOption('proxy_url')).'</strong>';
			?></li><?php } ?>
		</ol>
		<p><?php _e('Tools', 'wp-piwik'); ?>:</p>
		<ol>
			<li><a href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&testscript=1' ); ?>"><?php _e('Run testscript', 'wp-piwik'); ?></a></li>
			<li><a href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&sitebrowser=1' ); ?>"><?php _e('Sitebrowser', 'wp-piwik'); ?></a></li>
			<li><a href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&clear=1' ); ?>"><?php _e('Clear cache', 'wp-piwik'); ?></a></li>
			<li><a onclick="return confirm('<?php _e('Are you sure you want to clear all settings?', 'wp-piwik'); ?>')" href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&clear=2' ); ?>"><?php _e('Reset WP-Piwik', 'wp-piwik'); ?></a></li>
		</ol>
		<h3><?php _e('Latest support threads on WordPress.org', 'wp-piwik'); ?></h3><?php
		$supportThreads = $this->readRSSFeed('http://wordpress.org/support/rss/plugin/wp-piwik');
		if (!empty($supportThreads)) {
			echo '<ol>';
			foreach ($supportThreads as $supportThread)
				echo '<li><a href="'.$supportThread['url'].'">'.$supportThread['title'].'</a></li>';
			echo '</ol>';
		}
	}

	/**
	 * Read RSS feed
	 *
	 * @param string $feed
	 *        	feed URL
	 * @param int $cnt
	 *        	item limit
	 * @return array feed items array[](title, url)
	 *
	 */
	private function readRSSFeed($feed, $cnt = 5) {
		$result = array ();
		if (function_exists ( 'simplexml_load_file' ) && ! empty ( $feed )) {
			$xml = @simplexml_load_file ( $feed );
			if (! $xml || ! isset ( $xml->channel [0]->item ))
				return array (
						array (
								'title' => 'Can\'t read RSS feed.',
								'url' => $xml
						)
				);
			foreach ( $xml->channel [0]->item as $item ) {
				if ($cnt -- == 0)
					break;
				$result [] = array (
						'title' => $item->title [0],
						'url' => $item->link [0]
				);
			}
		}
		return $result;
	}

	/**
	 * Clear cache and reset settings
	 *
	 * @param boolean $clearSettings set to true to reset settings (default: false)
	 */
	private function clear($clearSettings = false) {
		if ($clearSettings) {
			self::$settings->resetSettings();
			$this->showBox ( 'updated', 'yes', __ ( 'Settings cleared (except connection settings).' ) );
		}
		global $wpdb;
		if (self::$settings->checkNetworkActivation()) {
			$aryBlogs = \WP_Piwik\Settings::getBlogList();
			if (is_array($aryBlogs))
				foreach ($aryBlogs as $aryBlog) {
					switch_to_blog($aryBlog['blog_id']);
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-piwik_%'");
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-piwik_%'");
					restore_current_blog();
				}
		} else {
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-piwik_%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-piwik_%'");
		}
		$this->showBox ( 'updated', 'yes', __ ( 'Cache cleared.' ) );
	}

	/**
	 * Execute test script and display results
	 */
	private function runTestscript() { ?>
		<div class="wp-piwik-debug">
		<h2>Testscript Result</h2>
		<?php
			if (self::$wpPiwik->isConfigured()) {
				if (isset($_GET['testscript_id']) && $_GET['testscript_id'])
					switch_to_blog((int) $_GET['testscript_id']);
		?>
		<textarea cols="80" rows="10"><?php
			echo '`WP-Piwik '.self::$wpPiwik->getPluginVersion()."\nMode: ".self::$settings->getGlobalOption('piwik_mode')."\n\n";
		?>Test 1/3: global.getPiwikVersion<?php
			$GLOBALS ['wp-piwik_debug'] = true;
			$id = \WP_Piwik\Request::register ( 'API.getPiwikVersion', array() );
			echo "\n\n"; var_dump( self::$wpPiwik->request( $id ) ); echo "\n";
			var_dump( self::$wpPiwik->request( $id, true ) ); echo "\n";
			$GLOBALS ['wp-piwik_debug'] = false;
		?>Test 2/3: SitesManager.getSitesWithAtLeastViewAccess<?php
			$GLOBALS ['wp-piwik_debug'] = true;
			$id = \WP_Piwik\Request::register ( 'SitesManager.getSitesWithAtLeastViewAccess', array() );
			echo "\n\n"; var_dump( self::$wpPiwik->request( $id ) ); echo "\n";
			var_dump( self::$wpPiwik->request( $id, true ) ); echo "\n";
			$GLOBALS ['wp-piwik_debug'] = false;
		?>Test 3/3: SitesManager.getSitesIdFromSiteUrl<?php
			$GLOBALS ['wp-piwik_debug'] = true;
			$id = \WP_Piwik\Request::register ( 'SitesManager.getSitesIdFromSiteUrl', array (
				'url' => get_bloginfo ( 'url' )
			) );
			echo "\n\n";  var_dump( self::$wpPiwik->request( $id ) ); echo "\n";
			var_dump( self::$wpPiwik->request( $id, true ) ); echo "\n";
			echo "\n\n";  var_dump( self::$settings->getDebugData() ); echo "`";
			$GLOBALS ['wp-piwik_debug'] = false;
		?></textarea>
		<?php
				if (isset($_GET['testscript_id']) && $_GET['testscript_id'])
					restore_current_blog();
			} else echo '<p>Please configure WP-Piwik first.</p>';
		?>
		</div>
	<?php }

}
