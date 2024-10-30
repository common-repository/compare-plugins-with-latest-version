<?php
/**
 * Plugin Name: Plugin Files Comparison
 * Description: A plugin to compare plugin files with the latest version.
 * Version: 1.0.0
 * Author: Your Name
 *
 * @package PluginFilesComparison
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the formatted file list for the plugin editor.
 *
 * @param array|string $tree  List of file/folder paths, or filename.
 * @param string       $label Name of file or folder to print.
 * @param int          $level The aria-level for the current iteration.
 * @param int          $size  The aria-setsize for the current iteration.
 * @param int          $index The aria-posinset for the current iteration.
 */
function pfcv_print_plugin_file_tree( $tree, $label = '', $level = 2, $size = 1, $index = 1 ) {
	$file = ! empty( $_GET['pfcvfile'] ) ? sanitize_text_field( wp_unslash( $_GET['pfcvfile'] ) ) : '';
	$folder_explode = explode( '/', $file );

	if ( is_array( $tree ) ) {
		$index = 0;
		$size = count( $tree );
		foreach ( $tree as $label => $plugin_file ) :
			$index++;
			if ( ! is_array( $plugin_file ) ) {
				pfcv_print_plugin_file_tree( $plugin_file, $label, $level, $index, $size );
				continue;
			}
			?>
			<li class="nav-item" role="treeitem" aria-expanded="true" tabindex="-1"
				aria-level="<?php echo esc_attr( $level ); ?>"
				aria-setsize="<?php echo esc_attr( $size ); ?>"
				aria-posinset="<?php echo esc_attr( $index ); ?>">
				<a class="collapsed nav-link py-1" href="#<?php echo esc_attr( $label ); ?>" data-toggle="collapse"
				   data-target="#<?php echo esc_attr( $label ); ?>" aria-expanded="true">
					<span class="folder-label"><?php echo esc_html( $label ); ?> <span class="screen-reader-text"></span>
						<span aria-hidden="true" class="icon"></span>
					</span>
				</a>
				<div class="collapse <?php echo in_array( $label, $folder_explode ) ? 'show' : ''; ?> <?php echo in_array( $file, $plugin_file ) ? 'show' : ''; ?>"
					 id="<?php echo esc_attr( $label ); ?>" aria-expanded="false">
					<ul role="group" class="tree-folder flex-column nav pl-4">
						<?php pfcv_print_plugin_file_tree( $plugin_file, '', $level + 1, $index, $size ); ?>
					</ul>
				</div>
			</li>
			<?php
		endforeach;
	} else {
		$pfcv_file_nonce = wp_create_nonce( $tree );
		$plugin_folder = ! empty( $_GET['pfcvplugin'] ) ? sanitize_text_field( wp_unslash( $_GET['pfcvplugin'] ) ) : '';
		$url = add_query_arg(
			array(
				'_pfcvview' => 'view',
				'pfcvnonce' => $pfcv_file_nonce,
				'pfcvfile' => rawurlencode( $tree ),
				'pfcvplugin' => rawurlencode( $plugin_folder ),
			),
			site_url( '/' )
		);

		?>
		<li role="none" class="nav-item <?php echo esc_attr( $file === $tree ? 'current-file' : '' ); ?>">
			<a class="collapsed" role="treeitem" tabindex="<?php echo esc_attr( $file === $tree ? '0' : '-1' ); ?>"
			   href="<?php echo esc_url( $url ); ?>"
			   aria-level="<?php echo esc_attr( $level ); ?>"
			   aria-setsize="<?php echo esc_attr( $size ); ?>"
			   aria-posinset="<?php echo esc_attr( $index ); ?>">
				<?php
				if ( $file === $tree ) {
					echo '<span class="notice notice-info">' . esc_html( $label ) . '</span>';
				} else {
					echo esc_html( $label );
				}
				?>
			</a>
		</li>
		<?php
	}
}

/**
 * Get list of file extensions that are editable in plugins.
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 * @return string[] Array of editable file extensions.
 */
function pfcv_get_plugin_file_editable_extensions( $plugin ) {
	$editable_extensions = array(
		'css',
		'htm',
		'html',
		'inc',
		'include',
		'js',
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'phps',
		'text',
		'txt',
	);

	/**
	 * Filters file type extensions editable in the plugin editor.
	 *
	 * @param string[] $editable_extensions An array of editable plugin file extensions.
	 * @param string   $plugin              Path to the plugin file relative to the plugins directory.
	 */
	$editable_extensions = (array) apply_filters( 'pfcv_editable_extensions', $editable_extensions, $plugin );

	return $editable_extensions;
}

/**
 * Get total number of file count.
 *
 * @param string $path Path to the plugin file relative to the plugins directory.
 * @return int Total count of editable files.
 */
function pfcv_get_totalnumber_of_file( $path ) {
	$pfcv_plugin = wp_unslash( sanitize_text_field( $path ) );
	$pfcv_get_plugins = get_plugin_files( $pfcv_plugin );
	$editable_extensions = pfcv_get_plugin_file_editable_extensions( $pfcv_plugin );
	$plugin_editable_files = array();

	foreach ( $pfcv_get_plugins as $plugin_file ) {
		if ( preg_match( '/\.([^.]+)$/', $plugin_file, $matches ) && in_array( $matches[1], $editable_extensions ) ) {
			$plugin_editable_files[] = $plugin_file;
		}
	}

	return count( $plugin_editable_files );
}
