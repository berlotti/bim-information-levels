BIMInformationLevels = function() {};
BIMInformationLevels = BIMInformationLevels.prototype = function() {};

BIMInformationLevels.initialized = false;

BIMInformationLevels.filters = function() {
	jQuery( ".filter-checkbox" ).click( BIMInformationLevels.updateFilters );
	if( jQuery( ".filter-checkbox" ).length > 0 ) {
		BIMInformationLevels.updateFilters();
	}
};

BIMInformationLevels.updateFilters = function() {
	var checked = new Array();
	var cookieValue = "";
	jQuery( ".filter-checkbox:checked" ).each( function() {
		checked.push( jQuery( this ).val() );
		if( cookieValue != "" ) {
			cookieValue += ",";
		}
		cookieValue += jQuery( this ).val();
	} );
	jQuery( ".item-row" ).each( function() {
		var hide = checked.length == 7 ? false : true;
		if( hide ) {
			for( var i = 0; i < checked.length; i ++ ) {
				if( jQuery( this ).find( ".information-level-" + checked[i] ).hasClass( "checked" ) ) {
					hide = false;
					break;
				}
			}
		}
		if( hide ) {
			jQuery( this ).addClass( "hidden" );
		} else {
			jQuery( this ).removeClass( "hidden" );
		}
	} );
	jQuery.cookie( "filter_levels", cookieValue, { expires: 365, path: "/" } );
	// If the report form is available we update it
	if( document.getElementById( "report-filters" ) ) {
		document.getElementById( "report-filters" ).value = cookieValue;
		if( BIMInformationLevels.initialized ) {
			BIMInformationLevels.updateSelection();
		}
	}
};

BIMInformationLevels.addReportOptionRow = function( value ) {
	var number = jQuery( "#report-control-panel .control-option" ).length;
	var html = "<div class=\"control-option\" id=\"control-container-" + number + "\">" +
			"<a href=\"javascript:void( null );\" class=\"remove-option-button\" onclick=\"BIMInformationLevels.removeOptionRow( this );\">" + BIMInformationLevels.settings.text.trash + "</a>" + 
			"&nbsp;<label class=\"topic-label\" for=\"topic-select-" + number + "\">" + BIMInformationLevels.settings.text.topicLabel + "</label> " +
			"&nbsp;<select class=\"topic-select\" id=\"topic-select-" + number + "\" onchange=\"BIMInformationLevels.updateTopicSelection( this );\">" +
			"<option value=\"\">" + BIMInformationLevels.settings.text.all + "</option>";
	for( var i = 0; i < BIMInformationLevels.settings.mainTopics.length; i ++ ) {
		html += "<option value=\"" + BIMInformationLevels.settings.mainTopics[i].term_id + "\"" + ( ( value && value == BIMInformationLevels.settings.mainTopics[i].term_id ) ? " selected" : "" ) + ">" + BIMInformationLevels.settings.mainTopics[i].name + "</option>";
	}
	html += "</select>";
	html += "</div>";
	jQuery( "#report-control-panel .content" ).append( html );
};

BIMInformationLevels.updateTopicSelection = function( select, value ) {
	var container = jQuery( select.parentNode );
	var number = container.attr( "id" ).replace( "control-container-", "" );
	container.find( ".sub-topic-select, .sub-topic-label" ).remove();
	
	if( select.value != "" ) {
		var html = "&nbsp;<label class=\"sub-topic-label\" for=\"sub-topic-" + number + "\">" + BIMInformationLevels.settings.text.subTopicLabel + "</label>" +
				"&nbsp;<select class=\"sub-topic-select\" id=\"sub-topic-" + number + "\" onchange=\"BIMInformationLevels.updateSelection();\">" +
				"<option value=\"\">" + BIMInformationLevels.settings.text.all + "</option>";
		for( var i = 0; i < BIMInformationLevels.settings.mainTopics.length; i ++ ) {
			if( BIMInformationLevels.settings.mainTopics[i].term_id == select.value ) {
				for( var p = 0; p < BIMInformationLevels.settings.mainTopics[i].children.length; p ++ ) {
					html += "<option value=\"" + BIMInformationLevels.settings.mainTopics[i].children[p].term_id + "\"" + ( ( value && value == BIMInformationLevels.settings.mainTopics[i].children[p].term_id ) ? " selected" : "" ) + ">" + BIMInformationLevels.settings.mainTopics[i].children[p].name + "</option>";
				}
				break;
			}
		}
		html += "</select>";
		container.append( html );
	}
	if( typeof value == "undefined" ) {
		BIMInformationLevels.updateSelection();
	}
};

BIMInformationLevels.removeOptionRow = function( link ) {
	jQuery( link.parentNode ).remove();
	BIMInformationLevels.updateSelection();
};

BIMInformationLevels.updateSelection = function() {
	var settings = "[";
	jQuery( ".control-option" ).each( function() {
		if( settings != "[" ) {
			settings += ",";
		}
		settings += "{\"topicId\":\"" + jQuery( this ).find( ".topic-select" ).val() + "\"";
		var subTopic = jQuery( this ).find( ".sub-topic-select" );
		if( subTopic.length > 0 ) {
			settings += ",\"subTopicId\":\"" + subTopic.val() + "\"";
		}
		settings += "}";
	} );
	settings += "]";
	// Store the current settings in a cookie
	jQuery.cookie( "filter_topics", settings, { expires: 365, path: "/" } );
	
	if( BIMInformationLevels.settings.type == "report" ) {
		// In report mode we do an ajax callback to show the report preview
		jQuery( "#report-settings" ).val( settings );
		jQuery.post( BIMInformationLevels.settings.ajaxUrl, "type=getPreview&settings=" + settings + "&filters=" + jQuery.cookie( "filter_levels" ), function( data ) {
			if( data && data.result ) {
				jQuery( "#result-preview" ).html( data.result );
			}
		}, "json" );
	} else {
		// If we are not in report mode we do the selection client side
		jQuery( ".bim-object-category" ).addClass( "hidden" );
		jQuery( ".item-row" )
			.addClass( "sub-topic-hidden" );
		jQuery( ".row-sub-topic-none" )
			.removeClass( "sub-topic-hidden" );
		var options = jQuery.parseJSON( settings );
		var restrictions = false;
		var subRestrictions = {};
		var showAll = options.length == 0 ? true : false;
		for( var i = 0; i < options.length; i ++ ) {
			if( options[i].topicId != "" && options[i].subTopicId == "" ) {
				jQuery( "#topic-" + options[i].topicId ).removeClass( "hidden" );
				restrictions = true;
				if( typeof subRestrictions[options[i].topicId] == "undefined" ) {
					subRestrictions[options[i].topicId] = true;
				}
			} else if( options[i].topicId != "" && options[i].subTopicId != "" ) {
				jQuery( "#topic-" + options[i].topicId ).removeClass( "hidden" );
				jQuery( "#topic-" + options[i].subTopicId ).removeClass( "hidden" );
				// filter on specific rows too...
				jQuery( ".row-sub-topic-" + options[i].subTopicId )
					.removeClass( "sub-topic-hidden" );
				restrictions = true;
				subRestrictions[options[i].topicId] = false;
			} else if( options[i].topicId == "" ) {
				showAll = true;
			}
		}
		if( !restrictions && showAll ) {
			jQuery( ".bim-object-category" ).removeClass( "hidden" );
			jQuery( ".sub-topic-hidden" ).removeClass( "sub-topic-hidden" );
		}
		for( var key in subRestrictions ) {
			if( subRestrictions.hasOwnProperty( key ) && subRestrictions[key] ) {
				jQuery( "#topic-" + key + " .item-row" ).removeClass( "sub-topic-hidden" );
				jQuery( ".parent-topic-" + key ).removeClass( "hidden" );
			}
		}
	}
};

jQuery( document ).ready( function() {
	if( typeof informationLevelsControlSettings !== "undefined" ) {
		// There are settings so we are on the report page
		BIMInformationLevels.settings = informationLevelsControlSettings;
		// Restore the cookie settings
		var settings = false;
		try {
			settings = jQuery.parseJSON( jQuery.cookie( "filter_topics" ) );
		} catch( exception ) { }
		
		if( settings ) {
			for( var i = 0; i < settings.length; i ++ ) {
				BIMInformationLevels.addReportOptionRow( settings[i].topicId );
				BIMInformationLevels.updateTopicSelection( jQuery( ".control-option .topic-select:last" )[0], settings[i].subTopicId ? settings[i].subTopicId : false );
			}
		} else {
			BIMInformationLevels.addReportOptionRow();
		}
		BIMInformationLevels.updateSelection();
	}
	BIMInformationLevels.filters();
	BIMInformationLevels.initialized = true;
	
	jQuery( ".toggle-link" ).each( function() {
		var containerId = this.id.replace( "toggle-", "" );
		if( jQuery.cookie( "toggle_status_" + containerId ) == "closed" ) {
			this.innerHTML = "+";
			jQuery( "#" + containerId ).addClass( "hidden" );
		} else {
			this.innerHTML = "-";
			jQuery( "#" + containerId ).removeClass( "hidden" );
		}
	} );
} );

BIMInformationLevels.toggleBox = function( link ) {
	var containerId = link.id.replace( "toggle-", "" );
	if( link.innerHTML == "+" ) {
		link.innerHTML = "-";
		jQuery( "#" + containerId ).removeClass( "hidden" );
		jQuery.cookie( "toggle_status_" + containerId, "open", { expires: 365, path: "/" } );
	} else {
		link.innerHTML = "+";
		jQuery( "#" + containerId ).addClass( "hidden" );
		jQuery.cookie( "toggle_status_" + containerId, "closed", { expires: 365, path: "/" } );
	}
};
