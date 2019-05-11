<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    // require_once GKTPP_PLUGIN_DIR . '/class-gktpp-wp-list-table.php';
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'WP_Posts_List_Table' ) ) {
    // require_once GKTPP_PLUGIN_DIR . '/class-gktpp-wp-list-table.php';
    require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );
}



class GKTPP_Table extends WP_Posts_List_Table {

    public $_column_headers;
    public $_hints_per_page;
    private $_sql;
    public $_table;

	public function __construct() {
		parent::__construct( array(
			'singular' => 'url',
			'plural'   => 'urls',
			'ajax'     => false
		) );
    }

    public function prepare_items() {
		if ( ! is_admin() ) {
			exit;
        }

        $this->get_proper_data();
        
        if ( isset( $_GET['updated'] ) ) {
            $result = $_GET['updated'];
            $this->show_update_result('added', $result);
        } 
 
        if (gktpp_check_pp_admin()) {
            echo '<form method="post" action="' . admin_url('admin.php?page=gktpp-plugin-settings') . '"' . ' style="margin-top: 20px;">';
        }

        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        $current_page = $this->get_pagenum();
        $total_items  = $this->url_count();         // need to fix

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $this->_hints_per_page
        ) );

        $this->items = $this->create_table( $this->_hints_per_page, $current_page );

        $this->display();

        if (gktpp_check_pp_admin()) {
            echo '</form>';
            $table = new GKTPP_Enter_Data();
            $table->create_new_hint_table();
        }
        
    }

    public function get_proper_data() {
        global $wpdb;

        $this->_table = $wpdb->prefix . 'gktpp_table';

        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option( 'per_page', 'option' );
        $total_hints = get_user_meta( $user, $option, true );
        $this->_hints_per_page = ($total_hints) ? $total_hints : 10;

        // if ( gktpp_check_pp_admin() ) {
            $this->_sql = "SELECT * FROM $this->_table";
        // } 
    }

	public function create_table( $per_page, $page_number = 1 ) {
		global $wpdb;

        if (! empty($this->_sql)) {
            if ( ! empty( $_REQUEST['orderby'] ) ) {
                $this->_sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
                $this->_sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
            }
    
            $this->_sql .= " LIMIT $per_page";
            $this->_sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
            $result = $wpdb->get_results( $this->_sql, ARRAY_A );
        } 
        else {
            $result = $wpdb->get_results( $this->_sql, ARRAY_A );
        }

	    return $result;
	}

	public function get_columns() {
        $columns = array(
            'cb'			=> '<input type="checkbox" />',
            'url'			=> __( 'URL', 'gktpp' ),
            'hint_type'		=> __( 'Hint Type', 'gktpp' ),
            'status'		=> __( 'Status', 'gktpp' ),
            'post_name'		=> __( 'Post Name', 'gktpp' ),
            'created_by'	=> __( 'Created By', 'gktpp' )
        );

        if ( gktpp_check_pp_admin() ) {
            $columns['post_name'] = __( 'Post Name', 'gktpp' );
        }

        return $columns;
	}

	public function update_hints( $action, $hint_ids ) {
        global $wpdb;
        $concat_ids = implode( ',', array_map( 'absint', $hint_ids ) );

        if ($action === 'deleted') {
            $sql = "DELETE FROM $this->_table WHERE id IN ($concat_ids)";
        } elseif ($action === 'enabled') {
            $sql = "UPDATE $this->_table SET status = 'Enabled' WHERE id IN ($concat_ids)";
        } elseif ($action === 'disabled') {
            $sql = "UPDATE $this->_table SET status = 'Disabled' WHERE id IN ($concat_ids)";
        }

        if (!empty($sql) > 0) {
            $wpdb->query($sql);
        }

        $this->show_update_result($action, 'success');

        // add notice right here to see if db call was good/not
	}

	public function url_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM $this->_table";
		return $wpdb->get_var( $sql );
	}

	public function no_items() {
		esc_html_e( 'Enter a URL or domain name..', 'gktpp' );
	}

	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'url': return $item['url'];
			case 'hint_type': return $item['hint_type'];
			case 'status': return $item['status'];
            case 'post_name': return $item['post_id'];
            case 'created_by': return $item['created_by'];
			default: return esc_html_e( 'Error', 'gktpp' );
		}
	}

	public function column_cb( $id ) {
		return sprintf( '<input type="checkbox" name="urlValue[]" value="%1$s" />', $id['id'] );
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'url'			=> array( 'url', true ),
			'hint_type' 	=> array( 'hint_type', false ),
            'status'    	=> array( 'status', false ),
            'post_name'    	=> array( 'post_name', false )
		);

		return $sortable_columns;
    }



	public function get_bulk_actions() {
		$actions = array(
			'deleted'  => __( 'Delete', 'gktpp' ),
			'enabled'  => __( 'Enable', 'gktpp' ),
			'disabled' => __( 'Disable', 'gktpp' )
		);
		return $actions;
	}

	private function process_bulk_action() {
		if ( ! isset( $_POST['urlValue'] ) ) {
			return;
        } 

		$hint_ids = filter_input( INPUT_POST, 'urlValue', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
        $action = $this->current_action();

		if ( ( is_array( $hint_ids ) ) ) {
            return $this->update_hints( $action, $hint_ids );
		}
	}

    public static function show_update_result($action, $result) {
        if ($result === 'success') {
            $msg = "Resource hints $action successfully.";
        } else {
            $msg = "Resource hints failed to update. Please try again or submit a bug report in the form below.";
        }
        
        echo '<div class="inline notice notice-' . $result . ' is-dismissible"><p>' . esc_html($msg) . '</p></div>';
	}

}
