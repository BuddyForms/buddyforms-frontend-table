var buddyformsDatatableInstance = {
	initFilterFor: function (targetForm, filterContainer, currentTable) {
		if (!targetForm || !filterContainer || !currentTable) {
			return false;
		}

		var haveFilters = false;
		var headRows = jQuery(currentTable).find('thead th');
		var filterExample = '<div id="SLUG" class="buddyforms-data-table-filter-child"><p><label for="SLUG"><strong>NAME</strong></label></p><input data-target-column="COLUMN" class="buddyforms-datatable-filter-input" type="search" name="SLUG" id="SLUG"></div>';
		var targetColumn = 0;
		jQuery.each(headRows, function () {
			var currentRow = jQuery(this);
			var isSearchable = currentRow.attr('data-searchable');
			if (isSearchable && isSearchable.toLowerCase() === "true") {
				var filterString = filterExample;
				var targetName = currentRow.text();
				var targetSlug = currentRow.attr('data-field-slug');
				filterString = filterString.replace(/SLUG/g, targetSlug);
				filterString = filterString.replace(/NAME/g, targetName);
				filterString = filterString.replace(/COLUMN/g, targetColumn);
				filterContainer.append(filterString);
				haveFilters = true;
			}
			targetColumn++;
		});

		return haveFilters;
	},
	init: function () {
		var tableContainer = jQuery('.buddyforms_data_table');
		if (buddyformsDatatable && tableContainer.length > 0) {
			var currentTable = tableContainer.find('table[id^="buddyforms-data-table"]');
			if (currentTable && currentTable.length > 0) {
				var targetForm = currentTable.attr('data-form-slug');
				if (!targetForm) {
					return;
				}

				var filterContainer = tableContainer.find('.buddyforms-data-table-filter-container');
				var needFilters = (filterContainer && filterContainer.length > 0);

				if (needFilters) {
					this.initFilterFor(targetForm, filterContainer, currentTable);
				}

				var tableOptions = {
					"responsive": true,
					"serverSide": true,
					"orderMulti": true,
					"ajax": {
						"type": "POST",
						"url": buddyformsDatatable.ajax,
						"data": function (d) {
							d.action = 'buddyforms_data_table';
							d.nonce = buddyformsDatatable.nonce;
							d.form_slug = targetForm;
						}
					}
				};
				tableOptions['lengthMenu'] = buddyformsDatatable.lengthMenu ? buddyformsDatatable.lengthMenu : [[10, 25, 50], [10, 25, 50]];
				tableOptions['pageLength'] = buddyformsDatatable.pageLength ? buddyformsDatatable.pageLength : false;
				tableOptions['info'] = buddyformsDatatable.info ? buddyformsDatatable.info : false;
				tableOptions['paging'] = buddyformsDatatable.paging ? buddyformsDatatable.paging : false;
				tableOptions['processing'] = buddyformsDatatable.processing ? buddyformsDatatable.processing : true;
				tableOptions['searching'] = buddyformsDatatable.searching ? buddyformsDatatable.searching : false;

				if (buddyformsDatatable.searching) {
					tableOptions['searchDelay'] = buddyformsDatatable.searchDelay ? buddyformsDatatable.searchDelay : 400;
				}

				if (buddyformsDatatable.stateSave) {
					tableOptions['stateSave'] = buddyformsDatatable.stateSave;
					tableOptions['stateSaveCallback'] = function (settings, data) {
						localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data))
					};
					tableOptions['stateLoadCallback'] = function (settings, data) {
						return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance))
					};
				}

				var dataTable = jQuery(currentTable).DataTable(tableOptions);

				if (dataTable) {
					// Apply the search by columns
					dataTable.columns().every(function () {
						var that = this;
						jQuery('input[data-target-column="' + that[0] + '"]', tableContainer).on('keyup change clear', function () {
							if (that.search() !== this.value) {
								that.search(this.value).draw();
							}
						});
					});
				}
			}
		}
	}
};

jQuery(document).ready(function () {
	buddyformsDatatableInstance.init();
});
