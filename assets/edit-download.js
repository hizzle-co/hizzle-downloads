"use strict";

jQuery( document ).ready( function( $ ) {

    var downloadable_file_frame;
	var file_path_field;

	// Upload file.
	$( document.body ).on( 'click', '.hizzle-upload-downloadable-file', function( event ) {
		var $el = $( this );

		file_path_field = $( '#hizzle-file-url' );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( downloadable_file_frame ) {
			downloadable_file_frame.open();
			return;
		}

		var downloadable_file_states = [
			// Main states.
			new wp.media.controller.Library({
				library:   wp.media.query(),
				multiple:  true,
				title:     $el.data('choose'),
				priority:  20,
				filterable: 'uploaded'
			})
		];

		// Create the media frame.
		downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),
			library: {
				type: ''
			},
			button: {
				text: $el.data('update')
			},
			multiple: false,
			states: downloadable_file_states
		});

		// When a file is selected, update the file URL text field.
		downloadable_file_frame.on( 'select', function() {
			var file_path = '';
			var selection = downloadable_file_frame.state().get( 'selection' );

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();
				if ( attachment.url ) {
					file_path = attachment.url;
				}
			});

			file_path_field.val( file_path ).trigger( 'change' );
		});

		// Set post to 0 and set our custom type.
		downloadable_file_frame.on( 'ready', function() {
			downloadable_file_frame.uploader.options.uploader.params = {
				type: 'hizzle_downloadable_file'
			};
		});

		// Finally, open the modal.
		downloadable_file_frame.open();
	});

	// Fetch GitHub repo.
	$( document.body ).on( 'click', '.hizzle-downloads-fetch-github-repo', function( event ) {
		event.preventDefault();

		$( '.hizzle-git-url-spinner' ).css( 'visibility', 'visible' );
		$( '.hizzle-git-url-success, .hizzle-git-url-error' ).hide();

		// Disable button.
		$( '.hizzle-downloads-fetch-github-repo' ).prop( 'disabled', true );

		// POST
		wp

			// Send the repo URL to the server.
			.apiFetch( {
				path: '/hizzle_download/v1/github-updater/manual-update',
				method: 'POST',
				data: {
					repository: $( '#hizzle-git-url' ).val(),
					tag: $( '#hizzle-git-tag' ).val(),
				},
			} )

			.then( function ( res ) {
				$( '#hizzle-git-update-key' ).val( res.update_key );
				$( '#hizzle-file-url' ).val( res.file_url );

				$( '.hizzle-git-url-success' ).show();

				setTimeout( function() {
					$( '.hizzle-git-url-success' ).hide();
				}, 4000);

				return res;
			} )

			// Handle errors.
			.catch( function ( err ) {

				// Error will have a message, code and data that's passed to WP_Error.
				if ( err && err.message ) {
					var error = err.message;
				}

				// If not, render the default error message.
				else {
					var error = wp.i18n.__( 'An unexpected error occured. Please try again.', 'hizzle-downloads' );
				}

				$( '.hizzle-git-url-error' ).text( error ).show();

				setTimeout( function() {
					$( '.hizzle-git-url-error' ).hide();
				}, 6000);
			} )

			// Unblock the form.
			.finally( function() {
				$( '.hizzle-git-url-spinner' ).css( 'visibility', 'hidden' );
				$( '.hizzle-downloads-fetch-github-repo' ).prop( 'disabled', false );
			});
	});

	// Conditional logic editing app.
	Vue
		.createApp({
			data: function data() {
				return $( '#hizzle-downloads-edit-conditional-logic-app' ).data( 'conditional-logic' );
			},
			methods: {

				// Checks if there are rule options.
				hasRuleOptions: function hasRuleOptions( rule_type ) {
					return this.allRules[ rule_type ] !== undefined && this.allRules[ rule_type ].options !== undefined;
				},

				// Retrieves the rule options.
				getRuleOptions: function getRuleOptions( rule_type ) {
					return this.allRules[ rule_type ].options;
				},

				// Adds a new rule.
				addRule: function addRule() {
					this.rules.push({
						type: 'user_role',
						condition: 'is',
						value: 'administrator'
					});
				},

				// Removes an existing rule.
				removeRule: function removeRule( rule ) {
					this.rules.splice( this.rules.indexOf( rule ), 1 );
				},

				// Checks if a rule is the last one.
				isLastRule: function isLastRule( index ) {
					return index === this.rules.length - 1;
				}
			}
		})
		.mount('#hizzle-downloads-edit-conditional-logic-app')

})
