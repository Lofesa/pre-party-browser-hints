<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Send_Hints {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'send_resource_hints' ), 1, 0 );
	}

	public function send_resource_hints() {
        global $wpdb;
        global $post;
        
        $post_ID = (string) ($post->ID);

        if (is_home()) {
            $post_ID = '-1';
        }

		$table = $wpdb->prefix . 'gktpp_table';
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status = %s AND post_id = %s OR post_id = %s", 'Enabled', $post_ID, '0'), OBJECT );

		if ( count( $links ) < 1 || ( ! is_array( $links ) ) ) {
			return;
		}
        
        $resourceHintStr = array( 
            'header_string' => '',
            'head_string' => ''
        );

		foreach ( $links as $key => $value ) {
            $resourceHintStr['header_string'] .= $value->header_string;
            $resourceHintStr['head_string'] .= $value->head_string;
		}

        return $resourceHintStr;
	}
}


function gktpp_send_hints() {
	$send_hints = new GKTPP_Send_Hints();
    return $send_hints->send_resource_hints();
    
    // return new GKTPP_Send_Hints();
}

get_option( 'gktpp_send_in_header' ) === 'HTTP Header'
    ? header( 'Link:' . gktpp_send_hints()['header_string'] ) 
    : add_action( 'wp_head', function() { 
        return printf( gktpp_send_hints()['head_string'] ); 
}, 1, 0 );