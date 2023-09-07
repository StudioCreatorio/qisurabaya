<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Query {
	/**
	 * The query unique ID
	 */
	private $id = '';

	/**
	 * Element ID
	 */
	public $element_id = '';

	/**
	 * Element settings
	 */
	public $settings = [];

	/**
	 * Query vars
	 */
	public $query_vars = [];

	/**
	 * Type of object queried: 'post', 'term', 'user'
	 */
	public $object_type = 'post';

	/**
	 * Query result (WP_Posts | WP_Term_Query | WP_User_Query | Other)
	 */
	public $query_result;

	/**
	 * Query results total
	 */
	public $count = 0;

	/**
	 * Query results total pages
	 */
	public $max_num_pages = 1;

	/**
	 * Is looping
	 *
	 * @var boolean
	 */
	public $is_looping = false;

	/**
	 * When looping, keep the iteration index
	 */
	public $loop_index = 0;

	/**
	 * When looping, keep the object
	 */
	public $loop_object = null;

	/**
	 * Store the original post before looping to restore the context (nested loops)
	 */
	private $original_post_id = 0;

	/**
	 * Cache key
	 */
	private $cache_key = false;

	/**
	 * Class constructor
	 *
	 * @param array $element
	 */
	public function __construct( $element = [] ) {
		$this->register_query();

		$this->element_id = ! empty( $element['id'] ) ? $element['id'] : '';

		$this->object_type = ! empty( $element['settings']['query']['objectType'] ) ? $element['settings']['query']['objectType'] : 'post';

		// Remove object type from query vars to avoid future conflicts
		unset( $element['settings']['query']['objectType'] );

		$this->settings = ! empty( $element['settings'] ) ? $element['settings'] : [];

		// STEP: Set the query vars from the element settings (@since 1.8)
		$this->query_vars = self::prepare_query_vars_from_settings( $this->settings );

		// STEP: Perform the query, set the query result, count and max_num_pages (@since 1.8)
		$this->run();
	}

	/**
	 * Add this query to the global store
	 */
	public function register_query() {
		global $bricks_loop_query;

		$this->id = Helpers::generate_random_id( false );

		if ( ! is_array( $bricks_loop_query ) ) {
			$bricks_loop_query = [];
		}

		$bricks_loop_query[ $this->id ] = $this;
	}

	/**
	 * Calling unset( $query ) does not destroy query quickly enough
	 *
	 * Have to call the 'destroy' method explicitly before unset.
	 */
	public function __destruct() {
		$this->destroy();
	}

	/**
	 * Use the destroy method to remove the query from the global store
	 *
	 * @return void
	 */
	public function destroy() {
		global $bricks_loop_query;

		unset( $bricks_loop_query[ $this->id ] );
	}

	/**
	 * Get the query cache
	 *
	 * @since 1.5
	 *
	 * @return mixed
	 */
	public function get_query_cache() {
		if ( ! isset( Database::$global_settings['cacheQueryLoops'] ) || ! bricks_is_frontend() || bricks_is_builder_call() ) {
			return false;
		}

		// Check: Nesting query?
		$parent_query_id  = self::is_any_looping();
		$parent_object_id = $parent_query_id ? self::get_loop_object_id( $parent_query_id ) : 0;

		// Include in the cache key a representation of the query vars to break cache for certain scenarios like pagination or search keywords
		$query_vars = json_encode( $this->query_vars );

		// Get & set query loop cache (@since 1.5)
		$this->cache_key = md5( "brx_query_{$this->element_id}_{$query_vars}_{$parent_object_id}" );

		return wp_cache_get( $this->cache_key, 'bricks' );
	}

	/**
	 * Set the query cache
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function set_query_cache( $object ) {
		if ( ! $this->cache_key ) {
			return;
		}

		wp_cache_set( $this->cache_key, $object, 'bricks', MINUTE_IN_SECONDS );
	}

	/**
	 * Prepare query_vars for the Query before running it
	 * Remove unwanted keys, set defaults, populate correct query vars, etc.
	 * Static method to be used by other classes. (Bricks\Database)
	 *
	 * @since 1.8
	 */
	public static function prepare_query_vars_from_settings( $settings ) {
		$query_vars = isset( $settings['query'] ) ? $settings['query'] : [];

		// Unset infinite scroll
		if ( isset( $query_vars['infinite_scroll'] ) ) {
			unset( $query_vars['infinite_scroll'] );
		}

		// Do not use meta_key if orderby is not set to meta_value or meta_value_num (@since 1.8)
		if ( isset( $query_vars['meta_key'] ) ) {
			$orderby = isset( $query_vars['orderby'] ) ? $query_vars['orderby'] : '';
			if ( ! in_array( $orderby, [ 'meta_value', 'meta_value_num' ] ) ) {
				unset( $query_vars['meta_key'] );
			}
		}

		$object_type = self::get_query_object_type();
		$element_id  = self::get_query_element_id();

		// Meta Query vars
		$query_vars = self::parse_meta_query_vars( $query_vars );

		// Set different query vars depending on the object type
		switch ( $object_type ) {
			case 'post':
				// Attachments
				$is_attachment_query = false;
				// post_type can be 'string' or 'array'
				$post_type = ! empty( $query_vars['post_type'] ) ? $query_vars['post_type'] : false;

				if ( $post_type ) {
					if ( is_array( $post_type ) ) {
						$is_attachment_query = in_array( 'attachment', $post_type ) && count( $post_type ) == 1;
					} else {
						$is_attachment_query = $post_type === 'attachment';
					}
				}

				// @since 1.5: If the post type is 'attachment', change default post status to 'inherit' @see: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
				$query_vars['post_status'] = $is_attachment_query ? 'inherit' : 'publish';

				// post_mime_type: used only for post_type = 'attachment' (@since 1.5)
				if ( $is_attachment_query ) {
					$mime_types = isset( $query_vars['post_mime_type'] ) ? bricks_render_dynamic_data( $query_vars['post_mime_type'] ) : 'image';

					$mime_types = explode( ',', $mime_types );

					$tquery_vars['post_mime_type'] = $mime_types;
				}

				// Page & Pagination
				// @since 1.7.1 - Standardize use the get_paged_query_var() function to get the paged value
				$query_vars['paged'] = self::get_paged_query_var( $query_vars );

				// Value must be -1 or > 1 (0 is not allowed)
				$query_vars['posts_per_page'] = ! empty( $query_vars['posts_per_page'] ) ? intval( $query_vars['posts_per_page'] ) : get_option( 'posts_per_page' );

				// Exclude current post
				if ( isset( $query_vars['exclude_current_post'] ) ) {
					// @since 1.8 - Capture exclude_current_post value inside builder call (#861m48kv4)
					if ( is_single() || is_page() || bricks_is_builder_call() ) {
						$query_vars['post__not_in'][] = get_the_ID();
					}

					unset( $query_vars['exclude_current_post'] );
				}

				// @since 1.5 - Post parent
				if ( isset( $query_vars['post_parent'] ) ) {
					$post_parent = bricks_render_dynamic_data( $query_vars['post_parent'] );

					if ( strpos( $post_parent, ',' ) !== false ) {
						$post_parent = explode( ',', $post_parent );

						// @since 1.7.1
						$query_vars['post_parent__in'] = (array) $post_parent;

						unset( $query_vars['post_parent'] );
					} else {
						$query_vars['post_parent'] = (int) $post_parent;
					}
				}

				// Tax query
				$query_vars = self::set_tax_query_vars( $query_vars );

				// @see: https://academy.bricksbuilder.io/article/filter-bricks-posts-merge_query/
				$merge_query = apply_filters( 'bricks/posts/merge_query', true, $element_id );

				/**
				 * Merge wp_query vars and posts element query vars
				 *
				 * @since 1.7: Merge query only if 'disable_query_merge' control is not set!
				 */
				if ( $merge_query && ( is_archive() || is_author() || is_search() || is_home() ) && empty( $query_vars['disable_query_merge'] ) ) {
					global $wp_query;

					$query_vars = wp_parse_args( $query_vars, $wp_query->query );
				}

				/**
				 * REST API /load_query_page adds "_merge_vars" to the query to make sure the archive context is maintained on infinite scroll
				 *
				 * @since 1.5.1
				 */
				if ( ! empty( $query_vars['_merge_vars'] ) ) {
					$merge_query_vars = $query_vars['_merge_vars'];

					unset( $query_vars['_merge_vars'] );

					$query_vars = wp_parse_args( $query_vars, $merge_query_vars );
				}

				// @see: https://academy.bricksbuilder.io/article/filter-bricks-posts-query_vars/
				// @since 1.3.6 Added $element_id
				$query_vars = apply_filters( 'bricks/posts/query_vars', $query_vars, $settings, $element_id );
				break;

			case 'term':
				// Number. Default is "0" (all) but as a safety procedure we limit the number
				$query_vars['number'] = isset( $query_vars['number'] ) ? $query_vars['number'] : get_option( 'posts_per_page' );

				$paged = self::get_paged_query_var( $query_vars );

				// Pagination: Fix the offset value (@since 1.5)
				$offset = ! empty( $query_vars['offset'] ) ? $query_vars['offset'] : 0;

				// If pagination exists, and number is limited (!= 0), use $offset as the pagination trigger
				if ( $paged !== 1 && ! empty( $query_vars['number'] ) ) {
					$query_vars['offset'] = ( $paged - 1 ) * $query_vars['number'] + $offset;
				}

				// Hide empty
				if ( isset( $query_vars['show_empty'] ) ) {
					$query_vars['hide_empty'] = false;

					unset( $query_vars['show_empty'] );
				}

				// Current Post Term - (@since 1.8.4)
				if ( isset( $query_vars['current_post_term'] ) ) {
					$query_vars['object_ids'] = get_the_ID();

					unset( $query_vars['current_post_term'] );
				}

				if ( isset( $query_vars['child_of'] ) ) {
					$query_vars['child_of'] = bricks_render_dynamic_data( $query_vars['child_of'] );
				}

				if ( isset( $query_vars['parent'] ) ) {
					$query_vars['parent'] = bricks_render_dynamic_data( $query_vars['parent'] );
				}

				// Include & Exclude terms
				if ( isset( $query_vars['tax_query'] ) ) {
					$query_vars['include'] = self::convert_terms_to_ids( $query_vars['tax_query'] );

					unset( $query_vars['tax_query'] );
				}

				if ( isset( $query_vars['tax_query_not'] ) ) {
					$query_vars['exclude'] = self::convert_terms_to_ids( $query_vars['tax_query_not'] );

					unset( $query_vars['tax_query_not'] );
				}

				// @see: https://academy.bricksbuilder.io/article/filter-bricks-terms-query_vars/
				$query_vars = apply_filters( 'bricks/terms/query_vars', $query_vars, $settings, $element_id );
				break;

			case 'user':
				// Unset post_type
				if ( isset( $query_vars['post_type'] ) ) {
					unset( $query_vars['post_type'] );
				}

				// Paged
				$query_vars['paged'] = self::get_paged_query_var( $query_vars );

				// Pagination (number, offset, paged). Default is "-1" but as a safety procedure we limit the number (0 is not allowed)
				$query_vars['number'] = ! empty( $query_vars['number'] ) ? $query_vars['number'] : get_option( 'posts_per_page' );

				// Pagination: Fix the offset value (@since 1.5)
				$offset = ! empty( $query_vars['offset'] ) ? $query_vars['offset'] : 0;

				if ( ! empty( $offset ) && $query_vars['paged'] !== 1 ) {
					$query_vars['offset'] = ( $query_vars['paged'] - 1 ) * $query_vars['number'] + $offset;
				}

				// @see: https://academy.bricksbuilder.io/article/filter-bricks-users-query_vars/
				$query_vars = apply_filters( 'bricks/users/query_vars', $query_vars, $settings, $element_id );
				break;
		}

		return $query_vars;
	}

	/**
	 * Perform the query (maybe cache)
	 *
	 * Set $this->query_result, $this->count, $this->max_num_pages
	 *
	 * @return void (@since 1.8)
	 */
	public function run() {
		$count         = $this->count;
		$max_num_pages = $this->max_num_pages;
		$query_vars    = $this->query_vars;

		switch ( $this->object_type ) {
			case 'post':
				$result = $this->run_wp_query();

				// STEP: Populate the total count
				$count = empty( $query_vars['no_found_rows'] ) ? $result->found_posts : ( is_array( $result->posts ) ? count( $result->posts ) : 0 );

				$max_num_pages = empty( $query_vars['posts_per_page'] ) ? 1 : ceil( $count / $query_vars['posts_per_page'] );
				break;

			case 'term':
				$result = $this->run_wp_term_query();

				// Pagination: Fix the offset value  (@since 1.5)
				$offset = ! empty( $query_vars['offset'] ) ? $query_vars['offset'] : 0;
				// STEP: Populate the total count
				if ( empty( $query_vars['number'] ) ) {
					$count = ! empty( $result ) && is_array( $result ) ? count( $result ) : 0;
				} else {
					$args = $query_vars;

					unset( $args['offset'] );
					unset( $args['number'] );

					// Numeric string containing the number of terms in that taxonomy or WP_Error if the taxonomy does not exist.
					$count = wp_count_terms( $args );

					if ( is_wp_error( $count ) ) {
						$count = 0;
					} else {
						$count = (int) $count;

						$count = $offset <= $count ? $count - $offset : 0;
					}
				}

				// STEP : Populate the max number of pages
				$max_num_pages = empty( $query_vars['number'] ) ? 1 : ceil( $count / $query_vars['number'] );
				break;

			case 'user':
				$users_query = $this->run_wp_user_query();

				// STEP: The query result
				$result = $users_query->get_results();

				// STEP: Populate the total count of the users in this query
				$count = $users_query->get_total();

				// Pagination: Fix the offset value (@since 1.5)
				$offset = ! empty( $query_vars['offset'] ) ? $query_vars['offset'] : 0;

				// Subtract the $offset to fix pagination
				$count = $offset <= $count ? $count - $offset : 0;

				// STEP : Populate the max number of pages
				$max_num_pages = empty( $query_vars['number'] ) ? 1 : ceil( $count / $query_vars['number'] );
				break;

			default:
				// Allow other query providers to return a query result (Woo Cart, ACF, Metabox...)
				$result = apply_filters( 'bricks/query/run', [], $this );

				$count = ! empty( $result ) && is_array( $result ) ? count( $result ) : 0;
				break;
		}

		/**
		 * Set the query result, count and max_num_pages in a centralized way
		 * Previously this was done in run_wp_query(), run_wp_term_query() and run_wp_user_query()
		 * Filters provided
		 *
		 * @see https://academy.bricksbuilder.io/article/filter-bricks-query-result/
		 * @see https://academy.bricksbuilder.io/article/filter-bricks-query-result_count/
		 *
		 * @since 1.8
		 */
		$this->query_result  = apply_filters( 'bricks/query/result', $result, $this );
		$this->count         = apply_filters( 'bricks/query/result_count', $count, $this );
		// $this->max_num_pages = apply_filters( 'bricks/query/result_max_num_pages', $max_num_pages, $this );
		$this->max_num_pages = $max_num_pages; // This value seems like not in use as max_num_pages should be coming from
	}

	/**
	 * Run WP_Term_Query
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_term_query/
	 *
	 * @return array Terms (WP_Term)
	 */
	public function run_wp_term_query() {
		// Cache?
		$result = $this->get_query_cache();

		if ( $result === false ) {
			$terms_query = new \WP_Term_Query( $this->query_vars );

			$result = $terms_query->get_terms();

			$this->set_query_cache( $result );
		}

		return $result;
	}

	/**
	 * Run WP_User_Query
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @return WP_User_Query (@since 1.8)
	 */
	public function run_wp_user_query() {
		// Cache?
		$users_query = $this->get_query_cache();

		if ( $users_query === false ) {
			$users_query = new \WP_User_Query( $this->query_vars );

			$this->set_query_cache( $users_query );
		}

		return $users_query;
	}

	/**
	 * Run WP_Query
	 *
	 * @return object
	 */
	public function run_wp_query() {
		// Cache?
		$posts_query = $this->get_query_cache();

		if ( $posts_query === false ) {
			add_action( 'pre_get_posts', [ $this, 'set_pagination_with_offset' ], 5 );
			add_filter( 'found_posts', [ $this, 'fix_found_posts_with_offset' ], 5, 2 );

			/**
			 * Use random seed when: 'orderby' is 'rand' && 'randomSeedTtl' > 0
			 *
			 * Default: 60 minutes
			 */
			$use_random_seed = isset( $this->query_vars['orderby'] ) && $this->query_vars['orderby'] === 'rand' && ! ( isset( $this->settings['query']['randomSeedTtl'] ) && absint( $this->settings['query']['randomSeedTtl'] ) === 0 );

			// @since 1.7.1 - Avoid duplicate posts when using 'rand' orderby
			if ( $use_random_seed ) {
				add_filter( 'posts_orderby', [ $this, 'set_bricks_query_loop_random_order_seed' ], 11 );
			}

			$posts_query = new \WP_Query( $this->query_vars );

			// @since 1.7.1 - Avoid duplicate posts when using 'rand' orderby
			if ( $use_random_seed ) {
				remove_filter( 'posts_orderby', [ $this, 'set_bricks_query_loop_random_order_seed' ], 11 );
			}

			remove_action( 'pre_get_posts', [ $this, 'set_pagination_with_offset' ], 5 );
			remove_filter( 'found_posts', [ $this, 'fix_found_posts_with_offset' ], 5, 2 );

			$this->set_query_cache( $posts_query );
		}

		return $posts_query;
	}

	/**
	 * Get the page number for a query based on the query var "paged"
	 *
	 * @since 1.5
	 *
	 * @return integer
	 */
	public static function get_paged_query_var( $query_vars ) {
		// @since 1.7.1 - Avoid query_var param merged accidentally if disable_query_merge is true
		$disable_query_merge = isset( $query_vars['disable_query_merge'] );

		if ( $disable_query_merge ) {
			// @since 1.7.1 - Return paged 1 if disable_query_merge is true
			return 1;
		}

		$paged = 1;

		if ( get_query_var( 'page' ) ) {
			// Check for 'page' on static front page
			$paged = get_query_var( 'page' );
		} elseif ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} else {
			$paged = ! empty( $query_vars['paged'] ) ? abs( $query_vars['paged'] ) : 1;
		}

		return intval( $paged );
	}

	/**
	 * Parse the Meta Query vars through the DD logic
	 *
	 * @Since 1.5
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public static function parse_meta_query_vars( $query_vars ) {
		if ( empty( $query_vars['meta_query'] ) ) {
			return $query_vars;
		}

		foreach ( $query_vars['meta_query'] as $key => $query_item ) {
			unset( $query_vars['meta_query'][ $key ]['id'] );

			if ( empty( $query_vars['meta_query'][ $key ]['value'] ) ) {
				continue;
			}

			$query_vars['meta_query'][ $key ]['value'] = bricks_render_dynamic_data( $query_vars['meta_query'][ $key ]['value'] );
		}

		if ( ! empty( $query_vars['meta_query_relation'] ) ) {
			$query_vars['meta_query']['relation'] = $query_vars['meta_query_relation'];
		}

		unset( $query_vars['meta_query_relation'] );

		return $query_vars;
	}

	/**
	 * Set 'tax_query' vars (e.g. Carousel, Posts, Related Posts)
	 *
	 * Include & exclude terms of different taxonomies
	 *
	 * @since 1.3.2
	 */
	public static function set_tax_query_vars( $query_vars ) {
		// Include terms
		if ( isset( $query_vars['tax_query'] ) ) {
			$terms     = $query_vars['tax_query'];
			$tax_query = [];

			foreach ( $terms as $term ) {
				if ( ! is_string( $term ) ) {
					continue;
				}

				$term_parts = explode( '::', $term );
				$taxonomy   = isset( $term_parts[0] ) ? $term_parts[0] : false;
				$term       = isset( $term_parts[1] ) ? $term_parts[1] : false;

				if ( ! $taxonomy || ! $term ) {
					continue;
				}

				if ( isset( $tax_query[ $taxonomy ] ) ) {
					$tax_query[ $taxonomy ]['terms'][] = $term;
				} else {
					$tax_query[ $taxonomy ] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => [ $term ],
					];
				}
			}

			$tax_query = array_values( $tax_query );

			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'OR';

				$query_vars['tax_query'] = [ $tax_query ];
			} else {
				$query_vars['tax_query'] = $tax_query;
			}
		}

		// Exclude terms
		if ( isset( $query_vars['tax_query_not'] ) ) {
			$terms             = $query_vars['tax_query_not'];
			$tax_query_exclude = [];

			foreach ( $query_vars['tax_query_not'] as $term ) {
				if ( ! is_string( $term ) ) {
					continue;
				}

				$term_parts = explode( '::', $term );
				$taxonomy   = $term_parts[0];
				$term       = $term_parts[1];

				if ( isset( $tax_query_exclude[ $taxonomy ] ) ) {
					$tax_query_exclude[ $taxonomy ]['terms'][] = $term;
				} else {
					$tax_query_exclude[ $taxonomy ] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => [ $term ],
						'operator' => 'NOT IN',
					];
				}
			}

			$tax_query_exclude = array_values( $tax_query_exclude );

			if ( count( $tax_query_exclude ) > 1 ) {
				$tax_query_exclude['relation'] = 'AND';

				$query_vars['tax_query'][] = [ $tax_query_exclude ];
			} else {
				$query_vars['tax_query'][] = $tax_query_exclude;
			}

			unset( $query_vars['tax_query_not'] );
		}

		if ( isset( $query_vars['tax_query_advanced'] ) ) {
			foreach ( $query_vars['tax_query_advanced'] as $tax_query ) {
				if ( empty( $tax_query['terms'] ) ) {
					continue;
				}

				$tax_query['terms'] = bricks_render_dynamic_data( $tax_query['terms'] );

				if ( strpos( $tax_query['terms'], ',' ) ) {
					$tax_query['terms'] = explode( ',', $tax_query['terms'] );
					$tax_query['terms'] = array_map( 'trim', $tax_query['terms'] );
				}

				unset( $tax_query['id'] );

				if ( isset( $tax_query['include_children'] ) ) {
					$tax_query['include_children'] = filter_var( $tax_query['include_children'], FILTER_VALIDATE_BOOLEAN );
				}

				$query_vars['tax_query'][] = $tax_query;
			}
		}

		if ( isset( $query_vars['tax_query'] ) && is_array( $query_vars['tax_query'] ) && count( $query_vars['tax_query'] ) > 1 ) {
			$query_vars['tax_query']['relation'] = isset( $query_vars['tax_query_relation'] ) ? $query_vars['tax_query_relation'] : 'AND';
		}

		unset( $query_vars['tax_query_relation'] );
		unset( $query_vars['tax_query_advanced'] );

		return $query_vars;
	}

	/**
	 * Modifies $query offset variable to make pagination work in combination with offset.
	 *
	 * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
	 * Note that the link recommends exiting the filter if $query->is_paged returns false,
	 * but then max_num_pages on the first page is incorrect.
	 *
	 * @param \WP_Query $query WordPress query.
	 */
	public function set_pagination_with_offset( $query ) {
		if ( ! isset( $this->query_vars['offset'] ) ) {
			return;
		}

		$new_offset = $this->query_vars['offset'] + ( $query->get( 'paged', 1 ) - 1 ) * $query->get( 'posts_per_page' );
		$query->set( 'offset', $new_offset );
	}

	/**
	 * By default, WordPress includes offset posts into the final post count.
	 * This method excludes them.
	 *
	 * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
	 * Note that the link recommends exiting the filter if $query->is_paged returns false,
	 * but then max_num_pages on the first page is incorrect.
	 *
	 * @param int       $found_posts Found posts.
	 * @param \WP_Query $query WordPress query.
	 * @return int Modified found posts.
	 */
	public function fix_found_posts_with_offset( $found_posts, $query ) {
		if ( ! isset( $this->query_vars['offset'] ) ) {
			return $found_posts;
		}

		return $found_posts - $this->query_vars['offset'];
	}

	/**
	 * Set the initial loop index (needed for the infinite scroll)
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function init_loop_index() {
		if ( $this->object_type == 'post' ) {
			$offset = isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;

			return $offset + ( $this->query_vars['posts_per_page'] > 0 ? ( $this->query_vars['paged'] - 1 ) * $this->query_vars['posts_per_page'] : 0 );
		} elseif ( $this->object_type == 'term' ) {
			return isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;
		} elseif ( $this->object_type == 'user' ) {
			$offset = isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;
			$page   = isset( $this->query_vars['paged'] ) ? $this->query_vars['paged'] : 1;

			return $offset + ( $this->query_vars['number'] > 0 ? ( $page - 1 ) * $this->query_vars['number'] : 0 );
		}

		return 0;
	}

	/**
	 * Main render function
	 *
	 * @param string  $callback to render each item
	 * @param array   $args callback function args
	 * @param boolean $return_array whether returns a string or an array of all the iterations
	 * @return mixed
	 */
	public function render( $callback, $args, $return_array = false ) {
		// Remove array keys
		$args = array_values( $args );

		// Query results
		$query_result = $this->query_result;

		$content = [];

		$this->loop_index = $this->init_loop_index();

		$this->is_looping = true;

		// @see https://academy.bricksbuilder.io/article/action-bricks-query-before_loop (@since 1.7.2)
		do_action( 'bricks/query/before_loop', $this, $args );

		// Query is empty
		if ( empty( $this->count ) ) {
			$content[] = $this->get_no_results_content();
		}

		// Iterate
		else {
			// STEP: Loop posts
			if ( $this->object_type == 'post' ) {

				$this->original_post_id = get_the_ID();

				while ( $query_result->have_posts() ) {
					$query_result->the_post();

					$this->loop_object = get_post();

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Loop terms
			elseif ( $this->object_type == 'term' ) {
				foreach ( $query_result as $term_object ) {
					$this->loop_object = $term_object;

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Loop users
			elseif ( $this->object_type == 'user' ) {
				foreach ( $query_result as $user_object ) {
					$this->loop_object = $user_object;

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Other render providers (wooCart, ACF repeater, Meta Box groups)
			else {
				$this->original_post_id = get_the_ID();

				foreach ( $query_result as $loop_key => $loop_object ) {
					// @see: https://academy.bricksbuilder.io/article/filter-bricks-query-loop_object/
					$this->loop_object = apply_filters( 'bricks/query/loop_object', $loop_object, $loop_key, $this );

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}
		}

		// @see https://academy.bricksbuilder.io/article/action-bricks-query-after_loop (@since 1.7.2)
		do_action( 'bricks/query/after_loop', $this, $args );

		$this->loop_object = null;

		$this->is_looping = false;

		$this->reset_postdata();

		return $return_array ? $content : implode( '', $content );
	}

	public static function parse_dynamic_data( $content, $post_id ) {
		if ( is_array( $content ) ) {
			if ( isset( $content['background']['image']['useDynamicData'] ) ) {
				$size = isset( $content['background']['image']['size'] ) ? $content['background']['image']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

				$images = Integrations\Dynamic_Data\Providers::render_tag( $content['background']['image']['useDynamicData'], $post_id, 'image', [ 'size' => $size ] );

				if ( isset( $images[0] ) ) {
					$content['background']['image']['url'] = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $size ) : $images[0];

					unset( $content['background']['image']['useDynamicData'] );
				}
			}

			return map_deep( $content, [ 'Bricks\Integrations\Dynamic_Data\Providers', 'render_content' ] );
		} else {
			return bricks_render_dynamic_data( $content, $post_id );
		}
	}

	/**
	 * Reset the global $post to the parent query or the global $wp_query
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function reset_postdata() {

		// Reset is not needed
		if ( empty( $this->original_post_id ) ) {
			return;
		}

		$looping_query_id = self::is_any_looping();

		// Not a nested query, reset global query
		if ( ! $looping_query_id ) {
			wp_reset_postdata();
		}

		// Set the parent query context
		global $post;

		$post = get_post( $this->original_post_id );

		setup_postdata( $post );
	}

	/**
	 * Get the current Query object
	 *
	 * @return Query
	 */
	public static function get_query_object( $query_id = false ) {
		global $bricks_loop_query;

		if ( ! is_array( $bricks_loop_query ) || $query_id && ! array_key_exists( $query_id, $bricks_loop_query ) ) {
			return false;
		}

		return $query_id ? $bricks_loop_query[ $query_id ] : end( $bricks_loop_query );
	}

	/**
	 * Get the current Query object type
	 *
	 * @return string
	 */
	public static function get_query_object_type( $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		return $query ? $query->object_type : '';
	}

	/**
	 * Get the object of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object( $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		return $query ? $query->loop_object : null;
	}

	/**
	 * Get the object ID of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object_id( $query_id = '' ) {
		$object = self::get_loop_object( $query_id );

		$object_id = 0;

		if ( is_a( $object, 'WP_Post' ) ) {
			$object_id = $object->ID;
		}

		if ( is_a( $object, 'WP_Term' ) ) {
			$object_id = $object->term_id;
		}

		if ( is_a( $object, 'WP_User' ) ) {
			$object_id = $object->ID;
		}

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-query-loop_object_id/
		return apply_filters( 'bricks/query/loop_object_id', $object_id, $object, $query_id );
	}

	/**
	 * Get the object type of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object_type( $query_id = '' ) {
		$object = self::get_loop_object( $query_id );

		$object_type = null;

		if ( is_a( $object, 'WP_Post' ) ) {
			$object_type = 'post';
		}

		if ( is_a( $object, 'WP_Term' ) ) {
			$object_type = 'term';
		}

		if ( is_a( $object, 'WP_User' ) ) {
			$object_type = 'user';
		}

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-query-loop_object_type/
		return apply_filters( 'bricks/query/loop_object_type', $object_type, $object, $query_id );
	}

	/**
	 * Get the current loop iteration index
	 *
	 * @return mixed
	 */
	public static function get_loop_index() {
		$query = self::get_query_object();

		return $query && $query->is_looping ? $query->loop_index : '';
	}

	/**
	 * Check if the render function is looping (in the current query)
	 *
	 * @param string $element_id if specificed checks if the element_id matches the element that is set to loop (e.g. container)
	 * @return boolean
	 */
	public static function is_looping( $element_id = '', $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		if ( ! $query ) {
			return false;
		}

		if ( empty( $element_id ) ) {
			return $query->is_looping;
		}

		// Still here, search for the element_id query
		$query = self::get_query_for_element_id( $element_id );

		return $query ? $query->is_looping : false;
	}

	/**
	 * Get query object created for a specific element ID
	 *
	 * @param string $element_id
	 * @return mixed
	 */
	public static function get_query_for_element_id( $element_id = '' ) {
		if ( empty( $element_id ) ) {
			return false;
		}

		global $bricks_loop_query;

		if ( empty( $bricks_loop_query ) ) {
			return false;
		}

		foreach ( $bricks_loop_query as $key => $query ) {
			if ( $query->element_id == $element_id ) {
				return $query;
			}
		}

		return false;
	}

	/**
	 * Get element ID of query loop element
	 *
	 * @param object $query Defaults to current query.
	 *
	 * @since 1.4
	 *
	 * @return string|boolean Element ID or false
	 */
	public static function get_query_element_id( $query = '' ) {
		$query = self::get_query_object( $query );

		return ! empty( $query->element_id ) ? $query->element_id : false;
	}

	/**
	 * Check if there is any active query looping (nested queries) and if yes, return the query ID of the most deep query
	 *
	 * @return mixed
	 */
	public static function is_any_looping() {
		global $bricks_loop_query;

		if ( empty( $bricks_loop_query ) ) {
			return false;
		}

		$query_ids = array_reverse( array_keys( $bricks_loop_query ) );

		foreach ( $query_ids as $query_id ) {
			if ( $bricks_loop_query[ $query_id ]->is_looping ) {
				return $query_id;
			}
		}

		return false;
	}

	/**
	 * Convert a list of option strings taxonomy::term_id into a list of term_ids
	 */
	public static function convert_terms_to_ids( $terms = [] ) {
		if ( empty( $terms ) ) {
			return [];
		}

		$options = [];

		foreach ( $terms as $term ) {
			if ( ! is_string( $term ) ) {
				continue;
			}

			$term_parts = explode( '::', $term );
			// $taxonomy   = $term_parts[0];

			$options[] = $term_parts[1];
		}

		return $options;
	}

	public function get_no_results_content() {
		// Return: Avoid showing no results message when infinite scroll is enabled (@since 1.5.6)
		if ( bricks_is_rest_call() ) {
			return '';
		}

		$content = isset( $this->settings['query']['no_results_text'] ) ? $this->settings['query']['no_results_text'] : '';

		if ( ! empty( $content ) ) {
			$content = '<div class="bricks-posts-nothing-found"><p>' . $content . '</p></div>';
			$content = bricks_render_dynamic_data( $content );
			$content = do_shortcode( $content );
		}

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-query_no_results_content/
		$content = apply_filters( 'bricks/query/no_results_content', $content, $this->settings, $this->element_id );

		return $content;
	}

	/**
	 * Use random seed to make sure the order is the same for all queries of the same element
	 *
	 * The transient is also deleted when the random seed setting inside the query loop control is changed.
	 *
	 * @param string $order_statement
	 * @return string
	 * @since 1.7.1
	 */
	public function set_bricks_query_loop_random_order_seed( $order_statement ) {
		// Transient name is based on the element ID
		$transient_name = "bricks_query_loop_random_seed_{$this->element_id}";
		$random_seed    = get_transient( $transient_name );

		if ( ! $random_seed ) {
			// Generate a random seed for this query
			$random_seed = rand( 0, 99999 );

			// Default transient TTL is 60 minutes
			$random_seed_ttl = ! empty( $this->settings['query']['randomSeedTtl'] ) ? absint( $this->settings['query']['randomSeedTtl'] ) : 60;

			set_transient( $transient_name, $random_seed, $random_seed_ttl * MINUTE_IN_SECONDS );
		}

		$order_statement = 'RAND(' . $random_seed . ')';

		return $order_statement;
	}

	/**
	 * All query arguments that can be set for the archive query
	 * https://developer.wordpress.org/reference/classes/wp_query/#parameters
	 *
	 * @return array
	 *
	 * @since 1.8
	 */
	public static function archive_query_arguments() {
		$arguments = [
			'post_type',
			'post_status',
			'p',
			'page_id',
			'name',
			'pagename',
			'page',
			'hour',
			'minute',
			'second',
			'year',
			'monthnum',
			'day',
			'w',
			'm',
			'cat',
			'category_name',
			'category__and',
			'category__in',
			'category__not_in',
			'tag',
			'tag_id',
			'tag__and',
			'tag__in',
			'tag__not_in',
			'tag_slug__and',
			'tag_slug__in',
			'taxonomy',
			'term',
			'field',
			'operator',
			'include_children',
			'paged',
			'posts_per_page',
			'nopaging',
			'offset',
			'ignore_sticky_posts',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'post__in',
			'post__not_in',
			'post_name__in',
			'author',
			'author_name',
			'author__in',
			'author__not_in',
			's',
			'exact',
			'sentence',
			'meta_key',
			'meta_value',
			'meta_value_num',
			'meta_compare',
			'meta_query',
			'date_query',
			'cache_results',
			'update_post_term_cache',
			'update_post_meta_cache',
			'no_found_rows',
			'order',
			'orderby',
			'perm',
			'post_mime_type',
			'comment_count',
			'comment_status',
			'post_comment_status',
		];

		// NOTE: Undocumented
		return apply_filters( 'bricks/query/archive_query_arguments', $arguments );
	}

	/**
	 * All bricks query object types that can be set for the archive query.
	 * If there is custom query by user and it might be used as archive query, should be added here.
	 *
	 * @return array
	 *
	 * @since 1.8
	 */
	public static function archive_query_supported_object_types() {
		$object_types = [
			'post',
			'term',
			'user',
		];

		// NOTE: Undocumented
		return apply_filters( 'bricks/query/archive_query_supported_object_types', $object_types );
	}

}
