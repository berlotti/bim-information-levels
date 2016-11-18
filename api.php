<?php
include( '../../../wp-config.php' );

global $wpdb;

$options = BIMInformationLevels::getOptions();

// Supported API methods and descriptions
$methods = Array( 
		'getInformationLevels' => Array(),
		'getObjectsByTopic' => Array( 'topic' ), 
		'getObjectsByLevel' => Array( 'level' ),
		'getPropertiesByLevel' => Array( 'level' ),
		'getTopics' => Array() );
$descriptions = Array( 
		'getInformationLevels' => __( 'Retrieve a list of information levels and their description.', 'bim-information-levels' ),
		'getTopics' => __( 'Retrieve a list of topics.', 'bim-information-levels' ),
		'getObjectsByTopic' => __( 'Retrieve a list of objects based on the supplied topic', 'bim-information-levels' ),
		'getObjectsByLevel' => __( 'Retrieve a list of objects based on the supplied information level', 'bim-information-levels' ),
		'getPropertiesByLevel' => __( 'Retrieve a list of properties based on the supplied information level', 'bim-information-levels' )
);
ksort( $methods );

if( isset( $_GET[ 'method' ] ) && isset( $methods[ $_GET[ 'method' ] ] ) ) {
	// here we execute the methods
	if( $_GET[ 'method' ] == 'getInformationLevels' ) {
		$informationLevels = BIMInformationLevels::getJSONExport( BIMInformationLevels::getInformationLevels() );
		print( json_encode( $informationLevels ) );
	} elseif( $_GET[ 'method' ] == 'getPropertiesByLevel' ) {
		$level = isset( $_GET[ 'level' ] ) ? $_GET[ 'level' ] : false;
		$allInformationLevels = BIMInformationLevels::getInformationLevels();
		$informationLevel = false;
		foreach( $allInformationLevels as $informationLevelData ) {
			if( $informationLevelData->information_level == $level ) {
				$informationLevel = $informationLevelData;
				break;
			}
		}
		if( $informationLevel === false ) {
			print( json_encode( Array( 'error' => 'invalid argument supplied or unknown information level' ) ) );
		} else {
			$properties = $wpdb->get_results( $wpdb->prepare( "SELECT *
					FROM {$wpdb->posts}
					LEFT JOIN {$wpdb->prefix}property_information_level ON property_id = ID
					WHERE post_type = '{$options[ 'bim_property_post_type' ]}' AND post_status = 'publish'
					AND information_level_id = %d
					GROUP BY ID, object_id
					ORDER BY post_title", $informationLevel->ID ) );
						
			foreach( $properties as $key => $property ) {
				$informationLevels = BIMInformationLevels::getItemInformationLevels( $property->object_id, $property->ID );
				$jsonInformationLevels = Array();
				foreach( $informationLevels as $propertyInformationLevel ) {
					foreach( $allInformationLevels as $informationLevelData ) {
						if( $propertyInformationLevel->information_level_id == $informationLevelData->ID ) {
							$jsonInformationLevels[] = Array(
									'title' => $informationLevelData->post_title,
									'informationLevel' => $informationLevelData->information_level
							);
							break;
						}
					}
				}
				$properties[$key]->informationLevels = $jsonInformationLevels;
				//$properties[$key]->code = get_post_meta( $property->ID, 'code', true );
				$properties[$key]->unit = get_post_meta( $property->ID, 'unit', true );
				$properties[$key]->ifcEquivalent = get_post_meta( $property->ID, 'ifc_equivalent', true );
				$properties[$key]->bsddGuid = get_post_meta( $property->ID, 'bsdd_guid', true );
				$properties[$key]->cbnlId = get_post_meta( $property->ID, 'cbnl_id', true );
			}
			print( json_encode( BIMInformationLevels::getJSONExport( $properties ) ) );
		}		
	} elseif( $_GET[ 'method' ] == 'getObjectsByTopic' ) {
		$term = isset( $_GET[ 'topic' ] ) ? get_term_by( 'name', $_GET[ 'topic' ], $options[ 'bim_object_category_taxonomy' ] ) : false;
		if( $term === false ) {
			print( json_encode( Array( 'error' => 'invalid argument supplied or unknown topic' ) ) );
		} else {
			$objects = get_posts( Array(
					'post_type' => $options[ 'bim_object_post_type' ],
					'post_status' => 'publish',
					'tax_query' => Array(
							Array(
									'taxonomy' => $options[ 'bim_object_category_taxonomy' ],
									'field' => 'id',
									'terms' => $term->term_id
							)
					),
					'meta_key' => 'code',
					'numberposts' => -1,
					'orderby' => 'meta_value title',
					'order' => 'ASC'
			) );
			$allInformationLevels = BIMInformationLevels::getInformationLevels();
			foreach( $objects as $key => $property ) {
				$informationLevels = BIMInformationLevels::getItemInformationLevels( $property->object_id, $property->ID );
				$jsonInformationLevels = Array();
				foreach( $informationLevels as $informationLevel ) {
					foreach( $allInformationLevels as $informationLevelData ) {
						if( $informationLevel->information_level_id == $informationLevelData->ID ) {
							$jsonInformationLevels[] = Array(
								'title' => $informationLevelData->post_title,
								'informationLevel' => $informationLevelData->information_level
							);
							break;
						}
					}
				}
				$objects[$key]->informationLevels = $jsonInformationLevels;
				$objects[$key]->code = get_post_meta( $property->ID, 'code', true );
				$objects[$key]->unit = get_post_meta( $property->ID, 'unit', true );
				$objects[$key]->ifcEquivalent = get_post_meta( $property->ID, 'ifc_equivalent', true );
				$objects[$key]->bsddGuid = get_post_meta( $property->ID, 'bsdd_guid', true );
				$objects[$key]->cbnlId = get_post_meta( $object->ID, 'cbnl_id', true );
			}
			print( json_encode( BIMInformationLevels::getJSONExport( $objects ) ) );
		}
	} elseif( $_GET[ 'method' ] == 'getObjectsByLevel' ) {
		$level = isset( $_GET[ 'level' ] ) ? $_GET[ 'level' ] : false;
		$allInformationLevels = BIMInformationLevels::getInformationLevels();
		$informationLevel = false;
		foreach( $allInformationLevels as $informationLevelData ) {
			if( $informationLevelData->information_level == $level ) {
				$informationLevel = $informationLevelData;
				break;
			}
		}
		if( $informationLevel === false ) {
			print( json_encode( Array( 'error' => 'invalid argument supplied or unknown information level' ) ) );
		} else {
			$objects = $wpdb->get_results( $wpdb->prepare( "SELECT *
					FROM {$wpdb->posts}
					LEFT JOIN {$wpdb->prefix}property_information_level ON object_id = ID
					WHERE post_type = '{$options[ 'bim_object_post_type' ]}' AND post_status = 'publish'
						AND information_level_id = %d
					GROUP BY ID, object_id
					ORDER BY post_title", $informationLevel->ID ) );
			
			foreach( $objects as $key => $object ) {
				$informationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID );
				$jsonInformationLevels = Array();
				foreach( $informationLevels as $propertyInformationLevel ) {
					foreach( $allInformationLevels as $informationLevelData ) {
						if( $propertyInformationLevel->information_level_id == $informationLevelData->ID ) {
							$jsonInformationLevels[] = Array(
									'title' => $informationLevelData->post_title,
									'informationLevel' => $informationLevelData->information_level
							);
							break;
						}
					}
				}
				$objects[$key]->informationLevels = $jsonInformationLevels;
				$objects[$key]->code = get_post_meta( $object->ID, 'code', true );
				$objects[$key]->unit = get_post_meta( $object->ID, 'unit', true );
				$objects[$key]->ifcEquivalent = get_post_meta( $object->ID, 'ifc_equivalent', true );
				$objects[$key]->bsddGuid = get_post_meta( $object->ID, 'bsdd_guid', true );
				$objects[$key]->cbnlId = get_post_meta( $object->ID, 'cbnl_id', true );
			}
			print( json_encode( BIMInformationLevels::getJSONExport( $objects ) ) );
		}
	} elseif( $_GET[ 'method' ] == 'getTopics' ) {
		$topics = BIMInformationLevels::getJSONExport( $wpTopics = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => 0 ) ) );
		foreach( $wpTopics as $key => $wpTopic ) {
			$topics[$key][ 'children' ] = BIMInformationLevels::getJSONExport( get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => $wpTopic->term_id ) ) );
		}
		// We remove weird characters for now...
		print( str_replace( '\u00a0', '', json_encode( $topics ) ) );
	}
} else {
	// Here we show how to use this interface
?>
<html>
<head>
	<title><?php _e( 'BIM Information Levels API', 'bim-information-levels' ); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php print( plugins_url( 'api.css', __FILE__ ) );?>" />
</head>
<body>
	<div id="content">
	<h2><?php _e( 'Available API calls', 'bim-information-levels' ); ?></h2>
<?php
	foreach( $methods as $method => $parameters ) {
?>
	<div class="method-container">
		<h3><?php print( $method ); ?></h3>
		<p><?php print( $descriptions[$method] ); ?></p>
		<h4><?php _e( 'Parameters', 'bim-information-levels' ); ?></h4>
		<div class="parameter-container">
<?php 
		$example = plugins_url( 'api.php', __FILE__ ) . '?method=' . $method;
		if( count( $parameters ) == 0 ) {
			_e( 'None', 'bim-information-levels' );
		} else {
			foreach( $parameters as $parameter ) {
				$example .= '&' . $parameter . '=' . ( $method == 'getObjectsByTopic' ? '$_ - ALGEMEEN' : '0' );
				print( $parameter . ' - String<br />' );
			}
		}
?>		
		</div>
		<h4><?php _e( 'Example', 'bim-information-levels' ); ?></h4>
		<a href="<?php print( $example ); ?>"><?php print( $example ); ?></a><br />
	</div>
<?php	
	}
?>
	</div>
</body>
</html>	
<?php	
}