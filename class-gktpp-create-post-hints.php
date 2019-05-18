<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTTP_Posts {

    public function __construct() {
        $this->meta_preconnect_key = 'gktpp_preconnect_reset';
        $this->data = [];

        add_action( 'add_meta_boxes', array( $this, 'create_meta_box') );
        add_action( 'save_post', array( $this, 'save_hints') );     // change to 'update_post' ?
    }

    public function create_meta_box($a) {

        global $wpdb;
        $table = $wpdb->prefix . 'gktpp_table';

        $post_id = (string) $_GET['post'];
        $sql = "SELECT * FROM $table WHERE post_id = $post_id OR post_id = 0";

        $this->data = $wpdb->get_results($sql, ARRAY_A);

        $id = 'gktpp_post_meta';
		$title =  'Pre* Party Resource Hints';
        // $callback = array( $this, 'create_pp_meta_box' );
        $callback = array( $this, 'create_pp_meta_box' );
		$context = 'normal';
		$priority = 'high';
		$callback_args = '';
        $screens = array( 'post', 'page' );

		foreach ( $screens as $screen ) {
			add_meta_box( $id, $title, $callback, $screen, $context,
                $priority, $callback_args, 
                array( '__block_editor_compatible_meta_box' => false, ) );
        }
    }


    public function create_pp_meta_box() {
        $title = get_the_title();
        ?>
            <h3>Resource Hints Used on <?php echo $title; ?></h3>

            <?php
                $gktpp_table = new GKTPP_Table();
                $gktpp_table->prepare_items();
                $gktpp_table->display();

                $newEnterData = new GKTPP_Enter_Data();
                $newEnterData->create_new_hint_table();
            ?>

            <br/>

            <input id="gktppPageReset" class="button button-primary" type="button" value="Reset Post Preconnect Hints"/>
            <br/>
            <input size="100" type="text" name="gktpp_post_reset" id="gktppPageResetValue" class="gktppHidden" value=""/>
            <input size="100" type="text" name="gktpp_update_hints" id="gktppUpdateHints" class="gktppHidden" value=""/>
            <input size="100" type="text" name="gktpp_insert_hints" id="gktppInsertedHints" class="gktppHidden" value=""/>

        <?php

    }

    public function save_hints($post_id) {

        // if ( $_POST['gktpp_post_reset'] || $_POST['gktpp_update_hints'] || $_POST['gktpp_insert_hints'] ) {
        //     check_admin_referer( 'gktpp_settings', 'gktpp_post_nonce' );
        // }
        // 
        // wp_verify_nonce('gktpp_post_nonce2', 'save_post');

        if ( $_POST['gktpp_post_reset'] ) {
            update_post_meta($post_id, $this->meta_preconnect_key, 'notset');
        }

        if ( $_POST['gktpp_update_hints'] ) {
            global $wpdb;
            $table = $wpdb->prefix . 'gktpp_table';
            
            $update_hints = json_decode(stripslashes($_POST['gktpp_update_hints']));
            $update_action = $update_hints->action;
            $ids = implode( ',', array_map( 'absint', $update_hints->hintIDs ) );
            $sql = "";

            if ($update_action === 'deleted') {
                $sql = "DELETE FROM $table WHERE id IN ($ids)";
            } elseif ($update_action === 'enabled') {
                $sql = "UPDATE $table SET status = 'Enabled' WHERE id IN ($ids)";
            } elseif ($update_action === 'disabled') {
                $sql = "UPDATE $table SET status = 'Disabled' WHERE id IN ($ids)";
            } 

            if (!empty($sql)) {
                $wpdb->query($sql);
            }
        } 
        
        if ( $_POST['gktpp_insert_hints'] ) {
            global $wpdb;
            $table = $wpdb->prefix . 'gktpp_table';

            $new_hint = json_decode(stripslashes($_POST['gktpp_insert_hints']));
            $create_Hints = new GKTPP_Create_Hints();
            $create_Hints->create_hint( $new_hint->url, $new_hint->type, $post_id);

        }

    }
    
}

new GKTTP_Posts();
