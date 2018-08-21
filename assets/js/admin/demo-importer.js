/* global _demoImporterSettings */
window.wp = window.wp || {};

( function( $ ) {

// Set up our namespace...
var demos, l10n;
demos = wp.demos = wp.demos || {};

// Store the demo data and settings for organized and quick access
// demos.data.settings, demos.data.demos, demos.data.l10n
demos.data = _demoImporterSettings;
l10n = demos.data.l10n;

// Shortcut for isNew check
demos.isNew = !! demos.data.settings.isNew;

// Setup app structure
_.extend( demos, { model: {}, view: {}, routes: {}, router: {}, template: wp.template });

demos.Model = Backbone.Model.extend({
	// Adds attributes to the default data coming through the demos api
	// Map `id` to `slug` for shared code
	initialize: function() {
		var description;

		// Set the attributes
		this.set({
			// slug is for installation, id is for existing.
			id: this.get( 'slug' ) || this.get( 'id' )
		});

		// Map `section.description` to `description`
		// as the API sometimes returns it differently
		if ( this.has( 'sections' ) ) {
			description = this.get( 'sections' ).description;
			this.set({ description: description });
		}
	}
});

// Main view controller for demo importer
// Unifies and renders all available views
demos.view.Appearance = wp.Backbone.View.extend({

	el: '#wpbody-content .wrap .theme-browser',

	window: $( window ),
	// Pagination instance
	page: 0,

	// Sets up a throttler for binding to 'scroll'
	initialize: function( options ) {
		// Scroller checks how far the scroll position is
		_.bindAll( this, 'scroller' );

		this.SearchView = options.SearchView ? options.SearchView : demos.view.Search;
		// Bind to the scroll event and throttle
		// the results from this.scroller
		this.window.bind( 'scroll', _.throttle( this.scroller, 300 ) );
	},

	// Main render control
	render: function() {
		// Setup the main demo view
		// with the current demo collection
		this.view = new demos.view.Demos({
			collection: this.collection,
			parent: this
		});

		// Render search form.
		this.search();

		this.$el.removeClass( 'search-loading' );

		// Render and append
		this.view.render();
		this.$el.empty().append( this.view.el ).addClass( 'rendered' );
	},

	// Defines search element container
	searchContainer: $( '.search-form' ),

	// Search input and view
	// for current demo collection
	search: function() {
		var view,
			self = this;

		// Don't render the search if there is only one demo
		if ( demos.data.demos.length === 1 ) {
			return;
		}

		view = new this.SearchView({
			collection: self.collection,
			parent: this
		});
		self.SearchView = view;

		// Render and append after screen title
		view.render();
		this.searchContainer
			.append( $.parseHTML( '<label class="screen-reader-text" for="wp-filter-search-input">' + l10n.search + '</label>' ) )
			.append( view.el )
			.on( 'submit', function( event ) {
				event.preventDefault();
			});
	},

	// Checks when the user gets close to the bottom
	// of the page and triggers a demo:scroll event
	scroller: function() {
		var self = this,
			bottom, threshold;

		bottom = this.window.scrollTop() + self.window.height();
		threshold = self.$el.offset().top + self.$el.outerHeight( false ) - self.window.height();
		threshold = Math.round( threshold * 0.9 );

		if ( bottom > threshold ) {
			// this.trigger( 'demo:scroll' );
		}
	}
});

// Set up the Collection for our demo data
// @has 'id' 'name' 'screenshot' 'author' 'authorURI' 'version' 'active' ...
demos.Collection = Backbone.Collection.extend({

	model: demos.Model,

	// Search terms
	terms: '',

	// Controls searching on the current theme collection
	// and triggers an update event
	doSearch: function( value ) {

		// Don't do anything if we've already done this search
		// Useful because the Search handler fires multiple times per keystroke
		if ( this.terms === value ) {
			return;
		}

		// Updates terms with the value passed
		this.terms = value;

		// If we have terms, run a search...
		if ( this.terms.length > 0 ) {
			this.search( this.terms );
		}

		// If search is blank, show all demos
		// Useful for resetting the views when you clean the input
		if ( this.terms === '' ) {
			this.reset( demos.data.demos );
			$( 'body' ).removeClass( 'no-results' );
		}

		// Trigger a 'demos:update' event
		this.trigger( 'demos:update' );
	},

	// Performs a search within the collection
	// @uses RegExp
	search: function( term ) {
		var match, results, haystack, name, description, author;

		// Start with a full collection
		this.reset( demos.data.demos, { silent: true } );

		// Escape the term string for RegExp meta characters
		term = term.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' );

		// Consider spaces as word delimiters and match the whole string
		// so matching terms can be combined
		term = term.replace( / /g, ')(?=.*' );
		match = new RegExp( '^(?=.*' + term + ').+', 'i' );

		// Find results
		// _.filter and .test
		results = this.filter( function( data ) {
			name        = data.get( 'name' ).replace( /(<([^>]+)>)/ig, '' );
			description = data.get( 'description' ).replace( /(<([^>]+)>)/ig, '' );
			author      = data.get( 'author' ).replace( /(<([^>]+)>)/ig, '' );

			haystack = _.union( [ name, data.get( 'id' ), description, author, data.get( 'tags' ) ] );

			if ( match.test( data.get( 'author' ) ) && term.length > 2 ) {
				data.set( 'displayAuthor', true );
			}

			return match.test( haystack );
		});

		if ( results.length === 0 ) {
			this.trigger( 'query:empty' );
		} else {
			$( 'body' ).removeClass( 'no-results' );
		}

		this.reset( results );
	},

	// Paginates the collection with a helper method
	// that slices the collection
	paginate: function( instance ) {
		var collection = this;
		instance = instance || 0;

		// Demos per instance are set at 20
		collection = _( collection.rest( 20 * instance ) );
		collection = _( collection.first( 20 ) );

		return collection;
	},

	count: false,

	// Handles requests for more demos
	// and caches results
	//
	// When we are missing a cache object we fire an apiCall()
	// which triggers events of `query:success` or `query:fail`
	query: function( request ) {
		/**
		 * @static
		 * @type Array
		 */
		var queries = this.queries,
			self = this,
			query, isPaginated, count;

		// Store current query request args
		// for later use with the event `demo:end`
		this.currentQuery.request = request;

		// Search the query cache for matches.
		query = _.find( queries, function( query ) {
			return _.isEqual( query.request, request );
		});

		// If the request matches the stored currentQuery.request
		// it means we have a paginated request.
		isPaginated = _.has( request, 'page' );

		// Reset the internal api page counter for non paginated queries.
		if ( ! isPaginated ) {
			this.currentQuery.page = 1;
		}

		// Otherwise, send a new API call and add it to the cache.
		if ( ! query && ! isPaginated ) {
			query = this.apiCall( request ).done( function( data ) {

				// Update the collection with the queried data.
				if ( data.demos ) {
					self.reset( data.demos );
					count = data.info.results;
					// Store the results and the query request
					queries.push( { demos: data.demos, request: request, total: count } );
				}

				// Trigger a collection refresh event
				// and a `query:success` event with a `count` argument.
				self.trigger( 'demos:update' );
				self.trigger( 'query:success', count );

				if ( data.demos && data.demos.length === 0 ) {
					self.trigger( 'query:empty' );
				}

			}).fail( function() {
				self.trigger( 'query:fail' );
			});
		} else {
			// If it's a paginated request we need to fetch more demos...
			if ( isPaginated ) {
				return this.apiCall( request, isPaginated ).done( function( data ) {
					// Add the new demos to the current collection
					// @todo update counter
					self.add( data.demos );
					self.trigger( 'query:success' );

					// We are done loading demos for now.
					self.loadingDemos = false;

				}).fail( function() {
					self.trigger( 'query:fail' );
				});
			}

			if ( query.demos.length === 0 ) {
				self.trigger( 'query:empty' );
			} else {
				$( 'body' ).removeClass( 'no-results' );
			}

			// Only trigger an update event since we already have the demos
			// on our cached object
			if ( _.isNumber( query.total ) ) {
				this.count = query.total;
			}

			this.reset( query.demos );
			if ( ! query.total ) {
				this.count = this.length;
			}

			this.trigger( 'demos:update' );
			this.trigger( 'query:success', this.count );
		}
	},

	// Local cache array for API queries
	queries: [],

	// Keep track of current query so we can handle pagination
	currentQuery: {
		page: 1,
		request: {}
	},

	// Send request to api.github.com/repos/themegrill-demo-pack
	apiCall: function( request, paginated ) {
		return wp.ajax.send( 'query-demos', {
			data: {
				// Request data
				request: _.extend({
					per_page: 100
				}, request )
			},

			beforeSend: function() {
				if ( ! paginated ) {
					// Spin it
					$( 'body' ).addClass( 'loading-content' ).removeClass( 'no-results' );
				}
			}
		});
	},

	// Static status controller for when we are loading demos.
	loadingDemos: false
});

// This is the view that controls each demo item
// that will be displayed on the screen
demos.view.Demo = wp.Backbone.View.extend({

	// Wrap demo data on a div.theme element
	className: 'theme',

	// Reflects which demo view we have
	// 'grid' (default) or 'detail'
	state: 'grid',

	// The HTML template for each element to be rendered
	html: demos.template( 'demo' ),

	events: {
		'click': 'preview',
		'keydown': 'preview',
		'touchend': 'preview',
		'keyup': 'addFocus',
		'touchmove': 'preventExpand',
		'click .demo-import': 'importDemo'
	},

	touchDrag: false,

	initialize: function() {
		this.model.on( 'change', this.render, this );
	},

	render: function() {
		var data = this.model.toJSON();

		// Render demos using the html template
		this.$el.html( this.html( data ) ).attr({
			tabindex: 0,
			'aria-describedby' : data.id + '-action ' + data.id + '-name',
			'data-slug': data.id
		});

		// Renders active demo styles
		this.activeDemo();

		if ( this.model.get( 'displayAuthor' ) ) {
			this.$el.addClass( 'display-author' );
		}
	},

	// Adds a class to the currently active demo
	// and to the overlay in detailed view mode
	activeDemo: function() {
		if ( this.model.get( 'active' ) ) {
			this.$el.addClass( 'active' );
		}
	},

	// Add class of focus to the demo we are focused on.
	addFocus: function() {
		var $demoToFocus = ( $( ':focus' ).hasClass( 'theme' ) ) ? $( ':focus' ) : $(':focus').parents('.theme');

		$('.theme.focus').removeClass('focus');
		$demoToFocus.addClass('focus');
	},

	preventExpand: function() {
		this.touchDrag = true;
	},

	preview: function( event ) {
		var self = this,
			current, preview;

		event = event || window.event;

		// Bail if the user scrolled on a touch device
		if ( this.touchDrag === true ) {
			return this.touchDrag = false;
		}

		// Allow direct link path to installing a demo.
		if ( $( event.target ).not( '.install-demo-preview' ).parents( '.theme-actions' ).length ) {
			return;
		}

		// 'enter' and 'space' keys expand the details view when a demo is :focused
		if ( event.type === 'keydown' && ( event.which !== 13 && event.which !== 32 ) ) {
			return;
		}

		// pressing enter while focused on the buttons shouldn't open the preview
		if ( event.type === 'keydown' && event.which !== 13 && $( ':focus' ).hasClass( 'button' ) ) {
			return;
		}

		event.preventDefault();

		event = event || window.event;

		// Set focus to current demo.
		demos.focusedDemo = this.$el;

		// Construct a new Preview view.
		demos.preview = preview = new demos.view.Preview({
			model: this.model
		});

		// Render the view and append it.
		preview.render();
		this.setNavButtonsState();

		// Hide previous/next navigation if there is only one demo
		if ( this.model.collection.length === 1 ) {
			preview.$el.addClass( 'no-navigation' );
		} else {
			preview.$el.removeClass( 'no-navigation' );
		}

		// Append preview
		$( 'div.wrap' ).append( preview.el );

		// Listen to our preview object
		// for `demo:next` and `demo:previous` events.
		this.listenTo( preview, 'demo:next', function() {

			// Keep local track of current demo model.
			current = self.model;

			// If we have ventured away from current model update the current model position.
			if ( ! _.isUndefined( self.current ) ) {
				current = self.current;
			}

			// Get next demo model.
			self.current = self.model.collection.at( self.model.collection.indexOf( current ) + 1 );

			// If we have no more themes, bail.
			if ( _.isUndefined( self.current ) ) {
				self.options.parent.parent.trigger( 'demo:end' );
				return self.current = current;
			}

			preview.model = self.current;

			// Render and append.
			preview.render();
			this.setNavButtonsState();
			$( '.next-theme' ).focus();
		})
		.listenTo( preview, 'demo:previous', function() {

			// Keep track of current demo model.
			current = self.model;

			// Bail early if we are at the beginning of the collection
			if ( self.model.collection.indexOf( self.current ) === 0 ) {
				return;
			}

			// If we have ventured away from current model update the current model position.
			if ( ! _.isUndefined( self.current ) ) {
				current = self.current;
			}

			// Get previous theme model.
			self.current = self.model.collection.at( self.model.collection.indexOf( current ) - 1 );

			// If we have no more themes, bail.
			if ( _.isUndefined( self.current ) ) {
				return;
			}

			preview.model = self.current;

			// Render and append.
			preview.render();
			this.setNavButtonsState();
			$( '.previous-theme' ).focus();
		});

		this.listenTo( preview, 'preview:close', function() {
			self.current = self.model;
		});
	},

	// Handles .disabled classes for previous/next buttons in demo installer preview
	setNavButtonsState: function() {
		var $demoInstaller = $( '.theme-install-overlay' ),
			current = _.isUndefined( this.current ) ? this.model : this.current,
			previousDemoButton = $demoInstaller.find( '.previous-theme' ),
			nextDemoButton = $demoInstaller.find( '.next-theme' );

		// Disable previous at the zero position
		if ( 0 === this.model.collection.indexOf( current ) ) {
			previousDemoButton
				.addClass( 'disabled' )
				.prop( 'disabled', true );

			nextDemoButton.focus();
		}

		// Disable next if the next model is undefined
		if ( _.isUndefined( this.model.collection.at( this.model.collection.indexOf( current ) + 1 ) ) ) {
			nextDemoButton
				.addClass( 'disabled' )
				.prop( 'disabled', true );

			previousDemoButton.focus();
		}
	},

	importDemo: function( event ) {
		var _this = this,
			$target = $( event.target );
		event.preventDefault();

		if ( $target.hasClass( 'disabled' ) ) {
			return;
		}

		if ( ! window.confirm( wp.demos.data.settings.confirmImport ) ) {
			return;
		}

		wp.updates.maybeRequestFilesystemCredentials( event );

		$( document ).on( 'wp-demo-import-success', function( event, response ) {
			if ( _this.model.get( 'id' ) === response.slug ) {
				_this.model.set( { 'imported': true } );
			}
		} );

		wp.updates.importDemo( {
			slug: $target.data( 'slug' )
		} );
	}
});

// Theme Preview view
// Set ups a modal overlay with the expanded demo data
demos.view.Preview = wp.Backbone.View.extend({

	className: 'wp-full-overlay expanded',
	el: '.theme-install-overlay',

	events: {
		'click .close-full-overlay': 'close',
		'click .collapse-sidebar': 'collapse',
		'click .devices button': 'previewDevice',
		'click .previous-theme': 'previousDemo',
		'click .next-theme': 'nextDemo',
		'keyup': 'keyEvent',
		'click .demo-import': 'importDemo',
		'click .plugins-install': 'installPlugins'
	},

	// The HTML template for the demo preview
	html: demos.template( 'demo-preview' ),

	render: function() {
		var self = this,
			currentPreviewDevice,
			data = this.model.toJSON(),
			$body = $( document.body );

		$body.attr( 'aria-busy', 'true' );

		this.$el.removeClass( 'iframe-ready' ).html( this.html( data ) );

		currentPreviewDevice = this.$el.data( 'current-preview-device' );
		if ( currentPreviewDevice ) {
			self.tooglePreviewDeviceButtons( currentPreviewDevice );
		}

		demos.router.navigate( demos.router.baseUrl( demos.router.demoPath + this.model.get( 'id' ) ), { replace: false } );

		this.$el.fadeIn( 200, function() {
			$body.addClass( 'demo-importer-active full-overlay-active' );
		});

		this.$el.find( 'iframe' ).one( 'load', function() {
			self.iframeLoaded();
		});
	},

	iframeLoaded: function() {
		this.$el.addClass( 'iframe-ready' );
		$( document.body ).attr( 'aria-busy', 'false' );
	},

	close: function() {
		this.$el.fadeOut( 200, function() {
			$( 'body' ).removeClass( 'demo-importer-active full-overlay-active' );

			// Return focus to the demo div
			if ( demos.focusedDemo ) {
				demos.focusedDemo.focus();
			}
		}).removeClass( 'iframe-ready' );

		// Restore the previous browse tab if available.
		if ( demos.router.selectedTab ) {
			demos.router.navigate( demos.router.baseUrl( '&browse=' + demos.router.selectedTab ) );
		} else {
			demos.router.navigate( demos.router.baseUrl( '' ) );
		}
		this.trigger( 'preview:close' );
		this.undelegateEvents();
		this.unbind();
		return false;
	},

	collapse: function( event ) {
		var $button = $( event.currentTarget );
		if ( 'true' === $button.attr( 'aria-expanded' ) ) {
			$button.attr({ 'aria-expanded': 'false', 'aria-label': l10n.expandSidebar });
		} else {
			$button.attr({ 'aria-expanded': 'true', 'aria-label': l10n.collapseSidebar });
		}

		this.$el.toggleClass( 'collapsed' ).toggleClass( 'expanded' );
		return false;
	},

	previewDevice: function( event ) {
		var device = $( event.currentTarget ).data( 'device' );

		this.$el
			.removeClass( 'preview-desktop preview-tablet preview-mobile' )
			.addClass( 'preview-' + device )
			.data( 'current-preview-device', device );

		this.tooglePreviewDeviceButtons( device );
	},

	tooglePreviewDeviceButtons: function( newDevice ) {
		var $devices = $( '.wp-full-overlay-footer .devices' );

		$devices.find( 'button' )
			.removeClass( 'active' )
			.attr( 'aria-pressed', false );

		$devices.find( 'button.preview-' + newDevice )
			.addClass( 'active' )
			.attr( 'aria-pressed', true );
	},

	keyEvent: function( event ) {
		// The escape key closes the preview
		if ( event.keyCode === 27 ) {
			this.undelegateEvents();
			this.close();
		}
		// The right arrow key, next demo
		if ( event.keyCode === 39 ) {
			_.once( this.nextDemo() );
		}

		// The left arrow key, previous demo
		if ( event.keyCode === 37 ) {
			this.previousDemo();
		}
	},

	nextDemo: function() {
		var self = this;
		self.trigger( 'demo:next', self.model.cid );
		return false;
	},

	previousDemo: function() {
		var self = this;
		self.trigger( 'demo:previous', self.model.cid );
		return false;
	},

	importDemo: function( event ) {
		var _this = this,
			$target = $( event.target );
		event.preventDefault();

		if ( $target.hasClass( 'disabled' ) || $target.hasClass( 'updating-message' ) ) {
			return;
		}

		if ( ! window.confirm( wp.demos.data.settings.confirmImport ) ) {
			return;
		}

		wp.updates.maybeRequestFilesystemCredentials( event );

		// Disable the next and previous demo.
		$( '.theme-install-overlay' ).find( '.next-theme, .previous-theme' ).addClass( 'disabled' );

		$( document ).on( 'wp-demo-import-success', function( event, response ) {
			if ( _this.model.get( 'id' ) === response.slug ) {
				_this.model.set( { 'imported': true } );
			}
		} );

		wp.updates.importDemo( {
			slug: $target.data( 'slug' )
		} );
	},

	installPlugins: function( event ) {
		var _this         = this,
			pluginsList   = $( '.plugins-list-table' ).find( '#the-list tr' ),
			$target       = $( '.plugins-install' ),
			success       = 0,
			error         = 0,
			errorMessages = [];

		event.preventDefault();

		if ( $target.hasClass( 'disabled' ) || $target.hasClass( 'updating-message' ) ) {
			return;
		}

		// Bail if there were required plugins.
		if ( pluginsList.length ) {
			$( '.wp-full-overlay-sidebar-content' ).animate( { scrollTop: $( document ).height() } );

			if ( $target.html() !== wp.updates.l10n.installing ) {
				$target.data( 'originaltext', $target.html() );
			}

			$target
				.addClass( 'updating-message' )
				.text( wp.updates.l10n.installing );
			wp.a11y.speak( wp.updates.l10n.installingMsg, 'polite' );

			// Disable the next and previous demo.
			$( '.theme-install-overlay' ).find( '.next-theme, .previous-theme' ).addClass( 'disabled' );
		}

		wp.updates.maybeRequestFilesystemCredentials( event );

		$( document ).trigger( 'wp-plugin-bulk-install', pluginsList );

		// Find all the plugins which are required.
		pluginsList.each( function( index, element ) {
			var $itemRow = $( element );

			// Only add inactive items to the update queue.
			if ( ! $itemRow.hasClass( 'inactive' ) || $itemRow.find( 'notice-error' ).length ) {
				return;
			}

			// Add it to the queue.
			wp.updates.queue.push( {
				action: 'install-plugin',
				data:   {
					plugin: $itemRow.data( 'plugin' ),
					slug: $itemRow.data( 'slug' )
				}
			} );
		} );

		// Display bulk notification for install of plugin.
		$( document ).on( 'wp-plugin-bulk-install-success wp-plugin-bulk-install-error', function( event, response ) {
			var $itemRow = $( '[data-slug="' + response.slug + '"]' ),
				$bulkActionNotice, itemName;

			if ( 'wp-' + response.install + '-bulk-install-success' === event.type ) {
				success++;
			} else {
				itemName = response.pluginName ? response.pluginName : $itemRow.find( '.plugin-name' ).text();

				error++;
				errorMessages.push( itemName + ': ' + response.errorMessage );
			}

			wp.updates.adminNotice = wp.template( 'wp-bulk-installs-admin-notice' );

			// Remove previous error messages, if any.
			$( '.plugins-details .bulk-action-notice' ).remove();

			$( '.plugins-details .plugins-info' ).after( wp.updates.adminNotice( {
				id:            'bulk-action-notice',
				className:     'bulk-action-notice notice-alt',
				successes:     success,
				errors:        error,
				errorMessages: errorMessages,
				type:          response.install
			} ) );

			$bulkActionNotice = $( '#bulk-action-notice' ).on( 'click', 'button', function() {
				// $( this ) is the clicked button, no need to get it again.
				$( this )
					.toggleClass( 'bulk-action-errors-collapsed' )
					.attr( 'aria-expanded', ! $( this ).hasClass( 'bulk-action-errors-collapsed' ) );
				// Show the errors list.
				$bulkActionNotice.find( '.bulk-action-errors' ).toggleClass( 'hidden' );
			} );

			if ( ! wp.updates.queue.length ) {
				if ( error > 0 ) {
					$target
						.removeClass( 'updating-message' )
						.text( $target.data( 'originaltext' ) );
				} else {
					_this.model.set( { requiredPlugins: false } );
					_this.render();

					// Disable the next and previous demo.
					$( '.theme-install-overlay' ).find( '.next-theme, .previous-theme' ).addClass( 'disabled' );
				}
			}
		} );

		// Reset admin notice template after #bulk-action-notice was added.
		$( document ).on( 'wp-updates-notice-added', function() {
			wp.updates.adminNotice = wp.template( 'wp-updates-admin-notice' );
		} );

		// Check the queue, now that the event handlers have been added.
		wp.updates.queueChecker();
	}
});

// Controls the rendering of div.themes,
// a wrapper that will hold all the theme elements
demos.view.Demos = wp.Backbone.View.extend({

	className: 'themes wp-clearfix',
	$overlay: $( 'div.theme-overlay' ),

	// Number to keep track of scroll position
	// while in theme-overlay mode
	index: 0,

	// The demo count element
	count: $( '.wrap .demo-count' ),

	// The live demos count
	liveDemoCount: 0,

	initialize: function( options ) {
		var self = this;

		// Set up parent
		this.parent = options.parent;

		// Set current view to [grid]
		this.setView( 'grid' );

		// Move the imported demo to the beginning of the collection
		self.importedDemo();

		// When the collection is updated by user input...
		this.listenTo( self.collection, 'demos:update', function() {
			self.parent.page = 0;
			self.importedDemo();
			self.render( this );
		} );

		// Update demo count to full result set when available.
		this.listenTo( self.collection, 'query:success', function( count ) {
			if ( _.isNumber( count ) ) {
				self.count.text( count );
				self.announceSearchResults( count );
			} else {
				self.count.text( self.collection.length );
				self.announceSearchResults( self.collection.length );
			}
		});

		this.listenTo( self.collection, 'query:empty', function() {
			$( 'body' ).addClass( 'no-results' );
		});

		this.listenTo( this.parent, 'demo:scroll', function() {
			self.renderDemos( self.parent.page );
		});
	},

	// Manages rendering of demo pages
	// and keeping demo count in sync
	render: function() {
		// Clear the DOM, please
		this.$el.empty();

		// Generate the demos
		// Using page instance
		// While checking the collection has items
		if ( this.options.collection.size() > 0 ) {
			this.renderDemos( this.parent.page );
		}

		// Display a live demo count for the collection
		this.liveDemoCount = this.collection.count ? this.collection.count : this.collection.length;
		this.count.text( this.liveDemoCount );
	},

	// Iterates through each instance of the collection
	// and renders each demo module
	renderDemos: function( page ) {
		var self = this;

		self.instance = self.collection.paginate( page );

		// If we have no more demos bail
		if ( self.instance.size() === 0 ) {
			// Fire a no-more-demos event.
			this.parent.trigger( 'demo:end' );
			return;
		}

		// Make sure the add-new stays at the end
		if ( demos.isNew && page >= 1 ) {
			$( '.add-new-theme' ).remove();
		}

		// Loop through the demos and setup each demo view
		self.instance.each( function( demo ) {
			self.demo = new demos.view.Demo({
				model: demo,
				parent: self
			});

			// Render the views...
			self.demo.render();
			// and append them to div.themes
			self.$el.append( self.demo.el );
		});

		// 'Suggest us!' element shown at the end of the grid
		if ( demos.isNew && demos.data.settings.suggestURI ) {
			this.$el.append( '<div class="theme add-new-theme"><a href="' + demos.data.settings.suggestURI + '" target="blank"><div class="theme-screenshot"><span></span></div><h2 class="theme-name">' + l10n.suggestNew + '</h2></a></div>' );
		}

		this.parent.page++;
	},

	// Grabs imported demo and puts it at the beginning of the collection
	importedDemo: function() {
		var self = this,
			current;

		current = self.collection.findWhere({ active: true });

		// Move the imported demo to the beginning of the collection
		if ( current ) {
			self.collection.remove( current );
			self.collection.add( current, { at:0 } );
		}
	},

	// Sets current view
	setView: function( view ) {
		return view;
	},

	// Dispatch audible search results feedback message
	announceSearchResults: function( count ) {
		if ( 0 === count ) {
			wp.a11y.speak( l10n.noDemosFound );
		} else {
			wp.a11y.speak( l10n.demosFound.replace( '%d', count ) );
		}
	}
});

// Search input view controller.
demos.view.Search = wp.Backbone.View.extend({

	tagName: 'input',
	className: 'wp-filter-search',
	id: 'wp-filter-search-input',
	searching: false,

	attributes: {
		placeholder: l10n.searchPlaceholder,
		type: 'search',
		'aria-describedby': 'live-search-desc'
	},

	events: {
		'input': 'search',
		'keyup': 'search',
		'blur': 'pushState'
	},

	initialize: function( options ) {

		this.parent = options.parent;

		this.listenTo( this.parent, 'demo:close', function() {
			this.searching = false;
		} );
	},

	search: function( event ) {
		// Clear on escape.
		if ( event.type === 'keyup' && event.which === 27 ) {
			event.target.value = '';
		}

		// Since doSearch is debounced, it will only run when user input comes to a rest.
		this.doSearch( event );
	},

	// Runs a search on the demo collection.
	doSearch: function( event ) {
		var options = {};

		this.collection.doSearch( event.target.value.replace( /\+/g, ' ' ) );

		// if search is initiated and key is not return
		if ( this.searching && event.which !== 13 ) {
			options.replace = true;
		} else {
			this.searching = true;
		}

		// Update the URL hash
		if ( event.target.value ) {
			demos.router.navigate( demos.router.baseUrl( demos.router.searchPath + event.target.value ), options );
		} else {
			demos.router.navigate( demos.router.baseUrl( '' ) );
		}
	},

	pushState: function( event ) {
		var url = demos.router.baseUrl( '' );

		if ( event.target.value ) {
			url = demos.router.baseUrl( demos.router.searchPath + encodeURIComponent( event.target.value ) );
		}

		this.searching = false;
		demos.router.navigate( url );
	}
});

/**
 * Navigate router.
 *
 * @since 1.5.0
 *
 * @param {string} url - URL to navigate to.
 * @param {object} state - State.
 * @returns {void}
 */
function navigateRouter( url, state ) {
	var router = this;
	if ( Backbone.history._hasPushState ) {
		Backbone.Router.prototype.navigate.call( router, url, state );
	}
}

// Sets up the routes events for relevant url queries
// Listens to [demo] and [search] params
demos.Router = Backbone.Router.extend({

	routes: {
		'themes.php?page=demo-importer&demo=:slug': 'preview',
		'themes.php?page=demo-importer&browse=:sort': 'sort',
		'themes.php?page=demo-importer&search=:query': 'search',
		'themes.php?page=demo-importer': 'sort'
	},

	baseUrl: function( url ) {
		return 'themes.php?page=demo-importer' + url;
	},

	demoPath: '&demo=',
	browsePath: '&browse=',
	searchPath: '&search=',

	search: function( query ) {
		$( '.wp-filter-search' ).val( query.replace( /\+/g, ' ' ) );
	},

	navigate: navigateRouter
});

// Extend the main Search view
demos.view.InstallerSearch = demos.view.Search.extend({

	events: {
		'input': 'search',
		'keyup': 'search'
	},

	terms: '',

	// Handles Ajax request for searching through demos in public repo
	search: function( event ) {

		// Tabbing or reverse tabbing into the search input shouldn't trigger a search
		if ( event.type === 'keyup' && ( event.which === 9 || event.which === 16 ) ) {
			return;
		}

		this.collection = this.options.parent.view.collection;

		// Clear on escape.
		if ( event.type === 'keyup' && event.which === 27 ) {
			event.target.value = '';
		}

		this.doSearch( event.target.value );
	},

	doSearch: function( value ) {
		var request = {};

		// Don't do anything if the search terms haven't changed.
		if ( this.terms === value ) {
			return;
		}

		// Updates terms with the value passed.
		this.terms = value;

		request.search = value;

		$( '.filter-links li > a.current' )
			.removeClass( 'current' )
			.removeAttr( 'aria-current' );

		this.collection.doSearch( value.replace( /\+/g, ' ' ) );

		// Set route
		demos.router.navigate( demos.router.baseUrl( demos.router.searchPath + encodeURIComponent( value ) ), { replace: true } );
	}
});

demos.view.Installer = demos.view.Appearance.extend({

	el: '#wpbody-content .wrap',

	// Register events for sorting and filters in demo-navigation
	events: {
		'click .filter-links li > a': 'onSort'
	},

	// Initial render method
	render: function() {
		var self = this;

		this.search();

		this.collection = new demos.Collection();

		// Bump `collection.currentQuery.page` and request more demos if we hit the end of the page.
		this.listenTo( this, 'demo:end', function() {

			// Make sure we are not already loading
			if ( self.collection.loadingDemos ) {
				return;
			}

			// Set loadingDemos to true and bump page instance of currentQuery.
			self.collection.loadingDemos = true;
			self.collection.currentQuery.page++;

			// Use currentQuery.page to build the demos request.
			_.extend( self.collection.currentQuery.request, { page: self.collection.currentQuery.page } );
			self.collection.query( self.collection.currentQuery.request );
		});

		this.listenTo( this.collection, 'query:success', function() {
			$( 'body' ).removeClass( 'loading-content' );
			$( '.theme-browser' ).find( 'div.error' ).remove();
		});

		this.listenTo( this.collection, 'query:fail', function() {
			$( 'body' ).removeClass( 'loading-content' );
			$( '.theme-browser' ).find( 'div.error' ).remove();
			$( '.theme-browser' ).find( 'div.themes' ).before( '<div class="error"><p>' + l10n.error + '</p><p><button class="button try-again">' + l10n.tryAgain + '</button></p></div>' );
			$( '.theme-browser .error .try-again' ).on( 'click', function( e ) {
				e.preventDefault();
				$( 'input.wp-filter-search' ).trigger( 'input' );
			} );
		});

		if ( this.view ) {
			this.view.remove();
		}

		// Set ups the view and passes the section argument
		this.view = new demos.view.Demos({
			collection: this.collection,
			parent: this
		});

		// Reset pagination every time the install view handler is run
		this.page = 0;

		// Render and append
		this.$el.find( '.themes' ).remove();
		this.view.render();
		this.$el.find( '.theme-browser' ).append( this.view.el ).addClass( 'rendered' );
	},

	// Handles all the rendering of the public demo directory
	browse: function( section, builder ) {
		// Create a new collection with the proper demo data
		// for each section
		this.collection.query( { browse: section, builder: builder } );
	},

	// Sorting navigation
	onSort: function( event ) {
		var $el = $( event.target ),
			sort = $el.data( 'sort' ),
			type = $el.data( 'type' );

		event.preventDefault();

		// Restore the previous browse tab if available.
		sort = sort ? sort : demos.router.selectedTab;
		type = type ? type : demos.router.selectedType;

		// Bail if this is already active
		if ( $el.hasClass( this.activeClass ) ) {
			return;
		}

		this.sort( sort, type );

		// Trigger a router.naviagte update
		demos.router.navigate( demos.router.baseUrl( demos.router.browsePath + sort ) );
	},

	sort: function( sort, type ) {
		this.clearSearch();

		// Track sorting so we can restore the correct tab when closing preview.
		demos.router.selectedTab  = sort;
		demos.router.selectedType = type;

		$( '.filter-links li > a' )
			.removeClass( this.activeClass )
			.removeAttr( 'aria-current' );

		$( '[data-sort="' + sort + '"]' )
			.addClass( this.activeClass )
			.attr( 'aria-current', 'page' );

		$( '[data-type="' + type + '"]' )
			.addClass( this.activeClass )
			.attr( 'aria-current', 'page' );

		this.browse( sort, type );
	},

	activeClass: 'current',

	clearSearch: function() {
		$( '#wp-filter-search-input' ).val( '' );
	}
});

// Execute and setup the application
demos.RunInstaller = {

	init: function() {
		// Set up the view
		// Passes the default 'section' as an option
		this.view = new demos.view.Installer({
			section: 'all',
			SearchView: demos.view.InstallerSearch
		});

		// Render results
		this.render();

		// Start debouncing user searches after Backbone.history.start().
		this.view.SearchView.doSearch = _.debounce( this.view.SearchView.doSearch, 500 );
	},

	render: function() {

		// Render results
		this.view.render();
		this.routes();

		if ( Backbone.History.started ) {
			Backbone.history.stop();
		}
		Backbone.history.start({
			root: demos.data.settings.adminUrl,
			pushState: true,
			hashChange: false
		});
	},

	routes: function() {
		var self = this,
			request = {};

		// Bind to our global `wp.demos` object
		// so that the object is available to sub-views
		demos.router = new demos.Router();

		// Handles `demo` route event
		// Queries the API for the passed demo slug
		demos.router.on( 'route:preview', function( slug ) {

			// Remove existing handlers.
			if ( demos.preview ) {
				demos.preview.undelegateEvents();
				demos.preview.unbind();
			}

			// If the demo preview is active, set the current demo.
			if ( self.view.view.demo && self.view.view.demo.preview ) {
				self.view.view.demo.model = self.view.collection.findWhere( { 'slug': slug } );
				self.view.view.demo.preview();
			} else {

				// Select the demo by slug.
				request.demo = slug;
				self.view.collection.query( request );
				self.view.collection.trigger( 'update' );

				// Open the theme preview.
				self.view.collection.once( 'query:success', function() {
					$( 'div[data-slug="' + slug + '"]' ).trigger( 'click' );
				});
			}
		});

		// Handles sorting / browsing routes
		// Also handles the root URL triggering a sort request
		// for `all`, the default view
		demos.router.on( 'route:sort', function( sort ) {
			var type = demos.router.selectedType ? demos.router.selectedType : $( '.filter-links.pagebuilders li' ).first().find( 'a' ).data( 'type' );

			if ( ! sort || ! $( '[data-sort="' + sort + '"]' ).length ) {
				sort = 'all';
				demos.router.navigate( demos.router.baseUrl( '&browse=all' ), { replace: true } );
			}

			self.view.sort( sort, type );

			// Close the preview if open.
			if ( demos.preview ) {
				demos.preview.close();
			}
		});

		// The `search` route event. The router populates the input field.
		demos.router.on( 'route:search', function() {
			$( '.wp-filter-search' ).focus().trigger( 'keyup' );
		});

		this.extraRoutes();
	},

	extraRoutes: function() {
		return false;
	}
};

// Ready...
$( document ).ready( function() {
	demos.RunInstaller.init();

	// Initialize TipTip.
	$( document.body ).on( 'init_tooltips', function() {
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.tips' ).tipTip({ 'attribute': 'data-tip', 'defaultPosition': 'top', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });
	} ).trigger( 'init_tooltips' );

	// Confirm WordPress reset wizard.
	$( '.themegrill-reset-wordpress' ).on( 'click', function() {
		return window.confirm( _demoImporterSettings.settings.confirmReset );
	});

	// Change the footer text when rating link is clicked.
	$( '.themegrill-demo-importer-rating-link' ).on( 'click', function() {
		var $this_el = $( this );

		$.post( demos.data.settings.ajaxUrl, {
			action: 'footer-text-rated'
		});

		$this_el.parent().text( $this_el.data( 'rated' ) );
	} );
});

})( jQuery );
