<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPML_TM_Translate_Independently {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init all plugin actions.
	 */
	public function init() {
		$this->define_hooks();
	}

	public function define_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_icl_disconnect_posts', array( $this, 'ajax_disconnect_duplicates' ) );
		add_action( 'wp_ajax_icl_check_duplicates', array( $this, 'ajax_check_duplicates' ) );
	}

	public function load_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'wpml_tm_translate_independently',
			WPML_TM_TRANSLATE_INDEPENDENTLY_URL . "/assets/js/wpml_tm_translate_independently{$min}.js",
			array( 'jquery' ),
			WPML_TM_TRANSLATE_INDEPENDENTLY_VERSION
		);
		$message = _x( 'Some posts have duplicated versions.', '1/3 Confirm to disconnect duplicates', 'sitepress' ) . "\n";
		$message .= _x( 'Would you like to translate them independently?', '2/3 Confirm to disconnect duplicates', 'sitepress' ) . "\n";
		$message .= _x( 'If you prefer not to do this you will lose translations when original document is updated.', '3/3 Confirm to disconnect duplicates', 'sitepress' ) . "\n";
		wp_localize_script(
			'wpml_tm_translate_independently',
			'wpml_tm_translate_independently',
			array( 'confirm_message' => $message )
		);
	}

	public function ajax_disconnect_duplicates() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pro-translation-icl' ) ) {
			wp_send_json_error( __( 'Failed to disconnected posts', 'sitepress' ) );
		}

		global $iclTranslationManagement;
		$post_ids = array_map( 'intval', $_POST['posts'] );

		$limit = 500;
		$offset = 0;

		$query = $this->query_helper( $post_ids, $limit, $offset );
		while ( $offset < $query->found_posts ) {
			foreach ( $query->posts as $post_id ) {
				$iclTranslationManagement->reset_duplicate_flag( $post_id );
			}
			if ( $query->found_posts > $limit ) {
				$offset += $limit;
				$query = $this->query_helper( $post_ids, $limit, $offset );
			} else {
				$offset = $query->found_posts;
			}
		}
		wp_reset_postdata();
		wp_send_json_success( __( 'Successfully disconnected posts', 'sitepress' ) );
	}

	public function ajax_check_duplicates() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pro-translation-icl' ) ) {
			wp_send_json_error( array( 'found_posts' => 0 ) );
		}
		$post_ids = array_map( 'intval', $_POST['posts'] );
		$query = $this->query_helper( $post_ids, 1, 0 );
		wp_send_json_success( array( 'found_posts' => $query->found_posts ) );
		wp_reset_postdata();
	}

	private function query_helper( $post_ids = array(), $limit = 100, $offset = 0 ) {
		$args = array(
			'post_type'       => 'any',
			'posts_per_page'  => $limit,
			'offset'          => $offset,
			'fields'          => 'ids',
			'meta_query'      => array(
				array(
					'key'     => '_icl_lang_duplicate_of',
					'value'   => $post_ids,
					'compare' => 'IN',
				),
			),
			'suppress_filters' => true,
		);
		return new WP_Query( $args );
	}
}
