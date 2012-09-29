<?php
/**
 * @file request-review-page.tpl.php
 * Template for Model Authors to request a Review.
 *
 * - $review: an array of keyed values. It contains:
 *   - $review['model_nid']:        NID for model being Reviewed
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */
?>

<div>
  <p>You may request that this model be reviewed for completeness and Modeling Best-Practices. Models that meet Model Standards will be certified and will display a Certified badge as well as be included on OpenABM&rsquo;s home page as a Featured Model.</p>
  <p>In order for a model to be certified as meeting CoMSES Best-Practices Standards, it must:</p>
  <ol>
    <li>Have well-formatted and commented programming code. This is to ensure other users can understand and replicate the algorithms in your code.</li>
    <li>Be fully documented using the ODD standard. By writing documentation that complies with the ODD, other modelers should be able to replicate your model and its results without having to refer to your programming code.</li>
    <li>Run correctly with the instructions provided with the model. If the model requires special input files or file structures to run, all must be fully explained in the instructions.</li>
  </ol>
  <p>When your model is reviewed, it will be examined to verify it meets all these standards. The reviewer will indicate in the review system whether your model meets these standards. Any elements that require revision will be noted in the comments fields, and if necessary, your model will be flagged for you to revise as needed.</p>
  <p>We urge you to review your model according to these guidelines prior to requesting a review. Once you are confident your model is ready for review, submit your request below.</p>
  <div><?php print drupal_get_form(modelreview_requestreview_form); ?></div>
</div>
