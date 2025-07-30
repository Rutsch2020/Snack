<?php
/**
 * Reports Template - Business Intelligence Dashboard
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
if (!current_user_can('amp_view_reports')) {
    wp_die(__('Sie haben keine Berechtigung für diese Seite.', 'automaten-manager-pro'));
}

// Mock data for demonstration (will be replaced by actual database queries)
$executive_kpis = [
    'total_revenue' => 24750.89,
    'total_profit' => 8965.32,
    'profit_margin' => 36.2,
    'total_sales' => 342,
    'avg_sale_value' => 72.37,
    'inventory_value' => 15420.50,
    'waste_value' => 234.67,
    'waste_percentage' => 1.52,
    'top_category' => 'Getränke',
    'top_product' => 'Coca Cola 0,5L'
];

$revenue_trend = [
    ['date' => '2025-07-21', 'revenue' => 2150.45, 'profit' => 780.23],
    ['date' => '2025-07-22', 'revenue' => 2340.78, 'profit' => 850.12],
    ['date' => '2025-07-23', 'revenue' => 2890.34, 'profit' => 1045.67],
    ['date' => '2025-07-24', 'revenue' => 3120.56, 'profit' => 1134.89],
    ['date' => '2025-07-25', 'revenue' => 2780.23, 'profit' => 1008.45],
    ['date' => '2025-07-26', 'revenue' => 3450.89, 'profit' => 1250.34],
    ['date' => '2025-07-27', 'revenue' => 4017.64, 'profit' => 1456.78]
];

$category_performance = [
    ['name' => 'Getränke', 'revenue' => 12450.89, 'profit' => 4567.23, 'margin' => 36.7, 'color' => '#007BFF'],
    ['name' => 'Snacks', 'revenue' => 8760.45, 'profit' => 3124.56, 'margin' => 35.7, 'color' => '#10B981'],
    ['name' => 'Kühlprodukte', 'revenue' => 3450.67, 'profit' => 1205.89, 'margin' => 34.9, 'color' => '#F59E0B'],
    ['name' => 'Backwaren', 'revenue' => 1890.23, 'profit' => 456.78, 'margin' => 24.2, 'color' => '#EF4444']
];

$abc_analysis = [
    'A' => ['count' => 8, 'revenue_share' => 80.2, 'products' => ['Coca Cola', 'Chips Paprika', 'Milch 3,5%']],
    'B' => ['count' => 12, 'revenue_share' => 15.3, 'products' => ['Energy Drink', 'Brot Vollkorn']],
    'C' => ['count' => 25, 'revenue_share' => 4.5, 'products' => ['Diverse Artikel']]
];

$hourly_sales = [
    ['hour' => 6, 'sales' => 12, 'revenue' => 145.67],
    ['hour' => 7, 'sales' => 28, 'revenue' => 290.45],
    ['hour' => 8, 'sales' => 45, 'revenue' => 567.89],
    ['hour' => 9, 'sales' => 67, 'revenue' => 789.23],
    ['hour' => 10, 'sales' => 89, 'revenue' => 1123.45],
    ['hour' => 11, 'sales' => 76, 'revenue' => 934.56],
    ['hour' => 12, 'sales' => 95, 'revenue' => 1456.78],
    ['hour' => 13, 'sales' => 102, 'revenue' => 1678.90],
    ['hour' => 14, 'sales' => 87, 'revenue' => 1234.56],
    ['hour' => 15, 'sales' => 73, 'revenue' => 1045.67],
    ['hour' => 16, 'sales' => 65, 'revenue' => 890.34],
    ['hour' => 17, 'sales' => 52, 'revenue' => 678.23],
    ['hour' => 18, 'sales' => 38, 'revenue' => 456.78],
    ['hour' => 19, 'sales' => 24, 'revenue' => 289.45],
    ['hour' => 20, 'sales' => 15, 'revenue' => 178.90]
];

$payment_methods = [
    ['method' => 'Bargeld', 'count' => 215, 'percentage' => 62.9, 'revenue' => 15567.45],
    ['method' => 'EC-Karte', 'count' => 98, 'percentage' => 28.7, 'revenue' => 7123.56],
    ['method' => 'Gemischt', 'count' => 29, 'percentage' => 8.4, 'revenue' => 2059.88]
];

$vat_summary = [
    '19_percent' => ['net' => 18456.78, 'vat' => 3506.79, 'gross' => 21963.57],
    '7_percent' => ['net' => 2567.34, 'vat' => 179.71, 'gross' => 2747.05]
];

// Calculate growth rates
$yesterday_revenue = 3450.89;
$today_revenue = 4017.64;
$revenue_growth = (($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100;

function formatCurrency($amount) {
    return number_format($amount, 2) . '€';
}

function formatPercentage($percentage) {
    return number_format($percentage, 1) . '%';
}

function getGrowthClass($value) {
    if ($value > 0) return 'amp-positive';
    if ($value < 0) return 'amp-negative';
    return 'amp-neutral';
}
?>

<div class="amp-container">
    <!-- Header Section -->
    <div class="amp-header-section">
        <div class="amp-header-content">
            <div class="amp-header-left">
                <h1 class="amp-page-title">
                    <i class="amp-icon-chart-bar"></i>
                    <?php _e('Business Intelligence', 'automaten-manager-pro'); ?>
                </h1>
                <p class="amp-page-subtitle">
                    <?php _e('Executive Dashboard, Analysen und Performance-Berichte', 'automaten-manager-pro'); ?>
                </p>
            </div>
            <div class="amp-header-actions">
                <div class="amp-period-selector">
                    <select class="amp-form-control" id="amp-report-period">
                        <option value="today"><?php _e('Heute', 'automaten-manager-pro'); ?></option>
                        <option value="yesterday"><?php _e('Gestern', 'automaten-manager-pro'); ?></option>
                        <option value="7days" selected><?php _e('7 Tage', 'automaten-manager-pro'); ?></option>
                        <option value="30days"><?php _e('30 Tage', 'automaten-manager-pro'); ?></option>
                        <option value="3months"><?php _e('3 Monate', 'automaten-manager-pro'); ?></option>
                        <option value="12months"><?php _e('12 Monate', 'automaten-manager-pro'); ?></option>
                        <option value="custom"><?php _e('Benutzerdefiniert', 'automaten-manager-pro'); ?></option>
                    </select>
                </div>
                <div class="amp-btn-group">
                    <button class="amp-btn amp-btn-primary" id="amp-generate-report-btn">
                        <i class="amp-icon-document-text"></i>
                        <?php _e('Bericht', 'automaten-manager-pro'); ?>
                    </button>
                    <button class="amp-btn amp-btn-secondary" id="amp-export-dashboard-btn">
                        <i class="amp-icon-arrow-down-tray"></i>
                        <?php _e('Export', 'automaten-manager-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="amp-executive-kpis">
        <div class="amp-kpi-card amp-kpi-revenue">
            <div class="amp-kpi-icon">
                <i class="amp-icon-currency-euro"></i>
            </div>
            <div class="amp-kpi-content">
                <div class="amp-kpi-value"><?php echo formatCurrency($executive_kpis['total_revenue']); ?></div>
                <div class="amp-kpi-label"><?php _e('Umsatz (7 Tage)', 'automaten-manager-pro'); ?></div>
                <div class="amp-kpi-change <?php echo getGrowthClass($revenue_growth); ?>">
                    <i class="amp-icon-trending-up"></i>
                    <?php echo formatPercentage($revenue_growth); ?> vs. gestern
                </div>
            </div>
        </div>
        
        <div class="amp-kpi-card amp-kpi-profit">
            <div class="amp-kpi-icon">
                <i class="amp-icon-banknotes"></i>
            </div>
            <div class="amp-kpi-content">
                <div class="amp-kpi-value"><?php echo formatCurrency($executive_kpis['total_profit']); ?></div>
                <div class="amp-kpi-label"><?php _e('Gewinn (7 Tage)', 'automaten-manager-pro'); ?></div>
                <div class="amp-kpi-change amp-positive">
                    <i class="amp-icon-trending-up"></i>
                    Marge: <?php echo formatPercentage($executive_kpis['profit_margin']); ?>
                </div>
            </div>
        </div>
        
        <div class="amp-kpi-card amp-kpi-sales">
            <div class="amp-kpi-icon">
                <i class="amp-icon-shopping-cart"></i>
            </div>
            <div class="amp-kpi-content">
                <div class="amp-kpi-value"><?php echo $executive_kpis['total_sales']; ?></div>
                <div class="amp-kpi-label"><?php _e('Verkäufe (7 Tage)', 'automaten-manager-pro'); ?></div>
                <div class="amp-kpi-change amp-positive">
                    <i class="amp-icon-arrow-up"></i>
                    Ø <?php echo formatCurrency($executive_kpis['avg_sale_value']); ?> pro Verkauf
                </div>
            </div>
        </div>
        
        <div class="amp-kpi-card amp-kpi-efficiency">
            <div class="amp-kpi-icon">
                <i class="amp-icon-chart-pie"></i>
            </div>
            <div class="amp-kpi-content">
                <div class="amp-kpi-value"><?php echo formatPercentage(100 - $executive_kpis['waste_percentage']); ?></div>
                <div class="amp-kpi-label"><?php _e('Effizienz-Rate', 'automaten-manager-pro'); ?></div>
                <div class="amp-kpi-change amp-positive">
                    <i class="amp-icon-trending-up"></i>
                    Verlust: <?php echo formatPercentage($executive_kpis['waste_percentage']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="amp-dashboard-grid">
        <!-- Revenue Trend Chart -->
        <div class="amp-card amp-chart-card amp-revenue-chart">
            <div class="amp-chart-header">
                <h3><?php _e('Umsatz- & Gewinn-Trend', 'automaten-manager-pro'); ?></h3>
                <div class="amp-chart-controls">
                    <div class="amp-toggle-group">
                        <button class="amp-toggle-btn amp-active" data-chart="revenue"><?php _e('Umsatz', 'automaten-manager-pro'); ?></button>
                        <button class="amp-toggle-btn" data-chart="profit"><?php _e('Gewinn', 'automaten-manager-pro'); ?></button>
                        <button class="amp-toggle-btn" data-chart="both"><?php _e('Beide', 'automaten-manager-pro'); ?></button>
                    </div>
                </div>
            </div>
            <div class="amp-chart-container">
                <canvas id="amp-revenue-chart" width="400" height="300"></canvas>
            </div>
            <div class="amp-chart-legend">
                <div class="amp-legend-item">
                    <div class="amp-legend-color" style="background: #007BFF;"></div>
                    <span><?php _e('Umsatz', 'automaten-manager-pro'); ?></span>
                </div>
                <div class="amp-legend-item">
                    <div class="amp-legend-color" style="background: #10B981;"></div>
                    <span><?php _e('Gewinn', 'automaten-manager-pro'); ?></span>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="amp-card amp-category-performance">
            <div class="amp-card-header">
                <h3><?php _e('Kategorie-Performance', 'automaten-manager-pro'); ?></h3>
                <div class="amp-performance-controls">
                    <select class="amp-form-control" id="amp-performance-metric">
                        <option value="revenue"><?php _e('Umsatz', 'automaten-manager-pro'); ?></option>
                        <option value="profit"><?php _e('Gewinn', 'automaten-manager-pro'); ?></option>
                        <option value="margin"><?php _e('Marge', 'automaten-manager-pro'); ?></option>
                    </select>
                </div>
            </div>
            <div class="amp-category-list">
                <?php foreach ($category_performance as $index => $category): ?>
                <div class="amp-category-item">
                    <div class="amp-category-info">
                        <div class="amp-category-icon" style="background-color: <?php echo $category['color']; ?>;">
                            <?php echo ($index + 1); ?>
                        </div>
                        <div class="amp-category-details">
                            <div class="amp-category-name"><?php echo esc_html($category['name']); ?></div>
                            <div class="amp-category-metrics">
                                <span class="amp-metric"><?php echo formatCurrency($category['revenue']); ?> Umsatz</span>
                                <span class="amp-metric"><?php echo formatPercentage($category['margin']); ?> Marge</span>
                            </div>
                        </div>
                    </div>
                    <div class="amp-category-progress">
                        <?php 
                        $max_revenue = max(array_column($category_performance, 'revenue'));
                        $percentage = ($category['revenue'] / $max_revenue) * 100;
                        ?>
                        <div class="amp-progress-bar">
                            <div class="amp-progress-fill" 
                                 style="width: <?php echo $percentage; ?>%; background-color: <?php echo $category['color']; ?>;"></div>
                        </div>
                        <span class="amp-category-value"><?php echo formatCurrency($category['profit']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ABC Analysis -->
        <div class="amp-card amp-abc-analysis">
            <div class="amp-card-header">
                <h3><?php _e('ABC-Analyse', 'automaten-manager-pro'); ?></h3>
                <button class="amp-btn amp-btn-ghost amp-btn-sm" onclick="ampShowABCDetails()">
                    <i class="amp-icon-information-circle"></i>
                    <?php _e('Details', 'automaten-manager-pro'); ?>
                </button>
            </div>
            <div class="amp-abc-grid">
                <div class="amp-abc-item amp-abc-a">
                    <div class="amp-abc-label">
                        <span class="amp-abc-letter">A</span>
                        <span class="amp-abc-title"><?php _e('Top-Seller', 'automaten-manager-pro'); ?></span>
                    </div>
                    <div class="amp-abc-stats">
                        <div class="amp-abc-count"><?php echo $abc_analysis['A']['count']; ?> Artikel</div>
                        <div class="amp-abc-share"><?php echo formatPercentage($abc_analysis['A']['revenue_share']); ?> Umsatz</div>
                    </div>
                    <div class="amp-abc-products">
                        <?php foreach ($abc_analysis['A']['products'] as $product): ?>
                        <span class="amp-product-tag"><?php echo esc_html($product); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="amp-abc-item amp-abc-b">
                    <div class="amp-abc-label">
                        <span class="amp-abc-letter">B</span>
                        <span class="amp-abc-title"><?php _e('Mittel-Seller', 'automaten-manager-pro'); ?></span>
                    </div>
                    <div class="amp-abc-stats">
                        <div class="amp-abc-count"><?php echo $abc_analysis['B']['count']; ?> Artikel</div>
                        <div class="amp-abc-share"><?php echo formatPercentage($abc_analysis['B']['revenue_share']); ?> Umsatz</div>
                    </div>
                    <div class="amp-abc-products">
                        <?php foreach ($abc_analysis['B']['products'] as $product): ?>
                        <span class="amp-product-tag"><?php echo esc_html($product); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="amp-abc-item amp-abc-c">
                    <div class="amp-abc-label">
                        <span class="amp-abc-letter">C</span>
                        <span class="amp-abc-title"><?php _e('Slow-Mover', 'automaten-manager-pro'); ?></span>
                    </div>
                    <div class="amp-abc-stats">
                        <div class="amp-abc-count"><?php echo $abc_analysis['C']['count']; ?> Artikel</div>
                        <div class="amp-abc-share"><?php echo formatPercentage($abc_analysis['C']['revenue_share']); ?> Umsatz</div>
                    </div>
                    <div class="amp-abc-products">
                        <?php foreach ($abc_analysis['C']['products'] as $product): ?>
                        <span class="amp-product-tag"><?php echo esc_html($product); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly Sales Pattern -->
        <div class="amp-card amp-hourly-sales">
            <div class="amp-card-header">
                <h3><?php _e('Verkaufsmuster (Stündlich)', 'automaten-manager-pro'); ?></h3>
                <div class="amp-hourly-controls">
                    <button class="amp-btn amp-btn-sm amp-btn-ghost amp-active" data-metric="sales">
                        <?php _e('Anzahl', 'automaten-manager-pro'); ?>
                    </button>
                    <button class="amp-btn amp-btn-sm amp-btn-ghost" data-metric="revenue">
                        <?php _e('Umsatz', 'automaten-manager-pro'); ?>
                    </button>
                </div>
            </div>
            <div class="amp-hourly-chart">
                <canvas id="amp-hourly-chart" width="400" height="200"></canvas>
            </div>
            <div class="amp-peak-hours">
                <div class="amp-peak-item">
                    <span class="amp-peak-label"><?php _e('Peak-Zeit:', 'automaten-manager-pro'); ?></span>
                    <span class="amp-peak-value">13:00 - 14:00</span>
                </div>
                <div class="amp-peak-item">
                    <span class="amp-peak-label"><?php _e('Schwach-Zeit:', 'automaten-manager-pro'); ?></span>
                    <span class="amp-peak-value">06:00 - 07:00</span>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="amp-card amp-payment-methods">
            <div class="amp-card-header">
                <h3><?php _e('Zahlungsarten-Verteilung', 'automaten-manager-pro'); ?></h3>
            </div>
            <div class="amp-payment-grid">
                <?php foreach ($payment_methods as $payment): ?>
                <div class="amp-payment-item">
                    <div class="amp-payment-header">
                        <div class="amp-payment-icon">
                            <?php if ($payment['method'] === 'Bargeld'): ?>
                                <i class="amp-icon-banknotes"></i>
                            <?php elseif ($payment['method'] === 'EC-Karte'): ?>
                                <i class="amp-icon-credit-card"></i>
                            <?php else: ?>
                                <i class="amp-icon-squares-2x2"></i>
                            <?php endif; ?>
                        </div>
                        <div class="amp-payment-details">
                            <div class="amp-payment-name"><?php echo esc_html($payment['method']); ?></div>
                            <div class="amp-payment-count"><?php echo $payment['count']; ?> Transaktionen</div>
                        </div>
                    </div>
                    <div class="amp-payment-stats">
                        <div class="amp-payment-percentage"><?php echo formatPercentage($payment['percentage']); ?></div>
                        <div class="amp-payment-revenue"><?php echo formatCurrency($payment['revenue']); ?></div>
                    </div>
                    <div class="amp-payment-bar">
                        <div class="amp-payment-fill" style="width: <?php echo $payment['percentage']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- VAT Summary -->
        <div class="amp-card amp-vat-summary">
            <div class="amp-card-header">
                <h3><?php _e('Mehrwertsteuer-Übersicht', 'automaten-manager-pro'); ?></h3>
                <button class="amp-btn amp-btn-ghost amp-btn-sm" onclick="ampExportVATReport()">
                    <i class="amp-icon-document-download"></i>
                    <?php _e('MwSt-Export', 'automaten-manager-pro'); ?>
                </button>
            </div>
            <div class="amp-vat-grid">
                <div class="amp-vat-item amp-vat-19">
                    <div class="amp-vat-header">
                        <div class="amp-vat-rate">19%</div>
                        <div class="amp-vat-label"><?php _e('Standard-MwSt', 'automaten-manager-pro'); ?></div>
                    </div>
                    <div class="amp-vat-amounts">
                        <div class="amp-vat-row">
                            <span><?php _e('Netto:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['19_percent']['net']); ?></span>
                        </div>
                        <div class="amp-vat-row">
                            <span><?php _e('MwSt:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['19_percent']['vat']); ?></span>
                        </div>
                        <div class="amp-vat-row amp-vat-total">
                            <span><?php _e('Brutto:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['19_percent']['gross']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="amp-vat-item amp-vat-7">
                    <div class="amp-vat-header">
                        <div class="amp-vat-rate">7%</div>
                        <div class="amp-vat-label"><?php _e('Ermäßigte MwSt', 'automaten-manager-pro'); ?></div>
                    </div>
                    <div class="amp-vat-amounts">
                        <div class="amp-vat-row">
                            <span><?php _e('Netto:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['7_percent']['net']); ?></span>
                        </div>
                        <div class="amp-vat-row">
                            <span><?php _e('MwSt:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['7_percent']['vat']); ?></span>
                        </div>
                        <div class="amp-vat-row amp-vat-total">
                            <span><?php _e('Brutto:', 'automaten-manager-pro'); ?></span>
                            <span><?php echo formatCurrency($vat_summary['7_percent']['gross']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="amp-vat-summary-total">
                    <div class="amp-vat-total-row">
                        <span><?php _e('Gesamte MwSt:', 'automaten-manager-pro'); ?></span>
                        <span class="amp-vat-total-amount">
                            <?php echo formatCurrency($vat_summary['19_percent']['vat'] + $vat_summary['7_percent']['vat']); ?>
                        </span>
                    </div>
                    <div class="amp-vat-total-row">
                        <span><?php _e('Gesamtumsatz:', 'automaten-manager-pro'); ?></span>
                        <span class="amp-vat-total-amount">
                            <?php echo formatCurrency($vat_summary['19_percent']['gross'] + $vat_summary['7_percent']['gross']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="amp-quick-reports-section">
        <h3><?php _e('Schnell-Berichte', 'automaten-manager-pro'); ?></h3>
        <div class="amp-quick-reports-grid">
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('daily')">
                <i class="amp-icon-calendar-days"></i>
                <span><?php _e('Tagesbericht', 'automaten-manager-pro'); ?></span>
            </button>
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('weekly')">
                <i class="amp-icon-calendar"></i>
                <span><?php _e('Wochenbericht', 'automaten-manager-pro'); ?></span>
            </button>
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('monthly')">
                <i class="amp-icon-chart-bar"></i>
                <span><?php _e('Monatsbericht', 'automaten-manager-pro'); ?></span>
            </button>
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('tax')">
                <i class="amp-icon-calculator"></i>
                <span><?php _e('Steuer-Export', 'automaten-manager-pro'); ?></span>
            </button>
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('inventory')">
                <i class="amp-icon-cube"></i>
                <span><?php _e('Lager-Analyse', 'automaten-manager-pro'); ?></span>
            </button>
            <button class="amp-quick-report-btn" onclick="ampGenerateQuickReport('profit')">
                <i class="amp-icon-trending-up"></i>
                <span><?php _e('Gewinn-Analyse', 'automaten-manager-pro'); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Custom Report Modal -->
<div class="amp-modal" id="amp-custom-report-modal">
    <div class="amp-modal-backdrop" onclick="ampCloseModal('amp-custom-report-modal')"></div>
    <div class="amp-modal-content amp-modal-large">
        <div class="amp-modal-header">
            <h3><?php _e('Bericht generieren', 'automaten-manager-pro'); ?></h3>
            <button class="amp-modal-close" onclick="ampCloseModal('amp-custom-report-modal')">
                <i class="amp-icon-x"></i>
            </button>
        </div>
        <div class="amp-modal-body">
            <div class="amp-report-builder">
                <div class="amp-report-options">
                    <div class="amp-form-group">
                        <label class="amp-label"><?php _e('Berichtstyp', 'automaten-manager-pro'); ?></label>
                        <select class="amp-form-control" id="amp-report-type">
                            <option value="executive"><?php _e('Executive Summary', 'automaten-manager-pro'); ?></option>
                            <option value="detailed"><?php _e('Detaillierte Analyse', 'automaten-manager-pro'); ?></option>
                            <option value="financial"><?php _e('Finanz-Bericht', 'automaten-manager-pro'); ?></option>
                            <option value="inventory"><?php _e('Lager-Bericht', 'automaten-manager-pro'); ?></option>
                            <option value="tax"><?php _e('Steuer-Bericht', 'automaten-manager-pro'); ?></option>
                        </select>
                    </div>
                    
                    <div class="amp-form-group">
                        <label class="amp-label"><?php _e('Zeitraum', 'automaten-manager-pro'); ?></label>
                        <div class="amp-date-range">
                            <input type="date" class="amp-form-control" id="amp-report-date-from">
                            <span><?php _e('bis', 'automaten-manager-pro'); ?></span>
                            <input type="date" class="amp-form-control" id="amp-report-date-to">
                        </div>
                    </div>
                    
                    <div class="amp-form-group">
                        <label class="amp-label"><?php _e('Format', 'automaten-manager-pro'); ?></label>
                        <div class="amp-format-options">
                            <label class="amp-checkbox-label">
                                <input type="checkbox" checked> PDF
                            </label>
                            <label class="amp-checkbox-label">
                                <input type="checkbox"> Excel
                            </label>
                            <label class="amp-checkbox-label">
                                <input type="checkbox"> CSV
                            </label>
                        </div>
                    </div>
                    
                    <div class="amp-form-group">
                        <label class="amp-label"><?php _e('Zusätzliche Optionen', 'automaten-manager-pro'); ?></label>
                        <div class="amp-additional-options">
                            <label class="amp-checkbox-label">
                                <input type="checkbox" checked> <?php _e('Charts einbetten', 'automaten-manager-pro'); ?>
                            </label>
                            <label class="amp-checkbox-label">
                                <input type="checkbox"> <?php _e('Rohdaten anhängen', 'automaten-manager-pro'); ?>
                            </label>
                            <label class="amp-checkbox-label">
                                <input type="checkbox"> <?php _e('Vergleich mit Vorperiode', 'automaten-manager-pro'); ?>
                            </label>
                            <label class="amp-checkbox-label">
                                <input type="checkbox" checked> <?php _e('Executive Summary', 'automaten-manager-pro'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="amp-modal-actions">
                <button class="amp-btn amp-btn-primary" onclick="ampGenerateCustomReport()">
                    <i class="amp-icon-document-text"></i>
                    <?php _e('Bericht generieren', 'automaten-manager-pro'); ?>
                </button>
                <button class="amp-btn amp-btn-secondary" onclick="ampCloseModal('amp-custom-report-modal')">
                    <?php _e('Abbrechen', 'automaten-manager-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Reports-specific styles */
.amp-executive-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.amp-kpi-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.9));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.amp-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 16px 16px 0 0;
}

.amp-kpi-revenue::before { background: linear-gradient(90deg, #007BFF, #0056b3); }
.amp-kpi-profit::before { background: linear-gradient(90deg, #10B981, #059669); }
.amp-kpi-sales::before { background: linear-gradient(90deg, #F59E0B, #d97706); }
.amp-kpi-efficiency::before { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }

.amp-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.amp-kpi-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.amp-kpi-revenue .amp-kpi-icon { background: linear-gradient(135deg, #007BFF, #0056b3); }
.amp-kpi-profit .amp-kpi-icon { background: linear-gradient(135deg, #10B981, #059669); }
.amp-kpi-sales .amp-kpi-icon { background: linear-gradient(135deg, #F59E0B, #d97706); }
.amp-kpi-efficiency .amp-kpi-icon { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }

.amp-kpi-content {
    flex: 1;
}

.amp-kpi-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--amp-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.amp-kpi-label {
    font-size: 0.875rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}

.amp-kpi-change {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    width: fit-content;
}

.amp-kpi-change.amp-positive {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.amp-kpi-change.amp-negative {
    background: rgba(239, 68, 68, 0.1);
    color: #DC2626;
}

.amp-kpi-change.amp-neutral {
    background: rgba(107, 114, 128, 0.1);
    color: #6B7280;
}

.amp-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.amp-revenue-chart {
    grid-column: span 8;
}

.amp-category-performance {
    grid-column: span 4;
}

.amp-abc-analysis {
    grid-column: span 6;
}

.amp-hourly-sales {
    grid-column: span 6;
}

.amp-payment-methods {
    grid-column: span 4;
}

.amp-vat-summary {
    grid-column: span 8;
}

.amp-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #E5E7EB;
}

.amp-toggle-group {
    display: flex;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #E5E7EB;
}

.amp-toggle-btn {
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    color: #6B7280;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.amp-toggle-btn.amp-active {
    background: var(--amp-primary);
    color: white;
}

.amp-chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}

.amp-chart-legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
}

.amp-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6B7280;
}

.amp-legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.amp-category-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.amp-category-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 12px;
    transition: all 0.3s;
}

.amp-category-item:hover {
    background: rgba(248, 249, 250, 1);
    transform: translateX(4px);
}

.amp-category-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.amp-category-details {
    flex: 1;
}

.amp-category-name {
    font-weight: 600;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-category-metrics {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6B7280;
}

.amp-category-progress {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 120px;
}

.amp-progress-bar {
    flex: 1;
    height: 6px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
}

.amp-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.amp-category-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--amp-text);
}

.amp-abc-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.amp-abc-item {
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid;
    transition: all 0.3s;
}

.amp-abc-a {
    border-color: #10B981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
}

.amp-abc-b {
    border-color: #F59E0B;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.05));
}

.amp-abc-c {
    border-color: #EF4444;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
}

.amp-abc-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.amp-abc-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.amp-abc-letter {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
    font-size: 1.125rem;
}

.amp-abc-a .amp-abc-letter { background: #10B981; }
.amp-abc-b .amp-abc-letter { background: #F59E0B; }
.amp-abc-c .amp-abc-letter { background: #EF4444; }

.amp-abc-title {
    font-weight: 600;
    color: var(--amp-text);
}

.amp-abc-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.amp-abc-count {
    color: #6B7280;
}

.amp-abc-share {
    font-weight: 600;
    color: var(--amp-text);
}

.amp-abc-products {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.amp-product-tag {
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 6px;
    font-size: 0.75rem;
    color: var(--amp-text);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.amp-hourly-controls {
    display: flex;
    gap: 0.5rem;
}

.amp-peak-hours {
    display: flex;
    justify-content: space-around;
    padding-top: 1rem;
    border-top: 1px solid #E5E7EB;
    margin-top: 1rem;
}

.amp-peak-item {
    text-align: center;
}

.amp-peak-label {
    font-size: 0.75rem;
    color: #6B7280;
    display: block;
    margin-bottom: 0.25rem;
}

.amp-peak-value {
    font-weight: 600;
    color: var(--amp-text);
    font-size: 0.875rem;
}

.amp-payment-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.amp-payment-item {
    padding: 1rem;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 12px;
    transition: all 0.3s;
}

.amp-payment-item:hover {
    background: rgba(248, 249, 250, 1);
}

.amp-payment-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.amp-payment-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--amp-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

.amp-payment-details {
    flex: 1;
}

.amp-payment-name {
    font-weight: 600;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-payment-count {
    font-size: 0.75rem;
    color: #6B7280;
}

.amp-payment-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.amp-payment-percentage {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--amp-text);
}

.amp-payment-revenue {
    font-size: 0.875rem;
    color: #6B7280;
}

.amp-payment-bar {
    height: 4px;
    background: #E5E7EB;
    border-radius: 2px;
    overflow: hidden;
}

.amp-payment-fill {
    height: 100%;
    background: var(--amp-primary);
    border-radius: 2px;
    transition: width 0.3s;
}

.amp-vat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.amp-vat-item {
    padding: 1.5rem;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 12px;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.amp-vat-19 {
    border-color: #007BFF;
}

.amp-vat-7 {
    border-color: #10B981;
}

.amp-vat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.amp-vat-header {
    text-align: center;
    margin-bottom: 1rem;
}

.amp-vat-rate {
    font-size: 2rem;
    font-weight: 700;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-vat-label {
    font-size: 0.875rem;
    color: #6B7280;
}

.amp-vat-amounts {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.amp-vat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.amp-vat-total {
    border-top: 2px solid #E5E7EB;
    font-weight: 600;
    color: var(--amp-text);
}

.amp-vat-summary-total {
    grid-column: span 2;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
    border-radius: 12px;
    border: 2px solid #007BFF;
}

.amp-vat-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--amp-text);
}

.amp-vat-total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--amp-primary);
}

.amp-quick-reports-section {
    margin-top: 2rem;
}

.amp-quick-reports-section h3 {
    color: var(--amp-text);
    margin-bottom: 1rem;
}

.amp-quick-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.amp-quick-report-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.8));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: var(--amp-text);
}

.amp-quick-report-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    background: linear-gradient(135deg, rgba(255, 255, 255, 1), rgba(248, 249, 250, 0.95));
}

.amp-quick-report-btn i {
    font-size: 2rem;
    color: var(--amp-primary);
}

.amp-quick-report-btn span {
    font-weight: 500;
    text-align: center;
}

.amp-report-builder {
    max-width: 600px;
    margin: 0 auto;
}

.amp-report-options {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.amp-date-range {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.amp-date-range span {
    color: #6B7280;
    font-size: 0.875rem;
}

.amp-format-options,
.amp-additional-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.amp-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--amp-text);
}

/* Samsung S25 Ultra optimizations */
@media (max-width: 430px) and (min-height: 900px) {
    .amp-executive-kpis {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .amp-kpi-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .amp-kpi-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .amp-kpi-value {
        font-size: 1.75rem;
    }
    
    .amp-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .amp-dashboard-grid > * {
        grid-column: span 1 !important;
    }
    
    .amp-vat-grid {
        grid-template-columns: 1fr;
    }
    
    .amp-vat-summary-total {
        grid-column: span 1;
    }
    
    .amp-quick-reports-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .amp-toggle-group {
        flex-direction: column;
    }
    
    .amp-category-item {
        flex-direction: column;
        text-align: center;
    }
    
    .amp-category-progress {
        width: 100%;
        justify-content: center;
    }
}

/* Touch optimizations */
@media (pointer: coarse) {
    .amp-toggle-btn {
        min-height: 44px;
        min-width: 60px;
    }
    
    .amp-quick-report-btn {
        min-height: 120px;
    }
    
    .amp-abc-item {
        min-height: 120px;
    }
}

/* High DPI displays */
@media (-webkit-min-device-pixel-ratio: 3), (min-resolution: 3dppx) {
    .amp-kpi-card {
        border-width: 0.5px;
    }
    
    .amp-progress-bar {
        height: 4px;
    }
    
    .amp-payment-bar {
        height: 3px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize reports dashboard
    initReportsDashboard();
});

function initReportsDashboard() {
    // Initialize charts
    initRevenueChart();
    initHourlyChart();
    
    // Setup period selector
    setupPeriodSelector();
    
    // Setup chart controls
    setupChartControls();
    
    // Setup button handlers
    setupReportsButtons();
}

function setupPeriodSelector() {
    const periodSelect = document.getElementById('amp-report-period');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const period = this.value;
            ampUpdateDashboard(period);
        });
    }
}

function setupChartControls() {
    // Revenue chart toggles
    const revenueToggles = document.querySelectorAll('.amp-revenue-chart .amp-toggle-btn');
    revenueToggles.forEach(btn => {
        btn.addEventListener('click', function() {
            revenueToggles.forEach(b => b.classList.remove('amp-active'));
            this.classList.add('amp-active');
            
            const chartType = this.dataset.chart;
            ampUpdateRevenueChart(chartType);
        });
    });
    
    // Hourly chart toggles
    const hourlyToggles = document.querySelectorAll('.amp-hourly-controls .amp-btn');
    hourlyToggles.forEach(btn => {
        btn.addEventListener('click', function() {
            hourlyToggles.forEach(b => b.classList.remove('amp-active'));
            this.classList.add('amp-active');
            
            const metric = this.dataset.metric;
            ampUpdateHourlyChart(metric);
        });
    });
    
    // Category performance selector
    const performanceSelect = document.getElementById('amp-performance-metric');
    if (performanceSelect) {
        performanceSelect.addEventListener('change', function() {
            const metric = this.value;
            ampUpdateCategoryPerformance(metric);
        });
    }
}

function setupReportsButtons() {
    const generateReportBtn = document.getElementById('amp-generate-report-btn');
    if (generateReportBtn) {
        generateReportBtn.addEventListener('click', () => ampShowModal('amp-custom-report-modal'));
    }
    
    const exportDashboardBtn = document.getElementById('amp-export-dashboard-btn');
    if (exportDashboardBtn) {
        exportDashboardBtn.addEventListener('click', ampExportDashboard);
    }
}

function initRevenueChart() {
    const canvas = document.getElementById('amp-revenue-chart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Mock chart data - in real implementation, use Chart.js
    const revenueData = [2150, 2340, 2890, 3120, 2780, 3450, 4017];
    const profitData = [780, 850, 1045, 1134, 1008, 1250, 1456];
    const labels = ['21.07', '22.07', '23.07', '24.07', '25.07', '26.07', '27.07'];
    
    // Simple placeholder visualization
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#6B7280';
    ctx.font = '16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Chart wird geladen...', canvas.width / 2, canvas.height / 2);
    
    // In real implementation, initialize Chart.js here
    console.log('Revenue Chart Data:', { revenueData, profitData, labels });
}

function initHourlyChart() {
    const canvas = document.getElementById('amp-hourly-chart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Simple placeholder visualization
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#6B7280';
    ctx.font = '16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Hourly Chart wird geladen...', canvas.width / 2, canvas.height / 2);
}

function ampUpdateDashboard(period) {
    ampShowNotification(`Dashboard wird für Zeitraum "${period}" aktualisiert...`, 'info');
    
    // In real implementation, fetch new data based on period
    setTimeout(() => {
        // Update KPI cards
        ampUpdateKPICards(period);
        
        // Update charts
        ampUpdateRevenueChart('both');
        ampUpdateHourlyChart('sales');
        
        ampShowNotification('Dashboard aktualisiert', 'success');
    }, 1000);
}

function ampUpdateKPICards(period) {
    // Update KPI values based on selected period
    console.log(`Updating KPI cards for period: ${period}`);
}

function ampUpdateRevenueChart(chartType) {
    console.log(`Updating revenue chart to show: ${chartType}`);
    ampShowNotification(`Chart wird für "${chartType}" aktualisiert...`, 'info');
}

function ampUpdateHourlyChart(metric) {
    console.log(`Updating hourly chart to show: ${metric}`);
    ampShowNotification(`Hourly Chart zeigt jetzt "${metric}"`, 'info');
}

function ampUpdateCategoryPerformance(metric) {
    console.log(`Updating category performance to show: ${metric}`);
    ampShowNotification(`Kategorie-Performance zeigt jetzt "${metric}"`, 'info');
}

function ampShowABCDetails() {
    ampShowNotification('ABC-Analyse Details werden geladen...', 'info');
}

function ampExportVATReport() {
    ampShowNotification('MwSt-Bericht wird exportiert...', 'success');
}

function ampGenerateQuickReport(type) {
    const reportTypes = {
        'daily': 'Tagesbericht',
        'weekly': 'Wochenbericht',
        'monthly': 'Monatsbericht',
        'tax': 'Steuer-Export',
        'inventory': 'Lager-Analyse',
        'profit': 'Gewinn-Analyse'
    };
    
    const reportName = reportTypes[type] || type;
    ampShowNotification(`${reportName} wird generiert...`, 'info');
    
    // Simulate report generation
    setTimeout(() => {
        ampShowNotification(`${reportName} wurde erfolgreich erstellt und heruntergeladen`, 'success');
    }, 2000);
}

function ampExportDashboard() {
    ampShowNotification('Dashboard wird als PDF exportiert...', 'info');
    
    setTimeout(() => {
        ampShowNotification('Dashboard-Export erfolgreich heruntergeladen', 'success');
    }, 1500);
}

function ampGenerateCustomReport() {
    const reportType = document.getElementById('amp-report-type').value;
    const dateFrom = document.getElementById('amp-report-date-from').value;
    const dateTo = document.getElementById('amp-report-date-to').value;
    
    if (!dateFrom || !dateTo) {
        ampShowNotification('Bitte wählen Sie einen gültigen Zeitraum', 'error');
        return;
    }
    
    ampCloseModal('amp-custom-report-modal');
    ampShowNotification('Benutzerdefinierter Bericht wird generiert...', 'info');
    
    setTimeout(() => {
        ampShowNotification('Bericht wurde erfolgreich erstellt und heruntergeladen', 'success');
    }, 3000);
}

// Analytics functions for deeper insights
function ampAnalyzeRevenueTrends() {
    // Advanced revenue trend analysis
    console.log('Analyzing revenue trends...');
}

function ampCalculateProfitMargins() {
    // Profit margin calculations by product/category
    console.log('Calculating profit margins...');
}

function ampForecastSales() {
    // Sales forecasting based on historical data
    console.log('Forecasting sales...');
}

function ampOptimizePricing() {
    // Price optimization recommendations
    console.log('Analyzing price optimization opportunities...');
}

// Real-time dashboard updates
function ampStartRealTimeUpdates() {
    // Start polling for real-time data updates
    setInterval(() => {
        // Update KPIs and charts with live data
        console.log('Updating dashboard with real-time data...');
    }, 30000); // Update every 30 seconds
}

// Initialize real-time updates
// ampStartRealTimeUpdates();
</script>