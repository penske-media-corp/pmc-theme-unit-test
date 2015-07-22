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
	 * @version 1.0, 2015-07-21, for PPT-5077, Archana Mandhare
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {

		$this->_setup_hooks();
	}


	protected function _setup_hooks() {

		add_filter( 'pmc_xmlrpc_client_credentials', array( $this, 'filter_pmc_xmlrpc_client_credentials' ) );
	}

	public function filter_pmc_xmlrpc_client_credentials( $xmlrpc_args ) {

		return apply_filters( 'pmc_theme_ut_xmlrpc_client_auth', $xmlrpc_args, $this->_domain );

	}

	public function call_xmlrpc_api_route( $params ) {

		if ( empty( $params['domain'] ) ) {
			return;
		}

		$xmlrpc_data   = array();
		$this->_domain = $params['domain'];
		$route         = $params['route'];

		if ( empty( $this->xmlrpc_client ) ) {
			$this->xmlrpc_client = $this->_get_xmlrpc_client( $this->_domain );
		}

		switch ( $route ) {
			case 'taxonomies' :
				$xmlrpc_data[] = $this->_call_taxonomies_xmlrpc_route();
				break;
			case 'options' :
				$xmlrpc_data[] = $this->_call_options_xmlrpc_route();
				break;
			default:
				break;
		}

		return $xmlrpc_data;
	}

	private function _get_xmlrpc_client() {

		$this->xmlrpc_client = new \PMC_HTTP_IXR_Client();

		return $this->xmlrpc_client;
	}

	private function _call_taxonomies_xmlrpc_route() {

		$terms_ids = array();

		$result = $this->xmlrpc_client->get_taxonomies();;

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;

			return new \WP_Error( 'unauthorized_access', $error . " Failed with Exception - " );

		} else {

			foreach ( $result as $tax ) {

				// Don't fetch terms for category or tag since its already done by REST API.
				if ( 'category' === $tax['name'] || 'post_tag' === $tax['name'] ) {
					continue;
				}

				$terms = $this->xmlrpc_client->get_terms( $tax['name'] );

				$taxonomies_id[] = Taxonomies_Importer::get_instance()->save_taxonomy( $tax );
				$terms_ids[]     = Terms_Importer::get_instance()->call_import_route( $terms );

			}
		}

		return $terms_ids;
	}

	private function _call_options_xmlrpc_route() {

		$options_id = array();

		$result = $this->xmlrpc_client->get_wp_options();;

		if ( ! $result ) {

			$error = $this->xmlrpc_client->error->message;

			return new \WP_Error( 'unauthorized_access', $error . " Failed with Exception - " );

		} else {

			$options_id[] = Options_Importer::get_instance()->call_import_route( $result );

		}

		return $options_id;
	}

	public function get_taxonomy_term_by_id(  $taxonomy, $taxonomy_term_id ) {

		// Get the XMLRPC client object
		if ( empty( $this->xmlrpc_client ) ) {
			$this->xmlrpc_client = $this->_get_xmlrpc_client();
		}

		// Fetch data
		$tax    = $this->xmlrpc_client->get_taxonomy( $taxonomy );
		$result = $this->xmlrpc_client->get_taxonomy_term( $taxonomy, $taxonomy_term_id );

		if ( empty( $tax ) || empty( $result ) ) {

			$error = $this->xmlrpc_client->error->message;

			return new \WP_Error( 'unauthorized_access', $error . " Failed with Exception - " );

		} else {
			// Save Taxonomy if not exists in the current site.
			Taxonomies_Importer::get_instance()->save_taxonomy( $tax );
			// Save Taxonomy Term if not exists in the current site.
			return Terms_Importer::get_instance()->save_taxonomy_terms( $result );
		}


	}
}

