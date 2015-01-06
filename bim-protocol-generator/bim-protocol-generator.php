<?php
/*
Plugin Name: BIM Protocol Generator
Plugin URI:
Description:
Version: 1.0
Author: Bastiaan Grutters
Author URI: http://www.bastiaangrutters.nl
*/

class BIMProtocolGenerator {
	private $options;

	public function __construct() {
		add_action( 'admin_menu', Array( 'BIMProtocolGenerator', 'optionsMenu' ) );

		$this->options = get_option( 'bim_protocol_generator_options', Array() );

		if( isset( $this->options[ 'question_post_type' ] ) ) {
			add_action( 'admin_init', Array( 'BIMProtocolGenerator', 'questionsEditorInit' ) );
		}

		add_action( 'admin_enqueue_scripts', Array( 'BIMProtocolGenerator', 'adminEnqueueScripts' ) );
		add_action( 'wp_enqueue_scripts', Array( 'BIMProtocolGenerator', 'wpEnqueueScripts' ) );

		// Add post types etc at the WordPress init action
		add_action( 'init', Array( 'BIMProtocolGenerator', 'wordPressInit' ) );

		// --- Add shortcodes ---
		add_shortcode( 'showProtocolQuestions', Array( 'BIMProtocolGenerator', 'showProtocolQuestions' ) );
		add_shortcode( 'showInitiatorForm', Array( 'BIMProtocolGenerator', 'showInitiatorForm' ) );
		add_shortcode( 'showProtocolList', Array( 'BIMProtocolGenerator', 'showProtocolList' ) );

		// Load the correct language file
		add_action( 'plugins_loaded', Array( 'BIMProtocolGenerator', 'pluginsLoaded' ) );
	}

	public static function pluginsLoaded() {
		load_plugin_textdomain( 'bim-protocol-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public static function optionsMenu() {
		$pfile = basename( dirname( __FILE__ ) ) . '/bim-protocol-generator-options.php';
		add_options_page( 'bim-protocol-generator', __( 'BIM Protocol Generator Options', 'bim-protocol-generator' ), 'activate_plugins', $pfile );
	}

	public static function questionsEditorInit() {
		$options = BIMProtocolGenerator::getOptions();
		add_meta_box( 'question-meta', __( 'Question options', 'bim-protocol-generator' ), Array( 'BIMProtocolGenerator', 'questionsEditorWidget' ), $options[ 'question_post_type' ], 'normal', 'high' );
		add_action( 'save_post', Array( 'BIMProtocolGenerator', 'saveQuestionsEditorWidget' ) );
	}

	public static function adminEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'bim-protocol-generator-admin', plugins_url( 'bim-protocol-generator-admin.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
		wp_enqueue_style( 'bim-protocol-generator-admin', plugins_url( 'bim-protocol-generator-admin.css', __FILE__ ) );
	}

	public static function wpEnqueueScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'bim-protocol-generator', plugins_url( 'bim-protocol-generator.js', __FILE__ ), Array( 'jquery' ), "1.0", true );
		wp_enqueue_style( 'bim-protocol-generator', plugins_url( 'bim-protocol-generator.css', __FILE__ ) );
	}

	public static function questionsEditorWidget() {
		global $post, $wpdb, $sitepress;

		$options = BIMProtocolGenerator::getOptions();
		$typeQuestion = get_post_meta( $post->ID, '_type_question', true );
		$settings = new stdClass();
		$settings->answers = get_post_meta( $post->ID, '_answers', false );
		$settings->reportText = get_post_meta( $post->ID, '_report_text', true );
		$settings->reportTextNot = get_post_meta( $post->ID, '_report_text_not', true );
		$settings->formatTypesRead = get_post_meta( $post->ID, '_format_types_read', true );
		$settings->formatTypesWrite = get_post_meta( $post->ID, '_format_types_write', true );
		$settings->checkDocumentsType = get_post_meta( $post->ID, '_check_documents_type', true );
		$settings->other = get_post_meta( $post->ID, '_other', true );
		$settings->reportChapter = get_post_meta( $post->ID, '_report_chapter', true );
		$settings->confirmationText = __( 'Changing the question type will erase all answer information, do you want to continue?', 'bim-protocol-generator' );
		$settings->nextQuestionText = __( 'Next question', 'bim-protocol-generator' );
		$settings->noneText = __( 'None', 'bim-protocol-generator' );
		$postLanguageInformation = isset( $_GET[ 'lang' ] ) ? $_GET[ 'lang' ] : '';
		if( isset( $sitepress ) ) {
			$defaultLanguage = $sitepress->get_default_language();
		} else {
			$defaultLanguage = '';
		}
		$suffix = BIMProtocolGenerator::getLanguageSuffix( $postLanguageInformation, $defaultLanguage );
		$settings->reportChapters = $options[ 'report_chapters' . $suffix ];
?>
<div id="type-question-container">
	<label for="type-question">Questions type</label> <select name="_type_question" id="type-question">
		<option value="">
			---
			<?php _e( 'Choose a type', 'bim-protocol-generator' ); ?>
			---
		</option>
		<option value="page"<?php print( $typeQuestion == 'page' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Page containing questions', 'bim-protocol-generator' ); ?>
		</option>
		<option value="radio"<?php print( $typeQuestion == 'radio' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Radiobuttons', 'bim-protocol-generator' ); ?>
		</option>
		<option value="checkbox"<?php print( $typeQuestion == 'checkbox' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Checkboxes', 'bim-protocol-generator' ); ?>
		</option>
		<option value="participant"<?php print( $typeQuestion == 'participant' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Participant information', 'bim-protocol-generator' ); ?>
		</option>
		<option value="goals"<?php print( $typeQuestion == 'goals' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Initiator goals', 'bim-protocol-generator' ); ?>
		</option>
		<option value="0pointtemplate"<?php print( $typeQuestion == '0pointtemplate' ? ' selected="selected"' : '' ); ?>>
			<?php _e( '0 Point Templates', 'bim-protocol-generator' ); ?>
		</option>
		<option value="modelingtemplate"<?php print( $typeQuestion == 'modelingtemplate' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Modeling Templates', 'bim-protocol-generator' ); ?>
		</option>
		<option value="required_information"<?php print( $typeQuestion == 'required_information' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Required information (matrix)', 'bim-protocol-generator' ); ?>
		</option>
		<option value="leading_partner"<?php print( $typeQuestion == 'leading_partner' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Leading partner', 'bim-protocol-generator' ); ?>
		</option>
		<option value="end"<?php print( $typeQuestion == 'end' ? ' selected="selected"' : '' ); ?>>
			<?php _e( 'Thanks page, end of questions', 'bim-protocol-generator' ); ?>
		</option>
	</select>
	<div class="clear"></div>
</div>
<h4><?php _e( 'Question settings', 'bim-protocol-generator' ); ?></h4>
<div id="question-settings-container"></div>
<h4><?php _e( 'Answers', 'bim-protocol-generator' ); ?></h4>
<div id="answer-container"></div>
<script type="text/javascript">
   		var bimProtocolGeneratorSettings = <?php print( json_encode( $settings ) ); ?>;
   		</script>
<input type="hidden" name="answers_noncename" value="<?php print( wp_create_nonce(__FILE__) ); ?>" />
<?php
	}

	public static function saveQuestionsEditorWidget( $postId ) {
      if( !isset( $_POST[ 'answers_noncename' ] ) || !wp_verify_nonce( $_POST[ 'answers_noncename' ], __FILE__ ) ) {
         return $postId;
      }
      if ( !current_user_can( 'edit_post', $postId ) ) {
         return $postId;
      }

      $post = get_post( $postId );
      $options = BIMProtocolGenerator::getOptions();

      if( $post->post_type == $options[ 'question_post_type' ] ) {
         BIMProtocolGenerator::updatePostMeta( $postId, '_type_question' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_next_question' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_format_types_read' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_format_types_write' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_other' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_report_text' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_report_text_not' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_check_documents_type' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_visible_in_report' );
         BIMProtocolGenerator::updatePostMeta( $postId, '_report_chapter' );

         $answerNumber = 1;
         $answers = Array();

         while( isset( $_POST[ 'answer_text_' . $answerNumber ] ) ) {
            $answer = Array();
            if( isset( $_POST[ 'answer_text_' . $answerNumber ] ) ) {
               $answer[ 'text' ] = $_POST[ 'answer_text_' . $answerNumber ];
            }
            $answers[] = $answer;
            $answerNumber ++;
         }

         delete_post_meta( $postId, '_answers' );
         foreach( $answers AS $answer ) {
            add_post_meta( $postId, '_answers', $answer, false );
         }
      }

      return $postId;
   }

   public static function updatePostMeta( $postId, $metaKey ) {
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

	public static function getQuestionsByCode( $code ) {
		global $wpdb, $sitepress;
		$options = BIMProtocolGenerator::getOptions();
		$query = $wpdb->prepare( "SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_value = %s AND meta_key = 'code'", $code );
		$postId = $wpdb->get_var( $query );
		if( isset( $postId ) ) {
			// We retrieve the list of questions here
			$pages = Array();
			$language = get_post_meta( $postId, 'language', true );
			$pages = $wpdb->get_results( "SELECT {$wpdb->posts}.*
					FROM {$wpdb->posts} " .
					( $language != '' ? "JOIN {$wpdb->prefix}icl_translations ON element_id = ID AND language_code = '{$language}'" : '' ) . "
					JOIN {$wpdb->postmeta} ON ID = post_id
					WHERE post_type = '{$options[ 'question_post_type' ]}' AND meta_key = '_type_question' AND meta_value = 'page'
					ORDER BY menu_order ASC" );
			foreach( $pages as $key => $page ) {
				$pages[$key]->questions = $wpdb->get_results( "SELECT {$wpdb->posts}.*
					FROM {$wpdb->posts}
					WHERE post_type = '{$options[ 'question_post_type' ]}' AND post_parent = {$page->ID}
					ORDER BY menu_order ASC" );
				foreach( $pages[$key]->questions as $questionKey => $question ) {
					$pages[$key]->questions[$questionKey]->questionType = get_post_meta( $question->ID, '_type_question', true );
					$pages[$key]->questions[$questionKey]->formatTypesRead = get_post_meta( $question->ID, '_format_types_read', true );
					$pages[$key]->questions[$questionKey]->formatTypesWrite = get_post_meta( $question->ID, '_format_types_write', true );
					$pages[$key]->questions[$questionKey]->other = get_post_meta( $question->ID, '_other', true );
					$pages[$key]->questions[$questionKey]->answers = get_post_meta( $question->ID, '_answers' );
					$pages[$key]->questions[$questionKey]->reportText = get_post_meta( $question->ID, '_report_text', true );
					$pages[$key]->questions[$questionKey]->reportTextNot = get_post_meta( $question->ID, '_report_text_not', true );
					$pages[$key]->questions[$questionKey]->checkDocumentsType = get_post_meta( $question->ID, '_check_documents_type', true );
					$pages[$key]->questions[$questionKey]->reportChapter = get_post_meta( $question->ID, '_report_chapter', true );
				}
			}
			$questions = Array( 'postId' => $postId, 'pages' => $pages, 'initiator' => get_post( $postId ), 'reportStatus' => get_post_meta( $postId, '_report_status', true ), 'language' => $language );
			return $questions;
		} else {
			return false;
		}
	}

	public static function getPageHTML( $questions, $code, $currentPage = 1, $previousAnswers = Array(), $message = '' ) {
		global $sitepress;
		$options = BIMProtocolGenerator::getOptions();
		$informationLevels = Array();
		if( isset( $sitepress ) ) {
			$defaultLanguage = $sitepress->get_default_language();
		} else {
			$defaultLanguage = '';
		}
		$suffix = BIMProtocolGenerator::getLanguageSuffix( $questions[ 'language' ], $defaultLanguage );
		$informationLevels = isset( $options[ 'information_levels' . $suffix ] ) ? $options[ 'information_levels' . $suffix ] : Array();
   		$currentIndex = $currentPage - 1;
   		$html = '<div class="page page-' . $currentPage . '">';
   		$html .= '<form method="post" action="?code=' . $code . '">';
   		$html .= '<input type="hidden" name="page" value="' . $currentPage . '" />';
   		if( $message != '' ) {
   			$html .= '<span class="message-icon"></span><div class="message">' . $message . '</div>';
   		}
   		$html .= '<h2 class="page-title">' . $questions[ 'pages' ][$currentIndex]->post_title . '</h2>';
   		if( strlen( $questions[ 'pages' ][$currentIndex]->post_content ) > 5 ) {
   			$html .= '<div class="page-content">' . apply_filters( 'the_content', $questions[ 'pages' ][$currentIndex]->post_content ) . '</div>';
   		}
   		foreach( $questions[ 'pages' ][$currentIndex]->questions as $questionNumber => $question ) {
   			if( isset( $previousAnswers[ 'answers' ] ) && isset( $previousAnswers[ 'answers' ][ $currentIndex ] ) && isset( $previousAnswers[ 'answers' ][ $currentIndex ][$questionNumber] ) ) {
   				$answers = $previousAnswers[ 'answers' ][ $currentIndex ][$questionNumber];
   			} else {
   				$answers = false;
   			}
   			if( isset( $previousAnswers[ 'answers' ] ) && isset( $previousAnswers[ 'answers' ][ $currentIndex ] ) && isset( $previousAnswers[ 'answers' ][ $currentIndex ][$questionNumber . '_other'] ) ) {
   				$answerOther = $previousAnswers[ 'answers' ][ $currentIndex ][$questionNumber . '_other'];
   			} else {
   				$answerOther = false;
   			}
			$html .= '<h3 class="question-title">' . $question->post_title . '</h3>';
	      	if( strlen( $question->post_content ) > 5 ) {
	      		$html .= '<div class="question-content">' . apply_filters( 'the_content', $question->post_content ) . '</div>';
	      	}
	      	$answerNumber = 1;
	        if( $question->questionType == 'radio' ) {
	            foreach( $question->answers as $answer ) {
	               $html .= '<input type="radio" ' . ( $answers == $answer[ 'text' ] ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $answer[ 'text' ] . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	               $html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $answer[ 'text' ] . '</label>';
	               $html .= '<div class="clear"></div>';
	               $answerNumber ++;
	            }
	            if( isset( $question->other ) && $question->other == 'other' ) {
	            	$html .= '<input type="radio" ' . ( $answers == 'other' ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="other" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	            	$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . __( 'Other', 'bim-protocol-generator' ) . '</label>';
	            	$html .= '&nbsp;<input type="text" placeholder="' . __( 'Other', 'bim-protocol-generator' ) . '" name="answer_' . $currentPage . '_' . $questionNumber . '_other" value="' . ( $answerOther ? $answerOther : '' ) . '" />';
	            	$html .= '<div class="clear"></div>';
	            }
	         } elseif( $question->questionType == 'checkbox' ) {
	            $answers = !$answers ? Array() : $answers;
	            foreach( $question->answers as $answer ) {
	               $html .= '<input type="checkbox" ' . ( in_array( $answer[ 'text' ], $answers ) ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '[]" value="' . $answer[ 'text' ] . '" class="vraag-checkbox-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	               $html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $answer[ 'text' ] . '</label>';
	               $html .= '<div class="clear"></div>';
	               $answerNumber ++;
	            }
	            if( isset( $question->other ) && $question->other == 'other' ) {
	            	$html .= '<input type="checkbox" ' . ( in_array( 'other', $answers ) ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '[]" value="other" class="answer-checkbox-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	            	$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . __( 'Other', 'bim-protocol-generator' ) . '</label>';
	            	$html .= '&nbsp;<input type="text" placeholder="' . __( 'Other', 'bim-protocol-generator' ) . '" name="answer_' . $currentPage . '_' . $questionNumber . '_other" value="' . ( $answerOther ? $answerOther : '' ) . '" />';
	            	$html .= '<div class="clear"></div>';
	            }
	         } elseif( $question->questionType == 'participant' ) {
	         	// get the information for each participant and display this
	         	// and project information
	         	$participants = get_post_meta( $questions[ 'postId' ], 'participant' );
	         	$projectName = get_post_meta( $questions[ 'postId' ], 'name', true );
	         	$projectPhase = get_post_meta( $questions[ 'postId' ], 'phase', true );
	         	$html .= __( 'Project', 'bim-protocol-generator' ) . ': ' . $projectName . '<br />';
	         	$html .= __( 'Phase', 'bim-protocol-generator' ) . ': ' . $projectPhase . '<br />';
	         	$html .= '<ul>';
	         	foreach( $participants as $participant ) {
	         		$html .= '<li>' . $participant[0] . '</li>';
	         	}
	         	$html .= '</ul>';

	         } elseif( $question->questionType == 'goals' ) {
	         	$goals = get_post_meta( $questions[ 'postId' ], 'goals', true );
	            foreach( $goals as $goal ) {
	            	$html .= '<input type="radio" ' . ( $answers == $goal ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $goal . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	            	$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $goal . '</label>';
	            	$html .= '<div class="clear"></div>';
	               	$answerNumber ++;
	            }
            } elseif( $question->questionType == 'leading_partner' ) {
            	$participants = get_post_meta( $questions[ 'postId' ], 'participant' );
            	foreach( $participants as $participant ) {
            		$html .= '<input type="radio" ' . ( $answers == $participant[0] ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $participant[0] . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
            		$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $participant[0] . '</label>';
            		$html .= '<div class="clear"></div>';
            		$answerNumber ++;
            	}
	         } elseif( $question->questionType == '0pointtemplate' ) {
	         	$zeroPoints = get_post_meta( $questions[ 'postId' ], 'zeroPoints', true );
	         	foreach( $zeroPoints as $zeroPoint ) {
	         		$html .= '<input type="radio" ' . ( $answers == $zeroPoint[0] ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $zeroPoint[0] . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	         		$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $zeroPoint[0] . ' - <a target="_blank" href="' . $zeroPoint[1] . '">' . $zeroPoint[1] . '</a></label>';
	         		$html .= '<div class="clear"></div>';
	         		$answerNumber ++;
	         	}
	         } elseif( $question->questionType == 'modelingtemplate' ) {
	         	$modelingTemplates = get_post_meta( $questions[ 'postId' ], 'modelingTemplates', true );
	         	foreach( $modelingTemplates as $modelingTemplate ) {
	         		$html .= '<input type="radio" ' . ( $answers == $modelingTemplate[0] ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $modelingTemplate[0] . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
	         		$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $modelingTemplate[0] . ' - <a target="_blank" href="' . $modelingTemplate[1] . '">' . $modelingTemplate[1] . '</a></label>';
	         		$html .= '<div class="clear"></div>';
	         		$answerNumber ++;
	         	}
	         } elseif( $question->questionType == 'required_information' ) {
            	$information = get_post_meta( $questions[ 'postId' ], 'information', true );
            	$statuses = get_post_meta( $questions[ 'postId' ], 'statuses', true );
            	$participants = get_post_meta( $questions[ 'postId' ], 'participant' );
            	$formats = BIMProtocolGenerator::getQuestionFormats( $questions );
            	$modelingTemplates = get_post_meta( $questions[ 'postId' ], 'modelingTemplates', true );
            	/*foreach( $modelingTemplates as $modelingTemplate ) {
            		$html .= '<input type="radio" ' . ( $answers == $modelingTemplate[0] ? 'checked="checked" ' : '' ) . 'name="answer_' . $currentPage . '_' . $questionNumber . '" value="' . $modelingTemplate[0] . '" class="answer-radio-input" id="answer-' .  $questionNumber . '-' . $answerNumber . '" />';
            		$html .= '&nbsp;<label class="question-label" for="answer-' .  $questionNumber . '-' . $answerNumber . '">' . $modelingTemplate[0] . ' - <a target="_blank" href="' . $modelingTemplate[1] . '">' . $modelingTemplate[1] . '</a></label>';
            		$html .= '<div class="clear"></div>';
            		$answerNumber ++;
            	}*/
            	$html .= '<table id="required-information"><tr class="odd"><th>' . __( 'What do you need? (information)', 'bim-protocol-generator' ) . '</th>';
            	$html .= '<th>' . __( 'Who should provide it?', 'bim-protocol-generator' ) . '</th>';
            	$html .= '<th>' . __( 'At which information level?', 'bim-protocol-generator' ) . '</th>';
            	$html .= '<th>' . __( 'With what status?', 'bim-protocol-generator' ) . '</th>';
            	$html .= '<th>' . __( 'What kind of format would you prefer?', 'bim-protocol-generator' ) . '</th>';
            	$html .= '<th>' . __( 'What kind of modeling agreements?', 'bim-protocol-generator' ) . '</th></tr>';
				foreach( $information as $key => $informationItem ) {
	            	$html .= '<tr class="' . ( $key % 2 == 0 ? 'even' : 'odd' ) . '" id="row-' . $key . '"><td>' . $informationItem . '</td>';
	            	$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[participant][]">';
	            	foreach( $participants as $participant ) {
	            		$html .= '<option value="' . $participant[0] . '"' . ( ( $answers && isset( $answers[ 'participant' ] ) && isset( $answers[ 'participant' ][$key] ) && $answers[ 'participant' ][$key] == $participant[0] ) ? ' selected': '' ) . '>' . $participant[0] . '</option>';
	            	}
	            	$html .= '</select></td>';
	            	$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[informationlevel][]">';
	            	foreach( $informationLevels as $informationLevel ) {
	            		$html .= '<option value="' . $informationLevel . '"' . ( ( $answers && isset( $answers[ 'informationlevel' ] ) && isset( $answers[ 'informationlevel' ][$key] ) && $answers[ 'informationlevel' ][$key] == $informationLevel ) ? ' selected': '' ) . '>' . $informationLevel . '</option>';
	            	}
	            	$html .= '</select></td>';
	            	$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[status][]">';
	            	foreach( $statuses as $status ) {
	            		$html .= '<option value="' . $status . '"' . ( ( $answers && isset( $answers[ 'status' ] ) && isset( $answers[ 'status' ][$key] ) && $answers[ 'status' ][$key] == $status ) ? ' selected': '' ) . '>' . $status . '</option>';
	            	}
	            	$html .= '</select></td>';
	            	$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[format][]">';
	            	foreach( $formats as $format ) {
	            		$html .= '<option value="' . $format . '"' . ( ( $answers && isset( $answers[ 'format' ] ) && isset( $answers[ 'format' ][$key] ) && $answers[ 'format' ][$key] == $format ) ? ' selected': '' ) . '>' . $format . '</option>';
	            	}
	            	$html .= '</select></td>';
	            	$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[modelingagreement][]">';
	            	foreach( $modelingTemplates as $modelingTemplate ) {
	            		$html .= '<option value="' . $modelingTemplate[0] . '"' . ( ( $answers && isset( $answers[ 'modelingagreement' ] ) && isset( $answers[ 'modelingagreement' ][$key] ) && $answers[ 'modelingagreement' ][$key] == $modelingTemplate[0] ) ? ' selected': '' ) . '>' . $modelingTemplate[0] . '</option>';
	            	}
	            	$html .= '</select>';
	            	$html .= '</td></tr>';
				}
				$key ++;
				$extraFields = 1;
				if( $answers && isset( $answers[ 'information' ] ) ) {
					$extraFields = count( $answers[ 'information' ] );
					if( $extraFields == 0 ) {
						$extraFields = 1;
					}
				}
				for( $i = 0; $i < $extraFields; $i ++ ) {
					$html .= '<tr class="' . ( ( $key + $i ) % 2 == 0 ? 'even' : 'odd' ) . '" id="row-' . ( $key + $i ) . '"><td><input type="text" placeholder="' . __( 'Required information', 'bim-protocol-generator' ) . '" name="answer_' . $currentPage . '_' . $questionNumber . '[information][]" value="' . ( ( $answers && isset( $answers[ 'information' ] ) && isset( $answers[ 'information' ][$i] ) ) ? $answers[ 'information' ][$i] : '' ) . '" /></td>';
					$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[participant][]">';
					foreach( $participants as $participant ) {
						$html .= '<option value="' . $participant[0] . '"' . ( ( $answers && isset( $answers[ 'participant' ] ) && isset( $answers[ 'participant' ][$key] ) && $answers[ 'participant' ][$key] == $participant[0] ) ? ' selected': '' ) . '>' . $participant[0] . '</option>';
					}
					$html .= '</select></td>';
					$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[informationlevel][]">';
					foreach( $informationLevels as $informationLevel ) {
	            		$html .= '<option value="' . $informationLevel . '"' . ( ( $answers && isset( $answers[ 'informationlevel' ] ) && isset( $answers[ 'informationlevel' ][$key] ) && $answers[ 'informationlevel' ][$key] == $informationLevel ) ? ' selected': '' ) . '>' . $informationLevel . '</option>';
	            	}
					$html .= '</select></td>';
					$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[status][]">';
					foreach( $statuses as $status ) {
	            		$html .= '<option value="' . $status . '"' . ( ( $answers && isset( $answers[ 'status' ] ) && isset( $answers[ 'status' ][$key] ) && $answers[ 'status' ][$key] == $status ) ? ' selected': '' ) . '>' . $status . '</option>';
					}
					$html .= '</select></td>';
					$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[format][]">';
					foreach( $formats as $format ) {
						$html .= '<option value="' . $format . '"' . ( ( $answers && isset( $answers[ 'format' ] ) && isset( $answers[ 'format' ][$key] ) && $answers[ 'format' ][$key] == $format ) ? ' selected': '' ) . '>' . $format . '</option>';

					}
					$html .= '</select></td>';
					$html .= '<td><select name="answer_' . $currentPage . '_' . $questionNumber . '[modelingagreement][]">';
					foreach( $modelingTemplates as $modelingTemplate ) {
						$html .= '<option value="' . $modelingTemplate[0] . '"' . ( ( $answers && isset( $answers[ 'modelingagreement' ] ) && isset( $answers[ 'modelingagreement' ][$key] ) && $answers[ 'modelingagreement' ][$key] == $modelingTemplate[0] ) ? ' selected': '' ) . '>' . $modelingTemplate[0] . '</option>';
					}
					$html .= '</select>';
					$html .= '</td></tr>';
					$key ++;
				}
            	$html .= '</table>';
            	$html .= '<a href="javascript:void( null );" onclick="BIMProtocolGenerator.addTableRow( \'required-information\' )">' . __( 'Add row', 'bim-protocol-generator' ) . '</a><br />';
	         }
	      }
   		$html .= '<div class="button-container">';
   		if( $currentPage > 1 ) {
   			$html .= '<input type="submit" value="&lt; ' . __( 'Previous', 'bim-protocol-generator' ) . '" name="previous" class="previous-button" />&nbsp;';
   		}
   		if( $currentPage < count( $questions[ 'pages' ] ) ) {
   			$html .= '<input type="submit" value="' . __( 'Next', 'bim-protocol-generator' ) . '  &gt;" name="next" class="next-button" />';
   		} else {
   			$html .= '<input type="submit" value="' . __( 'Submit', 'bim-protocol-generator' ) . '  &gt;" name="next" class="next-button" />';
   		}
   		$html .= '</div>';
      	$html .= '</form>';
      	$html .= '</div>';
      	return $html;
	}

   public static function getAnswers( $codes ) {
      global $wpdb;
      // use the dual code thingie
      $codes = implode( '-', $codes );
      $query = $wpdb->prepare( "SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = '_code' AND meta_value LIKE %s", $codes );
      $postId = $wpdb->get_var( $query );
      if( isset( $postId ) && ctype_digit( $postId ) ) {
         $answers = Array( 'answers' => get_post_meta( $postId, '_answers', true ), 'postId' => $postId );
         $status = get_post_meta( $postId, '_status', true );
         if( $status && $status != '' ) {
         	$answers[ 'status' ] = $status;
         }
         return $answers;
      } else {
         return false;
      }
   }

   public static function getAnswersForAll( $codes ) {
   	global $wpdb;
   	// use the dual code thingie
   	$query = $wpdb->prepare( "SELECT post_id
   			FROM $wpdb->postmeta
   			WHERE meta_key = '_code' AND meta_value LIKE %s", $codes[0] . '-%' );
   	$postIds = $wpdb->get_results( $query );
   	if( isset( $postIds ) && count( $postIds ) > 0 ) {
   		$answersAll = Array();
   		foreach( $postIds as $post ) {
   			$answers = Array(
   					'answers' => get_post_meta( $post->post_id, '_answers', true ),
   					'postId' => $post->post_id );
   			$answers[ 'code' ] = get_post_meta( $post->post_id, '_code', true );
   			$status = get_post_meta( $post->post_id, '_status', true );
   			if( $status && $status != '' ) {
   				$answers[ 'status' ] = $status;
   			}
   			$answersAll[] = $answers;
   		}
   		return $answersAll;
   	} else {
   		return false;
   	}
   }

   public static function showProtocolQuestions() {
      global $wpdb;
      $code = isset( $_GET[ 'code' ] ) ? $_GET[ 'code' ] : '-';
      $codes = explode( '-', $code );
      $questions = false;
      $previousAnswers = false;

      if( count( $codes ) == 2 && $codes[0] != '' && $codes[1] != '' ) {
	      // Extract question information and participant information from codes
	      $questions = BIMProtocolGenerator::getQuestionsByCode( $codes[0] );
	      $options = BIMProtocolGenerator::getOptions();
	      $previousAnswers = BIMProtocolGenerator::getAnswers( $codes );
	  }

      if( $questions !== false && $previousAnswers !== false && is_array( $previousAnswers ) && ( !isset( $previousAnswers[ 'status' ] ) || $previousAnswers[ 'status' ] != 'complete' ) ) {
         $page = isset( $_POST[ 'page' ] ) ? intval( $_POST[ 'page' ] ) : 1;
         if( $page < 1 ) {
         	$page = 1;
         }
         if( $page > count( $questions[ 'pages' ] ) ) {
         	$page = count( $questions[ 'pages' ] );
         }
         $pageIndex = $page - 1;

         $havePage = $page;
         $message = '';
         // make sure we remember all the stored answers
         if( isset( $_POST[ 'previous' ] ) || isset( $_POST[ 'next' ] ) ) {
            // Answers for the questions on this page should be stored before going to the next or previous one
         	$charset = get_option( 'blog_charset' );
         	$answers = Array();
         	if( isset( $questions[ 'pages' ][$pageIndex] ) ) {
	            foreach( $questions[ 'pages' ][$pageIndex]->questions as $questionNumber => $question ) {
	            	$answer = false;
	            	if( isset( $_POST[ 'answer_' . $page . '_' . $questionNumber . '_other' ] ) ) {
	            		$otherAnswer = htmlentities( $_POST[ 'answer_' . $page . '_' . $questionNumber . '_other' ], ENT_QUOTES, $charset );
	            	} else {
	            		$otherAnswer = false;
	            	}
	            	if( isset( $_POST[ 'answer_' . $page . '_' . $questionNumber ] ) && is_array( $_POST[ 'answer_' . $page . '_' . $questionNumber ] ) ) {
	            		$answer = Array();
	            		foreach( $_POST[ 'answer_' . $page . '_' . $questionNumber ] as $key => $answerItem ) {
	            			if( is_array( $answerItem ) ) {
	            				$answer[$key] = Array();
	            				foreach( $answerItem as $answerSubItem ) {
	            					$answer[$key][] = htmlentities( $answerSubItem, ENT_QUOTES, $charset );
	            				}
	            			} else {
	            				$answer[] = htmlentities( $answerItem, ENT_QUOTES, $charset );
	            			}
	            		}
	            	} elseif( isset( $_POST[ 'answer_' . $page . '_' . $questionNumber ] ) ) {
	            		$answer = htmlentities( $_POST[ 'answer_' . $page . '_' . $questionNumber ], ENT_QUOTES, $charset );
	            	} else {
	            		// TODO: not sure if this is still required... Check if I need this for some other type of question?
	            		$answer = Array();

	            		$done = false;
	            		$number = 1;
	            		while( !$done ) {
	            			if( isset( $_POST[ 'answer_' . $page . '_' . $questionNumber . '_' . $number ] ) ) {
	            				$answer[] = htmlentities( $_POST[ 'answer_' . $page . '_' . $questionNumber . '_' . $number ], ENT_QUOTES, $charset );
	            			} else {
	            				$done = true;
	            			}
	            			$number ++;
	            		}
	            	}
	            	$answers[$questionNumber] = $answer;
	            	if( $otherAnswer !== false ) {
	            		$answers[$questionNumber . '_other'] = $otherAnswer;
	            	}
	            }
         	}

            $answerId = $previousAnswers[ 'postId' ];
            $previousAnswers[ 'answers' ][$pageIndex] = $answers;
            update_post_meta( $answerId, '_answers', $previousAnswers[ 'answers' ] );

            // Adjust page number

            if( isset( $_POST[ 'previous' ] ) ) {
               $page --;
               if( $page < 1 ) {
                  $page = 1;
               }
            } else {
            	$allValid = true;
            	foreach( $questions[ 'pages' ][$page - 1]->questions as $key => $question ) {
            		// validate all input fields before proceeding to the next page
            		if( ( ( $question->questionType == 'radio' || $question->questionType == 'goals' || $question->questionType == '0pointtemplate' || $question->questionType == 'modelingtemplate' || $question->questionType == 'leading_partner' )
            					&& ( !isset( $previousAnswers[ 'answers' ][$pageIndex][$key] ) || count( $previousAnswers[ 'answers' ][$pageIndex][$key] ) == 0 ) ) ||
            				( $question->questionType == 'checkbox' && ( !isset( $previousAnswers[ 'answers' ][$pageIndex][$key] ) || count( $previousAnswers[ 'answers' ][$pageIndex][$key] ) == 0 ) ) ||
            				( $question->questionType == 'required_information' && ( !isset( $previousAnswers[ 'answers' ][$pageIndex][$key] ) || count( $previousAnswers[ 'answers' ][$pageIndex][$key] ) == 0 ) ) ) {
            			$message .= $question->post_title . ': ';
            			if( $question->questionType == 'radio' || $question->questionType == 'modelingtemplate' || $question->questionType == 'leading_partner' ) {
            				$message .= __( 'Select one of the options', 'bim-protocol-generator' ) . '<br />';
            			} elseif( $question->questionType == 'checkbox'  ) {
            				$message .= __( 'Check at least one of the options', 'bim-protocol-generator' ) . '<br />';
            			} elseif( $question->questionType == 'goals'  ) {
            				$message .= __( 'Select one of the goals', 'bim-protocol-generator' ) . '<br />';
            			} elseif( $question->questionType == '0pointtemplate'  ) {
            				$message .= __( 'Select a 0 point template', 'bim-protocol-generator' ) . '<br />';
            			} elseif( $question->questionType == 'required_information'  ) {
            				$message .= __( 'Check at least one of the options', 'bim-protocol-generator' ) . '<br />';
            			}
            			$allValid = false;
            		}
            	}
            	if( $allValid ) {
            		$page ++;
            	}
            }
         }

         // If we are not going through the form right now we return to the latest page without filled in answers
         // For convenience and fun!
         if( !isset( $_POST[ 'page' ] ) ) {
            if( $previousAnswers ) {
            	$found = false;
	         	foreach( $questions[ 'pages' ] as $key => $formPage ) {
	            	foreach( $formPage->questions as $questionNumber => $question ) {
	            		if( $question->questionType != 'participant' ) {
		            		if( !$previousAnswers || !isset( $previousAnswers[ 'answers' ][$key] ) || !isset( $previousAnswers[ 'answers' ][$key][$questionNumber] ) || $previousAnswers[ 'answers' ][$key][$questionNumber] == '' ) {
		            			$page = $key + 1;
		            			$found = true;
		            			break 2;
		            		}
	            		}
	            	}
	            }
	            if( !$found ) {
	            	$page = count( $questions[ 'pages' ] );
	            }
            }
         }

         if( $page > count( $questions[ 'pages' ] ) && isset( $_POST[ 'next' ] ) && $_POST[ 'next' ] != '' ) {
         	print( '<p>' );
         	_e( 'Thank you for filling out the BIM protocol generator form', 'bim-protocol-generator' );
         	print( '</p>' );
         	// Set status to complete for these answers, so the form is no longer available for this code
         	update_post_meta( $previousAnswers[ 'postId' ], '_status', 'complete' );
         	// Check if all participants filled in the form, if all did we can generate the report
         	if( BIMProtocolGenerator::checkAllSubmitted( $codes, $questions ) ) {
         		BIMProtocolGenerator::generateReport( $codes, $questions );

         		print( '<a href="' . plugins_url( 'download.php', __FILE__ ) . '?code=' . $code . '">' );
         		_e( 'Download the report', 'bim-protocol-generator' );
         		print( '</a>' );
         	}
         } else {
         	print( BIMProtocolGenerator::getPageHTML( $questions, $code, $page, $previousAnswers, $message ) );
         }
      } elseif( $questions !== false && $previousAnswers !== false && is_array( $previousAnswers ) && isset( $previousAnswers[ 'status' ] ) && $previousAnswers[ 'status' ] == 'complete' ) {
      	$reportStatus = get_post_meta( $questions[ 'postId' ], '_report_status', true );
?>
		<h2><?php print( $questions[ 'initiator' ]->post_title ); ?></h2>
		<?php _e( 'Phase', 'bim-protocol-generator' ); ?>: <?php print( get_post_meta( $questions[ 'postId' ], 'phase', true ) ); ?><br />
		<br />
<?php
      	if( isset( $reportStatus ) && $reportStatus == 'complete' ) {
			// DEBUG: BIMProtocolGenerator::generateReport( $codes, $questions );
			print( '<a href="' . plugins_url( 'download.php', __FILE__ ) . '?code=' . $code . '">' );
       		_e( 'Download the report', 'bim-protocol-generator' );
       		print( '</a>' );
      	} else {
      		$participants = get_post_meta( $questions[ 'postId' ], 'participant' );
      		$participantHtml = '';
      		$answers = BIMProtocolGenerator::getAnswersForAll( $codes );
      		foreach( $participants as $participant ) {
      			$participantHtml .= $participant[0];
      			if( $answers !== false ) {
      				foreach( $answers as $answer ) {
      					if( $answer[ 'code' ] == $codes[0] . '-' . $participant[2] && isset( $answer[ 'status' ] ) && $answer[ 'status' ] == 'complete' ) {
      						$participantHtml .= ' (' . __( 'completed', 'bim-protocol-generator' ) . ')';
      						break 1;
      					}
      				}
      			}
      			$participantHtml .= '<br />';
      		}
      		$completed = 0;
      		if( $answers !== false ) {
      			foreach( $answers as $answer ) {
      				if( isset( $answer[ 'status' ] ) && $answer[ 'status' ] == 'complete' ) {
      					$completed ++;
      				}
      			}
      			$progress = round( $completed / count( $participants ) * 100 ) . '%';
      		} else {
      			$progress = '0%';
      		}
?>
		<?php _e( 'Progress', 'bim-protocol-generator' ); ?>: <?php print( $progress ); ?><br />
		<h3><?php _e( 'Participants', 'bim-protocol-generator' ); ?></h3>
<?php
			print( $participantHtml );
      	}
      } else {
?>
<p>
	<?php _e( 'No BIM protocol generator questions available for you at the moment.', 'bim-protocol-generator' ); ?>
	<br />
	<?php _e( 'Make sure you provided the correct code.', 'bim-protocol-generator' ); ?>
</p>
<?php
      }
   }

   public static function generateReport( $codes, $questions ) {
      global $wpdb, $sitepress;

      $options = BIMProtocolGenerator::getOptions();
      $answers = BIMProtocolGenerator::getAnswersForAll( $codes );
      $participants = get_post_meta( $questions[ 'postId' ], 'participant' );
      $projectName = get_post_meta( $questions[ 'postId' ], 'name', true );
      $projectPhase = get_post_meta( $questions[ 'postId' ], 'phase', true );
      $stats = Array( 'agreed' => 0, 'notAgreed' => 0 );
      $html = '';
      $chapters = Array();
      if( isset( $sitepress ) ) {
      	$defaultLanguage = $sitepress->get_default_language();
      } else {
      	$defaultLanguage = '';
      }
      $suffix = BIMProtocolGenerator::getLanguageSuffix( $questions[ 'language' ], $defaultLanguage );
      foreach( $options[ 'report_chapters' . $suffix ] as $chapter ) {
      	$chapters[$chapter] = '';
      }
      $readFormats = Array();
      $writeFormats = Array();
      $informationFlow = Array( 'from' => Array(), 'to' => Array() );
      foreach( $questions[ 'pages' ] as $pageIndex => $page ) {
      	foreach( $page->questions as $questionNumber => $question ) {
      		if( isset( $question->formatTypesWrite ) && $question->formatTypesWrite != '' ) {
      			foreach( $answers as $participantAnswers ) {
	      			$answerCode = explode( '-', $participantAnswers[ 'code' ] );
	      			$formats = Array();
	      			if( is_array( $participantAnswers[ 'answers' ] ) && isset( $participantAnswers[ 'answers' ][$pageIndex] ) && isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      				if( is_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      					$formats = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
	      				} else {
	      					$formats[] = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
	      				}
	      			}

					if( !isset( $writeFormats[$answerCode[1]] ) ) {
		      			$writeFormats[$answerCode[1]] = $formats;
		      		} else {
		      			$writeFormats[$answerCode[1]] = array_merge( $writeFormats[$answerCode[1]], $formats );
		      		}
      			}
      		}
      		if( isset( $question->formatTypesRead ) && $question->formatTypesRead != '' ) {
	      		foreach( $answers as $participantAnswers ) {
	      			$answerCode = explode( '-', $participantAnswers[ 'code' ] );
	      			$formats = Array();
	      			if( is_array( $participantAnswers[ 'answers' ] ) && isset( $participantAnswers[ 'answers' ][$pageIndex] ) && isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      				if( is_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      					$formats = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
	      				} else {
	      					$formats[] = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
	      				}
	      			}

      				if( !isset( $readFormats[$answerCode[1]] ) ) {
      					$readFormats[$answerCode[1]] = $formats;
      				} else {
      					$readFormats[$answerCode[1]] = array_merge( $writeFormats[$answerCode[1]], $formats );
      				}
	      		}
      		}
      	}
      }

      $done = false;
      $inOutPut = Array();
      $inOutPutSet = Array();
      foreach( $questions[ 'pages' ] as $pageIndex => $page ) {
      	foreach( $page->questions as $questionNumber => $question ) {
      		$chapter = $question->reportChapter;

      		if( $question->questionType == 'required_information' ) {
      			if( $chapter != '' ) {
	      			// add the required information table to the report
	      			$chapters[$chapter] .= '<table><tr><th>' . __( 'Information', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Requesting party', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Responding party', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Level', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Status', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Request preference', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Response options', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Proposal', 'bim-protocol-generator' ) . '</th>';
	      			$chapters[$chapter] .= '<th>' . __( 'Modeling template', 'bim-protocol-generator' ) . '</th></tr>';

	      			$information = get_post_meta( $questions[ 'postId' ], 'information', true );
	      			$modelingTemplates = get_post_meta( $questions[ 'postId' ], 'modelingTemplates', true );

	      			foreach( $participants as $participant ) {
	      				foreach( $answers as $participantAnswers ) {
	      					$answerCode = explode( '-', $participantAnswers[ 'code' ] );
		      				if( $answerCode[1] == $participant[2] && is_array( $participantAnswers[ 'answers' ] ) && isset( $participantAnswers[ 'answers' ][$pageIndex] ) && isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
		      					foreach( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'participant' ] as $key => $selectedParticipant ) {
		      						$requestedInformation = ( isset( $information[$key] ) ? $information[$key] : ( isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'information' ][$key - count( $information )] ) ? $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'information' ][$key - count( $information )] : '' ) );
		      						if( $requestedInformation != '' ) {
			      						$informationFlow[ 'to' ][] = $participant[0];
			      						$informationFlow[ 'from' ][] = $selectedParticipant;
			      						$chapters[$chapter] .= '<tr>';
			      						$chapters[$chapter] .= '<td>' . $requestedInformation . '</td>';
			      						$chapters[$chapter] .= '<td>' . $participant[0] . '</td>';
			      						$chapters[$chapter] .= '<td>' . $selectedParticipant . '</td>';
			      						$chapters[$chapter] .= '<td>' . $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'informationlevel' ][$key] . '</td>';
			      						$chapters[$chapter] .= '<td>' . $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'status' ][$key] . '</td>';
			      						$chapters[$chapter] .= '<td>' . $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'format' ][$key] . '</td>';
			      						$responseFormats = __( 'None', 'bim-protocol-generator' );
			      						$proposed = '';
			      						$color = '';
			      						foreach( $participants as $requestedFrom ) {
			      							if( $requestedFrom[0] == $selectedParticipant ) {
			      								//var_dump( $preferedFormats[$requestedFrom[2]] );
			      								if( isset( $writeFormats[$requestedFrom[2]] ) && count( $writeFormats[$requestedFrom[2]] ) > 0 ) {
			      									$responseFormats = implode( ', ', $writeFormats[$requestedFrom[2]] );
			      									if( in_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'format' ][$key], $writeFormats[$requestedFrom[2]] ) ) {
			      										// GREEN!
			      										$proposed = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'format' ][$key];
			      										$color = '#0BC213';
			      									} else {
			      										// YELLOW?
			      										foreach( $writeFormats[$requestedFrom[2]] as $format ) {
			      											if( in_array( $format, $readFormats[$answerCode[1]] ) ) {
			      												if( $proposed != '' ) {
			      													$proposed .= ', ';
			      												}
			      												$proposed .= $format;
			      											}
			      										}
			      										if( $proposed != '' ) {
			      											$color = '#F2F028';
			      										}
			      									}
			      									if( $proposed == '' ) {
			      										// RED!
			      										$color = '#C2110B';
			      									}
			      								}
			      								break 1;
			      							}
			      						}
			      						// tabel afmaken met juiste informatie
			      						$chapters[$chapter] .= '<td>' . $responseFormats . '</td>';
			      						$chapters[$chapter] .= '<td style="background-color: ' . $color . '">' . $proposed . '</td>';

			      						$modelingTemplateFound = false;
		      							foreach( $modelingTemplates as $modelingTemplate ) {
		      								if( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber][ 'modelingagreement' ][$key] == $modelingTemplate[0] ) {
		      									$chapters[$chapter] .= '<td><a href="' . $modelingTemplate[1] . '" target="_blank">' . $modelingTemplate[0] . '</a></td>';
		      									$modelingTemplateFound = true;
		      									break 1;
		      								}
		      							}
		      							if( !$modelingTemplateFound ) {
		      								$chapters[$chapter] .= '<td>-</td>';
		      							}
			      						$chapters[$chapter] .= '</tr>';
		      						}
		      					}
		      					break 1;
		      				}
	      				}
	      			}
	      			$chapters[$chapter] .= '</table><br /><br />';
      			}
      		} elseif( $question->questionType == 'participant' ) {
      			if( $chapter != '' ) {
	      			$chapters[$chapter] .= '<h3>' . $question->post_title . '</h3>';
	      			$chapters[$chapter] .= __( 'Project', 'bim-protocol-generator' ) . ': ' . $projectName . '<br />';
	      			$chapters[$chapter] .= __( 'Phase', 'bim-protocol-generator' ) . ': ' . $projectPhase . '<br />';
	      			$chapters[$chapter] .= '<ul>';
	      			foreach( $participants as $participant ) {
	      				$chapters[$chapter] .= '<li>' . $participant[0] . '</li>';
	      			}
	      			$chapters[$chapter] .= '</ul>';
	      			$chapters[$chapter] .= '<br />';
	      			$chapters[$chapter] .= '{agreement_percentage}<br />';
      			}
      		} elseif( $question->questionType != 'end' && $question->questionType != 'page' ) {
	      		$theAnswers = Array();
	      		$theOtherAnswers = Array();
      			$pickedAnswers = Array();
	      		$pickedOtherAnswers = Array();
	      		$noAnswers = 0;
	      		foreach( $answers as $participantAnswers ) {
	      			if( is_array( $participantAnswers[ 'answers' ] ) && isset( $participantAnswers[ 'answers' ][$pageIndex] ) && isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      				if( is_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
	      					foreach( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] as $answerOption ) {
	      						if( !isset( $pickedAnswers[$answerOption] ) ) {
	      							$pickedAnswers[$answerOption] = 1;
	      						} else {
	      							$pickedAnswers[$answerOption] ++;
	      						}
	      						if( !in_array( $answerOption, $theAnswers ) ) {
	      							$theAnswers[] = $answerOption;
	      						}
	      					}
	      				} else {
	      					if( !isset( $pickedAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber]] ) ) {
	      						$pickedAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber]] = 1;
	      					} else {
	      						$pickedAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber]] ++;
	      					}
	      					if( !in_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber], $theAnswers ) ) {
	      						$theAnswers[] = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
	      					}
	      				}
	      				if( isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other'] ) && $participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other'] != '' ) {
	      					if( !isset( $pickedOtherAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other']] ) ) {
	      						$pickedOtherAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other']] = 1;
	      					} else {
	      						$pickedOtherAnswers[$participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other']] ++;
	      					}
	      					if( !in_array( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other'], $theOtherAnswers ) ) {
	      						$theOtherAnswers[] = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber . '_other'];
	      					}
	      				}
	      			} else {
	      				// Just count this to be sure, it might or might not be possible depending on answer policy
	      				$noAnswers ++;
	      			}
	      		}
	      		if( count( $pickedOtherAnswers ) > 1 ) {
	      			$otherAnswersDifferent = true;
	      		} else {
	      			$otherAnswersDifferent = false;
	      		}
	      		$agreed = false;
	      		if( $question->questionType == 'checkbox' ) {
	      			$allSame = true;
	      			$same = count( $answers );
	      			foreach( $pickedAnswers as $amount ) {
	      				if( $same != $amount ) {
	      					$allSame = false;
	      					break 1;
	      				}
	      			}
	      			if( $allSame ) {
	      				$stats[ 'agreed' ] ++;
	      				$agreed = true;
	      			} else {
	      				$stats[ 'notAgreed' ] ++;
	      			}
	      		} elseif( count( $pickedAnswers ) == 1 && !$otherAnswersDifferent ) {
	      			// All picked the same answer, yay!
	      			$stats[ 'agreed' ] ++;
	      			$agreed = true;
	      		} else {
	      			$stats[ 'notAgreed' ] ++;
	      		}

	      		if( $chapter != '' ) {
		      		if( $question->questionType == 'goals' ) {
		      			$chapters[$chapter] .= __( 'The goal for this phase is', 'bim-protocol-generator' );
		      			$goals = get_post_meta( $questions[ 'postId' ], 'goals', true );
		      			if( $agreed ) {
		      				foreach( $goals as $goal ) {
	      						if( $theAnswers[0] == $goal ) {
	      							$chapters[$chapter] .= ' ' . $goal . '<br />';
	      							break 1;
	      						}
		      				}
		      			} else {
		      				$chapters[$chapter] .= '<br />';
		      				 $chapters[$chapter] .= __( 'Not everyone agrees on the goal. The following goals have been selected', 'bim-protocol-generator' ) . ':<br />';
		      				 foreach( $goals as $goal ) {
		      				 	foreach( $theAnswers as $theAnswer ) {
			      				 	if( $theAnswer == $goal ) {
			      				 		$chapters[$chapter] .= $goal . ': ' . round( $pickedAnswers[$goal] / count( $answers ) * 100, 2 ) . '%<br />';
			      				 		break 1;
			      				 	}
		      				 	}
		      				 }
		      			}
		      		} elseif ( $question->questionType == '0pointtemplate' ) {
		      			$chapters[$chapter] .= __( 'The team is going to use', 'bim-protocol-generator' );
		      			$zeroPoints = get_post_meta( $questions[ 'postId' ], 'zeroPoints', true );
		      			if( $agreed ) {
		      				foreach( $zeroPoints as $zeroPoint ) {
		      					if( $theAnswers[0] == $zeroPoint[0] ) {
		      						$chapters[$chapter] .= ' <a href="' . $zeroPoint[1] . '" target="_blank">' . $zeroPoint[0] . '</a><br />';
		      						break 1;
		      					}
		      				}
		      			} else {
		      				$chapters[$chapter] .= '<br />';
		      				$chapters[$chapter] .= __( 'Not everyone agrees on the 0 point template. The following options have been selected', 'bim-protocol-generator' ) . ':<br />';
		      				foreach( $zeroPoints as $zeroPoint ) {
		      					foreach( $theAnswers as $theAnswer ) {
		      						if( $theAnswer == $zeroPoint[0] ) {
		      							$chapters[$chapter] .= '<a href="' . $zeroPoint[1] . '" target="_blank">' . $zeroPoint[0] . '</a>: ' . round( $pickedAnswers[$zeroPoint[0]] / count( $answers ) * 100, 2 ) . '%<br />';
		      							break 1;
		      						}
		      					}
		      				}
		      			}
	      			} elseif ( $question->questionType == 'modelingtemplate' ) {
	      				$chapters[$chapter] .= __( 'The team is going to use', 'bim-protocol-generator' );
	      				$modelingTemplates = get_post_meta( $questions[ 'postId' ], 'modelingTemplates', true );
	      				if( $agreed ) {
	      					foreach( $modelingTemplates as $modelingTemplate ) {
	      						if( $theAnswers[0] == $modelingTemplate[0] ) {
	      							$chapters[$chapter] .= ' <a href="' . $modelingTemplate[1] . '" target="_blank">' . $modelingTemplate[0] . '</a><br />';
	      							break 1;
	      						}
	      					}
	      				} else {
	      					$chapters[$chapter] .= '<br />';
	      					$chapters[$chapter] .= __( 'Not everyone agrees on the modeling template. The following options have been selected', 'bim-protocol-generator' ) . ':<br />';
	      					foreach( $modelingTemplates as $modelingTemplate ) {
	      						foreach( $theAnswers as $theAnswer ) {
	      							if( $theAnswer == $modelingTemplate[0] ) {
	      								$chapters[$chapter] .= '<a href="' . $modelingTemplate[1] . '" target="_blank">' . $modelingTemplate[0] . '</a>: ' . round( $pickedAnswers[$modelingTemplate[0]] / count( $answers ) * 100, 2 ) . '%<br />';
	      								break 1;
	      							}
	      						}
	      					}
	      				}
		      		} elseif( $question->questionType == 'leading_partner' ) {
		      			if( $agreed ) {
		      				$chapters[$chapter] .= __( 'The team sees the following partner as leading', 'bim-protocol-generator' );
		      				$chapters[$chapter] .= ': ' . $theAnswers[0] . '<br />';
		      			} else {
		      				$chapters[$chapter] .= __( 'Not everyone agrees who should be the leading partner', 'bim-protocol-generator' ) . ':<br />';
	      					foreach( $theAnswers as $theAnswer ) {
	   							$chapters[$chapter] .= $theAnswer . ': ' . round( $pickedAnswers[$theAnswer] / count( $answers ) * 100, 2 ) . '%<br />';
	      					}
		      			}
		      		} elseif ( $question->checkDocumentsType == 'input_check' || $question->checkDocumentsType == 'output_check' ) {
		      			$inOutPutSet[$question->checkDocumentsType] = true;
		      			foreach( $participants as $participant ) {
		      				foreach( $answers as $participantAnswers ) {
		      					$codeParts = explode( '-', $participantAnswers[ 'code' ] );
		      					if( $codeParts[1] == $participant[2] ) {
		      						if( isset( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] ) ) {
			      						if( !isset( $inOutPut[$participant[2]] ) ) {
			      							$inOutPut[$participant[2]] = Array( 'name' => $participant[0] );
			      						}
			      						$inOutPut[$participant[2]][$question->checkDocumentsType] = $participantAnswers[ 'answers' ][$pageIndex][$questionNumber];
			      						foreach( $question->answers as $key => $answer ) {
			      							if( $participantAnswers[ 'answers' ][$pageIndex][$questionNumber] == htmlentities( $answer[ 'text' ], ENT_QUOTES, get_option( 'blog_charset' ) ) ) {
			      								// maybe change this if question changes... for now it is ok
			      								if( $key == 0 ) {
			      									$inOutPut[$participant[2]][$question->checkDocumentsType . '_value' ] = false;
			      								} else {
			      									$inOutPut[$participant[2]][$question->checkDocumentsType . '_value' ] = true;
			      								}
			      								break 1;
			      							}
			      						}
		      						}
		      						break 1;
		      					}
		      				}
		      			}

		      			if( isset( $inOutPutSet[ 'input_check' ] ) && isset( $inOutPutSet[ 'output_check' ] ) ) {
		      				$chapters[$chapter] .= '<table><tr><th>' . __( 'Party', 'bim-protocol-generator' ) . '</th>';
		      				$chapters[$chapter] .= '<th>' . __( 'Check outgoing', 'bim-protocol-generator' ) . '</th>';
		      				$chapters[$chapter] .= '<th>' . __( 'Check incoming', 'bim-protocol-generator' ) . '</th>';
		      				$chapters[$chapter] .= '<th>' . __( 'Party', 'bim-protocol-generator' ) . '</th></tr>';
		      				foreach( $inOutPut as $code => $values ) {
		      				 	foreach( $inOutPut as $code2 => $values2 ) {
		      				 		if( $code != $code2 ) {
		      				 			$show = false;
		      				 			foreach( $informationFlow[ 'from' ] as $flowKey => $from ) {
		      				 				if( $from == $values[ 'name' ] && $informationFlow[ 'to' ][$flowKey] == $values2[ 'name' ] ) {
		      				 					$show = true;
		      				 					break 1;
		      				 				}
		      				 			}
		      				 			if( $show ) {
		      				 				$allStyle = '';
		      				 				if( !$values[ 'output_check_value' ] && !$values2[ 'input_check_value' ] ) {
		      				 					$style = ' style="background-color: #C2110B; font-weight: bold;"';
		      				 				} else {
		      				 					$style = '';
		      				 				}
		      				 			} else {
		      				 				$style = $allStyle = ' style="background-color: #cccccc;"';
		      				 			}

			      				 		$chapters[$chapter] .= '<tr>';
			      				 		$chapters[$chapter] .= '<td' . $allStyle . '>' . $values[ 'name' ] . '</td>';
			      				 		$chapters[$chapter] .= '<td' . $style . '>' . $values[ 'output_check' ] . '</td>';
			      				 		$chapters[$chapter] .= '<td' . $style . '>' . $values2[ 'input_check' ] .  '</td>';
			      				 		$chapters[$chapter] .= '<td' . $allStyle . '>' . $values2[ 'name' ] . '</td>';
			      				 		$chapters[$chapter] .= '</tr>';
			      				 	}
		      				 	}
		      				}
		      				$chapters[$chapter] .= '</table>';
		      			}
		      		} elseif( $question->questionType == 'checkbox' ) {
		      			$chapters[$chapter] .= $question->reportText . ': ' . '<br />';
	      				foreach( $theAnswers as $theAnswer ) {
	      			 		$chapters[$chapter] .= ( $theAnswer == 'other' ? __( $theAnswer, 'bim-protocol-generator' ) : $theAnswer ) . ( $theAnswer == 'other' ? ( ': ' . implode( ', ', $theOtherAnswers ) ) : '' ) . ': ' . round( $pickedAnswers[$theAnswer] / count( $answers ) * 100, 2 ) . '%<br />';
						}
		      		} else {
		      			if( $agreed ) {
		      				$chapters[$chapter] .= $question->reportText . ': ' . ( $theAnswers[0] == 'other' ? __( $theAnswers[0], 'bim-protocol-generator' ) : $theAnswers[0] ) . ( $theAnswers[0] == 'other' ? ( ': ' . implode( ', ', $theOtherAnswers ) ) : '' ) . '<br />';
		      			} else {
		      				$chapters[$chapter] .= $question->reportText . ': -<br />';
		      				$chapters[$chapter] .= $question->reportTextNot . ':<br />';
	      					foreach( $theAnswers as $theAnswer ) {
	      						$chapters[$chapter] .= ( $theAnswer == 'other' ? __( $theAnswer, 'bim-protocol-generator' ) : $theAnswer ) . ( $theAnswer == 'other' ? ( ': ' . implode( ', ', $theOtherAnswers ) ) : '' ) . ': ' . round( $pickedAnswers[$theAnswer] / count( $answers ) * 100, 2 ) . '%<br />';
	      					}
		      			}
		      		}
		      		$chapters[$chapter] .= '<br />';
	      		}
      		}
      	}
      }

      // Put all the chapters in the report
      foreach( $chapters as $key => $chapter ) {
      	$html .= '<h2>' . $key . '</h2>';
      	$html .= $chapter;
      }

      $agreementRatio = round( $stats[ 'agreed' ] / ( $stats[ 'agreed' ] + $stats[ 'notAgreed' ] ) * 100, 2 );
      // Set the average agreement percentage
      $html = '<img src="http://bimprotocolgenerator.com/wp-content/uploads/sites/24/2014/03/bimprotocolgenerator-300x52.png" alt="BIM Protocol Generator" /><br /><br />' .
      	__( 'This document was generated by bimprotocolgenerator.com on', 'bim-protocol-generator' ) . ' ' . date( 'Y-m-d H:i' ) . '.<br /><br />' .
      	str_replace( '{agreement_percentage}', '<strong>' . __( 'The average "agreement percentage" of this BIM protocol is', 'bim-protocol-generator' ) . ': ' . $agreementRatio . '%</strong><br />', $html );

      $html .= '<h2>' . __( 'Disclaimer', 'bim-protocol-generator' ) . '</h2>';
      $html .= '<p>' . stripslashes( $options[ 'disclaimer_text' . $suffix ] ) . '</p>';
      $html .= '<h2>' . __( 'Definitions', 'bim-protocol-generator' ) . '</h2>';
      $html .= '<p>' . stripslashes( $options[ 'definitions' . $suffix ] ) . '</p>';

      //var_dump( $html, $questions[ 'postId' ] );

      wp_update_post( Array(
      		'ID' => $questions[ 'postId' ],
      		'post_content' => $html
      		) );

      update_post_meta( $questions[ 'postId' ], '_report_status', 'complete' );

      // go through all participants and send email
      $title = __( 'BIM Protocol Generator Report', 'bim-protocol-generator' );
      $initiatorId = get_post_meta( $questions[ 'postId' ], 'initiator', true );
      $initiatorUser = get_userdata( $initiatorId );
      if( $questions[ 'language' ] != '' && function_exists( 'icl_object_id' ) ) {
      	// Get the correct page for this language
      	$languagePostId = icl_object_id( $options[ 'question_page' ], 'page', false, $questions[ 'language' ] );
      	$uri = get_permalink( $languagePostId );
      } else {
      	$uri = get_bloginfo( 'wpurl' ) . $options[ 'question_uri' ];
      }

      foreach( $participants as $participant ) {
      	$content = $participant[0] . ",\n\n" .
      			__( 'A BIM Protocol report has been generated.', 'bim-protocol-generator' ) . "\n" .
      			__( 'Project', 'bim-protocol-generator' ) . ': ' . $projectName . "\n" .
      			__( 'Phase', 'bim-protocol-generator' ) . ': ' . $projectPhase . "\n" .
      			__( 'You can download the report from the following link:', 'bim-protocol-generator' ) . "\n" .
      			$uri . "?code={$codes[0]}-{$participant[2]}\n\n" .
      			__( 'Best regards', 'bim-protocol-generator' ) . ",\n" .
      			$initiatorUser->user_firstname . ' ' . $initiatorUser->user_lastname;
      	wp_mail( $participant[1], $title, $content );
      }
   }

	public static function getOptions( $forceReload = false ) {
		global $bimProtocolGenerator;
   		if( $forceReload ) {
			$bimProtocolGenerator->options = get_option( 'bim_protocol_generator_options', Array() );
		}
		return $bimProtocolGenerator->options;
	}

	public static function wordPressInit() {
		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'Questions', 'post type general name' ),
						'singular_name' => _x( 'Question', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New Question' ),
						'edit_item' => __( 'Edit Question' ),
						'new_item' => __( 'New Question' ),
						'all_items' => __( 'All Questions' ),
						'view_item' => __( 'View Question' ),
						'search_items' => __( 'Search Questions' ),
						'not_found' =>  __( 'No Questions found' ),
						'not_found_in_trash' => __( 'No Questions found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'Questions' ),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => true,
				'has_archive' => true,
				'hierarchical' => true,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' )
		);
		register_post_type( 'question', $postTypeArguments );

		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'Initiators', 'post type general name' ),
						'singular_name' => _x( 'Initiator', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New Initiator' ),
						'edit_item' => __( 'Edit Initiator' ),
						'new_item' => __( 'New Initiator' ),
						'all_items' => __( 'All Initiators' ),
						'view_item' => __( 'View Initiator' ),
						'search_items' => __( 'Search Initiators' ),
						'not_found' =>  __( 'No Initiator found' ),
						'not_found_in_trash' => __( 'No Initiators found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'Initiators' ),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => false,
				'rewrite' => false,
				'has_archive' => false,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' )
		);
		register_post_type( 'initiator', $postTypeArguments );

		$postTypeArguments = Array(
				'labels' => Array(
						'name' => _x( 'Answers', 'post type general name' ),
						'singular_name' => _x( 'Answer', 'post type singular name'),
						'add_new' => __( 'Add New' ),
						'add_new_item' => __( 'Add New Answer' ),
						'edit_item' => __( 'Edit Answer' ),
						'new_item' => __( 'New Answer' ),
						'all_items' => __( 'All Answers' ),
						'view_item' => __( 'View Answer' ),
						'search_items' => __( 'Search Answers' ),
						'not_found' =>  __( 'No Answers found' ),
						'not_found_in_trash' => __( 'No Answers found in Trash' ),
						'parent_item_colon' => '',
						'menu_name' => 'Answers' ),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => Array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' )
		);
		register_post_type( 'answer', $postTypeArguments );
	}

	public static function showInitiatorForm() {
		if( !is_user_logged_in() ) {
			_e( 'Please log in to initiate a BIM Protocol.', 'bim-protocol-generator' );
			//wp_login_form(); // This is apparently not compatible with BruteForce
		} else {
			$currentUser = wp_get_current_user();
			if( isset( $_POST[ 'submit' ] ) ) {
				$options = BIMProtocolGenerator::getOptions();
				$initiator = $currentUser->ID;
				$projectName = $_POST[ 'project_name' ];
				$projectPhase = $_POST[ 'project_phase' ];
				$participants = Array( Array( $currentUser->user_firstname . ' ' . $currentUser->user_lastname, $currentUser->user_email, uniqid() ) );
				$index = 1;
				while( isset( $_POST[ 'name_' . $index ] ) ) {
					if( $_POST[ 'name_' . $index ] != '' && $_POST[ 'email_' . $index ] != ''  ) {
						$participants[] = Array( $_POST[ 'name_' . $index ], $_POST[ 'email_' . $index ], uniqid() );
					}
					$index ++;
				}
				$index = 1;
				$goals = Array();
				while( isset( $_POST[ 'goal_' . $index ] ) ) {
					if( $_POST[ 'goal_' . $index ] != '' ) {
						$goals[] = $_POST[ 'goal_' . $index ];
					}
					$index ++;
				}
				$zeroPoints = Array();
				$index = 1;
				while( isset( $_POST[ '0point_template_' . $index ] ) ) {
					if( $_POST[ '0point_template_' . $index ] != '' && $_POST[ '0point_uri_' . $index ] != ''  ) {
						$zeroPoints[] = Array( $_POST[ '0point_template_' . $index ], $_POST[ '0point_uri_' . $index ] );
					}
					$index ++;
				}
				$modelingTemplates = Array();
				$index = 1;
				while( isset( $_POST[ 'modeling_template_' . $index ] ) ) {
					if( $_POST[ 'modeling_template_' . $index ] != '' && $_POST[ 'modeling_uri_' . $index ] != ''  ) {
						$modelingTemplates[] = Array( $_POST[ 'modeling_template_' . $index ], $_POST[ 'modeling_uri_' . $index ] );
					}
					$index ++;
				}
				$index = 1;
				$information = Array();
				while( isset( $_POST[ 'information_' . $index ] ) ) {
					if( $_POST[ 'information_' . $index ] != '' ) {
						$information[] = $_POST[ 'information_' . $index ];
					}
					$index ++;
				}
				$index = 1;
				$statuses = Array();
				while( isset( $_POST[ 'status_' . $index ] ) ) {
					if( $_POST[ 'status_' . $index ] != '' ) {
						$statuses[] = $_POST[ 'status_' . $index ];
					}
					$index ++;
				}
				$postData = Array(
					'post_title' => $projectName . ' ' . date( 'd-m-Y' ),
					'post_type' => $options[ 'initiator_post_type' ],
					'post_status' => 'publish'
				);
				$postId = wp_insert_post( $postData );
				if( ctype_digit( $postId ) ) {
					$uniqId = uniqid();
					add_post_meta( $postId, 'initiator', $initiator, true );
					add_post_meta( $postId, 'name', $projectName, true );
					add_post_meta( $postId, 'phase', $projectPhase, true );
					global $sitepress;
					$currentLanguage = '';
					if( isset( $sitepress ) ) {
						$currentLanguage = $sitepress->get_current_language();
						add_post_meta( $postId, 'language', $currentLanguage );
					}
					foreach( $participants as $participant ) {
						add_post_meta( $postId, 'participant', $participant );
						$postData = Array(
								'post_status' => 'publish',
								'post_type' => $options[ 'answer_post_type' ],
								'post_author' => 1,
								'ping_status' => get_option( 'default_ping_status' ),
								'post_title' => 'Answers for ' . $uniqId . '-' . $participant[2] );
						$answerId = wp_insert_post( $postData );
						add_post_meta( $answerId, '_code', $uniqId . '-' . $participant[2] );
					}
					add_post_meta( $postId, 'goals', $goals, true );
					add_post_meta( $postId, 'zeroPoints', $zeroPoints, true );
					add_post_meta( $postId, 'modelingTemplates', $modelingTemplates, true );
					add_post_meta( $postId, 'information', $information, true );
					add_post_meta( $postId, 'statuses', $statuses, true );
					add_post_meta( $postId, 'code', $uniqId, true );
					if( $currentLanguage != '' && function_exists( 'icl_object_id' ) ) {
						// Get the correct page for this language
						$languagePostId = icl_object_id( $options[ 'question_page' ], 'page', false, $currentLanguage );
						$uri = get_permalink( $languagePostId );
					} else {
						$uri = get_bloginfo( 'wpurl' ) . $options[ 'question_uri' ];
					}
					// send out emails to all the participants with the unique link to their questions
					foreach( $participants as $participant ) {
						$subject = __( 'Invitation to BIM Protocol Generator questions', 'bim-protocol-generator' );
						$content = $participant[0] . ",\n\n" .
								__( 'You have been invited to fill in the BIM Protocol Generator questions for a project phase.', 'bim-protocol-generator' ) . "\n" .
								__( 'Follow the link below to get started:', 'bim-protocol-generator' ) . "\n" .
								$uri . "?code={$uniqId}-{$participant[2]}\n\n" .
								__( 'Best regards', 'bim-protocol-generator' ) . ",\n" .
								$currentUser->user_firstname . ' ' . $currentUser->user_lastname;
						if( wp_mail( $participant[1], $subject, $content ) ) {
							_e( 'Invitation sent to', 'bim-protocol-generator' );
							print( ': ' . $participant[0] . ' (' . $participant[1] . ')<br />' );
						} else {
							_e( 'Could not send invitation to', 'bim-protocol-generator' );
							print( ': ' . $participant[0] . ' (' . $participant[1] . ')!<br />' );
							if( WP_DEBUG ) {
								var_dump( $participant[1], $subject, $content );
							}
						}
					}
				} else {
?>
					<p><?php _e( 'There was a problem storing the data for this BIM protocol generator, try again or contact an admin', 'bim-protocol-generator' ); ?></p>
<?php
				}
			} else {
?>
			<form method="post" action="">
				<h3>1. <?php _e( 'Project information', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'Please provide some general meta-information about the project. This is being used in the header and title of the generated BIM protocol.', 'bim-protocol-generator' ); ?></p>
				<table id="initiator-table" class="bim-protocol-generator-table">
					<tr>
						<td><label for="project-name"><?php _e( 'Project name', 'bim-protocol-generator' ); ?></label></td>
						<td><input type="text" name="project_name" id="project-name" placeholder="<?php _e( 'Project name', 'bim-protocol-generator' ); ?>" /></td>
						<td><span class="table-description"><?php _e( '(the name under which the project is known)', 'bim-protocol-generator' ); ?></span></td>
					</tr>
					<tr>
						<td><label for="project-phase"><?php _e( 'Project phase name', 'bim-protocol-generator' ); ?></label></td>
						<td><input type="text" name="project_phase" id="project-phase" placeholder="<?php _e( 'Project phase name', 'bim-protocol-generator' ); ?>" /></td>
						<td><span class="table-description"><?php _e( '(for example: concept, specification, etc...)', 'bim-protocol-generator' ); ?></span></td>
					</tr>
				</table>
				<h3>2. <?php _e( 'Participants', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'Please list the participants in this phase. After initiating the protocol generator, all participants will get an invitation. This list is also used to answer some questions (for example: ‘who do you think should be the BIM manager’)', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Name', 'bim-protocol-generator' ); ?></th>
						<th><?php _e( 'Email', 'bim-protocol-generator' ); ?></th>
						<th></th>
					</tr>
					<tr>
						<td>1) </td>
						<td><?php print( $currentUser->user_firstname . ' ' . $currentUser->user_lastname ); ?></td>
						<td><?php print( $currentUser->user_email ); ?></td>
						<td><span class="table-description"><?php _e( 'change your name in your profile', 'bim-protocol-generator' ); ?> <a href="<?php bloginfo( 'wpurl' ); ?>/wp-admin/profile.php" target="_blank"><?php _e( 'here', 'bim-protocol-generator' ); ?></a></span></td>
					</tr>
					<tr class="participant-row">
						<td class="row-number"><span class="row-number">2) </span></td>
						<td><input type="text" name="name_1" placeholder="<?php _e( 'Name', 'bim-protocol-generator' ); ?>" /></td>
						<td><input type="email" name="email_1" placeholder="<?php _e( 'Email', 'bim-protocol-generator' ); ?>" /></td>
						<td></td>
					</tr>
					<tr>
						<td colspan="4"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( 'participant', [ '<?php _e( 'Name', 'bim-protocol-generator' ); ?>', '<?php _e( 'Email', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add another participant', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<h3>3. <?php _e( 'Project goals', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'Define the possible answers to question 1: “why do you think the team is going to work with BIM”. The participants will only have the options you fill out here to answer the question. Examples can be ‘create drawings’, ‘visualize the design’, ‘coordination of the engineering’, etc. Please provide as clear and ad much goals as possible.', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Possible goal', 'bim-protocol-generator' ); ?></th>
					</tr>
					<tr class="goal-row">
						<td class="row-number"><span class="row-number">1) </span></td>
						<td><input type="text" name="goal_1" placeholder="<?php _e( 'Goal', 'bim-protocol-generator' ); ?>" /></td>
					</tr>
					<tr>
						<td colspan="2"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( 'goal', [ '<?php _e( 'Goal', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add more posible answers', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<h3>4. <?php _e( 'Origin templates', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'There are several methods to match the origin of different discipline models (aspect models). The participants are being asked which concept has their preference. The participants will only have the options you fill out here to answer the question. You have to provide the name of the concept and an URL with more information about it. Examples can be ‘the block’ at <a href="http://nationalbimguidelines.nl/origintemplates/block" target="_blank">http://nationalbimguidelines.nl/origintemplates/block</a> or ‘CoBIM’ from ‘<a href="http://files.kotisivukone.com/en.buildingsmart.kotisivukone.com/COBIM2012/cobim_2_inventory_bim_v1.pdf" target="_blank">http://files.kotisivukone.com/en.buildingsmart.kotisivukone.com/COBIM2012/cobim_2_inventory_bim_v1.pdf</a>’. If you don’t know what to fill out, please find inspiration on <a href="http://bimprotocolgenerator.com/initiate/origintemplates/" target="_blank">http://bimprotocolgenerator.com/initiate/origintemplates/</a>', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Name', 'bim-protocol-generator' ); ?></th>
						<th><?php _e( 'URL', 'bim-protocol-generator' ); ?></th>
					</tr>
					<tr class="0point-row">
						<td class="row-number"><span class="row-number">1) </span></td>
						<td><input type="text" name="0point_template_1" placeholder="<?php _e( 'Name', 'bim-protocol-generator' ); ?>" /></td>
						<td><input type="url" name="0point_uri_1" placeholder="<?php _e( 'URL', 'bim-protocol-generator' ); ?>" /></td>
					</tr>
					<tr>
						<td colspan="3"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( '0point', [ '<?php _e( 'Name', 'bim-protocol-generator' ); ?>', '<?php _e( 'URL', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add more possible answers', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<h3>5. <?php _e( 'Modelling templates/guidelines', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'There are several guidelines and agreements about the way a BIM model should be constructed. The participants are being asked which template/guideline/agreement has their preference. The participants will only have the options you fill out here to answer the question. You have to provide the name of the agreement template and an URL with more information about it. Examples can be ‘GSA guidelines’ at <a href="http://www.gsa.gov/portal/content/102281" target="_blank">http://www.gsa.gov/portal/content/102281</a> or ‘CoBIM architectural’ from ‘<a href="http://files.kotisivukone.com/en.buildingsmart.kotisivukone.com/COBIM2012/cobim_3_architectural_design_v1.pdf" target="_blank">http://files.kotisivukone.com/en.buildingsmart.kotisivukone.com/COBIM2012/cobim_3_architectural_design_v1.pdf</a>’. If you don’t know what to fill out, please find inspiration on <a href="http://bimprotocolgenerator.com/initiate/modelguidelines/" target="_blank">http://bimprotocolgenerator.com/initiate/modelguidelines/</a>', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Name', 'bim-protocol-generator' ); ?></th>
						<th><?php _e( 'URL', 'bim-protocol-generator' ); ?></th>
					</tr>
					<tr class="modelingtemplate-row">
						<td class="row-number"><span class="row-number">1) </td>
						<td><input type="text" name="modeling_template_1" placeholder="<?php _e( 'Modeling template', 'bim-protocol-generator' ); ?>" /></td>
						<td><input type="url" name="modeling_uri_1" placeholder="<?php _e( 'URL', 'bim-protocol-generator' ); ?>" /></td>
					</tr>
					<tr>
						<td colspan="3"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( 'modelingtemplate', [ '<?php _e( 'Modeling template', 'bim-protocol-generator' ); ?>', '<?php _e( 'URL', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add more possible answers', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<h3>6. <?php _e( 'Required information', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'In question 8 the participants will be asked what information they need from the other project participants. This will be asked as a matrix/list what information they need, from who, in what format and on what level and what the status of the information should be. The participants are free to add the information they need, but as an initiator you can provide mandatory information blocks that have to be answered. For example if you fill out ‘building part A’ in this list, every participant has to define how they want to receive information from ‘building part A’, from who, in what format, etc.', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Required information blocks', 'bim-protocol-generator' ); ?></th>
					</tr>
					<tr class="information-row">
						<td class="row-number"><span class="row-number">1) </span></td>
						<td><input type="text" name="information_1" placeholder="<?php _e( 'Required information', 'bim-protocol-generator' ); ?>" /></td>
					</tr>
					<tr>
						<td colspan="2"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( 'information', [ '<?php _e( 'Required information', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add more possible answers', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<h3>7. <?php _e( 'Information status', 'bim-protocol-generator' ); ?></h3>
				<p><?php _e( 'In question 8 the participants will be asked what information they need from the other project participants. This will be asked as a matrix/list what information they need, from who, in what format and on what level and what the status of the information should be. In this list you can define what kind of ‘status’ the participants can request the information to be at. Examples can be ‘concept’, ‘definitive’, ‘proposal’, etc.', 'bim-protocol-generator' ); ?></p>
				<table class="bim-protocol-generator-table">
					<tr>
						<th></th>
						<th><?php _e( 'Possible statuses of information', 'bim-protocol-generator' ); ?></th>
					</tr>
					<tr class="status-row">
						<td class="row-number"><span class="row-number">1) </span></td>
						<td><input type="text" name="status_1" placeholder="<?php _e( 'Information status', 'bim-protocol-generator' ); ?>" /></td>
					</tr>
					<tr>
						<td colspan="2"><a href="javascript:void( null );" onclick="BIMProtocolGenerator.addRow( 'status', [ '<?php _e( 'Information status', 'bim-protocol-generator' ); ?>' ] );"><?php _e( 'Click here to add more possible answers', 'bim-protocol-generator' ); ?></a></td>
					</tr>
				</table>
				<div class="button-container">
					<input type="submit" value="<?php _e( 'Send invitations', 'bim-protocol-generator' ); ?>" name="submit" />
				</div>
			</form>
<?php
			}
		}
	}

	public static function getQuestionFormats( $questions ) {
		$formatTypes = Array();
		foreach( $questions[ 'pages' ] as $page ) {
			foreach( $page->questions as $question ) {
				if( isset( $question->formatTypesRead ) && $question->formatTypesRead != '' ) {
					foreach( $question->answers as $answer ) {
						$formatTypes[] = $answer[ 'text' ];
					}
				}
			}
		}
		return $formatTypes;
	}

	public static function checkAllSubmitted( $codes, $questions ) {
		$options = BIMProtocolGenerator::getOptions();
		$filledAnswers = BIMProtocolGenerator::getAnswersForAll( $codes );
		$participants = get_post_meta( $questions[ 'postId' ], 'participant' );
		$allSubmitted = true;
		foreach( $participants as $participant ) {
			foreach( $filledAnswers as $answers ) {
				if( $codes[0] . '-' . $participant[2] == $answers[ 'code' ] ) {
					if( !isset( $answers[ 'status' ] ) || $answers[ 'status' ] != 'complete' ) {
						$allSubmitted = false;
					}
					break 1;
				}
			}
		}
		return $allSubmitted;
	}

	public static function printWordDocumentByCode( $code ) {
		$codes = explode( '-', $code );
		if( count( $codes ) == 2 ) {
			$code = $codes[0];
		}
		$questions = BIMProtocolGenerator::getQuestionsByCode( $code );
		if( $questions !== false && isset( $questions[ 'reportStatus' ] ) && $questions[ 'reportStatus' ] == 'complete' ) {
			header( 'Content-type: application/vnd.ms-word' );
			header( 'Content-Disposition: attachment;Filename=' . sanitize_title( $questions[ 'initiator' ]->post_title ) . '.doc' );
?>
<html
    xmlns:o='urn:schemas-microsoft-com:office:office'
    xmlns:w='urn:schemas-microsoft-com:office:word'
    xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
    	<title><?php print( $questions[ 'initiator' ]->post_title ); ?></title>
    	<xml>
    	    <w:worddocument xmlns:w="#unknown">
	            <w:view>Print</w:view>
            	<w:zoom>90</w:zoom>
            	<w:donotoptimizeforbrowser />
        	</w:worddocument>
    	</xml>
	</head>
	<body lang=EN-US>
		<?php print( $questions[ 'initiator' ]->post_content ); ?>
	</body>
</html>
<?php
			return true;
		} else {
			return false;
		}
	}

	public static function showProtocolList() {
		if( is_user_logged_in() ) {
			$options = BIMProtocolGenerator::getOptions();
			$protocols = get_posts( Array(
					'post_type' => $options[ 'initiator_post_type' ],
					'author' => get_current_user_id(),
					'numberposts' => -1
					) );
			if( isset( $_POST[ 'protocol_id' ] ) ) {
				$protocol = get_post( $_POST[ 'protocol_id' ] );
				$reportStatus = get_post_meta( $protocol->ID, '_report_status', true );
				if( $protocol->post_type == $options[ 'initiator_post_type' ] && $protocol->post_author == get_current_user_id() &&
						( !isset( $reportStatus ) || $reportStatus != 'complete' ) ) {
					$code = get_post_meta( $protocol->ID, 'code', true );
					$questions = BIMProtocolGenerator::getQuestionsByCode( $code );
					BIMProtocolGenerator::generateReport( Array( $code ), $questions );
				}
			}
?>
		<table id="protocol-list">
			<tr class="odd"><th><?php _e( 'Project', 'bim-protocol-generator' ); ?></th><th><?php _e( 'Phase', 'bim-protocol-generator' ); ?></th><th><?php _e( 'Participants', 'bim-protocol-generator' ); ?></th><th><?php _e( 'Language', 'bim-protocol-generator' ); ?></th><th><?php _e( 'Progress', 'bim-protocol-generator' ); ?></th><th><?php _e( 'Action', 'bim-protocol-generator' ); ?></th></tr>
<?php
			$count = 0;
			foreach( $protocols as $protocol ) {
				$code = get_post_meta( $protocol->ID, 'code', true );
				$language = get_post_meta( $protocol->ID, 'language', true );
				$participants = get_post_meta( $protocol->ID, 'participant' );
				$participantHtml = '';
				$answers = BIMProtocolGenerator::getAnswersForAll( Array( $code ) );
				if( $language != '' && function_exists( 'icl_object_id' ) ) {
					// Get the correct page for this language
					$languagePostId = icl_object_id( $options[ 'question_page' ], 'page', false, $language );
					$uri = get_permalink( $languagePostId );
				} else {
					$uri = get_bloginfo( 'wpurl' ) . $options[ 'question_uri' ];
				}
				foreach( $participants as $participant ) {
					$participantHtml .= $participant[0] . ': <a href="' . $uri . '?code=' . $code . '-' . $participant[2] . '">' . __( 'URL', 'bim-protocol-generator' ) . '</a>';
					if( $answers !== false ) {
						foreach( $answers as $answer ) {
							if( $answer[ 'code' ] == $code . '-' . $participant[2] && isset( $answer[ 'status' ] ) && $answer[ 'status' ] == 'complete' ) {
								$participantHtml .= ' (' . __( 'completed', 'bim-protocol-generator' ) . ')';
								break 1;
							}
						}
					}
					$participantHtml .= '<br />';
				}
				$completed = 0;
				if( $answers !== false ) {
					foreach( $answers as $answer ) {
						if( isset( $answer[ 'status' ] ) && $answer[ 'status' ] == 'complete' ) {
							$completed ++;
						}
					}
					$progress = round( $completed / count( $participants ) * 100 ) . '%';
				}
				$reportStatus = get_post_meta( $protocol->ID, '_report_status', true );
				if( isset( $reportStatus ) && $reportStatus == 'complete' ) {
					$action = __( 'Report ready', 'bim-protocol-generator' );
				} elseif( $completed > 1 && $completed > count( $answers ) * 0.5 ) {
					$action = '<form method="post" action=""><input type="submit" value="' . __( 'Force generate report', 'bim-protocol-generator' ) . '" /><input type="hidden" name="protocol_id" value="' . $protocol->ID . '" /></form>';
				} else {
					$action = '-';
				}
?>
			<tr class="<?php print( $count % 2 == 0 ? 'even' : 'odd' ); ?>">
				<td><?php print( $protocol->post_title ); ?></td>
				<td><?php print( get_post_meta( $protocol->ID, 'phase', true ) ); ?></td>
				<td><?php print( $participantHtml ); ?></td>
				<td><?php print( $language == '' ? __( 'default', 'bim-protocol-generator' ) : $language ); ?></td>
				<td><?php print( $progress ); ?></td>
				<td><?php print( $action ); ?></td>
			</tr>
<?php
				$count ++;
			}
?>
		</table>
<?php
		} else {
			_e( 'Please log in to see your initiated protocols.', 'bim-protocol-generator' );
		}
	}

	public static function getLanguageSuffix( $language, $default ) {
		$suffix = '';
		if( $language != '' && $language != $default ) {
			$suffix = '_' . $language;
		}
		return $suffix;
	}
}

$bimProtocolGenerator = new BIMProtocolGenerator();