<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_page_2_create_form(&$form, $form_state) {

  if (isset($form_state['saved_values'][TPPS_PAGE_2])) {
    $values = $form_state['saved_values'][TPPS_PAGE_2];
  }
  else {
    $values = array();
  }

  $tpps_form = tpps_main(array(), $form_state);

  $form['study_location'] = $tpps_form['study_location'];
  $form['data_type'] = $tpps_form['data_type'];
  $form['study_type'] = $tpps_form['study_type'];
  unset($form['study_type']['#ajax']);

  $form['Back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#prefix' => '<div class="input-description">* : Required Field</div>',
  );

  $form['Save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}
