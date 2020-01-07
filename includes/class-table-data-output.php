<?php
/**
 * @package WordPress
 * @subpackage BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2019, Themekraft
 * @link http://themkraft.com/
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuddyFormsFrontendTableDataOutput {
	public function __construct() {
		add_filter( 'buddyforms_locate_template', array( $this, 'override_shortcode_template' ), 10, 3 );
		add_filter( 'buddyforms_granted_list_post_style', array( $this, 'override_list_post_style' ), 10, 1 );
		add_action( 'wp_footer', array( $this, 'include_assets' ) );
		add_action( 'wp_ajax_buddyforms_data_table', array( $this, 'ajax_get_buddyforms_data_table' ) );
		add_action( 'wp_ajax_nopriv_buddyforms_data_table', array( $this, 'ajax_get_buddyforms_data_table' ) );
		add_action( 'wp_ajax_buddyforms_data_table_autocomplete', array( $this, 'ajax_get_buddyforms_data_table_autocomplete' ) );
		add_action( 'wp_ajax_nopriv_buddyforms_data_table_autocomplete', array( $this, 'ajax_get_buddyforms_data_table_autocomplete' ) );
	}

	public function include_assets() {
		if ( BuddyFormsFrontendTable::getNeedAssets() ) {
			wp_enqueue_script( 'buddyforms-datatable', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'DataTables/datatables.min.js', array( 'jquery' ), BuddyFormsFrontendTable::getVersion() );
			$posts_per_page = apply_filters( 'buddyforms_user_posts_query_args_posts_per_page', 10 );
			wp_localize_script( 'buddyforms-datatable', 'buddyformsDatatable', array(
				'ajax'        => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( __DIR__ . 'buddyforms-datatable' ),
				'alwaysOpen'  => apply_filters( 'buddyforms_datatable_always_open', false ),
				'childFullTable'  => apply_filters( 'buddyforms_datatable_always_open_child_full_table', false ),
				//https://datatables.net/examples/advanced_init/length_menu
				'lengthMenu'  => apply_filters( 'buddyforms_datatable_length_menu', array( array( 10, 25, 50, 100 ), array( 10, 25, 50, 100 ) ) ),
				//https://datatables.net/reference/option/pageLength
				'pageLength'  => apply_filters( 'buddyforms_datatable_page_length', $posts_per_page ),
				//https://datatables.net/reference/option/info
				'info'        => apply_filters( 'buddyforms_datatable_info', false ),
				//https://datatables.net/reference/option/paging
				'paging'      => apply_filters( 'buddyforms_datatable_paging', true ),
				//https://datatables.net/reference/option/stateSave
				'stateSave'   => apply_filters( 'buddyforms_datatable_state_false', false ),
				//https://datatables.net/reference/option/processing
				'processing'  => apply_filters( 'buddyforms_datatable_processing', true ),
				//https://datatables.net/reference/option/searching
				'searching'   => apply_filters( 'buddyforms_datatable_searching', true ),
				//https://datatables.net/reference/option/searchDelay
				'searchDelay' => apply_filters( 'buddyforms_datatable_search_delay', 400 ),
				//https://datatables.net/reference/option/dom
				'dom' => apply_filters( 'buddyforms_datatable_dom', '<"fg-toolbar ui-toolbar ui-widget-header ui-helper-clearfix ui-corner-tl ui-corner-tr"flr>t<"fg-toolbar ui-toolbar ui-widget-header ui-helper-clearfix ui-corner-bl ui-corner-br"ip>' ),
				//https://datatables.net/reference/option/language
				'language'    => apply_filters( 'buddyforms_datatable_language', array(
					"decimal"        => "",
					"emptyTable"     => __( "No data available in table", 'buddyforms-frontend-table' ),
					"info"           => __( "Showing _START_ to _END_ of _TOTAL_ entries", 'buddyforms-frontend-table' ),
					"infoEmpty"      => __( "Showing 0 to 0 of 0 entries", 'buddyforms-frontend-table' ),
					"infoFiltered"   => __( "(filtered from _MAX_ total entries)", 'buddyforms-frontend-table' ),
					"infoPostFix"    => "",
					"thousands"      => ",",
					"lengthMenu"     => __( "Show _MENU_ entries", 'buddyforms-frontend-table' ),
					"loadingRecords" => __( "Loading...", 'buddyforms-frontend-table' ),
					"processing"     => __( "Processing...", 'buddyforms-frontend-table' ),
					"search"         => __( "Search:", 'buddyforms-frontend-table' ),
					"zeroRecords"    => __( "No matching records found", 'buddyforms-frontend-table' ),
					"paginate"       => array(
						"first"    => __( "First", 'buddyforms-frontend-table' ),
						"last"     => __( "Last", 'buddyforms-frontend-table' ),
						"next"     => __( "Next", 'buddyforms-frontend-table' ),
						"previous" => __( "Previous", 'buddyforms-frontend-table' )
					),
					"aria"           => array(
						"sortAscending"  => __( ": activate to sort column ascending", 'buddyforms-frontend-table' ),
						"sortDescending" => __( ": activate to sort column descending", 'buddyforms-frontend-table' )
					)
				) ),
			) );
			wp_enqueue_script( 'buddyforms-datatable-script', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'js/script.js', array( 'jquery', 'buddyforms-datatable' ), BuddyFormsFrontendTable::getVersion() );
			wp_enqueue_style( 'buddyforms-datatable-style', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'css/style.css', array(), BuddyFormsFrontendTable::getVersion() );
			wp_enqueue_style( 'buddyforms-datatable-style-jquery', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'DataTables/DataTables/css/jquery.dataTables.min.css', array(), BuddyFormsFrontendTable::getVersion() );
			wp_enqueue_script( 'buddyforms-datatable-autocomplete', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'devbridge.autocomplete/jquery.autocomplete.min.js', array( 'buddyforms-datatable-script' ), BuddyFormsFrontendTable::getVersion() );
		}
	}

	public function ajax_get_buddyforms_data_table_autocomplete() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				die();
			}
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['nonce'] ) || empty( $_POST['form_slug'] ) ) {
				die();
			}
			if ( ! wp_verify_nonce( $_POST['nonce'], __DIR__ . 'buddyforms-datatable' ) ) {
				die();
			}

			$form_slug     = buddyforms_sanitize_slug( $_POST['form_slug'] );
			$query         = ! empty( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : false;
			$target_column = isset( $_POST['target_column'] ) && isset( $_POST['target_column'][0] ) ? intval( $_POST['target_column'][0] ) : false;

			if ( empty( $query ) || $target_column === false || empty( $form_slug ) ) {
				die();
			}

			// if multi site is enabled switch to the form blog id
			buddyforms_switch_to_form_blog( $form_slug );

			$result              = new stdClass();
			$result->query       = $query;
			$result->suggestions = array();

			$all_fields = buddyforms_get_form_fields( $form_slug );
			if ( empty( $all_fields ) ) {
				die();
			}
			$fields = array();
			foreach ( $all_fields as $all_field_key => $all_field_data ) {
				if ( ! empty( $all_field_data['frontend_table'] ) && ! empty( $all_field_data['frontend_table'][0] ) && $all_field_data['frontend_table'][0] === 'enabled' ) {
					$fields[ $all_field_key ] = $all_field_data;
				}
			}

			$fields_keys = array_keys( $fields );

			$cached_key = 'buddyforms_datatable_autocomplete_' . $query . '_' . $target_column;
			$output     = wp_cache_get( $cached_key, BuddyFormsFrontendTable::getSlug() );
			if ( $output === false ) {
				$target_field_id = ! empty( $fields_keys[ $target_column ] ) ? $fields_keys[ $target_column ] : false;
				if ( ! empty( $target_field_id ) ) {
					$target_field = $fields[ $target_field_id ];
					$field_type   = $target_field['type'];
					//Capture the field value and cache it
					switch ( $field_type ) {
						case 'country':
							if ( ! empty( $target_field['country_list'] ) ) {
								$country_list = json_decode( $target_field['country_list'], true );
								if ( ! empty( $country_list ) ) {
									foreach ( $country_list as $code => $name ) {
										if ( ! empty( $code ) && ! empty( $name ) ) {
											if ( stristr( $name, $query ) !== false || stristr( $code, $query ) !== false ) {
												$output[ $code ] = $name;
											}
										}
									}
								}
							}
							break;
						case 'state':
							if ( ! empty( $target_field['state_list'] ) ) {
								$state_list = json_decode( $target_field['state_list'], true );
								if ( ! empty( $state_list ) ) {
									foreach ( $state_list as $key => $state ) {
										if ( ! empty( $key ) && $key !== 'nostate' && ! empty( $state ) ) {
											foreach ( $state as $code => $name ) {
												if ( ! empty( $code ) && ! empty( $name ) ) {
													if ( stristr( $name, $query ) !== false || stristr( $code, $query ) !== false ) {
														$output[ $code ] = $name;
													}
												}
											}
										}
									}
								}
							}
							break;
					}
				}
				if ( ! empty( $output ) ) {
					wp_cache_set( $cached_key, $output, BuddyFormsFrontendTable::getSlug() );
				}
			}
			if ( ! empty( $output ) ) {
				//transform the field value to the correct output
				foreach ( $output as $data => $value ) {
					if ( ! empty( $data ) && ! empty( $value ) ) {
						$item                  = new stdClass();
						$item->value           = $value;
						$item->data            = $data;
						$result->suggestions[] = $item;
					}
				}
			}

			// If multi site is enabled we should restore now to the current blog.
			if ( buddyforms_is_multisite() ) {
				restore_current_blog();
			}

			wp_send_json( $result );
		} catch ( Exception $ex ) {
			BuddyFormsFrontendTable::error_log( $ex->getMessage() );
		}
		die();
	}

	public function buddyforms_cast_field_type( $field_type ) {
		switch ( $field_type ) {
			case 'time':
			case 'date':
				$cast = 'DATETIME';
				break;
			default:
				$cast = 'CHAR';
		}

		return $cast;
	}

	public function ajax_get_buddyforms_data_table() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				die();
			}
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['nonce'] ) || empty( $_POST['form_slug'] ) ) {
				die();
			}
			if ( ! wp_verify_nonce( $_POST['nonce'], __DIR__ . 'buddyforms-datatable' ) ) {
				die();
			}

			$form_slug = buddyforms_sanitize_slug( $_POST['form_slug'] );

			if ( empty( $form_slug ) ) {
				die();
			}

			global $buddyforms;

			// if multi site is enabled switch to the form blog id
			buddyforms_switch_to_form_blog( $form_slug );

			$has_action   = isset( $_POST['has_action'] ) ? boolval( $_POST['has_action'] ) : false;
			$draw         = isset( $_POST['draw'] ) ? intval( $_POST['draw'] ) : 1;
			$start        = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 0;
			$length       = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : apply_filters( 'buddyforms_user_posts_query_args_posts_per_page', 10 );
			$search_value = isset( $_POST['search'] ) && ! empty( $_POST['search']['value'] ) ? sanitize_text_field( $_POST['search']['value'] ) : false;

			$result = array( 'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array() );

			$post_type = $buddyforms[ $form_slug ]['post_type'];

			$post_status = apply_filters( 'buddyforms_shortcode_the_loop_post_status', array(
				'publish',
				'pending',
				'draft',
				'future'
			), $form_slug );

			$the_author_id = apply_filters( 'buddyforms_the_loop_author_id', get_current_user_id(), $form_slug );

			if ( ! $the_author_id ) {
				$post_status = array( 'publish' );
			}

			$all_fields = buddyforms_get_form_fields( $form_slug );
			if ( empty( $all_fields ) ) {
				die();
			}
			$fields = array();
			foreach ( $all_fields as $all_field_key => $all_field_data ) {
				if ( ! empty( $all_field_data['frontend_table'] ) && ! empty( $all_field_data['frontend_table'][0] ) && $all_field_data['frontend_table'][0] === 'enabled' ) {
					$fields[ $all_field_key ] = $all_field_data;
				}
			}

			$fields_keys = array_keys( $fields );

			$order_target_column = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? $_POST['order'] : false;
			$target_column       = isset( $_POST['columns'] ) && is_array( $_POST['columns'] ) ? $_POST['columns'] : false;

			$apply_order = isset( $order_target_column ) && isset( $order_target_column[0]['column'] );

			$meta_query = array(
				'relation'    => 'AND',
				'form_clause' => array(
					'key'   => '_bf_form_slug',
					'value' => $form_slug,
				),
			);

			$extra_ordering = array();
			if ( $apply_order ) {
				foreach ( $order_target_column as $item_order ) {
					if ( isset( $item_order['column'] ) ) {
						$target_field_id = ! empty( $fields_keys[ intval( $item_order['column'] ) ] ) ? $fields_keys[ intval( $item_order['column'] ) ] : false;
						if ( ! empty( $target_field_id ) ) {
							$target_field                                      = $fields[ $target_field_id ];
							$meta_key                                          = $target_field['slug'] . '_clause';
							$extra_ordering[ intval( $item_order['column'] ) ] = $meta_key;
							$meta_query[ $meta_key ]                           = array(
								'key'     => $target_field['slug'],
								'compare' => 'EXIST',
								'type'    => $this->buddyforms_cast_field_type( $target_field['type'] )
							);
						}
					}

				}
			}

			$exist_title   = buddyforms_exist_field_type_in_form( $form_slug, 'buddyforms_form_title' );
			$exist_content = buddyforms_exist_field_type_in_form( $form_slug, 'buddyforms_form_content' );

			if ( ! empty( $search_value ) ) {
				if ( $exist_title || $exist_content ) {
					$query_args['s'] = $search_value;
				} else {
					$search_query = array( 'relation' => 'OR', );
					foreach ( $fields as $field ) {
						$meta_key                  = $field['slug'] . '_clause';
						$search_query[ $meta_key ] = array(
							'key'     => $field['slug'],
							'value'   => apply_filters( 'buddyforms_datatable_search_value', $search_value, $field, $meta_key, $form_slug ),
							'compare' => apply_filters( 'buddyforms_datatable_search_compare', 'LIKE', $field, $meta_key, $form_slug ),
						);
					}
					$meta_query[] = $search_query;
				}
			}

			foreach ( $target_column as $item_column ) {
				if ( isset( $item_column['data'] ) && isset( $item_column['search'] ) && isset( $item_column['search']['value'] ) && strlen( $item_column['search']['value'] ) > 0 ) {
					$target_field_id = ! empty( $fields_keys[ $item_column['data'] ] ) ? $fields_keys[ $item_column['data'] ] : false;
					if ( ! empty( $target_field_id ) ) {
						$target_field            = $fields[ $target_field_id ];
						$meta_key                = $target_field['slug'] . '_clause';
						$meta_query[ $meta_key ] = array(
							'key'     => $target_field['slug'],
							'value'   => apply_filters( 'buddyforms_datatable_meta_value', $item_column['search']['value'], $item_column, $meta_key, $target_field, $form_slug ),
							'compare' => apply_filters( 'buddyforms_datatable_meta_compare', '=', $item_column, $meta_key, $target_field, $form_slug ),
						);
					}
				}
			}

			$query_args = array(
				'fields'         => 'ids',
				'post_type'      => $post_type,
				'form_slug'      => $form_slug,
				'post_status'    => $post_status,
				'posts_per_page' => $length,
				'offset'         => $start,
				'meta_query'     => $meta_query,
			);

			if ( $apply_order && ! empty( $extra_ordering ) ) {
				$ordering = array();
				foreach ( $order_target_column as $item_order ) {
					if ( isset( $item_order['column'] ) ) {
						$order    = ! empty( $item_order['dir'] ) ? $item_order['dir'] : 'ASC';
						$meta_key = isset( $extra_ordering[ $item_order['column'] ] ) ? $extra_ordering[ $item_order['column'] ] : false;
						if ( ! empty( $meta_key ) ) {
							$ordering[ $meta_key ] = $order;
						}
					}
				}
				$query_args['orderby'] = $ordering;
			}

			if ( ! current_user_can( 'buddyforms_' . $form_slug . '_all' ) ) {
				$query_args['author'] = $the_author_id;
			}

			$query_args = apply_filters( 'buddyforms_user_posts_query_args', $query_args );

			do_action( 'buddyforms_the_loop_start', $query_args );

			$the_lp_query = new WP_Query( $query_args );
			$the_lp_query = apply_filters( 'buddyforms_the_lp_query', $the_lp_query );

			if ( $the_lp_query->have_posts() ) {
				$posts                     = $the_lp_query->get_posts();
				$result['recordsTotal']    = intval( $the_lp_query->post_count );
				$result['recordsFiltered'] = intval( $the_lp_query->found_posts );
				foreach ( $posts as $post_id ) {
					$entry_metas = buddyforms_get_post_field_meta( $post_id, $fields );
					if ( ! empty( $entry_metas ) ) {
						$final_result = array();
						foreach ( $entry_metas as $entry_meta ) {
							$final_result[] = isset( $entry_meta['value'] ) ? $entry_meta['value'] : '';
						}
						if ( $has_action ) {
							$include_edit_action = apply_filters( 'buddyforms_datatable_include_bf_action', true, $post_id, $form_slug, $fields, $entry_metas );
							ob_start();
							echo '<div class="action"><div class="meta">';
							if ( $include_edit_action ) {
								global $post;
								$post = get_post( $post_id, OBJECT );
								setup_postdata( $post );
								buddyforms_post_entry_actions( $form_slug );

							}
							do_action( 'buddyforms_the_loop_after_actions', $post_id, $form_slug );
							echo '</div></div>';
							wp_reset_postdata();
							$action_html    = ob_get_clean();
							$action_html    = apply_filters( 'buddyforms_datatable_action_html', $action_html, $post_id, $form_slug, $fields, $entry_metas );
							$final_result[] = $action_html;
						}
						$result['data'][] = $final_result;
					}
				}

			}

			do_action( 'buddyforms_the_loop_end', $query_args );

			// If multi site is enabled we should restore now to the current blog.
			if ( buddyforms_is_multisite() ) {
				restore_current_blog();
			}

			wp_send_json( $result );
		} catch ( Exception $ex ) {
			BuddyFormsFrontendTable::error_log( $ex->getMessage() );
		}
		die();
	}

	public function override_list_post_style( $list_post_style ) {
		return array_merge( $list_post_style, array( 'data-table' ) );
	}

	public static function get_table_columns( $form_slug ) {
		$columns = array();
		$fields  = buddyforms_get_form_fields( $form_slug );
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field_id => $field_data ) {
				$columns[ $field_data['slug'] ] = $field_data;
			}
		}

		return $columns;
	}

	public function override_shortcode_template( $template_path, $slug, $form_slug ) {
		if ( ! empty( $template_path ) && ! empty( $slug ) && $slug === 'data-table' && ! empty( $form_slug ) ) {
			$template_path = BUDDYFORMS_FRONTEND_TABLE_VIEW . 'table.php';
			BuddyFormsFrontendTable::setNeedAssets( true );
		}

		return $template_path;
	}
}
