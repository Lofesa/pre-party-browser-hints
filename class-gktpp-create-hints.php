<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Create_Hints {

    public $results;

    // public function __construct() {
    //     add_action( "admin_init", array( $this, 'save_data' ) );
    // }

	public function create_hint($url, $hint_type, $post_id) {

        global $wpdb;
        $this->table = $wpdb->prefix . 'gktpp_table';
        

        if (!empty($post_id)) {
            $this->post_id = $post_id;
        } elseif (isset( $_POST['UseOnHomePostsOnly'] )) {
            $this->post_id = '-1';
        } else {
            $this->post_id = '0';
        }

        $this->sanitize_data($url, $hint_type);

        if ( preg_match( '/(DNS-Prefetch|Preconnect)/', $this->hint_type ) ) {
            $this->parse_for_domain_name();
        }


        $this->set_attributes();
        $this->create_str();         
        
        if ( $this->post_id === '0') {
            $this->remove_duplicate_post_hints();
        }
		
        $this->insert_hints();
        return $this->results;
        //  wp_safe_redirect( admin_url( "admin.php?page=gktpp-plugin-settings$results" ));

    }

    public function sanitize_data($url, $hint_type) {
        $this->url = filter_var(str_replace(' ', '', $url), FILTER_SANITIZE_URL);
        $this->hint_type = preg_replace('/[^%A-z-]/', '', $hint_type);
        return $this->post_id = preg_replace('/[^%0-9]/', '', $this->post_id);
    }

    public function parse_for_domain_name() {
        if ( preg_match( '/(http|https)/i', $this->url ) ) {
            return $this->url = parse_url( $this->url, PHP_URL_SCHEME ) . '://' . parse_url( $this->url, PHP_URL_HOST );
        } elseif ( substr( $this->url, 0, 2 ) === '//' ) {
            return $this->url = '//' . parse_url( $this->url, PHP_URL_HOST );
        } else {
            return $this->url = '//' . parse_url( $this->url, PHP_URL_PATH );
        }
    }

    public function set_attributes() {
		$basename = pathinfo( $this->url )['basename'];

		$file_type = strlen( strpbrk( $basename, '?' ) ) > 0
			? strrchr( explode( '?', $basename )[0], '.' ) 
            : strrchr( $basename, '.' );

        $this->crossorigin = ( preg_match('/fonts.(googleapis|gstatic).com/i', $this->url) || preg_match( '/(.woff|.woff2|.ttf|.eot)/', $file_type ) ) ? ' crossorigin' : '';

        if ( preg_match( '/(.woff|.woff2|.ttf|.eot)/', $file_type ) ) {
            $this->as_attr = 'font';
        } elseif ($file_type === '.js') {
            $this->as_attr = 'script';
        } elseif ($file_type === '.css') {
            $this->as_attr = 'style';
        } elseif ($file_type === '.mp3') {
            $this->as_attr = 'audio';
        } elseif ($file_type === '.mp4') {
            $this->as_attr = 'video';
        } elseif (preg_match( '/(.jpg|.jpeg|.png|.svg|.webp)/', $file_type )) {
            $this->as_attr = 'image';
        } elseif ($file_type === '.vtt') {
            $this->as_attr = 'track';
        } elseif ($file_type === '.swf') {
            $this->as_attr = 'embed';
        } else {
            $this->as_attr = '';
        }

        if ($file_type === '.woff') {
            $this->type_attr = 'font/woff';
        } elseif ($file_type === '.woff2') {
            $this->type_attr = 'font/woff2';
        } elseif ($file_type === '.ttf') {
            $this->type_attr = 'font/ttf';
        } elseif ($file_type === '.eot') {
            $this->type_attr = 'font/eot';
        } else {
            $this->type_attr = '';
        }
        return $this;
    }

	public function create_str() {
        $lower_case_hint = strtolower( $this->hint_type );

        $this->head_str = '<link href="' . $this->url . '" rel="' . $lower_case_hint . '"';
        $this->header_str = "<$this->url>; rel=\"$lower_case_hint\"";

        if (!empty($this->as_attr)) {
            $this->head_str .= " as=\"$this->as_attr\"";
            $this->header_str .= " as=$this->as_attr;";
        }

        if (!empty($this->type_attr)) {
            $this->head_str .= " type=\"$this->type_attr\"";
            $this->header_str .= " type=$this->type_attr;";
        }

        if (!empty($this->crossorigin)) {
            $this->head_str .= $this->crossorigin;
            $this->header_str .= $this->crossorigin . ';';
        }

        $this->head_str .= '>';
    
        $lastSemiColonPos = strrpos($this->header_str, ';');

		if ( $lastSemiColonPos === (strlen($this->header_str) - 2) ) {		// replace the last semi-colon and replace it with a comma.
			$this->header_str = substr( $this->header_str, 0, $lastSemiColonPos) . ',';
		}

		return $this;
	}

    public function remove_duplicate_post_hints() {
        global $wpdb;
        $url2 = "'" . $this->url . "'";
        $hint2 = "'" . $this->hint_type . "'";
        $sql = "SELECT COUNT(*) FROM $this->table WHERE hint_type = $hint2 AND url = $url2";
        $count = $wpdb->get_var( $sql );

        if ($count > 0) {
            $wpdb->delete( $this->table, array(
                'url'           => $this->url,
                'hint_type'     => $this->hint_type
            ), array( '%s', '%s' ) );

            // return $this->result = '&removedDupPostHint=success';

            // return $this->result['removedDupPostHint'] = true;
            return $this->results .= '&removedDupPostHint=true';
        }
    }

    public function insert_hints() {
        global $wpdb;
        $current_user = wp_get_current_user()->display_name;

        $this->autoset = ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? 1 : 0;

        $wpdb->insert( $this->table, array(
            'url'           => $this->url,
            'hint_type'     => $this->hint_type,
            'ajax_domain'   => $this->autoset,
            'as_attr'       => $this->as_attr,
            'type_attr'     => $this->type_attr,
            'crossorigin'   => $this->crossorigin,
            'header_string' => $this->header_str,
            'head_string'   => $this->head_str,
            'post_id'       => $this->post_id,
            'created_by'    => $current_user ) );

        return $this->result['added'] = 'success';
        // return $this->results = 'success';

    }

}
