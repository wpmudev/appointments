<?php

/**
 * Class App_Transactions_Test
 *
 * @group transactions
 */
class App_Transactions_Test extends App_UnitTestCase {

	function test_insert_transaction() {
		$result = appointments_insert_transaction( array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		) );

		$this->assertTrue( is_int( $result ) );
	}

	function test_get_transaction() {
		$args = array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		);
		$id = appointments_insert_transaction( $args );

		$transaction = appointments_get_transaction( $id );

		foreach ( $args as $key => $arg ) {
			$field = 'transaction_' . $key;
			if ( 'total_amount' == $key ) {
				$this->assertEquals( $transaction->$field, (int) round( $args[$key] * 100 ) );
			}
			else {
				$this->assertEquals( $transaction->$field, $args[ $key ] );
			}

		}

	}

	function test_update_transaction() {
		$args = array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		);
		$id = appointments_insert_transaction( $args );

		$new_args = array(
			'app_ID' => 2,
			'paypal_ID' => 'ABCD',
			'stamp' => 1234,
			'total_amount' => 12334,
			'currency' => 'DOL',
			'status' => 'Future',
			'note' => 'A Note 2',
		);

		appointments_update_transaction( $id, $new_args );

		$transaction = appointments_get_transaction( $id );

		foreach ( $new_args as $key => $arg ) {
			$field = 'transaction_' . $key;
			if ( 'total_amount' == $key ) {
				$this->assertEquals( $transaction->$field, (int) round( $new_args[$key] * 100 ) );
			}
			else {
				$this->assertEquals( $transaction->$field, $new_args[ $key ] );
			}

		}

	}

	function test_delete_transaction() {
		$args = array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		);
		$id = appointments_insert_transaction( $args );

		$transaction = appointments_get_transaction( $id );

		$this->assertTrue( appointments_delete_transaction( $id ) );

		$transaction = appointments_get_transaction( $id );

		$this->assertFalse( $transaction );
	}

	function test_get_transaction_by_paypal_id() {
		$args = array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		);
		$id = appointments_insert_transaction( $args );

		$transaction = appointments_get_transaction_by_paypal_id( 'ABC' );

		$this->assertTrue( is_a( $transaction, 'Appointments_Transaction' ) );
	}

	function test_get_transactions() {
		$args = array(
			'app_ID' => 1,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'Pending',
			'note' => 'A Note',
		);
		$id_1 = appointments_insert_transaction( $args );

		$args = array(
			'app_ID' => 2,
			'paypal_ID' => 'ABCD',
			'stamp' => 1234,
			'total_amount' => 12334,
			'currency' => 'DOL',
			'status' => 'Future',
			'note' => 'A Note 2',
		);
		$id_2 = appointments_insert_transaction( $args );

		$transactions = appointments_get_transactions();
		$this->assertEquals( array( $id_2, $id_1 ), wp_list_pluck($transactions, 'transaction_ID' ) );

		$transactions = appointments_get_transactions(array( 'per_page' => 1 ));
		$this->assertEquals( array( $id_2 ), wp_list_pluck($transactions, 'transaction_ID' ) );

		$transactions = appointments_get_transactions(array( 'order' => 'ASC' ));
		$this->assertEquals( array( $id_1, $id_2 ), wp_list_pluck($transactions, 'transaction_ID' ) );
	}

	function test_get_client_name() {
		$user_id = $user = $this->factory()->user->create();
		$args = $this->factory->appointment->generate_args();
		$args['user'] = $user_id;
		$app_id = $this->factory->appointment->create_object( $args );
		$args = array(
			'app_ID' => $app_id,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'pending',
			'note' => 'A Note',
		);
		$id_1 = appointments_insert_transaction( $args );
		$transaction = appointments_get_transaction($id_1);
		$this->assertContains( appointments_get_appointment( $app_id )->name, $transaction->get_client_name() );
	}

	function test_get_service() {
		$service_id = $this->factory->service->create_object( $this->factory->service->generate_args() );
		$args = $this->factory->appointment->generate_args();
		$args['service'] = $service_id;
		$app_id = $this->factory->appointment->create_object( $args );

		$args = array(
			'app_ID' => $app_id,
			'paypal_ID' => 'ABC',
			'stamp' => 123,
			'total_amount' => 1233,
			'currency' => 'EUR',
			'status' => 'pending',
			'note' => 'A Note',
		);
		$id_1 = appointments_insert_transaction( $args );

		$transaction = appointments_get_transaction($id_1);
		$this->assertEquals( appointments_get_service( $service_id ), $transaction->get_service() );
	}


}