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
    $options_arr = [
      'new' => 'Create new TPPSC Submission',
      'placeholder1' => '------------------------- YOUR STUDIES -------------------------',
    ];
    $options_arr = $options_arr + tpps_submission_get_accession_list([
      ['status', 'Incomplete', '='],
      ['uid', $user->uid, '='],
    ]);
    $options_arr['placeholder2'] = '-------------------- OTHER USER STUDIES --------------------';
    $options_arr = $options_arr + tpps_submission_get_accession_list([
      ['status', 'Incomplete'],
      ['uid', $user->uid, '<>'],
    ]);

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
      . 'ORDER BY accession DESC;');

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

  $module_path = drupal_get_path('module', 'tpps');
  $form['#attached']['js'][] = $module_path . TPPS_JS_PATH;
  $form['#attached']['css'][] = $module_path . TPPS_CSS_PATH;

  return $form;
}
