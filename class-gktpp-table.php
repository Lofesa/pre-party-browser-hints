<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GKTPP_WP_List_Table' ) ) {
    require_once( GKTPP_PLUGIN_DIR . '/class-gktpp-wp-list-table.php' );
}

class GKTPP_Table extends GKTPP_WP_List_Table {

    public $_column_headers;
    public $_hints_per_page;
    public $_table;
    public $_data;
    public $items;

	public function __construct() {
		parent::__construct( array(
			'singular' => 'url',
			'plural'   => 'urls',
			'ajax'     => false
		) );
    }

    public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
            case 'url': 
                return $item['url'];
            case 'hint_type': 
                return $item['hint_type'];
            case 'status': 
                return $item['status'];
            case 'post_name': 
                return $item['post_id'];
            case 'created_by': 
                return $item['created_by'];
            case 'id': 
                return $item['id'];
            default: 
                return esc_html_e( 'Error', 'gktpp' );
		}
    }
    


    public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="urlValue[]" value="%1$s" />', $item['id'] );
    }
    
    public function get_columns() {
        $columns = array(
            'cb'			=> '<input type="checkbox" />',
            'url'			=> __( 'URL', 'gktpp' ),
            'hint_type'		=> __( 'Hint Type', 'gktpp' ),
            'status'		=> __( 'Status', 'gktpp' ),
            'post_name'		=> __( 'Post Name', 'gktpp' ),
            'created_by'	=> __( 'Created By', 'gktpp' ),
            'id'	        => __( 'ID', 'gktpp' )
        );

        if ( GKTPP_ON_PP_ADMIN_PAGE ) {
            $columns['post_name'] = __( 'Post Name', 'gktpp' );
        }

        return $columns;
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
    
	public function process_bulk_action() {
		if ( ! isset( $_POST['urlValue'] ) ) {
			return;
        } 

		$hint_ids = filter_input( INPUT_POST, 'urlValue', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
        $action = $this->current_action();

		if ( ( is_array( $hint_ids ) ) ) {
            return $this->update_hints( $action, $hint_ids );
		}
	}

    public function prepare_items() {
		if ( ! is_admin() ) {
			exit;
        }

        global $wpdb;

        $this->_table = $wpdb->prefix . 'gktpp_table';

        $screen = get_current_screen();
        $option = $screen->get_option( 'per_page', 'option' );


        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        // $this->process_bulk_action();

        $user = get_current_user_id();

        $total_hints = get_user_meta( $user, $option, true );
        $this->_hints_per_page = ($total_hints) ? $total_hints : 10;

        $this->load_data();

        $current_page = $this->get_pagenum();
        $total_items = count($this->_data);
        $data = array_slice($this->_data,(($current_page-1)*$this->_hints_per_page), $this->_hints_per_page);

        $this->items = $data;


        $this->set_pagination_args( array(
            'total_items' => count($total_items),         // need to fix
            'per_page'    => $this->_hints_per_page,
            'total_pages' => ceil($total_items/$this->_hints_per_page)
        ) );

    }

	public function load_data() {
        global $wpdb;
        $per_page = $this->_hints_per_page;
        $current_page = $this->get_pagenum();

        $sql = "SELECT * FROM $this->_table";

        if (! empty($sql)) {
            if ( ! empty( $_REQUEST['orderby'] ) ) {
                $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
                $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
            }
    
            $sql .= " LIMIT $per_page";
            $sql .= ' OFFSET ' . ( $current_page - 1 ) * $per_page;
            $this->_data = $wpdb->get_results( $sql, ARRAY_A );
        } 
        else {
            $this->_data = $wpdb->get_results( $sql, ARRAY_A );
        }

	    return $this->_data;
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

        if (!empty($sql)) {
            $wpdb->query($sql);
        }

        return $this->show_update_result($action, 'success');
    }
    
	public function no_items() {
		esc_html_e( 'Enter a URL or domain name..', 'gktpp' );
    }
    
    public function show_update_result($action, $result) {
        if ($result === 'success') {
            $msg = "Resource hints $action successfully.";
        } else {
            $msg = "Resource hints failed to update. Please try again or submit a bug report in the form below.";
        }
        
        echo '<div class="inline notice notice-' . $result . ' is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
    

    // possible to implement this in the future...
    // function column_url($item) {

    //     $actions = array(
    //         'edit'      => sprintf('<a href="?page=%s&action=%s&hint=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
    //         'delete'    => sprintf('<a href="?page=%s&action=%s&hint=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
    //     );
        
    //     //Return the title contents
    //     return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
    //         /*$1%s*/ $item['url'],
    //         /*$2%s*/ $item['id'],
    //         /*$3%s*/ $this->row_actions($actions)
    //     );

    // }

}
