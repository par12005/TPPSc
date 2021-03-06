<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_submit_email($form, $form_state) {

  global $user;
  global $base_url;
  $admin = variable_get('tpps_admin_email', "treegenesdb@gmail.com");

  $params = array();

  $to = $user->mail;
  $from = variable_get('site_mail', '');
  $params['subject'] = "TPPSC Submission Received: {$form_state['saved_values'][TPPS_PAGE_1]['publication']['title']}";

  $study_options = array(
    0 => '- Select -',
    1 => 'Natural Population (Landscape)',
    2 => 'Growth Chamber',
    3 => 'Greenhouse',
    4 => 'Experimental/Common Garden',
    5 => 'Plantation',
  );

  $params['author'] = $form_state['saved_values'][TPPS_PAGE_1]['primaryAuthor'];
  $params['title'] = $form_state['saved_values'][TPPS_PAGE_1]['publication']['title'];
  $params['organisms'] = $form_state['saved_values'][TPPS_PAGE_1]['organism'];
  $params['study_type'] = $study_options[$form_state['saved_values'][TPPS_PAGE_2]['study_type']];
  $params['data_type'] = $form_state['saved_values'][TPPS_PAGE_2]['data_type'];
  $params['accession'] = $form_state['accession'];
  $params['base_url'] = $base_url;
  $params['body'] = '';

  $params['headers'][] = 'MIME-Version: 1.0';
  $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';

  drupal_mail('tppsc', 'user_recieved', $to, user_preferred_language($user), $params, $from, TRUE);

  $params['user_mail'] = $user->mail;

  drupal_mail('tppsc', 'admin_recieved', $admin, user_preferred_language($user), $params, $from, TRUE);

  return;
}
