<?php
/*
Plugin Name: BIM Quality Blocks
Plugin URI:
Description: BIM Quality Blocks. Quality Blocks, BIM! BIM Blocks Quality? Quality Blocks BIM! Blocks... BIM... Quality? Quality, Blocks, BIM. BIM Blocks! Quality Blocks, Quality BIM. BIM Quality Blocks.
Version: 1.0
Author: Bastiaan Grutters
Author URI: http://www.bastiaangrutters.nl
*/

/*
 * Usage: Place shortcodes in pages:
 * [showBIMQualityBlocks]
 */

namespace BIMQualityBlocks;

class BIMQualityBlocks {
	private $options;

   public static $layers;

	public function __construct() {
      spl_autoload_register( Array( '\BIMQualityBlocks\BIMQualityBlocks', 'autoload' ) );

		add_action( 'admin_menu', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'optionsMenu' ) );

		$this->options = get_option( 'bim_quality_blocks_options', Array() );
		
		if( isset( $this->options[ 'bim_quality_blocks_post_type' ] ) ) {
			add_action( 'admin_init', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'editorInit' ) );
		}

		add_action( 'admin_enqueue_scripts', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'adminEnqueueScripts' ) );
		add_action( 'wp_enqueue_scripts', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'wpEnqueueScripts' ) );

		// Add post types etc at the WordPress init action
		add_action( 'init', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'wordPressInit' ) );
		
		// --- Add shortcodes ---
		add_shortcode( 'showBIMQualityBlocks', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'showBIMQualityBlocks' ) );
      add_shortcode( 'showBIMQualityBlocksReportList', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'showBIMQualityBlocksReportList' ) );

      // action for ajax call (or just outside wordpress calls with WP context)
      add_action( 'wp_ajax_bqb_download_report', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'downloadReport' ) );
      add_action( 'wp_ajax_bqb_download_xml', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'showReportXml' ) );

      BIMQualityBlocks::$layers = Array(
         'building_type' => __( 'Gebouwtype', 'bim-quality-blocks' ),
         'bim_usage' => __( 'Primaire interesse in BIM', 'bim-quality-blocks' ),
         'basic_model' => __( 'Basis model', 'bim-quality-blocks' ),
         'properties' => __( 'Eigenschappen', 'bim-quality-blocks' ),
         'applications' => __( 'Toepassingen', 'bim-quality-blocks' ),
         'attachments' => __( 'Bijlagen', 'bim-quality-blocks' )
      );

      add_action( 'wp_ajax_bimqbcallback', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'ajaxCallback' ) );
      add_action( 'wp_ajax_nopriv_bimqbcallback', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'ajaxCallback' ) );
   }

   /**
    * @param $class
    */
   public static function autoload( $class ) {
      $class = ltrim( $class, '\\' );
      if( strpos( $class, __NAMESPACE__ ) !== 0 ) {
         return;
      }
      $class = str_replace( __NAMESPACE__, '', $class );
      $filename = plugin_dir_path( __FILE__ ) . 'includes/class-bim-quality-blocks' .
          strtolower( str_replace( '\\', '-', $class ) ) . '.php';
      require_once( $filename );
   }

   public static function optionsMenu() {
		add_options_page( __( 'BIM Quality Blocks Options', 'bim-quality-blocks' ), __( 'BIM Quality Blocks Options', 'bim-quality-blocks' ),
          'activate_plugins', basename( dirname( __FILE__ ) ) . '/bim-quality-blocks-options.php' );
	}
	 
	public static function adminEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'chosen', plugins_url( 'chosen/chosen.jquery.min.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
      wp_enqueue_script( 'bim-quality-blocks-admin', plugins_url( 'bim-quality-blocks-admin.js', __FILE__ ), Array( 'chosen' ), "1.0", true );
      wp_enqueue_style( 'chosen', plugins_url( 'chosen/chosen.min.css', __FILE__ ) );
      wp_enqueue_style( 'bim-quality-blocks', plugins_url( 'bim-quality-blocks.css', __FILE__ ) );
	}

	public static function wpEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'json-fallback', plugins_url( 'libraries/json.js', __FILE__ ), Array(), "1.0", true );
      wp_enqueue_script( 'bim-quality-blocks', plugins_url( 'bim-quality-blocks.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
		wp_enqueue_style( 'bim-quality-blocks', plugins_url( 'bim-quality-blocks.css', __FILE__ ) );
	}

	public static function editorInit() {
		$options = BIMQualityBlocks::getOptions();
		add_meta_box( 'bim-quality-blocks-meta', __( 'BIM Quality Block Options', 'bim-quality-blocks' ),
          Array( '\BIMQualityBlocks\BIMQualityBlocks', 'editorWidget' ), $options[ 'bim_quality_blocks_post_type' ], 'normal', 'high' );
		add_action( 'save_post', Array( '\BIMQualityBlocks\BIMQualityBlocks', 'saveEditorWidget' ) );
	}	
    
	public static function getOptions( $forceReload = false ) {
		global $bimQualityBlocks;
   		if( $forceReload ) {
			$bimQualityBlocks->options = get_option( 'bim_quality_blocks_options', Array() );
		}
		return $bimQualityBlocks->options;
	}
	
	public static function wordPressInit() {
		$postTypeArguments = Array(
				'labels' => Array(
						'name' => __( 'BIM Quality Blocks', 'bim-quality-blocks' ),
						'singular_name' => __( 'BIM Quality Block', 'bim-quality-blocks' ),
						'add_new' => __( 'Add New', 'bim-quality-blocks' ),
						'add_new_item' => __( 'Add New BIM Quality Block', 'bim-quality-blocks' ),
						'edit_item' => __( 'Edit BIM Quality Block', 'bim-quality-blocks' ),
						'new_item' => __( 'New BIM Quality Block', 'bim-quality-blocks' ),
						'all_items' => __( 'All BIM Quality Blocks', 'bim-quality-blocks' ),
						'view_item' => __( 'View BIM Quality Block', 'bim-quality-blocks' ),
						'search_items' => __( 'Search BIM Quality Blocks', 'bim-quality-blocks' ),
						'not_found' =>  __( 'No BIM Quality Blocks found', 'bim-quality-blocks' ),
						'not_found_in_trash' => __( 'No BIM Quality Blocks found in Trash', 'bim-quality-blocks' ),
						'parent_item_colon' => '',
						'menu_name' => 'BIM Quality Blocks' ),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields' )
		);
		register_post_type( 'bim_quality_block', $postTypeArguments );

      $postTypeArguments = Array(
          'labels' => Array(
              'name' => __( 'Private BQ Blocks', 'bim-quality-blocks' ),
              'singular_name' => __( 'Private BQ Block', 'bim-quality-blocks' ),
              'add_new' => __( 'Add New', 'bim-quality-blocks' ),
              'add_new_item' => __( 'Add New Private BQ Block', 'bim-quality-blocks' ),
              'edit_item' => __( 'Edit Private BQ Block', 'bim-quality-blocks' ),
              'new_item' => __( 'New Private BQ Block', 'bim-quality-blocks' ),
              'all_items' => __( 'All Private BQ Blocks', 'bim-quality-blocks' ),
              'view_item' => __( 'View Private BQ Block', 'bim-quality-blocks' ),
              'search_items' => __( 'Search Private BQ Blocks', 'bim-quality-blocks' ),
              'not_found' =>  __( 'No Private BQ Blocks found', 'bim-quality-blocks' ),
              'not_found_in_trash' => __( 'No Private BQ Blocks found in Trash', 'bim-quality-blocks' ),
              'parent_item_colon' => '',
              'menu_name' => 'Private BQ Blocks' ),
          'public' => true,
          'publicly_queryable' => true,
          'show_ui' => true,
          'show_in_menu' => true,
          'query_var' => true,
          'rewrite' => true,
          'has_archive' => true,
          'hierarchical' => false,
          'menu_position' => null,
          'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields' )
      );
      register_post_type( 'private_bqblock', $postTypeArguments );

      add_image_size( 'grid-icon', 120, 120, true );

      $postTypeArguments = Array(
          'labels' => Array(
              'name' => __( 'BIM Quality Blocks Reports', 'bim-quality-blocks' ),
              'singular_name' => __( 'Report', 'bim-quality-blocks' ),
              'add_new' => __( 'Add New', 'bim-quality-blocks' ),
              'add_new_item' => __( 'Add New Report', 'bim-quality-blocks' ),
              'edit_item' => __( 'Edit Report', 'bim-quality-blocks' ),
              'new_item' => __( 'New Report', 'bim-quality-blocks' ),
              'all_items' => __( 'All Reports', 'bim-quality-blocks' ),
              'view_item' => __( 'View Report', 'bim-quality-blocks' ),
              'search_items' => __( 'Search Reports', 'bim-quality-blocks' ),
              'not_found' =>  __( 'No Reports found', 'bim-quality-blocks' ),
              'not_found_in_trash' => __( 'No Reports found in Trash', 'bim-quality-blocks' ),
              'parent_item_colon' => '',
              'menu_name' => 'BIM Quality Blocks Report' ),
          'public' => true,
          'publicly_queryable' => true,
          'show_ui' => true,
          'show_in_menu' => true,
          'query_var' => true,
          'rewrite' => true,
          'has_archive' => true,
          'hierarchical' => false,
          'menu_position' => null,
          'supports' => Array( 'title', 'editor', 'author' )
      );
      register_post_type( 'bim_q_b_report', $postTypeArguments );
	}

   /**
    * @return QualityBlock[]
    */
   public static function getQualityBlocks() {
      $options = BIMQualityBlocks::getOptions();
      $posts = get_posts( Array(
         'post_type' => $options['bim_quality_blocks_post_type'],
         'posts_per_page' => -1,
         'order' => 'ASC'
      ) );
      $blocks = Array();
      foreach( $posts as $post ) {
         $blocks[] = new QualityBlock( $post );
      }
      return $blocks;
   }

   public static function showBIMQualityBlocks() {
      if( is_user_logged_in() ) {
         if( isset( $_POST['report'] ) ) {
            // To the report page!
            BIMQualityBlocks::showReportPage();
         } else {
            print( '<div id="bim-quality-block-layers">' );
            print( '<a href="' . add_query_arg( Array( 'action' => 'bimqbcallback' ), admin_url( 'admin-ajax.php' ) ) . '" class="hidden" id="bim-quality-blocks-ajax"></a>' );

            print( '<label for="report-title">' . __( 'Report title', 'bim-quality-blocks' ) . '</label><br />' );
            print( '<input type="text" id="report-title" placeholder="' . __( 'Report title', 'bim-quality-blocks' ) . '" required title="' . __( 'Fill in a title for this report', 'bim-quality-report' ) . '" /><br /><br />' );

            $blocks = BIMQualityBlocks::getQualityBlocks();
            $number = 0;
            $mergedLayers = false;
            $mergedLayerKeys = Array( 'basic_model', 'properties', 'applications' );
            foreach( BIMQualityBlocks::$layers as $key => $label ) {
               if( $key == 'building_type' ) {
                  print( '<div id="building-select-container">' );
                  $html = '<div class="hidden">';
                  print( '<label for="select-building">' . $label . '</label><br />' );
                  print( '<select id="select-building" onchange="BIMQualityBlocks.buildingTypeChanged();">' );
                  print( '<option value="">' . __( 'Select a building type', 'bim-quality-blocks' ) . '</option>' );
                  foreach( $blocks as $block ) {
                     if( $block->layer == $key ) {
                        print( '<option value="' . $block->post->ID . '">' . $block->post->post_title . '</option>' );
                        $html .= '<div class="quality-block ' . $block->layer . ' hidden" id="quality-block-' . $block->post->ID . '">';
                        $html .= '<div class="disabled-tooltip hidden">' . __( 'This block is disabled because', 'bim-quality-blocks' ) . ' <span class="reason-list"></span><span class="start-text hidden">' . __( 'has been selected', 'bim-quality-blocks' ) . '</span></div>';
                        $html .= '<div class="exclude hidden">' . implode( ',', $block->relations ) . '</div>';
                        $html .= '<div class="deselect hidden">' . implode( ',', $block->deselects ) . '</div>';
                        $html .= '<div class="behaviour hidden">' . $block->behaviour . '</div>';
                        $html .= '</div>';
                     }
                  }
                  print( '</select>' );
                  $html .= '</div>';
                  print( $html );
                  print( '</div>' );
               } else {
                  if( !$mergedLayers && in_array( $key, $mergedLayerKeys ) || !in_array( $key, $mergedLayerKeys ) ) {
                     if( $mergedLayers && !in_array( $key, $mergedLayerKeys ) ) {
                        print( '<div class="clear"></div>' );
                        print( '</div>' );
                     }
                     print( '<div class="layer" id="layer_' . $key . '">' );
                     print( '<div class="overlay"></div>' );
                     print( '<h3>' . $label . '</h3>' );
                     if( $number >= 2 ) {
                        print( '<div class="navigation-container">
                                    <a href="" class="previous">' . __( 'Previous', 'bim-quality-blocks' ) . '</a>
                                    <a href="" class="next' . ( $key == 'attachments' ? ' end-page' : '' ) . '">' . ( $key == 'attachments' ? __( 'Next page', 'bim-quality-blocks' ) : __( 'Next', 'bim-quality-blocks' ) ) . '</a>
                                    <div class="clear"></div>
                              </div>' );
                     }
                     if( !$mergedLayers && in_array( $key, $mergedLayerKeys ) ) {
                        $mergedLayers = true;
                     }
                  }
                  foreach( $blocks as $block ) {
                     if( $block->layer == $key ) {
                        print( '<div class="quality-block ' . $block->layer . ( $block->behaviour == 'normal' ? ' selected' : '' ) . '" id="quality-block-' . $block->post->ID . '">' );
                        print( '<h3>' . $block->post->post_title . '</h3>' );
                        print( '<div class="image-container">' );
                        $image = get_the_post_thumbnail( $block->post->ID, 'grid-icon' );
                        if( $image == '' ) {
                           print( '<div class="no-image-placeholder"></div>' );
                        } else {
                           print( '<img src="' . $image . '" alt="' . $block->post->post_title . '" />' );
                        }
                        print( '</div>' );
                        print( '<div class="tooltip hidden">' . apply_filters( 'the_content', $block->post->post_content ) . '</div>' );
                        print( '<div class="disabled-tooltip hidden">' . __( 'This block is disabled because', 'bim-quality-blocks' ) . ' <span class="reason-list"></span><span class="start-text hidden">' . __( 'has been selected', 'bim-quality-blocks' ) . '</span></div>' );
                        print( '<div class="exclude hidden">' . implode( ',', $block->relations ) . '</div>' );
                        print( '<div class="deselect hidden">' . implode( ',', $block->deselects ) . '</div>' );
                        print( '<div class="behaviour hidden">' . $block->behaviour . '</div>' );
                        print( '</div>' );
                     }
                  }
                  if( !in_array( $key, $mergedLayerKeys ) ) {
                     print( '<div class="clear"></div>' );
                     print( '</div>' );
                  }
               }
               $number ++;
            }
            print( '<div class="clear"></div>' );
            print( '<div class="hidden" id="quality-block-tooltip"><div class="content"></div></div>' );
            print( '</div>' );

            $options = BIMQualityBlocks::getOptions();
            $privateBlocks = get_posts( Array(
               'post_type' => $options['private_bqblocks_post_type'],
               'post_status' => 'private',
               'posts_per_page' => -1
            ) );

            print( '<div id="private-blocks" class="hidden">' );
            print( '<h3>' . __( 'Private blocks', 'bim-quality-blocks' ) . '</h3>' );
            print( '<div id="private-block-editor">' );
            print( '<h4 class="hidden edit-title">' . __( 'Edit private block', 'bim-quality-blocks' ) . '</h4>' );
            print( '<h4 class="new-title">' . __( 'Add new private block', 'bim-quality-blocks' ) . '</h4>' );
            print( '<input type="hidden" id="private-block-id" value="" />' );
            print( '<label for="private-block-title">' . __( 'Title', 'bim-quality-blocks' ) . '</label><br />' );
            print( '<input type="text" id="private-block-title" placeholder="' . __( 'Private block title', 'bim-quality-blocks' ) . '" title="' . __( 'Fill in a title for this private block', 'bim-quality-report' ) . '" /><br /><br />' );
            print( '<label for="private-block-text">' . __( 'Report text', 'bim-quality-blocks' ) . '</label><br />' );
            print( '<textarea id="private-block-text" placeholder="' . __( 'Report title', 'bim-quality-blocks' ) . '" title="' . __( 'Fill in the report text when this block is included in a report', 'bim-quality-report' ) . '"></textarea><br />' );
            print( '<label for="private-block-xml">' . __( 'Report XML', 'bim-quality-blocks' ) . '</label><br />' );
            print( '<textarea id="private-block-xml" placeholder="' . __( 'Report XML', 'bim-quality-blocks' ) . '" title="' . __( 'Fill in the xml snippet used when this block is included in a report', 'bim-quality-report' ) . '"></textarea><br />' );
            print( '<br />' );
            print( '<a href="" class="button" id="save-private-block">' . __( 'Save', 'bim-quality-blocks' ) . '</a>' );
            print( '<a href="" class="button hidden" id="cancel-edit-private-block">' . __( 'Cancel', 'bim-quality-blocks' ) . '</a>' );

            print( '</div>' );
            print( '<div id="private-block-list">' );
            foreach( $privateBlocks as $privateBlock ) {
               print( BIMQualityBlocks::getPrivateBlockHtml( $privateBlock ) );
            }
            print( '<div class="clear"></div>' );
            print( '</div>' );
            print( '<a href="" class="button submit">' . __( 'Finish', 'bim-quality-blocks' ) . '</a>' );
            print( '</div>' );

            print( '<form method="post" action="">' );
            print( '<input type="hidden" id="report-content" name="report" value="" />' );
            print( '<input type="submit" value="" class="hidden" />' );
            print( '</form>' );
         }
      } else {
         print( '<p>' . __( 'Log in to use BIM quality blocks', 'bim-quality-blocks' ) . '</p>' );
      }
   }

   public static function getPrivateBlockHtml( $privateBlock ) {
      $html = '<div class="private-block" id="private-block-' . $privateBlock->ID . '">';
      $html .= '<a href="" class="edit-private-block">' . __( 'edit', 'bim-quality-blocks' ) . '</a>';
      $html .= '<strong class="title">' . $privateBlock->post_title . '</strong><br />';
      $html .= '<div class="text">' . $privateBlock->post_content . '</div>';
      $html .= '<div class="xml hidden">' . get_post_meta( $privateBlock->ID, 'xml', true ) . '</div>';
      $html .= '</div>';
      return $html;
   }

   public static function showReportPage() {
      $report = json_decode( stripslashes( $_POST['report'] ) );
      if( isset( $report, $report->buildingType, $report->blocks, $report->title, $report->privateBlocks ) && $report->title != '' && is_array( $report->blocks ) && is_array( $report->privateBlocks ) ) {
         try {
            $options = BIMQualityBlocks::getOptions();
            $reportText = '<h1>' . __( 'BIM Quality Blocks Report', 'bim-quality-blocks' ) . '</h1>';
            $xml = isset( $options['xml_header'] ) ? $options['xml_header'] : '';
            $buildingType = get_post( intval( $report->buildingType ) );
            $reportText .= __( 'Building type', 'bim-quality-blocks' ) . ': ' . $buildingType->post_title;
            $downloadFiles = Array();
            foreach( $report->blocks as $layer ) {
               if( !isset( $layer->type ) || !isset( BIMQualityBlocks::$layers[$layer->type] ) ) {
                  throw new \Exception( 'Invalid report submitted' );
               }
               if( count( $layer->blocks ) > 0 ) {
                  $reportText .= '<h2>' . BIMQualityBlocks::$layers[ $layer->type ] . '</h2>';
                  foreach( $layer->blocks as $blockId ) {
                     $block = new QualityBlock( $blockId );
                     $reportText .= '<h3>' . $block->post->post_title . '</h3>';
                     $reportText .= '<p>' . $block->reportText . '</p>';
                     $attachments = get_children( Array(
                         'post_parent'    => $block->post->ID,
                         'post_type'      => 'attachment',
                         'post_mime_type' => Array( 'application/doc', 'application/zip', 'application/pdf', 'text/plain' ),
                         'numberposts'    => - 1
                     ) );
                     $downloadFiles = array_merge( $downloadFiles, $attachments );
                     $xml .= $block->reportXml;
                  }
               }
            }
            if( count( $report->privateBlocks ) > 0 ) {
               $reportText .= '<h2>' . __( 'Private blocks', 'bim-quality-blocks' ) . '</h2>';
               foreach( $report->privateBlocks as $privateBlockId ) {
                  $privateBlock = get_post( $privateBlockId );
                  if( $privateBlock->post_author == get_current_user_id() && $privateBlock->post_type == $options['private_bqblocks_post_type'] ) {
                     $reportText .= '<h3>' . $privateBlock->post_title . '</h3>';
                     $reportText .= '<p>' . $privateBlock->post_content . '</p>';
                     $xml .= get_post_meta( $privateBlock->ID, 'xml', true );
                  }
               }
            }

            $xml .= $options['xml_footer'];
            // write report to post type
            $postData = Array(
               'post_title' => $report->title,
               'post_content' => $reportText,
               'post_type' => $options['report_post_type']
            );
            $reportId = wp_insert_post( $postData );
            add_post_meta( $reportId, '_report', $report );
            add_post_meta( $reportId, '_xml', $xml );
            $downloadFileIds = Array();
            print( '<a href="' . add_query_arg( Array( 'action' => 'bqb_download_report', 'id' => $reportId ), admin_url( 'admin-ajax.php' ) ) . '" target="_blank" id="download-report-link">' . __( 'Download report', 'bim-quality-blocks' ) . '</a><br />' );
            print( '<a href="' . add_query_arg( Array( 'action' => 'bqb_download_xml', 'id' => $reportId ), admin_url( 'admin-ajax.php' ) ) . '" target="_blank" id="download-xml-link">' . __( 'Download XML', 'bim-quality-blocks' ) . '</a><br />' );
            if( count( $downloadFiles ) > 0 ) {
               print( '<h3>' . __( 'Documents', 'bim-quality-blocks' ) . '</h3>' );
               print( '<ul>' );
               foreach( $downloadFiles as $attachment ) {
                  print( '<li><a href="' . wp_get_attachment_url( $attachment->ID ) . '" target="_blank">' . $attachment->post_title . '</a></li>' );
                  $downloadFileIds[] = $attachment->ID;
               }
               print( '</ul>' );
            }
            add_post_meta( $reportId, '_downloads', $downloadFileIds );
         } catch( \Exception $e ) {
            print( '<p>' . __( 'Invalid report submitted, please try again', 'bim-quality-blocks' ) . '</p>' );
         }
      } else {
         print( '<p>' . __( 'Invalid report submitted, please try again', 'bim-quality-blocks' ) . '</p>' );
      }
   }

   /**
    * @param $userId
    *
    * @return array
    */
   public static function getDisplayName( $userId ) {
      if( !is_object( $userId ) && $userId == 0 ) {
         return '';
      } else {
         /** @var \wpdb $wpdb */
         global $wpdb;
         if( is_object( $userId ) ) {
            $displayName = $userId->display_name;
         } else {
            $displayName = $wpdb->get_var( "SELECT display_name
					FROM {$wpdb->users}
					WHERE ID = " . intval( $userId ) );
         }
         if( strpos( $displayName, '@' ) !== false ) {
            $displayName = explode( '@', $displayName );
            return $displayName[0];
         } else {
            return $displayName;
         }
      }
   }
	
	public static function editorWidget() {
		global $post;//, $wpdb;
		
		//$options = BIMQualityBlocks::getOptions();

      $blocks = BIMQualityBlocks::getQualityBlocks();
      $layer = get_post_meta( $post->ID, '_block_layer', true );
      $behaviour = get_post_meta( $post->ID, '_special_behaviour', true );
      $relations = get_post_meta( $post->ID, '_relations', true );
      $deselects = get_post_meta( $post->ID, '_deselects', true );
?>
		<div id="bim-quality-blocks-container">
			<h4><?php _e( 'BIM Quality Block settings', 'bim-quality-blocks' ); ?></h4>
         <label for="block-special-type"><?php _e( 'Block special behaviour', 'bim-quality-blocks' ); ?></label><br />
         <select id="block-special-type" name="quality_block_behaviour">
            <option value="normal"<?php print( 'normal' == $behaviour ? ' selected' : '' ); ?>><?php _e( 'Normal block', 'bim-quality-blocks' ); ?></option>
            <option value="exclude_entire_layer"<?php print( 'exclude_entire_layer' == $behaviour ? ' selected' : '' ); ?>><?php _e( 'When selected disable all others in the layer', 'bim-quality-blocks' ); ?></option>
         </select><br />
         <label for="block-layer"><?php _e( 'Block layer', 'bim-quality-blocks' ); ?></label><br />
         <select id="block-layer" name="quality_block_layer">
            <?php
            foreach( BIMQualityBlocks::$layers as $key => $label ) {
               ?>
               <option value="<?php print( $key ); ?>"<?php print( $layer == $key ? ' selected' : '' ); ?>><?php print( $label ); ?></option>
               <?php
            }
            ?>
         </select><br />
         <label for="block-relations"><?php _e( 'Selecting this block excludes the following block(s)', 'bim-quality-blocks' ); ?></label><br />
         <select multiple="multiple" id="block-relations" name="quality_block_relations[]">
            <?php
            foreach( BIMQualityBlocks::$layers as $key => $label ) {
               ?>
               <optgroup label="<?php print( $label ); ?>">
            <?php
               foreach( $blocks as $block ) {
                  if( $block->layer == $key && $block->post->ID != $post->ID ) {
                     print( '<option value="' . $block->post->ID . '"' . ( in_array( $block->post->ID, $relations ) ? ' selected' : '' ) . '>' . apply_filters( 'the_title', $block->post->post_title ) . '</option>' );
                  }
               }
               ?>
                  </optgroup>
            <?php
            }
            ?>
         </select><br />
         <label for="block-deselects"><?php _e( 'Selecting this block deselects the following block(s)', 'bim-quality-blocks' ); ?></label><br />
         <select multiple="multiple" id="block-deselects" name="quality_block_deselects[]">
            <?php
            foreach( BIMQualityBlocks::$layers as $key => $label ) {
               ?>
               <optgroup label="<?php print( $label ); ?>">
                  <?php
                  foreach( $blocks as $block ) {
                     if( $block->layer == $key && $block->post->ID != $post->ID ) {
                        print( '<option value="' . $block->post->ID . '"' . ( in_array( $block->post->ID, $deselects ) ? ' selected' : '' ) . '>' . apply_filters( 'the_title', $block->post->post_title ) . '</option>' );
                     }
                  }
                  ?>
               </optgroup>
            <?php
            }
            ?>
         </select><br />
         <label for="block-report-text"><?php _e( 'Report text', 'bim-quality-blocks' ); ?></label><br />
         <textarea id="block-report-text" name="quality_block_report_text" placeholder="<?php _e( 'Report text used in the final report if this block is enabled', 'bim-quality-blocks' ); ?>"><?php print( get_post_meta( $post->ID, '_report_text', true ) ); ?></textarea><br />
         <label for="block-report-xml"><?php _e( 'Report xml', 'bim-quality-blocks' ); ?></label><br />
         <textarea id="block-report-xml" name="quality_block_report_xml" placeholder="<?php _e( 'Report XML used in the final report XML if this block is enabled', 'bim-quality-blocks' ); ?>"><?php print( get_post_meta( $post->ID, '_report_xml', true ) ); ?></textarea><br />
		</div>
		<input type="hidden" name="bim_quality_blocks_noncename" value="<?php print( wp_create_nonce(__FILE__) ); ?>" />
<?php
	}
		
	public static function saveEditorWidget( $postId ) {
		//global $wpdb;
		if( !isset( $_POST[ 'bim_quality_blocks_noncename' ] ) || !wp_verify_nonce( $_POST[ 'bim_quality_blocks_noncename' ], __FILE__ ) ) {
			return $postId;
		}
		if ( !current_user_can( 'edit_post', $postId ) ) {
			return $postId;
		}

      $post = get_post( $postId );
      $options = BIMQualityBlocks::getOptions();

      if( $post->post_type == $options[ 'bim_quality_blocks_post_type' ] ) {
         $behaviour = filter_input( INPUT_POST, 'quality_block_behaviour', FILTER_SANITIZE_STRING );
         if( !isset( $behaviour ) || !$behaviour ) {
            $behaviour = '';
         }
         update_post_meta( $postId, '_special_behaviour', $behaviour );

         if( isset( $_POST['quality_block_relations'] ) && is_array( $_POST['quality_block_relations'] ) ) {
            $relations = Array();
            foreach( $_POST['quality_block_relations'] as $value ) {
               $relations[] = intval( $value );
            }
         } else {
            $relations = Array();
         }
         update_post_meta( $postId, '_relations', $relations );

         if( isset( $_POST['quality_block_deselects'] ) && is_array( $_POST['quality_block_deselects'] ) ) {
            $relations = Array();
            foreach( $_POST['quality_block_deselects'] as $value ) {
               $relations[] = intval( $value );
            }
         } else {
            $relations = Array();
         }
         update_post_meta( $postId, '_deselects', $relations );

         $reportText = filter_input( INPUT_POST, 'quality_block_report_text', FILTER_SANITIZE_STRING );
         if( !isset( $reportText ) || !$reportText ) {
            $reportText = '';
         }
         update_post_meta( $postId, '_report_text', $reportText );

         $reportXml = filter_input( INPUT_POST, 'quality_block_report_xml', FILTER_DEFAULT );
         if( !isset( $reportXml ) || !$reportXml ) {
            $reportXml = '';
         }
         update_post_meta( $postId, '_report_xml', $reportXml );

         $layer = filter_input( INPUT_POST, 'quality_block_layer', FILTER_SANITIZE_STRING );
         if( !isset( $layer ) || !$layer ) {
            $layer = '';
         }
         update_post_meta( $postId, '_block_layer', $layer );
      }
      return $postId;
	}

   public static function showReportXml() {
      if( is_user_logged_in() && isset( $_GET['id'] ) ) {
         $options = BIMQualityBlocks::getOptions();
         $report = get_post( intval( $_GET['id'] ) );
         if( isset( $report ) && $report->post_author == get_current_user_id() && $report->post_type == $options['report_post_type'] ) {
            header( 'Content-type: text/xml' );
            print( get_post_meta( $report->ID, '_xml', true ) );
            exit();
         } else {
            _e( 'Report not available', 'bim-quality-blocks' );
         }
      } else {
         _e( 'Reports are only available if you log in', 'bim-quality-blocks' );
      }
   }


   public static function downloadReport() {
      if( is_user_logged_in() && isset( $_GET['id'] ) ) {
         $options = BIMQualityBlocks::getOptions();
         $report = get_post( intval( $_GET['id'] ) );
         if( isset( $report ) && $report->post_author == get_current_user_id() && $report->post_type == $options['report_post_type'] ) {
            header( 'Content-type: application/vnd.ms-word' );
            header( 'Content-Disposition: attachment;Filename=bim-quality-blocks-report.doc' );
            ?>
<html
    xmlns:o='urn:schemas-microsoft-com:office:office'
    xmlns:w='urn:schemas-microsoft-com:office:word'
    xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
    	<title><?php print( $report->post_title ); ?></title>
    	<xml>
    	    <w:worddocument xmlns:w="#unknown">
	            <w:view>Print</w:view>
            	<w:zoom>90</w:zoom>
            	<w:donotoptimizeforbrowser />
        	</w:worddocument>
    	</xml>
	</head>
	<body lang=EN-US><?php print( $report->post_content ); ?></body>
</html>
         <?php
            exit(); // Just to be sure nothing outputs anything after we print the report
         } else {
            _e( 'Report not available', 'bim-quality-blocks' );
         }
      } else {
         _e( 'Reports are only available if you log in', 'bim-quality-blocks' );
      }
   }

   public static function showBIMQualityBlocksReportList() {
      if( is_user_logged_in() ) {
         $options = BIMQualityBlocks::getOptions();
         $reports = get_posts( Array(
            'post_type' => $options['report_post_type'],
            'post_author' => get_current_user_id(),
            'post_status' => 'draft',
            'posts_per_page' => -1
         ) );
         if( count( $reports ) > 0 ) {
            $buildingTypes = Array();
            print( '<table>' );
            print( '<tr><th>' . __( 'Building type', 'bim-quality-blocks' ) . '</th><th>' . __( 'Title', 'bim-quality-blocks' ) . '</th><th>' . __( 'XML', 'bim-quality-blocks' ) . '</th><th>' . __( 'Documents', 'bim-quality-blocks' ) . '</th><th>' . __( 'Date', 'bim-quality-blocks' ) . '</th></tr>' );
            foreach( $reports as $report ) {
               $downloadFiles = get_post_meta( $report->ID, '_downloads', true );
               $reportData = get_post_meta( $report->ID, '_report', true );
               if( count( $downloadFiles ) > 0 ) {
                  $html = '<ul class="table-ul">';
                  foreach( $downloadFiles as $attachmentId ) {
                     $attachment = get_post( $attachmentId );
                     $html .= '<li><a href="' . wp_get_attachment_url( $attachmentId ) . '" target="_blank">' . $attachment->post_title . '</a></li>';
                  }
                  $html .= '</ul>';
               } else {
                  $html = __( 'No documents', 'bim-quality-blocks' );
               }

               if( !isset( $buildingTypes[ $reportData->buildingType ] ) ) {
                  $buildingTypes[ $reportData->buildingType ] = get_post( $reportData->buildingType );
               }

               print( '<tr>' .
                   '<td>' . $buildingTypes[ $reportData->buildingType ]->post_title . '</td>' .
                   '<td><a href="' . add_query_arg( Array( 'action' => 'bqb_download_report', 'id' => $report->ID ), admin_url( 'admin-ajax.php' ) ) . '" target="_blank" id="download-report-link">' . $report->post_title . '</a></td>' .
                   '<td><a href="' . add_query_arg( Array( 'action' => 'bqb_download_xml', 'id' => $report->ID ), admin_url( 'admin-ajax.php' ) ) . '" target="_blank" id="download-xml-link">' . __( 'Download XML', 'bim-quality-blocks' ) . '</a></td>' .
                   '<td>' . $html . '</td>' .
                   '<td>' . date( 'Y-m-d H:i', strtotime( $report->post_date ) ) . '</td></tr>' );
            }
            print( '</table>' );
         } else {
            print( '<p>' . __( 'You have no reports, try to create one', 'bim-quality-blocks' ) . '</p>' );
         }
      } else {
         print( '<p>' . __( 'Log in to be able to use BIM quality blocks', 'bim-quality-blocks' ) . '</p>' );
      }
   }

   public static function ajaxCallback() {
      // Save or update private blocks
      if( is_user_logged_in() && isset( $_POST['title'] ) && $_POST['title'] != '' ) {
         $options = BIMQualityBlocks::getOptions();
         $existing = false;
         if( isset( $_POST['id'] ) && ctype_digit( $_POST['id'] ) ) {
            $existing = get_post( intval( $_POST['id'] ) );
            if( !isset( $existing ) || $existing->post_type != $options['private_bqblocks_post_type'] || $existing->post_author != get_current_user_id() ) {
               $existing = false;
            }
         }
         $reportText = ( isset( $_POST['text'] ) && $_POST['text'] != '' ) ? sanitize_text_field( $_POST['text'] ) : '';
         $reportXml = ( isset( $_POST['xml'] ) && $_POST['xml'] != '' ) ? sanitize_text_field( $_POST['xml'] ) : '';
         $postData = Array(
            'post_title' => sanitize_text_field( $_POST['title'] ),
            'post_content' => $reportText,
            'post_type' => $options['private_bqblocks_post_type'],
            'post_status' => 'private'
         );

         if( $existing === false ) {
            $postId = wp_insert_post( $postData );
            add_post_meta( $postId, 'xml', $reportXml );
         } else {
            $postData['ID'] = $postId = $existing->ID;
            wp_update_post( $postData );
            update_post_meta( $postId, 'xml', $reportXml );
         }
         $post = get_post( $postId );
         print( BIMQualityBlocks::getPrivateBlockHtml( $post ) );
      }
      exit(); // When we are done we do not allow anything else to be done
   }
}

$bimQualityBlocks = new BIMQualityBlocks();
