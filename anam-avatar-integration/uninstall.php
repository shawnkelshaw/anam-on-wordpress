<?php
/**
 * Uninstall Script for Anam on WordPress - Admin Settings
 * 
 * This file runs when the plugin is DELETED (not just deactivated) from WordPress.
 * WordPress will show a confirmation dialog before running this script.
 * 
 * It removes ALL plugin data from the database:
 * - All settings (API keys, avatar IDs, etc.)
 * - Conversations table
 * - Verification status
 * 
 * After deletion, if the plugin is reinstalled, it will be like starting fresh.
 * 
 * @package AnamOnWordPress
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
delete_option('anam_options');           // Main settings (API key, persona ID, avatar ID, etc.)
delete_option('anam_api_verified');      // API verification status
delete_option('anam_api_verified_at');   // API verification timestamp

// Delete the conversations table
global $wpdb;
$table_name = $wpdb->prefix . 'anam_conversations';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clear any transients or cached data
delete_transient('anam_api_status');
delete_transient('anam_session_token');

// Log the uninstall (for debugging)
error_log('Anam on WordPress: All plugin data deleted during uninstall');
