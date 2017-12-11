<?php
namespace PMC\Theme_Unit_Test\Admin;

use PMC\Theme_Unit_Test\Traits\Singleton;

class Import_Processor extends PMC_Async_Request {

	use Singleton;

	/**
	 * @var string
	 */
	protected $action = 'import_request';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		// Actions to perform
	}

}