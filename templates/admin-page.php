<?php
/**
 * Template: Admin Page
 */

if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission
if (isset($_POST['submit'])) {
    check_admin_referer('hd_admin_settings');

    update_option('hd_auto_redirect', isset($_POST['hd_auto_redirect']));

    $personalized_urls = array();
    if (isset($_POST['hd_personalized_urls'])) {
        foreach ($_POST['hd_personalized_urls'] as $type => $url) {
            if (!empty($url)) {
                $personalized_urls[sanitize_key($type)] = esc_url_raw($url);
            }
        }
    }
    update_option('hd_personalized_urls', $personalized_urls);

    echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';
}

$auto_redirect = get_option('hd_auto_redirect', false);
$personalized_urls = get_option('hd_personalized_urls', array());

$types = array(
    'generator' => 'Generator',
    'manifestinggenerator' => 'Manifesting Generator',
    'projector' => 'Projector',
    'manifestor' => 'Manifestor',
    'reflector' => 'Reflector'
);

// Get type colors for display
function get_hd_type_colors($type) {
    $colors = array(
        'Generator' => '#e74c3c',
        'Manifesting Generator' => '#e67e22',
        'Projector' => '#3498db',
        'Manifestor' => '#2ecc71',
        'Reflector' => '#9b59b6'
    );
    return $colors[$type] ?? '#667eea';
}

function get_hd_type_description($type) {
    $descriptions = array(
        'Generator' => 'Die Arbeiter und Erschaffer - 70% der Bevölkerung',
        'Manifesting Generator' => 'Die Multi-Talente mit Manifestationskraft',
        'Projector' => 'Die natürlichen Leiter und Koordinatoren',
        'Manifestor' => 'Die Initiatoren mit unabhängiger Kraft',
        'Reflector' => 'Die seltenen Spiegel der Gemeinschaft'
    );
    return $descriptions[$type] ?? '';
}
?>

<div class="wrap">
    <h1>🔮 Human Design Settings</h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- Main Settings -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>🎯 Personalisierung & Weiterleitungen</h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('hd_admin_settings'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="hd_auto_redirect">Automatische Weiterleitung</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           name="hd_auto_redirect"
                                           id="hd_auto_redirect"
                                           value="1"
                                           <?php checked($auto_redirect); ?>>
                                    Benutzer automatisch zu personalisierten Seiten weiterleiten
                                </label>
                                <p class="description">
                                    Nach der Typ-Erkennung werden Benutzer zu speziellen Seiten für ihren Typ weitergeleitet.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <h3>🎨 Personalisierte URLs für jeden Typ</h3>
                    <p>Definiere spezielle Seiten für jeden Human Design Typ. Diese werden für automatische Weiterleitungen und personalisierte CTAs verwendet.</p>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 200px;">Human Design Typ</th>
                                <th>Personalisierte URL</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $key => $type): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 16px; height: 16px; border-radius: 50%; background: <?php echo get_hd_type_colors($type); ?>;"></div>
                                            <strong><?php echo esc_html($type); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="url"
                                               name="hd_personalized_urls[<?php echo esc_attr($key); ?>]"
                                               value="<?php echo esc_url($personalized_urls[$key] ?? ''); ?>"
                                               placeholder="https://humandesigns.ch/<?php echo esc_attr($key); ?>-guide/"
                                               class="regular-text"
                                               style="width: 100%;">
                                    </td>
                                    <td>
                                        <small style="color: #666;"><?php echo get_hd_type_description($type); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 20px;">
                        <?php submit_button('Einstellungen speichern', 'primary', 'submit', false); ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div>
            <!-- Plugin Status -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2>📊 Plugin Status</h2>
                </div>
                <div class="inside">
                    <?php if (session_status() === PHP_SESSION_ACTIVE): ?>
                        <p>✅ <strong>PHP Sessions:</strong> Aktiv</p>

                        <?php if (!empty($_SESSION['hd_type'])): ?>
                            <div style="background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <p><strong>🎯 Aktueller Typ:</strong>
                                   <span style="background: <?php echo get_hd_type_colors($_SESSION['hd_type']); ?>; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                       <?php echo esc_html($_SESSION['hd_type']); ?>
                                   </span>
                                </p>
                                <p><strong>📅 Berechnet:</strong> <?php echo !empty($_SESSION['hd_calculated']) ? 'Ja' : 'Nein'; ?></p>
                                <?php if (!empty($_SESSION['hd_calculated_timestamp'])): ?>
                                    <p><strong>🕒 Zeitstempel:</strong> <?php echo date('d.m.Y H:i', $_SESSION['hd_calculated_timestamp']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p>⚪ <strong>Session:</strong> Kein Human Design Typ gespeichert</p>
                        <?php endif; ?>

                        <?php if (class_exists('SessionTags')): ?>
                            <p>✅ <strong>SessionTags:</strong> Plugin aktiv & integriert</p>
                        <?php else: ?>
                            <p>⚠️ <strong>SessionTags:</strong> Plugin nicht gefunden</p>
                            <p><small>Für erweiterte Features installiere das SessionTags Plugin</small></p>
                        <?php endif; ?>

                        <?php if (did_action('elementor/loaded')): ?>
                            <p>✅ <strong>Elementor:</strong> Integration aktiv</p>
                        <?php else: ?>
                            <p>⚪ <strong>Elementor:</strong> Nicht aktiv</p>
                            <p><small>Für Elementor Widgets installiere Elementor</small></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>❌ <strong>PHP Sessions:</strong> Nicht aktiv</p>
                        <p><small>Sessions sind für die Typ-Speicherung erforderlich</small></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usage Guide -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2>📋 Verwendung</h2>
                </div>
                <div class="inside">
                    <h4>Basis Shortcodes:</h4>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace;">
                        [hd_calculator]
                    </div>
                    <small>Vollständiger Calculator</small><br><br>

                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace;">
                        [hd_calculator style="minimal"]
                    </div>
                    <small>Kompakter Calculator</small><br><br>

                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace;">
                        [hd_type default="Unbekannt"]
                    </div>
                    <small>Zeigt aktuellen Typ</small><br><br>

                    <h4>Bedingte Inhalte:</h4>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace; font-size: 12px;">
                        [hd_content type="Generator"]<br>
                        Inhalt nur für Generatoren<br>
                        [/hd_content]
                    </div>

                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace; font-size: 12px;">
                        [hd_content types="Generator,Projector"]<br>
                        Inhalt für mehrere Typen<br>
                        [/hd_content]
                    </div>

                    <?php if (class_exists('SessionTags')): ?>
                        <h4>SessionTags Integration:</h4>
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-family: monospace;">
                            [st k="hd_type" d="Unbekannt"]
                        </div>
                        <small>Human Design Typ über SessionTags</small><br><br>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2>📈 Statistiken</h2>
                </div>
                <div class="inside">
                    <?php
                    // Simple statistics (you could expand this with actual data)
                    $total_calculations = get_option('hd_total_calculations', 0);
                    $today_calculations = get_option('hd_today_calculations_' . date('Y-m-d'), 0);
                    ?>

                    <p><strong>📊 Gesamt Berechnungen:</strong> <?php echo number_format($total_calculations); ?></p>
                    <p><strong>📅 Heute:</strong> <?php echo number_format($today_calculations); ?></p>

                    <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 4px;">
                        <p><strong>💡 Tipp:</strong></p>
                        <p><small>Platziere den Calculator prominent auf deiner Homepage für maximale Conversion!</small></p>
                    </div>
                </div>
            </div>

            <!-- Support -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2>🛟 Support</h2>
                </div>
                <div class="inside">
                    <p><strong>📧 Support:</strong><br>
                    <a href="mailto:samuel@humandesigns.ch">samuel@humandesigns.ch</a></p>

                    <p><strong>📖 Dokumentation:</strong><br>
                    <a href="https://samuelbaer.ch/sessiontags" target="_blank">SessionTags Docs</a></p>

                    <p><strong>🐛 Bug Reports:</strong><br>
                    <a href="https://github.com/sambbaaer/Sessiontags/issues" target="_blank">GitHub Issues</a></p>

                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px;">
                        <p><strong>🚀 Feature Request?</strong></p>
                        <p><small>Lass uns wissen, welche Features du dir wünschst!</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Area -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2>🧪 Test Calculator</h2>
        </div>
        <div class="inside">
            <p>Teste den Calculator hier direkt im Admin-Bereich:</p>

            <div style="max-width: 400px; margin: 20px 0;">
                <?php echo do_shortcode('[hd_calculator style="minimal"]'); ?>
            </div>

            <p><small><strong>Hinweis:</strong> Dies ist ein Test-Calculator. Für die Live-Website verwende den Shortcode auf deinen Seiten.</small></p>
        </div>
    </div>
</div>

<style>
.hd-type-color {
    vertical-align: middle;
}

.widefat th,
.widefat td {
    padding: 12px;
}

.postbox .inside {
    margin: 0;
    padding: 12px;
}

.form-table th {
    width: 200px;
}

.notice {
    margin: 5px 0 15px;
}

.hd-admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.hd-stat-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    border: 1px solid #dee2e6;
}

.hd-stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.hd-stat-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}
</style>
<?php

// Track admin page visits (simple analytics)
$admin_visits = get_option('hd_admin_visits', 0);
update_option('hd_admin_visits', $admin_visits + 1);
?>
