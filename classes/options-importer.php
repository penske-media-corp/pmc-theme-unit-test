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

		return true;

	}


	private function _save_option( $option_name, $option_value, $no_autoload ) {

		$time = date( '[d/M/Y:H:i:s]' );

		if ( in_array( $option_name, $no_autoload ) ) {

			delete_option( $option_name );

			$option_value = maybe_unserialize( $option_value );

			$option_added = add_option( $option_name, $option_value, '', 'no' );

			error_log( $time . $option_name . ' - IMPORTED with value ' . json_encode( $option_value ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

		} else {

			$option_value = maybe_unserialize( $option_value );

			$option_added = update_option( $option_name, $option_value, true );

			error_log( $time . $option_name . ' - IMPORTED with value ' . json_encode( $option_value ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

		}

		return $option_added;
	}

}
