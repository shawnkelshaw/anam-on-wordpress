<?php
/**
 * Plugin Name: CDN Connectivity Test
 * Description: Test direct access to CDN services from WordPress
 * Version: 1.0.0
 * Author: Shawn Kelshaw
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CDNConnectivityTest {
    
    private $target_page_slug;
    
    public function __construct() {
        $this->target_page_slug = 'your-target-page-slug-here';
        
        add_action('wp_footer', array($this, 'add_cdn_test'));
        add_action('wp_ajax_test_cdn_access', array($this, 'test_cdn_access'));
        add_action('wp_ajax_nopriv_test_cdn_access', array($this, 'test_cdn_access'));
    }
    
    public function add_cdn_test() {
        if ($this->is_target_page()) {
            ?>
            <!-- CDN Connectivity Test -->
            <div id="cdn-test-panel" style="position: fixed; top: 20px; left: 20px; z-index: 9999; background: white; border: 2px solid #333; border-radius: 10px; padding: 20px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h3 style="margin: 0 0 15px 0; color: #333;">🌐 CDN Connectivity Test</h3>
                
                <div id="cdn-test-results" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;">
                    <strong>Ready to test CDN connectivity...</strong><br>
                    This will test both browser-side and server-side access.
                </div>
                
                <button id="test-cdn-btn" onclick="testCDNConnectivity()" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                    🧪 Test CDN Access
                </button>
                
                <button onclick="clearCDNResults()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    🗑️ Clear
                </button>
                
                <button onclick="closeCDNTest()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; float: right;">
                    ✕ Close
                </button>
            </div>

            <script>
            console.log('🌐 CDN Connectivity Test loaded');
            
            const CDN_TEST_CONFIG = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('cdn_test'); ?>'
            };
            
            function logCDNResult(message, type = 'info') {
                const resultsDiv = document.getElementById('cdn-test-results');
                const timestamp = new Date().toLocaleTimeString();
                const colors = {
                    'info': '#333',
                    'success': '#28a745',
                    'error': '#dc3545',
                    'warning': '#ffc107'
                };
                
                const color = colors[type] || colors.info;
                resultsDiv.innerHTML += `<div style="color: ${color}; margin: 2px 0;">[${timestamp}] ${message}</div>`;
                resultsDiv.scrollTop = resultsDiv.scrollHeight;
                console.log(`🌐 CDN Test: ${message}`);
            }
            
            async function testBrowserCDNAccess() {
                logCDNResult('🌍 Testing browser-side CDN access...', 'info');
                
                const cdnUrls = [
                    'https://esm.sh/@anam-ai/js-sdk@latest',
                    'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
                    'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js'
                ];
                
                for (const url of cdnUrls) {
                    try {
                        logCDNResult(`📡 Testing: ${url}`, 'info');
                        
                        const response = await fetch(url, {
                            method: 'HEAD',
                            mode: 'no-cors'
                        });
                        
                        logCDNResult(`✅ ${url} - Response received`, 'success');
                    } catch (error) {
                        logCDNResult(`❌ ${url} - Error: ${error.message}`, 'error');
                    }
                }
            }
            
            async function testServerCDNAccess() {
                logCDNResult('🖥️ Testing server-side CDN access...', 'info');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'test_cdn_access');
                    formData.append('nonce', CDN_TEST_CONFIG.nonce);
                    
                    const response = await fetch(CDN_TEST_CONFIG.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        logCDNResult('✅ Server-side test completed', 'success');
                        data.data.results.forEach(result => {
                            const status = result.success ? '✅' : '❌';
                            const type = result.success ? 'success' : 'error';
                            logCDNResult(`${status} ${result.url} - ${result.message}`, type);
                        });
                    } else {
                        logCDNResult(`❌ Server test failed: ${data.data}`, 'error');
                    }
                } catch (error) {
                    logCDNResult(`❌ Server test error: ${error.message}`, 'error');
                }
            }
            
            async function testCDNConnectivity() {
                const testBtn = document.getElementById('test-cdn-btn');
                testBtn.disabled = true;
                testBtn.textContent = '🔄 Testing...';
                
                logCDNResult('🚀 Starting CDN connectivity tests...', 'info');
                
                // Test browser-side access
                await testBrowserCDNAccess();
                
                // Test server-side access
                await testServerCDNAccess();
                
                logCDNResult('🏁 CDN connectivity tests completed', 'info');
                
                testBtn.disabled = false;
                testBtn.textContent = '🧪 Test CDN Access';
            }
            
            function clearCDNResults() {
                document.getElementById('cdn-test-results').innerHTML = '<strong>Results cleared...</strong><br>';
            }
            
            function closeCDNTest() {
                document.getElementById('cdn-test-panel').style.display = 'none';
            }
            
            // Auto-run test on load
            setTimeout(() => {
                logCDNResult('🔄 Auto-running CDN connectivity test...', 'info');
                testCDNConnectivity();
            }, 1000);
            </script>
            <?php
        }
    }
    
    public function test_cdn_access() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cdn_test')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $cdn_urls = array(
            'https://esm.sh/@anam-ai/js-sdk@latest',
            'https://unpkg.com/@anam-ai/js-sdk@latest/dist/index.umd.js',
            'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@latest/dist/index.umd.js',
            'https://api.anam.ai/v1/auth/session-token'
        );
        
        $results = array();
        
        foreach ($cdn_urls as $url) {
            $start_time = microtime(true);
            
            $args = array(
                'method' => 'HEAD',
                'timeout' => 10,
                'sslverify' => true,
                'user-agent' => 'WordPress-CDN-Test/1.0'
            );
            
            $response = wp_remote_head($url, $args);
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000);
            
            if (is_wp_error($response)) {
                $results[] = array(
                    'url' => $url,
                    'success' => false,
                    'message' => 'WP Error: ' . $response->get_error_message(),
                    'response_time' => $response_time
                );
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                $results[] = array(
                    'url' => $url,
                    'success' => ($response_code >= 200 && $response_code < 400),
                    'message' => "HTTP {$response_code} ({$response_time}ms)",
                    'response_time' => $response_time,
                    'response_code' => $response_code
                );
            }
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'timestamp' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ));
    }
    
    private function is_target_page() {
        global $post;
        return is_single() && $post && $post->post_name === $this->target_page_slug;
    }
}

new CDNConnectivityTest();
