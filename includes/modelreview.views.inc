<?php
/**
 * @file modelreview.views.inc
 * This file is used to tell the Views module about the modelreview tables.
 *
 * Database definitions:
 * @code
 * -- Table structure for table `modelreview`
 * --
 *
 * CREATE TABLE IF NOT EXISTS `modelreview` (
 *   `review_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `model_nid` int(10) unsigned NOT NULL DEFAULT '0',
 *   PRIMARY KEY (`review_id`),
 *   KEY `model_nid` (`model_nid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 *
 * -- Table structure for table `modelreview_action`
 * --
 *
 * CREATE TABLE IF NOT EXISTS `modelreview_action` (
 *   `review_id` int(10) unsigned NOT NULL DEFAULT '0',
 *   `review_sid` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `modelversion_nid` int(10) unsigned NOT NULL DEFAULT '0',
 *   `status` int(11) NOT NULL DEFAULT '0',
 *   `statusdate` int(11) NOT NULL DEFAULT '0',
 *   `reviewer` int(11) NOT NULL DEFAULT '0',
 *   `code_clean` int(11) NOT NULL DEFAULT '0',
 *   `code_commented` int(11) NOT NULL DEFAULT '0',
 *   `model_documented` int(11) NOT NULL DEFAULT '0',
 *   `model_runs` int(11) NOT NULL DEFAULT '0',
 *   `code_notes` text,
 *   `doc_notes` text,
 *   `other_notes` text,
 *   PRIMARY KEY (`review_sid`),
 *   UNIQUE KEY `id_sid` (`review_id`,`review_sid`),
 *   KEY `modelversion_nid` (`modelversion_nid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 * @endcode
 */

/**
 * Implementation of hook_views_handlers() to register all of the basic handlers
 * views uses.
 */
#function modelreview_views_handlers() {
#  return array(
#    'info' => array(
#      'path' => drupal_get_path('module', 'modelreview'). '/includes',
#    ),
#    'handlers' =>; array(
#      // The name of my handler
#      'modelreview_handler_field_boolean' => array(
#        // The name of the handler we are extending.
#        'parent' => 'views_handler_field_boolean',
#      ),
#    ),
#  );
#}


function modelreview_views_data()  {
  // Basic table information.

  // ----------------------------------------------------------------
  // modelreview tables
  //  New group within Views called 'Model Reviews'
  //  The group will appear in the UI in the dropdown that allows you
  //  to narrow down which fields and filters are available.

  $data = array();
  $data['modelreview']['table']['group']  = t('Model Review');
  $data['modelreview_action']['table']['group']  = t('Model Review');
  $data['modelreview_actiondesc']['table']['group']  = t('Model Review');
  $data['modelreview_compliance']['table']['group']  = t('Model Review');
  $data['modelreview_recommend']['table']['group']  = t('Model Review');

  // Let Views know that our tables join to the 'node'
  // base table. This means it will be available when listing
  // nodes and automatically make its fields appear.
  //
  $data['modelreview']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'model_nid',
    ),
  );

  $data['modelreview_action']['table']['join'] = array(
    'node' => array(
      'left_table' => 'modelreview',
      'left_field' => 'sid',
      'field' => 'sid',
    ),
  );

  $data['modelreview_actiondesc']['table']['join'] = array(
    'node' => array(
      'left_table' => 'modelreview_action',
      'left_field' => 'statusid',
      'field' => 'statusid',
    ),
  );

  $data['modelreview_recommend']['table']['join'] = array(
    'node' => array(
      'left_table' => 'modelreview_action',
      'left_field' => 'recommendation',
      'field' => 'id',
    ),
  );

  /* * * * * * * * * * * * * * * *
   * Describe modelreview fields *
   * * * * * * * * * * * * * * * */
  // Review ID - Key
  $data['modelreview']['rid'] = array(
    'title' => t('Review ID'),
    'help' => t('Unique identifier for an individual model review.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Model Node ID    
  $data['modelreview']['model_nid'] = array(
    'title' => t('Model NID'),
    'help' => t('Node ID of Model being reviewed.'),
    'relationship' => array(
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Model Review'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  /* * * * * * * * * * * * * * * * * * * *
   * Describe modelreview_action fields  *
   * * * * * * * * * * * * * * * * * * * */

  // Model Version Node ID    
  $data['modelreview_action']['modelversion_nid'] = array(
    'title' => t('Model Version NID'),
    'help' => t('Node ID for the Model Version associated with this review action.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Model Status Code    
  $data['modelreview_action']['statusid'] = array(
    'title' => t('Status Code'),
    'help' => t('Current review status code.'),

    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Status Date
  $data['modelreview_action']['statusdate'] = array(
    'title' => t('Status Date'),
    'help' => t('Date-time the review action completed.'),

    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
  );

  // Reviewer
  $data['modelreview_action']['reviewer'] = array(
    'title' => t('Reviewer'),
    'help' => t('User that reviewed the model.'),

    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  /* * * * * * * * * * * * * * * * * * * *
   * Describe modelreview_actiondesc fields  *
   * * * * * * * * * * * * * * * * * * * */

  // Model Review Status    
  $data['modelreview_actiondesc']['status'] = array(
    'title' => t('Status Description'),
    'help' => t('Status Description.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
     'filter' => array(
      'handler' => 'views_handler_filter_string',
     ),
     'sort' => array(
      'handler' => 'views_handler_sort',
     ),
   );


  return $data;
}
