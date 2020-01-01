var buddyformsDatatableInstance = {
	grantedAutocompleteFieldType: ['country', 'state'],
	initFilterFor: function(targetForm, filterContainer, currentTable) {
		if (!targetForm || !filterContainer || !currentTable) {
			return false;
		}

		var haveFilters = false;
		var headRows = jQuery(currentTable).find('thead th');
		var filterExample = '<div id="SLUG" class="buddyforms-data-table-filter-child"><p><label for="SLUG"><strong>NAME</strong></label></p><input data-field-type="TYPE" data-form-slug="' + targetForm + '" data-target-column="COLUMN" class="buddyforms-datatable-filter-input" type="search" name="SLUG" id="SLUG"></div>';
		var targetColumn = 0;
		jQuery.each(headRows, function() {
			var currentRow = jQuery(this);
			var isSearchable = currentRow.attr('data-searchable');
			if (isSearchable && isSearchable.toLowerCase() === 'true') {
				var filterString = filterExample;
				var targetName = currentRow.text();
				var targetSlug = currentRow.attr('data-field-slug');
				var targetType = currentRow.attr('data-field-type');
				filterString = filterString.replace(/SLUG/g, targetSlug);
				filterString = filterString.replace(/NAME/g, targetName);
				filterString = filterString.replace(/TYPE/g, targetType);
				filterString = filterString.replace(/COLUMN/g, targetColumn);
				filterContainer.append(filterString);
				haveFilters = true;
			}
			targetColumn++;
		});

		return haveFilters;
	},
	init: function() {
		var tableContainer = jQuery('.buddyforms_data_table');
		if (buddyformsDatatable && tableContainer.length > 0) {
			var currentTable = tableContainer.find('table[id^="buddyforms-data-table"]');
			if (currentTable && currentTable.length > 0) {
				var targetForm = currentTable.attr('data-form-slug');
				if (!targetForm) {
					return;
				}

				var headerRows = currentTable.find('thead>tr>th');
				var hasHeaderRows = (headerRows && headerRows.length > 0);

				if (!hasHeaderRows) {
					return;
				}

				var hasActionColumn = currentTable.find('thead>tr>th[data-field-slug="actions"]').length > 0;

				var filterContainer = tableContainer.find('.buddyforms-data-table-filter-container');
				var needFilters = (filterContainer && filterContainer.length > 0);

				if (needFilters) {
					this.initFilterFor(targetForm, filterContainer, currentTable);
				}

				var tableOptions = {
					'serverSide': true,
					'orderMulti': true,
					'ajax': {
						'type': 'POST',
						'url': buddyformsDatatable.ajax,
						'data': function(d) {
							d.action = 'buddyforms_data_table';
							d.nonce = buddyformsDatatable.nonce;
							d.form_slug = targetForm;
							d.has_action = hasActionColumn;
						},
					},
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

				if (buddyformsDatatable.alwaysOpen) {
					tableOptions['responsive'] = {
						details: {
							display: jQuery.fn.dataTable.Responsive.display.childRowImmediate,
							type: 'none',
							target: '',
						},
					};
				} else {
					tableOptions['responsive'] = true;
				}

				if (buddyformsDatatable.stateSave) {
					tableOptions['stateSave'] = buddyformsDatatable.stateSave;
					tableOptions['stateSaveCallback'] = function(settings, data) {
						localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
					};
					tableOptions['stateLoadCallback'] = function(settings, data) {
						return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
					};
				}

				var dataTable = jQuery(currentTable).DataTable(tableOptions);

				if (dataTable && needFilters) {
					// Apply the search by columns
					dataTable.columns().every(function() {
						var that = this;
						var config = {
							serviceUrl: buddyformsDatatable.ajax,
							autoSelectFirst: true,
							showNoSuggestionNotice: true,
							type: 'POST',
							params: {'action': 'buddyforms_data_table_autocomplete', 'nonce': buddyformsDatatable.nonce, 'form_slug': targetForm, 'target_column': that[0]},
							onInvalidateSelection: function() {
								that.search('').draw();
							},
							onSelect: function(suggestion) {
								if (suggestion && suggestion.data && that.search() !== suggestion.data) {
									that.search(suggestion.data).draw();
								}
							},
						};
						jQuery.each(jQuery('input[data-target-column="' + that[0] + '"]', tableContainer), function() {
							var thisRow = jQuery(this);
							var currentFieldType = thisRow.attr('data-field-type');
							if (buddyformsDatatableInstance.grantedAutocompleteFieldType.indexOf(currentFieldType.toLowerCase()) > -1) {
								thisRow.devbridgeAutocomplete(config);
							}
							else {
								thisRow.on('keyup change clear', function() {
									if (this.value) {
										if (that.search() !== this.value) {
											that.search(this.value).draw();
										}
									}
									else {
										that.search('').draw();
									}
								});
							}
						});
					});
				}
			}
		}
	},
};

jQuery(document).ready(function() {
	buddyformsDatatableInstance.init();
});
