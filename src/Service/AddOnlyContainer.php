<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Service;

use Inpsyde\MultilingualPress\Service\Exception\ContainerBootstrapped;
use Inpsyde\MultilingualPress\Service\Exception\ContainerLocked;
use Inpsyde\MultilingualPress\Service\Exception\CannotUnsetValue;
use Inpsyde\MultilingualPress\Service\Exception\ValueAlreadySet;
use Inpsyde\MultilingualPress\Service\Exception\ValueNotSet;

/**
 * Simple add-only container implementation to be used for dependency management.
 *
 * @package Inpsyde\MultilingualPress\Service
 * @since   3.0.0
 */
final class AddOnlyContainer implements Container {

	/**
	 * @var callable[]
	 */
	private $factories = [];

	/**
	 * @var bool
	 */
	private $is_bootstrapped = false;

	/**
	 * @var bool
	 */
	private $is_locked = false;

	/**
	 * @var bool[]
	 */
	private $shared = [];

	/**
	 * @var array
	 */
	private $values;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param array $values Initial values or factory callbacks to be stored.
	 */
	public function __construct( array $values = [] ) {

		if ( ! isset( $this->values ) ) {
			$this->values = [];

			foreach ( $values as $name => $value ) {
				$this->offsetSet( $name, $value );
			}
		}
	}

	/**
	 * Checks if a value or factory callback with the given name exists.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of a value or factory callback.
	 *
	 * @return bool Whether or not a value or factory callback with the given name exists.
	 */
	public function offsetExists( $name ) {

		return array_key_exists( $name, $this->values ) || isset( $this->factories[ $name ] );
	}

	/**
	 * Returns the value or factory callback with the given name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of a value or factory callback.
	 *
	 * @return mixed The value or factory callback with the given name.
	 *
	 * @throws ValueNotSet           if there is no value or factory callback with the given name.
	 * @throws ContainerBootstrapped if a not shared value or factory callback is to be accessed on a bootstrapped
	 *                               container.
	 */
	public function offsetGet( $name ) {

		if ( ! $this->offsetExists( $name ) ) {
			throw ValueNotSet::for_name( $name, 'read' );
		}

		if ( $this->is_bootstrapped && ! array_key_exists( $name, $this->shared ) ) {
			throw ContainerBootstrapped::for_name( $name, 'read' );
		}

		if ( ! array_key_exists( $name, $this->values ) ) {
			$factory = $this->factories[ $name ];

			$this->values[ $name ] = $factory( $this );

			if ( $this->is_locked ) {
				unset( $this->factories[ $name ] );
			}
		}

		return $this->values[ $name ];
	}

	/**
	 * Stores the given value or factory callback with the given name.
	 *
	 * Scalar values will get shared automatically.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name  The name of a value or factory callback.
	 * @param mixed  $value The value or factory callback.
	 *
	 * @return void
	 *
	 * @throws ContainerLocked if the container is locked.
	 * @throws ValueAlreadySet if there already is a value with the given name.
	 */
	public function offsetSet( $name, $value ) {

		if ( $this->is_locked ) {
			throw ContainerLocked::for_name( $name, 'set' );
		}

		if ( array_key_exists( $name, $this->values ) ) {
			throw ValueAlreadySet::for_name( $name, 'set' );
		}

		if ( is_callable( $value ) ) {
			$this->factories[ $name ] = $value;

			return;
		}

		$this->values[ $name ] = $value;

		if ( is_scalar( $value ) && ! array_key_exists( $name, $this->shared ) ) {
			$this->shared[ $name ] = true;
		}
	}

	/**
	 * Removes the value or factory callback with the given name.
	 *
	 * Removing values or factory callbacks is not allowed.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of a value or factory callback.
	 *
	 * @return void
	 *
	 * @throws CannotUnsetValue
	 */
	public function offsetUnset( $name ) {

		throw CannotUnsetValue::for_name( $name );
	}

	/**
	 * Bootstraps (and locks) the container.
	 *
	 * Only shared values and factory callbacks are accessible from now on.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function bootstrap() {

		$this->lock();

		$this->is_bootstrapped = true;
	}

	/**
	 * Replaces the factory callback with the given name with the given factory callback.
	 *
	 * The new factory callback will receive as first argument the object created by the current factory, and as second
	 * argument the container.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $name        The name of an existing factory callback.
	 * @param callable $new_factory The new factory callback.
	 *
	 * @return Container Container instance.
	 *
	 * @throws ContainerLocked if the container is locked.
	 * @throws ValueNotSet     if there is no value or factory callback with the given name.
	 * @throws ValueAlreadySet if there already is a value with the given name.
	 */
	public function extend( string $name, callable $new_factory ): Container {

		if ( $this->is_locked ) {
			throw ContainerLocked::for_name( $name, 'extend' );
		}

		if ( ! array_key_exists( $name, $this->factories ) ) {
			throw ValueNotSet::for_name( $name, 'extend' );
		}

		if ( array_key_exists( $name, $this->values ) ) {
			throw ValueAlreadySet::for_name( $name, 'extend' );
		}

		$current_factory = $this->factories[ $name ];

		$this->factories[ $name ] = function ( Container $container ) use ( $new_factory, $current_factory ) {

			return $new_factory( $current_factory( $container ), $container );
		};

		return $this;
	}

	/**
	 * Locks the container.
	 *
	 * A locked container cannot be manipulated anymore. All stored values and factory callbacks are still accessible.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function lock() {

		$this->is_locked = true;
	}

	/**
	 * Stores the given value or factory callback with the given name, and defines it to be accessible even after the
	 * container has been bootstrapped.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name  The name of a value or factory callback.
	 * @param mixed  $value The value or factory callback.
	 *
	 * @return Container Container instance.
	 */
	public function share( string $name, $value ): Container {

		$this->offsetSet( $name, $value );

		$this->shared[ $name ] = true;

		return $this;
	}
}
