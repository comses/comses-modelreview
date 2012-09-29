<?php
/**
 * @file reviewer-status-page.tpl.php
 * Template for displaying Model Review status for Model Authors.
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

?>

<?php  // Determine who is viewing the Status page and the current Review Status Code
  // Lookup the Model
  $sql = "SELECT nid, uid, title FROM {node} WHERE type = 'model' AND node.nid = %d";
  $result = db_query($sql, $review['model_nid']);
  $row = db_fetch_object($result);
  $author = $row->author;
  $title = $row->title;
?>

<?php drupal_set_title('Model Review Status'); ?>

<div id="modelreview-<?php print $review['rid']; ?>" class="<?php print $classes; ?> clearfix">
  <?php print $user_picture; ?>

  <?php if ($title): ?>
    <h2 class="title"><a href="<?php print base_path() .'model/'. $review['model_nid'] ?>" target="_blank"><?php print $title; ?></a></h2>
    <div class="model-review-title-link"><a href="<?php print base_path() .'model/'. $review['model_nid'] ?>" target="_blank"><?php print t('(View Model in New Window)'); ?></a></div>
  <?php endif; ?>

  <?php if ($unpublished): ?>
    <div class="unpublished"><?php print t('Unpublished'); ?></div>
  <?php endif; ?>

  <?php if ($display_submitted || $terms): ?>
    <div class="meta">
      <?php if ($display_submitted): ?>
        <span class="submitted">
          <?php print $submitted; ?>
        </span>
      <?php endif; ?>

      <?php if ($terms): ?>
        <div class="terms terms-inline"><?php print $terms; ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="modelreview-section">
    <div class="modelreview-field">
      <div class="modelreview-label">Current Status:</div>
      <div class="modelreview-value"><?php print $review['status']; ?></div>
    </div>
    <div class="modelreview-field">
      <div class="modelreview-label">Status Changed:</div>
      <div class="modelreview-value"><?php print date('M j, Y - G:i', $review['statusdate']); ?></div>
    </div>

<?php
  switch ($review['statusid']) {
    case 1: // Review Requested
      // Status 1: invalid page
      drupal_set_message('73: You are not authorized to view this model information.');
      drupal_goto('page/invalid-request'); 
      break;


    case 2: // Reviewer Assigned
      // Status 2: Show model status info, review form
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">This model has been assigned to you as a CoMSES Reviewer and is waiting for your review to begin.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Complete Review</div>';
      print drupal_get_form(modelreview_review_form);
      print '  </div>';
      break;

    case 3: // Review Completed
      // Status 3: Thank you for your service
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';
      break;

    case 4: // Model Revisions Needed
      // Status 4: Thank you for your service
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    case 5: // Re-Review Requested
      // Status 5 (Re-Review): Show model status info and review form
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">The model author has completed model revisions and requested a re-review. Please review the model and submit your recommendation on whether this model should be now be certified.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';

      // fetch Editor actions, and report the reviewer reports associated with that editor action

      // Lookup Editor Notes (May be multiple posts due to re-reviews)
      $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.statusid, mrad.status, statusdate, editor_notes, recommendation "
            ."FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mra.statusid = 4 "
            ."INNER JOIN {modelreview_actiondesc} mrad ON mra.statusid = mrad.statusid WHERE mr.model_nid = %d";
      $editoractions = db_query($sql, $review['model_nid']);

      while ($editor_row = db_fetch_object($editoractions)) {
        // Lookup Reviewer Notes (May be multiple posts due to re-reviews, or multiple reviewers)
        $sql = "SELECT mr.model_nid, mra.rid, mra.sid, mra.related, mra.statusid, mrad.status, statusdate, "
              ."mc1.compliance AS 'code_clean', mc2.compliance AS 'code_commented', mc3.compliance AS 'model_documented', "
              ."mc4.compliance AS 'model_runs', code_notes, doc_notes, other_notes, editor_notes, mrec.recommendation "
              ."FROM modelreview mr INNER JOIN modelreview_action mra ON mr.rid = mra.rid AND mra.statusid = 3 "
              ."INNER JOIN modelreview_actiondesc mrad ON mra.statusid = mrad.statusid "
              ."INNER JOIN modelreview_compliance mc1 ON mra.code_clean = mc1.cid "
              ."INNER JOIN modelreview_compliance mc2 ON mra.code_clean = mc2.cid "
              ."INNER JOIN modelreview_compliance mc3 ON mra.code_clean = mc3.cid "
              ."INNER JOIN modelreview_compliance mc4 ON mra.code_clean = mc4.cid "
              ."INNER JOIN modelreview_recommend mrec ON mra.code_clean = mrec.id "
              ."WHERE mr.model_nid = %d AND mra.related = %d";
        $reviews = db_query($sql, $review['model_nid'], $editor_row->sid);

        while ($review_row = db_fetch_object($reviews)) {
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
          print '        <div class="modelreview-label">Instructions from the Editor:</div>';
          print '        <div class="modelreview-textvalue">'. $editor_row->editor_notes .'</div>';
          print '      </div>';
          print '    </div>';
          print '  </div>';
        }
      }

      print '  <div class="modelreview-section">';
      print '    <div class="modelreview-section-head">Complete Review</div>';
      print drupal_get_form(modelreview_review_form);
      print '  </div>';

      break;

    case 6:
      // Status 6: Thank you for your service
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    case 7:
      // Status 7: Thank you for your service
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Info on Current Status:</div>';
      print '      <div class="modelreview-textvalue">Thank you for your service. You will be notified if any further action is required from you on this model review.</div>';
      print '    </div>';
      print '    <div class="modelreview-field">';
      print '      <div class="modelreview-label">Assigned Reviewer:</div>';
      print '      <div class="modelreview-value">'. $review['reviewer'] .'</div>';
      print '    </div>';
      print '  </div>';

      break;

    default:
      // No review requested: invalid page
      drupal_set_message('261r: You are not authorized to view this information.');
      drupal_goto('page/invalid-request'); 
      break;
  }
?>

  <?php print $links; ?>
</div>
