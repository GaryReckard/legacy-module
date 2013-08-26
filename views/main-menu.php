<?php

$this->table->set_template($cp_table_template);
//$this->table->set_heading(array('colspan'=>2, 'Here are some options:'));
$this->table->set_heading('Legacy Options');

//echo $_base_url;
//hard-code for now
$this->table->add_row('<a href="'.$_base_url.AMP.'method=showShortcode">Show Shortcode Candidates</a>');
$this->table->add_row('<a href="'.$_base_url.AMP.'method=populateTags">Populate Tagger Fields</a>');

echo $this->table->generate();
