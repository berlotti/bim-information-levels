<?php
global $bimInformationLevels, $wpdb;

$message = '';

if( isset( $_POST['action'] ) && $_POST[ 'action' ] == 'update' ) {
	$options = BIMInformationLevels::getOptions();

	foreach( $_POST[ 'bim_information_levels_options' ] AS $key => $newOption ) {
		$options[$key] = $newOption;
	}
	 
	if( isset( $options[ 'information_levels_page' ] ) && $options[ 'information_levels_page' ] != -1 ) {
		$permalink = get_permalink( $options[ 'information_levels_page' ] );
		$wpurl = get_bloginfo( 'wpurl' );
		$options[ 'information_levels_uri' ] = str_replace( $wpurl, '', $permalink );
	}
	 
	update_option( 'bim_information_levels_options', $options );
	
	if( isset( $_FILES[ 'import_nl_fsb_csv' ] ) && isset( $_FILES[ 'import_nl_fsb_csv' ][ 'tmp_name' ] ) && $_FILES[ 'import_nl_fsb_csv' ][ 'tmp_name' ] != '' ) {
		if( isset( $_FILES[ 'import_nl_fsb_csv' ][ 'error' ] ) && $_FILES[ 'import_nl_fsb_csv' ][ 'error' ] != 0 ) {
			$message = __( 'Could not import the NL FSB file' ) . ' Error code: ' . $_FILES[ 'import_nl_fsb_csv' ][ 'error' ];
	   	} else {
	   		$lines = explode( "\n", file_get_contents( $_FILES[ 'import_nl_fsb_csv' ][ 'tmp_name' ] ) );
	   		$parentId = -1;
	   		$importedFSB = 0;
	   		foreach( $lines as $line ) {
	   			$values = explode( ',', $line );
	   			if( count( $values ) == 2 && ( strlen( $values[0] ) == 2 || substr( $values[0], 0, 3 ) == 'GWW' ) ) {
		   			if( ( strlen( $values[0] ) == 2 && substr( $values[0], 1 ) == '_' ) || substr( $values[0], 0, 4 ) == 'GWW_' ) {
		   				$term = wp_insert_term( $values[0] . ' - ' . trim( $values[1] ), $options[ 'bim_object_category_taxonomy' ] );
		   				if( is_array( $term ) ) {
		   					$parentId = $term[ 'term_id' ];
		   				}
		   			} else {
		   				$term = wp_insert_term( $values[0] . ' - ' . trim( $values[1] ), $options[ 'bim_object_category_taxonomy' ], Array( 'parent' => $parentId ) );
		   			}
		   			$importedFSB ++;
	   			}
	   		}
	   		delete_option( "{$options[ 'bim_object_category_taxonomy' ]}_children" );
	   		$message = __( 'NL FSB Categories imported', 'bim-information-levels' ) . ': ' . number_format( $importedFSB );
	   	}
	}
	
	if( isset( $_FILES[ 'import_csv' ] ) && isset( $_FILES[ 'import_csv' ][ 'tmp_name' ] ) && $_FILES[ 'import_csv' ][ 'tmp_name' ] != '' ) {
		if( $message != '' ) {
			$message .= '<br />';
		}
		if( isset( $_FILES[ 'import_csv' ][ 'error' ] ) && $_FILES[ 'import_csv' ][ 'error' ] != 0 ) {
			$message = __( 'Could not import the file' ) . ' Error code: ' . $_FILES[ 'import_csv' ][ 'error' ];
		} else {
			$lines = explode( "\n", file_get_contents( $_FILES[ 'import_csv' ][ 'tmp_name' ] ) );
			$ignoreLines = 2;
			$importedProperties = 0;
			$importedObjects = 0;
			$informationLevels = BimInformationLevels::getInformationLevels();
			$codes = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false ) );
			
			foreach( $lines as $line ) {
				if( $ignoreLines > 0 ) {
					$ignoreLines --;
				} else {
					$values = explode( ',', $line );
					if( count( $values ) == 14 ) { // Need 14 values per row
						if( trim( $values[1] ) == '' && trim( $values[2] ) == '' ) {
							// Empty row, skip!
						} else {
							$postData = Array();
							$existingProperty = null;
							if( trim( $values[1] ) != '' ) {
								// Object
								$postData = Array(
									'post_title' => trim( $values[1] ),
									'post_status' => 'publish',
									'post_type' => $options[ 'bim_object_post_type' ]
								);
								$importedObjects ++;
							} elseif( trim( $values[2] ) != '' ) {
								// Property
								$postData = Array(
										'post_title' => trim( $values[2] ),
										'post_status' => 'publish',
										'post_type' => $options[ 'bim_property_post_type' ]
								);
								$existingProperty = get_page_by_title( trim( $values[2] ), OBJECT, $options[ 'bim_property_post_type' ] );
								if( isset( $existingProperty ) ) {
									$postData[ 'ID' ] = $existingProperty->ID;
								}
								$importedProperties ++;
							}
							if( count( $postData ) > 0 ) {
								$postMeta = Array();
								if( !isset( $existingProperty ) ) {
									$postMeta[ 'code' ] = trim( $values[0] );
									if( $postMeta[ 'code' ] == '' ) {
										$postMeta[ 'code' ] = '-';
									}
									$postMeta[ 'unit' ] = trim( $values[3] );
									$postMeta[ 'ifc_equivalent' ] = trim( $values[4] );
									$postMeta[ 'bsdd_guid' ] = trim( $values[5] );
									$postMeta[ 'cbnl_id' ] = trim( $values[6] );
								}
								$itemInformationLevels = Array();
								foreach( $informationLevels as $informationLevel ) {
									if( isset( $values[7 + $informationLevel->information_level] ) && trim( $values[7 + $informationLevel->information_level] ) != '' ) {
										$itemInformationLevels[$informationLevel->information_level] = $informationLevel->ID;
									}
								}
								// import the post
								$postId = wp_insert_post( $postData );
								
								if( ctype_digit( $postId ) ) {
									if( trim( $values[1] ) != '' ) {
										$objectId = $postId;
										$propertyId = -1;
									} elseif( isset( $objectId ) ) {
										$postMeta[ '_object_id' ] = $objectId;
										$propertyId = $postId;
									}
									foreach( $postMeta as $key => $value ) {
										add_post_meta( $postId, $key, $value, $key != '_object_id' );
									}
									$sql = "INSERT INTO {$wpdb->prefix}property_information_level\n
										( `object_id`, `property_id`, `information_level_id` )\n
										VALUES ";
									$count = 0;
									foreach( $itemInformationLevels as $levelId ) {
										if( $count > 0 ) {
											$sql .= ', ';
										}
										$sql .= "( $objectId, $propertyId, $levelId )";
										$count ++;
									}
									if( $count > 0 ) {
										$wpdb->query( $sql );
									}
									$termId = null;
									$codeParts = explode( '.', trim( $values[0] ) );
									// I think this is how I should match these things, but not sure...
									foreach( $codes as $code ) {
										if( $codeParts[0] == substr( $code->name, 0, 2 ) || str_replace( '$', '0', str_replace( '_', '0', substr( $code->name, 0, 2 ) ) ) == $codeParts[0] || $codeParts[0] == substr( $code->name, 0, 4 ) ) {
											$termId = $code->term_id;
											break;
										}
									}
									if( isset( $termId ) ) {
										wp_set_object_terms( $postId, Array( intval( $termId ) ), $options[ 'bim_object_category_taxonomy' ] );
									}
									//var_dump( $postData, $postMeta, $itemInformationLevels, $termId, $code );
									//break;
								}
							}
						}
					}
				}
			}
			$message .= __( 'BIM Objects imported', 'bim-information-levels' ) . ': ' . number_format( $importedObjects ) . '<br />';
			$message .= __( 'BIM Object properties imported', 'bim-information-levels' ) . ': ' . number_format( $importedProperties );
		}
	}
} elseif( isset( $_POST['delete'] ) && $_POST[ 'delete' ] == 'delete objects and properties' &&
		isset( $_POST[ 'delete_confirm' ] ) && $_POST[ 'delete_confirm' ] == 'confirmed' ) {
	$options = BIMInformationLevels::getOptions();
	$wpdb->query( "DELETE FROM {$wpdb->prefix}property_information_level" );
	$objectCategories = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false ) );
	foreach( $objectCategories as $objectCategory ) {
		wp_delete_term( $objectCategory->term_id, $options[ 'bim_object_category_taxonomy' ] );
	}
	$objects = get_posts( Array(
		'post_type' => $options[ 'bim_object_post_type' ],
		'numberposts' => -1	
	) );
	foreach( $objects as $object ) {
		wp_delete_post( $object->ID );
	}
	$properties = get_posts( Array(
		'post_type' => $options[ 'bim_property_post_type' ],
		'numberposts' => -1	
	) );
	foreach( $properties as $property ) {
		wp_delete_post( $property->ID );
	}
	// We remove the property information linking table and reinstall it to make sure we get the latest version
	$wpdb->query( "DROP TABLE {$wpdb->prefix}property_information_level" );
	BIMInformationLevels::install();
	$message = __( 'All objects, properties and object categories removed.', 'bim-information-levels' );
}

$bimInformationLevelsOptions = BIMInformationLevels::getOptions( true );
$postTypes = get_post_types( Array(), 'objects' );
$taxonomies = get_taxonomies();
$pages = get_posts( Array(
		'post_type' => 'page',
		'posts_per_page' => -1
) );

if( $message != '' ) {
?>
    <div class="updated">
        <p><?php print( $message ); ?></p>
    </div>
<?php
}
?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2><?php _e( 'BIM Information Levels Options' ); ?></h2>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tr valign="top">
				<td><label for="bim-object-post-type"><?php _e( 'BIM object post type', 'bim-information-levels' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_information_levels_options[bim_object_post_type]" id="bim-object-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( $key == $bimInformationLevelsOptions[ 'bim_object_post_type' ] ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which BIM objects are stored', 'bim-information-levels' ); ?></p>
				</td>
				
			</tr>
			<tr valign="top">
				<td><label for="bim-property-post-type"><?php _e( 'BIM property post type', 'bim-information-levels' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_information_levels_options[bim_property_post_type]" id="bim-property-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( $key == $bimInformationLevelsOptions[ 'bim_property_post_type' ] ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which the properties for BIM objects are stored', 'bim-information-levels' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="information-level-post-type"><?php _e( 'Information Level post type', 'bim-information-levels' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_information_levels_options[information_level_post_type]" id="information-level-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( $key == $bimInformationLevelsOptions[ 'information_level_post_type' ] ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which information levels are stored', 'bim-information-levels' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="bim-object-category-taxonomy"><?php _e( 'BIM object category taxonomy', 'bim-information-levels' ); ?></label></td>
				<td>
<?php
if( is_array( $taxonomies ) ) {
?>
                  <select name="bim_information_levels_options[bim_object_category_taxonomy]" id="bim-object-category-taxonomy">
<?php
   foreach( $taxonomies AS $key => $taxonomy ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( $key == $bimInformationLevelsOptions[ 'bim_object_category_taxonomy' ] ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $taxonomy ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The taxonomy in which categories for BIM objects are stored', 'bim-information-levels' ); ?></p>
				</td>
			</tr>			
			<tr valign="top">
				<td><label for="question-page"><?php _e( 'Information Levels page', 'bim-information-levels' ); ?></label>
				</td>
				<td>
					<select name="bim_information_levels_options[information_levels_page]" id="question-page">
						<option value=""><?php _e( 'Select page', 'bim-information-levels' ); ?></option>
<?php
	foreach( $pages as $page ) {
?>					
						<option value="<?php print( $page->ID ); ?>"<?php print( ( isset( $bimInformationLevelsOptions[ 'information_levels_page' ] ) && $bimInformationLevelsOptions[ 'information_levels_page' ] == $page->ID ? ' selected' : '' ) ); ?>>
							<?php print( $page->post_title ); ?>
                    	</option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'Page which displays all information levels', 'bim-information-levels' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="question-uri">Information Levels page URI</label></td>
				<td>
					<input type="text" id="question-uri" name="bim_information_levels_options[information_levels_uri]" value="<?php print( isset( $bimInformationLevelsOptions[ 'information_levels_uri' ] ) ? $bimInformationLevelsOptions[ 'information_levels_uri' ] : '' ); ?>" />
					<p class="description"><?php _e( 'This should point to the page which displays all information levels', 'bim-information-levels' ); ?></p>
				</td>
			</tr>
			<!--tr valign="top">
				<td><label for="email-adres"><?php _e( 'Email adres voor notificaties', 'bim-information-levels' ); ?></label></td>
				<td>
				   <input type="text" name="bim_information_levels_options[email_adres]" value="<?php print( isset( $bimInformationLevelsOptions[ 'email_adres' ] ) ? $bimInformationLevelsOptions[ 'email_adres' ] : '' ); ?>" id="email-adres" />
				   <p class="description"><?php _e( 'Het email adres waar notificaties naartoe worden gestuurd zoals wanneer een vragenlijst is ingevuld.', 'bim-information-levels' ); ?></p>
				</td>
			</tr-->
			<tr valign="top">
				<td><label for="import-nl-sfb-csv"><?php _e( 'Import NL FSB CSV', 'bim-information-levels' ); ?></label></td>
				<td>
				   <input type="file" name="import_nl_fsb_csv" id="import-nl-sfb-csv" />
				   <p class="description"><?php _e( 'Import CSV containing NL FSB categories.', 'bim-information-levels' ); ?></p>
				</td>
			</tr>	
			<tr valign="top">
				<td><label for="import-csv"><?php _e( 'Import CSV', 'bim-information-levels' ); ?></label></td>
				<td>
				   <input type="file" name="import_csv" id="import-csv" />
				   <p class="description"><?php _e( 'Import CSV containing objects, properties and their information levels.', 'bim-information-levels' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
				   <p class="description"><?php _e( 'API accessible through', 'bim-information-levels' ); ?>: <a href="<?php print( plugins_url( 'api.php', __FILE__ ) ); ?>"><?php print( plugins_url( 'api.php', __FILE__ ) ); ?></a></p>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<p class="submit">
						<input class="button-primary" type="submit" name="action" value="update" />
					</p>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<p class="submit">
						<input type="checkbox" id="delete-confirm" name="delete_confirm" value="confirmed" /> <label for="delete-confirm"><?php _e( 'Confirm deleting all objects, properties and object categories from the database', 'bim-information-levels' ); ?></label><br />
						<em><?php _e( 'Note: Can not be undone!', 'bim-information-levels' ); ?></em><br />
						<input class="button-primary" type="submit" name="delete" value="delete objects and properties" />
					</p>
				</td>
			</tr>
		</table>
	</form>
</div>
