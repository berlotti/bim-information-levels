BimQuickscan = function() {};
BimQuickscan = BimQuickscan.prototype = function() {};

BimQuickscan.barOptions = {
		scaleShowGridLines: false,
		scaleOverride: true,
		scaleSteps: 5,
		scaleStepWidth: 1,
		scaleStartValue: 0
	};
BimQuickscan.radarOptions = {
		scaleOverlay: false,
		scaleOverride: true,
		scaleSteps: 10,
		scaleStepWidth: 10,
		scaleStartValue: 0
	};
BimQuickscan.chartTimeout = -1;

jQuery( document ).ready( function() {
	if( document.getElementById( "radar-plot" ) ) {
		var contextRadarPlot = document.getElementById( "radar-plot" ).getContext( "2d" );
		new Chart( contextRadarPlot ).Radar( bimRadarData, BimQuickscan.radarOptions );
	}
	
	if( document.getElementById( "bar-chart" ) ) {
		var contextBarChart = document.getElementById( "bar-chart" ).getContext( "2d" );
		new Chart( contextBarChart ).Bar( bimBarData, BimQuickscan.barOptions );
	}
	
	if( document.getElementById( "slider" ) ) {
		jQuery( "#slider" ).slider( {
			min: customizableChartSettings.min,
			max: customizableChartSettings.max,
			range: true,
			values: [ customizableChartSettings.min, customizableChartSettings.max ],
			slide: BimQuickscan.sliderUpdate,
		} );
		
		BimQuickscan.radarContext = document.getElementById( "interactive-radar-plot" ).getContext( "2d" );
		BimQuickscan.barContext = document.getElementById( "interactive-bar-chart" ).getContext( "2d" );
		
		BimQuickscan.sliderUpdate( "initialize", { values: [ customizableChartSettings.min, customizableChartSettings.max ] } );
	}
	
	jQuery( ".core-business, .scan-types, .languages" ).click( BimQuickscan.updateChartData );
} );

BimQuickscan.sliderUpdate = function( event, ui ) {
	var startYear = customizableChartSettings.startYear + Math.floor( ( customizableChartSettings.startMonth + ui.values[0] ) / 12 );
	var startMonth = ( customizableChartSettings.startMonth + ui.values[0] ) % 12;
	if( ( "" + startMonth ).length < 2 ) {
		startMonth = "0" + startMonth;
	}
	var endYear = customizableChartSettings.startYear + Math.floor( ( customizableChartSettings.startMonth + ui.values[1] ) / 12 );
	var endMonth = ( customizableChartSettings.startMonth + ui.values[1] ) % 12;				
	if( ( "" + endMonth ).length < 2 ) {
		endMonth = "0" + endMonth;
	}
	jQuery( "#slider-result" ).html( startMonth + "-" + startYear + " en " + endMonth + "-" + endYear );
	clearTimeout( BimQuickscan.chartTimeout );
	BimQuickscan.chartTimeout = setTimeout( "BimQuickscan.updateChartData();", 400 );
};

BimQuickscan.updateChartData = function() {
	var range = jQuery( "#slider-result" ).html().split( " en " );
	var selected = "";
	var first = true;
	var types = "&types=" + jQuery( ".scan-types:checked" ).val();
	var limitedLanguages = "&show_language=" + jQuery( ".languages:checked" ).val();
	jQuery( ".core-business" ).each( function() {
		if( !first ) {
			selected += ",";
		}
		if( jQuery( this ).attr( "checked" ) ) {
			if( this.id == "all-scans" ) {
				selected += "--all--";
			} else if( this.id == "own-scans" ) {
				selected += "--own--";			
			} else {
				selected += this.id.replace( "scans-", "" );
			}
		}
		first = false;
	} );
	if( selected != "" ) {
		jQuery.ajax( customizableChartSettings.ajaxUri + "?selected=" + selected + "&start=" + range[0] + "&end=" + range[1] + types + "&lang=" + customizableChartSettings.language + limitedLanguages, {
			dataType: "json",
			type: "get",
			success: BimQuickscan.chartDataResponse
		} );
	}
};

BimQuickscan.chartDataResponse = function( response ) {
	if( response.barChart && response.barChart.datasets.length > 0 ) {
		new Chart( BimQuickscan.barContext ).Bar( response.barChart, BimQuickscan.barOptions );
	} else {
		BimQuickscan.barContext.clearRect( 0, 0, 625, 600 );
	}
	if( response.radarChart && response.radarChart.datasets.length > 0 ) {
		new Chart( BimQuickscan.radarContext ).Radar( response.radarChart, BimQuickscan.radarOptions );
	} else {
		BimQuickscan.radarContext.clearRect( 0, 0, 625, 600 );
	}
};
