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
		return $this->EE->load->view('index.php', $vars, TRUE);
	}

	public function showShortcodeOptions() {
		
		$this->EE->load->library('table');
		
		$this->EE->view->cp_page_title = lang('show_shortcode_options');
		
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$type = null;
		$title = null;
		$matches = null;
		$tesxt = null;
		$url = null;
		$pattern = null;
		$starts_with = null;
		$vars['candidates'] = array();

		//query db for shortcode candidates: entry_id, blog or page type, title with link
		$query_string = "SELECT * FROM exp_channel_data, exp_channel_titles WHERE exp_channel_data.entry_id = exp_channel_titles.entry_id";
		$query = $this->EE->db->query($query_string);
		
		$entries = $query->result_array();
		
		foreach ($entries as $entry) {
			
			//is it a blog or page?  if neither then disregard
			if ($entry['channel_id'] == 1) {
				$type = "page";
				$text = $entry['field_id_1'];
				$url = $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL='.urlencode($this->EE->functions->create_url($entry['url_title']));
			} else if ($entry['channel_id'] == 3) {
				$type = "blog";
				$text = $entry['field_id_13'];
				$url = "/index.php/blog/entry/".$entry['url_title'];
			} else continue;
			
			//look for []'s in body
			$pattern = '/\[(.*)\]/';
			preg_match($pattern, $text, $matches);
			
			//if we have an opening '[' add to candidates
			if ($matches != NULL) {
				$str_arr = explode(' ',trim($matches[0]));
				$starts_with = $str_arr[0];		
				$arr =  array('entry_id'=>$entry['entry_id'], 'type'=>$type, 'starts_with'=>$starts_with, 'title'=>$entry['title'], 'url'=>$url);
				array_push($vars['candidates'], $arr);
			}				
		}	

		return $this->EE->load->view('shortcode-view.php', $vars, TRUE);
	}

	public function redirectBlogs() {
		
		$this->EE->view->cp_page_title = '301 Redirect Blogs';
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$blog_channel_id = 3;
		$blog_legacy_url = 'field_id_17';
		$blog_path = 'blog/entry/';
		$vars['inserted_blog_redirects'] = array();
		$vars['updated_blog_redirects'] = array();
		$legacy_url_map = array();
		
		//get a map of existing legacy_urls and their id
		$query_string = "SELECT detour_id, original_url FROM exp_detours";
		//die($query_string);
		$query = $this->EE->db->query($query_string);
		$legacy_entries = $query->result_array();	
		foreach ($legacy_entries as $legacy_entry) {
			$legacy_url_map[$legacy_entry['original_url']] = $legacy_entry['detour_id'];
		}
		///die("<pre>".print_r($legacy_url_map,true)."</pre>");
		
		//get url-title and legacy-url from all blogs
		$query_string = "SELECT $blog_legacy_url as legacy_url, url_title, ct.channel_id FROM exp_channel_data cd, exp_channel_titles ct
			WHERE cd.entry_id = ct.entry_id and ct.channel_id = 3";
		//die($query_string);
		$query = $this->EE->db->query($query_string);
		$results = $query->result_array();
		
		//insert the pair, along with 301 option into exp_detours
		//die("results count is ".count($results));
		foreach($results as $row) {
			$new_url = $blog_path.$row['url_title'];
			$old_url_segments = parse_url($row['legacy_url']);
			$domainless_legacy_url = $old_url_segments['path'];
			$domainless_legacy_url = ltrim($domainless_legacy_url, "/");
			$domainless_legacy_url = rtrim($domainless_legacy_url, "/");
			
			//strip all the stuff after a % legacy, replace with one % b/c detour uses this as a wildcard
			$arr = explode("%",$domainless_legacy_url);
			$domainless_legacy_url = $arr[0]."%";
			
			//if the legacy url is already in there, update the entry
			if (array_key_exists($domainless_legacy_url, $legacy_url_map)) {
				$data = array('new_url' => $new_url, 'detour_method' => 301, 'site_id' => 1);				
				$this->EE->db->where('original_url', $legacy_url_map[$domainless_legacy_url]);
				$this->EE->db->update('exp_detours', $data); 
				array_push($vars['updated_blog_redirects'], $data);
			}
			//else insert as new
			else {
				$data = array('original_url' => $domainless_legacy_url, 'new_url' => $new_url, 'detour_method' => 301, 'site_id' => 1);
				$sql = $this->EE->db->insert_string('exp_detours', $data);
				$sql = $this->EE->db->query($sql);
				array_push($vars['inserted_blog_redirects'], $data);				
			}
		}
		
		return $this->EE->load->view('redirections-results.php', $vars, TRUE);
	}
	
	public function redirectPages() {
			
		$this->EE->view->cp_page_title = '301 Redirect Pages';
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		
	}
	
	public function populate301Redirects() {
		
		$this->EE->load->library('table');

		$this->EE->view->cp_page_title = lang('populate_301_redirects');
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars['_base_url'] = $this->_base_url;
				
		return $this->EE->load->view('redirections-view.php', $vars, TRUE);
				
	}

	public function populateTaggerFields() {
				
		$this->EE->view->cp_page_title = lang('populate_tagger_fields');
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars['_base_url'] = $this->_base_url;
				
		return $this->EE->load->view('tags-view.php', $vars, TRUE);
	}
	
	public function queryTags() {

		$raw_tags = array();
		$distinct_tags = array();
		$tag_map = array();
		$existing_tag_map = array();

		$this->EE->view->cp_page_title = 'Populate Tags';
		
		//figure out how to display breadcrumbs
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars['_base_url'] = $this->_base_url;
		
		//get a map of existing legacy_urls and their id
		$query_string = "SELECT tag_id, tag_name FROM exp_tagger";
		$query = $this->EE->db->query($query_string);
		$existing_tags = $query->result_array();	
		foreach ($existing_tags as $existing_tag) {
			$existing_tag_map[$existing_tag['tag_name']] = $existing_tag['tag_id'];
		}
		
		//get all raw tags for comma-separated text field
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
		$now = time();

		//insert distinct tags into exp_tagger table if not already there, then save map of tag_name to tag_id
		foreach ($distinct_tags as $tag) {
			
			if (array_key_exists($tag, $existing_tag_map)) {
				$tag_map[$tag] = $existing_tag_map[$tag];
			}
			else {
				$data = array('tag_name' => $tag, 'entry_date' => $now, 'edit_date' => $now);
				$sql = $this->EE->db->insert_string('exp_tagger', $data);
				$sql = $this->EE->db->query($sql);
				$last_id = $entry_id = $this->EE->db->insert_id();
				$tag_map[$tag] = $last_id;				
			}
		}
		
		//truncate links table
		$this->EE->db->truncate('exp_tagger_links');
		
		//go through each blog entry, populate/re-populate exp_tagger_links
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