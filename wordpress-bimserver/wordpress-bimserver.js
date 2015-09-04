WordPressBimserver = function() {};
WordPressBimserver = WordPressBimserver.prototype = function() {};

jQuery( document ).ready( function() {
	if( typeof wpBimserverSettings !== "undefined" ) {
		WordPressBimserver.settings = wpBimserverSettings;

		// TODO: if we need to check upload progress start it here
	}
} );

