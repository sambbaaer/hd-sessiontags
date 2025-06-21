<?php
/**
 * Plugin Name: SessionTags Human Design Extension
 * Plugin URI: https://humandesigns.ch
 * Description: Erweitert SessionTags um Human Design Personalisierung für automatische Website-Anpassung basierend auf dem Energietyp
 * Version: 1.2.0
 * Author: Samuel Baer
 * Author URI: https://samuelbaer.ch
 * Text Domain: sessiontags-humandesign
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('STHD_VERSION', '1.2.0');
define('STHD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STHD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Always include the core engine
require_once STHD_PLUGIN_DIR . 'includes/humandesign-engine.php';

// Check if Elementor is active and loaded properly before loading widgets
function sthd_load_elementor_integration() {
    // Check if Elementor is installed and active
    if (!class_exists('\Elementor\Plugin')) {
        return;
    }

    // Wait for Elementor to be fully loaded
    if (!did_action('elementor/loaded')) {
        return;
    }

    // Check if required Elementor classes exist
    if (!class_exists('\Elementor\Widget_Base') || !class_exists('\Elementor\Core\DynamicTags\Tag')) {
        return;
    }

    // Now it's safe to load our Elementor extensions
    require_once STHD_PLUGIN_DIR . 'includes/elementor-widgets.php';
    require_once STHD_PLUGIN_DIR . 'includes/dynamic-tags.php';
}

// Hook into elementor/loaded action instead of plugins_loaded
add_action('elementor/loaded', 'sthd_load_elementor_integration');

// Fallback: Also try on init with a higher priority
add_action('init', function() {
    if (class_exists('\Elementor\Plugin') && did_action('elementor/loaded')) {
        sthd_load_elementor_integration();
    }
}, 20);

/**
 * Main SessionTags Human Design Class
 */
class SessionTags_HumanDesign {

    private $session_key = 'hd_type';
    private $calculated_key = 'hd_calculated';
    private $version = STHD_VERSION;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Shortcodes
        add_shortcode('hd_type', array($this, 'shortcode_hd_type'));
        add_shortcode('hd_content', array($this, 'shortcode_hd_content'));
        add_shortcode('hd_calculator', array($this, 'shortcode_hd_calculator'));
        add_shortcode('hd_personalized', array($this, 'shortcode_hd_personalized'));

        // Admin
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));

        // Auto-personalization hooks
        add_filter('body_class', array($this, 'add_body_class'));
        add_action('wp_head', array($this, 'add_custom_css'));

        // Redirect handling
        add_action('template_redirect', array($this, 'maybe_redirect_to_personalized_page'));

        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        if (!session_id()) {
            session_start();
        }

        // Load text domain for translations
        load_plugin_textdomain('sessiontags-humandesign', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');

        // Inline CSS for calculator - can be moved to a separate file if it grows
        wp_add_inline_style('wp-block-library', $this->get_inline_css());

        // Enqueue a dummy script to attach our inline JS to
        wp_enqueue_script('hd-calculator-script', STHD_PLUGIN_URL . 'assets/js/dummy.js', ['jquery'], $this->version, true);

        // Pass data to the script, including the correct AJAX URL
        wp_localize_script('hd-calculator-script', 'hd_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);

        // Add the main calculator JS inline
        wp_add_inline_script('hd-calculator-script', $this->get_calculator_js());
    }

    /**
     * Shortcode: Display current HD type
     * Usage: [hd_type default="Unbekannt"]
     */
    public function shortcode_hd_type($atts) {
        $atts = shortcode_atts(array(
            'default' => __('Unbekannt', 'sessiontags-humandesign')
        ), $atts);

        $type = $_SESSION[$this->session_key] ?? $atts['default'];
        return esc_html($type);
    }

    /**
     * Shortcode: Conditional content based on HD type
     * Usage: [hd_content type="Generator"]Content for Generators[/hd_content]
     */
    public function shortcode_hd_content($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => '',
            'types' => '', // comma-separated list
            'exclude' => ''
        ), $atts);

        $current_type = $_SESSION[$this->session_key] ?? '';

        if (empty($current_type)) {
            return '';
        }

        $show_content = false;

        if (!empty($atts['type'])) {
            $show_content = ($current_type === $atts['type']);
        } elseif (!empty($atts['types'])) {
            $types = array_map('trim', explode(',', $atts['types']));
            $show_content = in_array($current_type, $types);
        } else {
            $show_content = true; // Show for any type if no specific type is set
        }

        if (!empty($atts['exclude'])) {
            $excluded = array_map('trim', explode(',', $atts['exclude']));
            if (in_array($current_type, $excluded)) {
                $show_content = false;
            }
        }

        return $show_content ? do_shortcode($content) : '';
    }

    /**
     * Shortcode: Human Design Calculator Widget
     * Usage: [hd_calculator style="full" redirect="true"]
     */
    public function shortcode_hd_calculator($atts) {
        $atts = shortcode_atts(array(
            'style' => 'full',
            'redirect' => 'true',
            'show_if_calculated' => 'false'
        ), $atts);

        // Don't show if already calculated (unless specified)
        if ($atts['show_if_calculated'] === 'false' && !empty($_SESSION[$this->calculated_key])) {
            return '<div class="hd-already-calculated">✅ ' . __('Human Design Typ bereits ermittelt:', 'sessiontags-humandesign') . ' <strong>' . esc_html($_SESSION[$this->session_key] ?? 'Unbekannt') . '</strong></div>';
        }

        ob_start();
        $template_path = STHD_PLUGIN_DIR . 'templates/calculator-widget.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // This fallback is useful if the template file is ever deleted
            echo $this->get_calculator_fallback($atts);
        }
        return ob_get_clean();
    }

    /**
     * Shortcode: Personalized content with fallback
     * Usage: [hd_personalized]Content for calculated users[/hd_personalized]
     */
    public function shortcode_hd_personalized($atts, $content = '') {
        $atts = shortcode_atts(array(
            'show_calculator' => 'true'
        ), $atts);

        $current_type = $_SESSION[$this->session_key] ?? '';

        if (empty($current_type)) {
            if ($atts['show_calculator'] === 'true') {
                return $this->shortcode_hd_calculator(array('style' => 'minimal'));
            }
            return '<p>' . __('Entdecke deinen Human Design Typ für personalisierte Inhalte!', 'sessiontags-humandesign') . '</p>';
        }

        return do_shortcode($content);
    }

    /**
     * Add body class based on HD type
     */
    public function add_body_class($classes) {
        $type = $_SESSION[$this->session_key] ?? '';
        if (!empty($type)) {
            $classes[] = 'hd-type-' . strtolower(str_replace(' ', '-', $type));
            $classes[] = 'hd-personalized';
        }
        return $classes;
    }

    /**
     * Add custom CSS for personalization
     */
    public function add_custom_css() {
        $type = $_SESSION[$this->session_key] ?? '';
        if (empty($type)) return;

        $colors = $this->get_type_colors($type);

        echo "<style id='hd-personalization'>
        :root {
            --hd-primary-dynamic: {$colors['primary']};
            --hd-secondary-dynamic: {$colors['secondary']};
            --hd-accent-dynamic: {$colors['accent']};
        }

        .hd-type-" . strtolower(str_replace(' ', '-', $type)) . " {
            --hd-primary: {$colors['primary']};
            --hd-secondary: {$colors['secondary']};
            --hd-accent: {$colors['accent']};
        }

        .hd-personalized .hd-cta-button,
        .hd-personalized a[href*='reading-buchen'],
        .elementor-element.hd-personalized .elementor-button {
            background: var(--hd-primary-dynamic) !important;
            border-color: var(--hd-primary-dynamic) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            display: inline-block !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        }

        .hd-personalized .hd-cta-button:hover,
        .hd-personalized a[href*='reading-buchen']:hover,
        .elementor-element.hd-personalized .elementor-button:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
        }

        .hd-personalized .hd-highlight {
            border-left: 4px solid var(--hd-accent-dynamic);
        }
        </style>";
    }

    /**
     * Maybe redirect to personalized page
     */
    public function maybe_redirect_to_personalized_page() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;

        $type = $_SESSION[$this->session_key] ?? '';
        $redirect_enabled = get_option('hd_auto_redirect', false);

        if (!empty($type) && $redirect_enabled && !isset($_GET['hd_no_redirect'])) {
            $personalized_url = $this->get_personalized_url($type);
            if ($personalized_url && $personalized_url !== $_SERVER['REQUEST_URI']) {
                wp_redirect($personalized_url);
                exit;
            }
        }
    }

    /**
     * Get personalized URL for type
     */
    private function get_personalized_url($type) {
        $urls = get_option('hd_personalized_urls', array());
        $type_key = strtolower(str_replace(' ', '', $type));
        return $urls[$type_key] ?? '';
    }

    /**
     * Get type colors for personalization
     */
    private function get_type_colors($type) {
        $colors = array(
            'Generator' => array( 'primary' => '#e74c3c', 'secondary' => '#c0392b', 'accent' => '#f39c12' ),
            'ManifestingGenerator' => array( 'primary' => '#e67e22', 'secondary' => '#d35400', 'accent' => '#f1c40f' ),
            'Manifesting Generator' => array( 'primary' => '#e67e22', 'secondary' => '#d35400', 'accent' => '#f1c40f' ),
            'Projector' => array( 'primary' => '#3498db', 'secondary' => '#2980b9', 'accent' => '#9b59b6' ),
            'Manifestor' => array( 'primary' => '#2ecc71', 'secondary' => '#27ae60', 'accent' => '#1abc9c' ),
            'Reflector' => array( 'primary' => '#9b59b6', 'secondary' => '#8e44ad', 'accent' => '#e91e63' )
        );
        return $colors[$type] ?? $colors['Generator'];
    }

    /**
     * Admin Menu
     */
    public function admin_menu() {
        add_options_page(
            __('Human Design Einstellungen', 'sessiontags-humandesign'),
            __('Human Design', 'sessiontags-humandesign'),
            'manage_options',
            'human-design-settings',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('hd_settings', 'hd_auto_redirect');
        register_setting('hd_settings', 'hd_personalized_urls');
    }

    public function admin_page() {
        if (file_exists(STHD_PLUGIN_DIR . 'templates/admin-page.php')) {
            include STHD_PLUGIN_DIR . 'templates/admin-page.php';
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        if (!get_option('hd_auto_redirect')) {
            add_option('hd_auto_redirect', false);
        }
        if (!get_option('hd_personalized_urls')) {
            add_option('hd_personalized_urls', array());
        }
        // Create dummy JS file for script localization
        $assets_dir = STHD_PLUGIN_DIR . 'assets/js/';
        if (!is_dir($assets_dir)) {
            mkdir($assets_dir, 0755, true);
        }
        if (!file_exists($assets_dir . 'dummy.js')) {
            file_put_contents($assets_dir . 'dummy.js', '// This file is required for wp_localize_script');
        }
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Get inline CSS for calculator
     */
    private function get_inline_css() {
        return "
        /* This CSS is now in templates/calculator-widget.php to keep it component-specific */
        ";
    }

    /**
     * Get calculator JavaScript
     */
    private function get_calculator_js() {
        return "
        jQuery(document).ready(function($) {
            $(document).on('submit', '.hd-calculator-form', function(e) {
                e.preventDefault();

                var form = $(this);
                var container = form.closest('.hd-calculator');
                var resultDiv = container.find('.hd-result');
                var errorDiv = container.find('.hd-error');
                var submitBtn = form.find('.hd-calculate-btn');
                var btnText = submitBtn.find('.hd-btn-text');
                var btnLoading = submitBtn.find('.hd-btn-loading');

                // Show loading state
                submitBtn.prop('disabled', true);
                btnText.hide();
                btnLoading.show();
                resultDiv.hide();
                errorDiv.hide();

                // Get form data
                var birthDateVal = form.find('input[name=\"birth_date\"]').val();
                if (!birthDateVal) {
                    // Handle error, e.g., show message in errorDiv
                    errorDiv.find('.hd-error-message').text('Bitte gib ein gültiges Geburtsdatum ein.');
                    errorDiv.show();
                    // Reset button
                    submitBtn.prop('disabled', false);
                    btnLoading.hide();
                    btnText.show();
                    return;
                }
                var birthDate = new Date(birthDateVal);

                var data = {
                    action: 'calculate_human_design',
                    nonce: form.find('input[name=\"hd_nonce\"]').val(),
                    year: birthDate.getFullYear(),
                    month: birthDate.getMonth() + 1,
                    day: birthDate.getDate(),
                    hour: parseInt(form.find('select[name=\"birth_hour\"]').val()),
                    minute: parseInt(form.find('select[name=\"birth_minute\"]').val()),
                    location: form.find('input[name=\"birth_location\"]').val() || 'Zurich, Switzerland'
                };

                // AJAX call using the localized URL
                $.post(hd_ajax_object.ajax_url, data, function(response) {
                    if (response.success) {
                        // Create result HTML
                        var resultHTML = '<div class=\"hd-type-badge\" style=\"background:' + response.data.color + '\">' + response.data.type + '</div>';
                        if (response.data.strategy) {
                            resultHTML += '<p><strong>Deine Strategie:</strong> ' + response.data.strategy + '</p>';
                        }
                        if (response.data.description) {
                            resultHTML += '<p>' + response.data.description + '</p>';
                        }

                        // Show result and hide form
                        resultDiv.find('.hd-result-content').html(resultHTML);
                        resultDiv.show();
                        form.hide();

                        // Add body class for immediate personalization
                        $('body').addClass('hd-type-' + response.data.type.toLowerCase().replace(/ /g, '-')).addClass('hd-personalized');

                        // Update dynamic CSS variable
                        document.documentElement.style.setProperty('--hd-primary-dynamic', response.data.color);

                        // Trigger custom event
                        $(document).trigger('humanDesignCalculated', response.data);

                        // Redirect if enabled
                        var shouldRedirect = form.data('redirect') === 'yes' || form.data('redirect') === true;
                        if (shouldRedirect && response.data.redirect_url) {
                           resultDiv.find('.hd-result-content').append('<p><em>Du wirst in Kürze weitergeleitet...</em></p>');
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2500);
                        }
                    } else {
                        // Show error
                        errorDiv.find('.hd-error-message').text(response.data || 'Berechnung fehlgeschlagen. Bitte prüfe deine Eingaben.');
                        errorDiv.show();
                    }
                }).fail(function() {
                    errorDiv.find('.hd-error-message').text('Ein Verbindungsfehler ist aufgetreten. Bitte versuche es später erneut.');
                    errorDiv.show();
                }).always(function() {
                    // Reset button state
                    submitBtn.prop('disabled', false);
                    btnLoading.hide();
                    btnText.show();
                });
            });

            // Handle retry button
            $(document).on('click', '.hd-retry-btn', function() {
                var container = $(this).closest('.hd-calculator');
                container.find('.hd-error').hide();
                container.find('.hd-calculator-form').show();
            });
        });
        ";
    }

    // Fallback function in case template is missing
    private function get_calculator_fallback($atts) { return 'Calculator template file is missing.'; }
}

// Initialize the plugin
new SessionTags_HumanDesign();

// Helper functions for theme integration
function hd_is_calculated() { return !empty($_SESSION['hd_calculated']); }
function hd_get_type($default = '') { return $_SESSION['hd_type'] ?? $default; }
function hd_is_type($type) { return (hd_get_type() === $type); }

// SessionTags integration
if (class_exists('SessionTags')) {
    add_filter('sessiontags_get_parameter', function($value, $key) {
        if ($key === 'hd_type') { return $_SESSION['hd_type'] ?? ''; }
        if ($key === 'hd_strategy') { return $_SESSION['hd_strategy'] ?? ''; }
        if ($key === 'hd_calculated') { return !empty($_SESSION['hd_calculated']) ? 'yes' : 'no'; }
        return $value;
    }, 10, 2);
}
?>
