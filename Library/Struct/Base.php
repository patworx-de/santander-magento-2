<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Struct;

abstract class Base {

	/**
	 * SantanderStruct constructor.
	 *
	 * @param null $array
	 */
	public function __construct( $array = null ) {
		if ( $array !== null ) {
			if ( is_array( $array ) ) {
				$this->fromArray( $array );
			}
		}
	}

	/**
	 * @param array $array
	 */
	public function fromArray( array $array ) {
		foreach ( $array as $field => $v ) {
			$this->{$field} = $v;
		}
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return get_object_vars( $this );
	}
}
