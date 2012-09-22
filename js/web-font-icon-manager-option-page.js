var AddFontUrlInput = function() {
	this.add_input_box();
	this.remove_input_box();
}
AddFontUrlInput.prototype = {
	add_input_box : function() {
		var font_file_fields = jQuery( 'ul#font_file_fields' );
		jQuery( 'input#add_font_file' ).click( function() {
			jQuery( 'ul#font_file_fields' ).append( '<li><input type="text" name="wfim_font_urls[]" size="80" /><span class="remove"> X </span></li>' );
		});
	},
	remove_input_box : function() {
		jQuery( 'ul#font_file_fields>li>span.remove' ).live( 'click', function() {
			jQuery( this ).closest( 'li' ).remove();
		});
	}
}

jQuery( document ).ready( function() {
	new AddFontUrlInput();
});
