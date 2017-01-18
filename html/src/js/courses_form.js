( function( $ ) {

	/*
	iCheck
	 */
	$( function() {
		$( '[data-courses_form] input[type="radio"], [data-courses_form] input[type="checkbox"]' ).iCheck( {
			checkboxClass: 'icheckbox_square-blue',
			radioClass: 'iradio_square-blue',
		} );
	} );

	/*
	Form Switching
	 */
	var FormSwithing = {

		init: function( switch_group ) {

			this.switch_group = switch_group;
			this.switches = this.switch_group.find( '[data-form-switching-target]' );
			this.targets = this.findTargets();

			//Events
			this.switchTargets();
			this.switches.on( 'ifChanged', $.proxy( this.switchTargets, this ) );
		},

		/**
		 * Find all targets associated with current switches
		 *
		 * @returns {JQuery|HTMLElement}
		 */
		findTargets: function() {

			var targets = [];

			for( var i = 0; i < this.switches.length; i++ ) {

				var target = $( this.switches[ i ] ).data( 'form-switching-target' );
				targets.push( $( '[data-form-switching-id="' + target + '"]' )[0] );

			}

			return $( targets );

		},

		/**
		 * Process all switches
		 */
		switchTargets: function() {

			for( var i = 0; i < this.switches.length; i++ ) {
				var current_switch = $( this.switches[ i ] );
				var checked = current_switch.is( ':checked' );

				if( checked ) {
					this.showTarget( current_switch.data( 'form-switching-target' ) );
				} else {
					this.hideTarget( current_switch.data( 'form-switching-target' ) );
				}

			}

		},

		/**
		 * Show one target based on it's name
		 *
		 * @param target_name
		 */
		showTarget: function( target_name ) {

			var target = this.targets.filter( '[data-form-switching-id="' + target_name + '"]:first' );
			target.find( 'input,select' ).attr( 'disabled', false );
			target.velocity( 'slideDown' );

		},

		/**
		 * Hide one target based on it's name
		 *
		 * @param target_name
		 */
		hideTarget: function( target_name ) {

			var target = this.targets.filter( '[data-form-switching-id="' + target_name + '"]:first' );
			target.find( 'input,select' ).attr( 'disabled', true );
			target.velocity( 'slideUp' );

		}

	};

	/*
	DOM ready
	 */
	$( function() {
		$( '[data-form-switching-group]' ).each( function() {
			Object.create( FormSwithing ).init( $( this ) );
		} );
	} );

} )( jQuery );