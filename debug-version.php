<?php
/**
 * Plugin Name: Anam Avatar Integration (Debug)
 * Description: Debug version with detailed logging
 * Version: 1.0.1
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarIntegrationDebug {
    
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
        
        // Add debug logging
        add_action('wp_footer', array($this, 'add_debug_info'));
    }
    
    public function enqueue_scripts() {
        if ($this->is_target_page()) {
            // Add debug console logging
            $debug_js = "
            console.log('=== ANAM DEBUG INFO ===');
            console.log('Plugin directory: " . plugin_dir_url(__FILE__) . "');
            console.log('Target page detected: true');
            console.log('WordPress AJAX URL: " . admin_url('admin-ajax.php') . "');
            ";
            wp_add_inline_script('jquery', $debug_js);
            
            // Load Anam SDK directly in footer instead of enqueue
            add_action('wp_footer', array($this, 'load_anam_sdk_inline'), 5);
        }
    }
    
    public function load_anam_sdk_inline() {
        ?>
        <script>
        console.log('Loading Anam SDK inline...');
        
        // AJAX configuration
        window.anam_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('anam_session_token'); ?>'
        };
        
        // Load SDK from CDN
        function loadAnamSDK() {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js';
            script.onload = function() {
                console.log('Anam SDK loaded successfully from unpkg');
                initializeAnamAvatar();
            };
            script.onerror = function() {
                console.log('Failed to load from unpkg, trying jsdelivr...');
                const fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js';
                fallbackScript.onload = function() {
                    console.log('Anam SDK loaded from jsdelivr');
                    initializeAnamAvatar();
                };
                fallbackScript.onerror = function() {
                    console.error('Failed to load Anam SDK from all CDNs');
                    const loadingElement = document.getElementById('anam-loading');
                    if (loadingElement) {
                        loadingElement.innerHTML = 'Failed to load SDK from CDN';
                        loadingElement.style.background = 'rgba(255, 0, 0, 0.8)';
                    }
                };
                document.head.appendChild(fallbackScript);
            };
            document.head.appendChild(script);
        }
        
        // Initialize avatar function
        async function initializeAnamAvatar() {
            console.log('Initializing Anam Avatar...');
            
            const videoElement = document.getElementById('anam-video');
            const loadingElement = document.getElementById('anam-loading');
            
            if (!videoElement) {
                console.error('Video element not found');
                return;
            }
            
            if (typeof window.AnamSDK === 'undefined') {
                console.error('AnamSDK still not available');
                return;
            }
            
            try {
                console.log('Getting session token...');
                const sessionToken = await getSessionToken();
                console.log('Session token received:', sessionToken ? 'SUCCESS' : 'FAILED');
                
                const { createClient } = window.AnamSDK;
                const anamClient = createClient(sessionToken);
                
                console.log('Anam client created, streaming to video element...');
                await anamClient.streamToVideoElement('anam-video');
                
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                
                console.log('Anam avatar initialized successfully');
                
            } catch (error) {
                console.error('Failed to initialize avatar:', error);
                if (loadingElement) {
                    loadingElement.innerHTML = 'Error: ' + error.message;
                    loadingElement.style.background = 'rgba(255, 0, 0, 0.8)';
                }
            }
        }
        
        // Get session token function
        async function getSessionToken() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_anam_session_token');
                formData.append('nonce', window.anam_ajax.nonce);
                
                console.log('Making AJAX request to:', window.anam_ajax.ajax_url);
                
                const response = await fetch(window.anam_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                console.log('AJAX response status:', response.status);
                
                const data = await response.json();
                console.log('AJAX response data:', data);
                
                if (data.success) {
                    return data.data.sessionToken;
                } else {
                    throw new Error(data.data || 'Failed to get session token');
                }
            } catch (error) {
                console.error('Error getting session token:', error);
                throw error;
            }
        }
        
        // Start loading when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadAnamSDK);
        } else {
            loadAnamSDK();
        }
        </script>
        <?php
    }
    
    public function add_avatar_to_target_page() {
        if ($this->is_target_page()) {
            ?>
            <div id="anam-avatar-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-video" width="300" height="400" autoplay playsinline style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);"></video>
                <div id="anam-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; max-width: 250px; text-align: center;">
                    Loading Avatar...
                </div>
            </div>
            <?php
        }
    }
    
    public function add_debug_info() {
        if ($this->is_target_page()) {
            ?>
            <div id="anam-debug" style="position: fixed; top: 10px; left: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; font-size: 12px; z-index: 10000; max-width: 300px;">
                <strong>Anam Debug Info:</strong><br>
                Page Slug: <?php echo get_post_field('post_name'); ?><br>
                Target Slug: <?php echo $this->target_page_slug; ?><br>
                Match: <?php echo $this->is_target_page() ? 'YES' : 'NO'; ?><br>
                Plugin Dir: <?php echo plugin_dir_url(__FILE__); ?>
            </div>
            <?php
        }
    }
    
    public function get_session_token() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'anam_session_token')) {
            wp_send_json_error('Security check failed');
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
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Anam API Response Code: ' . $response_code);
        error_log('Anam API Response Body: ' . $body);
        
        if ($response_code !== 200) {
            wp_send_json_error('API returned status ' . $response_code . ': ' . $body);
        }
        
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
new AnamAvatarIntegrationDebug();
