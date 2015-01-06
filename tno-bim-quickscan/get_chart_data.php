<?php
include( '../../../wp-config.php' );

if( is_user_logged_in() && isset( $_GET[ 'selected' ] ) && isset( $_GET[ 'start' ] ) && isset( $_GET[ 'end' ] ) ) {
	// Max length for labels
	$labelLength = 25;
	global $sitepress;

	if( isset( $sitepress ) && isset( $_GET[ 'lang' ] ) && strlen( $_GET[ 'lang' ] ) == 2 ) {
		$sitepress->switch_lang( 'en', true );
		$defaultLanguage = $sitepress->get_default_language();
		$language = $_GET[ 'lang' ];
	} else {
		$defaultLanguage = '';
		$language = '';
	}
	
	if( isset( $sitepress ) ) {
		remove_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );		
	}

	$start = explode( '-', $_GET[ 'start' ] );
	$end = explode( '-', $_GET[ 'end' ] );
	$selected = explode( ',', $_GET[ 'selected' ] );
	if( count( $start ) == 2 && ctype_digit( $start[0] ) && ctype_digit( $start[1] ) &&
			count( $end ) == 2 && ctype_digit( $end[0] ) && ctype_digit( $end[1] ) &&
			count( $selected ) > 0 ) {
		global $tnoBIMQuickscan;
		// Validate start and end range
		$start = mysql_real_escape_string( $start[1] . '-' . $start[0] . '-01 00:00:00' );
		$end = mysql_real_escape_string( $end[1] . '-' . $end[0] . '-31 23:59:59' );

		$userId = get_current_user_id();

		$selectedStack = Array();
		foreach( $selected as $selectedItem ) {
			if( $selectedItem == '--all--' ) {
				$selectedStack[0] = '';
			} elseif( $selectedItem == '--own--' ) {
				$selectedStack[1] = 'post_author = ' . $userId;
			} elseif( $selectedItem != '' ) {
				if( !isset( $selectedStack[2] ) ) {
					$selectedStack[2] = '';
				} else {
					$selectedStack[2] .= ', ';
				}
				$selectedStack[2] .= '\'' . mysql_real_escape_string( $selectedItem ) . '\'';
			}
		}

		if( $_GET[ 'types' ] == 'quickscan' ) {
			$types = "LEFT OUTER JOIN $wpdb->postmeta AS advisors ON ID = advisors.post_id AND advisors.meta_key = '_advisor' ";
			$whereAdd = "AND NOT advisors.meta_value IS NULL ";
		} elseif( $_GET[ 'types' ] == 'selfscan' ) {
			$types = "LEFT OUTER JOIN $wpdb->postmeta AS advisors ON ID = advisors.post_id AND advisors.meta_key = '_advisor' ";
			$whereAdd = "AND advisors.meta_value IS NULL ";
		} else {
			$types = '';
			$whereAdd = '';
		}
		
		if( $_GET[ 'show_language' ] != '_all_' && strlen( $_GET[ 'show_language' ] ) == 2 ) {
			$languageJoin = " JOIN $wpdb->postmeta AS language_meta ON ID = language_meta.post_id AND language_meta.meta_key = '_language' AND language_meta.meta_value = '" . mysql_real_escape_string( $_GET[ 'show_language' ] ) . "' ";
		} else {
			$languageJoin = '';
		}

		// We fetch reports from the right data range and from the selected core_businesses (special case for all and our own reports)
		$query = "SELECT $wpdb->posts.*, $wpdb->postmeta.meta_value AS core_business, categories.meta_value AS categories, topics.meta_value AS topics
				FROM $wpdb->posts $languageJoin
				LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = 'core_business'
				LEFT JOIN $wpdb->postmeta AS categories ON categories.post_id = $wpdb->posts.ID AND categories.meta_key = '_categories'
				LEFT JOIN $wpdb->postmeta AS topics ON topics.post_id = $wpdb->posts.ID AND topics.meta_key = '_topics' " .
				$types .
				"WHERE post_type = '{$tnoBIMQuickscan->options[ 'report_post_type' ]}' AND post_date >= '$start' AND post_date <= '$end' " . $whereAdd;

		if( !isset( $selectedStack[0] ) && isset( $selectedStack[1] ) && !isset( $selectedStack[2] ) ) {
			$query .= 'AND ' . $selectedStack[1] . ' ';
		} elseif( !isset( $selectedStack[0] ) && isset( $selectedStack[1] ) && isset( $selectedStack[2] ) ) {
			$query .= 'AND ( ' . $selectedStack[1] . " OR $wpdb->postmeta.meta_value IN( " . $selectedStack[2] . ' ) ) ';
		} elseif( !isset( $selectedStack[0] ) && !isset( $selectedStack[1] ) && isset( $selectedStack[2] ) ) {
			$query .= "AND $wpdb->postmeta.meta_value IN( " . $selectedStack[2] . ' ) ';
		}
		$query .= "ORDER BY ( $wpdb->postmeta.meta_value = 'anders' ) ASC, $wpdb->postmeta.meta_value ASC";
		//print( "Query: $query\n" );

		$reports = $wpdb->get_results( $query );

		$labelLength = 18;
		$categoryInformation = get_terms( $tnoBIMQuickscan->options[ 'taxonomy_category' ], Array( 'hide_empty' => false ) );
		$topicInformation = get_terms( $tnoBIMQuickscan->options[ 'taxonomy_topic' ], Array( 'hide_empty' => false, 'exclude' => Array( $tnoBIMQuickscan->options[ 'exclude_topic' ] ) ) );

		$dataRanges = Array();
		foreach( $reports as $report ) {
			$reportCategories = maybe_unserialize( $report->categories );
			foreach( $categoryInformation as $category ) {
				$entry = Array( 'data' => isset( $reportCategories[$category->term_id] ) ? ( $reportCategories[$category->term_id][1] != 0 ? $reportCategories[$category->term_id][0] / $reportCategories[$category->term_id][1] * 100 : 0 ) : 0, 'amount' => 1 );
				if( !isset( $dataRanges[ 'max' ] ) ) {
					$dataRanges[ 'max' ] = Array();
				}
				if( !isset( $dataRanges[ 'max' ][ $category->term_id ] ) ) {
					$dataRanges[ 'max' ][ $category->term_id ] = Array( 'data' => 100 );
				}

				if( isset( $selectedStack[0] ) ) {
					if( !isset( $dataRanges[ 'all' ] ) ) {
						$dataRanges[ 'all' ] = Array();
					}
					if( isset( $dataRanges[ 'all' ][ $category->term_id ] ) ) {
						$dataRanges[ 'all' ][ $category->term_id ][ 'data' ] += $entry[ 'data' ];
						$dataRanges[ 'all' ][ $category->term_id ][ 'amount' ] += $entry[ 'amount' ];
					} else {
						$dataRanges[ 'all' ][ $category->term_id ] = $entry;
					}
				}
				if( isset( $selectedStack[1] ) ) {
					if( !isset( $dataRanges[ 'own' ] ) ) {
						$dataRanges[ 'own' ] = Array();
					}
					if( $report->post_author == $userId ) {
						if( isset( $dataRanges[ 'own' ][ $category->term_id ] ) ) {
							$dataRanges[ 'own' ][ $category->term_id ][ 'data' ] += $entry[ 'data' ];
							$dataRanges[ 'own' ][ $category->term_id ][ 'amount' ] += $entry[ 'amount' ];
						} else {
							$dataRanges[ 'own' ][ $category->term_id ] = $entry;
						}
					}
				}
				if( isset( $selectedStack[2] ) && isset( $report->core_business ) && in_array( $report->core_business, $selected ) ) {
					if( !isset( $dataRanges[ 'core_business_' . $report->core_business ] ) ) {
						$dataRanges[ 'core_business_' . $report->core_business ] = Array();
					}
					if( isset( $dataRanges[ 'core_business_' . $report->core_business ][ $category->term_id ] ) ) {
						$dataRanges[ 'core_business_' . $report->core_business ][ $category->term_id ][ 'data' ] += $entry[ 'data' ];
						$dataRanges[ 'core_business_' . $report->core_business ][ $category->term_id ][ 'amount' ] += $entry[ 'amount' ];
					} else {
						$dataRanges[ 'core_business_' . $report->core_business ][ $category->term_id ] = $entry;
					}
				}
			}
		}

		if( isset( $dataRanges[ 'own' ] ) && count( $dataRanges[ 'own' ] ) == 0 ) {
			foreach( $categoryInformation as $category ) {
				$dataRanges[ 'own' ][ $category->term_id ] = Array( 'data' => 0, 'amount' => 1 );
			}
		}

		// devide values by the amount of reports in that set
		foreach( $dataRanges as $key => $dataRange ) {
			if( $key != 'max' ) {
				foreach( $categoryInformation as $category ) {
					$dataRanges[$key][ $category->term_id ][ 'data' ] /= $dataRanges[$key][ $category->term_id ][ 'amount' ];
				}
			}
		}

		$radarLabels = Array();
		foreach( $categoryInformation as $category ) {
			if( $defaultLanguage == '' || 'en' == $language ) {
				if( !isset( $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id ] ) ) {
					$radarLabels[] = substr( $category->name, 0, $labelLength );
				} else {
					$radarLabels[] = $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id ];
				}
			} else {
				if( !isset( $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id . '_' . $language ] ) ) {
					$languageId = icl_object_id( $category->term_id, $tnoBIMQuickscan->options[ 'taxonomy_category' ], true, $language );
					$categoryLanguage = get_term( $languageId, $tnoBIMQuickscan->options[ 'taxonomy_category' ] );
					$radarLabels[] = substr( $categoryLanguage->name, 0, $labelLength );
				} else {
					$radarLabels[] = $tnoBIMQuickscan->options[ 'aspect_short_name_' . $category->term_id . '_' . $language ];
				}
			}
		}

		$colorIndex = 0;
		$radarChart = Array( 'labels' => $radarLabels, 'datasets' => Array() );
		foreach( $dataRanges as $key => $dataRange ) {
			if( $key == 'max' ) {
				$colorIndex = 0;
			} elseif( $key == 'all' ) {
				$colorIndex = 1;
			} elseif( $key == 'own' ) {
				$colorIndex = 2;
			} else {
				foreach( $selected as $selectedKey => $selectedItem ) {
					if( $key == 'core_business_' . $selectedItem ) {
						$colorIndex = 1 + $selectedKey;
						break;
					}
				}
			}
			$radarPlot = Array(
				 'fillColor' => $tnoBIMQuickscan->colors[ 'fillColor' ][$colorIndex],
				 'strokeColor' => $tnoBIMQuickscan->colors[ 'strokeColor' ][$colorIndex],
				 'pointColor' => $tnoBIMQuickscan->colors[ 'pointColor' ][$colorIndex],
				 'pointStrokeColor' => $tnoBIMQuickscan->colors[ 'pointStrokeColor' ][$colorIndex],
				 'data' => Array(),
				 'labels' => $radarLabels
					);
			foreach( $dataRange as $data ) {
				$radarPlot[ 'data' ][] = $data[ 'data' ];
			}
			$radarChart[ 'datasets' ][] = $radarPlot;
		}

		// Radar chart data is complete now, let's do the bar chart next

		$dataRanges = Array();
		foreach( $reports as $report ) {
			$reportTopics = maybe_unserialize( $report->topics );
			foreach( $topicInformation as $topic ) {
				$entry = Array( 'data' => isset( $reportTopics[$topic->term_id] ) ? $reportTopics[$topic->term_id][0] : 0, 'amount' => 1 );
				if( !isset( $dataRanges[ 'max' ] ) ) {
					$dataRanges[ 'max' ] = Array();
				}
				if( !isset( $dataRanges[ 'max' ][ $topic->term_id ] ) || $dataRanges[ 'max' ][ $topic->term_id ][ 'data' ] == 0 ) {
					$dataRanges[ 'max' ][ $topic->term_id ] = Array( 'data' => isset( $reportTopics[$topic->term_id] ) ? $reportTopics[$topic->term_id][1] : 0 );
				}

				if( isset( $selectedStack[0] ) ) {
					if( !isset( $dataRanges[ 'all' ] ) ) {
						$dataRanges[ 'all' ] = Array();
					}
					if( isset( $dataRanges[ 'all' ][ $topic->term_id ] ) ) {
						$dataRanges[ 'all' ][ $topic->term_id ][ 'data' ] += $entry[ 'data' ];
						$dataRanges[ 'all' ][ $topic->term_id ][ 'amount' ] += $entry[ 'amount' ];
					} else {
						$dataRanges[ 'all' ][ $topic->term_id ] = $entry;
					}
				}
				if( isset( $selectedStack[1] ) ) {
					if( !isset( $dataRanges[ 'own' ] ) ) {
						$dataRanges[ 'own' ] = Array();
					}
					if( $report->post_author == $userId ) {
						if( isset( $dataRanges[ 'own' ][ $topic->term_id ] ) ) {
							$dataRanges[ 'own' ][ $topic->term_id ][ 'data' ] += $entry[ 'data' ];
							$dataRanges[ 'own' ][ $topic->term_id ][ 'amount' ] += $entry[ 'amount' ];
						} else {
							$dataRanges[ 'own' ][ $topic->term_id ] = $entry;
						}
					}
				}
				if( isset( $selectedStack[2] ) && isset( $report->core_business ) && in_array( $report->core_business, $selected ) ) {
					if( !isset( $dataRanges[ 'core_business_' . $report->core_business ] ) ) {
						$dataRanges[ 'core_business_' . $report->core_business ] = Array();
					}
					if( isset( $dataRanges[ 'core_business_' . $report->core_business ][ $topic->term_id ] ) ) {
						$dataRanges[ 'core_business_' . $report->core_business ][ $topic->term_id ][ 'data' ] += $entry[ 'data' ];
						$dataRanges[ 'core_business_' . $report->core_business ][ $topic->term_id ][ 'amount' ] += $entry[ 'amount' ];
					} else {
						$dataRanges[ 'core_business_' . $report->core_business ][ $topic->term_id ] = $entry;
					}
				}
			}
		}

		if( isset( $dataRanges[ 'own' ] ) && count( $dataRanges[ 'own' ] ) == 0 ) {
			foreach( $topicInformation as $topic ) {
				$dataRanges[ 'own' ][ $topic->term_id ] = Array( 'data' => 0, 'amount' => 1 );
			}
		}

		// devide values by the amount of reports in that set
		foreach( $dataRanges as $key => $dataRange ) {
			if( $key != 'max' ) {
				foreach( $topicInformation as $topic ) {
					$dataRanges[$key][ $topic->term_id ][ 'data' ] /= $dataRanges[$key][ $topic->term_id ][ 'amount' ];
				}
			}
		}

		$barLabels = Array();
		foreach( $topicInformation as $topic ) {
			if( $defaultLanguage == '' || 'en' == $language ) {
				$topicParts = explode( ': ', $topic->name );
				$barLabels[] = count( $topicParts ) == 2 ? $topicParts[0] : substr( $topic->name, 0, $labelLength );
			} else {
				$languageId = icl_object_id( $topic->term_id, $tnoBIMQuickscan->options[ 'taxonomy_topic' ], true, $language );
				$topicLanguage = get_term( $languageId, $tnoBIMQuickscan->options[ 'taxonomy_topic' ] );
				$topicParts = explode( ': ', $topicLanguage->name );
				$barLabels[] = count( $topicParts ) == 2 ? $topicParts[0] : substr( $topicLanguage->name, 0, $labelLength );
			}			
		}

		$colorIndex = 0;
		$barChart = Array( 'labels' => $barLabels, 'datasets' => Array() );
		foreach( $dataRanges as $key => $dataRange ) {
			if( $key == 'max' ) {
				$colorIndex = 0;
			} elseif( $key == 'all' ) {
				$colorIndex = 1;
			} elseif( $key == 'own' ) {
				$colorIndex = 2;
			} else {
				foreach( $selected as $selectedKey => $selectedItem ) {
					if( $key == 'core_business_' . $selectedItem ) {
						$colorIndex = 1 + $selectedKey;
						break;
					}
				}
			}

			$bar = Array(
				 'fillColor' => $tnoBIMQuickscan->colors[ 'fillColor' ][$colorIndex],
				 'strokeColor' => $tnoBIMQuickscan->colors[ 'strokeColor' ][$colorIndex],
				 'data' => Array()
					);
			foreach( $dataRange as $data ) {
				$bar[ 'data' ][] = $data[ 'data' ];
			}
			$barChart[ 'datasets' ][] = $bar;
		}
		
		if( isset( $sitepress ) ) {
			add_filter( 'get_term', Array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
		}

		// Print JSON output
		print( json_encode( Array( 'barChart' => $barChart, 'radarChart' => $radarChart ) ) );
	} else {
		print( '{}' );
	}
} else {
	print( '{}' );
}