<?php

/*
Plugin Name: Book BuyBack Prices
Plugin URI: https://buybackdata.com/
Description: Show buyback (trade-in) prices for books.  Shows results from multiple vendors.
Author: Michael Berding
Version: 1.0.9
Requires PHP: 7.0
Author URI: https://berdingconsulting.com
*/
// slug: book-buyback-prices
define( 'BBBP_DEBUG', false );

if ( !function_exists( 'bbbp_fs' ) ) {
    // Create a helper function for easy SDK access.
    function bbbp_fs()
    {
        global  $bbbp_fs ;
        
        if ( !isset( $bbbp_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $bbbp_fs = fs_dynamic_init( array(
                'id'             => '7815',
                'slug'           => 'book-buyback-prices',
                'type'           => 'plugin',
                'public_key'     => 'pk_ae271f2934c0e72365b0b7db234ad',
                'is_premium'     => false,
                'premium_suffix' => 'Professional',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'    => 'bbbp-menu',
                'support' => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $bbbp_fs;
    }
    
    // Init Freemius.
    bbbp_fs();
    // Signal that SDK was initiated.
    do_action( 'bbbp_fs_loaded' );
}

require_once __DIR__ . '/includes/bbbp_isbn.php';
require_once __DIR__ . '/includes/privacy.php';
add_action( 'admin_init', 'bbbp_plugin_add_privacy_policy_content' );
/*
	ADMIN MENU START
*/

if ( is_admin() ) {
    // do stuff only if we're logged into the admin area
    // Adds "Settings" link to the plugin action page
    function bbbp_add_plugin_link( $plugin_actions, $plugin_file )
    {
        $new_actions = array();
        if ( strpos( $plugin_file, 'book-buyback-prices.php' ) > 0 ) {
            $new_actions['bbbp_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'bbbp' ), esc_url( admin_url( 'admin.php?page=bbbp-menu' ) ) );
        }
        return array_merge( $new_actions, $plugin_actions );
    }
    
    add_filter(
        'plugin_action_links',
        'bbbp_add_plugin_link',
        10,
        2
    );
    add_action( 'admin_menu', 'bbbp_admin_menu_setup' );
    function bbbp_admin_menu_setup()
    {
        add_menu_page(
            'Book BuyBack Prices Settings',
            // page_title
            'Book BuyBack Prices',
            // menu_title
            'manage_options',
            // capability
            'bbbp-menu',
            'bbbp_menu_render'
        );
    }
    
    function bbbp_menu_render()
    {
        // do stuff
        
        if ( BBBP_DEBUG ) {
            echo  'get_admin_page_parent = ' . get_admin_page_parent() . '<br>' ;
            echo  'get_admin_page_title = ' . get_admin_page_title() . '<br>' ;
        }
        
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        //if(stripos(get_admin_page_title(),'settings')!==false) {
        if ( true ) {
            require plugin_dir_path( __FILE__ ) . 'admin/settings.php';
        }
    }

}

/*
	ADMIN MENU END
*/
/*
	ACTIVATION AND DEACTIVATION HOOKS START
*/
//register_activation_hook( __FILE__, 'bbbp_activation_hook' );
//register_deactivation_hook( __FILE__, 'kobidevices_deactivation_hook' );
//register_uninstall_hook(__FILE__, 'kobidevices_uninstall_hook');
/*
	ACTIVATION AND DEACTIVATION HOOKS END
*/
function bbbp_fetch_bbd_api_key()
{
    $bbbp_options = get_option( 'bbbp_options' );
    
    if ( !isset( $bbbp_options['bbbp_field_api_key'] ) || $bbbp_options['bbbp_field_api_key'] == '' ) {
        // set the option
        $url = 'https://app.buybackdata.com/api/register.php?domain=' . bbbp_getDomain();
        $response = wp_remote_post( $url );
        $body = $response['body'];
        $jresponse = json_decode( $body, true );
        
        if ( $jresponse['success'] === true ) {
            $bbbp_options['bbbp_field_api_key'] = $jresponse['api_key'];
            update_option( 'bbbp_options', $bbbp_options );
            wp_redirect( 'admin.php?page=bbbp-menu' );
            exit;
        }
    
    }

}

/*
function bbbp_activation_hook() {
	
}
*/
// add_action( 'wp', 'bbbp_command_watcher' );
add_filter( 'init', 'bbbp_command_watcher' );
function bbbp_command_watcher()
{
    if ( isset( $_REQUEST['command'] ) ) {
        if ( $_REQUEST['command'] == 'bbbp-fetch-api-key' ) {
            bbbp_fetch_bbd_api_key();
        }
    }
}

/*
	SETTINGS START
*/
function bbbp_settings_init()
{
    $bbbp_options = get_option( 'bbbp_options' );
    // Register a new setting for "wporg" page.
    register_setting(
        'bbbp',
        // option_group
        'bbbp_options'
    );
    $bbbp_affiliate_links = get_option( 'bbbp_affiliate_links' );
    // Register a new setting for "wporg" page.
    register_setting(
        'bbbp',
        // option_group
        'bbbp_affiliate_links'
    );
    // Register a new section in the "wporg" page.
    add_settings_section(
        'bbbp_section_developers',
        // id
        __( 'Book BuyBack Prices Information', 'bbbp' ),
        // title
        'bbbp_section_developers_callback',
        // callback
        'bbbp'
    );
    
    if ( !isset( $bbbp_options['bbbp_field_api_key'] ) || $bbbp_options['bbbp_field_api_key'] == '' ) {
        $bbd_api_key_description = 'Click <a href="admin.php?page=bbbp-menu&command=bbbp-fetch-api-key">here</a> to get a free API key from BuyBackData.com.  Uses your site domain name for registration purposes.';
    } else {
        $bbd_api_key_description = "Used to authenticate this domain and prevent abuse. Used to fetch data from BuyBackData.com";
    }
    
    add_settings_field(
        'bbbp_field_api_key',
        // As of WP 4.6 this value is used only internally.
        // Use $args' label_for to populate the id inside the callback.
        __( 'BuyBack Data API Key', 'bbbp' ),
        // title
        'bbbp_settingsTextInput',
        // callback (was 'kobidevices_field_pill_cb')
        'bbbp',
        // page
        'bbbp_section_developers',
        // section
        array(
            'label_for'        => 'bbbp_field_api_key',
            'class'            => 'bbbp_row',
            'bbbp_custom_data' => 'custom',
            'option_name'      => 'bbbp_options',
            'description'      => $bbd_api_key_description,
            'width'            => '200px;',
        )
    );
    add_settings_field(
        'bbbp_field_include_book_meta',
        // As of WP 4.6 this value is used only internally.
        // Use $args' label_for to populate the id inside the callback.
        __( 'Show Book Details', 'bbbp' ),
        // title
        'bbbp_selectListDisplay',
        // callback (was 'kobidevices_field_pill_cb')
        'bbbp',
        // page
        'bbbp_section_developers',
        // section
        array(
            'label_for'        => 'bbbp_field_include_book_meta',
            'class'            => 'bbbp_row',
            'bbbp_custom_data' => 'custom',
            'option_name'      => 'bbbp_options',
            'elements'         => [
            'yes' => 'Include',
            'no'  => 'Omit',
        ],
            'description'      => 'Show the book image, title, author, etc. in the results',
        )
    );
    add_settings_section(
        'bbbp_section_grecaptcha',
        // id
        __( 'Google Recaptcha', 'bbbp' ),
        // title
        'bbbp_section_google_recaptcha_callback',
        // callback
        'bbbp'
    );
    add_settings_field(
        'bbbp_field_google_recaptcha_site_key',
        // As of WP 4.6 this value is used only internally.
        // Use $args' label_for to populate the id inside the callback.
        __( 'Site Key', 'bbbp' ),
        // title
        'bbbp_settingsTextInput',
        // callback (was 'kobidevices_field_pill_cb')
        'bbbp',
        // page
        'bbbp_section_grecaptcha',
        // section
        array(
            'label_for'        => 'bbbp_field_google_recaptcha_site_key',
            'class'            => 'bbbp_row',
            'bbbp_custom_data' => 'custom',
            'option_name'      => 'bbbp_options',
        )
    );
    add_settings_field(
        'bbbp_field_google_recaptcha_secret_key',
        // As of WP 4.6 this value is used only internally.
        // Use $args' label_for to populate the id inside the callback.
        __( 'Secret Key', 'bbbp' ),
        // title
        'bbbp_settingsTextInput',
        // callback (was 'kobidevices_field_pill_cb')
        'bbbp',
        // page
        'bbbp_section_grecaptcha',
        // section
        array(
            'label_for'        => 'bbbp_field_google_recaptcha_secret_key',
            'class'            => 'bbbp_row',
            'bbbp_custom_data' => 'custom',
            'option_name'      => 'bbbp_options',
        )
    );
    add_settings_section(
        'bbbp_section_affiliate_links',
        // id
        __( 'Affiliate Links', 'bbbp' ),
        // title
        'bbbp_section_affiliate_links_callback',
        // callback
        'bbbp'
    );
    $affiliate_link_callback_function = 'bbbp_show_upgrade_now_notice';
    $vendors = array();
    $vendors[] = array(
        'id'   => 825,
        'name' => 'BeerMoneyBooks',
        'url'  => 'https://beermoneybooks.com',
    );
    $vendors[] = array(
        'id'   => 48,
        'name' => 'BlueRocketBooks',
        'url'  => 'https://www.bluerocketbooks.com',
    );
    $vendors[] = array(
        'id'   => 2,
        'name' => 'Bookbyte',
        'url'  => 'https://www.bookbyte.com',
    );
    $vendors[] = array(
        'id'   => 832,
        'name' => 'Books2Cash',
        'url'  => 'https://books2cash.com',
    );
    $vendors[] = array(
        'id'   => 809,
        'name' => 'BooksRun',
        'url'  => 'https://booksrun.com',
    );
    $vendors[] = array(
        'id'   => 50,
        'name' => 'Bookstores.com',
        'url'  => 'https://www.bookstores.com',
    );
    $vendors[] = array(
        'id'   => 853,
        'name' => 'BookToCash',
        'url'  => 'https://booktocash.com',
    );
    $vendors[] = array(
        'id'   => 7,
        'name' => 'CollegeBooksDirect',
        'url'  => 'http://www.collegebooksdirect.com',
    );
    $vendors[] = array(
        'id'   => 824,
        'name' => 'Comic Blessing',
        'url'  => 'https://comicblessing.com',
    );
    $vendors[] = array(
        'id'   => 6,
        'name' => 'eCampus',
        'url'  => 'https://www.ecampus.com',
    );
    $vendors[] = array(
        'id'   => 841,
        'name' => 'Empire Text',
        'url'  => 'https://empiretext.com',
    );
    $vendors[] = array(
        'id'   => 854,
        'name' => 'PiggyBook',
        'url'  => 'http://www.piggybook.net',
    );
    $vendors[] = array(
        'id'   => 24,
        'name' => "Powell's",
        'url'  => 'http://www.powells.com',
    );
    $vendors[] = array(
        'id'   => 845,
        'name' => 'Sell Books',
        'url'  => 'https://buyback.sellbooks.net',
    );
    $vendors[] = array(
        'id'   => 4,
        'name' => 'SellBackBooks',
        'url'  => 'https://www.sellbackbooks.com',
    );
    $vendors[] = array(
        'id'   => 25,
        'name' => 'SellBackYourBook',
        'url'  => 'http://www.sellbackyourbook.com',
    );
    $vendors[] = array(
        'id'   => 846,
        'name' => 'Textbook Drop',
        'url'  => 'https://textbookdrop.com',
    );
    $vendors[] = array(
        'id'   => 823,
        'name' => 'TextbookCashback',
        'url'  => 'https://textbookcashback.com',
    );
    $vendors[] = array(
        'id'   => 77,
        'name' => 'TextbookManiac',
        'url'  => 'https://buyback.textbookmaniac.com',
    );
    $vendors[] = array(
        'id'   => 19,
        'name' => 'TextbookRush',
        'url'  => 'https://www.textbookrush.com',
    );
    $vendors[] = array(
        'id'   => 67,
        'name' => 'TopDollar4Books',
        'url'  => 'http://www.topdollar4books.com',
    );
    $vendors[] = array(
        'id'   => 16,
        'name' => 'ValoreBooks',
        'url'  => 'https://www.valorebooks.com',
    );
    $vendors[] = array(
        'id'   => 836,
        'name' => 'Ziffit',
        'url'  => 'https://www.ziffit.com',
    );
    foreach ( $vendors as $vendorData ) {
        $description = '';
        /*
        if(isset($vendorData['url']) && $vendorData['url']!='') {
        	$description = "<a href='".$vendorData['url']."' target='_blank'><span class='dashicons dashicons-admin-links'></span></a>"; // ".$vendorData['url']."
        }
        */
        $name_display = $vendorData['name'] . " <a href='" . $vendorData['url'] . "' style='text-decoration: none;' target='_blank'><span class='dashicons dashicons-external'></span></a>";
        // ".$vendorData['url']."
        add_settings_field(
            'bbbp_afflink_' . $vendorData['id'],
            // As of WP 4.6 this value is used only internally.
            // Use $args' label_for to populate the id inside the callback.
            __( $name_display, 'bbbp' ),
            // title
            $affiliate_link_callback_function,
            // callback (was 'kobidevices_field_pill_cb')
            'bbbp',
            // page
            'bbbp_section_affiliate_links',
            // section
            array(
                'label_for'        => 'bbbp_afflink_' . $vendorData['id'],
                'class'            => 'bbbp_row',
                'bbbp_custom_data' => 'custom',
                'option_name'      => 'bbbp_affiliate_links',
                'description'      => $description,
            )
        );
    }
}

add_action( 'admin_init', 'bbbp_settings_init' );
function bbbp_section_developers_callback( $args )
{
    ?>
    <p id="<?php 
    echo  esc_attr( $args['id'] ) ;
    ?>"><?php 
    esc_html_e( 'BuyBack pricing obtained from BuyBackData.com', 'bbbp' );
    ?></p>
    <?php 
}

function bbbp_section_google_recaptcha_callback( $args )
{
    ?>
    <p id="<?php 
    echo  esc_attr( $args['id'] ) ;
    ?>">Google Recaptcha v3 is required to prevent abuse.  <b>Important!</b> make sure to select "reCAPTCHA <b>v3</b>".  Create a key for free here: <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a>.</p>
    <?php 
}

function bbbp_section_affiliate_links_callback( $args )
{
    ?>
    <p id="<?php 
    echo  esc_attr( $args['id'] ) ;
    ?>">
    	<ul style="list-style: disc;padding-left: 30px;">
    		<li>Use your own affiliate links to earn commissions on sales generated from your site.</li>
    		<li>Important: You must apply for affiliate programs for each vendor to earn commissions with them.</li>
    		<li>If you have ISBN-specific links, use the placeholder [isbn] in your link.</li>
    	</ul>
    </p>
    <?php 
}

function bbbp_show_upgrade_now_notice( $params = array() )
{
    //echo '<section><h1>' . __('Awesome Premium Features', 'woocommerce-product-trade-in') . '</h1>';
    echo  '<a href="' . bbbp_fs()->get_upgrade_url() . '">' . __( 'Upgrade Now!', 'bbbp' ) . '</a>' ;
    // echo '</section>';
}

function bbbp_settingsTextInput( $args )
{
    $option_name = $args['option_name'];
    $options = get_option( $option_name );
    //echo var_dump($options);
    $value = @$options[$args['label_for']];
    $description = @$args['description'];
    $width = @$args['width'];
    if ( $width == '' ) {
        $width = '400px;';
    }
    ?>
		<input type='text' 
			data-custom="<?php 
    echo  esc_attr( $args['bbbp_custom_data'] ) ;
    ?>"
			name="<?php 
    echo  $option_name ;
    ?>[<?php 
    echo  esc_attr( $args['label_for'] ) ;
    ?>]" 
			value="<?php 
    esc_html_e( $value, 'bbbp' );
    ?>"
			style="width: <?php 
    echo  $width ;
    ?>;">
			
	
    <?php 
    
    if ( $description != '' ) {
        ?>
		<p class="description">
			<?php 
        // esc_html_e( $description, 'bbbp' );
        echo  $description ;
        ?>
		</p>
	<?php 
    }
    
    ?>

	<?php 
}

function bbbp_selectListDisplay( $args )
{
    $option_name = $args['option_name'];
    $options = get_option( $option_name );
    $elements = $args['elements'];
    $description = @$args['description'];
    ?>
		<select
            id="<?php 
    echo  esc_attr( $args['label_for'] ) ;
    ?>"
            data-custom="<?php 
    echo  esc_attr( $args['bbbp_custom_data'] ) ;
    ?>"
            name="<?php 
    echo  $option_name ;
    ?>[<?php 
    echo  esc_attr( $args['label_for'] ) ;
    ?>]">
            <?php 
    foreach ( $elements as $key => $value ) {
        echo  "<option value='" . $key . "' " ;
        if ( @$options[$args['label_for']] == $key ) {
            echo  ' selected ' ;
        }
        echo  ">" ;
        echo  esc_html_e( $value, 'bbbp' ) ;
        echo  "</option>" ;
    }
    ?>
		</select>
		<?php 
    
    if ( $description != '' ) {
        ?>
			<p class="description">
				<?php 
        esc_html_e( $description, 'bbbp' );
        ?>
			</p>
		<?php 
    }
    
    ?>
		
	<?php 
}

/*
	SETTINGS END
*/
function bbbp_getDomain()
{
    $parts = parse_url( get_site_url() );
    return $parts['host'];
}

// https://wordpress.stackexchange.com/questions/299521/creating-a-contact-form-without-a-plugin
class bbbp_Form
{
    var  $error_display = array() ;
    var  $notifier_display = array() ;
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->define_hooks();
    }
    
    /**
     * Define hooks related to plugin
     */
    private function define_hooks()
    {
        /**
         * Add action to send email
         */
        //add_action( 'wp', array( $this, 'controller' ) );
        /**
         * Add shortcode to display form
         */
        add_shortcode( 'bbbp', array( $this, 'display_form' ) );
    }
    
    /**
     * Display form
     */
    public function display_form( $atts = array(), $content = null, $tag = '' )
    {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        // override default attributes with user attributes
        $bbbp_atts = shortcode_atts( array(
            'isbn' => 'optional',
        ), $atts, $tag );
        $critical_errors = false;
        $single_isbn = false;
        $isbn = '';
        
        if ( isset( $bbbp_atts['isbn'] ) && strlen( $bbbp_atts['isbn'] ) > 0 && $bbbp_atts['isbn'] != 'optional' ) {
            $single_isbn = true;
            $isbn = $bbbp_atts['isbn'];
        } else {
            if ( isset( $_POST['isbn'] ) ) {
                $isbn = sanitize_text_field( @$_POST['isbn'] );
            }
        }
        
        $output = '';
        //$output .= 'bbbp_getDomain = '.bbbp_getDomain().'<br>';
        $bbbp_options = get_option( 'bbbp_options' );
        
        if ( !isset( $bbbp_options['bbbp_field_api_key'] ) || $bbbp_options['bbbp_field_api_key'] == '' ) {
            $this->error_display[] = "Book BuyBack Prices API key is not on file.  Check settings page to obtain a free API key.";
            $critical_errors = true;
        }
        
        
        if ( !isset( $bbbp_options['bbbp_field_google_recaptcha_site_key'] ) || $bbbp_options['bbbp_field_google_recaptcha_site_key'] == '' ) {
            $this->error_display[] = "Google Recaptcha Site Key is required.";
            $critical_errors = true;
        }
        
        
        if ( !isset( $bbbp_options['bbbp_field_google_recaptcha_secret_key'] ) || $bbbp_options['bbbp_field_google_recaptcha_secret_key'] == '' ) {
            $this->error_display[] = "Google Recaptcha Secret Key is required.";
            $critical_errors = true;
        }
        
        
        if ( is_array( $this->error_display ) && count( $this->error_display ) > 0 ) {
            $output .= '<h2 class="bbbp-error-heading">Error' . (( count( $this->error_display ) == 1 ? '' : 's' )) . '</h2>';
            $output .= '<pre class="bbbp-error-display">';
            $output .= implode( "\n", $this->error_display );
            $output .= '</pre>';
            $this->error_display = array();
        }
        
        
        if ( is_array( $this->notifier_display ) && count( $this->notifier_display ) > 0 ) {
            $output .= '<p class="bbbp-notice-display">';
            $output .= implode( '<br>', $this->notifier_display );
            $output .= '</p>';
            $this->notifier_display = array();
        }
        
        
        if ( !$critical_errors ) {
            $output .= '<form action="" method="post" id="bbbp-search-form" onsubmit="bbbp_ajaxSubmit()">';
            $output .= '<input type="hidden" name="action" value="bbbp_isbn_search">';
            $output .= '<input type="hidden" name="command" value="bbbp_submission">';
            $output .= '<input type="hidden" name="security" value="' . wp_create_nonce( "bbbp-nonce" ) . '">';
            $output .= '<input type="hidden" name="recaptcha_response" id="recaptchaResponse" value="">';
            $output .= ' <div id="bbbp-isbn-input-div">';
            $output .= '<input type="text" name="isbn" id="bbbp_isbn_input" value="' . esc_attr( $isbn ) . '" placeholder="10 or 13 digit ISBN">';
            //$output .= '<input type="submit" name="submit" value="Search" />';
            $output .= '<input type="button" id="bbbp_isbn_search_button" value="Search" onclick="bbbp_ajaxSubmit()">';
            //$output .= '<button type="submit">Search</button>';
            $output .= '</div>';
            $output .= '</form>';
            $output .= '<script>
								function bbbp_defer(method) {
									if (window.jQuery) {
										method();
									} else {
										setTimeout(function() { bbbp_defer(method) }, 50);
									}
								}
							</script>';
            
            if ( $single_isbn ) {
                //! search for and put the results into the #bbbpResults container
                $output .= '<style>
								#bbbp-isbn-input-div {
									display:none;
								}
							</style>';
                $output .= '<script>
								bbbp_defer(function () {
									bbbp_ajaxSubmit();
								});
							</script>';
            }
            
            $output .= '<div id="bbbpResults">';
            if ( $single_isbn ) {
                $output .= "<img src='" . plugins_url( '/img/spinner.svg', __FILE__ ) . "'>";
            }
            $output .= '</div>';
        } else {
            $output .= '<br><b>Crititcal errors were found, please correct them before continuing.</b>';
        }
        
        return $output;
    }

}
new bbbp_Form();
add_action( 'wp_enqueue_scripts', 'bbbp_enqueue_function' );
function bbbp_enqueue_function()
{
    wp_register_script( 'bbbp', plugins_url( '/js/bbbp.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script( 'bbbp' );
    wp_enqueue_style(
        'bbbp',
        plugins_url( '/css/bbbp.css', __FILE__ ),
        false,
        '1.0.0',
        'all'
    );
}

// https://webprogramo.com/how-to-create-an-ajax-form-in-wordpress/1156/
function bbbp_javascript_variables()
{
    $bbbp_options = get_option( 'bbbp_options' );
    $grsk = $bbbp_options['bbbp_field_google_recaptcha_site_key'];
    ?>
		<script src="https://www.google.com/recaptcha/api.js?render=<?php 
    echo  $grsk ;
    ?>"></script>
		<script type="text/javascript">
			var ajax_url = '<?php 
    echo  admin_url( "admin-ajax.php" ) ;
    ?>';
			var BBBP_GOOGLE_RECAPTCHA_SITE_KEY = '<?php 
    echo  $grsk ;
    ?>';
			var bbbp_spinner_location = '<?php 
    echo  plugins_url( '/img/spinner.svg', __FILE__ ) ;
    ?>';
			var bbbp_logos_directory = '<?php 
    echo  plugins_url( '/img/logos/', __FILE__ ) ;
    ?>';
		</script>
    <?php 
}

add_action( 'wp_head', 'bbbp_javascript_variables' );
// Here we register our "send_form" function to handle our AJAX request, do you remember the "superhypermega" hidden field? Yes, this is what it refers, the "send_form" action.
add_action( 'wp_ajax_bbbp_isbn_search', 'bbbp_isbn_search' );
// This is for authenticated users
add_action( 'wp_ajax_nopriv_bbbp_isbn_search', 'bbbp_isbn_search' );
// This is for unauthenticated users.
/**
 * In this function we will handle the form inputs and send our email.
 *
 * @return void
 */
function bbbp_isbn_search()
{
    // This is a secure process to validate if this request comes from a valid source.
    check_ajax_referer( 'bbbp-nonce', 'security' );
    /**
     * First we make some validations, 
     * I think you are able to put better validations and sanitizations. =)
     */
    $isbn = sanitize_text_field( $_REQUEST['isbn'] );
    
    if ( $isbn == '' ) {
        wp_send_json( [
            'error' => 'Please enter an ISBN to get results.',
        ] );
        //echo "Please enter an ISBN to get results.";
        wp_die();
    }
    
    $isbn13 = bbbp_isbn::giveMeISBN13( $isbn );
    
    if ( strlen( $isbn13 ) != 13 ) {
        // echo "Please enter a valid ISBN to get results.";
        wp_send_json( [
            'error' => 'Please enter a valid ISBN to get results',
        ] );
        wp_die();
    }
    
    
    if ( $_POST['recaptcha_response'] == '' ) {
        //echo "Error with recaptcha response.";
        wp_send_json( [
            'error' => 'Error with recaptcha response',
        ] );
        wp_die();
    }
    
    $bbbp_options = get_option( 'bbbp_options' );
    $gsecret = $bbbp_options['bbbp_field_google_recaptcha_secret_key'];
    // Build POST request:
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = $gsecret;
    $recaptcha_response = $_POST['recaptcha_response'];
    // Make and decode POST request:
    $recaptcha = file_get_contents( $recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response );
    $recaptcha = json_decode( $recaptcha );
    
    if ( bbbp_getDomain() != $recaptcha->hostname ) {
        //echo "Request appears to come from the wrong domain. Exiting.";
        wp_send_json( [
            'error' => 'Request appears to come from the wrong domain. Exiting',
        ] );
    } else {
        // Take action based on the score returned:
        
        if ( $recaptcha->score >= 0.5 ) {
            // Verified - send email
            //echo 'isbn: '.$isbn;
            $bbbp_options = get_option( 'bbbp_options' );
            $url = 'https://app.buybackdata.com/api/get.php?api_key=' . $bbbp_options['bbbp_field_api_key'] . '&isbn=' . $isbn13;
            if ( $bbbp_options['bbbp_field_include_book_meta'] == 'yes' ) {
                $url .= '&bookmeta=true';
            }
            //echo '<br>fetching: '.$url;
            $response = wp_remote_post( $url );
            $body = $response['body'];
            $jresponse = json_decode( $body, true );
            wp_send_json( $jresponse );
        } else {
            // Not verified - show form error
            //echo 'You appear to be a bot according to google. (Score: '.$recaptcha->score.') Sorry, we are not going to show results.';
            wp_send_json( [
                'error' => 'You appear to be a bot according to google. (Score: ' . $recaptcha->score . ') Sorry, we are not going to show results.',
            ] );
        }
    
    }
    
    wp_die();
}
