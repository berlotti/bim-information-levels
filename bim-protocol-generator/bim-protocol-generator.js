BIMProtocolGenerator = function() {};
BIMProtocolGenerator = BIMProtocolGenerator.prototype = function() {};

BIMProtocolGenerator.typeAntwoord = false;
BIMProtocolGenerator.settings = false;

jQuery( document ).ready( function() {
	// If we are editing a vragenlijst we need to initialize some stuff
	var typeAntwoorden = jQuery( "#type-question" );
	if( typeAntwoorden.length > 0 ) {
		if( ddmaSettings ) {
			BIMProtocolGenerator.settings = ddmaSettings;
		}
		typeAntwoorden.change( BIMProtocolGenerator.typeAntwoordChanged );
		BIMProtocolGenerator.typeAntwoordChanged();
		for( var i = 0; i < BIMProtocolGenerator.settings.answers.length; i ++ ) {
			var number = i + 1;
			if( BIMProtocolGenerator.settings.answers[i].tekst ) {
				jQuery( "#antwoord-tekst-" + number ).val( BIMProtocolGenerator.settings.answers[i].tekst );
			}
			if( BIMProtocolGenerator.settings.answers[i].advies_tekst ) {
				jQuery( "#antwoord-advies-tekst-" + number ).val( BIMProtocolGenerator.settings.answers[i].advies_tekst );
			}
			if( BIMProtocolGenerator.settings.answers[i].gevolg ) {
				jQuery( "#antwoord-gevolg-" + number ).val( BIMProtocolGenerator.settings.answers[i].gevolg );
				if( BIMProtocolGenerator.settings.answers[i].gevolg == "slecht" ) {
					var html = "<label for=\"antwoord-advies-" + number + "\" class=\"antwoord-advies-label\">Advies</label>";
					html += "<textarea id=\"antwoord-advies-" + number + "\" name=\"antwoord_advies_" + number + "\" class=\"antwoord-advies\"></textarea><div class=\"clear\"></div>";
					jQuery( "#antwoord-" + number ).append( html );
					if( BIMProtocolGenerator.settings.answers[i].advies ) {
						jQuery( "#antwoord-advies-" + number ).val( BIMProtocolGenerator.settings.answers[i].advies );
					}
				}
			}
			if( BIMProtocolGenerator.settings.answers[i].vervolg ) {
				jQuery( "#antwoord-vervolg-" + number ).val( BIMProtocolGenerator.settings.answers[i].vervolg );
			}
			if( BIMProtocolGenerator.settings.answers[i].verplicht && BIMProtocolGenerator.settings.answers[i].verplicht == "verplicht" ) {
				jQuery( "#antwoord-verplicht-" + number ).attr( "checked", true );
			}
			if( i < BIMProtocolGenerator.settings.answers.length - 1 ) {
				BIMProtocolGenerator.voegAntwoordToe();
			}
		}
	}
} );

BIMProtocolGenerator.addRow = function( type, labels ) {
	var rows = jQuery( "." + type + "-row" ).length + 1;
	var html = "<tr class=\"" + type + "-row\">";
	if( type == "participant" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".participant-row" ).length + 2 ) + ") </span></td>" +
				"<td><input type=\"text\" name=\"name_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>" + 
			"<td><input type=\"email\" name=\"email_" + rows + "\" placeholder=\"" + labels[1] + "\" /></td>" +
			"<td></td>";
	} else if( type == "goal" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".goal-row" ).length + 1 ) + ") </span></td>" +
				"<td><input type=\"text\" name=\"goal_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>"; 
	} else if( type == "0point" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".0point-row" ).length + 1 ) + ") </span></td>" +
			"<td><input type=\"text\" name=\"0point_template_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>" + 
			"<td><input type=\"url\" name=\"0point_uri_" + rows + "\" placeholder=\"" + labels[1] + "\" /></td>";
	} else if( type == "modelingtemplate" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".modelingtemplate-row" ).length + 1 ) + ") </span></td>" +
			"<td><input type=\"text\" name=\"modeling_template_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>" + 
			"<td><input type=\"url\" name=\"modeling_uri_" + rows + "\" placeholder=\"" + labels[1] + "\" /></td>";		
	} else if( type == "information" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".information-row" ).length + 1 ) + ") </span></td>" +
			"<td><input type=\"text\" name=\"information_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>"; 
	} else if( type == "status" ) {
		html += "<td class=\"row-number\"><span class=\"row-number\">" + ( jQuery( ".status-row" ).length + 1 ) + ") </span></td>" +
			"<td><input type=\"text\" name=\"status_" + rows + "\" placeholder=\"" + labels[0] + "\" /></td>"; 
	} 
	html += "</tr>";
	jQuery( "." + type + "-row:last" ).after( html );
};

BIMProtocolGenerator.addTableRow = function( tableId ) {
	var count = jQuery( "#" + tableId + " tr" ).length;
	var html = jQuery( "#" + tableId + " tr:last" ).html();
	jQuery( "#" + tableId + " tr:last" ).after( "<tr class=\"" + ( ( count - 1 ) % 2 == 0 ? "even" : "odd" ) + "\" id=\"row-" + ( count - 1 ) + "\">" + html + "</tr>" );
};
