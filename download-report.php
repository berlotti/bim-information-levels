<?php
include( '../../../wp-config.php' );

if( !isset( $_POST[ 'settings' ], $_POST[ 'filters' ] ) || $_POST[ 'settings' ] == '' ) {
	http_response_code( 404 );
	_e( '404 Not found', 'bim-information-levels' );
} else {
	$settings = json_decode( stripslashes( $_POST[ 'settings' ], $_POST[ 'filters' ] ) );
	BIMInformationLevels::printWordDocument( $settings );
}