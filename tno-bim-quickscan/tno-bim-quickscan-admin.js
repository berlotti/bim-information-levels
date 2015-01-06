TNOBIMQuickscanAdmin = function() {};
TNOBIMQuickscanAdmin = TNOBIMQuickscanAdmin.prototype = function() {};

TNOBIMQuickscanAdmin.initializeGravityFormFields = function( event, field, form ) {
	jQuery( "#bim-points-per-answer" ).html( "" );
	for( var i = 0; i < field.choices.length; i ++ ) {
		jQuery( "#bim-points-per-answer" ).append( "<input onkeyup=\"TNOBIMQuickscanAdmin.saveBimPoints();\" type=\"text\" size=\"3\" class=\"bim-points-setting-input\" id=\"bim-points-" + i + "\" value=\"\" /> <label for=\"bim-points-" + i + "\" class=\"inline\">Weight of " + field.choices[i].text + "</label><br />" );
	}
	if( field.enableOtherChoice ) {
		jQuery( "#bim-points-per-answer" ).append( "<input onkeyup=\"TNOBIMQuickscanAdmin.saveBimPoints();\" type=\"text\" size=\"3\" class=\"bim-points-setting-input\" id=\"bim-points-" + i + "\" value=\"\" /> <label for=\"bim-points-" + i + "\" class=\"inline\">Weight of other option</label><br />" );
	}
	if( field.bim_points ) {
		var weight = field.bim_points.split( "," );
		for( var i = 0; i < weight.length; i ++ ) {
			jQuery( "#bim-points-" + i ).val( weight[i] );
		}
	} else {
		jQuery( ".bim-points-setting-input" ).val( "" );
	}
	jQuery( ".bim-category-setting-checkbox" ).attr( "checked", false );
	if( field.bim_categories ) {
		var categories = field.bim_categories.split( "," );
		var categoryWeights = field.bim_category_weights ? field.bim_category_weights.split( "," ) : new Array();
		for( var i =0; i < categories.length; i ++ ) {
			jQuery( "#bim-category-" + categories[i] ).attr( "checked", true );
		}
		var index = 0;
		jQuery( ".bim-category-setting-weight" ).each( function() {
			if( categoryWeights[index] ) {
				var values = categoryWeights[index].split( ":" );
				this.value = values[0];
			} else {
				this.value = 1;
			}
			index ++;
		} );
	}
	jQuery( "#bim-post-meta" ).val( field.bim_post_meta );
};

/*
TNOBIMQuickscanAdmin.markForCheck = function( element, id ) {
	jQuery.post( TNOBIMQuickscanAdminSettings.baseUri + "/wp-content/plugins/tno-bim-quickscan/mark-for-check.php", "id=" + id, function( data ) {
		jQuery( element.parentNode ).html( data );
	}, "html" );
};

TNOBIMQuickscanAdmin.statusChanged = function( element, id ) {
	jQuery.post( TNOBIMQuickscanAdminSettings.baseUri + "/wp-content/plugins/tno-bim-quickscan/set-status.php", "id=" + id + "&status=" + element.value );
};*/

TNOBIMQuickscanAdmin.saveBimCategories = function() {
	var categoryWeights = "";
	var categories = "";
	jQuery( ".bim-category-setting-checkbox:checked" ).each( function() {
		if( categories != "" ) {
			categories += ",";
		}
		categories += this.value;
	} );
	jQuery( ".bim-category-setting-weight" ).each( function() {
		if( categoryWeights != "" ) {
			categoryWeights += ",";
		}
		categoryWeights += this.value + ":" + this.id.replace( "bim-category-weight-", "" );
	} );
	SetFieldProperty( 'bim_categories', categories );
	SetFieldProperty( 'bim_category_weights', categoryWeights );
};

TNOBIMQuickscanAdmin.saveBimPoints = function() {
	var weight = "";
	jQuery( ".bim-points-setting-input" ).each( function() {
		if( weight != "" ) {
			weight += ",";
		}
		weight += this.value;
	} );
	SetFieldProperty( 'bim_points', weight );
};