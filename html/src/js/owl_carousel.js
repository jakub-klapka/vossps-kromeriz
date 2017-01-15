/* global jQuery */
( function( $ ){

	var owl;

	$( function(){

		var wraper = $( '#main_slider' );

		var owl_el = wraper.owlCarousel( {
			singleItem: true,
			autoPlay: wraper.data( 'timeout' ),
			stopOnHover: true,
			responsiveBaseWidth: '.main_slider'
		} );

		owl = owl_el.data('owlCarousel');

	} );

	$( window ).load( function(){
		owl.updateVars();
	} );

} )( jQuery );