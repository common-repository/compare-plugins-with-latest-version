<?php
/**
 * Plugin Name: Compare Plugins With Latest Version
 * Plugin URI: http://wordpress.org/plugins/compare-plugins-with-latest-version
 * Description: In some cases, admin users might not want to upgrade their plugins because the admin user doesn’t know whether after an upgrade, what changes he may lose. So in this plugin, we provide one feature which can compare plugin’s current files with the new version file.
 * Author: Brainvire
 * Version: 1.0.3
 * Author URI: https://www.brainvire.com/
 *
 * @package ComparePluginsWithLatestVersion
 */

define( 'CPLV_VERSION', '1.0.3' );
define( 'CPLV_CURRENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPLV_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . '/' );
define( 'CPLV_TEXT_DOMAIN', 'pluginsfilecompare' );
define( 'CPLV_PLUGIN_URL', plugins_url( '/compare-plugins-with-latest-version/' ) );
define( 'CPLV_TEMP_FOLDER', 'compare-plugins-with-latest-version/extract/' );

require_once( CPLV_CURRENT_PLUGIN_DIR . 'class-pluginfilescomparison-admin.php' );
