<?php
/**
 * Anam Sessions Admin Page
 * Displays conversation sessions with review and Supabase integration
 * 
 * @package AnamOnWordPress
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Anam Sessions Admin Class
 */
class AnamSessionsAdmin {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'anam_conversations';
        
        // Add AJAX handlers
        add_action('wp_ajax_anam_send_to_supabase', array($this, 'ajax_send_to_supabase'));
        add_action('wp_ajax_anam_update_parsed_data', array($this, 'ajax_update_parsed_data'));
        add_action('wp_ajax_anam_delete_session', array($this, 'ajax_delete_session'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'anam-avatar_page_anam-sessions') {
            return;
        }
        
        wp_enqueue_script(
            'anam-sessions-admin',
            plugins_url('anam-sessions-admin.js', __FILE__),
            array('jquery'),
            '2.0.0',
            true
        );
        
        wp_localize_script('anam-sessions-admin', 'anamSessions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anam_sessions_nonce')
        ));
        
        // Add inline styles
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }
    
    /**
     * Get admin CSS styles
     */
    private function get_admin_styles() {
        return '
            .anam-sessions-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .anam-sessions-table th { background: #f0f0f1; padding: 12px; text-align: left; font-weight: 600; }
            .anam-sessions-table td { padding: 12px; border-bottom: 1px solid #ddd; vertical-align: top; }
            .anam-sessions-table tr:hover { background: #f9f9f9; }
            .anam-sessions-table tr.expanded { background: #f9f9f9; }
            .anam-accordion-content { display: none; padding: 20px; background: #fafafa; border-top: 2px solid #0073aa; }
            .anam-accordion-content.active { display: block; }
            .anam-accordion-section { margin-bottom: 20px; padding: 15px; background: white; border-radius: 4px; border: 1px solid #ddd; }
            .anam-accordion-section h4 { margin-top: 0; color: #0073aa; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .anam-status-badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 500; }
            .anam-status-pending { background: #fef7e0; color: #996800; }
            .anam-status-sent { background: #e0f7e0; color: #006900; }
            .anam-status-error { background: #ffe0e0; color: #990000; }
            .anam-parsed-data { background: #f5f5f5; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .anam-parsed-field { margin: 5px 0; }
            .anam-parsed-field strong { display: inline-block; width: 80px; }
            .anam-button { padding: 6px 12px; margin: 2px; border-radius: 3px; cursor: pointer; border: none; }
            .anam-button-primary { background: #2271b1; color: white; }
            .anam-button-secondary { background: #f0f0f1; color: #2c3338; }
            .anam-button-danger { background: #d63638; color: white; }
            .anam-button:hover { opacity: 0.9; }
            .anam-modal { display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
            .anam-modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
            .anam-modal-close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
            .anam-transcript { background: #f9f9f9; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace; font-size: 13px; }
            .anam-filters { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; }
            .anam-filter-item { display: inline-block; margin-right: 15px; }
            .anam-pagination { margin: 20px 0; text-align: center; }
            .anam-pagination a { padding: 8px 12px; margin: 0 2px; background: #f0f0f1; text-decoration: none; border-radius: 3px; }
            .anam-pagination a.current { background: #2271b1; color: white; }
            .anam-edit-field { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px; }
        ';
    }
    
    /**
     * Render sessions page
     */
    public function render_sessions_page() {
        global $wpdb;
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $where = "1=1";
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND review_status = %s", $status_filter);
        }
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE $where");
        
        // Get sessions
        $sessions = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} 
            WHERE $where 
            ORDER BY created_at DESC 
            LIMIT $per_page OFFSET $offset"
        );
        
        // Get counts for filters
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE review_status = 'pending'");
        $sent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE review_status = 'sent'");
        
        // Get email notifications setting
        $options = get_option('anam_options', array());
        $email_notifications = isset($options['email_notifications']) ? $options['email_notifications'] : true;
        
        ?>
        <div class="wrap">
            <h1>Chat Transcripts</h1>
            
            <!-- Email Notifications Setting -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <form method="post" action="options.php">
                    <?php settings_fields('anam_options'); ?>
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="anam_options[email_notifications]" value="1" <?php checked($email_notifications, true); ?> onchange="this.form.submit()" />
                        <strong>Send email when new conversation is ready for review</strong>
                    </label>
                    <p class="description" style="margin: 8px 0 0 0;">
                        Email will be sent to: <strong><?php echo esc_html(get_option('admin_email')); ?></strong>
                    </p>
                </form>
            </div>
            
            <div class="anam-filters">
                <div class="anam-filter-item">
                    <strong>Filter by Status:</strong>
                    <a href="?page=anam-sessions&status=all" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">
                        All (<?php echo $total; ?>)
                    </a> |
                    <a href="?page=anam-sessions&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        Pending Review (<?php echo $pending_count; ?>)
                    </a> |
                    <a href="?page=anam-sessions&status=sent" class="<?php echo $status_filter === 'sent' ? 'current' : ''; ?>">
                        Sent to Supabase (<?php echo $sent_count; ?>)
                    </a>
                </div>
            </div>
            
            <?php if (empty($sessions)): ?>
                <p>No sessions found.</p>
            <?php else: ?>
                <table class="anam-sessions-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Session ID</th>
                            <th style="width: 180px;">Date</th>
                            <th style="width: 150px;">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <?php 
                            $parsed_data = json_decode($session->parsed_data, true);
                            $has_data = !empty($parsed_data) && (
                                !empty($parsed_data['year']) || 
                                !empty($parsed_data['make']) || 
                                !empty($parsed_data['model']) || 
                                !empty($parsed_data['vin'])
                            );
                            ?>
                            <tr data-session-id="<?php echo esc_attr($session->id); ?>" class="anam-session-row">
                                <td>
                                    <?php 
                                    if (!empty($session->session_id) && strlen($session->session_id) > 6) {
                                        $display_id = substr($session->session_id, 0, 6) . '...';
                                    } elseif (!empty($session->session_id)) {
                                        $display_id = $session->session_id; // Show full ID if it's short
                                    } else {
                                        $display_id = 'No ID';
                                    }
                                    ?>
                                    <code title="<?php echo esc_attr($session->session_id); ?>"><?php echo esc_html($display_id); ?></code>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($session->created_at)); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'anam-status-' . $session->review_status;
                                    $status_text = ucfirst($session->review_status);
                                    ?>
                                    <span class="anam-status-badge <?php echo $status_class; ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                    <?php if (!empty($session->supabase_id)): ?>
                                        <br><small>ID: <?php echo esc_html($session->supabase_id); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="anam-button anam-button-secondary anam-toggle-accordion" 
                                            data-session-id="<?php echo esc_attr($session->id); ?>">
                                        View Details
                                    </button>
                                    
                                    <?php if ($session->review_status === 'pending' && $has_data): ?>
                                        <button class="anam-button anam-button-primary anam-send-to-supabase" 
                                                data-session-id="<?php echo esc_attr($session->id); ?>">
                                            Send to Supabase
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="anam-button anam-button-danger anam-delete-session" 
                                            data-session-id="<?php echo esc_attr($session->id); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <!-- Accordion Content -->
                            <tr class="anam-accordion-row" data-session-id="<?php echo esc_attr($session->id); ?>" style="display: none;">
                                <td colspan="4">
                                    <div class="anam-accordion-content">
                                        <div class="anam-accordion-section">
                                            <h4>Session Information</h4>
                                            <p><strong>Full Session ID:</strong> <code><?php echo esc_html($session->session_id); ?></code></p>
                                            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($session->created_at)); ?></p>
                                            <p><strong>Page URL:</strong> <a href="<?php echo esc_url($session->page_url); ?>" target="_blank"><?php echo esc_html($session->page_url); ?></a></p>
                                            <?php if (!empty($session->supabase_id)): ?>
                                                <p><strong>Supabase ID:</strong> <code><?php echo esc_html($session->supabase_id); ?></code></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="anam-accordion-section">
                                            <h4>Vehicle Data <button class="anam-button anam-button-secondary anam-edit-data" data-session-id="<?php echo esc_attr($session->id); ?>" style="float: right;">Edit</button></h4>
                                            <div class="anam-data-view">
                                                <?php if ($has_data): ?>
                                                    <p><strong>Year:</strong> <?php echo !empty($parsed_data['year']) ? esc_html($parsed_data['year']) : '<em>Not captured</em>'; ?></p>
                                                    <p><strong>Make:</strong> <?php echo !empty($parsed_data['make']) ? esc_html($parsed_data['make']) : '<em>Not captured</em>'; ?></p>
                                                    <p><strong>Model:</strong> <?php echo !empty($parsed_data['model']) ? esc_html($parsed_data['model']) : '<em>Not captured</em>'; ?></p>
                                                    <p><strong>VIN:</strong> <?php echo !empty($parsed_data['vin']) ? '<code>' . esc_html($parsed_data['vin']) . '</code>' : '<em>Not captured</em>'; ?></p>
                                                <?php else: ?>
                                                    <p><em>No vehicle data was captured in this conversation.</em></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="anam-data-edit" style="display: none;">
                                                <form class="anam-edit-form" data-session-id="<?php echo esc_attr($session->id); ?>">
                                                    <p>
                                                        <label><strong>Year:</strong><br>
                                                        <input type="text" name="year" class="anam-edit-field" value="<?php echo esc_attr($parsed_data['year'] ?? ''); ?>" placeholder="e.g., 2020"></label>
                                                    </p>
                                                    <p>
                                                        <label><strong>Make:</strong><br>
                                                        <input type="text" name="make" class="anam-edit-field" value="<?php echo esc_attr($parsed_data['make'] ?? ''); ?>" placeholder="e.g., Toyota"></label>
                                                    </p>
                                                    <p>
                                                        <label><strong>Model:</strong><br>
                                                        <input type="text" name="model" class="anam-edit-field" value="<?php echo esc_attr($parsed_data['model'] ?? ''); ?>" placeholder="e.g., Camry"></label>
                                                    </p>
                                                    <p>
                                                        <label><strong>VIN:</strong><br>
                                                        <input type="text" name="vin" class="anam-edit-field" value="<?php echo esc_attr($parsed_data['vin'] ?? ''); ?>" placeholder="17-character VIN"></label>
                                                    </p>
                                                    <button type="submit" class="anam-button anam-button-primary">Save Changes</button>
                                                    <button type="button" class="anam-button anam-button-secondary anam-cancel-edit">Cancel</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <div class="anam-accordion-section">
                                            <h4>Actions</h4>
                                            <?php if ($session->review_status === 'pending'): ?>
                                                <button class="anam-button anam-button-primary anam-send-to-supabase" data-session-id="<?php echo esc_attr($session->id); ?>">Send to Supabase</button>
                                            <?php else: ?>
                                                <p style="color: #46b450;">✓ Already sent to Supabase</p>
                                            <?php endif; ?>
                                            <button class="anam-button anam-button-secondary anam-toggle-accordion" data-session-id="<?php echo esc_attr($session->id); ?>">Close</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php
                // Pagination
                $total_pages = ceil($total / $per_page);
                if ($total_pages > 1):
                ?>
                    <div class="anam-pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=anam-sessions&status=<?php echo $status_filter; ?>&paged=<?php echo $i; ?>" 
                               class="<?php echo $i === $page ? 'current' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX: Send to Supabase
     */
    public function ajax_send_to_supabase() {
        check_ajax_referer('anam_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $session_id = intval($_POST['session_id']);
        
        $handler = anam_get_transcript_handler();
        if (!$handler) {
            wp_send_json_error('Transcript handler not available');
        }
        
        $result = $handler->send_to_supabase($session_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Update parsed data
     */
    public function ajax_update_parsed_data() {
        check_ajax_referer('anam_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $session_id = intval($_POST['session_id']);
        $parsed_data = array(
            'year' => sanitize_text_field($_POST['year']),
            'make' => sanitize_text_field($_POST['make']),
            'model' => sanitize_text_field($_POST['model']),
            'vin' => sanitize_text_field($_POST['vin'])
        );
        
        $result = $wpdb->update(
            $this->table_name,
            array('parsed_data' => json_encode($parsed_data)),
            array('id' => $session_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Data updated successfully');
        } else {
            wp_send_json_error('Failed to update data');
        }
    }
    
    /**
     * AJAX: Delete session
     */
    public function ajax_delete_session() {
        check_ajax_referer('anam_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $session_id = intval($_POST['session_id']);
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $session_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success('Session deleted');
        } else {
            wp_send_json_error('Failed to delete session');
        }
    }
}

// Initialize
$anam_sessions_admin = new AnamSessionsAdmin();

// Global function for rendering sessions page (called from menu)
function anam_render_sessions_page() {
    global $anam_sessions_admin;
    if ($anam_sessions_admin) {
        $anam_sessions_admin->render_sessions_page();
    }
}
