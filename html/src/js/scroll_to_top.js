/* global jQuery */
( function( $ ){

	var ScrollToTop = {

		init: function( el ) {
			this.el = el;
			this.window = $( window );
			this.hidden = true;
			this.duration = 300;

			this.bindEvents();
		},

		bindEvents: function() {
			this.window.on( 'scroll', $.proxy( this.checkForScroll, this ) );
			this.el.on( 'click', $.proxy( this.scroll, this ) );
		},

		checkForScroll: function() {
			var scroll_position = this.window.scrollTop();
			if( scroll_position === 0 && this.hidden === false ) {
				this.hide();
			}
			if( scroll_position !== 0 && this.hidden === true ) {
				this.show();
			}
		},

		show: function(){
			this.el.velocity( 'stop' ).velocity( 'fadeIn', { duration: this.duration } );
			this.hidden = false;
		},

		hide: function(){
			this.el.velocity( 'stop' ).velocity( 'fadeOut', { duration: this.duration } );
			this.hidden = true;
		},

		scroll: function() {
			$('html').velocity( 'scroll', { duration: 1000, offset: 0 } );
		}



	};

	$( function(){
		$( '.scroll_to_top' ).each( function(){
			Object.create( ScrollToTop ).init( $( this ) );
		} );
	} );

} )( jQuery );