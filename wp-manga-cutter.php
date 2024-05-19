<?php
/**
 *  Plugin Name: WP Manga - Image Cutter
 *  Description: Cut images if their height exceeds 1000px.
 *  Plugin URI: https://sectscans.com/
 *  Author: WavaDev
 *  Author URI: https://sectscans.com/
 *  Author Email: admin@sectscans.com
 *  Version: 1.0.5
 *  Text Domain: wp-manga-cutter
 * @since 1.0
 */

// Plugin dir URI
if (!defined('WP_MANGA_SPLITTER_URI')) {
    define('WP_MANGA_SPLITTER_URI', plugin_dir_url(__FILE__));
}

// Plugin dir path
if (!defined('WP_MANGA_SPLITTER_DIR')) {
    define('WP_MANGA_SPLITTER_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WP_MANGA_SPLITTER_TEXTDOMAIN')) {
    define('WP_MANGA_SPLITTER_TEXTDOMAIN', 'wp-manga-splitter');
}



class WP_Manga_Splitter_Updater {
    private $api_url;
    private $plugin_path;
    private $plugin_slug;
    private $slug;

    function __construct($api_url, $plugin_path) {
        $this->api_url = $api_url;
        $this->plugin_path = $plugin_path;
        $this->plugin_slug = plugin_basename($plugin_path);
        list($t1, $t2) = explode('/', $this->plugin_slug);
        $this->slug = str_replace('.php', '', $t2);

        add_filter('pre_set_site_transient_update_plugins', array($this, 'set_update_transient'));
        add_filter('plugins_api', array($this, 'info_screen'), 10, 3);
    }

    function set_update_transient($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_info = $this->get_remote_info();

        if ($remote_info && version_compare($remote_info->new_version, $transient->checked[$this->plugin_slug], '>')) {
            $transient->response[$this->plugin_slug] = $remote_info;
        }

        return $transient;
    }

    function info_screen($res, $action, $args) {
        if ($action == 'plugin_information' && $args->slug == $this->slug) {
            return $this->get_remote_info();
        }
        return $res;
    }

    function get_remote_info() {
        $response = wp_remote_get($this->api_url, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response) || 200 != wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $response = unserialize(wp_remote_retrieve_body($response));

        if (isset($response->sections)) {
            $response->sections = (array) $response->sections;
        }

        return $response;
    }
}

// Start the update checker
$api_url = 'https://raw.githubusercontent.com/WavaDev/Madara-Image-Splitter/main/update-checker.php'; // Replace with your server script URL
$plugin_path = __FILE__;
new WP_Manga_Splitter_Updater($api_url, $plugin_path);


class WP_MANGA_ADDON_SPLITTER {
    private static $instance;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new WP_MANGA_ADDON_SPLITTER();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'load_plugin_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'));
    }

    function load_plugin_textdomain() {
        load_plugin_textdomain('wp-manga-splitter', false, plugin_basename(dirname(__FILE__)) . '/languages');
        add_action('wp_manga_upload_after_extract', array($this, 'split_after_extract'), 100, 4);
    }

/**
 * Split image file if its height is more than 1000px
 **/
function split_image_file($file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    switch ($extension) {
        case 'png':
            $im = imagecreatefrompng($file);
            break;
        case 'jpg':
        case 'jpeg':
            $im = imagecreatefromjpeg($file);
            break;
        case 'webp':
            $im = imagecreatefromwebp($file);
            break;
        default:
            return; // Do nothing for unsupported formats
    }

    $width = imagesx($im);
    $height = imagesy($im);

    // Check if the height is less than or equal to 1000px
    if ($height <= 1000) {
        imagedestroy($im);
        return; // Do nothing and return
    }

    $split_count = 0;
    $remaining_height = $height;

    while ($remaining_height > 1000) {
        $new_image = imagecreatetruecolor($width, 1000);
        imagecopy($new_image, $im, 0, 0, 0, $split_count * 1000, $width, 1000);
        $new_filename = str_replace(".$extension", "_part_" . ($split_count + 1) . ".$extension", $file);

        switch ($extension) {
            case 'png':
                imagepng($new_image, $new_filename);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($new_image, $new_filename, 99);
                break;
            case 'webp':
                imagewebp($new_image, $new_filename);
                break;
        }

        imagedestroy($new_image);
        $split_count++;
        $remaining_height -= 1000;
    }

    // Handle the last remaining part
    if ($remaining_height > 0) {
        $new_image = imagecreatetruecolor($width, $remaining_height);
        imagecopy($new_image, $im, 0, 0, 0, $split_count * 1000, $width, $remaining_height);
        $new_filename = str_replace(".$extension", "_part_" . ($split_count + 1) . ".$extension", $file);

        switch ($extension) {
            case 'png':
                imagepng($new_image, $new_filename);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($new_image, $new_filename, 99);
                break;
            case 'webp':
                imagewebp($new_image, $new_filename);
                break;
        }

        imagedestroy($new_image);
    }

    if ($split_count > 0) {
        unlink($file);  // Delete the original image
    }

    imagedestroy($im);
}


    // After upload, split the image if its height is more than 1000px
    function split_after_extract($post_id, $slugified_name, $extract, $storage) {
        $files = glob($extract . '/*.*');
        natsort($files);  // Sort files in natural order
        foreach ($files as $file) {
            if (!is_dir($file)) {
                $this->split_image_file($file);
            }
        }
    }

    function admin_enqueue_script() {
        wp_enqueue_script('wp-manga-splitter-admin', WP_MANGA_SPLITTER_URI . 'assets/js/wp-splitter.js', array('jquery'), '', true);
        wp_enqueue_style('wp-manga-splitter-admin', WP_MANGA_SPLITTER_URI . 'assets/css/admin.css');
    }
}

$wp_manga_splitter = WP_MANGA_ADDON_SPLITTER::get_instance();
