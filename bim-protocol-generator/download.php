<?php
include( '../../../wp-config.php' );

if( !isset( $_GET[ 'code' ] ) || $_GET[ 'code' ] == '' ) {
	http_response_code( 403 );
	_e( '403 Forbidden', 'bim-protocol-generator' );
} else {
	if( !BIMProtocolGenerator::printWordDocumentByCode( $_GET[ 'code' ] ) ) {
		http_response_code( 403 );
		_e( '403 Forbidden', 'bim-protocol-generator' );
	}
}