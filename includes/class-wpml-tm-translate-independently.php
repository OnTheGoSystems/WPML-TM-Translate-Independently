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
		add_action( 'wp_ajax_icl_disconnect_posts', array( $this, 'handle_ajax_request' ) );
	}

	public function load_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'wpml_tm_translate_independently',
			WPML_TM_TRANSLATE_INDEPENDENTLY_URL . "/assets/js/wpml_tm_translate_independently{$min}.js",
			array( 'jquery' ),
			WPML_TM_TRANSLATE_INDEPENDENTLY_VERSION
		);
	}

	public function handle_ajax_request() {
		global $iclTranslationManagement;
		$post_ids = array_map( 'intval', $_POST['posts'] );

		$limit = 500;
		$offset = 0;

		$query = $this->query_helper( $post_ids, $limit, $offset );
		while ( $offset < $query->found_posts ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$iclTranslationManagement->reset_duplicate_flag( get_the_ID() );
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

	private function query_helper( $post_ids = array(), $limit = 100, $offset = 0 ) {
		$args = array(
			'post_type'       => 'any',
			'posts_per_page'  => $limit,
			'offset'          => $offset,
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
