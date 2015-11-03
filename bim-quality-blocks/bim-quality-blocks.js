BIMQualityBlocks = function() {};
BIMQualityBlocks = BIMQualityBlocks.prototype = function() {};

BIMQualityBlocks.initialized = false;

jQuery( document ).ready( function() {
	BIMQualityBlocks.initializeEvents();
	var container = jQuery( "#bim-quality-block-layers" );
	var navigationContainer = container.find( ".navigation-container" );
	var titleInput = jQuery( "#report-title" );
	var privateBlocks = jQuery( "#private-blocks" );
	navigationContainer.find( ".previous" ).click( function( event ) {
		event.preventDefault();
		BIMQualityBlocks.activatePreviousLayer( container.find( ".layer.active" ) );
		BIMQualityBlocks.disableNextLayers( container.find( ".layer.active" ) );
	} );
	navigationContainer.find( ".next" ).click( function( event ) {
		event.preventDefault();
		if( jQuery( this ).hasClass( "end-page" )  ) {
			if( titleInput.val() == "" ) {
				titleInput.addClass( "invalid" );
				jQuery( "html, body" ).animate( { scrollTop: 0 }, "fast" );
			} else {
				if( privateBlocks.length == 0 ) {
					var report = BIMQualityBlocks.createReport();
					report.title = titleInput.val();
					var reportContent = jQuery( "#report-content" );
					reportContent.val( JSON.stringify( report ) );
					reportContent.parent().submit();
				} else {
					jQuery( "#bim-quality-block-layers" ).addClass( "hidden" );
					privateBlocks.removeClass( "hidden" );
				}
			}
		} else {
			BIMQualityBlocks.enableNextLayer( container.find( ".layer.active" ) );
		}
	} );
	titleInput.keyup( function() {
		if( this.value != "" ) {
			titleInput.removeClass( "invalid" );
		} else {
			titleInput.addClass( "invalid" );
		}
	} );
	jQuery( "#save-private-block" ).click( function( event ) {
		event.preventDefault();
		var privateBlockTitle = jQuery( "#private-block-title" );

		if( privateBlockTitle.val() != "" ) {
			var privateBlockText = jQuery( "#private-block-text" );
			var privateBlockXml = jQuery( "#private-block-xml" );
			var privateBlockId = jQuery( "#private-block-id" );
			var data = {
				title: privateBlockTitle.val(),
				text: privateBlockText.val(),
				xml: privateBlockXml.val()
			};

			// Check if editing
			if( privateBlockId.val() != "" ) {
				data.id = privateBlockId.val();
			}

			jQuery.post( jQuery( "#bim-quality-blocks-ajax" ).attr( "href" ), data, function( response ) {
				privateBlockTitle.val( "" );
				privateBlockText.val( "" );
				privateBlockXml.val( "" );
				if( privateBlockId.val() != "" ) {
					jQuery( "#private-block-" + privateBlockId.val() ).remove();
					privateBlockId.val( "" );
					var editor = jQuery( "#private-block-editor" );
					editor.find( ".edit-title" ).addClass( "hidden" );
					editor.find( ".new-title" ).removeClass( "hidden" );
					editor.find( "#cancel-edit-private-block" ).addClass( "hidden" );
				}
				jQuery( "#private-block-list" ).find( "> .clear" ).before( response );
				BIMQualityBlocks.initializeEvents();
			} );
		}
	} );
	jQuery( "#cancel-edit-private-block" ).click( function( event ) {
		event.preventDefault();
		var editor = jQuery( "#private-block-editor" );
		editor.find( "#private-block-id" ).val( "" );
		editor.find( ".edit-title" ).addClass( "hidden" );
		editor.find( ".new-title" ).removeClass( "hidden" );
		editor.find( "#cancel-edit-private-block" ).addClass( "hidden" );
		editor.find( "#private-block-title" ).val( "" );
		editor.find( "#private-block-text" ).val( "" );
		editor.find( "#private-block-xml" ).val( "" );
	} );
	if( privateBlocks.length > 0 ) {
		privateBlocks.find( ".submit" ).click( function( event ) {
			event.preventDefault();
			var report = BIMQualityBlocks.createReport();
			report.title = titleInput.val();
			var reportContent = jQuery( "#report-content" );
			reportContent.val( JSON.stringify( report ) );
			reportContent.parent().submit();
		} );
	}
	BIMQualityBlocks.buildingTypeChanged();
} );

BIMQualityBlocks.initializeEvents = function() {
	jQuery( "#bim-quality-block-layers" ).find( ".quality-block" ).off( "click" ).click( BIMQualityBlocks.blockClicked )
		.off( "mouseover" ).mouseover( function( event ) {
			jQuery( this ).addClass( "mouseover" );
			BIMQualityBlocks.showTooltip( event, this );
		} ).off( "mouseout" ).mouseout( function() {
			jQuery( this ).removeClass( "mouseover" );
			BIMQualityBlocks.hideTooltip();
		} );

	var privateBlockList = jQuery( "#private-block-list" );

	privateBlockList.find( ".private-block" ).off( "click" ).click( function( event ) {
		if( event.currentTarget == event.target ) {
			var block = jQuery( this );
			if( block.hasClass( "selected" ) ) {
				block.removeClass( "selected" );
			} else {
				block.addClass( "selected" );
			}
		}
	} ).off( "mouseover" ).mouseover( function( event ) {
			jQuery( this ).addClass( "mouseover" );
			//BIMQualityBlocks.showTooltip( event, this );
		} ).off( "mouseout" ).mouseout( function() {
			jQuery( this ).removeClass( "mouseover" );
			//BIMQualityBlocks.hideTooltip();
		} );

	privateBlockList.find( ".edit-private-block" ).off( "click" ).click( function( event ) {
		event.preventDefault();
		var block = jQuery( this ).parent();
		var editor = jQuery( "#private-block-editor" );
		editor.find( "#private-block-id" ).val( block.attr( "id" ).replace( "private-block-", "" ) );
		editor.find( ".edit-title" ).removeClass( "hidden" );
		editor.find( ".new-title" ).addClass( "hidden" );
		editor.find( "#cancel-edit-private-block" ).removeClass( "hidden" );
		editor.find( "#private-block-title" ).val( block.find( ".title" ).html() );
		editor.find( "#private-block-text" ).val( block.find( ".text" ).html() );
		editor.find( "#private-block-xml" ).val( block.find( ".xml" ).html() );
	} );
};

BIMQualityBlocks.blockClicked = function() {
	var block = jQuery( this );
	if( !block.hasClass( "disabled" ) ) {
		if( block.hasClass( "selected" ) ) {
			if( block.find( ".behaviour" ).html() == "exclude_entire_layer" ) {
				block.parent().find( ".quality-block" ).removeClass( "selected" );
				BIMQualityBlocks.disableNextLayers( block.parent() );
			}
			block.removeClass( "selected" );
		} else {
			if( block.find( ".behaviour" ).html() == "exclude_entire_layer" ) {
				block.parent().find( ".quality-block" ).removeClass( "selected" );
				BIMQualityBlocks.enableNextLayer( block.parent() );
			}
			block.addClass( "selected" );
		}
		// Change selection and deal with disabled blocks
		BIMQualityBlocks.disableBlocks( block );
	}
};

BIMQualityBlocks.enableNextLayer = function( layer ) {
	var next = false;
	jQuery( "#bim-quality-block-layers" ).find( ".layer" ).each( function() {
		if( next ) {
			jQuery( this ).addClass( "active" ).find( ".overlay" ).addClass( "hidden" );
			return false; // Break off here
		} else {
			if( layer.attr( "id" ) == this.id ) {
				jQuery( this ).removeClass( "active" );
				next = true;
			}
		}
	} );
};

BIMQualityBlocks.activatePreviousLayer = function( layer ) {
	var previous = false;
	jQuery( "#bim-quality-block-layers" ).find( ".layer" ).each( function() {
		if( layer.attr( "id" ) == this.id ) {
			jQuery( this ).removeClass( "active" );
			return false;
		} else {
			previous = this;
		}
	} );
	if( previous ) {
		jQuery( previous ).addClass( "active" );
	}
};

BIMQualityBlocks.disableNextLayers = function( layer ) {
	var next = false;
	jQuery( "#bim-quality-block-layers" ).find( ".layer" ).each( function() {
		if( next ) {
			jQuery( this ).removeClass( "active" ).find( ".overlay" ).removeClass( "hidden" );
		} else {
			if( layer.attr( "id" ) == this.id ) {
				next = true;
				jQuery( this ).addClass( "active" );
			}
		}
	} );
};

BIMQualityBlocks.disableBlocks = function( block ) {
	var deselects = block.find( ".deselect" ).html().split( "," );
	for( i in deselects ) {
		if( deselects.hasOwnProperty( i ) ) {
			jQuery( "#quality-block-" + deselects[i] ).removeClass( "selected" );
		}
	}
	var layerContainer = jQuery( "#bim-quality-block-layers" );
	layerContainer.find( ".quality-block" ).removeClass( "disabled" );
	layerContainer.find( ".reason-list" ).html( "" );
	layerContainer.find( ".layer" ).each( function() {
		jQuery( this ).find( ".quality-block.selected" ).each( function() {
			var title = jQuery( this ).find( "> h3" ).html();
			var excludes = jQuery( this ).find( ".exclude" ).html().split( "," );
			for( var i in excludes ) {
				if( excludes.hasOwnProperty( i ) ) {
					var excludeBlock = jQuery( "#quality-block-" + excludes[i] );
					excludeBlock.addClass( "disabled" );
					var text = "&quot;" + title + "&quot; " + excludeBlock.find( ".disabled-tooltip .start-text" ).html();
					var reason = excludeBlock.find( ".disabled-tooltip .reason-list" );
					if( reason.html() != "" ) {
						text = ", " + text;
					}
					reason.html( reason.html() + text );
				}
			}
		} );
		jQuery( this ).find( ".quality-block.disabled" ).each( function() {
			jQuery( this ).parent().find( "> .clear" ).before( jQuery( this ).remove() );
		} );
	} );
	var selectBuilding = jQuery( "#select-building" );
	var buildingType = selectBuilding.val();
	if( buildingType != "" ) {
		var excludes = jQuery( "#quality-block-" + buildingType ).find( ".exclude" ).html().split( "," );
		for( var i in excludes ) {
			if( excludes.hasOwnProperty( i ) ) {
				var excludeBlock = jQuery( "#quality-block-" + excludes[i] );
				excludeBlock.addClass( "disabled" );
				var title = selectBuilding.find( "option:selected" ).text();
				var text = "&quot;" + title + "&quot; " + excludeBlock.find( ".disabled-tooltip .start-text" ).html();
				var reason = excludeBlock.find( ".disabled-tooltip .reason-list" );
				if( reason.html() != "" ) {
					text = ", " + text;
				}
				reason.html( reason.html() + text );
			}
		}
	}
	BIMQualityBlocks.initializeEvents();
};

BIMQualityBlocks.hideTooltip = function() {
	jQuery( "#quality-block-tooltip" ).addClass( "hidden" );
};


BIMQualityBlocks.showTooltip = function( event, block ) {
	var tooltip = jQuery( "#quality-block-tooltip" );
	block = jQuery( block );
	var content = "";
	if( block.hasClass( "disabled" ) ) {
		content = block.find( ".disabled-tooltip" ).html().trim();
	} else {
		content = block.find( ".tooltip" ).html().trim();
	}
	if( content != "" ) {
		var tooltipContainer = jQuery( "#bim-quality-block-layers" ).offset();
		tooltip.css( "left", ( event.pageX - tooltipContainer.left + 30 ) + "px" )
			.css( "top", ( event.pageY - tooltipContainer.top ) + "px" )
			.removeClass( "hidden" )
			.find( ".content" ).html( content );
	}
};

BIMQualityBlocks.createReport = function() {
	var report = {
		buildingType: jQuery( "#select-building" ).val(),
		blocks: [],
		privateBlocks: []
	};

	jQuery( "#bim-quality-block-layers" ).find( ".layer" ).each( function() {
		var layer = {
			type: this.id.replace( "layer_", "" ),
			blocks: []
		};
		jQuery( this ).find( ".quality-block.selected" ).each( function() {
			if( !jQuery( this ).hasClass( "disabled" ) ) {
				layer.blocks.push( this.id.replace( "quality-block-", "" ) );
			}
		} );
		report.blocks.push( layer );
	} );

	jQuery( "#private-block-list" ).find( ".private-block.selected" ).each( function() {
		report.privateBlocks.push( jQuery( this ).attr( "id" ).replace( "private-block-", "" ) );
	} );
	return report;
};

BIMQualityBlocks.buildingTypeChanged = function() {
	var selectBuilding = document.getElementById( "select-building" );
	if( selectBuilding ) {
		if( selectBuilding.value != "" ) {
			var startLayer = jQuery( "#bim-quality-block-layers" ).find( ".layer:first" );
			startLayer.addClass( "active" );
			startLayer.find( ".overlay" ).addClass( "hidden" );
			var block = jQuery( "#quality-block-" + selectBuilding.value );
			if( block.length > 0 ) {
				BIMQualityBlocks.disableBlocks( block );
			}
		} else {
			var layers = jQuery( "#bim-quality-block-layers" ).find( ".layer" );
			layers.removeClass( "active" );
			layers.find( ".overlay" ).removeClass( "hidden" );
		}
	}
};
