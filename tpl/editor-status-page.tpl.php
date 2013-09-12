<?php
/**
 * @file editor-status-page.tpl.php
 * Template for displaying Model Review status for Model Authors.
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
 *   - $reviewer:         Name of Reviewer assigned to case
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */

  // Determine who is viewing the Status page and the current Review Status Code
  // Lookup the Model
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = :nid";
  $result = db_query($sql, array(':nid' => $model_nid));
  $row = $result->fetchObject();
  
  $author = $row->uid;
  $title = $row->title;

?>

<?php drupal_set_title('Model Review Status'); ?>

<div id="modelreview-<?php print $rid; ?>" class="<?php print $classes; ?> clearfix">

  <?php if ($title): ?>
    <h2 class="title"><a href="<?php print base_path() .'model/'. $model_nid ?>" target="_blank"><?php print $title; ?></a></h2>
    <div class="model-review-title-link"><a href="<?php print base_path() .'model/'. $model_nid ?>" target="_blank"><?php print t('(View Model in New Window)'); ?></a></div>
  <?php endif; ?>

  <div class="modelreview-section">
    <div class="modelreview-field">
      <div class="modelreview-label">Current Status:</div>
      <div class="modelreview-value"><?php print $status; ?></div>
    </div>
    <div class="modelreview-field">
      <div class="modelreview-label">Status Changed:</div>
      <div class="modelreview-value"><?php print date('M j, Y - G:i', $statusdate); ?></div>
    </div>

<?php
  switch ($statusid) {
    case 10: // Review Requested
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">The author has requested a review of this model. This case needs to be assigned to a reviewer. Please assign one below, and an invitation email will be sent to the potential reviewer.</div>';
      print '    </div>';
      print '  </div>';

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Assign Reviewer</div>';
      print drupal_render(drupal_get_form('modelreview_assignreviewer_form'));
      print '  </div>';

      break;

    case 20: // Reviewer Invited
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Awaiting reviewer acceptance of this model review case.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Invited Reviewer:</div>';
      print '      <div class="modelreview-textvalue">' . $reviewer . '</div>';
      print '    </div>';
      print '  </div>';
      break;

    case 23: // Reviewer Declined Case
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">The reviewer has declined to review this model. This case needs to be assigned to a new reviewer. The previously invited reviewer may have suggested an alternate reviewer for this case. Please assign one below, and an invitation email will be sent to the potential reviewer.</div>';
      print '    </div>';
      print '  </div>';

      // fetch Reviewer note

      // Lookup declined reviewers (May be multiple entries if repeated rejections have occurred)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, mra.statusdate, mra.other_notes, "
           . "mra.reviewer, reviewer_firstname.field_profile2_firstname_value as firstname, "
           . "reviewer_lastname.field_profile2_lastname_value as lastname "
           . "FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 23 "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
           . "LEFT JOIN {users} reviewer ON mra.reviewer = reviewer.uid "
           . "LEFT JOIN {profile} p ON reviewer.uid = p.uid AND p.type = 'main' "
           . "LEFT JOIN field_data_field_profile2_firstname reviewer_firstname ON p.pid = reviewer_firstname.entity_id "
           . "LEFT JOIN field_data_field_profile2_lastname reviewer_lastname ON p.pid = reviewer_lastname.entity_id "
           . "WHERE mr.model_nid = :nid ";
      $declinedreviews = db_query($sql, array(':nid' => $model_nid));

      while ($declinedreview_row = $declinedreviews->fetchObject()) {
        print '  <div class="modelreview-reviewinfo modelreview-section">';
        print '    <div class="modelreview-section-head">';
        print '      <div class="modelreview-label">Review Declined</div>';
        print '    </div>';
        print '    <div class="modelreview-codeinfo modelreview-block">';
        print '      <div class="modelreview-block-head">'. $declinedreview_row->firstname .' '. $declinedreview_row->lastname .'</div>';
        print '      <div class="modelreview-field">';
        print '        <div class="modelreview-label">Declined:</div>';
        print '        <div class="modelreview-value">'. date('M j, Y - G:i', $declinedreview_row->statusdate) .'</div>';
        print '      </div>';
        print '      <div class="modelreview-field">';
        print '        <div class="modelreview-label">Alternate Reviewers:</div>';
        print '        <div class="modelreview-value">'. $declinedreview_row->other_notes .'</div>';
        print '      </div>';
        print '    </div>';
        print '  </div>';
      }

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Assign New Reviewer</div>';
      print drupal_render(drupal_get_form('modelreview_assignreviewer_form'));
      print '  </div>';

      break;

    case 25: // Reviewer Accepted Case
      // Show model status and who is assigned as Reviewer
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">This model has been assigned to a CoMSES Reviewer and is waiting for the review to begin.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    case 30: // Review Completed
      // Review comments and recommendation, process case (Revise, Close)
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">The model review has been completed. Please review the Reviewer\'s notes and recommendation. Based on this information, select the most appropriate action below: Certify the model, return to the author for revisions, or deny certification.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, code_clean, code_commented, "
           . "model_documented, model_runs, code_notes, doc_notes, other_notes, editor_notes, recommendation FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid IN (40,50,60) "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
      $editoractions = db_query($sql, array(':nid' => $model_nid));

      while ($editor_row = $editoractions->fetchObject()) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
             . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
             . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
             . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
             . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
             . "INNER JOIN {modelreview_compliance} mc1 ON mra.code_clean = mc1.cid "
             . "INNER JOIN {modelreview_compliance} mc2 ON mra.code_commented = mc2.cid "
             . "INNER JOIN {modelreview_compliance} mc3 ON mra.model_documented = mc3.cid "
             . "INNER JOIN {modelreview_compliance} mc4 ON mra.model_runs = mc4.cid "
             . "INNER JOIN {modelreview_recommend} mrec ON mra.recommendation = mrec.id "
             . "WHERE mr.model_nid = :nid AND mra.related = :related";
        $reviews = db_query($sql, array(':nid' => $model_nid, ':related' => $editor_row->sid));

        while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Running the Model</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Other Notes:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Editor\'s Instructions</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      // plus we need to display the most recent review that is not associate with an editor action (because we haven't done that yet.)

      // Lookup new Reviewer Notes (Not related to any editor actions)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
           . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
           . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
           . "FROM modelreview mr INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mra.statusid = 30 "
           . "LEFT JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
           . "LEFT JOIN modelreview_compliance mc1 ON mra.code_clean = mc1.cid "
           . "LEFT JOIN modelreview_compliance mc2 ON mra.code_commented = mc2.cid "
           . "LEFT JOIN modelreview_compliance mc3 ON mra.model_documented = mc3.cid "
           . "LEFT JOIN modelreview_compliance mc4 ON mra.model_runs = mc4.cid "
           . "LEFT JOIN modelreview_recommend mrec ON mra.recommendation = mrec.id "
           . "WHERE mr.model_nid = :nid AND mra.related IS NULL";
      $reviews = db_query($sql, array(':nid' => $model_nid));

      while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Running the Model</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Other Notes:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
      }

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Process Review</div>';
      print drupal_render(drupal_get_form('modelreview_editor_process_form'));
      print '  </div>';
      break;

    case 40: // Model Revisions Needed
      // Show model status & history
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div>This review case has been returned to the author, and is awaiting revisions.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, code_clean, code_commented, "
           . "model_documented, model_runs, code_notes, doc_notes, other_notes, editor_notes, recommendation FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid IN (40,50,60) "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
      $editoractions = db_query($sql, array(':nid' => $model_nid));

      while ($editor_row = $editoractions->fetchObject()) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
             . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
             . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
             . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
             . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
             . "INNER JOIN {modelreview_compliance} mc1 ON mra.code_clean = mc1.cid "
             . "INNER JOIN {modelreview_compliance} mc2 ON mra.code_commented = mc2.cid "
             . "INNER JOIN {modelreview_compliance} mc3 ON mra.model_documented = mc3.cid "
             . "INNER JOIN {modelreview_compliance} mc4 ON mra.model_runs = mc4.cid "
             . "INNER JOIN {modelreview_recommend} mrec ON mra.recommendation = mrec.id "
             . "WHERE mr.model_nid = :nid AND mra.related = :related";
        $reviews = db_query($sql, array(':nid' => $model_nid, ':related' => $editor_row->sid));

        while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Running the Model</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          if (!empty($review_row->other_notes)) {
            print '      <div class="modelreview-field">';
            print '        <div class="modelreview-label">Other Notes:</div>';
            print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
            print '      </div>';
          }
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Editor\'s Instructions</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      break;

    case 50: // Re-Review Requested
      // Show model status & history
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div>The model author has completed model revisions and requested a re-review. The case has been returned to the assigned reviewer for processing.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, code_clean, code_commented, "
           . "model_documented, model_runs, code_notes, doc_notes, other_notes, editor_notes, recommendation FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid IN (40,50,60) "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
      $editoractions = db_query($sql, array(':nid' => $model_nid));

      while ($editor_row = $editoractions->fetchObject()) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
             . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
             . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
             . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
             . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
             . "INNER JOIN {modelreview_compliance} mc1 ON mra.code_clean = mc1.cid "
             . "INNER JOIN {modelreview_compliance} mc2 ON mra.code_commented = mc2.cid "
             . "INNER JOIN {modelreview_compliance} mc3 ON mra.model_documented = mc3.cid "
             . "INNER JOIN {modelreview_compliance} mc4 ON mra.model_runs = mc4.cid "
             . "INNER JOIN {modelreview_recommend} mrec ON mra.recommendation = mrec.id "
             . "WHERE mr.model_nid = :nid AND mra.related = :related";
        $reviews = db_query($sql, array(':nid' => $model_nid, ':related' => $editor_row->sid));

        while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
//          print '      <div class="modelreview-field">';
//          print '        <div class="modelreview-label">Notes on Running the Model:</div>';
//          print '        <div class="modelreview-value">'. $review_row->run_notes .'</div>';
//          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          if (!empty($review_row->other_notes)) {
            print '      <div class="modelreview-field">';
            print '        <div class="modelreview-label">Other Notes:</div>';
            print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
            print '      </div>';
          }
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Editor\'s Instructions</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      break;

    case 60:
      // Status 6 (Closed - Certified): Show model status & history
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div>This model has been Certified, and the review case is closed.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, code_clean, code_commented, "
           . "model_documented, model_runs, code_notes, doc_notes, other_notes, editor_notes, recommendation FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid IN (40,50,60) "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
      $editoractions = db_query($sql, array(':nid' => $model_nid));

      while ($editor_row = $editoractions->fetchObject()) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
             . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
             . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
             . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
             . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
             . "INNER JOIN {modelreview_compliance} mc1 ON mra.code_clean = mc1.cid "
             . "INNER JOIN {modelreview_compliance} mc2 ON mra.code_commented = mc2.cid "
             . "INNER JOIN {modelreview_compliance} mc3 ON mra.model_documented = mc3.cid "
             . "INNER JOIN {modelreview_compliance} mc4 ON mra.model_runs = mc4.cid "
             . "INNER JOIN {modelreview_recommend} mrec ON mra.recommendation = mrec.id "
             . "WHERE mr.model_nid = :nid AND mra.related = :related";
        $reviews = db_query($sql, array(':nid' => $model_nid, ':related' => $editor_row->sid));

        while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
//          print '      <div class="modelreview-field">';
//          print '        <div class="modelreview-label">Notes on Running the Model:</div>';
//          print '        <div class="modelreview-value">'. $review_row->run_notes .'</div>';
//          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          if (!empty($review_row->other_notes)) {
            print '      <div class="modelreview-field">';
            print '        <div class="modelreview-label">Other Notes:</div>';
            print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
            print '      </div>';
          }
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Editor\'s Instructions</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      break;

    case 70:
      // Status 7 (Closed - Denied): Show model status & history
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div>This model review case was closed, as the model can not be Certified at this time.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, code_clean, code_commented, "
           . "model_documented, model_runs, code_notes, doc_notes, other_notes, editor_notes, recommendation FROM {modelreview} mr "
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid IN (40,50,60) "
           . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = :nid";
      $editoractions = db_query($sql, array(':nid' => $model_nid));

      while ($editor_row = $editoractions->fetchObject()) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
             . "mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
             . "mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
             . "FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 30 "
             . "INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid "
             . "INNER JOIN {modelreview_compliance} mc1 ON mra.code_clean = mc1.cid "
             . "INNER JOIN {modelreview_compliance} mc2 ON mra.code_commented = mc2.cid "
             . "INNER JOIN {modelreview_compliance} mc3 ON mra.model_documented = mc3.cid "
             . "INNER JOIN {modelreview_compliance} mc4 ON mra.model_runs = mc4.cid "
             . "INNER JOIN {modelreview_recommend} mrec ON mra.recommendation = mrec.id "
             . "WHERE mr.model_nid = :nid AND mra.related = :related";
        $reviews = db_query($sql, array(':nid' => $model_nid, ':related' => $editor_row->sid));

        while ($review_row = $reviews->fetchObject()) {
          print '  <div class="modelreview-reviewinfo modelreview-section">';
          print '    <div class="modelreview-section-head">';
          print '      <div class="modelreview-label">Review:</div>';
          print '      <div class="modelreview-value">'. date('M j, Y - G:i', $review_row->statusdate) .'</div>';
          print '    </div>';
          print '    <div class="modelreview-codeinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Programming Code</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Clean:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_clean .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Code Commented:</div>';
          print '        <div class="modelreview-value">'. $review_row->code_commented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Code:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->code_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-docinfo modelreview-block">';
          print '      <div class="modelreview-block-head">Documentation</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Documented:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_documented .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Review Notes on Documentation:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->doc_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-runinfo modelreview-block">';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Model Runs:</div>';
          print '        <div class="modelreview-value">'. $review_row->model_runs .'</div>';
          print '      </div>';
//          print '      <div class="modelreview-field">';
//          print '        <div class="modelreview-label">Notes on Running the Model:</div>';
//          print '        <div class="modelreview-value">'. $review_row->run_notes .'</div>';
//          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Other Review Notes</div>';
          if (!empty($review_row->other_notes)) {
            print '      <div class="modelreview-field">';
            print '        <div class="modelreview-label">Other Notes:</div>';
            print '        <div class="modelreview-textvalue">'. $review_row->other_notes .'</div>';
            print '      </div>';
          }
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Notes to the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $review_row->editor_notes .'</div>';
          print '      </div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-label">Reviewer Recommendation:</div>';
          print '        <div class="modelreview-value">'. $review_row->recommendation .'</div>';
          print '      </div>';
          print '    </div>';
          print '    <div class="modelreview-instructions modelreview-block">';
          print '      <div class="modelreview-block-head">Editor\'s Instructions</div>';
          print '      <div class="modelreview-field">';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      break;

    default:
      // No review requested: invalid page
      drupal_set_message(t('You are not authorized to view this information.'));
      drupal_goto('page/invalid-request'); 
      break;
  }
?>
</div>
