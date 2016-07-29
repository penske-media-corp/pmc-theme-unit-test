<?php
namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\PMC_Singleton;
use PMC\Theme_Unit_Test\Settings\Config as Config;
use PMC\Theme_Unit_Test\Logger\Status;

class Options extends PMC_Singleton {

	const LOG_NAME = 'options';

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 */
	public function call_import_route( $api_data ) {
		$saved_options = $this->instant_options_import( $api_data );
		return $saved_options;
	}

	/**
	 * The main controller for the actual import stage.
	 *
	 * @since 2015-07-20
	 * @version 2015-07-20 Archana Mandhare PPT-5077
	 *
	 * @param array json_decoded array of Options object
	 * @return array of Options on success.
	 */
	public function instant_options_import( $options_data ) {

		$status = Status::get_instance();

		$options_log_data = array(
			'option_name'   => 0,
			'option_value'  => 0,
			'error_message' => '',
		);

		if ( empty( $options_data ) || ! is_array( $options_data ) ) {
			$options_log_data['error_message'] = 'NO OPTIONS TO IMPORT';
			$status->save_current_log( self::LOG_NAME, array( 0 => $options_log_data ) );
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

		foreach ( $options_data['options'] as $option_name => $option_value ) {

			try {

				if ( in_array( $option_name, $blacklist ) ) {
					continue;
				}

				$saved = $this->_save_option( $option_name, $option_value, $options_data['no_autoload'] );

				$options_log_data = array(
					'option_name'   => json_encode( $option_name ),
					'option_value'  => $option_value,
					'error_message' => '',
				);

				$status->save_current_log( self::LOG_NAME, array( json_encode( $option_name ) => $options_log_data ) );

			} catch ( \Exception $ex ) {

				$options_log_data['error_message'] = $ex->getMessage();
				$status->save_current_log( self::LOG_NAME, array( 0 => $options_log_data ) );
				continue;

			}
		}
	}

	/**
	 * Function to add or update option
	 *
	 * @since 2015-09-09
	 * @version 2015-09-09 Archana Mandhare PPT-5077
	 *
	 * @param string $option_name, string $option_value and $array $no_autoload
	 * @return bool status if option was added
	 */
	private function _save_option( $option_name, $option_value, $no_autoload ) {

		$status = Status::get_instance();

		//replace all the live domains with the local domain path
		$domain   = get_option( Config::api_domain );
		$home_url = get_home_url();
		$option_value = maybe_unserialize( $option_value );
		$option_value = $this->_recursive_array_replace( 'http://' . $domain, $home_url, $option_value );

		$options_log_data = array(
			'option_name'   => $option_name,
			'option_value'  => json_encode( $option_value ),
			'error_message' => '',
		);

		if ( in_array( $option_name, $no_autoload ) ) {
			delete_option( $option_name );
			$option_added = add_option( $option_name, $option_value, '', 'no' );
		} else {
			$option_added = update_option( $option_name, $option_value, true );
		}

		$status->save_current_log( self::LOG_NAME, array( $option_name => $options_log_data ) );

		return $option_added;
	}

	/**
	 * Function to find and replace value in a array recursively
	 *
	 * @since 2015-09-09
	 * @version 2015-09-09 Archana Mandhare PPT-5077
	 *
	 * @param string $find, string $replace and array $array
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
