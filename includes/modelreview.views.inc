<?php
/**
 * @file modelreview.views.inc
 * This file is used to tell the Views module about the modelreview tables.
 */

function modelreview_views_data()  {
  // Basic table information.

  // ----------------------------------------------------------------
  // modelreview tables
  //  New group within Views called 'Model Reviews'
  //  The group will appear in the UI in the dropdown that allows you
  //  to narrow down which fields and filters are available.

  $data = array();

  $data['modelreview'] = array(
    'table' => array(
      'base' => array(
        'field' => 'rid',
        'title' => 'Model Review',
        'help' => 'Model Review base table',
      ),
      'group' => 'Model Review',
    ),
    'rid' => array(
      'title' => 'Review Id',
      'help' => 'Main id for each model review case',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'sid' => array(
      'title' => 'Current Step ID',
      'help' => 'Current step in the review case',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'model_nid' => array(
      'title' => 'Model NID',
      'help' => 'Model NID for this review case',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
  );

  $data['modelreview_action'] = array(
    'table' => array(
      'join' => array(
        'modelreview' => array(
          'left_field' => 'sid',
          'field' => 'sid',
          'title' => 'Model Review Action',
          'help' => 'Individual actions in review cases',
        ),
      ),
      'group' => 'Model Review Action',
    ),
    'rid' => array(
      'title' => 'Review ID',
      'help' => 'Review ID - references case',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'sid' => array(
      'title' => 'Step ID',
      'help' => 'Unique ID for review action',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'related' => array(
      'title' => 'Related',
      'help' => 'Relates a completed review with its editor processing action.',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'modelversion_nid' => array(
      'title' => 'Model Version NID',
      'help' => 'References current model version at time of review action',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'statusid' => array(
      'title' => 'Status ID',
      'help' => 'Type of action or status being set on review case.',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'statusdate' => array(
      'title' => 'Status Date',
      'help' => 'Date of review action',
      'field' => array(
        'handler' => 'views_handler_field_date',
        'click sortable' => FALSE,
      ),
    ),
    'reviewer' => array(
      'title' => 'Reviewer',
      'help' => 'User id of assigned reviewer',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'code_clean' => array(
      'title' => 'Code Clean',
      'help' => 'Whether the model code is clean',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'code_commented' => array(
      'title' => 'Code Commented',
      'help' => 'Whether the model code is well-commented',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'model_documented' => array(
      'title' => 'Model Documented',
      'help' => 'Whether the model is well-documented',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'model_runs' => array(
      'title' => 'Model Runs',
      'help' => 'Does the model run correctly',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'code_notes' => array(
      'title' => 'Code Notes',
      'help' => 'Notes on the code',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
    'doc_notes' => array(
      'title' => 'Doc Notes',
      'help' => 'Notes on the documentation',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
    'editor_notes' => array(
      'title' => 'Editor Notes',
      'help' => 'Notes to/from the Editor',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
    'other_notes' => array(
      'title' => 'Other Notes',
      'help' => 'Other notes',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
    'recommendation' => array(
      'title' => 'Recommendation',
      'help' => 'Certification recommendation',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
  );

  $data['modelreview_actiondesc'] = array(
    'table' => array(
      'join' => array(
        'modelreview_action' => array(
          'left_field' => 'statusid',
          'field' => 'statusid',
          'title' => 'Model Review Action Description',
          'help' => 'Action Description',
        ),
      ),
      'group' => 'Model Review Action Description',
    ),
    'statusid' => array(
      'title' => 'Status ID',
      'help' => 'Reference ID for REview Actions',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'status' => array(
      'title' => 'Status',
      'help' => 'Action description',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
  );

  $data['modelreview_compliance'] = array(
    'table' => array(
      'join' => array(
        'modelreview_action' => array(
          'left_field' => 'code_clean',
          'field' => 'cid',
          'title' => 'Model Review Compliance',
          'help' => 'Review criteria compliance status',
        ),
      ),
      'group' => 'Model Review Compliance',
    ),
    'cid' => array(
      'title' => 'CID',
      'help' => 'Compliance code',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'compliance' => array(
      'title' => 'Compliance',
      'help' => 'Compliance description',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
  );

  $data['modelreview_recommend'] = array(
    'table' => array(
      'join' => array(
        'modelreview_action' => array(
          'left_field' => 'recommendation',
          'field' => 'id',
          'title' => 'Model Review Recommendation',
          'help' => 'Certification recommendation codes',
        ),
      ),
      'group' => 'Model Review Recommendation',
    ),
    'id' => array(
      'title' => 'ID',
      'help' => 'Recommendation code',
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => FALSE,
      ),
    ),
    'recommendation' => array(
      'title' => 'Recommendation description',
      'help' => 'Recommendation description text',
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
    ),
  );



  return $data;
}

