<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPML_TM_Translate_Independently {

	/**
	 * ICL TM Object
	 */
	public $icl_tm;

	/**
	 * Query limit.
	 * @var int
	 */
	public $limit = 500;

	public function __construct( $iclTranslationManagement ) {
		$this->icl_tm = $iclTranslationManagement;
	}

	/**
	 * Init all plugin actions.
	 */
	public function init() {
		$this->define_hooks();
	}

	public function define_hooks() {
		add_action( 'current_screen', array( $this, 'is_basket' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_icl_disconnect_posts', array( $this, 'ajax_disconnect_duplicates' ) );
		add_action( 'wp_ajax_icl_check_duplicates', array( $this, 'ajax_check_duplicates' ) );
	}

	/**
	 * Check if current admin screen is TM basket.
	 */
	public function is_basket() {
		$screen = get_current_screen();
		if (
			'wpml_page_wpml-translation-management/menu/main' === $screen->id
			&&
		    isset( $_GET['sm'] )
			&&
		    'basket' === $_GET['sm']
			&&
		    is_admin()
		) {
			add_action( 'admin_footer', array( $this, 'add_hidden_field' ) );
		}
	}

	/**
	 * Add hidden fields to TM basket.
	 * #icl_duplicate_post_in_basket with list of ids in basket.
	 * #icl_disconnect_nonce nonce for AJAX call.
	 */
	public function add_hidden_field() {
		if ( class_exists( 'TranslationProxy_Basket' ) ) {
			$basket = TranslationProxy_Basket::get_basket( true );
			if ( ! isset( $basket['post'] ) ) {
				return;
			}
			$post_ids = array_map( 'intval', array_keys( $basket['post'] ) );
			if ( true === $this->duplicated_posts_found( $post_ids ) ) :
				?>
				<input type="hidden" value="<?php echo implode( ',', $post_ids ); ?>" id="icl_duplicate_post_in_basket">
				<input type="hidden" value="<?php echo wp_create_nonce( 'icl_disconnect_duplicates' ); ?>" id="icl_disconnect_nonce">
				<?php
			endif;
		}
	}

	/**
	 * Load JS scripts.
	 */
	public function load_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'wpml_tm_translate_independently',
			WPML_TM_TRANSLATE_INDEPENDENTLY_URL . "/assets/js/wpml_tm_translate_independently{$min}.js",
			array( 'jquery' ),
			WPML_TM_TRANSLATE_INDEPENDENTLY_VERSION
		);
		$message = esc_html_x( 'You are about to translate duplicated posts.', '1/2 Confirm to disconnect duplicates', 'sitepress' ) . "\n";
		$message .= esc_html_x( 'These items will be automatically disconnected from originals, so translation is not lost when you update the originals.', '2/2 Confirm to disconnect duplicates', 'sitepress' );
		wp_localize_script(
			'wpml_tm_translate_independently',
			'wpml_tm_translate_independently',
			array( 'message' => $message )
		);
	}

	/**
	 * AJAX action to bulk disconnect posts before sending them to translation.
	 */
	public function ajax_disconnect_duplicates() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'icl_disconnect_duplicates' ) ) {
			wp_send_json_error( esc_html__( 'Failed to disconnected posts', 'wpml-translation-management' ) );
		}

		// Get post basket post ids.
		$post_ids = isset( $_POST['posts'] ) ? explode( ',', $_POST['posts'] ) : array();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( esc_html__( 'No duplicate posts found to disconnect.', 'wpml-translation-management' ) );
		}
		$post_ids = array_map( 'intval', $post_ids );

		// Get originals from duplicates posts.
		$this->disconnect_helper( $this->get_duplicate_args( $post_ids ) );

		// Disconnect all duplicates.
		$this->disconnect_helper( $this->get_duplicated_originals_args( $post_ids ) );

		wp_send_json_success( esc_html__( 'Successfully disconnected posts', 'wpml-translation-management' ) );
	}

	/**
	 * Check if any of the given posts have duplicates.
	 * @param array $post_ids
	 *
	 * @return bool
	 */
	public function duplicated_posts_found( $post_ids ) {
		$found_duplicates = false;

		$duplicate_posts = $this->query_helper( 1, 0, $this->get_duplicate_args( $post_ids ) );
		$duplicated_originals = $this->query_helper( 1, 0, $this->get_duplicated_originals_args( $post_ids ) );

		if ( 0 !== $duplicate_posts['found_posts'] || 0 !== $duplicated_originals['found_posts'] ) {
			$found_duplicates = true;
		}
		return $found_duplicates;
	}

	/**
	 * WP_Query helper function.
	 * @param array $post_ids
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return WP_Query
	 */
	public function query_helper( $limit = 100, $offset = 0, $args = array() ) {
		$output = array();
		$query_args = array(
			'post_type'              => 'any',
			'posts_per_page'         => intval( $limit ),
			'offset'                 => intval( $offset ),
			'fields'                 => 'ids',
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $args ) ) {
			$query_args = array_merge( $query_args, $args );
		}

		$query = new WP_Query( $query_args );

		$output['found_posts'] = $query->found_posts;
		$output['posts'] = $query->posts;

		wp_reset_postdata();
		return $output;
	}

	public function disconnect_helper( $args ) {
		$offset = 0;
		$posts = array();
		$query = $this->query_helper( $this->limit, $offset, $args );
		while ( $offset < $query['found_posts'] ) {
			foreach ( $query['posts'] as $post_id ) {
				$posts[] = $post_id;
			}
			if ( $query['found_posts'] > $this->limit ) {
				$offset += $this->limit;
				$query = $this->query_helper( $this->limit, $offset, $args );
			} else {
				$offset = $query['found_posts'];
			}
		}

		array_walk( $posts, array( $this->icl_tm, 'reset_duplicate_flag' ) );
	}

	public function get_duplicate_args( $post_ids ) {
		return array(
			'post__in' => $post_ids,
			'meta_query' => array(
				array(
					'key'     => '_icl_lang_duplicate_of',
					'compare' => 'EXISTS',
				),
			),
		);
	}

	public function get_duplicated_originals_args( $post_ids ) {
		return array(
			'meta_query' => array(
				array(
					'key'     => '_icl_lang_duplicate_of',
					'value'   => $post_ids,
					'compare' => 'IN',
				),
			),
		);
	}
}
