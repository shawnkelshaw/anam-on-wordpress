jQuery(document).ready(function($) {
    
    console.log('🎯 Anam Admin - Simple Version Loading...');
    
    // ============================================================================
    // BASIC STATE MANAGEMENT - NO COMPLEX CONDITIONAL LOGIC
    // ============================================================================
    
    function checkApiVerified() {
        return $('#api-status').text().includes('✅');
    }
    
    function checkInitialCondition() {
        const apiKey = $('#anam-api-key').val();
        const isVerified = checkApiVerified();
        // Initial condition is when there's no API key AND it's not verified
        // If it's verified, we have saved data even if field appears empty
        return (!apiKey || apiKey.trim() === '') && !isVerified;
    }
    
    
    // ============================================================================
    // DISPLAY METHOD TOGGLE (Display Settings Page)
    // ============================================================================
    
    function toggleDisplayMethodSections() {
        const selectedMethod = $('input[name="anam_options[display_method]"]:checked').val();
        
        const elementIdRow = $('#anam-container-id').closest('tr');
        const positionRow = $('#anam-avatar-position').closest('tr');
        const pageSelectionRow = $('#page-selection-section').closest('tr');
        
        if (selectedMethod === 'element_id') {
            elementIdRow.show();
            positionRow.hide();
            pageSelectionRow.hide();
            
            // Set default value for Element ID if empty
            const elementIdInput = $('#anam-container-id');
            if (!elementIdInput.val() || elementIdInput.val().trim() === '') {
                elementIdInput.val('anam-stream-container');
            }
            
        } else if (selectedMethod === 'page_position') {
            elementIdRow.hide();
            positionRow.show();
            pageSelectionRow.show();
            
            // Set defaults for page position method if no saved values
            const homeCheckbox = $('input[value="homepage"]');
            const positionSelect = $('#anam-avatar-position');
            
            // Check if any page is already selected
            const anyPageSelected = $('input[name="anam_options[selected_pages][]"]:checked').length > 0;
            
            if (!anyPageSelected) {
                // Default to Home checked
                homeCheckbox.prop('checked', true);
            }
            
            // Default to Bottom Right if no position selected
            if (!positionSelect.val() || positionSelect.val() === '') {
                positionSelect.val('bottom-right');
            }
        }
    }
    
    $('input[name="anam_options[display_method]"]').on('change', toggleDisplayMethodSections);
    toggleDisplayMethodSections(); // Initialize
    
    // ============================================================================
    // SUPABASE TOGGLE (Supabase Config Page)
    // ============================================================================
    
    function toggleSupabaseFields() {
        const isEnabled = $('#supabase-enable').is(':checked');
        const supabaseFields = $('.supabase-field');
        
        if (isEnabled) {
            supabaseFields.closest('tr').show();
        } else {
            supabaseFields.closest('tr').hide();
        }
    }
    
    $('#supabase-enable').on('change', toggleSupabaseFields);
    toggleSupabaseFields(); // Initialize
    
    // ============================================================================
    // DATABASE SETTINGS VALIDATION
    // ============================================================================
    
    function validateDatabaseSettings() {
        // Only run on Database Integration page
        const supabaseUrlField = $('input[name="anam_options[supabase_url]"]');
        if (supabaseUrlField.length === 0) return; // Not on the right page
        
        const supabaseUrl = supabaseUrlField.val() ? supabaseUrlField.val().trim() : '';
        const supabaseKey = $('input[name="anam_options[supabase_key]"]').val() ? $('input[name="anam_options[supabase_key]"]').val().trim() : '';
        const supabaseTable = $('input[name="anam_options[supabase_table]"]').val() ? $('input[name="anam_options[supabase_table]"]').val().trim() : '';
        const submitButton = $('#anam-supabase-submit');
        
        if (submitButton.length === 0) return; // Submit button not found
        
        // Check if all required fields have values
        const allFieldsFilled = supabaseUrl && supabaseKey && supabaseTable;
        
        if (allFieldsFilled) {
            submitButton.prop('disabled', false).removeClass('disabled');
        } else {
            submitButton.prop('disabled', true).addClass('disabled');
        }
    }
    
    // Bind validation to input changes on Database Integration page
    $(document).on('input keyup', 'input[name="anam_options[supabase_url]"], input[name="anam_options[supabase_key]"], input[name="anam_options[supabase_table]"]', validateDatabaseSettings);
    
    // ============================================================================
    // SESSIONS LIST (Chat Transcripts Page)
    // ============================================================================
    
    function loadSessions(page = 1, perPage = 10) {
        // Only run on sessions page
        if ($('#sessions-container').length === 0) return;
        
        // Check if ANAM_CONFIG exists
        if (typeof ANAM_CONFIG === 'undefined') {
            console.error('❌ ANAM_CONFIG is not defined!');
            $('#sessions-loading').hide();
            $('#sessions-error-message').text('Configuration error: ANAM_CONFIG not loaded. Please refresh the page.');
            $('#sessions-error').show();
            return;
        }
        
        console.log('✅ ANAM_CONFIG found:', ANAM_CONFIG);
        console.log('📤 Requesting sessions - page:', page, 'perPage:', perPage);
        
        $('#sessions-loading').show();
        $('#sessions-error').hide();
        $('#sessions-container').hide();
        
        $.ajax({
            url: ANAM_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'anam_list_sessions',
                nonce: ANAM_CONFIG.nonce,
                page: page,
                perPage: perPage
            },
            success: function(response) {
                $('#sessions-loading').hide();
                
                console.log('🔍 Sessions API Response:', response);
                
                if (response.success && response.data) {
                    const data = response.data;
                    console.log('📊 Response data:', data);
                    console.log('📊 Data keys:', Object.keys(data));
                    
                    const sessions = data.data || [];
                    const meta = data.meta || {};
                    
                    console.log('📋 Sessions array:', sessions);
                    console.log('📋 Sessions count:', sessions.length);
                    console.log('📋 Meta:', meta);
                    
                    if (sessions.length === 0) {
                        $('#sessions-error-message').text('No sessions found. API returned empty data array.');
                        $('#sessions-error').show();
                        return;
                    }
                    
                    // Build sessions table
                    let html = '';
                    sessions.forEach(function(session) {
                        const createdAt = session.createdAt ? new Date(session.createdAt).toLocaleString() : 'N/A';
                        const updatedAt = session.updatedAt ? new Date(session.updatedAt).toLocaleString() : 'N/A';
                        const clientLabel = session.clientLabel || 'N/A';
                        const sessionId = session.id || 'N/A';
                        
                        html += '<tr>';
                        html += '<td><code>' + sessionId + '</code></td>';
                        html += '<td>' + createdAt + '</td>';
                        html += '<td>' + updatedAt + '</td>';
                        html += '<td>' + clientLabel + '</td>';
                        html += '<td style="text-align: center;"><button class="button button-small view-session" data-session-id="' + sessionId + '">View</button></td>';
                        html += '</tr>';
                    });
                    
                    $('#sessions-list').html(html);
                    
                    // Build pagination
                    if (meta.total > 0) {
                        let paginationHtml = '<div style="display: flex; justify-content: space-between; align-items: center;">';
                        paginationHtml += '<div>Showing ' + sessions.length + ' of ' + meta.total + ' sessions</div>';
                        paginationHtml += '<div>';
                        
                        if (meta.currentPage > 1) {
                            paginationHtml += '<button class="button sessions-page-btn" data-page="' + (meta.currentPage - 1) + '">Previous</button> ';
                        }
                        
                        paginationHtml += '<span style="margin: 0 10px;">Page ' + meta.currentPage + ' of ' + meta.lastPage + '</span>';
                        
                        if (meta.currentPage < meta.lastPage) {
                            paginationHtml += ' <button class="button sessions-page-btn" data-page="' + (meta.currentPage + 1) + '">Next</button>';
                        }
                        
                        paginationHtml += '</div></div>';
                        $('#sessions-pagination').html(paginationHtml);
                    }
                    
                    $('#sessions-container').show();
                } else {
                    $('#sessions-error-message').text(response.data || 'Failed to load sessions');
                    $('#sessions-error').show();
                }
            },
            error: function(xhr, status, error) {
                $('#sessions-loading').hide();
                $('#sessions-error-message').text('Error loading sessions: ' + error);
                $('#sessions-error').show();
            }
        });
    }
    
    // Pagination click handler
    $(document).on('click', '.sessions-page-btn', function() {
        const page = $(this).data('page');
        loadSessions(page, 10);
    });
    
    // View session button - show modal with session details
    $(document).on('click', '.view-session', function() {
        const sessionId = $(this).data('session-id');
        console.log('📋 Loading session details for:', sessionId);
        
        // Show modal
        $('#session-details-modal').show();
        
        // Reset content to loading state
        $('#session-details-content').html(
            '<div style="text-align: center; padding: 40px;">' +
            '<div class="anam-spinner" style="margin: 0 auto 20px; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
            '<p>Loading session details...</p>' +
            '</div>'
        );
        
        // Fetch session details
        $.ajax({
            url: ANAM_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'anam_get_session_details',
                nonce: ANAM_CONFIG.nonce,
                sessionId: sessionId
            },
            success: function(response) {
                console.log('✅ Session details received:', response);
                console.log('Response data:', response.data);
                console.log('Has transcript?', response.data ? response.data.has_transcript : 'no data');
                console.log('Transcript:', response.data ? response.data.transcript : 'no data');
                
                if (response.success && response.data) {
                    const data = response.data;
                    
                    if (data.has_transcript && data.transcript && data.transcript.length > 0) {
                        // Display formatted transcript
                        let html = '<h3>Session Transcript</h3>';
                        html += '<p style="color: #666; margin-bottom: 20px;">Session ID: <code>' + sessionId + '</code> | ' + data.message_count + ' messages</p>';
                        html += '<div style="max-height: 500px; overflow-y: auto;">';
                        
                        data.transcript.forEach(function(msg) {
                            const isUser = msg.type === 'user' || msg.role === 'user';
                            const bgColor = isUser ? '#e3f2fd' : '#f1f8e9';
                            const label = isUser ? '👤 User' : '🤖 Avatar';
                            const text = msg.text || msg.content || msg.message || '';
                            
                            html += '<div style="margin-bottom: 15px; padding: 12px; background: ' + bgColor + '; border-radius: 8px; border-left: 4px solid ' + (isUser ? '#2196f3' : '#8bc34a') + ';">';
                            html += '<div style="font-weight: bold; font-size: 12px; color: #666; margin-bottom: 6px;">' + label + '</div>';
                            html += '<div style="color: #333; line-height: 1.5;">' + escapeHtml(text) + '</div>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                        $('#session-details-content').html(html);
                    } else {
                        // No transcript available
                        $('#session-details-content').html(
                            '<div style="text-align: center; padding: 40px;">' +
                            '<div style="font-size: 48px; margin-bottom: 20px;">💬</div>' +
                            '<h3 style="color: #666;">No Transcript Available</h3>' +
                            '<p style="color: #999;">This session does not have a saved transcript.</p>' +
                            '<p style="color: #999; font-size: 13px;">Transcripts are captured when users interact with the avatar on your site.</p>' +
                            '</div>'
                        );
                    }
                } else {
                    $('#session-details-content').html(
                        '<div class="notice notice-error"><p>Failed to load session details: ' + 
                        (response.data || 'Unknown error') + '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading session details:', error);
                $('#session-details-content').html(
                    '<div class="notice notice-error"><p>Error loading session details: ' + error + '</p></div>'
                );
            }
        });
    });
    
    // Close modal button
    $(document).on('click', '#close-session-modal', function() {
        $('#session-details-modal').hide();
    });
    
    // Close modal when clicking outside
    $(document).on('click', '#session-details-modal', function(e) {
        if (e.target.id === 'session-details-modal') {
            $('#session-details-modal').hide();
        }
    });
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // ============================================================================
    // INITIALIZE
    // ============================================================================
    
    // Delay initialization to ensure DOM is fully loaded
    setTimeout(function() {
        console.log('🔍 Anam Admin initializing...');
        validateDatabaseSettings(); // Check database settings validation
        
        // Load sessions if on sessions page
        if ($('#sessions-container').length > 0) {
            console.log('📋 Loading sessions...');
            loadSessions();
        }
        
        console.log('✅ Anam Admin - Ready!');
    }, 100);
    
});
