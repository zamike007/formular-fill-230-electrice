<?php
/**
 * Plugin Name: Formular Fill - PDF Form Generator
 * Plugin URI: https://example.com/
 * Description: Plugin that generates Form 230 PDF by filling form data from user input
 * Version: 1.0.0
 * Author: Developer
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('FORMULAR_FILL_VERSION', '1.0.0');
define('FORMULAR_FILL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FORMULAR_FILL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Include TCPDF library if not already loaded
 */
function formular_fill_include_tcpdf() {
    if (!class_exists('TCPDF')) {
        require_once FORMULAR_FILL_PLUGIN_DIR . 'tcpdf/tcpdf.php';
    }
}

/**
 * PDF Field Coordinates Configuration
 * These coordinates will need to be adjusted based on your PDF template
 * Format: 'field_name' => array(x, y, width, height, font_size)
 * Coordinates are in mm for A4 size (210 x 297 mm)
 * 
 * IMPORTANT: You will need to adjust these coordinates based on your specific PDF template
 * The coordinates represent where each field should be placed on the PDF
 */
function formular_fill_get_pdf_coordinates() {
    return apply_filters('formular_fill_pdf_coordinates', array(
        // Contributor details (Date de identificare a contribuabilului)
        'nume'           => array(30, 45, 80, 8, 10),
        'prenume'        => array(110, 45, 80, 8, 10),
        'initiala'       => array(30, 55, 20, 8, 10),
        'cnp'            => array(50, 55, 60, 8, 10),
        'mail'           => array(110, 55, 70, 8, 10),
        
        // Address (Adresa de domiciliu)
        'judet'          => array(30, 70, 80, 8, 10),
        'localitate'     => array(110, 70, 80, 8, 10),
        'zip'            => array(30, 80, 30, 8, 10),
        'strada'         => array(65, 80, 80, 8, 10),
        'nr'             => array(150, 80, 25, 8, 10),
        'bloc'           => array(30, 90, 25, 8, 10),
        'scara'          => array(60, 90, 25, 8, 10),
        'etaj'           => array(90, 90, 25, 8, 10),
        'apartament'     => array(120, 90, 30, 8, 10),
        
        // Period (Perioada redirectionarii)
        'perioada'       => array(30, 110, 8, 8, 0),
        
        // Target organization details
        'target_name'    => array(30, 130, 150, 8, 10),
        'target_cif'     => array(30, 140, 60, 8, 10),
        'target_iban'    => array(90, 140, 100, 8, 10),
        
        // Year
        'an'             => array(30, 150, 30, 8, 10),
        
        // Signature
        'semnatura'      => array(30, 200, 100, 30, 0),
        
        // Agreement checkbox
        'acord_date'     => array(30, 235, 8, 8, 0),
    ));
}

/**
 * Get counties list (Romania)
 */
function formular_fill_get_counties() {
    return array(
        1 => 'Alba',
        2 => 'Arad',
        3 => 'Argeș',
        4 => 'Bacău',
        5 => 'Bihor',
        6 => 'Bistrița-Năsăud',
        7 => 'Botoșani',
        8 => 'Brăila',
        9 => 'Brașov',
        10 => 'București',
        11 => 'Buzău',
        12 => 'Călărași',
        13 => 'Caraș-Severin',
        14 => 'Cluj',
        15 => 'Constanța',
        16 => 'Covasna',
        17 => 'Dâmbovița',
        18 => 'Dolj',
        19 => 'Galați',
        20 => 'Giurgiu',
        21 => 'Gorj',
        22 => 'Harghita',
        23 => 'Hunedoara',
        24 => 'Ialomița',
        25 => 'Iași',
        26 => 'Ilfov',
        27 => 'Maramureș',
        28 => 'Mehedinți',
        29 => 'Mureș',
        30 => 'Neamț',
        31 => 'Olt',
        32 => 'Prahova',
        33 => 'Sălaj',
        34 => 'Satu Mare',
        35 => 'Sibiu',
        36 => 'Suceava',
        37 => 'Teleorman',
        38 => 'Timiș',
        39 => 'Tulcea',
        40 => 'Vâlcea',
        41 => 'Vaslui',
        42 => 'Vrancea',
    );
}

/**
 * Get counties as text for display
 */
function formular_fill_get_counties_text() {
    $counties = formular_fill_get_counties();
    $text_counties = array();
    foreach ($counties as $id => $name) {
        $text_counties[$name] = $name;
    }
    return $text_counties;
}

/**
 * Generate unique ID for form
 */
function formular_fill_generate_form_id() {
    return 'ff_' . wp_generate_password(12, false, false);
}

/**
 * Enqueue plugin scripts and styles
 */
function formular_fill_enqueue_scripts() {
    // Add FontAwesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    wp_enqueue_style('formular-fill-style', FORMULAR_FILL_PLUGIN_URL . 'css/formular-fill.css', array(), FORMULAR_FILL_VERSION);
    wp_enqueue_script('formular-fill-script', FORMULAR_FILL_PLUGIN_URL . 'js/formular-fill.js', array('jquery'), FORMULAR_FILL_VERSION, true);
    
    wp_localize_script('formular-fill-script', 'formularFill', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('formular_fill_nonce'),
        'plugin_url' => FORMULAR_FILL_PLUGIN_URL,
    ));
}
add_action('wp_enqueue_scripts', 'formular_fill_enqueue_scripts');

/**
 * Handle form submission via AJAX
 */
function formular_fill_handle_submit() {
    check_ajax_referer('formular_fill_nonce', 'nonce');
    
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    
    if (empty($form_id)) {
        wp_send_json_error(array('message' => 'Invalid form ID'));
    }
    
    // Get form data
    $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
    
    // Validate required fields
    $required_fields = array('nume', 'prenume', 'cnp', 'judet', 'localitate', 'strada', 'nr', 'acord_date', 'gdpr');
    $errors = array();
    
    foreach ($required_fields as $field) {
        if (!isset($form_data[$field]) || empty(trim($form_data[$field]))) {
            $errors[] = 'Câmpul obligatoriu lipsește: ' . $field;
        }
    }
    
    if (!empty($errors)) {
        wp_send_json_error(array('message' => implode(', ', $errors)));
    }
    
    // Generate PDF
    try {
        $pdf_path = formular_fill_generate_pdf($form_data, $form_id);
        
        if ($pdf_path && file_exists($pdf_path)) {
            wp_send_json_success(array(
                'pdf_url' => FORMULAR_FILL_PLUGIN_URL . 'download.php?file=' . basename($pdf_path),
                'message' => 'PDF generat cu succes!'
            ));
        } else {
            wp_send_json_error(array('message' => 'Eroare la generarea PDF-ului'));
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Eroare: ' . $e->getMessage()));
    }
}
add_action('wp_ajax_formular_fill_submit', 'formular_fill_handle_submit');
add_action('wp_ajax_nopriv_formular_fill_submit', 'formular_fill_handle_submit');

/**
 * Generate PDF from form data
 */
function formular_fill_generate_pdf($form_data, $form_id) {
    formular_fill_include_tcpdf();
    
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Formular Fill Plugin');
    $pdf->SetAuthor('WordPress');
    $pdf->SetTitle('Formular 230');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Get coordinates
    $coords = formular_fill_get_pdf_coordinates();
    
    // Get counties for converting county ID to name
    $counties = formular_fill_get_counties();
    
    // Set font
    $pdf->SetFont('helvetica', '', 11);
    
    // Fill in the form fields
    foreach ($form_data as $key => $value) {
        if (isset($coords[$key]) && !empty($value)) {
            $x = $coords[$key][0];
            $y = $coords[$key][1];
            $w = isset($coords[$key][2]) ? $coords[$key][2] : 0;
            $h = isset($coords[$key][3]) ? $coords[$key][3] : 0;
            $font_size = isset($coords[$key][4]) ? $coords[$key][4] : 10;
            
            if ($font_size > 0) {
                $pdf->SetFontSize($font_size);
            }
            
            // Convert county ID to county name for PDF
            if ($key === 'judet' && is_numeric($value) && isset($counties[$value])) {
                $value = $counties[$value];
            }
            
            switch ($key) {
                case 'semnatura':
                    // Handle signature (base64 image)
                    if (strpos($value, 'data:image') !== false) {
                        $img_data = str_replace('data:image/png;base64,', '', $value);
                        $img_data = str_replace(' ', '+', $img_data);
                        $img_decoded = base64_decode($img_data);
                        
                        if ($img_decoded) {
                            $temp_file = tempnam(sys_get_temp_dir(), 'sig_');
                            file_put_contents($temp_file, $img_decoded);
                            $pdf->Image($temp_file, $x, $y, $w, $h);
                            @unlink($temp_file);
                        }
                    }
                    break;
                    
                case 'perioada':
                    // Radio button - 1 = un an, 2 = doi ani
                    if ($value == '1') {
                        $pdf->Rect($x, $y, 4, 4, 'F');
                    } elseif ($value == '2') {
                        $pdf->Rect($x + 20, $y, 4, 4, 'F');
                    }
                    break;
                    
                case 'acord_date':
                    // Checkbox handling
                    if ($value == '1' || $value === true) {
                        $pdf->Rect($x, $y, 4, 4, 'F');
                    }
                    break;
                    
                default:
                    // Regular text field
                    $pdf->SetXY($x, $y);
                    $pdf->Cell($w, $h, $value, 0, 0, 'L');
                    break;
            }
        }
    }
    
    // Save PDF
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/formular-fill';
    
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }
    
    $filename = 'formular_230_' . $form_id . '_' . time() . '.pdf';
    $filepath = $pdf_dir . '/' . $filename;
    
    $pdf->Output($filepath, 'F');
    
    return $filepath;
}

/**
 * Shortcode callback function
 */
function formular_fill_shortcode($atts) {
    $atts = shortcode_atts(array(
        'pdf_template' => '', // Path to custom PDF template
        'target_name' => '',
        'target_iban' => '',
        'target_cif' => '',
        'logo' => '',
        'title' => '',
    ), $atts);
    
    // Generate unique form ID
    $form_id = formular_fill_generate_form_id();
    
    // Get counties for dropdown
    $counties = formular_fill_get_counties();
    
    // Start output buffering
    ob_start();
    
    ?>
    <div class="wer-pop-body">
        <div class="wer-pdf-formular" id="<?php echo esc_attr($form_id); ?>">
            <form id="formular-fill-form-<?php echo esc_attr($form_id); ?>" class="formular-fill-form" method="post">
                <!-- Hidden fields from original form -->
                <?php if (!empty($atts['target_name'])): ?>
                <input type="hidden" class="wer-input" name="target_name" value="<?php echo esc_attr($atts['target_name']); ?>">
                <?php endif; ?>
                <?php if (!empty($atts['target_iban'])): ?>
                <input type="hidden" class="wer-input" name="target_iban" value="<?php echo esc_attr($atts['target_iban']); ?>">
                <?php endif; ?>
                <?php if (!empty($atts['target_cif'])): ?>
                <input type="hidden" class="wer-input" name="target_cif" value="<?php echo esc_attr($atts['target_cif']); ?>">
                <?php endif; ?>
                <input type="hidden" class="wer-input" name="an" value="<?php echo esc_attr(date('Y')); ?>">
                <input type="hidden" class="wer-input" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                
                <!-- Form Title -->
                <?php if (!empty($atts['title'])): ?>
                <h2 class="ttc-primary f230-nume-ong"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <p class="f230-instructiuni">
                    Completează, semnează și trimite formularul 230 online.
                    <br>Echipa organizației se va ocupa să depună formularul la ANAF.
                </p>
                
                <div class="f230-subtitle">
                    Date de identificare a contribuabilului
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group tf-3">
                        <input type="text" class="wer-input" name="nume" data-r="1" data-len="100" value="">
                        <label>Nume *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group tf-3">
                        <input type="text" class="wer-input" name="prenume" data-r="1" data-len="100" value="">
                        <label>Prenume *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group tf-2">
                        <input type="text" class="wer-input" name="initiala" data-len="1" value="">
                        <label>Inițială Tată (opțional)</label>
                    </div>
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group tf-3">
                        <input type="text" class="wer-input" name="cnp" data-r="1" data-len="15" value="">
                        <label>CNP / NIF *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group tf-3">
                        <input type="email" class="wer-input" name="mail" data-len="50" value="">
                        <label>Email</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group tf-2">
                        <input type="text" class="wer-input" name="telefon" data-len="30" value="">
                        <label>Telefon (opțional)</label>
                    </div>
                </div>
                
                <div class="f230-subtitle">
                    Adresa de domiciliu (așa cum apare în documentul de identitate)
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group">
                        <select data-r="1" class="wer-jud-loc wer-input wer-gray-empty" data-pass-change="1" name="judet" id="judet-<?php echo esc_attr($form_id); ?>">
                            <option value="">Județ* (alege)</option>
                            <?php foreach ($counties as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="localitate" data-r="1" data-len="50" value="" id="localitate-<?php echo esc_attr($form_id); ?>">
                        <label>Localitate *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="zip" data-len="15" value="">
                        <label>Cod poștal</label>
                    </div>
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="strada" data-r="1" data-len="70" value="">
                        <label>Strada *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="nr" data-r="1" data-len="6" value="">
                        <label>Nr *</label>
                        <div class="wer-i-msg"></div>
                    </div>
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="bloc" data-len="10" value="">
                        <label>Bloc</label>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="scara" data-len="8" value="">
                        <label>Scara</label>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="etaj" data-len="8" value="">
                        <label>Etaj</label>
                    </div>
                    <div class="wer-input-group">
                        <input type="text" class="wer-input" name="apartament" data-len="15" value="">
                        <label>Apartament</label>
                    </div>
                </div>
                
                <div class="f230-subtitle">
                    Perioada redirecționării
                </div>
                
                <div class="wer-input-row f230-checkboxes">
                    <div class="wer-input-group mt2">
                        <input class="wer-input ignore-leave wer-valid hasval" type="radio" name="perioada" value="1" checked id="un_an-<?php echo esc_attr($form_id); ?>">
                        <label class="tt-font-11 tt-bold-600" for="un_an-<?php echo esc_attr($form_id); ?>">Un an</label>
                    </div>
                    <div class="wer-input-group mt2">
                        <input class="wer-input ignore-leave" type="radio" name="perioada" value="2" id="doi_ani-<?php echo esc_attr($form_id); ?>">
                        <label class="tt-font-11 tt-bold-600" for="doi_ani-<?php echo esc_attr($form_id); ?>">Doi ani</label>
                    </div>
                </div>
                
                <div class="f230-subtitle">
                    Semnătură olograf *
                </div>
                <p class="f230-semnatura-instructiuni">(Semnează olograf / de mână cât mai centrat pe linie pentru o încadrare bună. Folosește touchscreen-ul de pe telefon, tabletă, laptop etc.)</p>
                
                <div class="wer-input-group wer-bool-group">
                    <input type="checkbox" name="acord_date" value="1" class="wer-input" id="acord_date-<?php echo esc_attr($form_id); ?>" data-r="1">
                    <label for="acord_date-<?php echo esc_attr($form_id); ?>">Sunt de acord ca datele de identificare (nume, prenume și cod numeric personal/număr de identificare fiscală), precum și suma direcționată să fie comunicate entității beneficiare.</label>
                </div>
                
                <div class="wer-sign" data-clear="1" style="width:100%" data-ratio="0.6">
                    <input class="wer-input ignore-leave" name="semnatura" type="hidden" value="">
                    <div class="wer-sign-controls">
                        <div class="tf-grow tt-l tt-bold-600 wer-signhere-title">Semnează în chenar</div>
                        <div class="tt-but tt-danger wer-sign-clear-but" style="order: 5;">Semnătură nouă</div>
                    </div>
                    <canvas width="337" height="202" style="height: 202px;"></canvas>
                </div>
                
                <div class="tt-l" style="padding: 1em 0 1em 0.22em;">
                    <div class="f230-big tt-but tt-washy wer-pdf-preview ml4" id="preview-btn-<?php echo esc_attr($form_id); ?>">
                        <i class="fa fa-eye ttc-primary" aria-hidden="true"></i> Previzualizează
                    </div>
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group pdl0">
                        <input class="wer-input" type="checkbox" name="gdpr" data-r="1" id="gdpr-<?php echo esc_attr($form_id); ?>">
                        <label for="gdpr-<?php echo esc_attr($form_id); ?>">Sunt de acord cu
                            <a class="ttc-primary tt-bold-600 tt-font-11" href="/termeni-si-conditii/" target="_blank">Termenii de utilizare</a>, 
                            <a class="ttc-primary tt-bold-600 tt-font-11" href="/politica-de-confidentialitate/" target="_blank">politica de confidențialitate (GDPR)</a>
                             și 
                            <a class="ttc-primary tt-bold-600 tt-font-11" href="/cookies/" target="_blank">Cookies</a>
                         *
                        </label>
                        <div class="wer-i-msg"></div>
                    </div>
                </div>
                
                <div class="wer-input-row">
                    <div class="wer-input-group pdl0">
                        <input class="wer-input" type="checkbox" name="newsletter" id="newsletter-<?php echo esc_attr($form_id); ?>">
                        <label for="newsletter-<?php echo esc_attr($form_id); ?>">Doresc să primesc notificări</label>
                    </div>
                </div>
                
                <!-- Submit button -->
                <div class="wer-submit-row">
                    <button type="submit" class="wer-submit-btn" id="submit-btn-<?php echo esc_attr($form_id); ?>">
                        Generează PDF
                    </button>
                </div>
                
                <!-- Loading/Status message -->
                <div class="wer-status" id="status-<?php echo esc_attr($form_id); ?>"></div>
            </form>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('formular_fill', 'formular_fill_shortcode');

/**
 * Create necessary directories and download.php on plugin activation
 */
function formular_fill_activate() {
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/formular-fill';
    
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }
    
    // Create .htaccess to protect files
    $htaccess_content = "deny from all\nallow from none";
    @file_put_contents($pdf_dir . '/.htaccess', $htaccess_content);
    
    // Create index.php to prevent directory listing
    @file_put_contents($pdf_dir . '/index.php', '<?php // Silence is golden');
}
register_activation_hook(__FILE__, 'formular_fill_activate');

/**
 * Create download.php file
 */
function formular_fill_create_download() {
    $download_file = FORMULAR_FILL_PLUGIN_DIR . 'download.php';
    
    if (!file_exists($download_file)) {
        $content = '<?php
// Secure file download
if (!defined(\'ABSPATH\')) {
    exit;
}

$file = isset($_GET[\'file\']) ? sanitize_file_name($_GET[\'file\']) : \'\';

if (empty($file)) {
    wp_die(\'No file specified\');
}

$upload_dir = wp_upload_dir();
$file_path = $upload_dir[\'basedir\'] . \'/formular-fill/\' . $file;

if (!file_exists($file_path) || strpos($file_path, \'formular-fill\') === false) {
    wp_die(\'File not found\');
}

header(\'Content-Description: File Transfer\');
header(\'Content-Type: application/pdf\');
header(\'Content-Disposition: attachment; filename="\' . basename($file_path) . \'"\');
header(\'Expires: 0\');
header(\'Cache-Control: must-revalidate\');
header(\'Pragma: public\');
header(\'Content-Length: \' . filesize($file_path));
readfile($file_path);
exit;
';
        @file_put_contents($download_file, $content);
    }
}
add_action('admin_init', 'formular_fill_create_download');
