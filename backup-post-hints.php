<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTTP_Posts {

    public function __construct() {
        $this->meta_preconnect_key = 'gktpp_preconnect_reset';
        $this->data = [];

        add_action( 'add_meta_boxes', array( $this, 'create_meta_box') );
        add_action( 'save_post', array( $this, 'save_hints') );
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
        $callback = array( $this, 'create_pp_meta_box2' );
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
        $post_hints = $this->data;
        ?>

            <h3>Resource Hints Used on <?php echo $title; ?> </h3>

            <select name="" id="gktpp-option-select">
                <option value="-1">Bulk Actions</option>
                <option value="delete">Delete</option>
                <option value="enable">Enable</option>
                <option value="disable">Disable</option>
            </select>

            <input type="button" id="gktppApply" class="button action" value="Apply">

            <table id="gktpp-post-table" class="wp-list-table widefat fixed striped urls">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input type="checkbox"/>
                        </td>
                        <th scope="col">URL</th>
                        <th scope="col">Hint Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Post ID</th>
                    </tr>
                </thead>
                <tbody>

                <?php 
                    $row_count = 0;
                    foreach ($post_hints as $post_hint => $value) {
                        if (!empty($value['id'])) {
                            $str = '<tr><th scope="row" class="check-column"><input value="' . $value['id'] . '" type="checkbox"/></th>'; 
                            $str .= "<td>" . $value['url'] . "</td>";
                            $str .= "<td>" . $value['hint_type'] . "</td>";
                            $str .= "<td>" . $value['status'] . "</td>";
                            $str .= "<td>" . $value['post_id'] . "</td></tr>";
                            echo $str;
                            $row_count++;
                        }
                    }

                    if (strlen($str) === 0) {
                        echo "No custom hints on this page.";
                    }
                ?>

                </tbody>
            </table>

            <br/>
            <br/>
            <input id="gktppPageReset" class="button button-primary" type="button" value="Reset Post Preconnect Hints"/>
            <br/>

            <input size="100" type="text" name="gktpp_post_reset" id="gktppPageResetValue" class="gktppHidden" value=""/>
            <input size="100" type="text" name="gktpp_update_hints" id="gktppUpdateHints" class="gktppHidden" value=""/>
            <input size="100" type="text" name="gktpp_insert_hints" id="gktppInsertedHints" class="gktppHidden" value=""/>

        <?php
        $data = new GKTPP_Enter_Data();
        $data->create_new_hint_table();

    }

    public function create_pp_meta_box2() {

        $gktpp_table = new GKTPP_Table();
        $gktpp_table->prepare_items();

    }


    public function save_hints($post_id) {

        // if ( $_POST['gktpp_post_reset'] || $_POST['gktpp_update_hints'] || $_POST['gktpp_insert_hints'] ) {
        //     check_admin_referer( 'gktpp_settings', 'gktpp_post_nonce' );
        // }

        if ( $_POST['gktpp_post_reset'] ) {
            update_post_meta($post_id, $this->meta_preconnect_key, 'notset');
        }

        if ( $_POST['gktpp_update_hints'] ) {
            global $wpdb;
            $table = $wpdb->prefix . 'gktpp_table';
            
            $update_hints = json_decode(stripslashes($_POST['gktpp_update_hints']));
            $action = $update_hints->action;
            $ids = implode( ',', array_map( 'absint', $update_hints->hintIDs ) );
            $sql = "";

            if ($action === 'delete') {
                $sql = "DELETE FROM $table WHERE id IN ($ids)";
            } elseif ($action === 'enable') {
                $sql = "UPDATE $table SET status = 'Enabled' WHERE id IN ($ids)";
            } elseif ($action === 'disable') {
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
