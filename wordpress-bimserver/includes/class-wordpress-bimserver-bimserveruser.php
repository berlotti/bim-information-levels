<?php

namespace WordPressBimserver;


class BimserverUser {
   private $user = false;
   private $bimserverPassword;
   private $isBimserverUser = false;
   private $bimserver;
   private $bimserverUserSettings = false;

   /**
    * @param int $userId
    *
    * @throws \Exception
    */
   public function __construct( $userId = -1 ) {
      if( $userId == -1 && is_user_logged_in() ) {
         $userId = get_current_user_id();
      }

      if( $userId != -1 ) {
         $this->user = get_userdata( $userId );
         $this->bimserverPassword = get_user_meta( $this->user->ID, '_bimserver_password', true );
         if( $this->bimserverPassword != '' ) {
            $this->isBimserverUser = true;
            $options = WordPressBimserver::getOptions();
            $this->bimserver = new BimServerApi( $options['url'] );
            $this->authenticateWithBimServer();
            $settings = get_user_meta( $this->user->ID, '_bimserver_settings', true );
            if( $settings != '' ) {
               $this->bimserverUserSettings = $settings;
            } else {
               // TODO: retrieve the settings and store them

            }
         } else {
            $this->isBimserverUser = false;
         }
      } else {
         throw new \Exception( 'Unknown user id' );
      }
   }

   private function authenticateWithBimServer() {
      $currentToken = get_user_meta( $this->user->ID, '_bimserver_token', true );
      if( $currentToken != '' ) {
         $this->bimserver->setToken( $currentToken );
         try {
            $isLoggedIn = $this->bimserver->apiCall( 'Bimsie1AuthInterface', 'isLoggedIn' );
            if( isset( $isLoggedIn['response'], $isLoggedIn['response']['result'] ) ) {
               $invalidToken = !$isLoggedIn['response']['result'];
            } else {
               $invalidToken = true;
            }
         } catch( \Exception $e ) {
            $invalidToken = true;
         }
      } else {
         $invalidToken = true;
      }
      if( $invalidToken ) {
         $token = $this->bimserver->apiCall( 'Bimsie1AuthInterface', 'login', Array( 'username' => $this->user->user_email, 'password' => $this->bimserverPassword ) );
         if( isset( $token['response'], $token['response']['result'] ) ) {
            $this->bimserver->setToken( $token['response']['result'] );
            update_user_meta( $this->user->ID, '_bimserver_token', $token['response']['result'] );
         } else {
            throw new \Exception( 'Could not authenticate with Bimserver' );
         }
      }
   }

   /**
    * @param string     $interface
    * @param string     $method
    * @param array      $parameters
    *
    * @return array|bool|mixed|object
    */
   public function apiCall( $interface, $method, $parameters = Array() ) {
      if( isset( $this->bimserver ) ) {
         return $this->bimserver->apiCall( $interface, $method, $parameters );
      } else {
         return false;
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

   /**
    * @return boolean
    */
   public function isBimserverUser() {
      return $this->isBimserverUser;
   }

   /**
    * @return bool|mixed
    */
   public function getBimserverUserSettings() {
      return $this->bimserverUserSettings;
   }

   /**
    * @param bool|mixed $bimserverUserSettings
    */
   public function setBimserverUserSettings( $bimserverUserSettings ) {
      $this->bimserverUserSettings = $bimserverUserSettings;
   }

   public function addProject( $name ) {
      if( $this->isBimserverUser() ) {
         $options = WordPressBimserver::getOptions();
         $sanitizedName = sanitize_title( $name );
         try {
            $existingProjects = $this->apiCall( 'Bimsie1ServiceInterface', 'getAllProjects', Array( 'onlyTopLevel' => true, 'onlyActive' => true ) );
            $number = 1;
            $numberText = '';
            $unique = false;
            while( !$unique ) {
               if( $number > 1 ) {
                  $numberText = '-' . $number;
               }
               $unique = true;
               foreach( $existingProjects['response']['result'] as $project ) {
                  if( $sanitizedName . $numberText == $project['name'] ) {
                     $unique = false;
                     break 1;
                  }
               }
               $number ++;
            }
            $poid = $this->apiCall( 'Bimsie1ServiceInterface', 'addProject', Array(
                'projectName' => $sanitizedName . $numberText,
                'schema' => $options['project_scheme']
            ) );
            if( isset( $poid['response'], $poid['response']['result'], $poid['response']['result']['oid'] ) ) {
               // Add the configured service to this project
               $sService = $this->getSServiceObject( $poid['response']['result']['oid'] );
               $this->apiCall( 'ServiceInterface', 'addLocalServiceToProject', Array(
                   'poid' => $poid['response']['result']['oid'],
                   'internalServiceOid' => $options['service_id'],
                   'sService' => $sService
               ) );
            } else {
               return false;
            }
         } catch( \Exception $e ) {
            var_dump( $e );
            $poid = false;
         }
         return $poid;
      } else {
         return false;
      }
   }

   public function getSServiceObject( $poid ) {
      $service = $this->getServiceInformation();
      if( $service !== false ) {
         $options = WordPressBimserver::getOptions();
         $sService = Array(
            '__type' => 'SService',
            'description' => $service['description'],
            'internalServiceId' => $options['service_id'],
            'serviceIdentifier' => $options['service_id'],
            'name' => $service['name'],
            'serviceName' => $service['name'],
            'notificationProtocol' => $service['notificationProtocol'],
            'providerName' => $service['providerName'],
            'profileDescription' => $service['description'],
            'profileIdentifier' => $service['identifier'],
            'profileName' => $service['name'],
            'profilePublic' => false,
            'projectId' => $poid,
            'readExtendedDataId' => $service['readExtendedData'],
            'readRevisionId' => $service['readRevision'],
            'writeExtendedDataId' => $service['writeExtendedData'],
            'writeRevisionId' => $service['writeRevision'],
            'token' => $service['token'],
            'trigger' => $service['trigger'],
            'url' => $service['url'],

            'userId' => -1, // TODO: the user id?
            'rid' => '', // TODO: unknown
         );
         return $sService;
      } else {
         return false;
      }
   }

   public function getServiceInformation() {
      $service = get_option( '_wordpress_bimserver_service' );
      if( $service == '' ) {
         $options = WordPressBimserver::getOptions();
         $services = $this->apiCall( 'ServiceInterface', 'getAllLocalServiceDescriptors' );
         $service = false;
         foreach( $services['response']['result'] as $checkService ) {
            if( $checkService['identifier'] == $options['service_id'] ) {
               $service = $checkService;
               break;
            }
         }
         if( $service !== false ) {
            update_option( '_wordpress_bimserver_service', $service );
         }
      }
      return $service;
   }
}