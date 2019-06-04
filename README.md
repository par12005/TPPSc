# TPPSc:Tripal Plant PopGen Submit - Curate
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Features](#features)
4. [Current Input Fields](#current-input-fields)
5. [Currently Working On](#currently-working-on)

# Introduction
TPPSc is a [Drupal](https://www.drupal.org/) module built to extend the functionality of the [Tripal](http://tripal.info/) toolset. The purpose of this tripal module is to permit internal curation of manuscripts available on public sites with supplemental data, such as Dryad. This module will allow members of the database team to load existing flat files and select the data that is relevant. This module does not currently require any other modules.

# Installation
1. At the top of this page, find the drop-down menu next to the URL with options 'SSH' and 'HTTPS'. Select 'HTTPS', then click 'Copy URL to clipboard'. Download this module by running ```git clone <URL> ``` on command line. 
2. Place the cloned module folder "TPPSc" inside your /sites/all/modules/custom. Then enable the module by running ```drush en tppsc``` (for more instructions, read the [Drupal documentation page](https://www.drupal.org/node/120641)).

# Features
- Support for genotype, phenotype, and environmental data and metadata
- Restricted access to approved users of the site
- The studies can be queried or downloaded (flatfiles) through the Tripal interface

# Current Input Fields

The module has 6 primary input fields and many secondary input fields dependent on the primary input. The primary input fields are DOI, Species, Study Type, Study Design, Study Location, and Tree Accession File.
1. The DOI field is a textfield where one would enter the DOI link from Dyrad
2. The Species field has a dropdown menu with options of 1-20 for number of different species in the study. Once a number is selected, the same amount of textfields appear.
3. The Study Type Field is a dropdown menu with 7 options of all the different combinations of Genotype, Phenotype, and Enviornment
    If Genotype selected, a second field will pop up for genotype marker type (SNPs, SSRs/cpSSRs, other). Once an option is selected, an optional file upload input appears to upload the selected data in its correct file format.
    If Phenotype or Enviornment is selected, an optional file upload input appears to upload the phenotypic or enviornmental data.
4. The Study Design Field is a dropdown menu with 5 options (Natural Population, Growth Chamber, Greenhouse, Common Garden, and Plantation). No further data requested / needed.
5. The Study Location Field is a dropdown menu with 4 options (Lattitude Longitude (WGS 84), Lat/Long (NAD 83), Lat/Long (ETRS 89), and Custom Location). The Study Location is not location of the trees but instead the general location of the study.
    Once an option is selected, a textfield appears with either coordinates (for all of the lat/long options) or Custom Location (for custom location). 
6. The Tree Accession File is a file upload field for one to upload an excel file with columns of tree ID's and their location.

# Currently Working On
Currently Working on Data Storage in the drupal database and the drupal validate function

