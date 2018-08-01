# TPPSc: Tripal Plant PopGen Submit Curate:
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Features](#features)

# Introduction
TPPSc is a [Drupal](https://www.drupal.org/) module built to extend the functionality of the [Tripal](http://tripal.info/) toolset. The purpose of this tripal module is to permit internal curation of manuscripts available on public sites with supplemental data, such as Dryad. This module will allow members of the database team to load existing flat files and select the data that is relevant. This module does not currently require any other modules.

# Installation
1. At the top of this page, find the drop-down menu next to the URL with options 'SSH' and 'HTTPS'. Select 'HTTPS', then click 'Copy URL to clipboard'. Download this module by running ```git clone <URL> ``` on command line. 
2. Place the cloned module folder "TPPSc" inside your /sites/all/modules/custom. Then enable the module by running ```drush en tppsC``` (for more instructions, read the [Drupal documentation page](https://www.drupal.org/node/120641)).

# Features
- Support for genotype, phenotype, and environmental data and metadata
- Restricted access to approved users of the site
- The studies can be queried or downloaded (flatfiles) through the Tripal interface
