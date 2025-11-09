jQuery(document).ready(function($) {
    
    console.log('🎯 Anam Admin - Simple Version Loading...');
    
    // Track current page for sessions list
    let currentPage = 1;
    
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
        
        // Store current page
        currentPage = page;
        
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
                        $('#sessions-error').html('<div class="notice notice-warning"><p>No sessions found. API returned empty data array.</p></div>').show();
                        return;
                    }
                    
                    // Build sessions table
                    let html = '';
                    sessions.forEach(function(session) {
                        const createdAt = session.createdAt ? new Date(session.createdAt).toLocaleString() : 'N/A';
                        const updatedAt = session.updatedAt ? new Date(session.updatedAt).toLocaleString() : 'N/A';
                        const clientLabel = session.clientLabel || 'N/A';
                        const sessionId = session.id || 'N/A';
                        const isParsed = session.parsed || false;
                        
                        html += '<tr>';
                        html += '<td><code>' + sessionId + '</code></td>';
                        html += '<td>' + createdAt + '</td>';
                        html += '<td>' + updatedAt + '</td>';
                        html += '<td>' + clientLabel + '</td>';
                        html += '<td style="text-align: center;"><button class="button button-small view-session" data-session-id="' + sessionId + '">Review and parse</button></td>';
                        html += '<td style="text-align: center;">' + (isParsed ? '<span style="color: #46b450; font-size: 18px;" title="Parsed">✓</span>' : '') + '</td>';
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
                    $('#sessions-error').html('<div class="notice notice-error"><p>' + (response.data || 'Failed to load sessions') + '</p></div>').show();
                }
            },
            error: function(xhr, status, error) {
                $('#sessions-loading').hide();
                $('#sessions-error').html('<div class="notice notice-error"><p>Error loading sessions: ' + error + '</p></div>').show();
            }
        });
    }
    
    // Pagination click handler
    $(document).on('click', '.sessions-page-btn', function() {
        const page = $(this).data('page');
        loadSessions(page, 10);
    });
    
    // Tab switching
    $(document).on('click', '.nav-tab', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        
        // Update tab styling - remove active class and white background from all
        $('.nav-tab').removeClass('nav-tab-active').css('background', '');
        // Add active class and white background to clicked tab
        $(this).addClass('nav-tab-active').css('background', 'white');
        
        // Show appropriate content
        $('#session-json-tab-content').hide();
        $('#transcript-tab-content').hide();
        $('#transcript-json-tab-content').hide();
        
        if (tab === 'session-json') {
            $('#session-json-tab-content').show();
            
            // Fetch session metadata if not already loaded
            if ($('#session-json-tab-content').data('loaded') !== true) {
                loadSessionMetadata();
            }
        } else if (tab === 'transcript') {
            $('#transcript-tab-content').show();
        } else if (tab === 'transcript-json') {
            $('#transcript-json-tab-content').show();
        }
    });
    
    // View session button - show modal with session details
    $(document).on('click', '.view-session', function() {
        const sessionId = $(this).data('session-id');
        console.log('📋 Loading session details for:', sessionId);
        
        // Show modal and reset to transcript tab
        $('#session-details-modal').show();
        $('.nav-tab').removeClass('nav-tab-active').css('background', '');
        $('.nav-tab[data-tab="transcript"]').addClass('nav-tab-active').css('background', 'white');
        
        // Update Session ID display above tabs
        $('#current-session-id').text(sessionId);
        
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
                session_id: sessionId
            },
            success: function(response) {
                console.log('✅ Session details received:', response);
                console.log('Response data:', response.data);
                console.log('Has transcript?', response.data ? response.data.has_transcript : 'no data');
                console.log('Transcript:', response.data ? response.data.transcript : 'no data');
                
                if (response.success && response.data) {
                    const data = response.data;
                    
                    // Store session ID for later use
                    $('#session-details-content').data('current-session-id', sessionId);
                    
                    // Build transcript tab content (formatted view)
                    let transcriptHtml = '';
                    if (data.has_transcript && data.transcript && data.transcript.length > 0) {
                        transcriptHtml += '<p style="color: #666; margin-bottom: 20px;">' + data.message_count + ' messages</p>';
                        transcriptHtml += '<div style="max-height: 500px; overflow-y: auto;">';
                        
                        data.transcript.forEach(function(msg) {
                            const isUser = msg.type === 'user' || msg.role === 'user';
                            const bgColor = isUser ? '#e3f2fd' : '#f1f8e9';
                            const label = isUser ? '👤 User' : '🤖 Avatar';
                            const text = msg.text || msg.content || msg.message || '';
                            
                            transcriptHtml += '<div style="margin-bottom: 15px; padding: 12px; background: ' + bgColor + '; border-radius: 8px; border-left: 4px solid ' + (isUser ? '#2196f3' : '#8bc34a') + ';">';
                            transcriptHtml += '<div style="font-weight: bold; font-size: 12px; color: #666; margin-bottom: 6px;">' + label + '</div>';
                            transcriptHtml += '<div style="color: #333; line-height: 1.5;">' + escapeHtml(text) + '</div>';
                            transcriptHtml += '</div>';
                        });
                        
                        transcriptHtml += '</div>';
                    } else {
                        transcriptHtml = '<div class="notice notice-error" style="margin: 20px 0;">' +
                            '<p><strong>Failed to load transcripts either because no transcript exists or the transcript has been deleted from WordPress table due to a plugin reset.</strong></p>' +
                            '</div>';
                    }
                    
                    // Build transcript JSON tab content
                    let transcriptJsonHtml = '';
                    if (data.has_transcript && data.transcript) {
                        transcriptJsonHtml += '<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 500px;">' + 
                            escapeHtml(JSON.stringify(data.transcript, null, 2)) + '</pre>';
                    } else {
                        transcriptJsonHtml += '<div class="notice notice-error" style="margin: 20px 0;">' +
                            '<p><strong>Failed to load transcripts either because no transcript exists or the transcript has been deleted from WordPress table due to a plugin reset.</strong></p>' +
                            '</div>';
                    }
                    
                    // Build session JSON tab content (placeholder - will load on demand)
                    let sessionJsonHtml = '<div style="text-align: center; padding: 40px;">' +
                        '<p style="color: #999;">Click to load session metadata from Anam API...</p>' +
                        '</div>';
                    
                    // Combine all three tabs
                    let html = '<div id="session-json-tab-content" style="display: none;" data-loaded="false">' + sessionJsonHtml + '</div>';
                    html += '<div id="transcript-tab-content">' + transcriptHtml + '</div>';
                    html += '<div id="transcript-json-tab-content" style="display: none;">' + transcriptJsonHtml + '</div>';
                    
                    // Add Parse Chat button
                    html += '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: right;">';
                    
                    if (data.parsed == 1) {
                        // Already parsed - show info message and disabled button
                        html += '<div style="background: #e7f7ff; border-left: 4px solid #00a0d2; padding: 12px; margin-bottom: 15px; text-align: left;">';
                        html += '<p style="margin: 0; color: #00a0d2;"><strong>ℹ️ This chat has already been parsed</strong></p>';
                        html += '<p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Parsed on: ' + data.parsed_at + '</p>';
                        html += '</div>';
                        html += '<button type="button" class="button" id="parse-chat-btn" disabled style="padding: 8px 20px; background: #46b450; border-color: #46b450; color: white; cursor: not-allowed;">';
                        html += '✓ Parsed</button>';
                    } else {
                        // Not parsed yet - show active button
                        html += '<button type="button" class="button button-primary" id="parse-chat-btn" style="padding: 8px 20px;">';
                        html += '🔍 Parse Chat</button>';
                    }
                    
                    html += '</div>';
                    
                    $('#session-details-content').html(html);
                    
                    // Store transcript and session data for parsing
                    $('#session-details-content').data('transcript-data', data.transcript);
                    $('#session-details-content').data('session-id', sessionId);
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
    
    // Function to load session metadata from Anam API
    function loadSessionMetadata() {
        const sessionId = $('#session-details-content').data('current-session-id');
        
        if (!sessionId) {
            $('#session-json-tab-content').html('<p style="color: #999;">Session ID not found.</p>');
            return;
        }
        
        // Show loading state
        $('#session-json-tab-content').html(
            '<div style="text-align: center; padding: 40px;">' +
            '<div class="anam-spinner" style="margin: 0 auto 20px; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
            '<p>Loading session metadata from Anam API...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: ANAM_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'anam_get_session_metadata',
                nonce: ANAM_CONFIG.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '<p style="color: #666; margin-bottom: 20px;">Session ID: <code>' + sessionId + '</code></p>';
                    html += '<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 500px;">' + 
                        escapeHtml(JSON.stringify(response.data, null, 2)) + '</pre>';
                    
                    $('#session-json-tab-content').html(html).data('loaded', true);
                    
                    // Store session metadata for parsing
                    $('#session-details-content').data('session-metadata', response.data);
                } else {
                    $('#session-json-tab-content').html(
                        '<div class="notice notice-error"><p>Failed to load session metadata: ' + 
                        (response.data || 'Unknown error') + '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $('#session-json-tab-content').html(
                    '<div class="notice notice-error"><p>Error loading session metadata: ' + error + '</p></div>'
                );
            }
        });
    }
    
    // Parse Chat button - send to Google AI Studio parser
    $(document).on('click', '#parse-chat-btn', function() {
        console.log('🔍 Parse Chat button clicked');
        
        const $button = $(this);
        const sessionId = $('#session-details-content').data('session-id');
        const sessionMetadata = $('#session-details-content').data('session-metadata');
        
        if (!sessionId) {
            alert('No session ID available.');
            return;
        }
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('⏳ Parsing...');
        
        // Send to backend which will forward to Google AI Studio
        $.ajax({
            url: ANAM_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'anam_parse_transcript',
                nonce: ANAM_CONFIG.nonce,
                session_id: sessionId,
                sessionMetadata: sessionMetadata ? JSON.stringify(sessionMetadata) : null
            },
            success: function(response) {
                console.log('✅ Parse response:', response);
                
                if (response.success) {
                    // Close the modal
                    $('#session-details-modal').hide();
                    
                    // Reload the sessions table after a brief delay to ensure DB is updated
                    setTimeout(function() {
                        loadSessions(currentPage);
                    }, 500);
                    
                    console.log('Parser response:', response.data.parser_response);
                } else {
                    alert('❌ Failed to parse transcript:\n\n' + response.data);
                    $button.prop('disabled', false).text('🔍 Parse Chat');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Parse error:', error);
                alert('❌ Error parsing transcript:\n\n' + error);
                $button.prop('disabled', false).text('🔍 Parse Chat');
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
            
            // Check if we should auto-open a session modal from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const viewSessionId = urlParams.get('view_session');
            if (viewSessionId) {
                console.log('🔗 Auto-opening session from email link:', viewSessionId);
                // Wait for sessions to load, then trigger the view
                setTimeout(function() {
                    const viewButton = $('button[data-session-id="' + viewSessionId + '"]');
                    if (viewButton.length > 0) {
                        viewButton.click();
                    } else {
                        console.warn('⚠️ Session not found on current page:', viewSessionId);
                    }
                }, 1000);
            }
        }
        
        console.log('✅ Anam Admin - Ready!');
    }, 100);
    
});
