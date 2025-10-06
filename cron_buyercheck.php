<?php
/**
 * Cron job script for executing the BuyerCheck process_orders function.
 *
 * You can set this up as a cron job on your server to run periodically.
 * For example, to run it every hour, your cron command would look like this:
 * 0 * * * * /usr/bin/php /path/to/your/opencart/cron_buyercheck.php
 *
 * Make sure to replace /path/to/your/opencart/ with the actual absolute path to your store's root directory.
 * You may also need to adjust the path to your PHP executable (/usr/bin/php).
 */

// Set the route to the controller method you want to execute.
$_GET['route'] = 'extension/fraud/buyercheck/process_orders';

// Set dummy server variables if running from command line (CLI)
// This is necessary for OpenCart to function correctly in a cron environment.
if (php_sapi_name() == 'cli') {
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['HTTPS'] = true; // Set to true if your store uses SSL
}

// Load the main OpenCart entry point.
// This will initialize the framework and dispatch the route we set above.
require_once('index.php');

?>