<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class XMLRPC_Router extends PMC_Singleton {

	public $xmlrpc_client;
	private $_domain;

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {

		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks and filters required for xmlrpc
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	protected function _setup_hooks() {

		add_filter( 'pmc_tut_xmlrpc_client_credentials', array( $this, 'filter_pmc_tut_xmlrpc_client_credentials' ) );
	}

	/**
	 * Instantiate the PMC_HTTP_IXR_Client class and return the object
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 * @return object PMC_HTTP_IXR_Client
	 *
	 */
	private function _get_xmlrpc_client() {

		$this->xmlrpc_client = new XMLRPC_Client();

		return $this->xmlrpc_client;
	}


	/**
	 * Filter that returns the credentials for xmlrpc client call
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 * @return array containing the credentials
	 *
	 */
	public function filter_pmc_tut_xmlrpc_client_credentials( $xmlrpc_args ) {

		return apply_filters( 'pmc_theme_ut_xmlrpc_client_auth', $xmlrpc_args, $this->_domain );

	}

	/**
	 * Depending on the Domain initialize the xmlrpc client and call the required routes
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	public function call_xmlrpc_api_route( $params ) {

		if ( empty( $params['domain'] ) ) {
			return;
		}

		$xmlrpc_data   = array();
		$this->_domain = $params['domain'];
		$route         = $params['route'];

		if ( empty( $this->xmlrpc_client ) ) {

			$this->_domain = $params['domain'];
			$this->xmlrpc_client = $this->_get_xmlrpc_client();

		}

		switch ( $route ) {

			case 'taxonomies' :
				$xmlrpc_data[] = $this->_call_taxonomies_route();
				break;

			case 'options' :
				$xmlrpc_data[] = $this->_call_options_route();
				break;

			default:
				break;

		}

		return $xmlrpc_data;
	}


	/**
	 * Call the taxonomies route for getting taxonomies and terms via xmlrpc client
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	private function _call_taxonomies_route() {

		$terms_ids = array();

		$result = $this->xmlrpc_client->get_taxonomies();

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;

			return new \WP_Error( "unknown_error", " - Taxonomy import failed with Exception " . $error );

		} else {

			// Dont save the taxonomies since they should be registered on init hook from the admin.
			//$taxonomies_id[] = Taxonomies_Importer::get_instance()->call_import_route( $result );

			foreach ( $result as $tax ) {

				// Don't fetch terms for category or tag since its already done by REST API.
				if ( 'category' === $tax['name'] || 'post_tag' === $tax['name'] ) {
					continue;
				}

				// pass the filter that you want to apply to the returned results
				$filter = array( 'number' => 100 );
				// Get Terms for the current taxonomy
				$terms       = $this->xmlrpc_client->get_terms( $tax['name'], $filter );
				$terms_ids[] = Terms_Importer::get_instance()->call_import_route( $terms );

			}
		}

		return $terms_ids;
	}


	/**
	 * Call the options route for getting whitelisted options via xmlrpc client.
	 * To whitelist use 'options_import_whitelist' filter so that those can be exported.
	 * To blacklist use 'options_export_blacklist' filter so that those will not be exported.
	 * Allow an installation to define a regular expression export blacklist for security purposes. It's entirely possible
	 * For instance, if you run a multsite installation, you could add in an mu-plugin:
	 *          define( 'WP_OPTION_EXPORT_BLACKLIST_REGEX', '/^(mailserver_(login|pass|port|url))$/' );
	 * to ensure that none of your sites could export your mailserver settings.
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22 Archana Mandhare - PPT-5077
	 *
	 */
	private function _call_options_route() {

		$options_id = array();

		$result = $this->xmlrpc_client->get_all_options();

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;

			return new \WP_Error( 'unauthorized_access', $error . " Failed with Exception - " );

		} else {

			$options_id[] = Options_Importer::get_instance()->call_import_route( $result );

		}

		return $options_id;
	}

	/**
	 * This is used in Menu Importer since we do not pull all the terms for each Taxonomy
	 * We make a call to xmlrpc and pull the term if it is required for Menu.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-24 Archana Mandhare - PPT-5077
	 *
	 */
	public function get_taxonomy_term_by_id( $taxonomy, $term_id ) {

		// Get the XMLRPC client object
		if ( empty( $this->xmlrpc_client ) ) {
			$this->xmlrpc_client = $this->_get_xmlrpc_client();
		}

		// Fetch taxonomy
		$taxonomy_id = taxonomy_exists( $taxonomy );

		if ( false === $taxonomy_id ) {
			return new \WP_Error( "unknown_error", "Taxonomy Term Failed because taxonomy does not exists.  - " . $taxonomy );
		}

		//Fetch Term
		$result = $this->xmlrpc_client->get_term( $term_id, $taxonomy );

		if ( empty( $result ) ) {
			$error = $this->xmlrpc_client->error->message;
			return new \WP_Error( "unknown_error", "Taxonomy Term Failed with Exception - " . $error );

		} else {
			// Save Taxonomy Term if not exists in the current site.
			return Terms_Importer::get_instance()->save_taxonomy_terms( $result );
		}


	}
}
