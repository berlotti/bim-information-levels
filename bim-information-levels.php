<?php
/*
Plugin Name: BIM Information Levels
Plugin URI:
Description:
Version: 1.0
Author: Bastiaan Grutters
Author URI: http://www.bastiaangrutters.nl
*/

/*
 * Usage: Place shortcodes in pages:
 * [showBIMInformationLevels]
 * [showBIMInformationLevelsProperties]
 * [showBIMInformationLevelsByLevel]
 * [showBIMInformationLevelsLevels]
 * [showBIMInformationLevelsReport] 
 */

class BIMInformationLevels {
	private $options;

	public function __construct() {
		register_activation_hook( __FILE__, Array( 'BIMInformationLevels', 'install' ) );
		register_deactivation_hook( __FILE__, Array( 'BIMInformationLevels', 'uninstall' ) );
		
		add_action( 'admin_menu', Array( 'BIMInformationLevels', 'optionsMenu' ) );

		$this->options = get_option( 'bim_information_levels_options', Array() );
		
		if( isset( $this->options[ 'bim_property_post_type' ] ) && isset( $this->options[ 'bim_object_post_type' ] ) ) {
			add_action( 'admin_init', Array( 'BIMInformationLevels', 'editorInit' ) );
		}

		add_action( 'admin_enqueue_scripts', Array( 'BIMInformationLevels', 'adminEnqueueScripts' ) );
		add_action( 'wp_enqueue_scripts', Array( 'BIMInformationLevels', 'wpEnqueueScripts' ) );
		add_action( 'delete_post', Array( 'BIMInformationLevels', 'deletePost' ) );
		
		// Add post types etc at the WordPress init action
		add_action( 'init', Array( 'BIMInformationLevels', 'wordPressInit' ) );
		
		// --- Add shortcodes ---
		add_shortcode( 'showBIMInformationLevels', Array( 'BIMInformationLevels', 'showBIMInformationLevels' ) );
		add_shortcode( 'showBIMInformationLevelsProperties', Array( 'BIMInformationLevels', 'showBIMInformationLevelsProperties' ) );
		add_shortcode( 'showBIMInformationLevelsByLevel', Array( 'BIMInformationLevels', 'showBIMInformationLevelsByLevel' ) );
		add_shortcode( 'showBIMInformationLevelsLevels', Array( 'BIMInformationLevels', 'showBIMInformationLevelsLevels' ) );
		add_shortcode( 'showBIMInformationLevelsReport', Array( 'BIMInformationLevels', 'showBIMInformationLevelsReport' ) );
		
		// Add filters for search
		add_filter( 'the_title', Array( 'BIMInformationLevels', 'theTitleSearch' ), 10, 2 );
		add_filter( 'the_permalink', Array( 'BIMInformationLevels', 'thePermalinkSearch' ) );
		add_filter( 'the_excerpt', Array( 'BIMInformationLevels', 'theExcerptSearch' ) );
		add_filter( 'comments_open', Array( 'BIMInformationLevels', 'commentsOpen' ), 999, 2 );
	}
	 
	public static function optionsMenu() {
		$pfile = basename( dirname( __FILE__ ) ) . '/bim-information-levels-options.php';
		add_options_page( __( 'BIM Information Levels Options', 'bim-information-levels' ), __( 'BIM Information Levels Options', 'bim-information-levels' ), 'activate_plugins', $pfile );
	}
	 
	public static function adminEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
		//wp_enqueue_script( 'bim-information-levels-admin', plugins_url( 'bim-information-levels-admin.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
	}

	public static function wpEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-cookie', plugins_url( 'jquery.cookie.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
		wp_enqueue_script( 'bim-information-levels', plugins_url( 'bim-information-levels.js', __FILE__ ), Array( 'jquery', 'jquery-cookie' ), "1.0", true );
		wp_enqueue_style( 'bim-information-levels', plugins_url( 'bim-information-levels.css', __FILE__ ) );
	}

	public static function editorInit() {
		$options = BIMInformationLevels::getOptions();
		add_meta_box( 'bim-information-levels-meta', __( 'Information Level Options', 'bim-information-levels' ), Array( 'BIMInformationLevels', 'editorWidget' ), $options[ 'bim_object_post_type' ], 'normal', 'high' );
		add_meta_box( 'bim-information-levels-meta', __( 'Information Level Options', 'bim-information-levels' ), Array( 'BIMInformationLevels', 'editorWidget' ), $options[ 'bim_property_post_type' ], 'normal', 'high' );
		add_action( 'save_post', Array( 'BIMInformationLevels', 'saveEditorWidget' ) );
	}	
    
	public static function getOptions( $forceReload = false ) {
		global $bimInformationLevels;
   		if( $forceReload ) {
			$bimInformationLevels->options = get_option( 'bim_information_levels_options', Array() );
		}
		return $bimInformationLevels->options;
	}
	
	public static function wordPressInit() {
		$options = BIMInformationLevels::getOptions();
		// Change rewrite rules
		if( isset( $options[ 'information_levels_uri' ] ) && $options[ 'information_levels_uri' ] != '' ) {
			if( substr( $options[ 'information_levels_uri' ], 0, 1 ) == '/' ) {
				$uri = substr( $options[ 'information_levels_uri' ], 1 );
			} else {
				$uri = $options[ 'information_levels_uri' ];
			}
			add_rewrite_tag( '%bim_object%', '([^&]+)' );
			add_rewrite_tag( '%bim_category_id%', '([^&]+)' );
			add_rewrite_rule( '^' . $uri . '([^/]*)/([^/]*)/?', 'index.php?page_id=' . $options[ 'information_levels_page' ] . '&bim_object=$matches[1]&bim_category_id=$matches[2]', 'top' );
			//var_dump( '^' . $uri . '([^/]*)/([^/]*)/?', 'index.php?page_id=' . $options[ 'information_levels_page' ] . '&object=$matches[1]&id=$matches[2]' );
		}
		
		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'BIM objects', 'post type general name' ),
						'singular_name' => _x( 'BIM object', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New BIM object' ),
						'edit_item' => __( 'Edit BIM object' ),
						'new_item' => __( 'New BIM object' ),
						'all_items' => __( 'All BIM objects' ),
						'view_item' => __( 'View BIM object' ),
						'search_items' => __( 'Search BIM objects' ),
						'not_found' =>  __( 'No BIM objects found' ),
						'not_found_in_trash' => __( 'No BIM objects found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'BIM objects' ),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields', 'comments' )
		);
		register_post_type( 'bim_object', $postTypeArguments );
	
		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'BIM property', 'post type general name' ),
						'singular_name' => _x( 'BIM property', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New BIM property' ),
						'edit_item' => __( 'Edit BIM property' ),
						'new_item' => __( 'New BIM property' ),
						'all_items' => __( 'All BIM properties' ),
						'view_item' => __( 'View BIM property' ),
						'search_items' => __( 'Search BIM properties' ),
						'not_found' =>  __( 'No BIM property found' ),
						'not_found_in_trash' => __( 'No BIM properties found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'BIM properties' ),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => false,
				'rewrite' => false,
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields' )
		);
		register_post_type( 'bim_property', $postTypeArguments );
		
		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'Information Level', 'post type general name' ),
						'singular_name' => _x( 'Information Level', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New Information Level' ),
						'edit_item' => __( 'Edit Information Level' ),
						'new_item' => __( 'New Information Level' ),
						'all_items' => __( 'All Information Levels' ),
						'view_item' => __( 'View Information Level' ),
						'search_items' => __( 'Search Information Levels' ),
						'not_found' =>  __( 'No Information Levels found' ),
						'not_found_in_trash' => __( 'No Information Levels found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'Information Levels' ),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' )
		);
		register_post_type( 'information_level', $postTypeArguments );
		
		$arguments = Array(
				'hierarchical'      => true,
				'labels'            => Array(
					'name'              => _x( 'BIM object categories', 'taxonomy general name' ),
					'singular_name'     => _x( 'BIM object category', 'taxonomy singular name' ),
					'search_items'      => __( 'Search BIM object categories' ),
					'all_items'         => __( 'All BIM object categories' ),
					'parent_item'       => __( 'Parent BIM object category' ),
					'parent_item_colon' => __( 'Parent BIM object category:' ),
					'edit_item'         => __( 'Edit BIM object category' ),
					'update_item'       => __( 'Update BIM object category' ),
					'add_new_item'      => __( 'Add New BIM object category' ),
					'new_item_name'     => __( 'New BIM object category Name' ),
					'menu_name'         => __( 'BIM object category' ),
				),
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'bim-object-category' )
		);
		
		register_taxonomy( 'bim_object_category', Array( 'bim_object', 'bim_property' ), $arguments );
	}
	
	public static function install() {
		global $wpdb;
		$tableName = $wpdb->prefix . 'property_information_level';
		$sql = "CREATE TABLE $tableName (
			property_information_level_id int(14) NOT NULL AUTO_INCREMENT,
			object_id int(14) NOT NULL,
			property_id int(14) NOT NULL,
			information_level_id int(14) NOT NULL,
			UNIQUE KEY property_information_level_id (property_information_level_id),
			INDEX `object_id` ( `object_id` ),
			INDEX `property_id` ( `property_id` ),
			INDEX `information_level_id` ( `information_level_id` )
		)";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	public static function uninstall() {
		// do we want to delete all information here... maybe not, could be dangerous
		// For now we leave it as it is, there is a button in the plugin options to delete all data
	}
	
	public static function deletePost( $postId ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}property_information_level WHERE property_id = %d", $postId ) );
	}
	
	public static function hasLevels( $objectId, $levels, $propertyId = -1 ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( property_information_level_id ) 
				FROM {$wpdb->prefix}property_information_level 
				WHERE object_id = %d AND property_id = %d AND information_level_id IN ( " . implode( ', ', $levels ) . " )", $objectId, $propertyId ) ) > 0;
	}
	
	public static function getItemInformationLevels( $objectId, $propertyId = -1 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}property_information_level 
			WHERE object_id = %d AND property_id = %d", $objectId, $propertyId ) );
	}
	
	public static function editorWidget() {
		global $post, $wpdb;
		
		$informationLevels = BimInformationLevels::getInformationLevels();
		$options = BIMInformationLevels::getOptions();
		
		/*
		 * Display added property editor options
		 */
		if( $post->post_type == $options[ 'bim_property_post_type' ] ) {
			$objects = get_posts( Array(
					'post_type' => $options[ 'bim_object_post_type' ],
					'orderby' => 'title',
					'order' => 'ASC',
					'numberposts' => -1
			) );
			$objectIds = get_post_meta( $post->ID, '_object_id' );
?>
		<div id="bim-object-container">
<?php
			$count = 0;
			foreach( $objectIds as $objectId ) {
				$itemInformationLevels = BimInformationLevels::getItemInformationLevels( $objectId, $post->ID );
?>
			<label for="linked-object-id-<?php print( $count ); ?>"><?php _e( 'Property of object', 'bim-information-levels' ); ?></label> <select name="_object_id[]" id="linked-object-id-<?php print( $count ); ?>">
				<option value=""><?php _e( 'None', 'bim-information-levels' ); ?></option>
<?php
				foreach( $objects as $object ) {
?>
				<option value="<?php print( $object->ID ); ?>"<?php print( $objectId == $object->ID ? ' selected' : '' ); ?>><?php print( $object->post_title ); ?></option>
<?php
				}
?>
			</select><br />
			<div id="bim-information-levels-container">
				<h4><?php _e( 'Information Levels', 'bim-information-levels' ); ?></h4>
<?php
				foreach( $informationLevels as $informationLevel ) {
					$checked = false;
					foreach( $itemInformationLevels as $itemInformationLevel ) {
						if( $itemInformationLevel->information_level_id == $informationLevel->ID ) {
							$checked = true;
							break;
						}
					}
?>
				<input type="checkbox" <?php print( $checked ? 'checked ' : '' ); ?>value="true" name="information_level_<?php print( $objectId ); ?>_<?php print( $informationLevel->information_level ); ?>" id="information-level-<?php print( $objectId ); ?>-<?php print( $informationLevel->information_level ); ?>" /> <label for="information-level-<?php print( $objectId ); ?>-<?php print( $informationLevel->information_level ); ?>"><?php print( $informationLevel->information_level ); ?> - <?php print( $informationLevel->post_title ); ?></label><br />
<?php
					$count ++;
				}
?>
			</div><br />
<?php
			}
?>
			<h4><?php _e( 'Add to object', 'bim-information-levels' ); ?></h4>
			<label for="add-property-to-object"><?php _e( 'Add this property to another object', 'bim-information-levels' ); ?></label> <select name="_object_id[]" id="add-property-to-object">
				<option value=""><?php _e( 'No', 'bim-information-levels' ); ?></option>
<?php
				foreach( $objects as $object ) {
?>
				<option value="<?php print( $object->ID ); ?>"><?php print( $object->post_title ); ?></option>
<?php
				}
?>				
			</select>
			<br />
		</div>
<?php
		/*
		 * Display added object editor options
		 */
		} else {
			$properties = $wpdb->get_results( $wpdb->prepare( "SELECT post_id 
				FROM $wpdb->postmeta 
				WHERE meta_key = '_object_id' AND meta_value = %d", $post->ID ) );
			$itemInformationLevels = BimInformationLevels::getItemInformationLevels( $post->ID );
?>
		<div id="bim-information-levels-container">
			<h4><?php _e( 'Information Levels', 'bim-information-levels' ); ?></h4>
<?php
			foreach( $informationLevels as $informationLevel ) {
				$checked = false;
				foreach( $itemInformationLevels as $itemInformationLevel ) {
					if( $itemInformationLevel->information_level_id == $informationLevel->ID ) {
						$checked = true;
						break;
					}
				}
?>
			<input type="checkbox" <?php print( $checked ? 'checked ' : '' ); ?>value="true" name="information_level_<?php print( $informationLevel->information_level ); ?>" id="information-level-<?php print( $informationLevel->information_level ); ?>" /> <label for="information-level-<?php print( $informationLevel->information_level ); ?>"><?php print( $informationLevel->information_level ); ?> - <?php print( $informationLevel->post_title ); ?></label><br />
<?php
			}
?>
		</div>
		<div id="bim-object-properties">
			<h4><?php _e( 'Properties', 'bim-information-levels' ); ?></h4>
<?php 
			foreach( $properties as $propertyId ) {
				$property = get_post( $propertyId->post_id );
?>
			<div class="property" id="bim-property-<?php print( $propertyId->post_id ); ?>"><a href="<?php print( get_edit_post_link( $property->ID ) ); ?>"><?php print( $property->post_title ); ?></a></div>
<?php
			}
?>			
		</div>
<?php
		}
?>
		<input type="hidden" name="bim_information_levels_noncename" value="<?php print( wp_create_nonce(__FILE__) ); ?>" />
<?php
	}
		
	public static function saveEditorWidget( $postId ) {
		global $wpdb;
		if( !isset( $_POST[ 'bim_information_levels_noncename' ] ) || !wp_verify_nonce( $_POST[ 'bim_information_levels_noncename' ], __FILE__ ) ) {
			return $postId;
		}
		if ( !current_user_can( 'edit_post', $postId ) ) {
			return $postId;
		}

    $post = get_post( $postId );
    $options = BIMInformationLevels::getOptions();
      
    if( $post->post_type == $options[ 'bim_property_post_type' ] || $post->post_type == $options[ 'bim_object_post_type' ] ) {
			if( $post->post_type == $options[ 'bim_property_post_type' ] ) {
				$propertyId = $post->ID;
				$objectIds = Array();
				if( isset( $_POST[ '_object_id' ] ) && is_array( $_POST[ '_object_id' ] ) ) {
					delete_post_meta( $postId, '_object_id' );
					foreach( $_POST[ '_object_id' ] as $objectId ) {
						if( ctype_digit( $objectId ) ) {
							add_post_meta( $postId, '_object_id', $objectId );
							$objectIds[] = $objectId;
						}
					}
				}
			} else {
				$objectIds = Array( $postId );
				$propertyId = -1;
			}
			$informationLevels = BimInformationLevels::getInformationLevels();
			
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}property_information_level WHERE object_id = %d AND property_id = %d", $objectId, $propertyId ) );
			$sql = "INSERT INTO {$wpdb->prefix}property_information_level\n
					( `object_id`, `property_id`, `information_level_id` )\n
					VALUES ";
			$count = 0;
			foreach( $objectIds as $objectId ) {
				$ids = Array();
				foreach( $informationLevels as $informationLevel ) {
					if( ( isset( $_POST[ 'information_level_' . $informationLevel->information_level ] ) && $_POST[ 'information_level_' . $informationLevel->information_level ] == 'true' ) || 
							( isset( $_POST[ 'information_level_' . $objectId . '_' . $informationLevel->information_level ] ) && $_POST[ 'information_level_' . $objectId . '_' . $informationLevel->information_level ] == 'true' ) ) {
						$ids[] = $informationLevel->ID;
					}
				}
				foreach( $ids as $id ) {
					if( $count > 0 ) {
						$sql .= ', ';
					}
					$sql .= "( $objectId, $propertyId, $id )";
					$count ++;
				}
			}
			if( $count > 0 ) {
				$wpdb->query( $sql );
			}
		}
		return $postId;
	}
   
	public static function getInformationLevels()  {
		$options = BimInformationLevels::getOptions();
		$informationLevels = get_posts( Array(
				'post_type' => $options[ 'information_level_post_type' ],
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'meta_key' => 'information_level',
				'numberposts' => -1
		) );
		foreach( $informationLevels as $key => $informationLevel ) {
			$informationLevels[$key]->information_level = get_post_meta( $informationLevel->ID, 'information_level', true );
		}
		return $informationLevels;
	}
	
	public static function showBIMInformationLevels() {
		global $wp_query, $wp_rewrite;
		$options = BIMInformationLevels::getOptions();
		$informationLevels = BIMInformationLevels::getInformationLevels();
		
		$parent = ( isset( $_GET[ 'id'] ) && ctype_digit( $_GET[ 'id' ] ) ) ? $_GET[ 'id' ] : 0;
		$objectId = ( isset( $_GET[ 'object'] ) && ctype_digit( $_GET[ 'object' ] ) ) ? $_GET[ 'object' ] : 0;
		$propertyId = ( isset( $_GET[ 'property'] ) && ctype_digit( $_GET[ 'property' ] ) ) ? $_GET[ 'property' ] : 0;
		if( isset( $_COOKIE[ 'filter_levels' ] ) ) {
			$selectedLevels = explode( ',', $_COOKIE[ 'filter_levels' ] );
			array_walk( $selectedLevels, 'intval' );
		} else {
			$selectedLevels = Array();
			foreach( $informationLevels as $informationLevel ) {
				$selectedLevels[] = $informationLevel->ID;
			}
		}
		
		if( $objectId == 0 && $propertyId == 0 ) {
			$topics = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => 0 ) );
			foreach( $topics as $key => $topic ) {
				$topics[$key]->children = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => $topic->term_id ) );
			}
			$settings = Array(
					'ajaxUrl' => plugins_url( 'ajax-callback.php', __FILE__ ),
					'mainTopics' => $topics,
					'text' => Array(
							'all' => __( 'all', 'bim-information-levels' ),
							'topicLabel' => __( 'Topic', 'bim-information-levels' ),
							'subTopicLabel' => __( 'Sub topic', 'bim-information-levels' ),
							'trash' => __( 'Remove', 'bim-information-levels' )
					),
					'type' => 'list'
			);
			$bimCategories = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => $parent ) );
			if( $parent != 0 ) {
				$parentTerm = get_term( $parent, $options[ 'bim_object_category_taxonomy' ] );
?>
			<a href="?"><?php _e( 'To overview', 'bim-information-levels' );?></a><br />
			<br />
			<h3 id="parent-term-<?php print( $parent ); ?>" class="parent-term"><?php print( $parentTerm->name ); ?></h3>
<?php
			}
?>
			<h3><?php _e( 'Show objects by information level(s)', 'bim-information-levels' ); ?> <a id="toggle-information-level-container" class="toggle-link" href="javascript:void( null );" onclick="BIMInformationLevels.toggleBox( this );">+</a></h3>
			<div id="information-level-container">
<?php
			foreach( $informationLevels as $informationLevel ) {
?>
			<input type="checkbox" class="filter-checkbox"<?php print( in_array( $informationLevel->ID, $selectedLevels ) ? ' checked' : '' ); ?> value="<?php print( $informationLevel->ID ); ?>" id="information-level-<?php print( $informationLevel->ID ); ?>" /> <label for="information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $informationLevel->post_title ); ?></label><br />
<?php
			}
?>	
			</div>
			<h3><?php _e( 'Show object with topics', 'bim-information-levels' ); ?> <a id="toggle-report-control-panel" class="toggle-link" href="javascript:void( null );" onclick="BIMInformationLevels.toggleBox( this );">+</a></h3>
			<div id="report-control-panel">
				<div class="content"></div>
				<a href="javascript:void( null );" onclick="BIMInformationLevels.addReportOptionRow();"><?php _e( 'add specific topic', 'bim-information-levels' ); ?></a><br />
			</div>
			<br />
			<script type="text/javascript">
				var informationLevelsControlSettings = <?php print( json_encode( $settings ) ); ?>;
			</script>
<?php
			foreach( $bimCategories as $bimCategory ) {
				$objects = get_posts( Array( 
					'post_type' => $options[ 'bim_object_post_type' ],
					'post_status' => 'publish',
					'tax_query' => Array(
						Array(
							'taxonomy' => $options[ 'bim_object_category_taxonomy' ],
							'field' => 'id',
							'terms' => $bimCategory->term_id
						)
					),
					'meta_key' => 'code',
					'numberposts' => -1,
					'orderby' => 'meta_value title',
					'order' => 'ASC'
				) );
?>
			<div class="bim-object-category<?php print( $parent != 0 ? ( ' parent-topic-' . $parent ) : '' ); ?>" id="topic-<?php print( $bimCategory->term_id ); ?>">
				<h4><a href="?id=<?php print( $bimCategory->term_id ); ?>"><?php print( $bimCategory->name ); ?></a></h4>
<?php
				if( count( $objects ) > 0 ) {
					$count = 0;
?>
				<table class="bim-information-level-table">
					<tr class="odd">
						<th class="numeric"><?php _e( 'NlSfb Code', 'bim-information-levels' ); ?></th>
						<th><?php _e( 'Object', 'bim-information-levels' ); ?></th>
						<th><?php _e( 'Unit', 'bim-information-levels' ); ?></th>
						<th><?php _e( 'ifcEquivalent', 'bim-information-levels' ); ?></th>
						<th><?php _e( 'BSDD GUID', 'bim-information-levels' ); ?></th>
						<th><?php _e( 'CBNL ID', 'bim-information-levels' ); ?></th>
<?php 
					foreach( $informationLevels as $informationLevel ) {
?>
						<th><?php print( '<span class="information-level">' . $informationLevel->information_level . '</span>' ); ?></th>
<?php
					}
?>
					</tr>
<?php 
					foreach( $objects as $object ) {
						$objectInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID );
						$terms = wp_get_object_terms( $object->ID, $options[ 'bim_object_category_taxonomy' ], Array( 'fields' => 'ids' ) );
						$specificTerm = 'none';
						$termParentId = -1;
						foreach( $terms as $term ) {
							if( $term != $bimCategory->term_id ) {
								$specificTerm = $term;
								break 1;
							}
						}
?>
					<tr class="item-row <?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?> <?php print( 'row-sub-topic-' . $specificTerm ); ?>">
						<td class="numeric"><?php print( get_post_meta( $object->ID, 'code', true ) ); ?></td>
						<td><a href="?object=<?php print( $object->ID ); ?>&id=<?php print( $parent ); ?>"><?php print( $object->post_title ); ?></a></td>
						<td><?php print( get_post_meta( $object->ID, 'unit', true ) ); ?></td>
						<td><?php print( get_post_meta( $object->ID, 'ifc_equivalent', true ) ); ?></td>
						<td><?php print( get_post_meta( $object->ID, 'bsdd_guid', true ) ); ?></td>
						<td><?php print( get_post_meta( $object->ID, 'cbnl_id', true ) ); ?></td>
<?php 
						foreach( $informationLevels as $informationLevel ) {
							$checked = false;
							foreach( $objectInformationLevels as $objectInformationLevel ) {
								if( $objectInformationLevel->information_level_id == $informationLevel->ID ) {
									$checked = true;
									break;
								}
							}
?>
						<td class="<?php print( $checked ? 'checked' : 'unchecked' ); ?> information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $checked ? 'x' : '' ); ?></td>
<?php
						}
?>						
					</tr>
<?php
						$count ++;
					}
?>				
				</table>
<?php
				} 
?>
			</div>
<?php
			}
		} elseif( $propertyId != 0 ) {
?>
			<a href="?"><?php _e( 'To overview', 'bim-information-levels' );?></a><br />
			<br />
<?php			
			$property = get_post( $propertyId );
			if( isset( $property ) && $property->post_type == $options[ 'bim_property_post_type' ] ) {
?>
			<h2><?php print( $property->post_title ); ?></h2>
			Unit: <?php print( get_post_meta( $property->ID, 'unit', true ) ); ?><br />
			IfcEquivalent: <?php print( get_post_meta( $property->ID, 'ifc_equivalent', true ) ); ?><br />
			BSDD Guid: <?php print( get_post_meta( $property->ID, 'bsdd_guid', true ) ); ?><br />
			<br />
			<table class="bim-information-level-table">
				<tr class="odd">
					<th class="numeric"><?php _e( 'NlSfb Code', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Object', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Unit', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'ifcEquivalent', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'BSDD GUID', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'CBNL ID', 'bim-information-levels' ); ?></th>
<?php 
					foreach( $informationLevels as $informationLevel ) {
?>
					<th><?php print( '<span class="information-level">' . $informationLevel->information_level . '</span>' ); ?></th>
<?php
					}
?>
				</tr>
<?php
				$count = 0;
				$objectIds = get_post_meta( $property->ID, '_object_id' );
				foreach( $objectIds as $objectId ) {
					$object = get_post( $objectId );
					$objectInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID, $property->ID );
?>
				<tr class="item-row <?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
					<td class="numeric"><?php print( get_post_meta( $object->ID, 'code', true ) ); ?></td>
					<td><a href="?object=<?php print( $object->ID ); ?>"><?php print( $object->post_title ); ?></a></td>
					<td><?php print( get_post_meta( $object->ID, 'unit', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'ifc_equivalent', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'bsdd_guid', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'cbnl_id', true ) ); ?></td>
<?php 
					foreach( $informationLevels as $informationLevel ) {
						$checked = false;
						foreach( $objectInformationLevels as $objectInformationLevel ) {
							if( $objectInformationLevel->information_level_id == $informationLevel->ID ) {
								$checked = true;
								break;
							}
						}
?>
					<td class="<?php print( $checked ? 'checked' : 'unchecked' ); ?> information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $checked ? 'x' : '' ); ?></td>
<?php
					}
?>						
				</tr>
<?php
					$count ++;
				}
?>
			</table>
<?php
			} else {
				_e( 'Not a valid property', 'bim-information-levels' );
			}
		} else {
			// Here we show a single object
			$object = get_post( $objectId );
			$parent = ( isset( $_GET[ 'id'] ) && ctype_digit( $_GET[ 'id' ] ) ) ? $_GET[ 'id' ] : 0;
			if( $parent != 0 ) {
				$parentTerm = get_term( $parent, $options[ 'bim_object_category_taxonomy' ] );
			}
			if( $parent == 0 || !isset( $parentTerm ) ) {
?>
			<a href="?"><?php _e( 'To overview', 'bim-information-levels' );?></a><br />
			<br />
<?php
			} else {
?>
			<a href="?id=<?php print( $parent ); ?>"><?php print( $parentTerm->name ); ?></a><br />
			<br />
<?php
			}
			if( isset( $object->post_type ) && $object->post_type == $options[ 'bim_object_post_type' ] ) {
?>
			<h3><?php _e( 'Show properties by information level(s)', 'bim-information-levels' ); ?></h3>
<?php
				foreach( $informationLevels as $informationLevel ) {
?>
			<input type="checkbox" class="filter-checkbox"<?php print( in_array( $informationLevel->ID, $selectedLevels ) ? ' checked' : '' ); ?> value="<?php print( $informationLevel->ID ); ?>" id="information-level-<?php print( $informationLevel->ID ); ?>" /> <label for="information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $informationLevel->post_title ); ?></label><br />
<?php
			}
				$properties = get_posts( Array(
						'post_type' => $options[ 'bim_property_post_type' ],
						'post_status' => 'publish',
						'meta_query' => Array(
								Array(
										'key' => '_object_id',
										'compare' => '=',
										'value' => $object->ID
								)
						),
						'meta_key' => 'code',
						'numberposts' => -1,
						'orderby' => 'meta_value title',
						'order' => 'ASC'
				) );
				$count = 0;
				$objectInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID );
				$thumbnail = get_the_post_thumbnail( $object->ID, 'medium_size' );
?>
			<br />
			<h2 class="title"><?php print( apply_filters( 'the_title', $object->post_title ) ); ?></h2>
<?php
				if( $thumbnail != '' ) {
?>
			<div class="image-container"><?php print( $thumbnail ); ?></div>
<?php
				}
?>
			<p><?php print( apply_filters( 'the_content', $object->post_content ) ); ?></p>
			<table class="bim-information-level-table">
				<tr class="odd">
					<th class="numeric"><?php _e( 'NlSfb Code', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Object/property', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Unit', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'ifcEquivalent', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'BSDD GUID', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'CBNL ID', 'bim-information-levels' ); ?></th>
<?php 
					foreach( $informationLevels as $informationLevel ) {
?>
					<th><?php print( '<span class="information-level">' . $informationLevel->information_level . '</span>' ); ?></th>
<?php
					}
?>
				</tr>
				<tr class="item-row <?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
					<td class="numeric"><?php print( get_post_meta( $object->ID, 'code', true ) ); ?></td>
					<td><h3><?php print( $object->post_title ); ?></h3></td>
					<td><?php print( get_post_meta( $object->ID, 'unit', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'ifc_equivalent', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'bsdd_guid', true ) ); ?></td>
					<td><?php print( get_post_meta( $object->ID, 'cbnl_id', true ) ); ?></td>
<?php 
					foreach( $informationLevels as $informationLevel ) {
						$checked = false;
						foreach( $objectInformationLevels as $objectInformationLevel ) {
							if( $objectInformationLevel->information_level_id == $informationLevel->ID ) {
								$checked = true;
								break;
							}
						}
?>
					<td class="<?php print( $checked ? 'checked' : 'unchecked' ); ?> information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $checked ? 'x' : '' ); ?></td>
<?php
					}
?>						
				</tr>					
<?php 
				$count ++;
				foreach( $properties as $property ) {
					$propertyInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID, $property->ID );
?>
				<tr class="item-row <?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
					<td class="numeric"><?php print( get_post_meta( $property->ID, 'code', true ) ); ?></td>
					<td><a href="?property=<?php print( $property->ID ); ?>"><?php print( $property->post_title ); ?></a></td>
					<td><?php print( get_post_meta( $property->ID, 'unit', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'ifc_equivalent', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'bsdd_guid', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'cbnl_id', true ) ); ?></td>
<?php 
					foreach( $informationLevels as $informationLevel ) {
						$checked = false;
						foreach( $propertyInformationLevels as $propertyInformationLevel ) {
							if( $propertyInformationLevel->information_level_id == $informationLevel->ID ) {
								$checked = true;
								break;
							}
						}
?>
					<td class="<?php print( $checked ? 'checked' : 'unchecked' ); ?> information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $checked ? 'x' : '' ); ?></td>
<?php
					}
?>						
				</tr>
<?php
					$count ++;
				}
?>				
			</table>
			<a name="comments"></a>
<?php
			$comments = get_comments( Array( 
					'post_id' => $object->ID 
			) );
?>
			<ol class="comment-list">
<?php
			wp_list_comments( Array(), $comments );
?>
			</ol>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					jQuery( "#comments" ).remove();
					jQuery( "#commentform .form-submit" ).append( "<input type=\"hidden\" name=\"redirect_to\" value=\"<?php print( get_bloginfo( 'wpurl' ) . $options[ 'information_levels_uri' ] . '?object=' . $object->ID . '&id=' . $parent  ); ?>\" />" );
				} );
			</script>
<?php
			comment_form( Array(), $object->ID );
			} else {
				_e( 'No valid object id', 'bim-information-levels' );
			}
		}
	}
	
	public static function showBIMInformationLevelsProperties() {
		global $wp_query, $wp_rewrite, $wpdb;
		$options = BIMInformationLevels::getOptions();
		
		$perPage = 50;
		$totalProperties = $wpdb->get_var( "SELECT COUNT(ID)
				FROM {$wpdb->posts}
				WHERE post_type = '{$options[ 'bim_property_post_type' ]}' AND post_status = 'publish'" );
		$maxPages = ceil( $totalProperties / $perPage );
		if( isset( $wp_query->query_vars[ 'page' ] ) && is_numeric( $wp_query->query_vars[ 'page' ] ) ) {
			if( $wp_query->query_vars[ 'page' ] > 0 && $wp_query->query_vars[ 'page' ] <= $maxPages ) {
				$page = $wp_query->query_vars[ 'page' ];
			} else {
				$page = 1;
			} 
		} else {
			$page = 1;
		}
		$count = 0;
		
		$properties = get_posts( Array(
				'post_type' => $options[ 'bim_property_post_type' ],
				'posts_per_page' => $perPage,
				'orderby' => 'title',
				'order' => 'ASC',
				'offset' => ( $page - 1 ) * $perPage,
				'post_status' => 'publish'
		) );
?>
			<table class="bim-information-level-table">
				<tr class="odd">
					<th><?php _e( 'Property', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Unit', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'ifcEquivalent', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'BSDD GUID', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'CBNL ID', 'bim-information-levels' ); ?></th>
				</tr>
<?php
		foreach( $properties as $property ) {
			//$propertyInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID, $property->ID );
?>
				<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
					<td><a href="<?php bloginfo( 'wpurl' ); print( $options[ 'information_levels_uri' ] ); ?>?property=<?php print( $property->ID ); ?>"><?php print( $property->post_title ); ?></a></td>
					<td><?php print( get_post_meta( $property->ID, 'unit', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'ifc_equivalent', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'bsdd_guid', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'cbnl_id', true ) ); ?></td>
				</tr>
<?php
			$count ++;
		}
?>
			</table>
<?php
		if( $maxPages > 1 ) {
?>
			<div class="property-pages">
<?php 
			if( false && $page > 1 ) {
?>
				<a href="?page=<?php print( $page - 1 ); ?>"><?php _e( 'Previous', 'bim-information-levels' ); ?></a>
<?php
			}
			for( $i = 1; $i <= $maxPages; $i ++ ) {
				if( $i == $page ) {
?>
				<span class="selected"><?php print( $i ); ?></span>
<?php 
				} else {
?>
				<a href="?page=<?php print( $i ); ?>"><?php print( $i ); ?></a>
<?php 
				}
			}
			if( false && $page < $maxPages ) {
?>
				<a href="?page=<?php print( $page + 1 ); ?>"><?php _e( 'Next', 'bim-information-levels' ); ?></a>
<?php
			}
?>			
</div>
<?php
		}
	}
	
	public static function showBIMInformationLevelsByLevel() {
		global $wp_query, $wp_rewrite, $wpdb;
		$options = BIMInformationLevels::getOptions();
		
		$selectedLevels = ( isset( $_GET[ 'filter' ] ) && is_array( $_GET[ 'filter' ] ) ) ? $_GET[ 'filter' ] : Array();
		array_walk( $selectedLevels, 'intval' );
		if( count( $selectedLevels ) > 0 ) {
			$filters = ' AND information_level_id IN( ' . implode( ', ', $selectedLevels ) . ' )';
		} else {
			$filters = ' AND 0 = 1';
		}
		
		$perPage = 50;
		$totalProperties = count( $wpdb->get_results( "SELECT ID
			FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->prefix}property_information_level ON property_id = ID
			WHERE post_type = '{$options[ 'bim_property_post_type' ]}' AND post_status = 'publish'$filters
			GROUP BY ID, object_id" ) );
		$maxPages = ceil( $totalProperties / $perPage );
		if( isset( $wp_query->query_vars[ 'page' ] ) && is_numeric( $wp_query->query_vars[ 'page' ] ) ) {
			if( $wp_query->query_vars[ 'page' ] > 0 && $wp_query->query_vars[ 'page' ] <= $maxPages ) {
				$page = $wp_query->query_vars[ 'page' ];
			} else {
				$page = 1;
			}
		} else {
			$page = 1;
		}
		$count = 0;
?>
				<h3><?php _e( 'Show properties and objects by information level(s)', 'bim-information-levels' ); ?></h3>
				<form method="get" action="">
<?php
		$informationLevels = BIMInformationLevels::getInformationLevels();
		foreach( $informationLevels as $informationLevel ) {
?>
					<input type="checkbox" name="filter[]"<?php print( in_array( $informationLevel->ID, $selectedLevels ) ? ' checked' : '' ); ?> value="<?php print( $informationLevel->ID ); ?>" id="information-level-<?php print( $informationLevel->ID ); ?>" /> <label for="information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $informationLevel->post_title ); ?></label><br />
<?php
		}
?>
					<input type="submit" value="<?php _e( 'Filter', 'bim-information-levels' ); ?>" />
				</form>
				<br />
				<?php print( $totalProperties ); ?> <?php _e( 'results', 'bim-information-levels' ); ?><br />
				<br />
<?php
		$properties = $wpdb->get_results( $wpdb->prepare( "SELECT *
			FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->prefix}property_information_level ON property_id = ID
			WHERE post_type = '{$options[ 'bim_property_post_type' ]}' AND post_status = 'publish'$filters
			GROUP BY ID, object_id
			ORDER BY post_title
			LIMIT %d, %d", ( $page - 1 ) * $perPage, $perPage ) );
?>
			<table class="bim-information-level-table">
				<tr class="odd">
					<th><?php _e( 'Object', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Property', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'Unit', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'ifcEquivalent', 'bim-information-levels' ); ?></th>
					<th><?php _e( 'BSDD GUID', 'bim-information-levels' ); ?></th>
<?php 
					foreach( $informationLevels as $informationLevel ) {
?>
					<th><?php print( '<span class="information-level">' . $informationLevel->information_level . '</span>' ); ?></th>
<?php
					}
?>
				</tr>
<?php
		foreach( $properties as $property ) {
			$object = get_post( $property->object_id );
			/*$objects = $wpdb->get_results( $wpdb->prepare( "SELECT object_id, post_title
					FROM {$wpdb->prefix}property_information_level
					LEFT JOIN $wpdb->posts ON ID = object_id
					WHERE property_id = %d $filters", $property->ID ) );
			foreach( $objects as $object ) {*/
				$propertyInformationLevels = BIMInformationLevels::getItemInformationLevels( $property->object_id, $property->ID );
?>
				<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
					<td><a href="<?php bloginfo( 'wpurl' ); print( $options[ 'information_levels_uri' ] ); ?>?object=<?php print( $property->object_id ); ?>"><?php print( $object->post_title ); ?></a></td>
					<td><a href="<?php bloginfo( 'wpurl' ); print( $options[ 'information_levels_uri' ] ); ?>?property=<?php print( $property->ID ); ?>"><?php print( $property->post_title ); ?></a></td>
					<td><?php print( get_post_meta( $property->ID, 'unit', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'ifc_equivalent', true ) ); ?></td>
					<td><?php print( get_post_meta( $property->ID, 'bsdd_guid', true ) ); ?></td>
<?php 
						foreach( $informationLevels as $informationLevel ) {
							$checked = false;
							foreach( $propertyInformationLevels as $propertyInformationLevel ) {
								if( $propertyInformationLevel->information_level_id == $informationLevel->ID ) {
									$checked = true;
									break;
								}
							}
?>
					<td class="<?php print( $checked ? 'checked' : 'unchecked' ); ?>"><?php print( $checked ? 'x' : '' ); ?></td>
<?php
					}
?>						
				</tr>
<?php
				$count ++;
			//}
		}
?>
			</table>
<?php
		if( $maxPages > 1 ) {
			$linkFilter = '&filter[]=' . implode( '&filter[]=', $selectedLevels );
?>
			<div class="property-pages">
<?php 
			if( false && $page > 1 ) {
?>
				<a href="?page=<?php print( ( $page - 1 ) . $linkFilter ); ?>"><?php _e( 'Previous', 'bim-information-levels' ); ?></a>
<?php
			}
			for( $i = 1; $i <= $maxPages; $i ++ ) {
				if( $i == $page ) {
?>
				<span class="selected"><?php print( $i ); ?></span>
<?php 
				} else {
?>
				<a href="?page=<?php print( $i . $linkFilter ); ?>"><?php print( $i ); ?></a>
<?php 
				}
			}
			if( false && $page < $maxPages ) {
?>
				<a href="?page=<?php print( ( $page + 1 ) . $linkFilter ); ?>"><?php _e( 'Next', 'bim-information-levels' ); ?></a>
<?php
			}
?>			
</div>
<?php
		}
	}
	
	public static function showBIMInformationLevelsLevels() {
		$options = BIMInformationLevels::getOptions();
		$informationLevels = BIMInformationLevels::getInformationLevels();
		foreach( $informationLevels as $informationLevel ) {
?>
				<h3><?php print( apply_filters( 'the_title', $informationLevel->post_title ) ); ?></h3>
				<p><?php print( apply_filters( 'the_content', $informationLevel->post_content ) ); ?></p>
<?php
		}
	}
	
	public static function thePermalinkSearch( $permalink ) {
		global $post;
		$options = BIMInformationLevels::getOptions();
		if( is_search() && ( $post->post_type == $options[ 'bim_property_post_type' ] || $post->post_type == $options[ 'bim_object_post_type' ] ) ) {
			if( $post->post_type == $options[ 'bim_property_post_type' ] ) {
				$permalink = get_bloginfo( 'wpurl' ) . $options[ 'information_levels_uri' ] . '?property=' . $post->ID;
			} else {
				$permalink = get_bloginfo( 'wpurl' ) . $options[ 'information_levels_uri' ] . '?object=' . $post->ID;
			}
		}
		return $permalink;
	}
	
	public static function theTitleSearch( $title, $postId = -1 ) {
		global $post;
		$options = BIMInformationLevels::getOptions();
		if( is_search() && $postId == $post->ID && ( $post->post_type == $options[ 'bim_property_post_type' ] || $post->post_type == $options[ 'bim_object_post_type' ] ) ) {
			if( $post->post_type == $options[ 'bim_property_post_type' ] ) {
				$title = __( 'Property', 'bim-information-level' ) . ': ' . $title;
			} else {
				$title = __( 'Object', 'bim-information-level' ) . ': ' . $title;
			}
		}
		return $title;
	}
	
	public static function theExcerptSearch( $excerpt ) {
		$options = BIMInformationLevels::getOptions();
		// TODO: maybe show different things
		return $excerpt;
	}
	
	public static function commentsOpen( $open, $postId = -1 ) {
		$options = BIMInformationLevels::getOptions();
		$post = get_post( $postId );
		if( $post->post_type == $options[ 'bim_property_post_type' ] || $post->post_type == $options[ 'bim_object_post_type' ] ) {
			$open = false;
		}
		return $open;
	}
	
	public static function showBIMInformationLevelsReport() {
		global $wp_query, $wp_rewrite;
		$options = BIMInformationLevels::getOptions();
		$informationLevels = BIMInformationLevels::getInformationLevels();
		
		$topics = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => 0 ) );
		foreach( $topics as $key => $topic ) {
			$topics[$key]->children = get_terms( $options[ 'bim_object_category_taxonomy' ], Array( 'hide_empty' => false, 'parent' => $topic->term_id ) );
		}
		
		$settings = Array(
			'ajaxUrl' => plugins_url( 'ajax-callback.php', __FILE__ ),
			'mainTopics' => $topics,
			'text' => Array(
				'all' => __( 'all', 'bim-information-levels' ),
				'topicLabel' => __( 'Topic', 'bim-information-levels' ),
				'subTopicLabel' => __( 'Sub topic', 'bim-information-levels' ), 
				'trash' => __( 'Remove', 'bim-information-levels' )
			),
			'type' => 'report'
		);

		if( isset( $_COOKIE[ 'filter_levels' ] ) ) {
			$selectedLevels = explode( ',', $_COOKIE[ 'filter_levels' ] );
			array_walk( $selectedLevels, 'intval' );
		} else {
			$selectedLevels = Array();
			foreach( $informationLevels as $informationLevel ) {
				$selectedLevels[] = $informationLevel->ID;
			}
		}
?>
			<h3><?php _e( 'Show objects by information level(s)', 'bim-information-levels' ); ?> <a id="toggle-information-level-container" class="toggle-link" href="javascript:void( null );" onclick="BIMInformationLevels.toggleBox( this );">+</a></h3>
			<div id="information-level-container">
<?php
		foreach( $informationLevels as $informationLevel ) {
?>
			<input type="checkbox" class="filter-checkbox"<?php print( in_array( $informationLevel->ID, $selectedLevels ) ? ' checked' : '' ); ?> value="<?php print( $informationLevel->ID ); ?>" id="information-level-<?php print( $informationLevel->ID ); ?>" /> <label for="information-level-<?php print( $informationLevel->ID ); ?>"><?php print( $informationLevel->post_title ); ?></label><br />
<?php
		}
?>
			</div>
			<h3><?php _e( 'Show object with topics', 'bim-information-levels' ); ?> <a id="toggle-report-control-panel" class="toggle-link" href="javascript:void( null );" onclick="BIMInformationLevels.toggleBox( this );">+</a></h3>
			<div id="report-control-panel">
				<div class="content"></div>
				<a href="javascript:void( null );" onclick="BIMInformationLevels.addReportOptionRow();"><?php _e( 'add specific topic', 'bim-information-levels' ); ?></a><br />
			</div>
			<br />
			<form method="post" action="<?php print( plugins_url( 'download-report.php', __FILE__ ) ); ?>">
				<input type="hidden" id="report-settings" name="settings" value="{}" />
				<input type="hidden" id="report-filters" name="filters" value="" />
				<input type="submit" value="<?php _e( 'download report', 'bim-information-levels' ); ?>" />
			</form>
			<h3><?php _e( 'Report preview', 'bim-information-levels' ); ?></h3>
			<div id="result-preview"></div>
			<script type="text/javascript">
				var informationLevelsControlSettings = <?php print( json_encode( $settings ) ); ?>;
			</script>
<?php
	}
	
	public static function getReportHTML( $settings, $levelFilters ) {
		$options = BIMInformationLevels::getOptions();
		$informationLevels = BIMInformationLevels::getInformationLevels();
		$taxQueries = Array();
		
		if( is_array( $settings ) ) {
			foreach( $settings as $setting ) {
				if( isset( $setting->subTopicId ) && $setting->subTopicId != '' ) {
					$taxQueries[] = Array(
							'taxonomy' => $options[ 'bim_object_category_taxonomy' ],
							'field' => 'id',
							'terms' => $setting->subTopicId,
							'include_children' => false
					);
				}
				if( isset( $setting->topicId ) && $setting->topicId != '' ) {
					$taxQueries[] = Array(
							'taxonomy' => $options[ 'bim_object_category_taxonomy' ],
							'field' => 'id',
							'terms' => $setting->topicId,
							'include_children' => ( isset( $setting->subTopicId ) && $setting->subTopicId != '' ) ? false : true
					);
				}
			}
		}
		
		if( count( $taxQueries ) > 1 ) {
			$taxQueries[ 'relation' ] = 'OR';
		}
		
		$levelFilters = explode( ',', $levelFilters );
		// Force integers
		foreach( $levelFilters as $key => $value ) {
			$levelFilters[$key] = intval( $value );
		}
		
		$objects = get_posts( Array(
				'post_type' => $options[ 'bim_object_post_type' ],
				'post_status' => 'publish',
				'tax_query' => $taxQueries,
				'meta_key' => 'code',
				'numberposts' => -1,
				'orderby' => 'meta_value title',
				'order' => 'ASC'
		) );
		$tableHeader = '
				<table style="width: 100%" class="bim-information-level-table">
				<tr>
				<th>' . __( 'NlSfb Code', 'bim-information-levels' ) . '</th>
				<th>' . __( 'Object', 'bim-information-levels' ) . '</th>
				<th>' . __( 'Unit', 'bim-information-levels' ) . '</th>
				<th>' . __( 'ifcEquivalent', 'bim-information-levels' ) . '</th>
				<th>' . __( 'BSDD GUID', 'bim-information-levels' ) . '</th>
				<th>' . __( 'CBNL ID', 'bim-information-levels' ) . '</th>'; 
		foreach( $informationLevels as $informationLevel ) {
			$tableHeader .= '<th>' . $informationLevel->information_level . '</th>';
		}
		$tableHeader .= '</tr>';
		$html = '';
		$previousTopic = false;
		$previousSubTopic = false;
		foreach( $objects as $object ) {
			// We have to check if this object is in our filter list
			if( BIMInformationLevels::hasLevels( $object->ID, $levelFilters ) ) {
				$changedTopic = false;
				$changedSubTopic = false;
				if( false === $previousTopic || !has_term( $previousTopic->term_id, $options[ 'bim_object_category_taxonomy' ], $object ) ) {
					// There is always a topic
					$topics = wp_get_object_terms( Array( $object->ID ), $options[ 'bim_object_category_taxonomy' ] );
					foreach( $topics as $topic ) {
						if( 0 == $topic->parent && ( false === $previousTopic || $previousTopic->term_id != $topic->term_id ) ) {
							$previousTopic = $topic;
							$changedTopic = true;
							break 1;
						}
					}
					if( $changedTopic ) {
						if( false !== $previousTopic ) {
							$html .= '</table>';
						}
						$html .= '<h2>' . $previousTopic->name . '</h2>';
					}
				}	
				if( false === $previousSubTopic || !has_term( $previousSubTopic->term_id, $options[ 'bim_object_category_taxonomy' ], $object ) ) {
					if( !$changedTopic ) {
						$html .= '</table>';
					}
					$topics = wp_get_object_terms( Array( $object->ID ), $options[ 'bim_object_category_taxonomy' ] );
					$previousSubTopic = false;
					foreach( $topics as $topic ) {
						if( 0 != $topic->parent ) {
							$previousSubTopic = $topic;
						}
					}
					if( $previousSubTopic !== false ) {
						$html .= '<h2>' . $previousSubTopic->name . '</h2>';
					}
					$changedSubTopic = true;
				}
				if( $changedTopic || $changedSubTopic ) {
					$html .= $tableHeader;
				}
				$objectInformationLevels = BIMInformationLevels::getItemInformationLevels( $object->ID );
				$html .= '<tr>
					<td>' . get_post_meta( $object->ID, 'code', true ) . '</td>
					<td>' . $object->post_title . '</td>
					<td>' . get_post_meta( $object->ID, 'unit', true ) . '</td>
					<td>' . get_post_meta( $object->ID, 'ifc_equivalent', true ) . '</td>
					<td>' . get_post_meta( $object->ID, 'bsdd_guid', true ) . '</td>
					<td>' . get_post_meta( $object->ID, 'cbnl_id', true ) . '</td>';
				foreach( $informationLevels as $informationLevel ) {
					$checked = false;
					foreach( $objectInformationLevels as $objectInformationLevel ) {
						if( $objectInformationLevel->information_level_id == $informationLevel->ID ) {
							$checked = true;
							break;
						}
					}
					$html .= '<td>' . ( $checked ? 'x' : '' ) . '</td>';
				}
				$html .= '</tr>';
			}
		}
		$html .= '</table>';
		return $html;
	}
	
	public static function printWordDocument( $settings, $filters ) {
		$html = BIMInformationLevels::getReportHTML( $settings, $filters );
		header( 'Content-type: application/vnd.ms-word' );
		header( 'Content-Disposition: attachment;Filename=bim-information-levels.doc' );
?>
<html
    xmlns:o='urn:schemas-microsoft-com:office:office'
    xmlns:w='urn:schemas-microsoft-com:office:word'
    xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
    	<title>BIM Information Levels</title>
    	<xml>
    	    <w:worddocument xmlns:w="#unknown">
	            <w:view>Print</w:view>
            	<w:zoom>90</w:zoom>
            	<w:donotoptimizeforbrowser />
        	</w:worddocument>
    	</xml>
	</head>
	<body lang=EN-US>
		<?php print( $html ); ?>
	</body>
</html>
<?php
	}
	
	private static function getCleanJSONObject( $object ) {
		if( is_object( $object ) || is_array( $object ) ) {
			$cleaned = Array();
			$object = ( Array ) $object;
			$allowedKeys = Array( 
				'post_title' => 'title', 
				'information_level' => 'informationLevel',
				'name' => 'title',
				'informationLevels' => 'informationLevels',
				'code' => 'code',
				'unit' => 'unit',
				'ifcEquivalent' => 'ifcEquivalent',
				'bsddGuid' => 'bsddGuid',
				'cbnlId' => 'cbnlId',
			);
			foreach( $allowedKeys as $key => $value ) {
				if( isset( $object[$key] ) ) {
					$cleaned[$value] = $object[$key];
				}
			}
			return $cleaned;
		} else {
			return $object;
		}
	}
	
	public static function getJSONExport( $variables ) {
		if( is_array( $variables ) ) {
			$cleaned = Array();
			foreach( $variables as $variable ) {
				$cleaned[] = BIMInformationLevels::getCleanJSONObject( $variable );
			}
			return $cleaned;
		} else {
			$cleaned = BIMInformationLevels::getCleanJSONObject( $variables );
			return $cleaned;
		}
	}
} 

$bimInformationLevels = new BIMInformationLevels();
?>