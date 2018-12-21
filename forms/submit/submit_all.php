<?php

function tppsC_submit_all(&$form_state){
    
    return;
    
    $values = $form_state['saved_values'];
    $firstpage = $values[TPPSC_PAGE_1];
    $file_rank = 0;

    $project_id = tppsC_create_record('project', array(
      'name' => $firstpage['publication']['title'],
      'description' => $firstpage['publication']['abstract']
    ));

    $organism_ids = tppsC_submit_page_1($form_state, $project_id, $file_rank);
    
    tppsC_submit_page_2($form_state, $project_id, $file_rank);
    
    tppsC_submit_page_3($form_state, $project_id, $file_rank, $organism_ids);
    
    tppsC_submit_page_4($form_state, $project_id, $file_rank, $organism_ids);
    
    //for simplicity and efficiency, all fourth page submissions take place in the TPPSC File Parsing Tripal Job
}

function tppsC_submit_page_1(&$form_state, $project_id, &$file_rank){
    global $user;
    
    $dbxref_id = $form_state['dbxref_id'];
    $firstpage = $form_state['saved_values'][TPPSC_PAGE_1];

    tppsC_create_record('project_dbxref', array(
      'project_id' => $project_id,
      'dbxref_id' => $dbxref_id
    ));

    tppsC_create_record('contact', array(
      'name' => $firstpage['primaryAuthor'],
      'type_id' => '71',
    ));

    $author_string = $firstpage['primaryAuthor'];
    if ($firstpage['publication']['secondaryAuthors']['check'] == 0 and $firstpage['publication']['secondaryAuthors']['number'] != 0){

        for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++){
            tppsC_create_record('contact', array(
              'name' => $firstpage['publication']['secondaryAuthors'][$i],
              'type_id' => '71'
            ));
            $author_string .= "; {$firstpage['publication']['secondaryAuthors'][$i]}";
        }
    }
    elseif ($firstpage['publication']['secondaryAuthors']['check'] != 0){
        tppsC_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '2836',
          'value' => file_create_url(file_load($firstpage['publication']['secondaryAuthors']['file'])->uri),
          'rank' => $file_rank
        ));
        
        $file = file_load($firstpage['publication']['secondaryAuthors']['file']);
        $location = drupal_realpath($file->uri);
        $content = tppsC_parse_xlsx($location);
        $column_vals = $firstpage['publication']['secondaryAuthors']['file-columns'];
        
        foreach ($column_vals as $col => $val){
            if ($val == '1'){
                $first_name = $col;
            }
            if ($val == '2'){
                $last_name = $col;
            }
            if ($val == '3'){
                $middle_initial = $col;
            }
        }
        
        for ($i = 0; $i < count($content) - 1; $i++){
            tppsC_create_record('contact', array(
              'name' => "{$content[$i][$last_name]}, {$content[$i][$first_name]} {$content[$i][$middle_initial]}",
              'type_id' => '71'
            ));
            $author_string .= "; {$content[$i][$last_name]}, {$content[$i][$first_name]} {$content[$i][$middle_initial]}";
        }
        $file->status = FILE_STATUS_PERMANENT;
        $file = file_save($file);
        $file_rank++;
    }
    
    $publication_id = tppsC_create_record('pub', array(
      'title' => $firstpage['publication']['title'],
      'series_name' => $firstpage['publication']['journal'],
      'type_id' => '229',
      'pyear' => $firstpage['publication']['year'],
      'uniquename' => "$author_string {$firstpage['publication']['title']}. {$firstpage['publication']['journal']}; {$firstpage['publication']['year']}"
    ));

    tppsC_create_record('project_pub', array(
      'project_id' => $project_id,
      'pub_id' => $publication_id
    ));

    tppsC_create_record('contact', array(
      'name' => $firstpage['organization'],
      'type_id' => '72',
    ));

    $names = explode(" ", $firstpage['primaryAuthor']);
    $first_name = $names[0];
    $last_name = implode(" ", array_slice($names, 1));

    tppsC_create_record('pubauthor', array(
      'pub_id' => $publication_id,
      'rank' => '0',
      'surname' => $last_name,
      'givennames' => $first_name
    ));
    
    if ($firstpage['publication']['secondaryAuthors']['check'] == 0 and $firstpage['publication']['secondaryAuthors']['number'] != 0){
        for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++){
            $names = explode(" ", $firstpage['publication']['secondaryAuthors'][$i]);
            $first_name = $names[0];
            $last_name = implode(" ", array_slice($names, 1));
            tppsC_create_record('pubauthor', array(
              'pub_id' => $publication_id,
              'rank' => "$i",
              'surname' => $last_name,
              'givennames' => $first_name
            ));
        }
    }
    elseif ($firstpage['publication']['secondaryAuthors']['check'] != 0){
        
        $file = file_load($firstpage['publication']['secondaryAuthors']['file']);
        $location = drupal_realpath($file->uri);
        $content = tppsC_parse_xlsx($location);
        $column_vals = $firstpage['publication']['secondaryAuthors']['file-columns'];
        $groups = $firstpage['publication']['secondaryAuthors']['file-groups'];
        
        if (!empty($firstpage['publication']['secondaryAuthors']['file-no-header'])){
            tppsC_content_no_header($content);
        }
        
        $first_name = $groups['First Name']['1'];
        $last_name = $groups['Last Name']['2'];
        
        foreach ($column_vals as $col => $val){
            if ($val == '3'){
                $middle_initial = $col;
                break;
            }
        }
        
        for ($i = 0; $i < count($content) - 1; $i++){
            $rank = $i + 1;
            tppsC_create_record('pubauthor', array(
              'pub_id' => $publication_id,
              'rank' => "$rank",
              'surname' => $content[$i][$last_name],
              'givennames' => $content[$i][$first_name] . " " . $content[$i][$middle_initial]
            ));
        }
    }

    $organism_ids = array();
    $organism_number = $firstpage['organism']['number'];
    
    for ($i = 1; $i <= $organism_number; $i++){
        $parts = explode(" ", $firstpage['organism'][$i]);
        $genus = $parts[0];
        $species = implode(" ", array_slice($parts, 1));
        if (isset($parts[2]) and ($parts[2] == 'var.' or $parts[2] == 'subsp.')){
            $infra = implode(" ", array_slice($parts, 2));
        }
        else {
            $infra = NULL;
        }
        $organism_ids[$i] = tppsC_create_record('organism', array(
          'genus' => $genus,
          'species' => $species,
          'infraspecific_name' => $infra
        ));
        tppsC_create_record('project_organism', array(
          'organism_id' => $organism_ids[$i],
          'project_id' => $project_id,
        ));
    }
    return $organism_ids;
}

function tppsC_submit_page_2(&$form_state, $project_id, &$file_rank){
    
    $secondpage = $form_state['saved_values'][TPPSC_PAGE_2];

    if ($secondpage['studyLocation']['type'] !== '2'){
        $standard_coordinate = explode(',', tppsC_standard_coord($secondpage['studyLocation']['coordinates']));
        $latitude = $standard_coordinate[0];
        $longitude = $standard_coordinate[1];
        
        tppsC_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '54718',    //this cvterm id was created custom for TG. Chado may have one, but I was unable to find it. 
          'value' => $latitude
        ));
        
        tppsC_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '54717',    //this cvterm id was created custom for TG. Chado may have one, but I was unable to find it. 
          'value' => $longitude
        ));
    }
    else{
        $location = $secondpage['studyLocation']['custom'];
        
        tppsC_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '127998',    //this cvterm id was created custom for TG. Chado may have one, but I was unable to find it. 
          'value' => $location
        ));
    }
    
    $datatype = $secondpage['dataType'];
    
    tppsC_create_record('projectprop', array(
      'project_id' => $project_id,
      'type_id' => '54740',
      'value' => $datatype
    ));
    
    $studytype_options = array(
      0 => '- Select -',
      1 => 'Natural Population (Landscape)',
      2 => 'Growth Chamber',
      3 => 'Greenhouse',
      4 => 'Experimental/Common Garden',
      5 => 'Plantation',
    );
    
    $study_type = $studytype_options[$secondpage['studyType']];
    
    tppsC_create_record('projectprop', array(
      'project_id' => $project_id,
      'type_id' => '128021',    //this cvterm id was created custom for TG. Chado may have one, but I was unable to find it.
      'value' => $study_type
    ));
}

function tppsC_submit_page_3(&$form_state, $project_id, &$file_rank, $organism_ids){
    
    $firstpage = $form_state['saved_values'][TPPSC_PAGE_1];
    $thirdpage = $form_state['saved_values'][TPPSC_PAGE_3];
    $organism_number = $firstpage['organism']['number'];
    
    $stock_ids = array();
    
    if ($organism_number == '1' or $thirdpage['tree-accession']['check'] == 0){
        //single file
        tppsC_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '2836',
          'value' => file_create_url(file_load($thirdpage['tree-accession']['file'])->uri),
          'rank' => $file_rank
        ));
        
        $file = file_load($thirdpage['tree-accession']['file']);
        $location = drupal_realpath($file->uri);
        $content = tppsC_parse_xlsx($location);
        $column_vals = $thirdpage['tree-accession']['file-columns'];
        $groups = $thirdpage['tree-accession']['file-groups'];
        
        foreach ($column_vals as $col => $val){
            if ($val == '8'){
                $county_col_name = $col;
            }
            if ($val == '9'){
                $district_col_name = $col;
            }
        }
        
        $id_col_accession_name = $groups['Tree Id']['1'];
        
        if ($organism_number == '1'){
            //only one species
            for ($i = 0; $i < count($content) - 1; $i++){
                $tree_id = $content[$i][$id_col_accession_name];
                $stock_ids[$tree_id] = tppsC_create_record('stock', array(
                  'uniquename' => t($tree_id),
                  'type_id' => '2824',
                  'organism_id' => $organism_ids[1],
                ));
            }
        }
        else {
            //multiple species in one tree accession file -> users must define species and genus columns
            
            //get genus/species column
            if ($groups['Genus and Species']['#type'] == 'separate'){
                $genus_col_name = $groups['Genus and Species']['6'];
                $species_col_name = $groups['Genus and Species']['7'];
            }
            else {
                $org_col_name = $groups['Genus and Species']['10'];
            }
            
            //parse file
            for ($i = 0; $i < count($content) - 1; $i++){
                $tree_id = $content[$i][$id_col_accession_name];
                for ($j = 1; $j <= $organism_number; $j++){
                    //match genus and species to genus and species given on page 1
                    if ($groups['Genus and Species']['#type'] == 'separate'){
                        $genus_full_name = "{$content[$i][$genus_col_name]} {$content[$i][$species_col_name]}";
                    }
                    else {
                        $genus_full_name = "{$content[$i][$org_col_name]}";
                    }
                    
                    if ($firstpage['organism'][$j] == $genus_full_name){
                        //obtain organism id from matching species
                        $id = $organism_ids[$j];
                        break;
                    }
                }
                
                //create record with the new id
                $stock_ids[$tree_id] = tppsC_create_record('stock', array(
                  'uniquename' => t($tree_id),
                  'type_id' => '2824',
                  'organism_id' => $id,
                ));
            }
        }
        
        if ($groups['Location (latitude/longitude or country/state)']['#type'] == 'gps'){
            $lat_name = $groups['Location (latitude/longitude or country/state)']['4'];
            $long_name = $groups['Location (latitude/longitude or country/state)']['5'];
            
            for ($i = 0; $i < count($content) - 1; $i++){
                $tree_id = $content[$i][$id_col_accession_name];
                $stock_id = $stock_ids[$tree_id];
                
                tppsC_create_record('stockprop', array(
                  'stock_id' => $stock_id,
                  'type_id' => '54718',
                  'value' => $content[$i][$lat_name]
                ));
                
                tppsC_create_record('stockprop', array(
                  'stock_id' => $stock_id,
                  'type_id' => '54717',
                  'value' => $content[$i][$long_name]
                ));
            }
        }
        else {
            $country_col_name = $groups['Location (latitude/longitude or country/state)']['2'];
            $state_col_name = $groups['Location (latitude/longitude or country/state)']['3'];
            
            for ($i = 0; $i < count($content) - 1; $i++){
                $tree_id = $content[$i][$id_col_accession_name];
                $stock_id = $stock_ids[$tree_id];
                
                tppsC_create_record('stockprop', array(
                  'stock_id' => $stock_id,
                  'type_id' => '128162',
                  'value' => $content[$i][$country_col_name]
                ));
                
                tppsC_create_record('stockprop', array(
                  'stock_id' => $stock_id,
                  'type_id' => '128947',
                  'value' => $content[$i][$state_col_name]
                ));
                
                if (isset($county_col_name)){
                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_id,
                      'type_id' => '128946',
                      'value' => $content[$i][$county_col_name]
                    ));
                }
                
                if (isset($district_col_name)){
                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_id,
                      'type_id' => '128945',
                      'value' => $content[$i][$district_col_name]
                    ));
                }
            }
        }
        
        $file->status = FILE_STATUS_PERMANENT;
        $file = file_save($file);
        $file_rank++;
    }
    else {
        //multiple files, sorted by species
        for($i = 1; $i <= $organism_number; $i++){
            tppsC_create_record('projectprop', array(
              'project_id' => $project_id,
              'type_id' => '2836',
              'value' => drupal_realpath(file_load($thirdpage['tree-accession']["species-$i"]['file'])->uri),
              'rank' => $file_rank
            ));
            
            $file = file_load($thirdpage['tree-accession']["species-$i"]['file']);
            $location = drupal_realpath($file->uri);
            $content = tppsC_parse_xlsx($location);
            $column_vals = $thirdpage['tree-accession']["species-$i"]['file-columns'];
            $groups = $thirdpage['tree-accession']["species-$i"]['file-groups'];
            
            $id_col_accession_name = $groups['Tree Id']['1'];

            foreach ($column_vals as $col => $val){
                if ($val == '8'){
                    $county_col_name = $col;
                }
                if ($val == '9'){
                    $district_col_name = $col;
                }
            }

            for ($j = 0; $j < count($content) - 1; $j++){
                $tree_id = $content[$j][$id_col_accession_name];
                $stock_ids[$tree_id] = tppsC_create_record('stock', array(
                  'uniquename' => t($tree_id),
                  'type_id' => '2824',
                  'organism_id' => $organism_ids[$i],
                ));
                
                if ($groups['Location (latitude/longitude or country/state)']['#type'] == 'gps'){
                    $lat_name = $groups['Location (latitude/longitude or country/state)']['4'];
                    $long_name = $groups['Location (latitude/longitude or country/state)']['5'];
                    
                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_ids[$tree_id],
                      'type_id' => '54718',
                      'value' => $content[$j][$lat_name]
                    ));

                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_ids[$tree_id],
                      'type_id' => '54717',
                      'value' => $content[$j][$long_name]
                    ));
                }
                else {
                    $country_col_name = $groups['Location (latitude/longitude or country/state)']['2'];
                    $state_col_name = $groups['Location (latitude/longitude or country/state)']['3'];
                    
                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_id,
                      'type_id' => '128162',
                      'value' => $content[$j][$country_col_name]
                    ));

                    tppsC_create_record('stockprop', array(
                      'stock_id' => $stock_id,
                      'type_id' => '128947',
                      'value' => $content[$j][$state_col_name]
                    ));

                    if (isset($county_col_name)){
                        tppsC_create_record('stockprop', array(
                          'stock_id' => $stock_id,
                          'type_id' => '128946',
                          'value' => $content[$j][$county_col_name]
                        ));
                    }

                    if (isset($district_col_name)){
                        tppsC_create_record('stockprop', array(
                          'stock_id' => $stock_id,
                          'type_id' => '128945',
                          'value' => $content[$j][$district_col_name]
                        ));
                    }
                }
            }

            $file->status = FILE_STATUS_PERMANENT;
            $file = file_save($file);
            $file_rank++;
        }
    }
    
    foreach ($stock_ids as $tree_id => $stock_id){
        tppsC_create_record('project_stock', array(
          'stock_id' => $stock_id,
          'project_id' => $project_id
        ));
    }
    
    $form_state['file_rank'] = $file_rank;
    
}

function tppsC_submit_page_4(&$form_state, $project_id, &$file_rank, $organism_ids){
    $fourthpage = $form_state['saved_values'][TPPSC_PAGE_4];
    $organism_number = $form_state['saved_values'][TPPSC_PAGE_1]['organism']['number'];
    
    for ($i = 1; $i <= $organism_number; $i++){
        if (isset($fourthpage["organism-$i"]['genotype'])){
            $ref_genome = $fourthpage["organism-$i"]['genotype']['ref-genome'];
            
            if ($ref_genome === 'url' or $ref_genome === 'manual' or $ref_genome === 'manual2'){
                //create job for tripal fasta importer
                $class = 'FASTAImporter';
                tripal_load_include_importer_class($class);

                $fasta = $fourthpage["organism-$i"]['genotype']['tripal_fasta'];

                $file_upload = isset($fasta['file']['file_upload']) ? trim($fasta['file']['file_upload']) : 0;
                $file_existing = isset($fasta['file']['file_upload_existing']) ? trim($fasta['file']['file_upload_existing']) : 0;
                $file_remote = isset($fasta['file']['file_remote']) ? trim($fasta['file']['file_remote']) : 0;
                $analysis_id = $fasta['analysis_id'];
                $seqtype = $fasta['seqtype'];
                $organism_id = $organism_ids[$i];
                $re_accession = $fasta['db']['re_accession'];
                $db_id = $fasta['db']['db_id'];

                $run_args = array(
                  'importer_class' => $class,
                  'file_remote' => $file_remote,
                  'analysis_id' => $analysis_id,
                  'seqtype' => $seqtype,
                  'organism_id' => $organism_id,
                  'method' => '2',
                  'match_type' => '0',
                  're_name' => '',
                  're_uname' => '',
                  're_accession' => $re_accession,
                  'db_id' => $db_id,
                  'rel_type' => '',
                  're_subject' => '',
                  'parent_type' => '',
                );

                $file_details = array();

                if ($file_existing){
                    $file_details['fid'] = $file_existing;
                }
                elseif ($file_upload){
                    $file_details['fid'] = $file_upload;
                }
                elseif ($file_remote){
                    $file_details['file_remote'] = $file_remote;
                }

                try {
                    $importer = new $class();
                    $form = array();
                    $importer->formSubmit($form, $form_state);

                    $importer->create($run_args, $file_details);

                    $importer->submitJob();

                } catch (Exception $ex) {
                    drupal_set_message('Cannot submit import: ' . $ex->getMessage(), 'error');
                }
            }
        }
    }
}