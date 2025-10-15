(function ($) {
    function updateSaveButton() {
        var form = $('#anam-ai-settings-form');
        var requiredFields = form.find('[data-required="1"]');
        var allFilled = true;
        requiredFields.each(function () {
            var value = $(this).val();
            if (!value) {
                allFilled = false;
                return false;
            }
        });
        $('#anam-ai-save').prop('disabled', !allFilled);
    }

    $(document).ready(function () {
        var dependentFields = $('.anam-ai-dependent');
        var apiField = $('#anam-ai-api-key');
        var hasStoredApi = !!anamAiSettings.hasApiKey;
        var clearButton = $('#anam-ai-clear');
        var clearFlag = $('#anam-ai-clear-all-flag');
        var form = $('#anam-ai-settings-form');
        var clearDialog = $('#anam-ai-clear-modal');

        function disableDependents() {
            dependentFields.prop('disabled', true);
        }

        function enableDependents() {
            dependentFields.prop('disabled', false);
        }

        function updateClearButton() {
            var anyFilled = false;
            form.find('[data-required="1"]').each(function () {
                if ($(this).val()) {
                    anyFilled = true;
                    return false;
                }
            });
            clearButton.prop('disabled', !anyFilled);
        }

        if (hasStoredApi) {
            enableDependents();
        } else {
            disableDependents();
            apiField.trigger('focus');
        }

        if (clearFlag.length) {
            clearFlag.val('0');
        }

        if (clearDialog.length) {
            clearDialog.dialog({
                autoOpen: false,
                modal: true,
                resizable: false,
                closeOnEscape: true,
                buttons: [
                    {
                        text: anamAiSettings.clearConfirmCancel,
                        class: 'button-secondary',
                        click: function () {
                            clearFlag.val('0');
                            $(this).dialog('close');
                        }
                    },
                    {
                        text: anamAiSettings.clearConfirmConfirm,
                        class: 'button-primary',
                        click: function () {
                            clearFlag.val('1');
                            form.find('input[type="text"], input[type="password"]').val('');
                            form.find('select').each(function () {
                                this.selectedIndex = 0;
                            });
                            disableDependents();
                            clearButton.prop('disabled', true);
                            updateSaveButton();
                            updateClearButton();
                            $(this).dialog('close');
                            apiField.focus();
                            form.trigger('submit');
                            if (form.length && form[0]) {
                                form[0].submit();
                            }
                        }
                    }
                ]
            });
        }

        updateSaveButton();
        updateClearButton();

        apiField.on('blur', function () {
            var value = $.trim($(this).val());
            if (value) {
                enableDependents();
                clearButton.prop('disabled', false);
            } else {
                disableDependents();
                clearButton.prop('disabled', true);
            }
            updateSaveButton();
            updateClearButton();
        });

        apiField.on('input', function () {
            updateSaveButton();
            updateClearButton();
        });

        $('#anam-ai-settings-form').on('input change', '[data-required="1"]', function () {
            updateSaveButton();
            updateClearButton();
        });

        form.on('submit', function () {
            if (clearFlag.val() !== '1') {
                clearFlag.val('0');
            }
        });

        clearButton.on('click', function (event) {
            event.preventDefault();
            if (clearButton.prop('disabled')) {
                return;
            }

            if (!clearDialog.length) {
                return;
            }

            clearDialog.dialog('open');
        });
    });
})(jQuery);
