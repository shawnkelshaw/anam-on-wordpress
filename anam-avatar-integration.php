<?php
/**
 * Plugin Name: Anam Avatar Integration
 * Description: Integrates Anam.ai digital avatars using JavaScript SDK
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarIntegration {
    
    private $api_key;
    private $persona_id;
    private $avatar_id;
    private $voice_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'NTc5NmQ1MDgtN2RmZS00MTdlLTg1OTctMzM5NWE2ZDlmZjNmOnFaMnhXbGd6YmFBaDJuMFYvaCt5cW4vQWFzckhjb3V4dlA5WFVEU0liRHM9';
        $this->persona_id = '1bf11606-16b7-4788-a3db-3293148ca7bd';
        $this->avatar_id = '30fa96d0-26c4-4e55-94a0-517025942e18';
        $this->voice_id = 'b7bf471f-5435-49f8-a979-4483e4ccc10f';
        $this->target_page_slug = 'using-anam-ai-digital-avatars-and-custom-llm-to-replace-long-form-data-entry';
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_avatar_to_target_page'));
        add_action('wp_ajax_get_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_get_anam_session_token', array($this, 'get_session_token'));
    }
    
    public function enqueue_scripts() {
        if ($this->is_target_page()) {
            // Try alternative CDN for Anam.ai SDK
            wp_enqueue_script(
                'anam-sdk',
                'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
                array(),
                '1.0.0',
                true
            );
            
            // Add inline script as fallback if CDN fails
            $inline_js = "
            if (typeof window.AnamSDK === 'undefined') {
                console.log('Loading Anam SDK from alternative source...');
                const script = document.createElement('script');
                script.src = 'https://cdn.skypack.dev/@anam-ai/js-sdk';
                script.onload = function() {
                    console.log('Anam SDK loaded from Skypack');
                    if (typeof initializeAnamAvatar === 'function') {
                        initializeAnamAvatar();
                    }
                };
                document.head.appendChild(script);
            }
            ";
            wp_add_inline_script('anam-sdk', $inline_js);
            
            // Enqueue our custom JavaScript with proper path
            wp_enqueue_script(
                'anam-avatar-init',
                plugins_url('anam-avatar.js', __FILE__),
                array('anam-sdk'),
                '1.0.0',
                true
            );
            
            // Localize script with AJAX URL
            wp_localize_script('anam-avatar-init', 'anam_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('anam_session_token')
            ));
        }
    }
    
    public function add_avatar_to_target_page() {
        if ($this->is_target_page()) {
            ?>
            <div id="anam-avatar-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-video" width="300" height="400" autoplay playsinline style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);"></video>
                <div id="anam-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px;">
                    Loading Avatar...
                </div>
            </div>
            <?php
        }
    }
    
    public function get_session_token() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'anam_session_token')) {
            wp_die('Security check failed');
        }
        
        $url = 'https://api.anam.ai/v1/auth/session-token';
        
        $body = json_encode(array(
            'personaConfig' => array(
                'name' => 'Digital Assistant',
                'avatarId' => $this->avatar_id,
                'voiceId' => $this->voice_id,
                'systemPrompt' => 'You are a helpful customer service representative assisting visitors on Shawn Kelshaw\'s website. You can help answer questions about digital avatars, AI, and the content on this page.'
            )
        ));
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => $body,
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to get session token: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['sessionToken'])) {
            wp_send_json_success(array('sessionToken' => $data['sessionToken']));
        } else {
            wp_send_json_error('Invalid response from Anam.ai API: ' . $body);
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

// Initialize the plugin
new AnamAvatarIntegration();
