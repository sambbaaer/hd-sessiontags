<?php
/**
 * Human Design Elementor Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

class HumanDesign_Elementor_Extension {

    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
    }

    public function register_category($elements_manager) {
        $elements_manager->add_category('human-design', array(
            'title' => 'Human Design',
            'icon' => 'fa fa-star',
        ));
    }

    public function register_widgets() {
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \HumanDesign_Calculator_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \HumanDesign_Type_Display_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \HumanDesign_Conditional_Content_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \HumanDesign_Personalized_CTA_Widget());
    }
}

new HumanDesign_Elementor_Extension();

/**
 * Human Design Calculator Widget für Elementor
 */
class HumanDesign_Calculator_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-calculator';
    }

    public function get_title() {
        return 'Human Design Calculator';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function _register_controls() {

        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Calculator Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'calculator_style',
            [
                'label' => 'Calculator Style',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'full' => 'Vollständig',
                    'minimal' => 'Minimal',
                ],
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => 'Titel',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '🔮 Entdecke deinen Human Design Typ',
                'condition' => [
                    'calculator_style!' => 'minimal',
                ],
            ]
        );

        $this->add_control(
            'subtitle',
            [
                'label' => 'Untertitel',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'In nur 30 Sekunden zu deiner personalisierten Website-Erfahrung',
                'condition' => [
                    'calculator_style!' => 'minimal',
                ],
            ]
        );

        $this->add_control(
            'redirect_after_calculation',
            [
                'label' => 'Nach Berechnung weiterleiten',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ja',
                'label_off' => 'Nein',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'show_if_calculated',
            [
                'label' => 'Auch nach Berechnung anzeigen',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ja',
                'label_off' => 'Nein',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => 'Primary Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#667eea',
                'selectors' => [
                    '{{WRAPPER}} .hd-calculate-btn' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .hd-type-badge' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 12,
                ],
                'selectors' => [
                    '{{WRAPPER}} .hd-calculator' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = 'hd-calc-' . $this->get_id();

        // Don't show if already calculated (unless specified)
        if ($settings['show_if_calculated'] !== 'yes' && !empty($_SESSION['hd_calculated'])) {
            echo '<div class="hd-already-calculated">✅ Human Design Typ bereits ermittelt: <strong>' . esc_html($_SESSION['hd_type'] ?? 'Unbekannt') . '</strong></div>';
            return;
        }

        ?>
        <div class="hd-calculator-elementor hd-calculator-<?php echo esc_attr($settings['calculator_style']); ?>">
            <?php if ($settings['calculator_style'] !== 'minimal'): ?>
                <div class="hd-calculator-header">
                    <?php if (!empty($settings['title'])): ?>
                        <h3><?php echo esc_html($settings['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($settings['subtitle'])): ?>
                        <p><?php echo esc_html($settings['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="hd-calculator-form" data-widget-id="<?php echo esc_attr($widget_id); ?>" data-redirect="<?php echo esc_attr($settings['redirect_after_calculation']); ?>">
                <?php wp_nonce_field('hd_calculation_nonce', 'hd_nonce'); ?>

                <div class="hd-form-grid">
                    <div class="hd-form-group">
                        <label>Geburtsdatum:</label>
                        <input type="date" name="birth_date" required max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="hd-time-row">
                        <div class="hd-form-group hd-form-half">
                            <label>Stunde:</label>
                            <select name="birth_hour" required>
                                <option value="">--</option>
                                <?php for ($h = 0; $h < 24; $h++): ?>
                                    <option value="<?php echo $h; ?>"><?php echo sprintf('%02d', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="hd-form-group hd-form-half">
                            <label>Minute:</label>
                            <select name="birth_minute" required>
                                <option value="">--</option>
                                <?php for ($m = 0; $m < 60; $m += 5): ?>
                                    <option value="<?php echo $m; ?>"><?php echo sprintf('%02d', $m); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($settings['calculator_style'] === 'full'): ?>
                        <div class="hd-form-group">
                            <label>Geburtsort:</label>
                            <input type="text" name="birth_location" placeholder="z.B. Zürich, Schweiz" required>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="birth_location" value="Zurich, Switzerland">
                    <?php endif; ?>
                </div>

                <button type="submit" class="hd-calculate-btn">
                    <span class="hd-btn-text">✨ Typ ermitteln</span>
                    <span class="hd-btn-loading" style="display: none;">
                        <div class="hd-spinner"></div> Berechne...
                    </span>
                </button>
            </form>

            <div class="hd-result" style="display: none;">
                <div class="hd-result-content"></div>
            </div>

            <div class="hd-error" style="display: none;">
                <div class="hd-error-content"></div>
            </div>
        </div>
        <?php
    }
}

/**
 * Human Design Type Display Widget
 */
class HumanDesign_Type_Display_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-type-display';
    }

    public function get_title() {
        return 'Human Design Type Display';
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Content',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_style',
            [
                'label' => 'Display Style',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'badge',
                'options' => [
                    'badge' => 'Badge',
                    'card' => 'Card',
                    'text' => 'Text Only',
                ],
            ]
        );

        $this->add_control(
            'show_strategy',
            [
                'label' => 'Strategie anzeigen',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ja',
                'label_off' => 'Nein',
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'display_style!' => 'text',
                ],
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label' => 'Beschreibung anzeigen',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ja',
                'label_off' => 'Nein',
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'display_style' => 'card',
                ],
            ]
        );

        $this->add_control(
            'fallback_text',
            [
                'label' => 'Fallback Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Typ noch nicht ermittelt',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $type = $_SESSION['hd_type'] ?? '';

        if (empty($type)) {
            echo '<div class="hd-no-type">' . esc_html($settings['fallback_text']) . '</div>';
            return;
        }

        $strategy = $_SESSION['hd_strategy'] ?? '';
        $description = $this->get_type_description($type);

        ?>
        <div class="hd-type-display hd-display-<?php echo esc_attr($settings['display_style']); ?>">
            <?php if ($settings['display_style'] === 'badge'): ?>
                <span class="hd-type-badge"><?php echo esc_html($type); ?></span>
                <?php if ($settings['show_strategy'] === 'yes' && $strategy): ?>
                    <span class="hd-strategy-text"><?php echo esc_html($strategy); ?></span>
                <?php endif; ?>

            <?php elseif ($settings['display_style'] === 'card'): ?>
                <div class="hd-type-card">
                    <h3><?php echo esc_html($type); ?></h3>
                    <?php if ($settings['show_strategy'] === 'yes' && $strategy): ?>
                        <p class="hd-strategy"><strong>Strategie:</strong> <?php echo esc_html($strategy); ?></p>
                    <?php endif; ?>
                    <?php if ($settings['show_description'] === 'yes'): ?>
                        <p class="hd-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <?php echo esc_html($type); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_type_description($type) {
        $descriptions = array(
            'Generator' => 'Du bist hier um zu arbeiten und zu erschaffen. Folge deiner Begeisterung!',
            'ManifestingGenerator' => 'Du bist ein Multitalent mit der Kraft zu manifestieren.',
            'Projector' => 'Du bist ein natürlicher Leiter. Warte auf Anerkennung.',
            'Manifestor' => 'Du bist ein Initiator. Informiere andere über deine Pläne.',
            'Reflector' => 'Du bist ein Spiegel der Gemeinschaft. Lass dir Zeit.'
        );

        return $descriptions[$type] ?? '';
    }
}

/**
 * Human Design Conditional Content Widget
 */
class HumanDesign_Conditional_Content_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-conditional-content';
    }

    public function get_title() {
        return 'Human Design Conditional Content';
    }

    public function get_icon() {
        return 'eicon-conditional-module';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'condition_section',
            [
                'label' => 'Condition',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'condition_type',
            [
                'label' => 'Condition Type',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'show_for_type',
                'options' => [
                    'show_for_type' => 'Show for specific type',
                    'hide_for_type' => 'Hide for specific type',
                    'show_for_types' => 'Show for multiple types',
                    'show_if_calculated' => 'Show only if calculated',
                    'show_if_not_calculated' => 'Show only if not calculated',
                ],
            ]
        );

        $this->add_control(
            'target_types',
            [
                'label' => 'Target Types',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'Generator' => 'Generator',
                    'ManifestingGenerator' => 'Manifesting Generator',
                    'Projector' => 'Projector',
                    'Manifestor' => 'Manifestor',
                    'Reflector' => 'Reflector',
                ],
                'condition' => [
                    'condition_type' => ['show_for_type', 'hide_for_type', 'show_for_types'],
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Content',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'content',
            [
                'label' => 'Content',
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => 'This content will be shown based on the conditions above.',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $current_type = $_SESSION['hd_type'] ?? '';
        $is_calculated = !empty($_SESSION['hd_calculated']);

        $show_content = false;

        switch ($settings['condition_type']) {
            case 'show_for_type':
                $show_content = in_array($current_type, $settings['target_types']);
                break;

            case 'hide_for_type':
                $show_content = !in_array($current_type, $settings['target_types']);
                break;

            case 'show_for_types':
                $show_content = in_array($current_type, $settings['target_types']);
                break;

            case 'show_if_calculated':
                $show_content = $is_calculated;
                break;

            case 'show_if_not_calculated':
                $show_content = !$is_calculated;
                break;
        }

        if ($show_content) {
            echo '<div class="hd-conditional-content">' . wp_kses_post($settings['content']) . '</div>';
        }
    }
}

/**
 * Human Design Personalized CTA Widget
 */
class HumanDesign_Personalized_CTA_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-personalized-cta';
    }

    public function get_title() {
        return 'Human Design Personalized CTA';
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'cta_section',
            [
                'label' => 'CTA Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Generator CTA
        $this->add_control(
            'generator_heading',
            [
                'label' => 'Generator CTA',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'generator_text',
            [
                'label' => 'Generator Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '🔥 Generator Reading buchen',
            ]
        );

        $this->add_control(
            'generator_url',
            [
                'label' => 'Generator URL',
                'type' => \Elementor\Controls_Manager::URL,
                'default' => [
                    'url' => '/reading-buchen/?type=generator',
                ],
            ]
        );

        // Projector CTA
        $this->add_control(
            'projector_heading',
            [
                'label' => 'Projector CTA',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'projector_text',
            [
                'label' => 'Projector Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '👑 Projector Reading buchen',
            ]
        );

        $this->add_control(
            'projector_url',
            [
                'label' => 'Projector URL',
                'type' => \Elementor\Controls_Manager::URL,
                'default' => [
                    'url' => '/reading-buchen/?type=projector',
                ],
            ]
        );

        // Default CTA
        $this->add_control(
            'default_heading',
            [
                'label' => 'Default CTA (kein Typ)',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'default_text',
            [
                'label' => 'Default Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '✨ Typ ermitteln und Reading buchen',
            ]
        );

        $this->add_control(
            'default_url',
            [
                'label' => 'Default URL',
                'type' => \Elementor\Controls_Manager::URL,
                'default' => [
                    'url' => '#hd-calculator',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $current_type = $_SESSION['hd_type'] ?? '';

        // Determine CTA text and URL based on type
        $cta_text = $settings['default_text'];
        $cta_url = $settings['default_url']['url'];

        switch ($current_type) {
            case 'Generator':
            case 'ManifestingGenerator':
                $cta_text = $settings['generator_text'];
                $cta_url = $settings['generator_url']['url'];
                break;

            case 'Projector':
                $cta_text = $settings['projector_text'];
                $cta_url = $settings['projector_url']['url'];
                break;

            // Add more types as needed
        }

        ?>
        <div class="hd-personalized-cta">
            <a href="<?php echo esc_url($cta_url); ?>" class="hd-cta-button hd-type-<?php echo strtolower($current_type); ?>">
                <?php echo esc_html($cta_text); ?>
            </a>
        </div>
        <?php
    }
}
?>
