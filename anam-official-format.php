<?php
/**
 * Plugin Name: Anam Avatar Official Format
 * Description: Anam.ai integration using exact official API format
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarOfficial {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $llm_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'MWZjNWY0YjgtMGM1Zi00OWRiLTgwMjgtNWM4N2FjNGY4NGU0OmhrNkd5ZVpmWmNSTFlFR3Q0dWluNWpwclY0NlpJWitjTnZOMnZsQmlWWFU9';
        $this->avatar_id = '30fa96d0-26c4-4e55-94a0-517025942e18';
        $this->voice_id = 'b7bf471f-5435-49f8-a979-4483e4ccc10f';
        $this->llm_id = '0934d97d-0c3a-4f33-91b0-5e136a0ef466'; // From official docs
        $this->target_page_slug = 'using-anam-ai-digital-avatars-and-custom-llm-to-replace-long-form-data-entry';
        
        add_action('wp_footer', array($this, 'add_avatar_container'));
        add_action('wp_ajax_anam_session', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_session', array($this, 'get_session_token'));
    }
    
    public function add_avatar_container() {
        if ($this->is_target_page()) {
            ?>
            <!-- Official Anam Avatar Implementation -->
            <div id="anam-official-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-official-video" width="300" height="400" autoplay playsinline muted style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: #000;"></video>
                <div id="anam-official-status" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center;">
                    🤖 Loading Avatar...<br><small>Following official docs</small>
                </div>
            </div>

            <script>
            console.log('📚 Anam Official Format - Starting...');
            
            const OFFICIAL_CONFIG = {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_official'); ?>'
            };
            
            let officialClient = null;
            
            function updateOfficialStatus(message, isError = false) {
                const status = document.getElementById('anam-official-status');
                if (status) {
                    status.innerHTML = message;
                    status.style.background = isError ? 'rgba(220, 53, 69, 0.9)' : 'rgba(0,0,0,0.9)';
                }
                console.log(isError ? '❌' : '📚', message.replace(/<[^>]*>/g, ''));
            }
            
            async function getOfficialSessionToken() {
                updateOfficialStatus('🔑 Getting session token (official format)...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'anam_session');
                    formData.append('nonce', OFFICIAL_CONFIG.nonce);
                    
                    const response = await fetch(OFFICIAL_CONFIG.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    console.log('📚 Session response:', data);
                    
                    if (data.success && data.data && data.data.sessionToken) {
                        console.log('✅ Official session token received');
                        return data.data.sessionToken;
                    } else {
                        throw new Error(data.data || 'No session token in response');
                    }
                } catch (error) {
                    console.error('❌ Session token error:', error);
                    throw error;
                }
            }
            
            async function loadOfficialSDK() {
                updateOfficialStatus('📦 Loading official Anam SDK...');
                
                return new Promise((resolve, reject) => {
                    // Use exact CDN from official docs
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js';
                    script.async = true;
                    
                    script.onload = function() {
                        console.log('✅ Official SDK loaded');
                        
                        // Wait for SDK to initialize
                        setTimeout(() => {
                            if (window.AnamSDK) {
                                console.log('✅ AnamSDK object available');
                                resolve(window.AnamSDK);
                            } else {
                                console.error('❌ AnamSDK object not found');
                                reject(new Error('AnamSDK not available after load'));
                            }
                        }, 1000);
                    };
                    
                    script.onerror = function() {
                        console.error('❌ Failed to load official SDK');
                        reject(new Error('Official SDK failed to load'));
                    };
                    
                    document.head.appendChild(script);
                });
            }
            
            async function initOfficialAvatar() {
                try {
                    updateOfficialStatus('🚀 Initializing (official method)...');
                    
                    // Step 1: Load official SDK
                    const AnamSDK = await loadOfficialSDK();
                    updateOfficialStatus('✅ SDK loaded');
                    
                    // Step 2: Get session token using official format
                    const sessionToken = await getOfficialSessionToken();
                    updateOfficialStatus('✅ Token obtained');
                    
                    // Step 3: Create client using official method
                    updateOfficialStatus('🔧 Creating official client...');
                    const { createClient } = AnamSDK;
                    officialClient = createClient(sessionToken);
                    console.log('✅ Official client created:', officialClient);
                    
                    // Step 4: Stream to video using official method
                    updateOfficialStatus('📹 Starting official stream...');
                    await officialClient.streamToVideoElement('anam-official-video');
                    console.log('✅ Official stream started');
                    
                    // Step 5: Handle official events
                    officialClient.on('connected', () => {
                        console.log('🔗 Official avatar connected');
                        updateOfficialStatus('🎉 Official avatar ready!');
                        setTimeout(() => {
                            const status = document.getElementById('anam-official-status');
                            if (status) status.style.display = 'none';
                        }, 3000);
                    });
                    
                    officialClient.on('disconnected', () => {
                        console.log('🔌 Official avatar disconnected');
                        updateOfficialStatus('🔌 Avatar disconnected', true);
                    });
                    
                    officialClient.on('error', (error) => {
                        console.error('❌ Official avatar error:', error);
                        updateOfficialStatus(`❌ Error: ${error.message}`, true);
                    });
                    
                    console.log('🎉 Official initialization complete');
                    
                } catch (error) {
                    console.error('❌ Official initialization failed:', error);
                    updateOfficialStatus(`❌ Failed: ${error.message}`, true);
                }
            }
            
            // Start official initialization
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOfficialAvatar);
            } else {
                initOfficialAvatar();
            }
            
            // Global debug access
            window.anamOfficial = {
                client: () => officialClient,
                reinit: initOfficialAvatar,
                config: OFFICIAL_CONFIG
            };
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_official')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Use exact format from official documentation
        $persona_config = array(
            'name' => 'Digital Assistant',
            'avatarId' => $this->avatar_id,
            'voiceId' => $this->voice_id,
            'llmId' => $this->llm_id, // Include llmId as shown in docs
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
        
        // Log the exact request for debugging
        error_log('=== OFFICIAL ANAM API REQUEST ===');
        error_log('URL: https://api.anam.ai/v1/auth/session-token');
        error_log('Headers: ' . print_r($args['headers'], true));
        error_log('Body: ' . $args['body']);
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        
        if (is_wp_error($response)) {
            $error_msg = 'WordPress HTTP Error: ' . $response->get_error_message();
            error_log('API Error: ' . $error_msg);
            wp_send_json_error($error_msg);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log('API Response Code: ' . $response_code);
        error_log('API Response Body: ' . $response_body);
        
        if ($response_code !== 200) {
            wp_send_json_error("API returned status {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON response: ' . json_last_error_msg());
            return;
        }
        
        if (isset($data['sessionToken']) && !empty($data['sessionToken'])) {
            wp_send_json_success(array(
                'sessionToken' => $data['sessionToken'],
                'timestamp' => current_time('mysql'),
                'format' => 'official'
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

new AnamAvatarOfficial();
