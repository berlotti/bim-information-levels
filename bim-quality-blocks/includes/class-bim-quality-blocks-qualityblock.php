<?php
namespace BIMQualityBlocks;

class QualityBlock {
   public $post;
   public $layer;
   public $reportText;
   public $relations;
   public $deselects;
   public $behaviour;
   public $reportXml;

   public function __construct( $id ) {
      if( is_object( $id ) ) {
         $this->post = $id;
      } else {
         $this->post = get_post( intval( $id ) );
      }
      if( !isset( $this->post ) || !$this->isQualityBlock() ) {
         throw new \Exception( 'Invalid Quality Block ID supplied' );
      }
      // Load block meta data
      $this->layer = get_post_meta( $this->post->ID, '_block_layer', true );
      $this->behaviour = get_post_meta( $this->post->ID, '_special_behaviour', true );
      $this->relations = get_post_meta( $this->post->ID, '_relations', true );
      if( !is_array( $this->relations ) ) {
         $this->relations = Array();
      }
      $this->deselects = get_post_meta( $this->post->ID, '_deselects', true );
      if( !is_array( $this->deselects ) ) {
         $this->deselects = Array();
      }
      $this->reportText = get_post_meta( $this->post->ID, '_report_text', true );
      $this->reportXml = get_post_meta( $this->post->ID, '_report_xml', true );
   }

   private function isQualityBlock() {
      $options = BIMQualityBlocks::getOptions();
      return $this->post->post_type == $options['bim_quality_blocks_post_type'];
   }
}
