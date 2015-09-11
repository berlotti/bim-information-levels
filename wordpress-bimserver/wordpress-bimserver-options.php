<?php
global $wordPressBimserver, $wpdb;

use WordPressBimserver\WordPressBimserver;

$message = '';

if( isset( $_POST['action'] ) && $_POST[ 'action' ] == 'update' ) {
	$options = WordPressBimserver::getOptions();

	foreach( $_POST[ 'wordpress_bimserver_options' ] AS $key => $newOption ) {
		$options[$key] = $newOption;
	}
	 
	update_option( 'wordpress_bimserver_options', $options );
}
if( isset( $_POST['delete_confirm'], $_POST['delete'] ) && $_POST['delete_confirm'] == 'confirmed' ) {
   print( '<div class="status">' . __( 'All blocks deleted', 'wordpress-bimserver' ) . '</div>' );
}
if( isset( $_POST['import'] ) ) {
   $importStats = Array();
   $file = fopen( $_FILES['csv'], 'r' );
   while( ( $data = fgetcsv( $file ) ) !== false ) {
      // TODO: import stuff from CSV
   }
   fclose( $handle );
}


$wordPressBimserverOptions = WordPressBimserver::getOptions( true );

// ServiceInterface.getAllLocalServiceDescriptors
// TODO: create an admin user and gather all service descriptors

if( isset( $wordPressBimserverOptions['url'] ) && $wordPressBimserverOptions['url'] != '' ) {
   try {
      $bimserver = new \WordPressBimserver\BimServerApi( $wordPressBimserverOptions['url'] );
      $bimserverServices = $bimserver->apiCall( 'ServiceInterface', 'getAllLocalServiceDescriptors' );
   } catch( \Exception $e ) {
      print( '<div class="error">' . __( 'Could not retrieve services from Bimserver, check the configured URL. Message', 'wordpress-bimserver' ) . ': ' . $e->getMessage() . '</div>' );
      $bimserverServices = Array();
   }
   var_dump( $bimserverServices );
} else {
   $bimserverServices = Array();
}

$postTypes = get_post_types( Array(), 'objects' );
$taxonomies = get_taxonomies();
$pages = get_posts( Array(
		'post_type' => 'page',
		'posts_per_page' => -1
) );
?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2><?php _e( 'WordPress and Bimserver Options', 'wordpress-bimserver' ); ?></h2>
   <?php
   if( isset( $importStats ) ) {
      // TODO: show
   }
   ?>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<!--tr valign="top">
				<td><label for="wordpress-bimserver-post-type"><?php _e( 'BIM Quality Blocks post type', 'wordpress-bimserver' ); ?></label></td>
				<td>
<?php
if( is_array( $postTypes ) ) {
?>
                  <select name="wordpress_bimserver_options[bim_quality_blocks_post_type]" id="wordpress-bimserver-post-type">
<?php
   foreach( $postTypes AS $key => $postType ) {
?>
                     <option value="<?php print( $key ); ?>" <?php print(
                     ( ( isset( $wordPressBimserverOptions[ 'bim_quality_blocks_post_type' ] ) && $key == $wordPressBimserverOptions[ 'bim_quality_blocks_post_type' ] ) ? ' selected="selected"' : '' ) ); ?>>
						      <?php print( $postType->labels->name ); ?>
                     </option>
<?php
   }
?>
				   </select>
<?php
}
?>
					<p class="description"><?php _e( 'The post type in which BIM Quality Blocks are stored', 'wordpress-bimserver' ); ?></p>
				</td>
			</tr-->
         <tr valign="top">
            <td><label for="wordpress-bimserver-url"><?php _e( 'Bimserver url', 'wordpress-bimserver' ); ?></label></td>
            <td>
               <input type="text" name="wordpress_bimserver_options[url]" id="wordpress-bimserver-url" value="<?php print( isset( $wordPressBimserverOptions['url'] ) ? $wordPressBimserverOptions['url'] : '' ); ?>" />
               <p class="description"><?php _e( 'The URL of the Bimserver which we should connect to', 'wordpress-bimserver' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="wordpress-bimserver-service-id"><?php _e( 'Bimserver service ID', 'wordpress-bimserver' ); ?></label></td>
            <td>
               <input type="text" name="wordpress_bimserver_options[service_id]" id="wordpress-bimserver-service-id" value="<?php print( isset( $wordPressBimserverOptions['service_id'] ) ? $wordPressBimserverOptions['service_id'] : '' ); ?>" />
               <p class="description"><?php _e( 'The service ID of the service of the service on the Bimserver which we need to trigger', 'wordpress-bimserver' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="wordpress-bimserver-new-project"><?php _e( 'Each upload is a new project', 'wordpress-bimserver' ); ?></label></td>
            <td>
               <select name="wordpress_bimserver_options[new_project]" id="wordpress-bimserver-new-project">
                  <option value="yes"<?php print( ( isset( $wordPressBimserverOptions['new_project'] ) && $wordPressBimserverOptions['new_project'] == 'yes' ) ? ' selected' : '' ); ?>><?php _e( 'Yes', 'wordpress-bimserver' ); ?></option>
                  <option value="no"<?php print( ( isset( $wordPressBimserverOptions['new_project'] ) && $wordPressBimserverOptions['new_project'] == 'no' ) ? ' selected' : '' ); ?>><?php _e( 'No', 'wordpress-bimserver' ); ?></option>
               </select>
               <p class="description"><?php _e( 'If set to yes, for each upload a new project is created, if set to no it is all added to the same project', 'wordpress-bimserver' ); ?></p>
            </td>
         </tr>
         <tr valign="top">
            <td><label for="wordpress-bimserver-new-revision"><?php _e( 'Each upload is a new revision', 'wordpress-bimserver' ); ?></label></td>
            <td>
               <select name="wordpress_bimserver_options[new_revision]" id="wordpress-bimserver-new-revision">
                  <option value="yes"<?php print( ( isset( $wordPressBimserverOptions['new_revision'] ) && $wordPressBimserverOptions['new_revision'] == 'yes' ) ? ' selected' : '' ); ?>><?php _e( 'Yes', 'wordpress-bimserver' ); ?></option>
                  <option value="no"<?php print( ( isset( $wordPressBimserverOptions['new_revision'] ) && $wordPressBimserverOptions['new_revision'] == 'no' ) ? ' selected' : '' ); ?>><?php _e( 'No', 'wordpress-bimserver' ); ?></option>
               </select>
               <p class="description"><?php _e( 'If set to yes, for each upload a new revision is return, if set to no the result data will be added as extended data', 'wordpress-bimserver' ); ?></p>
            </td>
         </tr>
			<tr valign="top">
				<td colspan="2">
					<p class="submit">
						<input class="button-primary" type="submit" name="action" value="update" />
					</p>
				</td>
			</tr>
         <!--tr valign="top">
            <td colspan="2">
               <p class="submit">
                  <input type="checkbox" id="delete-confirm" name="delete_confirm" value="confirmed" /> <label for="delete-confirm"><?php _e( 'Confirm deleting all blocks and their related data from the database', 'wordpress-bimserver' ); ?></label><br />
                  <em><?php _e( 'Note: Can not be undone!', 'wordpress-bimserver' ); ?></em><br />
                  <input class="button-primary" type="submit" name="delete" value="<?php _e( 'Delete data', 'wordpress-bimserver' ); ?>" />
               </p>
            </td>
         </tr-->
		</table>
	</form>
</div>
