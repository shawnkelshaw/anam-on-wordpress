<?php
class Anam_AI_Admin {
    public function init() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_menu_page() {
        add_menu_page(
            __('Anam.ai Digital Assistant', 'anam-ai-digital-assistant'),
            __('Anam.ai Assistant', 'anam-ai-digital-assistant'),
            'manage_options',
            'anam-ai-digital-assistant',
            array($this, 'render_settings_page'),
            'dashicons-id'
        );
    }

    public function register_settings() {
        register_setting(
            'anam_ai_settings_group',
            ANAM_AI_DA_OPTION_NAME,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    public function enqueue_assets($hook) {
        if ('toplevel_page_anam-ai-digital-assistant' !== $hook) {
            return;
        }
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style(
            'anam-ai-da-admin',
            ANAM_AI_DA_PLUGIN_URL . 'assets/css/admin-settings.css',
            array(),
            ANAM_AI_DA_VERSION
        );
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script(
            'anam-ai-da-admin',
            ANAM_AI_DA_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            ANAM_AI_DA_VERSION,
            true
        );
        $options = get_option(ANAM_AI_DA_OPTION_NAME, array());
        $has_api_key = !empty($options['api_key']);
        wp_localize_script(
            'anam-ai-da-admin',
            'anamAiSettings',
            array(
                'hasApiKey' => $has_api_key,
                'clearConfirmTitle' => __('Clear all Anam.ai settings?', 'anam-ai-digital-assistant'),
                'clearConfirmMessage' => __('This will remove all saved Anam.ai settings. Do you want to continue?', 'anam-ai-digital-assistant'),
                'clearConfirmConfirm' => __('Clear all', 'anam-ai-digital-assistant'),
                'clearConfirmCancel' => __('Cancel', 'anam-ai-digital-assistant')
            )
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $options = get_option(ANAM_AI_DA_OPTION_NAME, array());
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $persona_id = isset($options['persona_id']) ? $options['persona_id'] : '';
        $avatar_id = isset($options['avatar_id']) ? $options['avatar_id'] : '';
        $voice_id = isset($options['voice_id']) ? $options['voice_id'] : '';
        $llm_id = isset($options['llm_id']) ? $options['llm_id'] : 'default';
        $all_populated = $api_key && $persona_id && $avatar_id && $voice_id && $llm_id;
        $dependent_disabled = $api_key ? '' : 'disabled';
        $save_disabled = $all_populated ? '' : 'disabled';
        ?>
        <div class="wrap anam-ai-da-settings">
            <h1><?php esc_html_e('Anam.ai Digital Assistant Settings', 'anam-ai-digital-assistant'); ?></h1>
            <form id="anam-ai-settings-form" action="<?php echo esc_url('options.php'); ?>" method="post" autocomplete="off">
                <?php
                settings_fields('anam_ai_settings_group');
                settings_errors('anam_ai_settings_messages');
                ?>
                <input type="hidden" id="anam-ai-clear-all-flag" name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[clear_flag]" value="0" />
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="anam-ai-api-key"><?php esc_html_e('Anam.ai API Key', 'anam-ai-digital-assistant'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="anam-ai-api-key"
                                name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[api_key]"
                                value="<?php echo esc_attr($api_key); ?>"
                                class="regular-text"
                                data-required="1"
                                autocomplete="new-password"
                                autocapitalize="none"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <p class="description">
                                <a href="https://anam.ai" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('Get your Anam.ai API key here.', 'anam-ai-digital-assistant'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="anam-ai-persona-id"><?php esc_html_e('Persona ID', 'anam-ai-digital-assistant'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="anam-ai-persona-id"
                                name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[persona_id]"
                                value="<?php echo esc_attr($persona_id); ?>"
                                class="regular-text anam-ai-dependent"
                                autocomplete="off"
                                autocapitalize="none"
                                autocorrect="off"
                                spellcheck="false"
                                <?php echo esc_attr($dependent_disabled); ?>
                                data-required="1"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="anam-ai-avatar-id"><?php esc_html_e('Avatar ID', 'anam-ai-digital-assistant'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="anam-ai-avatar-id"
                                name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[avatar_id]"
                                value="<?php echo esc_attr($avatar_id); ?>"
                                class="regular-text anam-ai-dependent"
                                <?php echo esc_attr($dependent_disabled); ?>
                                data-required="1"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="anam-ai-voice-id"><?php esc_html_e('Voice ID', 'anam-ai-digital-assistant'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="anam-ai-voice-id"
                                name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[voice_id]"
                                value="<?php echo esc_attr($voice_id); ?>"
                                class="regular-text anam-ai-dependent"
                                <?php echo esc_attr($dependent_disabled); ?>
                                data-required="1"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="anam-ai-llm-id"><?php esc_html_e('LLM ID', 'anam-ai-digital-assistant'); ?></label>
                        </th>
                        <td>
                            <select
                                id="anam-ai-llm-id"
                                name="<?php echo esc_attr(ANAM_AI_DA_OPTION_NAME); ?>[llm_id]"
                                class="anam-ai-dependent"
                                autocomplete="off"
                                <?php echo esc_attr($dependent_disabled); ?>
                                data-required="1"
                            >
                                <option value="default" <?php selected($llm_id, 'default'); ?>><?php esc_html_e('Default', 'anam-ai-digital-assistant'); ?></option>
                                <option value="0934d97d-0c3a-4f33-91b0-5e136a0ef466" <?php selected($llm_id, '0934d97d-0c3a-4f33-91b0-5e136a0ef466'); ?>><?php esc_html_e('OpenAI GPT-4 Mini model', 'anam-ai-digital-assistant'); ?></option>
                                <option value="ANAM_LLAMA_v3_3_70B_V1" <?php selected($llm_id, 'ANAM_LLAMA_v3_3_70B_V1'); ?>><?php esc_html_e('Llama 3.3 70B model', 'anam-ai-digital-assistant'); ?></option>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
                submit_button(__('Clear All', 'anam-ai-digital-assistant'), 'secondary', 'anam-ai-clear', false, array('id' => 'anam-ai-clear', 'type' => 'button', 'disabled' => 'disabled', 'class' => 'anam-ai-button-gap'));
                submit_button(__('Save Settings', 'anam-ai-digital-assistant'), 'primary', 'submit', false, array('id' => 'anam-ai-save', 'disabled' => $save_disabled));
                ?>
            </form>
            <div id="anam-ai-clear-modal" title="<?php esc_attr_e('Clear all Anam.ai settings?', 'anam-ai-digital-assistant'); ?>" style="display:none;">
                <p><?php esc_html_e('This will remove all saved Anam.ai settings. Do you want to continue?', 'anam-ai-digital-assistant'); ?></p>
            </div>
        </div>
        <?php
    }

    public function sanitize_settings($input) {
        $clear_requested = isset($input['clear_flag']) && '1' === $input['clear_flag'];
        $existing = get_option(ANAM_AI_DA_OPTION_NAME, array());

        if ($clear_requested) {
            $cleared = array(
                'api_key' => '',
                'persona_id' => '',
                'avatar_id' => '',
                'voice_id' => '',
                'llm_id' => 'default'
            );

            update_option(ANAM_AI_DA_OPTION_NAME, $cleared);

            add_settings_error(
                'anam_ai_settings_messages',
                'anam_ai_settings_cleared',
                __('All Anam avatar settings were cleared successfully.', 'anam-ai-digital-assistant'),
                'updated'
            );
            return $cleared;
        }

        $sanitized = array();
        unset($input['clear_flag']);
        $sanitized['api_key'] = isset($input['api_key']) ? trim($input['api_key']) : '';
        $sanitized['persona_id'] = isset($input['persona_id']) ? sanitize_text_field($input['persona_id']) : '';
        $sanitized['avatar_id'] = isset($input['avatar_id']) ? sanitize_text_field($input['avatar_id']) : '';
        $sanitized['voice_id'] = isset($input['voice_id']) ? sanitize_text_field($input['voice_id']) : '';
        $allowed_llm_ids = array('default', '0934d97d-0c3a-4f33-91b0-5e136a0ef466', 'ANAM_LLAMA_v3_3_70B_V1');
        $requested_llm = isset($input['llm_id']) ? $input['llm_id'] : 'default';
        $sanitized['llm_id'] = in_array($requested_llm, $allowed_llm_ids, true) ? $requested_llm : 'default';

        $field_labels = array(
            'api_key' => __('Anam.ai API Key', 'anam-ai-digital-assistant'),
            'persona_id' => __('Persona ID', 'anam-ai-digital-assistant'),
            'avatar_id' => __('Avatar ID', 'anam-ai-digital-assistant'),
            'voice_id' => __('Voice ID', 'anam-ai-digital-assistant'),
            'llm_id' => __('LLM ID', 'anam-ai-digital-assistant'),
        );

        $missing = array();
        foreach ($field_labels as $key => $label) {
            if (empty($sanitized[$key])) {
                $missing[] = $label;
            }
        }

        if (!empty($missing)) {
            $message = sprintf(
                /* translators: %s is a comma-separated list of field labels. */
                __('Unable to save settings. The following fields require values: %s.', 'anam-ai-digital-assistant'),
                implode(', ', $missing)
            );
            add_settings_error(
                'anam_ai_settings_messages',
                'anam_ai_settings_missing',
                $message,
                'error'
            );
            return is_array($existing) ? $existing : array();
        }

        add_settings_error(
            'anam_ai_settings_messages',
            'anam_ai_settings_saved',
            __('Your Anam avatar settings were saved successfully.', 'anam-ai-digital-assistant'),
            'updated'
        );

        return $sanitized;
    }
}
