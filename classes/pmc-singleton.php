<?php
/**
 * This class is here only for backwards compatibility for code which still
 * uses this class to implement Singleton pattern. Any new classes should be
 * using the new PMC\Global_Functions\Traits\Singleton trait to implement
 * Singleton pattern.
 *
 * @since 2013-01-07 Corey Gilmore
 *
 * @version 2014-06-10 Corey Gilmore Trigger the `pmc_singleton_init_{$called_class}` action immediately after calling _init(). Useful for dependencies.
 * @version 2013-02-07 Corey Gilmore Use an array of instances per GK. Previously static::$_instance would always refer to the first inherited object that was instantiated.
 * @version 2013-01-07 Corey Gilmore
 * @version 2017-06-19 Amit Gupta - implemented PMC\Global_Functions\Traits\Singleton trait for backward compatibility
 *
 */

namespace PMC\Theme_Unit_Test;

use PMC\Theme_Unit_Test\Traits\Singleton;

abstract class PMC_Singleton {

	use Singleton;

	/**
	 * This method is deprecated and is no longer to be used.
	 * Use protected class constructor for all init stuff.
	 * This is defined here only for backward compatibility.
	 *
	 * @return void
	 */
	protected function _init() {
	}

}

//EOF
