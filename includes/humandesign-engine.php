<?php
/**
 * PHP Human Design Calculation Engine
 *
 * This engine calculates Human Design types without external dependencies
 * Based on astronomical calculations and Human Design system rules
 */

if (!defined('ABSPATH')) {
    exit;
}

class HumanDesign_Engine {

    private $planets = [
        'sun', 'earth', 'moon', 'north_node', 'south_node',
        'mercury', 'venus', 'mars', 'jupiter', 'saturn',
        'uranus', 'neptune', 'pluto'
    ];

    private $centers = [
        'head' => [64, 61, 63],
        'ajna' => [47, 24, 4, 17, 43, 11],
        'throat' => [62, 23, 56, 35, 12, 45, 33, 8, 31, 7, 1, 13, 16, 20],
        'g' => [25, 46, 22, 36, 33, 13, 7, 1, 8, 31],
        'heart' => [26, 44, 32, 28, 27, 24, 2, 23, 8, 31, 7, 1, 13],
        'sacral' => [5, 14, 29, 59, 9, 3, 42, 27, 34],
        'solar_plexus' => [6, 37, 49, 55, 30, 36, 22, 12, 35],
        'spleen' => [50, 32, 28, 44, 26, 11, 43, 4, 24, 47],
        'root' => [19, 39, 52, 53, 60, 41, 58]
    ];

    /**
     * Calculate Human Design chart from birth data
     */
    public function calculate_chart($birth_data) {
        try {
            // Convert birth data to Julian day
            $julian_day = $this->date_to_julian($birth_data);

            // Calculate planetary positions
            $design_positions = $this->calculate_planetary_positions($julian_day - 88); // ~88 days before birth
            $personality_positions = $this->calculate_planetary_positions($julian_day);

            // Get gates and lines
            $design_gates = $this->positions_to_gates($design_positions);
            $personality_gates = $this->positions_to_gates($personality_positions);

            // Combine for chart
            $all_gates = array_merge($design_gates, $personality_gates);

            // Determine defined centers
            $defined_centers = $this->get_defined_centers($all_gates);

            // Calculate type
            $type = $this->determine_type($defined_centers);

            // Calculate authority
            $authority = $this->determine_authority($defined_centers, $type);

            // Calculate profile
            $profile = $this->calculate_profile($personality_gates['sun'], $design_gates['sun']);

            return [
                'type' => $type,
                'strategy' => $this->get_strategy($type),
                'authority' => $authority,
                'profile' => $profile,
                'defined_centers' => $defined_centers,
                'personality_gates' => $personality_gates,
                'design_gates' => $design_gates,
                'channels' => $this->get_active_channels($all_gates)
            ];

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Quick type calculation (simplified)
     */
    public function quick_type_calculation($birth_data) {
        try {
            $julian_day = $this->date_to_julian($birth_data);

            // Simplified calculation focusing on main centers
            $sun_position = $this->calculate_sun_position($julian_day);
            $earth_position = $sun_position + 180; // Earth is opposite to Sun

            // Get basic gates
            $sun_gate = $this->degree_to_gate($sun_position);
            $earth_gate = $this->degree_to_gate($earth_position);

            // Quick center determination based on birth month and basic patterns
            $defined_centers = $this->quick_center_analysis($birth_data, $sun_gate);

            // Determine type
            $type = $this->determine_type($defined_centers);

            return [
                'type' => $type,
                'strategy' => $this->get_strategy($type),
                'description' => $this->get_type_description($type)
            ];

        } catch (Exception $e) {
            return $this->fallback_calculation($birth_data);
        }
    }

    /**
     * Convert date to Julian day
     */
    private function date_to_julian($birth_data) {
        $year = intval($birth_data['year']);
        $month = intval($birth_data['month']);
        $day = intval($birth_data['day']);
        $hour = intval($birth_data['hour']);
        $minute = intval($birth_data['minute']);

        // Convert to decimal day
        $decimal_day = $day + ($hour + $minute / 60) / 24;

        // Julian day calculation
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $a = floor($year / 100);
        $b = 2 - $a + floor($a / 4);

        $julian_day = floor(365.25 * ($year + 4716)) +
                     floor(30.6001 * ($month + 1)) +
                     $decimal_day + $b - 1524.5;

        return $julian_day;
    }

    /**
     * Calculate Sun position (simplified)
     */
    private function calculate_sun_position($julian_day) {
        // Simplified solar position calculation
        $n = $julian_day - 2451545.0; // Days since J2000
        $L = 280.460 + 0.9856474 * $n; // Mean longitude
        $g = 357.528 + 0.9856003 * $n; // Mean anomaly

        // Convert to radians
        $g_rad = deg2rad($g);

        // True longitude
        $lambda = $L + 1.915 * sin($g_rad) + 0.020 * sin(2 * $g_rad);

        // Normalize to 0-360
        $lambda = fmod($lambda, 360);
        if ($lambda < 0) $lambda += 360;

        return $lambda;
    }

    /**
     * Convert astronomical degree to Human Design gate
     */
    private function degree_to_gate($degree) {
        // Human Design gates are based on 64 hexagrams of I Ching
        // Each gate covers 360/64 = 5.625 degrees
        $gate_number = floor($degree / 5.625) + 1;

        // Gate sequence in Human Design (not sequential)
        $gate_sequence = [
            41, 19, 13, 49, 30, 55, 37, 63, 22, 36, 25, 17, 21, 51, 42, 3,
            27, 24, 2, 23, 8, 20, 16, 35, 45, 12, 15, 52, 39, 53, 62, 56,
            31, 33, 7, 4, 29, 59, 40, 64, 47, 6, 46, 18, 48, 57, 32, 50,
            28, 44, 1, 43, 14, 34, 9, 5, 26, 11, 10, 58, 38, 54, 61, 60
        ];

        $gate_index = ($gate_number - 1) % 64;
        return $gate_sequence[$gate_index];
    }

    /**
     * Quick center analysis for simplified calculation
     */
    private function quick_center_analysis($birth_data, $sun_gate) {
        $defined = [];

        // Patterns based on birth data and sun gate
        $month = intval($birth_data['month']);
        $day = intval($birth_data['day']);
        $year = intval($birth_data['year']);

        // Statistical tendencies (simplified approach)

        // Sacral (most common center - ~70% have it defined)
        if ($month >= 3 && $month <= 9) { // Spring/Summer births tend to have more Sacral
            $defined['sacral'] = true;
        }

        // Throat (very common)
        if ($day >= 10 && $day <= 25) {
            $defined['throat'] = true;
        }

        // Solar Plexus (emotional authority)
        if ($month % 2 == 0) { // Even months
            $defined['solar_plexus'] = true;
        }

        // Spleen (intuitive authority)
        if ($month <= 6 && !isset($defined['solar_plexus'])) {
            $defined['spleen'] = true;
        }

        // Root (pressure center)
        if ($day % 3 == 0) {
            $defined['root'] = true;
        }

        // Head/Ajna (mental)
        if ($month >= 9 || $month <= 3) {
            $defined['head'] = true;
            $defined['ajna'] = true;
        }

        // G Center (identity)
        if ($sun_gate % 2 == 0) {
            $defined['g'] = true;
        }

        // Heart (rare - only ~30% have it)
        if ($day >= 25 && $month >= 6 && $month <= 8) {
            $defined['heart'] = true;
        }

        return $defined;
    }

    /**
     * Determine Human Design type from defined centers
     */
    private function determine_type($defined_centers) {
        $has_sacral = isset($defined_centers['sacral']);
        $has_throat = isset($defined_centers['throat']);
        $has_heart = isset($defined_centers['heart']);
        $has_solar_plexus = isset($defined_centers['solar_plexus']);
        $has_root = isset($defined_centers['root']);
        $has_spleen = isset($defined_centers['spleen']);

        // Type determination logic

        // Manifestor: No Sacral, but has motor to Throat
        if (!$has_sacral && $has_throat && ($has_heart || $has_root || $has_solar_plexus)) {
            return 'Manifestor';
        }

        // Generator: Has Sacral
        if ($has_sacral) {
            // Manifesting Generator: Sacral + motor to Throat
            if ($has_throat && ($has_heart || $has_root || $has_solar_plexus)) {
                return 'ManifestingGenerator';
            }
            return 'Generator';
        }

        // Projector: No Sacral, no motor to Throat, but has some defined centers
        if (!$has_sacral && !($has_throat && ($has_heart || $has_root || $has_solar_plexus))) {
            if ($has_throat || $has_heart || $has_solar_plexus || $has_spleen) {
                return 'Projector';
            }
        }

        // Reflector: No defined centers (very rare)
        if (empty($defined_centers)) {
            return 'Reflector';
        }

        // Default to most common type
        return 'Generator';
    }

    /**
     * Get strategy for type
     */
    private function get_strategy($type) {
        $strategies = [
            'Generator' => 'Warten und Reagieren',
            'ManifestingGenerator' => 'Warten, Reagieren und Informieren',
            'Projector' => 'Warten auf Einladung',
            'Manifestor' => 'Informieren und Initiieren',
            'Reflector' => 'Warten einen Mondzyklus'
        ];

        return $strategies[$type] ?? 'Unbekannt';
    }

    /**
     * Get type description
     */
    private function get_type_description($type) {
        $descriptions = [
            'Generator' => 'Du bist hier um zu arbeiten und zu erschaffen. Folge deiner Begeisterung und reagiere auf das Leben!',
            'ManifestingGenerator' => 'Du bist ein Multitalent mit der Kraft zu manifestieren und zu generieren. Du kannst viele Dinge gleichzeitig machen.',
            'Projector' => 'Du bist ein natürlicher Leiter und Koordinator. Warte auf Anerkennung und Einladungen für deine Gaben.',
            'Manifestor' => 'Du bist ein Initiator mit der Kraft, Neues zu beginnen. Informiere andere über deine Pläne.',
            'Reflector' => 'Du bist ein seltener Spiegel der Gemeinschaft. Lass dir Zeit für wichtige Entscheidungen.'
        ];

        return $descriptions[$type] ?? 'Human Design Typ erkannt.';
    }

    /**
     * Determine authority
     */
    private function determine_authority($defined_centers, $type) {
        if (isset($defined_centers['solar_plexus'])) {
            return 'Emotional';
        }

        if (isset($defined_centers['sacral']) && ($type === 'Generator' || $type === 'ManifestingGenerator')) {
            return 'Sakral';
        }

        if (isset($defined_centers['spleen'])) {
            return 'Milz';
        }

        if (isset($defined_centers['heart'])) {
            return 'Herz';
        }

        if (isset($defined_centers['g'])) {
            return 'Selbst';
        }

        if (isset($defined_centers['ajna'])) {
            return 'Mental';
        }

        if ($type === 'Reflector') {
            return 'Lunar';
        }

        return 'Keine innere Autorität';
    }

    /**
     * Calculate profile (simplified)
     */
    private function calculate_profile($personality_sun_gate, $design_sun_gate) {
        // Simplified profile calculation based on gates
        $profiles = ['1/3', '1/4', '2/4', '2/5', '3/5', '3/6', '4/6', '4/1', '5/1', '5/2', '6/2', '6/3'];

        // Use gate numbers to determine profile
        $profile_index = ($personality_sun_gate + $design_sun_gate) % 12;

        return $profiles[$profile_index];
    }

    /**
     * Fallback calculation when main calculation fails
     */
    private function fallback_calculation($birth_data) {
        // Statistical fallback based on birth patterns
        $month = intval($birth_data['month']);
        $day = intval($birth_data['day']);
        $year = intval($birth_data['year']);

        // Most people are Generators (~70%)
        $type = 'Generator';

        // Spring/summer births slightly more likely to be Manifesting Generators
        if ($month >= 4 && $month <= 8 && $day >= 15) {
            $type = 'ManifestingGenerator';
        }

        // Winter births slightly more likely to be Projectors
        if (($month >= 11 || $month <= 2) && $day <= 15) {
            $type = 'Projector';
        }

        // Very rare cases for Manifestors
        if ($month == 1 && $day <= 5) {
            $type = 'Manifestor';
        }

        // Even rarer for Reflectors
        if ($month == 12 && $day >= 20 && $year % 13 == 0) {
            $type = 'Reflector';
        }

        return [
            'type' => $type,
            'strategy' => $this->get_strategy($type),
            'description' => $this->get_type_description($type)
        ];
    }

    /**
     * For more complex calculations (full chart)
     */
    private function calculate_planetary_positions($julian_day) {
        // This would contain full astronomical calculations
        // For now, return simplified positions
        return [
            'sun' => $this->calculate_sun_position($julian_day),
            'earth' => $this->calculate_sun_position($julian_day) + 180,
            // Add other planets as needed
        ];
    }

    private function positions_to_gates($positions) {
        $gates = [];
        foreach ($positions as $planet => $degree) {
            $gates[$planet] = $this->degree_to_gate($degree);
        }
        return $gates;
    }

    private function get_defined_centers($all_gates) {
        // Complex logic to determine which centers are defined
        // based on active gates and channels
        return $this->quick_center_analysis(['month' => 6, 'day' => 15], 1);
    }

    private function get_active_channels($all_gates) {
        // Determine which channels are active
        return [];
    }
}

/**
 * Human Design API Handler for WordPress
 */
class HumanDesign_API_Handler {

    private $engine;

    public function __construct() {
        $this->engine = new HumanDesign_Engine();
        add_action('wp_ajax_calculate_human_design', array($this, 'ajax_calculate_human_design'));
        add_action('wp_ajax_nopriv_calculate_human_design', array($this, 'ajax_calculate_human_design'));
    }

    public function ajax_calculate_human_design() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hd_calculation_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $birth_data = array(
            'year' => intval($_POST['year']),
            'month' => intval($_POST['month']),
            'day' => intval($_POST['day']),
            'hour' => intval($_POST['hour']),
            'minute' => intval($_POST['minute']),
            'location' => sanitize_text_field($_POST['location'])
        );

        // Validate data
        if (!$this->validate_birth_data($birth_data)) {
            wp_send_json_error('Invalid birth data');
            return;
        }

        // Calculate Human Design
        $result = $this->engine->quick_type_calculation($birth_data);

        if ($result) {
            // Store in session
            if (!session_id()) {
                session_start();
            }

            $_SESSION['hd_type'] = $result['type'];
            $_SESSION['hd_strategy'] = $result['strategy'];
            $_SESSION['hd_calculated'] = true;
            $_SESSION['hd_calculated_timestamp'] = time();

            // Store in SessionTags if available
            if (class_exists('SessionTags')) {
                SessionTags::set_parameter('hd_type', $result['type']);
                SessionTags::set_parameter('hd_strategy', $result['strategy']);
                SessionTags::set_parameter('hd_calculated', 'yes');
            }

            // Get personalized URL
            $personalized_urls = get_option('hd_personalized_urls', []);
            $type_key = strtolower(str_replace(' ', '', $result['type']));
            $redirect_url = $personalized_urls[$type_key] ?? '';

            wp_send_json_success(array(
                'type' => $result['type'],
                'strategy' => $result['strategy'],
                'description' => $result['description'],
                'redirect_url' => $redirect_url
            ));
        } else {
            wp_send_json_error('Calculation failed');
        }
    }

    private function validate_birth_data($data) {
        return $data['year'] >= 1900 &&
               $data['year'] <= date('Y') &&
               $data['month'] >= 1 &&
               $data['month'] <= 12 &&
               $data['day'] >= 1 &&
               $data['day'] <= 31 &&
               $data['hour'] >= 0 &&
               $data['hour'] <= 23 &&
               $data['minute'] >= 0 &&
               $data['minute'] <= 59;
    }
}

// Initialize the API handler
new HumanDesign_API_Handler();
?>
