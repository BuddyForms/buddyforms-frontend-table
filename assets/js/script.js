jQuery(document).ready(function () {
	if (buddyformsDatatable) {
		jQuery('#example').DataTable({
			"responsive": true,
			"processing": true,
			"serverSide": true,
			"orderMulti": true,
			"ajax": {
				"type": "POST",
				"url": buddyformsDatatable.ajax,
				"data": function (d) {
					d.action = 'buddyforms_data_table';
					d.nonce = buddyformsDatatable.nonce;
					d.form_slug = jQuery('#example').attr('data-form-slug');
				}
			}
		});
	}
});
