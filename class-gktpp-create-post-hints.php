<?php

if (! defined('ABSPATH')) {
    exit;
}

class GKTTP_Posts {

    public function __construct() {
        $this->meta_preconnect_key = 'gktpp_preconnect_reset';
        add_action('add_meta_boxes', array($this, 'create_meta_box'));

        // add_action('edit_post', array($this, 'save_hints'));
        // add_action('pre_post_update', array($this, 'save_hints'), 10, 1);
        add_action('save_post', array($this, 'save_hints'), 10, 1);
    }

    public function create_meta_box() {
        $id = 'gktpp_post_meta';
        $title =  'Pre* Party Resource Hints';
        $callback = array( $this, 'create_pp_meta_box' );
        $context = 'normal';
        $priority = 'high';
        $callback_args = '';
        $screens = get_post_types();

        foreach ($screens as $screen) {
            add_meta_box(
                $id, 
                $title, 
                $callback, 
                $screen, 
                $context, 
                $priority, 
                $callback_args
                // array('__block_editor_compatible_meta_box' => false)
            );
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
            <div style="text-align: center;">
                <input id="gktppPageReset" class="button button-primary" type="submit" value="Reset Post Preconnect Hints?"/>
            </div>

            <br/>
            <input size="50" type="hidden" name="gktpp_post_reset" id="gktppPageResetValue" value=""/>
            <input size="50" type="hidden" name="gktpp_update_hints" id="gktppUpdateHints" value=""/>
            <input size="50" type="hidden" name="gktpp_insert_hints" id="gktppInsertedHints" value=""/>

            <!-- <p id="gktppSavePostMsg" style="text-align: center; font-style: italic;">Please save this post to allow your changes to take effect.</p>  -->
        <?php


    }

    public function save_hints($post_id) {

        if ( !empty($_POST['gktpp_post_reset']) || !empty($_POST['gktpp_update_hints']) || !empty($_POST['gktpp_insert_hints']) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'gktpp_table';
            // check_admin_referer( 'gktpp_settings', 'gktpp_post_nonce' );

            if ($_POST['gktpp_post_reset']) {
                update_post_meta($post_id, $this->meta_preconnect_key, 'notset');
            }
    
            if ($_POST['gktpp_update_hints']) {
                
                $update_hints = json_decode(stripslashes($_POST['gktpp_update_hints']));
                $update_action = $update_hints->action;
                $ids = implode(',', array_map('absint', $update_hints->hintIDs));
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
            
            if ( $_POST['gktpp_insert_hints']) {
                $new_hint = json_decode(stripslashes($_POST['gktpp_insert_hints']));
                $create_Hints = new GKTPP_Create_Hints();
                $create_Hints->create_hint($new_hint->url, $new_hint->type, $post_id);
            }

        }
        // 
        // wp_verify_nonce('gktpp_post_nonce2', 'save_post');

    }
    
}

new GKTTP_Posts();
