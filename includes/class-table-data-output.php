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
			wp_localize_script( 'buddyforms-datatable', 'buddyformsDatatable', array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( __DIR__ . 'buddyforms-datatable' )
			) );
			wp_enqueue_script( 'buddyforms-datatable-script', BUDDYFORMS_FRONTEND_TABLE_ASSETS . 'js/script.js', array( 'jquery', 'buddyforms-datatable' ), BuddyFormsFrontendTable::getVersion() );
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

			$result = array( 'draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array() );

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

			$paged = buddyforms_get_url_var( 'page' );

			$query_args = array(
				'fields'         => 'ids',
				'post_type'      => $post_type,
				'form_slug'      => $form_slug,
				'post_status'    => $post_status,
				'posts_per_page' => apply_filters( 'buddyforms_user_posts_query_args_posts_per_page', '' ),
				'paged'          => $paged,
				'meta_key'       => '_bf_form_slug',
				'meta_value'     => $form_slug
			);

			if ( ! current_user_can( 'buddyforms_' . $form_slug . '_all' ) ) {
				$query_args['author'] = $the_author_id;
			}

			$query_args = apply_filters( 'buddyforms_user_posts_query_args', $query_args );

			do_action( 'buddyforms_the_loop_start', $query_args );

			$the_lp_query = new WP_Query( $query_args );
			$the_lp_query = apply_filters( 'buddyforms_the_lp_query', $the_lp_query );

			if ( $the_lp_query->have_posts() ) {
				$fields = buddyforms_get_form_fields( $form_slug );
				$posts  = $the_lp_query->get_posts();
				$result['recordsTotal'] = $the_lp_query->found_posts;
				$result['recordsFiltered'] = $the_lp_query->found_posts;
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
				$columns[ $field_data['slug'] ] = $field_data['name'];
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
