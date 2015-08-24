<?php

namespace WordPressBimserver;


class BimserverUser {
   private $user = false;
   private $bimserverData = false;


   public function __construct( $userId = -1 ) {
      if( $userId == -1 && is_user_logged_in() ) {
         $userId = get_current_user_id();
      }

      if( $userId != -1 ) {
         $this->user = get_userdata( $userId );
         $this->$bimserverData = get_user_meta( $this->user->ID, '_bimserver_data', true );
      } else {
         throw new \Exception( 'Unknown user id' );
      }
   }



   /**
    * @return false|\WP_User
    */
   public function getUser() {
      return $this->user;
   }

   /**
    * @param \WP_User $user
    */
   public function setUser( $user ) {
      $this->user = $user;
   }

}