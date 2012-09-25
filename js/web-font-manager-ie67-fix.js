jQuery( document ).ready( function() {
	jQuery( '.icon' ).each( function() {
		var j = jQuery( this );
		var data_icon = j.attr( 'data-icon' );
		if ( data_icon )
			j.prepend( '<span class="i">' + data_icon + '</span>' );
	});
});
