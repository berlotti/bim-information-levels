TNOBIMQuickscan = function() {};
TNOBIMQuickscan = TNOBIMQuickscan.prototype = function() {};
TNOBIMQuickscan.firstRun = true;
TNOBIMQuickscan.searchTimeout = -1;
TNOBIMQuickscan.currentContainer;
TNOBIMQuickscan.lastResults;

TNOBIMQuickscan.initialize = function() {
	jQuery( ".user-selection-container .search-container input" ).live( "keydown", TNOBIMQuickscan.keyPressed );
};

TNOBIMQuickscan.keyPressed = function() {
	if( TNOBIMQuickscan.searchTimeout > -1 ) {
		clearTimeout( TNOBIMQuickscan.searchTimeout );
	}
	TNOBIMQuickscan.currentContainer = jQuery( this ).parents( ".user-selection-container" );
	TNOBIMQuickscan.searchTimeout = setTimeout( "TNOBIMQuickscan.search();", 250 );
};

TNOBIMQuickscan.search = function() {
	TNOBIMQuickscan.searchTimeout = -1;
	var query = jQuery( TNOBIMQuickscan.currentContainer ).find( ".search-container input" ).val();
	if( query.length > 0 ) {
		jQuery( TNOBIMQuickscan.currentContainer ).find( ".search-container .loading-image" ).css( "display", "inline" );
		TNOBIMQuickscan.clearResultList();
		jQuery.post( TNOBIMQuickscan.settings.baseUri + "/wp-content/plugins/tno-bim-quickscan/user-search.php", "q=" + query, function( data ) {
			jQuery( TNOBIMQuickscan.currentContainer ).find( ".search-container .loading-image" ).css( "display", "none" );
			TNOBIMQuickscan.lastResults = data;
			var html = "<div class=\"result-list\">";
			for( var i = 0; i < TNOBIMQuickscan.lastResults.length; i ++ ) {
				html += "<a href=\"javascript:void( null );\" class=\"result-location\" onclick=\"TNOBIMQuickscan.selectResult( " + i + " );\">" + TNOBIMQuickscan.lastResults[i].display_name + ( TNOBIMQuickscan.lastResults[i].city != "" ? ( " (" + TNOBIMQuickscan.lastResults[i].city + ")" ) : "" ) + "</a>";
			}
			html += "</div>";
			jQuery( TNOBIMQuickscan.currentContainer ).find( ".search-container" ).append( html );
		}, "json" );
	} else {
		TNOBIMQuickscan.clearResultList();
	}
};

TNOBIMQuickscan.clearResultList = function() {
	jQuery( ".user-selection-container .search-container .result-list" ).remove();
};

TNOBIMQuickscan.selectResult = function( index ) {
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".tno_bim_bedrijf" ).val( TNOBIMQuickscan.lastResults[index].ID );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .name" ).val( TNOBIMQuickscan.lastResults[index].display_name );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .email" ).val( TNOBIMQuickscan.lastResults[index].user_email );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .street" ).val( TNOBIMQuickscan.lastResults[index].street );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .postcode" ).val( TNOBIMQuickscan.lastResults[index].postcode );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .city" ).val( TNOBIMQuickscan.lastResults[index].city );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".address-information .other" ).val( TNOBIMQuickscan.lastResults[index].other );
	jQuery( TNOBIMQuickscan.currentContainer ).find( ".selected-user .value" ).html( TNOBIMQuickscan.lastResults[index].display_name + " (<a href=\"javascript:void( null );\" onclick=\"TNOBIMQuickscan.addNewAddress( this );\">" + TNOBIMQuickscan.settings.addNewAddressText + "</a>)" );
	TNOBIMQuickscan.clearResultList();
};

TNOBIMQuickscan.addNewAddress = function( link ) {
	var container = jQuery( link ).parents( ".user-selection-container" );
	jQuery( container ).find( ".tno_bim_bedrijf " ).val( "new" );
	jQuery( container ).find( ".address-information .name" ).val( "" );
	jQuery( container ).find( ".address-information .email" ).val( "" );
	jQuery( container ).find( ".address-information .street" ).val( "" );
	jQuery( container ).find( ".address-information .postcode" ).val( "" );
	jQuery( container ).find( ".address-information .city" ).val( "" );
	jQuery( container ).find( ".address-information .other" ).val( "" );
	jQuery( container ).find( ".selected-user .value" ).html( TNOBIMQuickscan.settings.noneAddNewCompanyText );
};

TNOBIMQuickscan.initialize();
