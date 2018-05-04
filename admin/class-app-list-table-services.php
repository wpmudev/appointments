<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Appointments_WP_List_Table_Services extends WP_List_Table {

	private $currency;

	public function __construct() {
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'service',     //singular name of the listed records
			'plural'    => 'services',    //plural name of the listed records
			'ajax'      => false,//does this table support ajax?
		) );
		$this->currency = appointments_get_option( 'currency' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'capacity':
			case 'duration':
			case 'price':
				return $item->$column_name;
			default:
				return print_r( $item,true ); //Show the whole array for troubleshooting purposes
		}
	}

	public function column_name( $item ) {
		$actions = array(
			'edit'    => sprintf(
				'<a href="#section-edit-service" data-id="%s" data-nonce="%s" class="edit">%s</a>',
				esc_attr( $item->ID ),
				esc_attr( wp_create_nonce( 'service-'.$item->ID ) ),
				__( 'Edit', 'appointments' )
			),
			'delete'    => sprintf(
				'<a href="#" data-id="%s" data-nonce="%s" class="delete">%s</a>',
				esc_attr( $item->ID ),
				esc_attr( wp_create_nonce( 'service-'.$item->ID ) ),
				__( 'Delete', 'appointments' )
			),
		);

		//Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ $item->name,
			/*$2%s*/ $item->ID,
			/*$3%s*/ $this->row_actions( $actions )
		);
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
		);
	}
	public function column_page( $item ) {
		$page = false;
		if ( 0 !== $item->page ) {
			$page = get_page( $item->page );
		}
		if ( empty( $page ) ) {
			return sprintf( '<small>[%s]</small>', esc_html__( 'None', 'appointments' ) );
		}
		return sprintf(
			'%s<div class="row-actions"><a href="%s">%s</a></div>',
			$page->post_title,
			get_page_link( $page->ID ),
			__( 'View page', 'appointments' )
		);
	}

	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'name' => __( 'Name', 'appointments' ),
			'capacity' => __( 'Capacity', 'appointments' ),
			'duration' => __( 'Duration (mins)', 'appointments' ),
			'price' => sprintf( __( 'Price (%s)', 'appointments' ), $this->currency ),
			'page' => __( 'Description page', 'appointments' ),
		);
		return $columns;
	}


	/** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'     => array( 'title',false ),     //true means it's already sorted
			'rating'    => array( 'rating',false ),
			'director'  => array( 'director',false ),
		);
		return $sortable_columns;
	}


	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	public function get_bulk_actions() {
		$actions = array(
			'delete'    => 'Delete',
		);
		return $actions;
	}


	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			wp_die( 'Items deleted (or they would be if we had items to delete)!' );
		}

	}

	public function prepare_items() {
		global $wpdb; //This is used only if making any database queries

		l( 'aaa' );

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 5;

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$data = appointments_get_services();
		l( $data );

		/***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         *
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         *
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $data );

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = appointments_get_services();

		l( $data );

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ),//WE have to calculate the total number of pages
		) );
	}
}

