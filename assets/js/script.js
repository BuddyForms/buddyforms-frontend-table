var buddyformsDatatableInstance = {
	grantedAutocompleteFieldType: ['country', 'state'],
	initFilterFor: function(targetForm, filterContainer, currentTable) {
		if (!targetForm || !filterContainer || !currentTable) {
			return false;
		}

		var haveFilters = false;
		var headRows = jQuery(currentTable).find('thead th');
		var filterExample = '<div id="SLUG" class="buddyforms-data-table-filter-child dataTables_filter"><label for="SLUG">NAME<input data-field-type="TYPE" data-form-slug="' + targetForm + '" data-target-column="COLUMN" class="buddyforms-datatable-filter-input" type="search" name="SLUG" id="SLUG"></label></div>';
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
				var targetContent = filterContainer.find('.ui-toolbar.ui-widget-header').first();
				targetContent.prepend(filterString);
				haveFilters = true;
			}
			targetColumn++;
		});

		return haveFilters;
	},
	tableAll: function(options) {
		options = jQuery.extend({
			tableClass: '',
		}, options);

		return function(api, rowIdx, columns) {
			var data = jQuery.map(columns, function(col) {
				return '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
					'<td class="header-col">' + col.title + '</td> ' +
					'<td class="data-col">' + col.data + '</td>' +
					'</tr>';
			}).join('');
			jQuery(api.cell(rowIdx, 0).node()).parent().hide();
			return jQuery('<table class="' + options.tableClass + ' dtr-details" width="100%"/>').append(data);
		};
	},
	childRowImmediate: function(row, update, render) {
		if ((!update && row.child.isShown()) || !row.responsive.hasHidden()) {
			// User interaction and the row is show, or nothing to show
			row.child(false);
			jQuery(row.node()).removeClass('parent');
			jQuery(row.node()).unbind('*');
			return false;
		}
		else {
			// Display
			var ren = render();
			row.child(ren, 'child').show();
			jQuery(row.node()).addClass('parent');
			if (jQuery(row.node()).hasClass('odd')) {
				jQuery(ren).parent().parent().addClass('odd');
			}
			else {
				jQuery(ren).parent().parent().addClass('even');
			}

			return true;
		}
	},
	init: function() {
		var tableContainer = jQuery('.buddyforms_data_table');
		if (buddyformsDatatable && tableContainer.length > 0 && BuddyFormsHooks) {
			var currentTable = tableContainer.find('table[id^="buddyforms-data-table"]');
			if (currentTable && currentTable.length > 0) {
				var targetForm = currentTable.attr('data-form-slug');
				var currentPage = currentTable.attr('data-current-page');
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

				var tableOptions = {
					'serverSide': true,
					'orderMulti': true,
					'fixedColumns': true,
					'autoWidth': false,
					'ajax': {
						'type': 'POST',
						'url': buddyformsDatatable.ajax,
						'data': function(d) {
							d.action = 'buddyforms_data_table';
							d.nonce = buddyformsDatatable.nonce;
							d.form_slug = targetForm;
							d.has_action = hasActionColumn;
							d.page = currentPage;
						},
					},
				};
				tableOptions['lengthMenu'] = buddyformsDatatable.lengthMenu ? buddyformsDatatable.lengthMenu : [[10, 25, 50], [10, 25, 50]];
				tableOptions['pageLength'] = buddyformsDatatable.pageLength ? buddyformsDatatable.pageLength : false;
				tableOptions['info'] = buddyformsDatatable.info ? buddyformsDatatable.info : false;
				tableOptions['paging'] = buddyformsDatatable.paging ? buddyformsDatatable.paging : false;
				tableOptions['processing'] = buddyformsDatatable.processing ? buddyformsDatatable.processing : true;
				tableOptions['searching'] = buddyformsDatatable.searching ? buddyformsDatatable.searching : false;

				if (buddyformsDatatable.language) {
					tableOptions['language'] = buddyformsDatatable.language;
				}

				if (buddyformsDatatable.dom) {
					tableOptions['dom'] = buddyformsDatatable.dom;
				}

				if (buddyformsDatatable.searching) {
					tableOptions['searchDelay'] = buddyformsDatatable.searchDelay ? buddyformsDatatable.searchDelay : 400;
				}

				if (buddyformsDatatable.alwaysOpen) {
					tableOptions['responsive'] = {
						details: {
							display: buddyformsDatatableInstance.childRowImmediate,
							type: 'column',
							target: '',
						},
					};
					if (buddyformsDatatable.childFullTable) {
						tableOptions['responsive'].details.renderer = buddyformsDatatableInstance.tableAll();
					}
				}
				else {
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

				jQuery(document).on('preInit.dt', function() {
					if (needFilters) {
						buddyformsDatatableInstance.initFilterFor(targetForm, tableContainer, currentTable);
					}
					currentTable.show();
				});

				var dataTable = jQuery(currentTable).DataTable(tableOptions);

				BuddyFormsHooks.doAction('buddyforms-datatable:init', [dataTable]);

				if (dataTable && buddyformsDatatable.alwaysOpen && buddyformsDatatable.childFullTable) {
					//Show or hide the rows when the childFullTable is enabled
					dataTable.on('responsive-resize', function(e, datatable, columns) {
						var oneIsHide = columns.filter(function(curr) {
							return curr === false;
						});
						var existHidden = oneIsHide.length > 0;
						if (!existHidden) {
							currentTable.find('tbody tr[role="row"]').show();
						}
						else {
							currentTable.find('tbody tr[role="row"]').hide();
						}
					});
				}

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
