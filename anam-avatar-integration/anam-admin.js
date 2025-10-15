jQuery(document).ready(function($) {
    
    // Function to validate if all required fields have values
    function validateRequiredFields() {
        const isVerified = $('#api-status').text().includes('✅');
        const avatarId = $('input[name="anam_options[avatar_id]"]').val().trim();
        const voiceId = $('input[name="anam_options[voice_id]"]').val().trim();
        const llmId = $('input[name="anam_options[llm_id]"], select[name="anam_options[llm_id]"]').val();
        const systemPrompt = $('textarea[name="anam_options[system_prompt]"]').val().trim();
        
        // Check if all required fields are filled
        const allFieldsFilled = isVerified && avatarId && voiceId && llmId && systemPrompt;
        
        // Enable/disable Save Settings button
        $('#anam-save-settings').prop('disabled', !allFieldsFilled);
        
        return allFieldsFilled;
    }
    
    // Unified verification function with modal
    function runVerificationWithModal() {
        const apiKey = $('#anam-api-key').val();
        
        if (!apiKey || apiKey.trim() === '') {
            alert('Please enter an API key first.');
            return;
        }
        
        // Clear any existing verification messages
        $('.wrap .notice').each(function() {
            const noticeText = $(this).text();
            if (noticeText.includes('Anam API Key') || noticeText.includes('API key verified')) {
                $(this).remove();
            }
        });
        
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
                const remainingTime = Math.max(0, 4000 - elapsed); // Ensure 4 second minimum
                
                setTimeout(function() {
                    modal.hide();
                    
                    if (response.success) {
                        // SUCCESS PATH
                        $('#api-status').html('✅ Verified').css('color', '#46b450');
                        
                        // Show success message
                        const successNotice = $('<div class="notice notice-success is-dismissible"><p>✅ Anam API Key verified</p></div>')
                            .insertAfter('.wrap h1');
                        
                        // Add dismiss button (WordPress native pattern)
                        successNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                        
                        // Initialize WordPress dismiss functionality
                        successNotice.find('.notice-dismiss').on('click', function() {
                            successNotice.fadeOut(function() {
                                $(this).remove();
                            });
                        });
                        
                        // Enable all Avatar Configuration fields
                        $('.anam-dependent-field').prop('disabled', false).css('opacity', '1');
                        $('.anam-dependent-field').closest('tr').find('span[style*="color: #dc3545"]').remove();
                        
                        // Enable Display Settings fields
                        $('#anam-avatar-position').prop('disabled', false).css('opacity', '1');
                        $('#page-selection-section input[type="checkbox"]').prop('disabled', false).css('opacity', '1');
                        
                        // Validate required fields and enable Save Settings if all filled
                        validateRequiredFields();
                        
                        // Enable Reset All button
                        $('#anam-reset-all').prop('disabled', false);
                        
                    } else {
                        // ERROR PATH
                        $('#api-status').html('❌ Not verified').css('color', '#dc3545');
                        
                        // Show error message
                        const errorMsg = response.data && response.data.message ? response.data.message : (response.data || 'Unknown error');
                        const errorNotice = $('<div class="notice notice-error is-dismissible"><p>❌ Anam API Key error: ' + errorMsg + '</p></div>')
                            .insertAfter('.wrap h1');
                        
                        // Add dismiss button (WordPress native pattern)
                        errorNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                        
                        // Initialize WordPress dismiss functionality
                        errorNotice.find('.notice-dismiss').on('click', function() {
                            errorNotice.fadeOut(function() {
                                $(this).remove();
                            });
                        });
                        
                        // Clear API key
                        $('#anam-api-key').val('');
                        
                        // Clear and disable all Avatar Configuration fields
                        $('.anam-dependent-field').val('').prop('disabled', true).css('opacity', '0.5');
                        $('textarea.anam-dependent-field').val('');
                        
                        // Disable Save Settings button
                        $('#anam-save-settings').prop('disabled', true);
                        
                        // Clear Display Settings
                        $('#page-selection-section input[type="checkbox"]').prop('checked', false).prop('disabled', true).css('opacity', '0.5');
                        $('#anam-container-id').val('').prop('disabled', true).css('opacity', '0.5');
                        $('#anam-avatar-position').prop('disabled', true).css('opacity', '0.5');
                        
                        // Default Display Method to "By Element ID"
                        $('input[name="anam_options[display_method]"][value="element_id"]').prop('checked', true);
                        toggleDisplayMethodSections();
                        
                        // Focus on API key input
                        $('#anam-api-key').focus();
                    }
                }, remainingTime);
            },
            error: function(xhr, status, error) {
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, 4000 - elapsed);
                
                setTimeout(function() {
                    modal.hide();
                    
                    // ERROR PATH
                    $('#api-status').html('❌ Not verified').css('color', '#dc3545');
                    
                    const errorNotice = $('<div class="notice notice-error is-dismissible"><p>❌ Anam API Key error: ' + error + '</p></div>')
                        .insertAfter('.wrap h1');
                    
                    // Add dismiss button (WordPress native pattern)
                    errorNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                    
                    // Initialize WordPress dismiss functionality
                    errorNotice.find('.notice-dismiss').on('click', function() {
                        errorNotice.fadeOut(function() {
                            $(this).remove();
                        });
                    });
                    
                    // Clear API key
                    $('#anam-api-key').val('');
                    
                    // Clear and disable all fields
                    $('.anam-dependent-field').val('').prop('disabled', true).css('opacity', '0.5');
                    $('textarea.anam-dependent-field').val('');
                    
                    // Disable Save Settings button
                    $('#anam-save-settings').prop('disabled', true);
                    
                    // Clear Display Settings
                    $('#page-selection-section input[type="checkbox"]').prop('checked', false).prop('disabled', true).css('opacity', '0.5');
                    $('#anam-container-id').val('').prop('disabled', true).css('opacity', '0.5');
                    $('#anam-avatar-position').prop('disabled', true).css('opacity', '0.5');
                    
                    // Default Display Method to "By Element ID"
                    $('input[name="anam_options[display_method]"][value="element_id"]').prop('checked', true);
                    toggleDisplayMethodSections();
                    
                    // Focus on API key input
                    $('#anam-api-key').focus();
                }, remainingTime);
            }
        });
    }
    
    // Auto-verification on page load
    function autoVerifyApiKey() {
        const apiKey = $('#anam-api-key').val();
        
        // If there's an API key, run verification with modal
        if (apiKey && apiKey.trim() !== '') {
            runVerificationWithModal();
        } else {
            // No API key - disable everything
            $('.anam-dependent-field').prop('disabled', true).css('opacity', '0.5');
            $('#anam-avatar-position').prop('disabled', true).css('opacity', '0.5');
            $('#page-selection-section input[type="checkbox"]').prop('disabled', true).css('opacity', '0.5');
            $('#anam-save-settings').prop('disabled', true);
        }
    }
    
    // Check initial API verification status and disable fields if needed
    function checkApiVerificationStatus() {
        const isVerified = $('#api-status').text().includes('✅');
        const dependentFields = $('.anam-dependent-field');
        
        if (!isVerified) {
            dependentFields.prop('disabled', true).css('opacity', '0.5');
            dependentFields.closest('tr').find('.description').append('<br><span style="color: #dc3545; font-weight: bold;">⚠️ API key must be verified first</span>');
            $('#anam-save-settings').prop('disabled', true);
        } else {
            dependentFields.prop('disabled', false).css('opacity', '1');
            $('#anam-save-settings').prop('disabled', false);
        }
    }
    
    // Run auto-verification on page load
    autoVerifyApiKey();
    
    // Verify API Key - use the unified modal verification
    $('#verify-api-key').on('click', function() {
        runVerificationWithModal();
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
        
        // Find the table rows containing each section
        const containerIdRow = $('#element-id-section').closest('tr');
        const avatarPositionRow = $('#page-position-section').closest('tr');
        const pageSelectionRow = $('#page-selection-section').closest('tr');
        
        if (selectedMethod === 'element_id') {
            // Show Element ID row, hide Page Position rows
            containerIdRow.show();
            avatarPositionRow.hide();
            pageSelectionRow.hide();
            
            // Clear all page selection checkboxes
            $('#page-selection-section input[type="checkbox"]').prop('checked', false);
            
            // Validate container ID is required
            const containerField = $('#anam-container-id');
            if (!containerField.val().trim()) {
                containerField.css('border-color', '#dc3545');
            }
        } else if (selectedMethod === 'page_position') {
            // Show Page Position rows, hide Element ID row
            containerIdRow.hide();
            avatarPositionRow.show();
            pageSelectionRow.show();
            
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
    
    // Reset All functionality
    function performReset() {
        // Clear any existing verification messages
        $('.wrap .notice').each(function() {
            const noticeText = $(this).text();
            if (noticeText.includes('Anam API Key') || noticeText.includes('API key verified')) {
                $(this).remove();
            }
        });
        
        // Clear API key and reset verification status
        $('#anam-api-key').val('');
        $('#api-status').html('❌ Not verified').css('color', '#dc3545');
        
        // Clear and disable all Avatar Configuration fields
        $('.anam-dependent-field').val('').prop('disabled', true).css('opacity', '0.5');
        $('textarea.anam-dependent-field').val('');
        
        // Clear Display Settings
        $('#page-selection-section input[type="checkbox"]').prop('checked', false).prop('disabled', true).css('opacity', '0.5');
        $('#anam-container-id').val('').prop('disabled', true).css('opacity', '0.5');
        $('#anam-avatar-position').prop('disabled', true).css('opacity', '0.5');
        
        // Default Display Method to "By Element ID"
        $('input[name="anam_options[display_method]"][value="element_id"]').prop('checked', true);
        toggleDisplayMethodSections();
        
        // Disable Save Settings button (user needs to verify API key first)
        $('#anam-save-settings').prop('disabled', true);
        
        // Disable Reset All button (since API is no longer verified)
        $('#anam-reset-all').prop('disabled', true);
        
        // Focus on API key input (ready for user to enter new key)
        $('#anam-api-key').focus();
    }
    
    // Reset All button click - show confirmation modal
    $('#anam-reset-all').on('click', function() {
        $('#anam-reset-modal').css('display', 'flex');
    });
    
    // Reset All - Cancel button
    $('#anam-reset-cancel').on('click', function() {
        $('#anam-reset-modal').hide();
    });
    
    // Reset All - Confirm button
    $('#anam-reset-confirm').on('click', function() {
        $('#anam-reset-modal').hide();
        performReset();
    });
    
    // Add event listeners to required fields to validate on change
    $('input[name="anam_options[avatar_id]"], input[name="anam_options[voice_id]"], input[name="anam_options[llm_id]"], select[name="anam_options[llm_id]"], textarea[name="anam_options[system_prompt]"]').on('input change', function() {
        validateRequiredFields();
    });
    
    // Run initial validation on page load
    validateRequiredFields();
    
});
