<?php
/**
 * Plugin Name: Anam Avatar Local SDK
 * Description: Anam.ai integration with locally hosted SDK
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarLocalSDK {
    
    private $api_key;
    private $persona_id;
    private $avatar_id;
    private $voice_id;
    private $target_page_slug;
    private $plugin_dir;
    
    public function __construct() {
        $this->api_key = 'MWZjNWY0YjgtMGM1Zi00OWRiLTgwMjgtNWM4N2FjNGY4NGU0OmhrNkd5ZVpmWmNSTFlFR3Q0dWluNWpwclY0NlpJWitjTnZOMnZsQmlWWFU9';
        $this->persona_id = '1bf11606-16b7-4788-a3db-3293148ca7bd';
        $this->avatar_id = '30fa96d0-26c4-4e55-94a0-517025942e18';
        $this->voice_id = 'b7bf471f-5435-49f8-a979-4483e4ccc10f';
        $this->target_page_slug = 'using-anam-ai-digital-avatars-and-custom-llm-to-replace-long-form-data-entry';
        $this->plugin_dir = plugin_dir_path(__FILE__);
        
        add_action('wp_footer', array($this, 'add_avatar_to_target_page'));
        add_action('wp_ajax_get_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_get_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_download_anam_sdk', array($this, 'download_sdk'));
        add_action('wp_ajax_nopriv_download_anam_sdk', array($this, 'download_sdk'));
        
        // Create SDK directory if it doesn't exist
        $this->ensure_sdk_directory();
    }
    
    private function ensure_sdk_directory() {
        $sdk_dir = $this->plugin_dir . 'sdk/';
        if (!file_exists($sdk_dir)) {
            wp_mkdir_p($sdk_dir);
        }
    }
    
    private function get_local_sdk_url() {
        return plugins_url('sdk/anam-sdk.js', __FILE__);
    }
    
    private function get_local_sdk_path() {
        return $this->plugin_dir . 'sdk/anam-sdk.js';
    }
    
    private function sdk_exists_locally() {
        return file_exists($this->get_local_sdk_path());
    }
    
    public function download_sdk() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_download_sdk')) {
            wp_send_json_error('Security verification failed');
            return;
        }
        
        $sdk_urls = array(
            'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
            'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js'
        );
        
        foreach ($sdk_urls as $url) {
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'WordPress-Anam-Plugin/1.0'
            ));
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $sdk_content = wp_remote_retrieve_body($response);
                
                if (!empty($sdk_content)) {
                    $local_path = $this->get_local_sdk_path();
                    
                    if (file_put_contents($local_path, $sdk_content)) {
                        wp_send_json_success(array(
                            'message' => 'SDK downloaded successfully',
                            'size' => strlen($sdk_content),
                            'url' => $url,
                            'local_path' => $local_path
                        ));
                        return;
                    }
                }
            }
        }
        
        wp_send_json_error('Failed to download SDK from all sources');
    }
    
    public function add_avatar_to_target_page() {
        if ($this->is_target_page()) {
            ?>
            <!-- Anam Avatar Container -->
            <div id="anam-avatar-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <video id="anam-video" width="300" height="400" autoplay playsinline muted style="border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: #000;"></video>
                <div id="anam-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); color: white; padding: 15px; border-radius: 8px; font-size: 14px; max-width: 250px; text-align: center;">
                    🤖 Loading Digital Avatar...<br>
                    <small>Please wait...</small>
                </div>
                <button id="anam-toggle" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 16px;">×</button>
            </div>

            <script>
            console.log('🚀 Anam Avatar Local SDK - Starting...');
            
            // Configuration
            const ANAM_CONFIG = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                session_nonce: '<?php echo wp_create_nonce('anam_session_token'); ?>',
                download_nonce: '<?php echo wp_create_nonce('anam_download_sdk'); ?>',
                local_sdk_url: '<?php echo $this->get_local_sdk_url(); ?>',
                sdk_exists: <?php echo $this->sdk_exists_locally() ? 'true' : 'false'; ?>,
                debug: true
            };
            
            // Global variables
            let anamClient = null;
            let isInitialized = false;
            
            // Utility functions
            function log(message, data = null) {
                if (ANAM_CONFIG.debug) {
                    console.log('🤖 Anam:', message, data || '');
                }
            }
            
            function showError(message) {
                const loadingEl = document.getElementById('anam-loading');
                if (loadingEl) {
                    loadingEl.innerHTML = `❌ Error: ${message}<br><small>Check console for details</small>`;
                    loadingEl.style.background = 'rgba(220, 53, 69, 0.9)';
                }
            }
            
            function showLoading(message) {
                const loadingEl = document.getElementById('anam-loading');
                if (loadingEl) {
                    loadingEl.innerHTML = `🤖 ${message}<br><small>Please wait...</small>`;
                    loadingEl.style.background = 'rgba(0,0,0,0.9)';
                    loadingEl.style.display = 'block';
                }
            }
            
            function hideLoading() {
                const loadingEl = document.getElementById('anam-loading');
                if (loadingEl) {
                    loadingEl.style.display = 'none';
                }
            }
            
            // Download SDK to local server
            async function downloadSDK() {
                log('📥 Downloading SDK to local server...');
                showLoading('Downloading SDK...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'download_anam_sdk');
                    formData.append('nonce', ANAM_CONFIG.download_nonce);
                    
                    const response = await fetch(ANAM_CONFIG.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        log('✅ SDK downloaded successfully:', data.data);
                        ANAM_CONFIG.sdk_exists = true;
                        return true;
                    } else {
                        throw new Error(data.data || 'Download failed');
                    }
                } catch (error) {
                    log('❌ SDK download failed:', error);
                    throw error;
                }
            }
            
            // Load SDK from local server
            async function loadLocalSDK() {
                return new Promise((resolve, reject) => {
                    log('📂 Loading SDK from local server...');
                    showLoading('Loading Local SDK...');
                    
                    const script = document.createElement('script');
                    script.src = ANAM_CONFIG.local_sdk_url + '?v=' + Date.now(); // Cache busting
                    script.async = true;
                    
                    script.onload = function() {
                        log('✅ Local SDK loaded successfully');
                        
                        setTimeout(() => {
                            if (window.AnamSDK) {
                                resolve(window.AnamSDK);
                            } else {
                                reject(new Error('SDK loaded but AnamSDK not available'));
                            }
                        }, 500);
                    };
                    
                    script.onerror = function() {
                        log('❌ Failed to load local SDK');
                        reject(new Error('Local SDK failed to load'));
                    };
                    
                    document.head.appendChild(script);
                });
            }
            
            // Get session token
            async function getSessionToken() {
                log('🔑 Getting session token...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_anam_session_token');
                    formData.append('nonce', ANAM_CONFIG.session_nonce);
                    
                    const response = await fetch(ANAM_CONFIG.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.data && data.data.sessionToken) {
                        log('✅ Session token received');
                        return data.data.sessionToken;
                    } else {
                        throw new Error(data.data || 'Invalid session token response');
                    }
                } catch (error) {
                    log('❌ Session token error:', error);
                    throw error;
                }
            }
            
            // Initialize avatar
            async function initializeAvatar() {
                if (isInitialized) {
                    log('Avatar already initialized');
                    return;
                }
                
                try {
                    log('🚀 Starting avatar initialization...');
                    
                    // Step 1: Ensure SDK is available locally
                    if (!ANAM_CONFIG.sdk_exists) {
                        await downloadSDK();
                    }
                    
                    // Step 2: Load local SDK
                    const AnamSDK = await loadLocalSDK();
                    log('✅ SDK loaded successfully');
                    
                    // Step 3: Get session token
                    showLoading('Getting Session Token...');
                    const sessionToken = await getSessionToken();
                    log('✅ Session token obtained');
                    
                    // Step 4: Create client
                    showLoading('Creating Avatar Client...');
                    const { createClient } = AnamSDK;
                    anamClient = createClient(sessionToken);
                    log('✅ Avatar client created');
                    
                    // Step 5: Stream to video
                    showLoading('Starting Video Stream...');
                    await anamClient.streamToVideoElement('anam-video');
                    log('✅ Video stream started');
                    
                    // Step 6: Setup event listeners
                    anamClient.on('connected', () => {
                        log('🔗 Avatar connected');
                        hideLoading();
                        isInitialized = true;
                    });
                    
                    anamClient.on('disconnected', () => {
                        log('🔌 Avatar disconnected');
                        showError('Avatar disconnected');
                    });
                    
                    anamClient.on('error', (error) => {
                        log('❌ Avatar error:', error);
                        showError('Avatar error: ' + error.message);
                    });
                    
                    log('🎉 Avatar initialization complete!');
                    
                } catch (error) {
                    log('❌ Avatar initialization failed:', error);
                    showError(error.message);
                }
            }
            
            // Toggle visibility
            function setupToggle() {
                const toggleBtn = document.getElementById('anam-toggle');
                const container = document.getElementById('anam-avatar-container');
                
                if (toggleBtn && container) {
                    let isVisible = true;
                    
                    toggleBtn.addEventListener('click', () => {
                        if (isVisible) {
                            container.style.transform = 'translateX(320px)';
                            toggleBtn.innerHTML = '🤖';
                            toggleBtn.style.right = '-10px';
                        } else {
                            container.style.transform = 'translateX(0)';
                            toggleBtn.innerHTML = '×';
                            toggleBtn.style.right = '10px';
                        }
                        isVisible = !isVisible;
                    });
                    
                    container.style.transition = 'transform 0.3s ease';
                }
            }
            
            // Start initialization when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setupToggle();
                    initializeAvatar();
                });
            } else {
                setupToggle();
                initializeAvatar();
            }
            
            log('🎯 Anam Avatar Local SDK loaded and ready!');
            </script>
            <?php
        }
    }
    
    public function get_session_token() {
        // Enhanced security and error handling
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_session_token')) {
            wp_send_json_error('Security verification failed');
            return;
        }
        
        $url = 'https://api.anam.ai/v1/auth/session-token';
        
        $persona_config = array(
            'name' => 'Digital Assistant',
            'avatarId' => $this->avatar_id,
            'voiceId' => $this->voice_id,
            'systemPrompt' => 'You are a helpful digital assistant on Shawn Kelshaw\'s website. You can discuss digital avatars, AI technology, and help visitors with questions about the content on this page. Be friendly, knowledgeable, and concise in your responses.'
        );
        
        $body = json_encode(array('personaConfig' => $persona_config));
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent' => 'WordPress-Anam-Plugin/1.0'
            ),
            'body' => $body,
            'timeout' => 45,
            'sslverify' => true
        );
        
        $response = wp_remote_post($url, $args);
        
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
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Session token not found in response');
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

// Initialize the plugin
new AnamAvatarLocalSDK();
