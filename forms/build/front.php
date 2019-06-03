<?php

function tppsC_front_create_form(&$form, $form_state){
    
    global $base_url;
    global $user;
    
    if (isset($user->mail)){
        // Logged in
        $options_arr = array();
        $options_arr['new'] = 'Create new TPPSC Submission';
        
        $results = db_select('tpps_submission', 's')
          ->fields('s')
          ->condition('status', 'Incomplete')
          ->execute();
        
        foreach ($results as $item){
            $state = tpps_load_submission($item->accession);
            
            if ($state != NULL and isset($state['saved_values'][TPPS_PAGE_1]['publication']['title'])){
                $title = ($state['saved_values'][TPPS_PAGE_1]['publication']['title'] != NULL) ? $state['saved_values'][TPPS_PAGE_1]['publication']['title'] : "No Title";
                $tgdrC_id = $state['accession'];
                $options_arr["$tgdrC_id"] = "$title";
            }
            else {
                if (isset($state) and !isset($state['saved_values'][TPPS_PAGE_1])){
                    tpps_delete_submission($item->accession);
                }
            }
        }
        
        if (count($options_arr) > 1){
            // Has submissions.
            $form['accession'] = array(
              '#type' => 'select',
              '#title' => t('Would you like to load an old TPPSC submission, or create a new one?'),
              '#options' => $options_arr,
              '#default_value' => isset($form_state['saved_values']['frontpage']['accession']) ? $form_state['saved_values']['frontpage']['accession'] : 'new',
            );
        }
    }
    
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Continue to TPPSC'),
    );
    
    $prefix_text = 
"<div>
Welcome to TPPSC!<br><br>
If you would like to submit your data, you can click the button 'Continue to TPPSC' below!<br><br>
</div>";
    
    if (isset($form['accession'])){
        $form['accession']['#prefix'] = $prefix_text;
    }
    else {
        $form['Next']['#prefix'] = $prefix_text;
    }
    
    return $form;
}