<?php
/**
 * Autoloader for PHP classes inside PMC Plugins
 *
 * @author Amit Gupta <agupta@pmc.com>
 * @since 2015-05-12
 */


namespace PMC\Theme_Unit_Test;


class Autoloader {

	public static function load_resource( $resource = '' ) {
		$namespace_root = 'PMC\\';

		$resource = trim( $resource, '\\' );

		if ( empty( $resource ) || strpos( $resource, '\\' ) === false || strpos( $resource, $namespace_root ) !== 0 ) {
			//not our namespace, bail out
			return;
		}

		$path = explode(
					'\\',
					str_replace( '_', '-', $resource )
				);

		$plugin_name = untrailingslashit(
							strtolower(
									implode(
											'-',
											array_slice( $path, 0, 2 )
									)
							)
						);

		$class_path = strtolower(
							implode(
									'/',
									array_slice( $path, 2 )
							)
						);

		$resource_path = sprintf( '%s/%s/classes/%s.php', untrailingslashit( dirname( PMC_GLOBAL_FUNCTIONS_PATH ) ), $plugin_name, $class_path );

		if ( file_exists( $resource_path ) ) {
			require_once $resource_path;
		}
	}

}


/**
 * Register autoloader
 */
spl_autoload_register( __NAMESPACE__ . '\Autoloader::load_resource' );


//EOF
