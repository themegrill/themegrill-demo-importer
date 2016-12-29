( function( $, wp, settings ) {
	var $document = $( document );

	wp = wp || {};

	/**
	 * The WP Updates object.
	 *
	 * @type {object}
	 */
	wp.updates = wp.updates || {};

	/**
	 * Localized strings.
	 *
	 * @type {object}
	 */
	wp.updates.l10n = _.extend( wp.updates.l10n, settings.l10n || {} );

	/**
	 * Sends an Ajax request to the server to delete a demo.
	 *
	 * @param {object}             args
	 * @param {string}             args.slug    Demo ID.
	 * @param {deleteDemoSuccess=} args.success Optional. Success callback. Default: wp.updates.deleteDemoSuccess
	 * @param {deleteDemoError=}   args.error   Optional. Error callback. Default: wp.updates.deleteDemoError
	 * @return {$.promise} A jQuery promise that represents the request,
	 *                     decorated with an abort() method.
	 */
	wp.updates.deleteDemo = function( args ) {
		var $button = $( '.theme-actions .delete-demo' );

		args = _.extend( {
			success: wp.updates.deleteDemoSuccess,
			error: wp.updates.deleteDemoError
		}, args );

		if ( $button && $button.html() !== wp.updates.l10n.deleting ) {
			$button
				.data( 'originaltext', $button.html() )
				.text( wp.updates.l10n.deleting );
		}

		wp.a11y.speak( wp.updates.l10n.deleting, 'polite' );

		// Remove previous error messages, if any.
		$( '.theme-info .update-message' ).remove();

		$document.trigger( 'wp-demo-deleting', args );

		return wp.updates.ajax( 'delete-demo', args );
	};

	/**
	 * Updates the UI appropriately after a successful demo deletion.
	 *
	 * @typedef {object} deleteDemoSuccess
	 * @param {object} response      Response from the server.
	 * @param {string} response.slug Slug of the demo that was deleted.
	 */
	wp.updates.deleteDemoSuccess = function( response ) {
		wp.a11y.speak( wp.updates.l10n.deleted, 'polite' );

		$document.trigger( 'wp-demo-delete-success', response );
	};

	/**
	 * Updates the UI appropriately after a failed demo deletion.
	 *
	 * @typedef {object} deleteDemoError
	 * @param {object} response              Response from the server.
	 * @param {string} response.slug         Slug of the demo to be deleted.
	 * @param {string} response.errorCode    Error code for the error that occurred.
	 * @param {string} response.errorMessage The error that occurred.
	 */
	wp.updates.deleteDemoError = function( response ) {
		var $button      = $( '.theme-actions .delete-demo' ),
			errorMessage = wp.updates.l10n.deleteFailed.replace( '%s', response.errorMessage ),
			$message     = wp.updates.adminNotice( {
				className: 'update-message notice-error notice-alt',
				message:   errorMessage
			} );

		if ( wp.updates.maybeHandleCredentialError( response, 'delete-demo' ) ) {
			return;
		}

		$( '.theme-info .theme-description' ).before( $message );

		$button.html( $button.data( 'originaltext' ) );

		wp.a11y.speak( errorMessage, 'assertive' );

		$document.trigger( 'wp-demo-delete-error', response );
	};

	/**
	 * Pulls available jobs from the queue and runs them.
	 * @see https://core.trac.wordpress.org/ticket/39364
	 */
	wp.updates.queueChecker = function() {
		var job;

		if ( wp.updates.ajaxLocked || ! wp.updates.queue.length ) {
			return;
		}

		job = wp.updates.queue.shift();

		// Handle a queue job.
		$document.trigger( 'wp-updates-queue-job', job );
	};

})( jQuery, window.wp, window._demoUpdatesSettings );
