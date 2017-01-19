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
			var inputs = target.find( 'input,select' );

			//Set all inputs as enabled
			inputs.attr( 'disabled', false );

			//Set required where aplicable
			inputs.filter( '[data-required]' ).attr( 'required', 'required' );

			//Velocity
			target.velocity( 'slideDown' );

		},

		/**
		 * Hide one target based on it's name
		 *
		 * @param target_name
		 */
		hideTarget: function( target_name ) {

			var target = this.targets.filter( '[data-form-switching-id="' + target_name + '"]:first' );
			var inputs = target.find( 'input,select' );

			//Set all inputs as enabled
			inputs.attr( 'disabled', true );

			//Set required where aplicable
			inputs.filter( '[required]' ).attr( 'required', false );

			//Velocity
			target.velocity( 'slideUp' );

		}

	};

	/*
	Scroll to form
	 */
	var ScrollToForm = {

		init: function( button ) {
			this.button = button;
			this.target = $( button.data( 'scroll-to' ) ).first();

			this.button.on( 'click', $.proxy( this.scrollToTarget, this ) );
		},

		scrollToTarget: function () {
			this.target.velocity( 'scroll' );
		}

	};


	/*
	DOM ready
	 */
	$( function() {
		$( '[data-form-switching-group]' ).each( function() {
			Object.create( FormSwithing ).init( $( this ) );
		} );

		$( '[data-scroll-to]' ).each( function() {
			Object.create( ScrollToForm ).init( $( this ) );
		} );
	} );

} )( jQuery );