var wfim_font_selector = function() {
	this.icon_set = jQuery( '<div id="wfim_icon_set"></div>' ).hide();
	this.init();

	// Event actions
	this.open_icon_selector();
	this.insert_code_point();
	this.remove_after_insert();
	this.thick_box_height_fix();
	this.delete_icon();
}
wfim_font_selector.prototype = {
	init : function() {
		this.set_code_points();
		jQuery( 'body' ).append( this.icon_set );
	},
	set_code_points : function() {
		if ( typeof wfim_fonts == 'undefined') {
			this.icon_set.append( '<p>' + wfim_cm_i18n['Please upload font file.'] + '</p>' );
			return;
		}

		var fonts = wfim_fonts;
		var code_points;
		var output = '<ul id="wfim_icon_list">';
		for ( var font in fonts ) {
			code_points = fonts[font];
			for ( var i = 0; i < code_points.length; i++ ) {
				output += '<li><a href="" class="icon-' + font + '" title="' + code_points[i] + '">&#' + code_points[i] + '</a></li>';
			}
		}
		output += '</ul>';
		this.icon_set.append( output );
	},
	open_icon_selector : function() {
		var self = this;
		jQuery( 'input.wfim_icon_select' ).live( 'click', function() {
			self.current_item = jQuery( this );
			tb_show( wfim_cm_i18n['Icon'], '#TB_inline?inlineId=wfim_icon_set&width=640&height=480' );
			self.fix_thickbox_height();
		});
	},
	insert_code_point : function() {
		var self = this;
		jQuery( '#wfim_icon_list>li>a' ).live( 'click', function() {
			var code_point = jQuery( this ).attr( 'title' );
			var font_name = jQuery( this ).attr( 'class' );
			font_name = font_name.replace( /^icon-/, '' );
			if ( self.current_item.size() > 0 ) {
				var parent_elm = self.current_item.closest( '.field-data-icon' );
				parent_elm.children( 'span.icon_preview' ).text( '' ).append( '&#' + code_point + ';' ).addClass( 'icon-' + font_name );
				parent_elm.children( 'input.data_icon' ).val( code_point );
				parent_elm.children( 'input.font_name' ).val( font_name );
			}
			tb_remove();
			return false;
		});
	},
	remove_after_insert : function() {
		jQuery( document ).ajaxComplete( function() {
			jQuery( '.field-data-icon span.icon_preview' ).text( '' );
		});
	},
	thick_box_height_fix : function() {
		var self = this;
		jQuery( window ).resize(function() {
			self.fix_thickbox_height();
		});
	},
	fix_thickbox_height : function() {
		var tb_window_height = jQuery( '#TB_window' ).height();
		var tb_title_height = jQuery( '#TB_title' ).height();
		jQuery( '#TB_ajaxContent' ).height( tb_window_height - tb_title_height - 18 );
	},
	delete_icon : function() {
		jQuery( 'a.wfim_delete_icon' ).click( function() {
			jQuery( this ).closest( 'div.field-data-icon' ).children( 'span.icon_preview' ).removeClass().addClass( 'icon_preview' ).text( '' );
			return false;
		});
	}
}

jQuery( document ).ready( function() {
	new wfim_font_selector();
});
