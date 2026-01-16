<?php
/**
 * Singleton base class.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CAIS_Singleton' ) ) :

	/**
	 * CAIS_Singleton class.
	 */
	class CAIS_Singleton {
		/**
		 * Instances storage.
		 *
		 * @var array
		 */
		protected static $instances = array();

		/**
		 * Constructor.
		 */
		protected function __construct() {
			// Protected constructor to prevent direct instantiation.
		}

		/**
		 * Get singleton instance.
		 *
		 * @return static
		 */
		public static function instance() {
			$class = static::class;

			if ( ! isset( self::$instances[ $class ] ) ) {
				self::$instances[ $class ] = new static();
			}

			return self::$instances[ $class ];
		}
	}

endif;
