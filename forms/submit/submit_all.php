<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_submit_all($accession) {

  $transaction = db_transaction();

  try {
    $form_state = tpps_load_submission($accession);
    $uid = $form_state['submitting_uid'];
    $values = $form_state['saved_values'];
    $firstpage = $values[TPPS_PAGE_1];
    $file_rank = 0;

    $project_id = tpps_chado_insert_record('project', array(
      'name' => $firstpage['publication']['title'],
      'description' => $firstpage['publication']['abstract'],
    ));

    $organism_ids = tppsc_submit_page_1($form_state, $project_id, $file_rank);

    tppsc_submit_page_2($form_state, $project_id, $file_rank);

    tppsc_submit_page_3($form_state, $project_id, $file_rank, $organism_ids);

    tppsc_submit_page_4($form_state, $project_id, $file_rank, $organism_ids);

    tpps_update_submission($form_state);

    tpps_file_parsing($accession);
    $form_state['status'] = 'Approved'
    tpps_update_submission($form_state, array('status' => 'Approved'));
  }
  catch (Exception $e) {
    $transaction->rollback();
    watchdog_exception('tppsc', $e);
  }
}

/**
 *
 */
function tppsc_submit_page_1(&$form_state, $project_id, &$file_rank) {

  $dbxref_id = $form_state['dbxref_id'];
  $firstpage = $form_state['saved_values'][TPPS_PAGE_1];

  tpps_chado_insert_record('project_dbxref', array(
    'project_id' => $project_id,
    'dbxref_id' => $dbxref_id,
  ));

  tpps_chado_insert_record('contact', array(
    'name' => $firstpage['primaryAuthor'],
    'type_id' => array(
      'cv_id' => array(
        'name' => 'tripal_contact',
      ),
      'name' => 'Person',
      'is_obsolete' => 0,
    ),
  ));

  $author_string = $firstpage['primaryAuthor'];
  if (!$firstpage['publication']['secondaryAuthors']['check'] and $firstpage['publication']['secondaryAuthors']['number'] != 0) {

    for ($i = 0; $i < $firstpage['publication']['secondaryAuthors']['number']; $i++) {
      tpps_chado_insert_record('contact', array(
        'name' => $firstpage['publication']['secondaryAuthors'][$i],
        'type_id' => array(
          'cv_id' => array(
            'name' => 'tripal_contact',
          ),
          'name' => 'Person',
          'is_obsolete' => 0,
        ),
      ));
      $author_string .= "; {$firstpage['publication']['secondaryAuthors'][$i]}";
    }
  }

  $publication_id = tpps_chado_insert_record('pub', array(
    'title' => $firstpage['publication']['title'],
    'series_name' => $firstpage['publication']['journal'],
    'type_id' => array(
      'cv_id' => array(
        'name' => 'tripal_pub',
      ),
      'name' => 'Journal Article',
      'is_obsolete' => 0,
    ),
    'pyear' => $firstpage['publication']['year'],
    'uniquename' => "$author_string {$firstpage['publication']['title']}. {$firstpage['publication']['journal']}; {$firstpage['publication']['year']}",
  ));

  tpps_chado_insert_record('project_pub', array(
    'project_id' => $project_id,
    'pub_id' => $publication_id,
  ));

  $names = explode(" ", $firstpage['primaryAuthor']);
  $first_name = $names[0];
  $last_name = implode(" ", array_slice($names, 1));

  tpps_chado_insert_record('pubauthor', array(
    'pub_id' => $publication_id,
    'rank' => '0',
    'surname' => $last_name,
    'givennames' => $first_name,
  ));

  if (!$firstpage['publication']['secondaryAuthors']['check'] and $firstpage['publication']['secondaryAuthors']['number'] != 0) {
    for ($i = 0; $i < $firstpage['publication']['secondaryAuthors']['number']; $i++) {
      $names = explode(" ", $firstpage['publication']['secondaryAuthors'][$i]);
      $first_name = $names[0];
      $last_name = implode(" ", array_slice($names, 1));
      tpps_chado_insert_record('pubauthor', array(
        'pub_id' => $publication_id,
        'rank' => "$i",
        'surname' => $last_name,
        'givennames' => $first_name,
      ));
    }
  }

  $organism_ids = array();
  $organism_number = $firstpage['organism']['number'];

  for ($i = 1; $i <= $organism_number; $i++) {
    $parts = explode(" ", $firstpage['organism'][$i]);
    $genus = $parts[0];
    $species = implode(" ", array_slice($parts, 1));
    if (isset($parts[2]) and ($parts[2] == 'var.' or $parts[2] == 'subsp.')) {
      $infra = implode(" ", array_slice($parts, 2));
    }
    else {
      $infra = NULL;
    }
    $organism_ids[$i] = tpps_chado_insert_record('organism', array(
      'genus' => $genus,
      'species' => $species,
      'infraspecific_name' => $infra,
    ));
    tpps_chado_insert_record('project_organism', array(
      'organism_id' => $organism_ids[$i],
      'project_id' => $project_id,
    ));
  }
  return $organism_ids;
}

/**
 *
 */
function tppsc_submit_page_2(&$form_state, $project_id, &$file_rank) {

  $secondpage = $form_state['saved_values'][TPPS_PAGE_2];

  tpps_chado_insert_record('projectprop', array(
    'project_id' => $project_id,
    'type_id' => array(
      'cv_id' => array(
        'name' => 'local',
      ),
      'name' => 'association_results_type',
      'is_obsolete' => 0,
    ),
    'value' => $secondpage['data_type'],
  ));

  $studytype_options = array(
    0 => '- Select -',
    1 => 'Natural Population (Landscape)',
    2 => 'Growth Chamber',
    3 => 'Greenhouse',
    4 => 'Experimental/Common Garden',
    5 => 'Plantation',
  );

  tpps_chado_insert_record('projectprop', array(
    'project_id' => $project_id,
    'type_id' => array(
      'cv_id' => array(
        'name' => 'local',
      ),
      'name' => 'study_type',
      'is_obsolete' => 0,
    ),
    'value' => $studytype_options[$secondpage['study_type']],
  ));
}

/**
 *
 */
function tppsc_submit_page_3(&$form_state, $project_id, &$file_rank, $organism_ids) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_3($form_state, $project_id, $file_rank, $organism_ids);
}

/**
 *
 */
function tppsc_submit_page_4(&$form_state, $project_id, &$file_rank, $organism_ids) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_4($form_state, $project_id, $file_rank, $organism_ids);
}
