<?php
/**
 * @file reviewer-status-page.tpl.php
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
 *   - $reviewer:         UID of Reviewer assigned to case
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */
global $base_root;

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
      // Status 1: invalid page
      drupal_set_message(t('You are not authorized to view this model information.'));
      drupal_goto('page/invalid-request'); 
      break;

    case 20: // Invite Reviewer
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">

<p>In order to promote best practices and high quality computational models, the CoMSES Net Computational Model Library allows authors of models in the library to request peer-review for Certification.</p>

<p>As a member of CoMSES Net, would you be willing to review the model titled: "' . $title . '" for Certification in the CoMSES Net Computational Model Library? The review process is not intensive. It involves a review of the programming code and documentation to ensure it meets some basic standards for readability and whether the model runs correctly and does what it is stated to do. More information on the Model Certification standards and review process is available here: ' . l($base_root . '/page/model-review-overview', 'page/model-review-overview') . '</p>

<p>The model can be viewed at: ' . l($base_root .'model/'. $model_nid, 'model/'. $model_nid) . '</p>

<p>PLEASE CONFIRM THAT YOU ARE OR ARE NOT ABLE TO REVIEW THIS MODEL. THANK YOU!</p>

<p>---------------------------------------------</p>';

      print '      </div>';
      print '      <div class="modelreview-acceptdecline status-section">'. drupal_render(drupal_get_form('modelreview_reviewer_acceptdecline_form')) .'</div>';

      print '    </div>';
      print '  </div>';
      break;

    case 23: // Reviewer Declined Case
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for letting us know that you cannot review this model.</div>';
      print '    </div>';
      print '  </div>';
      break;

    case 25: // Reviewer Accepted Case
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for accepting this model review case. It is now waiting for your review to be completed.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Review Instructions</div>';
      print '    <div>';
      print '     <p>Please keep in mind that this is a review of the model implementation itself, not of its scientific or theoretical merits.</p>';
      print '     <p>You are asked to review this model according to the following standards and determine if the model Meets, Partially Meets, or Does Not Meet any of these standards. Note fields are available for you to provide specific feedback or as needed to the model author or the editor regarding the case.</p>';
      print '     <h3>CoMSES Model Standards:</h3>';
      print '     <ul>';
      print '       <li>The model code should be well-formatted and commented.</li>';
      print '       <li>The model should be fully documented using the ODD standard or an equivalent documentation protocol.</li>';
      print '       <li>The model should run correctly with the instructions provided. Any extra steps needed to preapre the model for running, such as placing input data files in specific locations, should be fully described in the operating instructions.</li>';
      print '       <li>The model should correctly simulate the processes it claims to simulate.</li>';
      print '    </div>';
      print '  </div>';

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Complete Review</div>';
      print drupal_render(drupal_get_form('modelreview_review_form'));
      print '  </div>';
      break;

    case 30: // Review Completed
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';
      break;

    case 40: // Model Revisions Needed
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    case 50: // Re-Review Requested
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">The model author has completed model revisions and requested a re-review. Please review the model and submit your recommendation on whether this model should be now be certified.</div>';
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
           . "INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 40 "
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
          print '        <div class="modelreview-label">Instructions from the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Review Instructions</div>';
      print '    <div>';
      print '     <p>Reminder: this is a review of the model implementation itself, not of its scientific or theoretical merits.</p>';
      print '     <p>You are asked to re-review this model according to the following standards and determine if the model Meets, Partially Meets, or Does Not Meet any of these standards. Note fields are available for you to provide specific feedback or as needed to the model author or the editor regarding the case.</p>';
      print '     <h3>CoMSES Model Standards:</h3>';
      print '     <ul>';
      print '       <li>The model code should be well-formatted and commented.</li>';
      print '       <li>The model should be fully documented using the ODD standard or an equivalent documentation protocol.</li>';
      print '       <li>The model should run correctly with the instructions provided. Any extra steps needed to preapre the model for running, such as placing input data files in specific locations, should be fully described in the operating instructions.</li>';
      print '       <li>The model should correctly simulate the processes it claims to simulate.</li>';
      print '    </div>';
      print '  </div>';

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Complete Review</div>';
      print drupal_render(drupal_get_form('modelreview_review_form'));
      print '  </div>';

      break;

    case 60: // Case Closed - Certified
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    case 70: // Case Closed - Denied
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $reviewer .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    default:
      // No review requested: invalid page
      drupal_set_message(t('You are not authorized to view this information.'));
      drupal_goto('page/invalid-request'); 
      break;
  }
?>
</div>
