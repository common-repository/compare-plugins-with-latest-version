<?php
/**
 * Plugin Compare Screen Template
 *
 * @package PluginName
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CPLV_VERSION' ) ) {
	exit;
}

?>
<!doctype html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	   <?php
		wp_enqueue_style( 'bootstrap-min-css', self::pfcv_retry_plugins_path( 'assets/css/bootstrap.min.css' ), array(), CPLV_VERSION, 'all' );
		wp_enqueue_style( 'load-compare-css', self::pfcv_retry_plugins_path( 'assets/css/load-compare-screen.css' ), array(), CPLV_VERSION, 'all' );
		wp_enqueue_style( 'bv-main-css', self::pfcv_retry_plugins_path( 'assets/css/all.css' ), array(), CPLV_VERSION, 'all' );
		wp_enqueue_script( 'bootstrap-min-js', self::pfcv_retry_plugins_path( 'assets/js/bootstrap.min.js' ), array( 'jquery' ), CPLV_VERSION, true );
		wp_enqueue_script( 'compare-screen-js', self::pfcv_retry_plugins_path( 'assets/js/compare-screen.js' ), array( 'jquery' ), CPLV_VERSION, true );

		wp_head();
		?>
	</head>
	<body>
		<?php
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( "You can't access this page", 'text-domain' ) );
		}

		$pfcv_absp_path = ABSPATH;
		require_once( $pfcv_absp_path . 'wp-admin/includes/plugin.php' );
		require_once( $pfcv_absp_path . 'wp-admin/includes/file.php' );
		require_once( $pfcv_absp_path . 'wp-admin/includes/misc.php' );
		require_once( CPLV_CURRENT_PLUGIN_DIR . 'cplv-load-common-function.php' );
		require_once( CPLV_CURRENT_PLUGIN_DIR . 'assets/lib/class-pfcv-diff.php' );

		$pfcv_plugin = '';
		if ( isset( $_GET['pfcvplugin'] ) && ! empty( sanitize_text_field( wp_unslash( $_GET['pfcvplugin'] ) ) ) ) {
			$pfcv_plugin = sanitize_text_field( wp_unslash( $_GET['pfcvplugin'] ) );
		}

		$pfcv_files = isset( $_GET['pfcvfile'] ) ? sanitize_text_field( wp_unslash( $_GET['pfcvfile'] ) ) : '';
		$pfcv_get_plugins = get_plugin_files( $pfcv_plugin );

		$editable_extensions = pfcv_get_plugin_file_editable_extensions( $pfcv_plugin );
		$plugin_editable_files = array();
		foreach ( $pfcv_get_plugins as $plugin_file ) {
			if ( preg_match( '/\.([^.]+)$/', $plugin_file, $matches ) && in_array( $matches[1], $editable_extensions ) ) {
				$plugin_editable_files[] = $plugin_file;
			}
		}
		if ( ! empty( $plugin_editable_files ) ) {
			?>

			<div class="header">
				<a href="https://www.brainvire.com/" target="_blank"><img src="<?php echo esc_url( CPLV_PLUGIN_URL . 'assets/images/bv_logo.png' ); ?>" alt="logo"></a>
				<a class="modal-btn" data-toggle="modal" data-target="#myModal" title="File Information"><i class="fa fa-info-circle"></i></a>
			</div>

			<div id="container-fluid">
				<div class="row">
					<div class="col-2 collapse show d-md-flex bg-light min-vh-100" id="sidebar">    
						<ul class="nav flex-column flex-nowrap" role="tree" aria-labelledby="plugin-files-label">
							<li class="nav-item" role="treeitem" tabindex="-1" aria-expanded="true" aria-level="1" aria-posinset="1" aria-setsize="1">
								<ul role="group">
									<?php pfcv_print_plugin_file_tree( wp_make_plugin_file_tree( $plugin_editable_files ) ); ?>
								</ul>
						</ul>
					</div>

					<div id="comparescreen" class="col-lg-10 px-0">
						<?php
						$extractfolder = CPLV_CURRENT_PLUGIN_DIR . 'extract';
						$latestfilearr = $extractfolder . '/' . $pfcv_files;
						$currentfilearr = CPLV_PLUGIN_DIR . $pfcv_files;
						$nofilefound = CPLV_CURRENT_PLUGIN_DIR . 'cplv-file-not-found.txt';
						$getfile_m_time = @filemtime( $currentfilearr );
						$getfile_m_time = gmdate( 'l jS \of F Y h:i:s A', $getfile_m_time );

						if ( ! is_dir( $extractfolder ) ) {
							?>
							<div class="alert alert-danger alert-dismissible">
								<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">x</a>
								<strong><?php esc_html_e( 'Error:', 'text-domain' ); ?></strong> <?php echo esc_html__( 'Unable to create directory ', 'text-domain' ) . esc_html( str_replace( '\\', '/', $extractfolder ) ); ?>
							</div> 
							<?php
						}

						if ( file_exists( $currentfilearr ) ) {
							$get_currentfilearr = $currentfilearr;
						} else {
							$get_currentfilearr = $nofilefound;
						}

						if ( file_exists( $latestfilearr ) ) {
							$get_latestfilearr = $latestfilearr;
						} else {
							$get_latestfilearr = $nofilefound;
						}

						echo wp_kses_post( Pfcv_Diff::to_table( Pfcv_Diff::compare_files( $get_currentfilearr, $get_latestfilearr ) ) );
						?>
					</div>
				</div>
			</div>    
			<?php
		} else {
			wp_die( esc_html__( 'Sorry, that file cannot be edited.', 'text-domain' ) );
		}
		?>

		<div class="modal fade" id="myModal" role="dialog">
			<div class="modal-dialog modal-lg">

				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<div class="list">
							<?php
							echo "<h4 class='change file'>" . esc_html__( 'Last Changed:', 'text-domain' ) . ' ' . esc_html( $getfile_m_time ) . '</h4>';
							echo "<h4 class='file_name file'>" . esc_html__( 'File Name:', 'text-domain' ) . ' ' . esc_html( str_replace( '\\', '/', CPLV_PLUGIN_DIR . $pfcv_files ) ) . '</h4>';
							echo "<span class='file notecss'><strong>" . esc_html__( 'Note:', 'text-domain' ) . '</strong> ' . esc_html__( 'We have considered only this', 'text-domain' ) . ' (' . esc_html( implode( ', ', pfcv_get_plugin_file_editable_extensions( $pfcv_plugin ) ) ) . ') ' . esc_html__( 'extension of files', 'text-domain' ) . '</span>';
							?>
						</div>
					</div>
				</div>

			</div>
		</div>
	</body>
	<?php wp_footer(); ?>
</html>
