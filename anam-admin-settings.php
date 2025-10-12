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

class AnamAdminSettings {
    
    private $option_group = 'anam_settings';
    private $option_name = 'anam_options';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_footer', array($this, 'add_avatar_integration'));
        add_action('wp_ajax_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_verify_anam_api_key', array($this, 'verify_api_key'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Anam.ai Avatar Settings',
            'Anam Avatar',
            'manage_options',
            'anam-settings',
            array($this, 'admin_page')
        );
    }
    
    public function init_settings() {
        register_setting($this->option_group, $this->option_name, array($this, 'sanitize_settings'));
        
        add_settings_section(
            'anam_api_section',
            'Anam.ai API Configuration',
            array($this, 'api_section_callback'),
            'anam-settings'
        );
        
        add_settings_section(
            'anam_avatar_section',
            'Avatar Configuration',
            array($this, 'avatar_section_callback'),
            'anam-settings'
        );
        
        add_settings_section(
            'anam_display_section',
            'Display Settings',
            array($this, 'display_section_callback'),
            'anam-settings'
        );
        
        // API Settings
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_field'),
            'anam-settings',
            'anam_api_section'
        );
        
        // Avatar Settings
        add_settings_field(
            'persona_id',
            'Persona ID',
            array($this, 'persona_id_field'),
            'anam-settings',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'avatar_id',
            'Avatar ID',
            array($this, 'avatar_id_field'),
            'anam-settings',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'voice_id',
            'Voice ID',
            array($this, 'voice_id_field'),
            'anam-settings',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'llm_id',
            'LLM ID',
            array($this, 'llm_id_field'),
            'anam-settings',
            'anam_avatar_section'
        );
        
        add_settings_field(
            'system_prompt',
            'System Prompt',
            array($this, 'system_prompt_field'),
            'anam-settings',
            'anam_avatar_section'
        );
        
        // Display Settings
        add_settings_field(
            'display_method',
            'Display Method',
            array($this, 'display_method_field'),
            'anam-settings',
            'anam_display_section'
        );
        
        add_settings_field(
            'container_id',
            'Element ID',
            array($this, 'container_id_field'),
            'anam-settings',
            'anam_display_section'
        );
        
        add_settings_field(
            'avatar_position',
            'Avatar Position',
            array($this, 'avatar_position_field'),
            'anam-settings',
            'anam_display_section'
        );
        
        add_settings_field(
            'page_selection',
            'Show Avatar On',
            array($this, 'page_selection_field'),
            'anam-settings',
            'anam_display_section'
        );
    }
    
    public function admin_page() {
        $options = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>🤖 Anam.ai Avatar Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>Getting Started:</strong> Configure your Anam.ai credentials below. You can find these values in your <a href="https://app.anam.ai" target="_blank">Anam.ai dashboard</a>.</p>
            </div>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully! 🎉</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('anam-settings');
                submit_button('Save Avatar Settings');
                ?>
            </form>
            
            
            <div class="anam-help-section" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
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
    
    public function api_section_callback() {
        echo '<p>Enter your Anam.ai API credentials. These are required for the avatar to function.</p>';
    }
    
    public function avatar_section_callback() {
        echo '<p>Configure your avatar\'s appearance, voice, and behavior.</p>';
    }
    
    public function display_section_callback() {
        echo '<p>Control where and how your avatar appears on your website.</p>';
    }
    
    public function api_key_field() {
        $options = get_option($this->option_name, array());
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        $is_verified = get_option('anam_api_verified', false);
        
        echo '<input type="password" id="anam-api-key" name="' . $this->option_name . '[api_key]" value="' . esc_attr($value) . '" class="regular-text" placeholder="Enter your Anam.ai API key" />';
        echo '<button type="button" id="verify-api-key" class="button button-secondary" style="margin-left: 10px;">Verify API Key</button>';
        
        if ($is_verified) {
            echo '<span id="api-status" style="margin-left: 10px; color: #46b450;">✅ Verified</span>';
        } else {
            echo '<span id="api-status" style="margin-left: 10px; color: #dc3545;">❌ Not verified</span>';
        }
        
        echo '<br><a href="https://app.anam.ai" target="_blank" style="font-size: 12px;">Get your Anam.ai API key →</a>';
        echo '<p class="description">Your secret API key from Anam.ai dashboard. Must be verified before other settings are enabled.</p>';
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
            <option value="" <?php selected($dropdown_value, ''); ?>>Default (Leave Empty)</option>
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
                        <strong>Default (Leave Empty)</strong><br>
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
                    <strong>💡 Pro Tip:</strong> Start with "Default (Leave Empty)" - it works for most personas. Only change if your specific persona requires a different LLM.
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
        echo '<textarea name="' . $this->option_name . '[system_prompt]" rows="12" class="large-text anam-dependent-field" style="width: 100%; max-width: 100%; resize: vertical;" placeholder="Enter system prompt for your avatar...">' . esc_textarea($value) . '</textarea>';
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
        echo '<input type="text" id="anam-container-id" name="' . $this->option_name . '[container_id]" value="' . esc_attr($value) . '" class="regular-text" placeholder="anam-stream-container" />';
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
        $sanitized = array();
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
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
        
        return $sanitized;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_anam-settings') {
            return;
        }
        
        wp_enqueue_script('anam-admin', plugin_dir_url(__FILE__) . 'anam-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('anam-admin', 'anam_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anam_verify_api')
        ));
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
        
        import { createClient } from "https://esm.sh/@anam-ai/js-sdk@3.5.1/es2022/js-sdk.mjs";
        
        const ANAM_CONFIG = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('anam_session'); ?>',
            displayMethod: '<?php echo esc_js($display_method); ?>',
            containerId: '<?php echo esc_js($container_id); ?>',
            position: '<?php echo esc_js($position); ?>'
        };
        
        let anamClient = null;
        
        // Handle widget button interactions
        if (ANAM_CONFIG.displayMethod === 'page_position') {
            const widget = document.getElementById('anam-avatar-widget');
            const startBtn = document.getElementById('anam-start-btn');
            
            // Start conversation button
            startBtn.addEventListener('click', () => {
                initAvatar();
            });
        } else {
            // For element_id method, initialize directly
            initAvatar();
        }
        
        function updateStatus(message, isError = false) {
            console.log(isError ? '❌' : '🎯', message.replace(/<[^>]*>/g, ''));
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
                // Stop streaming if client exists
                if (anamClient) {
                    console.log('🛑 Stopping avatar stream...');
                    await anamClient.stopStreaming();
                    console.log('✅ Stream stopped');
                }
            } catch (error) {
                console.error('❌ Error stopping stream:', error);
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
                formData.append('action', 'anam_session_token');
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
                
                updateStatus('📹 Starting stream...');
                
                // Handle different display methods
                if (ANAM_CONFIG.displayMethod === 'element_id') {
                    // Element ID method - stream to specified container
                    const targetElement = ANAM_CONFIG.containerId;
                    
                    if (!targetElement) {
                        throw new Error('Element ID method selected but no container ID specified');
                    }
                    
                    const customContainer = document.getElementById(targetElement);
                    if (!customContainer) {
                        throw new Error(`Container "${targetElement}" not found on this page`);
                    }
                    
                    // Add close button to custom container
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001;';
                    closeBtn.addEventListener('click', () => {
                        customContainer.innerHTML = '';
                        console.log('🚫 Avatar closed by user');
                    });
                    customContainer.style.position = 'relative';
                    customContainer.appendChild(closeBtn);
                    
                    // Use the custom container directly
                    await anamClient.streamToVideoElement(targetElement);
                    
                } else if (ANAM_CONFIG.displayMethod === 'page_position') {
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
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Test the API key with a minimal persona config
        $test_config = array(
            'name' => 'API Test',
            'systemPrompt' => 'You are a test assistant.'
        );
        
        $request_body = array(
            'personaConfig' => $test_config
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body),
            'timeout' => 15,
            'sslverify' => true
        );
        
        $start_time = microtime(true);
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            if (isset($data['sessionToken'])) {
                // API key is valid, store verification status
                update_option('anam_api_verified', true);
                
                wp_send_json_success(array(
                    'message' => 'API key verified successfully!',
                    'response_time' => $response_time . 'ms',
                    'token_preview' => substr($data['sessionToken'], 0, 20) . '...'
                ));
            } else {
                wp_send_json_error('Invalid response format from API');
            }
        } else {
            // Clear verification status on failure
            update_option('anam_api_verified', false);
            wp_send_json_error("API returned error {$response_code}: {$response_body}");
        }
    }
}

new AnamAdminSettings();
