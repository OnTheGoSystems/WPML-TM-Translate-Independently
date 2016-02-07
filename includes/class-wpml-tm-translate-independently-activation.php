<?php


class WPML_TM_Translate_Independently_Activation {


	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_dependencies' ) );
	}

	public function check_dependencies() {
		if (
		! is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' )
		||
		! is_plugin_active( 'wpml-translation-management/plugin.php' )
		) {
			deactivate_plugins( WPML_TM_TRANSLATE_INDEPENDENTLY_PATH . 'wpml-tm-translate-independently.php' );
			add_action( 'admin_notices', array( $this, 'add_user_notice' ) );
		}
	}

	public function add_user_notice() {
		echo '<div class="updated"><p>';
		echo __( 'WPML TM Translate Independently has been deactivated. It requires both "WPML Multilingual CMS" and "WPML Translation Management" to be active.', 'sitepress' );
		echo '</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}