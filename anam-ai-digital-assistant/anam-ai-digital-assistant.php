<?php
/**
 * Plugin Name: Anam.ai Digital Assistant
 * Description: Admin setup integration for Anam.ai.
 * Version: 0.1.0
 * Author: Shawn Kelshaw
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ANAM_AI_DA_VERSION', '0.1.0');
define('ANAM_AI_DA_PLUGIN_FILE', __FILE__);
define('ANAM_AI_DA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ANAM_AI_DA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ANAM_AI_DA_OPTION_NAME', 'anam_ai_settings');

require_once ANAM_AI_DA_PLUGIN_DIR . 'includes/class-anam-ai-admin.php';

function anam_ai_da_run() {
    if (!is_admin()) {
        return;
    }
    $admin = new Anam_AI_Admin();
    $admin->init();
}
add_action('plugins_loaded', 'anam_ai_da_run');

function anam_ai_da_activate() {
    $defaults = array(
        'api_key' => '',
        'persona_id' => '',
        'avatar_id' => '',
        'voice_id' => '',
        'llm_id' => 'default',
    );
    $options = get_option(ANAM_AI_DA_OPTION_NAME, array());
    if (!is_array($options)) {
        $options = array();
    }
    update_option(ANAM_AI_DA_OPTION_NAME, array_merge($defaults, $options));
}
register_activation_hook(__FILE__, 'anam_ai_da_activate');

function anam_ai_da_uninstall() {
    delete_option(ANAM_AI_DA_OPTION_NAME);
}
register_uninstall_hook(__FILE__, 'anam_ai_da_uninstall');
