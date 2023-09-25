<?php

/*
Plugin Name: NETOPIA Payments Payment Gateway
Plugin URI: https://www.netopia-payments.ro
Description: accept payments through NETOPIA Payments
Author: Netopia
Version: 1.3+1
License: GPLv2
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'netopiapayments_init', 0 );
function netopiapayments_init() {
    // set Api Version to work with plugin
	

    // If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	DEFINE ('NTP_PLUGIN_DIR', plugins_url(basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );

    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'add_netopiapayments_gateway' );
    function add_netopiapayments_gateway( $methods ) {
        $methods[] = 'netopiapayments';
        return $methods;
        }

    // Add custom action links
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'netopia_action_links' );
    function netopia_action_links( $links ) {
        $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=netopiapayments' ) . '">' . __( 'Settings', 'netopiapayments' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    if (getNtpApiVer() == 1) {
        // If we made it this far, then include our Gateway Class
        include_once( 'wc-netopiapayments-gateway.php' );
        include_once( 'wc-netopiapayments-update-key.php' );

        
        add_action( 'admin_enqueue_scripts', 'netopiapaymentsjs_init' );
        function netopiapaymentsjs_init($hook) {
            if ( 'woocommerce_page_wc-settings' != $hook ) {
                    return;
                }
                // wp_enqueue_script( 'netopiapaymentsjs', plugin_dir_url( __FILE__ ) . 'js/netopiapayments_.js',array('jquery'),'2.0' ,true);
                // wp_enqueue_script( 'netopiatoastrjs', plugin_dir_url( __FILE__ ) . 'js/toastr.min.js',array(),'2.0' ,true);
                // wp_enqueue_style('netopiatoastrcss', plugin_dir_url( __FILE__ ) . 'css/toastr.min.css',array(),'2.0' ,false);
                 
                // Get ntp_notify_value if exist
                 $ntpOptions = get_option( 'woocommerce_netopiapayments_settings' );
                 $ntpNotify = array_key_exists('ntp_notify_value', $ntpOptions) ? $ntpOptions['ntp_notify_value'] : '';
 
                 wp_enqueue_script( 'netopiapaymentsjs', plugin_dir_url( __FILE__ ) . 'v2/js/netopiapayments.js',array('jquery'),'1.1' ,true);
                 wp_enqueue_script( 'netopiaUIjs', plugin_dir_url( __FILE__ ) . 'v2/js/netopiaCustom.js',array(),'1.2' ,true);
                 wp_localize_script( 'netopiaUIjs', 'netopiaUIPath_data', array(
                     'plugin_url' => getAbsoulutFilePath(),
                     'site_url' => get_site_url(),
                     'ntp_notify' => $ntpNotify,
                     )
                 );
            }
    } else {
        // Api v2 Here 

        // If we made it this far, then include our Gateway Class
        include_once( 'v2/wc-netopiapayments-gateway.php' );
        include_once( 'wc-netopiapayments-update-key.php' );
        // include_once( 'v2/wc-netopiapayments-auth.php' );

        add_action( 'admin_enqueue_scripts', 'netopiapaymentsjs_init' );
        function netopiapaymentsjs_init($hook) {
                if ( 'woocommerce_page_wc-settings' != $hook ) {
                    return;
                    }

                
                // Get ntp_notify_value if exist
                $ntpOptions = get_option( 'woocommerce_netopiapayments_settings' );
                $ntpNotify = array_key_exists('ntp_notify_value', $ntpOptions) ? $ntpOptions['ntp_notify_value'] : '';

                wp_enqueue_script( 'netopiapaymentsjs', plugin_dir_url( __FILE__ ) . 'v2/js/netopiapayments.js',array('jquery'),'1.0' ,true);
                wp_enqueue_script( 'netopiaUIjs', plugin_dir_url( __FILE__ ) . 'v2/js/netopiaCustom.js',array(),'1.1' ,true);
                wp_localize_script( 'netopiaUIjs', 'netopiaUIPath_data', array(
                    'plugin_url' => getAbsoulutFilePath(),
                    'site_url' => get_site_url(),
                    'ntp_notify' => $ntpNotify,
                    )
                );
        }
    }
}

function getAbsoulutFilePath() {
	// Get the absolute path to the plugin directory
	$plugin_dir_path = plugin_dir_path( __FILE__ );

	// Get the absolute path to the WordPress installation directory
	$wordpress_dir_path = realpath( ABSPATH . '..' );

	// Remove the WordPress installation directory from the plugin directory path
	$plugin_dir_path = str_replace( $wordpress_dir_path, '', $plugin_dir_path );

	// Remove the leading directory separator
	$plugin_dir_path = ltrim( $plugin_dir_path, '/' );

	// Remove the first directory name (which is the site directory name)
	$plugin_dir_path = preg_replace( '/^[^\/]+\//', '/', $plugin_dir_path );

	return $plugin_dir_path;
}

/**
 * Decided which API should be used 
 * Check if plugin is configured for API v2
 * NOTE : it MUST HAVE LIVE API KEY in order to switch to api v2
 */
function getNtpApiVer() {
    $ntpOptions = get_option( 'woocommerce_netopiapayments_settings' );
    $hasLiveApiKey = array_key_exists('live_api_key', $ntpOptions) && !empty($ntpOptions['live_api_key']) ? true : false;
    // $hasSandboxApiKey = array_key_exists('sandbox_api_key', $ntpOptions) && !empty($ntpOptions['sandbox_api_key']) ? true : false;
	// $apiV = $hasLiveApiKey && $hasSandboxApiKey ? 2 : 1;

    $apiV = $hasLiveApiKey ? 2 : 1;
    return $apiV;
}

/**
 * Activation hook  once after install / update will execute
 * By "verify-regenerat" key will verify if certifications not exist
 * Then try to regenerated the certifications
 * */ 
register_activation_hook( __FILE__, 'plugin_activated' );
function plugin_activated(){
	add_option( 'woocommerce_netopiapayments_certifications', 'verify-and-regenerate' );
}

/**
 * Verify Ceredential Keys on Upgrade Plugin
 */
// function isPluginUpgradeCompleted($upgrader_object, $options) {
//     echo "<pre>";
//     var_dump($options);
//     echo "<hr>";
//     var_dump($upgrader_object);
//     echo "</pre>";
//     die(1);
//     // Check if the upgrade process was for your plugin
//     if ($options['action'] == 'update' && $options['type'] == 'plugin' && in_array('netopia-payments-payment-gateway/netopiapayments.php', $options['plugins'])) {
//         // Your custom code to run after the plugin upgrade here
//         // Replace 'your-plugin-folder/your-plugin-file.php' with your plugin's folder and main file.
//     }
// }

// add_action('upgrader_process_complete', 'isPluginUpgradeCompleted', 10, 2);

// Check for upgrades after plugin installation or upgrade
// function my_plugin_check_for_upgrade($response, $hook_extra, $result) {
//     echo "<pre>";
//     echo "response <br>";var_dump($response);
//     echo "hook_extra <br>";var_dump($hook_extra);
//     echo "result <br>";var_dump($result);
//     echo "</pre>";
//     // die(1);
//     // if ($hook_extra['type'] === 'plugin' && isset($hook_extra['plugin'])) {
//     //     // Get the plugin folder and main file from hook_extra
//     //     $plugin_folder = dirname($hook_extra['plugin']);
//     //     $plugin_file = basename($hook_extra['plugin']);

//     //     if ($result === true && $hook_extra['action'] === 'update') {
//     //         // Upgrade-specific code here
//     //         // For example, you can update database tables or settings.
//     //         // This code will execute only when an existing plugin is upgraded.
//     //     }
//     // }
// }

// add_action('upgrader_post_install', 'my_plugin_check_for_upgrade', 10, 3);


