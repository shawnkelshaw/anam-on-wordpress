jQuery(document).ready(function($) {
    
    console.log('🎯 Anam Getting Started - Initializing...');
    
    // Handle Save button click
    $('#anam-save-sdk-setting').on('click', function() {
        const button = $(this);
        const checkbox = $('#anam-advanced-sdk-toggle');
        const isChecked = checkbox.is(':checked');
        const sessionsButton = $('#anam-sessions-button');
        
        console.log('=== SAVE BUTTON CLICKED ===');
        console.log('Checkbox state:', isChecked);
        
        // Disable button during save
        button.prop('disabled', true).text('Saving...');
        
        // Save to database via AJAX
        $.ajax({
            url: anam_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'anam_toggle_advanced_sdk',
                nonce: anam_ajax.admin_nonce,
                enabled: isChecked ? 'true' : 'false'
            },
            success: function(response) {
                console.log('=== AJAX SUCCESS ===');
                console.log('Response:', response);
                
                if (response && response.success) {
                    console.log('✅ Saved! Reloading...');
                    button.text('Saved!');
                    // Reload to update menu
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    console.error('❌ Save failed');
                    alert('Failed to save: ' + (response.data || 'Unknown error'));
                    button.prop('disabled', false).text('Save Setting');
                }
            },
            error: function(xhr, status, error) {
                console.error('=== AJAX ERROR ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                alert('AJAX Error: ' + error);
                button.prop('disabled', false).text('Save Setting');
            }
        });
    });
    
    console.log('✅ Anam Getting Started - Ready!');
    
});
