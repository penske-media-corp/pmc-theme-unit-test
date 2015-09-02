<?php

/*
 * Command base class for all wp cli command process
 */

class PMC_WP_CLI_Base extends WPCOM_VIP_CLI_Command {
	public $log_file       = '';
	public $sleep          = 2;   // number of second to sleep
	public $max_iteration  = 20;  // number of iteration before calling sleep if requested
	public $batch_size     = 500; // default batch size
	public $dry_run        = false;

	protected $_iteraction_count = 0;
	protected $_assoc_args_properties_mapping = array();

	public function __construct( $args = false , $assoc_args = false ) {
		$this->_extract_common_args( $assoc_args );
	}

	protected function _extract_common_args( $assoc_args ) {
		if ( empty( $assoc_args ) ) {
			return false;
		}

		if ( empty( $assoc_args ) ) {
			return;
		}

		if ( ! empty( $assoc_args['log-file'] ) ) {
			$this->log_file = $assoc_args['log-file'];
		}

		if ( ! empty( $assoc_args['sleep'] ) ) {
			$this->sleep = (int)$assoc_args['sleep'];
		}

		if ( ! empty( $assoc_args['max-iteration'] ) ) {
			$this->max_iteration = (int)$assoc_args['max-iteration'];
		}

		if ( ! empty( $assoc_args['batch-size' ] ) ) {
			$this->batch_size = (int)$assoc_args['batch-size'];

			if ( $this->batch_size > 10000 ) {
				$this->batch_size = 10000;
			} else if ( $this->batch_size < 10 ) {
				$this->batch_size = 10;
			}
		}

		$this->dry_run = ! empty( $assoc_args['dry-run'] );

		if ( !empty( $this->_assoc_args_properties_mapping ) ) {
			foreach ( $this->_assoc_args_properties_mapping as $key => $name) {
				if( !empty( $assoc_args[$name] ) ) {
					$this->$key = $assoc_args[$name];
				}
			}
			unset ( $this->_assoc_args_properties_mapping );
		}
	}

	/*
	 * @deprecated refer to _update_iteration, function to be phased out and remove in the future
	 */
	protected function _update_interation() {
		$this->_update_iteration();
	}

	protected function _update_iteration() {
		$this->_iteraction_count++;

		if ( $this->sleep > 0 && $this->max_iteration > 0 && $this->_iteraction_count > $this->max_iteration ) {
			$this->_iteraction_count = 0;
			WP_CLI::line( "Sleep for {$this->sleep} seconds..." );
			$this->stop_the_insanity();
			sleep( $this->sleep );
		}
	}

	protected function _write_log( $msg, $is_error = false ) {
		$is_error = ( $is_error === true ) ? true : false;
		$msg_prefix = '';

		if ( ! $is_error ) {
			WP_CLI::line( $msg );
		} else {
			$msg_prefix = 'Error: ';
		}

		if ( ! empty( $this->log_file ) ) {
			file_put_contents( $this->log_file, $msg_prefix . $msg . "\n", FILE_APPEND );
		}

		if ( $is_error ) {
			WP_CLI::error( $msg );
		}
	}

	protected function _error( $msg ) {
		$this->_write_log( $msg, true );
	}

}

// EOF
