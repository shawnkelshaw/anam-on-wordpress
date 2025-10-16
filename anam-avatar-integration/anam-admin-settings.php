<?php
/**
 * Plugin Name: Anam on WordPress - Admin Settings
 * Description: WordPress admin interface for configuring Anam.ai avatar settings
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 * Plugin URI: https://github.com/shawnkelshaw/anam-on-wordpress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include transcript handler (safe - will self-disable if feature flag is off)
require_once(plugin_dir_path(__FILE__) . 'anam-transcript-handler.php');

// Sessions admin page disabled - functionality removed
// require_once(plugin_dir_path(__FILE__) . 'anam-sessions-admin.php');

class AnamAdminSettings {
    
    private $option_group = 'anam_settings';
    private $option_name = 'anam_options';
    
    public function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        
        // Hook into WordPress
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_footer', array($this, 'add_avatar_integration'));
        add_action('wp_ajax_anam_get_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_get_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_anam_get_session_data', array($this, 'get_session_data'));
        add_action('wp_ajax_anam_list_sessions', array($this, 'list_sessions'));
        add_action('wp_ajax_anam_get_session_details', array($this, 'get_session_details'));
        add_action('wp_ajax_anam_get_session_metadata', array($this, 'get_session_metadata'));
        add_action('wp_ajax_anam_save_transcript', array($this, 'save_transcript'));
        add_action('wp_ajax_nopriv_anam_save_transcript', array($this, 'save_transcript'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Create database table on plugin activation
        register_activation_hook(__FILE__, array($this, 'create_transcripts_table'));
        
        // Also create table on init if it doesn't exist
        add_action('init', array($this, 'ensure_transcripts_table_exists'));
    }
    
    /**
     * Ensure transcripts table exists (runs on every init)
     */
    public function ensure_transcripts_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'anam_transcripts';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log('⚠️ Anam transcripts table missing, creating now...');
            $this->create_transcripts_table();
        }
    }
    
    /**
     * Create database table for storing transcripts
     */
    public function create_transcripts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'anam_transcripts';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            transcript_data longtext NOT NULL,
            message_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('✅ Anam transcripts table created/verified');
    }
    
    public function add_admin_menu() {
        // Add top-level menu (with Getting Started as default page)
        add_menu_page(
            'Anam Avatar',
            'Anam Avatar',
            'manage_options',
            'anam-avatar', // Parent slug
            array($this, 'getting_started_page'),
            'dashicons-admin-users', // Robot/user icon
            5 // Position
        );
        
        // Add Getting Started submenu (first in list, same slug as parent)
        add_submenu_page(
            'anam-avatar',
            'Getting Started',
            'Getting Started',
            'manage_options',
            'anam-avatar', // Same as parent - this makes it first
            array($this, 'getting_started_page')
        );
        
        // Add Avatar Setup submenu (settings)
        add_submenu_page(
            'anam-avatar',
            'Avatar Setup',
            'Avatar Setup',
            'manage_options',
            'anam-settings',
            array($this, 'admin_page')
        );
        
        // Add Display Settings submenu
        add_submenu_page(
            'anam-avatar',
            'Display Settings',
            'Display Settings',
            'manage_options',
            'anam-display-settings',
            array($this, 'display_settings_page')
        );
        
        // Add Chat Transcripts submenu
        add_submenu_page(
            'anam-avatar',
            'Chat Transcripts',
            'Chat Transcripts',
            'manage_options',
            'anam-sessions',
            'anam_render_sessions_page'
        );
        
        // Add Database Integration submenu
        add_submenu_page(
            'anam-avatar',
            'Database Integration',
            'Database Integration',
            'manage_options',
            'anam-supabase-config',
            array($this, 'supabase_config_page')
        );
    }
    
    /**
     * Getting Started page
     */
    public function getting_started_page() {
        ?>
        <div class="wrap">
            <h1>🚀 Getting Started with Anam Avatar</h1>
            
            <div class="notice notice-info">
                <p><strong>Welcome!</strong> This guide will help you set up your Anam.ai avatar integration.</p>
            </div>
            
            <div style="max-width: 800px; margin-top: 30px;">
                <h2>Quick Setup Steps</h2>
                
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                    <h3>1️⃣ Avatar Setup</h3>
                    <p>Set up your Anam.ai API credentials and avatar settings.</p>
                    <a href="<?php echo admin_url('admin.php?page=anam-settings'); ?>" class="button button-primary">Go to Avatar Setup →</a>
                </div>
                
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                    <h3>2️⃣ Display Settings</h3>
                    <p>Configure how and where your avatar appears on your website.</p>
                    <a href="<?php echo admin_url('admin.php?page=anam-display-settings'); ?>" class="button button-primary">Configure Display →</a>
                </div>
                
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                    <h3>3️⃣ Chat Transcripts</h3>
                    <p>View and manage conversation transcripts from your avatar.</p>
                    <a href="<?php echo admin_url('admin.php?page=anam-sessions'); ?>" class="button button-primary">View Transcripts →</a>
                </div>
                
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                    <h3>4️⃣ Database Integration</h3>
                    <p>Configure Supabase database connection for storing conversation data.</p>
                    <a href="<?php echo admin_url('admin.php?page=anam-supabase-config'); ?>" class="button button-primary">Configure Database →</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function init_settings() {
        register_setting($this->option_group, $this->option_name, array($this, 'sanitize_settings'));
        
        // Avatar Configuration Tab
        add_settings_section(
            'anam_api_section',
            'Anam.ai API Configuration',
            array($this, 'api_section_callback'),
            'anam-settings-avatar'
        );
        
        add_settings_section(
            'anam_avatar_section',
            'Avatar Configuration',
            array($this, 'avatar_section_callback'),
            'anam-settings-avatar'
        );
        
        // Display Settings Tab
        add_settings_section(
            'anam_display_section',
            'Display Settings',
            array($this, 'display_section_callback'),
            'anam-settings-display'
        );
        
        // Supabase Configuration Tab
        add_settings_section(
            'anam_supabase_section',
            'Supabase Integration',
            array($this, 'supabase_section_callback'),
            'anam-settings-supabase'
        );
        
        // API Settings
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_field'),
            'anam-settings-avatar',
            'anam_api_section'
        );
        
        add_settings_field(
            'auth_token',
            'Auth Token',
            array($this, 'auth_token_field'),
            'anam-settings-avatar',
            'anam_api_section'
        );
        
        // Avatar Settings
        add_settings_field(
            'persona_id',
            'Persona ID',
            array($this, 'persona_id_field'),
            'anam-settings-avatar',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'avatar_id',
            'Avatar ID',
            array($this, 'avatar_id_field'),
            'anam-settings-avatar',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'voice_id',
            'Voice ID',
            array($this, 'voice_id_field'),
            'anam-settings-avatar',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'llm_id',
            'LLM ID',
            array($this, 'llm_id_field'),
            'anam-settings-avatar',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'system_prompt',
            'System Prompt',
            array($this, 'system_prompt_field'),
            'anam-settings-avatar',
            'anam_avatar_section'
        );
        
        // Display Settings
        add_settings_field(
            'display_method',
            'Display Method',
            array($this, 'display_method_field'),
            'anam-settings-display',
            'anam_display_section'
        );
        
        add_settings_field(
            'container_id',
            'Element ID',
            array($this, 'container_id_field'),
            'anam-settings-display',
            'anam_display_section'
        );
        
        add_settings_field(
            'avatar_position',
            'Avatar Position',
            array($this, 'avatar_position_field'),
            'anam-settings-display',
            'anam_display_section'
        );
        
        add_settings_field(
            'page_selection',
            'Show Avatar On',
            array($this, 'page_selection_field'),
            'anam-settings-display',
            'anam_display_section'
        );
        
        // Supabase Settings
        add_settings_field(
            'supabase_enabled',
            'Enable Supabase Integration',
            array($this, 'supabase_enabled_field'),
            'anam-settings-supabase',
            'anam_supabase_section'
        );
        
        add_settings_field(
            'supabase_url',
            'Supabase URL',
            array($this, 'supabase_url_field'),
            'anam-settings-supabase',
            'anam_supabase_section'
        );
        
        add_settings_field(
            'supabase_key',
            'Supabase API Key',
            array($this, 'supabase_key_field'),
            'anam-settings-supabase',
            'anam_supabase_section'
        );
        
        add_settings_field(
            'supabase_table',
            'Table Name',
            array($this, 'supabase_table_field'),
            'anam-settings-supabase',
            'anam_supabase_section'
        );
        
    }
    
    public function admin_page() {
        $options = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>🤖 Avatar Setup</h1>
            
            <div class="notice notice-info">
                <p><strong>Getting Started:</strong> Configure your Anam.ai credentials below. You can find these values in your <a href="https://app.anam.ai" target="_blank">Anam.ai dashboard</a>.</p>
            </div>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully! 🎉</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php" id="anam-settings-form">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('anam-settings-avatar');
                ?>
                <?php submit_button('Save Settings', 'primary', 'submit', true, array('id' => 'anam-save-settings')); ?>
            </form>
            
            <!-- Auto-verification Modal -->
            <div id="anam-verification-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; justify-content: center; align-items: center;">
                <div style="background: white; padding: 40px; border-radius: 8px; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <div class="anam-spinner" style="margin: 0 auto 20px; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <h2 style="margin: 0 0 10px 0; color: #23282d;">Verifying Your Anam API Key</h2>
                    <p style="margin: 0; color: #666;">Please wait...</p>
                </div>
            </div>
            
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            
            <div class="anam-help-section" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px; max-width: 978px;">
                <h3>📚 Help & Documentation</h3>
                <ul>
                    <li><strong>API Key:</strong> Found in your Anam.ai dashboard under API settings</li>
                    <li><strong>Persona ID:</strong> The unique identifier for your AI persona</li>
                    <li><strong>Avatar ID:</strong> The visual representation of your avatar</li>
                    <li><strong>Voice ID:</strong> The voice model for your avatar</li>
                    <li><strong>LLM ID:</strong> The language model powering your avatar</li>
                </ul>
                <p style="margin-bottom: 15px;"><a href="https://docs.anam.ai" target="_blank">View Anam.ai documentation</a></p>
                <ul>
                    <li><strong>Container ID:</strong> HTML element ID where avatar should appear (optional)</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function display_settings_page() {
        $options = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>📺 Display Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>Configure how and where your avatar appears</strong> on your website.</p>
            </div>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Display settings saved successfully! 🎉</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php" id="anam-display-form">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('anam-settings-display');
                submit_button('Save Display Settings');
                ?>
            </form>
        </div>
        <?php
    }
    
    public function supabase_config_page() {
        $options = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>🗄️ Database Integration</h1>
            
            <div class="notice notice-info">
                <p><strong>Configure Supabase integration</strong> for storing parsed vehicle data. <a href="?page=anam-sessions">View Sessions →</a></p>
            </div>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Supabase settings saved successfully! 🎉</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php" id="anam-supabase-form">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('anam-settings-supabase');
                submit_button('Save Database Settings', 'primary', 'submit', true, array('id' => 'anam-supabase-submit'));
                ?>
            </form>
        </div>
        <?php
    }
    
    public function api_section_callback() {
        echo '<p>Enter your Anam.ai API credentials. These are required for the avatar to function.</p>';
    }
    
    public function avatar_section_callback() {
        echo '<p>Configure your avatar\'s appearance, voice, and behavior.</p>';
    }
    
    public function display_section_callback() {
        echo '<p>Control where and how your avatar appears on your website.</p>';
    }
    
    public function supabase_section_callback() {
        echo '<p>Configure Supabase database connection for storing parsed vehicle data. <a href="?page=anam-sessions">View Sessions →</a></p>';
    }
    
    public function api_key_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        
        echo '<input type="password" id="anam-api-key" name="' . $this->option_name . '[api_key]" value="' . esc_attr($value) . '" class="large-text" placeholder="Enter your Anam.ai API key" />';
        echo '<p class="description">Your Anam.ai API key (base64 encoded Bearer token).</p>';
    }
    
    public function auth_token_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['auth_token']) ? $options['auth_token'] : '';
        
        echo '<input type="password" id="anam-auth-token" name="' . $this->option_name . '[auth_token]" value="' . esc_attr($value) . '" class="large-text" placeholder="Enter your Anam.ai Auth Token" />';
        echo '<p class="description">Your authentication token for API management operations (listing sessions, etc.). This is the base64-encoded Bearer token.</p>';
    }
    
    public function persona_id_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['persona_id']) ? $options['persona_id'] : '';
        echo '<input type="text" name="' . $this->option_name . '[persona_id]" value="' . esc_attr($value) . '" class="regular-text anam-dependent-field" placeholder="e.g., 1bf11606-16b7-4788-a3db-3293148ca7bd" />';
        echo '<p class="description">Unique identifier for your AI persona</p>';
    }
    
    public function avatar_id_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['avatar_id']) ? $options['avatar_id'] : '';
        echo '<input type="text" name="' . $this->option_name . '[avatar_id]" value="' . esc_attr($value) . '" class="regular-text anam-dependent-field" placeholder="e.g., 30fa96d0-26c4-4e55-94a0-517025942e18" />';
        echo '<p class="description">Visual representation ID for your avatar</p>';
    }
    
    public function voice_id_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['voice_id']) ? $options['voice_id'] : '';
        echo '<input type="text" name="' . $this->option_name . '[voice_id]" value="' . esc_attr($value) . '" class="regular-text anam-dependent-field" placeholder="e.g., b7bf471f-5435-49f8-a979-4483e4ccc10f" />';
        echo '<p class="description">Voice model ID for your avatar</p>';
    }
    
    public function llm_id_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['llm_id']) ? $options['llm_id'] : '';
        
        // Determine if we should show custom input
        $is_custom = !in_array($value, ['', '0934d97d-0c3a-4f33-91b0-5e136a0ef466', 'ANAM_LLAMA_v3_3_70B_V1', 'CUSTOMER_CLIENT_V1']);
        $dropdown_value = $is_custom ? 'custom' : $value;
        
        ?>
        <select name="<?php echo $this->option_name; ?>[llm_id]" id="anam-llm-dropdown" class="regular-text anam-dependent-field">
            <option value="" <?php selected($dropdown_value, ''); ?>>Default (Safest if unsure)</option>
            <option value="0934d97d-0c3a-4f33-91b0-5e136a0ef466" <?php selected($dropdown_value, '0934d97d-0c3a-4f33-91b0-5e136a0ef466'); ?>>Standard Anam LLM</option>
            <option value="ANAM_LLAMA_v3_3_70B_V1" <?php selected($dropdown_value, 'ANAM_LLAMA_v3_3_70B_V1'); ?>>Llama 3.3 70B</option>
            <option value="CUSTOMER_CLIENT_V1" <?php selected($dropdown_value, 'CUSTOMER_CLIENT_V1'); ?>>Custom Client Model</option>
            <option value="custom" <?php selected($dropdown_value, 'custom'); ?>>Custom LLM ID</option>
        </select>
        
        <button type="button" id="llm-help-btn" class="button button-link" style="margin-left: 10px;">
            ❓ What's this?
        </button>
        
        <div id="custom-llm-input" style="margin-top: 10px; <?php echo $is_custom ? '' : 'display: none;'; ?>">
            <input type="text" id="custom-llm-id" value="<?php echo $is_custom ? esc_attr($value) : ''; ?>" 
                   class="regular-text" placeholder="Enter custom LLM UUID" />
            <p class="description">Enter your custom LLM ID (UUID format)</p>
        </div>
        
        <p class="description">Language model powering your avatar. Most personas work with the default setting.</p>
        
        <!-- LLM Help Modal -->
        <div id="llm-help-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 600px; position: relative;">
                <span id="llm-modal-close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                
                <h3 style="margin-top: 0;">LLM (Language Model) Options</h3>
                
                <div style="margin-bottom: 20px;">
                    <h4>Available Options:</h4>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #46b450;">
                        <strong>Default (Safest if unsure)</strong><br>
                        <em>Recommended for most users</em><br>
                        Most Anam.ai personas have a default LLM configured. This is the safest choice if you're unsure.
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                        <strong>Standard Anam LLM</strong><br>
                        <code>0934d97d-0c3a-4f33-91b0-5e136a0ef466</code><br>
                        General purpose language model optimized for conversational AI.
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #d63638;">
                        <strong>Llama 3.3 70B</strong><br>
                        <code>ANAM_LLAMA_v3_3_70B_V1</code><br>
                        Advanced open-source model with 70 billion parameters. More capable but may be slower.
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #dba617;">
                        <strong>Custom Client Model</strong><br>
                        <code>CUSTOMER_CLIENT_V1</code><br>
                        For custom LLM integrations and specialized use cases.
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #72777c;">
                        <strong>Custom LLM ID</strong><br>
                        Enter your own LLM UUID if you have a custom model configured in your Anam.ai account.
                    </div>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <strong>💡 Pro Tip:</strong> Start with "Default (Safest if unsure)" - it works for most personas. Only change if your specific persona requires a different LLM.
                </div>
                
                <div style="text-align: right;">
                    <button type="button" id="llm-modal-ok" class="button button-primary">Got it!</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function system_prompt_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['system_prompt']) ? $options['system_prompt'] : 'You are a helpful digital assistant. Be friendly and concise in your responses.';
        echo '<textarea name="' . $this->option_name . '[system_prompt]" rows="12" class="large-text anam-dependent-field" style="width: 100%; max-width: 978px; resize: vertical;" placeholder="Enter system prompt for your avatar...">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Instructions that define your avatar\'s personality and behavior. No character limit - write as much as you need!</p>';
    }
    
    public function display_method_field() {
        $options = get_option($this->option_name, array());
        
        // Handle backward compatibility
        $display_method = 'element_id'; // Default as requested
        if (isset($options['display_method'])) {
            $display_method = $options['display_method'];
        } else {
            // Migrate from old settings
            if (isset($options['avatar_position']) && $options['avatar_position'] === 'custom') {
                $display_method = 'element_id';
            } else if (isset($options['avatar_position']) || isset($options['target_pages'])) {
                $display_method = 'page_position';
            }
        }
        ?>
        <div id="display-method-radios">
            <label style="display: block; margin-bottom: 10px;">
                <input type="radio" name="<?php echo $this->option_name; ?>[display_method]" value="element_id" <?php checked($display_method, 'element_id'); ?> />
                <strong>By Element ID</strong> - Stream avatar to a specific HTML element on your pages
            </label>
            <label style="display: block;">
                <input type="radio" name="<?php echo $this->option_name; ?>[display_method]" value="page_position" <?php checked($display_method, 'page_position'); ?> />
                <strong>By Page and Position</strong> - Show avatar at fixed position on selected pages
            </label>
        </div>
        <p class="description">Choose how you want to display the avatar on your website</p>
        <?php
    }
    
    public function container_id_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['container_id']) ? $options['container_id'] : '';
        
        echo '<div id="element-id-section">';
        echo '<input type="text" id="anam-container-id" name="' . $this->option_name . '[container_id]" value="' . esc_attr($value) . '" class="regular-text" placeholder="element-id" />';
        echo '<p class="description">HTML element ID where the avatar should appear (e.g., "anam-stream-container")</p>';
        echo '</div>';
    }
    
    public function avatar_position_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['avatar_position']) ? $options['avatar_position'] : 'bottom-right';
        ?>
        <div id="page-position-section">
            <select name="<?php echo $this->option_name; ?>[avatar_position]" id="anam-avatar-position">
                <option value="bottom-right" <?php selected($value, 'bottom-right'); ?>>Bottom Right</option>
                <option value="bottom-left" <?php selected($value, 'bottom-left'); ?>>Bottom Left</option>
                <option value="top-right" <?php selected($value, 'top-right'); ?>>Top Right</option>
                <option value="top-left" <?php selected($value, 'top-left'); ?>>Top Left</option>
            </select>
            <p class="description">Choose where the avatar appears on your site</p>
        </div>
        <?php
    }
    
    public function supabase_enabled_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['supabase_enabled']) ? $options['supabase_enabled'] : false;
        echo '<label><input type="checkbox" id="supabase-enable" name="' . $this->option_name . '[supabase_enabled]" value="1" ' . checked($value, true, false) . ' /> Enable Supabase integration</label>';
        echo '<p class="description">Turn on to store parsed vehicle data in Supabase</p>';
    }
    
    public function supabase_url_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['supabase_url']) ? $options['supabase_url'] : '';
        echo '<input type="url" name="' . $this->option_name . '[supabase_url]" value="' . esc_attr($value) . '" class="regular-text supabase-field" placeholder="https://your-project.supabase.co" />';
        echo '<p class="description">Your Supabase project URL (e.g., https://xxxxx.supabase.co)</p>';
    }
    
    public function supabase_key_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['supabase_key']) ? $options['supabase_key'] : '';
        echo '<input type="password" name="' . $this->option_name . '[supabase_key]" value="' . esc_attr($value) . '" class="regular-text supabase-field" placeholder="Enter Supabase API key" />';
        echo '<p class="description">Your Supabase anon or service role key</p>';
    }
    
    public function supabase_table_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['supabase_table']) ? $options['supabase_table'] : 'vehicle_conversations';
        echo '<input type="text" name="' . $this->option_name . '[supabase_table]" value="' . esc_attr($value) . '" class="regular-text supabase-field" placeholder="vehicle_conversations" />';
        echo '<p class="description">Name of the table to store vehicle data (default: vehicle_conversations)</p>';
    }
    
    public function page_selection_field() {
        $options = get_option($this->option_name, array());
        $selected_pages = isset($options['selected_pages']) ? $options['selected_pages'] : array('homepage');
        
        if (!is_array($selected_pages)) {
            $selected_pages = array('homepage'); // Default to homepage selected
        }
        
        echo '<div id="page-selection-section">';
        echo '<div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">';
        
        // Quick categories
        echo '<h4 style="margin-top: 0;">Quick Options:</h4>';
        echo '<label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="' . $this->option_name . '[selected_pages][]" value="all_posts" ' . (in_array('all_posts', $selected_pages) ? 'checked' : '') . ' /> <strong>All Posts</strong></label>';
        echo '<label style="display: block; margin-bottom: 15px;"><input type="checkbox" name="' . $this->option_name . '[selected_pages][]" value="all_pages" ' . (in_array('all_pages', $selected_pages) ? 'checked' : '') . ' /> <strong>All Pages</strong></label>';
        
        // Individual pages
        echo '<h4>Individual Pages:</h4>';
        
        // Home page first (always show, selected by default)
        $homepage_checked = in_array('homepage', $selected_pages) ? 'checked' : '';
        echo '<label id="homepage-checkbox" style="display: block; margin-bottom: 5px;"><input type="checkbox" name="' . $this->option_name . '[selected_pages][]" value="homepage" ' . $homepage_checked . ' /> <strong>Home</strong></label>';
        
        // Get all pages and sort alphabetically, excluding homepage
        $homepage_id = get_option('page_on_front');
        $pages = get_pages(array('number' => 50, 'sort_column' => 'post_title', 'sort_order' => 'ASC'));
        if (!empty($pages)) {
            foreach ($pages as $page) {
                // Skip the homepage if it's set as a static page (we already show "Home" above)
                if ($homepage_id && $page->ID == $homepage_id) {
                    continue;
                }
                
                $page_value = 'page_' . $page->ID;
                $checked = in_array($page_value, $selected_pages) ? 'checked' : '';
                echo '<label class="individual-page-checkbox" style="display: block; margin-bottom: 5px;"><input type="checkbox" name="' . $this->option_name . '[selected_pages][]" value="' . $page_value . '" ' . $checked . ' /> ' . esc_html($page->post_title) . '</label>';
            }
        }
        
        // Individual posts
        $posts = get_posts(array('numberposts' => 20, 'post_status' => 'publish'));
        if (!empty($posts)) {
            echo '<h4 style="margin-top: 15px;">Recent Posts:</h4>';
            foreach ($posts as $post) {
                $post_value = 'post_' . $post->ID;
                $checked = in_array($post_value, $selected_pages) ? 'checked' : '';
                echo '<label class="individual-post-checkbox" style="display: block; margin-bottom: 5px;"><input type="checkbox" name="' . $this->option_name . '[selected_pages][]" value="' . $post_value . '" ' . $checked . ' /> ' . esc_html($post->post_title) . '</label>';
            }
        }
        
        echo '</div>';
        echo '<p class="description">Select where you want the avatar to appear. Homepage is selected by default.</p>';
        echo '</div>';
    }
    
    
    public function sanitize_settings($input) {
        // Start with existing saved options to preserve data from other pages
        $existing_options = get_option($this->option_name, array());
        $sanitized = $existing_options;
        
        if (isset($input['api_key'])) {
            // Don't sanitize API key - it contains special characters like colons and equals
            $sanitized['api_key'] = trim($input['api_key']);
        }
        
        if (isset($input['auth_token'])) {
            // Don't sanitize auth token - it's base64 encoded and contains special characters
            $sanitized['auth_token'] = trim($input['auth_token']);
        }
        
        if (isset($input['persona_id'])) {
            $sanitized['persona_id'] = sanitize_text_field($input['persona_id']);
        }
        
        if (isset($input['avatar_id'])) {
            $sanitized['avatar_id'] = sanitize_text_field($input['avatar_id']);
        }
        
        if (isset($input['voice_id'])) {
            $sanitized['voice_id'] = sanitize_text_field($input['voice_id']);
        }
        
        if (isset($input['llm_id'])) {
            $llm_value = sanitize_text_field($input['llm_id']);
            // Handle custom LLM ID case
            if ($llm_value === 'custom' && isset($_POST['custom_llm_id'])) {
                $sanitized['llm_id'] = sanitize_text_field($_POST['custom_llm_id']);
            } else {
                $sanitized['llm_id'] = $llm_value;
            }
        }
        
        if (isset($input['system_prompt'])) {
            $sanitized['system_prompt'] = sanitize_textarea_field($input['system_prompt']);
        }
        
        // Display Method
        if (isset($input['display_method'])) {
            $sanitized['display_method'] = sanitize_text_field($input['display_method']);
        }
        
        // Container ID - only save if display method is element_id
        if (isset($input['container_id'])) {
            if (isset($input['display_method']) && $input['display_method'] === 'element_id') {
                $sanitized['container_id'] = sanitize_text_field($input['container_id']);
            } else {
                $sanitized['container_id'] = ''; // Clear container ID for page_position method
            }
        }
        
        // Avatar Position - only save if display method is page_position
        if (isset($input['avatar_position'])) {
            if (isset($input['display_method']) && $input['display_method'] === 'page_position') {
                $sanitized['avatar_position'] = sanitize_text_field($input['avatar_position']);
            } else {
                $sanitized['avatar_position'] = 'bottom-right'; // Default position
            }
        }
        
        // Selected Pages - only save if display method is page_position
        if (isset($input['selected_pages']) && is_array($input['selected_pages'])) {
            if (isset($input['display_method']) && $input['display_method'] === 'page_position') {
                $sanitized['selected_pages'] = array_map('sanitize_text_field', $input['selected_pages']);
            } else {
                $sanitized['selected_pages'] = array(); // Clear selected pages for element_id method
            }
        } else {
            $sanitized['selected_pages'] = array();
        }
        
        // Legacy fields for backward compatibility (will be migrated)
        if (isset($input['target_pages'])) {
            $sanitized['target_pages'] = sanitize_text_field($input['target_pages']);
        }
        
        if (isset($input['custom_slugs'])) {
            $sanitized['custom_slugs'] = sanitize_text_field($input['custom_slugs']);
        }
        
        // Supabase settings
        $sanitized['supabase_enabled'] = isset($input['supabase_enabled']) ? true : false;
        
        if (isset($input['supabase_url'])) {
            $sanitized['supabase_url'] = esc_url_raw($input['supabase_url']);
        }
        
        if (isset($input['supabase_key'])) {
            $sanitized['supabase_key'] = sanitize_text_field($input['supabase_key']);
        }
        
        if (isset($input['supabase_table'])) {
            $sanitized['supabase_table'] = sanitize_text_field($input['supabase_table']);
        }
        
        // Email notifications
        $sanitized['email_notifications'] = isset($input['email_notifications']) ? true : false;
        
        // Advanced SDK functionality
        $sanitized['advanced_sdk_enabled'] = isset($input['advanced_sdk_enabled']) ? true : false;
        
        return $sanitized;
    }
    
    public function enqueue_admin_scripts($hook) {
        // Debug: Log the hook to see what it actually is
        error_log('Admin hook: ' . $hook);
        
        // Enqueue on ALL admin pages for now to test
        if (strpos($hook, 'anam') !== false || $hook === 'anam-avatar_page_anam-settings' || $hook === 'settings_page_anam-settings') {
            error_log('✅ Enqueuing anam-admin.js on hook: ' . $hook);
            
            wp_enqueue_script('anam-admin', plugin_dir_url(__FILE__) . 'anam-admin.js', array('jquery'), '2.0.2', true);
            
            // Pass both anam_ajax (for verification) and ANAM_CONFIG (for sessions)
            wp_localize_script('anam-admin', 'anam_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('anam_verify_api'),
                'admin_nonce' => wp_create_nonce('anam_admin_nonce')
            ));
            
            wp_localize_script('anam-admin', 'ANAM_CONFIG', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('anam_session')
            ));
            
            error_log('✅ Localized ANAM_CONFIG with ajaxUrl and nonce');
        } else {
            error_log('❌ Hook does not match anam pattern: ' . $hook);
        }
        
        // Enqueue on Getting Started page
        if ($hook === 'toplevel_page_anam-avatar') {
            wp_enqueue_script('anam-getting-started', plugin_dir_url(__FILE__) . 'anam-getting-started.js', array('jquery'), '1.0.5', true);
            wp_localize_script('anam-getting-started', 'anam_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_nonce' => wp_create_nonce('anam_admin_nonce')
            ));
        }
    }
    
    public function add_avatar_integration() {
        if (!$this->should_show_avatar()) {
            return;
        }
        
        $options = get_option($this->option_name, array());
        
        // Check if we have minimum required settings
        if (empty($options['api_key']) || empty($options['persona_id'])) {
            return;
        }
        
        $display_method = isset($options['display_method']) ? $options['display_method'] : 'element_id';
        $container_id = !empty($options['container_id']) ? $options['container_id'] : '';
        $position = isset($options['avatar_position']) ? $options['avatar_position'] : 'bottom-right';
        
        ?>
        <!-- Anam Avatar Integration -->
        <?php if ($display_method === 'page_position'): ?>
        <div id="anam-avatar-widget" style="position: fixed; <?php echo $this->get_position_styles($position); ?>; z-index: 9999; width: 300px; background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); overflow: hidden;">
            <div style="padding: 20px; text-align: center; position: relative;">
                <div style="font-size: 32px; margin-bottom: 15px;">💬</div>
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Ready to chat?</h3>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.4;">
                    Start a conversation with our AI assistant
                </p>
                <button id="anam-start-btn" style="padding: 12px 24px; border: none; background: #007cba; color: white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
                    Start Conversation
                </button>
            </div>
        </div>
        <?php endif; ?>


        <script type="module">
        console.log('🎯 Anam Avatar - Starting...');
        
        import { createClient, AnamEvent } from "https://esm.sh/@anam-ai/js-sdk@3.5.1/es2022/js-sdk.mjs";
        
        const ANAM_CONFIG = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('anam_session'); ?>',
            displayMethod: '<?php echo esc_js($display_method); ?>',
            containerId: '<?php echo esc_js($container_id); ?>',
            position: '<?php echo esc_js($position); ?>'
        };
        
        let anamClient = null;
        let conversationTranscript = [];
        let currentSessionId = null;
        
        // Function to update transcript debug box
        function updateTranscriptDebugBox(messages) {
            const transcriptMessages = document.getElementById('transcript-messages');
            if (!transcriptMessages) {
                console.log('⚠️ Transcript debug box not found');
                return;
            }
            
            if (!messages || messages.length === 0) {
                transcriptMessages.innerHTML = '<em style="color: #999;">Waiting for conversation to start...</em>';
                return;
            }
            
            let html = '';
            messages.forEach((msg, index) => {
                const isUser = msg.type === 'user' || msg.role === 'user';
                const bgColor = isUser ? '#e3f2fd' : '#f1f8e9';
                const label = isUser ? '👤 User' : '🤖 Avatar';
                const text = msg.text || msg.content || msg.message || '';
                
                html += `<div style="margin-bottom: 10px; padding: 8px; background: ${bgColor}; border-radius: 6px;">
                    <div style="font-weight: bold; font-size: 11px; color: #666; margin-bottom: 4px;">${label}</div>
                    <div style="color: #333;">${text}</div>
                </div>`;
            });
            
            transcriptMessages.innerHTML = html;
            
            // Auto-scroll to bottom
            const transcriptDebug = document.getElementById('transcript-debug');
            if (transcriptDebug) {
                transcriptDebug.scrollTop = transcriptDebug.scrollHeight;
            }
        }
        
        // Missing function that's being called - add placeholder
        function showElementTokenIcon(success) {
            console.log('showElementTokenIcon called with:', success);
            // This function was being called but didn't exist, causing the ReferenceError
        }
        
        // Helper function to extract session ID from token (if possible)
        function extractSessionIdFromToken(token) {
            try {
                // JWT tokens have 3 parts separated by dots
                const parts = token.split('.');
                if (parts.length === 3) {
                    // Decode the payload (second part)
                    const payload = JSON.parse(atob(parts[1]));
                    console.log('🔍 Token payload:', payload);
                    
                    // Look for session ID in various possible fields
                    const sessionId = payload.sessionId || payload.session_id || payload.sid || payload.sub || payload.jti || null;
                    console.log('🆔 Extracted session ID:', sessionId);
                    return sessionId;
                }
            } catch (error) {
                console.log('⚠️ Could not decode token:', error);
            }
            return null;
        }
        
        // Alternative: Use Anam client session ID directly
        function getSessionIdFromClient() {
            try {
                if (anamClient && anamClient.sessionId) {
                    console.log('🎯 Using client session ID:', anamClient.sessionId);
                    return anamClient.sessionId;
                }
                if (anamClient && anamClient.session && anamClient.session.id) {
                    console.log('🎯 Using client session.id:', anamClient.session.id);
                    return anamClient.session.id;
                }
            } catch (error) {
                console.log('⚠️ Could not get session ID from client:', error);
                console.error('❌ Error extracting session ID from token:', error);
            }
            return null;
        }
        
        // Function to save transcript to database
        async function saveTranscriptToDatabase(sessionId) {
            if (!sessionId) {
                console.log('📝 No session ID to save transcript');
                return;
            }
            
            if (!conversationTranscript || conversationTranscript.length === 0) {
                console.log('📝 No transcript data to save');
                return;
            }
            
            try {
                console.log('💾 Saving transcript to database...');
                console.log('📋 Session ID:', sessionId);
                console.log('💬 Messages:', conversationTranscript.length);
                
                // Clean the transcript data - convert to plain array
                const cleanTranscript = Array.isArray(conversationTranscript) 
                    ? conversationTranscript.map(msg => ({
                        type: msg.type || msg.role || 'unknown',
                        text: msg.text || msg.content || msg.message || '',
                        timestamp: msg.timestamp || new Date().toISOString()
                    }))
                    : [];
                
                console.log('📦 Clean transcript:', cleanTranscript);
                
                const response = await fetch(ANAM_CONFIG.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'anam_save_transcript',
                        nonce: ANAM_CONFIG.nonce,
                        session_id: sessionId,
                        transcript_data: JSON.stringify(cleanTranscript)
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Transcript saved successfully:', data.data);
                } else {
                    console.error('❌ Failed to save transcript:', data.data);
                }
            } catch (error) {
                console.error('❌ Error saving transcript:', error);
            }
        }
        
        // Real-time message sending to server
        async function sendMessageToServer(messageData) {
            if (!currentSessionId) {
                console.log('⚠️ No session ID available for message sending');
                return;
            }
            
            try {
                console.log('📤 Sending message to server:', messageData);
                console.log('📤 Session ID:', currentSessionId);
                console.log('📤 Nonce:', ANAM_CONFIG.nonce);
                
                const response = await fetch(ANAM_CONFIG.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'anam_store_message',
                        nonce: ANAM_CONFIG.nonce,
                        session_id: currentSessionId,
                        message_data: JSON.stringify(messageData),
                        timestamp: messageData.timestamp
                    })
                });
                
                console.log('📤 Response status:', response.status);
                const data = await response.json();
                console.log('📤 Response data:', data);
                
                if (data.success) {
                    console.log('✅ Message stored:', data.data);
                } else {
                    console.error('❌ Message storage error:', data.data);
                }
            } catch (error) {
                console.error('❌ Failed to send message to server:', error);
            }
        }
        
        // Mark conversation as complete
        async function markConversationComplete() {
            if (!currentSessionId) return;
            
            try {
                console.log('🏁 Marking conversation complete for session:', currentSessionId);
                
                const response = await fetch(ANAM_CONFIG.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'anam_conversation_complete',
                        nonce: ANAM_CONFIG.nonce,
                        session_id: currentSessionId,
                        transcript: JSON.stringify(conversationTranscript)
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('✅ Conversation marked complete:', data.data);
                } else {
                    console.error('❌ Conversation completion error:', data.data);
                }
            } catch (error) {
                console.error('❌ Failed to mark conversation complete:', error);
            }
        }
        
        // Finalize conversation (session ended)
        async function finalizeConversation() {
            if (!currentSessionId) return;
            
            try {
                console.log('🔚 Finalizing conversation for session:', currentSessionId);
                
                // Send final transcript and trigger parsing
                const response = await fetch(ANAM_CONFIG.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'anam_finalize_conversation',
                        nonce: ANAM_CONFIG.nonce,
                        session_id: currentSessionId,
                        transcript: JSON.stringify(conversationTranscript)
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('✅ Conversation finalized and parsed:', data.data);
                } else {
                    console.error('❌ Conversation finalization error:', data.data);
                }
            } catch (error) {
                console.error('❌ Failed to finalize conversation:', error);
            }
        }
        
        // Handle widget button interactions
        if (ANAM_CONFIG.displayMethod === 'page_position') {
            const widget = document.getElementById('anam-avatar-widget');
            const startBtn = document.getElementById('anam-start-btn');
            
            // Start conversation button
            startBtn.addEventListener('click', () => {
                initAvatar();
            });
        } else {
            // For element_id method, show welcome screen in container
            showElementIdWelcome();
        }
        
        function updateStatus(message, isError = false) {
            console.log(isError ? '❌' : '🎯', message.replace(/<[^>]*>/g, ''));
        }
        
        function showElementIdWelcome() {
            console.log('🎯 Setting up Element ID welcome screen...');
            
            const targetElement = ANAM_CONFIG.containerId;
            
            if (!targetElement) {
                console.error('❌ Element ID method selected but no container ID specified');
                return;
            }
            
            const customContainer = document.getElementById(targetElement);
            if (!customContainer) {
                console.error(`❌ Container "${targetElement}" not found on this page`);
                return;
            }
            
            // Show welcome screen in the custom container
            customContainer.innerHTML = `
                <div style="padding: 20px; text-align: center; position: relative; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <div style="font-size: 32px; margin-bottom: 15px;">💬</div>
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Ready to chat?</h3>
                    <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.4;">
                        Start a conversation with our AI assistant
                    </p>
                    <button id="anam-element-start-btn" style="padding: 12px 24px; border: none; background: #007cba; color: white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
                        Start Conversation
                    </button>
                </div>
            `;
            
            // Set container positioning for buttons
            customContainer.style.position = 'relative';
            
            // Add start button event listener
            const startBtn = document.getElementById('anam-element-start-btn');
            startBtn.addEventListener('click', () => {
                initElementIdAvatar();
            });
            
            console.log('✅ Element ID welcome screen ready');
        }
        
        async function initElementIdAvatar() {
            console.log('🎯 Initializing Element ID avatar...');
            
            const targetElement = ANAM_CONFIG.containerId;
            const customContainer = document.getElementById(targetElement);
            
            if (!customContainer) {
                console.error(`❌ Container "${targetElement}" not found`);
                return;
            }
            
            try {
                // Show loading state
                customContainer.innerHTML = `
                    <div style="padding: 40px 20px; text-align: center; background: #f8f9fa; border-radius: 12px;">
                        <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e3e3e3; border-top: 4px solid #007cba; border-radius: 50%; animation: anam-spin 1s linear infinite; margin-bottom: 15px;"></div>
                        <div style="color: #666; font-size: 14px;">Setting up your avatar...</div>
                        <div style="color: #999; font-size: 12px; margin-top: 5px;">This may take a moment</div>
                    </div>
                    <style>
                        @keyframes anam-spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `;
                
                // Get session token and create client
                const sessionToken = await getSessionToken();
                updateStatus('✅ Token obtained');
                
                updateStatus('🔧 Creating client...');
                anamClient = createClient(sessionToken);
                console.log('✅ Client created:', anamClient);
                
                // Add transcript capture event listener with debug display
                anamClient.addListener(AnamEvent.MESSAGE_HISTORY_UPDATED, (messages) => {
                    conversationTranscript = messages;
                    console.log('📝 Transcript updated:', messages.length, 'messages');
                    updateTranscriptDebugBox(messages);
                });
                
                // Add listener for when streaming starts (session ID might be available then)
                anamClient.addListener(AnamEvent.STREAM_STARTED, () => {
                    console.log('🎬 Stream started, checking for session ID again...');
                    if (!currentSessionId) {
                        try {
                            if (anamClient.sessionId) {
                                currentSessionId = anamClient.sessionId;
                                console.log('📋 Session ID captured after stream start:', currentSessionId);
                            } else if (anamClient.session && anamClient.session.id) {
                                currentSessionId = anamClient.session.id;
                                console.log('📋 Session ID captured from session after stream start:', currentSessionId);
                            }
                        } catch (error) {
                            console.error('❌ Error capturing session ID after stream start:', error);
                        }
                    }
                });
                
                // Try to capture session ID from client
                try {
                    console.log('🔍 Inspecting anamClient for session ID:', anamClient);
                    
                    // Method 1: Check if session ID is available in client properties
                    if (anamClient.sessionId) {
                        currentSessionId = anamClient.sessionId;
                        console.log('📋 Session ID captured from client.sessionId:', currentSessionId);
                    } else if (anamClient.session && anamClient.session.id) {
                        currentSessionId = anamClient.session.id;
                        console.log('📋 Session ID captured from client.session.id:', currentSessionId);
                    } else {
                        console.log('⚠️ Session ID not immediately available in client properties');
                        console.log('🔍 Available client properties:', Object.keys(anamClient));
                        
                        // Try to extract from session token (if it contains session info)
                        currentSessionId = extractSessionIdFromToken(sessionToken);
                        if (currentSessionId) {
                            console.log('📋 Session ID extracted from token:', currentSessionId);
                        } else {
                            console.log('⚠️ Could not extract session ID from token either');
                        }
                    }
                } catch (error) {
                    console.error('❌ Error capturing session ID:', error);
                }
                
                updateStatus('📹 Starting stream...');
                
                // Create video element (hidden initially)
                const video = document.createElement('video');
                video.id = 'anam-element-video';
                video.width = 400;
                video.height = 300;
                video.autoplay = true;
                video.playsInline = true;
                video.muted = false;
                video.controls = false;
                video.style.cssText = 'width: 100%; height: auto; border-radius: 12px; background: #000; display: none;';
                
                // Add video to container (hidden)
                customContainer.appendChild(video);
                
                // Stream to video element
                await anamClient.streamToVideoElement('anam-element-video');
                
                console.log('🎬 Streaming to element completed');
                
                // Replace loading with video and controls
                customContainer.innerHTML = '';
                video.style.display = 'block';
                customContainer.appendChild(video);
                
                // Add transcript debug box
                const transcriptDebug = document.createElement('div');
                transcriptDebug.id = 'transcript-debug';
                transcriptDebug.style.cssText = 'margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; max-height: 300px; overflow-y: auto;';
                transcriptDebug.innerHTML = '<div style="font-weight: bold; margin-bottom: 10px; color: #666;">📝 Live Transcript</div><div id="transcript-messages" style="font-size: 13px; line-height: 1.6;"><em style="color: #999;">Waiting for conversation to start...</em></div>';
                customContainer.appendChild(transcriptDebug);
                
                // Add expand button
                const expandBtn = document.createElement('button');
                expandBtn.innerHTML = '⛶';
                expandBtn.title = 'Expand to full screen';
                expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
                expandBtn.addEventListener('click', () => {
                    expandElementToModal(customContainer, video);
                });
                customContainer.appendChild(expandBtn);
                
                // Add close button
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '&times;';
                closeBtn.title = 'Close avatar';
                closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
                closeBtn.addEventListener('click', async () => {
                    await closeElementIdAvatar(customContainer);
                });
                customContainer.appendChild(closeBtn);
                
                console.log('✅ Element ID avatar ready');
                
            } catch (error) {
                console.error('❌ Error initializing Element ID avatar:', error);
                customContainer.innerHTML = `
                    <div style="padding: 20px; text-align: center; background: #fee; border-radius: 12px; color: #c33;">
                        <div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>
                        <div style="font-size: 14px; font-weight: bold;">Avatar initialization failed</div>
                        <div style="font-size: 12px; margin-top: 5px;">${error.message}</div>
                        <button onclick="showElementIdWelcome()" style="margin-top: 15px; padding: 8px 16px; border: none; background: #007cba; color: white; border-radius: 4px; cursor: pointer;">Try Again</button>
                    </div>
                `;
            }
        }
        
        function expandElementToModal(container, video) {
            console.log('🔄 Expanding element avatar to modal...');
            
            // Store reference to original container
            originalWidget = container;
            
            // Create modal
            const { modal, modalContent } = createModal();
            currentModal = modal;
            
            // Add collapse button to modal (left side)
            const collapseBtn = document.createElement('button');
            collapseBtn.innerHTML = '⤡';
            collapseBtn.title = 'Return to container';
            collapseBtn.style.cssText = 'position: absolute; top: 15px; left: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 18px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
            collapseBtn.addEventListener('click', () => collapseElementToContainer(container, video));
            modalContent.appendChild(collapseBtn);
            
            // Add close button to modal (right side)
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.title = 'Close avatar';
            closeBtn.style.cssText = 'position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 20px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
            closeBtn.addEventListener('click', async () => {
                await closeElementIdAvatar(container);
            });
            modalContent.appendChild(closeBtn);
            
            // Move video to modal
            video.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 12px;';
            modalContent.appendChild(video);
            
            // Hide original container
            container.style.display = 'none';
            
            // Add backdrop click to collapse
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    collapseElementToContainer(container, video);
                }
            });
            
            // Add keyboard support
            const handleKeydown = (e) => {
                if (e.key === 'Escape') {
                    collapseElementToContainer(container, video);
                    document.removeEventListener('keydown', handleKeydown);
                }
            };
            document.addEventListener('keydown', handleKeydown);
            
            // Add modal to page
            document.body.appendChild(modal);
            
            // Fade in modal
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
            
            console.log('✅ Element avatar expanded to modal');
        }
        
        function collapseElementToContainer(container, video) {
            console.log('🔄 Collapsing avatar to container...');
            
            if (!currentModal) {
                console.error('❌ No modal reference found');
                return;
            }
            
            // Restore video styling for container
            video.style.cssText = 'width: 100%; height: auto; border-radius: 12px; background: #000; display: block;';
            
            // Move video back to container
            container.innerHTML = '';
            container.appendChild(video);
            
            // Re-add buttons to container
            const expandBtn = document.createElement('button');
            expandBtn.innerHTML = '⛶';
            expandBtn.title = 'Expand to full screen';
            expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
            expandBtn.addEventListener('click', () => {
                expandElementToModal(container, video);
            });
            container.appendChild(expandBtn);
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.title = 'Close avatar';
            closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
            closeBtn.addEventListener('click', async () => {
                await closeElementIdAvatar(container);
            });
            container.appendChild(closeBtn);
            
            // Show original container
            container.style.display = 'block';
            
            // Remove modal
            currentModal.style.opacity = '0';
            setTimeout(() => {
                if (currentModal) {
                    currentModal.remove();
                    currentModal = null;
                }
            }, 300);
            
            console.log('✅ Avatar collapsed to container');
        }
        
        async function closeElementIdAvatar(container) {
            console.log('🚫 Closing Element ID avatar completely...');
            
            try {
                // Process session before stopping stream
                // Try to get session ID from multiple sources
                let sessionIdToProcess = currentSessionId || getSessionIdFromClient();
                
                if (!sessionIdToProcess && anamClient) {
                    // Try to get from Anam client properties
                    console.log('🔍 Searching anamClient for session ID...');
                    console.log('🔍 anamClient keys:', Object.keys(anamClient));
                    
                    // Check various possible locations
                    sessionIdToProcess = anamClient.sessionId || 
                                       (anamClient.session && anamClient.session.id) ||
                                       (anamClient._session && anamClient._session.id) ||
                                       (anamClient.connection && anamClient.connection.sessionId);
                }
                
                if (sessionIdToProcess) {
                    console.log('📋 Processing session:', sessionIdToProcess);
                    console.log('💬 Transcript messages:', conversationTranscript.length);
                    console.log('📝 Transcript data:', conversationTranscript);
                    
                    // Save transcript to database
                    await saveTranscriptToDatabase(sessionIdToProcess);
                    
                    currentSessionId = null; // Clear session ID after sending
                } else {
                    console.log('⚠️ No session ID available for processing');
                    console.log('🔍 Final debug - anamClient structure:', anamClient);
                }
                
                conversationTranscript = []; // Clear transcript data
                
                // Stop streaming if client exists
                if (anamClient) {
                    console.log('🛑 Stopping avatar stream...');
                    await anamClient.stopStreaming();
                    console.log('✅ Stream stopped');
                }
            } catch (error) {
                console.error('❌ Error during close:', error);
            }
            
            // Remove modal if it exists
            if (currentModal) {
                currentModal.remove();
                currentModal = null;
            }
            
            // Reset client
            anamClient = null;
            originalWidget = null;
            
            // Return to welcome screen
            showElementIdWelcome();
            
            console.log('✅ Element ID avatar closed and reset to welcome screen');
        }
        
        // Modal and expand/collapse functionality
        let currentModal = null;
        let originalWidget = null;
        
        function createModal() {
            const modal = document.createElement('div');
            modal.id = 'anam-avatar-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.9);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                position: relative;
                width: 80vw;
                height: 60vh;
                max-width: 800px;
                max-height: 600px;
                background: #000;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            `;
            
            modal.appendChild(modalContent);
            return { modal, modalContent };
        }
        
        function expandToModal() {
            console.log('🔄 Expanding avatar to modal...');
            
            const widget = document.getElementById('anam-avatar-widget');
            const video = widget.querySelector('#anam-default-video');
            
            if (!video) {
                console.error('❌ No video element found to expand');
                return;
            }
            
            // Store reference to original widget
            originalWidget = widget;
            
            // Create modal
            const { modal, modalContent } = createModal();
            currentModal = modal;
            
            // Add collapse button to modal (left side)
            const collapseBtn = document.createElement('button');
            collapseBtn.innerHTML = '⤡';
            collapseBtn.title = 'Return to widget';
            collapseBtn.style.cssText = 'position: absolute; top: 15px; left: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 18px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
            collapseBtn.addEventListener('click', collapseToWidget);
            modalContent.appendChild(collapseBtn);
            
            // Add close button to modal (right side)
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.title = 'Close avatar';
            closeBtn.style.cssText = 'position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 20px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
            closeBtn.addEventListener('click', async () => {
                await closeAvatarCompletely();
            });
            modalContent.appendChild(closeBtn);
            
            // Move video to modal
            video.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 12px;';
            modalContent.appendChild(video);
            
            // Hide original widget
            widget.style.display = 'none';
            
            // Add backdrop click to collapse
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    collapseToWidget();
                }
            });
            
            // Add keyboard support
            const handleKeydown = (e) => {
                if (e.key === 'Escape') {
                    collapseToWidget();
                    document.removeEventListener('keydown', handleKeydown);
                }
            };
            document.addEventListener('keydown', handleKeydown);
            
            // Add modal to page
            document.body.appendChild(modal);
            
            // Fade in modal
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
            
            console.log('✅ Avatar expanded to modal');
        }
        
        function collapseToWidget() {
            console.log('🔄 Collapsing avatar to widget...');
            
            if (!currentModal || !originalWidget) {
                console.error('❌ No modal or widget reference found');
                return;
            }
            
            const video = currentModal.querySelector('#anam-default-video');
            
            if (!video) {
                console.error('❌ No video element found in modal');
                return;
            }
            
            // Restore video styling for widget
            video.style.cssText = 'width: 100%; height: auto; border-radius: 10px; background: #000; display: block;';
            
            // Move video back to widget
            originalWidget.appendChild(video);
            
            // Show original widget
            originalWidget.style.display = 'block';
            
            // Remove modal
            currentModal.style.opacity = '0';
            setTimeout(() => {
                if (currentModal) {
                    currentModal.remove();
                    currentModal = null;
                }
            }, 300);
            
            console.log('✅ Avatar collapsed to widget');
        }
        
        async function closeAvatarCompletely() {
            console.log('🚫 Closing avatar completely...');
            
            try {
                // Process session before stopping stream
                let sessionIdToProcess = currentSessionId || getSessionIdFromClient();
                
                if (!sessionIdToProcess && anamClient) {
                    // Try to get from Anam client properties
                    console.log('🔍 Searching anamClient for session ID...');
                    sessionIdToProcess = anamClient.sessionId || 
                                       (anamClient.session && anamClient.session.id) ||
                                       (anamClient._session && anamClient._session.id) ||
                                       (anamClient.connection && anamClient.connection.sessionId);
                }
                
                if (sessionIdToProcess) {
                    console.log('📋 Processing session:', sessionIdToProcess);
                    
                    // Store session ID on server for audit trail
                    await sendSessionIdToServer(sessionIdToProcess);
                    
                    currentSessionId = null; // Clear session ID after sending
                } else {
                    console.log('⚠️ No session ID available for processing');
                }
                
                conversationTranscript = []; // Clear transcript data
                
                // Stop streaming if client exists
                if (anamClient) {
                    console.log('🛑 Stopping avatar stream...');
                    await anamClient.stopStreaming();
                    console.log('✅ Stream stopped');
                }
            } catch (error) {
                console.error('❌ Error during close:', error);
            }
            
            // Remove modal if it exists
            if (currentModal) {
                currentModal.remove();
                currentModal = null;
            }
            
            // Reset to welcome screen
            const widget = document.getElementById('anam-avatar-widget');
            if (widget) {
                // Restore welcome screen content
                widget.innerHTML = `
                    <div style="padding: 20px; text-align: center; position: relative;">
                        <div style="font-size: 32px; margin-bottom: 15px;">💬</div>
                        <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Ready to chat?</h3>
                        <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.4;">
                            Start a conversation with our AI assistant
                        </p>
                        <button id="anam-start-btn" style="padding: 12px 24px; border: none; background: #007cba; color: white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
                            Start Conversation
                        </button>
                    </div>
                `;
                
                // Reset widget styling
                widget.style.padding = '';
                widget.style.width = '300px';
                widget.style.height = 'auto';
                widget.style.display = 'block';
                
                // Re-attach event listeners
                const newStartBtn = document.getElementById('anam-start-btn');
                
                newStartBtn.addEventListener('click', () => {
                    initAvatar();
                });
            }
            
            // Reset client
            anamClient = null;
            originalWidget = null;
            
            console.log('✅ Avatar closed and reset to welcome screen');
        }
        
        async function getSessionToken() {
            updateStatus('🔑 Getting session token...');
            
            try {
                const formData = new FormData();
                formData.append('action', 'anam_get_session_token');
                formData.append('nonce', ANAM_CONFIG.nonce);
                
                const response = await fetch(ANAM_CONFIG.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.sessionToken) {
                    return data.data.sessionToken;
                } else {
                    throw new Error(data.data || 'No session token in response');
                }
            } catch (error) {
                console.error('❌ Session token error:', error);
                throw error;
            }
        }
        
        async function initAvatar() {
            try {
                updateStatus('🚀 Initializing...');
                
                const sessionToken = await getSessionToken();
                updateStatus('✅ Token obtained');
                
                updateStatus('🔧 Creating client...');
                anamClient = createClient(sessionToken);
                console.log('✅ Client created:', anamClient);
                
                // Simple session ID capture - no real-time transcript needed
                anamClient.addListener(AnamEvent.STREAM_STARTED, () => {
                    console.log('🎬 Stream started');
                    if (anamClient.sessionId) {
                        currentSessionId = anamClient.sessionId;
                        console.log('📋 Session ID captured:', currentSessionId);
                    }
                });
                
                // Try to capture session ID from client
                try {
                    console.log('🔍 Inspecting anamClient for session ID:', anamClient);
                    
                    // Method 1: Check if session ID is available in client properties
                    if (anamClient.sessionId) {
                        currentSessionId = anamClient.sessionId;
                        console.log('📋 Session ID captured from client.sessionId:', currentSessionId);
                    } else if (anamClient.session && anamClient.session.id) {
                        currentSessionId = anamClient.session.id;
                        console.log('📋 Session ID captured from client.session.id:', currentSessionId);
                    } else {
                        console.log('⚠️ Session ID not immediately available in client properties');
                        console.log('🔍 Available client properties:', Object.keys(anamClient));
                        
                        // Try to extract from session token (if it contains session info)
                        currentSessionId = extractSessionIdFromToken(sessionToken);
                        if (currentSessionId) {
                            console.log('📋 Session ID extracted from token:', currentSessionId);
                        } else {
                            console.log('⚠️ Could not extract session ID from token either');
                        }
                    }
                } catch (error) {
                    console.error('❌ Error capturing session ID:', error);
                }
                
                updateStatus('📹 Starting stream...');
                
                // Handle different display methods
                if (ANAM_CONFIG.displayMethod === 'page_position') {
                    // Page Position method - replace widget content with video
                    const widget = document.getElementById('anam-avatar-widget');
                    
                    const video = document.createElement('video');
                    video.id = 'anam-default-video';
                    video.width = 300;
                    video.height = 400;
                    video.autoplay = true; // Need autoplay for streaming to work
                    video.playsInline = true;
                    video.muted = false;
                    video.controls = false;
                    video.style.cssText = 'width: 100%; height: auto; border-radius: 10px; background: #000; display: block;';
                    
                    // Show loading state first
                    widget.innerHTML = `
                        <div id="anam-loading" style="padding: 40px 20px; text-align: center; background: #f8f9fa;">
                            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e3e3e3; border-top: 4px solid #007cba; border-radius: 50%; animation: anam-spin 1s linear infinite; margin-bottom: 15px;"></div>
                            <div style="color: #666; font-size: 14px;">Setting up your avatar...</div>
                            <div style="color: #999; font-size: 12px; margin-top: 5px;">This may take a moment</div>
                        </div>
                        <style>
                            @keyframes anam-spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        </style>
                    `;
                    
                    // Maintain the widget's fixed positioning and size
                    widget.style.position = 'fixed';
                    widget.style.width = '300px';
                    widget.style.height = 'auto';
                    widget.style.padding = '0';
                    
                    console.log('🎥 Video element created and loading screen shown');
                    
                    // Add video element to DOM first (hidden behind loading screen)
                    video.style.display = 'none';
                    widget.appendChild(video);
                    
                    await anamClient.streamToVideoElement('anam-default-video');
                    
                    console.log('🎬 Streaming to video element completed');
                    
                    // Replace loading screen with video and controls
                    widget.innerHTML = '';
                    widget.style.padding = '0';
                    video.style.display = 'block';
                    widget.appendChild(video);
                    
                    // Add expand button
                    const expandBtn = document.createElement('button');
                    expandBtn.innerHTML = '⛶';
                    expandBtn.title = 'Expand to full screen';
                    expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
                    expandBtn.addEventListener('click', () => {
                        expandToModal();
                    });
                    widget.appendChild(expandBtn);
                    
                    // Add close button
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.title = 'Close avatar';
                    closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
                    closeBtn.addEventListener('click', async () => {
                        await closeAvatarCompletely();
                    });
                    widget.appendChild(closeBtn);
                    
                } else {
                    throw new Error('Invalid display method: ' + ANAM_CONFIG.displayMethod);
                }
                console.log('✅ Stream started');
                
                updateStatus('🎉 Avatar ready!');
                
                setTimeout(() => {
                    const status = document.getElementById('anam-avatar-status');
                    if (status) status.style.display = 'none';
                }, 3000);
                
            } catch (error) {
                console.error('❌ Initialization failed:', error);
                updateStatus(`❌ Failed: ${error.message}`, true);
            }
        }
        
        function getPositionStyles(position) {
            switch(position) {
                case 'top-left': return 'top: 20px; left: 20px';
                case 'top-right': return 'top: 20px; right: 20px';
                case 'bottom-left': return 'bottom: 20px; left: 20px';
                case 'bottom-right': 
                default: return 'bottom: 20px; right: 20px';
            }
        }
        
        window.anamAvatar = {
            client: () => anamClient,
            reinit: initAvatar,
            config: ANAM_CONFIG
        };
        </script>
        <?php
    }
    
    private function get_position_styles($position) {
        switch($position) {
            case 'top-left': return 'top: 20px; left: 20px';
            case 'top-right': return 'top: 20px; right: 20px';
            case 'bottom-left': return 'bottom: 20px; left: 20px';
            case 'bottom-right': 
            default: return 'bottom: 20px; right: 20px';
        }
    }
    
    private function should_show_avatar() {
        $options = get_option($this->option_name, array());
        $display_method = isset($options['display_method']) ? $options['display_method'] : 'element_id';
        
        // For element_id method, avatar only shows where container exists (handled by JavaScript)
        if ($display_method === 'element_id') {
            return true; // Let JavaScript handle container detection
        }
        
        // For page_position method, check selected pages
        if ($display_method === 'page_position') {
            $selected_pages = isset($options['selected_pages']) ? $options['selected_pages'] : array('homepage');
            
            if (!is_array($selected_pages) || empty($selected_pages)) {
                return false;
            }
            
            // Check quick options (homepage is now handled as individual page selection)
            // Note: 'homepage' may still exist in old settings for backward compatibility
            
            if (in_array('all_posts', $selected_pages) && is_single()) {
                return true;
            }
            
            if (in_array('all_pages', $selected_pages) && is_page()) {
                return true;
            }
            
            // Check for legacy homepage selection (backward compatibility)
            if (in_array('homepage', $selected_pages) && is_front_page()) {
                return true;
            }
            
            // Check individual pages and posts
            global $post;
            if ($post) {
                $current_page_value = is_page() ? 'page_' . $post->ID : 'post_' . $post->ID;
                if (in_array($current_page_value, $selected_pages)) {
                    return true;
                }
            }
            
            // Check if current page is homepage and homepage page is selected individually
            if (is_front_page()) {
                $homepage_id = get_option('page_on_front');
                if ($homepage_id && in_array('page_' . $homepage_id, $selected_pages)) {
                    return true;
                }
            }
            
            return false;
        }
        
        // Fallback for backward compatibility with old target_pages system
        $target_pages = isset($options['target_pages']) ? $options['target_pages'] : 'all';
        
        switch($target_pages) {
            case 'home':
                return is_front_page();
            case 'posts':
                return is_single();
            case 'pages':
                return is_page();
            case 'custom':
                if (!empty($options['custom_slugs'])) {
                    global $post;
                    if ($post) {
                        $slugs = array_map('trim', explode(',', $options['custom_slugs']));
                        return in_array($post->post_name, $slugs);
                    }
                }
                return false;
            case 'all':
            default:
                return true;
        }
    }
    
    public function get_session_token() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $options = get_option($this->option_name, array());
        
        if (empty($options['api_key'])) {
            wp_send_json_error('API key not configured');
            return;
        }
        
        // Build persona config according to new API requirements
        $persona_config = array();
        
        // Add persona ID if available (this is key for the new API)
        if (!empty($options['persona_id'])) {
            $persona_config['personaId'] = $options['persona_id'];
        }
        
        // Add other configuration
        if (!empty($options['avatar_id'])) {
            $persona_config['avatarId'] = $options['avatar_id'];
        }
        
        if (!empty($options['voice_id'])) {
            $persona_config['voiceId'] = $options['voice_id'];
        }
        
        if (!empty($options['llm_id'])) {
            $persona_config['llmId'] = $options['llm_id'];
        }
        
        // Always include system prompt
        $persona_config['systemPrompt'] = !empty($options['system_prompt']) 
            ? $options['system_prompt'] 
            : 'You are a helpful digital assistant.';
            
        // Add a name for the persona
        $persona_config['name'] = 'WordPress Avatar';
        
        $request_body = array(
            'personaConfig' => $persona_config
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $options['api_key']
            ),
            'body' => json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        );
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error("API error {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['sessionToken']) && !empty($data['sessionToken'])) {
            wp_send_json_success(array(
                'sessionToken' => $data['sessionToken'],
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Session token not found in response');
        }
    }
    
    public function verify_api_key() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_verify_api')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Don't sanitize - base64 strings have special characters
        $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Simple API verification - just check personas endpoint
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 15,
            'sslverify' => true
        );
        
        $start_time = microtime(true);
        $response = wp_remote_get('https://api.anam.ai/v1/personas', $args);
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Check if API key is valid based on response
        if ($response_code === 200) {
            // Mark as verified and save timestamp
            update_option('anam_api_verified', true);
            update_option('anam_api_verified_at', current_time('mysql'));
            wp_send_json_success(array(
                'message' => 'API key verified successfully',
                'response_time' => $response_time . 'ms',
                'verified_at' => current_time('mysql')
            ));
        } else {
            // Clear verification status
            update_option('anam_api_verified', false);
            
            // Provide helpful error message
            $error_message = 'API verification failed';
            if ($response_code === 401 || $response_code === 403) {
                $error_message = 'Invalid API key - Access denied';
            } elseif ($response_code === 429) {
                $error_message = 'Rate limit exceeded - Please try again later';
            } elseif ($response_code >= 500) {
                $error_message = 'Anam.ai server error - Please try again later';
            }
            
            wp_send_json_error(array(
                'message' => $error_message,
                'status_code' => $response_code,
                'response' => substr($response_body, 0, 200)
            ));
        }
    }
    
    // Handle advanced SDK toggle
    public function handle_toggle_advanced_sdk() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get the enabled value - it comes as string 'true' or 'false'
        $enabled_string = isset($_POST['enabled']) ? $_POST['enabled'] : 'false';
        $enabled = ($enabled_string === 'true');
        
        // Get current options - MUST get fresh copy
        $options = get_option('anam_options', array());
        
        // Set the value
        $options['advanced_sdk_enabled'] = $enabled;
        
        // Delete first to force update
        delete_option('anam_options');
        add_option('anam_options', $options);
        
        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? 'Advanced SDK enabled' : 'Advanced SDK disabled'
        ));
    }
    
    // Handle session token requests
    public function handle_session_token() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $options = get_option($this->option_name, array());
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }
        
        // Get persona configuration from options
        $persona_id = isset($options['persona_id']) ? $options['persona_id'] : '';
        $avatar_id = isset($options['avatar_id']) ? $options['avatar_id'] : '';
        $voice_id = isset($options['voice_id']) ? $options['voice_id'] : '';
        $llm_id = isset($options['llm_id']) ? $options['llm_id'] : '';
        $system_prompt = isset($options['system_prompt']) ? $options['system_prompt'] : '';
        
        // Build persona config
        $persona_config = array();
        
        if (!empty($persona_id)) {
            $persona_config['personaId'] = $persona_id;
        }
        
        if (!empty($avatar_id)) {
            $persona_config['avatarId'] = $avatar_id;
        }
        
        if (!empty($voice_id)) {
            $persona_config['voiceId'] = $voice_id;
        }
        
        if (!empty($llm_id)) {
            $persona_config['llmId'] = $llm_id;
        }
        
        if (!empty($system_prompt)) {
            $persona_config['systemPrompt'] = $system_prompt;
        }
        
        // Create session token request
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'personaConfig' => $persona_config
            )),
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to get session token: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Debug logging
        error_log('Anam Session Token Response Code: ' . $response_code);
        error_log('Anam Session Token Response Body: ' . $response_body);
        
        if ($response_code === 200 || $response_code === 201) {
            $data = json_decode($response_body, true);
            
            if (isset($data['sessionToken'])) {
                wp_send_json_success(array(
                    'sessionToken' => $data['sessionToken']
                ));
            } else {
                wp_send_json_error('Invalid response format');
            }
        } else {
            wp_send_json_error('Session token request failed');
        }
    }

    /**
     * AJAX handler to get session data from Anam API
     */
    public function get_session_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        if (empty($session_id)) {
            wp_send_json_error('No session ID provided');
            return;
        }

        $api_key = get_option('anam_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }

        // Call Anam API to get session data
        $response = wp_remote_get("https://api.anam.ai/v1/sessions/{$session_id}", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('API request failed: ' . $response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            $session_data = json_decode($response_body, true);
            
            // Parse vehicle data from transcript
            $vehicle_data = $this->parse_vehicle_data($session_data);
            
            wp_send_json_success(array(
                'session_data' => $session_data,
                'vehicle_data' => $vehicle_data,
                'transcript' => isset($session_data['transcript']) ? $session_data['transcript'] : 'No transcript available'
            ));
        } else {
            wp_send_json_error('Failed to fetch session data: HTTP ' . $response_code);
        }
    }

    /**
     * Parse vehicle data from session transcript
     */
    private function parse_vehicle_data($session_data) {
        if (!isset($session_data['transcript']) || empty($session_data['transcript'])) {
            return array('error' => 'No transcript data available');
        }

        $transcript = is_array($session_data['transcript']) ? 
            implode(' ', array_column($session_data['transcript'], 'text')) : 
            $session_data['transcript'];

        $vehicle_data = array();

        // VIN pattern (17 characters, alphanumeric)
        if (preg_match('/\b[A-HJ-NPR-Z0-9]{17}\b/i', $transcript, $matches)) {
            $vehicle_data['vin'] = strtoupper($matches[0]);
        }

        // Year pattern (4 digits, 1900-2030)
        if (preg_match('/\b(19|20)\d{2}\b/', $transcript, $matches)) {
            $vehicle_data['year'] = $matches[0];
        }

        // Make pattern (common car manufacturers)
        $makes = array('toyota', 'honda', 'ford', 'chevrolet', 'nissan', 'bmw', 'mercedes', 'audi', 'volkswagen', 'hyundai', 'kia', 'mazda', 'subaru', 'lexus', 'acura', 'infiniti', 'cadillac', 'buick', 'gmc', 'jeep', 'ram', 'dodge', 'chrysler', 'lincoln', 'volvo', 'jaguar', 'land rover', 'porsche', 'tesla', 'mitsubishi');
        foreach ($makes as $make) {
            if (preg_match('/\b' . preg_quote($make, '/') . '\b/i', $transcript)) {
                $vehicle_data['make'] = ucfirst($make);
                break;
            }
        }

        // Model pattern (word after make, if make found)
        if (isset($vehicle_data['make'])) {
            $pattern = '/\b' . preg_quote($vehicle_data['make'], '/') . '\s+([a-z0-9\-]+)/i';
            if (preg_match($pattern, $transcript, $matches)) {
                $vehicle_data['model'] = ucfirst($matches[1]);
            }
        }

        return empty($vehicle_data) ? array('error' => 'No vehicle data found in transcript') : $vehicle_data;
    }

    /**
     * AJAX handler to list sessions from Anam API
     */
    public function list_sessions() {
        // Verify nonce
        if (!check_ajax_referer('anam_session', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Get saved credentials
        $options = get_option('anam_options', array());
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $auth_token = isset($options['auth_token']) ? $options['auth_token'] : '';

        // Debug logging
        error_log('List Sessions - API Key: ' . (empty($api_key) ? 'EMPTY' : 'Present (length: ' . strlen($api_key) . ')'));
        error_log('List Sessions - Auth Token: ' . (empty($auth_token) ? 'EMPTY' : 'Present (length: ' . strlen($auth_token) . ')'));

        if (empty($api_key)) {
            wp_send_json_error('API Key not configured. Please add your API Key in Avatar Setup.');
            return;
        }

        if (empty($auth_token)) {
            wp_send_json_error('Auth Token not configured. Please add your Auth Token in Avatar Setup.');
            return;
        }
        
        // The API key IS the base64 encoded bearer token - use it directly
        $bearer_token = $api_key;

        // Get pagination parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['perPage']) ? intval($_POST['perPage']) : 10;

        // Build API URL - use apiKeyId parameter, not personaId
        $api_url = 'https://api.anam.ai/v1/sessions?page=' . $page . '&perPage=' . $per_page . '&apiKeyId=' . urlencode($auth_token);
        
        error_log('List Sessions - API URL: ' . $api_url);

        // Make request to Anam API
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log('List Sessions - WP Error: ' . $response->get_error_message());
            wp_send_json_error('Failed to fetch sessions: ' . $response->get_error_message());
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log('List Sessions - Status Code: ' . $status_code);
        error_log('List Sessions - Raw Body: ' . substr($body, 0, 500)); // First 500 chars

        if ($status_code !== 200) {
            error_log('List Sessions - Non-200 status, full body: ' . $body);
            wp_send_json_error('API returned error: ' . $status_code . ' - ' . $body);
            return;
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('List Sessions - JSON Error: ' . json_last_error_msg());
            wp_send_json_error('Failed to parse API response: ' . json_last_error_msg());
            return;
        }

        // Debug: Log the parsed response structure
        error_log('List Sessions - Parsed data keys: ' . implode(', ', array_keys($data)));
        if (isset($data['data'])) {
            error_log('List Sessions - Sessions count: ' . count($data['data']));
        }

        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler to get individual session details with transcript
     */
    public function get_session_details() {
        // Verify nonce
        if (!check_ajax_referer('anam_session', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $session_id = isset($_POST['sessionId']) ? sanitize_text_field($_POST['sessionId']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        // Get saved transcript from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'anam_transcripts';
        
        $transcript_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        $response_data = array(
            'session_id' => $session_id,
            'has_transcript' => false,
            'transcript' => null,
            'message_count' => 0
        );
        
        if ($transcript_row) {
            error_log('Raw transcript data: ' . substr($transcript_row->transcript_data, 0, 200));
            $transcript_messages = json_decode($transcript_row->transcript_data, true);
            
            if ($transcript_messages === null) {
                error_log('❌ JSON decode failed: ' . json_last_error_msg());
                error_log('Raw data length: ' . strlen($transcript_row->transcript_data));
            }
            
            $response_data['has_transcript'] = true;
            $response_data['transcript'] = $transcript_messages;
            $response_data['message_count'] = $transcript_row->message_count;
            $response_data['created_at'] = $transcript_row->created_at;
            $response_data['updated_at'] = $transcript_row->updated_at;
            
            error_log('✅ Transcript found for session: ' . $session_id . ' (' . $transcript_row->message_count . ' messages)');
        } else {
            error_log('⚠️ No transcript found for session: ' . $session_id);
        }
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX handler to get session metadata from Anam API
     */
    public function get_session_metadata() {
        if (!check_ajax_referer('anam_session', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $session_id = isset($_POST['sessionId']) ? sanitize_text_field($_POST['sessionId']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        $options = get_option('anam_options', array());
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        if (empty($api_key)) {
            wp_send_json_error('API Key not configured');
            return;
        }
        
        $api_url = 'https://api.anam.ai/v1/sessions/' . urlencode($session_id);
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch session metadata: ' . $response->get_error_message());
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            wp_send_json_error('API returned error: ' . $status_code);
            return;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Failed to parse API response');
            return;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler to save transcript data
     */
    public function save_transcript() {
        // Verify nonce
        if (!check_ajax_referer('anam_session', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $transcript_data = isset($_POST['transcript_data']) ? $_POST['transcript_data'] : '';
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        if (empty($transcript_data)) {
            wp_send_json_error('Transcript data is required');
            return;
        }
        
        // Decode and validate transcript data
        $messages = json_decode(stripslashes($transcript_data), true);
        if (!$messages || !is_array($messages)) {
            error_log('❌ Invalid transcript data received');
            error_log('Raw data: ' . substr($transcript_data, 0, 200));
            wp_send_json_error('Invalid transcript data');
            return;
        }
        
        // Re-encode as clean JSON for storage
        $clean_json = json_encode($messages);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'anam_transcripts';
        
        // Check if transcript already exists for this session
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        $message_count = count($messages);
        
        if ($existing) {
            // Update existing transcript
            $result = $wpdb->update(
                $table_name,
                array(
                    'transcript_data' => $clean_json,
                    'message_count' => $message_count,
                    'updated_at' => current_time('mysql')
                ),
                array('session_id' => $session_id),
                array('%s', '%d', '%s'),
                array('%s')
            );
        } else {
            // Insert new transcript
            $result = $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'transcript_data' => $clean_json,
                    'message_count' => $message_count,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s', '%s')
            );
        }
        
        if ($result === false) {
            error_log('❌ Failed to save transcript: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        error_log('✅ Transcript saved for session: ' . $session_id . ' (' . $message_count . ' messages)');
        wp_send_json_success(array(
            'message' => 'Transcript saved successfully',
            'session_id' => $session_id,
            'message_count' => $message_count
        ));
    }

}


new AnamAdminSettings();

/**
 * Render the sessions page with sessions list
 */
function anam_render_sessions_page() {
    $options = get_option('anam_options', array());
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    $persona_id = isset($options['persona_id']) ? $options['persona_id'] : '';
    
    ?>
    <div class="wrap">
        <h1>💬 Chat Transcripts</h1>
        
        <?php if (empty($api_key) || empty($persona_id)): ?>
            <div class="notice notice-warning">
                <p><strong>Configuration Required:</strong> Please configure your API Key and Persona ID in <a href="<?php echo admin_url('admin.php?page=anam-settings'); ?>">Avatar Setup</a> first.</p>
            </div>
        <?php else: ?>
            <div class="notice notice-info">
                <p>Displaying sessions for Persona ID: <code><?php echo esc_html($persona_id); ?></code> | <a href="<?php echo admin_url('admin.php?page=anam-settings'); ?>">Avatar Setup</a></p>
            </div>
            
            <div id="sessions-loading" style="text-align: center; padding: 40px;">
                <div class="anam-spinner" style="margin: 0 auto 20px; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p>Loading sessions...</p>
            </div>
            
            <div id="sessions-error" style="display: none;">
            </div>
            
            <div id="sessions-container" style="display: none; max-width: 1440px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 300px;">Session ID</th>
                            <th style="width: 150px;">Created At</th>
                            <th style="width: 150px;">Updated At</th>
                            <th>Client Label</th>
                            <th style="width: 100px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sessions-list">
                        <!-- Sessions will be loaded here -->
                    </tbody>
                </table>
                
                <div id="sessions-pagination" style="margin-top: 20px; text-align: center;">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Session Details Modal -->
        <div id="session-details-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 1200px; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                <div style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;">Chat Details</h2>
                    <button id="close-session-modal" style="background: none; border: none; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</button>
                </div>
                <div class="nav-tab-wrapper" style="margin: 0; border-bottom: 1px solid #ddd;">
                    <a href="#" class="nav-tab" data-tab="session-json">Session JSON</a>
                    <a href="#" class="nav-tab nav-tab-active" data-tab="transcript" style="background: white;">Transcript</a>
                    <a href="#" class="nav-tab" data-tab="transcript-json">Transcript JSON</a>
                </div>
                <div id="session-details-content" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
                    <div style="text-align: center; padding: 40px;">
                        <div class="anam-spinner" style="margin: 0 auto 20px; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p>Loading session details...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            #session-details-modal pre {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 12px;
                line-height: 1.5;
            }
        </style>
    </div>
    <?php
}
