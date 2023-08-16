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

  $form['data_type'] = $tpps_form['data_type'];
  $form['study_type'] = $tpps_form['study_type'];
  unset($form['study_type']['#ajax']);

  tpps_add_buttons($form, 'page_2');
  return $form;
}
