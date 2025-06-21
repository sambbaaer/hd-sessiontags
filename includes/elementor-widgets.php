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
            'title' => __('Human Design', 'sessiontags-humandesign'),
            'icon' => 'fa fa-star',
        ));
    }

    public function register_widgets($widgets_manager) {
        $widgets_manager->register(new \HumanDesign_Calculator_Widget());
        $widgets_manager->register(new \HumanDesign_Type_Display_Widget());
        $widgets_manager->register(new \HumanDesign_Conditional_Content_Widget());
        $widgets_manager->register(new \HumanDesign_Personalized_CTA_Widget());
    }
}

new HumanDesign_Elementor_Extension();

/**
 * Human Design Rechner Widget für Elementor
 */
class HumanDesign_Calculator_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-rechner';
    }

    public function get_title() {
        return __('Human Design Rechner', 'sessiontags-humandesign');
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function register_controls() {

        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Rechner-Einstellungen', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'calculator_style',
            [
                'label' => __('Rechner-Stil', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'full' => __('Vollständig', 'sessiontags-humandesign'),
                    'minimal' => __('Minimal', 'sessiontags-humandesign'),
                ],
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => __('Titel', 'sessiontags-humandesign'),
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
                'label' => __('Untertitel', 'sessiontags-humandesign'),
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
                'label' => __('Nach Berechnung weiterleiten', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'sessiontags-humandesign'),
                'label_off' => __('Nein', 'sessiontags-humandesign'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'show_if_calculated',
            [
                'label' => __('Auch nach Berechnung anzeigen', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'sessiontags-humandesign'),
                'label_off' => __('Nein', 'sessiontags-humandesign'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Stil', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => __('Hauptfarbe', 'sessiontags-humandesign'),
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
                'label' => __('Rand-Radius', 'sessiontags-humandesign'),
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

        $show_if_calculated = $settings['show_if_calculated'] === 'yes';

        if (!$show_if_calculated && !empty($_SESSION['hd_calculated'])) {
            echo '<div class="hd-already-calculated">✅ ' . __('Human Design Typ bereits ermittelt:', 'sessiontags-humandesign') . ' <strong>' . esc_html($_SESSION['hd_type'] ?? 'Unbekannt') . '</strong></div>';
            return;
        }

        // We use the shortcode to render the calculator to ensure JS/CSS are loaded correctly
        echo do_shortcode('[hd_calculator style="' . esc_attr($settings['calculator_style']) . '" redirect="' . esc_attr($settings['redirect_after_calculation']) . '" show_if_calculated="true"]');
    }
}


/**
 * Human Design Typ Anzeige Widget
 */
class HumanDesign_Type_Display_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-typ-anzeige';
    }

    public function get_title() {
        return __('Human Design Typ Anzeige', 'sessiontags-humandesign');
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Inhalt', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_style',
            [
                'label' => __('Anzeige-Stil', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'badge',
                'options' => [
                    'badge' => __('Badge', 'sessiontags-humandesign'),
                    'card' => __('Karte', 'sessiontags-humandesign'),
                    'text' => __('Nur Text', 'sessiontags-humandesign'),
                ],
            ]
        );

        $this->add_control(
            'show_strategy',
            [
                'label' => __('Strategie anzeigen', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'sessiontags-humandesign'),
                'label_off' => __('Nein', 'sessiontags-humandesign'),
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
                'label' => __('Beschreibung anzeigen', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'sessiontags-humandesign'),
                'label_off' => __('Nein', 'sessiontags-humandesign'),
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
                'label' => __('Fallback-Text', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Typ noch nicht ermittelt.', 'sessiontags-humandesign'),
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
        $color = $this->get_type_colors($type)['primary'];

        ?>
        <div class="hd-type-display hd-display-<?php echo esc_attr($settings['display_style']); ?>">
            <?php if ($settings['display_style'] === 'badge'): ?>
                <span class="hd-type-badge" style="background-color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($type); ?></span>
                <?php if ($settings['show_strategy'] === 'yes' && $strategy): ?>
                    <span class="hd-strategy-text"><?php echo esc_html($strategy); ?></span>
                <?php endif; ?>

            <?php elseif ($settings['display_style'] === 'card'): ?>
                <div class="hd-type-card" style="border-left-color: <?php echo esc_attr($color); ?>;">
                    <h3 style="color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($type); ?></h3>
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
            'Manifesting Generator' => 'Du bist ein Multitalent mit der Kraft zu manifestieren.',
            'ManifestingGenerator' => 'Du bist ein Multitalent mit der Kraft zu manifestieren.',
            'Projector' => 'Du bist ein natürlicher Leiter. Warte auf Anerkennung.',
            'Manifestor' => 'Du bist ein Initiator. Informiere andere über deine Pläne.',
            'Reflector' => 'Du bist ein Spiegel der Gemeinschaft. Lass dir Zeit.'
        );
        return $descriptions[$type] ?? '';
    }

    private function get_type_colors($type) {
        $colors = array(
            'Generator' => array( 'primary' => '#e74c3c' ),
            'Manifesting Generator' => array( 'primary' => '#e67e22' ),
            'ManifestingGenerator' => array( 'primary' => '#e67e22' ),
            'Projector' => array( 'primary' => '#3498db' ),
            'Manifestor' => array( 'primary' => '#2ecc71' ),
            'Reflector' => array( 'primary' => '#9b59b6' )
        );
        return $colors[$type] ?? $colors['Generator'];
    }
}

/**
 * Bedingte Human Design Inhalte Widget
 */
class HumanDesign_Conditional_Content_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-bedingte-inhalte';
    }

    public function get_title() {
        return __('Bedingte Human Design Inhalte', 'sessiontags-humandesign');
    }

    public function get_icon() {
        return 'eicon-code';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'condition_section',
            [
                'label' => __('Bedingung', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'condition_type',
            [
                'label' => __('Bedingungstyp', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'show_for_types',
                'options' => [
                    'show_for_types' => __('Zeige für bestimmte Typen', 'sessiontags-humandesign'),
                    'hide_for_types' => __('Verberge für bestimmte Typen', 'sessiontags-humandesign'),
                    'show_if_calculated' => __('Zeige nur, wenn Typ berechnet wurde', 'sessiontags-humandesign'),
                    'show_if_not_calculated' => __('Zeige nur, wenn Typ NICHT berechnet wurde', 'sessiontags-humandesign'),
                ],
            ]
        );

        $this->add_control(
            'target_types',
            [
                'label' => __('Ziel-Typen', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'Generator' => 'Generator',
                    'Manifesting Generator' => 'Manifesting Generator',
                    'Projector' => 'Projector',
                    'Manifestor' => 'Manifestor',
                    'Reflector' => 'Reflector',
                ],
                'condition' => [
                    'condition_type' => ['show_for_types', 'hide_for_types'],
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Inhalt', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'content',
            [
                'label' => __('Inhalt', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => __('Dieser Inhalt wird basierend auf der Bedingung angezeigt.', 'sessiontags-humandesign'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $current_type = $_SESSION['hd_type'] ?? '';
        // Unify type name for matching
        if ($current_type === 'ManifestingGenerator') {
            $current_type = 'Manifesting Generator';
        }
        $is_calculated = !empty($_SESSION['hd_calculated']);

        $show_content = false;

        switch ($settings['condition_type']) {
            case 'show_for_types':
                $show_content = !empty($current_type) && in_array($current_type, (array) $settings['target_types']);
                break;

            case 'hide_for_types':
                $show_content = empty($current_type) || !in_array($current_type, (array) $settings['target_types']);
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
 * Personalisierter Human Design CTA Widget
 */
class HumanDesign_Personalized_CTA_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'hd-personalisierter-cta';
    }

    public function get_title() {
        return __('Personalisierter HD CTA', 'sessiontags-humandesign');
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return ['human-design'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'cta_section',
            [
                'label' => __('CTA Einstellungen', 'sessiontags-humandesign'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Default CTA
        $this->add_control( 'default_heading', [ 'label' => __('Default CTA (kein Typ)', 'sessiontags-humandesign'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->add_control( 'default_text', [ 'label' => __('Default Text', 'sessiontags-humandesign'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '✨ Typ ermitteln & Reading buchen' ] );
        $this->add_control( 'default_url', [ 'label' => __('Default URL', 'sessiontags-humandesign'), 'type' => \Elementor\Controls_Manager::URL, 'default' => [ 'url' => '#hd-rechner' ] ] );

        $types = [
            'generator' => __('Generator', 'sessiontags-humandesign'),
            'manifesting_generator' => __('Manifesting Generator', 'sessiontags-humandesign'),
            'projector' => __('Projector', 'sessiontags-humandesign'),
            'manifestor' => __('Manifestor', 'sessiontags-humandesign'),
            'reflector' => __('Reflector', 'sessiontags-humandesign'),
        ];

        $default_texts = [
            'generator' => '🔥 Generator Reading buchen',
            'manifesting_generator' => '🚀 MG Reading buchen',
            'projector' => '👑 Projector Reading buchen',
            'manifestor' => '⚡️ Manifestor Reading buchen',
            'reflector' => '🌙 Reflector Reading buchen',
        ];

        foreach ($types as $key => $type_name) {
            $this->add_control( $key . '_heading', [ 'label' => $type_name . ' CTA', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
            $this->add_control( $key . '_text', [ 'label' => $type_name . ' Text', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $default_texts[$key] ] );
            $this->add_control( $key . '_url', [ 'label' => $type_name . ' URL', 'type' => \Elementor\Controls_Manager::URL, 'default' => [ 'url' => '/reading-buchen/?type=' . str_replace('_', '-', $key) ] ] );
        }

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $current_type = $_SESSION['hd_type'] ?? '';

        $cta_text = $settings['default_text'];
        $cta_url_data = $settings['default_url'];

        $type_map = [
            'Generator' => 'generator',
            'Manifesting Generator' => 'manifesting_generator',
            'ManifestingGenerator' => 'manifesting_generator',
            'Projector' => 'projector',
            'Manifestor' => 'manifestor',
            'Reflector' => 'reflector',
        ];

        $type_key = $type_map[$current_type] ?? null;

        if ($type_key && !empty($settings[$type_key . '_text'])) {
            $cta_text = $settings[$type_key . '_text'];
            $cta_url_data = $settings[$type_key . '_url'];
        }

        $this->add_render_attribute('button', 'href', esc_url($cta_url_data['url']));
        if (!empty($cta_url_data['is_external'])) {
            $this->add_render_attribute('button', 'target', '_blank');
        }
        if (!empty($cta_url_data['nofollow'])) {
            $this->add_render_attribute('button', 'rel', 'nofollow');
        }

        $this->add_render_attribute('button', 'class', 'elementor-button');
        $this->add_render_attribute('button', 'class', 'hd-cta-button');
        if($current_type) {
            $this->add_render_attribute('button', 'class', 'hd-type-' . strtolower(str_replace(' ', '-', $current_type)));
        }

        ?>
        <div class="hd-personalized-cta-widget">
            <a <?php echo $this->get_render_attribute_string('button'); ?>>
                <?php echo esc_html($cta_text); ?>
            </a>
        </div>
        <?php
    }
}
