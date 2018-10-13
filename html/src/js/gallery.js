/* global jQuery */
( function( $ ){

	$( function() {

		$( '.gallery_root__section_heading' ).each( function() {

			var heading = $( this );
			heading.on( 'click', function( evt ) {
				evt.preventDefault();

				var is_closed = heading.hasClass( 'is-closed' );
				var container = $( '#' + heading.data('target') );

				if( is_closed ) {
					container.velocity( 'slideDown', { duration: 500 } );
					heading.removeClass( 'is-closed' );
				} else {
                    container.velocity( 'slideUp', { duration: 500 } );
                    heading.addClass( 'is-closed' );
				}

			} );

		} );

		$( '.ped_gallery a' ).fancybox({
			padding: 0,
			type: 'image'
		});

	} );

} )( jQuery );