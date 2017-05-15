<?php

class Appointments_Integrations_Divi {

	public function __construct() {

          add_filter( 'appointments_pagination_shortcode_next_class', array( $this, 'disable_divi_smoothscroll' ), 10 );
          add_filter( 'appointments_pagination_shortcode_previous_class', array( $this, 'disable_divi_smoothscroll' ), 10 );

     }

     public function is_divi_active(){

          if( function_exists( 'et_setup_theme' ) ){
               return true;
          }

          return false;

     }

     //Add css class "et_smooth_scroll_disabled" so that pagination anchor links don't get smoothscroll functionality by Divi and other et themes
     public function disable_divi_smoothscroll( $class ){

          if ( $this->is_divi_active() ) {
			$class .= ' et_smooth_scroll_disabled';
		}

          return $class;
     }

}

new Appointments_Integrations_Divi();
