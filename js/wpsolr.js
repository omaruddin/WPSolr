jQuery( document ).ready( function( $ ) {
	var change_field_type = function() {
		// determine what type of inputs we are dealing with
		var itype = $( 'option:selected', this ).closest( 'optgroup' ).attr( 'class' );
		var thisrow = $( this ).closest( 'tr' );
		// hide all of the type_options divs
		$( '.type_options', thisrow ).slideUp();
		// show the correct set of option fields based on the thing chosen
		switch( itype ) {
			case 'text_types':
				// nothing to do
				break;
			case 'choice_types':
				$( '.choice_type_options', thisrow ).slideDown();
				break;
			case 'range_types':
				$( '.range_type_options', thisrow ).slideDown();
				break;			
		}
	};
	$( '.wpsolr_settings_field_type_selector' ).change( change_field_type );

	$( '#wpsolr_add_field_button' ).click( function() {
		$.get(
			ajaxurl,
			{
				action: 'add_field',
				next: $( '#wpsolr-fields-table tbody tr' ).length
			},
			function( res ) {
				$( '#wpsolr-fields-table tbody' ).append( res );
			}
		);
	});
});