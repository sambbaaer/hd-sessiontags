<?php
/**
 * Human Design Elementor Dynamic Tags
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Human Design Typ Dynamic Tag
 */
class HumanDesign_Type_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-typ';
    }

    public function get_title() {
        return __('Human Design Typ', 'sessiontags-humandesign');
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => __('Fallback-Text', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Typ unbekannt', 'sessiontags-humandesign'),
            ]
        );

        $this->add_control(
            'show_calculator_link',
            [
                'label' => __('Link zum Rechner anzeigen, falls Typ fehlt', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'sessiontags-humandesign'),
                'label_off' => __('Nein', 'sessiontags-humandesign'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        $type = $_SESSION['hd_type'] ?? '';

        if (empty($type)) {
            if ($settings['show_calculator_link'] === 'yes') {
                echo '<a href="#hd-rechner" class="hd-calculate-link">' . esc_html($settings['fallback']) . ' ✨</a>';
            } else {
                echo esc_html($settings['fallback']);
            }
        } else {
            echo esc_html($type);
        }
    }
}

/**
 * Human Design Strategie Dynamic Tag
 */
class HumanDesign_Strategy_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-strategie';
    }

    public function get_title() {
        return __('Human Design Strategie', 'sessiontags-humandesign');
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => __('Fallback-Text', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Strategie unbekannt', 'sessiontags-humandesign'),
            ]
        );

        $this->add_control(
            'prefix',
            [
                'label' => __('Präfix', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Deine Strategie: ', 'sessiontags-humandesign'),
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        $strategy = $_SESSION['hd_strategy'] ?? '';

        if (empty($strategy)) {
            echo esc_html($settings['fallback']);
        } else {
            echo esc_html($settings['prefix']) . esc_html($strategy);
        }
    }
}

/**
 * Human Design Beschreibung Dynamic Tag
 */
class HumanDesign_Description_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-beschreibung';
    }

    public function get_title() {
        return __('Human Design Beschreibung', 'sessiontags-humandesign');
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => __('Fallback-Text', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Entdecke deinen Human Design Typ für eine personalisierte Beschreibung.', 'sessiontags-humandesign'),
            ]
        );

        $this->add_control(
            'length',
            [
                'label' => __('Textlänge', 'sessiontags-humandesign'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'short' => __('Kurz', 'sessiontags-humandesign'),
                    'full' => __('Vollständig', 'sessiontags-humandesign'),
                ],
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        $type = $_SESSION['hd_type'] ?? '';

        if (empty($type)) {
            echo esc_html($settings['fallback']);
            return;
        }

        $descriptions = [
            'Generator' => [
                'short' => 'Du bist hier, um zu arbeiten, zu erschaffen und auf das Leben zu reagieren.',
                'full' => 'Als Generator bist du hier, um zu arbeiten und zu erschaffen. Deine Superkraft ist deine Sakralenergie. Folge deiner Begeisterung und reagiere auf die Impulse, die das Leben dir gibt, um Erfüllung zu finden.'
            ],
            'Manifesting Generator' => [
                'short' => 'Du bist ein Multitalent mit Manifestationskraft und Effizienz.',
                'full' => 'Als Manifesting Generator bist du ein energiegeladenes Multitalent mit der Kraft, zu manifestieren und zu generieren. Du kannst viele Dinge gleichzeitig tun und bist ein Meister der Effizienz. Informiere andere, bevor du handelst.'
            ],
            'ManifestingGenerator' => [
                'short' => 'Du bist ein Multitalent mit Manifestationskraft und Effizienz.',
                'full' => 'Als Manifesting Generator bist du ein energiegeladenes Multitalent mit der Kraft, zu manifestieren und zu generieren. Du kannst viele Dinge gleichzeitig tun und bist ein Meister der Effizienz. Informiere andere, bevor du handelst.'
            ],
            'Projector' => [
                'short' => 'Du bist ein natürlicher Leiter, der auf Anerkennung wartet.',
                'full' => 'Als Projektor bist du ein natürlicher Leiter und Koordinator. Deine Gabe ist es, andere zu sehen und zu führen. Warte auf Anerkennung und formelle Einladungen, um deine einzigartigen Gaben erfolgreich in die Welt zu bringen.'
            ],
            'Manifestor' => [
                'short' => 'Du bist ein unabhängiger Initiator, der Neues beginnt.',
                'full' => 'Als Manifestor bist du ein reiner Initiator mit der Kraft, aus dem Nichts Neues zu beginnen. Deine unabhängige Natur ist deine Stärke. Um Widerstand zu vermeiden, informiere andere über deine Pläne, bevor du handelst.'
            ],
            'Reflector' => [
                'short' => 'Du bist ein seltener Spiegel der Gemeinschaft und brauchst Zeit.',
                'full' => 'Als seltener Reflektor bist du ein Spiegel der Gemeinschaft und ihrer Gesundheit. Du bist hier, um das Potenzial anderer zu reflektieren. Nimm dir für wichtige Entscheidungen einen vollen Mondzyklus Zeit, um Klarheit zu finden.'
            ]
        ];

        $length = $settings['length'];
        $description = $descriptions[$type][$length] ?? $settings['fallback'];

        echo esc_html($description);
    }
}

/**
 * Register Dynamic Tags Group
 */
add_action('elementor/dynamic_tags/register', function($dynamic_tags_manager) {

    // Register group
    $dynamic_tags_manager->register_group('human-design', [
        'title' => __('Human Design', 'sessiontags-humandesign')
    ]);

    // Register tags
    $dynamic_tags_manager->register(new \HumanDesign_Type_Tag());
    $dynamic_tags_manager->register(new \HumanDesign_Strategy_Tag());
    $dynamic_tags_manager->register(new \HumanDesign_Description_Tag());
});
