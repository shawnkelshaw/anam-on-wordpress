<?php
/**
 * Plugin Name: Anam API Key Tester
 * Description: Simple tester to verify Anam.ai API key and credentials
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AnamAPITester {
    
    private $api_key;
    private $avatar_id;
    private $voice_id;
    private $target_page_slug;
    
    public function __construct() {
        $this->api_key = 'your-anam-api-key-here';
        $this->avatar_id = 'your-avatar-id-here';
        $this->voice_id = 'your-voice-id-here';
        $this->target_page_slug = 'your-target-page-slug-here';
        
        add_action('wp_footer', array($this, 'add_api_tester'));
        add_action('wp_ajax_test_anam_api', array($this, 'test_api_key'));
        add_action('wp_ajax_nopriv_test_anam_api', array($this, 'test_api_key'));
    }
    
    public function add_api_tester() {
        if ($this->is_target_page()) {
            ?>
            <!-- Anam API Tester -->
            <div id="anam-api-tester" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: white; border: 2px solid #333; border-radius: 10px; padding: 20px; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h3 style="margin: 0 0 15px 0; color: #333;">🔧 Anam API Tester</h3>
                
                <div id="test-results" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                    <strong>Ready to test...</strong><br>
                    Click the button below to test your API key.
                </div>
                
                <button id="test-api-btn" onclick="testAnamAPI()" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                    🧪 Test API Key
                </button>
                
                <button onclick="clearResults()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    🗑️ Clear
                </button>
                
                <button onclick="closeAPITester()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; float: right;">
                    ✕ Close
                </button>
            </div>

            <script>
            console.log('🧪 Anam API Tester loaded');
            
            const TESTER_CONFIG = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('anam_api_test'); ?>',
                api_key: '<?php echo substr($this->api_key, 0, 20) . '...'; ?>',
                avatar_id: '<?php echo $this->avatar_id; ?>',
                voice_id: '<?php echo $this->voice_id; ?>'
            };
            
            function logResult(message, type = 'info') {
                const resultsDiv = document.getElementById('test-results');
                const timestamp = new Date().toLocaleTimeString();
                const colors = {
                    'info': '#333',
                    'success': '#28a745',
                    'error': '#dc3545',
                    'warning': '#ffc107'
                };
                
                resultsDiv.innerHTML += `<div style="color: ${colors[type]}; margin: 5px 0;">[${timestamp}] ${message}</div>`;
                resultsDiv.scrollTop = resultsDiv.scrollHeight;
            }
            
            function clearResults() {
                document.getElementById('test-results').innerHTML = '<strong>Results cleared...</strong><br>';
            }
            
            function closeAPITester() {
                document.getElementById('anam-api-tester').style.display = 'none';
            }
            
            async function testAnamAPI() {
                const testBtn = document.getElementById('test-api-btn');
                testBtn.disabled = true;
                testBtn.innerHTML = '🔄 Testing...';
                
                logResult('🚀 Starting API test...', 'info');
                logResult(`📋 API Key: ${TESTER_CONFIG.api_key}`, 'info');
                logResult(`🎭 Avatar ID: ${TESTER_CONFIG.avatar_id}`, 'info');
                logResult(`🎤 Voice ID: ${TESTER_CONFIG.voice_id}`, 'info');
                logResult('', 'info');
                
                try {
                    logResult('📡 Making AJAX request to WordPress...', 'info');
                    
                    const formData = new FormData();
                    formData.append('action', 'test_anam_api');
                    formData.append('nonce', TESTER_CONFIG.nonce);
                    
                    const response = await fetch(TESTER_CONFIG.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    logResult(`📊 WordPress Response Status: ${response.status}`, response.ok ? 'success' : 'error');
                    
                    if (!response.ok) {
                        throw new Error(`WordPress AJAX failed: ${response.status} ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    logResult('📦 WordPress Response received', 'success');
                    
                    if (data.success) {
                        logResult('✅ API TEST SUCCESSFUL!', 'success');
                        logResult(`🎯 Session Token: ${data.data.sessionToken.substring(0, 30)}...`, 'success');
                        logResult(`⏰ Response Time: ${data.data.response_time}ms`, 'info');
                        logResult(`📡 API Status: ${data.data.api_status}`, 'success');
                        
                        if (data.data.headers) {
                            logResult('📋 Response Headers:', 'info');
                            Object.entries(data.data.headers).forEach(([key, value]) => {
                                logResult(`  ${key}: ${value}`, 'info');
                            });
                        }
                        
                    } else {
                        logResult('❌ API TEST FAILED!', 'error');
                        logResult(`💥 Error: ${data.data}`, 'error');
                    }
                    
                } catch (error) {
                    logResult('💥 AJAX REQUEST FAILED!', 'error');
                    logResult(`Error: ${error.message}`, 'error');
                }
                
                testBtn.disabled = false;
                testBtn.innerHTML = '🧪 Test API Key';
            }
            
            // Auto-run test on load
            setTimeout(() => {
                logResult('🔄 Auto-running initial test...', 'info');
                testAnamAPI();
            }, 1000);
            </script>
            <?php
        }
    }
    
    public function test_api_key() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_api_test')) {
            wp_send_json_error('Security verification failed');
            return;
        }
        
        $start_time = microtime(true);
        
        // Test API endpoint
        $url = 'https://api.anam.ai/v1/auth/session-token';
        
        $persona_config = array(
            'name' => 'API Test Assistant',
            'avatarId' => $this->avatar_id,
            'voiceId' => $this->voice_id,
            'systemPrompt' => 'You are a test assistant for API verification.'
        );
        
        $body = json_encode(array('personaConfig' => $persona_config));
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent' => 'WordPress-Anam-API-Tester/1.0',
                'Accept' => 'application/json'
            ),
            'body' => $body,
            'timeout' => 30,
            'sslverify' => true,
            'redirection' => 5
        );
        
        // Detailed logging
        error_log('=== ANAM API TEST ===');
        error_log('URL: ' . $url);
        error_log('Headers: ' . print_r($args['headers'], true));
        error_log('Body: ' . $body);
        error_log('Args: ' . print_r($args, true));
        
        $response = wp_remote_post($url, $args);
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000);
        
        // Log raw response
        error_log('Raw Response: ' . print_r($response, true));
        
        if (is_wp_error($response)) {
            $error_message = 'WordPress HTTP Error: ' . $response->get_error_message();
            $error_code = $response->get_error_code();
            
            error_log('WP Error Code: ' . $error_code);
            error_log('WP Error Message: ' . $error_message);
            
            wp_send_json_error(array(
                'message' => $error_message,
                'error_code' => $error_code,
                'response_time' => $response_time,
                'test_details' => array(
                    'url' => $url,
                    'method' => 'POST',
                    'timeout' => 30,
                    'ssl_verify' => true
                )
            ));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        // Enhanced logging
        error_log('Response Code: ' . $response_code);
        error_log('Response Headers: ' . print_r($response_headers, true));
        error_log('Response Body: ' . $response_body);
        
        $success_data = array(
            'response_time' => $response_time,
            'api_status' => $response_code,
            'headers' => $response_headers,
            'raw_body' => $response_body
        );
        
        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['sessionToken'])) {
                $success_data['sessionToken'] = $data['sessionToken'];
                $success_data['message'] = 'API key is valid and working!';
                wp_send_json_success($success_data);
            } else {
                wp_send_json_error(array_merge($success_data, array(
                    'message' => 'Valid response but no session token found',
                    'json_error' => json_last_error_msg()
                )));
            }
        } else {
            wp_send_json_error(array_merge($success_data, array(
                'message' => "API returned status {$response_code}"
            )));
        }
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

// Initialize the tester
new AnamAPITester();
