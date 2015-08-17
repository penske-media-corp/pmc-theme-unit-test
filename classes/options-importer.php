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
	public function instant_options_import( $options_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		// Allow others to be able to exclude their options from exporting
		$blacklist = apply_filters( 'options_export_blacklist', array() );

		foreach ( $options_json as $option_name => $option_value ) {

			try {

				if ( in_array( $option_name, $blacklist ) ) {
					continue;
				}

				$option_exists = get_option( $option_name );

				if ( empty( $option_exists ) ) {

					$option_value = maybe_unserialize( $option_value['value'] );

					update_option( $option_name, $option_value );

					error_log( $time . $option_name . ' -- IMPORTED with value ' . json_enocde( $option_value ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				}
			} catch ( \Exception $ex ) {

				error_log( $time . ' -- ' . $ex->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
				continue;
			}

		}

	}
}
