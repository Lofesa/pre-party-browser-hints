<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GKTPP_Options {

	public function __construct() {
        add_action( 'admin_menu', array( $this, 'settings_page_init' ) );
        add_filter( 'set-screen-option', array( $this, 'apply_wp_screen_options' ), 1, 3 );

    }
    
	public function settings_page_init() {

        $settings_page = add_menu_page(
            'Pre* Party Settings',
            'Pre* Party',
            'manage_options',
            'gktpp-plugin-settings',
            array( $this, 'settings_page' ),
            plugins_url( '/pre-party-browser-hints/images/lightning.png' )
        );

        if (GKTPP_CHECK_PAGE === 'ppAdmin') {
            add_action( "load-{$settings_page}", array( $this, 'screen_option' ) );
            add_action( "load-{$settings_page}", array( $this, 'save_data' ) );
        } 
        else {
            add_action( "load-post.php", array( $this, 'screen_option' ) );
        }
        
    }

    public function save_data() {
        if ( isset( $_POST['gktpp-settings-submit'] ) ) {

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
                return;
            }

            // check_admin_referer( 'gktpp-enter-data' );

            if ( GKTPP_CHECK_PAGE === 'ppAdmin') {

                if (isset( $_POST['hint_type']) && isset( $_POST['url'])) {
                    $create_hints = new GKTPP_Create_Hints();
                    $url_params = $create_hints->create_hint( $_POST['url'], $_POST['hint_type'], null );
                }

            } 
            wp_safe_redirect( admin_url( "admin.php?page=gktpp-plugin-settings$url_params" ));
            exit();
	    }
    }
    
	public function apply_wp_screen_options( $status, $option, $value ) {
        return ( 'gktpp_screen_options' === $option ) ? $value : $status;
	}

	public function show_admin_tabs( $current = 'insert-urls' ) {

        $current_tab = (isset( $_GET['tab'] )) ? $_GET['tab'] : 'insert-urls';

		$tabs = array(
            'insert-urls'   => 'Insert URLs',
            'info'          => 'Information'
        );

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab === $current_tab ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=gktpp-plugin-settings&tab=$tab'>" . esc_html( $name ) . "</a>";
		}
		echo '</h2>';
	}

	public function settings_page() {
		if ( ! is_admin() ) {
			exit;
		}
		
        echo '<div class="wrap">';
        // echo '<h2>Pre* Party Plugin Settings</h2>';
        $obj = get_current_screen();

        $this->show_admin_tabs();

        $this->display_admin_content();

        do_meta_boxes('toplevel_page_gktpp-plugin-settings', 'normal', $obj);

        echo '</div>';
    }

    public function display_list_table() {

        $gktpp_table = new GKTPP_Table();
        $gktpp_table->prepare_items();

        if ( GKTPP_CHECK_PAGE === 'ppAdmin') {
            echo '<form id="gktpp-list-table" method="post" action="' . admin_url( 'admin.php?page=gktpp-plugin-settings' ) . '">';
            // echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
            $gktpp_table->display();
            echo '</form>';
        } 
    }

    public function add_conditional_form_elem() {
        $gktpp_Enter_Data = new GKTPP_Enter_Data();

        if ( GKTPP_CHECK_PAGE === 'ppAdmin') {
            echo '<form id="gktpp-new-hint" method="post" action="' . admin_url( 'admin.php?page=gktpp-plugin-settings' ) . '">';
            $gktpp_Enter_Data->create_new_hint_table();
            echo '</form>';
        } 
    }
    
    public function display_admin_content() {

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'insert-urls';

        switch ( $tab ) {
            case 'insert-urls':
                $this->display_list_table();
                $this->add_conditional_form_elem();
                GKTPP_Enter_Data::show_info();
                // GKTPP_Enter_Data::contact_author();
            break;

            case 'info':
                $hint_info = new GKTPP_Hint_Info();
                $hint_info->resource_hint_nav();
            break;

            default:
                $this->display_list_table();
                $this->add_conditional_form_elem();

                GKTPP_Enter_Data::show_info();
                // GKTPP_Enter_Data::contact_author();
            break;
        }

        echo sprintf( __( 'Tip: test your website on <a href="%s">WebPageTest.org</a> to know which resource hints and URLs to insert.' ), __( 'https://www.webpagetest.org' ) );
    }

	public function screen_option() {

        $this->register_meta_boxes();

		$option = 'per_page';
		$args = array(
			'label'   => 'URLs',
			'default' => 10,
			'option'  => 'gktpp_screen_options'
		);

		add_screen_option( $option, $args );

		$this->resource_obj = new GKTPP_Table();
    }
    
    public function register_meta_boxes() {
        $id = 'gktpp_admin_meta';
		$title =  'Pre* Party Resource Hints2';
        $callback = array( $this, 'create_pp_meta_box2' );
        return add_meta_box( $id, $title, $callback, 'toplevel_page_gktpp-plugin-settings', 'normal', 'default', '' );
    }

    public function create_pp_meta_box2() {
        // $meta_boxes = do_meta_boxes('gktpp-plugin-settings', 'side');

        GKTPP_Enter_Data::contact_author();
        // echo '<h2>test</h2>';
    }



}

if ( is_admin() ) {
	new GKTPP_Options();
}
