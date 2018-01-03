<?php

namespace PMC\Theme_Unit_Test\Background;

class Async_Import_Request extends PMC_Async_Request {

	/**
	 * @var string
	 */
	protected $action = 'import_request';

	/**
	 * Handle
	 *
	 * @see https://github.com/A5hleyRich/wp-background-processing
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		// Actions to perform
	}

}