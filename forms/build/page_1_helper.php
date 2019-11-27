<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_organism(&$form, &$form_state) {
  $org_number = tpps_get_ajax_value($form_state, array('organism', 'number'), 1);

  $form['organism'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('<div class="fieldset-title">Organism information:</div>'),
    '#description' => t('Please provide the name(s) of the species included in this publication.'),
    '#collapsible' => TRUE,
    '#prefix' => '<div id="organism-wrapper">',
    '#suffix' => '</div>',
  );

  $form['organism']['add'] = array(
    '#type' => 'button',
    '#title' => t('Add Organism'),
    '#button_type' => 'button',
    '#value' => t('Add Organism'),
    '#name' => t('Add Organism'),
    '#ajax' => array(
      'wrapper' => 'organism-wrapper',
      'callback' => 'tppsc_organism_callback',
    ),
  );

  $form['organism']['remove'] = array(
    '#type' => 'button',
    '#title' => t('Remove Organism'),
    '#button_type' => 'button',
    '#value' => t('Remove Organism'),
    '#name' => t('Remove Organism'),
    '#ajax' => array(
      'wrapper' => 'organism-wrapper',
      'callback' => 'tppsc_organism_callback',
    ),
  );

  $doi = tpps_get_ajax_value($form_state, array('doi'));
  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#value' => !empty($doi) ? $org_number : NULL,
  );

  for ($i = 1; $i <= $org_number; $i++) {

    $form['organism']["$i"] = array(
      '#type' => 'textfield',
      '#title' => t("Species @num: *", array('@num' => $i)),
      '#autocomplete_path' => "species/autocomplete",
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
      ),
    );
    $org = tpps_get_ajax_value($form_state, array('organism', $i));
    $form['organism'][$i]['#attributes']['value'] = $org ?? NULL;
  }

  return $form;
}
