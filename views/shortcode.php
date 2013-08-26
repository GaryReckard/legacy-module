<?php

$this->table->set_template($cp_table_template);

$this->table->set_heading('entry_id', 'type', "first occurrence starts with", "title/link");

foreach ($entries as $entry) {
	
	$type = null;
	$title = null;
	$matches = null;
	$tesxt = null;
	$url = null;
	$pattern = null;
	$starts_with = null;
	
	//is it a blog or page?  if neither don't include in table.
	if ($entry['field_id_1'] != "") {
		$type = "page";
		$text = $entry['field_id_1'];
	} else if ($entry['field_id_13'] != "") {
		$type = "blog";
		$text = $entry['field_id_13'];
		$url = "/index.php/blog/entry/".$entry['url_title'];
	} else continue;
	
	//look for []'s in body
	$pattern = '/\[(.*)\]/';
	preg_match($pattern, $text, $matches);
	
	//if we have an opening '[' add to our tabl
	if ($matches != NULL) {
		
		$str_arr = explode(' ',trim($matches[0]));
		$starts_with = $str_arr[0];
		
		$this->table->add_row(
			array('width'=>'10%', 'data'=>$entry['entry_id']),
			array('width'=>'10%', 'data'=>$type),
			$starts_with,
			"<a href='".$url."'>".$entry['title']."</a>"
		);		
	}
}

echo $this->table->generate();
