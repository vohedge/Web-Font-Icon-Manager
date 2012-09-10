var AddExtraMenuOption = function() {
	this.saved_data_icons = ( typeof( menu_item_data_icons ) != 'undefined' ) ? menu_item_data_icons : '';
	this.menu_items = jQuery( '#menu-to-edit>li' );
	this.init();
	this.new_menu();
}
AddExtraMenuOption.prototype = {
	get_menu_id : function( j ) {
		var id = j.attr( 'id' );
		id = id.replace( /menu-item-/, '' );
		return id;
	},
	init : function() {
		var self = this;
		this.update_menu_item();
		this.menu_items.each( function() {
			var j = jQuery( this );
			if ( ! j.find( 'p.field-data-icon' ).size() ) {
				var id = self.get_menu_id( j );
				var data_icon = self.saved_data_icons[id] || '';
				var field = '<p class="field-data-icon description description-thin">';
				field += '<label for="edit-menu-iteme-data-icon-' + id + '">';
				field += '"data-icon"<br />'
				field += '</label>';
				field += '<input type="text" id="edit-menu-iteme-data-icon-' + id + '" class="widefat code edit-menu-item-icon" name="menu-item-data-icon[' + id + ']" value="' + data_icon + '" />';
				field += '</p>';
				j.find( 'div.menu-item-settings>p.field-css-classes' ).after( field );
			}
		});
	},
	new_menu : function() {
		var self = this;
		jQuery( document ).ajaxComplete( function() {
			self.init();
		});
	},
	update_menu_item : function() {
		this.menu_items = jQuery( '#menu-to-edit>li' );
	}
}


jQuery( document ).ready( function() {
	new AddExtraMenuOption();
});
