<?php

class App_Period {

	private $_start;
	private $_end;

	public function __construct( $start, $end ) {
		$this->_start = is_numeric( $start ) ? $start : strtotime( $start );
		$this->_end = is_numeric( $end ) ? $end : strtotime( $end );
	}

	/**
	 * check perion contain
	 *
	 * @since 2.2.4 added `$use_bundaries` argument.
	 *
	 * @param integer $start Start date to check.
	 * @param integer $end end date to check.
	 * @param boolean $use_bundaries Force to use boundaries, instead exac match.
	 */
	public function contains( $start, $end, $use_bundaries = false ) {
		$start = is_numeric( $start ) ? $start : strtotime( $start );
		$end = is_numeric( $end ) ? $end : strtotime( $end );
		if ( $use_bundaries || appointments_use_legacy_boundaries_calculus() ) {
			return $this->_contains_boundaries( $start, $end );
		}
		return $this->_contains_exact( $start, $end );
	}

	public function get_start() {
		return $this->_start; }
	public function get_end() {
		return $this->_end; }

	// doesn't handle enclosed appointments
	protected function _contains_exact( $start, $end ) {
		return (
			$this->_start >= $start
			&&
			$this->_end <= $end
		);
	}
	// detects appointments overlap
	protected function _contains_boundaries( $start, $end ) {
		return (
			$end > $this->_start
			&&
			$start < $this->_end
		);
	}
}
