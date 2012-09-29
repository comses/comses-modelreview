<?php

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
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the open 'modelreview' record
  $sql = 'SELECT mr.rid, mr.sid, mr.model_nid, modelversion_nid, related, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d';
  $result = db_query($sql, $modelnid);
  $row = db_fetch_object($result);
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $view_args = array($modelnid); // The display we are using takes an argument
  $display_id = 'page_1';   // We are accessing the Latest Version display...
  $view = views_get_view('modelversion'); // ...from the modelversion View
  $view->set_arguments($view_args); // Send Views the arguments it needs
  $view->set_display($display_id);  // and tell it which display we are accessing
  $view->pre_execute(); // run any preprocessing
  $view->execute(); // then execute the view

  $modelversion_nid = $view->render_field('nid', 0);

  // Insert new 'modelreview_action' record
  $action_record = array(
    'rid' => $review_id,
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $form_state['values']['reviewer'],
    'statusid' => 2,
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
  $view_args = array($modelnid);
  $display_id = 'page_6';
  $model = views_get_view('model');
  $model->set_arguments($view_args);
  $model->set_display($display_id);
  $model->pre_execute();
  $model->execute();

  $subject = "You have been assigned a new Model Review case";
  $body = 'You have been assigned a new Model Review case for the model, "'. $model->render_field('title', 0) .'". You may access this review case by visiting: '. l("http://dev.comses.asu.edu/model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
  notifications_lite_send($form_state['values']['reviewer'], $subject, $body);

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

  $sql = 'SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 2 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d';
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
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // get the node id for the latest modelversion, using Views
  $view_args = array($modelnid); // The display we are using takes an argument
  $display_id = 'page_1';   // We are accessing the Latest Version display...
  $view = views_get_view('modelversion'); // ...from the modelversion View
  $view->set_arguments($view_args); // Send Views the arguments it needs
  $view->set_display($display_id);  // and tell it which display we are accessing
  $view->pre_execute(); // run any preprocessing
  $view->execute(); // then execute the view

  $modelversion_nid = $view->render_field('nid', 0);

  // Insert new 'modelreview_action' record
  $action_record = array(
    'rid' => $form_state['values']['rid'],
    'modelversion_nid' => $modelversion_nid,
    'reviewer' => $form_state['values']['reviewer'],
    'statusid' => 3,
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
  $view_args = array($modelnid);
  $display_id = 'page_6';
  $model = views_get_view('model');
  $model->set_arguments($view_args);
  $model->set_display($display_id);
  $model->pre_execute();
  $model->execute();

  $subject = "Model Review has been completed";
  $body = 'The model review case for the model, "'. $model->render_field('title', 0) .'", has been completed by the assigned Reviewer. The reviewer\'s notes and recommendation need to be processed by an Editor, and the case processed as needed. You may access this review case by browsing: '. l("http://dev.comses.asu.edu/model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");

  $sql = "SELECT u.uid, u.name FROM users u INNER JOIN users_roles ur ON u.uid = ur.uid "
        ."INNER JOIN permission p ON ur.rid = p.rid WHERE p.perm like '%%%s%%'";
  $results = db_query($sql, 'edit review');
  while ($row = db_fetch_object($results)) {
    notifications_lite_send($row->uid, $subject, $body);
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
  global $user;
  $modelnid = $form_state['values']['model_nid'];

  // lookup the rid for the 'modelreview' record
  $sql = 'SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 2 INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d';
  $result = db_query($sql, arg(1));
  $row = db_fetch_object($result);
  $review_id = $row->rid;
  $reviewer = $row->reviewer;

  // get the node id for the latest modelversion, using Views
  $view_args = array($modelnid); // The display we are using takes an argument
  $display_id = 'page_1';   // We are accessing the Latest Version display...
  $view = views_get_view('modelversion'); // ...from the modelversion View
  $view->set_arguments($view_args); // Send Views the arguments it needs
  $view->set_display($display_id);  // and tell it which display we are accessing
  $view->pre_execute(); // run any preprocessing
  $view->execute(); // then execute the view

  $modelversion_nid = $view->render_field('nid', 0);


  // we need to lookup the review actions that have been submitted for the editor to review
  // they will be actions that have not been associated ('related') to any editor actions
  $sql = 'SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, 
                FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 3
                INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d AND related = NULL';
  $reviews = db_query($sql, $review['model_nid']);
  // after we add our editor action, we will need to update these review actions to point (via 'related') to our new editor action

  // determine the status id that will need to be recorded here.
  switch ($form_state['values']['decision']) {
    case 0:
      $step = 7;
      break;

    case 1:
      $step = 4;
      break;

    case 2:
      $step = 6;
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
  $sql = "UPDATE {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 3 "
         ."SET related = %d WHERE mr.model_nid = %d AND mra.related IS NULL";
  db_query($sql, $action_sid, $modelnid);

  // Lookup the Model author
  $view_args = array($modelnid);
  $display_id = 'page_6';
  $model = views_get_view('model');
  $model->set_arguments($view_args);
  $model->set_display($display_id);
  $model->pre_execute();
  $model->execute();

  // notify the Author about this action
  switch ($form_state['values']['decision']) {
    case 0:
      $subject = "Model Review has been completed, Certification Denied";
      $body = 'The model review case for your model, "'. $model->render_field('title', 0) .'", has been completed by a CoMSES Reviewer. Unfortunately, After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and your model cannot be certified. For more information on this please visit the Model Review Status Page at: '. l("http://dev.comses.asu.edu/model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;

    case 1:
      $subject = "Model Review has been completed, Revisions Requested";
      $body = 'The model review case for your model, "'. $model->render_field('title', 0) .'", has been completed by the assigned Reviewer. Before your model can be certified, the CoMSES Reviewer has requested some revisions be made to your model in order to fully meet Modeling Best Practes. To review the notes and recommendation for your case, please visit the Model Review Status Page at: '. l("http://dev.comses.asu.edu/model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;

    case 2:
      $subject = "Model Review has been completed, Certified";
      $body = 'The model review case for your model, "'. $model->render_field('title', 0) .'", has been completed by a CoMSES Reviewer. After reviewing your case, your model has been determined to meet CoMSES Modeling Best-Practices and is now Certified. You may access this review case by visiting the Model Review Status Page at: '. l("http://dev.comses.asu.edu/model/". $modelnid ."/review/status", "model/". $modelnid ."/review/status");
      break;
  }

  //notifications_lite_send($model->render_field('uid', 0), $subject, $body);

  drupal_goto('model/'. $modelnid .'/review/status'); 

}
