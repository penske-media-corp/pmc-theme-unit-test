<?php

/**
 * Base singleton class to be extended by all other singletons.
 *
 * Dependent items can use the `pmc_singleton_init_{$called_class}` hook to
 * execute code immediately after _init() is called.
 *
 * Using the singleton pattern in WordPress is mostly a way to protect against
 * mistakes. With complex plugins, there are many cases where multiple copies of
 * the plugin would load, and action hooks would load (and trigger) multiple
 * times.
 *
 *   If you're planning on using a global variable, then you should extend
 *   PMC_Singleton. Singletons are a way to safely use globals; they let you
 *   access and set the global from anywhere, without risk of collision.
 *
 *   If any method in the class needs to be aware of "state", then you should
 *   extend PMC_Singleton.
 *
 *   If any method in the class need to "talk" to another or be aware of what
 *   another method has done, then you should extend PMC_Singleton.
 *
 *   If your class is being used as a way to collect related functions together,
 *   use a class with only static methods.
 *
 *   If you specifically need multiple objects, then use a normal class.
 *
 *   If you're unsure, ask in engineering chat.
 *
 * @since 2013-01-07 Corey Gilmore
 *
 * @version 2014-06-10 Corey Gilmore Trigger the `pmc_singleton_init_{$called_class}` action immediately after calling _init(). Useful for dependencies.
 * @version 2013-02-07 Corey Gilmore Use an array of instances per GK. Previously static::$_instance would always refer to the first inherited object that was instantiated.
 * @version 2013-01-07 Corey Gilmore
 *
 */
namespace PMC\Theme_Unit_Test;

abstract class PMC_Singleton {
	protected static $_instance = array();

	/**
	 * Prevent direct object creation
	 */
	protected function  __construct() {
	}

	/**
	 * Prevent object cloning
	 */
	final private function  __clone() {
	}

	/**
	 * Returns new or existing Singleton instance
	 * @return obj self::$_instance[$class] Instance of PMC_Singleton
	 */
	final public static function get_instance() {
		/*
		 * If you extend this class, self::$_instance will be part of the base
		 * class.
		 * In the sinlgeton pattern, if you have multiple classes extending this
		 * class, self::$_instance will be overwritten with the most recent
		 * class instance that was instantiated. Thanks to late static binding
		 * we use get_called_class() to grab the caller's class name, and store
		 * a key=>value pair for each classname=>instance in self::$_instance
		 * for each subclass.
		 */
		$class = get_called_class();
		if ( ! isset( static::$_instance[ $class ] ) ) {
			self::$_instance[ $class ] = new $class();

			// Run's the class's _init() method, where the class can hook into actions and filters, and do any other initialization it needs
			self::$_instance[ $class ]->_init();

			// Dependent items can use the `pmc_singleton_init_{$called_class}` hook to execute code immediately after _init() is called.
			do_action( "pmc_singleton_init_$class" );
		}

		return self::$_instance[ $class ];
	}

	/**
	 * Initialization function called when object is instantiated. Does nothing
	 * by default. This class should be overriden in the child class.
	 * Stuff that you only want to do once, such as hooking into actions and
	 * filters, goes here.
	 */
	protected function _init() {
	}

}    //end class

//EOF
