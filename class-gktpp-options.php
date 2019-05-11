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
            ' Pre* Party Settings',
            'Pre* Party',
            'manage_options',
            'gktpp-plugin-settings',
            array( $this, 'settings_page' ),
            plugins_url( '/pre-party-browser-hints/images/lightning.png' )
        );

        add_action( "load-{$settings_page}", array( $this, 'screen_option' ) );
        add_action( "load-post.php", array( $this, 'screen_option' ) );
        add_action( "load-{$settings_page}", array( $this, 'save_data' ) );
    }

    public function save_data() {
        if ( isset( $_POST['gktpp-settings-submit'] ) ) {

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
                return;
            }

            check_admin_referer( 'gktpp-enter-data' );

            if ( gktpp_check_pp_admin() ) {

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

	public function admin_tabs( $current = 'insert-urls' ) {
		$tabs = array(
            'insert-urls'   => 'Insert URLs',
            'info'          => 'Information'
        );

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab === $current ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=gktpp-plugin-settings&tab=$tab'>" . esc_html( $name ) . "</a>";
		}
		echo '</h2>';
	}

	public function settings_page() {
		if ( ! is_admin() ) {
			exit;
		}
		?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Pre* Party Plugin Settings', 'gktpp' ); ?></h2>
                <form method="post" action="<?php admin_url( 'admin.php?page=gktpp-plugin-settings' ); ?>">
                    <?php ( isset( $_GET['tab'] ) ) ? $this->admin_tabs( $_GET['tab'] ) : $this->admin_tabs( 'insert-urls' ); ?>
                </form>
                <?php
                    if ( gktpp_check_pp_admin() ) {
                        return $this->display_tabs();
                    } 
                ?>
            </div>
        <?php 
    }
    
    public function display_tabs() {

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'insert-urls';
        $gktpp_table = new GKTPP_Table();

        switch ( $tab ) {
            case 'insert-urls':
                $gktpp_table->prepare_items();
                GKTPP_Enter_Data::show_info();
                GKTPP_Enter_Data::contact_author();
            break;

            case 'info':
                $hint_info = new GKTPP_Hint_Info();
                $hint_info->resource_hint_nav();
            break;

            default:
                $gktpp_table->prepare_items();
                GKTPP_Enter_Data::show_info();
                GKTPP_Enter_Data::contact_author();
            break;
        }

        echo sprintf( __( 'Tip: test your website on <a href="%s">WebPageTest.org</a> to know which resource hints and URLs to insert.' ), __( 'https://www.webpagetest.org' ) );
    }

	public function screen_option() {
		$option = 'per_page';
		$args = array(
			'label'   => 'URLs',
			'default' => 10,
			'option'  => 'gktpp_screen_options'
		);

		add_screen_option( $option, $args );

		$this->resource_obj = new GKTPP_Table();
	}

}

if ( is_admin() ) {
	new GKTPP_Options();
}
