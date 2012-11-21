<?php
/**
 * @file review-queues-page.tpl.php
 * Template for displaying Model Review status to Model Authors.
 *
 * - $review: an array of keyed values. It contains:
 *
 *   - $review['model_nid']:        NID for model being Reviewed
 *   - $review['modelversion_nid']: NID of current Model Version node
 *   - $review['rid']:              ID of this model review
 *   - $review['sid']:              ID of the latest review action ("Step") posted 
 *   - $review['statusid']:         Status ID of the latest review action
 *   - $review['statusdate']:       Datetime (Unix) of the latest review action
 *   - $review['status']:           Text of action status
 *   - $review['reviewer']:         UID of Reviewer assigned to case
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */

drupal_set_title('Model Reviews - Management Queue');
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer, "
  ."ctp.field_fullname_value AS reviewer_name, model_node.title AS model_title, users.name, "
  ."ctp2.field_fullname_value AS model_author FROM modelreview mr "
  ."INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
  ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
  ."LEFT JOIN node node_reviewer ON reviewer = node_reviewer.uid AND node_reviewer.type =  'profile' "
  ."LEFT JOIN content_type_profile ctp ON node_reviewer.vid = ctp.vid "
  ."INNER JOIN node model_node ON mr.model_nid = model_node.nid "
  ."INNER JOIN users ON model_node.uid = users.uid "
  ."LEFT JOIN node model_author ON model_node.uid = model_author.uid AND model_author.type = 'profile' "
  ."LEFT JOIN content_type_profile ctp2 ON model_author.vid = ctp2.vid WHERE mra.statusid = 10";
$results = db_query($sql);

if ($row = db_fetch_object($results)) {
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
  } while ($row = db_fetch_object($results));

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer, "
  ."ctp.field_fullname_value AS reviewer_name, model_node.title AS model_title, users.name, "
  ."ctp2.field_fullname_value AS model_author FROM modelreview mr "
  ."INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
  ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
  ."LEFT JOIN node node_reviewer ON reviewer = node_reviewer.uid AND node_reviewer.type =  'profile' "
  ."LEFT JOIN content_type_profile ctp ON node_reviewer.vid = ctp.vid "
  ."INNER JOIN node model_node ON mr.model_nid = model_node.nid "
  ."INNER JOIN users ON model_node.uid = users.uid "
  ."LEFT JOIN node model_author ON model_node.uid = model_author.uid AND model_author.type = 'profile' "
  ."LEFT JOIN content_type_profile ctp2 ON model_author.vid = ctp2.vid WHERE mra.statusid = 20";
$results = db_query($sql);

if ($row = db_fetch_object($results)) {
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
  } while ($row = db_fetch_object($results));

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer, "
  ."ctp.field_fullname_value AS reviewer_name, model_node.title AS model_title, users.name, "
  ."ctp2.field_fullname_value AS model_author FROM modelreview mr "
  ."INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
  ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
  ."LEFT JOIN node node_reviewer ON reviewer = node_reviewer.uid AND node_reviewer.type =  'profile' "
  ."LEFT JOIN content_type_profile ctp ON node_reviewer.vid = ctp.vid "
  ."INNER JOIN node model_node ON mr.model_nid = model_node.nid "
  ."INNER JOIN users ON model_node.uid = users.uid "
  ."LEFT JOIN node model_author ON model_node.uid = model_author.uid AND model_author.type = 'profile' "
  ."LEFT JOIN content_type_profile ctp2 ON model_author.vid = ctp2.vid WHERE mra.statusid = 30";
$results = db_query($sql);

if ($row = db_fetch_object($results)) {
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
  } while ($row = db_fetch_object($results));

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer, "
  ."ctp.field_fullname_value AS reviewer_name, model_node.title AS model_title, users.name, "
  ."ctp2.field_fullname_value AS model_author FROM modelreview mr "
  ."INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
  ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
  ."LEFT JOIN node node_reviewer ON reviewer = node_reviewer.uid AND node_reviewer.type =  'profile' "
  ."LEFT JOIN content_type_profile ctp ON node_reviewer.vid = ctp.vid "
  ."INNER JOIN node model_node ON mr.model_nid = model_node.nid "
  ."INNER JOIN users ON model_node.uid = users.uid "
  ."LEFT JOIN node model_author ON model_node.uid = model_author.uid AND model_author.type = 'profile' "
  ."LEFT JOIN content_type_profile ctp2 ON model_author.vid = ctp2.vid WHERE mra.statusid = 40";
$results = db_query($sql);

if ($row = db_fetch_object($results)) {
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
  } while ($row = db_fetch_object($results));

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>

<?php
$sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, reviewer, "
  ."ctp.field_fullname_value AS reviewer_name, model_node.title AS model_title, users.name, "
  ."ctp2.field_fullname_value AS model_author FROM modelreview mr "
  ."INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
  ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
  ."LEFT JOIN node node_reviewer ON reviewer = node_reviewer.uid AND node_reviewer.type =  'profile' "
  ."LEFT JOIN content_type_profile ctp ON node_reviewer.vid = ctp.vid "
  ."INNER JOIN node model_node ON mr.model_nid = model_node.nid "
  ."INNER JOIN users ON model_node.uid = users.uid "
  ."LEFT JOIN node model_author ON model_node.uid = model_author.uid AND model_author.type = 'profile' "
  ."LEFT JOIN content_type_profile ctp2 ON model_author.vid = ctp2.vid WHERE mra.statusid = 50";
$results = db_query($sql);

if ($row = db_fetch_object($results)) {
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
  } while ($row = db_fetch_object($results));

  print '    </table>';
  print '</div><!-- /#modelreview-queue -->';
}
?>
