<?php
/**
 * Plugin Name: Anam Avatar ESM CDN
 * Description: Anam.ai integration using official ESM.sh CDN (vendor recommended)
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarESM {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $llm_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'MWZjNWY0YjgtMGM1Zi00OWRiLTgwMjgtNWM4N2FjNGY4NGU0OmhrNkd5ZVpmWmNSTFlFR3Q0dWluNWpwclY0NlpJWitjTnZOMnZsQmlWWFU9';
        $this->avatar_id = '30fa96d0-26c4-4e55-94a0-517025942e18';
        $this->voice_id = 'b7bf471f-5435-49f8-a979-4483e4ccc10f';
        $this->llm_id = '0934d97d-0c3a-4f33-91b0-5e136a0ef466';
        $this->target_page_slug = 'using-anam-ai-digital-avatars-and-custom-llm-to-replace-long-form-data-entry';
        
        add_action('wp_footer', array($this, 'add_avatar_container'));
        add_action('wp_ajax_anam_esm_session', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_esm_session', array($this, 'get_session_token'));
    }
    
    public function add_avatar_container() {
        if ($this->is_target_page()) {
            ?>
            <!-- Anam Avatar using Official ESM CDN -->
            <div id="anam-esm-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-esm-video" width="300" height="400" autoplay playsinline muted style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: #000;"></video>
                <div id="anam-esm-status" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center;">
                    🌐 Loading via ESM CDN...<br><small>Official alternative source</small>
                </div>
            </div>

            <script type="module">
            console.log('🌐 Anam ESM CDN - Starting...');
            
            // Use official ESM.sh CDN (from vendor docs)
            import { createClient } from "https://esm.sh/@anam-ai/js-sdk@latest";
            
            const ESM_CONFIG = {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_esm'); ?>'
            };
            
            let esmClient = null;
            
            function updateESMStatus(message, isError = false) {
                const status = document.getElementById('anam-esm-status');
                if (status) {
                    status.innerHTML = message;
                    status.style.background = isError ? 'rgba(220, 53, 69, 0.9)' : 'rgba(0,0,0,0.9)';
                }
                console.log(isError ? '❌' : '🌐', message.replace(/<[^>]*>/g, ''));
            }
            
            async function getESMSessionToken() {
                updateESMStatus('🔑 Getting session token (ESM)...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'anam_esm_session');
                    formData.append('nonce', ESM_CONFIG.nonce);
                    
                    const response = await fetch(ESM_CONFIG.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    console.log('🌐 ESM session response:', data);
                    
                    if (data.success && data.data && data.data.sessionToken) {
                        console.log('✅ ESM session token received');
                        return data.data.sessionToken;
                    } else {
                        throw new Error(data.data || 'No session token in ESM response');
                    }
                } catch (error) {
                    console.error('❌ ESM session token error:', error);
                    throw error;
                }
            }
            
            async function initESMAvatar() {
                try {
                    updateESMStatus('🚀 Initializing via ESM...');
                    console.log('🌐 ESM SDK imported successfully');
                    
                    // Step 1: Get session token
                    const sessionToken = await getESMSessionToken();
                    updateESMStatus('✅ ESM token obtained');
                    
                    // Step 2: Create client using ESM imported function
                    updateESMStatus('🔧 Creating ESM client...');
                    console.log('🔍 Debug - createClient function:', typeof createClient);
                    console.log('🔍 Debug - sessionToken:', sessionToken ? 'present' : 'missing');
                    esmClient = createClient(sessionToken);
                    console.log('✅ ESM client created:', esmClient);
                    console.log('🔍 Debug - client type:', typeof esmClient);
                    console.log('🔍 Debug - client.on exists:', typeof esmClient?.on);
                    
                    // Step 3: Stream to video
                    updateESMStatus('📹 Starting ESM stream...');
                    await esmClient.streamToVideoElement('anam-esm-video');
                    console.log('✅ ESM stream started');
                    
                    // Step 4: Handle events
                    esmClient.on('connected', () => {
                        console.log('🔗 ESM avatar connected');
                        updateESMStatus('🎉 ESM avatar ready!');
                        setTimeout(() => {
                            const status = document.getElementById('anam-esm-status');
                            if (status) status.style.display = 'none';
                        }, 3000);
                    });
                    
                    esmClient.on('disconnected', () => {
                        console.log('🔌 ESM avatar disconnected');
                        updateESMStatus('🔌 Avatar disconnected', true);
                    });
                    
                    esmClient.on('error', (error) => {
                        console.error('❌ ESM avatar error:', error);
                        updateESMStatus(`❌ Error: ${error.message}`, true);
                    });
                    
                    console.log('🎉 ESM initialization complete');
                    
                } catch (error) {
                    console.error('❌ ESM initialization failed:', error);
                    updateESMStatus(`❌ ESM Failed: ${error.message}`, true);
                }
            }
            
            // Start ESM initialization
            initESMAvatar();
            
            // Global debug access
            window.anamESM = {
                client: () => esmClient,
                reinit: initESMAvatar,
                config: ESM_CONFIG
            };
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_esm')) {
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
        
        // Log for debugging
        error_log('=== ESM ANAM API REQUEST ===');
        error_log('URL: https://api.anam.ai/v1/auth/session-token');
        error_log('Body: ' . $args['body']);
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        
        if (is_wp_error($response)) {
            $error_msg = 'WordPress HTTP Error: ' . $response->get_error_message();
            error_log('ESM API Error: ' . $error_msg);
            wp_send_json_error($error_msg);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log('ESM API Response Code: ' . $response_code);
        error_log('ESM API Response Body: ' . $response_body);
        
        if ($response_code !== 200) {
            wp_send_json_error("API returned status {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['sessionToken']) && !empty($data['sessionToken'])) {
            wp_send_json_success(array(
                'sessionToken' => $data['sessionToken'],
                'timestamp' => current_time('mysql'),
                'source' => 'esm_cdn'
            ));
        } else {
            wp_send_json_error('Session token not found in ESM response: ' . $response_body);
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

new AnamAvatarESM();
