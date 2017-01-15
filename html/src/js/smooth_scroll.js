/* global jQuery */
( function( $ ){

	$( function() {

		$( 'a[href^="#"]' ).on( 'click', function() {
			var link = $( this ),
				target = $( link.attr( 'href' ) );
			target.velocity( 'scroll', { duration: 1000 } );
		} );

	} );

} )( jQuery );