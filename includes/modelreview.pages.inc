<?php
/**
 * @file modelreview.pages.inc
 *
 */

/**
 * Status Action 1: Request Review
 */
function modelreview_requestreview_form() {
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
           . "LEFT JOIN {field_data_field_modelversion_modelnid} modelversion_modelnid "
           . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
           . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
           . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
           . "WHERE (modelversion_modelnid.field_modelversion_modelnid_value = :nid) AND (node.type IN ('modelversion')) "
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
    // lookup model node
    $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
    $result = db_query($sql, array(':nid' => $modelnid));
    $row = $result->fetchObject();
    $title = $row->title;

    $subject = "Model Review has been requested";
    $body = 'The author of the model, "' . $title . '", has requested a Model Review. A Reviewer needs to be assigned to this case, in order for it to proceed. You may access this case by visiting: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");

    $query = 'SELECT u.uid, u.mail FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid '
           . 'LEFT JOIN {role_permission} rp ON ur.rid = rp.rid '
           . 'WHERE rp.permission = :perm';
    $result = db_query($query, array(':perm' => 'manage review'));

    foreach ($result as $index => $record) 
      if (variable_get('modelreview_emails_enabled', 0) == 1) {
        modelreview_send_mail($record->mail, $subject, $body);
      }

    drupal_goto('model/' . $modelnid . '/review/status');
  }
}

/**
 * Status Action 2: Assign Reviewer
 */
function modelreview_assignreviewer_form() {
  $options[NULL] = '';
  $query = "SELECT u.uid, reviewer_firstname.field_profile2_firstname_value as firstname, "
         . "reviewer_lastname.field_profile2_lastname_value as lastname FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid "
         . "LEFT JOIN {profile} p ON u.uid = p.uid AND p.type = 'main' "
         . "LEFT JOIN {role_permission} rp ON ur.rid = rp.rid "
         . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON p.pid = reviewer_firstname.entity_id "
         . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON p.pid = reviewer_lastname.entity_id "
         . "WHERE rp.permission = :perm";
  $result = db_query($query, array(':perm' => 'review model'));

  foreach ($result as $index => $record) {
    // don't include user 1 (system maintenance account)
    if ($record->uid > 1)
      $options[$record->uid] = substr($record->firstname, 0, 1) . ' ' . $record->lastname;
  }

  $form['model_nid'] = array(
    '#type' => 'hidden',
    '#value' => arg(1),
  );

  $form['reviewer'] = array(
    '#type' => 'select',
    '#title' => t('Assign Reviewer'),
    '#options' => $options,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}

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
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_modelnid} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_modelnid_value = :nid) AND (node.type IN ('modelversion')) "
         . "ORDER BY field_modelversion_number_value DESC";
  $modelversion_nid = db_query_range($query, 0, 1, array(':nid' => $modelnid))->fetchField();

  // Insert new 'modelreview_action' record
  $action_sid = db_insert('modelreview_action')
              ->fields(array(
                  'rid' => $review_id,
                  'modelversion_nid' => $modelversion_nid,
                  'reviewer' => $form_state['values']['reviewer'],
                  'statusid' => 20,
                  'statusdate' => REQUEST_TIME,
                ))
              ->execute();
  watchdog('modelreview', 'Reviewer assigned to Model Review case. UID: %reviewer', array('%reviewer' => $form_state['values']['reviewer'],), WATCHDOG_INFO);

  // now update 'modelreview' record with action sid
  $num_updated = db_update('modelreview')
               ->fields(array(
                   'sid' => $action_sid,
                 ))
               ->condition('rid', $review_id, '=')
               ->execute();

  // notify the Assigned Reviewer
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $title = $row->title;

  $subject = "You have been assigned a new Model Review case";
  $body = 'You have been assigned a new Model Review case for the model, "' . $title . '". You may access this review case by visiting: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");

  $query = 'SELECT u.mail FROM {users} u WHERE u.uid = :uid';
  $reviewer_uid = db_query($query, array(':uid' => $form_state['values']['reviewer']))->fetchField();
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    modelreview_send_mail($reviewer_uid, $subject, $body);
  }

  drupal_goto('model/' . $modelnid . '/review/status'); 
}

/**
 * Status Action 3: Review Completed
 */
function modelreview_review_form() {
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

  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
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

function modelreview_review_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // get the node id for the latest modelversion
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_modelnid} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_modelnid_value = :nid) AND (node.type IN ('modelversion')) "
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
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $title = $row->title;

  $subject = "Model Review has been completed";
  $body = 'The model review case for the model, "' . $title . '", has been completed by the assigned Reviewer. The reviewer\'s notes and recommendation need to be processed by an Editor, and the case processed as needed. You may access this review case by browsing: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");

  $query = 'SELECT u.uid, u.mail FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid '
         . 'LEFT JOIN {role_permission} rp ON ur.rid = rp.rid '
         . 'WHERE rp.permission = :perm';
  $result = db_query($query, array(':perm' => 'manage review'));

  foreach ($result as $index => $record) 
    if (variable_get('modelreview_emails_enabled', 0) == 1) {
      modelreview_send_mail($record->mail, $subject, $body);
    }

  drupal_goto('model/' . $modelnid . '/review/status'); 
}


/**
 *
 * Status Actions: Editor processes Review, Revise, Certify, or Deny
 *
 */
function modelreview_editor_process_form() {
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

function modelreview_editor_process_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the 'modelreview' record
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
  $result = db_query($sql, array(':nid' => arg(1)));
  $row = $result->fetchObject();
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_modelnid} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_modelnid_value = :nid) AND (node.type IN ('modelversion')) "
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
  switch ($form_state['values']['decision']) {
    case 0:
      $subject = "Model Review has been completed, Certification Denied";
      $body = 'The model review case for your model, "' . $title . '", has been completed by a CoMSES Reviewer. Unfortunately, After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and your model cannot be certified. For more information on this please visit the Model Review Status Page at: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");
      break;

    case 1:
      $subject = "Model Review has been completed, Revisions Requested";
      $body = 'The model review case for your model, "' . $title . '", has been completed by the assigned Reviewer. Before your model can be certified, the CoMSES Reviewer has requested some revisions be made to your model in order to fully meet Modeling Best Practes. To review the notes and recommendation for your case, please visit the Model Review Status Page at: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");
      break;

    case 2:
      $subject = "Model Review has been completed, Certified";
      $body = 'The model review case for your model, "' . $title . '", has been completed by a CoMSES Reviewer. After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and is now Certified. You may access this review case by visiting the Model Review Status Page at: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");
      break;
  }

  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    modelreview_send_mail($author, $subject, $body);
  }

  drupal_goto('model/' . $modelnid . '/review/status'); 

}


/**
 * Status Action 5: Request Re-Review
 */
function modelreview_requestrereview_form() {
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

function modelreview_requestrereview_form_submit($form, &$form_state) {
  global $base_url;
  global $base_path;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the open 'modelreview' record
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $query = "SELECT node.nid FROM {node} node "                              
         . "LEFT JOIN {field_data_field_modelversion_modelnid} modelversion_modelnid "
         . "ON node.nid = modelversion_modelnid.entity_id AND (modelversion_modelnid.entity_type = 'node'AND modelversion_modelnid.deleted = '0') "
         . "LEFT JOIN {field_data_field_modelversion_number} modelversion_number "
         . "ON node.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         . "WHERE (modelversion_modelnid.field_modelversion_modelnid_value = :nid) AND (node.type IN ('modelversion')) "
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
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
  $result = db_query($sql, array(':nid' => $modelnid));
  $row = $result->fetchObject();
  $title = $row->title;

  $subject = "Your Model Review Case requires a Re-Review";
  $body = 'A model you have reviewed., "'. $title .'," has been revised by the author, and the author has requested a re-review of their case to determine if the model now meets the requirements for certification. You may access this review case by visiting: '. l($base_url ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    modelreview_send_mail($reviewer, $subject, $body);
  }

  // notify managing editor of the Re-review Request
  $subject = "Model Re-Review has been requested";
  $body = 'The author of the model, "' . $title . '", has completed requested revisions and has requested a Re-Review of their model. The assigned Reviewer has been notified that they need to complete the Re-Review. You may access this case by visiting: ' . l($base_url . "model/" . $modelnid . "/review/status", "model/" . $modelnid . "/review/status");

  $query = 'SELECT u.uid, u.mail FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid '
         . 'LEFT JOIN {role_permission} rp ON ur.rid = rp.rid '
         . 'WHERE rp.permission = :perm';
  $result = db_query($query, array(':perm' => 'manage review'));

  foreach ($result as $index => $record) 
    if (variable_get('modelreview_emails_enabled', 0) == 1) {
      modelreview_send_mail($record->mail, $subject, $body);
    }

  // transfer user to Review form
  drupal_goto('model/' . $modelnid . '/review/status');

}
