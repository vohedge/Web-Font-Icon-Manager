var AddExtraMenuOption = function() {
	this.saved_data_icons = ( typeof( menu_item_data_icons ) != 'undefined' ) ? menu_item_data_icons : '';
	this.saved_data_icons_classes = ( typeof( menu_item_data_icons_classes ) != 'undefined' ) ? menu_item_data_icons_classes : '';
	this.i18n = ( typeof( wfim_cm_i18n ) != 'undefined' ) ? wfim_cm_i18n : '';
	this.menu_items = jQuery( '#menu-to-edit>li' );
	this.init();
	this.new_menu();
	this.delete_icon();
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
				var code_point = self.saved_data_icons[id] || '';
				var data_icon = code_point ? '&#' + code_point + ';' : '';
				var font_name = self.saved_data_icons_classes[id] || '';
				var class_name = font_name ? ' icon-' + font_name : '';	
				var field = '<p class="field-data-icon description description-thin">';
				field += '<label for="edit-menu-iteme-data-icon-' + id + '">';
				field += self.i18n['Icon'] + '<br />'
				field += '</label>';
				field += '<input type="button" class="wfim_icon_select" value="' + self.i18n['Select Icon'] + '" />';
				field += ' <a style="font-style:normal" class="wfim_delete_icon" href="">' + self.i18n['Delete'] + '</a>';
				field += '<span class="icon_preview' + class_name+ '">' + data_icon + '</span>';
				field += '<input type="hidden" id="edit-menu-iteme-data-icon-' + id + '" class="widefat code data_icon" name="menu-item-data-icon[' + id + ']" value="' + code_point + '" />';
				field += '<input type="hidden" id="edit-menu-iteme-data-icon-class-' + id + '" class="widefat code font_name" name="menu-item-data-icon-class[' + id + ']" value="' + font_name + '" />';
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
	},
	delete_icon : function() {
		jQuery( 'a.wfim_delete_icon' ).click( function() {
			jQuery( this ).closest( 'p.field-data-icon' ).children( 'span.icon_preview' ).removeClass().addClass( 'icon_preview' ).text( '' );
			return false;
		});
	}
}

jQuery( document ).ready( function() {
	new AddExtraMenuOption();

	/* Add noce field */
	if ( typeof( wfim_cm_nonce ) != 'undefined' ) {
		var nonce_field = '<input type="hidden" id="web-font-icon-manager-nonce" name="web-font-icon-manager-nonce" value="' + wfim_cm_nonce + '" />';
		jQuery( 'div#nav-menu-header' ).append( nonce_field );
	}
});
