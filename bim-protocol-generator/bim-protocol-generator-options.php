<?php
global $bimProtocolGenerator, $wpdb, $sitepress;
if( isset( $sitepress ) ) {
	$availableLanguages = $sitepress->get_active_languages();
	$defaultLanguage = $sitepress->get_default_language();
} else {
	$availableLanguages = Array();
	$defaultLanguage = '';
}
if( isset( $_POST['action'] ) ) {
   $options = BIMProtocolGenerator::getOptions();

   foreach( $_POST[ 'bim_protocol_generator_options' ] AS $key => $newOption ) {
      if( substr( $key, 0, 15 ) == 'report_chapters' ) {
      	$raw = explode( ',', $newOption );
      	$options[$key] = Array();
      	foreach( $raw as $chapter ) {
      		$chapter = trim( $chapter );
      		if( $chapter != '' ) {
      			$options[$key][] = $chapter;
      		}
      	}
      } elseif( substr( $key, 0, 18 ) == 'information_levels' ) {
      	$raw = explode( ',', $newOption );
      	$options[$key] = Array();
      	foreach( $raw as $informationLevel ) {
      		$informationLevel = trim( $informationLevel );
      		if( $informationLevel != '' ) {
      			$options[$key][] = $informationLevel;
      		}
      	}
      } else {
      	$options[$key] = $newOption;
      }
   }
   
   if( isset( $options[ 'question_page' ] ) && $options[ 'question_page' ] != -1 ) {
	   	$permalink = get_permalink( $options[ 'question_page' ] );
	   	$wpurl = get_bloginfo( 'wpurl' );
	   	$options[ 'question_uri' ] = str_replace( $wpurl, '', $permalink );
   }
   
   if( !isset( $_POST[ 'bim_protocol_generator_options' ][ 'enabled_post_types' ] ) ) {
      $options[ 'enabled_post_types' ] = Array();
   }

   update_option( 'bim_protocol_generator_options', $options );
} else {
   $defaultOptions = Array ();
   add_option( 'bim_protocol_generator_options', $defaultOptions );
}

$bimProtocolGeneratorOptions = BIMProtocolGenerator::getOptions( true );
$postTypes = get_post_types( Array(), 'objects' );
$pages = get_posts( Array(
		'post_type' => 'page',
		'posts_per_page' => -1
) );
?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2><?php _e( 'BIM Execution plan Generator Options' ); ?></h2>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tr valign="top">
				<td><label for="initiator-post-type"><?php _e( 'Initiator post type', 'bim-protocol-generator' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_protocol_generator_options[initiator_post_type]" id="initiator-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $bimProtocolGeneratorOptions[ 'initiator_post_type' ] ) && $key == $bimProtocolGeneratorOptions[ 'initiator_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which questionnaire information is stored', 'bim-protocol-generator' ); ?></p>
				</td>
				
			</tr>
			<tr valign="top">
				<td><label for="question-post-type"><?php _e( 'Question post type', 'bim-protocol-generator' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_protocol_generator_options[question_post_type]" id="question-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $bimProtocolGeneratorOptions[ 'question_post_type' ] ) && $key == $bimProtocolGeneratorOptions[ 'question_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which the questions are stored', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="answer-post-type"><?php _e( 'Answer post type', 'bim-protocol-generator' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_protocol_generator_options[answer_post_type]" id="answer-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print( ( ( isset( $bimProtocolGeneratorOptions[ 'answer_post_type' ] ) && $key == $bimProtocolGeneratorOptions[ 'answer_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						<?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which answers are stored', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="question-page"><?php _e( 'Question page', 'bim-protocol-generator' ); ?></label>
				</td>
				<td>
					<select name="bim_protocol_generator_options[question_page]" id="question-page">
						<option value="-1"><?php _e( 'Custom URI', 'bim-protocol-generator' ); ?></option>
<?php
	foreach( $pages as $page ) {
?>					
						<option value="<?php print( $page->ID ); ?>"<?php print( ( isset( $bimProtocolGeneratorOptions[ 'question_page' ] ) && $bimProtocolGeneratorOptions[ 'question_page' ] == $page->ID ? ' selected' : '' ) ); ?>>
							<?php print( $page->post_title ); ?>
                    	</option>
<?php
   }
?>
				   </select>
				   <p class="description"><?php _e( 'Page which displays the questions, also used to pick the right language version of this page when using multiple languages', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="question-uri">Question URI</label></td>
				<td>
					<input type="text" id="question-uri" name="bim_protocol_generator_options[question_uri]" value="<?php print( isset( $bimProtocolGeneratorOptions[ 'question_uri' ] ) ? $bimProtocolGeneratorOptions[ 'question_uri' ] : '' ); ?>" />
					<p class="description"><?php _e( 'Set the relative URI here if you do not select a page in the option above. This should point to the page which displays single issues. If you only set this value multiple languages will not work, for that you should also set the questions page', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
<?php
$chapters = stripslashes( implode( ', ', isset( $bimProtocolGeneratorOptions[ 'report_chapters' ] ) ? $bimProtocolGeneratorOptions[ 'report_chapters' ] : Array() ) );
$informationLevels = stripslashes( implode( ', ', isset( $bimProtocolGeneratorOptions[ 'information_levels' ] ) ? $bimProtocolGeneratorOptions[ 'information_levels' ] : Array() ) );
if( count( $availableLanguages ) == 0 ) {
?>			
			<tr valign="top">
				<td><label for="disclaimer-text"><?php _e( 'Disclaimer text', 'bim-protocol-generator' ); ?></label></td>
				<td>
					<textarea cols="30" rows="4" id="disclaimer-text" name="bim_protocol_generator_options[disclaimer_text]"><?php print( isset( $bimProtocolGeneratorOptions[ 'disclaimer_text' ] ) ? stripslashes( $bimProtocolGeneratorOptions[ 'disclaimer_text' ] ) : '' ); ?></textarea>
					<p class="description"><?php _e( 'This disclaimer text is included in the generated execution plan.', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="definitions"><?php _e( 'Definitions', 'bim-protocol-generator' ); ?></label></td>
				<td>
					<textarea cols="30" rows="4" id="definitions" name="bim_protocol_generator_options[definitions]"><?php print( isset( $bimProtocolGeneratorOptions[ 'definitions' ] ) ? stripslashes( $bimProtocolGeneratorOptions[ 'definitions' ] ) : '' ); ?></textarea>
					<p class="description"><?php _e( 'The text included in the execution plan under the header definitions, it should contain definitions!', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="report-chapters"><?php _e( 'Report chapters', 'bim-protocol-generator' ); ?></label></td>
				<td>
					<textarea cols="30" rows="4" id="report-chapters" name="bim_protocol_generator_options[report_chapters]"><?php print( $chapters ); ?></textarea>
					<p class="description"><?php _e( 'Comma separated list of chapters for the report. The order used here will be maintained', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="information-levels"><?php _e( 'Information Levels', 'bim-protocol-generator' ); ?></label></td>
				<td>
					<textarea cols="30" rows="4" id="information-levels" name="bim_protocol_generator_options[information_levels]"><?php print( $informationLevels ); ?></textarea>
					<p class="description"><?php _e( 'Comma separated list of information levels, these have to be set for every used language.', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
<?php
} else {
	foreach( $availableLanguages as $key => $language ) {
		if( $key == $defaultLanguage ) {
			$suffix = '';
			$languageChapters = $chapters;
			$languageInformationLevels = $informationLevels;
		} else {
			$suffix = '_' . $key;
			$languageChapters = stripslashes( implode( ', ', isset( $bimProtocolGeneratorOptions[ 'report_chapters' . $suffix ] ) ? $bimProtocolGeneratorOptions[ 'report_chapters' . $suffix ] : Array() ) );
			$languageInformationLevels = stripslashes( implode( ', ', isset( $bimProtocolGeneratorOptions[ 'information_levels' . $suffix ] ) ? $bimProtocolGeneratorOptions[ 'information_levels' . $suffix ] : Array() ) );
		}
?>
			<tr valign="top">
				<td><label for="disclaimer-text"><?php _e( 'Disclaimer text', 'bim-protocol-generator' ); ?> (<?php print( $language[ 'display_name' ] ); ?>)</label></td>
				<td>
					<textarea cols="30" rows="4" id="disclaimer-text" name="bim_protocol_generator_options[disclaimer_text<?php print( $suffix ); ?>]"><?php print( isset( $bimProtocolGeneratorOptions[ 'disclaimer_text' . $suffix ] ) ? stripslashes( $bimProtocolGeneratorOptions[ 'disclaimer_text' . $suffix ] ) : '' ); ?></textarea>
					<p class="description"><?php _e( 'This disclaimer text is included in the generated reports for the participants and initiator of a protocol.', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="definitions"><?php _e( 'Definitions', 'bim-protocol-generator' ); ?> (<?php print( $language[ 'display_name' ] ); ?>)</label></td>
				<td>
					<textarea cols="30" rows="4" id="definitions" name="bim_protocol_generator_options[definitions<?php print( $suffix ); ?>]"><?php print( isset( $bimProtocolGeneratorOptions[ 'definitions' . $suffix ] ) ? stripslashes( $bimProtocolGeneratorOptions[ 'definitions' . $suffix ] ) : '' ); ?></textarea>
					<p class="description"><?php _e( 'The text included in the report under the header definitions, it should contain definitions!', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="report-chapters<?php print( $suffix ); ?>"><?php _e( 'Report chapters', 'bim-protocol-generator' ); ?> (<?php print( $language[ 'display_name' ] ); ?>)</label></td>
				<td>
					<textarea cols="30" rows="4" id="report-chapters<?php print( $suffix ); ?>" name="bim_protocol_generator_options[report_chapters<?php print( $suffix ); ?>]"><?php print( $languageChapters ); ?></textarea>
					<p class="description"><?php _e( 'Comma separated list of chapters for the report. The order used here will be maintained', 'bim-protocol-generator' ); ?></p>
					<p class="description"><?php _e( 'Make sure to show all chapters and put them in the same order for all languages', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td><label for="information-levels<?php print( $suffix ); ?>"><?php _e( 'Information Levels', 'bim-protocol-generator' ); ?> (<?php print( $language[ 'display_name' ] ); ?>)</label></td>
				<td>
					<textarea cols="30" rows="4" id="information-levels<?php print( $suffix ); ?>" name="bim_protocol_generator_options[information_levels<?php print( $suffix ); ?>]"><?php print( $languageInformationLevels ); ?></textarea>
					<p class="description"><?php _e( 'Comma separated list of information levels, these have to be set for every used language.', 'bim-protocol-generator' ); ?></p>
				</td>
			</tr>
<?php
	}
} 
?>			
			<tr valign="top">
				<td colspan="2">
					<p class="submit">
						<input class="button-primary" type="submit" name="action" value="update" />
					</p>
				</td>
			</tr>
		</table>
	</form>
</div>
