<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$columns        = BuddyFormsFrontendTableDataOutput::get_table_columns( $form_slug );
$footer_enabled = true;
?>
<div id="buddyforms-data-table-view" class="buddyforms_data_table buddyforms-posts-container">
	<?php if ( ! empty( $columns ) ) : ?>
		<table id="example" class="display" style="width:100%" data-form-slug="<?php echo $form_slug ?>">
			<thead>
			<tr>
				<?php foreach ( $columns as $column_id => $column_name ) : ?>
					<th data-field-slug="<?php echo $column_id ?>"><?php echo esc_html( $column_name ) ?></th>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody>

			</tbody>
			<tfoot>
			<?php if ( $footer_enabled ): ?>
				<tr>
					<?php foreach ( $columns as $column_id => $columns_name ) : ?>
						<th data-field-slug="<?php echo $column_id ?>"><?php echo esc_html( $columns_name ) ?></th>
					<?php endforeach; ?>
				</tr>
			<?php endif; ?>
			</tfoot>
		</table>
	<?php endif; ?>
</div>
