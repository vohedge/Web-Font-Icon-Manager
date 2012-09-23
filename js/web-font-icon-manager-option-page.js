jQuery( document ).ready( function() {
	jQuery( 'a.toggle' ).click( function() {
		jQuery( this ).parents( 'div#font_list' ).children( 'ul.file_list' ).slideToggle();
		return false;
	});
});
