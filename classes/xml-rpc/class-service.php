<?php

namespace PMC\Theme_Unit_Test\XML_RPC;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Settings\Config;
use PMC\Theme_Unit_Test\Importer\Terms;
use PMC\Theme_Unit_Test\Importer\Options;
use PMC\Theme_Unit_Test\Logger\Status;

class Service {

	use Singleton;

	public $xmlrpc_client;

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 2015-07-21
	 *
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 *
	 */
	protected function __construct() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks and filters required for xmlrpc
	 *
	 * @since 2015-07-22
	 *
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 */
	protected function _setup_hooks() {
		add_filter( 'pmc_xmlrpc_client_credentials', array( $this, 'filter_pmc_xmlrpc_client_credentials' ) );
	}

	/**
	 * Filter that returns the credentials for xmlrpc client call
	 *
	 * @since 2015-07-22
	 *
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $xmlrpc_args array
	 *
	 * @return array containing the credentials
	 *
	 */
	public function filter_pmc_xmlrpc_client_credentials( $xmlrpc_args ) {

		$domain          = get_option( Config::API_DOMAIN );
		$xmlrpc_username = get_option( Config::API_XMLRPC_USERNAME );
		$xmlrpc_password = get_option( Config::API_XMLRPC_PASSWORD );

		if ( empty( $domain ) || empty( $xmlrpc_username ) || empty( $xmlrpc_password ) ) {
			return $xmlrpc_args;
		}

		$xmlrpc_args['server']   = "http://{$domain}/xmlrpc.php";
		$xmlrpc_args['username'] = $xmlrpc_username;
		$xmlrpc_args['password'] = $xmlrpc_password;

		return $xmlrpc_args;

	}

	public function is_xmlrpc_valid() {
		$status = Status::get_instance();

		$this->xmlrpc_client = new Client();

		if ( empty( $this->xmlrpc_client ) ) {

			$status->log_to_file( 'XMLRPC Client not initialized because of missing credentials' );

			return false;
		}

		return $this->xmlrpc_client;
	}

	/**
	 * Depending on the Domain initialize the xmlrpc client and call the required routes
	 *
	 * @since 2015-07-22
	 *
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $route string - the name of the endpoint route that we need to fetch data for
	 * @param $params array
	 *
	 * @return array
	 *
	 */
	public function call_xmlrpc_api_route( $route, $params = array() ) {

		$status = Status::get_instance();

		try {

			$xmlrpc_client = $this->is_xmlrpc_valid();

			$xmlrpc_client->cache_key = md5( 'pmc-theme-unit-test-' . $route );

			switch ( $route ) {

				case 'taxonomies':
					$xmlrpc_data = $this->_call_taxonomies_route();
					break;

				case 'options':
					$xmlrpc_data = $this->_call_options_route();
					break;

				case 'posts':
					if ( ! empty( $params['post_id'] ) ) {
						$xmlrpc_data[] = $this->_call_posts_route( $params['post_id'] );
					}
					break;

				default:
					$xmlrpc_data = false;
					break;

			}
		} catch ( \Exception $e ) {
			$status->log_to_file( 'XMLRPC error -- ' . $e->getMessage() );

			return new \WP_Error( 'unknown_error', $e->getMessage() );
		}

		return $xmlrpc_data;
	}


	/**
	 * Call the taxonomies route for getting taxonomies and terms via xmlrpc client
	 *
	 * @since 2015-07-22
	 *
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 */
	private function _call_taxonomies_route() {

		$status = Status::get_instance();

		$result = $this->xmlrpc_client->get_taxonomies();

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;
			$status->log_to_file( '_call_taxonomies_route Failed ' . $error );

			return new \WP_Error( 'unknown_error', ' - Taxonomy import failed with Exception ' . $error );

		} else {

			// Dont save the taxonomies since they should be registered on init hook from the admin.
			//$taxonomies_id[] = Taxonomies::get_instance()->call_import_route( $result );

			foreach ( $result as $tax ) {

				// Don't fetch terms for category or tag since its already done by REST API.
				if ( in_array( $tax['name'], (array) Config::$default_taxonomies, true ) ) {
					continue;
				}

				// pass the filter that you want to apply to the returned results
				$filter = array( 'number' => 100 );
				// Get Terms for the current taxonomy
				$terms       = $this->xmlrpc_client->get_terms( $tax['name'], $filter );
				$terms_ids[] = Terms::get_instance()->call_import_route( $terms );

			}

			return $terms_ids;
		}

	}

	/**
	 * Call the options route for getting whitelisted options via xmlrpc client.
	 * To whitelist use 'options_import_whitelist' filter so that those can be exported.
	 * To blacklist use 'options_export_blacklist' filter so that those will not be exported.
	 * Allow an installation to define a regular expression export blacklist for security purposes. It's entirely possible
	 * For instance, if you run a multsite installation, you could add in an mu-plugin:
	 *          define( 'WP_OPTION_EXPORT_BLACKLIST_REGEX', '/^(mailserver_(login|pass|port|url))$/' );
	 * to ensure that none of your sites could export your mailserver settings.
	 * @since 2015-07-22
	 *
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 * @return mixed|WP_Error
	 *
	 */
	private function _call_options_route() {

		$status = Status::get_instance();

		$result = $this->xmlrpc_client->get_all_options();

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;
			$status->log_to_file( '_call_options_route Failed ' . $error );

			return new \WP_Error( 'unauthorized_access', 'Failed with Exception - ' . $error );

		} else {
			return Options::get_instance()->call_import_route( $result );
		}

	}

	/**
	 * Call the posts route for getting custom fields and custom taxonomy terms.
	 *
	 * @since 2015-08-10
	 *
	 * @version 2015-08-10 Archana Mandhare PPT-5077
	 *
	 * @param int $post_id - the post ID we want to fetch data for.
	 *
	 * @return mixed
	 *
	 */
	private function _call_posts_route( $post_id ) {

		$status = Status::get_instance();

		$fields = array( 'post', 'terms', 'custom_fields' );
		$result = $this->xmlrpc_client->get_post_custom_data( $post_id, $fields );

		if ( ! $result ) {
			$error = $this->xmlrpc_client->error->message;
			$status->log_to_file( '_call_posts_route Failed ' . $error );

			return new \WP_Error( 'unauthorized_access', $error . ' Failed with Exception - ' );
		} else {
			return $result;
		}

	}

	/**
	 * This is used in Menu Importer since we do not pull all the terms for each Taxonomy
	 * We make a call to xmlrpc and pull the term if it is required for Menu.
	 *
	 * @since 2015-07-22
	 *
	 * @version 2015-07-24 Archana Mandhare PPT-5077
	 *
	 * @param string $taxonomy -taxonomy name on Production Site
	 *         int $term_id | name - the term_id or name for the Term on production Site
	 *
	 * @return int|WP_Error The taxonomy Term Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function get_taxonomy_term_by_id( $taxonomy, $term_id ) {

		$status = Status::get_instance();

		// Check taxonomy
		$taxonomy_id = taxonomy_exists( $taxonomy );

		if ( false === $taxonomy_id ) {

			$status->log_to_file( 'Taxonomy does not exits hence Menu Import failed  - ' . $taxonomy );

			return new \WP_Error( 'taxonomy_error', 'Taxonomy does not exits hence Menu Import failed - ' . $taxonomy );
		}

		$this->xmlrpc_client = new Client();

		if ( empty( $this->xmlrpc_client ) ) {

			$status->log_to_file( 'XMLRPC Client not initialized because of missing credentials' );

			return false;
		}

		$this->xmlrpc_client->cache_key = md5( 'pmc-theme-unit-test-' . $term_id );

		//Fetch Term
		$result = $this->xmlrpc_client->get_term( $taxonomy, $term_id );

		if ( empty( $result ) ) {

			$error = $this->xmlrpc_client->error->message;

			$status->log_to_file( 'Menu Taxonomy Term Import Failed during importing for Menu with Exception - ' . $error );

			return new \WP_Error( 'taxonomy_error', 'Menu Taxonomy Term Import Failed during importing for Menu with Exception - ' . $error );

		} else {
			// Save Taxonomy Term if not exists in the current site.
			return Terms::get_instance()->save_taxonomy_terms( $result );
		}
	}
}
