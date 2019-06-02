<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Send_Hints {

    public $header_str;
    public $head_str;

	// public function __construct() {
	// 	add_action( 'wp', array( $this, 'send_resource_hints' ), 11, 0 );
	// }

	public function send_resource_hints() {
        global $wpdb;
        
        $post_ID = (string) get_the_ID();

        if (is_home()) {
            $post_ID = '-1';
        }

		$table = $wpdb->prefix . 'gktpp_table';
		$links = $wpdb->get_results(
            "
            SELECT header_string, head_string
            FROM $table
            WHERE status = 'Enabled'
            AND post_id = $post_ID
            OR post_id = 0
            "
        );



		if ( count( $links ) < 1 || ( ! is_array( $links ) ) ) {
			return;
		}

		foreach ( $links as $key => $value ) {
            $this->header_str .= $value->header_string . ', ';
            $this->head_str .= $value->head_string;
        }
        
        return $this->send_hints();
    }
    
    public function send_hints() {
        $option = get_option( 'gktpp_send_in_header' );

        return ($option === 'HTTP Header')
            ? header( 'Link:' . $this->header_str )
            : add_action('wp_head', array( $this, 'send_hints_to_head' ) );
    }

    public function send_hints_to_head() {
        return printf( $this->head_str );
    }

}

add_action('wp', 'gktpp_get_hints');
function gktpp_get_hints() {
	$send_hints = new GKTPP_Send_Hints();
    $hints = $send_hints->send_resource_hints();
}
