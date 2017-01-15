/* global jQuery */
( function( $ ){

	var HeaderMenu = {

		init: function( container ) {

			this.container = container;
			this.submenu = this.container.find( 'ul' );
			this.duration = 200;

			this.bindEvents();
		},

		bindEvents: function() {

			this.container.hover( $.proxy( this.showMenu, this ), $.proxy( this.hideMenu, this ) );

		},

		showMenu: function() {
			this.submenu.velocity( 'stop' ).velocity( 'fadeIn', { duration: this.duration } );
		},

		hideMenu: function() {
			this.submenu.velocity( 'stop' ).velocity( 'fadeOut', { duration: this.duration } );
		}

	};

	$( function() {
		$( '.main_header__quick_menu__ul__item:has(ul)' ).each( function(){
			Object.create( HeaderMenu ).init( $( this ) );
		} );
	} );

} )( jQuery );