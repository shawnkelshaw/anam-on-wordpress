<?php
/**
 * Plugin Name: Anam Avatar Clean Version
 * Description: Clean Anam.ai integration without proxy dependencies
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarClean {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'MWZjNWY0YjgtMGM1Zi00OWRiLTgwMjgtNWM4N2FjNGY4NGU0OmhrNkd5ZVpmWmNSTFlFR3Q0dWluNWpwclY0NlpJWitjTnZOMnZsQmlWWFU9';
        $this->avatar_id = '30fa96d0-26c4-4e55-94a0-517025942e18';
        $this->voice_id = 'b7bf471f-5435-49f8-a979-4483e4ccc10f';
        $this->target_page_slug = 'using-anam-ai-digital-avatars-and-custom-llm-to-replace-long-form-data-entry';
        
        add_action('wp_footer', array($this, 'add_avatar_container'));
        add_action('wp_ajax_get_anam_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_get_anam_token', array($this, 'get_session_token'));
    }
    
    public function add_avatar_container() {
        if ($this->is_target_page()) {
            ?>
            <!-- Clean Anam Avatar -->
            <div id="anam-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-video" width="300" height="400" autoplay playsinline muted style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: #000;"></video>
                <div id="anam-status" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center;">
                    🤖 Initializing...<br><small>Please wait</small>
                </div>
            </div>

            <script>
            // Clean implementation - no external dependencies
            console.log('🧹 Anam Clean Version Starting...');
            
            const CONFIG = {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_token'); ?>'
            };
            
            let client = null;
            
            function updateStatus(message, isError = false) {
                const status = document.getElementById('anam-status');
                if (status) {
                    status.innerHTML = message;
                    status.style.background = isError ? 'rgba(220, 53, 69, 0.9)' : 'rgba(0,0,0,0.9)';
                }
                console.log(isError ? '❌' : '🤖', message.replace(/<[^>]*>/g, ''));
            }
            
            async function getToken() {
                updateStatus('🔑 Getting session token...');
                
                const formData = new FormData();
                formData.append('action', 'get_anam_token');
                formData.append('nonce', CONFIG.nonce);
                
                const response = await fetch(CONFIG.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Token received');
                    return data.data.token;
                } else {
                    throw new Error(data.data || 'Token request failed');
                }
            }
            
            async function loadSDK() {
                updateStatus('📦 Loading Anam SDK...');
                
                return new Promise((resolve, reject) => {
                    // Try direct script injection with multiple sources
                    const sources = [
                        'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
                        'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js'
                    ];
                    
                    let currentIndex = 0;
                    
                    function tryNext() {
                        if (currentIndex >= sources.length) {
                            reject(new Error('All SDK sources failed'));
                            return;
                        }
                        
                        const script = document.createElement('script');
                        script.src = sources[currentIndex];
                        script.async = true;
                        
                        script.onload = () => {
                            console.log(`✅ SDK loaded from: ${sources[currentIndex]}`);
                            setTimeout(() => {
                                if (window.AnamSDK) {
                                    resolve(window.AnamSDK);
                                } else {
                                    console.log(`❌ SDK object not found from: ${sources[currentIndex]}`);
                                    currentIndex++;
                                    tryNext();
                                }
                            }, 1000);
                        };
                        
                        script.onerror = () => {
                            console.log(`❌ Failed to load: ${sources[currentIndex]}`);
                            currentIndex++;
                            tryNext();
                        };
                        
                        document.head.appendChild(script);
                    }
                    
                    tryNext();
                });
            }
            
            async function initAvatar() {
                try {
                    updateStatus('🚀 Starting initialization...');
                    
                    // Step 1: Load SDK
                    const SDK = await loadSDK();
                    updateStatus('✅ SDK loaded successfully');
                    
                    // Step 2: Get token
                    const token = await getToken();
                    updateStatus('✅ Token obtained');
                    
                    // Step 3: Create client
                    updateStatus('🔧 Creating client...');
                    const { createClient } = SDK;
                    client = createClient(token);
                    
                    // Step 4: Stream to video
                    updateStatus('📹 Starting video stream...');
                    await client.streamToVideoElement('anam-video');
                    
                    // Step 5: Handle events
                    client.on('connected', () => {
                        updateStatus('🎉 Avatar ready!');
                        setTimeout(() => {
                            const status = document.getElementById('anam-status');
                            if (status) status.style.display = 'none';
                        }, 2000);
                    });
                    
                    client.on('error', (error) => {
                        updateStatus(`❌ Avatar error: ${error.message}`, true);
                    });
                    
                } catch (error) {
                    updateStatus(`❌ Failed: ${error.message}`, true);
                    console.error('Initialization failed:', error);
                }
            }
            
            // Start when ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAvatar);
            } else {
                initAvatar();
            }
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        if (!wp_verify_nonce($_POST['nonce'], 'anam_token')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode(array(
                'personaConfig' => array(
                    'name' => 'Digital Assistant',
                    'avatarId' => $this->avatar_id,
                    'voiceId' => $this->voice_id,
                    'systemPrompt' => 'You are a helpful digital assistant.'
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['sessionToken'])) {
            wp_send_json_success(array('token' => $data['sessionToken']));
        } else {
            wp_send_json_error('No session token in response');
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

new AnamAvatarClean();
