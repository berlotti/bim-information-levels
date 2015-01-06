<?php
include( '../../../wp-config.php' );

$results = Array();

if( is_user_logged_in() && isset( $_POST[ 'q' ] ) && $_POST[ 'q' ] != '' ) {
	global $wpdb, $tnoBIMQuickscan;

	$query = $_POST[ 'q' ];
	$blogId = get_current_blog_id();
	if( $blogId > 1 ) {
		$capabilities = "wp_{$blogId}_capabilities";
	} else {
		$capabilities = 'wp_capabilities';
	}

	$statement = $wpdb->prepare( "SELECT display_name, user_email, ID
			FROM $wpdb->users
			JOIN $wpdb->usermeta ON user_id = ID
			WHERE meta_key = '$capabilities' AND meta_value LIKE %s AND
				( display_name LIKE %s OR user_nicename LIKE %s OR user_login LIKE %s )
			ORDER BY display_name ASC
			LIMIT 10", '%' . $tnoBIMQuickscan->options[ 'company_role' ] . '%', '%' . $query . '%', '%' . $query . '%', '%' . $query . '%' );
	$results = $wpdb->get_results( $statement );
	foreach( $results as $key => $result ) {
		$results[$key]->street = get_user_meta( $result->ID, 'street', true );
		$results[$key]->postcode = get_user_meta( $result->ID, 'postcode', true );
		$results[$key]->city = get_user_meta( $result->ID, 'city', true );
		$results[$key]->other = get_user_meta( $result->ID, 'other', true );
	}
}

print( json_encode( $results ) );