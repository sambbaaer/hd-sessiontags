<?php
/**
 * Human Design Elementor Dynamic Tags
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Human Design Type Dynamic Tag
 */
class HumanDesign_Type_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-type';
    }

    public function get_title() {
        return 'Human Design Type';
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function _register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Typ noch nicht ermittelt',
            ]
        );

        $this->add_control(
            'show_calculator_link',
            [
                'label' => 'Calculator Link bei fehlendem Typ',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ja',
                'label_off' => 'Nein',
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
                echo '<a href="#hd-calculator" class="hd-calculate-link">' . esc_html($settings['fallback']) . ' ✨</a>';
            } else {
                echo esc_html($settings['fallback']);
            }
        } else {
            echo esc_html($type);
        }
    }
}

/**
 * Human Design Strategy Dynamic Tag
 */
class HumanDesign_Strategy_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-strategy';
    }

    public function get_title() {
        return 'Human Design Strategy';
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function _register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Strategie unbekannt',
            ]
        );

        $this->add_control(
            'prefix',
            [
                'label' => 'Prefix',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Deine Strategie: ',
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
 * Human Design Description Dynamic Tag
 */
class HumanDesign_Description_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'hd-description';
    }

    public function get_title() {
        return 'Human Design Description';
    }

    public function get_group() {
        return 'human-design';
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function _register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback Text',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Entdecke deinen Human Design Typ für eine personalisierte Beschreibung.',
            ]
        );

        $this->add_control(
            'length',
            [
                'label' => 'Text Länge',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'short' => 'Kurz',
                    'full' => 'Vollständig',
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
                'short' => 'Du bist hier um zu arbeiten und zu erschaffen.',
                'full' => 'Du bist hier um zu arbeiten und zu erschaffen. Folge deiner Begeisterung und reagiere auf das Leben! Deine Sakralenergie ist deine Superkraft.'
            ],
            'ManifestingGenerator' => [
                'short' => 'Du bist ein Multitalent mit Manifestationskraft.',
                'full' => 'Du bist ein Multitalent mit der Kraft zu manifestieren und zu generieren. Du kannst viele Dinge gleichzeitig machen und bist ein Meister der Effizienz.'
            ],
            'Projector' => [
                'short' => 'Du bist ein natürlicher Leiter.',
                'full' => 'Du bist ein natürlicher Leiter und Koordinator. Warte auf Anerkennung und Einladungen für deine einzigartigen Gaben. Du siehst die Menschen um dich herum sehr klar.'
            ],
            'Manifestor' => [
                'short' => 'Du bist ein Initiator.',
                'full' => 'Du bist ein Initiator mit der Kraft, Neues zu beginnen. Informiere andere über deine Pläne und nutze deine unabhängige Natur als Stärke.'
            ],
            'Reflector' => [
                'short' => 'Du bist ein Spiegel der Gemeinschaft.',
                'full' => 'Du bist ein seltener Spiegel der Gemeinschaft. Lass dir Zeit für wichtige Entscheidungen und vertraue auf die Weisheit des Mondzyklus.'
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
add_action('elementor/dynamic_tags/register_tags', function($dynamic_tags_manager) {

    // Register group
    $dynamic_tags_manager->register_group('human-design', [
        'title' => 'Human Design'
    ]);

    // Register tags
    $dynamic_tags_manager->register_tag('HumanDesign_Type_Tag');
    $dynamic_tags_manager->register_tag('HumanDesign_Strategy_Tag');
    $dynamic_tags_manager->register_tag('HumanDesign_Description_Tag');
});

/**
 * Add Custom CSS for Dynamic Tags
 */
add_action('wp_head', function() {
    $type = $_SESSION['hd_type'] ?? '';
    if (empty($type)) return;

    $colors = [
        'Generator' => ['primary' => '#e74c3c', 'secondary' => '#c0392b', 'accent' => '#f39c12'],
        'ManifestingGenerator' => ['primary' => '#e67e22', 'secondary' => '#d35400', 'accent' => '#f1c40f'],
        'Projector' => ['primary' => '#3498db', 'secondary' => '#2980b9', 'accent' => '#9b59b6'],
        'Manifestor' => ['primary' => '#2ecc71', 'secondary' => '#27ae60', 'accent' => '#1abc9c'],
        'Reflector' => ['primary' => '#9b59b6', 'secondary' => '#8e44ad', 'accent' => '#e91e63']
    ];

    $type_colors = $colors[$type] ?? $colors['Generator'];

    echo "<style id='hd-dynamic-colors'>
    :root {
        --hd-primary: {$type_colors['primary']};
        --hd-secondary: {$type_colors['secondary']};
        --hd-accent: {$type_colors['accent']};
    }

    .elementor-widget-text-editor a[href*='reading-buchen'] {
        background: var(--hd-primary) !important;
        color: white !important;
        padding: 12px 24px !important;
        border-radius: 25px !important;
        text-decoration: none !important;
        display: inline-block !important;
        transition: transform 0.2s ease !important;
    }

    .elementor-widget-text-editor a[href*='reading-buchen']:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    }

    .hd-calculate-link {
        color: var(--hd-primary) !important;
        text-decoration: underline !important;
    }
    </style>";
});
?>
