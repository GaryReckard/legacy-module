<?php

$this->table->set_template($cp_table_template);
//$this->table->set_heading(array('colspan'=>2, 'Here are some options:'));
$this->table->set_heading('Legacy Options');

//hard-code for now
$this->table->add_row('<a href="'.$_base_url.AMP.'method=showShortcodeOptions">'.lang('show_shortcode_options').'</a>');
$this->table->add_row('<a href="'.$_base_url.AMP.'method=populateTaggerFields">'.lang('populate_tagger_fields').'</a>');
$this->table->add_row('<a href="'.$_base_url.AMP.'method=populate301Redirects">'.lang('populate_301_redirects').'</a>');

echo $this->table->generate();
