<?php
/**
 * Plugin Name: Anam on WordPress
 * Description: Anam.ai digital avatar integration for WordPress using JavaScript SDK
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 * Plugin URI: https://github.com/shawnkelshaw/anam-on-wordpress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamOnWordPress {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $llm_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'your-anam-api-key-here';
        $this->avatar_id = 'your-avatar-id-here';
        $this->voice_id = 'your-voice-id-here';
        $this->llm_id = 'your-llm-id-here';
        $this->target_page_slug = 'your-target-page-slug-here';
        
        add_action('wp_footer', array($this, 'add_avatar_container'));
        add_action('wp_ajax_anam_esm_only_session', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_esm_only_session', array($this, 'get_session_token'));
    }
    
    public function add_avatar_container() {
        if ($this->is_target_page()) {
            ?>
            <!-- Anam Avatar Status Overlay -->
            <div id="anam-esm-only-status" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center; max-width: 300px;">
                🎯 Loading avatar...<br><small>Initializing in your container</small>
            </div>

            <script type="module">
            console.log('🎯 Anam ESM Only - Starting...');
            
            // Use the direct SDK file URL
            import { createClient } from "https://esm.sh/@anam-ai/js-sdk@3.5.1/es2022/js-sdk.mjs";
            
            const ESM_ONLY_CONFIG = {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_esm_only'); ?>'
            };
            
            let esmOnlyClient = null;
            
            function updateESMOnlyStatus(message, isError = false) {
                const status = document.getElementById('anam-esm-only-status');
                if (status) {
                    status.innerHTML = message;
                    status.style.background = isError ? 'rgba(220, 53, 69, 0.9)' : 'rgba(0,0,0,0.9)';
                }
                console.log(isError ? '❌' : '🎯', message.replace(/<[^>]*>/g, ''));
            }
            
            async function getESMOnlySessionToken() {
                updateESMOnlyStatus('🔑 Getting session token...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'anam_esm_only_session');
                    formData.append('nonce', ESM_ONLY_CONFIG.nonce);
                    
                    const response = await fetch(ESM_ONLY_CONFIG.ajaxUrl, {
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
            
            async function initESMOnlyAvatar() {
                try {
                    updateESMOnlyStatus('🚀 Initializing...');
                    console.log('🎯 ESM SDK imported successfully');
                    console.log('🎯 createClient type:', typeof createClient);
                    console.log('🎯 createClient function:', createClient);
                    console.log('🎯 createClient.toString():', createClient.toString());
                    
                    // Step 1: Get session token
                    const sessionToken = await getESMOnlySessionToken();
                    updateESMOnlyStatus('✅ Token obtained');
                    
                    // Step 2: Create client
                    updateESMOnlyStatus('🔧 Creating client...');
                    esmOnlyClient = createClient(sessionToken);
                    console.log('✅ Client created:', esmOnlyClient);
                    console.log('🎯 Client type:', typeof esmOnlyClient);
                    console.log('🎯 Client.on exists:', typeof esmOnlyClient?.on);
                    console.log('🎯 Client.streamToVideoElement exists:', typeof esmOnlyClient?.streamToVideoElement);
                    
                    // Step 3: Stream to your custom container
                    updateESMOnlyStatus('📹 Starting stream...');
                    await esmOnlyClient.streamToVideoElement('anam-stream-container');
                    console.log('✅ Stream started');
                    
                    // Step 4: Avatar is streaming successfully!
                    console.log('🎉 Avatar initialized and streaming!');
                    updateESMOnlyStatus('🎉 Avatar ready!');
                    
                    // Hide status after 3 seconds
                    setTimeout(() => {
                        const status = document.getElementById('anam-esm-only-status');
                        if (status) status.style.display = 'none';
                    }, 3000);
                    
                    console.log('🎉 Initialization complete');
                    
                } catch (error) {
                    console.error('❌ Initialization failed:', error);
                    updateESMOnlyStatus(`❌ Failed: ${error.message}`, true);
                }
            }
            
            // Start initialization
            initESMOnlyAvatar();
            
            // Global debug access
            window.anamESMOnly = {
                client: () => esmOnlyClient,
                reinit: initESMOnlyAvatar,
                config: ESM_ONLY_CONFIG
            };
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_esm_only')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Use exact format from official documentation
        $persona_config = array(
            'name' => 'Digital Assistant',
            'avatarId' => $this->avatar_id,
            'voiceId' => $this->voice_id,
            'llmId' => $this->llm_id,
            'systemPrompt' => 'You are a helpful customer service representative assisting visitors on Shawn Kelshaw\'s website. Be friendly and concise in your responses.'
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
                'source' => 'esm_only'
            ));
        } else {
            wp_send_json_error('Session token not found in response: ' . $response_body);
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

// Old plugin disabled - using anam-admin-settings.php instead
// new AnamOnWordPress();
