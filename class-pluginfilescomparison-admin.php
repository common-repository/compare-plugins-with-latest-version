<?php
/**
 * Plugin Files Comparison Admin
 *
 * This class handles the comparison of plugin files with their latest versions.
 * It includes functionality for adding comparison links, handling AJAX requests,
 * managing plugin data, and scheduling cron jobs.
 *
 * @package ComparePluginsWithLatestVersion
 * @subpackage Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * PluginFilesComparison_Admin Class
 *
 * Handles the comparison of plugin files with the latest versions.
 *
 * @package ComparePluginsWithLatestVersion
 * @subpackage Admin
 */
class PluginFilesComparison_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$pfc_get_plugins = get_site_transient( 'update_plugins' );

		if ( isset( $pfc_get_plugins->response ) && is_array( $pfc_get_plugins->response ) ) {
			$pfc_get_plugins = array_keys( $pfc_get_plugins->response );
			foreach ( $pfc_get_plugins as $pfc_get_plugin_file ) {
				add_action( "in_plugin_update_message-{$pfc_get_plugin_file}", array( $this, 'pfcv_add_compare_file_link' ), 10, 2 );
			}
		}

		add_action( 'template_redirect', array( $this, 'pfcv_load_comparescreen' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'pfcv_extract_plugin_package_enqueue' ) );
		add_action( 'wp_ajax_pfcv_extract_plugin_package_ajax_action', array( $this, 'pfcv_extract_plugin_package_ajax_action' ) );
		add_action( 'wp_ajax_nopriv_pfcv_extract_plugin_package_ajax_action', array( $this, 'pfcv_extract_plugin_package_ajax_action' ) );
		add_action( 'pfcv_run_daily', array( $this, 'pfcv_daily_event_func' ) );
		register_activation_hook( CPLV_CURRENT_PLUGIN_DIR . 'compare-plugins-with-latest-version.php', array( $this, 'pfcv_schedule_cron_on_activation' ) );
		register_deactivation_hook( CPLV_CURRENT_PLUGIN_DIR . 'compare-plugins-with-latest-version.php', array( $this, 'pfcv_clear_cron_schedule' ) );
	}

	/**
	 * Add compare file with new version links.
	 *
	 * @param array  $plugin_data Array containing plugin data.
	 * @param object $response Response object from plugin update check.
	 */
	public static function pfcv_add_compare_file_link( $plugin_data, $response ) {
		$pfcv_get_package = ! empty( $plugin_data['package'] ) ? $plugin_data['package'] : '';
		$signed_hostnames = apply_filters( 'wp_signature_hosts', array( 'wordpress.org', 'downloads.wordpress.org', 's.w.org' ) );
		$check_package_pos = in_array( parse_url( $pfcv_get_package, PHP_URL_HOST ), $signed_hostnames, true );

		if ( ! empty( $pfcv_get_package ) && $check_package_pos ) {
			$pcf_get_plg_url = ! empty( $plugin_data['plugin'] ) ? $plugin_data['plugin'] : '';
			$pfcv_file_nonce = wp_create_nonce( $pcf_get_plg_url );
			$pcf_genrate_url = add_query_arg(
				array(
					'_pfcvview' => 'view',
					'pfcvnonce' => $pfcv_file_nonce,
					'pfcvfile' => rawurlencode( $pcf_get_plg_url ),
					'pfcvplugin' => rawurlencode( $pcf_get_plg_url ),
				),
				site_url()
			);
			echo '<a package-url="' . esc_attr( $pfcv_get_package ) . '" data-url="' . esc_url( $pcf_genrate_url ) . '" href="#" class="cmpfile"><strong>' . esc_html__( '(Compare files with new version)', 'cplv' ) . '</strong></a>';
			echo '<img class="loaderimg" style="width: 17px;display:none" src="' . esc_url( self::pfcv_retry_plugins_path( 'assets/images/loaderimage.gif' ) ) . '">';
		}
	}

	/**
	 * Load compare screen.
	 */
	public static function pfcv_load_comparescreen() {
		if ( isset( $_GET['_pfcvview'] ) && ! empty( $_GET['_pfcvview'] ) && sanitize_text_field( wp_unslash( $_GET['_pfcvview'] ) ) === 'view' && ! empty( $_GET['pfcvplugin'] ) ) {
			$pfcv_nonce = isset( $_GET['pfcvnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['pfcvnonce'] ) ) : '';
			$pfcv_get_file = isset( $_GET['pfcvfile'] ) ? sanitize_text_field( wp_unslash( $_GET['pfcvfile'] ) ) : '';

			if ( ! wp_verify_nonce( $pfcv_nonce, $pfcv_get_file ) ) {
				wp_die( esc_html__( 'Your nonce did not verify', 'cplv' ) );
			} else {
				/**
				 * Remove all scripts except specified ones.
				 */
				function pm_remove_all_scripts() {
					global $wp_scripts;
					$wp_scripts->queue = array( 'bootstrap-min-js', 'compare-screen-js' );
				}
				add_action( 'wp_print_scripts', 'pm_remove_all_scripts', 100 );

				/**
				 * Remove all styles except specified ones.
				 */
				function pm_remove_all_styles() {
					global $wp_styles;
					$wp_styles->queue = array( 'bootstrap-min-css', 'load-compare-css', 'bv-main-css', 'admin-bar' );
				}
				add_action( 'wp_print_styles', 'pm_remove_all_styles', 100 );
				require CPLV_CURRENT_PLUGIN_DIR . 'cplv-load-comparescreen-file.php';
			}
			exit();
		}
	}

	/**
	 * Enqueue scripts for extracting the plugin package.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function pfcv_extract_plugin_package_enqueue( $hook ) {
		if ( 'plugins.php' != $hook ) {
			// Only applies to plugins panel.
			return;
		}

		wp_enqueue_script( 'pfcv-package-extract', self::pfcv_retry_plugins_path( 'assets/js/extract-package.js' ), array( 'jquery' ), '1.0.3', true );
		wp_localize_script(
			'pfcv-package-extract',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'extractpackage' ),
			)
		);
	}

	/**
	 * Handle AJAX request for extracting the plugin package.
	 */
	public static function pfcv_extract_plugin_package_ajax_action() {
		check_ajax_referer( 'extractpackage', 'security' );
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		$packageurl = isset( $_POST['packageurl'] ) ? sanitize_text_field( wp_unslash( $_POST['packageurl'] ) ) : '';
		$extractfolder = CPLV_CURRENT_PLUGIN_DIR . 'extract';
		$error = '';
		$sucs = '';

		if ( $wp_filesystem->is_dir( $extractfolder ) ) {
			self::pfcv_unlink_extract_folder();
			$wp_filesystem->mkdir( $extractfolder, FS_CHMOD_DIR );
		} else {
			$wp_filesystem->mkdir( $extractfolder, FS_CHMOD_DIR );
		}

		if ( $wp_filesystem->is_dir( $extractfolder ) ) {
			$get_package_file = download_url( $packageurl, 300, false );
			$unzipfile = unzip_file( $get_package_file, $extractfolder );
			if ( $unzipfile ) {
				$sucs = 'true';
			} else {
				$error = 'There was an error unzipping the file';
				$sucs = 'false';
			}
		} else {
			$error = 'Could not create "extract" directory in this path "' . $extractfolder . '"';
			$sucs = 'false';
		}

		echo json_encode(
			array(
				'sucs' => $sucs,
				'error' => $error,
			)
		);
		wp_die();
	}

	/**
	 * Get the plugin path.
	 *
	 * @param string $path The relative path.
	 * @return string The full URL.
	 */
	public static function pfcv_retry_plugins_path( $path ) {
		return plugins_url( $path, __FILE__ );
	}

	/**
	 * Schedule a daily cron event on plugin activation.
	 */
	public static function pfcv_schedule_cron_on_activation() {
		// Schedule an action if it's not already scheduled.
		if ( ! wp_next_scheduled( 'pfcv_run_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'pfcv_run_daily' );
		}
	}

	/**
	 * Clear the scheduled cron event on plugin deactivation.
	 */
	public static function pfcv_clear_cron_schedule() {
		wp_clear_scheduled_hook( 'pfcv_run_daily' );
		self::pfcv_unlink_extract_folder();
	}

	/**
	 * Hook into the action that fires daily.
	 */
	public static function pfcv_daily_event_func() {
		$extractfolder = CPLV_CURRENT_PLUGIN_DIR . 'extract';

		$currentdate = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$created_folder_date = new DateTime( '@' . filemtime( $extractfolder ) );
		$dtdiff = $currentdate->diff( $created_folder_date );
		$getday = $dtdiff->format( '%a' );

		if ( $getday > 0 ) {
			self::pfcv_unlink_extract_folder();
		}
	}

	/**
	 * Unlink the extract folder.
	 */
	public static function pfcv_unlink_extract_folder() {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		$extractfolder = CPLV_CURRENT_PLUGIN_DIR . 'extract';

		if ( $wp_filesystem->is_dir( $extractfolder ) ) {
			$wp_filesystem->delete( $extractfolder, true );
		}
	}
}

// Initialize the class.
new PluginFilesComparison_Admin();
