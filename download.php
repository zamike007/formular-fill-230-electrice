<?php
// Secure file download
if (!defined('ABSPATH')) {
    exit;
}

$file = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : '';

if (empty($file)) {
    wp_die('No file specified');
}

$upload_dir = wp_upload_dir();
$file_path = $upload_dir['basedir'] . '/formular-fill/' . $file;

if (!file_exists($file_path) || strpos($file_path, 'formular-fill') === false) {
    wp_die('File not found');
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
