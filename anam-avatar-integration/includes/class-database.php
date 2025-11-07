<?php
/**
 * Database Operations Class
 * 
 * Handles all database-related operations for the Anam Avatar plugin.
 * 
 * @package AnamAvatar
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Anam_Database {
    
    /**
     * Get the transcripts table name
     * 
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'anam_transcripts';
    }
    
    /**
     * Ensure the transcripts table exists
     * Creates it if missing
     */
    public static function ensure_table_exists() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log('⚠️ Anam transcripts table missing, creating now...');
            self::create_table();
        }
    }
    
    /**
     * Create database table for storing transcripts
     */
    public static function create_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            transcript_data longtext NOT NULL,
            message_count int(11) DEFAULT 0,
            parsed tinyint(1) DEFAULT 0,
            parsed_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('✅ Anam transcripts table created/verified');
    }
    
    /**
     * Get transcript by session ID
     * 
     * @param string $session_id
     * @return object|null
     */
    public static function get_transcript($session_id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
    }
    
    /**
     * Save or update transcript
     * 
     * @param string $session_id
     * @param string $transcript_data JSON string
     * @param int $message_count
     * @return bool
     */
    public static function save_transcript($session_id, $transcript_data, $message_count) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Check if transcript already exists
        $existing = self::get_transcript($session_id);
        
        if ($existing) {
            // Update existing transcript
            $result = $wpdb->update(
                $table_name,
                array(
                    'transcript_data' => $transcript_data,
                    'message_count' => $message_count,
                    'updated_at' => current_time('mysql')
                ),
                array('session_id' => $session_id),
                array('%s', '%d', '%s'),
                array('%s')
            );
        } else {
            // Insert new transcript
            $result = $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'transcript_data' => $transcript_data,
                    'message_count' => $message_count,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Mark transcript as parsed
     * 
     * @param string $session_id
     * @return bool
     */
    public static function mark_as_parsed($session_id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $result = $wpdb->update(
            $table_name,
            array(
                'parsed' => 1,
                'parsed_at' => current_time('mysql')
            ),
            array('session_id' => $session_id),
            array('%d', '%s'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete all plugin data (for uninstall)
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
