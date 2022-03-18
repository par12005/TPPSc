<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_ajax_doi_callback(&$form, $form_state) {

  $value = $form['doi']['#value'];
  if ($value != "") {
    // check the tpps_submissions
    $tpps_submissions = chado_query("SELECT * FROM public.tpps_submission;");
    $found_doi = false;
    $found_doi_accession = "";
    foreach ($tpps_submissions as $submission_row) {
      $submission_state = unserialize($submission_row->submission_state);
      if(strtolower($value) == strtolower($submission_state['saved_values']['1']['doi'])) {
        $found_doi = true;
        $found_doi_accession = $submission_row->accession;
        break;
      }
      // dpm($submission_state['saved_values']['1']['doi']);
    }
    if($found_doi) {
      form_set_error('doi', "WARNING: DOI is already used by " . $found_doi_accession);
      $form['doi']['#prefix'] = "<div style='text-align: right; color: red;'>WARNING: DOI is already used by " . $found_doi_accession . "</div>";
    }
  }

  // dpm($form);

  $form['doi']['#suffix'] = "<div></div>";
  return $form;
}

/**
 *
 */
function tppsc_organism_callback($form, &$form_state) {

  return $form['organism'];
}
