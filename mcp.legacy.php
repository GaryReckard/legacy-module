<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Legacy Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Ryan Barrington Cox
 * @link		
 */

class Legacy_mcp {
	
	public $return_data;
	
	private $_base_url;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=legacy';
		
		$this->EE->cp->set_right_nav(array(
			'module_home'	=> $this->_base_url,
			// Add more right nav items here.
		));
	}
	
	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		$this->EE->load->library('table');
		
		$this->EE->view->cp_page_title = 'Legacy';
		
		$this->EE->cp->set_variable('cp_page_title', 
								lang('legacy_module_name'));
	
		$vars['_base_url'] = $this->_base_url;
		
		//this is how we pass data to the view
		$vars['options'] = array(
			"View shortcode candiates",
			"Import tags from string to tagger field",
		);
		return $this->EE->load->view('main-menu.php', $vars, TRUE);
	}

	public function showShortcode() {
		
		$this->EE->load->library('table');
		
		$this->EE->view->cp_page_title = 'Shortcode';
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");

		//query db for shortcode candidates: entry_id, blog or page type, title with link
		$query_string = "SELECT * FROM exp_channel_data, exp_channel_titles WHERE exp_channel_data.entry_id = exp_channel_titles.entry_id";
		$query = $this->EE->db->query($query_string);
		
		//$this->EE->db->select('entry_id, field_id_1, field_id_13');
		//$query = $this->EE->db->get('exp_channel_data');		
		$vars['entries'] = $query->result_array();

		return $this->EE->load->view('shortcode.php', $vars, TRUE);
	}

	public function populateTags() {
				
		$this->EE->view->cp_page_title = 'Populate Tags';
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars['_base_url'] = $this->_base_url;
				
		return $this->EE->load->view('tags.php', $vars, TRUE);
	}
	
	public function queryTags() {

		$raw_tags = array();
		$distinct_tags = array();
		$tag_map = array();

		$this->EE->view->cp_page_title = 'Populate Tags';
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars['_base_url'] = $this->_base_url;
		
		//get all raw tags
		$query_string = "SELECT entry_id, field_id_16 FROM exp_channel_data WHERE channel_id = 3 AND  field_id_16 <> ''";
		$query = $this->EE->db->query($query_string);
		$results = $query->result_array();
		
		//key on entry_id so entry_id=> (array, of, tags).
		foreach ($results as $row) {
			$entry_id = $row['entry_id'];
			$tags_string = $row['field_id_16'];
			$tags_array = array_map('trim',explode(",", $tags_string));			
			$raw_tags[$entry_id] = $tags_array;
			
			//push tags into distinct tags array if not already there
			foreach ($tags_array as $tag) {
				if (!in_array($tag, $distinct_tags)) {
					array_push($distinct_tags, $tag);
				}
			}
			
		}		
		//echo "<pre>".print_r($raw_tags,true)."</pre>";
		$now = time();

		//insert distinct tags into exp_tagger table and save map of tag_name to tag_id
		foreach ($distinct_tags as $tag) {
			$data = array('tag_name' => $tag, 'entry_date' => $now, 'edit_date' => $now);
			$sql = $this->EE->db->insert_string('exp_tagger', $data);
			$sql = $this->EE->db->query($sql);
			$last_id = $entry_id = $this->EE->db->insert_id();
			$tag_map[$tag] = $last_id;
		}
		//echo "<pre>".print_r($tag_map,true)."</pre>";
		
		//go through each blog entry
		foreach ($raw_tags as $key => $tags) {
			
			$tag_counter = 0;
			
			//insert a link to each tag
			foreach ($tags as $tag) {
				$tag_counter++;
				$id = $tag_map[$tag];
				$data = array('site' => $tag, 'entry_date' => $now, 'edit_date' => $now);
				$data = array('site_id' => 1,'entry_id'=> $key,'channel_id' => 3,'field_id'=> 14,'tag_id'=>$id,'author_id'=>1,'type'=>1,'tag_order'=>$tag_counter);
				$sql = $this->EE->db->insert_string('exp_tagger_links', $data);
				$sql = $this->EE->db->query($sql);				
			}
		}
		
		$vars['distinct_tags'] = $distinct_tags;
		$vars['raw_tags'] = $raw_tags;
		
		//display some freedback.  i.e.  'inserted xx tags for xx entries'
		return $this->EE->load->view('tag-results.php', $vars, TRUE);
		
	}

}
/* End of file mcp.legacy.php */
/* Location: /system/expressionengine/third_party/legacy/mcp.legacy.php */