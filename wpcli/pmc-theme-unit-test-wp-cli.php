<?php
/**
 * PMC_Theme_Unit_Test_WP_Cli CLI script : Fetch data from wp-ci
 * @since 2015-09-01
 * @version 2015-09-01 Archana Mandhare PPT-5366
 */

use PMC\Theme_Unit_Test;

WP_CLI::add_command( 'pmc-import-live', 'PMC_Theme_Unit_Test_WP_Cli' );

class PMC_Theme_Unit_Test_WP_Cli extends WP_CLI_Command {

	public $dry_run = false;
	public $auth_file = '';

	public function __construct( $args = array(), $assoc_args = array() ) {
		$this->_extract_common_args( $assoc_args );
	}

	protected function _extract_common_args( $assoc_args ) {
		if ( empty( $assoc_args ) ) {
			return false;
		}

		if ( empty( $assoc_args ) ) {
			return;
		}

		$this->dry_run = ! empty( $assoc_args['dry-run'] );

		if ( ! empty( $assoc_args['file'] ) ) {
			$this->auth_file = $assoc_args['file'];
		}

	}

	/**
	 * Import data from production server for a given local theme and URL.
	 * Pass in the json file that has the credentials information if credentials are not yet in the database
	 * The format of the file should be same as auth.json file added to the root of this plugin
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 * @subcommand import-all
	 * @synopsis   [--dry-run] [--file=<file>]
	 *
	 *
	 * Example usage :
	 * wp --url=vip.local pmc-import-live import-all
	 * wp --url=vip.local pmc-import-live import-all --file=/path/to/auth.json
	 */
	public function import_all( $args = array(), $assoc_args = array() ) {

		WP_CLI::line( 'Starting...' );

		$this->_extract_common_args( $assoc_args );

		$has_credentials = $this->_get_credentials_from_db();
		if ( ! $has_credentials && ! empty( $this->auth_file ) ) {
			$has_credentials = $this->_validate_credentials( $this->auth_file );
		}

		if ( ! $has_credentials ) {
			return false;
		}

		$this->_import_rest_routes();

		$this->_import_post_routes();

		$this->_import_xmlrpc_routes();

	}

	/**
	 * Import specific route data from production server for a given theme and URL.
	 * Pass in the json file that has the credentials information if credentials are not yet in the database
	 * The format of the file should be same as auth.json file added to the root of this plugin
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 * @subcommand import-routes
	 * @synopsis   [--dry-run] [--file=<file>] [--routes=<routes>]  [--post-type=<post-type>]  [--xmlrpc=<xmlrpc>]
	 *
	 *
	 * Example usage :
	 * wp --url=vip.local pmc-import-live import-routes --dry-run --file=/path/to/auth.json --routes=users,menus --post-type=post,pmc-gallery,page --xmlrpc=taxonomies,options
	 * wp --url=vip.local pmc-import-live import-routes --routes=users
	 *
	 * possible values for --routes param should be endpoint keys from the $all_routes in Config.php
	 * possible values for --post_type param is all the whitelisted post types for the rest api
	 * possible values for --xmlrpc should be from $xmlrpc_routes array in Config.php
	 */
	public function import_routes( $args = array(), $assoc_args = array() ) {

		WP_CLI::line( 'Starting Import...' );

		$this->_extract_common_args( $assoc_args );

		$has_credentials = $this->_get_credentials_from_db();

		if ( ! $has_credentials && ! empty( $assoc_args['file'] ) ) {
			$has_credentials = $this->_validate_credentials( $assoc_args['file'] );
		}

		if ( ! $has_credentials ) {
			return false;
		}

		// REST API Endpoints - users, menu, tags, categories etc
		if ( ! empty( $assoc_args['routes'] ) ) {
			$rest_endpoints = $assoc_args['routes'];
			$this->_import_rest_routes( $rest_endpoints );
		}

		// REST API Post and custom post type Endpoints
		if ( ! empty( $assoc_args['post-type'] ) ) {
			$post_endpoints = $assoc_args['post-type'];
			$this->_import_post_routes( $post_endpoints );
		}

		// REST API xmlrpc Endpoints - for custom taxonomies and options
		if ( ! empty( $assoc_args['xmlrpc'] ) ) {
			$post_endpoints = $assoc_args['xmlrpc'];
			$this->_import_xmlrpc_routes( $post_endpoints );
		}

	}

	/**
	 * Check that the credentials are saved in the database and return true Else return false
	 *
	 * @since 2015-09-02
	 * @version 2015-09-02 Archana Mandhare PPT-5366
	 *
	 */
	private function _get_credentials_from_db() {

		// Check the saved values from DB for REST API and XMLRPC
		$rest_auth    = false;
		$access_token = get_option( PMC\Theme_Unit_Test\Config::access_token_key );
		$domain       = get_option( PMC\Theme_Unit_Test\Config::api_domain );
		if ( ! empty( $access_token ) && ! empty( $domain ) && PMC\Theme_Unit_Test\REST_API_oAuth::get_instance()->is_valid_token() ) {
			$rest_auth = true;
		}

		$xlmrpc_auth     = false;
		$xmlrpc_username = get_option( PMC\Theme_Unit_Test\Config::api_xmlrpc_username );
		$xmlrpc_password = get_option( PMC\Theme_Unit_Test\Config::api_xmlrpc_username );
		if ( ! empty( $xmlrpc_username ) && ! empty( $xmlrpc_password ) ) {
			$xlmrpc_auth = true;
		}

		// if both are saved return true
		if ( $rest_auth && $xlmrpc_auth ) {
			WP_CLI::line( 'Authentication SUCCESSFUL with saved access token in DB !!' );

			return true;
		}

		return false;

	}

	/**
	 * Validates the user credentials from a file and saves to the database
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 */
	private function _validate_credentials( $credentials_file = '' ) {

		// Else look for the file that has credentials
		WP_CLI::line( 'Credentials File = ' . $credentials_file );
		if ( ! empty( $credentials_file ) ) {

			if ( ! file_exists( $credentials_file ) ) {
				WP_CLI::error( "Credentials file '$credentials_file' does not exists. Please create one." );

				return false;
			}
			if ( ! is_readable( $credentials_file ) ) {
				WP_CLI::error( "Unable to read credentials from file '$credentials_file'." );

				return false;
			}
		}

		// Read the file and fetch the access token and save to DB.
		try {
			$creds_details = PMC\Theme_Unit_Test\Admin::get_instance()->read_credentials_from_json_file( $credentials_file );

			update_option( PMC\Theme_Unit_Test\Config::api_xmlrpc_username, $creds_details['xmlrpc_username'] );

			update_option( PMC\Theme_Unit_Test\Config::api_xmlrpc_password, $creds_details['xmlrpc_password'] );

			update_option( PMC\Theme_Unit_Test\Config::api_domain, $creds_details['domain'] );

			update_option( PMC\Theme_Unit_Test\Config::api_client_id, $creds_details['client_id'] );

			update_option( PMC\Theme_Unit_Test\Config::api_client_secret, $creds_details['client_secret'] );

			update_option( PMC\Theme_Unit_Test\Config::api_redirect_uri, $creds_details['redirect_uri'] );

			if ( ! is_array( $creds_details ) || empty( $creds_details['client_id'] ) || empty( $creds_details['redirect_uri'] ) ) {
				WP_CLI::error( 'Authentication Failed. Some entries were missing. Please add all authentication details to the file ' . sanitize_title_with_dashes( $credentials_file ) );

				return false;
			}

			$args = array(
				'response_type' => 'code',
				'scope'         => 'global',
				'client_id'     => $creds_details['client_id'],
				'redirect_uri'  => $creds_details['redirect_uri'],
			);

			$query_params  = http_build_query( $args );
			$authorize_url = PMC\Theme_Unit_Test\Config::AUTHORIZE_URL . '?' . $query_params;

			WP_CLI::line( sprintf( 'Open in your browser: %s', $authorize_url ) );
			echo 'Enter the verification code: ';
			$code = sanitize_text_field( wp_unslash( trim( fgets( STDIN ) ) ) );

			$authenticated = PMC\Theme_Unit_Test\REST_API_oAuth::get_instance()->fetch_access_token( $code );

			if ( $authenticated ) {
				WP_CLI::line( 'Authentication SUCCESSFUL !!' );
			} else {
				WP_CLI::line( 'Authentication FAILED !!' );
			}

			return $authenticated;
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

	}

	/**
	 * Import data from all the REST API routes other than posts and post types
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 */
	private function _import_rest_routes( $endpoint = '' ) {

		$supported_routes = PMC\Theme_Unit_Test\Config_Helper::get_all_routes();
		$bad_endpoint     = false;

		if ( empty( $endpoint ) || 'all' === $endpoint ) {
			$endpoint = $supported_routes;
		} else {
			$endpoint = explode( ',', str_replace( ' ', '', $endpoint ) );
		}

		foreach ( $endpoint as $entity ) {
			if ( in_array( $entity, $supported_routes ) ) {
				if ( ! $this->dry_run ) {
					try {
						WP_CLI::line( 'Starting ' . $entity . ' Import...' );
						$saved_data[] = PMC\Theme_Unit_Test\REST_API_Router::get_instance()->call_rest_api_all_route( $entity );
						if ( is_wp_error( $saved_data ) ) {
							WP_CLI::warning( 'Tags Import failed with error: ' . sanitize_title_with_dashes( $saved_data ) );
						}
						WP_CLI::line( 'Done ' . $entity . ' Import...' );
					} catch ( Exception $e ) {
						WP_CLI::error( $e->getMessage() );
					}
				}
			} else {
				$bad_endpoint = true;
			}

			if ( $bad_endpoint ) {
				WP_CLI::warning( 'Invalid REST endpoint: ' . sanitize_title_with_dashes( $entity ) );
			}
		}
	}

	/**
	 * Import data from all post and post type REST API routes
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 */
	private function _import_post_routes( $post_endpoints = '' ) {

		$supported_posts = PMC\Theme_Unit_Test\Config_Helper::get_posts_routes();
		$bad_endpoint    = false;

		if ( empty( $post_endpoints ) || 'all' === $post_endpoints ) {
			$post_endpoints = $supported_posts;
		} else {
			$post_endpoints = explode( ',', str_replace( ' ', '', $post_endpoints ) );
		}

		foreach ( $post_endpoints as $entity ) {
			if ( post_type_exists( $entity ) && in_array( $entity, $supported_posts ) ) {
				if ( ! $this->dry_run ) {
					try {
						WP_CLI::line( 'Starting ' . $entity . ' Import...' );
						$saved_data[] = PMC\Theme_Unit_Test\REST_API_Router::get_instance()->call_rest_api_posts_route( $entity );
						if ( is_wp_error( $saved_data ) ) {
							$bad_endpoint = true;
						}
						WP_CLI::line( 'Done ' . $entity . ' Import...' );
					} catch ( Exception $e ) {
						WP_CLI::error( $e->getMessage() );
					}
				} else {
					$bad_endpoint = true;
				}

				if ( $bad_endpoint ) {
					WP_CLI::warning( 'Invalid post endpoint: ' . sanitize_title_with_dashes( $entity ) );
				}
			}
		}
	}

	/**
	 * Import data from XMLRPC routes
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare PPT-5366
	 *
	 */
	private function _import_xmlrpc_routes( $xmlrpc_endpoints = '' ) {

		$supported_xmlrpc_routes = PMC\Theme_Unit_Test\Config_Helper::get_xmlrpc_routes();
		$bad_endpoint            = false;

		if ( empty( $xmlrpc_endpoints ) || 'all' === $xmlrpc_endpoints ) {
			$xmlrpc_endpoints = $supported_xmlrpc_routes;
		} else {
			$xmlrpc_endpoints = explode( ',', str_replace( ' ', '', $xmlrpc_endpoints ) );
		}

		foreach ( $xmlrpc_endpoints as $entity ) {
			if ( in_array( $entity, $supported_xmlrpc_routes ) ) {
				if ( ! $this->dry_run ) {
					try {
						WP_CLI::line( 'Starting ' . $entity . ' Import...' );
						$saved_data[] = PMC\Theme_Unit_Test\XMLRPC_Router::get_instance()->call_xmlrpc_api_route( $entity );
						if ( is_wp_error( $saved_data ) ) {
							$bad_endpoint = true;
						}
						WP_CLI::line( 'Done ' . $entity . ' Import...' );
					} catch ( Exception $e ) {
						WP_CLI::error( $e->getMessage() );
					}
				}
			} else {
				$bad_endpoint = true;
			}

			if ( $bad_endpoint ) {
				WP_CLI::warning( 'Invalid xmlrpc endpoint: ' . sanitize_title_with_dashes( $entity ) );
			}
		}
	}
}
