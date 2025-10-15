<?php
/**
 * Plugin Name: Anam.ai Avatar Integration
 * Plugin URI: https://github.com/shawnkelshaw/anam-wordpress-integration
 * Description: Integrate Anam.ai digital avatars into WordPress using the JavaScript SDK
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 * Author URI: https://shawnkelshaw.com
 * License: MIT
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarPlugin {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $llm_id;
    private $target_page_slug;
    
    public function __construct() {
        // Configuration - Update these values with your Anam.ai credentials
        $this->api_key = 'your-anam-api-key-here';
        $this->avatar_id = 'your-avatar-id-here';
        $this->voice_id = 'your-voice-id-here';
        $this->llm_id = 'your-llm-id-here';
        $this->target_page_slug = 'your-target-page-slug-here';
        
        add_action('wp_footer', array($this, 'add_avatar_integration'));
        add_action('wp_ajax_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_session_token', array($this, 'get_session_token'));
    }
    
    public function add_avatar_integration() {
        if ($this->is_target_page()) {
            ?>
            <!-- Anam.ai Avatar Integration -->
            <div id="anam-avatar-status" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center; max-width: 300px;">
                🎯 Loading avatar...<br><small>Initializing Anam.ai integration</small>
            </div>

            <script type="module">
            console.log('🎯 Anam Avatar Plugin - Starting...');
            
            // Import Anam SDK using working ESM CDN
            import { createClient } from "https://esm.sh/@anam-ai/js-sdk@3.5.1/es2022/js-sdk.mjs";
            
            const ANAM_CONFIG = {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_session'); ?>'
            };
            
            let anamClient = null;
            
            function updateStatus(message, isError = false) {
                const status = document.getElementById('anam-avatar-status');
                if (status) {
                    status.innerHTML = message;
                    status.style.background = isError ? 'rgba(220, 53, 69, 0.9)' : 'rgba(0,0,0,0.9)';
                }
                console.log(isError ? '❌' : '🎯', message.replace(/<[^>]*>/g, ''));
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
                    console.log('🎯 Session response:', data);
                    
                    if (data.success && data.data && data.data.sessionToken) {
                        console.log('✅ Session token received');
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
                    console.log('🎯 SDK imported successfully');
                    
                    // Step 1: Get session token
                    const sessionToken = await getSessionToken();
                    updateStatus('✅ Token obtained');
                    
                    // Step 2: Create client
                    updateStatus('🔧 Creating client...');
                    anamClient = createClient(sessionToken);
                    console.log('✅ Client created:', anamClient);
                    
                    // Step 3: Stream to container
                    updateStatus('📹 Starting stream...');
                    
                    // Check if custom container exists, otherwise create default
                    let targetContainer = 'anam-stream-container';
                    if (!document.getElementById(targetContainer)) {
                        // Create default container if custom one doesn't exist
                        const container = document.createElement('div');
                        container.id = 'anam-default-container';
                        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999;';
                        document.body.appendChild(container);
                        
                        const video = document.createElement('video');
                        video.id = 'anam-default-video';
                        video.width = 300;
                        video.height = 400;
                        video.autoplay = true;
                        video.playsInline = true;
                        video.muted = true;
                        video.style.cssText = 'border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: #000;';
                        container.appendChild(video);
                        
                        targetContainer = 'anam-default-video';
                    }
                    
                    await anamClient.streamToVideoElement(targetContainer);
                    console.log('✅ Stream started');
                    
                    // Success!
                    console.log('🎉 Avatar initialized and streaming!');
                    updateStatus('🎉 Avatar ready!');
                    
                    // Hide status after 3 seconds
                    setTimeout(() => {
                        const status = document.getElementById('anam-avatar-status');
                        if (status) status.style.display = 'none';
                    }, 3000);
                    
                } catch (error) {
                    console.error('❌ Initialization failed:', error);
                    updateStatus(`❌ Failed: ${error.message}`, true);
                }
            }
            
            // Start initialization
            initAvatar();
            
            // Global debug access
            window.anamAvatar = {
                client: () => anamClient,
                reinit: initAvatar,
                config: ANAM_CONFIG
            };
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Validate API key is configured
        if (empty($this->api_key) || $this->api_key === 'your-anam-api-key-here') {
            wp_send_json_error('API key not configured. Please update the plugin configuration.');
            return;
        }
        
        // Prepare request to Anam.ai API
        $persona_config = array(
            'name' => 'Digital Assistant',
            'avatarId' => $this->avatar_id,
            'voiceId' => $this->voice_id,
            'llmId' => $this->llm_id,
            'systemPrompt' => 'You are a helpful digital assistant. Be friendly and concise in your responses.'
        );
        
        $request_body = array(
            'personaConfig' => $persona_config
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        );
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('WordPress HTTP Error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error("API returned status {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['sessionToken']) && !empty($data['sessionToken'])) {
            wp_send_json_success(array(
                'sessionToken' => $data['sessionToken'],
                'timestamp' => current_time('mysql'),
                'source' => 'anam_avatar_plugin'
            ));
        } else {
            wp_send_json_error('Session token not found in response: ' . $response_body);
        }
    }
    
    private function is_target_page() {
        global $post;
        
        // If no target page specified, show on all pages (for testing)
        if (empty($this->target_page_slug) || $this->target_page_slug === 'your-target-page-slug-here') {
            return true;
        }
        
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

// Initialize the plugin
new AnamAvatarPlugin();
