<?php
/**
 * @file review-queues-page.tpl.php
 * Template for displaying Model Review status to Model Authors.
 *
 * - Variables available:
 *
 *   - $model_nid:        NID for model being Reviewed
 *   - $modelversion_nid: NID of current Model Version node
 *   - $rid:              ID of this model review
 *   - $sid:              ID of the latest review action ("Step") posted 
 *   - $statusid:         Status ID of the latest review action
 *   - $statusdate:       Datetime (Unix) of the latest review action
 *   - $status:           Text of action status
 *   - $reviewer:         UID of Reviewer assigned to case
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */

global $base_path;

drupal_set_title('Model Reviews - Management Queue');

$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "model_node.title AS model_title FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 10";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<h2>New Review Requests - Assign Reviewers</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
  
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">Assign</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 20";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Reviewers Invited - Awaiting Acceptance/Decline</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 23";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Reviewers Declined Cases - Need New Reviewer Invited</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 25";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Reviewers Accepted Cases - Awaiting Review Completion</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 30";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Reviews Completed - Editor Action Required</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">Process</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 40";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Revisions Requested - Cases Returned to Authors for Revision</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_profile2_firstname_value as reviewer_first, "
     . "reviewer_lastname.field_profile2_lastname_value as reviewer_last, model_node.title AS model_title "
     . "FROM {modelreview} mr "
     . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN {profile} profile ON reviewer.uid = profile.uid "
     . "LEFT JOIN {field_data_field_profile2_firstname} reviewer_firstname ON profile.pid = reviewer_firstname.entity_id "
     . "LEFT JOIN {field_data_field_profile2_lastname} reviewer_lastname ON profile.pid = reviewer_lastname.entity_id "
     . "LEFT JOIN {node} model_node ON mr.model_nid = model_node.nid "
     . "WHERE mra.statusid = 50";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Re-Reviews Requested - Revisions Completed</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="' . $base_path . 'model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. substr($row->reviewer_first, 0, 1) . ' ' . $row->reviewer_last;
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-title">';
    print '          '. $row->model_title;
    print '        </td>';
    print '      </tr>';
    print '    </tbody>';
    $rownum++;
  } while ($row = $results->fetchObject());

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>
