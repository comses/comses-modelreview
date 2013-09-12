<?php
/*
    modelreview.module for the CoMSES Computational Model Library
    Copyright (C) 2012. Nathan D. Rollins, Research Network for Computational
        Modeling for the SocioEcological Sciences (CoMSES Net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * Admin settings page
 */
function modelreview_admin_settings() {
  $form = array();

  $form['reviews_enabled'] = array(
    '#type' => 'fieldset',
    '#title' => t('Model Reviews Status'),
    '#collapsible' => FALSE, 
    '#collapsed' => FALSE,
  );

  $form['reviews_enabled']['modelreview_system_enabled'] = array(
    '#type' => 'radios', 
    '#title' => t('Enable/Disable Model Reviews'), 
    '#default_value' => variable_get('modelreview_system_enabled', 0), 
    '#options' => array(t('Disabled'), t('Enabled')),
  );

  $form['notifications'] = array(
    '#type' => 'fieldset',
    '#title' => t('Email Notifications'),
    '#collapsible' => FALSE, 
    '#collapsed' => FALSE,
  );

  $form['notifications']['modelreview_emails_enabled'] = array(
    '#type' => 'radios', 
    '#title' => t('Enable/Disable Notifications'), 
    '#default_value' => variable_get('modelreview_emails_enabled', 0), 
    '#options' => array(t('Disabled'), t('Enabled')),
  );

  return system_settings_form($form);
}

/**
 * Notification template settings page
 */
function modelreview_admin_templates() {
  $form = array();

  $options = array(
    'perm' => t('Permission'),
    'user' => t('User'),
  );

  $messages = db_select('modelreview_messages', 'm')
            ->fields('m', array('name', 'recipient', 'recipient_type', 'subject', 'body'))
            ->execute()->fetchAllAssoc('name');

  $form['review_requested'] = array(
    '#type' => 'fieldset',
    '#title' => t('Review Requested'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['review_requested']['rr_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['review_requested']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['review_requested']['rr_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => 'perm', 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['review_requested']['rr_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['review_requested']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['review_requested']['rr_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['review_requested']->body, 
    '#required' => TRUE,
  );


  $form['invite_reviewer'] = array(
    '#type' => 'fieldset',
    '#title' => t('Invite Reviewer'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['invite_reviewer']['ir_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['invite_reviewer']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['invite_reviewer']['ir_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['invite_reviewer']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['invite_reviewer']['ir_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['invite_reviewer']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['invite_reviewer']['ir_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['invite_reviewer']->body, 
    '#required' => TRUE,
  );


  $form['review_declined'] = array(
    '#type' => 'fieldset',
    '#title' => t('Reviewer Declined'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['review_declined']['rd_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['review_declined']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['review_declined']['rd_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['review_declined']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['review_declined']['rd_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['review_declined']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['review_declined']['rd_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['review_declined']->body, 
    '#required' => TRUE,
  );


  $form['review_accepted'] = array(
    '#type' => 'fieldset',
    '#title' => t('Reviewer Accepted'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['review_accepted']['ra_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['review_accepted']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['review_accepted']['ra_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['review_accepted']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['review_accepted']['ra_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['review_accepted']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['review_accepted']['ra_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['review_accepted']->body, 
    '#required' => TRUE,
  );


  $form['review_completed'] = array(
    '#type' => 'fieldset',
    '#title' => t('Review Completed'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['review_completed']['rc_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['review_completed']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['review_completed']['rc_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['review_completed']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['review_completed']['rc_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['review_completed']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['review_completed']['rc_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['review_completed']->body, 
    '#required' => TRUE,
  );


  $form['revision_needed'] = array(
    '#type' => 'fieldset',
    '#title' => t('Revision Needed'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['revision_needed']['rn_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['revision_needed']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['revision_needed']['rn_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['revision_needed']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['revision_needed']['rn_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['revision_needed']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['revision_needed']['rn_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['revision_needed']->body, 
    '#required' => TRUE,
  );


  $form['reviewer_rereview'] = array(
    '#type' => 'fieldset',
    '#title' => t('Reviewer Notice - Rereview Requested'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['reviewer_rereview']['rrr_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['reviewer_rereview']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['reviewer_rereview']['rrr_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['reviewer_rereview']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['reviewer_rereview']['rrr_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['reviewer_rereview']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['reviewer_rereview']['rrr_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['reviewer_rereview']->body, 
    '#required' => TRUE,
  );


  $form['editor_rereview'] = array(
    '#type' => 'fieldset',
    '#title' => t('Editor Notice - Rereview Requested'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['editor_rereview']['er_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['editor_rereview']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['editor_rereview']['er_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['editor_rereview']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['editor_rereview']['er_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['editor_rereview']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['editor_rereview']['er_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['editor_rereview']->body, 
    '#required' => TRUE,
  );


  $form['cert_accepted'] = array(
    '#type' => 'fieldset',
    '#title' => t('Case Closed - Certified'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['cert_accepted']['cc_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['cert_accepted']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['cert_accepted']['cc_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['cert_accepted']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['cert_accepted']['cc_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['cert_accepted']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['cert_accepted']['cc_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['cert_accepted']->body, 
    '#required' => TRUE,
  );


  $form['cert_denied'] = array(
    '#type' => 'fieldset',
    '#title' => t('Case Closed - Denied'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,
  );

  $form['cert_denied']['cd_recipient'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient'), 
    '#default_value' => $messages['cert_denied']->recipient, 
    '#size' => 32,
    '#maxlength' => 32,
    '#required' => TRUE,
  );

  $form['cert_denied']['cd_recipient_type'] = array(
    '#type' => 'textfield', 
    '#title' => t('Recipient Type'), 
    '#default_value' => $messages['cert_denied']->recipient_type, 
    //'#options' => $options,
    '#size' => 4,
    '#maxlength' => 4,
    '#required' => TRUE,
  );

  $form['cert_denied']['cd_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('Subject'), 
    '#default_value' => $messages['cert_denied']->subject, 
    '#size' => 128,
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['cert_denied']['cd_body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Message Body'), 
    '#default_value' => $messages['cert_denied']->body, 
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save templates'),
  );

  return $form;
}

/**
 * Submit function for the notification template settings page
 */
function modelreview_admin_templates_submit($form, &$form_state) {
  $template_list = array(
    'cert_accepted' => 'cc_',
    'cert_denied' => 'cd_',
    'editor_rereview' => 'er_',
    'invite_reviewer' => 'ir_',
    'reviewer_rereview' => 'rrr_',
    'review_accepted' => 'ra_',
    'review_completed' => 'rc_',
    'review_declined' => 'rd_',
    'review_requested' => 'rr_',
    'revision_needed' => 'rn_',
    );

  foreach ($template_list as $template => $preview) {
    $query = db_update('modelreview_messages')
          ->fields(array(
            'recipient' => $form_state['values'][$preview . 'recipient'],
            'recipient_type' => $form_state['values'][$preview . 'recipient_type'],
            'subject' => $form_state['values'][$preview . 'subject'],
            'body' => $form_state['values'][$preview . 'body'],
          ))
          ->condition('name', $template)
          ->execute();
  }
}
