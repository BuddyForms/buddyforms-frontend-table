<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$columns        = BuddyFormsFrontendTableDataOutput::get_table_columns( $form_slug );
$footer_enabled = true;

$need_filter_container = false;
$initial_order         = '';
$thead                 = '<tr>';
$i                     = 0;
foreach ( $columns as $column_id => $columns_data ) {
	$sortable_string      = '';
	$is_filterable_string = '';
	$is_sortable          = ( ! empty( $columns_data['frontend_table_sortable'] ) && ! empty( $columns_data['frontend_table_sortable'][0] ) && $columns_data['frontend_table_sortable'][0] === 'enabled' );
	if ( $is_sortable && empty( $initial_order ) ) {
		$initial_order = "data-order='[[ " . $i . ", \"asc\" ]]'";
	}
	if ( ! $is_sortable ) {
		$sortable_string = 'data-sortable="false"';
	}
	$is_filterable = ( ! empty( $columns_data['frontend_table_filter'] ) && ! empty( $columns_data['frontend_table_filter'][0] ) && $columns_data['frontend_table_filter'][0] === 'enabled' );
	if ( ! $is_filterable ) {
		$is_filterable_string = 'data-searchable="false"';
	} else {
		$is_filterable_string  = 'data-searchable="true"';
		$need_filter_container = true;
	}
	if ( ! empty( $columns_data ) ) {
		if ( ! empty( $columns_data['frontend_table'] ) && ! empty( $columns_data['frontend_table'][0] ) && $columns_data['frontend_table'][0] === 'enabled' ) {
			$thead .= ' <th data-priority="' . $i . '" ' . $is_filterable_string . ' ' . $sortable_string . ' data-field-slug="' . $column_id . '">' . esc_html( $columns_data['name'] ) . '</th>';
		}
	}
	$i ++;
}
$thead .= '</tr>';
?>
<div id="buddyforms-data-table-view-<?php echo $form_slug ?>" class="buddyforms_data_table buddyforms-posts-container">
	<?php if ( ! empty( $columns ) ) : ?>
		<?php if ( ! empty( $need_filter_container ) ) : ?>
			<div class="buddyforms-data-table-filter-container" id="buddyforms-data-table-filter-container-<?php echo $form_slug ?>">
			</div>
		<?php endif; ?>
		<table id="buddyforms-data-table-<?php echo $form_slug ?>" class="display" style="width:100%" data-form-slug="<?php echo $form_slug ?>" <?php echo $initial_order ?>>
			<thead>
			<?php echo $thead ?>
			</thead>
			<tbody>

			</tbody>
			<tfoot>
			<?php if ( $footer_enabled ): ?>
				<?php echo $thead ?>
			<?php endif; ?>
			</tfoot>
		</table>
	<?php endif; ?>
</div>
