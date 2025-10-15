jQuery(document).ready(function($) {
    
    // Check initial API verification status and disable fields if needed
    function checkApiVerificationStatus() {
        const isVerified = $('#api-status').text().includes('✅');
        const dependentFields = $('.anam-dependent-field');
        
        if (!isVerified) {
            dependentFields.prop('disabled', true).css('opacity', '0.5');
            dependentFields.closest('tr').find('.description').append('<br><span style="color: #dc3545; font-weight: bold;">⚠️ API key must be verified first</span>');
        } else {
            dependentFields.prop('disabled', false).css('opacity', '1');
        }
    }
    
    // Run on page load
    checkApiVerificationStatus();
    
    // Verify API Key
    $('#verify-api-key').on('click', function() {
        const button = $(this);
        const apiKey = $('#anam-api-key').val();
        const statusSpan = $('#api-status');
        
        if (!apiKey) {
            alert('Please enter an API key first.');
            return;
        }
        
        button.prop('disabled', true).text('Verifying...');
        
        $.ajax({
            url: anam_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'verify_anam_api_key',
                nonce: anam_ajax.nonce,
                api_key: apiKey
            },
            success: function(response) {
                button.prop('disabled', false).text('Verify API Key');
                
                if (response.success) {
                    statusSpan.html('✅ Verified').css('color', '#46b450');
                    
                    // Enable dependent fields
                    const dependentFields = $('.anam-dependent-field');
                    dependentFields.prop('disabled', false).css('opacity', '1');
                    dependentFields.closest('tr').find('span[style*="color: #dc3545"]').remove();
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>✅ API key verified successfully! You can now configure your avatar settings.</p></div>')
                        .insertAfter('.wrap h1').delay(5000).fadeOut();
                        
                } else {
                    statusSpan.html('❌ Not verified').css('color', '#dc3545');
                    alert('API verification failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Verify API Key');
                statusSpan.html('❌ Not verified').css('color', '#dc3545');
                alert('Verification failed: ' + error);
            }
        });
    });
    
    
    // Show/hide custom slugs field based on target pages selection
    $('select[name="anam_options[target_pages]"]').on('change', function() {
        const customSlugsField = $('input[name="anam_options[custom_slugs]"]').closest('tr');
        if ($(this).val() === 'custom') {
            customSlugsField.show();
        } else {
            customSlugsField.hide();
        }
    }).trigger('change');
    
    // Show/hide container ID field based on position selection
    $('select[name="anam_options[avatar_position]"]').on('change', function() {
        const containerField = $('input[name="anam_options[container_id]"]').closest('tr');
        if ($(this).val() === 'custom') {
            containerField.find('.description').text('Required: HTML element ID where avatar should appear');
            containerField.find('input').attr('required', true);
        } else {
            containerField.find('.description').text('HTML element ID where avatar should appear. Leave empty for fixed positioning.');
            containerField.find('input').removeAttr('required');
        }
    }).trigger('change');
    
    // Auto-generate container ID suggestion
    $('input[name="anam_options[container_id]"]').on('focus', function() {
        if (!$(this).val()) {
            $(this).val('anam-stream-container');
        }
    });
    
    // Validate UUID format for IDs
    function validateUUID(input) {
        const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
        return uuidRegex.test(input);
    }
    
    // Real-time validation for ID fields (exclude container_id as it's an HTML element ID)
    $('input[name*="_id]"]:not([name*="container_id"])').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (value && !validateUUID(value)) {
            field.css('border-color', '#dc3545');
            field.next('.description').append('<br><span style="color: #dc3545;">⚠️ Invalid UUID format</span>');
        } else {
            field.css('border-color', '');
            field.next('.description').find('span[style*="color: #dc3545"]').remove();
        }
    });
    
    // Validate HTML element ID format for container ID
    function validateElementID(input) {
        // HTML element ID should start with a letter, followed by letters, digits, hyphens, underscores, colons, and periods
        const elementIdRegex = /^[a-zA-Z][a-zA-Z0-9_:-]*$/;
        return elementIdRegex.test(input);
    }
    
    // Real-time formatting and validation for container ID field
    $('input[name*="container_id"]').on('input', function() {
        const field = $(this);
        let value = field.val();
        
        // Replace spaces with hyphens and remove special characters
        value = value
            .replace(/\s+/g, '-')  // Replace spaces with hyphens
            .replace(/[^a-zA-Z0-9_-]/g, '')  // Remove special characters except underscore and hyphen
            .toLowerCase();  // Convert to lowercase for consistency
        
        // Ensure it starts with a letter if not empty
        if (value && !/^[a-zA-Z]/.test(value)) {
            value = 'anam-' + value.replace(/^[^a-zA-Z]+/, '');
        }
        
        field.val(value);
    });
    
    // Validation on blur for container ID field
    $('input[name*="container_id"]').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (value && !validateElementID(value)) {
            field.css('border-color', '#dc3545');
            field.next('.description').append('<br><span style="color: #dc3545;">⚠️ Invalid HTML element ID format</span>');
        } else {
            field.css('border-color', '');
            field.next('.description').find('span[style*="color: #dc3545"]').remove();
        }
    });
    
    // Character counter for system prompt (informational only, no limits)
    const systemPromptField = $('textarea[name="anam_options[system_prompt]"]');
    if (systemPromptField.length) {
        const counter = $('<div class="character-counter" style="text-align: right; font-size: 12px; color: #666; margin-top: 5px;"></div>');
        systemPromptField.after(counter);
        
        function updateCounter() {
            const length = systemPromptField.val().length;
            const words = systemPromptField.val().trim().split(/\s+/).filter(word => word.length > 0).length;
            counter.text(`${length.toLocaleString()} characters, ${words.toLocaleString()} words`);
            
            // Visual feedback for very large prompts
            if (length > 10000) {
                counter.css('color', '#d63638').html(`${length.toLocaleString()} characters, ${words.toLocaleString()} words <em>(Very large prompt - consider breaking into sections)</em>`);
            } else if (length > 5000) {
                counter.css('color', '#dba617').html(`${length.toLocaleString()} characters, ${words.toLocaleString()} words <em>(Large prompt)</em>`);
            } else {
                counter.css('color', '#666');
            }
        }
        
        systemPromptField.on('input', updateCounter);
        updateCounter();
    }
    
    // LLM Dropdown and Modal functionality
    $('#anam-llm-dropdown').on('change', function() {
        const selectedValue = $(this).val();
        const customInput = $('#custom-llm-input');
        const customField = $('#custom-llm-id');
        
        if (selectedValue === 'custom') {
            customInput.show();
            customField.focus();
        } else {
            customInput.hide();
            customField.val('');
        }
    });
    
    // LLM Help Modal
    $('#llm-help-btn').on('click', function(e) {
        e.preventDefault();
        $('#llm-help-modal').show();
    });
    
    $('#llm-modal-close, #llm-modal-ok').on('click', function() {
        $('#llm-help-modal').hide();
    });
    
    // Close modal when clicking outside
    $('#llm-help-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Handle custom LLM input synchronization
    $('#custom-llm-id').on('input', function() {
        const customValue = $(this).val();
        const dropdown = $('#anam-llm-dropdown');
        
        // Update the form value for submission
        if (dropdown.val() === 'custom') {
            // Create a hidden input to store the custom value
            let hiddenInput = $('input[name="anam_options[llm_id]"][type="hidden"]');
            if (hiddenInput.length === 0) {
                hiddenInput = $('<input type="hidden" name="anam_options[llm_id]">');
                dropdown.after(hiddenInput);
            }
            hiddenInput.val(customValue);
            dropdown.removeAttr('name'); // Remove name from dropdown when using custom
        }
    });
    
    // Restore dropdown name when not using custom
    $('#anam-llm-dropdown').on('change', function() {
        if ($(this).val() !== 'custom') {
            $(this).attr('name', 'anam_options[llm_id]');
            $('input[name="anam_options[llm_id]"][type="hidden"]').remove();
        }
    });
    
    // Avatar Position and Container ID interaction
    $('#anam-avatar-position').on('change', function() {
        const selectedPosition = $(this).val();
        const containerField = $('#anam-container-id');
        
        if (selectedPosition === 'custom') {
            containerField.prop('disabled', false).css('opacity', '1');
            containerField.focus();
        } else {
            containerField.prop('disabled', true).css('opacity', '0.5');
        }
    });
    
    // Initialize container field state on page load
    function initializeContainerField() {
        const selectedPosition = $('#anam-avatar-position').val();
        const containerField = $('#anam-container-id');
        
        if (selectedPosition === 'custom') {
            containerField.prop('disabled', false).css('opacity', '1');
        } else {
            containerField.prop('disabled', true).css('opacity', '0.5');
        }
    }
    
    // Display Method functionality
    function toggleDisplayMethodSections() {
        const selectedMethod = $('input[name="anam_options[display_method]"]:checked').val();
        
        if (selectedMethod === 'element_id') {
            // Show Element ID section, hide Page Position sections
            $('#element-id-section').show();
            $('#page-position-section').hide();
            $('#page-selection-section').hide();
            
            // Validate container ID is required
            const containerField = $('#anam-container-id');
            if (!containerField.val().trim()) {
                containerField.css('border-color', '#dc3545');
            }
        } else if (selectedMethod === 'page_position') {
            // Show Page Position sections, hide Element ID section
            $('#element-id-section').hide();
            $('#page-position-section').show();
            $('#page-selection-section').show();
            
            // Clear container ID validation
            $('#anam-container-id').css('border-color', '');
        }
    }
    
    // Display Method radio button change handler
    $('input[name="anam_options[display_method]"]').on('change', toggleDisplayMethodSections);
    
    // Initialize display method sections on page load
    toggleDisplayMethodSections();
    
    // Page selection checkbox logic
    function handlePageSelectionLogic() {
        const allPagesCheckbox = $('input[value="all_pages"]');
        const allPostsCheckbox = $('input[value="all_posts"]');
        const homepageCheckbox = $('#homepage-checkbox input');
        const individualPageCheckboxes = $('.individual-page-checkbox input');
        const individualPostCheckboxes = $('.individual-post-checkbox input');
        
        // When "All Pages" is checked/unchecked
        allPagesCheckbox.on('change', function() {
            if ($(this).is(':checked')) {
                // Check homepage and all individual pages
                homepageCheckbox.prop('checked', true);
                individualPageCheckboxes.prop('checked', true);
            } else {
                // Uncheck homepage and all individual pages
                homepageCheckbox.prop('checked', false);
                individualPageCheckboxes.prop('checked', false);
            }
        });
        
        // When "All Posts" is checked/unchecked
        allPostsCheckbox.on('change', function() {
            const allPagesChecked = allPagesCheckbox.is(':checked');
            
            if ($(this).is(':checked')) {
                // Check all individual posts
                individualPostCheckboxes.prop('checked', true);
                
                // Hide homepage checkbox when only "All Posts" is selected (without "All Pages")
                if (!allPagesChecked) {
                    $('#homepage-checkbox').hide();
                    homepageCheckbox.prop('checked', false);
                }
            } else {
                // Uncheck all individual posts
                individualPostCheckboxes.prop('checked', false);
                
                // Show homepage checkbox when "All Posts" is unchecked and "All Pages" is not checked
                if (!allPagesChecked) {
                    $('#homepage-checkbox').show();
                }
            }
        });
        
        // When individual post checkboxes change, check if all are selected
        individualPostCheckboxes.on('change', function() {
            const allIndividualPostsChecked = individualPostCheckboxes.length === individualPostCheckboxes.filter(':checked').length;
            
            // If all individual posts are checked, check "All Posts"
            if (allIndividualPostsChecked && individualPostCheckboxes.length > 0) {
                allPostsCheckbox.prop('checked', true);
            } else {
                allPostsCheckbox.prop('checked', false);
            }
        });
        
        // When individual page checkboxes change, check if all are selected
        individualPageCheckboxes.on('change', function() {
            const allIndividualPagesChecked = individualPageCheckboxes.length === individualPageCheckboxes.filter(':checked').length;
            const homepageChecked = homepageCheckbox.is(':checked');
            
            // If all individual pages + homepage are checked, check "All Pages"
            if (allIndividualPagesChecked && homepageChecked) {
                allPagesCheckbox.prop('checked', true);
            } else {
                allPagesCheckbox.prop('checked', false);
            }
        });
        
        // When homepage checkbox changes
        homepageCheckbox.on('change', function() {
            const allIndividualPagesChecked = individualPageCheckboxes.length === individualPageCheckboxes.filter(':checked').length;
            
            // If homepage + all individual pages are checked, check "All Pages"
            if ($(this).is(':checked') && allIndividualPagesChecked) {
                allPagesCheckbox.prop('checked', true);
            } else {
                allPagesCheckbox.prop('checked', false);
            }
        });
        
        // Initial state check
        const allPagesChecked = allPagesCheckbox.is(':checked');
        const allPostsChecked = allPostsCheckbox.is(':checked');
        
        if (allPostsChecked && !allPagesChecked) {
            $('#homepage-checkbox').hide();
        }
        
        // Check if "All Posts" should be auto-checked based on individual selections
        if (individualPostCheckboxes.length > 0) {
            const allIndividualPostsChecked = individualPostCheckboxes.length === individualPostCheckboxes.filter(':checked').length;
            if (allIndividualPostsChecked) {
                allPostsCheckbox.prop('checked', true);
            }
        }
    }
    
    // Initialize page selection logic
    handlePageSelectionLogic();
    
    // Enhanced container ID validation for display method
    $('#anam-container-id').on('blur', function() {
        const selectedMethod = $('input[name="anam_options[display_method]"]:checked').val();
        const field = $(this);
        const value = field.val().trim();
        
        if (selectedMethod === 'element_id') {
            if (!value) {
                field.css('border-color', '#dc3545');
                field.next('.description').append('<br><span style="color: #dc3545;">⚠️ Element ID is required when using "By Element ID" method</span>');
            } else if (!validateElementID(value)) {
                field.css('border-color', '#dc3545');
                field.next('.description').append('<br><span style="color: #dc3545;">⚠️ Invalid HTML element ID format</span>');
            } else {
                field.css('border-color', '');
                field.next('.description').find('span[style*="color: #dc3545"]').remove();
            }
        }
    });
    
    // Form submission validation for display method
    $('form').on('submit', function(e) {
        const apiKey = $('input[name="anam_options[api_key]"]').val();
        const selectedMethod = $('input[name="anam_options[display_method]"]:checked').val();
        const containerID = $('#anam-container-id').val().trim();
        
        if (!apiKey) {
            alert('⚠️ Warning: No API key entered. The avatar will not function without a valid API key.');
        }
        
        if (selectedMethod === 'element_id' && !containerID) {
            alert('⚠️ Error: Element ID is required when using "By Element ID" display method.');
            e.preventDefault();
            return false;
        }
    });
    
});
