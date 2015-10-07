<?php
global $bimQualityBlocks, $wpdb;

use BIMQualityBlocks\BIMQualityBlocks;

$message = '';

if( isset( $_POST['action'] ) && $_POST[ 'update' ] == 'update' ) {
	$options = BIMQualityBlocks::getOptions();

	foreach( $_POST[ 'bim_quality_blocks_options' ] AS $key => $newOption ) {
		$options[$key] = $newOption;
	}
	 
	update_option( 'bim_quality_blocks_options', $options );
}
if( isset( $_POST['delete_confirm'], $_POST['delete'] ) && $_POST['delete_confirm'] == 'confirmed' ) {
   $blocks = BIMQualityBlocks::getQualityBlocks();
   foreach( $blocks as $block ) {
      wp_delete_post( $block->post->ID );
   }
   print( '<div class="notice">' . __( 'All blocks deleted', 'bim-quality-blocks' ) . '</div>' );
}
if( isset( $_POST['import'], $_FILES['csv'], $_FILES['csv']['tmp_name'] ) && $_FILES['csv']['tmp_name'] != '' ) {
   $importStats = BIMQualityBlocks::import( $_FILES['csv']['tmp_name'] );
}

$bimQualityBlocksOptions = BIMQualityBlocks::getOptions( true );
$postTypes = get_post_types( Array(), 'objects' );
$taxonomies = get_taxonomies();
$pages = get_posts( Array(
		'post_type' => 'page',
		'posts_per_page' => -1
) );
?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2><?php _e( 'BIM Quality Blocks Levels Options', 'bim-quality-blocks' ); ?></h2>
   <?php
   if( isset( $importStats ) ) {
      print( '<div class="notice">' . __( 'Blocks imported', 'bim-quality-blocks' ) . ': ' . number_format( $importStats['blocks'] ) . '</div>' );
   }
   ?>
	<form method="post" enctype="multipart/form-data" action="">
		<table class="form-table">
			<tr valign="top">
				<td><label for="bim-quality-blocks-post-type"><?php _e( 'BIM Quality Blocks post type', 'bim-quality-blocks' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="bim_quality_blocks_options[bim_quality_blocks_post_type]" id="bim-quality-blocks-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print(
                     ( ( isset( $bimQualityBlocksOptions[ 'bim_quality_blocks_post_type' ] ) && $key == $bimQualityBlocksOptions[ 'bim_quality_blocks_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						      <?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which BIM Quality Blocks are stored', 'bim-quality-blocks' ); ?></p>
				</td>
			</tr>
         <tr valign="top">
            <td><label for="private-bim-quality-blocks-post-type"><?php _e( 'Private BIM Quality Blocks post type', 'bim-quality-blocks' ); ?></label></td>
            <td>
               <?php
               if( is_array( $postTypes ) ) {
                  ?>
                  <select name="bim_quality_blocks_options[private_bqblocks_post_type]" id="private-bim-quality-blocks-post-type">
                     <?php
                     foreach( $postTypes AS $key => $postType ) {
                        ?>
                        <option value="<?php print( $key ); ?>" <?php print(
                        ( ( isset( $bimQualityBlocksOptions[ 'private_bqblocks_post_type' ] ) && $key == $bimQualityBlocksOptions[ 'private_bqblocks_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
                           <?php print( $postType->labels->name ); ?>
                        </option>
                     <?php
                     }
                     ?>
                  </select>
               <?php
               }
               ?>
               <p class="description"><?php _e( 'The post type in which private BIM Quality Blocks are stored', 'bim-quality-blocks' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="bim-quality-blocks-report-post-type"><?php _e( 'BIM Quality Blocks report post type', 'bim-quality-blocks' ); ?></label></td>
            <td>
               <?php
               if( is_array( $postTypes ) ) {
                  ?>
                  <select name="bim_quality_blocks_options[report_post_type]" id="bim-quality-blocks-report-post-type">
                     <?php
                     foreach( $postTypes AS $key => $postType ) {
                        ?>
                        <option value="<?php print( $key ); ?>" <?php print(
                        ( ( isset( $bimQualityBlocksOptions[ 'report_post_type' ] ) && $key == $bimQualityBlocksOptions[ 'report_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
                           <?php print( $postType->labels->name ); ?>
                        </option>
                     <?php
                     }
                     ?>
                  </select>
               <?php
               }
               ?>
               <p class="description"><?php _e( 'The post type in which BIM Quality Blocks are stored', 'bim-quality-blocks' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="bim-quality-blocks-xml-header"><?php _e( 'BIM Quality Blocks XML header', 'bim-quality-blocks' ); ?></label></td>
            <td>
               <textarea name="bim_quality_blocks_options[xml_header]" id="bim-quality-blocks-xml-header"><?php print( isset( $bimQualityBlocksOptions['xml_header'] ) ? $bimQualityBlocksOptions['xml_header'] : '' ); ?></textarea>
               <p class="description"><?php _e( 'The generated report can include an XML file, this is the header of that XML file', 'bim-quality-blocks' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="bim-quality-blocks-xml-footer"><?php _e( 'BIM Quality Blocks XML footer', 'bim-quality-blocks' ); ?></label></td>
            <td>
               <textarea name="bim_quality_blocks_options[xml_footer]" id="bim-quality-blocks-xml-footer"><?php print( isset( $bimQualityBlocksOptions['xml_footer'] ) ? $bimQualityBlocksOptions['xml_footer'] : '' ); ?></textarea>
               <p class="description"><?php _e( 'The generated report can include an XML file, this is the footer of that XML file', 'bim-quality-blocks' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td colspan="2">
               <p class="submit">
                  <input class="button-primary" type="submit" name="update" value="<?php _e( 'Update', 'bim-quality-blocks' ); ?>" />
               </p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="import-csv"><?php _e( 'BIM Quality Blocks XML footer', 'bim-quality-blocks' ); ?></label></td>
            <td>
               <input type="file" name="csv" id="import-csv" />
               <p class="description"><?php _e( 'Import a CSV file containing blocks, delimiter=, and enclosure="', 'bim-quality-blocks' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td colspan="2">
               <p class="submit">
                  <input class="button-primary" type="submit" name="import" value="<?php _e( 'Import CSV', 'bim-quality-blocks' ); ?>" />
               </p>
            </td>
         </tr>
         <tr valign="top">
            <td colspan="2">
               <p class="submit">
                  <input type="checkbox" id="delete-confirm" name="delete_confirm" value="confirmed" /> <label for="delete-confirm"><?php _e( 'Confirm deleting all blocks and their related data from the database', 'bim-quality-blocks' ); ?></label><br />
                  <em><?php _e( 'Note: Can not be undone!', 'bim-quality-blocks' ); ?></em><br />
                  <input class="button-primary" type="submit" name="delete" value="<?php _e( 'Delete data', 'bim-quality-blocks' ); ?>" />
               </p>
            </td>
         </tr>
		</table>
	</form>
</div>
