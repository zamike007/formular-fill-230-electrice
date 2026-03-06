<?php
/**
 * Plugin Name: Formular Fill - ANAF 230 Form Generator
 * Plugin URI: https://example.com/formular-fill
 * Description: A WordPress plugin that generates ANAF Form 230 for 3.5% tax redirection. Use shortcode [formular_230] to display the form.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: formular-fill
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('FORMULAR_FILL_VERSION', '1.0.0');
define('FORMULAR_FILL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FORMULAR_FILL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Enqueue scripts and styles
 */
function formular_fill_enqueue_assets() {
    wp_enqueue_style('formular-fill-bootstrap', FORMULAR_FILL_PLUGIN_URL . 'css/bootstrap.min.css', array(), FORMULAR_FILL_VERSION);
    wp_enqueue_style('formular-fill-main', FORMULAR_FILL_PLUGIN_URL . 'css/main.css', array('formular-fill-bootstrap'), FORMULAR_FILL_VERSION);

    wp_enqueue_script('jquery');
    wp_enqueue_script('formular-fill-bootstrap', FORMULAR_FILL_PLUGIN_URL . 'js/bootstrap.bundle.min.js', array('jquery'), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-pdf-lib', FORMULAR_FILL_PLUGIN_URL . 'js/pdf-lib.min.js', array(), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-signature-pad', FORMULAR_FILL_PLUGIN_URL . 'js/signature_pad.umd.js', array(), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-download', FORMULAR_FILL_PLUGIN_URL . 'js/download.min.js', array(), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-fontkit', FORMULAR_FILL_PLUGIN_URL . 'js/fontkit.umd.min.js', array(), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-validate', FORMULAR_FILL_PLUGIN_URL . 'js/validate-forms.js', array(), FORMULAR_FILL_VERSION, true);
    wp_enqueue_script('formular-fill-main', FORMULAR_FILL_PLUGIN_URL . 'js/main.js', array(
        'jquery',
        'formular-fill-pdf-lib',
        'formular-fill-signature-pad',
        'formular-fill-download',
        'formular-fill-fontkit',
        'formular-fill-validate'
    ), FORMULAR_FILL_VERSION, true);

    // Localize script with config
    wp_localize_script('formular-fill-main', 'formularFillConfig', array(
        'pluginUrl' => FORMULAR_FILL_PLUGIN_URL,
        'orgName'   => get_option('formular_fill_org_name', 'Nume ONG'),
        'orgCIF'    => get_option('formular_fill_org_cif', ''),
        'orgIBAN'   => get_option('formular_fill_org_iban', ''),
        'percent'   => '3,5',
        // Representative (Imputernicit) fields
        'imputernicit_nume' => get_option('formular_fill_imputernicit_nume', ''),
        'imputernicit_cui' => get_option('formular_fill_imputernicit_cui', ''),
        'imputernicit_strada' => get_option('formular_fill_imputernicit_strada', ''),
        'imputernicit_numar' => get_option('formular_fill_imputernicit_numar', ''),
        'imputernicit_ap' => get_option('formular_fill_imputernicit_ap', ''),
        'imputernicit_judet' => get_option('formular_fill_imputernicit_judet', ''),
        'imputernicit_localitate' => get_option('formular_fill_imputernicit_localitate', ''),
        'imputernicit_telefon' => get_option('formular_fill_imputernicit_telefon', ''),
        'imputernicit_email' => get_option('formular_fill_imputernicit_email', ''),
    ));
}
add_action('wp_enqueue_scripts', 'formular_fill_enqueue_assets');

/**
 * Register the shortcode [formular_230]
 */
function formular_fill_shortcode($atts) {
    // Default attributes
    $atts = shortcode_atts(array(
        'org_name'   => get_option('formular_fill_org_name', 'Nume ONG'),
        'org_cif'   => get_option('formular_fill_org_cif', ''),
        'org_iban'  => get_option('formular_fill_org_iban', ''),
        'percent'   => '3,5',
    ), $atts, 'formular_230');

    // Start output buffering
    ob_start();
    ?>
    <div id="formular-fill-wrapper" data-bs-theme="dark">
        <div id="mainDiv" class="cover-container d-flex h-100 p-3 mx-auto flex-column">
            <header class="masthead mb-auto text-center">
                <div class="inner">
                    <h4 id="infoTitle" class="">Formular 230 ANAF</h3>
                </div>
            </header>

            <form id="form" class="container text-center needs-validation" novalidate>
                <h3 class="mt-4 mb-4">Date de identificare a contribuabilului</h3>
                <div class="row">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="nume" placeholder="Nume" required>
                            <label for="nume">Nume</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="prenume" placeholder="Prenume" required>
                            <label for="prenume">Prenume</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="initialaTatalui" placeholder="Inițiala tatălui"
                                required>
                            <label for="initialaTatalui">Inițiala tatălui</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="cnp" placeholder="CNP" required>
                            <label for="cnp">CNP</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" placeholder="Email" required>
                            <label for="email">Email</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="telefon" placeholder="Telefon" required>
                            <label for="telefon">Telefon</label>
                        </div>
                    </div>
                </div>

                <h3 class="mt-5 mb-4">Adresa de domiciliu<br/>(așa cum apare în documentul de identitate)</h3>
                <div class="row">
                    <div class="col-8">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="strada" placeholder="Strada" required>
                            <label for="strada">Strada</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="nrStrada" placeholder="Nr. stradă" required>
                            <label for="nrStrada">Nr. stradă</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="bloc" placeholder="Bloc">
                            <label for="bloc">Bloc</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="scara" placeholder="Scară">
                            <label for="scara">Scară</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="etaj" placeholder="Etaj">
                            <label for="etaj">Etaj</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="apartament" placeholder="Apartament">
                            <label for="apartament">Apartament</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="judet" placeholder="Județ" required>
                            <label for="judet">Județ</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="localitate" placeholder="Localitate" required>
                            <label for="localitate">Localitate</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="codPostal" placeholder="Cod poștal">
                            <label for="codPostal">Cod poștal</label>
                        </div>
                    </div>
                </div>

                <h3 class="mt-5 mb-4">Perioada redirecționării</h3>
                <div>
                    <input type="radio" class="btn-check" name="perioada" id="unAn" autocomplete="off">
                    <label class="btn btn-outline-primary" for="unAn">Un an</label>

                    <input type="radio" class="btn-check" name="perioada" id="doiAni" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="doiAni">Doi ani</label>
                </div>

                <div class="mt-5 mb-4">
                    <p>Semnătură olograf:</p>
                    <canvas id="signature-pad" class="signature-pad" width="400" height="200"></canvas>
                    <p><a class="link-underline-primary handCursor" onclick="ClearSignature()">Resetează semnătura</a></p>
                </div>

                <div class="row mt-5 mb-4">
                    <button type="submit" class="btn btn-primary">Generează și descarcă formularul 230 cu datele introduse mai
                        sus</button>
                </div>
            </form>

            <div class="modal fade" id="mainModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Info</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="mainModalMessage" class="text-center"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Am înțeles</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('formular_230', 'formular_fill_shortcode');

/**
 * Register plugin settings
 */
function formular_fill_settings_init() {
    register_setting('formular_fill', 'formular_fill_org_name');
    register_setting('formular_fill', 'formular_fill_org_cif');
    register_setting('formular_fill', 'formular_fill_org_iban');
    // Representative (Imputernicit) fields
    register_setting('formular_fill', 'formular_fill_imputernicit_nume');
    register_setting('formular_fill', 'formular_fill_imputernicit_cui');
    register_setting('formular_fill', 'formular_fill_imputernicit_strada');
    register_setting('formular_fill', 'formular_fill_imputernicit_numar');
    register_setting('formular_fill', 'formular_fill_imputernicit_ap');
    register_setting('formular_fill', 'formular_fill_imputernicit_judet');
    register_setting('formular_fill', 'formular_fill_imputernicit_localitate');
    register_setting('formular_fill', 'formular_fill_imputernicit_telefon');
    register_setting('formular_fill', 'formular_fill_imputernicit_email');
}
add_action('admin_init', 'formular_fill_settings_init');

/**
 * Add settings page
 */
function formular_fill_add_admin_menu() {
    add_options_page(
        'Formular Fill Settings',
        'Formular Fill',
        'manage_options',
        'formular_fill',
        'formular_fill_options_page'
    );
}
add_action('admin_menu', 'formular_fill_add_admin_menu');

function formular_fill_options_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('formular_fill');
            do_settings_sections('formular_fill');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Organization Name</th>
                    <td><input type="text" name="formular_fill_org_name" value="<?php echo esc_attr(get_option('formular_fill_org_name', 'Nume ONG')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Organization CIF</th>
                    <td><input type="text" name="formular_fill_org_cif" value="<?php echo esc_attr(get_option('formular_fill_org_cif', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Organization IBAN</th>
                    <td><input type="text" name="formular_fill_org_iban" value="<?php echo esc_attr(get_option('formular_fill_org_iban', '')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <h2>Representative (Împuternicit) Settings</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Representative Name</th>
                    <td><input type="text" name="formular_fill_imputernicit_nume" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_nume', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative CIF/CNP</th>
                    <td><input type="text" name="formular_fill_imputernicit_cui" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_cui', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative Street</th>
                    <td><input type="text" name="formular_fill_imputernicit_strada" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_strada', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative Number</th>
                    <td><input type="text" name="formular_fill_imputernicit_numar" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_numar', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative Apartment</th>
                    <td><input type="text" name="formular_fill_imputernicit_ap" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_ap', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative County</th>
                    <td><input type="text" name="formular_fill_imputernicit_judet" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_judet', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative City</th>
                    <td><input type="text" name="formular_fill_imputernicit_localitate" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_localitate', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative Phone</th>
                    <td><input type="text" name="formular_fill_imputernicit_telefon" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_telefon', '')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Representative Email</th>
                    <td><input type="text" name="formular_fill_imputernicit_email" value="<?php echo esc_attr(get_option('formular_fill_imputernicit_email', '')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Plugin activation hook
 */
function formular_fill_activate() {
    // Set default options if not already set
    if (!get_option('formular_fill_org_name')) {
        update_option('formular_fill_org_name', 'Nume ONG');
    }
}
register_activation_hook(__FILE__, 'formular_fill_activate');
