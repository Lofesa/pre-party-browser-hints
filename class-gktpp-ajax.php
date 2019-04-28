<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Ajax {

	public function __construct() {

        $this->post_preconnect_reset = 'gktpp_preconnect_reset';

		add_action( 'wp_footer', array( $this, 'add_domain_js' ), 10, 0 );
        add_action( 'wp_ajax_gktpp_post_domain_names', array( $this, 'gktpp_post_domain_names' ) );
        add_action( 'wp_ajax_nopriv_gktpp_post_domain_names', array( $this, 'gktpp_post_domain_names' ) );
	}

	public function add_domain_js() {
        global $wp_query;
        $post_ID = (string) $wp_query->queried_object_id;

        if (is_home()) {
            $post_ID = '-1';
            $reset_hints = get_option('gktpp_reset_home_posts');
        } else {
            $reset_hints = get_post_meta( $post_ID, $this->post_preconnect_reset, true );
        }

        if ( empty($reset_hints) || $reset_hints === 'notset' ) {
            wp_register_script( 'gktpp-find-domain-names', plugins_url( '/pre-party-browser-hints/js/find-external-domains.js' ), null, GKTPP_VERSION, true );
            wp_localize_script( 'gktpp-find-domain-names', 'post', array( 'postID' => $post_ID ) );
            wp_enqueue_script( 'gktpp-find-domain-names' );
        }
	}

	public function gktpp_post_domain_names() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            global $wpdb;

	     	$table = $wpdb->prefix . 'gktpp_table';
            $urls = isset( $_POST['urls'] ) ? json_decode( wp_unslash( $_POST['urls'] ) ) : '';
            $post_ID = isset( $_POST['postID'] ) ? (string) json_decode( wp_unslash( $_POST['postID'] ) ) : '';

			if ( is_array( $urls ) ) {

				$wpdb->delete( $table, array( 
                    'ajax_domain' => 1,
                    'post_ID'     => $post_ID
                ), array( '%s', '%s' ) );

                $global_hints = array();
                $prev_hints = $wpdb->get_results("SELECT url FROM $table WHERE post_ID = $post_ID OR post_ID = '0'", ARRAY_A );


                foreach ($prev_hints as $hint => $val) {
                    array_push( $global_hints, $val['url'] );
                }

				foreach ( $urls as $key => $url ) {
                    $gktpp_insert_to_db = new GKTPP_Create_Hints();

                    if ( ! in_array( $url, $global_hints ) ) {
                        $gktpp_insert_to_db->prepare_data( $url, 'Preconnect', $post_ID);
                    }
				}
			}

            if ( $post_ID !== '-1' ) {
                update_post_meta( $post_ID, $this->post_preconnect_reset, 'set');
            } else {
                update_option( 'gktpp_reset_home_posts', 'set', 'yes');
            }

			wp_die();
		} else {
			wp_safe_redirect( get_permalink( wp_unslash( $_REQUEST['post_id'] ) ) );
			exit();
		}
	}
}

new GKTPP_Ajax();
