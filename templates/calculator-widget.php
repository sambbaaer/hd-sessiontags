<?php
/**
 * Template: Calculator Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

$widget_id = 'hd-calculator-' . uniqid();
$style_class = 'hd-calculator-' . $atts['style'];
?>

<div id="<?php echo $widget_id; ?>" class="hd-calculator <?php echo $style_class; ?>">
    <?php if ($atts['style'] === 'full'): ?>
        <div class="hd-calculator-header">
            <h3>🔮 Entdecke deinen Human Design Typ</h3>
            <p>In nur 30 Sekunden zu deiner personalisierten Website-Erfahrung</p>
        </div>
    <?php endif; ?>

    <form class="hd-calculator-form" data-style="<?php echo $atts['style']; ?>" data-redirect="<?php echo $atts['redirect']; ?>">
        <?php wp_nonce_field('hd_calculation_nonce', 'hd_nonce'); ?>

        <?php if ($atts['style'] === 'minimal'): ?>
            <div class="hd-minimal-intro">
                <p><strong>Personalisierte Inhalte freischalten:</strong></p>
            </div>
        <?php endif; ?>

        <div class="hd-form-row">
            <div class="hd-form-group">
                <label for="hd_birth_date_<?php echo $widget_id; ?>">Geburtsdatum:</label>
                <input type="date"
                       id="hd_birth_date_<?php echo $widget_id; ?>"
                       name="birth_date"
                       required
                       max="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="hd-form-row hd-time-row">
            <div class="hd-form-group hd-form-half">
                <label for="hd_birth_hour_<?php echo $widget_id; ?>">Stunde:</label>
                <select id="hd_birth_hour_<?php echo $widget_id; ?>" name="birth_hour" required>
                    <option value="">--</option>
                    <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?php echo $h; ?>"><?php echo sprintf('%02d', $h); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="hd-form-group hd-form-half">
                <label for="hd_birth_minute_<?php echo $widget_id; ?>">Minute:</label>
                <select id="hd_birth_minute_<?php echo $widget_id; ?>" name="birth_minute" required>
                    <option value="">--</option>
                    <?php for ($m = 0; $m < 60; $m += 5): ?>
                        <option value="<?php echo $m; ?>"><?php echo sprintf('%02d', $m); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <?php if ($atts['style'] === 'full'): ?>
            <div class="hd-form-row">
                <div class="hd-form-group">
                    <label for="hd_birth_location_<?php echo $widget_id; ?>">Geburtsort:</label>
                    <input type="text"
                           id="hd_birth_location_<?php echo $widget_id; ?>"
                           name="birth_location"
                           placeholder="z.B. Zürich, Schweiz"
                           required>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="birth_location" value="Zurich, Switzerland">
        <?php endif; ?>

        <div class="hd-form-actions">
            <button type="submit" class="hd-calculate-btn">
                <span class="hd-btn-text">✨ Typ ermitteln</span>
                <span class="hd-btn-loading" style="display: none;">
                    <span class="hd-spinner"></span> Berechne...
                </span>
            </button>
        </div>
    </form>

    <div class="hd-result" style="display: none;">
        <div class="hd-result-content">
            <!-- Result will be populated by JavaScript -->
        </div>

        <?php if ($atts['style'] === 'full'): ?>
            <div class="hd-result-actions">
                <button class="hd-personalize-btn" onclick="location.reload();">🎯 Website personalisieren</button>
                <a href="/reading-buchen/" class="hd-book-reading-btn">📖 Reading buchen</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="hd-error" style="display: none;">
        <div class="hd-error-content">
            <p>❌ <span class="hd-error-message"></span></p>
            <button class="hd-retry-btn" onclick="this.parentNode.parentNode.style.display='none';">🔄 Nochmal versuchen</button>
        </div>
    </div>
</div>

<style>
.hd-calculator {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e9ecef;
    max-width: 500px;
    margin: 20px auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.hd-calculator-minimal {
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.hd-calculator-minimal .hd-minimal-intro {
    text-align: center;
    margin-bottom: 15px;
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

.hd-calculator-minimal .hd-calculator-header h3 {
    color: white;
}

.hd-form-row {
    margin-bottom: 15px;
}

.hd-time-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
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
    transition: transform 0.2s;
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

.hd-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: hd-spin 1s linear infinite;
}

@keyframes hd-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.hd-result {
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}

.hd-result.hidden {
    display: none;
}

.hd-type-badge {
    display: inline-block;
    padding: 8px 16px;
    background: #667eea;
    color: white;
    border-radius: 20px;
    font-weight: bold;
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

.hd-result-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 15px;
}

.hd-personalize-btn,
.hd-book-reading-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.2s ease;
}

.hd-personalize-btn {
    background: #28a745;
    color: white;
}

.hd-book-reading-btn {
    background: #17a2b8;
    color: white;
}

.hd-retry-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 10px;
}

/* Responsive */
@media (max-width: 480px) {
    .hd-calculator {
        margin: 10px;
        padding: 15px;
    }

    .hd-time-row {
        grid-template-columns: 1fr;
    }

    .hd-result-actions {
        grid-template-columns: 1fr;
    }
}
</style>
