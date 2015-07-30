<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Options_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks required to create options importer
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	protected function _setup_hooks() {

		add_filter( 'options_import_blacklist', array( $this, 'filter_pmc_theme_ut_blacklisted_options' ) );
		add_filter( 'options_import_whitelist', array( $this, 'filter_pmc_theme_ut_whitelisted_options' ) );
	}

	/**
	 * Filter to blacklist the options that should not be imported
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	public function filter_pmc_theme_ut_blacklisted_options( $blacklisted = array() ) {
		return $blacklisted;
	}

	/**
	 * Filter to whitelist the options that should be imported
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	public function filter_pmc_theme_ut_whitelisted_options( $whitelisted = array() ) {
		return $whitelisted;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data, $domain = ''  ) {


		$saved_options = $this->instant_options_import( $api_data );


		return $saved_options;


	}

	/**
	 * The main controller for the actual import stage.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Options object
	 *
	 * @return array of Options on success.
	 */
	public function instant_options_import( $options_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$options_data = json_decode( $options_json, true );

			$options_to_import = array_keys( $options_data["options"] );

			$whitelist_options_to_import = apply_filters( 'options_import_whitelist', array() );

			$options_to_import = array_unique( array_merge( $options_to_import, $whitelist_options_to_import ) );

			$override = false;

			$hash = '048f8580e913efe41ca7d402cc51e848';

			// Allow others to prevent their options from importing
			$blacklist = apply_filters( 'options_import_blacklist', array() );

			foreach ( $options_data["options"] as $option_name => $option_value ) {

				if ( isset( $option_value ) ) {

					// If option is blacklisted OR not part of the desired whitelist values skip the import for that option
					if ( in_array( $option_name, $blacklist ) || ! in_array( $option_name, $options_to_import ) ) {

						$error = "\n<p>" . sprintf( __( 'Skipped option `%s` because a plugin or theme does not allow it to be imported.' ), esc_html( $option_name ) ) . '</p>';
						error_log( $time . " -- " . $error . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
						continue;

					}

					// As an absolute last resort for security purposes, allow an installation to define a regular expression
					// blacklist. For instance, if you run a multsite installation, you could add in an mu-plugin:
					// 		define( 'WP_OPTION_IMPORT_BLACKLIST_REGEX', '/^(home|siteurl)$/' );
					// to ensure that none of your sites could change their own url using this tool.
					if ( defined( 'WP_OPTION_IMPORT_BLACKLIST_REGEX' ) && preg_match( WP_OPTION_IMPORT_BLACKLIST_REGEX, $option_name ) ) {

						$error = "\n<p>" . sprintf( __( 'Skipped option `%s` because this WordPress installation does not allow it.' ), esc_html( $option_name ) ) . '</p>';
						error_log( $time . " -- " . $error . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
						continue;
					}

					if ( ! $override ) {
						// we're going to use a random hash as our default, to know if something is set or not
						$old_value = get_option( $option_name, $hash );

						// only import the setting if it's not present
						if ( $old_value !== $hash ) {

							$error = "\n<p>" . sprintf( __( 'Skipped option `%s` because it currently exists.' ), esc_html( $option_name ) ) . '</p>';
							error_log( $time . " -- " . $error . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
							continue;
						}
					}

					$option_value = maybe_unserialize( $option_value );
					if ( in_array( $option_name, $options_data['no_autoload'] ) ) {
						delete_option( $option_name );
						add_option( $option_name, $option_value, '', 'no' );
					} else {
						update_option( $option_name, $option_value );
					}
				}

			}

		} catch ( \Exception $ex ) {

			error_log( $time . " -- " . $ex->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}
	}
}
