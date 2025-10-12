<?php
/**
 * Plugin Name: Anam Avatar Sanam-on-page-local
 * Description: Self-contained Anam.ai integration with no external dependencies
 * Version: 1.0.2
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAvatarStandalone {
    
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
        
        add_action('wp_footer', array($this, 'add_avatar_to_target_page'));
        add_action('wp_ajax_get_anam_session_token', array($this, 'get_session_token'));
        add_action('wp_ajax_nopriv_get_anam_session_token', array($this, 'get_session_token'));
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
            console.log('🚀 Anam Avatar Standalone - Starting...');
            
            // Configuration
            const ANAM_CONFIG = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_session_token'); ?>',
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
            
            // Session token function
            async function getSessionToken() {
                log('Getting session token from WordPress...');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_anam_session_token');
                    formData.append('nonce', ANAM_CONFIG.nonce);
                    
                    const response = await fetch(ANAM_CONFIG.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    log('Session token response:', data);
                    
                    if (data.success && data.data && data.data.sessionToken) {
                        log('✅ Session token received successfully');
                        return data.data.sessionToken;
                    } else {
                        throw new Error(data.data || 'Invalid session token response');
                    }
                } catch (error) {
                    log('❌ Session token error:', error);
                    throw error;
                }
            }
            
            // Load Anam SDK with multiple fallbacks
            async function loadAnamSDK() {
                return new Promise((resolve, reject) => {
                    // Check if already loaded
                    if (window.AnamSDK) {
                        log('✅ Anam SDK already loaded');
                        resolve(window.AnamSDK);
                        return;
                    }
                    
                    log('Loading Anam SDK...');
                    showLoading('Loading SDK...');
                    
                    const cdnUrls = [
                        'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
                        'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js',
                        'https://cdn.skypack.dev/@anam-ai/js-sdk'
                    ];
                    
                    let currentIndex = 0;
                    
                    function tryNextCDN() {
                        if (currentIndex >= cdnUrls.length) {
                            reject(new Error('All CDN sources failed to load'));
                            return;
                        }
                        
                        const script = document.createElement('script');
                        script.src = cdnUrls[currentIndex];
                        script.async = true;
                        
                        script.onload = function() {
                            log(`✅ SDK loaded from: ${cdnUrls[currentIndex]}`);
                            
                            // Wait a bit for SDK to initialize
                            setTimeout(() => {
                                if (window.AnamSDK) {
                                    resolve(window.AnamSDK);
                                } else {
                                    log(`❌ SDK loaded but AnamSDK not available from: ${cdnUrls[currentIndex]}`);
                                    currentIndex++;
                                    tryNextCDN();
                                }
                            }, 500);
                        };
                        
                        script.onerror = function() {
                            log(`❌ Failed to load from: ${cdnUrls[currentIndex]}`);
                            currentIndex++;
                            tryNextCDN();
                        };
                        
                        document.head.appendChild(script);
                    }
                    
                    tryNextCDN();
                });
            }
            
            // Initialize avatar
            async function initializeAvatar() {
                if (isInitialized) {
                    log('Avatar already initialized');
                    return;
                }
                
                try {
                    log('🚀 Starting avatar initialization...');
                    showLoading('Initializing Avatar...');
                    
                    // Step 1: Load SDK
                    const AnamSDK = await loadAnamSDK();
                    log('✅ SDK loaded successfully');
                    
                    // Step 2: Get session token
                    showLoading('Getting Session Token...');
                    const sessionToken = await getSessionToken();
                    log('✅ Session token obtained');
                    
                    // Step 3: Create client
                    showLoading('Creating Avatar Client...');
                    const { createClient } = AnamSDK;
                    anamClient = createClient(sessionToken);
                    log('✅ Avatar client created');
                    
                    // Step 4: Stream to video
                    showLoading('Starting Video Stream...');
                    await anamClient.streamToVideoElement('anam-video');
                    log('✅ Video stream started');
                    
                    // Step 5: Setup event listeners
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
            
            // Global functions for debugging
            window.anamDebug = {
                reinitialize: initializeAvatar,
                getClient: () => anamClient,
                getConfig: () => ANAM_CONFIG
            };
            
            log('🎯 Anam Avatar Standalone loaded and ready!');
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
        
        // Log the request for debugging
        error_log('Anam API Request: ' . $url);
        error_log('Anam API Body: ' . $body);
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = 'WordPress HTTP Error: ' . $response->get_error_message();
            error_log('Anam API Error: ' . $error_message);
            wp_send_json_error($error_message);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        // Enhanced logging
        error_log('Anam API Response Code: ' . $response_code);
        error_log('Anam API Response Headers: ' . print_r($response_headers, true));
        error_log('Anam API Response Body: ' . $response_body);
        
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
                'timestamp' => current_time('mysql')
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

// Initialize the plugin
new AnamAvatarStandalone();
