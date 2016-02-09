<?php
/**
 * Plugin Name: WPML TM Translate Independently
 * Plugin URI:  https://www.wpml.org/
 * Description: Allow bulk disconnect duplicate posts from TM Dashboard.
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
function init_plugin() {
	global $iclTranslationManagement;
	$wpml_tm_trnaslate_independently = new WPML_TM_Translate_Independently( $iclTranslationManagement );
	$wpml_tm_trnaslate_independently->init();

	$wpml_tm_trnaslate_independently_activation = new WPML_TM_Translate_Independently_Activation();
	$wpml_tm_trnaslate_independently_activation->init();
}
init_plugin();