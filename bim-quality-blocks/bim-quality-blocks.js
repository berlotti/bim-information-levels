BIMQualityBlocks = function() {};
BIMQualityBlocks = BIMQualityBlocks.prototype = function() {};

BIMQualityBlocks.initialized = false;

jQuery( document ).ready( function() {
	BIMQualityBlocks.initializeEvents();
	var container = jQuery( "#bim-quality-block-layers" );
	var navigationContainer = container.find( ".navigation-container" );
	navigationContainer.find( ".previous" ).click( function( event ) {
		event.preventDefault();

		BIMQualityBlocks.activatePreviousLayer( container.find( ".layer.active" ) );
		BIMQualityBlocks.disableNextLayers( container.find( ".layer.active" ) );
	} );
	navigationContainer.find( ".next" ).click( function( event ) {
		event.preventDefault();
		if( jQuery( this ).hasClass( "submit" )  ) {
			var report = BIMQualityBlocks.createReport();
			var reportContent = jQuery( "#report-content" );
			reportContent.val( JSON.stringify( report ) );
			reportContent.parent().submit();
		} else {
			BIMQualityBlocks.enableNextLayer( container.find( ".layer.active" ) );
		}
	} );
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
	var deselects = block.find( ".deselect" ).html().split( "," );
	for( i in deselects ) {
		if( deselects.hasOwnProperty( i ) ) {
			jQuery( "#quality-block-" + deselects[i] ).removeClass( "selected" );
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
		blocks: []
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
	return report;
};

BIMQualityBlocks.buildingTypeChanged = function() {
	var selectBuilding = document.getElementById( "select-building" );
	if( selectBuilding ) {
		if( selectBuilding.value != "" ) {
			var startLayer = jQuery( "#bim-quality-block-layers" ).find( ".layer:first" );
			startLayer.addClass( "active" );
			startLayer.find( ".overlay" ).addClass( "hidden" );
		} else {
			var layers = jQuery( "#bim-quality-block-layers" ).find( ".layer" );
			layers.removeClass( "active" );
			layers.find( ".overlay" ).removeClass( "hidden" );
		}
	}
};
