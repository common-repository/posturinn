<?php 
/**
 * Plugin Name: Pósturinn's Shipping with WooCommerce
 * Description: This plugin is an WooCommerce extension to extend shipping method with Pósturinn's - Iceland
 * Version: 1.3.1
 * Author: Pósturinn
 * Author URI: https://postur.is
 * Text Domain: postis
 * Domain Path: /languages
 * License: GPL2
 */


/* ======= Direct Access Not Allowed =========== */
if( ! defined('ABSPATH' ) ){ exit; }

/* ======= Define Constant =========== */
define('POSTIS_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define('POSTIS_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define('POSTIS_PDF_DIR', 'posturinn' );

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

function postis_hpos_active() {
    if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
        return true;
    }
    return false;
}

function postis_include_files() {
    $hpos_suffix = postis_hpos_active() ? '_hpos' : '';
    
    $include_paths = [
        '/inc' . $hpos_suffix . '/functions.php',
        '/inc' . $hpos_suffix . '/hooks.php',
        '/inc' . $hpos_suffix . '/api.class.php',
        '/inc' . $hpos_suffix . '/admin.class.php',
        '/inc' . $hpos_suffix . '/checkout-fields.class.php',
    ];

    foreach ($include_paths as $path) {
        if (file_exists(POSTIS_PATH . $path)) {
            include_once POSTIS_PATH . $path;
        }
    }
}

// Perform the inclusion of files after all plugins are loaded to ensure WooCommerce is initialized
add_action('plugins_loaded', 'postis_include_files');

function postis_get_woocommerce_version() {
    if ( defined( 'WC_VERSION' ) ) {
        return WC_VERSION;
    }
    return 'Unknown';
}

function postis_get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
    return $plugin_data['Version'];
}


class POSTIS_MAIN_CLASS {

    private static $ins = null;
    
    function __construct(){

        add_action ('init', array($this, 'load_textdomain'));
        
        add_action('woocommerce_shipping_init', array($this, 'load_shipping_method'));
        
        add_filter('woocommerce_shipping_methods', array($this, 'include_shipping_method'));
        
        add_action('woocommerce_review_order_after_shipping', 'postis_hooks_rendering_extra_shipping_services');
        
        add_action( 'wp_enqueue_scripts','postis_hooks_load_scripts', 10 );
        
        $gm_enable = postis_get_settings( 'googlemap_enable' );
        if ($gm_enable == 'yes') {
            add_action( 'woocommerce_after_checkout_form', 'postis_hooks_after_checkout_form', 20 );
        }
        
        $plugin_name = plugin_basename( __FILE__ );
        add_filter( "plugin_action_links_{$plugin_name}", array($this, 'plugin_setting_menu') );

        add_filter('http_request_args', array($this, 'postis_increase_curl_timeout'), 10, 2);

        add_filter('woocommerce_package_rates', array($this, 'maybe_add_initial_rates'), 10, 2);
    }

    // Increase timeout for our api calls.
    function postis_increase_curl_timeout($args, $url) {
        if (strpos($url, 'mappan') !== false) {
            $args['timeout'] = 30;
        }
        return $args;
    }
    
    /* 
    **============= Load Plugin TextDomain ================ 
    */
    function load_textdomain() {

    	$loadedok = load_plugin_textdomain('postis', false, basename( dirname( __FILE__ ) ) . '/languages');
    }
    
    public function maybe_add_initial_rates($rates, $package) {
        //error_log('maybe_add_initial_rates called');

        // Check if the plugin is enabled
        $settings = get_option('woocommerce_postis_settings');
        $is_enabled = isset($settings['enabled']) && $settings['enabled'] === 'yes';

        if (!$is_enabled) {
            //error_log('Postis plugin is not enabled');
            return $rates;
        }

        $domestic_services = array(
            'DPH' => 'Pakki Heim',
            'DPP' => 'Pakki Pósthús',
            'DPO' => 'Pakki Póstbox',
            'DPT' => 'Pakki Pakkaport',
        );

        $international_services = array(
            'OLP' => 'Smápakki til útlanda',
            'OIJ' => 'Pakki til Útlanda',
        );

        $our_rates_exist = false;
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate_id, 'postis:') === 0) {
                $our_rates_exist = true;
                break;
            }
        }

        if (!$our_rates_exist && empty($package['destination']['postcode'])) {
            //error_log('Adding initial rates');
            
            // Check the country code
            $country_code = isset($package['destination']['country']) ? $package['destination']['country'] : '';
            
            $services_to_show = ($country_code === 'IS') ? $domestic_services : $international_services;

            foreach ($services_to_show as $code => $name) {
                $rate_id = 'postis:' . $code;
                $rates[$rate_id] = new WC_Shipping_Rate(
                    $rate_id,
                    'Pósturinn - ' . $name,
                    0,
                    array(),
                    'postis'
                );
            }
        } else {
            //error_log('Our rates already exist or postcode is set');
        }

        return $rates;
    }

    /* 
    **============= Init Postis Shipping Method with all the settings ================ 
    */
    function load_shipping_method() {
        // Determine if HPOS is active
        $hpos_suffix = postis_hpos_active() ? '_hpos' : '';
        
        // Build the include path dynamically based on HPOS state
        $path = POSTIS_PATH . '/inc' . $hpos_suffix . '/postis.class.php';
        
        // Load main postis shipping class if it exists
        if (file_exists($path)) {
            include $path;
        }
    }
    
    
    /* 
    **============= Include the shipping method to WC list ================ 
    */
    function include_shipping_method( $methods ) {
        
    	$methods[] = 'POSTIS_Shipping';
    	return $methods;
    }
    
    /* 
    **========== Create Setting Menu on plugin page =========== 
    */
    function plugin_setting_menu( $links ) {
        
        $setting_url    = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=postis' );
        $title          = 'Posturinn Settings';
        $settings_link  = sprintf(__('<a href="%s">%s</a>','postis'), $setting_url, $title);
      	array_push( $links, $settings_link );
      	return $links;
    }
    
    
    /* 
    **============= Get class instance ================ 
    */
    public static function get_instance() {
        
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }
}

add_action('plugins_loaded', 'postis_plugin_loaded');
function postis_plugin_loaded() {
    return POSTIS_MAIN_CLASS::get_instance();
}

function postis_get_pdf_from_api($order_id, $args, $is_shipment_international) {
    $shipmentId = postis_get_shipment_data($order_id, 'shipmentId');

    $pdf_request = (new POSTIS_API())->pdf_shipment_api_request($order_id, $allow_printing = true);

    if (is_wp_error($pdf_request) || wp_remote_retrieve_response_code($pdf_request) != 200) {
        echo wp_remote_retrieve_body($pdf_request);
        //update_post_meta($order_id, 'postis_shipment_pdf_printed', 'not_printed');
    } else {
        $response = wp_remote_retrieve_body($pdf_request);

        // Output the PDF directly to the browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $shipmentId . '.pdf"');
        echo $response;
        exit;
    }
}

// process PDF
function postis_process_pdf( $file_name, $args, $is_shipment_international, $order_id ) {

    $labelSize = postis_get_settings('postis_labelSize');
    if (!empty($labelSize)) {
        // If postis_labelSize exists, call the new function to get the PDF from the API
        postis_get_pdf_from_api($order_id, $args, $is_shipment_international);
        return;
    }

	require_once(POSTIS_PATH.'/lib/fpdi/vendor/autoload.php');

	$dir_path = postis_files_setup_get_directory('pdf');
	$dir_path = "{$dir_path}{$file_name}";
	
	if( ! file_exists($dir_path) ) wp_die("No PDF Found");
	//postis_pa($args); exit;
	
	
	// initiate FPDI
	$pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
	
    $pdf_width       = $args['pdf_width'];
    $pdf_height      = $args['pdf_height'];
    $pdf_rotate      = intval($args['pdf_rotate']);
    $pdf_orientation = $args['pdf_orientation'];
    $pdf_moveright    = intval($args['pdf_moveright']);
    $pdf_movedown     = intval($args['pdf_movedown']);
    $pdf_biggerbarcode= intval($args['pdf_biggerbarcode']);
    $numberOfItems    = intval($args['numberOfItems']);
    
    // Page size
    $pdf_size   = array($pdf_width, $pdf_height, 'Rotate' => $pdf_rotate);
    
    // set the source file
    $pageCount = $pdf->setSourceFile($dir_path);
    // var_dump($numberOfItems);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplIdx = $pdf->importPage($pageNo);
    
        // add a page
        if ($is_shipment_international) {
            $pdf->AddPage();
        }else if ($numberOfItems > 1) {
            $pdf->AddPage();
        }else{
            $pdf->AddPage($pdf_orientation, $pdf_size);
        }
    
        $size   = $pdf->useTemplate($tplIdx, $pdf_moveright, $pdf_movedown, $pdf_biggerbarcode);
    }
    
    // set array for viewer preferences
    $preferences = array(
        'HideToolbar' => false,
        'HideMenubar' => false,
        'HideWindowUI' => false,
        'FitWindow' => true,
        'CenterWindow' => true,
        'DisplayDocTitle' => true,
        'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
        'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
        'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
        //'PrintArea' => 'BleedBox', // CropBox, BleedBox, TrimBox, ArtBox
        //'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
        //'PrintScaling' => 'AppDefault', // None, AppDefault
        //'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
        'PickTrayByPDFSize' => true,
        //'PrintPageRange' => array(1,1,2,3),
        'NumCopies' => 1
    );
    
    // Check the example n. 60 for advanced page settings
    
    // set pdf viewer preferences
    $pdf->setViewerPreferences($preferences);
	
	$pdf->Output();
}

function postis_merge_pdf_files($pdf_files, $pdf_path){
    require_once(POSTIS_PATH.'/lib/fpdi/vendor/autoload.php');
    $pdf_width         = postis_get_settings( 'pdf_width' );
    $pdf_height        = postis_get_settings( 'pdf_height' );
    $pdf_rotate        = postis_get_settings( 'pdf_rotate' );
    $pdf_orientation   = postis_get_settings( 'pdf_orientation' );
    $pdf_moveright     = postis_get_settings( 'pdf_moveright' );
    $pdf_movedown      = postis_get_settings( 'pdf_movedown' );
    $pdf_biggerbarcode = postis_get_settings( 'pdf_biggerbarcode' );
    $pdf_size   = array($pdf_width, $pdf_height, 'Rotate' => $pdf_rotate);
    
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();

    foreach ($pdf_files as $file) {
        
        if (!empty($file)) {
            
            $pageCount = $pdf->setSourceFile($file);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                
                // import a page
                $templateId = $pdf->importPage($pageNo);
                
                $pdf->AddPage($pdf_orientation,$pdf_size);
        
                $pdf->useTemplate($templateId, $pdf_moveright, $pdf_movedown, $pdf_biggerbarcode);
            }
        }
    }
    
    $pdf->Output($pdf_path,"F");
}
