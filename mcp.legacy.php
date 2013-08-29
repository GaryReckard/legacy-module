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
	public function __construct() {
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
	public function index() {
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
	
	public function getPageMap() {
				
		$this->EE->db->select('*');
		$this->EE->db->from('exp_sites');
		$this->EE->db->where('site_id',1);		
		$query = $this->EE->db->get();

		$result = $query->result_array();
		$site_pages = $result[0]['site_pages'];
		$site_pages = base64_decode($site_pages);
		$page_map = unserialize($site_pages);
		
		//die(print_r($page_map[1]['uris']));
		return $page_map[1]['uris'];
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
		$page_map = $this->getPageMap();

		//query db for shortcode candidates: entry_id, blog or page type, title with link
		$query_string = "SELECT * FROM exp_channel_data, exp_channel_titles WHERE exp_channel_data.entry_id = exp_channel_titles.entry_id";
		$query = $this->EE->db->query($query_string);
		
		$entries = $query->result_array();
		
		foreach ($entries as $entry) {
			
			$entry_id = $entry['entry_id'];
			
			//is it a blog or page?  if neither then disregard
			if ($entry['channel_id'] == 1) {
				$type = "page";
				$text = $entry['field_id_1'];
			} else if ($entry['channel_id'] == 3) {
				$type = "blog";
				$text = $entry['field_id_13'];
			} else continue;
			
			//look for []'s in body
			$pattern = '/\[(.*)\]/';
			preg_match($pattern, $text, $matches);
			
			//if we have an opening '[' add to candidates
			if ($matches != NULL) {
				$str_arr = explode(' ',trim($matches[0]));
				$starts_with = $str_arr[0];
				if ($type == "page") {
					$url = $page_map[$entry_id];
				} else {
					$url = "/index.php/blog/entry/".$entry['url_title'];
				}
				$arr =  array('entry_id'=>$entry['entry_id'], 'type'=>$type, 'starts_with'=>$starts_with, 'title'=>$entry['title'], 'url'=>$url);
				array_push($vars['candidates'], $arr);
			}				
		}	
		
		return $this->EE->load->view('shortcode-view.php', $vars, TRUE);
	}

	//get a map of existing legacy_urls and their id
	public function getLegacyMap($query_string) {
		//die($query_string);
		
		$legacy_url_map = array();
		
		$query = $this->EE->db->query($query_string);
		$legacy_entries = $query->result_array();	
		foreach ($legacy_entries as $legacy_entry) {
			$legacy_url_map[$legacy_entry['original_url']] = $legacy_entry['detour_id'];
		}
		//die("<pre>".print_r($legacy_url_map,true)."</pre>");
		return $legacy_url_map;	
	}

	public function redirectHelper($results, $channel_id, $legacy_url) {
		
		$this->EE->view->cp_page_title = '301 Redirect Blogs';
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");		
	
		$vars['inserted_redirects'] = array();
		$vars['updated_redirects'] = array();
		
		$map_query_string = "SELECT detour_id, original_url FROM exp_detours";	
		$legacy_map = $this->getLegacyMap($map_query_string);
		
		$domainless_legacy_url = "";
		//insert the pair, along with 301 option into exp_detours
		foreach($results as $row) {
			
			$new_url="";	
			$old_url_segments = parse_url($row['legacy_url']);
			$domainless_legacy_url = $old_url_segments['path'];
			$domainless_legacy_url = ltrim($domainless_legacy_url, "/");
			$domainless_legacy_url = rtrim($domainless_legacy_url, "/");
			
			//strip all the stuff after a % legacy, replace with one % b/c detour uses this as a wildcard
			$arr = explode("%",$domainless_legacy_url);
			$legacy_url_no_wildcard = $arr[0];
			$domainless_legacy_url = $arr[0]."%";
			
			//if it's a blog
			if ($row['channel_id'] == 3) {			
				$path = 'blog/entry/';
				$new_url = $path.$row['url_title'];			
			}
			
			//else if it's a page
			elseif ($row['channel_id'] == 1) {
				//echo "entry_id is ".$row['entry_id']."<br>";
				$page_map = $this->getPageMap();
				$entry_id = $row['entry_id'];
				//print_r($page_map);
				//die($page_map($row['entry_id']));
				$new_url = $page_map[$entry_id];
				$new_url = ltrim($new_url, "/");
				//die($new_url);
			}
			
			else {
				die("Something's gone terribly wrong.  Call for help!");
			}
			
			//special case: urls are same which causes infinte redirect loop, don't insert if they're the same!
			if ($new_url ==$legacy_url_no_wildcard) {
				
				//remove orignal url redirect if exists
				$this->EE->db->delete('exp_detours', array('original_url'=>$domainless_legacy_url));
				echo "removed entry";
				//break out of loop...
				continue;
			}
			
			//if the legacy url is already in there, update the entry
			if (array_key_exists($domainless_legacy_url, $legacy_map)) {
				
				$detour_id = $legacy_map[$domainless_legacy_url];
				$data = array('original_url' => $domainless_legacy_url, 'new_url' => $new_url, 'detour_method' => 301, 'site_id' => 1);			
				$this->EE->db->where('detour_id', $detour_id);
				$this->EE->db->update('exp_detours', $data);
				array_push($vars['updated_redirects'], $data);
			}
			//else insert as new
			else {
				$data = array('original_url' => $domainless_legacy_url, 'new_url' => $new_url, 'detour_method' => 301, 'site_id' => 1);
				$sql = $this->EE->db->insert_string('exp_detours', $data);
				$sql = $this->EE->db->query($sql);
				array_push($vars['inserted_redirects'], $data);	
			}
		}
		
		return $vars;	
	}

	public function redirectBlogs() {

		$blog_channel_id = 3;
		$blog_legacy_url = 'field_id_17';
		
		//get url-title and legacy-url from all blogs
		$query_string = "SELECT cd.channel_id, $blog_legacy_url as legacy_url, url_title, ct.channel_id FROM exp_channel_data cd, exp_channel_titles ct
			WHERE cd.entry_id = ct.entry_id and ct.channel_id = 3 AND $blog_legacy_url <> ''";
		//die($query_string);
		$query = $this->EE->db->query($query_string);
		$results = $query->result_array();		
		
		$vars = $this->redirectHelper($results, $blog_channel_id, $blog_legacy_url);
		
		return $this->EE->load->view('redirections-results.php', $vars, TRUE);	
		
	}
	
	public function redirectPages() {
		
		$page_channel_id = 1;
		$page_legacy_url = 'field_id_21';
		
		//get url-title and legacy-url from all blogs
		$query_string = "SELECT cd.entry_id, cd.channel_id, $page_legacy_url as legacy_url, url_title, ct.channel_id FROM exp_channel_data cd, exp_channel_titles ct
			WHERE cd.entry_id = ct.entry_id and ct.channel_id = $page_channel_id AND  $page_legacy_url <> ''";
			
		$query = $this->EE->db->query($query_string);
		$results = $query->result_array();	
			
		$this->EE->view->cp_page_title = '301 Redirect Pages';
		$this->EE->cp->set_breadcrumb($this->_base_url, "Legacy");
		
		$vars = $this->redirectHelper($results, $page_channel_id, $page_legacy_url);
		
		return $this->EE->load->view('redirections-results.php', $vars, TRUE);	
		
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