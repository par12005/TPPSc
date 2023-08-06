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

  $doi = tpps_get_ajax_value($form_state, ['doi']);
  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#value' => !empty($doi) ? $org_number : NULL,
  );

  for ($i = 1; $i <= $org_number; $i++) {

    $form['organism']["$i"]['name'] = array(
      '#type' => 'textfield',
      '#title' => t("Species @num: *", array('@num' => $i)),
      '#autocomplete_path' => "tpps/autocomplete/species",
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
      ),
      // [VS]
      '#description' => 'Example: '
        . '<a href"#" class="tpps-suggestion">Arabidopsis thaliana</a>.',
      // [/VS]
    );
    $org = tpps_get_ajax_value($form_state, array('organism', $i, 'name'));
    $form['organism'][$i]['name']['#attributes']['value'] = $org ?? NULL;

    $form['organism']["$i"]['is_tree'] = array(
      '#type' => 'checkbox',
      '#title' => t('This species is a tree.'),
      '#default_value' => 1,
    );
  }

  return $form;
}
