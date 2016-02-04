<?php
/**
 * Plugin Name: WPML TM Translate Independently
 * Plugin URI:  https://www.wpml.org/
 * Description: Allow to bulk disconnect duplicate posts.
 * Version:     0.0.1
 * Author:      OnTheGoSystems
 * Author URI:  http://www.onthegosystems.com/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Useful global constants
define( 'WPML_TM_TRANSLATE_INDEPENDENTLY_VERSION', '0.0.1' );
define( 'WPML_TM_TRANSLATE_INDEPENDENTLY_URL',     plugin_dir_url( __FILE__ ) );
define( 'WPML_TM_TRANSLATE_INDEPENDENTLY_PATH',    dirname( __FILE__ ) . '/' );

require_once WPML_TM_TRANSLATE_INDEPENDENTLY_PATH . 'includes/class-wpml-tm-translate-independently-activation.php';
require_once WPML_TM_TRANSLATE_INDEPENDENTLY_PATH . 'includes/class-wpml-tm-translate-independently.php';

// Init plugin.
$wpml_tm_trnaslate_independently = new WPML_TM_Translate_Independently();
$wpml_tm_trnaslate_independently_activation = new WPML_TM_Translate_Independently_Activation();