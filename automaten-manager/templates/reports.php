<?php
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <style>
    .automaten-reports {
        max-width: 1400px;
        margin: 0;
        padding: 20px 0;
    }
    
    .automaten-page-header {
        margin-bottom: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .automaten-page-title {
        font-size: 2.5rem;
        margin: 0 0 10px 0;
        font-weight: 700;
    }
    
    .automaten-page-subtitle {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
    }
    
    .automaten-coming-soon {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #e1e5e9;
    }
    
    .automaten-coming-soon-icon {
        font-size: 5rem;
        color: #667eea;
        margin-bottom: 30px;
    }
    
    .automaten-coming-soon h2 {
        font-size: 2rem;
        color: #495057;
        margin-bottom: 15px;
    }
    
    .automaten-coming-soon p {
        font-size: 1.1rem;
        color: #6c757d;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .automaten-features-preview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 40px;
    }
    
    .automaten-feature-card {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .automaten-feature-card:hover {
        border-color: #667eea;
        transform: translateY(-3px);
    }
    
    .automaten-feature-icon {
        font-size: 2.5rem;
        color: #667eea;
        margin-bottom: 15px;
    }
    
    .automaten-feature-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 10px;
    }
    
    .automaten-feature-description {
        font-size: 0.9rem;
        color: #6c757d;
        line-height: 1.5;
    }
    </style>

    <div class="automaten-reports">
        <!-- Page Header -->
        <div class="automaten-page-header">
            <h1 class="automaten-page-title">
                <i class="fas fa-chart-line"></i>
                Berichte & Analysen
            </h1>
            <p class="automaten-page-subtitle">
                Detaillierte Einblicke in Ihre Automaten-Performance
            </p>
        </div>

        <!-- Coming Soon Content -->
        <div class="automaten-coming-soon">
            <div class="automaten-coming-soon-icon">
                <i class="fas fa-rocket"></i>
            </div>
            
            <h2>Bald verfügbar!</h2>
            <p>
                Das Berichte-Modul befindet sich in der Entwicklung. Hier werden Sie bald umfangreiche 
                Analysen und Reports zu Ihren Automaten-Daten finden.
            </p>
            
            <!-- Features Preview -->
            <div class="automaten-features-preview">
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="automaten-feature-title">Verkaufsstatistiken</h3>
                    <p class="automaten-feature-description">
                        Detaillierte Verkaufsanalysen nach Produkten, Kategorien und Zeiträumen
                    </p>
                </div>
                
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 class="automaten-feature-title">Lagerberichte</h3>
                    <p class="automaten-feature-description">
                        Bestandsanalysen, Umschlagshäufigkeit und Nachbestellungsempfehlungen
                    </p>
                </div>
                
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <h3 class="automaten-feature-title">Umsatzanalysen</h3>
                    <p class="automaten-feature-description">
                        Gewinn- und Verlustrechnung, Marginalanalysen und ROI-Berechnungen
                    </p>
                </div>
                
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="automaten-feature-title">Zeitanalysen</h3>
                    <p class="automaten-feature-description">
                        Verkaufstrends nach Tageszeiten, Wochentagen und saisonalen Mustern
                    </p>
                </div>
                
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="automaten-feature-title">Beliebtheitsranking</h3>
                    <p class="automaten-feature-description">
                        Top-Performer und Flop-Produkte mit Handlungsempfehlungen
                    </p>
                </div>
                
                <div class="automaten-feature-card">
                    <div class="automaten-feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="automaten-feature-title">Export-Funktionen</h3>
                    <p class="automaten-feature-description">
                        PDF-Reports, Excel-Exporte und automatische E-Mail-Berichte
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>