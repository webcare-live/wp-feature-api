<?php

namespace A8C\WpFeatureApiDemo;

class Options {
    const OPTION_NAME = 'wp_feature_api_demo_openai_key';
    const OPTION_PAGE = 'wp-feature-api-demo-settings';

    public function init() {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    public function add_options_page() {
        add_options_page(
            __('WP Feature API Demo Settings', 'wp-feature-api-demo'),
            __('Feature API Demo', 'wp-feature-api-demo'),
            'manage_options',
            self::OPTION_PAGE,
            [$this, 'render_options_page']
        );
    }

    public function register_settings() {
        register_setting(
            self::OPTION_PAGE,
            self::OPTION_NAME,
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        add_settings_section(
            'wp_feature_api_demo_main_section',
            __('API Settings', 'wp-feature-api-demo'),
            [$this, 'render_section_description'],
            self::OPTION_PAGE
        );

        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'wp-feature-api-demo'),
            [$this, 'render_api_key_field'],
            self::OPTION_PAGE,
            'wp_feature_api_demo_main_section'
        );
    }

    public function render_section_description() {
        echo '<p>' . esc_html__('Configure your OpenAI API key to enable the chat functionality.', 'wp-feature-api-demo') . '</p>';
    }

    public function render_api_key_field() {
        $value = get_option(self::OPTION_NAME);
        ?>
        <input type="password"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
        />
        <p class="description">
            <?php esc_html_e('Enter your OpenAI API key. This is required for the chat functionality to work.', 'wp-feature-api-demo'); ?>
        </p>
        <?php
    }

    public function render_options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_PAGE);
                do_settings_sections(self::OPTION_PAGE);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function display_admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $api_key = get_option(self::OPTION_NAME);
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    printf(
                        /* translators: %s: URL to the settings page */
                        esc_html__('The OpenAI API key is not set. The chat functionality will not work. Please configure it in the %s.', 'wp-feature-api-demo'),
                        '<a href="' . esc_url(admin_url('options-general.php?page=' . self::OPTION_PAGE)) . '">' . esc_html__('Feature API Demo settings', 'wp-feature-api-demo') . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    public static function get_api_key() {
        return get_option(self::OPTION_NAME);
    }
}
