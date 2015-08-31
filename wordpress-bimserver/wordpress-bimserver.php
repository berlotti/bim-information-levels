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
 * [showBimserverSettings]
 * [showIfcForm]
 * [showReports]
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
      
      // --- Shortcodes ---
      add_shortcode( 'showBimserverSettings', Array( '\WordPressBimserver\WordPressBimserver', 'showBimserverSettings' ) );
      add_shortcode( 'showIfcForm', Array( '\WordPressBimserver\WordPressBimserver', 'showIfcForm' ) );
      add_shortcode( 'showReports', Array( '\WordPressBimserver\WordPressBimserver', 'showReports' ) );

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
   
   public function userRegister( $userId ) {
      $options = WordPressBimserver::getOptions();
      $userData = get_user_by( 'id', $userId );
      $username = $userData->get( 'user_email' );
      // Password generated
      if( function_exists( 'openssl_random_pseudo_bytes' ) ) {
         $password = bin2hex( openssl_random_pseudo_bytes( 8 ) );
      } else {
         // Unsafe fallback method in case openssl is not available for php
         $password = uniqid();
      }

      $name = '';
      if( isset( $_POST['first_name'] ) ) {
         $name .= $_POST['first_name'];
      }
      if( isset( $_POST['last_name'] ) ) {
         if( $name != '' ) {
            $name .= ' ';
         }
         $name .= $_POST['last_name'];
      }
      if( $name == '' ) {
         $name = $userData->get( 'user_login' );
      }

      $parameters = Array(
         'username' => $username,
         'password' => $password,
         'name' => $name,
         'type' => 'USER',
         'selfRegistration' => true,
         'resetUrl' => ''
      );


      // register
      $bimserver = new BimServerApi( $options['url'] );
      try {
         $response = $bimserver->apiCall( 'ServiceInterface', 'addUserWithPassword', $parameters );
         update_user_meta( $userId, '_bimserver_password', $password );
         update_user_meta( $userId, '_bimserver_uoid', $response );
         $bimserverUser = new BimserverUser( get_current_user_id() );
         $poid = $bimserverUser->apiCall( 'Bimsie1ServiceInterface', 'addProject', Array(
            'projectName' => 'WordPressBimserver',
            'schema' => ''
         ) );
         update_user_meta( $userId, '_bimserver_poid', $poid );
      } catch( \Exception $e ) {
         // TODO: Error registering on the BIM server... what do we do?
      }
   }

   public function showBimserverSettings() {
      if( is_user_logged_in() ) {
         $bimserverUser = new BimserverUser( get_current_user_id() );
         if( $bimserverUser->isBimserverUser() ) {
            $userSettings = $bimserverUser->getBimserverUserSettings();
            if( isset( $_POST['submit'] ) ) {
               // TODO: store settings

            }
            // TODO: generate a form based on the settings
            ?>
             <form method="post" action="">
                <?php

                ?>
                <input type="submit" name="submit" value="<?php _e( '', 'wordpress-bimserver' ); ?>" />
             </form>
             <?php
         } else {
            _e( 'This is not a valid Bimserver user',  'wordpress-bimserver' );
         }
      }
   }

   public function showIfcForm() {
      if( is_user_logged_in() ) {
         $options = WordPressBimserver::getOptions();
         $error = false;
         if( isset( $_POST['submit'], $_FILES['ifc'] ) ) {
            // upload the IFC to the bimserver and start the service
            $comment = isset( $_POST['comment'] ) ? filter_input( INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS ) : '';
            $data = file_get_contents( $_FILES['ifc']['tmp_name'] );
            $size = strlen( $data );
            $filename = $_FILES['ifc']['name'];
            $poid = get_user_meta( get_current_user_id(), '_bimserver_poid', true );
            $deserializer = ''; // TODO: figure out what value to use for this

            $parameters = Array(
               'poid' => $poid,
               'comment' => $comment,
               'deserializerOid' => $deserializer,
               'fileSize' => $size,
               'fileName' => $filename,
               'data' => $data,
               'merge' => $options['new_project'] == 'no',
               'sync' => true
            );

            try {
               $user = new BimserverUser( get_current_user_id() );
               $result = $user->apiCall( 'ServiceInterface', 'checkin', $parameters );

               // TODO: make this async

               // TODO: handle the results
            } catch( \Exception $e ) {
               $error = $e->getMessage();
            }

         }

         if( isset( $_POST['submit'], $_FILES['ifc'] ) || $error !== false ) {
            if( $error !== false ) {
               print( '<div class="error-message">' . __( 'There was a problem running this service', 'wordpress-bimserver' ) . ': ' . $error . '</div>' );
            }
            ?>
             <form method="post" enctype="multipart/form-data" action="">
                <label for="ifc-file"><?php _e( 'IFC', 'wordpress-bimserver' ); ?></label><br />
                <input type="file" name="ifc" id="ifc-file" accept=".ifc" /><br />
                <label for="checkin-comment"><?php _e( 'Comment', 'wordpress-bimserver' ); ?></label><br />
                <textarea name="comment" id="checkin-comment" placeholder="<?php _e( 'Comment', 'wordpress-bimserver' ); ?>"><?php print( isset( $_POST['comment'] ) ? filter_input( INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS ) : '' ); ?></textarea><br />
                <br />
                <input type="submit" name="submit" value="<?php _e( 'Upload', 'wordpress-bimserver' ); ?>" />
             </form>
            <?php
         }
      }
   }

   public function showReports() {
      if( is_user_logged_in() ) {
         // TODO: show a report list or something...
      }
   }
}

$wordPressBimserver = new WordPressBimserver();
