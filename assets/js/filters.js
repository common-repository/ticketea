/**
 * Ticketea event list filters
 *
 * @author     Ticketea
 * @package    Ticketea\Assets\JS
 * @since      1.0.0
 */

/* global ajaxurl, ticketeaFiltersL10n */

/**
 * Event list filters function.
 *
 * @param {jQuery} $ The jQuery instance.
 * @param {Object} options The options.
 */
;(function( $, options ) {

	'use strict';

	var TicketeaFilters = function( options ) {
		var defaults = {
			categories: {},
			filters: {
				typology: '',
				topic: []
			},
			texts: {
				countryPlaceholder: 'Select countries',
				categoryPlaceholder: 'Select a category',
				typologyPlaceholder: 'Select a typology',
				topicPlaceholder: 'Select topics',
				emptyFilters: 'You must choose at least one filter.',
				noEventIds: 'The filter Events IDs is empty.',
				testModalTitle: 'Test Filters'
			},
			adminNotice: '<div id="message" class="{{type}} notice"><p>{{message}}</p></div>',
			testModalContent: '<p>No content</p>'
		};

		this.options = $.extend( {}, defaults, options );
		this.$form = $( 'form#post' );
		this.$metabox = $( '#event-list-filters' );
		this.$filterBy = this.$metabox.find( 'input[name=event_list_filter_by]' );
		this.$country = this.$metabox.find( '#venue_country_code' );
		this.$category = this.$metabox.find( '#category' );
		this.$typology = this.$metabox.find( '#typology' );
		this.$topic = this.$metabox.find( '#topic' );
		this.$testButton = this.$metabox.find( '#test-filters' );
		this.$testModal = null;
		this.$filters = null;
	};

	TicketeaFilters.prototype = {

		init: function() {
			this._initFiltersDisplay();
			this._initSelects();
			this._createTestModal();
			this._bindEvents();
		},

		_initFiltersDisplay: function() {
			var filterBy = this.getFilterByValue();

			if ( 'event_id' === filterBy ) {
				this.$metabox.find( '#event-ids-filters' ).show();
				this.$metabox.find( '#others-filters' ).hide();
			} else {
				this.$metabox.find( '#event-ids-filters' ).hide();
				this.$metabox.find( '#others-filters' ).show();
			}
		},

		_initSelects: function() {
			this.$country.select2({
				placeholder: this.options.texts.countryPlaceholder,
				allowClear: true
			});

			this.$category.select2({
				placeholder: this.options.texts.categoryPlaceholder,
				allowClear: true
			});

			this._initTypologySelect2( this.$category.val(), this.options.filters.typology );
			this._initTopicSelect2( this.$category.val(), this.options.filters.topic );
		},

		_initTypologySelect2: function( categoryId, value ) {
			var typologies = this.getCategoryData( categoryId, 'typologies' );

			// Clear previous data.
			if ( this.$typology.data( 'select2' ) ) {
				this.$typology.find( 'option[value]' ).remove();
			}

			this.$typology.select2({
				placeholder: this.options.texts.typologyPlaceholder,
				allowClear: true,
				data: typologies
			});

			if ( typologies.length ) {
				this.$typology.prop( 'disabled', false );
			} else {
				value = '';
				this.$typology.prop( 'disabled', true );
			}

			this.$typology.val( value ).trigger( 'change' );
		},

		_initTopicSelect2: function( categoryId, value ) {
			var topic = this.getCategoryData( categoryId, 'topics' );

			// Clear previous data.
			if ( this.$topic.data( 'select2' ) ) {
				this.$topic.find( 'option[value]' ).remove();
			}

			this.$topic.select2({
				placeholder: this.options.texts.topicPlaceholder,
				allowClear: true,
				data: topic
			});

			if ( topic.length ) {
				this.$topic.prop( 'disabled', false );
			} else {
				value = null;
				this.$topic.prop( 'disabled', true );
			}

			this.$topic.val( value ).trigger( 'change' );
		},

		_createTestModal: function() {
			var that = this;

			this.$testModal = $( this.options.testModalContent );
			this.$testModal.dialog({
				dialogClass   : 'wp-dialog tkt-dialog no-close',
				title         : this.options.texts.testModalTitle,
				modal         : true,
				autoOpen      : false,
				closeOnEscape : false,
				minWidth      : 450,
				open          : function() {
					that.$testModal.html( that.options.testModalContent );
					that.$testModal.load( ajaxurl, {
						action: 'ticketea_test_filters',
						filters: that.getFiltersValues()
					});
				}
			});
		},

		_bindEvents: function() {
			var that = this;

			this.$filterBy.on( 'change', function() {
				that._initFiltersDisplay();
			});

			this.$category.on( 'change', function() {
				var categoryId = $( this ).val();

				that._initTypologySelect2( categoryId, '' );
				that._initTopicSelect2( categoryId, null );
			});

			this.$testButton.on( 'click', function( event ) {
				event.preventDefault();
				that.testFilters();
			});

			this.$testModal.on( 'click', '.close-modal', function( event ) {
				event.preventDefault();
				that.$testModal.dialog( 'close' );
			});

			this.$form.submit( function ( event ) {
				if ( that.validateFilters() ) {
					return;
				}

				event.preventDefault();
			});
		},

		validateFilters: function() {
			var filters = this.getFiltersValues();

			if ( 'event_id' === this.getFilterByValue() ) {
				if ( ! filters.event_id ) {
					this.addAdminNotice( 'error', this.options.texts.noEventIds );
					return false;
				}
			} else {
				for ( var index in filters ) {
					if ( filters[ index ] ) {
						return true;
					}
				}

				this.addAdminNotice( 'error', this.options.texts.emptyFilters );

				return false;
			}

			return true;
		},

		testFilters: function() {
			this.$testModal.dialog( 'open' );
		},

		addAdminNotice: function( type, message ) {
			var notice = this.options.adminNotice.replace( '{{type}}', type ).replace( '{{message}}', message );

			$( '#message' ).remove();

			this.$form.before( notice );
		},

		getCategoryData: function( categoryId, key ) {
			var data = [];

			if ( categoryId && this.options.categories[ categoryId ] && this.options.categories[ categoryId ][ key ] ) {
				data = this.options.categories[ categoryId ][ key ];
			}

			return data;
		},

		getFilterByValue: function() {
			return this.$filterBy.filter( ':checked' ).val();
		},

		getFilters: function() {
			if ( ! this.$filters ) {
				this.$filters = $( '[name^="event_list_filters"]' ).not( '[name*="_wpnonce"]' );
			}

			return this.$filters;
		},

		getFiltersValues: function() {
			var $filters = this.getFilters(),
			    filters = {},
			    values = {};

			$filters.each(function() {
				var value = $.trim( $( this ).val() ),
					name = $( this ).attr( 'name' ).replace( /event_list_filters/g, '' ).replace( /\[/g, '' ).replace( /\]/g, '' );

				values[ name ] = value;
			});

			if ( 'event_id' === this.getFilterByValue() ) {
				filters = { event_id: values.event_id };
			} else {
				filters = values;
				delete filters.event_id;
			}

			return filters;
		}

	};

	$(function() {
		new TicketeaFilters( options ).init();
	});

})( jQuery, ticketeaFiltersL10n );
