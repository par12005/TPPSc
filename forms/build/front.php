<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_front_create_form(&$form, $form_state) {

  global $base_url;
  global $user;

  if (isset($user->mail)) {
    // Logged in.
    $options_arr = array();
    $options_arr['new'] = 'Create new TPPSC Submission';
    // dpm($user->uid);
    $and = db_and()
      ->condition('status', 'Incomplete')
      ->condition('uid', $user->uid);
    $results = db_select('tpps_submission', 's')
      ->fields('s')
      ->condition($and)
      ->execute();

    foreach ($results as $item) {
      $state = tpps_load_submission($item->accession);
      if (!empty($state['tpps_type']) and $state['tpps_type'] == 'tppsc') {
        if ($state != NULL and isset($state['saved_values'][TPPS_PAGE_1]['publication']['title'])) {
          $title = ($state['saved_values'][TPPS_PAGE_1]['publication']['title'] != NULL) ? $state['saved_values'][TPPS_PAGE_1]['publication']['title'] : "No Title";
          $tgdrC_id = $state['accession'];
          if (strlen($title) > 97) {
            $title = substr($title, 0, 97) . '...';
          }
          $options_arr["$tgdrC_id"] = $title;
        }
        else {
          if (isset($state) and !isset($state['saved_values'][TPPS_PAGE_1])) {
            tpps_delete_submission($item->accession, FALSE);
          }
        }
      }
    }

    if (count($options_arr) > 1) {
      // Has submissions.
      $form['accession'] = array(
        '#type' => 'select',
        '#title' => t('Would you like to load an old TPPSC submission, or create a new one?'),
        '#options' => $options_arr,
        '#default_value' => isset($form_state['saved_values']['frontpage']['accession']) ? $form_state['saved_values']['frontpage']['accession'] : 'new',
      );
    }

    $form['use_old_tgdr'] = array(
      '#type' => 'checkbox',
      '#title' => t('I would like to use an existing TGDR number'),
    );

    $tgdr_options = array('- Select -');

    // $tgdr_query = chado_query('SELECT dbxref_id, accession '
    //   . 'FROM chado.dbxref '
    //   . 'WHERE accession LIKE \'TGDR%\' '
    //     . 'AND accession NOT IN (SELECT accession FROM tpps_submission) '
    //   . 'ORDER BY accession;');

    $tgdr_query = chado_query('SELECT dbxref_id, accession '
      . 'FROM chado.dbxref '
      . 'WHERE accession LIKE \'TGDR%\' '
      . 'ORDER BY accession;');      

    foreach ($tgdr_query as $item) {
      $tgdr_options[$item->dbxref_id] = $item->accession;
    }

    $form['old_tgdr'] = array(
      '#type' => 'select',
      '#title' => t('Existing TGDR number'),
      '#options' => $tgdr_options,
      '#states' => array(
        'visible' => array(
          ':input[name="use_old_tgdr"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Continue to TPPSC'),
  );

  $prefix_text = "<div>Welcome to TPPSC!<br><br>"
    . "If you would like to submit your data, you can click the button 'Continue to TPPSC' below!<br><br>"
    . "</div>";

  if (isset($form['accession'])) {
    $form['accession']['#prefix'] = $prefix_text;
  }
  else {
    $form['Next']['#prefix'] = $prefix_text;
  }

  return $form;
}
