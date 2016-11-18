<?php
include( '../../../wp-config.php' );

$results = Array();

if( isset( $_POST[ 'type' ] ) && $_POST[ 'type' ] == 'getPreview' ) {
	if( isset( $_POST[ 'settings' ], $_POST[ 'filters' ] ) && $_POST[ 'settings' ] != '' ) {
		$settings = json_decode( stripslashes( $_POST[ 'settings' ] ) );
		if( isset( $settings ) ) {
			$results[ 'result' ] = BIMInformationLevels::getReportHTML( $settings, $_POST[ 'filters' ] );
		}
	} else {
		$results[ 'error' ] = true;
	}
} else {
	$results[ 'error' ] = true;
}

print( json_encode( $results ) );