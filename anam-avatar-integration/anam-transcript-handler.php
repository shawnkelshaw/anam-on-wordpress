<?php
/**
 * Anam Transcript Handler
 * Handles conversation transcript processing and Parser Tool integration
 * 
 * @package AnamOnWordPress
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Feature flag for transcript processing
 * Set to false to completely disable transcript functionality
 */
if (!defined('ANAM_TRANSCRIPT_FEATURE')) {
    define('ANAM_TRANSCRIPT_FEATURE', true); // Enable for testing diagnostics
}

/**
 * Anam Transcript Handler Class
 * Safely handles transcript processing with extensive error checking
 */
class AnamTranscriptHandler {
    
    private $version = '2.0.0';
    private $table_name;
    private $feature_enabled = false;
    private $db_version = '2.0';
    
    public function __construct() {
        // Early exit if feature is disabled
        if (!defined('ANAM_TRANSCRIPT_FEATURE') || !ANAM_TRANSCRIPT_FEATURE) {
            error_log('Anam Transcript: Feature disabled via flag');
            return;
        }
        
        // Wait for WordPress to be fully loaded
        add_action('init', array($this, 'maybe_initialize'), 10);
        add_action('admin_init', array($this, 'run_diagnostics'), 5);
    }
    
    /**
     * Maybe initialize the transcript handler
     * Only runs after WordPress is fully loaded
     */
    public function maybe_initialize() {
        // Extensive safety checks
        if (!$this->is_wordpress_ready()) {
            error_log('Anam Transcript: WordPress not ready, skipping initialization');
            return;
        }
        
        if (!$this->check_requirements()) {
            error_log('Anam Transcript: Requirements not met, skipping initialization');
            return;
        }
        
        // Safe to initialize
        $this->initialize();
    }
    
    /**
     * Check if WordPress is ready for our operations
     */
    private function is_wordpress_ready() {
        $required_functions = [
            'wp_verify_nonce',
            'wp_send_json_success',
            'wp_send_json_error',
            'current_time',
            'get_option',
            'update_option'
        ];
        
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                error_log("Anam Transcript: Missing required function: $function");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check system requirements
     */
    private function check_requirements() {
        global $wpdb;
        
        // Check if database is available
        if (!isset($wpdb) || !is_object($wpdb)) {
            error_log('Anam Transcript: WordPress database not available');
            return false;
        }
        
        // Check WordPress version (require 5.0+)
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            error_log('Anam Transcript: WordPress version too old (requires 5.0+)');
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize the transcript handler
     */
    private function initialize() {
        global $wpdb;
        
        $this->table_name = $wpdb->prefix . 'anam_conversations';
        $this->feature_enabled = true;
        
        // Add AJAX handlers only if methods exist
        if (method_exists($this, 'handle_transcript_processing')) {
            add_action('wp_ajax_anam_process_transcript', array($this, 'handle_transcript_processing'));
            add_action('wp_ajax_nopriv_anam_process_transcript', array($this, 'handle_transcript_processing'));
        }
        
        // Add cron handler only if method exists
        if (method_exists($this, 'process_with_parser_tool')) {
            add_action('anam_process_with_parser_tool', array($this, 'process_with_parser_tool'));
        }
        
        // Create database table if it doesn't exist
        $this->create_table();
        
        error_log('Anam Transcript: Successfully initialized');
    }
    
    /**
     * Create database table for storing conversations
     */
    public function create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name)) === $this->table_name;
        
        if ($table_exists) {
            error_log('Anam Transcript: Table exists, checking for migration...');
            $this->migrate_table_if_needed();
            return;
        }
        
        // Create table with proper WordPress method
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            page_url varchar(500) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            metadata longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            parsed_data longtext DEFAULT NULL,
            review_status varchar(20) DEFAULT 'pending',
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            supabase_id varchar(100) DEFAULT NULL,
            supabase_sent_at datetime DEFAULT NULL,
            email_sent tinyint(1) DEFAULT 0,
            email_sent_at datetime DEFAULT NULL,
            transcript_raw longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY status (status),
            KEY review_status (review_status),
            KEY created_at (created_at),
            KEY page_url (page_url)
        ) $charset_collate;";
        
        // Include WordPress upgrade functions
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        
        // Create table safely
        $result = dbDelta($sql);
        
        if ($result) {
            error_log('Anam Transcript: Database table created successfully');
            update_option('anam_transcript_table_version', '2.0.0');
            update_option('anam_db_version', '2.0');
        } else {
            error_log('Anam Transcript: Failed to create database table');
        }
    }
    
    /**
     * Migrate existing table structure to new format with review workflow
     * Adds columns for parsed data, review status, and Supabase integration
     */
    public function migrate_table_if_needed() {
        global $wpdb;
        
        $current_db_version = get_option('anam_db_version', '1.0');
        
        // Migration to v2.0 - Add review workflow columns
        if (version_compare($current_db_version, '2.0', '<')) {
            error_log('Anam Transcript: Starting migration to v2.0 (review workflow)...');
            
            // Check which columns need to be added
            $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$this->table_name}");
            
            $columns_to_add = array();
            
            if (!in_array('parsed_data', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN parsed_data longtext DEFAULT NULL';
            }
            if (!in_array('review_status', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN review_status varchar(20) DEFAULT \'pending\'';
            }
            if (!in_array('reviewed_by', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN reviewed_by bigint(20) DEFAULT NULL';
            }
            if (!in_array('reviewed_at', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN reviewed_at datetime DEFAULT NULL';
            }
            if (!in_array('supabase_id', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN supabase_id varchar(100) DEFAULT NULL';
            }
            if (!in_array('supabase_sent_at', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN supabase_sent_at datetime DEFAULT NULL';
            }
            if (!in_array('email_sent', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN email_sent tinyint(1) DEFAULT 0';
            }
            if (!in_array('email_sent_at', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN email_sent_at datetime DEFAULT NULL';
            }
            if (!in_array('transcript_raw', $existing_columns)) {
                $columns_to_add[] = 'ADD COLUMN transcript_raw longtext DEFAULT NULL';
            }
            
            // Add columns if needed
            if (!empty($columns_to_add)) {
                $alter_sql = "ALTER TABLE {$this->table_name} " . implode(', ', $columns_to_add);
                $result = $wpdb->query($alter_sql);
                
                if ($result !== false) {
                    error_log('Anam Transcript: Added ' . count($columns_to_add) . ' new columns');
                } else {
                    error_log('Anam Transcript: Failed to add columns: ' . $wpdb->last_error);
                    return false;
                }
            }
            
            // Add index for review_status if it doesn't exist
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$this->table_name} WHERE Key_name = 'review_status'");
            if (empty($indexes)) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD KEY review_status (review_status)");
            }
            
            // Update version
            update_option('anam_db_version', '2.0');
            error_log('Anam Transcript: Migration to v2.0 completed successfully');
        }
        
        return true;
    }
    
    /**
     * Run diagnostic checks for cleanup
     */
    public function run_diagnostics() {
        if (!current_user_can('manage_options')) {
            return; // Only run for admins
        }
        
        $this->check_for_artifacts();
    }
    
    /**
     * Check for artifacts from failed installation
     */
    private function check_for_artifacts() {
        global $wpdb;
        
        if (!isset($wpdb) || !is_object($wpdb)) {
            return;
        }
        
        $artifacts_found = array();
        
        // Check for database table
        $table_name = $wpdb->prefix . 'anam_conversations';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $artifacts_found['database_table'] = array(
                'table' => $table_name,
                'records' => $count,
                'action_needed' => $count > 0 ? 'manual_review' : 'safe_to_cleanup'
            );
        }
        
        // Check for WordPress options
        $options_to_check = array(
            'anam_transcript_version',
            'anam_transcript_table_version',
            'anam_parser_tool_url',
            'anam_transcript_processing_enabled'
        );
        
        foreach ($options_to_check as $option) {
            $value = get_option($option, null);
            if ($value !== null) {
                $artifacts_found['options'][$option] = $value;
            }
        }
        
        // Log findings
        if (!empty($artifacts_found)) {
            error_log('Anam Transcript Diagnostics: Found artifacts: ' . json_encode($artifacts_found));
            
            // Store findings for admin review
            update_option('anam_transcript_diagnostics', $artifacts_found);
        } else {
            error_log('Anam Transcript Diagnostics: No artifacts found - clean slate');
            delete_option('anam_transcript_diagnostics');
        }
    }
    
    /**
     * Clean up empty artifacts (safe cleanup only)
     */
    public function cleanup_empty_artifacts() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $cleaned = array();
        
        // Only clean up empty table
        $table_name = $wpdb->prefix . 'anam_conversations';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            if ($count == 0) {
                $wpdb->query("DROP TABLE IF EXISTS $table_name");
                $cleaned['database_table'] = 'removed_empty_table';
                error_log('Anam Transcript: Cleaned up empty conversations table');
            }
        }
        
        // Clean up unused options
        $test_options = array(
            'anam_transcript_version',
            'anam_transcript_diagnostics'
        );
        
        foreach ($test_options as $option) {
            if (get_option($option, null) !== null) {
                delete_option($option);
                $cleaned['options'][] = $option;
            }
        }
        
        if (!empty($cleaned)) {
            error_log('Anam Transcript: Cleanup completed: ' . json_encode($cleaned));
        }
        
        return $cleaned;
    }
    
    /**
     * Remove database table (admin only, with confirmation)
     */
    public function remove_table() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'anam_conversations';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if ($table_exists) {
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
            delete_option('anam_transcript_table_version');
            error_log('Anam Transcript: Database table removed');
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle session processing AJAX request
     */
    public function handle_transcript_processing() {
        error_log('Anam Transcript: Session processing AJAX handler called');
        
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'anam_session')) {
            error_log('Anam Transcript: Security check failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Validate required data
        if (empty($_POST['session_id'])) {
            error_log('Anam Transcript: No session ID provided');
            wp_send_json_error('No session ID provided');
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $timestamp = sanitize_text_field($_POST['timestamp'] ?? '');
        $page_url = sanitize_url($_POST['page_url'] ?? '');
        $metadata = $_POST['metadata'] ?? '';
        
        // Sanitize metadata if provided
        if (!empty($metadata)) {
            $metadata = wp_unslash($metadata);
            $metadata_decoded = json_decode($metadata, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Anam Transcript: Invalid metadata JSON: ' . json_last_error_msg());
                $metadata = null;
            } else {
                $metadata = json_encode($metadata_decoded); // Re-encode for safety
            }
        }
        
        error_log('Anam Transcript: Received session ID: ' . $session_id);
        
        // Store session data in database
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'session_id' => $session_id,
                'page_url' => $page_url,
                'timestamp' => $timestamp ?: current_time('mysql'),
                'status' => 'pending',
                'metadata' => $metadata,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('Anam Transcript: Database insert failed. Error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to store transcript: ' . $wpdb->last_error);
            return;
        }
        
        error_log('Anam Transcript: Successfully stored session with ID: ' . $wpdb->insert_id);
        
        $conversation_id = $wpdb->insert_id;
        
        // Schedule background processing (parser tool will fetch from Anam.ai API)
        wp_schedule_single_event(time() + 10, 'anam_process_with_parser_tool', array($conversation_id));
        
        wp_send_json_success(array(
            'message' => 'Session ID received and queued for processing',
            'conversation_id' => $conversation_id,
            'session_id' => $session_id
        ));
    }
    
    /**
     * Convert transcript array to readable plain text
     */
    private function convert_transcript_to_plain_text($transcript_data) {
        if (!is_array($transcript_data)) {
            return '';
        }
        
        $plain_text = '';
        foreach ($transcript_data as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                $role = $message['role'] === 'user' ? 'User' : 'Assistant';
                $content = strip_tags($message['content']);
                $plain_text .= "$role: $content\n\n";
            }
        }
        
        return trim($plain_text);
    }
    
    /**
     * Parse vehicle data from transcript text
     * Extracts: Year, Make, Model, VIN
     */
    private function parse_vehicle_data($transcript_text) {
        $parsed = array(
            'year' => null,
            'make' => null,
            'model' => null,
            'vin' => null,
            'confidence' => array()
        );
        
        // Parse VIN (17 characters, alphanumeric, no I, O, Q)
        if (preg_match('/\b([A-HJ-NPR-Z0-9]{17})\b/i', $transcript_text, $matches)) {
            $parsed['vin'] = strtoupper($matches[1]);
            $parsed['confidence']['vin'] = 'high';
        }
        
        // Parse Year (1900-2099)
        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $transcript_text, $matches)) {
            $parsed['year'] = $matches[1];
            $parsed['confidence']['year'] = 'high';
        }
        
        // Parse Make (common vehicle manufacturers)
        $makes = array(
            'Toyota', 'Honda', 'Ford', 'Chevrolet', 'Chevy', 'Nissan', 'BMW', 'Mercedes', 'Audi',
            'Volkswagen', 'VW', 'Hyundai', 'Kia', 'Mazda', 'Subaru', 'Jeep', 'Ram', 'Dodge',
            'Chrysler', 'Buick', 'GMC', 'Cadillac', 'Lexus', 'Acura', 'Infiniti', 'Tesla',
            'Volvo', 'Porsche', 'Land Rover', 'Jaguar', 'Mini', 'Fiat', 'Alfa Romeo'
        );
        
        foreach ($makes as $make) {
            if (preg_match('/\b' . preg_quote($make, '/') . '\b/i', $transcript_text, $matches)) {
                $parsed['make'] = $matches[0];
                $parsed['confidence']['make'] = 'medium';
                break;
            }
        }
        
        // Parse Model (extract word after make, or common patterns)
        if ($parsed['make']) {
            // Try to find model after make mention
            $pattern = '/\b' . preg_quote($parsed['make'], '/') . '\s+([A-Za-z0-9-]+(?:\s+[A-Za-z0-9-]+)?)/i';
            if (preg_match($pattern, $transcript_text, $matches)) {
                $parsed['model'] = trim($matches[1]);
                $parsed['confidence']['model'] = 'medium';
            }
        }
        
        return $parsed;
    }
    
    /**
     * Send email notification to admin about new session
     */
    private function send_email_notification($conversation_id, $parsed_data) {
        $options = get_option('anam_options', array());
        $email_enabled = isset($options['email_notifications']) ? $options['email_notifications'] : true;
        
        if (!$email_enabled) {
            error_log('Anam: Email notifications disabled');
            return false;
        }
        
        global $wpdb;
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $conversation_id
        ));
        
        if (!$conversation) {
            return false;
        }
        
        // Prepare email content
        $admin_email = get_option('admin_email');
        $subject = '[Anam] New Vehicle Conversation Ready for Review';
        
        $review_url = admin_url('admin.php?page=anam-sessions&action=view&id=' . $conversation_id);
        
        $message = "A new vehicle conversation has been captured and is ready for review.\n\n";
        $message .= "Session ID: {$conversation->session_id}\n";
        $message .= "Date: " . date('F j, Y g:i A', strtotime($conversation->created_at)) . "\n";
        $message .= "Page: {$conversation->page_url}\n\n";
        
        if (!empty($parsed_data)) {
            $message .= "Parsed Vehicle Data:\n";
            $message .= "-------------------\n";
            if (!empty($parsed_data['year'])) $message .= "Year: {$parsed_data['year']}\n";
            if (!empty($parsed_data['make'])) $message .= "Make: {$parsed_data['make']}\n";
            if (!empty($parsed_data['model'])) $message .= "Model: {$parsed_data['model']}\n";
            if (!empty($parsed_data['vin'])) $message .= "VIN: {$parsed_data['vin']}\n";
            $message .= "\n";
        }
        
        $message .= "Review and approve this session:\n";
        $message .= $review_url . "\n\n";
        $message .= "Or view all pending sessions:\n";
        $message .= admin_url('admin.php?page=anam-sessions&status=pending') . "\n";
        
        // Send email
        $sent = wp_mail($admin_email, $subject, $message);
        
        if ($sent) {
            // Update email sent status
            $wpdb->update(
                $this->table_name,
                array(
                    'email_sent' => 1,
                    'email_sent_at' => current_time('mysql')
                ),
                array('id' => $conversation_id),
                array('%d', '%s'),
                array('%d')
            );
            error_log('Anam: Email notification sent for conversation ' . $conversation_id);
        } else {
            error_log('Anam: Failed to send email notification for conversation ' . $conversation_id);
            // Create admin notice as fallback
            $this->create_admin_notice($conversation_id);
        }
        
        return $sent;
    }
    
    /**
     * Create admin notice as fallback when email fails
     */
    private function create_admin_notice($conversation_id) {
        $notices = get_option('anam_pending_notices', array());
        $notices[] = array(
            'conversation_id' => $conversation_id,
            'created_at' => current_time('mysql')
        );
        update_option('anam_pending_notices', $notices);
    }
    
    /**
     * Process transcript with Parser Tool (background cron job)
     * NOTE: This function now works with session IDs and fetches transcripts from Anam.ai API
     */
    public function process_with_parser_tool($conversation_id) {
        global $wpdb;
        
        // Get conversation from database
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND status = 'pending'",
            $conversation_id
        ));
        
        if (!$conversation) {
            error_log("Anam: Conversation $conversation_id not found or already processed");
            return;
        }
        
        // Update status to processing
        $wpdb->update(
            $this->table_name,
            array('status' => 'processing'),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );
        
        // Get configuration from options
        $options = get_option('anam_options', array());
        $parser_tool_url = isset($options['parser_tool_url']) ? $options['parser_tool_url'] : '';
        $anam_api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        if (empty($parser_tool_url)) {
            $this->mark_conversation_error($conversation_id, 'Parser Tool URL not configured');
            return;
        }
        
        if (empty($anam_api_key)) {
            $this->mark_conversation_error($conversation_id, 'Anam API key not configured');
            return;
        }
        
        // Fetch transcript from Anam.ai API using session ID
        $session_id = $conversation->session_id;
        $anam_response = wp_remote_get("https://api.anam.ai/v1/sessions/{$session_id}/transcript", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $anam_api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($anam_response)) {
            $this->mark_conversation_error($conversation_id, 'Failed to fetch transcript from Anam.ai: ' . $anam_response->get_error_message());
            return;
        }
        
        $anam_status = wp_remote_retrieve_response_code($anam_response);
        if ($anam_status !== 200) {
            $this->mark_conversation_error($conversation_id, 'Anam.ai API returned status: ' . $anam_status);
            return;
        }
        
        $transcript_data = json_decode(wp_remote_retrieve_body($anam_response), true);
        
        // Store raw transcript
        $wpdb->update(
            $this->table_name,
            array('transcript_raw' => json_encode($transcript_data)),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );
        
        // Parse vehicle data from transcript
        $transcript_text = $this->convert_transcript_to_plain_text($transcript_data);
        $parsed_data = $this->parse_vehicle_data($transcript_text);
        
        // Store parsed data
        $wpdb->update(
            $this->table_name,
            array(
                'parsed_data' => json_encode($parsed_data),
                'review_status' => 'pending'
            ),
            array('id' => $conversation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        error_log('Anam: Parsed vehicle data for conversation ' . $conversation_id . ': ' . json_encode($parsed_data));
        
        // Send email notification
        $this->send_email_notification($conversation_id, $parsed_data);
        
        // Send transcript to Parser Tool (if configured)
        if (!empty($parser_tool_url)) {
            $response = wp_remote_post($parser_tool_url, array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode(array(
                    'sessionId' => $session_id,
                    'transcriptData' => $transcript_data,
                    'parsedData' => $parsed_data,
                    'conversation_id' => $conversation_id,
                    'page_url' => $conversation->page_url,
                    'timestamp' => $conversation->timestamp
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                error_log('Anam: Parser Tool request failed: ' . $response->get_error_message());
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code === 200) {
                    error_log('Anam: Parser Tool processed conversation ' . $conversation_id);
                } else {
                    error_log('Anam: Parser Tool returned error ' . $response_code);
                }
            }
        }
        
        // Mark as completed (parsing done, awaiting review)
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'completed',
                'processed_at' => current_time('mysql')
            ),
            array('id' => $conversation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        error_log("Anam: Successfully processed conversation $conversation_id with session $session_id");
    }
    
    /**
     * Mark conversation as failed with error message
     */
    private function mark_conversation_error($conversation_id, $error_message) {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'error',
                'error_message' => $error_message,
                'processed_at' => current_time('mysql')
            ),
            array('id' => $conversation_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        error_log('Anam: Failed to process conversation ' . $conversation_id . ': ' . $error_message);
    }
    
    /**
     * Get diagnostic information for admin display
     */
    public function get_diagnostics() {
        return get_option('anam_transcript_diagnostics', array());
    }
    
    /**
     * Check if feature is enabled and ready
     */
    public function is_enabled() {
        return $this->feature_enabled && defined('ANAM_TRANSCRIPT_FEATURE') && ANAM_TRANSCRIPT_FEATURE;
    }
    
    /**
     * Get feature status for debugging
     */
    public function get_status() {
        return array(
            'feature_flag' => defined('ANAM_TRANSCRIPT_FEATURE') ? ANAM_TRANSCRIPT_FEATURE : false,
            'wordpress_ready' => $this->is_wordpress_ready(),
            'requirements_met' => $this->check_requirements(),
            'initialized' => $this->feature_enabled,
            'version' => $this->version,
            'db_version' => get_option('anam_db_version', '1.0')
        );
    }
    
    /**
     * Send data to Supabase
     */
    public function send_to_supabase($conversation_id) {
        global $wpdb;
        
        // Get conversation
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $conversation_id
        ));
        
        if (!$conversation) {
            return array('success' => false, 'message' => 'Conversation not found');
        }
        
        // Check if already sent
        if (!empty($conversation->supabase_id)) {
            return array('success' => false, 'message' => 'Already sent to Supabase');
        }
        
        // Get Supabase configuration
        $options = get_option('anam_options', array());
        $supabase_url = isset($options['supabase_url']) ? $options['supabase_url'] : '';
        $supabase_key = isset($options['supabase_key']) ? $options['supabase_key'] : '';
        $supabase_table = isset($options['supabase_table']) ? $options['supabase_table'] : 'vehicle_conversations';
        
        if (empty($supabase_url) || empty($supabase_key)) {
            return array('success' => false, 'message' => 'Supabase not configured');
        }
        
        // Parse data
        $parsed_data = json_decode($conversation->parsed_data, true);
        
        // Prepare payload
        $payload = array(
            'session_id' => $conversation->session_id,
            'year' => $parsed_data['year'] ?? null,
            'make' => $parsed_data['make'] ?? null,
            'model' => $parsed_data['model'] ?? null,
            'vin' => $parsed_data['vin'] ?? null,
            'page_url' => $conversation->page_url,
            'conversation_date' => $conversation->created_at,
            'processed_at' => current_time('mysql')
        );
        
        // Send to Supabase
        $response = wp_remote_post(
            rtrim($supabase_url, '/') . '/rest/v1/' . $supabase_table,
            array(
                'headers' => array(
                    'apikey' => $supabase_key,
                    'Authorization' => 'Bearer ' . $supabase_key,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ),
                'body' => json_encode($payload),
                'timeout' => 30
            )
        );
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 201) {
            // Success - extract Supabase ID from response
            $response_data = json_decode($response_body, true);
            $supabase_id = isset($response_data[0]['id']) ? $response_data[0]['id'] : 'unknown';
            
            // Update conversation
            $wpdb->update(
                $this->table_name,
                array(
                    'supabase_id' => $supabase_id,
                    'supabase_sent_at' => current_time('mysql'),
                    'review_status' => 'sent',
                    'reviewed_by' => get_current_user_id(),
                    'reviewed_at' => current_time('mysql')
                ),
                array('id' => $conversation_id),
                array('%s', '%s', '%s', '%d', '%s'),
                array('%d')
            );
            
            error_log('Anam: Successfully sent conversation ' . $conversation_id . ' to Supabase');
            return array('success' => true, 'message' => 'Sent to Supabase', 'supabase_id' => $supabase_id);
        } else {
            error_log('Anam: Supabase error ' . $response_code . ': ' . $response_body);
            return array('success' => false, 'message' => 'Supabase error: ' . $response_code);
        }
    }
}

// Initialize the transcript handler
$anam_transcript_handler = new AnamTranscriptHandler();

// Make it globally accessible for admin pages
if (!function_exists('anam_get_transcript_handler')) {
    function anam_get_transcript_handler() {
        global $anam_transcript_handler;
        return $anam_transcript_handler;
    }
}
