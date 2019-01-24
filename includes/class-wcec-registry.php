<?php
/**
 * Responsible for holding instances of helper objects.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCEC_Registry' ) ) :

	/**
	 * WCEC_Registry
	 *
	 * @since 1.0.0
	 */
	class WCEC_Registry {

		/**
		 * Registered objects.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $objects;

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->objects = array();
		}

		/**
		 * Return a registered object.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class The name of the class.
		 * @return object|false Returns an object if the class exists, otherwise returns false.
		 */
		public function get( $class ) {
			$class_key = $this->get_class_key( $class );

			if ( ! array_key_exists( $class_key, $this->objects ) ) {
				$this->objects[ $class_key ] = $this->get_class_instance( $this->get_class_name( $class ) );
			}

			return $this->objects[ $class_key ];
		}

		/**
		 * Checks whether an object has already been registered.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class The name of the class.
		 * @return boolean
		 */
		public function has( $class ) {
			return array_key_exists( $this->get_class_key( $class ), $this->objects );
		}

		/**
		 * Register an object that has already been instantiated.
		 *
		 * @since  1.0.0
		 *
		 * @param  object $object The object to be registered.
		 * @return void
		 */
		public function register_object( $object ) {
			$class     = get_class( $object );
			$class_key = $this->get_class_key( $class );

			$this->objects[ $class_key ] = $object;
		}

		/**
		 * Given a class name, returns the key for that class.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class The class name.
		 * @return string
		 */
		protected function get_class_key( $class ) {
			if ( false === strpos( $class, 'WCEC_' ) ) {
				return $class;
			}

			return strtolower( str_replace( 'WCEC_', '', $class ) );
		}

		/**
		 * Return a sanitized class name.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class_key The class to return.
		 * @return string
		 */
		protected function get_class_name( $class_key ) {
			if ( 0 === strpos( $class_key, 'WCEC_' ) ) {
				return $class_key;
			}

			$class_words = str_replace( '_', ' ', $class_key );
			$class_words = ucwords( $class_words );
			return 'WCEC_' . str_replace( ' ', '_', $class_words );
		}

		/**
		 * Return the class instance.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class The class name.
		 * @return object
		 */
		protected function get_class_instance( $class ) {
			if ( ! class_exists( $class ) ) {
				/* translators: %s: class name */
				wp_die( sprintf( _x( 'Class %s does not exist.', 'error message when non-existent class is called', 'wwp' ), $class ) );
			}

			if( method_exists($class, 'instance') ){
				return $class::instance();
			}

			return new $class;
		}
	}

endif;
