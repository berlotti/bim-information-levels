<?php
global $tnoBIMQuickscan, $wpdb, $wp_roles, $sitepress;

if( isset( $sitepress ) ) {
	$availableLanguages = $sitepress->get_active_languages();
	$defaultLanguage = $sitepress->get_default_language();
	$sitepress->switch_lang( $defaultLanguage, true ); // Force default language for the settings/content
} else {
	$availableLanguages = Array();
	$defaultLanguage = '';
}

if( isset( $_POST['action'] ) ) {
   foreach( $_POST[ 'tno_bim_quickscan_options' ] AS $key => $newOption ) {
      $options[$key] = $newOption;
   }
   
   if( isset( $_FILES[ 'import_selfscan_csv' ] ) && isset( $_FILES[ 'import_selfscan_csv' ][ 'tmp_name' ] ) && $_FILES[ 'import_selfscan_csv' ][ 'tmp_name' ] != '' ) {
      $file = fopen( $_FILES[ 'import_selfscan_csv' ][ 'tmp_name' ], 'r' );
      $firstLine = Array();
      while( ( $line = fgets( $file ) ) !== false ) {
      	if( count( $firstLine ) == 0 ) {
      		$firstLine = explode( '","', $line );
      	  foreach( $firstLine as $key => $value ) {
      	  	$firstLine[$key] = trim( $value, "\x22" );
      	  }
      	} else {
      		$data = explode( '","', $line );
      	  foreach( $data as $key => $value ) {
      	  	$data[$key] = trim( $value, "\x22" );
      	  }
      		$tnoBIMQuickscan->importSelfscan( $data, $firstLine );
      	}
      }
      fclose( $file );
   }
   if( isset( $_FILES[ 'import_advisors_csv' ] ) && isset( $_FILES[ 'import_advisors_csv' ][ 'tmp_name' ] ) && $_FILES[ 'import_advisors_csv' ][ 'tmp_name' ] != '' ) {
      $file = fopen( $_FILES[ 'import_advisors_csv' ][ 'tmp_name' ], 'r' );
      while( ( $line = fgets( $file ) ) !== false ) {
      	$data = explode( '","', $line );
      	foreach( $data as $key => $value ) {
      		$data[$key] = trim( $value, "\x22 \r\n" );
      	}
      	if( count( $data ) >= 2 ) {
					$email = sanitize_user( $data[2] );
					// Check if this user already exists else we need to add it
					$advisorId = username_exists( $email );
					if( !isset( $advisorId ) ) {
						$randomPassword = wp_generate_password( 8, false );
						$advisorId = wp_create_user( $email, $randomPassword, $email );
						$displayName = $data[1];
						$user = get_user_by( 'id', $advisorId );
						if( isset( $user ) && $user !== false ) {
							$user->set_role( $tnoBIMQuickscan->options[ 'adviser_role' ] );
							$userData = Array( 'ID' => $advisorId, 'display_name' => $displayName, 'first_name' => $displayName );
							wp_update_user( $userData );
							add_user_meta( $advisorId, 'external_advisor_id', $data[0] );
						}
					}
				}
      }
      fclose( $file );
   }
   if( isset( $_FILES[ 'import_quickscan_csv' ] ) && isset( $_FILES[ 'import_quickscan_csv' ][ 'tmp_name' ] ) && $_FILES[ 'import_quickscan_csv' ][ 'tmp_name' ] != '' ) {
      $file = fopen( $_FILES[ 'import_quickscan_csv' ][ 'tmp_name' ], 'r' );
      $firstLine = Array();
      while( ( $line = fgets( $file ) ) !== false ) {
      	if( count( $firstLine ) == 0 ) {
      		$firstLine = explode( '","', $line );
      	  foreach( $firstLine as $key => $value ) {
      	  	$firstLine[$key] = trim( $value, "\x22" );
      	  }
      	} else {
      		$data = explode( '","', $line );
      	  foreach( $data as $key => $value ) {
      	  	$data[$key] = trim( $value, "\x22" );
      	  }
      		$tnoBIMQuickscan->importQuickscan( $data, $firstLine );
      	}
      }
      fclose( $file );
   }

   update_option( 'tno_bim_quickscan_options', $options );
   if( isset( $_POST[ 'add_roles' ] ) && $_POST[ 'add_roles' ] == 'true' ) {
	$tnoBIMQuickscan->activation();
   }
   if( isset( $_POST[ 'insert_nl_language' ] ) && $_POST[ 'insert_nl_language' ] == 'true' ) {
   	$missingMetaAmount = TNOBIMQuickscan::insertMissingCountryMeta();
?>
<br />
<br />
<p class="description"><?php _e( 'Language meta inserted' ); ?>: <?php print( number_format( $missingMetaAmount ) ); ?></p>
<?php
   }
} else {
   $defaultOptions = Array ();
   add_option( 'tno_bim_quickscan_options', $defaultOptions );
}

$tnoBIMQuickscanOptions = get_option( 'tno_bim_quickscan_options' );
$postTypes = get_post_types( Array(), 'objects' );
$taxonomies = get_taxonomies( Array( 'public' => true, '_builtin' => false ), 'objects' );
$roles = $wp_roles->roles;
if( isset( $tnoBIMQuickscanOptions[ 'taxonomy_topic' ] ) ) {
	$terms = get_terms( $tnoBIMQuickscanOptions[ 'taxonomy_topic' ], Array( 'hide_empty' => false ) );
} else {
	$terms = Array();
}
if( isset( $tnoBIMQuickscanOptions[ 'taxonomy_category' ] ) ) {
	$aspects = get_terms( $tnoBIMQuickscanOptions[ 'taxonomy_category' ], Array( 'hide_empty' => false ) );
} else {
	$aspects = Array();
}
$pages = get_posts( Array(
		'post_type' => 'page',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC'
		) );

?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2>TNO BIM Quickscan options</h2>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tr>
				<td><label for="company-role"><?php _e( 'Company role', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $wp_roles->roles ) ) {
?>
                  <select name="tno_bim_quickscan_options[company_role]" id="company-role">
<?php
   foreach( $wp_roles->roles AS $key => $role ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'company_role' ] ) && $key == $tnoBIMQuickscanOptions[ 'company_role' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $role[ 'name' ] ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The role users get for whom a BIM Quickscan has been submitted', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="adviser-role"><?php _e( 'Adviser role', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $wp_roles->roles ) ) {
?>
                  <select name="tno_bim_quickscan_options[adviser_role]" id="adviser-role">
<?php
   foreach( $wp_roles->roles AS $key => $role ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'adviser_role' ] ) && $key == $tnoBIMQuickscanOptions[ 'adviser_role' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $role[ 'name' ] ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The role users get who submit BIM Quickscans for other companies', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="report-post-type"><?php _e( 'Report post type', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="tno_bim_quickscan_options[report_post_type]" id="report-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'report_post_type' ] ) && $key == $tnoBIMQuickscanOptions[ 'report_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type used to store scan reports in', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="topic-taxonomy"><?php _e( 'The taxonomy for topics', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $taxonomies ) ) {
?>
                  <select name="tno_bim_quickscan_options[taxonomy_topic]" id="topic-taxonomy">
<?php
   foreach( $taxonomies AS $key => $taxonomy ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'taxonomy_topic' ] ) && $key == $tnoBIMQuickscanOptions[ 'taxonomy_topic' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $taxonomy->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The taxonomy topics are stored in', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="exclude-topic"><?php _e( 'Exclude topic from chart', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $terms ) ) {
?>
                  <select name="tno_bim_quickscan_options[exclude_topic]" id="exclude-topic">
                  	<option value=""><?php _e( 'None', 'tno-bim-quickscan' ); ?></option>
<?php
   foreach( $terms AS $key => $term ) {
?>
                     <option value="<?php print( $term->term_id ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'exclude_topic' ] ) && $term->term_id == $tnoBIMQuickscanOptions[ 'exclude_topic' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $term->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'A topic to exclude from the report bar chart', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="category-taxonomy"><?php _e( 'The taxonomy for aspects', 'tno-bim-quickscan' ); ?></label></td>
				<td>
<?php
if( is_array( $taxonomies ) ) {
?>
                  <select name="tno_bim_quickscan_options[taxonomy_category]" id="category-taxonomy">
<?php
   foreach( $taxonomies AS $key => $taxonomy ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $tnoBIMQuickscanOptions[ 'company_role' ] ) && $key == $tnoBIMQuickscanOptions[ 'taxonomy_category' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $taxonomy->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The taxonomy aspects are stored in', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="reports-per-page"><?php _e( 'Reports per page', 'tno-bim-quickscan' ); ?></label></td>
				<td>
				   <input type="text" name="tno_bim_quickscan_options[reports_per_page]" value="<?php print( isset( $tnoBIMQuickscanOptions[ 'reports_per_page' ] ) ? $tnoBIMQuickscanOptions[ 'reports_per_page' ] : '20' ); ?>" id="reports-per-page" />
				   <p class="description"><?php _e( 'The number of reports per page in the frontend.', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
<?php
if( count( $pages ) > 0 ) {
?>
			<tr>
				<td><label for="not-logged-in-page"><?php _e( 'Not logged in page', 'tno-bim-quickscan' ); ?></label></td>
				<td>
                  <select name="tno_bim_quickscan_options[not_logged_in_page]" id="not-logged-in-page">
<?php
   foreach( $pages as $page ) {
?>
                     <option value="<?php print( $page->ID ); ?>" <?php print( ( isset( $tnoBIMQuickscanOptions[ 'not_logged_in_page' ] ) && $page->ID == $tnoBIMQuickscanOptions[ 'not_logged_in_page' ] ) ? ' selected="selected"' : '' ); ?>>
						<?php print( $page->post_title ); ?>
                     </option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'The page to which visitors who are redirected when they are not logged in and trying to access a protected page', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="single-report-page"><?php _e( 'Report page', 'tno-bim-quickscan' ); ?></label></td>
				<td>
                  <select name="tno_bim_quickscan_options[report_page]" id="single-report-page">
<?php
   foreach( $pages as $page ) {
?>
                     <option value="<?php print( $page->ID ); ?>" <?php print( ( isset( $tnoBIMQuickscanOptions[ 'report_page' ] ) && $page->ID == $tnoBIMQuickscanOptions[ 'report_page' ] ) ? ' selected="selected"' : '' ); ?>>
						<?php print( $page->post_title ); ?>
                     </option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'This page should contain the shortcode [showSingleReport], it is used to display a single report', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="single-company-page"><?php _e( 'Company page', 'tno-bim-quickscan' ); ?></label></td>
				<td>
                  <select name="tno_bim_quickscan_options[company_page]" id="single-company-page">
<?php
   foreach( $pages as $page ) {
?>
                     <option value="<?php print( $page->ID ); ?>" <?php print( ( isset( $tnoBIMQuickscanOptions[ 'company_page' ] ) && $page->ID == $tnoBIMQuickscanOptions[ 'company_page' ] ) ? ' selected="selected"' : '' ); ?>>
						<?php print( $page->post_title ); ?>
                     </option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'This page should contain the shortcode [showSingleCompany], it is used to display a single company and their public reports', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="register-page"><?php _e( 'Register page', 'tno-bim-quickscan' ); ?></label></td>
				<td>
                  <select name="tno_bim_quickscan_options[register_page]" id="register-page">
<?php
   foreach( $pages as $page ) {
?>
                     <option value="<?php print( $page->ID ); ?>" <?php print( ( isset( $tnoBIMQuickscanOptions[ 'register_page' ] ) && $page->ID == $tnoBIMQuickscanOptions[ 'register_page' ] ) ? ' selected="selected"' : '' ); ?>>
						<?php print( $page->post_title ); ?>
                     </option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'Select the page that contains the registration form', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
<?php
}
if( count( $terms ) > 0 ) {
   foreach( $terms as $term ) {
   	if( $term->term_id != $tnoBIMQuickscanOptions[ 'exclude_topic' ] ) {
?>
			<tr>
				<td><label for="topic-cap-<?php print( $term->term_id ); ?>"><?php _e( 'Topic cap', 'tno-bim-quickscan' ); ?> <?php print( $term->name ); ?></label></td>
				<td>
					<input type="text" id="topic-cap-<?php print( $term->term_id ); ?>" name="tno_bim_quickscan_options[topic_cap_<?php print( $term->term_id ); ?>]" value="<?php print( isset( $tnoBIMQuickscanOptions[ 'topic_cap_' . $term->term_id ] ) ? $tnoBIMQuickscanOptions[ 'topic_cap_' . $term->term_id ] : 5 ); ?>" />
					<p class="description"><?php _e( 'The topic cap for the topic', 'tno-bim-quickscan' ); ?> &quot;<?php print( $term->name ); ?>&quot;, <?php _e( 'results will be scored down to this value', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
<?php
   	}
   }
}
if( count( $aspects ) > 0 ) {
   foreach( $aspects as $aspect ) {
?>
			<tr>
				<td><label for="aspect-short-name-<?php print( $aspect->term_id ); ?>">Short name for <?php print( $aspect->name ); ?></label></td>
				<td>
					<input type="text" id="aspect-short-name-<?php print( $aspect->term_id ); ?>" name="tno_bim_quickscan_options[aspect_short_name_<?php print( $aspect->term_id ); ?>]" value="<?php print( isset( $tnoBIMQuickscanOptions[ 'aspect_short_name_' . $aspect->term_id ] ) ? $tnoBIMQuickscanOptions[ 'aspect_short_name_' . $aspect->term_id ] : $aspect->name ); ?>" />
					<p class="description"><?php _e( 'The short name used in the radar graph for the aspect', 'tno-bim-quickscan' ); ?> &quot;<?php print( $aspect->name ); ?>&quot;</p>
				</td>
			</tr>
<?php
   }
   if( count( $availableLanguages ) > 0 ) {
	foreach( $availableLanguages as $language => $lang ) {
		if( $language != $defaultLanguage ) {
			foreach( $aspects as $aspect ) {
				$aspectTranslated = icl_object_id( $aspect->term_id, $tnoBIMQuickscanOptions[ 'taxonomy_category' ], true, $language );
				$aspectTranslated = get_term( $aspectTranslated, $tnoBIMQuickscanOptions[ 'taxonomy_category' ] );
?>
			<tr>
				<td><label for="aspect-short-name-<?php print( $aspect->term_id ); ?>-<?php print( $language ); ?>">Short name for <?php print( $aspectTranslated->name ); ?> (<?php print( $language ); ?>)</label></td>
				<td>
					<input type="text" id="aspect-short-name-<?php print( $aspect->term_id ); ?>-<?php print( $language ); ?>" name="tno_bim_quickscan_options[aspect_short_name_<?php print( $aspect->term_id ); ?>_<?php print( $language ); ?>]" value="<?php print( isset( $tnoBIMQuickscanOptions[ 'aspect_short_name_' . $aspect->term_id . '_' . $language ] ) ? $tnoBIMQuickscanOptions[ 'aspect_short_name_' . $aspect->term_id . '_' . $language ] : $aspectTranslated->name ); ?>" />
					<p class="description"><?php _e( 'The short name used in the radar graph for the aspect', 'tno-bim-quickscan' ); ?> &quot;<?php print( $aspectTranslated->name ); ?>&quot; (<?php print( $language ); ?>)</p>
				</td>
			</tr>
<?php			
			}
		}
	}
   }
}

if( class_exists( 'RGForms' ) ) {
	$forms =  RGFormsModel::get_forms();
} else {
	$forms = Array();
}
if( count( $forms ) > 0 ) {
?>
			<tr>
				<td><label for="quickscan-gravityform"><?php _e( 'The Quickscan form', 'tno-bim-quickscan' ); ?></label></td>
				<td>
                  <select name="tno_bim_quickscan_options[quickscan_form]" id="quickscan-gravityform">
<?php
   foreach( $forms as $form ) {
?>
                     <option value="<?php print( $form->id ); ?>" <?php print( ( isset( $tnoBIMQuickscanOptions[ 'quickscan_form' ] ) && $form->id == $tnoBIMQuickscanOptions[ 'quickscan_form' ] ) ? ' selected="selected"' : '' ); ?>>
						<?php print( $form->title ); ?>
                     </option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'The GravityForms form which contains the Quickscan questions', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
<?php
}
?>
			<tr>
				<td><label for="import-selfscan-csv"><?php _e( 'Import selfscan CSV', 'tno-bim-quickscan' ); ?></label></td>
				<td>
				   <input type="file" name="import_selfscan_csv" id="import-selfscan-csv" />
				   <p class="description"><?php _e( 'Upload a CSV file to be imported as selfscans.', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="import-quickscan-csv"><?php _e( 'Import quickscan CSV', 'tno-bim-quickscan' ); ?></label></td>
				<td>
				   <input type="file" name="import_quickscan_csv" id="import-quickscan-csv" />
				   <p class="description"><?php _e( 'Upload a CSV file to be imported as Quickscans with advisor.', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td><label for="import-quickscan-advisors"><?php _e( 'Import advisors CSV', 'tno-bim-quickscan' ); ?></label></td>
				<td>
				   <input type="file" name="import_advisors_csv" id="import-quickscan-advisors" />
				   <p class="description"><?php _e( 'Upload a CSV file containing the advisors and their id used to match them with Quickscans.', 'tno-bim-quickscan' ); ?></p>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="add_roles" id="add-roles" value="true" />
					<label for="add-roles"><?php _e( 'Force add the roles required for Quickscans', 'tno-bim-quickscan' ); ?></label>
					<p class="description"><?php _e( 'In case the advisor and company roles are not created on activating the plugin check this to force them to be added.', 'tno-bim-quickscan' ); ?></p>
				<td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="insert_nl_language" id="insert-nl-language" value="true" />
					<label for="insert-nl-language"><?php _e( 'Insert language meta', 'tno-bim-quickscan' ); ?></label>
					<p class="description"><?php _e( 'For Quickscan reports made before WPML was enabled checking this option will insert nl language values for each report missing language meta data.', 'tno-bim-quickscan' ); ?></p>
				<td>
			</tr>			
			<tr>
				<td colspan="2">
					<p class="submit">
						<input class="button-primary" type="submit" name="action" value="update" />
					</p>
				</td>
			</tr>
			</table>
	</form>
</div>
