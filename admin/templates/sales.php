<?php
/**
 * Sales Template - Verkaufshistorie & Session-Management
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Admin/Templates
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Security Check
if (!current_user_can('amp_process_sales')) {
    wp_die(__('Sie haben keine Berechtigung für diese Seite.', 'automaten-manager-pro'));
}

// Mock data for demonstration (will be replaced by actual database queries)
$mock_sessions = [
    [
        'id' => 1,
        'session_start' => '2025-07-27 14:30:00',
        'session_end' => '2025-07-27 14:32:15',
        'total_items' => 3,
        'total_net' => 5.04,
        'total_vat' => 0.96,
        'total_deposit' => 0.50,
        'total_gross' => 6.50,
        'payment_method' => 'cash',
        'user_id' => 1,
        'items' => [
            ['name' => 'Coca Cola 0,5L', 'quantity' => 2, 'price' => 1.75],
            ['name' => 'Chips Paprika', 'quantity' => 1, 'price' => 2.50]
        ]
    ],
    [
        'id' => 2,
        'session_start' => '2025-07-27 13:45:00',
        'session_end' => '2025-07-27 13:47:30',
        'total_items' => 2,
        'total_net' => 4.20,
        'total_vat' => 0.80,
        'total_deposit' => 0.25,
        'total_gross' => 5.25,
        'payment_method' => 'card',
        'user_id' => 1,
        'items' => [
            ['name' => 'Milch 3,5%', 'quantity' => 1, 'price' => 1.20],
            ['name' => 'Brot Vollkorn', 'quantity' => 1, 'price' => 2.50]
        ]
    ],
    [
        'id' => 3,
        'session_start' => '2025-07-27 12:15:00',
        'session_end' => '2025-07-27 12:16:45',
        'total_items' => 5,
        'total_net' => 8.40,
        'total_vat' => 1.60,
        'total_deposit' => 1.25,
        'total_gross' => 11.25,
        'payment_method' => 'mixed',
        'user_id' => 2,
        'items' => [
            ['name' => 'Coca Cola 0,5L', 'quantity' => 5, 'price' => 1.75]
        ]
    ]
];

$total_sessions = count($mock_sessions);
$total_revenue = array_sum(array_column($mock_sessions, 'total_gross'));
$total_items_sold = array_sum(array_column($mock_sessions, 'total_items'));
$avg_session_value = $total_revenue / $total_sessions;
?>

<div class="amp-container">
    <!-- Header Section -->
    <div class="amp-header-section">
        <div class="amp-header-content">
            <div class="amp-header-left">
                <h1 class="amp-page-title">
                    <i class="amp-icon-shopping-cart"></i>
                    <?php _e('Verkäufe & Sessions', 'automaten-manager-pro'); ?>
                </h1>
                <p class="amp-page-subtitle">
                    <?php _e('Verkaufshistorie, Session-Management und Umsatz-Analytics', 'automaten-manager-pro'); ?>
                </p>
            </div>
            <div class="amp-header-actions">
                <button class="amp-btn amp-btn-primary" id="amp-new-session-btn">
                    <i class="amp-icon-plus"></i>
                    <?php _e('Neue Session', 'automaten-manager-pro'); ?>
                </button>
                <div class="amp-btn-group">
                    <button class="amp-btn amp-btn-secondary" id="amp-export-pdf-btn">
                        <i class="amp-icon-file-pdf"></i>
                        PDF
                    </button>
                    <button class="amp-btn amp-btn-secondary" id="amp-export-csv-btn">
                        <i class="amp-icon-file-csv"></i>
                        CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Section -->
    <div class="amp-stats-grid">
        <div class="amp-stat-card amp-card-revenue">
            <div class="amp-stat-icon">
                <i class="amp-icon-euro"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo number_format($total_revenue, 2); ?>€</div>
                <div class="amp-stat-label"><?php _e('Tagesumsatz', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-positive">+12.4%</div>
            </div>
        </div>
        
        <div class="amp-stat-card amp-card-sessions">
            <div class="amp-stat-icon">
                <i class="amp-icon-shopping-bag"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo $total_sessions; ?></div>
                <div class="amp-stat-label"><?php _e('Sessions heute', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-positive">+8.7%</div>
            </div>
        </div>
        
        <div class="amp-stat-card amp-card-items">
            <div class="amp-stat-icon">
                <i class="amp-icon-package"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo $total_items_sold; ?></div>
                <div class="amp-stat-label"><?php _e('Artikel verkauft', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-positive">+15.2%</div>
            </div>
        </div>
        
        <div class="amp-stat-card amp-card-average">
            <div class="amp-stat-icon">
                <i class="amp-icon-trending-up"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo number_format($avg_session_value, 2); ?>€</div>
                <div class="amp-stat-label"><?php _e('Ø Session-Wert', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-positive">+3.4%</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="amp-card amp-filter-card">
        <div class="amp-filter-header">
            <h3><?php _e('Filter & Suche', 'automaten-manager-pro'); ?></h3>
            <button class="amp-btn amp-btn-ghost" id="amp-reset-filters-btn">
                <i class="amp-icon-refresh"></i>
                <?php _e('Zurücksetzen', 'automaten-manager-pro'); ?>
            </button>
        </div>
        
        <div class="amp-filter-grid">
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Zeitraum', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-timeframe">
                    <option value="today"><?php _e('Heute', 'automaten-manager-pro'); ?></option>
                    <option value="yesterday"><?php _e('Gestern', 'automaten-manager-pro'); ?></option>
                    <option value="this_week"><?php _e('Diese Woche', 'automaten-manager-pro'); ?></option>
                    <option value="last_week"><?php _e('Letzte Woche', 'automaten-manager-pro'); ?></option>
                    <option value="this_month"><?php _e('Dieser Monat', 'automaten-manager-pro'); ?></option>
                    <option value="custom"><?php _e('Benutzerdefiniert', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Zahlungsart', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-payment">
                    <option value=""><?php _e('Alle Zahlungsarten', 'automaten-manager-pro'); ?></option>
                    <option value="cash"><?php _e('Bargeld', 'automaten-manager-pro'); ?></option>
                    <option value="card"><?php _e('EC-Karte', 'automaten-manager-pro'); ?></option>
                    <option value="mixed"><?php _e('Gemischt', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Benutzer', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-user">
                    <option value=""><?php _e('Alle Benutzer', 'automaten-manager-pro'); ?></option>
                    <option value="1">Max Mustermann</option>
                    <option value="2">Anna Schmidt</option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Min. Betrag (€)', 'automaten-manager-pro'); ?></label>
                <input type="number" class="amp-form-control" id="amp-filter-min-amount" 
                       placeholder="0.00" min="0" step="0.01">
            </div>
        </div>
        
        <div class="amp-custom-date-range" id="amp-custom-date-range" style="display: none;">
            <div class="amp-date-inputs">
                <div class="amp-filter-group">
                    <label class="amp-label"><?php _e('Von', 'automaten-manager-pro'); ?></label>
                    <input type="date" class="amp-form-control" id="amp-filter-date-from">
                </div>
                <div class="amp-filter-group">
                    <label class="amp-label"><?php _e('Bis', 'automaten-manager-pro'); ?></label>
                    <input type="date" class="amp-form-control" id="amp-filter-date-to">
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="amp-card amp-table-card">
        <div class="amp-table-header">
            <h3><?php _e('Verkaufs-Sessions', 'automaten-manager-pro'); ?></h3>
            <div class="amp-table-actions">
                <div class="amp-search-box">
                    <input type="text" class="amp-form-control" placeholder="<?php _e('Session suchen...', 'automaten-manager-pro'); ?>" id="amp-search-sessions">
                    <i class="amp-icon-search"></i>
                </div>
                <button class="amp-btn amp-btn-ghost" id="amp-refresh-table-btn">
                    <i class="amp-icon-refresh"></i>
                </button>
            </div>
        </div>
        
        <div class="amp-table-responsive">
            <table class="amp-table" id="amp-sessions-table">
                <thead>
                    <tr>
                        <th class="amp-sortable" data-sort="id">
                            <?php _e('Session ID', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th class="amp-sortable" data-sort="datetime">
                            <?php _e('Datum & Zeit', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th class="amp-sortable" data-sort="duration">
                            <?php _e('Dauer', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th class="amp-sortable" data-sort="items">
                            <?php _e('Artikel', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th class="amp-sortable" data-sort="payment">
                            <?php _e('Zahlung', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th class="amp-sortable" data-sort="total">
                            <?php _e('Gesamt', 'automaten-manager-pro'); ?>
                            <i class="amp-icon-chevron-up-down"></i>
                        </th>
                        <th><?php _e('Aktionen', 'automaten-manager-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mock_sessions as $session): 
                        $start_time = new DateTime($session['session_start']);
                        $end_time = new DateTime($session['session_end']);
                        $duration = $end_time->diff($start_time);
                        $payment_icons = [
                            'cash' => 'amp-icon-banknotes',
                            'card' => 'amp-icon-credit-card',
                            'mixed' => 'amp-icon-squares-2x2'
                        ];
                        $payment_labels = [
                            'cash' => __('Bargeld', 'automaten-manager-pro'),
                            'card' => __('EC-Karte', 'automaten-manager-pro'),
                            'mixed' => __('Gemischt', 'automaten-manager-pro')
                        ];
                    ?>
                    <tr class="amp-table-row" data-session-id="<?php echo $session['id']; ?>">
                        <td class="amp-session-id">
                            <span class="amp-badge amp-badge-primary">#<?php echo str_pad($session['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </td>
                        <td class="amp-session-datetime">
                            <div class="amp-datetime-display">
                                <div class="amp-date"><?php echo $start_time->format('d.m.Y'); ?></div>
                                <div class="amp-time"><?php echo $start_time->format('H:i'); ?> Uhr</div>
                            </div>
                        </td>
                        <td class="amp-session-duration">
                            <span class="amp-duration-badge">
                                <?php echo $duration->format('%i:%S'); ?> min
                            </span>
                        </td>
                        <td class="amp-session-items">
                            <div class="amp-items-display">
                                <span class="amp-items-count"><?php echo $session['total_items']; ?></span>
                                <span class="amp-items-label"><?php _e('Artikel', 'automaten-manager-pro'); ?></span>
                            </div>
                        </td>
                        <td class="amp-session-payment">
                            <div class="amp-payment-method">
                                <i class="<?php echo $payment_icons[$session['payment_method']]; ?>"></i>
                                <?php echo $payment_labels[$session['payment_method']]; ?>
                            </div>
                        </td>
                        <td class="amp-session-total">
                            <div class="amp-price-breakdown">
                                <div class="amp-total-gross"><?php echo number_format($session['total_gross'], 2); ?>€</div>
                                <div class="amp-total-details">
                                    Netto: <?php echo number_format($session['total_net'], 2); ?>€ | 
                                    MwSt: <?php echo number_format($session['total_vat'], 2); ?>€
                                    <?php if ($session['total_deposit'] > 0): ?>
                                    | Pfand: <?php echo number_format($session['total_deposit'], 2); ?>€
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="amp-session-actions">
                            <div class="amp-action-buttons">
                                <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                        onclick="ampShowSessionDetails(<?php echo $session['id']; ?>)" 
                                        title="<?php _e('Details anzeigen', 'automaten-manager-pro'); ?>">
                                    <i class="amp-icon-eye"></i>
                                </button>
                                <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                        onclick="ampDownloadReceipt(<?php echo $session['id']; ?>)" 
                                        title="<?php _e('Beleg herunterladen', 'automaten-manager-pro'); ?>">
                                    <i class="amp-icon-download"></i>
                                </button>
                                <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                        onclick="ampEmailReceipt(<?php echo $session['id']; ?>)" 
                                        title="<?php _e('Beleg per E-Mail', 'automaten-manager-pro'); ?>">
                                    <i class="amp-icon-envelope"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="amp-table-pagination">
            <div class="amp-pagination-info">
                <?php _e('Zeige 1-3 von 3 Sessions', 'automaten-manager-pro'); ?>
            </div>
            <div class="amp-pagination-controls">
                <button class="amp-btn amp-btn-sm amp-btn-ghost" disabled>
                    <i class="amp-icon-chevron-left"></i>
                    <?php _e('Zurück', 'automaten-manager-pro'); ?>
                </button>
                <span class="amp-pagination-current">1</span>
                <button class="amp-btn amp-btn-sm amp-btn-ghost" disabled>
                    <?php _e('Weiter', 'automaten-manager-pro'); ?>
                    <i class="amp-icon-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Analytics Chart -->
    <div class="amp-card amp-chart-card">
        <div class="amp-chart-header">
            <h3><?php _e('Umsatz-Trend (7 Tage)', 'automaten-manager-pro'); ?></h3>
            <div class="amp-chart-controls">
                <select class="amp-form-control" id="amp-chart-period">
                    <option value="7days"><?php _e('7 Tage', 'automaten-manager-pro'); ?></option>
                    <option value="30days"><?php _e('30 Tage', 'automaten-manager-pro'); ?></option>
                    <option value="3months"><?php _e('3 Monate', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
        </div>
        <div class="amp-chart-container">
            <canvas id="amp-sales-chart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Session Details Modal -->
<div class="amp-modal" id="amp-session-details-modal">
    <div class="amp-modal-backdrop" onclick="ampCloseModal('amp-session-details-modal')"></div>
    <div class="amp-modal-content">
        <div class="amp-modal-header">
            <h3><?php _e('Session Details', 'automaten-manager-pro'); ?></h3>
            <button class="amp-modal-close" onclick="ampCloseModal('amp-session-details-modal')">
                <i class="amp-icon-x"></i>
            </button>
        </div>
        <div class="amp-modal-body" id="amp-session-details-content">
            <!-- Content will be loaded dynamically -->
            <div class="amp-loading-spinner">
                <div class="amp-spinner"></div>
                <p><?php _e('Lade Session-Details...', 'automaten-manager-pro'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- New Session Modal -->
<div class="amp-modal" id="amp-new-session-modal">
    <div class="amp-modal-backdrop" onclick="ampCloseModal('amp-new-session-modal')"></div>
    <div class="amp-modal-content">
        <div class="amp-modal-header">
            <h3><?php _e('Neue Verkaufs-Session starten', 'automaten-manager-pro'); ?></h3>
            <button class="amp-modal-close" onclick="ampCloseModal('amp-new-session-modal')">
                <i class="amp-icon-x"></i>
            </button>
        </div>
        <div class="amp-modal-body">
            <div class="amp-new-session-content">
                <div class="amp-session-info">
                    <i class="amp-icon-info-circle"></i>
                    <p><?php _e('Eine neue Verkaufs-Session startet automatisch beim ersten Scan eines Produkts über den Scanner.', 'automaten-manager-pro'); ?></p>
                </div>
                <div class="amp-session-actions">
                    <a href="<?php echo admin_url('admin.php?page=automaten-manager-scanner'); ?>" 
                       class="amp-btn amp-btn-primary amp-btn-lg">
                        <i class="amp-icon-qr-code"></i>
                        <?php _e('Scanner öffnen', 'automaten-manager-pro'); ?>
                    </a>
                    <button class="amp-btn amp-btn-secondary" onclick="ampCloseModal('amp-new-session-modal')">
                        <?php _e('Abbrechen', 'automaten-manager-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Sales-specific styles */
.amp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.amp-stat-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.9));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.amp-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--amp-primary);
    border-radius: 16px 16px 0 0;
}

.amp-card-revenue::before { background: linear-gradient(90deg, #10B981, #059669); }
.amp-card-sessions::before { background: linear-gradient(90deg, #007BFF, #0056b3); }
.amp-card-items::before { background: linear-gradient(90deg, #F59E0B, #d97706); }
.amp-card-average::before { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }

.amp-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.amp-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    background: linear-gradient(135deg, var(--amp-primary), rgba(0, 123, 255, 0.8));
}

.amp-card-revenue .amp-stat-icon { background: linear-gradient(135deg, #10B981, #059669); }
.amp-card-sessions .amp-stat-icon { background: linear-gradient(135deg, #007BFF, #0056b3); }
.amp-card-items .amp-stat-icon { background: linear-gradient(135deg, #F59E0B, #d97706); }
.amp-card-average .amp-stat-icon { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }

.amp-stat-content {
    flex: 1;
}

.amp-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--amp-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.amp-stat-label {
    font-size: 0.875rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}

.amp-stat-change {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
}

.amp-stat-change.amp-positive {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.amp-stat-change.amp-negative {
    background: rgba(239, 68, 68, 0.1);
    color: #DC2626;
}

.amp-filter-card {
    margin-bottom: 2rem;
}

.amp-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #E5E7EB;
}

.amp-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.amp-custom-date-range {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #E5E7EB;
}

.amp-date-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.amp-table-responsive {
    overflow-x: auto;
    margin: -1px;
}

.amp-table {
    width: 100%;
    min-width: 800px;
}

.amp-table th.amp-sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.amp-table th.amp-sortable:hover {
    background: rgba(0, 123, 255, 0.05);
}

.amp-table th.amp-sortable i {
    margin-left: 0.5rem;
    opacity: 0.5;
    transition: opacity 0.3s;
}

.amp-table th.amp-sortable:hover i {
    opacity: 1;
}

.amp-datetime-display {
    display: flex;
    flex-direction: column;
}

.amp-date {
    font-weight: 600;
    color: var(--amp-text);
}

.amp-time {
    font-size: 0.875rem;
    color: #6B7280;
}

.amp-duration-badge {
    background: rgba(0, 123, 255, 0.1);
    color: var(--amp-primary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.amp-items-display {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.amp-items-count {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--amp-text);
}

.amp-items-label {
    font-size: 0.75rem;
    color: #6B7280;
}

.amp-payment-method {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.amp-payment-method i {
    font-size: 1.125rem;
    color: var(--amp-primary);
}

.amp-price-breakdown {
    text-align: right;
}

.amp-total-gross {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-total-details {
    font-size: 0.75rem;
    color: #6B7280;
    line-height: 1.2;
}

.amp-action-buttons {
    display: flex;
    gap: 0.25rem;
}

.amp-chart-card {
    margin-top: 2rem;
}

.amp-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.amp-chart-container {
    position: relative;
    height: 300px;
    background: rgba(248, 249, 250, 0.5);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6B7280;
}

.amp-new-session-content {
    text-align: center;
    padding: 2rem;
}

.amp-session-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 8px;
    margin-bottom: 2rem;
}

.amp-session-info i {
    font-size: 1.5rem;
    color: var(--amp-primary);
}

.amp-session-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Samsung S25 Ultra optimizations */
@media (max-width: 430px) and (min-height: 900px) {
    .amp-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .amp-stat-card {
        padding: 1rem;
    }
    
    .amp-stat-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .amp-stat-value {
        font-size: 1.5rem;
    }
    
    .amp-filter-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .amp-table {
        min-width: 600px;
    }
    
    .amp-header-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .amp-btn-group {
        display: flex;
        width: 100%;
    }
    
    .amp-btn-group .amp-btn {
        flex: 1;
    }
}

/* Touch optimizations */
@media (pointer: coarse) {
    .amp-btn {
        min-height: 44px;
        min-width: 44px;
    }
    
    .amp-table th.amp-sortable {
        padding: 1rem;
    }
    
    .amp-action-buttons .amp-btn {
        min-width: 44px;
        min-height: 44px;
    }
}

/* High DPI displays */
@media (-webkit-min-device-pixel-ratio: 3), (min-resolution: 3dppx) {
    .amp-stat-card {
        border-width: 0.5px;
    }
    
    .amp-table {
        border-width: 0.5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sales page functionality
    initSalesPage();
});

function initSalesPage() {
    // Filter handling
    const timeframeSelect = document.getElementById('amp-filter-timeframe');
    const customDateRange = document.getElementById('amp-custom-date-range');
    
    if (timeframeSelect) {
        timeframeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    }
    
    // Table sorting
    const sortableHeaders = document.querySelectorAll('.amp-sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            const icon = this.querySelector('i');
            
            // Toggle sort direction
            if (icon.classList.contains('amp-icon-chevron-up')) {
                icon.className = 'amp-icon-chevron-down';
            } else if (icon.classList.contains('amp-icon-chevron-down')) {
                icon.className = 'amp-icon-chevron-up-down';
            } else {
                icon.className = 'amp-icon-chevron-up';
            }
            
            // Reset other headers
            sortableHeaders.forEach(otherHeader => {
                if (otherHeader !== this) {
                    const otherIcon = otherHeader.querySelector('i');
                    otherIcon.className = 'amp-icon-chevron-up-down';
                }
            });
            
            ampSortTable(sortField, icon.classList.contains('amp-icon-chevron-up') ? 'asc' : 'desc');
        });
    });
    
    // Button handlers
    const newSessionBtn = document.getElementById('amp-new-session-btn');
    if (newSessionBtn) {
        newSessionBtn.addEventListener('click', () => ampShowModal('amp-new-session-modal'));
    }
    
    const exportPdfBtn = document.getElementById('amp-export-pdf-btn');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', () => ampExportSales('pdf'));
    }
    
    const exportCsvBtn = document.getElementById('amp-export-csv-btn');
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => ampExportSales('csv'));
    }
    
    const resetFiltersBtn = document.getElementById('amp-reset-filters-btn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', ampResetFilters);
    }
    
    // Search functionality
    const searchInput = document.getElementById('amp-search-sessions');
    if (searchInput) {
        searchInput.addEventListener('input', ampFilterSessions);
    }
    
    // Initialize chart (placeholder)
    initSalesChart();
}

function ampShowSessionDetails(sessionId) {
    const modal = document.getElementById('amp-session-details-modal');
    const content = document.getElementById('amp-session-details-content');
    
    // Show modal
    ampShowModal('amp-session-details-modal');
    
    // Simulate loading session details
    setTimeout(() => {
        content.innerHTML = `
            <div class="amp-session-detail-card">
                <div class="amp-session-header">
                    <h4>Session #${sessionId.toString().padStart(4, '0')}</h4>
                    <span class="amp-badge amp-badge-success">Abgeschlossen</span>
                </div>
                <div class="amp-session-info-grid">
                    <div class="amp-info-item">
                        <label>Startzeit:</label>
                        <span>27.07.2025 14:30:00</span>
                    </div>
                    <div class="amp-info-item">
                        <label>Endzeit:</label>
                        <span>27.07.2025 14:32:15</span>
                    </div>
                    <div class="amp-info-item">
                        <label>Dauer:</label>
                        <span>2:15 Minuten</span>
                    </div>
                    <div class="amp-info-item">
                        <label>Benutzer:</label>
                        <span>Max Mustermann</span>
                    </div>
                </div>
                <div class="amp-session-items">
                    <h5>Verkaufte Artikel:</h5>
                    <div class="amp-items-list">
                        <div class="amp-item-row">
                            <span>2x Coca Cola 0,5L</span>
                            <span>3,50€</span>
                        </div>
                        <div class="amp-item-row">
                            <span>1x Chips Paprika</span>
                            <span>2,50€</span>
                        </div>
                    </div>
                </div>
                <div class="amp-session-totals">
                    <div class="amp-total-row">
                        <span>Netto:</span>
                        <span>5,04€</span>
                    </div>
                    <div class="amp-total-row">
                        <span>MwSt:</span>
                        <span>0,96€</span>
                    </div>
                    <div class="amp-total-row">
                        <span>Pfand:</span>
                        <span>0,50€</span>
                    </div>
                    <div class="amp-total-row amp-final-total">
                        <span>Gesamt:</span>
                        <span>6,50€</span>
                    </div>
                </div>
            </div>
        `;
    }, 500);
}

function ampDownloadReceipt(sessionId) {
    // Simulate PDF download
    const link = document.createElement('a');
    link.href = '#'; // Would be actual PDF URL
    link.download = `beleg_session_${sessionId}.pdf`;
    link.click();
    
    ampShowNotification('Beleg wird heruntergeladen...', 'success');
}

function ampEmailReceipt(sessionId) {
    // Simulate email sending
    ampShowNotification('Beleg wird per E-Mail versendet...', 'success');
}

function ampSortTable(field, direction) {
    // Table sorting logic would go here
    console.log(`Sorting by ${field} in ${direction} order`);
}

function ampFilterSessions() {
    const searchTerm = document.getElementById('amp-search-sessions').value.toLowerCase();
    const rows = document.querySelectorAll('#amp-sessions-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function ampResetFilters() {
    // Reset all filter inputs
    document.getElementById('amp-filter-timeframe').value = 'today';
    document.getElementById('amp-filter-payment').value = '';
    document.getElementById('amp-filter-user').value = '';
    document.getElementById('amp-filter-min-amount').value = '';
    document.getElementById('amp-search-sessions').value = '';
    document.getElementById('amp-custom-date-range').style.display = 'none';
    
    // Show all rows
    const rows = document.querySelectorAll('#amp-sessions-table tbody tr');
    rows.forEach(row => row.style.display = '');
    
    ampShowNotification('Filter zurückgesetzt', 'success');
}

function ampExportSales(format) {
    // Export functionality
    ampShowNotification(`Export als ${format.toUpperCase()} wird vorbereitet...`, 'success');
}

function initSalesChart() {
    // Placeholder for chart initialization
    const canvas = document.getElementById('amp-sales-chart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        // Simple placeholder visualization
        ctx.fillStyle = '#6B7280';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Chart wird geladen...', canvas.width / 2, canvas.height / 2);
        
        // In a real implementation, you would use Chart.js or similar
        // to create interactive charts based on actual sales data
    }
}
</script>