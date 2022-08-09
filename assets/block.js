"use strict";

// external dependencies : hizzleDownloads
(function (registerBlockType, createElement) {
	registerBlockType('hizzle/downloads', {
		title: hizzleDownloads.blockName,
		description: hizzleDownloads.blockDescription,
		category: 'widgets',
		icon: 'open-folder',
		supports: {
			html: false
		},

		edit: function edit(props) {
			return createElement("div", {
				style: {
					backgroundColor: '#f8f9f9',
					padding: '14px'
				}
			}, hizzleDownloads.placeholderText);
		},

		// Render nothing in the saved content, because we render in PHP
		save: function save(props) {
			return null;
		}
	});
})(window.wp.blocks.registerBlockType, window.wp.element.createElement);
