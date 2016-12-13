/* global demo_importer_params */
jQuery( function ( $ ) {

	var tg_demo_importer = {
		init: function() {
			this.uploader();
			this.init_tiptip();

			// Trigger importer events.
			$( '.theme-actions' ).on( 'click', '.import', this.process_import );
			$( '.notice.is-dismissible' ).on( 'click', '.notice-dismiss', this.dismiss_notice );
		},
		uploader: function() {
			var uploadViewToggle = $( '.upload-view-toggle' ),
				$body = $( document.body );

			uploadViewToggle.on( 'click', function() {
				// Toggle the upload view.
				$body.toggleClass( 'show-upload-view' );
				// Toggle the `aria-expanded` button attribute.
				uploadViewToggle.attr( 'aria-expanded', $body.hasClass( 'show-upload-view' ) );
			});
		},
		init_tiptip: function() {
			$( '#tiptip_holder' ).removeAttr( 'style' );
			$( '#tiptip_arrow' ).removeAttr( 'style' );
			$( '.tips' ).tipTip({ 'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 200 });
		},
		process_import: function( e ) {
			e.preventDefault();

			var $this_el = $( this );

			if ( ! $this_el.hasClass( 'disabled' ) ) {
				if ( window.confirm( demo_importer_params.i18n_import_dummy_data ) ) {

					var data = {
						action: 'tg_import_demo_data',
						demo_id: $this_el.data( 'demo_id' ),
						security: demo_importer_params.import_demo_data_nonce
					};

					$.ajax({
						url:  demo_importer_params.ajax_url,
						data: data,
						type: 'POST',
						beforeSend: function() {
							$this_el.parent().addClass( 'importing' );
							$this_el.parent().find( '.spinner' ).addClass( 'is-active' );
						},
						success: function( response ) {
							$this_el.closest( '.theme' ).find( '.notice' ).remove();
							$this_el.parent().find( '.spinner' ).removeClass( 'is-active' );
							$this_el.parent().removeClass( 'importing' ).addClass( 'imported' );

							// Display import message.
							if ( true === response.success ) {
								$this_el.closest( '.theme' ).append( '<div class="notice notice-success notice-alt"><p>' + response.data.message + '</p></div>' );
							} else {
								$this_el.closest( '.theme' ).append( '<div class="update-message notice notice-error notice-alt"><p>' + demo_importer_params.i18n_import_data_error + '</p></div>' );
							}
						},
						error: function( jqXHR, textStatus, errorThrown ) {
							$this_el.closest( '.theme' ).find( '.notice' ).remove();
							$this_el.parent().find( '.spinner' ).removeClass( 'is-active' );
							$this_el.parent().removeClass( 'importing' ).addClass( 'imported' );

							// Display error message.
							$this_el.closest( '.theme' ).append( '<div class="update-message notice notice-error notice-alt"><p>' + errorThrown + '</p></div>' );
						}
					});
				}

				return false;
			}
		},
		dismiss_notice: function( e ) {
			e.preventDefault();

			var $this_el = $( this );

			if ( $this_el.parent().attr( 'id' ) === 'undefined' ) {
				return;
			}

			$.post( demo_importer_params.ajax_url, {
				action: 'tg_dismiss_notice',
				notice_id: $this_el.parent().data( 'notice_id' )
			});

			return false;
		}
	};

	tg_demo_importer.init();
});
