BIMProtocolGenerator = function() {};
BIMProtocolGenerator = BIMProtocolGenerator.prototype = function() {};

BIMProtocolGenerator.typeAnswer = false;
BIMProtocolGenerator.settings = false;

jQuery( document ).ready( function() {
	// If we are editing a question we need to initialize some stuff
	var typeQuestion = jQuery( "#type-question" );
	if( typeQuestion.length > 0 ) {
		if( bimProtocolGeneratorSettings ) {
			BIMProtocolGenerator.settings = bimProtocolGeneratorSettings;
		}
		typeQuestion.change( BIMProtocolGenerator.typeQuestionChanged );
		BIMProtocolGenerator.typeQuestionChanged();
		for( var i = 0; i < BIMProtocolGenerator.settings.answers.length; i ++ ) {
			var number = i + 1;
			if( BIMProtocolGenerator.settings.answers[i].text ) {
				jQuery( "#answer-text-" + number ).val( BIMProtocolGenerator.settings.answers[i].text );
			}
			if( i < BIMProtocolGenerator.settings.answers.length - 1 ) {
				BIMProtocolGenerator.addAnswer();
			}
		}
	}
} );

BIMProtocolGenerator.typeQuestionChanged = function() {
	var value = jQuery( "#type-question" ).val();
	
	var container = jQuery( "#answer-container" );
	if( container.html() == "" || confirm( BIMProtocolGenerator.settings.confirmationText ) ) {
		container.html( "" );
		jQuery( "#question-settings-container" ).html( "" );
		var html = "";
		if( value == "radio" || value == "checkbox" ) {
			if( value == "checkbox" ) {
				html += "<label for=\"report-text\">Report text</label> ";
				html += "<input type=\"text\" id=\"report-text\" name=\"_report_text\" class=\"report-text\" value=\"\" />";
				html += "<div class=\"clear\"></div>";
			} else {
				html += "<label for=\"report-text\">Report text, when agreed</label> ";
				html += "<input type=\"text\" id=\"report-text\" name=\"_report_text\" class=\"report-text\" value=\"\" />";
				html += "<div class=\"clear\"></div>";
				html += "<label for=\"report-text-not-agreed\">Report text, when not agreed</label> ";
				html += "<input type=\"text\" id=\"report-text-not-agreed\" name=\"_report_text_not\" class=\"report-text\" value=\"\" />";
				html += "<div class=\"clear\"></div>";
				html += "<label for=\"check-question-type\">Check documents question type</label> ";
				html += "<select id=\"check-question-type\" name=\"_check_documents_type\" class=\"question-select\">";
				html += "<option value=\"\">None</option>";
				html += "<option value=\"input_check\">Input check</option>";
				html += "<option value=\"output_check\">Output check</option>";
				html += "</select>";
				html += "<div class=\"clear\"></div>";
			}
			html += "<input type=\"checkbox\" id=\"other-answer\" name=\"_other\" class=\"other-answer\" value=\"other\" /> ";
			html += "<label for=\"other-answer\">Add other answer</label>";
			html += "<div class=\"clear\"></div>";
			html += "<input type=\"checkbox\" id=\"format-types-read\" name=\"_format_types_read\" class=\"format-types\" value=\"format_types_read\" /> ";
			html += "<label for=\"format-types-read\">Contains readable file format types</label>";
			html += "<div class=\"clear\"></div>";
			html += "<input type=\"checkbox\" id=\"format-types-write\" name=\"_format_types_write\" class=\"format-types\" value=\"format_types_write\" /> ";
			html += "<label for=\"format-types-write\">Contains writable file format types</label>";
			html += "<div class=\"clear\"></div>";
		}
		html += "<label for=\"report-chapter\">Report chapter</label> ";
		html += "<select id=\"report-chapter\" name=\"_report_chapter\" class=\"\">";
		html += "<option value=\"\">" + BIMProtocolGenerator.settings.noneText + "</option>";
		for( var i = 0; i < BIMProtocolGenerator.settings.reportChapters.length; i ++ ) {
			html += "<option value=\"" + BIMProtocolGenerator.settings.reportChapters[i] + "\">" + BIMProtocolGenerator.settings.reportChapters[i] + "</option>";
		}
		html += "</select>";
		html += "<div class=\"clear\"></div>";
		/*html += "<input type=\"checkbox\" id=\"visible-in-report\" name=\"_visible_in_report\" class=\"\" value=\"true\" /> ";
		html += "<label for=\"visible-in-report\">Visible in report</label>";
		html += "<div class=\"clear\"></div>";*/
		
		jQuery( "#question-settings-container" ).html( html );
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.formatTypesRead && BIMProtocolGenerator.settings.formatTypesRead != "" ) {
			jQuery( "#format-types-read" ).attr( "checked", "checked" );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.formatTypesWrite && BIMProtocolGenerator.settings.formatTypesWrite != "" ) {
			jQuery( "#format-types-write" ).attr( "checked", "checked" );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.other && BIMProtocolGenerator.settings.other != "" ) {
			jQuery( "#other-answer" ).attr( "checked", "checked" );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.reportText ) {
			jQuery( "#report-text" ).attr( "value", BIMProtocolGenerator.settings.reportText );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.reportTextNot ) {
			jQuery( "#report-text-not-agreed" ).attr( "value", BIMProtocolGenerator.settings.reportTextNot );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.checkDocumentsType ) {
			jQuery( "#check-question-type" ).attr( "value", BIMProtocolGenerator.settings.checkDocumentsType );
		}
		if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.reportChapter && BIMProtocolGenerator.settings.reportChapter != "" ) {
			jQuery( "#report-chapter" ).attr( "value", BIMProtocolGenerator.settings.reportChapter );
		}
		/*if( BIMProtocolGenerator.settings && BIMProtocolGenerator.settings.visibleInReport && BIMProtocolGenerator.settings.visibleInReport != "" ) {
			jQuery( "#visible-in-report" ).attr( "checked", "checked" );
		}*/
		
		if( value != "end" ) {
			if( value != "" && value != "page" && value != "participant" && value != "goals" && value != "0pointtemplate" && value != "modelingtemplate" && value != "required_information" && value != "leading_partner" ) {
				BIMProtocolGenerator.addAnswer();
			}
			BIMProtocolGenerator.typeAnswer = value;
		}
	} else {
		if( BIMProtocolGenerator.typeAnswer ) {
			jQuery( "#type-answer" ).val( BIMProtocolGenerator.typeAnswer );
		}
		return false;
	}
};

BIMProtocolGenerator.addAnswer = function() {
	var type = jQuery( "#type-question" ).val();
	
	var amount = jQuery( "#answer-container .answer" ).length + 1;
	
	var html = "<div class=\"answer\" id=\"answer-" + amount + "\">";
	
	html += "<input id=\"answer-text-" + amount + "\" name=\"answer_text_" + amount + "\" /><div class=\"clear\"></div>";
	html += "<div class=\"control-links\">";
	html += "<a href=\"javascript:void( null );\" onclick=\"BIMProtocolGenerator.removeAnswer( this );\" class=\"remove-answer\">-</a>";
	html += "<a href=\"javascript:void( null );\" onclick=\"BIMProtocolGenerator.addAnswer();\" class=\"add-answer\">+</a>";
	html += "</div>";
	jQuery( "#answer-container" ).append( html );
	
	BIMProtocolGenerator.updateAddRemoveLinks();
};

BIMProtocolGenerator.removeAnswer = function( link ) {
	jQuery( link ).parents( ".answer" ).remove();
	BIMProtocolGenerator.updateAddRemoveLinks();
};

BIMProtocolGenerator.updateAddRemoveLinks = function() {
	var amount = jQuery( "#answer-container .answer" ).length;
	if( amount == 1 ) {
		jQuery( "#answer-container .answer .remove-answer" ).css( "display", "none" );
	} else {
		jQuery( "#answer-container .answer .remove-answer" ).css( "display", "inline" );
	}
	
	var number = 1;
	jQuery( "#answer-container .answer" ).each( function() {
		var oldNumber = this.id.replace( "answer-", "" );
		this.id = "answer-" + number;
		jQuery( this ).find( "label[for='answer-text-" + oldNumber + "']" ).attr( "for", "answer-text-" + number );
		jQuery( this ).find( "#answer-text-" + oldNumber )
			.attr( "id", "answer-text-" + number )
			.attr( "name", "answer_text_" + number );
		jQuery( this ).find( "label[for='answer-vervolg-" + oldNumber + "']" ).attr( "for", "answer-vervolg-" + number );
		jQuery( this ).find( "#answer-vervolg-" + oldNumber )
			.attr( "id", "answer-vervolg-" + number )
			.attr( "name", "answer_vervolg_" + number );
		jQuery( this ).find( "label[for='answer-advies-" + oldNumber + "']" ).attr( "for", "answer-advies-" + number );
		jQuery( this ).find( "#answer-advies-" + oldNumber )
			.attr( "id", "answer-advies-" + number )
			.attr( "name", "answer_advies_" + number );
		number ++;
	} );
};

