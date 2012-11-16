jQuery( document ).ready( function( $ ) {
	$( '#wpsolr_settings_field_type' ).change( function() {
		// determine what type of inputs we are dealing with
		var itype = $( 'option:selected', this ).closest( 'optgroup' ).attr( 'class' );
		// hide all of the type_options divs
		$( '.type_options' ).slideUp();
		// show the correct set of option fields based on the thing chosen
		switch( itype ) {
			case 'text_types':
				// nothing to do
				break;
			case 'choice_types':
				$( '.choice_type_options' ).slideDown();
				break;
			case 'range_types':
				$( '.range_type_options' ).slideDown();
				break;			
		}
	});
});