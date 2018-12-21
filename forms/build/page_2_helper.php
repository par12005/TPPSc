<?php

function studyLocation(&$form, $values, &$form_state){

    $form['studyLocation'] = array(
      '#type' => 'fieldset',
      '#title' => t('<div class="fieldset-title">Study Location:</div>'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
    );

    $form['studyLocation']['type'] = array(
      '#type' => 'select',
      '#title' => t('Coordinate Projection: *'),
      '#options' => array(
        0 => '- Select -',
        1 => 'WGS 84',
        3 => 'NAD 83',
        4 => 'ETRS 89',
        2 => 'Custom Location (street address)'
      ),
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('Please select a Coordinate Projection, or select "Custom Location", to enter a custom study location')
      )
    );

    $form['studyLocation']['coordinates'] = array(
      '#type' => 'textfield',
      '#title' => t('Coordinates: *'),
      '#states' => array(
        'visible' => array(
          array(
            array(':input[name="studyLocation[type]"]' => array('value' => '1')),
            'or',
            array(':input[name="studyLocation[type]"]' => array('value' => '3')),
            'or',
            array(':input[name="studyLocation[type]"]' => array('value' => '4')),
          )
        ),
      ),
      '#description' => 
'Accepted formats: <br>
Degrees Minutes Seconds: 41° 48\' 27.7" N, 72° 15\' 14.4" W<br>
Degrees Decimal Minutes: 41° 48.462\' N, 72° 15.24\' W<br>
Decimal Degrees: 41.8077° N, 72.2540° W<br>'
    );

    $form['studyLocation']['custom'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom Location: *'),
      '#states' => array(
        'visible' => array(
          ':input[name="studyLocation[type]"]' => array('value' => '2')
        ),
      )
    );

    $form['studyLocation']['map-button'] = array(
      '#type' => 'button',
      '#title' => 'Click here to update map',
      '#value' => 'Click here to update map',
      '#button_type' => 'button',
      '#executes_submit_callback' => FALSE,
      '#prefix' => '<div id="page_2_map">',
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => 'page_2_map_ajax',
        'wrapper' => 'page_2_map',
      )
    );

    if (isset($form_state['values']['studyLocation'])){
        $location = $form_state['values']['studyLocation'];
    }
    elseif (isset($form_state['saved_values'][PAGE_2]['studyLocation'])){
        $location = $form_state['saved_values'][PAGE_2]['studyLocation'];
    }

    if (isset($location)){
        if (isset($location['coordinates'])){
            $raw_coordinate = $location['coordinates'];
            $standard_coordinate = tpps_standard_coord($raw_coordinate);
        }

        if (isset($location['type']) and $location['type'] == '2' and isset($location['custom'])){
            $query = $location['custom'];
        }
        elseif (isset($location['type']) and $location['type'] != '0'){
            if ($standard_coordinate){
                $query = $standard_coordinate;
            }
            else {
                dpm('Invalid coordinates');
            }
        }

        if (isset($query) and $query != ""){
            $form['studyLocation']['map-button']['#suffix'] = "
            <br><iframe
              width=\"100%\"
              height=\"450\"
              frameborder=\"0\" style=\"border:0\"
              src=\"https://www.google.com/maps?q=$query&output=embed&key=AIzaSyDkeQ6KN6HEBxrIoiSCrCHFhIbipycqouY&z=5\" allowfullscreen>
            </iframe></div>";
        }
    }

    return $form;
}
