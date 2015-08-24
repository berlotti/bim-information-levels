<?php
/*
Plugin Name: WordPress Bimserver
Plugin URI:
Description: WordPress Bimserver connects WordPress with a Bimserver to enable a web frontend for Bimserver
Version: 0.1
Author: Bastiaan Grutters
Author URI: http://www.bastiaangrutters.nl
*/

/*
 * Usage: Place shortcodes in pages:
 * [showWordPressBimserver]
 */

namespace WordPressBimserver;

class WordPressBimserver {
   private $options;
   
   public function __construct() {
      spl_autoload_register( Array( '\WordPressBimserver\WordPressBimserver', 'autoload' ) );
      
      add_action( 'admin_menu', Array( '\WordPressBimserver\WordPressBimserver', 'optionsMenu' ) );
      
      $this->options = get_option( 'wordpress_bimserver_options', Array() );
      
      add_action( 'admin_enqueue_scripts', Array( '\WordPressBimserver\WordPressBimserver', 'adminEnqueueScripts' ) );
      add_action( 'wp_enqueue_scripts', Array( '\WordPressBimserver\WordPressBimserver', 'wpEnqueueScripts' ) );
      
      // Add post types etc at the WordPress init action
      add_action( 'init', Array( '\WordPressBimserver\WordPressBimserver', 'wordPressInit' ) );
      
      // --- Add shortcodes ---
      //add_shortcode( 'showWordPressBimserver', Array( '\WordPressBimserver\WordPressBimserver', 'showWordPressBimserver' ) );

      // action for ajax call (or just outside wordpress calls with WP context)
      add_action( 'wp_ajax_bqb_download_report', Array( '\WordPressBimserver\WordPressBimserver', 'downloadReport' ) );
      add_action( 'wp_ajax_bqb_download_xml', Array( '\WordPressBimserver\WordPressBimserver', 'showReportXml' ) );

      // Registration action
      add_action( 'user_register', Array( '\WordPressBimserver\WordPressBimserver', 'userRegister' ), 10, 1 );
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
      $filename = plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-bimserver' .
          strtolower( str_replace( '\\', '-', $class ) ) . '.php';
      require_once( $filename );
   }
   
   public static function optionsMenu() {
      add_options_page( __( 'WordPress and Bimserver Options', 'wordpress-bimserver' ), __( 'WordPress and Bimserver Options', 'wordpress-bimserver' ),
          'activate_plugins', basename( dirname( __FILE__ ) ) . '/wordpress-bimserver-options.php' );
   }
   
   public static function adminEnqueueScripts() {
      wp_enqueue_script( 'jquery' );
      wp_enqueue_style( 'wordpress-bimserver', plugins_url( 'wordpress-bimserver.css', __FILE__ ) );
   }
   
   public static function wpEnqueueScripts() {
      wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'json-fallback', plugins_url( 'libraries/json.js', __FILE__ ), Array(), "1.0", true );
      wp_enqueue_script( 'wordpress-bimserver', plugins_url( 'wordpress-bimserver.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
      wp_enqueue_style( 'wordpress-bimserver', plugins_url( 'wordpress-bimserver.css', __FILE__ ) );
   }
   
   public static function getOptions( $forceReload = false ) {
      global $wordPressBimserver;
      if( $forceReload ) {
         $wordPressBimserver->options = get_option( 'wordpress_bimserver_options', Array() );
      }
      return $wordPressBimserver->options;
   }
   
   public static function wordPressInit() {
      $postTypeArguments = Array(
          'labels' => Array(
              'name' => __( 'Report', 'wordpress-bimserver' ),
              'singular_name' => __( 'Report', 'wordpress-bimserver' ),
              'add_new' => __( 'Add New', 'wordpress-bimserver' ),
              'add_new_item' => __( 'Add New Report', 'wordpress-bimserver' ),
              'edit_item' => __( 'Edit Report', 'wordpress-bimserver' ),
              'new_item' => __( 'New Report', 'wordpress-bimserver' ),
              'all_items' => __( 'All Reports', 'wordpress-bimserver' ),
              'view_item' => __( 'View Report', 'wordpress-bimserver' ),
              'search_items' => __( 'Search Reports', 'wordpress-bimserver' ),
              'not_found' =>  __( 'No Reports found', 'wordpress-bimserver' ),
              'not_found_in_trash' => __( 'No Reports found in Trash', 'wordpress-bimserver' ),
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
      register_post_type( 'wp_bimserver_report', $postTypeArguments );
   }
   
   public static function downloadReport() {
      if( is_user_logged_in() && isset( $_GET['id'] ) ) {
      } else {
         _e( 'Reports are only available if you log in', 'wordpress-bimserver' );
      }
   }
   
   public static function ajaxCallback() {
      // Save or update private blocks
      if( is_user_logged_in() && isset( $_POST['title'] ) && $_POST['title'] != '' ) {
         $options = WordPressBimserver::getOptions();
      }
      exit(); // When we are done we do not allow anything else to be done
   }

   public function userRegister( $userId ) {
      $options = WordPressBimserver::getOptions();
      $userData = get_user_by( 'id', $userId );
      $username = $userData->get( 'user_login' );
      // TODO: password generated or extract from $_POST...
      $password = '';
      // TODO: probably need an email address too
      $email = '';
      // TODO: register
   }
}

$wordPressBimserver = new WordPressBimserver();
