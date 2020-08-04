/**
 * Get system status datas.
 */
jQuery(
	function ( $ ) {
		let demoImporterSystemStatus = {
			// Init class.
			init: function () {
				// Generate system report.
				$( '#system-status-report', this.generateReport );
			},

			generateReport: function () {
				let report = '';

				$( '.demo-importer-status-table thead, .demo-importer-status-table tbody' ).each(
					function () {
						if ( $( this ).is( 'thead' ) ) {
							let theadLabel = $( this ).text();
							report = report + '\n== ' + $.trim( theadLabel ) + ' ==\n\n';
						} else {
							$( 'tr', $( this ) ).each(
								function () {
									let tbodyLabel = $( this ).find( 'td:eq(0)' ).text();
								}
							);
						}
					}
				);

				$( '#system-status-report' ).find( 'textarea' ).val( report );
			},
		};

		demoImporterSystemStatus.init();
	}
);
