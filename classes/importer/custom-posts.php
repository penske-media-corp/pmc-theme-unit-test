<?php
namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\PMC_Singleton;

class Custom_Posts extends PMC_Singleton {

	protected function _init(){
		$this->_setup_hooks();
	}

	private function _setup_hooks(){
		add_filter( 'pmc_process_post_content', array( $this, 'pmc_process_post_content_for_ads' ),10, 2 );
	}

	public function pmc_process_post_content_for_ads( $content, $post_type ){
		if( 'pmc-ad' === $post_type ) {
			$content = json_decode( $content, true);
			if ( empty( $content['status'] ) ) {
				$content['status'] = 'Active';
			}
			return $content;
		}
		return $content;
	}
}

Custom_Posts::get_instance();