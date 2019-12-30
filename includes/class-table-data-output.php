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
	}

	public function include_assets() {
		if ( BuddyFormsFrontendTable::getNeedAssets() ) {
			wp_enqueue_script( 'buddyforms-datatable', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'DataTables/datatables.min.js', array( 'jquery' ), BuddyFormsFrontendTable::getVersion() );
			$posts_per_page = apply_filters( 'buddyforms_user_posts_query_args_posts_per_page', 10 );
			wp_localize_script( 'buddyforms-datatable', 'buddyformsDatatable', array(
				'ajax'        => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( __DIR__ . 'buddyforms-datatable' ),
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
			wp_enqueue_style( 'buddyforms-datatable', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'DataTables/DataTables/css/jquery.dataTables.min.css', array(), BuddyFormsFrontendTable::getVersion() );
		}
	}

	public function ajax_get_buddyforms_data_table() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['nonce'] ) || empty( $_POST['form_slug'] ) ) {
				die();
			}
			if ( ! wp_verify_nonce( $_POST['nonce'], __DIR__ . 'buddyforms-datatable' ) ) {
				die();
			}
			global $buddyforms;

			$form_slug = buddyforms_sanitize_slug( $_POST['form_slug'] );

			if ( empty( $form_slug ) ) {
				return;
			}

			// if multi site is enabled switch to the form blog id
			buddyforms_switch_to_form_blog( $form_slug );

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
				return;
			}
			$fields = array();
			foreach ( $all_fields as $all_field_key => $all_field_data ) {
				if ( ! empty( $all_field_data['frontend_table'] ) && ! empty( $all_field_data['frontend_table'][0] ) && $all_field_data['frontend_table'][0] === 'enabled' ) {
					$fields[ $all_field_key ] = $all_field_data;
				}
			}

			$fields_keys = array_keys( $fields );

			$order_target_column = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? $_POST['order'] : false;

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
				foreach ( $order_target_column as $item ) {
					if ( isset( $item['column'] ) ) {
						$target_field_id = ! empty( $fields_keys[ $item['column'] ] ) ? $fields_keys[ $item['column'] ] : false;
						if ( ! empty( $target_field_id ) ) {
							$target_field                      = $fields[ $target_field_id ];
							$order_key                         = $target_field['slug'] . '_clause';
							$extra_ordering[ $item['column'] ] = $order_key;
							$meta_query[ $order_key ]          = array(
								'key'     => $target_field['slug'],
								'compare' => 'EXISTS',
							);
						}
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
				foreach ( $order_target_column as $item ) {
					if ( isset( $item['column'] ) ) {
						$order     = ! empty( $item['dir'] ) ? $item['dir'] : 'ASC';
						$order_key = isset( $extra_ordering[ $item['column'] ] ) ? $extra_ordering[ $item['column'] ] : false;
						if ( ! empty( $order_key ) ) {
							$ordering[ $order_key ] = $order;
						}
					}
				}
				$query_args['orderby'] = $ordering;
			}

			if ( ! empty( $search_value ) ) {
				$query_args['s'] = $search_value;
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
						$result['data'][] = $final_result;
					}
				}

			}

			wp_reset_postdata();

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
