<?php
/**
 * Plugin Name: BuyerCheck
 * Plugin URI: https://buyercheck.bg
 * Description: A plugin to manage order processing and integration with BuyerCheck API.
 * Version: 1.1.35
 * Author: BuyerCheck
 * Author URI: https://buyercheck.bg
 * Text Domain: buyercheck
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('BUYERCHECK_PLUGIN_DIR')) {
    define('BUYERCHECK_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('BUYERCHECK_PLUGIN_URL')) {
    define('BUYERCHECK_PLUGIN_URL', plugin_dir_url(__FILE__));
}

define('BUYERCHECK_PLUGIN_VERSION', '1.1.35');

// Include necessary files
require_once BUYERCHECK_PLUGIN_DIR . 'includes/class-buyercheck-webhook.php';
require_once BUYERCHECK_PLUGIN_DIR . 'includes/class-buyercheck-settings.php';
require_once BUYERCHECK_PLUGIN_DIR . 'includes/class-buyercheck-order-processor.php';

// Activation hook
register_activation_hook(__FILE__, 'buyercheck_activate');
function buyercheck_activate() {
    // Schedule cron job
    if (!wp_next_scheduled('buyercheck_cron_job')) {
        wp_schedule_event(time(), 'hourly', 'buyercheck_cron_job');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'buyercheck_deactivate');
function buyercheck_deactivate() {
    // Clear scheduled cron job
    wp_clear_scheduled_hook('buyercheck_cron_job');
}

// Load plugin
add_action('plugins_loaded', 'buyercheck_init');
function buyercheck_init() {
    // Initialize webhook
    BuyerCheck_Webhook::init();
    // Initialize settings
    BuyerCheck_Settings::init();
    // Initialize order processor
    BuyerCheck_Order_Processor::init();
}

// Enqueue JavaScript for checkout
add_action('wp_enqueue_scripts', 'buyercheck_enqueue_scripts');
function buyercheck_enqueue_scripts() {
    if (is_checkout()) {
        wp_enqueue_script('buyercheck-checkout', BUYERCHECK_PLUGIN_URL . 'assets/js/buyercheck-checkout.js', array('jquery'), '1.1.35', true);
        wp_localize_script('buyercheck-checkout', 'buyercheck_vars', array(
            'rest_url' => esc_url_raw(rest_url(get_option('buyercheck_webhook_prefix') . '/v1/capture')),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
}

$basename = plugin_basename( __FILE__ );
$prefix = is_network_admin() ? 'network_admin_' : '';

// Add a "Settings" link to the plugin in the plugins list
add_filter("{$prefix}plugin_action_links_{$basename}", 'buyercheck_add_settings_link');

function buyercheck_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=buyercheck-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}