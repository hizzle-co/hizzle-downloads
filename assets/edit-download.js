"use strict";

jQuery( document ).ready( function( $ ) {

    var downloadable_file_frame;
	var file_path_field;

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

	// Conditional logic editing app.
	Vue
		.createApp({
			data: function() {
				return $( '#hizzle-downloads-edit-conditional-logic-app' ).data( 'conditional-logic' );
			},
			methods: {

				// Checks if there are rule options.
				hasRuleOptions: function( rule_type ) {
					return this.allRules[ rule_type ] !== undefined && this.allRules[ rule_type ].options !== undefined;
				},

				// Retrieves the rule options.
				getRuleOptions: function( rule_type ) {
					return this.allRules[ rule_type ].options;
				},

				// Adds a new rule.
				addRule: function() {
					this.rules.push({
						type: 'user_role',
						condition: 'is',
						value: 'administrator'
					});
				},

				// Removes an existing rule.
				removeRule: function( rule ) {
					this.rules.splice( this.rules.indexOf( rule ), 1 );
				},

				// Checks if a rule is the last one.
				isLastRule: function( index ) {
					return index === this.rules.length - 1;
				}
			}
		})
		.mount('#hizzle-downloads-edit-conditional-logic-app')

})
