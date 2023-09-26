<?php

use ConnectMatomo\Request;
use ConnectMatomo\Settings;

/**
 * The main Connect Matomo class configures, registers and manages the plugin
 *
 * @author Andr&eacute; Br&auml;kling <webmaster@braekling.de>
 * @package WP_Piwik
 */
class ConnectMatomo
{

    private static ?ConnectMatomo $instance = null;

    private int $blogId;
    private string $pluginBasename;
    public int $statsPageId;
    private Settings $settings;
    private Request $request;
    private int $optionsPageId;

    /**
     * Get singleton instance of Connect_Matomo
     * @return ConnectMatomo|null
     */
    public static function getInstance(): ?ConnectMatomo
    {
        if (self::$instance == null) {
            self::$instance = new ConnectMatomo();
        }
        return self::$instance;
    }

    /**
     * Constructor to configure and register all Connect Matomo components
     */
    private function __construct()
    {
        global $blog_id;
        $this->setBlogId($blog_id ?? -1);
        trigger_error("Initializing Connect Matomo for blog ID " . $blog_id, E_USER_NOTICE);
        $this->openSettings();
        $this->setup();
        $this->addFilters();
        $this->addActions();
        $this->addShortcodes();
    }

    /**
     * Load Connect Matomo settings
     * @return void
     */
    private function openSettings(): void
    {
        $this->setSettings(new Settings());
        if (!$this->isConfigSubmitted() && $this->isPHPMode()) {
            $this->defineMatomoConstants();
        }
    }

    /**
     * Setup class to prepare settings and check for installation and update
     * @return void
     */
    private function setup(): void
    {
        $this->setPluginBasename(plugin_basename(__FILE__));
        if (!$this->isInstalled()) {
            trigger_error("Installing Connect Matomo", E_USER_NOTICE);
            $this->installPlugin();
        } elseif ($this->isUpdated()) {
            trigger_error("Updating Connect Matomo", E_USER_NOTICE);
            $this->updatePlugin();
        }
        if ($this->isConfigSubmitted()) {
            $this->applySettings();
        }
        $this->getSettings()->save();
    }

    /**
     * Register Wordpress filters
     * @return void
     */
    private function addFilters(): void
    {
        if (is_admin()) {
            add_filter('plugin_row_meta', array(
                $this,
                'setPluginMeta'
            ), 10, 2);
            add_filter('screen_layout_columns', array(
                $this,
                'onScreenLayoutColumns'
            ), 10, 2);
        } elseif ($this->isTrackingActive()) {
            if ($this->isTrackFeed()) {
                add_filter('the_excerpt_rss', array(
                    $this,
                    'addFeedTracking'
                ));
                add_filter('the_content', array(
                    $this,
                    'addFeedTracking'
                ));
            }
            if ($this->isAddFeedCampaign()) {
                add_filter('post_link', array(
                    $this,
                    'addFeedCampaign'
                ));
            }
            if ($this->isCrossDomainLinkingEnabled()) {
                add_filter('wp_redirect', array(
                    $this,
                    'forwardCrossDomainVisitorId'
                ));
            }
        }
    }

    /**
     * Register WordPress actions
     * @return void
     */
    private function addActions(): void
    {
        $this->addAdminActions();
        $this->addToolbarActions();
        $this->addTrackingActions();
    }

    private function addAdminActions(): void
    {
        if (is_admin()) {
            add_action('admin_menu', array(
                $this,
                'buildAdminMenu'
            ));
            add_action('admin_post_save_wp-piwik_stats', array(
                $this,
                'onStatsPageSaveChanges'
            ));
            add_action('load-post.php', array(
                $this,
                'addPostMetaboxes'
            ));
            add_action('load-post-new.php', array(
                $this,
                'addPostMetaboxes'
            ));
            if ($this->isNetworkMode()) {
                add_action('network_admin_notices', array(
                    $this,
                    'showNotices'
                ));
                add_action('network_admin_menu', array(
                    $this,
                    'buildNetworkAdminMenu'
                ));
                add_action('update_site_option_blogname', array(
                    $this,
                    'onBlogNameChange'
                ));
                add_action('update_site_option_siteurl', array(
                    $this,
                    'onSiteUrlChange'
                ));
            } else {
                add_action('admin_notices', array(
                    $this,
                    'showNotices'
                ));
                add_action('update_option_blogname', array(
                    $this,
                    'onBlogNameChange'
                ));
                add_action('update_option_siteurl', array(
                    $this,
                    'onSiteUrlChange'
                ));
            }
            if ($this->isDashboardActive()) {
                add_action('wp_dashboard_setup', array(
                    $this,
                    'extendWordPressDashboard'
                ));
            }
        }
    }

    private function addToolbarActions(): void
    {
        if ($this->isToolbarActive()) {
            add_action(is_admin() ? 'admin_head' : 'wp_head', array(
                $this,
                'loadToolbarRequirements'
            ));
            add_action('admin_bar_menu', array(
                $this,
                'extendWordPressToolbar'
            ), 1000);
        }
    }

    private function addTrackingActions(): void
    {
        if ($this->isTrackingActive()) {
            if (!is_admin() || $this->isAdminTrackingActive()) {
                $prefix = is_admin() ? 'admin' : 'wp';
                add_action(
                    $this->getSettings()->getGlobalOption('track_codeposition') == 'footer' ?
                        $prefix . '_footer' : $prefix . '_head', array(
                        $this,
                        'addJavascriptCode'
                    )
                );
                if ($this->getSettings()->getGlobalOption('dnsprefetch')) {
                    add_action($prefix . '_head', array(
                        $this,
                        'addDNSPrefetchTag'
                    ));
                }
                if ($this->isAddNoScriptCode()) {
                    add_action($prefix . '_footer', array(
                        $this,
                        'addNoscriptCode'
                    ));
                }
            }
            if ($this->getSettings()->getGlobalOption('add_post_annotations')) {
                add_action('transition_post_status', array(
                    $this,
                    'addPiwikAnnotation'
                ), 10, 3);
            }
        }
    }


    /**
     * Register WordPress shortcodes
     * @return void
     */
    private function addShortcodes(): void
    {
        if ($this->isAddShortcode()) {
            add_shortcode('wp-piwik', array(
                $this,
                'shortcode'
            ));
        }
    }

    /**
     * Install Connect Matomo for the first time
     * @param bool $isUpdate
     * @return void
     */
    private function installPlugin(bool $isUpdate = false): void
    {
        self::$logger->log('Running Connect Matomo installation');
        if (!$isUpdate) {
            $this->addNotice('install',
                sprintf(__('%s %s installed.', 'wp-piwik'),
                    $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'),
                    $this->getVersion()), __('Next you should connect to Matomo', 'wp-piwik')
            );
        }
        $this->getSettings()->setGlobalOption('revision', $this->getRevisionId());
        $this->getSettings()->setGlobalOption('last_settings_update', time());
    }

    /**
     * Uninstall Connect Matomo
     * @return void
     */
    public function uninstallPlugin(): void
    {
        self::$logger->log('Running Connect Matomo uninstallation');
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            exit ();
        }
        self::deleteWordPressOption('wp-piwik-notices');
        $this->getSettings()->resetSettings(true);
    }

    /**
     * Update WP-Piwik
     */
    private function updatePlugin()
    {
        self::$logger->log('Upgrade Connect Matomo to ' . self::$version);
        $patches = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . '*.php');
        $isPatched = false;
        if (is_array($patches)) {
            sort($patches);
            foreach ($patches as $patch) {
                $patchVersion = ( int )pathinfo($patch, PATHINFO_FILENAME);
                if ($patchVersion && $this->getSettings()->getGlobalOption('revision') < $patchVersion) {
                    self::includeFile('update' . DIRECTORY_SEPARATOR . $patchVersion);
                    $isPatched = true;
                }
            }
        }
        if (($this->getSettings()->getGlobalOption('update_notice') == 'enabled') || (($this->getSettings()->getGlobalOption('update_notice') == 'script') && $isPatched)) {
            $this->addNotice('update', sprintf(__('%s updated to %s.', 'wp-piwik'), $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), self::$version), __('Please validate your configuration', 'wp-piwik'));
        }
        $this->installPlugin(true);
    }

    /**
     * Define a notice
     *
     * @param string $type
     *            identifier
     * @param string $subject
     *            notice headline
     * @param string $text
     *            notice content
     * @param boolean $stay
     *            set to true if the message should persist (default: false)
     */
    private function addNotice($type, $subject, $text, $stay = false)
    {
        $notices = $this->getWordPressOption('wp-piwik-notices', array());
        $notices [$type] = array(
            'subject' => $subject,
            'text' => $text,
            'stay' => $stay
        );
        $this->updateWordPressOption('wp-piwik-notices', $notices);
    }

    /**
     * Show all notices defined previously
     *
     * @see addNotice()
     */
    public function showNotices()
    {
        $link = sprintf('<a href="' . $this->getSettingsURL() . '">%s</a>', __('Settings', 'wp-piwik'));
        if ($notices = $this->getWordPressOption('wp-piwik-notices')) {
            foreach ($notices as $type => $notice) {
                printf('<div class="updated fade"><p>%s <strong>%s:</strong> %s: %s</p></div>', $notice ['subject'], __('Important', 'wp-piwik'), $notice ['text'], $link);
                if (!$notice ['stay']) {
                    unset ($notices [$type]);
                }
            }
        }
        $this->updateWordPressOption('wp-piwik-notices', $notices);
    }

    /**
     * Get the settings page URL
     *
     * @return string settings page URL
     */
    private function getSettingsURL()
    {
        return ($this->getSettings()->checkNetworkActivation() ? 'settings' : 'options-general') . '.php?page=' . self::$pluginBasename;
    }

    /**
     * Echo javascript tracking code
     */
    public function addJavascriptCode()
    {
        if ($this->isHiddenUser()) {
            self::$logger->log('Do not add tracking code to site (user should not be tracked) Blog ID: ' . self::$blog_id . ' Site ID: ' . $this->getSettings()->getOption('site_id'));
            return;
        }
        $trackingCode = new WP_Piwik\TrackingCode ($this);
        $trackingCode->is404 = (is_404() && $this->getSettings()->getGlobalOption('track_404'));
        $trackingCode->isUsertracking = $this->getSettings()->getGlobalOption('track_user_id') != 'disabled';
        $trackingCode->isSearch = (is_search() && $this->getSettings()->getGlobalOption('track_search'));
        self::$logger->log('Add tracking code. Blog ID: ' . self::$blog_id . ' Site ID: ' . $this->getSettings()->getOption('site_id'));
        if ($this->isNetworkMode() && $this->getSettings()->getGlobalOption('track_mode') == 'manually') {
            $siteId = $this->getPiwikSiteId();
            if ($siteId != 'n/a') {
                echo str_replace('{ID}', $siteId, $trackingCode->getTrackingCode());
            } else {
                echo '<!-- Site will be created and tracking code added on next request -->';
            }
        } else {
            echo $trackingCode->getTrackingCode();
        }
    }

    /**
     * Echo DNS prefetch tag
     */
    public function addDNSPrefetchTag()
    {
        echo '<link rel="dns-prefetch" href="' . $this->getPiwikDomain() . '" />';
    }

    /**
     * Get Piwik Domain
     */
    public function getPiwikDomain()
    {
        return match ($this->getSettings()->getGlobalOption('piwik_mode')) {
            'php' => '//' . parse_url($this->getSettings()->getGlobalOption('proxy_url'), PHP_URL_HOST),
            'cloud' => '//' . $this->getSettings()->getGlobalOption('piwik_user') . '.innocraft.cloud',
            'cloud-matomo' => '//' . $this->getSettings()->getGlobalOption('matomo_user') . '.matomo.cloud',
            default => '//' . parse_url($this->getSettings()->getGlobalOption('piwik_url'), PHP_URL_HOST),
        };
    }

    /**
     * Echo noscript tracking code
     */
    public function addNoscriptCode()
    {
        if ($this->getSettings()->getGlobalOption('track_mode') == 'proxy') {
            return;
        }
        if ($this->isHiddenUser()) {
            self::$logger->log('Do not add noscript code to site (user should not be tracked) Blog ID: ' . self::$blog_id . ' Site ID: ' . $this->getSettings()->getOption('site_id'));
            return;
        }
        self::$logger->log('Add noscript code. Blog ID: ' . self::$blog_id . ' Site ID: ' . $this->getSettings()->getOption('site_id'));
        echo $this->getSettings()->getOption('noscript_code') . "\n";
    }

    /**
     * Register post view meta boxes
     */
    public function addPostMetaboxes()
    {
        if ($this->getSettings()->getGlobalOption('add_customvars_box')) {
            add_action('add_meta_boxes', array(
                new WP_Piwik\Template\MetaBoxCustomVars ($this, $this->getSettings()),
                'addMetabox'
            ));
            add_action('save_post', array(
                new WP_Piwik\Template\MetaBoxCustomVars ($this, $this->getSettings()),
                'saveCustomVars'
            ), 10, 2);
        }
        if ($this->getSettings()->getGlobalOption('perpost_stats') != "disabled") {
            add_action('add_meta_boxes', array(
                $this,
                'onloadPostPage'
            ));
        }
    }

    /**
     * Register admin menu components
     */
    public function buildAdminMenu()
    {
        if (self::isConfigured()) {
            $cap = 'wp-piwik_read_stats';
            if ($this->getSettings()->checkNetworkActivation()) {
                global $current_user;
                $userRoles = $current_user->roles;
                $allowed = $this->getSettings()->getGlobalOption('capability_read_stats');
                if (is_array($userRoles) && is_array($allowed)) {
                    foreach ($userRoles as $userRole) {
                        if (isset($allowed[$userRole]) && $allowed[$userRole]) {
                            $cap = 'read';
                            break;
                        }
                    }
                }
            }
            $statsPage = new WP_Piwik\Admin\Statistics ($this, $this->getSettings());
            $this->statsPageId = add_dashboard_page(__('Matomo Statistics', 'wp-piwik'), $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), $cap, 'wp-piwik_stats', array(
                $statsPage,
                'show'
            ));
            $this->loadAdminStatsHeader($this->statsPageId, $statsPage);
        }
        if (!$this->getSettings()->checkNetworkActivation()) {
            $optionsPage = new WP_Piwik\Admin\Settings ($this, $this->getSettings());
            self::$optionsPageId = add_options_page($this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), 'activate_plugins', __FILE__, array(
                $optionsPage,
                'show'
            ));
            $this->loadAdminSettingsHeader(self::$optionsPageId, $optionsPage);
        }
    }

    /**
     * Register network admin menu components
     */
    public function buildNetworkAdminMenu()
    {
        if (self::isConfigured()) {
            $statsPage = new WP_Piwik\Admin\Network ($this, $this->getSettings());
            $this->statsPageId = add_dashboard_page(__('Matomo Statistics', 'wp-piwik'), $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), 'manage_sites', 'wp-piwik_stats', array(
                $statsPage,
                'show'
            ));
            $this->loadAdminStatsHeader($this->statsPageId, $statsPage);
        }
        $optionsPage = new WP_Piwik\Admin\Settings ($this, $this->getSettings());
        self::$optionsPageId = add_submenu_page('settings.php', $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), $this->getSettings()->getNotEmptyGlobalOption('plugin_display_name'), 'manage_sites', __FILE__, array(
            $optionsPage,
            'show'
        ));
        $this->loadAdminSettingsHeader(self::$optionsPageId, $optionsPage);
    }

    /**
     * Register admin header extensions for stats page
     *
     * @param $optionsPageId options
     *            page id
     * @param $optionsPage options
     *            page object
     */
    public function loadAdminStatsHeader($statsPageId, $statsPage)
    {
        add_action('admin_print_scripts-' . $statsPageId, array(
            $statsPage,
            'printAdminScripts'
        ));
        add_action('admin_print_styles-' . $statsPageId, array(
            $statsPage,
            'printAdminStyles'
        ));
        add_action('load-' . $statsPageId, array(
            $this,
            'onloadStatsPage'
        ));
    }

    /**
     * Register admin header extensions for settings page
     *
     * @param $optionsPageId options
     *            page id
     * @param $optionsPage options
     *            page object
     */
    public function loadAdminSettingsHeader($optionsPageId, $optionsPage)
    {
        add_action('admin_head-' . $optionsPageId, array(
            $optionsPage,
            'extendAdminHeader'
        ));
        add_action('admin_print_styles-' . $optionsPageId, array(
            $optionsPage,
            'printAdminStyles'
        ));
    }

    /**
     * Register WordPress dashboard widgets
     */
    public function extendWordPressDashboard()
    {
        if (current_user_can('wp-piwik_read_stats')) {
            if ($this->getSettings()->getGlobalOption('dashboard_widget') != 'disabled') {
                new WP_Piwik\Widget\Overview ($this, $this->getSettings(), 'dashboard', 'side', 'default', array(
                    'date' => $this->getSettings()->getGlobalOption('dashboard_widget'),
                    'period' => 'day'
                ));
            }
            if ($this->getSettings()->getGlobalOption('dashboard_chart')) {
                new WP_Piwik\Widget\Chart ($this, $this->getSettings());
            }
            if ($this->getSettings()->getGlobalOption('dashboard_ecommerce')) {
                new WP_Piwik\Widget\Ecommerce ($this, $this->getSettings());
            }
            if ($this->getSettings()->getGlobalOption('dashboard_seo')) {
                new WP_Piwik\Widget\Seo ($this, $this->getSettings());
            }
        }
    }

    /**
     * Register WordPress toolbar components
     */
    public function extendWordPressToolbar($toolbar)
    {
        if (current_user_can('wp-piwik_read_stats') && is_admin_bar_showing()) {
            $id = WP_Piwik\Request::register('VisitsSummary.getUniqueVisitors', array(
                'period' => 'day',
                'date' => 'last30'
            ));
            $unique = $this->request($id);
            $url = is_network_admin() ? $this->getSettingsURL() : false;
            $content = is_network_admin() ? __('Configure WP-Matomo', 'wp-piwik') : '';
            // Leave if result array does contain a message instead of valid data
            if (isset($unique['result'])) {
                $content .= '<!-- ' . $unique['result'] . ': ' . ($unique['message'] ? $unique['message'] : '...') . ' -->';
            } elseif (is_array($unique)) {
                $labels = "";
                for ($i = 0; $i < count($unique); $i++) {
                    $labels .= $i . ",";
                }
                ob_start();
                ?>
                <div style="width:100px; height:100%;">
                    <canvas id="wpPiwikSparkline"
                            style="max-width:100%; max-height:100%;padding-top:4px; padding-bottom:4px;"></canvas>
                </div>
                <script>
                    function showWpPiwikSparkline() {
                        new Chart(document.getElementById('wpPiwikSparkline').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: [<?php echo $labels; ?>],
                                datasets: [
                                    {
                                        borderColor: "rgb(240, 240, 241)",
                                        backgroundColor: "rgb(240, 240, 241)",
                                        borderWidth: 1,
                                        radius: 0,
                                        data: [<?php echo implode(',', $unique); ?>]
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {display: false},
                                    tooltip: {enabled: false}
                                },
                                scales: {
                                    y: {display: false},
                                    x: {display: false}
                                }
                            }
                        });
                    }

                    jQuery(showWpPiwikSparkline);
                </script>
                <?php
                $content .= ob_get_contents();
                ob_end_clean();
                $url = $this->getStatsURL();
            }
            $toolbar->add_menu(array(
                'id' => 'wp-piwik_stats',
                'title' => $content,
                'href' => $url
            ));
        }
    }

    /**
     * Add plugin meta data
     *
     * @param array $links
     *            list of already defined plugin meta data
     * @param string $file
     *            handled file
     * @return array complete list of plugin meta data
     */
    public function setPluginMeta($links, $file)
    {
        if ($file == 'wp-piwik/wp-piwik.php' && (!$this->isNetworkMode() || is_network_admin())) {
            return array_merge($links, array(
                sprintf('<a href="%s">%s</a>', self::getSettingsURL(), __('Settings', 'wp-piwik'))
            ));
        }
        return $links;
    }

    /**
     * Prepare toolbar widget requirements
     */
    public function loadToolbarRequirements()
    {
        if (is_admin_bar_showing()) {
            wp_enqueue_script('wp-piwik-chartjs', $this->getPluginURL() . 'js/chartjs/chart.min.js', "3.4.1");
        }
    }

    /**
     * Add tracking pixels to feed content
     *
     * @param string $content
     *            post content
     * @return string post content extended by tracking pixel
     */
    public function addFeedTracking($content)
    {
        global $post;
        if (is_feed()) {
            self::$logger->log('Add tracking image to feed entry.');
            if (!$this->getSettings()->getOption('site_id')) {
                $siteId = $this->requestPiwikSiteId();
                if ($siteId != 'n/a') {
                    $this->getSettings()->setOption('site_id', $siteId);
                } else {
                    return false;
                }
            }
            $title = the_title(null, null, false);
            $posturl = get_permalink($post->ID);
            $urlref = get_bloginfo('rss2_url');
            if ($this->getSettings()->getGlobalOption('track_mode') == 'proxy') {
                $url = plugins_url('wp-piwik') . '/proxy/matomo.php';
            } else {
                $url = $this->getSettings()->getGlobalOption('piwik_url');
                if (substr($url, -10, 10) == '/index.php') {
                    $url = str_replace('/index.php', '/matomo.php', $url);
                } else {
                    $url .= 'piwik.php';
                }
            }
            $trackingImage = $url . '?idsite=' . $this->getSettings()->getOption('site_id') . '&amp;rec=1&amp;url=' . urlencode($posturl) . '&amp;action_name=' . urlencode($title) . '&amp;urlref=' . urlencode($urlref);
            $content .= '<img src="' . $trackingImage . '" style="border:0;width:0;height:0" width="0" height="0" alt="" />';
        }
        return $content;
    }

    /**
     * Add a campaign parameter to feed permalink
     *
     * @param string $permalink
     *            permalink
     * @return string permalink extended by campaign parameter
     */
    public function addFeedCampaign($permalink)
    {
        global $post;
        if (is_feed()) {
            self::$logger->log('Add campaign to feed permalink.');
            $sep = (strpos($permalink, '?') === false ? '?' : '&');
            $permalink .= $sep . 'pk_campaign=' . urlencode($this->getSettings()->getGlobalOption('track_feed_campaign')) . '&pk_kwd=' . urlencode($post->post_name);
        }
        return $permalink;
    }

    /**
     * Forwards the cross domain parameter pk_vid if the URL parameter is set and a user is about to be redirected.
     * When another website links to WooCommerce with a pk_vid parameter, and WooCommerce redirects the user to another
     * URL, the pk_vid parameter would get lost and the visitorId would later not be applied by the tracking code
     * due to the lost pk_vid URL parameter. If the URL parameter is set, we make sure to forward this parameter.
     *
     * @param string $location
     *
     * @return string location extended by pk_vid URL parameter if the URL parameter is set
     */
    public function forwardCrossDomainVisitorId($location)
    {

        if (!empty($_GET['pk_vid'])
            && preg_match('/^[a-zA-Z0-9]{24,48}$/', $_GET['pk_vid'])) {
            // currently, the pk_vid parameter is 32 characters long, but it may vary over time.
            $location = add_query_arg('pk_vid', $_GET['pk_vid'], $location);
        }

        return $location;
    }

    /**
     * Apply settings update
     *
     * @return boolean settings update applied
     */
    private function applySettings()
    {
        $this->getSettings()->applyChanges($_POST ['wp-piwik']);
        $this->getSettings()->setGlobalOption('revision', self::$revisionId);
        $this->getSettings()->setGlobalOption('last_settings_update', time());
        return true;
    }

    /**
     * Check if WP-Piwik is configured
     *
     * @return boolean Is WP-Piwik configured?
     */
    public static function isConfigured()
    {
        return
            $this->getSettings()->getGlobalOption('piwik_token') &&
            ($this->getSettings()->getGlobalOption('piwik_mode') != 'disabled') &&
            (
                (
                    ($this->getSettings()->getGlobalOption('piwik_mode') == 'http')
                    && ($this->getSettings()->getGlobalOption('piwik_url'))
                ) || (
                    ($this->getSettings()->getGlobalOption('piwik_mode') == 'php')
                    && ($this->getSettings()->getGlobalOption('piwik_path'))
                ) || (
                    ($this->getSettings()->getGlobalOption('piwik_mode') == 'cloud')
                    && ($this->getSettings()->getGlobalOption('piwik_user'))
                ) || (
                    ($this->getSettings()->getGlobalOption('piwik_mode') == 'cloud-matomo')
                    && ($this->getSettings()->getGlobalOption('matomo_user'))
                )
            );
    }

    /**
     * Check if WP-Piwik was updated
     *
     * @return boolean Was WP-Piwik updated?
     */
    private function isUpdated()
    {
        return $this->getSettings()->getGlobalOption('revision') && $this->getSettings()->getGlobalOption('revision') < self::$revisionId;
    }

    /**
     * Check if WP-Piwik is already installed
     *
     * @return boolean Is WP-Piwik installed?
     */
    private function isInstalled()
    {
        $oldSettings = $this->getWordPressOption('wp-piwik_global-settings', false);
        if ($oldSettings && isset($oldSettings['revision'])) {
            self::log('Save old settings');
            $this->getSettings()->setGlobalOption('revision', $oldSettings['revision']);
        } else {
            self::log('Current revision ' . $this->getSettings()->getGlobalOption('revision'));
        }
        return $this->getSettings()->getGlobalOption('revision') > 0;
    }

    /**
     * Check if new settings were submitted
     *
     * @return boolean Are new settings submitted?
     */
    public static function isConfigSubmitted()
    {
        return isset ($_POST) && isset ($_POST ['wp-piwik']) && self::isValidOptionsPost();
    }

    /**
     * Check if PHP mode is chosen
     *
     * @return Is PHP mode chosen?
     */
    public function isPHPMode()
    {
        return $this->getSettings()->getGlobalOption('piwik_mode') && $this->getSettings()->getGlobalOption('piwik_mode') == 'php';
    }

    /**
     * Check if WordPress is running in network mode
     *
     * @return boolean Is WordPress running in network mode?
     */
    public function isNetworkMode()
    {
        return $this->getSettings()->checkNetworkActivation();
    }

    /**
     * Check if a WP-Piwik dashboard widget is enabled
     *
     * @return boolean Is a dashboard widget enabled?
     */
    private function isDashboardActive()
    {
        return $this->getSettings()->getGlobalOption('dashboard_widget') || $this->getSettings()->getGlobalOption('dashboard_chart') || $this->getSettings()->getGlobalOption('dashboard_seo');
    }

    /**
     * Check if a WP-Piwik toolbar widget is enabled
     *
     * @return boolean Is a toolbar widget enabled?
     */
    private function isToolbarActive()
    {
        return $this->getSettings()->getGlobalOption('toolbar');
    }

    /**
     * Check if WP-Piwik tracking code insertion is enabled
     *
     * @return boolean Insert tracking code?
     */
    private function isTrackingActive()
    {
        return $this->getSettings()->getGlobalOption('track_mode') != 'disabled';
    }

    /**
     * Check if admin tracking is enabled
     *
     * @return boolean Is admin tracking enabled?
     */
    private function isAdminTrackingActive()
    {
        return $this->getSettings()->getGlobalOption('track_admin') && is_admin();
    }

    /**
     * Check if WP-Piwik noscript code insertion is enabled
     *
     * @return boolean Insert noscript code?
     */
    private function isAddNoScriptCode()
    {
        return $this->getSettings()->getGlobalOption('track_noscript');
    }

    /**
     * Check if feed tracking is enabled
     *
     * @return boolean Is feed tracking enabled?
     */
    private function isTrackFeed()
    {
        return $this->getSettings()->getGlobalOption('track_feed');
    }

    /**
     * Check if feed permalinks get a campaign parameter
     *
     * @return boolean Add campaign parameter to feed permalinks?
     */
    private function isAddFeedCampaign()
    {
        return $this->getSettings()->getGlobalOption('track_feed_addcampaign');
    }

    /**
     * Check if feed permalinks get a campaign parameter
     *
     * @return boolean Add campaign parameter to feed permalinks?
     */
    private function isCrossDomainLinkingEnabled()
    {
        return $this->getSettings()->getGlobalOption('track_crossdomain_linking');
    }

    /**
     * Check if WP-Piwik shortcodes are enabled
     *
     * @return boolean Are shortcodes enabled?
     */
    private function isAddShortcode()
    {
        return $this->getSettings()->getGlobalOption('shortcodes');
    }

    /**
     * Define Piwik constants for PHP reporting API
     */
    public static function defineMatomoConstants(): void
    {
        if (!defined('MATOMO_INCLUDE_PATH')) {
            define('MATOMO_INCLUDE_PATH', $this->getSettings()->getGlobalOption('piwik_path'));
            define('MATOMO_USER_PATH', $this->getSettings()->getGlobalOption('piwik_path'));
            define('MATOMO_ENABLE_DISPATCH', false);
            define('MATOMO_ENABLE_ERROR_HANDLER', false);
            define('MATOMO_ENABLE_SESSION_START', false);
        }
    }


    /**
     * Include a WP-Piwik file
     */
    private function includeFile($strFile)
    {
        self::$logger->log('Include ' . $strFile . '.php');
        if (CONNECT_MATOMO_PATH . $strFile . '.php') {
            include_once CONNECT_MATOMO_PATH . $strFile . '.php';
        }
    }

    /**
     * Check if user should not be tracked
     *
     * @return boolean Do not track user?
     */
    private function isHiddenUser()
    {
        if (is_multisite()) {
            foreach ($this->getSettings()->getGlobalOption('capability_stealth') as $key => $val) {
                if ($val && current_user_can($key)) {
                    return true;
                }
            }
        }
        return current_user_can('wp-piwik_stealth');
    }

    /**
     * Check if tracking code is up to date
     *
     * @return boolean Is tracking code up to date?
     */
    public function isCurrentTrackingCode()
    {
        return $this->getSettings()->getOption('last_tracking_code_update') && $this->getSettings()->getOption('last_tracking_code_update') > $this->getSettings()->getGlobalOption('last_settings_update');
    }

    /**
     * DEPRECTAED Add javascript code to site header
     *
     * @deprecated
     *
     */
    public function site_header()
    {
        self::$logger->log('Using deprecated function site_header');
        $this->addJavascriptCode();
    }

    /**
     * DEPRECTAED Add javascript code to site footer
     *
     * @deprecated
     *
     */
    public function site_footer()
    {
        self::$logger->log('Using deprecated function site_footer');
        $this->addNoscriptCode();
    }

    /**
     * Identify new posts if an annotation is required
     * and create Piwik annotation
     *
     * @param string $newStatus
     *            new post status
     * @param strint $oldStatus
     *            new post status
     * @param object $post
     *            current post object
     */
    public function addPiwikAnnotation($newStatus, $oldStatus, $post)
    {
        $enabledPostTypes = $this->getSettings()->getGlobalOption('add_post_annotations');
        if (isset($enabledPostTypes[$post->post_type]) && $enabledPostTypes[$post->post_type] && $newStatus == 'publish' && $oldStatus != 'publish') {
            $note = 'Published: ' . $post->post_title . ' - URL: ' . get_permalink($post->ID);
            $id = WP_Piwik\Request::register('Annotations.add', array(
                'idSite' => $this->getPiwikSiteId(),
                'date' => date('Y-m-d'),
                'note' => $note
            ));
            $result = $this->request($id);
            self::$logger->log('Add post annotation. ' . $note . ' - ' . serialize($result));
        }
    }

    /**
     * Get WP-Piwik's URL
     */
    public function getPluginURL()
    {
        return trailingslashit(plugin_dir_url(dirname(__FILE__)));
    }

    /**
     * Get WP-Piwik's version
     */
    public function getPluginVersion()
    {
        return self::$version;
    }

    /**
     * Enable three columns for WP-Piwik stats screen
     *
     * @param
     *            array full list of column settings
     * @param
     *            mixed current screen id
     * @return array updated list of column settings
     */
    public function onScreenLayoutColumns($columns, $screen)
    {
        if (isset($this->statsPageId) && $screen == $this->statsPageId) {
            $columns [$this->statsPageId] = 3;
        }
        return $columns;
    }

    /**
     * Add tracking code to admin header
     */
    function addAdminHeaderTracking()
    {
        $this->addJavascriptCode();
    }

    /**
     * Get option value
     *
     * @param string $key
     *            option key
     * @return mixed option value
     */
    public function getOption($key)
    {
        return $this->getSettings()->getOption($key);
    }

    /**
     * Get global option value
     *
     * @param string $key
     *            global option key
     * @return mixed global option value
     */
    public function getGlobalOption($key)
    {
        return $this->getSettings()->getGlobalOption($key);
    }

    /**
     * Get stats page URL
     *
     * @return string stats page URL
     */
    public function getStatsURL()
    {
        return admin_url() . '?page=wp-piwik_stats';
    }

    /**
     * @return int
     */
    private function getBlogId(): int
    {
        return $this->blogId;
    }

    /**
     * @param int $blogId
     */
    private function setBlogId(int $blogId): void
    {
        $this->blogId = $blogId;
    }

    /**
     * @return string
     */
    private function getPluginBasename(): string
    {
        return $this->pluginBasename;
    }

    /**
     * @param string $pluginBasename
     */
    private function setPluginBasename(string $pluginBasename): void
    {
        $this->pluginBasename = $pluginBasename;
    }

    /**
     * @return Settings
     */
    private function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     * @param Settings $settings
     */
    private function setSettings(Settings $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return int
     */
    public function getRevisionId(): int
    {
        return $this->revisionId;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Execute WP-Piwik test script
     */
    private function loadTestscript()
    {
        $this->includeFile('debug' . DIRECTORY_SEPARATOR . 'testscript');
    }

    /**
     * Echo an error message
     *
     * @param string $message
     *            message content
     */
    private static function showErrorMessage($message)
    {
        echo '<strong class="wp-piwik-error">' . __('An error occured', 'wp-piwik') . ':</strong> ' . $message . ' [<a href="' . ($this->getSettings()->checkNetworkActivation() ? 'network/settings' : 'options-general') . '.php?page=wp-piwik/classes/WP_Piwik.php&tab=support">' . __('Support', 'wp-piwik') . '</a>]';
    }

    /**
     * Perform a Piwik request
     *
     * @param string $id
     *            request ID
     * @return mixed request result
     */
    public function request($id, $debug = false)
    {
        if ($this->getSettings()->getGlobalOption('piwik_mode') == 'disabled') {
            return 'n/a';
        }
        if (!isset (self::$request) || empty (self::$request)) {
            self::$request = ($this->getSettings()->getGlobalOption('piwik_mode') == 'http' || $this->getSettings()->getGlobalOption('piwik_mode') == 'cloud' || $this->getSettings()->getGlobalOption('piwik_mode') == 'cloud-matomo' ? new WP_Piwik\Request\Rest ($this, $this->getSettings()) : new WP_Piwik\Request\Php ($this, $this->getSettings()));
        }
        if ($debug) {
            return self::$request->getDebug($id);
        }
        return self::$request->perform($id);
    }

    /**
     * Reset request object
     */
    public function resetRequest()
    {
        if (is_object(self::$request)) {
            self::$request->reset();
        }
        self::$request = null;
    }

    /**
     * Execute WP-Piwik shortcode
     *
     * @param array $attributes
     *            attribute list
     */
    public function shortcode($attributes)
    {
        shortcode_atts(array(
            'title' => '',
            'module' => 'overview',
            'period' => 'day',
            'date' => 'yesterday',
            'limit' => 10,
            'width' => '100%',
            'height' => '200px',
            'idsite' => '',
            'language' => 'en',
            'range' => false,
            'key' => 'sum_daily_nb_uniq_visitors'
        ), $attributes);
        $shortcodeObject = new \WP_Piwik\Shortcode ($attributes, $this, $this->getSettings());
        return $shortcodeObject->get();
    }

    /**
     * Get Piwik site ID by blog ID
     *
     * @param int $blogId
     *            which blog's Piwik site ID to get, default is the current blog
     * @return mixed Piwik site ID or n/a
     */
    public function getPiwikSiteId($blogId = null, $forceFetch = false)
    {
        if (!$blogId && $this->isNetworkMode()) {
            $blogId = get_current_blog_id();
        }
        $result = $this->getSettings()->getOption('site_id');
        self::$logger->log('Database result: ' . $result);
        return !empty ($result) && !$forceFetch ? $result : $this->requestPiwikSiteId($blogId);
    }

    /**
     * Get a detailed list of all Piwik sites
     *
     * @return array Piwik sites
     */
    public function getPiwikSiteDetails()
    {
        $id = WP_Piwik\Request::register('SitesManager.getSitesWithAtLeastViewAccess', array());
        $piwikSiteDetails = $this->request($id);
        return $piwikSiteDetails;
    }

    /**
     * Estimate a Piwik site ID by blog ID
     *
     * @param int $blogId
     *            which blog's Piwik site ID to estimate, default is the current blog
     * @return mixed Piwik site ID or n/a
     */
    private function requestPiwikSiteId($blogId = null)
    {
        $isCurrent = !$this->getSettings()->checkNetworkActivation() || empty ($blogId);
        if ($this->getSettings()->getGlobalOption('auto_site_config')) {
            $id = WP_Piwik\Request::register('SitesManager.getSitesIdFromSiteUrl', array(
                'url' => $isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl
            ));
            $result = $this->request($id);
            $this->log('Tried to identify current site, result: ' . serialize($result));
            if (is_array($result) && empty($result)) {
                $result = $this->addPiwikSite($blogId);
            } elseif ($result != 'n/a' && isset($result [0])) {
                $result = $result [0] ['idsite'];
            } else {
                $result = null;
            }
        } else {
            $result = null;
        }
        self::$logger->log('Get Matomo ID: WordPress site ' . ($isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl) . ' = Matomo ID ' . $result);
        if ($result !== null) {
            $this->getSettings()->setOption('site_id', $result, $blogId);
            if ($this->getSettings()->getGlobalOption('track_mode') != 'disabled' && $this->getSettings()->getGlobalOption('track_mode') != 'manually') {
                $this->updateTrackingCode($result, $blogId);
            }
            $this->getSettings()->save();
            return $result;
        }
        return 'n/a';
    }

    /**
     * Add a new Piwik
     *
     * @param int $blogId
     *            which blog's Piwik site to create, default is the current blog
     * @return int Piwik site ID
     */
    public function addPiwikSite($blogId = null)
    {
        $isCurrent = !$this->getSettings()->checkNetworkActivation() || empty ($blogId);
        // Do not add site if Piwik connection is unreliable
        if (!$this->request('global.getPiwikVersion')) {
            return null;
        }
        $id = WP_Piwik\Request::register('SitesManager.addSite', array(
            'urls' => $isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl,
            'siteName' => urlencode($isCurrent ? get_bloginfo('name') : get_blog_details($blogId)->blogname)
        ));
        $result = $this->request($id);
        if (is_array($result) && isset($result['value'])) {
            $result = (int)$result['value'];
        } else {
            $result = (int)$result;
        }
        self::$logger->log('Create Matomo ID: WordPress site ' . ($isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl) . ' = Matomo ID ' . $result);
        if (empty ($result)) {
            return null;
        } else {
            do_action('wp-piwik_site_created', $result);
            return $result;
        }
    }

    /**
     * Update a Piwik site's detail information
     *
     * @param int $siteId
     *            which Piwik site to updated
     * @param int $blogId
     *            which blog's Piwik site ID to get, default is the current blog
     */
    private function updatePiwikSite($siteId, $blogId = null)
    {
        $isCurrent = !$this->getSettings()->checkNetworkActivation() || empty ($blogId);
        $id = WP_Piwik\Request::register('SitesManager.updateSite', array(
            'idSite' => $siteId,
            'urls' => $isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl,
            'siteName' => $isCurrent ? get_bloginfo('name') : get_blog_details($blogId)->blogname
        ));
        $this->request($id);
        self::$logger->log('Update Matomo site: WordPress site ' . ($isCurrent ? get_bloginfo('url') : get_blog_details($blogId)->siteurl));
    }

    /**
     * Update a site's tracking code
     *
     * @param int $siteId
     *            which Piwik site to updated
     * @param int $blogId
     *            which blog's Piwik site ID to get, default is the current blog
     * @return string tracking code
     */
    public function updateTrackingCode($siteId = false, $blogId = null)
    {
        if (!$siteId) {
            $siteId = $this->getPiwikSiteId();
        }
        if ($this->getSettings()->getGlobalOption('track_mode') == 'disabled' || $this->getSettings()->getGlobalOption('track_mode') == 'manually') {
            return false;
        }
        $id = WP_Piwik\Request::register('SitesManager.getJavascriptTag', array(
            'idSite' => $siteId,
            'mergeSubdomains' => $this->getSettings()->getGlobalOption('track_across') ? 1 : 0,
            'mergeAliasUrls' => $this->getSettings()->getGlobalOption('track_across_alias') ? 1 : 0,
            'disableCookies' => $this->getSettings()->getGlobalOption('disable_cookies') ? 1 : 0,
            'crossDomain' => $this->getSettings()->getGlobalOption('track_crossdomain_linking') ? 1 : 0,
            'trackNoScript' => 1
        ));
        $code = $this->request($id);
        if (is_array($code) && isset($code['value'])) {
            $code = $code['value'];
        }
        $result = !is_array($code) ? html_entity_decode($code) : '<!-- ' . json_encode($code) . ' -->';
        self::$logger->log('Delivered tracking code: ' . $result);
        $result = WP_Piwik\TrackingCode::prepareTrackingCode($result, $this->getSettings(), self::$logger, true);
        if (isset ($result ['script']) && !empty ($result ['script'])) {
            $this->getSettings()->setOption('tracking_code', $result ['script'], $blogId);
            $this->getSettings()->setOption('noscript_code', $result ['noscript'], $blogId);
            $this->getSettings()->setGlobalOption('proxy_url', $result ['proxy']);
        }
        return $result;
    }

    /**
     * Update Piwik site if blog name changes
     *
     * @param string $oldValue
     *            old blog name
     * @param string $newValue
     *            new blog name
     */
    public function onBlogNameChange($oldValue, $newValue = null)
    {
        $this->updatePiwikSite($this->getSettings()->getOption('site_id'));
    }

    /**
     * Update Piwik site if blog URL changes
     *
     * @param string $oldValue
     *            old blog URL
     * @param string $newValue
     *            new blog URL
     */
    public function onSiteUrlChange($oldValue, $newValue = null)
    {
        $this->updatePiwikSite($this->getSettings()->getOption('site_id'));
    }

    /**
     * Register stats page meta boxes
     *
     * @param mixed $statsPageId
     *            WordPress stats page ID
     */
    public function onloadStatsPage($statsPageId)
    {
        if ($this->getSettings()->getGlobalOption('disable_timelimit')) {
            set_time_limit(0);
        }
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');
        wp_enqueue_script('wp-piwik', $this->getPluginURL() . 'js/wp-piwik.js', array(), self::$version, true);
        wp_enqueue_script('wp-piwik-chartjs', $this->getPluginURL() . 'js/chartjs/chart.min.js', "3.4.1");
        new \WP_Piwik\Widget\Chart ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Visitors ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Overview ($this, $this->getSettings(), $this->statsPageId);
        if ($this->getSettings()->getGlobalOption('stats_ecommerce')) {
            new \WP_Piwik\Widget\Ecommerce ($this, $this->getSettings(), $this->statsPageId);
            new \WP_Piwik\Widget\Items ($this, $this->getSettings(), $this->statsPageId);
            new \WP_Piwik\Widget\ItemsCategory ($this, $this->getSettings(), $this->statsPageId);
        }
        if ($this->getSettings()->getGlobalOption('stats_seo')) {
            new \WP_Piwik\Widget\Seo ($this, $this->getSettings(), $this->statsPageId);
        }
        new \WP_Piwik\Widget\Pages ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Keywords ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Referrers ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Plugins ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Search ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Noresult ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Browsers ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\BrowserDetails ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Screens ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Types ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Models ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Systems ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\SystemDetails ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\City ($this, $this->getSettings(), $this->statsPageId);
        new \WP_Piwik\Widget\Country ($this, $this->getSettings(), $this->statsPageId);
    }

    /**
     * Add per post statistics to a post's page
     *
     * @param mixed $postPageId
     *            WordPress post page ID
     */
    public function onloadPostPage($postPageId)
    {
        global $post;
        $postUrl = get_permalink($post->ID);
        $this->log('Load per post statistics: ' . $postUrl);
        $locations = apply_filters('wp-piwik_meta_boxes_locations', get_post_types(array('public' => true), 'names'));
        array(
            new Post ($this, $this->getSettings(), $locations, 'side', 'default', array(
                'date' => $this->getSettings()->getGlobalOption('perpost_stats'),
                'period' => 'day',
                'url' => $postUrl
            )),
            'show'
        );
    }

    /**
     * Stats page changes by POST submit
     *
     * @see http://tinyurl.com/5r5vnzs
     */
    function onStatsPageSaveChanges()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Cheatin&#8217; uh?'));
        }
        check_admin_referer('wp-piwik_stats');
        wp_redirect($_POST ['_wp_http_referer']);
    }

    /**
     * Get option value, choose method depending on network mode
     *
     * @param string $option option key
     * @return string|array option value
     */
    private function getWordPressOption($option, $default = null)
    {
        return $this->isNetworkMode() ? get_site_option($option, $default) : get_option($option, $default);
    }

    /**
     * Delete option, choose method depending on network mode
     *
     * @param string $option option key
     */
    private function deleteWordPressOption($option)
    {
        if ($this->isNetworkMode()) {
            delete_site_option($option);
        } else {
            delete_option($option);
        }
    }

    /**
     * Set option value, choose method depending on network mode
     *
     * @param string $option option key
     * @param mixed $value option value
     */
    private function updateWordPressOption($option, $value)
    {
        if ($this->isNetworkMode()) {
            update_site_option($option, $value);
        } else {
            update_option($option, $value);
        }
    }

    /**
     * Check if WP-Piwik options page
     *
     * @return boolean True if current page is WP-Piwik's option page
     */
    public static function isValidOptionsPost()
    {
        return is_admin() && check_admin_referer('wp-piwik_settings') && current_user_can('manage_options');
    }

    /**
     * Log a message
     *
     * @param string $message
     *            logger message
     */
    public static function log($message): void
    {
        self::$logger->log($message);
    }

    /**
     * End logging
     */
    private function closeLogger(): void
    {
        self::$logger = null;
    }
}
