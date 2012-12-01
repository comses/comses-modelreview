<?php

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
  $sql = "SELECT COUNT(*) FROM modelreview mr INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid WHERE mr.model_nid = %d AND mra.statusid < 60;";
  $result = db_query($sql, $modelnid);
  $review_count = db_result($result);

  if ($review_count > 0) {
    // An open review case already exists
    drupal_set_message("Error: This model already has already been reviewed.");
    drupal_goto('model/'. $modelnid .'/review/status');
  } else {
    // Insert new 'modelreview' record
    $review_record = array(
      'rid' => NULL,
      'sid' => 0,
      'model_nid' => $modelnid,
    );
    drupal_write_record('modelreview', $review_record);

    // now lookup the rid for the new 'modelreview' record
    $sql = "SELECT mr.rid FROM modelreview mr WHERE mr.model_nid = %d ORDER BY mr.rid DESC LIMIT 1;";
    $result = db_query($sql, $modelnid);
    $review_id = db_result($result);

    // get the node id for the latest modelversion, using Views
    $sql = "SELECT node.nid FROM node node LEFT JOIN content_type_modelversion ctmv ON node.vid = ctmv.vid "
          ."LEFT JOIN content_type_modelversion ctmvn ON node.vid = ctmvn.vid "
          ."WHERE node.type = 'modelversion' AND node.status = 1 AND ctmv.field_modelversion_modelnid_value = %d "
          ."ORDER BY ctmvn.field_modelversion_number_value DESC LIMIT 1";
    $result = db_query($sql, $modelnid);
    $modelversion_nid = db_result($result);

    // Insert new 'modelreview_action' record
    $action_record = array(
      'rid' => $review_id,
      'modelversion_nid' => $modelversion_nid,
      'statusid' => 10,
      'statusdate' => time(),
    );
    drupal_write_record('modelreview_action', $action_record);

    // now update 'modelreview' record with action sid
    $sql = "SELECT mra.sid FROM {modelreview_action} mra WHERE mra.rid = %d ORDER BY mra.statusdate DESC LIMIT 1;";
    $result = db_query($sql, $review_id);
    $review_sid = db_result($result);

    $review_record = array(
      'rid' => $review_id,
      'sid' => $review_sid,
    );
    drupal_write_record('modelreview', $review_record, 'rid');

    // notify all Editors
    // lookup model node
    $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
    $result = db_query($sql, $modelnid);
    $row = db_fetch_object($result);
    $title = $row->title;

    $subject = "Model Review has been requested";
    $body = 'The author of the model, "'. $title .'", has requested a Model Review. A Reviewer needs to be assigned to this case, in order for it to proceed. You may access this case by visiting: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");

    $sql = "SELECT u.uid, u.name FROM users u INNER JOIN users_roles ur ON u.uid = ur.uid "
          ."INNER JOIN permission p ON ur.rid = p.rid WHERE p.perm like '%%%s%%'";
    $results = db_query($sql, 'manage review');
    while ($row = db_fetch_object($results)) {
      if (variable_get('modelreview_emails_enabled', 0) == 1) {
        notifications_lite_send($row->uid, $subject, $body);
      }
    }

    drupal_goto('model/'. $modelnid .'/review/status');
  }
}

/**
 * Status Action 2: Assign Reviewer
 */
function modelreview_assignreviewer_form() {
  $options[NULL] = '';
  $sql = "SELECT u.uid, u.name, ctp.field_fullname_value AS fullname ". 
         "FROM users u INNER JOIN users_roles ur ON u.uid = ur.uid ".
         "INNER JOIN permission p ON ur.rid = p.rid ".
         "LEFT JOIN node nu ON u.uid = nu.uid AND nu.type = 'profile' ".
         "LEFT JOIN content_type_profile ctp ON nu.vid = ctp.vid ".
         "WHERE p.perm like '%%%s%%'";
  $results = db_query($sql, 'review model');
  while ($row = db_fetch_object($results)) {
    $options[$row->uid] = $row->fullname;
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
  $sql = "SELECT mr.rid, mr.sid, mr.model_nid, modelversion_nid, related, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $sql = "SELECT node.nid FROM node node LEFT JOIN content_type_modelversion ctmv ON node.vid = ctmv.vid "
        ."LEFT JOIN content_type_modelversion ctmvn ON node.vid = ctmvn.vid "
        ."WHERE node.type = 'modelversion' AND node.status = 1 AND ctmv.field_modelversion_modelnid_value = %d "
        ."ORDER BY ctmvn.field_modelversion_number_value DESC LIMIT 1";
  $result = db_query($sql, $modelnid);
  $modelversion_nid = db_result($result);

  // Insert new 'modelreview_action' record
  $action_record = array(
    'rid' => $review_id,
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $form_state['values']['reviewer'],
    'statusid' => 20,
    'statusdate' => time(),
  );
  drupal_write_record('modelreview_action', $action_record);

  // now update 'modelreview' record with action sid
  $sql = "SELECT mra.sid FROM {modelreview_action} mra WHERE mra.rid = %d ORDER BY mra.statusdate DESC LIMIT 1;";
  $result = db_query($sql, $review_id);
  $review_sid = db_result($result);

  $review_record = array(
    'rid' => $review_id,
    'sid' => $review_sid,
  );
  drupal_write_record('modelreview', $review_record, 'rid');

  // notify the Assigned Reviewer
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $title = $row->title;

  $subject = "You have been assigned a new Model Review case";
  $body = 'You have been assigned a new Model Review case for the model, "'. $title .'". You may access this review case by visiting: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    notifications_lite_send($form_state['values']['reviewer'], $subject, $body);
  }

  drupal_goto('model/'. $modelnid .'/review/status'); 
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

  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d";
  $result = db_query($sql, arg(1));
  $row = db_fetch_object($result);

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

  // get the node id for the latest modelversion, using Views
  $sql = "SELECT node.nid FROM node node LEFT JOIN content_type_modelversion ctmv ON node.vid = ctmv.vid "
        ."LEFT JOIN content_type_modelversion ctmvn ON node.vid = ctmvn.vid "
        ."WHERE node.type = 'modelversion' AND node.status = 1 AND ctmv.field_modelversion_modelnid_value = %d "
        ."ORDER BY ctmvn.field_modelversion_number_value DESC LIMIT 1";
  $result = db_query($sql, $modelnid);
  $modelversion_nid = db_result($result);

  // Insert new 'modelreview_action' record
  $action_record = array(
    'rid' => $form_state['values']['rid'],
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $form_state['values']['reviewer'],
    'statusid' => 30,
    'statusdate' => time(),
    'code_clean' => $form_state['values']['code_clean'],
    'code_commented' => $form_state['values']['code_commented'],
    'model_documented' => $form_state['values']['model_documented'],
    'model_runs' => $form_state['values']['model_runs'],
    'code_notes' => $form_state['values']['code_notes'],
    'doc_notes' => $form_state['values']['doc_notes'],
    'other_notes' => $form_state['values']['other_notes'],
    'editor_notes' => $form_state['values']['editor_notes'],
    'recommendation' => $form_state['values']['recommendation'],
  );
  drupal_write_record('modelreview_action', $action_record);

  // now update 'modelreview' record with action sid
  $sql = "SELECT mra.sid FROM {modelreview_action} mra WHERE mra.rid = %d ORDER BY mra.statusdate DESC LIMIT 1;";
  $result = db_query($sql, $form_state['values']['rid']);
  $review_sid = db_result($result);

  $review_record = array(
    'rid' => $form_state['values']['rid'],
    'sid' => $review_sid,
  );
  drupal_write_record('modelreview', $review_record, 'rid');

  // notify all Editors
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $title = $row->title;

  $subject = "Model Review has been completed";
  $body = 'The model review case for the model, "'. $title .'", has been completed by the assigned Reviewer. The reviewer\'s notes and recommendation need to be processed by an Editor, and the case processed as needed. You may access this review case by browsing: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");

  $sql = "SELECT u.uid, u.name FROM users u INNER JOIN users_roles ur ON u.uid = ur.uid "
        ."INNER JOIN permission p ON ur.rid = p.rid WHERE p.perm like '%%%s%%'";
  $results = db_query($sql, 'manage review');
  while ($row = db_fetch_object($results)) {
    if (variable_get('modelreview_emails_enabled', 0) == 1) {
      notifications_lite_send($row->uid, $subject, $body);
    }
  }

  drupal_goto('model/'. $modelnid .'/review/status'); 
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
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d";
  $result = db_query($sql, arg(1));
  $row = db_fetch_object($result);
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $sql = "SELECT node.nid FROM node node LEFT JOIN content_type_modelversion ctmv ON node.vid = ctmv.vid "
        ."LEFT JOIN content_type_modelversion ctmvn ON node.vid = ctmvn.vid "
        ."WHERE node.type = 'modelversion' AND node.status = 1 AND ctmv.field_modelversion_modelnid_value = %d "
        ."ORDER BY ctmvn.field_modelversion_number_value DESC LIMIT 1";
  $result = db_query($sql, $modelnid);
  $modelversion_nid = db_result($result);

  // we need to lookup the review actions that have been submitted for the editor to review
  // they will be actions that have not been associated ('related') to any editor actions
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate "
        ."FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
        ."INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d AND related = NULL";
  $reviews = db_query($sql, $review['model_nid']);
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
  $action_record = array(
    'rid' => $review_id,
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $reviewer,
    'statusid' => $step,
    'statusdate' => time(),
    'editor_notes' => $form_state['values']['editor_notes'],
  );
  drupal_write_record('modelreview_action', $action_record);

  // now update 'modelreview' record with action sid
  $sql = "SELECT mra.sid FROM {modelreview_action} mra WHERE mra.rid = %d ORDER BY mra.statusdate DESC LIMIT 1;";
  $result = db_query($sql, $review_id);
  $action_sid = db_result($result);

  $review_record = array(
    'rid' => $review_id,
    'sid' => $action_sid,
  );
  drupal_write_record('modelreview', $review_record, 'rid');

  // and update any related review actions with the sid of the new editor action we just created
  $sql = "UPDATE {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
        ."SET related = %d WHERE mr.model_nid = %d AND mra.related IS NULL";
  db_query($sql, $action_sid, $modelnid);

  // Lookup the Model author
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $title = $row->title;
  $author = $row->uid;

  // notify the Author about this action
  switch ($form_state['values']['decision']) {
    case 0:
      $subject = "Model Review has been completed, Certification Denied";
      $body = 'The model review case for your model, "'. $title .'", has been completed by a CoMSES Reviewer. Unfortunately, After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and your model cannot be certified. For more information on this please visit the Model Review Status Page at: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;

    case 1:
      $subject = "Model Review has been completed, Revisions Requested";
      $body = 'The model review case for your model, "'. $title .'", has been completed by the assigned Reviewer. Before your model can be certified, the CoMSES Reviewer has requested some revisions be made to your model in order to fully meet Modeling Best Practes. To review the notes and recommendation for your case, please visit the Model Review Status Page at: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;

    case 2:
      $subject = "Model Review has been completed, Certified";
      $body = 'The model review case for your model, "'. $title .'", has been completed by a CoMSES Reviewer. After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and is now Certified. You may access this review case by visiting the Model Review Status Page at: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;
  }

  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    notifications_lite_send($author, $subject, $body);
  }

  drupal_goto('model/'. $modelnid .'/review/status'); 

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
  $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 20 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $sql = "SELECT node.nid FROM node node LEFT JOIN content_type_modelversion ctmv ON node.vid = ctmv.vid "
        ."LEFT JOIN content_type_modelversion ctmvn ON node.vid = ctmvn.vid "
        ."WHERE node.type = 'modelversion' AND node.status = 1 AND ctmv.field_modelversion_modelnid_value = %d "
        ."ORDER BY ctmvn.field_modelversion_number_value DESC LIMIT 1";
  $result = db_query($sql, $modelnid);
  $modelversion_nid = db_result($result);

  // Insert new 'modelreview_action' record
  $action_record = array(
    'rid' => $review_id,
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $reviewer,
    'statusid' => 50,
    'statusdate' => time(),
  );
  drupal_write_record('modelreview_action', $action_record);

  // now update 'modelreview' record with action sid
  $sql = "SELECT mra.sid FROM {modelreview_action} mra WHERE mra.rid = %d ORDER BY mra.statusdate DESC LIMIT 1;";
  $result = db_query($sql, $review_id);
  $review_sid = db_result($result);

  $review_record = array(
    'rid' => $review_id,
    'sid' => $review_sid,
  );
  drupal_write_record('modelreview', $review_record, 'rid');

  // notify the Assigned Reviewer
  // lookup model node
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $title = $row->title;

  $subject = "Your Model Review Case requires a Re-Review";
  $body = 'A model you have reviewed., "'. $title .'," has been revised by the author, and the author has requested a re-review of their case to determine if the model now meets the requirements for certification. You may access this review case by visiting: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
  if (variable_get('modelreview_emails_enabled', 0) == 1) {
    notifications_lite_send($reviewer, $subject, $body);
  }

  // notify Editors of the Re-review Request
  $subject = "Model Re-Review has been requested";
  $body = 'The author of the model, "'. $title .'", has completed requested revisions and has requested a Re-Review of their model. The assigned Reviewer has been notified that they need to complete the Re-Review. You may access this case by visiting: '. l($base_url . $base_path ."model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");

  $sql = "SELECT u.uid, u.name FROM users u INNER JOIN users_roles ur ON u.uid = ur.uid "
        ."INNER JOIN permission p ON ur.rid = p.rid WHERE p.perm like '%%%s%%'";
  $results = db_query($sql, 'manage review');
  while ($row = db_fetch_object($results)) {
    if (variable_get('modelreview_emails_enabled', 0) == 1) {
      notifications_lite_send($row->uid, $subject, $body);
    }
  }

  // transfer user to Review form
  drupal_goto('model/'. $modelnid .'/review/status');

}
