<?php
/**
 * @file modelreview.pages.inc
 *
 */

/**
 * Status Action 10: Request Review
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_requestreview_form($form, &$form_state) {
  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Request Review'),
  );

  return $form;
}

/**
 * Submit handler for the review request form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_requestreview_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  $modelnid = $form_state['values']['model_nid'];

  // Verify model does not have an open Review case.
  // "Only one per customer, please." (per model, at one time)
  // Otherwise redirect to Status page with error about an open case already present.
  $sql = "SELECT COUNT(*) FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid WHERE mr.model_nid = :nid AND mra.statusid < 60;";
  $result = db_query($sql, array(':nid' => $modelnid));
  $review_count = $result->fetchField();

  if ($review_count > 0) {
    // An open review case already exists
    drupal_set_message(t('Error: This model already has already been reviewed.'));
    drupal_goto('model/' . $modelnid . '/review/status');
  } 
  else {
    // Insert new 'modelreview' record
    $review_id = db_insert('modelreview')
               ->fields(array(
                   'sid' => 0,
                   'model_nid' => $modelnid,
                 ))
               ->execute();
    watchdog('modelreview', 'New review requested. RID: %rid', array('%rid' => $review_id,), WATCHDOG_INFO);

    // get the node id for the latest modelversion
    $query = "SELECT node.nid FROM {node} node "                              
           . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
           . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
           . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
           . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
           . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
           . "ORDER BY field_modelversion_number_value DESC";
    $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

    // Insert new 'modelreview_action' record
    $action_sid = db_insert('modelreview_action')
                ->fields(array(
                    'rid' => $review_id,
                    'modelversion_nid' => $modelversion_nid,
                    'statusid' => 10,
                    'statusdate' => REQUEST_TIME,
                  ))
                ->execute();
    watchdog('modelreview', 'New review action posted. SID: %sid', array('%sid' => $action_sid,), WATCHDOG_INFO);

    // now update 'modelreview' record with action sid
    $num_updated = db_update('modelreview')
                 ->fields(array(
                     'sid' => $action_sid,
                   ))
                 ->condition('rid', $review_id, '=')
                 ->execute();

    // notify all Editors
    if (variable_get('modelreview_emails_enabled', 0) == 1)
      modelreview_notifications($modelnid, 'review_requested');

    drupal_goto('model/' . $modelnid . '/review/status');
  }
}

/**
 * Status Action 20: Assign Reviewer
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_assignreviewer_form($form, &$form_state) {

  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );

  $form['reviewer'] = array(
    '#type' => 'textfield',
    '#title' => t('Assign Reviewer'),
    '#description' => t('Type last name of CoMSES Member for dropdown list'),
    '#autocomplete_path' => 'model/'. arg(1) .'/review/autocomplete',
  );

  $form['actions'] = array(
    '#type' => 'actions'
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}

/**
 * Validate handler to verify entered username.
 *
 * In case the user did not actually use the autocomplete or typed an invalid username, we'll look up
 * the username in the database and verify it corresponds with a CoMSES Member account.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_assignreviewer_form_validate($form, &$form_state) {
  $reviewer = $form_state['values']['reviewer'];

  // lookup user name and count number of matches
  $query = db_select('users', 'u');
  $query->leftJoin('users_roles', 'ur', 'u.uid = ur.uid');
  $query->leftJoin('role_permission', 'rp', 'ur.rid = rp.rid');
  $result = $query->fields('u', array('uid'))
                  ->condition('rp.permission', 'review assigned model')
                  ->condition('u.name', check_plain($reviewer))
                  ->execute();

  $match = $result->rowCount();

  if ($match == 0) {
    form_error($form['reviewer'], t('The "Reviewer" field must contain a valid value, the username of a CoMSES Member. The field will autocomplete suggestions when you start typing the last name of the member you have chosen.', array())); 
  }
}

/**
 * Submit handler for the assign reviewer form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_assignreviewer_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the open 'modelreview' record
  $sql = "SELECT mr.rid, mr.sid, mr.model_nid, modelversion_nid, related, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $review_id = $row->rid;

  // get the node id for the latest modelversion, using Views
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
         . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // lookup the uid for the chosen reviewer username
  $query = "SELECT u.uid FROM {users} u WHERE u.name = :name";
  $reviewer_uid = db_query($query, array(':name' => $form_state['values']['reviewer']))->fetchField();

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $review_id,
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $reviewer_uid,
                  'statusid' => 20,
                  'statusdate' => REQUEST_TIME,
                ))
              ->execute();
  watchdog('modelreview', 'Reviewer invited to review Model Review case. UID: %reviewer', array('%reviewer' => $form_state['values']['reviewer'],), WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $review_id, '=')
               ->execute();

  // notify the Assigned Reviewer if messaging is enabled
  if (variable_get('modelreview_emails_enabled', 0) == 1)
    modelreview_notifications($modelnid, 'invite_reviewer');

  drupal_goto('model/' . $modelnid . '/review/status'); 
}


/**
 * Status Action 23/25: Reviewer Accepts/Declines Case
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_reviewer_acceptdecline_form($form, &$form_state) {
  $acceptdecline = array(
    25 => t('Accept This Review Case'),
    23 => t('Decline This Review Case'),
  );

  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, mra.statusdate, mra.reviewer "
       . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 "
       . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
       . "WHERE mr.model_nid = :nid AND mra.reviewer NOT IN "
       . "(SELECT reviewer FROM modelreview mr2 INNER JOIN modelreview_action mra2 ON mr2.rid = mra2.rid AND mra2.statusid = 23 WHERE mr2.model_nid = :nid) ";
  $result = db_query($sql, array(':nid' => arg(1)));
  $row = $result->fetchObject();

  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );
  $form['rid'] = array(
    '#type' => 'hidden',
    '#value' => $row->rid,
  );
  $form['reviewer'] = array(
    '#type' => 'hidden',
    '#value' => $row->reviewer,
  );
  $form['accept_decline'] = array(
    '#type' => 'select',
    '#title' => t('Will You Review This Model?'),
    '#options' => $acceptdecline,
  );
  $form['other_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('If You Are Unable to Review This Model, Can You Recommend Others Who Might?'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#submit' => array('modelreview_reviewer_acceptdecline_form_submit'),
  );

  return $form;
}

/**
 * Submit handler for the review accept/decline form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_reviewer_acceptdecline_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // get the node id for the latest modelversion
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
         . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $form_state['values']['rid'],
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $form_state['values']['reviewer'],
                  'statusid' => $form_state['values']['accept_decline'],
                  'statusdate' => REQUEST_TIME,
                  'other_notes' => $form_state['values']['other_notes'],
                ))
              ->execute();
  watchdog('modelreview', 'Reviewer %acceptdecline a Model Review case. RID: %rid', array('%acceptdecline' => $form_state['values']['accept_decline'] == 23 ? 'declined ' : 'accepted ','%rid' => $form_state['values']['rid'],), WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $form_state['values']['rid'], '=')
               ->execute();

  // notify Editors whether review case was accepted or declined.
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    // If case was declined, editor needs to assign to a new reviewer.
    if ($form_state['values']['accept_decline'] == 23) {
      modelreview_notifications($modelnid, 'review_declined');
    } 
    else {
      // Else case was accepted, let editor know case is proceeding.
      modelreview_notifications($modelnid, 'review_accepted');
    }
  }

  drupal_goto('model/' . $modelnid . '/review/status'); 
}

/**
 * Status Action 30: Review Completed
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_review_form($form, &$form_state) {
  $standards = array(
    0 => t('Does Not Meet Standards'),
    1 => t('Partially Meets Standards'),
    2 => t('Fully Meets Standards'),
  );

  $recommend = array(
    NULL => '',
    0 => t('Do Not Certify (Deny)'),
    1 => t('Revise (Return to Author)'),
    2 => t('Certify (Accept)'),
  );

  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 25 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
  $result = db_query($sql, array(':nid' => arg(1)));
  $row = $result->fetchObject();

  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );
  $form['rid'] = array(
    '#type' => 'hidden',
    '#value' => $row->rid,
  );
  $form['reviewer'] = array(
    '#type' => 'hidden',
    '#value' => $row->reviewer,
  );
  $form['code_clean'] = array(
    '#type' => 'select',
    '#title' => t('Is the Code Clean/Well-written?'),
    '#options' => $standards,
  );
  $form['code_commented'] = array(
    '#type' => 'select',
    '#title' => t('Is the Code Well-Commented?'),
    '#options' => $standards,
  );
  $form['code_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Code Notes'),
  );
  $form['model_documented'] = array(
    '#type' => 'select',
    '#title' => t('Is the Model Documented Properly?'),
    '#options' => $standards,
  );
  $form['doc_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Documentation Notes'),
  );
  $form['model_runs'] = array(
    '#type' => 'select',
    '#title' => t('Does the Model Run with the Provided Instructions?'),
    '#options' => $standards,
  );
  $form['run_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Comments on Running the Model'),
  );
  $form['other_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Other Notes About Model'),
  );
  $form['recommendation'] = array(
    '#type' => 'select',
    '#title' => t('Your Recommendation'),
    '#options' => $recommend,
  );
  $form['editor_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Comments to the Editor (Private)'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#submit' => array('modelreview_review_form_submit'),
  );

  return $form;
}

/**
 * Submit handler for the review form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_review_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // get the node id for the latest modelversion
    $query = "SELECT node.nid FROM {node} node "                              
           . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
           . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
           . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
           . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
           . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
           . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $form_state['values']['rid'],
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $form_state['values']['reviewer'],
                  'statusid' => 30,
                  'statusdate' => REQUEST_TIME,
                  'code_clean' => $form_state['values']['code_clean'],
                  'code_commented' => $form_state['values']['code_commented'],
                  'model_documented' => $form_state['values']['model_documented'],
                  'model_runs' => $form_state['values']['model_runs'],
                  'code_notes' => $form_state['values']['code_notes'],
                  'doc_notes' => $form_state['values']['doc_notes'],
                  'other_notes' => $form_state['values']['other_notes'],
                  'editor_notes' => $form_state['values']['editor_notes'],
                  'recommendation' => $form_state['values']['recommendation'],
                ))
              ->execute();
  watchdog('modelreview', 'Review completed for Model Review case. RID: %rid', array('%rid' => $form_state['values']['rid'],), WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $form_state['values']['rid'], '=')
               ->execute();

  // notify all Editors
  if (variable_get('modelreview_emails_enabled', 0) == 1)
    modelreview_notifications($modelnid, 'review_completed');

  drupal_goto('model/' . $modelnid . '/review/status'); 
}


/**
 *
 * Status Actions: Editor processes Review, Revise, Certify, or Deny
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_editor_process_form($form, &$form_state) {
  $decision = array(
    NULL => '',
    0 => t('Do Not Certify (Deny)'),
    1 => t('Revise (Return to Author)'),
    2 => t('Certify (Accept)'),
  );

  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );

  $form['decision'] = array(
    '#type' => 'select',
    '#title' => t('Take Action'),
    '#options' => $decision,
  );
  $form['editor_notes'] = array(
    '#type' => 'textarea',
    '#title' => t('Instructions to the Model Author'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}
  
/**
 * Submit handler for the editor processing form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_editor_process_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the 'modelreview' record
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, mra.statusdate, mra.reviewer "
       . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 "
       . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
       . "WHERE mr.model_nid = :nid AND mra.reviewer NOT IN "
       . "(SELECT reviewer FROM modelreview mr2 INNER JOIN modelreview_action mra2 ON mr2.rid = mra2.rid AND mra2.statusid = 23 WHERE mr2.model_nid = :nid) ";
  $result = db_query($sql, array(':nid' => arg(1)));
  $row = $result->fetchObject();
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
    $query = "SELECT node.nid FROM {node} node "                              
           . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
           . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
           . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
           . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
           . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
           . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // we need to lookup the review actions that have been submitted for the editor to review
  // they will be actions that have not been associated ('related') to any editor actions
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate "
       . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
       . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid AND related = NULL";
  $reviews = db_query($sql, array(':nid' => $modelnid));
  // after we add our editor action, we will need to update these review actions to point (via 'related') to our new editor action

  // determine the status id that will need to be recorded here.
  switch ($form_state['values']['decision']) {
    case 0:
      $step = 70;
      break;

    case 1:
      $step = 40;
      break;

    case 2:
      $step = 60;
      break;
  }

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $review_id,
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $reviewer,
                  'statusid' => $step,
                  'statusdate' => REQUEST_TIME,
                  'editor_notes' => $form_state['values']['editor_notes'],
                ))
              ->execute();
  watchdog('modelreview', 'An editor has processed a Model Review Case.', NULL, WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $review_id, '=')
               ->execute();

  // and update any related review actions with the sid of the new editor action we just created
  $sql = "UPDATE {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
       . "SET related = :action WHERE mr.model_nid = :nid AND mra.related IS NULL";
  db_query($sql, array(':action' => $action_sid, ':nid' => $modelnid));

  // Lookup the Model author
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $title = $row->title;
  $author = $row->uid;

  // notify the Author about this action
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    switch ($form_state['values']['decision']) {
      case 0:
        modelreview_notifications($modelnid, 'cert_denied');
        break;

      case 1:
        modelreview_notifications($modelnid, 'revision_needed');
        break;

      case 2:
        modelreview_notifications($modelnid, 'cert_accepted');
        break;
    }
  }

  drupal_goto('model/' . $modelnid . '/review/status'); 
}


/**
 * Status Action 50: Request Re-Review
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function modelreview_requestrereview_form($form, &$form_state) {
  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Request Re-Review'),
  );

  return $form;
}

/**
 * Submit handler for the re-review request form.
 *
 * @param $form
 * @param $form_state
 */
function modelreview_requestrereview_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the open 'modelreview' record
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, mra.statusdate, mra.reviewer "
       . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 "
       . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
       . "WHERE mr.model_nid = :nid AND mra.reviewer NOT IN "
       . "(SELECT reviewer FROM modelreview mr2 INNER JOIN modelreview_action mra2 ON mr2.rid = mra2.rid AND mra2.statusid = 23 WHERE mr2.model_nid = :nid) ";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
    $query = "SELECT node.nid FROM {node} node "                              
           . "LEFT JOIN {field_data_field_modelversion_model} modelversion_modelnid "
           . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
           . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
           . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
           . "WHERE (modelversion_modelnid.field_modelversion_model_nid = :nid) AND (node.type IN ('modelversion')) "
           . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $review_id,
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $reviewer,
                  'statusid' => 50,
                  'statusdate' => REQUEST_TIME,
                ))
              ->execute();
  watchdog('modelreview', 'Model author has requested a re-revew of their Model Review case.', NULL,  WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $review_id, '=')
               ->execute();

  // notify the Assigned Reviewer
  if (variable_get('modelreview_emails_enabled', 0) == 1)
    modelreview_notifications($modelnid, 'reviewer_rereview');

  // notify managing editor of the Re-review Request
  if (variable_get('modelreview_emails_enabled', 0) == 1)
    modelreview_notifications($modelnid, 'editor_rereview');

  // transfer user to Review form
  drupal_goto('model/' . $modelnid . '/review/status');

}
