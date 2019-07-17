<?php

/**
 * @file
 */

/**
 *
 */
function page_2_validate_form(&$form, &$form_state) {
  if ($form_state['submitted'] == '1') {

    if (!$form_state['values']['data_type']) {
      form_set_error('data_type', 'Data Type: field is required.');
    }

    if (!$form_state['values']['study_type']) {
      form_set_error('study_type', 'Study Type: field is required.');
    }
  }
}
