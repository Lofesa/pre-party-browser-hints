<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Send_Hints {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'send_resource_hints' ), 1, 0 );
	}

	public function send_resource_hints() {
		if (function_exists( 'is_amp_endpoint' ) && is_amp_endpoint()) { return;}
		global $wpdb;
		$table = $wpdb->prefix . 'gktpp_table';
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status = %s", 'Enabled'), OBJECT );

		if ( count( $links ) < 1 || ( ! is_array( $links ) ) ) {
			return;
		}
		
		$destination = get_option( 'gktpp_send_in_header' );
		$resourceHintStr = '';

		foreach ( $links as $key => $value ) {
			$resourceHintStr .= ( $destination === 'HTTP Header' )
				? $value->header_string
				: $value->head_string;
		}

		return $resourceHintStr;
	}
}

function gktpp_send_hints() {
	$send_hints = new GKTPP_Send_Hints();
	return $send_hints->send_resource_hints();
}

get_option( 'gktpp_send_in_header' ) === 'HTTP Header'
	? header( 'Link:' . gktpp_send_hints() ) 
	: add_action( 'wp_head', function() { printf( gktpp_send_hints() ); }, 1, 0 );
