<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_submit_all($accession) {

  $form_state = tpps_load_submission($accession);
  $form_state['status'] = 'Submission Job Running';
  tpps_update_submission($form_state, array('status' => 'Submission Job Running'));
  $transaction = db_transaction();

  try {
    $form_state = tpps_load_submission($accession);
    $uid = $form_state['submitting_uid'];
    $values = $form_state['saved_values'];
    $firstpage = $values[TPPS_PAGE_1];
    $form_state['file_rank'] = 0;
    $form_state['ids'] = array();

    $form_state['ids']['project_id'] = tpps_chado_insert_record('project', array(
      'name' => $firstpage['publication']['title'],
      'description' => $firstpage['publication']['abstract'],
    ));

    module_load_include('php', 'tpps', 'forms/submit/submit_all');
    tpps_tripal_entity_publish('Project', array($firstpage['publication']['title'], $form_state['ids']['project_id']));

    tppsc_submit_page_1($form_state);

    tppsc_submit_page_2($form_state);

    tppsc_submit_page_3($form_state);

    tppsc_submit_page_4($form_state);

    tpps_update_submission($form_state);

    tpps_file_parsing($accession);
    $form_state['status'] = 'Approved';
    tpps_update_submission($form_state, array('status' => 'Approved'));
  }
  catch (Exception $e) {
    $transaction->rollback();
    $form_state = tpps_load_submission($accession);
    $form_state['status'] = 'Pending Approval';
    tpps_update_submission($form_state, array('status' => 'Pending Approval'));
    watchdog_exception('tppsc', $e);
  }
}

/**
 *
 */
function tppsc_submit_page_1(&$form_state) {

  $project_id = $form_state['ids']['project_id'];
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

    for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++) {
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
  tpps_tripal_entity_publish('Publication', array($firstpage['publication']['title'], $publication_id));

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
    for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++) {
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
    $form_state['ids']['organism_ids'][$i] = tpps_chado_insert_record('organism', array(
      'genus' => $genus,
      'species' => $species,
      'infraspecific_name' => $infra,
    ));
    tpps_chado_insert_record('project_organism', array(
      'organism_id' => $form_state['ids']['organism_ids'][$i],
      'project_id' => $project_id,
    ));
    tpps_tripal_entity_publish('Organism', array("$genus $species", $form_state['ids']['organism_ids'][$i]));
  }
}

/**
 *
 */
function tppsc_submit_page_2(&$form_state) {

  $project_id = $form_state['ids']['project_id'];
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
function tppsc_submit_page_3(&$form_state) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_3($form_state);
}

/**
 *
 */
function tppsc_submit_page_4(&$form_state) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_4($form_state);
}
