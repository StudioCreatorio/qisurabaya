<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Api {

	const API_NAMESPACE = 'bricks/v1';

	/**
	 * WordPress REST API help docs:
	 *
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'rest_api_init_custom_endpoints' ] );
	}

	/**
	 * Custom REST API endpoints
	 */
	public function rest_api_init_custom_endpoints() {
		// Server-side render (SSR) for builder elements via window.fetch API requests
		register_rest_route(
			self::API_NAMESPACE,
			'render_element',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_element' ],
				'permission_callback' => [ $this, 'render_element_permissions_check' ],
			]
		);

		// Get all templates data (templates, authors, bundles, tags etc.)
		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates-data/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates_data' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates' ],
				'permission_callback' => '__return_true',
			]
		);

		// Get individual template by ID
		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates/(?P<args>[a-zA-Z0-9-=&]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'args' => [
						'required' => true
					],
				],
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-authors/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_authors' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-bundles/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_bundles' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-tags/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_tags' ],
				'permission_callback' => '__return_true',
			]
		);

		/**
		 * Query loop: Infinite scroll
		 *
		 * @since 1.5
		 */
		register_rest_route(
			self::API_NAMESPACE,
			'load_query_page',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_query_page' ],
				'permission_callback' => [ $this, 'render_query_page_permissions_check' ],
			]
		);
	}

	/**
	 * Return element HTML retrieved via Fetch API
	 *
	 * @since 1.5
	 */
	public static function render_element( $request ) {
		$data = $request->get_json_params();

		if ( ! empty( $data['postId'] ) ) {
			Database::set_page_data( $data['postId'] );
		}

		// Include WooCommerce frontend classes and hooks to enable the WooCommerce element preview inside the builder (since 1.5)
		if ( Woocommerce::$is_active ) {
			WC()->frontend_includes();

			Woocommerce_Helpers::maybe_load_cart();
		}

		// Get rendered element HTML
		$html = Ajax::render_element( $data );

		// Prepare response
		$response = [ 'html' => $html ];

		// Template element (send template elements to run template element scripts on the canvas)
		if ( ! empty( $data['element']['name'] ) && $data['element']['name'] === 'template' ) {
			$template_id = isset( $data['element']['settings']['template'] ) ? $data['element']['settings']['template'] : false;

			if ( $template_id ) {
				$additional_data = Element_Template::get_builder_call_additional_data( $template_id );

				$response = array_merge( $response, $additional_data );
			}
		}

		return [ 'data' => $response ];
	}

	/**
	 * Element render permission check
	 *
	 * @since 1.5
	 */
	public function render_element_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['postId'] ) || empty( $data['element'] ) || empty( $data['nonce'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		$result = wp_verify_nonce( $data['nonce'], 'bricks-nonce' );

		if ( ! $result ) {
			return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie check failed' ), [ 'status' => 403 ] );
		}

		// Not in use as builder access capability check is already performed on page load
		// Capabilities::current_user_can_use_builder();

		return true;
	}

	/**
	 * Return all templates data in one call (templates, authors, bundles, tags, theme style)
	 *
	 * @param  array $data
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_templates_data( $data ) {
		$templates_args = isset( $data['args'] ) ? $data['args'] : [];
		$templates      = $this->get_templates( $templates_args );

		// STEP: Check for template error
		if ( isset( $templates['error'] ) ) {
			return $templates;
		}

		$theme_styles   = get_option( BRICKS_DB_THEME_STYLES, false );
		$global_classes = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );

		// STEP: Add theme style to template data to import when inserting a template (@since 1.3.2)
		foreach ( $templates as $index => $template ) {
			$theme_style_id = Theme_Styles::set_active_style( $template['id'], true );
			$theme_style    = isset( $theme_styles[ $theme_style_id ] ) ? $theme_styles[ $theme_style_id ] : false;

			if ( $theme_style ) {
				// Remove theme style conditions
				if ( isset( $theme_style['settings']['conditions'] ) ) {
					unset( $theme_style['settings']['conditions'] );
				}

				$theme_style['id']                 = $theme_style_id;
				$templates[ $index ]['themeStyle'] = $theme_style;
			}

			/**
			 * Loop over all template elements to add 'global_classes' data to remote template data
			 *
			 * To import global classes when importing remote template locally.
			 *
			 * @since 1.5
			 */
			if ( count( $global_classes ) ) {
				$template_classes  = [];
				$template_elements = [];

				if ( ! empty( $template['content'] ) && is_array( $template['content'] ) ) {
					$template_elements = $template['content'];
				} elseif ( ! empty( $template['header'] ) && is_array( $template['header'] ) ) {
					$template_elements = $template['header'];
				} elseif ( ! empty( $template['footer'] ) && is_array( $template['footer'] ) ) {
					$template_elements = $template['footer'];
				}

				foreach ( $template_elements as $element ) {
					if ( ! empty( $element['settings']['_cssGlobalClasses'] ) ) {
						$template_classes = array_unique( array_merge( $template_classes, $element['settings']['_cssGlobalClasses'] ) );
					}
				}

				if ( count( $template_classes ) ) {
					$templates[ $index ]['global_classes'] = [];

					foreach ( $template_classes as $template_class ) {
						foreach ( $global_classes as $global_class ) {
							if ( $global_class['id'] === $template_class ) {
								$templates[ $index ]['global_classes'][] = $global_class;
							}
						}
					}
				}
			}
		}

		// Return all templates data
		$templates_data = [
			'timestamp' => current_time( 'timestamp' ),
			'date'      => current_time( get_option( 'date_format' ) . ' (' . get_option( 'time_format' ) . ')' ),
			'templates' => $templates,
			'authors'   => Templates::get_template_authors(),
			'bundles'   => Templates::get_template_bundles(),
			'tags'      => Templates::get_template_tags(),
			'get'       => $_GET, // Pass URL params to perform additional checks (e.g. 'password' as license key, etc.) @since 1.5.5
		];

		$templates_data = apply_filters( 'bricks/api/get_templates_data', $templates_data );

		// Remove 'get' data to avoid storing it in db
		unset( $templates_data['get'] );

		return $templates_data;
	}

	/**
	 * Return templates array OR specific template by array index
	 *
	 * @since 1.0
	 *
	 * @param  array $data
	 *
	 * @return array
	 */
	public function get_templates( $data ) {
		$parameters = $_GET;

		$templates_response = Templates::can_get_templates( $parameters );

		// Check for templates error (no site/password etc. provided)
		if ( isset( $templates_response['error'] ) ) {
			return $templates_response;
		}

		$templates_args = isset( $data['args'] ) ? $data['args'] : [];

		// Merge $parameters with $templates_response args
		$templates_args = array_merge( $templates_args, $templates_response );

		$templates = Templates::get_templates( $templates_args );

		return $templates;
	}

	/**
	 * Get API endpoint
	 *
	 * Default: /api (to get Bricks Community Templates)
	 * Remote URL or 'render_element' set: /wp-json (to use default WP REST API prefix)
	 *
	 * @param string $endpoint Custom endpoint.
	 * @param string $default Default base URL.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public static function get_endpoint( $endpoint = 'get-templates', $default_base_url = BRICKS_REMOTE_URL ) {
		$remote_templates_url = Database::get_setting( 'remoteTemplatesUrl', false );
		$api_base_url         = $remote_templates_url ? $remote_templates_url : $default_base_url;
		$api_prefix           = $remote_templates_url || $endpoint === 'render_element' ? rest_get_url_prefix() : 'api';

		return trailingslashit( $api_base_url ) . trailingslashit( $api_prefix ) . trailingslashit( self::API_NAMESPACE ) . $endpoint;
	}

	/**
	 * Get the Bricks REST API url
	 *
	 * @since 1.5
	 *
	 * @return string
	 */
	public static function get_rest_api_url() {
		return trailingslashit( get_rest_url( null, '/' . self::API_NAMESPACE ) );
	}

	/**
	 * Check if current endpoint is Bricks API endpoint
	 *
	 * @since 1.8.1
	 *
	 * @param string $endpoint (e.g. 'render_element' or 'load_query_page' for our infinite scroll)
	 *
	 * @return bool
	 */
	public static function is_current_endpoint( $endpoint ) {
		if ( ! $endpoint ) {
			return false;
		}

		global $wp;

		// REST route (example: /bricks/v1/load_query_page)
		$current_rest_route = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : '';

		if ( ! $current_rest_route ) {
			return false;
		}

		// Example: /bricks/v1/load_query_page
		$bricks_rest_route =  '/' . self::API_NAMESPACE . '/' . $endpoint;

		return $current_rest_route === $bricks_rest_route;
	}

	/**
	 * Get template authors
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_authors() {
		return Templates::get_template_authors();
	}

	/**
	 * Get template bundles
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_bundles() {
		return Templates::get_template_bundles();
	}

	/**
	 * Get template tags
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_tags() {
		return Templates::get_template_tags();
	}

	/**
	 * Get news feed
	 *
	 * NOTE: Not in use.
	 *
	 * @return array
	 */
	public static function get_feed() {
		$remote_base_url = BRICKS_REMOTE_URL;
		$feed_url        = trailingslashit( $remote_base_url ) . trailingslashit( rest_get_url_prefix() ) . trailingslashit( self::API_NAMESPACE ) . trailingslashit( 'feed' );

		$response = Helpers::remote_get( $feed_url );

		if ( is_wp_error( $response ) ) {
			return [];
		} else {
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}

	/**
	 * Query loop: Infinite scroll permissions callback
	 *
	 * @since 1.5
	 */
	public function render_query_page_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['queryElementId'] ) || empty( $data['nonce'] ) || empty( $data['page'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		$result = wp_verify_nonce( $data['nonce'], 'bricks-nonce' );

		if ( $result === false ) {
			return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Bricks cookie check failed' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Query loop: Infinite scroll callback
	 *
	 * @since 1.5
	 */
	public function render_query_page( $request ) {
		$request_data = $request->get_json_params();

		$query_element_id = $request_data['queryElementId'];
		$post_id          = $request_data['postId'];
		$page             = $request_data['page'];
		$query_vars       = json_decode( $request_data['queryVars'], true ); // @since 1.5.1

		$data = Helpers::get_element_data( $post_id, $query_element_id );

		if ( empty( $data['elements'] ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Template data not found',
				]
			);
		}

		// STEP: Build the flat list index
		$indexed_elements = [];

		foreach ( $data['elements'] as $element ) {
			$indexed_elements[ $element['id'] ] = $element;
		}

		if ( ! array_key_exists( $query_element_id, $indexed_elements ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Element not found',
				]
			);
		}

		// STEP: Set the query element pagination
		$query_element = $indexed_elements[ $query_element_id ];

		$query_element['settings']['query']['paged'] = $page;

		// STEP: Add merge query vars, used to simulate the global query merge in the archives (@since 1.5.1)
		$query_element['settings']['query']['_merge_vars'] = $query_vars;

		// Remove the parent
		if ( ! empty( $query_element['parent'] ) ) {
			$query_element['parent']       = 0;
			$query_element['_noRootClass'] = 1;
		}

		// STEP: Get the query loop elements (main and children)
		$loop_elements = [ $query_element ];

		$children = $query_element['children'];

		while ( ! empty( $children ) ) {
			$child_id = array_shift( $children );

			if ( array_key_exists( $child_id, $indexed_elements ) ) {
				$loop_elements[] = $indexed_elements[ $child_id ];

				if ( ! empty( $indexed_elements[ $child_id ]['children'] ) ) {
					$children = array_merge( $children, $indexed_elements[ $child_id ]['children'] );
				}
			}
		}

		// Set Theme Styles (for correct preview of query loop nodes)
		Theme_Styles::load_set_styles( $post_id );

		// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
		$scroll_query_page_id = "scroll_{$query_element_id}_{$page}";

		Assets::generate_css_from_elements( $loop_elements, $scroll_query_page_id );

		$inline_css = ! empty( Assets::$inline_css[ $scroll_query_page_id ] ) ? Assets::$inline_css[ $scroll_query_page_id ] : '';

		// STEP: Render the element after styles are generated as data-query-loop-index might be inserted through hook in Assets class (@since 1.7.2)
		$html = Frontend::render_data( $loop_elements );

		// Add popup HTML plus styles (@since 1.7.1)
		$popups = Popups::$looping_popup_html;

		// STEP: Add dynamic data styles after render_data() to catch dynamic data changes (eg. background-image) (@since 1.8.2)
		$inline_css .= Assets::$inline_css_dynamic_data;

		$styles = ! empty( $inline_css ) ? "\n<style>/* INFINITE SCROLL CSS */\n{$inline_css}</style>\n" : '';

		return rest_ensure_response(
			[
				'html'   => $html,
				'styles' => $styles,
				'popups' => $popups,
			]
		);
	}
}
