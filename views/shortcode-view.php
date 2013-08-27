<?php

$this->table->set_template($cp_table_template);

$this->table->set_heading('entry_id', 'type', "first occurrence starts with", "title/link");

foreach ($candidates as $candidate) {
	
	$this->table->add_row(
		array('width'=>'10%', 'data'=>$candidate['entry_id']),
		array('width'=>'10%', 'data'=>$candidate['type']),
		$candidate['starts_with'],
		"<a href='".$candidate['url']."'>".$candidate['title']."</a>"
	);

}

echo $this->table->generate();
