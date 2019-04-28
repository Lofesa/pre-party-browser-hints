<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Ajax {

	public function __construct() {
        // $this->post_reset_meta_key = 'gktpp_post_reset';
        $this->post_preconnect_reset = 'gktpp_preconnect_reset';
        // $this->post_hints_meta_key = 'gktpp_post_hints';
		add_action( 'wp_footer', array( $this, 'add_domain_js' ), 10, 0 );
        add_action( 'wp_ajax_gktpp_post_domain_names', array( $this, 'gktpp_post_domain_names' ) );
        add_action( 'wp_ajax_nopriv_gktpp_post_domain_names', array( $this, 'gktpp_post_domain_names' ) );
	}

	public function add_domain_js() {
        global $wp_query;
        $post_ID = $wp_query->queried_object_id;

        if (is_home() ) {
            $post_ID = 0;
        }

        $reset_hints = get_post_meta( $post_ID, $this->post_preconnect_reset, true );

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
            $post_ID = isset( $_POST['postID'] ) ? json_decode( wp_unslash( $_POST['postID'] ) ) : '';

			if ( is_array( $urls ) ) {

				$wpdb->delete( $table, array( 
                    'ajax_domain' => 1,
                    'post_ID'     => $post_ID
                ), array( '%s', '%s' ) );

                $global_hints = array();
                $prev_hints = $wpdb->get_results("SELECT url FROM $table WHERE post_ID = $post_ID", ARRAY_A );

                foreach ($prev_hints as $hint => $val) {
                    array_push( $global_hints, $val['url'] );
                }

                $current_user = wp_get_current_user()->display_name;

				foreach ( $urls as $key => $url ) {

                    if ( ! in_array( $url, $global_hints ) ) {
                        $gktpp_insert_to_db = new GKTPP_Create_Hints();
                        $gktpp_insert_to_db->get_attributes( $url );
                        $gktpp_insert_to_db->check_for_crossorigin( $url );
    
                        $as_attr = $gktpp_insert_to_db->as_attr;
                        $type_attr = $gktpp_insert_to_db->type_attr;
                        $crossorigin = $gktpp_insert_to_db->crossorigin;
    
                        $gktpp_insert_to_db->create_str( $url, 'Preconnect', $as_attr, $type_attr, $crossorigin );
    
                        $header_string = $gktpp_insert_to_db->header_str;
                        $head_string = $gktpp_insert_to_db->head_str;

    
                        $wpdb->insert( $table, array(
                                                'url' => $url,
                                                'hint_type' => 'Preconnect',
                                                'ajax_domain' => 1,
                                                'as_attr' => $as_attr,
                                                'type_attr' => $type_attr,
                                                'crossorigin' => $crossorigin,
                                                'header_string' => $header_string,
                                                'head_string' => $head_string,
                                                'post_id' => $post_ID,
                                                'created_by' => $current_user ),
    
                                                array(
                                                    '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
                    }
				}
			}
            update_post_meta( $post_ID, $this->post_preconnect_reset, 'set');

            // $post_hints = $wpdb->get_results("SELECT * FROM $table WHERE post_ID = $post_ID", ARRAY_A );
            // $json = json_encode($post_hints);

            // update_post_meta( $post_ID, $this->post_hints_meta_key, $json);
			wp_die();
		} else {
			wp_safe_redirect( get_permalink( wp_unslash( $_REQUEST['post_id'] ) ) );
			exit();
		}
	}
}

new GKTPP_Ajax();
