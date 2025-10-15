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
    
    function updateFieldStates() {
        const apiKey = $('#anam-api-key').val();
        const isVerified = checkApiVerified();
        const isInitialCondition = checkInitialCondition();
        
        console.log('🔧 updateFieldStates called');
        console.log('  - API Key:', apiKey);
        console.log('  - Is Initial Condition:', isInitialCondition);
        console.log('  - Is Verified:', isVerified);
        console.log('  - Dependent fields found:', $('.anam-dependent-field').length);
        console.log('  - Verify button found:', $('#verify-api-key').length);
        console.log('  - Save button found:', $('#anam-save-settings').length);
        console.log('  - Reset button found:', $('#anam-reset-all').length);
        
        // Initial condition: No API key entered
        if (isInitialCondition) {
            console.log('  → Applying INITIAL CONDITION state');
            $('.anam-dependent-field').prop('disabled', true).css('opacity', '0.5');
            $('#verify-api-key').prop('disabled', true);
            $('#anam-save-settings').prop('disabled', true);
            $('#anam-reset-all').prop('disabled', true);
        }
        // API key entered but not verified
        else if (!isVerified) {
            console.log('  → Applying API KEY ENTERED state');
            $('.anam-dependent-field').prop('disabled', true).css('opacity', '0.5');
            $('#verify-api-key').prop('disabled', false);
            $('#anam-save-settings').prop('disabled', true);
            $('#anam-reset-all').prop('disabled', false);
        }
        // API verified
        else {
            console.log('  → Applying API VERIFIED state');
            $('.anam-dependent-field').prop('disabled', false).css('opacity', '1');
            $('#verify-api-key').prop('disabled', false);
            $('#anam-save-settings').prop('disabled', false);
            $('#anam-reset-all').prop('disabled', false);
        }
    }
    
    // ============================================================================
    // API VERIFICATION
    // ============================================================================
    
    function runVerificationWithModal() {
        const apiKey = $('#anam-api-key').val();
        
        if (!apiKey || apiKey.trim() === '') {
            alert('Please enter an API key first.');
            return;
        }
        
        const modal = $('#anam-verification-modal');
        const startTime = Date.now();
        
        // Show modal
        modal.css('display', 'flex');
        
        // Run verification
        $.ajax({
            url: anam_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'anam_verify_api',
                nonce: anam_ajax.nonce,
                api_key: apiKey
            },
            success: function(response) {
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, 4000 - elapsed);
                
                setTimeout(function() {
                    modal.hide();
                    
                    if (response.success) {
                        $('#api-status').html('✅ Verified').css('color', '#46b450');
                        
                        const successNotice = $('<div class="notice notice-success is-dismissible"><p>✅ Anam API Key verified</p></div>')
                            .insertAfter('.wrap h1');
                        
                        successNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                        successNotice.find('.notice-dismiss').on('click', function() {
                            successNotice.fadeOut(function() { $(this).remove(); });
                        });
                        
                        // Enable fields
                        updateFieldStates();
                        
                    } else {
                        $('#api-status').html('❌ Not verified').css('color', '#dc3545');
                        
                        const errorMsg = response.data && response.data.message ? response.data.message : (response.data || 'Unknown error');
                        const errorNotice = $('<div class="notice notice-error is-dismissible"><p>❌ Anam API Key error: ' + errorMsg + '</p></div>')
                            .insertAfter('.wrap h1');
                        
                        errorNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                        errorNotice.find('.notice-dismiss').on('click', function() {
                            errorNotice.fadeOut(function() { $(this).remove(); });
                        });
                        
                        // Disable fields
                        updateFieldStates();
                    }
                }, remainingTime);
            },
            error: function(xhr, status, error) {
                modal.hide();
                $('#api-status').html('❌ Not verified').css('color', '#dc3545');
                alert('Verification failed: ' + error);
                updateFieldStates();
            }
        });
    }
    
    // Verify button click
    $('#verify-api-key').on('click', runVerificationWithModal);
    
    // API key input change - update button states
    $('#anam-api-key').on('input', updateFieldStates);
    
    // ============================================================================
    // RESET ALL FUNCTIONALITY
    // ============================================================================
    
    function performReset() {
        // Clear Avatar Configuration fields
        $('#anam-api-key').val('');
        $('#api-status').html('❌ Not verified').css('color', '#dc3545');
        $('input[name="anam_options[persona_id]"]').val('');
        $('input[name="anam_options[avatar_id]"]').val('');
        $('input[name="anam_options[voice_id]"]').val('');
        $('select[name="anam_options[llm_id]"]').val('default');
        $('textarea[name="anam_options[system_prompt]"]').val('');
        
        // Update states
        updateFieldStates();
        
        // Focus on API key
        $('#anam-api-key').focus();
    }
    
    // Reset All button click
    $('#anam-reset-all').on('click', function() {
        $('#anam-reset-modal').css('display', 'flex');
    });
    
    // Reset modal - Cancel
    $('#anam-reset-cancel').on('click', function() {
        $('#anam-reset-modal').hide();
    });
    
    // Reset modal - Confirm
    $('#anam-reset-confirm').on('click', function() {
        $('#anam-reset-modal').hide();
        performReset();
    });
    
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
    // INITIALIZE
    // ============================================================================
    
    // Delay initialization to ensure DOM is fully loaded
    setTimeout(function() {
        console.log('🔍 Checking initial state...');
        console.log('API Key value:', $('#anam-api-key').val());
        console.log('API Status:', $('#api-status').text());
        updateFieldStates();
        console.log('✅ Anam Admin - Ready!');
    }, 100);
    
});
