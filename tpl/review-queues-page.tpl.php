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

drupal_set_title('Model Reviews - Management Queue');
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, statusdate, reviewer, "
     . "reviewer_firstname.field_prfl_firstname_value, reviewer_lastname.field_prfl_lastname_value, "
     . "model_node.title AS model_title, reviewer.name, author_firstname.field_prfl_firstname_value, "
     . "author_lastname.field_prfl_lastname_value FROM modelreview mr "
     . "INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN users reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN field_data_field_prfl_firstname reviewer_firstname ON reviewer.uid = reviewer_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname reviewer_lastname ON reviewer.uid = reviewer_lastname.entity_id "
     . "LEFT JOIN node model_node ON mr.model_nid = model_node.nid "
     . "LEFT JOIN users author ON model_node.uid = author.uid "
     . "LEFT JOIN field_data_field_prfl_firstname author_firstname ON author.uid = author_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname author_lastname ON author.uid = author_lastname.entity_id "
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
  //print '        <th class="modelreview-queue-field modelreview-queue-field-author">Author</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
  
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="/model/'. $row->model_nid .'/review/status" class="review-button">Assign</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
//    print '        <td class="modelreview-queue-field modelreview-queue-field-author">';
//    print '          '. ($row->model_author > '' ? $row->model_author : $row->name);
//    print '        </td>';
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
     . "reviewer_firstname.field_prfl_firstname_value, reviewer_lastname.field_prfl_lastname_value, "
     . "model_node.title AS model_title, reviewer.name, author_firstname.field_prfl_firstname_value, "
     . "author_lastname.field_prfl_lastname_value FROM modelreview mr "
     . "INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN users reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN field_data_field_prfl_firstname reviewer_firstname ON reviewer.uid = reviewer_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname reviewer_lastname ON reviewer.uid = reviewer_lastname.entity_id "
     . "LEFT JOIN node model_node ON mr.model_nid = model_node.nid "
     . "LEFT JOIN users author ON model_node.uid = author.uid "
     . "LEFT JOIN field_data_field_prfl_firstname author_firstname ON author.uid = author_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname author_lastname ON author.uid = author_lastname.entity_id "
     . "WHERE mra.statusid = 20";
$results = db_query($sql);

if ($row = $results->fetchObject()) {
  print '<hr />';
  print '<h2>Models Awaiting Review - Reviewer Assigned</h2>';
  print '<div id="modelreview-queue-5" class="clearfix">';
  print '  <table width="100%" class="modelreview modelreview-queue">';
  print '    <thead>';
  print '      <tr>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-nid"></th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-statusdate">Date</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-reviewer">Reviewer</th>';
//  print '        <th class="modelreview-queue-field modelreview-queue-field-author">Author</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="/model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. $row->reviewer_name;
    print '        </td>';
//    print '        <td class="modelreview-queue-field modelreview-queue-field-author">';
//    print '          '. ($row->model_author > '' ? $row->model_author : $row->name);
//    print '        </td>';
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
     . "reviewer_firstname.field_prfl_firstname_value, reviewer_lastname.field_prfl_lastname_value, "
     . "model_node.title AS model_title, reviewer.name, author_firstname.field_prfl_firstname_value, "
     . "author_lastname.field_prfl_lastname_value FROM modelreview mr "
     . "INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN users reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN field_data_field_prfl_firstname reviewer_firstname ON reviewer.uid = reviewer_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname reviewer_lastname ON reviewer.uid = reviewer_lastname.entity_id "
     . "LEFT JOIN node model_node ON mr.model_nid = model_node.nid "
     . "LEFT JOIN users author ON model_node.uid = author.uid "
     . "LEFT JOIN field_data_field_prfl_firstname author_firstname ON author.uid = author_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname author_lastname ON author.uid = author_lastname.entity_id "
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
//  print '        <th class="modelreview-queue-field modelreview-queue-field-author">Author</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="/model/'. $row->model_nid .'/review/status" class="review-button">Process</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. $row->reviewer_name;
    print '        </td>';
//    print '        <td class="modelreview-queue-field modelreview-queue-field-author">';
//    print '          '. ($row->model_author > '' ? $row->model_author : $row->name);
//    print '        </td>';
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
     . "reviewer_firstname.field_prfl_firstname_value, reviewer_lastname.field_prfl_lastname_value, "
     . "model_node.title AS model_title, reviewer.name, author_firstname.field_prfl_firstname_value, "
     . "author_lastname.field_prfl_lastname_value FROM modelreview mr "
     . "INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN users reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN field_data_field_prfl_firstname reviewer_firstname ON reviewer.uid = reviewer_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname reviewer_lastname ON reviewer.uid = reviewer_lastname.entity_id "
     . "LEFT JOIN node model_node ON mr.model_nid = model_node.nid "
     . "LEFT JOIN users author ON model_node.uid = author.uid "
     . "LEFT JOIN field_data_field_prfl_firstname author_firstname ON author.uid = author_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname author_lastname ON author.uid = author_lastname.entity_id "
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
//  print '        <th class="modelreview-queue-field modelreview-queue-field-author">Author</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="/model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. $row->reviewer_name;
    print '        </td>';
//    print '        <td class="modelreview-queue-field modelreview-queue-field-author">';
//    print '          '. ($row->model_author > '' ? $row->model_author : $row->name);
//    print '        </td>';
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
     . "reviewer_firstname.field_prfl_firstname_value, reviewer_lastname.field_prfl_lastname_value, "
     . "model_node.title AS model_title, reviewer.name, author_firstname.field_prfl_firstname_value, "
     . "author_lastname.field_prfl_lastname_value FROM modelreview mr "
     . "INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
     . "LEFT JOIN users reviewer ON mra.reviewer = reviewer.uid "
     . "LEFT JOIN field_data_field_prfl_firstname reviewer_firstname ON reviewer.uid = reviewer_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname reviewer_lastname ON reviewer.uid = reviewer_lastname.entity_id "
     . "LEFT JOIN node model_node ON mr.model_nid = model_node.nid "
     . "LEFT JOIN users author ON model_node.uid = author.uid "
     . "LEFT JOIN field_data_field_prfl_firstname author_firstname ON author.uid = author_firstname.entity_id "
     . "LEFT JOIN field_data_field_prfl_lastname author_lastname ON author.uid = author_lastname.entity_id "
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
//  print '        <th class="modelreview-queue-field modelreview-queue-field-author">Author</th>';
  print '        <th class="modelreview-queue-field modelreview-queue-field-title">Model Title</th>';
  print '      </tr>';
  print '    </thead>';
      
  $rownum = 1;
  do {
    print '    <tbody>';
    print '      <tr class="modelreview-queue-row '. ($rownum % 2 == 0 ? 'even' : 'odd')  .'">';
    print '        <td class="modelreview-queue-field modelreview-queue-field-nid">';
    print '           <a href="/model/'. $row->model_nid .'/review/status" class="review-button">View</a>';
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-statusdate">';
    print '          '. date('M j, Y', $row->statusdate);
    print '        </td>';
    print '        <td class="modelreview-queue-field modelreview-queue-field-reviewer">';
    print '          '. $row->reviewer_name;
    print '        </td>';
//    print '        <td class="modelreview-queue-field modelreview-queue-field-author">';
//    print '          '. ($row->model_author > '' ? $row->model_author : $row->name);
//    print '        </td>';
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
