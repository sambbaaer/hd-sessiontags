<?php
/**
 * Plugin Name: SessionTags Human Design Extension
 * Plugin URI: https://humandesigns.ch
 * Description: Erweitert SessionTags um Human Design Personalisierung für automatische Website-Anpassung basierend auf dem Energietyp
 * Version: 1.1.0
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
define('STHD_VERSION', '1.0.0');
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

        // Inline CSS for calculator
        wp_add_inline_style('wp-block-library', $this->get_inline_css());

        // Inline JavaScript for calculator functionality
        wp_add_inline_script('jquery', $this->get_calculator_js());
    }

    /**
     * Shortcode: Display current HD type
     * Usage: [hd_type default="Unbekannt"]
     */
    public function shortcode_hd_type($atts) {
        $atts = shortcode_atts(array(
            'default' => 'Unbekannt'
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
            return '<div class="hd-already-calculated">✅ Human Design Typ bereits ermittelt: <strong>' . esc_html($_SESSION[$this->session_key] ?? 'Unbekannt') . '</strong></div>';
        }

        ob_start();
        if (file_exists(STHD_PLUGIN_DIR . 'templates/calculator-widget.php')) {
            include STHD_PLUGIN_DIR . 'templates/calculator-widget.php';
        } else {
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
            return '<p>Entdecke deinen Human Design Typ für personalisierte Inhalte!</p>';
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
        .hd-type-" . strtolower(str_replace(' ', '-', $type)) . " {
            --hd-primary: {$colors['primary']};
            --hd-secondary: {$colors['secondary']};
            --hd-accent: {$colors['accent']};
        }

        .hd-personalized .hd-cta-button,
        .hd-personalized a[href*='reading-buchen'] {
            background: var(--hd-primary) !important;
            border-color: var(--hd-primary) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            display: inline-block !important;
            transition: transform 0.2s ease !important;
        }

        .hd-personalized .hd-cta-button:hover,
        .hd-personalized a[href*='reading-buchen']:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
        }

        .hd-personalized .hd-highlight {
            border-left: 4px solid var(--hd-accent);
        }

        .hd-personalized .elementor-button {
            background-color: var(--hd-primary) !important;
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
            'Generator' => array(
                'primary' => '#e74c3c',
                'secondary' => '#c0392b',
                'accent' => '#f39c12'
            ),
            'ManifestingGenerator' => array(
                'primary' => '#e67e22',
                'secondary' => '#d35400',
                'accent' => '#f1c40f'
            ),
            'Projector' => array(
                'primary' => '#3498db',
                'secondary' => '#2980b9',
                'accent' => '#9b59b6'
            ),
            'Manifestor' => array(
                'primary' => '#2ecc71',
                'secondary' => '#27ae60',
                'accent' => '#1abc9c'
            ),
            'Reflector' => array(
                'primary' => '#9b59b6',
                'secondary' => '#8e44ad',
                'accent' => '#e91e63'
            )
        );

        return $colors[$type] ?? $colors['Generator'];
    }

    /**
     * Admin Menu
     */
    public function admin_menu() {
        add_options_page(
            'Human Design Settings',
            'Human Design',
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
        } else {
            echo $this->get_admin_fallback();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('hd_auto_redirect')) {
            add_option('hd_auto_redirect', false);
        }
        if (!get_option('hd_personalized_urls')) {
            add_option('hd_personalized_urls', array());
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get inline CSS for calculator
     */
    private function get_inline_css() {
        return "
        .hd-calculator {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            max-width: 500px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .hd-calculator-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .hd-calculator-header h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.5em;
        }

        .hd-calculator-minimal {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hd-calculator-minimal .hd-calculator-header h3 {
            color: white;
            font-size: 1.2em;
        }

        .hd-form-group {
            margin-bottom: 15px;
        }

        .hd-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        .hd-calculator-minimal .hd-form-group label {
            color: rgba(255,255,255,0.9);
        }

        .hd-form-group input,
        .hd-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }

        .hd-form-group input:focus,
        .hd-form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
        }

        .hd-time-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .hd-calculate-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 10px;
        }

        .hd-calculate-btn:hover {
            transform: translateY(-2px);
        }

        .hd-calculate-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .hd-result {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .hd-type-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .hd-error {
            margin-top: 20px;
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            color: #721c24;
        }

        .hd-already-calculated {
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            color: #155724;
            text-align: center;
            margin: 20px auto;
            max-width: 500px;
        }

        @media (max-width: 480px) {
            .hd-calculator {
                margin: 10px;
                padding: 15px;
            }

            .hd-time-row {
                grid-template-columns: 1fr;
            }
        }
        ";
    }

    /**
     * Get calculator JavaScript
     */
    private function get_calculator_js() {
        return "
        jQuery(document).ready(function($) {
            // Calculator form submission
            $(document).on('submit', '.hd-calculator-form', function(e) {
                e.preventDefault();

                var form = $(this);
                var resultDiv = form.siblings('.hd-result');
                var errorDiv = form.siblings('.hd-error');
                var submitBtn = form.find('.hd-calculate-btn');

                // Show loading state
                submitBtn.prop('disabled', true).html('🔄 Berechne...');
                resultDiv.hide();
                errorDiv.hide();

                // Get form data
                var birthDate = new Date(form.find('input[name=\"birth_date\"]').val());

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

                // AJAX call
                $.post(ajaxurl || '/wp-admin/admin-ajax.php', data, function(response) {
                    if (response.success) {
                        // Create result HTML
                        var resultHTML = '<div class=\"hd-type-badge\">' + response.data.type + '</div>';
                        if (response.data.strategy) {
                            resultHTML += '<p><strong>Strategie:</strong> ' + response.data.strategy + '</p>';
                        }
                        if (response.data.description) {
                            resultHTML += '<p>' + response.data.description + '</p>';
                        }
                        if (response.data.redirect_url) {
                            resultHTML += '<p><em>Die Website wird nun für dich personalisiert...</em></p>';
                        }

                        // Show result
                        if (resultDiv.find('.hd-result-content').length) {
                            resultDiv.find('.hd-result-content').html(resultHTML);
                        } else {
                            resultDiv.html('<div class=\"hd-result-content\">' + resultHTML + '</div>');
                        }
                        resultDiv.show();

                        // Add body class for immediate personalization
                        $('body').addClass('hd-type-' + response.data.type.toLowerCase().replace(' ', '-'));
                        $('body').addClass('hd-personalized');

                        // Trigger custom event
                        $(document).trigger('humanDesignCalculated', response.data);

                        // Redirect if enabled
                        if (form.data('redirect') === 'true' && response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        // Show error
                        var errorHTML = '<div class=\"hd-error-content\"><p>❌ ' + (response.data || 'Berechnung fehlgeschlagen') + '</p></div>';
                        if (errorDiv.find('.hd-error-content').length) {
                            errorDiv.find('.hd-error-content').html(errorHTML);
                        } else {
                            errorDiv.html(errorHTML);
                        }
                        errorDiv.show();
                    }
                }).fail(function() {
                    var errorHTML = '<div class=\"hd-error-content\"><p>❌ Verbindungsfehler. Bitte versuche es erneut.</p></div>';
                    errorDiv.html(errorHTML).show();
                }).always(function() {
                    // Reset button
                    submitBtn.prop('disabled', false).html('✨ Typ ermitteln');
                });
            });
        });
        ";
    }

    /**
     * Get calculator fallback HTML (if template file is missing)
     */
    private function get_calculator_fallback($atts) {
        $widget_id = 'hd-calc-' . uniqid();
        $style_class = 'hd-calculator-' . $atts['style'];

        ob_start();
        ?>
        <div id="<?php echo $widget_id; ?>" class="hd-calculator <?php echo $style_class; ?>">
            <?php if ($atts['style'] !== 'minimal'): ?>
                <div class="hd-calculator-header">
                    <h3>🔮 Entdecke deinen Human Design Typ</h3>
                    <p>In nur 30 Sekunden zu deiner personalisierten Website-Erfahrung</p>
                </div>
            <?php endif; ?>

            <form class="hd-calculator-form" data-redirect="<?php echo esc_attr($atts['redirect']); ?>">
                <?php wp_nonce_field('hd_calculation_nonce', 'hd_nonce'); ?>

                <div class="hd-form-group">
                    <label>Geburtsdatum:</label>
                    <input type="date" name="birth_date" required max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="hd-time-row">
                    <div class="hd-form-group">
                        <label>Stunde:</label>
                        <select name="birth_hour" required>
                            <option value="">--</option>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?php echo $h; ?>"><?php echo sprintf('%02d', $h); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="hd-form-group">
                        <label>Minute:</label>
                        <select name="birth_minute" required>
                            <option value="">--</option>
                            <?php for ($m = 0; $m < 60; $m += 5): ?>
                                <option value="<?php echo $m; ?>"><?php echo sprintf('%02d', $m); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <?php if ($atts['style'] === 'full'): ?>
                    <div class="hd-form-group">
                        <label>Geburtsort:</label>
                        <input type="text" name="birth_location" placeholder="z.B. Zürich, Schweiz" required>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="birth_location" value="Zurich, Switzerland">
                <?php endif; ?>

                <button type="submit" class="hd-calculate-btn">✨ Typ ermitteln</button>
            </form>

            <div class="hd-result" style="display: none;"></div>
            <div class="hd-error" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get admin page fallback HTML (if template file is missing)
     */
    private function get_admin_fallback() {
        ob_start();
        ?>
        <div class="wrap">
            <h1>Human Design Settings</h1>
            <div class="notice notice-info">
                <p><strong>Plugin erfolgreich aktiviert!</strong></p>
                <p>Verwende <code>[hd_calculator]</code> um den Human Design Calculator auf deiner Website einzubinden.</p>
                <p>Weitere Shortcodes: <code>[hd_type]</code>, <code>[hd_content type="Generator"]...[/hd_content]</code></p>
            </div>

            <?php if (!empty($_SESSION['hd_type'])): ?>
                <div class="notice notice-success">
                    <p><strong>Aktueller Typ in Session:</strong> <?php echo esc_html($_SESSION['hd_type']); ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>📋 Verwendung</h3>
                <ul>
                    <li><strong>[hd_calculator]</strong> - Vollständiger Calculator</li>
                    <li><strong>[hd_calculator style="minimal"]</strong> - Kompakter Calculator</li>
                    <li><strong>[hd_type]</strong> - Zeigt aktuellen Typ</li>
                    <li><strong>[hd_content type="Generator"]</strong>Inhalt nur für Generatoren<strong>[/hd_content]</strong></li>
                </ul>
            </div>

            <?php if (class_exists('SessionTags')): ?>
                <div class="notice notice-success">
                    <p>✅ <strong>SessionTags Integration aktiv!</strong> Human Design Daten sind verfügbar über: <code>[st k="hd_type"]</code></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p>⚠️ <strong>SessionTags Plugin nicht gefunden!</strong> Für erweiterte Features installiere das SessionTags Plugin.</p>
                </div>
            <?php endif; ?>

            <?php if (class_exists('\Elementor\Plugin')): ?>
                <div class="notice notice-success">
                    <p>✅ <strong>Elementor Integration verfügbar!</strong> Human Design Widgets und Dynamic Tags sind aktiv.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>ℹ️ <strong>Elementor nicht gefunden.</strong> Installiere Elementor für erweiterte Widget-Funktionen.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new SessionTags_HumanDesign();

// Helper functions for theme integration
function hd_is_calculated() {
    return !empty($_SESSION['hd_calculated']);
}

function hd_get_type($default = '') {
    return $_SESSION['hd_type'] ?? $default;
}

function hd_is_type($type) {
    return (hd_get_type() === $type);
}

function hd_get_content($type, $content) {
    return hd_is_type($type) ? $content : '';
}

// SessionTags integration
if (class_exists('SessionTags')) {
    add_filter('sessiontags_get_parameter', function($value, $key) {
        if ($key === 'hd_type') {
            return $_SESSION['hd_type'] ?? '';
        }
        if ($key === 'hd_strategy') {
            return $_SESSION['hd_strategy'] ?? '';
        }
        if ($key === 'hd_calculated') {
            return !empty($_SESSION['hd_calculated']) ? 'yes' : 'no';
        }
        return $value;
    }, 10, 2);
}
?>
