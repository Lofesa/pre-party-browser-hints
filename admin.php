<?php
/**
 * Plugin Name: Pre* Party Resource Hints
 * Plugin URI: https://wordpress.org/plugins/pre-party-browser-hints/
 * Description: Take advantage of the browser resource hints DNS-Prefetch, Prerender, Preconnect, Prefetch, and Preload to improve page load time.
 * Version: 2.0.0
 * Author: Sam Perrow
 * Author URI: https://www.linkedin.com/in/sam-perrow
 * License: GPL2
 * last edited May 23, 2019
 *
 * Copyright 2016  Sam Perrow  (email : sam.perrow399@gmail.com)
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* 
To do:
- security
- Turn form boxes on PP page into real modal boxes
- add option to clear auto preconnect hints from home-posts page.
- Home page hint options
- Create v 2.0.0 info/updates tab

feedback:
1. 'disable auto WP RH's' option not working? (only in header?)
2. option to show/hide certain columns not working
3. add notice to indicate if a given hint is not legit (ex- domain name for a preload)
4. add notices when a redundant hint attempts to be added, or when redudant hints are removed.


bugs:
1. global 'reset preconnect hints' not working
2. reset hints for home page w/ only posts not working or loading hints after
3. clean up header hint str

*/

// prevent direct file access
if (! defined('ABSPATH')) {
    exit;
}

define('GKTPP_PLUGIN', __FILE__);
define('GKTPP_VERSION', '2.0.0');
define('GKTPP_PLUGIN_DIR', untrailingslashit(dirname(GKTPP_PLUGIN)));
define('GKTPP_CHECK_PAGE', GKTPP_Check_PP_admin());


function GKTPP_Check_PP_admin() {
    global $pagenow;

    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'gktpp-plugin-settings') {
        return 'ppAdmin';
    } elseif ($pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        return 'postEdit';
    }
    
}


add_action('init', 'gktppInitialize');

function gktppInitialize() {

    if (is_admin()) {
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-create-hints.php';
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-options.php';
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-info.php';
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-table.php';
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-enter-data.php';
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-create-post-hints.php';
    } else {
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-send-hints.php';
    }

    // this needs to be loaded front end and back end bc Ajax needs to be able to communicate between the two.
    if ((get_option('gktpp_autoload_preconnects') === 'true')) {
        include_once GKTPP_PLUGIN_DIR . '/class-gktpp-ajax.php';
    }
}



// register and call the CSS and JS we need only on the needed page
add_action('admin_menu', 'gktpp_register_admin_files');

function gktpp_register_admin_files() {
    global $pagenow;
    
    wp_register_style('gktpp_styles_css', plugin_dir_url(__FILE__) . 'css/styles.css', null, GKTPP_VERSION, 'all');
    wp_register_script('gktpp_admin_js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), GKTPP_VERSION, true);

    if (preg_match('/ppAdmin|postEdit/', GKTPP_CHECK_PAGE)) {
        wp_enqueue_script('gktpp_admin_js');
        wp_enqueue_style('gktpp_styles_css');
    }
}


// multisite install/delete db table
register_activation_hook(__FILE__, 'gktpp_install_db_table');
add_action('wpmu_new_blog', 'gktpp_install_db_table');

function gktpp_install_db_table() {
    global $wpdb;

    add_option('gktpp_autoload_preconnects', 'true', '', 'yes');
    add_option('gktpp_send_in_header', 'false', '', 'yes');
    add_option('gktpp_disable_wp_hints', 'false', '', 'yes');
    add_option('gktpp_reset_home_posts', 'notset', '', 'yes');


    $table = $wpdb->prefix . 'gktpp_table';
    $charset_collate = $wpdb->get_charset_collate();

    if (! function_exists('dbDelta')) {
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $siteTableNames = array( $table );

    if (is_multisite()) {
        $blogTable = $wpdb->base_prefix . 'blogs';
        $data = $wpdb->get_results("SELECT blog_id FROM $blogTable WHERE blog_id != 1;");

        if ($data) {
            foreach ($data as $object) {
                $sitePpTable = $wpdb->base_prefix . $object->blog_id . '_gktpp_table';
                array_push($siteTableNames, $sitePpTable);
            }
        }
    } 

    foreach ($siteTableNames as $siteTableName) {

        $sql = "CREATE TABLE $siteTableName (
            id INT(9) NOT NULL AUTO_INCREMENT,
            url VARCHAR(255) DEFAULT '' NOT NULL,
            hint_type VARCHAR(55) DEFAULT '' NOT NULL,
            status VARCHAR(55) DEFAULT 'Enabled' NOT NULL,
            as_attr VARCHAR(55) DEFAULT '',
            type_attr VARCHAR(55) DEFAULT '',
            crossorigin VARCHAR(55) DEFAULT '',
            ajax_domain TINYINT(1) DEFAULT 0 NOT NULL,
            header_string VARCHAR(255) DEFAULT '' NOT NULL,
            head_string VARCHAR(255) DEFAULT '' NOT NULL,
            post_id VARCHAR(55) DEFAULT '0' NOT NULL,
            created_by VARCHAR(55) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql, true);
    }

}


add_action('delete_blog', 'gktpp_remove_ms_db_table');

function gktpp_remove_ms_db_table( $blog_id ) {
    global $wpdb;

    if ( is_multisite() ) {
        $table_name = $wpdb->base_prefix . $blog_id . '_gktpp_table';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gktpp_set_admin_links');

function gktpp_set_admin_links( $links ) {
    $gktpp_links = array(
        '<a href="https://github.com/samperrow/pre-party-browser-hints">View on GitHub</a>',
        '<a href="https://www.paypal.me/samperrow">Donate</a>' );
    return array_merge($links, $gktpp_links);
}


// implement option to disable automatically generated resource hints
add_action('wp_head', 'gktpp_disable_wp_hints', 1, 0);

function gktpp_disable_wp_hints() {
    $option = get_option('gktpp_disable_wp_hints');

    if ($option === 'true') {
        remove_action('wp_head', 'wp_resource_hints', 2);
    }
}

?>