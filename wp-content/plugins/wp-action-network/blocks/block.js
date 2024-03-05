var site_url = WPURLS.siteurl;
var SelectControl = wp.components.SelectControl;
var el = wp.element.createElement;
var actionNetworkLists = [{
	'label' : 'Select Action Network',
	'value'	: ''
}];

jQuery.ajax({
	url: site_url + "/wp-admin/admin-ajax.php",
	type: "POST",
	data: {
		action: "getActionNetworks"
	},
	success: function (response) {
		var lists = JSON.parse(response);

		jQuery.each(lists, function(index, obj){
			actionNetworkLists.push({
				'label' : obj.title,
				'value'	: obj.id
			});
		});
	},
	error: function(jqXHR, textStatus, errorThrown) {
	   console.log(textStatus, errorThrown);
	}
});

wp.blocks.registerBlockType('actionnetwork/embed-action-network', {
	title: 'Action Network',		// Block name visible to user
	icon: 'shortcode',	// Toolbar icon can be either using WP Dashicons or custom SVG
	category: 'embed',	// Under which category the block would appear
	attributes: {			// The data this block will be storing
		type: { type: 'string', default: "" },
		content: { type: 'array', source: 'children', selector: 'p' }
	},
	edit: function(props) {
		
		function updateType( networkId ) {
			props.setAttributes( { type: networkId } );
			updateContent( networkId );
		}

		function updateContent( action_id ) {
			if ( action_id != '' ) {
				props.setAttributes( { content: "[actionnetwork id=" +action_id+ "]" } );
			} else {
				props.setAttributes( { content: "" } );
			}
		}

		return el( 'div', 
			{
				className: props.attributes.type
			},
			el(SelectControl, {
				label: "",
				help: "",
				selected: props.attributes.type,
				options: actionNetworkLists,
				onChange: updateType
			}),
			el(
				wp.blockEditor.RichText,
				{
				   tagName: 'p',
				   value: props.attributes.content,
				   placeholder: ''
				}
			)
		);	// End return
	},	// End edit()
	save: function(props) {
		// How our block renders on the frontend
		
		return el( 'div', { className: props.attributes.type },
			el( wp.blockEditor.RichText.Content, {
				tagName: 'p',
				value: props.attributes.content
			})
		);	// End return
		
	} // End save()
});