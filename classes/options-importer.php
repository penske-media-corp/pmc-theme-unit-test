<?php
namespace PMC\Theme_Unit_Test;

class Options_Importer extends PMC_Singleton {

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 *
	 * @version 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		$saved_options = $this->instant_options_import( $api_data );

		return $saved_options;

	}

	/**
	 * The main controller for the actual import stage.
	 *
	 * @since 2015-07-20
	 *
	 * @version 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decoded array of Options object
	 *
	 * @return array of Options on success.
	 */
	public function instant_options_import( $options_data ) {

		$time = date( '[d/M/Y:H:i:s]' );

		if ( empty( $options_data ) || ! is_array( $options_data ) ) {
			return false;
		}

		// Allow others to be able to exclude their options from exporting
		$blacklist = apply_filters( 'options_export_blacklist', array() );

		if ( empty( $options_data['no_autoload'] ) ) {

			$options_data['no_autoload'] = array(
				'moderation_keys',
				'recently_edited',
				'blacklist_keys',
				'uninstall_plugins',
			);
		}

		error_log( $time . json_encode( $options_data ) . ' - OPTIONS IMPORT ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		foreach ( $options_data['options'] as $option_name => $option_value ) {

			error_log( $time . json_encode( $option_name ) . ' - START IMPORT ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			try {

				if ( in_array( $option_name, $blacklist ) ) {
					continue;
				}

				$saved = $this->_save_option( $option_name, $option_value, $options_data['no_autoload'] );

			} catch ( \Exception $ex ) {

				error_log( $time . ' - ' . $ex > get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
				continue;
			}

		}
	}

	/**
	 * Function to add or update option
	 *
	 * @since 2015-09-09
	 *
	 * @version 2015-09-09 Archana Mandhare - PPT-5077
	 *
	 * @param string $option_name, string $option_value and $array $no_autoload
	 *
	 * @return bool status if option was added
	 */
	private function _save_option( $option_name, $option_value, $no_autoload ) {

		$time = date( '[d/M/Y:H:i:s]' );

		//replace all the live domains with the local domain path
		$domain   = get_option( Config::api_domain );
		$home_url = get_home_url();
		$option_value = maybe_unserialize( $option_value );
		$option_value = $this->_recursive_array_replace( 'http://' . $domain, $home_url, $option_value );

		if ( in_array( $option_name, $no_autoload ) ) {

			delete_option( $option_name );

			$option_added = add_option( $option_name, $option_value, '', 'no' );

			error_log( $time . $option_name . ' - IMPORTED with value ' . json_encode( $option_value ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

		} else {

			$option_added = update_option( $option_name, $option_value, true );

			error_log( $time . $option_name . ' - IMPORTED with value ' . json_encode( $option_value ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

		}

		return $option_added;
	}

	/**
	 * Function to find and replace value in a array recursively
	 *
	 * @since 2015-09-09
	 *
	 * @version 2015-09-09 Archana Mandhare - PPT-5077
	 *
	 * @param string $find, string $replace and array $array
	 *
	 * @return string/array
	 */
	private function _recursive_array_replace( $find, $replace, $array ) {
		if ( ! is_array( $array ) ) {
			return str_replace( $find, $replace, $array );
		}
		$new_array = array();
		foreach ( $array as $key => $value ) {
			$new_array[ $key ] = $this->_recursive_array_replace( $find, $replace, $value );
		}

		return $new_array;
	}
}
