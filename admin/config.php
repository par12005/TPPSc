<?php

function tppsC_admin_settings($form, &$form_state){
    
    $authors = variable_get('tppsC_author_files_dir', 'tppsC_authors');
    $accession = variable_get('tppsC_accession_files_dir', 'tppsC_accession');
    $genotype = variable_get('tppsC_genotype_files_dir', 'tppsC_genotype');
    $phenotype = variable_get('tppsC_phenotype_files_dir', 'tppsC_phenotype');
    $cartogratree_env = variable_get('tppsC_cartogratree_env', FALSE);
    
    $form['tppsC_admin_email'] = array(
      '#type' => 'textfield',
      '#title' => t('TPPSC Admin Email Address'),
      '#default_value' => variable_get('tppsC_admin_email', 'treegenesdb@gmail.com'),
    );
    
    
    $form['tppsC_cartogratree_env'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use environmental layers from CartograTree'),
      '#default_value' => $cartogratree_env,
      '#description' => t("If CartograTree is installed, TPPSC can add an optional field to the environment section for environment layers, using the data pulled in through CartograTree.")
    );
    
    $form['layer_groups'] = array(
      '#type' => 'fieldset',
      '#title' => 'CartograTree Environmental Layer Groups:',
      '#description' => 'Please select which layer groups will contain environmental data that is relevant to TPPSC. TPPSC will use the selected groups to decide which layers to present as environmental options to the users.',
      '#states' => array(
        'visible' => array(
          ':input[name="tppsC_cartogratree_env"]' => array('checked' => TRUE)
        )
      )
    );
    
    $query = db_select('cartogratree_groups', 'g')
        ->fields('g', array('group_name', 'group_id'));
    
    $results = $query->execute();
    
    while (($result = $results->fetchObject())){
        
        $form['layer_groups']["tppsC_layer_group_{$result->group_id}"] = array(
          '#type' => 'checkbox',
          '#title' => $result->group_name,
          '#default_value' => variable_get("tppsC_layer_group_{$result->group_id}", FALSE),
        );
    }
    
    $form['tppsC_genotype_group'] = array(
      '#type' => 'textfield',
      '#title' => t('TPPSC Genotype max group'),
      '#default_value' => variable_get('tppsC_genotype_group', 10000),
      '#description' => 'Some genotype files are very large. TPPSC tries to submit as many genotype entries together as possible, in order to speed up the process of writing genotype data to the database. However, very large size entries can cause errors within the Tripal Job daemon. This number is the maximum number of genotype entries that may be submitted at once. Larger numbers will make the process faster, but are more likely to cause errors. Defaults to 10,000.',
    );
    
    $form['tppsC_local_genome_dir'] = array(
      '#type' => 'textfield',
      '#title' => t('Reference Genome file directory:'),
      '#default_value' => variable_get('tppsC_local_genome_dir', NULL),
      '#description' => 'The directory of local genome files on your web server. If left blank, tppsC will skip the searching for local genomes step in the tppsC genotype section. Local genome files should be organized according to the following structure: <br>[file directory]/[species code]/[version number]/[genome data] where: <br>&emsp;&emsp;[file directory] is the full path to the genome files provided above <br>&emsp;&emsp;[species code] is the 4-letter standard species code - this must match the species code entry in the "chado.organismprop" table<br>&emsp;&emsp;[version number] is the reference genome version, of the format "v#.#"<br>&emsp;&emsp;[genome data] is the actual reference genome files - these can be any format or structure<br>More information is available <a href="https://tppsC.rtfd.io/en/latest/config.html" target="blank">here</a>.',
    );
    
    $form['tppsC_author_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Author files:'),
      '#default_value' => $authors,
      '#description' => t("Currently points to " . drupal_realpath("public://$authors") . '.'),
      '#prefix' => t('<h1>File Upload locations</h1>' . 'All file locations are relative to the "public://" file stream. Your current "public://" file stream points to "' . drupal_realpath('public://') . '".<br><br>')
    );
    
    $form['tppsC_accession_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Tree Accession files:'),
      '#default_value' => $accession,
      '#description' => t("Currently points to " . drupal_realpath("public://$accession") . '.')
    );
    
    $form['tppsC_genotype_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Genotype files:'),
      '#default_value' => $genotype,
      '#description' => t("Currently points to " . drupal_realpath("public://$genotype") . '.')
    );
    
    $form['tppsC_phenotype_files_dir'] = array(
      '#type' => 'textfield',
      '#title' => t('Phenotype files:'),
      '#default_value' => $phenotype,
      '#description' => t("Currently points to " . drupal_realpath("public://$phenotype") . '.')
    );
    
    return system_settings_form($form);
}

function tppsC_admin_settings_validate($form, &$form_state){
    foreach ($form_state['values'] as $key => $value){
        if (substr($key, -10) == '_files_dir'){
            $location = "public://$value";
            if (!file_prepare_directory($location, FILE_CREATE_DIRECTORY)){
                form_set_error("$key", "Error: path must be valid and current user must have permissions to access that path.");
            }
        }
        elseif ($key == 'tppsC_admin_email'){
            if (!valid_email_address($value)){
                form_set_error("$key", "Error: please enter a valid email address.");
            }
        }
        elseif ($key == 'tppsC_cartogratree_env'){
            if (!empty($value) and !module_exists('cartogratree')){
                form_set_error("$key", "Error: The CartograTree module is not installed.");
            }
            elseif (!empty($value) and (!db_table_exists('cartogratree_groups') or !db_table_exists('cartogratree_layers'))){
                form_set_error("$key", "Error: TPPSC was unable to find the required CartograTree tables for environmental layers.");
            }
        }
    }
}
