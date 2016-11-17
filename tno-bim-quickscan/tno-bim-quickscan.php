<?php
/*
Plugin Name: TNO BIM Quickscan
Plugin URI: http://www.tno.nl
Description:
Version: 1.0
Author: Bastiaan Grutters
Author URI: http://www.bastiaangrutters.nl
*/

class TNOBIMQuickscan {
	public $options;
	private $isAdvisorCache = Array();
	public $colors = Array(
		 'fillColor' => Array( 'rgba(220,220,220,0.4)', 'rgba(151,187,205,0.5)', 'rgba(197,241,158,0.5)', 'rgba(40,100,147,0.5)', 'rgba(153,95,227,0.5)', 'rgba(254,95,87,0.5)', 'rgba(225,159,32,0.5)', 'rgba(220,225,32,0.5)', 'rgba(18,219,41,0.5)', 'rgba(18,219,206,0.5)', 'rgba(18,97,219,0.5)', 'rgba(158,0,185,0.5)' ),
		 'strokeColor' => Array( 'rgba(220,220,220,1)', 'rgba(151,187,205,1)', 'rgba(121,187,62,1)', 'rgba(8,69,118,1)', 'rgba(111,41,201,1)', 'rgba(254,95,87,1)', 'rgba(225,159,32,1)', 'rgba(220,225,32,1)', 'rgba(18,219,41,1)', 'rgba(18,219,206,1)', 'rgba(18,97,219,0.5)', 'rgba(158,0,185,1)' ),
		 'pointColor' => Array( 'rgba(220,220,220,1)', 'rgba(151,187,205,1)', 'rgba(121,187,62,1)', 'rgba(8,69,118,1)', 'rgba(111,41,201,1)', 'rgba(254,95,87,1)', 'rgba(225,159,32,1)', 'rgba(220,225,32,1)', 'rgba(18,219,41,1)', 'rgba(18,219,206,1)', 'rgba(18,97,219,0.5)', 'rgba(158,0,185,1)' ),
		 'pointStrokeColor' => Array( '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff' ),
	);
	public static $coreBusinessesEnglish = Array(
		 'aannemer' => 'contractor',
		 'architect' => 'architect',
		 'bouwer' => 'builder',
		 'constructeur' => 'constructor/design engineer',
		 'installateur' => 'installer',
		 'installatieadvies' => 'installation adviser',
		 'ontwikkelaar' => 'developer',
		 'anders' => 'other',
		 'opdrachtgever' => 'client'
	);

	public function __construct() {
		add_action( 'admin_menu', Array( &$this, 'optionsMenu' ) );

		$this->options = get_option( 'tno_bim_quickscan_options' );
		if ( !isset ( $this->options ) ) {
			$this->options = Array ();
		}

		add_action( 'admin_enqueue_scripts', Array( &$this, 'adminEnqueueScripts' ) );
		add_action( 'wp_enqueue_scripts', Array( &$this, 'enqueueScripts' ) );
		add_action( 'wp_footer', Array( &$this, 'wpFooter' ), 100 );
		add_action( 'admin_footer', Array( &$this, 'adminFooter' ), 1000 );
		add_action( 'wp_dashboard_setup', Array( &$this, 'dashboardWidgets' ) );

		// Add filters and actions for Gravity Forms
		add_filter( 'gform_add_field_buttons', Array( &$this, 'addMapField' ) );
		add_filter( 'gform_field_type_title', Array( &$this, 'getTitle' ), 10, 2 );
		add_action( 'gform_editor_js', Array( &$this, 'gformEditorJs' ) );
		add_filter( 'gform_get_field_value', Array( &$this, 'getFieldValue' ), 10, 3 );
		add_filter( 'gform_save_field_value', Array( &$this, 'gFormSaveFieldValue' ), 10, 4 );
		add_action( 'gform_after_submission', Array( &$this, 'gFormAfterSubmission' ), 10, 2 );
		add_action( 'gform_pre_render', Array( &$this, 'gFormPreRender' ) );
		add_filter( 'gform_validation', Array( &$this, 'gFormValidation' ) );
		add_filter( 'gform_field_standard_settings', Array( &$this, 'gformFieldStandardSettings' ), 10, 2 );
		add_filter( 'gform_tooltips', Array( &$this, 'gformTooltips' ) );
		add_filter( 'gform_field_content', Array( &$this, 'displayUserSelection' ), 10, 5 );
		add_filter( 'gform_add_field_buttons', Array( &$this, 'addMapField' ) );

		add_action( 'init', Array( &$this, 'wordPressInit' ) );
		add_filter( 'map_meta_cap', Array( &$this, 'mapMetaCap' ), 10, 4 );

		//add_filter( 'parse_query', Array( &$this, 'parseQuery' ) );
		add_filter( 'posts_where', Array( &$this, 'postsWhere' ), 999, 2 );
		add_filter( 'posts_join', Array( &$this, 'postsJoin' ), 999, 2 );


		register_deactivation_hook( __FILE__, Array( &$this, 'deactivation' ) );
		register_activation_hook( __FILE__, Array( &$this, 'activation' ) );

		// Load the correct language file
		add_action( 'plugins_loaded', Array( 'TNOBIMQuickscan', 'pluginsLoaded' ) );

		// --- Add shortcodes ---
		add_shortcode( 'showCharts', Array( 'TNOBIMQuickscan', 'showCharts' ) );
		add_shortcode( 'showPublicCompanyList', Array( 'TNOBIMQuickscan', 'showPublicCompanyList' ) );
		add_shortcode( 'showPublicReportList', Array( 'TNOBIMQuickscan', 'showPublicReportList' ) );
		add_shortcode( 'showMyReportList', Array( 'TNOBIMQuickscan', 'showMyReportList' ) );
		add_shortcode( 'showSingleCompany', Array( 'TNOBIMQuickscan', 'showSingleCompany' ) );
		add_shortcode( 'showSingleReport', Array( 'TNOBIMQuickscan', 'showSingleReport' ) );
	}

	public static function pluginsLoaded() {
		load_plugin_textdomain( 'tno-bim-quickscan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function wordPressInit() {
		// Register custom post type and taxonomies
		$args = Array(
			 'labels' => Array(
				  'name' => _x( 'Reports', 'tno-bim-quickscan' ),
				  'singular_name' => _x( 'Report', 'tno-bim-quickscan' ),
				  'add_new' => _x( 'Add New', 'tno-bim-quickscan' ),
				  'add_new_item' => __( 'Add New Report', 'tno-bim-quickscan' ),
				  'edit_item' => __('Edit Report', 'tno-bim-quickscan' ),
				  'new_item' => __('New Report', 'tno-bim-quickscan' ),
				  'all_items' => __('All Reports', 'tno-bim-quickscan' ),
				  'view_item' => __('View Report', 'tno-bim-quickscan' ),
				  'search_items' => __('Search Reports', 'tno-bim-quickscan' ),
				  'not_found' =>  __('No report found', 'tno-bim-quickscan' ),
				  'not_found_in_trash' => __('No reports found in Trash', 'tno-bim-quickscan' ),
				  'parent_item_colon' => '',
				  'menu_name' => 'Reports' ),
			 'public' => true,
			 'publicly_queryable' => true,
			 'show_ui' => true,
			 'show_in_menu' => true,
			 'query_var' => true,
			 'rewrite' => true,
			 'capability_type' => 'rapport',
			 'capabilities' => Array(
				  'publish_posts' => 'publish_rapport',
				  'edit_posts' => 'edit_rapport',
				  'edit_others_posts' => 'edit_others_rapport',
				  'delete_posts' => 'delete_rapport',
				  'delete_others_posts' => 'delete_others_rapport',
				  'read_private_posts' => 'read_private_rapport',
				  'edit_post' => 'edit_rapport',
				  'delete_post' => 'delete_rapport',
				  'read_post' => 'read_rapport'
			 ),
			 'map_meta_cap' => false,
			 'has_archive' => false,
			 'hierarchical' => false,
			 'menu_position' => null,
			 'supports' => array( 'title', 'thumbnail', 'author' )
		);
		register_post_type( 'rapport', $args );

		$postTypeArguments = Array(
			 'labels' => Array(
				  'name' => _x( 'Advisors', 'post type general name' ),
				  'singular_name' => _x( 'Advisor', 'post type singular name'),
				  'add_new' => __( 'Add New' ),
				  'add_new_item' => __( 'Add New Advisor' ),
				  'edit_item' => __( 'Edit Advisor' ),
				  'new_item' => __( 'New Advisor' ),
				  'all_items' => __( 'All Advisors' ),
				  'view_item' => __( 'View Advisor' ),
				  'search_items' => __( 'Search Advisors' ),
				  'not_found' =>  __( 'No Advisors found' ),
				  'not_found_in_trash' => __( 'No Advisors found in Trash' ),
				  'parent_item_colon' => '',
				  'menu_name' => 'Advisors' ),
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
		register_post_type( 'advisor', $postTypeArguments );


		$args = Array(
			 'hierarchical'        => false,
			 'labels'              => Array(
				  'name'                => _x( 'Topics', 'tno-bim-quickscan' ),
				  'singular_name'       => _x( 'Topic', 'tno-bim-quickscan' ),
				  'search_items'        => __( 'Search Topics', 'tno-bim-quickscan' ),
				  'all_items'           => __( 'All Topics', 'tno-bim-quickscan' ),
				  'parent_item'         => __( 'Parent Topic', 'tno-bim-quickscan' ),
				  'parent_item_colon'   => __( 'Parent Topic:', 'tno-bim-quickscan' ),
				  'edit_item'           => __( 'Edit Topic', 'tno-bim-quickscan' ),
				  'update_item'         => __( 'Update Topic', 'tno-bim-quickscan' ),
				  'add_new_item'        => __( 'Add New Topic', 'tno-bim-quickscan' ),
				  'new_item_name'       => __( 'New Topic Name', 'tno-bim-quickscan' ),
				  'menu_name'           => __( 'Topic', 'tno-bim-quickscan' )
			 ),
			 'show_ui'             => true,
			 'show_admin_column'   => true,
			 'query_var'           => true,
			 'rewrite'             => array( 'slug' => 'topic' )
		);
		register_taxonomy( 'topic', 'rapport', $args );

		$args = Array(
			 'hierarchical'        => false,
			 'labels'              => Array(
				  'name'                => _x( 'BIM Quickscan categories', 'tno-bim-quickscan' ),
				  'singular_name'       => _x( 'BIM Quickscan category', 'tno-bim-quickscan' ),
				  'search_items'        => __( 'Search categories', 'tno-bim-quickscan' ),
				  'all_items'           => __( 'All categories', 'tno-bim-quickscan' ),
				  'parent_item'         => __( 'Parent category', 'tno-bim-quickscan' ),
				  'parent_item_colon'   => __( 'Parent category:', 'tno-bim-quickscan' ),
				  'edit_item'           => __( 'Edit category', 'tno-bim-quickscan' ),
				  'update_item'         => __( 'Update category', 'tno-bim-quickscan' ),
				  'add_new_item'        => __( 'Add new category', 'tno-bim-quickscan' ),
				  'new_item_name'       => __( 'New category Name', 'tno-bim-quickscan' ),
				  'menu_name'           => __( 'Category', 'tno-bim-quickscan' )
			 ),
			 'show_ui'             => true,
			 'show_admin_column'   => true,
			 'query_var'           => true,
			 'rewrite'             => array( 'slug' => 'bim_category' )
		);
		register_taxonomy( 'bim_category', 'rapport', $args );

		$adminRole = get_role( 'administrator' );
		if( !$adminRole->has_cap( 'publish_rapport' ) ) {
			$adminRole->add_cap( 'publish_rapport' );
			$adminRole->add_cap( 'edit_rapport' );
			$adminRole->add_cap( 'edit_others_rapport' );
			$adminRole->add_cap( 'delete_rapport' );
			$adminRole->add_cap( 'delete_others_rapport' );
			$adminRole->add_cap( 'read_private_rapport' );
			$adminRole->add_cap( 'edit_rapport' );
			$adminRole->add_cap( 'delete_rapport' );
			$adminRole->add_cap( 'read_rapport' );
		}

		$request = basename( $_SERVER[ 'REQUEST_URI' ] );
		if( $request == 'wp-login.php?action=register' ) {
			global $sitepress;
			if( isset( $sitepress ) ) {
				$sitepress->switch_lang( 'en', true );
			}
			wp_redirect( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $this->options[ 'register_page' ], 'page', true ) : $this->options[ 'register_page' ] ), 301 );
			exit();
		}
	}

	public function activation() {
		add_role( 'bedrijf', 'Bedrijf', Array(
			 'read' => true, // True allows that capability
			 'edit_rapport' => true,
			 'edit_private_rapport' => true,
			 'publish_rapport' => true,
		) );

		add_role( 'adviseur', 'Adviseur', Array(
			 'read' => true, // True allows that capability
			 'edit_rapport' => true,
			 'edit_private_rapport' => true,
			 'edit_advised_rapport' => true,
			 'publish_rapport' => true,
			 'read_rapport' => true
		) );
	}

	public function deactivation() {
		remove_role( 'adviseur' );
		remove_role( 'bedrijf' );
	}

	public function postsWhere( $where, $query ) {
		global $wpdb;
		$queryVars = & $query->query_vars;

		if( $queryVars[ 'post_type' ] == $this->options[ 'report_post_type' ] ) {
			$isAdvisor = false;
			$currentUserObject = wp_get_current_user();
			foreach( $currentUserObject->roles as $role ) {
				if( $role == $this->options[ 'adviser_role' ] ) {
					$isAdvisor = true;
					break;
				}
			}
			if( $isAdvisor || current_user_can( 'activate_plugins' ) ) {
				$start = strpos( $where, 'post_status = \'private\'' );
				$alsoPublished = strpos( $where, 'post_status = \'publish\'' );
				if( $start !== false ) {
					$start += 23;
					$where = substr( $where, 0, $start ) .
						 ( current_user_can( 'activate_plugins' ) ? '' : " AND post_author = {$currentUserObject->ID}" ) .
						 " ) OR ( ( post_status = 'private' " .
						 ( $alsoPublished !== false ? " OR post_status = 'publish' " : '' ) .
						 ") AND advisor_meta.meta_value = {$currentUserObject->ID} " . substr( $where, $start );
				}
			}
		}

		return $where;
	}

	public function postsJoin( $join, $query ) {
		global $wpdb;
		$queryVars = & $query->query_vars;

		if( $queryVars[ 'post_type' ] == $this->options[ 'report_post_type' ] ) {
			$isAdvisor = false;
			$currentUserObject = wp_get_current_user();
			foreach( $currentUserObject->roles as $role ) {
				if( $role == $this->options[ 'adviser_role' ] ) {
					$isAdvisor = true;
					break;
				}
			}
			if( $isAdvisor || current_user_can( 'activate_plugins' ) ) {
				$join .= " LEFT JOIN $wpdb->postmeta AS advisor_meta ON advisor_meta.post_id = ID AND advisor_meta.meta_key = '_advisor' AND advisor_meta.meta_value = {$currentUserObject->ID}";
			}
		}

		return $join;
	}

	public function mapMetaCap( $caps, $cap, $userId, $args ) {
		if( 'edit_rapport' == $cap || 'delete_rapport' == $cap || 'read_rapport' == $cap || 'edit_advised_rapport' == $cap || 'read_private_rapport' == $cap ) {
			if( is_array( $args ) && count( $args ) > 0 ) {
				$post = get_post( $args[0] );
				$postType = get_post_type_object( $post->post_type );
				$caps = Array();

				switch( $cap ) {
					case 'edit_rapport':
						$advisedBy = get_post_meta( $args[0], '_advisor', true );
						$caps[] = ( $userId == $post->post_author || $userId == $advisedBy ) ? $postType->cap->edit_posts : $postType->cap->edit_others_posts;
						break;
					case 'delete_rapport':
						$caps[] = ( $userId == $post->post_author ) ? $postType->cap->delete_posts : $postType->cap->delete_others_posts;
						break;
					case 'read_rapport':
						$advisedBy = get_post_meta( $post->ID, '_advisor', true );
						$caps[] = ( 'private' != $post->post_status || $userId == $post->post_author || $userId == $advisedBy ) ? 'read' : $postType->cap->read_private_posts;
						break;
					case 'edit_advised_rapport':
						$advisedBy = get_post_meta( $post->ID, '_advisor', true );
						$caps[] = ( $userId == $advisedBy ) ? $postType->cap->edit_posts : $postType->cap->edit_others_posts;
						break;
					case 'read_private_rapport':
						$advisedBy = get_post_meta( $post->ID, '_advisor', true );
						$caps[] = ( $userId == $advisedBy ) ? 'read' : $postType->cap->read_private_posts;
						break;
				}
			}
		}

		return $caps;
	}

	public function optionsMenu() {
		$pfile = basename( dirname( __FILE__ ) ) . '/tno-bim-quickscan-options.php';
		add_options_page( 'tno-bim-quickscan', 'TNO BIM Quickscan options', 'activate_plugins', $pfile );
	}

	public function adminEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
		//wp_enqueue_script( 'tno-bim-quickscan', plugins_url( '/tno-bim-quickscan-admin.js', __FILE__ ), Array( 'jquery' ) );
		wp_enqueue_style( 'tno-bim-quickscan', plugins_url( '/tno-bim-quickscan-admin.css', __FILE__ ) );
	}

	public function enqueueScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tno-bim-quickscan', plugins_url( '/tno-bim-quickscan.js', __FILE__ ), Array( 'jquery' ), 1.0, true );
		wp_enqueue_script( 'excanvas', plugins_url( '/excanvas.compiled.js', __FILE__ ) );
		wp_enqueue_script( 'chart', plugins_url( '/Chart.min.js', __FILE__ ), Array( 'jquery' ), 1.0, true );
		wp_enqueue_script( 'slider', plugins_url( '/jquery-ui-1.10.4.custom.min.js', __FILE__ ) );
		wp_enqueue_script( 'bim-chart', plugins_url( '/bim-chart.js', __FILE__ ), Array( 'jquery', 'chart' ), 1.0, true );
		wp_enqueue_style( 'jquery-ui-slider', plugins_url( '/jquery-ui-1.10.4.custom.css', __FILE__ ) );
		wp_enqueue_style( 'tno-bim-quickscan', plugins_url( '/tno-bim-quickscan.css', __FILE__ ), Array( 'jquery-ui-slider' ) );
	}

	public function updatePostMeta( $postId, $metaKey ) {
		$currentData = get_post_meta( $postId, $metaKey, true );

		$newData = $_POST[$metaKey];

		if( isset( $currentData ) ) {
			if( is_null( $newData ) ) {
				delete_post_meta( $postId, $metaKey );
			} else {
				update_post_meta( $postId, $metaKey, $newData );
			}
		} elseif ( !is_null( $newData ) ) {
			add_post_meta( $postId, $metaKey, $newData, true );
		}
	}

	public function addMapField( $fieldGroups ) {
		foreach( $fieldGroups as &$group ){
			if( $group[ 'name' ] == 'advanced_fields' ) {
				$group[ 'fields' ][] = Array( 'class' => 'button', 'value' => __( 'Select company', 'tno-bim-quickscan' ), 'onclick' => 'StartAddField( \'tno_bim_bedrijf\' );' );
				break 1;
			}
		}
		return $fieldGroups;
	}

	public function displayUserSelection( $content, $field, $value, $leadId, $formId ) {
		global $gform_update_post;

		if( isset( $field[ 'type' ] ) && $field[ 'type' ] == 'tno_bim_bedrijf' ) {
			if( RG_CURRENT_VIEW == "entry" ){
				$mode = empty( $_POST[ 'screen_mode' ] ) ? 'view' : $_POST[ 'screen_mode' ];
				if( $mode == 'view' ) {
					$content = '<tr>
					<td colspan=\'2\' class=\'entry-view-field-name\'>'. esc_html( GFCommon::get_label( $field ) ) . '</td>
					</tr>
					<tr>
					<td class=\'entry-view-field-value\' colspan=\'2\'>' . esc_html( $value ) . '</td>
					</tr>';
				} else {
					$content = "<tr valign='top'>
					<td class='detail-view'>
					<div style='margin-bottom:10px; border-bottom:1px dotted #ccc;'><h2 class='detail_gsection_title'>" . esc_html(GFCommon::get_label($field)) . "</h2></div>
					</td>
					</tr>";
				}
			} else {
				$deleteFieldLink = "<a class='field_delete_icon' id='gfield_delete_{$field['id']}' title='" . __( 'click to delete this field', 'tno-bim-quickscan' ) . "' href='javascript:void(0);' onclick='StartDeleteField(this);'>" . __( 'Delete', 'tno-bim-quickscan' ) . "</a>";
				$deleteFieldLink = apply_filters( 'gform_delete_field_link', $deleteFieldLink );

				//The edit and delete links need to be added to the content (if desired), when using this hook
				if( IS_ADMIN ) {
					$adminButtons = '<div class=\'gfield_admin_icons\'><div class=\'gfield_admin_header_title\'>' .
						 'Location: Field ID ' . $field[ 'id' ] . '</div>' .
						 $deleteFieldLink . " <a class='field_edit_icon edit_icon_collapsed' title='" . __( 'click to edit this field', 'tno-bim-quickscan' ) . "'>" . __( 'Edit', 'tno-bim-quickscan' ) . '</a></div>';
				} else {
					$adminButtons = '';
				}
				$content = $adminButtons;
				$maxChars = '';
				if(!IS_ADMIN && !empty( $field[ 'maxLength' ] ) && is_numeric( $field[ 'maxLength' ] ) ) {
					$maxChars = self::get_counter_script( $formId, $field[ 'id' ], $field[ 'maxLength' ] );
				}

				if( ctype_digit( $value ) ) {
					$currentUser = get_user_by( 'id', $value );
					$street = get_user_meta( $currentUser->ID, 'street', true );
					$postcode = get_user_meta( $currentUser->ID, 'postcode', true );
					$city = get_user_meta( $currentUser->ID, 'city', true );
					//$other = get_user_meta( $currentUser->ID, 'other', true );
				}

				$isAdvisor = false;
				$isCompany = false;
				if( is_user_logged_in() ) {
					$currentUserObject = wp_get_current_user();
					foreach( $currentUserObject->roles as $role ) {
						if( $role == $this->options[ 'adviser_role' ] ) {
							$isAdvisor = true;
						} elseif( $role == $this->options[ 'company_role' ] ) {
							$isCompany = true;
						}
					}
				}

				if( $isAdvisor || current_user_can( 'activate_plugins' ) ) {
					$tabindex = GFCommon::get_tabindex();
					$content .= '<label class=\'gfield_label sub-label\' for=\'input_' . $formId . '_' . $field[ 'id' ] . '.1\'>' . $field[ 'label' ] . ( $field[ 'isRequired' ] ? '<span class=\'gfield_required\'>*</span>' : '' ) . '</label>';
					$content .= '<div class=\'user-selection-container\' id=\'user-selection-container-' . $formId . '-' . $field[ 'id' ] . '\'>';
					$content .= '<input type=\'hidden\' class=\''. $field[ 'type' ] . ' ' . ( isset( $field[ 'cssClass' ] ) ? esc_attr( $field[ 'cssClass' ] ) : '' ) . ' ' . $field[ 'size' ] . '\' ' . ' name=\'input_' . $field[ 'id' ] . '\' value=\'' . ( !isset( $currentUser ) ? 'new' : $value ) . '\' />';
					$content .= '<div class=\'selected-user\'><span class=\'key\'>' .
						 __( 'Selected company', 'tno-bim-quickscan' ) . '</span><span class=\'value\'>' .
						 ( isset( $currentUser ) ? ( $currentUser->display_name . '  (<a href=\'javascript:void( null );\' onclick=\'TNOBIMQuickscan.addNewUser( this );\'>' . __( 'Add new company', 'tno-bim-quickscan' ) . '</a>)' )
							  : __( 'none, fill in company information or select one', 'tno-bim-quickscan' ) ) . '</span></div>';
					$content .= '<div class=\'search-container\'><label for=\'search-user-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'Search company', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' autocomplete=\'off\' id=\'search-user-' . $formId . '-' . $field[ 'id' ] . '\' /> <img src=\'' . get_bloginfo( 'wpurl' ) . '/wp-admin/images/wpspin_light.gif\' alt=\'loading...\' class=\'loading-image\' style=\'display: none\' /></div>';
					$content .= '<div class=\'address-information\'>';
					$content .= '<label for=\'name-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'Name', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' class=\'name\' id=\'name-' . $formId . '-' . $field[ 'id' ] . '\' name=\'name_' . $formId . '_' . $field[ 'id' ] . '\' value=\'' . ( isset( $_POST[ 'name_' . $formId . '_' . $field[ 'id' ] ] ) ? $_POST[ 'name_' . $formId . '_' . $field[ 'id' ] ] : ( isset( $currentUser ) ? $currentUser->display_name : '' ) ) . '\' /><br />';
					$content .= '<label for=\'email-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'Email contactpersoon', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' class=\'email\' id=\'email-' . $formId . '-' . $field[ 'id' ] . '\' name=\'email_' . $formId . '_' . $field[ 'id' ] . '\' value=\'' . ( isset( $_POST[ 'email_' . $formId . '_' . $field[ 'id' ] ] ) ? $_POST[ 'email_' . $formId . '_' . $field[ 'id' ] ] : ( isset( $currentUser ) ? $currentUser->user_login : '' ) ) . '\' /><br />';
					$content .= '<label for=\'street-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'Street', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' class=\'street\' id=\'street-' . $formId . '-' . $field[ 'id' ] . '\' name=\'street_' . $formId . '_' . $field[ 'id' ] . '\' value=\'' . ( isset( $_POST[ 'street_' . $formId . '_' . $field[ 'id' ] ] ) ? $_POST[ 'street_' . $formId . '_' . $field[ 'id' ] ] : ( isset( $street ) ? $street : '' ) ) . '\' /><br />';
					$content .= '<label for=\'postcode-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'Zipcode', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' class=\'postcode\' id=\'postcode-' . $formId . '-' . $field[ 'id' ] . '\' name=\'postcode_' . $formId . '_' . $field[ 'id' ] . '\' value=\'' . ( isset( $_POST[ 'postcode_' . $formId . '_' . $field[ 'id' ] ] ) ? $_POST[ 'postcode_' . $formId . '_' . $field[ 'id' ] ] : ( isset( $postcode ) ? $postcode : '' ) ) . '\' /><br />';
					$content .= '<label for=\'city-' . $formId . '-' . $field[ 'id' ] . '\'>' . __( 'City', 'tno-bim-quickscan' ) . '</label> <input type=\'text\' class=\'city\' id=\'city-' . $formId . '-' . $field[ 'id' ] . '\' name=\'city_' . $formId . '_' . $field[ 'id' ] . '\' value=\'' . ( isset( $_POST[ 'city_' . $formId . '_' . $field[ 'id' ] ] ) ? $_POST[ 'city_' . $formId . '_' . $field[ 'id' ] ] : ( isset( $city ) ? $city : '' ) ) . '\' /><br />';
					$content .= '</div>';
					$content .= '</div>';
					if( isset( $field[ 'validation_message' ] ) ) {
						$content .= '<div class="gfield_description validation_message">' . $field[ 'validation_message' ] . '</div>';
					}
				} elseif( $isCompany ) {
					$street = get_user_meta( $currentUserObject->ID, 'street', true );
					$postcode = get_user_meta( $currentUserObject->ID, 'postcode', true );
					$city = get_user_meta( $currentUserObject->ID, 'city', true );
					$tabindex = GFCommon::get_tabindex();
					$content .= '<label class=\'gfield_label sub-label\' for=\'input_' . $formId . '_' . $field[ 'id' ] . '.1\'>' . $field[ 'label' ] . ( $field[ 'isRequired' ] ? '<span class=\'gfield_required\'>*</span>' : '' ) . '</label>';
					$content .= '<input type=\'hidden\' class=\''. $field[ 'type' ] . ' ' . ( isset( $field[ 'cssClass' ] ) ? esc_attr( $field[ 'cssClass' ] ) : '' ) . ' ' . $field[ 'size' ] . '\' ' . ' name=\'input_' . $field[ 'id' ] . '\' value=\'' . ( !isset( $currentUserObject ) ? 'new' : $currentUserObject->ID ) . '\' />';
					$content .= '<table class=\'user-information\'>';
					$content .= '<tr><td>' . __( 'Name', 'tno-bim-quickscan' ) . '</td><td>' . $currentUserObject->display_name . '</td></tr>';
					$content .= '<tr><td>' . __( 'Email', 'tno-bim-quickscan' ) . '</td><td>' . $currentUserObject->user_email . '</td></tr>';
					$content .= '<tr><td>' . __( 'Street', 'tno-bim-quickscan' ) . '</td><td>' . $street . '</td></tr>';
					$content .= '<tr><td>' . __( 'Zipcode', 'tno-bim-quickscan' ) . '</td><td>' . $postcode . '</td></tr>';
					$content .= '<tr><td>' . __( 'City', 'tno-bim-quickscan' ) . '</td><td>' . $city . '</td></tr>';
					$content .= '</table>';
				} elseif( is_user_logged_in() ) {
					$content .= '<div class="quickscan-error">';
					$content .= '<strong>' . __( 'To fill in the Quickscan you need to have an advisor or company account.', 'tno-bim-quickscan' ) . '</strong><br />';
					$content .= __( 'You can change your account: <a href="/company-account"><strong>here</strong></a>', 'tno-bim-quickscan' ) . '<br />';
					$content .= '</div>';
				} else {
					$content .= __( 'You must be logged in to access the Quickscan.', 'tno-bim-quickscan' ) . '<br />';
				}
			}
		}

		return $content;
	}

	public function getTitle( $title, $type ) {
		return $title;
	}

	public function gformEditorJs() {
		?>
		<script type='text/javascript'>
			jQuery( document ).ready( function( $ ) {
				fieldSettings[ "tno_bim_bedrijf" ] = ".rules_setting, .label_setting, .description_setting, .admin_label_setting, .size_setting, .error_message_setting, .css_class_setting, .visibility_setting, .ll_choice_select_setting .rules_setting, .prepopulate_field_setting, .conditional_logic_field_setting";
				fieldSettings[ "multiselect" ] += ", .bim_points, .bim_categories, .bim_topic, .bim_value, .bim_post_meta, .bim_max_value, .bim_max_select";
				fieldSettings[ "select" ] += ", .bim_points, .bim_categories, .bim_topic, .bim_value, .bim_post_meta";
				fieldSettings[ "checkbox" ] += ", .bim_points, .bim_categories, .bim_topic, .bim_value, .bim_post_meta, .bim_max_value, .bim_max_select";
				fieldSettings[ "radio" ] += ", .bim_points, .bim_categories, .bim_topic, .bim_value, .bim_post_meta";
			} );
			jQuery( document ).bind( "gform_load_field_settings", function( event, field, form ) {
				if( field[ "type" ] == "multiselect" || field[ "type" ] == "select" || field[ "type" ] == "checkbox" || field[ "type" ] == "radio" ) {
					if( field[ "bim_value" ] ) {
						jQuery( "#bim-value-setting" ).val( field[ "bim_value" ] );
					} else {
						jQuery( "#bim-value-setting" ).val( "" );
					}
					if( field[ "bim_topic_setting" ] ) {
						jQuery( "#bim-topic-setting" ).val( field[ "bim_topic_setting" ] );
					} else {
						jQuery( "#bim-topic-setting" ).val( "" );
					}
					if( field[ "bim_max_value" ] ) {
						jQuery( "#bim-max-value-setting" ).val( field[ "bim_max_value" ] );
					} else {
						jQuery( "#bim-max-value-setting" ).val( "" );
					}
					if( field[ "bim_max_select" ] ) {
						jQuery( "#bim-max-select-setting" ).val( field[ "bim_max_select" ] );
					} else {
						jQuery( "#bim-max-select-setting" ).val( "" );
					}
					TNOBIMQuickscanAdmin.initializeGravityFormFields( event, field, form );
				}
			} );
		</script>
		<script type="text/javascript" src="<?php print( plugins_url( '/tno-bim-quickscan-admin.js', __FILE__ ) ); ?>"></script>
		<?php
	}

	public function gformFieldStandardSettings( $position, $formId ) {
		// bim_points, .bim_categories, .bim_topic, .bim_value
		if( $position == 1550 ) {
			?>
			<li class="bim_points field_setting" style="display:list-item;">
				<label for="bim-points-setting" class="inline">
					<?php _e( 'Set BIM Quickscan weight for each answer', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_points' ); ?><br />
				<div id="bim-points-per-answer"></div>
			</li>
			<li class="bim_categories field_setting" style="display:list-item;">
				<label for="bim-categories-setting" class="inline">
					<?php _e( 'Select BIM Quickscan aspects for this question', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_categories' ); ?><br />
				<?php
				$categories = get_terms( $this->options[ 'taxonomy_category' ], Array( 'hide_empty' => false ) );
				foreach( $categories as $category ) {
					?>
					<input onclick="TNOBIMQuickscanAdmin.saveBimCategories();" type="checkbox" class="bim-category-setting-checkbox" id="bim-category-<?php print( $category->term_id ); ?>" value="<?php print( $category->term_id ); ?>" />
					<input type="text" size="3" onkeyup="TNOBIMQuickscanAdmin.saveBimCategories();" id="bim-category-weight-<?php print( $category->term_id ); ?>" class="bim-category-setting-weight" value="" />
					<label for="bim-category-<?php print( $category->term_id ); ?>" class="inline"><?php print( $category->name ); ?></label><br />
					<?php
				}
				?>
			</li>
			<li class="bim_topic field_setting" style="display:list-item;">
				<label for="bim-topic-setting" class="inline">
					<?php _e( 'Select the BIM Quickscan topic', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_topic' ); ?><br />
				<select id="bim-topic-setting" onchange="SetFieldProperty( 'bim_topic_setting', this.value )">
					<?php
					$topics = get_terms( $this->options[ 'taxonomy_topic' ], Array( 'hide_empty' => false ) );
					foreach( $topics as $topic ) {
						?>
						<option value="<?php print( $topic->term_id ); ?>"><?php print( $topic->name ); ?></option>
						<?php
					}
					?>
				</select>
			</li>
			<li class="bim_value field_setting" style="display:list-item;">
				<label for="bim-value-setting" class="inline">
					<?php _e( 'BIM Quickscan weight for this question', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_value' ); ?><br />
				<input type="text" size="3" id="bim-value-setting" value="" onkeyup="SetFieldProperty( 'bim_value', this.value );" />
			</li>
			<li class="bim_max_value field_setting" style="display:list-item;">
				<label for="bim-max-value-setting" class="inline">
					<?php _e( 'BIM Quickscan maximum weight for this question', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_max_value' ); ?><br />
				<input type="text" size="3" id="bim-max-value-setting" value="" onkeyup="SetFieldProperty( 'bim_max_value', this.value );" />
			</li>
			<li class="bim_max_select field_setting" style="display:list-item;">
				<label for="bim-max-select-setting" class="inline">
					<?php _e( 'BIM Quickscan maximum number of answers', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_max_select' ); ?><br />
				<input type="text" size="3" id="bim-max-select-setting" value="" onkeyup="SetFieldProperty( 'bim_max_select', this.value );" />
			</li>
			<li class="bim_post_meta field_setting" style="display:list-item;">
				<label for="bim-post-meta" class="inline">
					<?php _e( 'Store value in post meta for BIM Quickscan report', 'tno-bim-quickscan' ); ?>
				</label>
				<?php gform_tooltip( 'form_field_bim_post_meta' ); ?><br />
				<input type="text" size="" id="bim-post-meta" value="" onkeyup="SetFieldProperty( 'bim_post_meta', this.value );" />
			</li>
			<?php
		}
	}

	public function gformTooltips( $tooltips ) {
		$tooltips[ 'form_field_bim_points' ] = __( 'Set the weight of each answer to this question for the BIM Quickscan, this must be a numerical value', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_categories' ] = __( 'Select the aspects for this question', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_topic' ] = __( 'Select the BIM Quickscan topic for this question', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_value' ] = __( 'Set the weight of this question for the BIM Quickscan, this must be a numerical value', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_post_meta' ] = __( 'Store the value of this field in the post meta of the report', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_max_value' ] = __( 'The maximum value that can be awarded for this question', 'tno-bim-quickscan' );
		$tooltips[ 'form_field_bim_max_select' ] = __( 'The maximum number of answers which score points for this question', 'tno-bim-quickscan' );
		return $tooltips;
	}


	public function getFieldValue( $value, $lead, $field ) {
		if( $field[ 'type' ] == 'tno_bim_bedrijf' ) {
			if( $value != '' && $value != 'new' ) {
				// get the value from the user id
				$user = get_user_by( 'id', $value );
				$value = $user->display_name;
			}
		}
		return $value;
	}

	public function wpFooter() {
		?>
		<script type="text/javascript">
			TNOBIMQuickscan.settings = {
				baseUri: "<?php bloginfo( 'wpurl' ); ?>",
				addNewAddressText: "<?php _e( 'add new company', 'tno-bim-quickscan' ); ?>",
				noneAddNewCompanyText: "<?php _e( 'none, enter company details or select a company', 'tno-bim-quickscan' ); ?>"
			};
		</script>
		<?php
	}

	public function gFormSaveFieldValue( $value, $lead, $field, $form ) {
		global $wpdb;
		if( $field[ 'type' ] == 'tno_bim_bedrijf' ) {
			$isAdvisor = false;
			if( is_user_logged_in() ) {
				$currentUserObject = wp_get_current_user();
				foreach( $currentUserObject->roles as $role ) {
					if( $role == $this->options[ 'adviser_role' ] ) {
						$isAdvisor = true;
					}
				}
			}

			if( $isAdvisor || current_user_can( 'activate_plugins' ) ) {
				$street = $_POST[ 'street_' . $form[ 'id' ] . '_' . $field[ 'id' ] ];
				$postcode = $_POST[ 'postcode_' . $form[ 'id' ] . '_' . $field[ 'id' ] ];
				$city = $_POST[ 'city_' . $form[ 'id' ] . '_' . $field[ 'id' ] ];

				$userId = username_exists( $_POST[ 'email_' . $form[ 'id' ] . '_' . $field[ 'id' ] ] );
				if( ctype_digit( $value ) ) {
					// Update a company
					$userId = $value;
				}

				if( !$userId && !email_exists( $_POST[ 'email_' . $form[ 'id' ] . '_' . $field[ 'id' ] ] ) ) {
					$randomPassword = wp_generate_password( 8, false );
					$userId = wp_create_user( $_POST[ 'email_' . $form[ 'id' ] . '_' . $field[ 'id' ] ], $randomPassword, $_POST[ 'email_' . $form[ 'id' ] . '_' . $field[ 'id' ] ] );

					if( $userId !== false ) {
						// TODO: maybe send email to the users email (username, password and what it is for)
						$user = get_user_by( 'id', $userId );
						$user->set_role( $this->options[ 'company_role' ] );
						$userData = Array( 'ID' => $userId, 'display_name' => $_POST[ 'name_' . $form[ 'id' ] . '_' . $field[ 'id' ] ], 'first_name' => $_POST[ 'name_' . $form[ 'id' ] . '_' . $field[ 'id' ] ] );
						wp_update_user( $userData );
						add_user_meta( $userId, 'street', $street );
						add_user_meta( $userId, 'postcode', $postcode );
						add_user_meta( $userId, 'city', $city );
					}
				} else {
					update_user_meta( $userId, 'street', $street );
					update_user_meta( $userId, 'postcode', $postcode );
					update_user_meta( $userId, 'city', $city );
				}
				return $userId;
			} else {
				return $value;
			}
		} else {
			return $value;
		}
	}

	public function adminFooter() {
		?>
		<script type="text/javascript">
			var TNOBIMQuickscanAdminSettings = {
				baseUri:	"<?php bloginfo( 'wpurl' ); ?>"
			};
		</script>
		<?php
	}

	public function gFormAfterSubmission( $entry, $form, $forceAdvisor = -1 ) {
		global $wpdb, $current_user, $sitepress;
		
		if( isset( $sitepress) ) {
			$sitepress->switch_lang( 'en', true );
			remove_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
		}
		$categories = Array();
		$topics = Array();
		$answers = Array();
		$score = 0;
		$maxScore = 0;
		$userId = -1;
		$categoryInformation = get_terms( $this->options[ 'taxonomy_category' ], Array( 'hide_empty' => false ) );
		$topicInformation = get_terms( $this->options[ 'taxonomy_topic' ], Array( 'hide_empty' => false, 'exclude' => Array( $this->options[ 'exclude_topic' ] ) ) );
		if( isset( $sitepress) ) {
			$sitepress->switch_lang( ICL_LANGUAGE_CODE, true );
			remove_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
		}
		$content = '<h2>' . __( 'Filled in questions', 'tno-bim-quickscan' ) . '</h2>';

		foreach( $form[ 'fields' ] as $field ) {
			if( $field[ 'type' ] == 'tno_bim_bedrijf' ) {
				// We show the company name at the top of the report
				if( $entry[$field[ 'id' ]] != '' ) {
					$userId = $entry[$field[ 'id' ]];
					$content .= '<h3>' . $field[ 'label' ] . '</h3>';
					$userData = get_user_by( 'id', $userId );
					$content .= '<p>' . $userData->get( 'display_name' ) . '</p>';
				}
			} elseif( $field[ 'type' ] == 'multiselect' || $field[ 'type' ] == 'select' || $field[ 'type' ] == 'checkbox' || $field[ 'type' ] == 'radio' ) {
				if( isset( $field[ 'bim_value' ] ) && isset( $field[ 'bim_points' ] ) && isset( $field[ 'bim_topic_setting' ] ) ) {
					$content .= '<h3>' . $field[ 'label' ] . '</h3>';
					$keys = Array();
					if( isset( $entry[$field[ 'id' ]] ) ) {
						// Single value answer
						foreach( $field[ 'choices' ] as $key => $choice ) {
							if( $choice[ 'value' ] == $entry[$field[ 'id' ]] ) {
								$keys[] = $key;
								$content .= '<ul><li>' . $choice[ 'text' ] . '</li></ul>';
							}
						}
					} elseif( isset( $field[ 'inputs' ] ) && is_array( $field[ 'inputs' ] ) ) {
						$content .= '<ul>';
						foreach( $field[ 'inputs' ] as $key => $input ) {
							if( isset( $entry['' . $input[ 'id' ]] ) && $entry['' . $input[ 'id' ]] != '' ) {
								$keys[] = $key;
								$content .= '<li>' . $input[ 'label' ] . '</li>';
							}
						}
						$content .= '</ul>';
					} else {
						print( "Error: Problem found adding field values to report!<br / >" );
						var_dump( $entry, $field );
					}

					$points = explode( ',', $field[ 'bim_points' ] );
					$questionPoints = 0;
					$maxPoints = 0;
					if( isset( $field[ 'bim_max_select' ] ) && $field[ 'bim_max_select' ] != '' && $field[ 'bim_max_select' ] > 0 ) {
						$maxValues = Array();
						foreach( $keys as $key ) {
							if( isset( $points[ $key ] ) ) {
								if( count( $maxValues ) < intval( $field[ 'bim_max_select' ] ) ) {
									$maxValues[] = $points[ $key ];
								} else {
									for( $i = 0; $i < count( $maxValues ); $i ++ ) {
										if( $points[ $key ] > $maxValues[$i] ) {
											for( $p = count( $maxValues ) - 1; $p < $i; $p -- ) {
												$maxValues[$p] = $maxValues[$p - 1];
											}
											$maxValues[$i] = $points[ $key ];
										}
									}
								}
							}
						}
						foreach( $maxValues as $value ) {
							$questionPoints += $value;
						}
					} else {
						foreach( $keys as $key ) {
							if( isset( $points[ $key ] ) ) {
								$questionPoints += $points[ $key ];
							}
						}
					}

					if( isset( $field[ 'bim_max_value' ] ) && $field[ 'bim_max_value' ] != '' && $field[ 'bim_max_value' ] > 0 ) {
						$maxPoints = $field[ 'bim_max_value' ];
					} else {
						// For singular we get max points based on highest value
						foreach( $points as $point ) {
							if( $maxPoints < $point ) {
								$maxPoints = $point;
							}
						}
					}

					if( $questionPoints > $maxPoints ) {
						$questionPoints = $maxPoints;
					}

					$categoryMultipliers = Array();
					if( isset( $field[ 'bim_category_weights' ] ) ) {
						$categoryWeights = explode( ',', $field[ 'bim_category_weights' ] );
						foreach( $categoryWeights as $categoryWeight ) {
							$values = explode( ':', $categoryWeight );
							if( count( $values ) >= 2 ) {
								$categoryMultipliers[$values[1]] = $values[0];
							}
						}
					}

					if( isset( $field[ 'bim_categories' ] ) ) {
						$questionCategories = explode( ',', $field[ 'bim_categories' ] );
					} else {
						$questionCategories = Array();
					}

					foreach( $questionCategories as $category ) {
						/*if( isset( $categoryMultipliers[$category] ) && $categoryMultipliers[$category] != 1 ) {
							print( "Category multiplier!!! {$categoryMultipliers[$category]} times bonus<br />" );
							print( "questionPoints: $questionPoints, maxPoints: $maxPoints<br />" );
							print( "Question: {$field[ 'label' ]}<br />" );
						}*/
						if( isset( $categories[$category] ) ) {
							$categories[$category][0] += $questionPoints * ( isset( $categoryMultipliers[$category] ) ? $categoryMultipliers[$category] : 1 );
							$categories[$category][1] += 1 * ( isset( $categoryMultipliers[$category] ) ? $categoryMultipliers[$category] : 1 );
						} else {
							$categories[$category] = Array( $questionPoints * ( isset( $categoryMultipliers[$category] ) ? $categoryMultipliers[$category] : 1 ), 1 * ( isset( $categoryMultipliers[$category] ) ? $categoryMultipliers[$category] : 1 ) );
						}
					}

					$questionPoints *= $field[ 'bim_value' ];
					$maxPoints *= $field[ 'bim_value' ];

					if( isset( $topics[$field[ 'bim_topic_setting' ]] ) ) {
						$topics[$field[ 'bim_topic_setting' ]][0] += $questionPoints;
						$topics[$field[ 'bim_topic_setting' ]][1] += $maxPoints;
					} else {
						$topics[$field[ 'bim_topic_setting' ]] = Array( $questionPoints, $maxPoints );
					}

					$answers[] = Array( $questionPoints, $maxPoints );
					$score += $questionPoints;
					$maxScore += $maxPoints;

					/*$content .= "<p>Resultaat: $questionPoints / $maxPoints\n";
					foreach( $topicInformation as $topicInfo ) {
						if( $topicInfo->term_id == $field[ 'bim_topic_setting' ] ) {
							$content .= "Topic: {$topicInfo->name}\n";
							break;
						}
					}
					foreach( $categoryInformation as $categoryInfo ) {
						if( in_array( $categoryInfo->term_id, $questionCategories ) ) {
							$content .= "Aspect: {$categoryInfo->name}\n";
						}
					}
					$content .= "</p>";*/
				} else {
					// For questions not related to the score
					$content .= '<h3>' . $field[ 'label' ] . '</h3>';
					if( isset( $entry[$field[ 'id' ]] ) ) {
						// Single value answer
						foreach( $field[ 'choices' ] as $key => $choice ) {
							if( $choice[ 'value' ] == $entry[$field[ 'id' ]] ) {
								$keys[] = $key;
								$content .= '<ul><li>' . $choice[ 'text' ] . '</li></ul>';
							}
						}
					} elseif( isset( $field[ 'inputs' ] ) && is_array( $field[ 'inputs' ] ) ) {
						$content .= '<ul>';
						foreach( $field[ 'inputs' ] as $key => $input ) {
							if( isset( $entry['' . $input[ 'id' ]] ) && $entry['' . $input[ 'id' ]] != '' ) {
								$keys[] = $key;
								$content .= '<li>' . $input[ 'label' ] . '</li>';
							}
						}
						$content .= '</ul>';
					}
				}
			}
		}

		if( $maxScore > 0 ) {
			$resultTableAspects = '<h2>' . __( 'Result per aspect', 'tno-bim-quickscan' ) . '</h2><table>';
			foreach( $categoryInformation as $categoryInfo ) {
				if( isset( $categories[$categoryInfo->term_id] ) && $categories[$categoryInfo->term_id][0] != 0 ) {
					$percentage = round( $categories[$categoryInfo->term_id][0] / $categories[$categoryInfo->term_id][1] * 100, 2 ) . '%';
					//$scoreText = round( $categories[$categoryInfo->term_id][0], 2 ) . ' / ' . round( $categories[$categoryInfo->term_id][1], 2 );
				} else {
					$percentage = '0%';
					if( isset( $categories[$categoryInfo->term_id][1] ) ) {
						//$scoreText = '0 / ' . round( $categories[$categoryInfo->term_id][1], 2 );
					} else {
						//$scoreText = '0 / 0';
					}
				}
				//<td class="number">' . $scoreText . '</td>
				$resultTableAspects .= '<tr><td>' . $categoryInfo->name . '</td><td class="number">' . $percentage . '</td></tr>';
			}
			$resultTableAspects .= '</table>';
			$resultTableTopics = '<h2>' . __( 'Result per topic', 'tno-bim-quickscan' ) . '</h2><table>';
			foreach( $topicInformation as $topicInfo ) {
				if( isset( $topics[$topicInfo->term_id] ) && $topics[$topicInfo->term_id][0] != 0 ) {
					$percentage = round( $topics[$topicInfo->term_id][0] / $topics[$topicInfo->term_id][1] * 100, 2 ) . '%';
					//$resultTableTopics .= '<tr><td>' . $topicInfo->name . ' no normalization</td><td class="number">' . round( $topics[$topicInfo->term_id][0], 2 ) . ' / ' . round( $topics[$topicInfo->term_id][1], 2 ) . '</td><td class="number">-</td></tr>';
					if( isset( $this->options[ 'topic_cap_' . $topicInfo->term_id ] ) ) {
						// If a maximum value is set for this topic, we scale the value and set actual maximum value for this topic to that maximum
						$topics[$topicInfo->term_id][0] = $topics[$topicInfo->term_id][0] / $topics[$topicInfo->term_id][1] * $this->options[ 'topic_cap_' . $topicInfo->term_id ];
						$topics[$topicInfo->term_id][1] = $this->options[ 'topic_cap_' . $topicInfo->term_id ];
					}
					$scoreText = round( $topics[$topicInfo->term_id][0], 2 ) . ' / ' . round( $topics[$topicInfo->term_id][1], 2 );
				} else {
					$percentage = '0%';
					$scoreText = '0 / 0';
				}
				$resultTableTopics .= '<tr><td>' . $topicInfo->name . '</td><td class="number">' . $scoreText . '</td><td class="number">' . $percentage . '</td></tr>';
			}
			$resultTableTopics .= '</table>';

			$resultTable = '<h2>' . __( 'Result', 'tno-bim-quickscan' ) . '</h2><table>';
			$percentage = round( $score / $maxScore * 100, 2 ) . '% / 100%';
			$resultTable .= '<tr><td>' . __( 'Result', 'tno-bim-quickscan' ) . '</td><td class="number">' . $score . ' / ' . $maxScore . '</td><td class="number">' . $percentage . '</td></tr>';
			$resultTable .= '</table>';

			/*if( $userId !== false ) {
				$userData = get_user_by( 'id', $userId );
			}*/

			$postData = Array(
				 'post_title' => 'Quickscan ' . ( isset( $userData ) ? ( $userData->get( 'display_name' ) . ' ' ) : '' ) . date( 'd-m-Y', strtotime( $entry[ 'date_created' ] ) ),
				 'post_content' => $content,
				 'post_type' => $this->options[ 'report_post_type' ],
				 'post_status' => 'private',
				 'post_date' => $entry[ 'date_created' ]
			);

			if( $userId !== false ) {
				$postData[ 'post_author' ] = $userId;
			}

			$postId = wp_insert_post( $postData );

			add_post_meta( $postId, '_language', defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '' );
			add_post_meta( $postId, '_results_table', $resultTable );
			add_post_meta( $postId, '_results_table_topics', $resultTableTopics );
			add_post_meta( $postId, '_results_table_aspects', $resultTableAspects );
			add_post_meta( $postId, '_categories', $categories );
			add_post_meta( $postId, '_topics', $topics );
			add_post_meta( $postId, '_answers', $answers );
			add_post_meta( $postId, '_results', Array( 'score' => $score, 'max_score' => $maxScore ) );
			add_post_meta( $postId, '_entry_id', $entry[ 'id' ] );

			foreach( $form[ 'fields' ] as $field ) {
				if( $field[ 'type' ] == 'multiselect' || $field[ 'type' ] == 'select' || $field[ 'type' ] == 'checkbox' || $field[ 'type' ] == 'radio' ) {
					if( isset( $field[ 'bim_post_meta' ] ) && $field[ 'bim_post_meta' ] != '' ) {
						if( isset( $entry[$field[ 'id' ]] ) && $entry[$field[ 'id' ]] != '' ) {
							if( isset( $field[ 'choices' ] ) ) {
								$value = '';
								foreach( $field[ 'choices' ] as $choice ) {
									if( $choice[ 'value' ] == $entry[$field[ 'id' ]] ) {
										$value = $choice[ 'value' ];
										break;
									}
								}
								if( $value == '' ) {
									$value = __( 'other', 'tno-bim-quickscan' );
								}
								add_post_meta( $postId, $field[ 'bim_post_meta' ], $value );
							} else {
								// Single value answer
								add_post_meta( $postId, $field[ 'bim_post_meta' ], $entry[$field[ 'id' ]] );
							}
						} elseif( isset( $field[ 'inputs' ] ) && is_array( $field[ 'inputs' ] ) ) {
							foreach( $field[ 'inputs' ] as $key => $input ) {
								if( isset( $entry['' . $input[ 'id' ]] ) && $entry['' . $input[ 'id' ]] != '' ) {
									add_post_meta( $postId, $field[ 'bim_post_meta' ], $entry['' . $input[ 'id' ]] );
								}
							}
						}
					}
				}
			}

			if( $forceAdvisor == -1 ) {
				$currentUserObject = wp_get_current_user();
				if( current_user_can( 'activate_plugins' ) ) {
					add_post_meta( $postId, '_advisor', $currentUserObject->ID );
				} else {
					foreach( $currentUserObject->roles as $role ) {
						if( $role == $this->options[ 'adviser_role' ] ) {
							add_post_meta( $postId, '_advisor', $currentUserObject->ID );
							break;
						}
					}
				}
			} elseif( $forceAdvisor > 0 ) {
				add_post_meta( $postId, '_advisor', $forceAdvisor );
			}
		}
	}

	public function gFormValidation( $validationResult ) {
		foreach( $validationResult[ 'form' ][ 'fields' ] as $key => $field ) {
			if( $field[ 'type' ] == 'tno_bim_bedrijf' && $field[ 'isRequired' ] && !RGFormsModel::is_field_hidden( $validationResult[ 'form' ], $field, Array() ) ) {
				// Need to validate this field
				$isAdvisor = false;
				$isCompany = false;
				$currentUserObject = wp_get_current_user();
				foreach( $currentUserObject->roles as $role ) {
					if( $role == $this->options[ 'adviser_role' ] ) {
						$isAdvisor = true;
					} elseif( $role == $this->options[ 'company_role' ] ) {
						$isCompany = true;
					}
				}

				$type = '';
				$valid = false;
				if( ( $isAdvisor || current_user_can( 'activate_plugins' ) ) && ( $_POST[ 'input_' . $field[ 'id' ] ] == 'new' || ctype_digit( $_POST[ 'input_' . $field[ 'id' ] ] ) ) &&
					 $_POST[ 'email_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] != '' &&
					 $_POST[ 'name_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] != '' &&
					 $_POST[ 'street_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] != '' &&
					 $_POST[ 'postcode_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] != '' &&
					 $_POST[ 'city_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] != '' ) {
					$valid = true;
					if( $_POST[ 'input_' . $field[ 'id' ] ] == 'new' && username_exists( $_POST[ 'email_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] ) ) {
						$valid = false;
						$type = 'username';
					}
					if( $_POST[ 'input_' . $field[ 'id' ] ] == 'new' && email_exists( $_POST[ 'email_' . $validationResult[ 'form' ][ 'id' ] . '_' . $field[ 'id' ] ] ) ) {
						$valid = false;
						$type = 'email';
					}
				}
				if( $isCompany && ctype_digit( $_POST[ 'input_' . $field[ 'id' ] ] ) && $_POST[ 'input_' . $field[ 'id' ] ] == $currentUserObject-> ID ) {
					$valid = true;
				}

				if( !$valid ) {
					$validationResult[ 'form' ][ 'fields' ][$key][ 'failed_validation' ] = true;
					if( $type == 'username' || $type == 'email' ) {
						$validationResult[ 'form' ][ 'fields' ][$key][ 'validation_message' ] = __( 'This email address is already used for a company, choose another email address or select the company.', 'tno-bim-quickscan' );
					} else {
						$validationResult[ 'form' ][ 'fields' ][$key][ 'validation_message' ] = __( 'Choose a company or enter the details of a new one', 'tno-bim-quickscan' );
					}
					$validationResult[ 'is_valid' ] = false;
				}
			}
		}

		return $validationResult;
	}

	public function gFormPreRender( $form ) {
		global $gform_update_post;
		foreach( $form[ 'fields' ] as &$field ) {
			$fieldType = RGFormsModel::get_input_type( $field );

			if( $fieldType == 'date' && isset( $field[ 'setDateToPost' ] ) && $field[ 'setDateToPost' ] == 'true' ) {
				if( isset( $gform_update_post ) && isset( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ] ) && is_numeric( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ] ) ) {
					$date = get_post_meta( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ], 'date', true );
					if( isset( $date ) && $date != '' ) {
						$field[ 'defaultValue' ] = $date;
					}
				}
			}
			if( $fieldType == 'select' && isset( $field[ 'restorePromotion' ] ) && $field[ 'restorePromotion' ] == 'true' ) {
				if( isset( $gform_update_post ) && isset( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ] ) && is_numeric( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ] ) ) {
					$promotieType = get_post_meta( $_REQUEST[ $gform_update_post->options[ 'request_id' ] ], 'promotie_type', true );

					foreach( $field[ 'choices' ] as $key => $value ) {
						if( $field[ 'choices' ][$key][ 'value' ] == $promotieType ) {
							$field[ 'choices' ][$key][ 'isSelected' ] = true;
						} else {
							$field[ 'choices' ][$key][ 'isSelected' ] = false;
						}
					}
				}
			}
		}
		return $form;
	}

	public function dashboardWidgets() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] );
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
		wp_add_dashboard_widget( 'quickscan_rapports', 'Quickscans', Array( &$this, 'quickscanDashboardWidget' ) );
	}

	public function quickscanDashboardWidget() {
		if( current_user_can( 'edit_posts' ) ) {
			$rapporten = get_posts( Array(
				 'post_type' => $this->options[ 'report_post_type' ],
				 'year' => date( 'Y' ),
				 'monthnum' => date( 'm' ),
				 'numberposts' => -1,
				 'post_status' => 'any'
			) );
			$stats = Array();
			foreach( $rapporten as $rapport ) {
				$coreBusiness = get_post_meta( $rapport->ID, 'core_business', true );
				if( !$coreBusiness || $coreBusiness == '' ) {
					$coreBusiness = 'onbekend';
				}
				if( isset( $stats[ 'core_business' ] ) ) {
					if( isset( $stats[ 'core_business' ][$coreBusiness] ) ) {
						$stats[ 'core_business' ][$coreBusiness][] = $rapport->ID;
					} else {
						$stats[ 'core_business' ][$coreBusiness] = Array( $rapport->ID );
					}
				} else {
					$stats[ 'core_business' ] = Array();
					$stats[ 'core_business' ][$coreBusiness] = Array( $rapport->ID );
				}
			}
			?>
			<div class="table table_content">
				<p class="sub"><?php _e( 'This month', 'tno-bim-quickscan' ); ?></p>
				<table>
					<tbody>
					<tr class="first">
						<td class="first b b-posts"><a href="edit.php?post_type=<?php print( $this->options[ 'report_post_type' ] ); ?>"><?php print( number_format( count( $rapporten ) ) ); ?></a></td>
						<td class="t posts"><a href="edit.php?post_type=<?php print( $this->options[ 'report_post_type' ] ); ?>"><?php _e( 'Quickscans done', 'tno-bim-quickscan' ); ?></a></td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="clear"></div>
			<div class="table table_discussion">
				<p class="sub"><?php _e( 'By core-business', 'tno-bim-quickscan' ); ?></p>
				<table>
					<tbody>
					<?php
					$first = true;
					if( count( $stats ) > 0 && is_array( $stats[ 'core_business' ] ) ) {
						foreach( $stats[ 'core_business' ] as $key => $coreBusiness ) {
							if( count( $coreBusiness ) > 0 ) {
								$value = round( count( $coreBusiness ) / count( $rapporten ) * 100 ) . '%';
							} else {
								$value = '0%';
							}
							$maxScore = 0;
							$score = 0;
							foreach( $coreBusiness as $rapportId ) {
								$resultaten = get_post_meta( $rapportId, '_answers', true );
								if( is_array( $resultaten ) ) {
									foreach( $resultaten as $resultaat ) {
										$score += $resultaat[0];
										$maxScore += $resultaat[1];
									}
								}
							}
							$maxScore /= count( $coreBusiness );
							$score /= count( $coreBusiness );
							if( $maxScore > 0 ) {
								$resultText = round( $score / $maxScore * 100 ) . '% / 100%';
							} else {
								$resultText =  '0% / 100%';
							}
							?>
							<tr class="<?php print( $first ? 'first' : '' ); ?>">
								<td class="t comments"><span class="total"><?php print( $key ); ?></span></td>
								<td class="b b-comments"><span class="total"><?php print( number_format( count( $coreBusiness ) ) ); ?></span></td>
								<td class="b b-comments"><span class="total">(<?php print( $value ); ?>)</span></td>
								<td class="last t comments"><span class="total"><?php _e( 'result', 'tno-bim-quickscan' ); ?>: <?php print( $resultText ); ?></span></td>
							</tr>

							<?php
						}
					}
					?>
					</tbody>
				</table>
			</div>
			<div class="versions">
				<br class="clear">
			</div>
			<?php
		}
	}

	public function isAdvisor( $userId ) {
		// Use simple request caching to prevent this from being checked multiple times per HTTP request
		if( isset( $this->isAdvisorCache[$userId] ) ) {
			return $this->isAdvisorCache[$userId];
		} else {
			$isAdvisor = false;
			$userObject = get_user_by( 'id', $userId );
			foreach( $userObject->roles as $role ) {
				if( $role == $this->options[ 'adviser_role' ] || $role == 'administrator' ) {
					$isAdvisor = true;
					break;
				}
			}
			$this->isAdvisorCache[$userId] = $isAdvisor;
			return $isAdvisor;
		}
	}

	public function printPageLinks( $currentPageId, $currentPage = 1, $totalReports ) {
		if( !isset( $currentPage ) || $currentPage == '' ) {
			$currentPage = 1;
		}

		// If no reports don't do anything
		if( empty($totalReports) || $totalReports > 0 ){
			return;
		}
		?>
		<div class="report-pagination">
			<?php
			for( $i = 1; $i <= $maxPage; $i ++ ) {
				?>
				<a class="<?php print( $i == $currentPage ? 'current-page' : '' ); ?>" href="<?php print( get_permalink( $currentPageId ) . $i ); ?>"><?php print( $i ); ?></a>
				<?php
			}
			?>
		</div>
		<?php
	}

	public function importSelfscan( $data, $titles ) {
		global $wpdb;
		/*
		 * 0 = id
		 * 1 = completed or not, seems to always be "Y"
		 * 2 = last changed date
		 * 3 = start date
		 * 4 = referer URI
		 * 5 = company information
		 * 6, 7, 8, etc = answers quickscan
		 */
		$titlesClean = array_slice( $titles, 6 );
		$dataClean = array_slice( $data, 6 );

		$wpdb->query( $wpdb->prepare("INSERT INTO {$wpdb->prefix}rg_lead
				( form_id, date_created, ip, source_url, user_agent, currency, created_by, status )
				VALUES ( %d, %s, %s, '', '', 'USD', '', 'active' )",
			 $this->options[ 'quickscan_form' ], mysql_real_escape_string( $data[3] ), mysql_real_escape_string( $data[5] ) ) );
		$leadId = $wpdb->insert_id;

		if( $data[5] == '' ) {
			// If empty we set this import email address to anoniem@bimquickscan.nl
			$email = sanitize_user( 'anoniem@bimquickscan.nl' );
		} else {
			$email = sanitize_user( $data[5] );
		}
		// Check if this user already exists else we need to add it
		$authorId = username_exists( $email );
		if( !isset( $authorId ) ) {
			$randomPassword = wp_generate_password( 8, false );
			$authorId = wp_create_user( $email, $randomPassword, $email );
			$displayName = substr( $email, 0, strpos( $email, '@') );
			$user = get_user_by( 'id', $authorId );
			if( isset( $user ) && $user !== false ) {
				$user->set_role( $this->options[ 'company_role' ] );
				$userData = Array( 'ID' => $authorId, 'display_name' => $displayName, 'first_name' => $displayName );
				wp_update_user( $userData );
			}
		}

		$form = $this->importFormEntryData( $leadId, $dataClean, $titlesClean, $authorId );
		$entry = RGFormsModel::get_lead( $leadId );
		// We need to generate the report from this entry
		$this->gFormAfterSubmission( $entry, $form, -2 );
	}

	public function importQuickscan( $data, $titles ) {
		global $wpdb;
		/*
		 * 0 = id
		 * 1 = adviseur id (staat in user meta external_advisor_id)
		 * 2 = completed or not, seems to always be "Y"
		 * 3 = last changed date
		 * 4 = start date
		 * 5 = IP adres
		 * 6 = bedrijfsinformatie
		 * 7, 8, 9, etc = answers quickscan
		 */

		// Try to extract as much information as possible from a string of seemingly random company information
		$companyInfoParts = explode( ' ', $data[6] );
		foreach( $companyInfoParts as $part ) {
			if( strstr( $part, '@' ) !== false ) {
				$email = $part;
				break;
			}
		}
		$contactpersoonPre = Array( 'dhr.', 'dhr', 'mvr', 'mvr.', 'ir.' );

		foreach( $companyInfoParts as $key => $part ) {
			$testPart = preg_replace( '/[^0-9]/', '', $part );
			if( ctype_digit( $testPart ) || in_array( strtolower( $part ), $contactpersoonPre ) ) {
				$back = ctype_digit( $testPart ) ? 1 : 0;
				$companyName = '';
				for( $i = 0; $i < $key - $back; $i ++ ) {
					if( $companyName != '' ) {
						$companyName .= ' ';
					}
					$companyName .= $companyInfoParts[$i];
				}
				break;
			}
		}

		foreach( $companyInfoParts as $key => $part ) {
			$testPart = preg_replace( '/[^0-9]/', '', $part );
			if( ctype_digit( $testPart ) && $key > 0 ) {
				$companyStreet = '';
				for( $i = $key - 1; $i < $key + 1; $i ++ ) {
					if( $companyStreet != '' ) {
						$companyStreet .= ' ';
					}
					$companyStreet .= $companyInfoParts[$i];
				}
				break;
			}
		}

		foreach( $companyInfoParts as $key => $part ) {
			if( ( ctype_digit( substr( $part, 0, 4 ) ) && ctype_alpha( substr( $part, strlen( $part ) - 2 ) ) ) ) {
				$companyZipcode = $part;
			} elseif( count( $companyInfoParts ) > $key + 1 && strlen( $part ) == 4 && ctype_digit( $part ) && strlen( $companyInfoParts[$key + 1] ) == 2 && ctype_alpha( $companyInfoParts[$key + 1] ) ) {
				$companyZipcode = $part . ' ' . $companyInfoParts[$key + 1];
				break;
			}
		}

		foreach( $companyInfoParts as $key => $part ) {
			if( in_array( strtolower( $part ), $contactpersoonPre ) ) {
				$companyPerson = '';
				$more = count( $companyInfoParts ) < $key + 3 ? count( $companyInfoParts ) - $key : 3;
				for( $i = $key; $i < $key + $more; $i ++ ) {
					if( $companyPerson != '' ) {
						$companyPerson .= ' ';
					}
					$companyPerson .= $companyInfoParts[$i];
				}
				break;
			}
		}

		if( !isset( $companyName ) ) {
			if( isset( $email ) || isset( $companyPerson ) ) {
				$companyName = $companyInfoParts[0];
			} else {
				$companyName = $data[6];
			}
		}
		if( !isset( $companyPerson ) ) {
			$companyPerson = '';
		}
		if( !isset( $companyStreet ) ) {
			$companyStreet = '';
		}
		if( !isset( $companyZipcode ) ) {
			$companyZipcode = '';
		}

		// If we did not get any email address from it we can't use it, need an email address to connect to or create and connect to an account
		if( !isset( $email ) && ( !isset( $companyName ) || strlen( $companyName ) > 63 ) ) {
			$email = __( 'company', 'tno-bim-quickscan' ) . '@bimquickscan.nl';
			$companyName = __( 'unknown', 'tno-bim-quickscan' );
			$companyPerson = '';
			$companyStreet = '';
			$companyZipcode = '';
		} elseif( !isset( $email ) && isset( $companyName ) ) {
			$emailDomain = preg_replace('/[^a-zA-Z0-9\s-]/s', '', strtolower( trim( $companyName ) ) );
			$emailDomain = str_replace( '  ', ' ', $emailDomain );
			$emailDomain = str_replace( ' ', '-', $emailDomain );
			$email = 'info@' . $emailDomain . '.nl';
		}

		// Make sure it is sane
		$email = sanitize_user( $email );

		/*print( "original text:&quot;{$data[6]}&quot;<br />" );
		print( 'email: &quot;' . $email . '&quot;<br />' );
		print( 'name: &quot;' . $companyName . '&quot;<br />' );
		print( 'contactpersoon: &quot;' . $companyPerson . '&quot;<br />' );
		print( 'street: &quot;' . $companyStreet . '&quot;<br />' );
		print( 'zipcode: &quot;' . $companyZipcode . '&quot;<br />' );*/

		// Check if this user already exists else we need to add it
		$authorId = username_exists( $email );
		if( !isset( $authorId ) ) {
			$randomPassword = wp_generate_password( 8, false );
			$authorId = wp_create_user( $email, $randomPassword, $email );
			$user = get_user_by( 'id', $authorId );
			if( isset( $user ) && $user !== false ) {
				$user->set_role( $this->options[ 'company_role' ] );
				$userData = Array( 'ID' => $authorId, 'display_name' => $companyName, 'first_name' => $companyName );
				wp_update_user( $userData );
				add_user_meta( $authorId, 'contact_persoon', $companyPerson );
				add_user_meta( $authorId, 'old_information', $data[6] );
				add_user_meta( $authorId, 'street', $companyStreet );
				add_user_meta( $authorId, 'postcode', $companyZipcode );
			} else {
				$authorId = -1;
			}
		}

		// fetch the advisor or create a new one
		$advisorId = $wpdb->get_var( "SELECT user_id
				FROM $wpdb->usermeta
				WHERE meta_key = 'external_advisor_id' AND meta_value = '" . mysql_real_escape_string( $data[1]) . "'" );
		if( !isset( $advisorId ) ) {
			// It seems no advisor is known for this import id
			$advisorEmail = sanitize_user( 'onbekende-adviseur@bimquickscan.nl' );
			$advisorId = username_exists( $advisorEmail );
			if( !isset( $advisorEmail ) ) {
				$randomPassword = wp_generate_password( 8, false );
				$advisorId = wp_create_user( $advisorEmail, $randomPassword, $advisorEmail );
				$user = get_user_by( 'id', $advisorId );
				if( isset( $user ) && $user !== false ) {
					$user->set_role( $this->options[ 'company_role' ] );
					$userData = Array( 'ID' => $advisorId, 'display_name' => 'Onbekende adviseur', 'first_name' => 'Onbekende adviseur' );
					wp_update_user( $userData );
				}
			}
		}

		$titlesClean = array_slice( $titles, 7 );
		$dataClean = array_slice( $data, 7 );

		$wpdb->query( $wpdb->prepare("INSERT INTO {$wpdb->prefix}rg_lead
				( form_id, date_created, ip, source_url, user_agent, currency, created_by, status )
				VALUES ( %d, %s, '', %s, '', 'USD', '', 'active' )",
			 $this->options[ 'quickscan_form' ], mysql_real_escape_string( $data[3] ), mysql_real_escape_string( $data[4] ) ) );
		$leadId = $wpdb->insert_id;

		$form = $this->importFormEntryData( $leadId, $dataClean, $titlesClean, $authorId );
		$entry = RGFormsModel::get_lead( $leadId );
		// We need to generate the report from this entry
		$this->gFormAfterSubmission( $entry, $form, $advisorId );
	}

	public function importFormEntryData( $leadId, $data, $titles, $authorId ) {
		global $wpdb;
		$form = RGFormsModel::get_form_meta( $this->options[ 'quickscan_form' ] );
		$index = 0;

		foreach( $form[ 'fields' ] as $field ) {
			// This question was different but no longer, so we convert it to the current one
			if( $titles[$index] == 'Werkt het bedrijf samen met (project)partners die dezelfde software systemen gebruiken?' ) {
				$titles[$index] = 'Gaat u nieuw binnengekomen informatie handmatig invoeren (overnemen) in uw eigen systeem?';
				if( $data[$index] == 'Nee, altijd met een diversiteit aan softwaresystemen (afgestemd op specialisme van de partners).' ) {
					$data[$index] = 'Nee, nooit';
				} elseif( $data[$index] == 'Soms, maar soms ook wisselende samenstelling.' ) {
					$data[$index] = 'Dat komt wel eens voor';
				}
				elseif( $data[$index] == 'Ja, bijna altijd.' ) {
					$data[$index] = 'Ja, bijna altijd';
				}
			}
			if( $field[ 'type' ] == 'tno_bim_bedrijf' ) {
				// Special field, we should set the author id here, this is not part of the data array
				$wpdb->query( $wpdb->prepare(
					 "INSERT INTO {$wpdb->prefix}rg_lead_detail
						( lead_id, form_id, field_number, value )
						VALUES( %d, %d, %f, %s )",
					 $leadId, $this->options[ 'quickscan_form' ], $field[ 'id' ], mysql_real_escape_string( $authorId ) ) );
				//print( "{$field[ 'label' ]}: $authorId<br />" );
			} elseif( $field[ 'type' ] == 'radio' ) {
				$answerOther = '';
				$answerIndex = -1;
				if( $data[$index] != '' ) {
					// We try an exact match first (remove 3 starting characters "a. " from answer)
					foreach( $field[ 'choices' ] as $key => $choice ) {
						if( strtolower( trim( substr( $choice[ 'text' ], 3 ), "\x2E" ) ) == strtolower( trim( $data[$index], "\x2E" ) ) ) {
							$answerIndex = $key;
							break;
						}
					}
					if( $answerIndex == -1 ) {
						// If an exact match did not happen we try looking for a partial match, is the old answer part of one of the new answers
						foreach( $field[ 'choices' ] as $key => $choice ) {
							if( strstr( strtolower( $choice[ 'text' ] ), strtolower( trim( $data[$index], "\x2E" ) ) ) !== false ) {
								$answerIndex = $key;
								break;
							}
						}
					}
				}
				//$answerIndex = TNOBIMQuickscan::alphaCharToNumber( $data[$index] );
				$oldIndex = $index;
				if( strstr( $titles[$index + 1], '[Anders]' ) !== false ) {
					// There is an other option too
					$answerOther = $data[$index + 1];
					$index ++;
				}
				$index ++;

				if( $answerIndex > -1 ) {
					$answerData = $field[ 'choices' ][$answerIndex][ 'value' ];
				} else {
					$answerData = $answerOther;
				}

				$wpdb->query( $wpdb->prepare(
					 "INSERT INTO {$wpdb->prefix}rg_lead_detail
						( lead_id, form_id, field_number, value )
						VALUES( %d, %d, %f, %s )",
					 $leadId, $this->options[ 'quickscan_form' ], $field[ 'id' ], mysql_real_escape_string( $answerData ) ) );
				//print( "{$field[ 'label' ]} ({$titles[$oldIndex]}): $answerData (=" . trim( $data[$oldIndex], "\x2E" ) . ")<br />" );
			} elseif( $field[ 'type' ] == 'checkbox' ) {
				// Add checkboxes too
				$skip = 0;
				$answers = Array();
				if( strstr( $titles[$index], '[' ) !== false ) {
					$baseString = trim( substr( $titles[$index], 0, strpos( $titles[$index], '[' ) ) );
				} else {
					$baseString = $titles[$index];
				}
				//print( "{$field[ 'label' ]} ({$titles[$index]}):" );
				foreach( $field[ 'inputs' ] as $key => $input ) {
					//if( strstr( $titles[$index + $skip], $baseString ) !== false ) {
					if( strtolower( $data[$index + $key] ) == 'ja' ) {
						$answers[] = $key;
						//print( $input[ 'label' ] . ', ' );
					}
					$skip = $key + 1;
					//}
				}

				//print( "<br />" );

				foreach( $answers as $answer ) {
					$wpdb->query( $wpdb->prepare(
						 "INSERT INTO {$wpdb->prefix}rg_lead_detail
						( lead_id, form_id, field_number, value )
						VALUES( %d, %d, %f, %s )",
						 $leadId, $this->options[ 'quickscan_form' ], $field[ 'inputs' ][$answer][ 'id' ], mysql_real_escape_string( $field[ 'choices' ][$answer][ 'value' ] ) ) );
				}
				$index += $skip;
			} elseif( $field[ 'type' ] == 'text' ) {
				// Are there any other fields? What do we do with them
				$wpdb->query( $wpdb->prepare(
					 "INSERT INTO {$wpdb->prefix}rg_lead_detail
						( lead_id, form_id, field_number, value )
						VALUES( %d, %d, %f, %s )",
					 $leadId, $this->options[ 'quickscan_form' ], $field[ 'id' ], mysql_real_escape_string( $data[$index] ) ) );
				//print( "{$field[ 'label' ]}: {$data[$index]}<br />" );
				$index ++;
			}
			if( $titles[$index] == 'Welke?' ) {
				// Dirty fix to skip a non-existing answer
				$index ++;
			}
		}
		//var_dump( $form );
		return $form;
	}

	public static function alphaCharToNumber( $character ) {
		$characterNumber = ord( strtolower( $character ) );
		if( $characterNumber > 96 && $characterNumber < 123 ) {
			return $characterNumber - 97;
		} else {
			return -1;
		}
	}

	public function checkLoggedIn() {
		if( is_user_logged_in() ) {
			return true;
		} else {
			?>
			<p><?php _e( 'You have to be logged in to view this page.', 'tno-bim-quickscan' ); ?></p>
			<p><?php _e( 'To log in now, click', 'tno-bim-quickscan' ); ?> <a href="<?php bloginfo( 'wpurl' ); ?>/wp-login.php"><?php _e( 'here', 'tno-bim-quickscan' ); ?></a></p>
			<p><?php _e( 'No user account yet?', 'tno-bim-quickscan' ); ?> <a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $this->options[ 'register_page' ], 'page', true ) : $this->options[ 'register_page' ] ) ); ?>"><?php _e( 'Register here', 'tno-bim-quickscan' ); ?></a></p>
			<?php
			return false;
		}
	}

	public static function showCharts() {
		global $wpdb, $tnoBIMQuickscan, $sitepress;
		if( $tnoBIMQuickscan->checkLoggedIn() ) {
			$currentUserId = get_current_user_id();
			$min = 0;
			$max = 0;

			$range = $wpdb->get_row( "SELECT MAX( post_date ), MIN( post_date )
					FROM $wpdb->posts
					WHERE post_type = '{$tnoBIMQuickscan->options[ 'report_post_type' ]}'", ARRAY_A );
			$startDate = explode( ' ', $range[ 'MIN( post_date )' ] );
			$startDate = explode( '-', $startDate[0] );
			$endDate = explode( ' ', $range[ 'MAX( post_date )' ] );
			$endDate = explode( '-', $endDate[0] );
			$max = ( $endDate[0] - $startDate[0] ) * 12;
			$max += $endDate[1] - $startDate[1];
			$max ++;
			if( isset( $sitepress ) ) {
				$availableLanguages = $sitepress->get_active_languages();
			} else {
				$availableLanguages = Array();
			}
			?>
			<div class="graph-container">
				<canvas id="interactive-radar-plot" data-type="Radar" width="625" height="600" style="width: 625px; height: 600px;"></canvas>
				<canvas id="interactive-bar-chart" width="625" height="600"></canvas>
			</div>
			<div class="graph-control">
				<div class="slider-container">
					<h4><?php _e( 'Show data between', 'tno-bim-quickscan' ); ?> <span id="slider-result"></span></h4>
					<div id="slider"></div><br />
					<h4><?php _e( 'Quickscan types', 'tno-bim-quickscan' ); ?></h4>
					<input type="radio" checked name="scan_types" class="scan-types" id="scan-type-all-radio" value="all" /> <label for="scan-type-all-radio"><?php _e( 'All', 'tno-bim-quickscan' ); ?></label><br />
					<input type="radio" name="scan_types" class="scan-types" id="scan-type-quickscan-radio" value="quickscan" /> <label for="scan-type-quickscan-radio"><?php _e( 'Quickscans with adviser', 'tno-bim-quickscan' ); ?></label><br />
					<input type="radio" name="scan_types" class="scan-types" id="scan-type-selfscan-radio" value="selfscan" /> <label for="scan-type-selfscan-radio"><?php _e( 'Quickscans without adviser', 'tno-bim-quickscan' ); ?></label><br />
					<h4><?php _e( 'Quickscan languages', 'tno-bim-quickscan' ); ?></h4>
					<input type="radio" checked name="languages" class="languages" id="languages-all-radio" value="_all_" /> <label for="languages-all-radio"><?php _e( 'All', 'tno-bim-quickscan' ); ?></label><br />
					<?php
					foreach( $availableLanguages as $language => $object ) {
					?>
					<input type="radio" name="languages" class="languages" id="languages-<?php print( $language ); ?>-radio" value="<?php print( $language ); ?>" /> <label for="languages-<?php print( $language ); ?>-radio"><?php print( $language ); ?><label><br />
							<?php
							}
							?>
				</div>
				<div class="core-business-container">
					<h4><?php _e( 'Show Quickscans', 'tno-bim-quickscan' ); ?></h4>
					<span class="legend" style="background-color: <?php print( $tnoBIMQuickscan->colors[ 'strokeColor' ][0] ); ?>">&nbsp;</span> <?php _e( 'Maximum', 'tno-bim-quickscan' ); ?><br />
					<span class="legend" style="background-color: <?php print( $tnoBIMQuickscan->colors[ 'strokeColor' ][1] ); ?>">&nbsp;</span> <input type="checkbox" id="all-scans" class="core-business" checked /> <label for="all-scans"><?php _e( 'All Quickscans', 'tno-bim-quickscan' ); ?></label><br />
					<span class="legend" style="background-color: <?php print( $tnoBIMQuickscan->colors[ 'strokeColor' ][2] ); ?>">&nbsp;</span> <input type="checkbox" id="own-scans" class="core-business" checked /> <label for="own-scans"><?php _e( 'My Quickscans', 'tno-bim-quickscan' ); ?></label><br />
					<h4><?php _e( 'Quickscans with specific core-businesses', 'tno-bim-quickscan' ); ?></h4>
					<?php
					$coreBusinesses = $wpdb->get_results( "SELECT meta_value
					FROM $wpdb->postmeta
					WHERE meta_key = 'core_business'
					GROUP BY meta_value
					ORDER BY meta_value" );
					$colorIndex = 3;
					foreach( $coreBusinesses as $coreBusiness ) {
						if( $coreBusiness->meta_value != 'anders' && $coreBusiness->meta_value != 'other' ) {
							?>
							<span class="legend" style="background-color: <?php
							print( $tnoBIMQuickscan->colors[ 'strokeColor' ][$colorIndex] );
							?>">&nbsp;</span> <input type="checkbox" id="scans-<?php print( $coreBusiness->meta_value ); ?>" class="core-business" /> <label for="scans-<?php
							print( $coreBusiness->meta_value );
							?>"><?php print( ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE == 'en' ) ? TNOBIMQuickscan::$coreBusinessesEnglish[$coreBusiness->meta_value] : $coreBusiness->meta_value ); ?></label><br />
							<?php
							$colorIndex ++;
						}
					}
					foreach( $coreBusinesses as $coreBusiness ) {
						if( $coreBusiness->meta_value == 'anders' ) {
							?>
							<span class="legend" style="background-color: <?php
							print( $tnoBIMQuickscan->colors[ 'strokeColor' ][$colorIndex] );
							?>">&nbsp;</span> <input type="checkbox" id="scans-<?php print( $coreBusiness->meta_value ); ?>" class="core-business" /> <label for="scans-<?php
							print( $coreBusiness->meta_value );
							?>"><?php print( ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE == 'en' ) ? TNOBIMQuickscan::$coreBusinessesEnglish[$coreBusiness->meta_value] : $coreBusiness->meta_value ); ?></label><br />
							<?php
							$colorIndex ++;
						}
					}
					?>
				</div>
				<div class="clear"></div>
			</div>
			<script type="text/javascript">
				var customizableChartSettings = {
					max: <?php print( $max ); ?>,
					min: <?php print( $min ); ?>,
					startYear: <?php print( $startDate[0] ); ?>,
					startMonth: <?php print( $startDate[1] ); ?>,
					endDate: <?php print( $endDate[1] . '-' . $endDate[0] ); ?>,
					language: "<?php print( ICL_LANGUAGE_CODE ); ?>",
					ajaxUri: "<?php print( plugins_url( 'get_chart_data.php', __FILE__ ) ); ?>"
				};
			</script>
			<?php
		}
	}

	public static function showPublicCompanyList() {
		global $tnoBIMQuickscan, $wpdb;
		if( isset( $tnoBIMQuickscan->options[ 'company_role' ] ) && isset( $tnoBIMQuickscan->options[ 'report_post_type' ] ) ) {
			$companies = get_users( Array( 'role' => $tnoBIMQuickscan->options[ 'company_role' ] ) );
		} else {
			$companies = Array();
		}
		$companiesWithPublicReport = Array();
		foreach( $companies as $key => $company ) {
			$amount = $wpdb->get_var( "SELECT COUNT(ID)
					FROM $wpdb->posts
					WHERE post_type = '{$tnoBIMQuickscan->options[ 'report_post_type' ]}' AND post_author = {$company->ID}
					AND post_status = 'publish'" );
			if( $amount > 0 ) {
				$company->amount_public = $amount;
				$companiesWithPublicReport[] = $company;
			}
		}

		if( count( $companiesWithPublicReport ) == 0 ) {
			?>
			<p><?php _e( 'There are no companies with public reports', 'tno-bim-quickscan' ); ?></p>
			<?php
		} else {
			?>
			<table class="list">
				<tr class="odd">
					<th><?php _e( 'Company', 'tno-bim-quickscan' ); ?></th>
					<th><?php _e( 'Published reports', 'tno-bim-quickscan' ); ?></th>
				</tr>
				<?php
				$count = 0;
				foreach( $companiesWithPublicReport as $company ) {
					?>
					<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
						<td><a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $tnoBIMQuickscan->options[ 'company_page' ], 'page', true ) : $tnoBIMQuickscan->options[ 'company_page' ] ) ); ?>?id=<?php print( $company->ID ); ?>"><?php print( $company->display_name ); ?></a></td>
						<td><?php print( $company->amount_public ); ?></td>
					</tr>
					<?php
					$count ++;
				}
				?>
			</table>
			<?php
		}
	}

	public static function showPublicReportList() {
		global $tnoBIMQuickscan, $page, $pageId;

		$query = new WP_Query( Array(
			 'post_type' => isset( $tnoBIMQuickscan->options[ 'report_post_type' ] ) ? $tnoBIMQuickscan->options[ 'report_post_type' ] : '',
			 'post_status' => Array( 'publish' ),
			 'posts_per_page' => isset( $tnoBIMQuickscan->options[ 'reports_per_page' ] ) ? $tnoBIMQuickscan->options[ 'reports_per_page' ] : 10,
			 'paged' => $page
		) );
		$publicReports = $query->posts;
		$totalReports = $query->found_posts;

		if( count( $publicReports ) == 0 ) {
			?>
			<p><?php _e( 'There are no public reports available', 'tno-bim-quickscan' ); ?></p>
			<?php
		} else {
			?>
			<table class="list">
				<tr class="odd">
					<th><?php _e( 'Date', 'tno-bim-quickscan' ); ?></th>
					<th><?php _e( 'Company', 'tno-bim-quickscan' ); ?></th>
					<th><?php _e( 'Core-business', 'tno-bim-quickscan' ); ?></th>
					<th class="number"><?php _e( 'Result', 'tno-bim-quickscan' ); ?> %</th>
					<th><?php _e( 'Language', 'tno-bim-quickscan' ); ?></th>
				</tr>
				<?php
				$count = 0;
				foreach( $publicReports as $report ) {
					$results = get_post_meta( $report->ID,  '_results', true );
					$coreBusiness = get_post_meta( $report->ID, 'core_business', true );
					if( !$results ) {
						$results = Array( 'score' => 1, 'max_score' => 1 );
					}
					$language = get_post_meta( $report->ID, '_language', true );
					if( !isset( $language ) || $language == '' ) {
						$language = 'nl';
					}
					?>
					<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
						<td><a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $tnoBIMQuickscan->options[ 'report_page' ], 'page', true ) : $tnoBIMQuickscan->options[ 'report_page' ] ) ); ?>?id=<?php print( $report->ID ); ?>"><?php print( date( 'd-m-Y', strtotime( $report->post_date ) ) ); ?></a></td>
						<?php
						$authorData = get_user_by( 'id', $report->post_author );
						?>
						<td><a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $tnoBIMQuickscan->options[ 'company_page' ], 'page', true ) : $tnoBIMQuickscan->options[ 'company_page' ] ) ); ?>?id=<?php print( $report->post_author ); ?>"><?php print( $authorData->display_name ); ?></a></td>
						<td><?php print( $coreBusiness ); ?></td>
						<td class="number"><?php print( round( $results[ 'score' ] / $results[ 'max_score' ] * 100, 2 ) . '%' ); ?></td>
						<td><?php print( $language ); ?></td>
					</tr>
					<?php
					$count ++;
				}
				?>
			</table>
			<?php
			$tnoBIMQuickscan->printPageLinks( $pageId, $page, $totalReports );
		}
	}

	public static function showMyReportList() {
		global $tnoBIMQuickscan;
		if( $tnoBIMQuickscan->checkLoggedIn() ) {
			$currentUserId = get_current_user_id();
			if( $tnoBIMQuickscan->isAdvisor( $currentUserId ) ) {
				$query = new WP_Query( Array(
					 'post_type' => $tnoBIMQuickscan->options[ 'report_post_type' ],
					 'post_status' => Array( 'publish', 'private' ),
					 'posts_per_page' => $tnoBIMQuickscan->options[ 'reports_per_page' ]
				) );
			} else {
				$query = new WP_Query( Array(
					 'post_type' => $tnoBIMQuickscan->options[ 'report_post_type' ],
					 'post_status' => Array( 'publish', 'private' ),
					 'posts_per_page' => $tnoBIMQuickscan->options[ 'reports_per_page' ],
					 'post_author' => $currentUserId
				) );
			}

			$myReports = $query->posts;
			if( count( $myReports ) == 0 ) {
				?>
				<p><?php _e( 'You do not have any reports, do the <a href="/quickscan-2">BIM Quickscan</a> now', 'tno-bim-quickscan' ); ?></p>
				<?php
			} else {
				?>
				<table class="list">
					<tr>
						<th><?php _e( 'Date', 'tno-bim-quickscan' ); ?></th>
						<?php
						if( $tnoBIMQuickscan->isAdvisor( $currentUserId ) ) {
							?>
							<th><?php _e( 'Company', 'tno-bim-quickscan' ); ?></th>
							<?php
						}
						?>
						<th><?php _e( 'Status', 'tno-bim-quickscan' ); ?></th>
						<th class="number"><?php _e( 'Result', 'tno-bim-quickscan' ); ?> %</th>
					</tr>
					<?php
					$count = 0;
					foreach( $myReports as $report ) {
						$results = get_post_meta( $report->ID,  '_results', true );
						if( !$results ) {
							$results = Array( 'score' => 1, 'max_score' => 1 );
						}
						$statusText = __( 'private', 'tno-bim-quickscan' );
						if( $report->post_status == 'publish' ) {
							$statusText = __( 'published', 'tno-bim-quickscan' );
						} elseif( get_post_meta( $report->ID, '_advisor_status', true ) == 'validated' ) {
							$statusText = __( 'validated', 'tno-bim-quickscan' );
						}
						?>
						<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
							<td><a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $tnoBIMQuickscan->options[ 'report_page' ], 'page', true ) : $tnoBIMQuickscan->options[ 'report_page' ] ) ); ?>?id=<?php print( $report->ID ); ?>"><?php print( date( 'd-m-Y', strtotime( $report->post_date ) ) ); ?></a></td>
							<?php
							if( $tnoBIMQuickscan->isAdvisor( $currentUserId ) ) {
								$authorData = get_user_by( 'id', $report->post_author );
								?>
								<td><?php print( $authorData->display_name ); ?></td>
								<?php
							}
							?>
							<td><?php print( $statusText ); ?></td>
							<td class="number"><?php print( round( $results[ 'score' ] / $results[ 'max_score' ] * 100, 2 ) . '%' ); ?></td>
						</tr>
						<?php
						$count ++;
					}
					?>
				</table>
				<?php
			}
		}
	}

	public static function showSingleReport() {
		global $tnoBIMQuickscan, $sitepress;
		$advisorId = - 1;
		$validKey = false;

		if( isset( $_GET['id'] ) ) {
			$id = intval( $_GET['id'] );
			$report = get_post( $id );
			if( isset( $report ) ) {
				$advisorId = get_post_meta( $report->ID, '_advisor', true );
				if( $advisorId == '' ) {
					$advisorId = - 1;
				}
				if( isset( $_GET['key'] ) && $_GET['key'] != '' && $_GET['key'] != false ) {
					if( $_GET['key'] == get_post_meta( $report->ID, '_advisor_key', true ) ) {
						$validKey = true;
					}
				}
			}
		}

		if( isset( $report ) && $report->post_type == $tnoBIMQuickscan->options['report_post_type'] &&
			 ( 'publish' == $report->post_status ||
				  ( $report->post_author == get_current_user_id() || $advisorId == get_current_user_id() || current_user_can( 'activate_plugins' ) ) ) ) {

			$currentUserId = get_current_user_id();
			// TODO: check if owner and setting advisor status
			/*if( $currentUserId == $report->post_author ) {
				$reportStatus
			}*/

			$language = get_post_meta( $report->ID, '_language', true );
			if( !isset( $language ) || $language == '' ) {
				$language = 'nl';
			}
			if( isset( $sitepress ) ) {
				$defaultLanguage = $sitepress->get_default_language();
				$sitepress->switch_lang( 'en', true );
				remove_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
			}
			?>
			<span class="language-info"><?php _e( 'Language', 'tno-bim-quickscan' ); ?>
				: <?php print( $language ); ?></span><br/>
			<br/>
			<?php
			$labelLength = 25;

			$topics = get_post_meta( $report->ID, '_topics', true );
			$topicInformation = get_terms( $tnoBIMQuickscan->options['taxonomy_topic'], Array( 'hide_empty' => false, 'exclude' => Array( $tnoBIMQuickscan->options['exclude_topic'] ) ) );
			$categories = get_post_meta( $report->ID, '_categories', true );
			$categoryInformation = get_terms( $tnoBIMQuickscan->options['taxonomy_category'], Array( 'hide_empty' => false ) );

			$radarLabels = '';
			$radarData = Array();
			foreach( $categoryInformation as $category ) {
				if( $radarLabels != '' ) {
					$radarLabels .= ', ';
				}
				if( $defaultLanguage == '' || 'en' == $language ) {
					if( !isset( $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id ] ) ) {
						$radarLabels .= '"' . substr( $category->name, 0, $labelLength ) . '"';
					} else {
						$radarLabels .= '"' . $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id ] . '"';
					}
				} else {
					$languageId = icl_object_id( $category->term_id, $tnoBIMQuickscan->options['taxonomy_category'], true, $language );
					if( !isset( $tnoBIMQuickscan->options[ 'aspect_short_name_' . $languageId . '_' . $language ] ) ) {
						$categoryLanguage = get_term( $languageId, $tnoBIMQuickscan->options['taxonomy_category'] );
						$radarLabels .= '"' . substr( $categoryLanguage->name, 0, $labelLength ) . '"';
					} else {
						$radarLabels .= '"' . $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id . '_' . $language ] . '"';
					}
				}

				if( isset( $categories[ $category->term_id ] ) ) {
					$radarData[] = Array( 'data' => ( 1 - $categories[ $category->term_id ][0] / $categories[ $category->term_id ][1] ) * 100, 'max' => 100 );
				} else {
					$radarData[] = Array( 'data' => 0, 'max' => 100 );
				}
			}
			$radarDataString = '{ fillColor : "' . $tnoBIMQuickscan->colors['fillColor'][1] . '", ' .
				 'strokeColor : "' . $tnoBIMQuickscan->colors['strokeColor'][1] . '",' .
				 'pointColor : "' . $tnoBIMQuickscan->colors['pointColor'][1] . '",' .
				 'pointStrokeColor : "' . $tnoBIMQuickscan->colors['pointStrokeColor'][1] . '",' .
				 'data: [';
			$first = true;
			foreach( $radarData as $data ) {
				if( !$first ) {
					$radarDataString .= ',';
				}
				$radarDataString .= $data['data'];
				$first = false;
			}
			$radarDataString .= '] }, { fillColor : "' . $tnoBIMQuickscan->colors['fillColor'][0] . '",' .
				 'strokeColor : "' . $tnoBIMQuickscan->colors['strokeColor'][0] . '",' .
				 'pointColor : "' . $tnoBIMQuickscan->colors['pointColor'][0] . '",' .
				 'pointStrokeColor : "' . $tnoBIMQuickscan->colors['pointStrokeColor'][0] . '",' .
				 'data: [';
			$first = true;
			foreach( $radarData as $data ) {
				if( !$first ) {
					$radarDataString .= ',';
				}
				$radarDataString .= $data['max'];
				$first = false;
			}
			$radarDataString .= '] }';

		$barLabels = '';
		$barData = Array();
			$pieData = Array();
			$index = 1;
		foreach( $topicInformation as $topic ) {
			if( $barLabels != '' ) {
				$barLabels .= ', ';
			}
			if( $defaultLanguage == '' || 'en' == $language ) {
				$labelParts = explode( ': ', $topic->name );
				$barLabels .= '"' . ( count( $labelParts ) == 2 ? substr( $labelParts[0], 0, $labelLength ) : substr( $topic->name, 0, $labelLength ) ) . '"';
			} else {
				$languageId = icl_object_id( $topic->term_id, $tnoBIMQuickscan->options['taxonomy_topic'], true, $language );
				$topicLanguage = get_term( $languageId, $tnoBIMQuickscan->options['taxonomy_topic'] );
				$labelParts = explode( ': ', $topicLanguage->name );
				$barLabels .= '"' . ( count( $labelParts ) == 2 ? substr( $labelParts[0], 0, $labelLength ) : substr( $topicLanguage->name, 0, $labelLength ) ) . '"';
			}
			$pieValue = 0;
			if( isset( $topics[ $topic->term_id ] ) ) {
				$barData[] = Array( 'data' => $topics[ $topic->term_id ][0], 'max' => $topics[ $topic->term_id ][1] );
				$pieValue = $topics[ $topic->term_id ][1] - $topics[ $topic->term_id ][0];
			} else {
				$barData[] = Array( 'data' => 0, 'max' => 0 );
			}
			$pieData[] = Array(
				 'value' => $pieValue,
				 'label' => ( count( $labelParts ) == 2 ? substr( $labelParts[0], 0, $labelLength ) : substr( $topic->name, 0, $labelLength ) ) . '"',
				 'color' => $tnoBIMQuickscan->colors['fillColor'][ $index ],
				 'highlight' => $tnoBIMQuickscan->colors['fillColor'][ $index ]
			);
			$index ++;
		}

		$barDataString = '{ fillColor: "' . $tnoBIMQuickscan->colors['fillColor'][1] . '", strokeColor: "' . $tnoBIMQuickscan->colors['strokeColor'][1] . '", data: [';
		$first = true;
		foreach( $barData as $data ) {
			if( !$first ) {
				$barDataString .= ',';
			}
			$barDataString .= $data['data'];
			$first = false;
		}
		$barDataString .= '] }, { fillColor : "' . $tnoBIMQuickscan->colors['fillColor'][0] . '", strokeColor : "' . $tnoBIMQuickscan->colors['strokeColor'][0] . '", data: [';
		$first = true;
		foreach( $barData as $data ) {
			if( !$first ) {
				$barDataString .= ',';
			}
			$barDataString .= $data['max'];
			$first = false;
		}
		$barDataString .= '] }';
		?>
		<canvas id="bar-chart" width="625" height="600"></canvas>
		<canvas id="radar-plot" data-type="Radar" width="625" height="600" style="width: 625px; height: 600px;"></canvas>
		<canvas id="pie-chart" width="625" height="600"></canvas>
		<script type="text/javascript">
			var bimRadarData = {
				labels: [<?php print( $radarLabels ); ?>],
				datasets: [<?php print( $radarDataString ); ?>]
			};
			var bimBarData = {
				labels: [<?php print( $barLabels ); ?>],
				datasets: [<?php print( $barDataString ); ?>]
			};
			var bimPieData = <?php print( json_encode( $pieData ) ); ?>;
		</script>
		<?php
			print( get_post_meta( $report->ID, '_results_table_topics', true ) );
			print( get_post_meta( $report->ID, '_results_table_aspects', true ) );

			if( is_user_logged_in() && ( $currentUserId == $advisorId || $report->post_author == $currentUserId ) ) {
				print( apply_filters( 'the_content', $report->post_content ) );
				if( $report->post_author == $currentUserId && $report->post_status == 'private' ) {
					// Display the advisor selection here
					TNOBIMQuickscan::singleReportAdvisorOptions( $report );
				}
			}

			if( isset( $sitepress ) ) {
				$sitepress->switch_lang( $language, true );
				add_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
			}

			if( $validKey ) {
				TNOBIMQuickscan::singleReportAdvisorSettings( $report );
			}
		}
	}

	public static function singleReportAdvisorOptions( $report ) {
		global $tnoBIMQuickscan;
		$mailSent = false;
		if( isset( $_POST['advisor_id'] ) && ctype_digit( $_POST['advisor_id'] ) ) {
			// set this advisor id, generate key and send email
			$advisor = get_post( intval( $_POST['advisor_id'] ) );
			if( isset( $advisor ) && $advisor->post_type == $tnoBIMQuickscan->options['advisor_post_type'] ) {
				$advisorEmail = get_post_meta( $advisor->ID, 'email', true );
				if( $advisorEmail != '' ) {
					// Only set this is email is set, else we cannot mail the advisor
					update_post_meta( $report->ID, '_advisor_status', 'pending' );
					$key = uniqid();
					update_post_meta( $report->ID, '_advisor_key', $key );
					$user = get_user_by( 'id', $report->post_author );
					$message = __( 'Dear madame/sir,', 'tno-bim-quickscan' ) . "\n\n" .
						 __( 'You have been asked to validate a BIM Compass report, you can find the report in the link below:', 'tno-bim-quickscan' ) . "\n" .
						 get_permalink( $tnoBIMQuickscan->options['report_page'] ) . "?id={$report->ID}&key={$key}\n\n" .
						 __( 'Fill in any recommendations below the report. Once you are done you can publish the report.', 'tno-bim-quickscan' ) . "\n" .
						 __( 'Report from', 'tno-bim-quickscan' ) . ": {$user->user_email}\n\n" .
						 __( 'The BIM Compass team', 'tno-bim-quickscan' );
					wp_mail( $advisorEmail, __( 'BIM Compass: Request for validation', 'tno-bim-quickscan' ), $message );
					wp_mail( get_option( 'admin_email' ), __( 'BIM Compass: Request for advisor validation', 'tno-bim-quickscan' ), $message );
					print( '<p>' . __( 'Validation request sent', 'tno-bim-quickscan' ) . '</p>');
					$mailSent = true;
				}
			}
		}
		if( !$mailSent ) {
			$status = get_post_meta( $report->ID, '_advisor_status', true );
			if( $status == '' || $status == 'start' ) {
				print( '<h2>' . __( 'Select an advisor to validate your scan', 'tno-bim-quickscan' ) . '</h2>' );
				$advisors = get_posts( Array(
					 'post_type' => $tnoBIMQuickscan->options['advisor_post_type'],
					 'post_status' => 'publish',
					 'orderby' => 'title',
					 'posts_per_page' => -1
				) );
				foreach( $advisors as $advisor ) {
					print( '<div class="advisor">' );
					print( '<h3>' . apply_filters( 'the_title', $advisor->post_title ) . '</h3>' );
					print( '<p>' . apply_filters( 'the_content', $advisor->post_content ) . '</p>' );
					print( '<form method="post" action="">' );
					print( '<input type="hidden" name="advisor_id" value="' . $advisor->ID . '" />' );
					print( '<input type="submit" class="advisor-request" value="' . __( 'Select this advisor', 'tno-bim-quickscan'  ) . '" />' );
					print( '</form>' );
					print( '</div>' );
				}
			} elseif( $status == 'pending' ) {
				if( $report->post_author == get_current_user_id() ) {
					print( '<p>' . __( 'An advisor is reviewing this report, once the review is done it will be visible for you here.', 'tno-bim-quickscan' ) . '</p>');
				}
			} else {
				if( $status == 'published' ) {
					print( '<h3>' . __( 'Advisor status', 'tno-bim-quickscan' ) . ': ' . __( 'Validated and published', 'tno-bim-quickscan' ) . '</h3>' );
				} elseif( $status == 'validated' ) {
					print( '<h3>' . __( 'Advisor status', 'tno-bim-quickscan' ) . ': ' . __( 'Validated', 'tno-bim-quickscan' ) . '</h3>' );
				}
				print( '<h3>' . __( 'Advisor report', 'tno-bim-quickscan' ) . '</h3>' );
				print( '<p>' . get_post_meta( $report->ID, '_advisor_report', true ) . '</p>' );
				print( '<h3>' . __( 'Advice', 'tno-bim-quickscan' ) . '</h3>' );
				print( '<p>' . get_post_meta( $report->ID, '_advice', true ) . '</p>' );
			}
		}
	}

	public static function singleReportAdvisorSettings( $report ) {
		if( isset( $_POST['save'] ) || isset( $_POST['validate'] ) || isset( $_POST['publish'] ) ) {
			if( isset( $_POST['advice'] ) ) {
				update_post_meta( $report->ID, '_advice', filter_input( INPUT_POST, 'advice', FILTER_SANITIZE_SPECIAL_CHARS ) );
			}
			if( isset( $_POST['advisor_report'] ) ) {
				update_post_meta( $report->ID, '_advisor_report', filter_input( INPUT_POST, 'advisor_report', FILTER_SANITIZE_SPECIAL_CHARS ) );
			}
			if( isset( $_POST['publish'] ) ) {
				$postData =Array(
					 'ID' => $report->ID,
					 'post_status' => 'publish'
				);
				wp_update_post( $postData );
				update_post_meta( $report->ID, '_advisor_status', 'published' );
			}
			if( isset( $_POST['validate'] ) ) {
				update_post_meta( $report->ID, '_advisor_status', 'validated' );
			}
		}

		$status = get_post_meta( $report->ID, '_advisor_status', true );
		$advice = get_post_meta( $report->ID, '_advice', true );
		$advisorReport = get_post_meta( $report->ID, '_advisor_report', true );
		print( '<form method="post" action="">' );
		print( '<label for="advisor-report">' . __( 'Report', 'tno-bim-quickscan' ) . '</label><br />' );
		print( '<textarea id="advisor-report" name="advisor_report">' . $advisorReport . '</textarea><br />' );
		print( '<label for="report-advice">' . __( 'Advice', 'tno-bim-quickscan' ) . '</label><br />' );
		print( '<textarea name="advice" id="report-advice">' . $advice . '</textarea><br /><br />' );
		print( '<input type="submit" value="' . __( 'Save', 'tno-bim-quickscan' ) . '" name="save" /><br /><br />' );
		if( $status == 'pending' ) {
			print( '<input type="submit" value="' . __( 'Validate', 'tno-bim-quickscan' ) . '" name="validate" /><br /><br />' );
		} elseif( $report->post_status != 'publish' ) {
			print( '<input type="submit" value="' . __( 'Publish', 'tno-bim-quickscan' ) . '" name="publish" /><br />' );
		}
		print( '</form>' );
	}

	public static function showSingleCompany() {
		global $tnoBIMQuickscan;

		if( isset( $_GET[ 'id' ] ) ) {
			$id = intval( $_GET[ 'id' ] );
			$author = get_user_by( 'id', $id );
		}

		if( isset( $author ) && user_can( $author, $tnoBIMQuickscan->options[ 'company_role' ] ) ) {
			$publicReports = get_posts( Array(
				 'post_type' => $tnoBIMQuickscan->options[ 'report_post_type' ],
				 'post_status' => Array( 'publish' ),
				 'author' => $author->get( 'ID' ),
				 'numberposts' => $tnoBIMQuickscan->options[ 'reports_per_page' ]
			) );
			if( count( $publicReports ) == 0 ) {
				?>
				<p><?php _e( 'This company does not have any public BIM Quickscan reports', 'tno-bim-quickscan' ); ?></p>
				<?php
			} else {
				?>
				<table class="list">
					<tr class="odd">
						<th><?php _e( 'Date', 'tno-bim-quickscan' ); ?></th>
						<th><?php _e( 'Core-business', 'tno-bim-quickscan' ); ?></th>
						<th class="number"><?php _e( 'Result', 'tno-bim-quickscan' ); ?> %</th>
					</tr>
					<?php
					$count = 0;
					foreach( $publicReports as $report ) {
						$results = get_post_meta( $report->ID,  '_results', true );
						$coreBusiness = get_post_meta( $report->ID, 'core_business', true );

						if( !$results ) {
							$results = Array( 'score' => 1, 'max_score' => 1 );
						}
						?>
						<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
							<td><a href="<?php print( get_permalink( function_exists( 'icl_object_id' ) ? icl_object_id( $tnoBIMQuickscan->options[ 'report_page' ], 'page', true ) : $tnoBIMQuickscan->options[ 'report_page' ] ) ); ?>?id=<?php print( $report->ID ); ?>"><?php print( date( 'd-m-Y', strtotime( $report->post_date ) ) ); ?></a><?php print( $report->post_status == 'private' ? ' (privé)' : '' ); ?></td>
							<?php
							$authorData = get_user_by( 'id', $report->post_author );
							?>
							<td><?php print( $coreBusiness ); ?></td>
							<td class="number"><?php print( round( $results[ 'score' ] / $results[ 'max_score' ] * 100, 2 ) . '% / 100%' ); ?></td>
						</tr>
						<?php
						$count ++;
					}
					?>
				</table>
				<?php
			}
		}
	}
	
	public static function insertMissingCountryMeta() {
		global $tnoBIMQuickscan, $wpdb;
		
		$missingMetaIds = $wpdb->get_results( "SELECT ID 
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON ID = post_id AND meta_key = '_language'
			WHERE IFNULL( meta_value, 'unknown' ) = 'unknown' AND post_type = '{$tnoBIMQuickscan->options[ 'report_post_type' ]}'" );

		foreach( $missingMetaIds as $postId ) {
			add_post_meta( $postId->ID, '_language', 'nl' );
		}
		
		return count( $missingMetaIds );
	}
}

$tnoBIMQuickscan = new TNOBIMQuickscan();