<?php

function page_2_validate_form(&$form, &$form_state){
    if ($form_state['submitted'] == '1'){
        
        $form_values = $form_state['values'];
        $location_type = $form_values['studyLocation']['type'];
        $coordinates = $form_values['studyLocation']['coordinates'];
        $custom_location = $form_values['studyLocation']['custom'];
        $data_type = $form_values['dataType'];
        $study_type = $form_values['studyType'];
        
        if ($location_type == '0'){
            form_set_error('studyLocation][type', 'Location Format: field is required.');
        }
        elseif ($location_type == '1' or $location_type == '3' or $location_type == '4'){
            if ($coordinates == ''){
                form_set_error('studyLocation][coordinates', 'Coordinates: field is required.');
            }
        }
        else{
            if($custom_location == ''){
                form_set_error('studyLocation][custom', 'Custom Location: field is required.');
            }
        }

        if (!$data_type){
            form_set_error('dataType', 'Data Type: field is required.');
        }
        
        if (!$study_type){
            form_set_error('studyType', 'Study Type: field is required.');
        }
    }
}
