<?php
/**
 * @file request-review-page.tpl.php
 * Template for Model Authors to request a Review.
 *
 * - Variables available:
 *   - $model_nid:        NID for model being Reviewed
 *
 * This template is based off the Zen Node template. Some code may be unneeded at this
 * time based on the features that have been implemented for Model Reviews, but that
 * is fine, those sections won't be generated.
 */
?>
<div>
  <p>Model authors may request that their models be reviewed for completeness and Modeling Best-Practices. Models that meet Model Standards will be certified and will display a Certified badge as well as be included on OpenABM&rsquo;s home page as a Featured Model.</p>
  <p>In order for a model to be certified as meeting CoMSES Best-Practices Standards, it must:</p>
  <ol>
    <li>Have well-formatted and commented programming code. This is to ensure other users can understand and replicate the algorithms in your code.</li>
    <li>Be fully documented using the ODD standard for model documentation, or an equivalent documentation protocol. By writing documentation that complies with the ODD, other modelers should be able to replicate your model and its results without having to refer to your programming code. We recommend you look at some of the Certified models in the library to see relevant examples.</li>
    <li>Run correctly with the instructions provided with the model. If the model requires special input files or file structures to run, all must be fully explained in the instructions.</li>
    <li>Correctly simulates the processes it claims to simulate.</li>
  </ol>
  <p>When a model is reviewed, it will be examined to verify it meets all these standards. The CoMSES reviewer will provide notes to the model author whether the model meets these standards. If any elements require revision in order to meet the Best-Practices Standards, the author will be given notes on what those steps are.</p>
  <p>Hopefully, the Model Review system and model certification will assist the CoMSES Community in recognizing well-developed models and help us all to raise the bar of quality for our field of practice.</p>
</div>
