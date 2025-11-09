<?php
/**
 * AJAX Handlers Class
 * 
 * Handles all AJAX requests for the Anam Avatar plugin.
 * 
 * @package AnamAvatar
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Anam_Ajax_Handlers {
    
    private $option_name = 'anam_options';
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register AJAX hooks
     */
    public static function register_hooks() {
        $instance = self::get_instance();
        
        // Session token generation
        add_action('wp_ajax_anam_get_session_token', array($instance, 'get_session_token'));
        add_action('wp_ajax_nopriv_anam_get_session_token', array($instance, 'get_session_token'));
        
        // Session data and listing
        add_action('wp_ajax_anam_get_session_data', array($instance, 'get_session_data'));
        add_action('wp_ajax_anam_list_sessions', array($instance, 'list_sessions'));
        add_action('wp_ajax_anam_get_session_details', array($instance, 'get_session_details'));
        add_action('wp_ajax_anam_get_session_metadata', array($instance, 'get_session_metadata'));
        
        // Transcript operations
        add_action('wp_ajax_anam_save_transcript', array($instance, 'save_transcript'));
        add_action('wp_ajax_nopriv_anam_save_transcript', array($instance, 'save_transcript'));
        add_action('wp_ajax_anam_parse_transcript', array($instance, 'parse_transcript'));
        
        // Admin operations
        add_action('wp_ajax_anam_reset_plugin', array($instance, 'reset_plugin'));
    }
    
    /**
     * Get session token from Anam API
     */
    public function get_session_token() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $options = get_option($this->option_name, array());
        
        if (empty($options['api_key'])) {
            wp_send_json_error('API key not configured');
            return;
        }
        
        // Build persona config
        $persona_config = array();
        
        if (!empty($options['persona_id'])) {
            $persona_config['personaId'] = $options['persona_id'];
            error_log('🎯 Creating session with personaId: ' . $options['persona_id']);
        } else {
            error_log('⚠️ WARNING: Creating session WITHOUT personaId');
        }
        
        if (!empty($options['avatar_id'])) {
            $persona_config['avatarId'] = $options['avatar_id'];
        }
        
        if (!empty($options['voice_id'])) {
            $persona_config['voiceId'] = $options['voice_id'];
        }
        
        if (!empty($options['llm_id'])) {
            $persona_config['llmId'] = $options['llm_id'];
        }
        
        // Do NOT override systemPrompt - use the persona's configured prompt from Anam Labs
        // This ensures tool calling instructions configured in Anam Labs are preserved
            
        $persona_config['name'] = 'WordPress Avatar';
        
        $request_body = array(
            'personaConfig' => $persona_config
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $options['api_key']
            ),
            'body' => json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        );
        
        $response = wp_remote_post('https://api.anam.ai/v1/auth/session-token', $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error("API error {$response_code}: {$response_body}");
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
    
    /**
     * List sessions from Anam API
     */
    public function list_sessions() {
        check_ajax_referer('anam_admin_nonce', 'nonce');
        
        $options = get_option($this->option_name, array());
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        
        if (empty($options['api_key']) || empty($options['auth_token'])) {
            wp_send_json_error('API credentials not configured');
            return;
        }
        
        $url = add_query_arg(array(
            'page' => $page,
            'perPage' => $per_page,
            'apiKeyId' => $options['auth_token']
        ), 'https://api.anam.ai/v1/sessions');
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $options['api_key']
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error("API error {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        
        // Add parsed status for each session from WordPress database
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$session) {
                $session_id = $session['id'];
                $transcript = Anam_Database::get_transcript($session_id);
                
                if ($transcript) {
                    $parsed_value = $transcript->parsed;
                    $parsed_type = gettype($parsed_value);
                    $is_parsed = ($parsed_value == 1 || $parsed_value === '1' || $parsed_value === 1);
                    $session['parsed'] = $is_parsed;
                    error_log("📊 Session {$session_id}: parsed_value={$parsed_value}, type={$parsed_type}, is_parsed=" . ($is_parsed ? 'TRUE' : 'FALSE'));
                } else {
                    $session['parsed'] = false;
                    error_log("📊 Session {$session_id}: NO TRANSCRIPT IN DATABASE");
                }
            }
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get session details (transcript from database)
     * Always returns success so modal can open and Session JSON tab works
     */
    public function get_session_details() {
        check_ajax_referer('anam_admin_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        $transcript = Anam_Database::get_transcript($session_id);
        
        // Always return success, even if no transcript exists
        // This allows the modal to open and Session JSON tab to work
        if (!$transcript) {
            wp_send_json_success(array(
                'has_transcript' => false,
                'transcript' => array(),
                'message_count' => 0,
                'parsed' => 0,
                'parsed_at' => null,
                'created_at' => null,
                'no_transcript_message' => 'No transcript found. Either no transcript exists or it was deleted during a plugin reset.'
            ));
            return;
        }
        
        // Parse transcript JSON
        $transcript_array = json_decode($transcript->transcript_data, true);
        
        wp_send_json_success(array(
            'has_transcript' => !empty($transcript_array),
            'transcript' => $transcript_array,
            'message_count' => $transcript->message_count,
            'parsed' => $transcript->parsed,
            'parsed_at' => $transcript->parsed_at,
            'created_at' => $transcript->created_at
        ));
    }
    
    /**
     * Get session metadata from Anam API
     */
    public function get_session_metadata() {
        check_ajax_referer('anam_admin_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $options = get_option($this->option_name, array());
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        if (empty($options['api_key'])) {
            wp_send_json_error('API key not configured');
            return;
        }
        
        $url = 'https://api.anam.ai/v1/sessions/' . $session_id;
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $options['api_key']
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error("API error {$response_code}: {$response_body}");
            return;
        }
        
        $data = json_decode($response_body, true);
        wp_send_json_success($data);
    }
    
    /**
     * Save transcript to database
     */
    public function save_transcript() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $transcript_data = isset($_POST['transcript_data']) ? $_POST['transcript_data'] : '';
        
        if (empty($session_id) || empty($transcript_data)) {
            wp_send_json_error('Session ID and transcript data are required');
            return;
        }
        
        // Decode and re-encode to clean the JSON
        $messages = json_decode(stripslashes($transcript_data), true);
        
        if (!is_array($messages)) {
            wp_send_json_error('Invalid transcript data format');
            return;
        }
        
        $clean_json = json_encode($messages);
        $message_count = count($messages);
        
        // Check if this is a new transcript (not an update)
        $existing = Anam_Database::get_transcript($session_id);
        
        $success = Anam_Database::save_transcript($session_id, $clean_json, $message_count);
        
        if ($success) {
            // Send email notification for new sessions only
            if (!$existing) {
                $this->send_session_notification($session_id, $message_count);
            }
            
            wp_send_json_success(array(
                'message' => 'Transcript saved successfully',
                'message_count' => $message_count
            ));
        } else {
            wp_send_json_error('Failed to save transcript');
        }
    }
    
    /**
     * Send email notification when a session completes
     */
    private function send_session_notification($session_id, $message_count) {
        // Get WordPress admin email
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            error_log('⚠️ Cannot send session notification: No admin email configured');
            return;
        }
        
        // Get session metadata from Anam API to calculate duration
        $options = get_option('anam_options', array());
        $duration = 'N/A';
        $created_at = current_time('mysql');
        
        if (!empty($options['api_key'])) {
            $url = 'https://api.anam.ai/v1/sessions/' . $session_id;
            $args = array(
                'headers' => array('Authorization' => 'Bearer ' . $options['api_key']),
                'timeout' => 10
            );
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $session_data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($session_data['createdAt']) && isset($session_data['updatedAt'])) {
                    $created = strtotime($session_data['createdAt']);
                    $updated = strtotime($session_data['updatedAt']);
                    $duration_seconds = $updated - $created;
                    $duration = gmdate('i:s', $duration_seconds) . ' (mm:ss)';
                    $created_at = date('Y-m-d H:i:s', $created);
                }
            }
        }
        
        // Build the email
        $site_name = get_bloginfo('name');
        $transcripts_url = admin_url('admin.php?page=anam-sessions');
        
        $subject = sprintf('[%s] New Chat Session Completed', $site_name);
        
        $message = "A new chat session has been completed on your website.\n\n";
        $message .= "Session Details:\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📅 Date/Time: {$created_at}\n";
        $message .= "🆔 Session ID: {$session_id}\n";
        $message .= "⏱️  Duration: {$duration}\n";
        $message .= "💬 Messages: {$message_count}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "View all transcripts here:\n";
        $message .= $transcripts_url . "\n\n";
        $message .= "---\n";
        $message .= "This is an automated notification from {$site_name}";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Send the email
        $sent = wp_mail($admin_email, $subject, $message, $headers);
        
        if ($sent) {
            error_log('✅ Session notification email sent to: ' . $admin_email . ' for session: ' . $session_id);
        } else {
            error_log('❌ Failed to send session notification email for session: ' . $session_id);
        }
    }
    
    /**
     * Parse transcript and send to parser endpoint
     */
    public function parse_transcript() {
        check_ajax_referer('anam_admin_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $options = get_option($this->option_name, array());
        
        if (empty($session_id)) {
            wp_send_json_error('Session ID is required');
            return;
        }
        
        // Get transcript from database
        $transcript = Anam_Database::get_transcript($session_id);
        
        if (!$transcript) {
            wp_send_json_error('Failed to load transcripts either because no transcript exists or the transcript has been deleted from WordPress table due to a plugin reset.');
            return;
        }
        
        // Check if already parsed
        if ($transcript->parsed == 1) {
            wp_send_json_error('This transcript has already been parsed');
            return;
        }
        
        // Get parser endpoint URL
        $parser_url = isset($options['parser_endpoint_url']) ? trim($options['parser_endpoint_url']) : '';
        
        if (empty($parser_url)) {
            error_log('Parser URL empty. Options: ' . print_r($options, true));
            wp_send_json_error('Parser endpoint URL not configured');
            return;
        }
        
        // Decode transcript data
        $messages = json_decode($transcript->transcript_data, true);
        
        // Get session metadata from Anam API
        $session_metadata = array();
        if (!empty($options['api_key'])) {
            $url = 'https://api.anam.ai/v1/sessions/' . $session_id;
            $args = array(
                'headers' => array('Authorization' => 'Bearer ' . $options['api_key']),
                'timeout' => 30
            );
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $session_metadata = json_decode(wp_remote_retrieve_body($response), true);
            }
        }
        
        // Build payload
        $payload = array(
            'session_id' => $session_id,
            'transcript' => $messages,
            'session_metadata' => $session_metadata,
            'user_profile' => array(
                'first_name' => 'Nick',
                'last_name' => 'Patterson',
                'phone' => '(912) 233-1234'
            ),
            'timestamp' => current_time('c')
        );
        
        // Send to parser
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 60
        );
        
        $response = wp_remote_post($parser_url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Parser connection error: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $response_body = wp_remote_retrieve_body($response);
            wp_send_json_error("Parser error {$response_code}: {$response_body}");
            return;
        }
        
        // Mark as parsed
        $marked = Anam_Database::mark_as_parsed($session_id);
        error_log("✅ Parse successful for session {$session_id}. Marked as parsed: " . ($marked ? 'YES' : 'NO'));
        
        if (!$marked) {
            error_log("❌ WARNING: Failed to mark session {$session_id} as parsed in database!");
        }
        
        wp_send_json_success(array(
            'message' => 'Transcript parsed successfully',
            'parsed_at' => current_time('mysql'),
            'marked_as_parsed' => $marked
        ));
    }
    
    /**
     * Reset plugin (delete all data)
     */
    public function reset_plugin() {
        check_ajax_referer('anam_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Delete options
        delete_option('anam_options');
        
        // Drop database table
        Anam_Database::drop_table();
        
        wp_send_json_success(array(
            'message' => 'Plugin reset successfully',
            'redirect' => admin_url('admin.php?page=anam-settings')
        ));
    }
    
    /**
     * Get session data (legacy method - kept for compatibility)
     */
    public function get_session_data() {
        // This method appears to be unused but kept for backward compatibility
        wp_send_json_error('This endpoint is deprecated');
    }
}
