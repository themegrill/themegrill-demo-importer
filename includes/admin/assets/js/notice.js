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

				// Select every codes added inside textarea.
				$( '#system-status-report' ).on( 'click', this.selectDetails );

				// Select every codes added inside the textarea and copy the content inside.
				$( '#copy-system-status-report' ).on( 'click', this.selectCopyDetails );
			},

			generateReport: function () {
				let report = '';

				$( '.demo-importer-status-table thead, .demo-importer-status-table tbody' ).each(
					function () {
						if ( $( this ).is( 'thead' ) ) {
							let theadLabel = $( this ).text();
							report = report + '\n== ' + $.trim( theadLabel ) + ' ==\n';
						} else {
							$( 'tr', $( this ) ).each(
								function () {
									let tbodyLabel = $( this ).find( 'td:eq(0)' ).text();
									let tbodyValue = $( this ).find( 'td:eq(1)' ).text();

									report = report + '\t' + $.trim( tbodyLabel ) + ' ' + $.trim( tbodyValue ) + '\n';
								}
							);
						}
					}
				);

				$( '#system-status-report' ).find( 'textarea' ).val( report );
			},

			selectDetails: function () {
				$( '#system-status-report' ).find( 'textarea' ).focus().select();
			},

			selectCopyDetails: function () {
				$( '#system-status-report' ).find( 'textarea' ).focus().select();
				document.execCommand( 'copy' );
			},
		};

		demoImporterSystemStatus.init();
	}
);
