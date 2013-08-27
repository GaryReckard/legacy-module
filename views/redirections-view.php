<p><?=lang('redirect_disclaimer')?></p>

<?
$this->table->set_template($cp_table_template);
//$this->table->set_heading(array('colspan'=>2, 'Here are some options:'));
$this->table->set_heading('Legacy Options');

$this->table->add_row('<a href='.$_base_url.AMP.'method=redirectBlogs>'.lang('populate_blog_redirects').'</a>');
$this->table->add_row('<a href='.$_base_url.AMP.'method=redirectPages>'.lang('populate_page_redirects').'</a>');

echo $this->table->generate();