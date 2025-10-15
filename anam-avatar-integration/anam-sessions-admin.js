jQuery(document).ready(function($) {
    
    // Toggle Accordion (only one open at a time)
    $('.anam-toggle-accordion').on('click', function() {
        const sessionId = $(this).data('session-id');
        const accordionRow = $('.anam-accordion-row[data-session-id="' + sessionId + '"]');
        const sessionRow = $('.anam-session-row[data-session-id="' + sessionId + '"]');
        
        // Check if this accordion is currently open
        const isOpen = accordionRow.is(':visible');
        
        // Close all accordions
        $('.anam-accordion-row').hide();
        $('.anam-session-row').removeClass('expanded');
        
        // If it wasn't open, open this one
        if (!isOpen) {
            accordionRow.show();
            sessionRow.addClass('expanded');
            
            // Update button text
            $(this).text('Close');
        } else {
            // Reset button text
            $('.anam-toggle-accordion[data-session-id="' + sessionId + '"]').text('View Details');
        }
    });
    
    // Edit Data button
    $(document).on('click', '.anam-edit-data', function() {
        const sessionId = $(this).data('session-id');
        const accordionRow = $('.anam-accordion-row[data-session-id="' + sessionId + '"]');
        
        accordionRow.find('.anam-data-view').hide();
        accordionRow.find('.anam-data-edit').show();
        $(this).hide();
    });
    
    // Cancel Edit button
    $(document).on('click', '.anam-cancel-edit', function() {
        const form = $(this).closest('.anam-data-edit');
        const section = form.closest('.anam-accordion-section');
        
        form.hide();
        section.find('.anam-data-view').show();
        section.find('.anam-edit-data').show();
    });
    
    // Edit form submission
    $(document).on('submit', '.anam-edit-form', function(e) {
        e.preventDefault();
        
        const sessionId = $(this).data('session-id');
        const formData = {
            action: 'anam_update_parsed_data',
            nonce: anamSessions.nonce,
            session_id: sessionId,
            year: $(this).find('[name="year"]').val(),
            make: $(this).find('[name="make"]').val(),
            model: $(this).find('[name="model"]').val(),
            vin: $(this).find('[name="vin"]').val()
        };
        
        $.post(anamSessions.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Data updated successfully! Refreshing page...');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Send to Supabase
    $('.anam-send-to-supabase').on('click', function() {
        const button = $(this);
        const sessionId = button.data('session-id');
        
        if (!confirm('Send this vehicle data to Supabase?')) {
            return;
        }
        
        button.prop('disabled', true).text('Sending...');
        
        $.post(anamSessions.ajax_url, {
            action: 'anam_send_to_supabase',
            nonce: anamSessions.nonce,
            session_id: sessionId
        }, function(response) {
            if (response.success) {
                alert('Successfully sent to Supabase!\nSupabase ID: ' + response.data.supabase_id);
                location.reload();
            } else {
                alert('Error: ' + response.data);
                button.prop('disabled', false).text('Send to Supabase');
            }
        }).fail(function() {
            alert('Request failed. Please check your Supabase configuration.');
            button.prop('disabled', false).text('Send to Supabase');
        });
    });
    
    // Delete session
    $('.anam-delete-session').on('click', function() {
        const button = $(this);
        const sessionId = button.data('session-id');
        
        if (!confirm('Are you sure you want to delete this session? This cannot be undone.')) {
            return;
        }
        
        button.prop('disabled', true).text('Deleting...');
        
        $.post(anamSessions.ajax_url, {
            action: 'anam_delete_session',
            nonce: anamSessions.nonce,
            session_id: sessionId
        }, function(response) {
            if (response.success) {
                button.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert('Error: ' + response.data);
                button.prop('disabled', false).text('Delete');
            }
        });
    });
    
});
