<?php 
require('libs/reefine_theme.php');

class Reefine {

	var $return_data	= '';
	var $p_limit = '';
	var $filter_channel;
	var $filter_groups;
	var $url_tag;
	/**
	 * Value of url parameter or url_output parameter if provided
	 * @var string
	 */
	var $url_output;
	/**
	 * @var boolean Whether to join exp_channel_titles on queries
	 */
	var $include_channel_titles = false; // include the channel entry title in searches.
	/**
	 *
	 * @var boolean Whether to join exp_categories to search query
	 */
	var $include_categories = false;
	/**
	 * @var int number of active filters
	 */
	var $active_filter_count = 0;
	var $class_name = 'Reefine';
	var $categories = array();
	/**
	 * @var string Database prefix
	 */
	var $dbprefix = 'exp_';
	/**
	 * 
	 * @var CI_Controller
	 */
	public $EE;
	/**
	 *
	 * @var array array of filter values to filter by in order of group, filter
	 */
	var $filter_values = array();

	/**
	 * Tag data from inside tag
	 * @var unknown_type
	 */
	var $tagdata='';
	/**
	 *
	 * @var array list of entry ids
	 */
	var $entry_id_list = array();
	/**
	 * The theme object to use
	 * @var unknown_type
	 */
	var $theme;
	/**
	 * If true, filter groups are available as seperate tags in tagdata.
	 * @var bool
	 */
	var $seperate_filters = false;
	/**
	 * The theme name to use
	 * @var unknown_type
	 */
	var $theme_name = '';
	/**
	 * search by status
	 * @var string
	 */
	var $status = 'open';
	/**
	 * Search field where sql from search:xyz parameters
	 * @var unknown_type
	 */
	var $search_field_where_clause='';
	
	/**
	 * current timestamp
	 * @var unknown_type
	 */
	var $timestamp;
	/**
	 * Whether the tag has been requested via ajax or not.
	 * @var boolean
	 */
	public $is_ajax_request = false;
	/**
	 * This is a hidden field that is submitted with the form. This way we know it is a full form post even if there are no other values present
	 * as in the case of using just tickboxes there is no other way to know if the form has been submitted or not if none have been ticked.
	 * @var boolean
	 */
	public $is_form_post = false;
	/**
	 * String to append to url that isnt related to Reefine. Will be the page offset eg "/P6"
	 * @var unknown
	 */
	var $url_suffix = '';
	
	var $disable_search = false;
	
	/**
	 * limit by a category url
	 * @var unknown
	 */
	var $category_url = '';
	
	/**
	 * Whether to change EE's uri variables to fix pagination issues caused by freebie
	 * @var boolean
	 */
	var $fix_pagination = false;
	
	/**
	 * category parameter, see function limit_by_category_ids
	 * @var string
	 */
	var $category = '';

	/**
	 * Entry id array to search by only these entry ids
	 * @var string
	 */
	var $entry_id = '';
	/**
	 * Author id array to search by only entries created by this author
	 * @var string
	 */
	var $author_id = '';
	/**
	 * Restrict search results to entries with Start date after start_on only
	 * @var unknown
	 */
	var $start_on = '';
	/**
	 * A fixed order of entry ids to order by
	 * @var unknown
	 */
	var $fixed_order = '';
	
	/**
	 * @var array default settings for group if not otherwise specified
	 */
	var $default_group_by_type = array(
			'text'=>array(
					'type'=>'list',
					'join'=>'or',
					'delimiter'=>''
			),
			'textarea' => array(
					'type'=>'search',
					'join'=>'or',
					'delimiter'=>''
			),
			'multi_select' => array(
					'type'=>'list',
					'join'=>'or',
					'delimiter'=>'|'
			),
			'checkboxes' => array(
					'type'=>'list',
					'join'=>'or',
					'delimiter'=>'|'
			),
			'title' => array(
					'type'=>'search',
					'join'=>'none',
					'delimiter'=>'',
					'label'=>'Search'
			),
			'date' => array(
					'type'=>'month_list',
					'join'=>'none',
					'delimiter'=>''
			),

	);

	function __construct()
	{
		
		// Make a local reference to the ExpressionEngine super object
		$this->EE = get_instance();
		$this->EE->load->library('logger');
		$this->timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;
		

		try {
			// if a second tag part is specified then stop processing
			if (count($this->EE->TMPL->tagparts)>1)
				return;
			
			$this->site = $this->EE->TMPL->fetch_param('site', $this->EE->config->item('site_id'));

			// to fix annoying bug where EE puts exp_ in the wrong places.
			$this->db = $this->EE->db;
			$this->dbprefix = $this->EE->db->dbprefix;
			$this->db->dbprefix = '';


			$delimiter = '|';
			$this->tagdata = $this->EE->TMPL->tagdata;
			$this->filter_groups = array();
			$this->_fetch_custom_channel_fields();
			//$this->_fetch_custom_category_fields();
			// get list of categories
			//$this->fetch_categories();

			$this->read_tag_parameters();

			$this->theme = $this->get_theme($this->theme_name);

			$this->filter_values = $this->get_filter_values();

			if (!$this->disable_search) {
				// get current search details
				$this->add_filter_values($this->filter_values);
			}
			
			//

			//get a unique id of this particular search.
			// $this->filter_id = md5(serialize($this->filter_groups));

			
			// change expressione ngin uri so paging works
			if ($this->method=='url') {
				if (!$this->disable_search) $this->do_redirects_for_text_inputs();
				if ($this->fix_pagination) $this->change_uri_for_paging();
			}

			// get the list of possible values to be used
			$this->set_filter_groups();

			// get all possible urls for each filters and put in $this->filter_groups[]['filters']['url']
			if ($this->method=='url' || $this->method=='ajax' || $this->method=='get') {
				$this->add_filter_url_to_filters();
			}
			// get all entry ids for this search.
			$this->entry_id_list = $this->get_entry_ids_from_database();
			$this->theme->before_parse_tag_data();
			
			$tag_array = $this->get_tag_data_result($this->entry_id_list );
			
			// set db prefix back
			$this->EE->db->dbprefix = $this->dbprefix;
			
			if ($this->method=='ajax' && $this->is_ajax_request) {
				$ajax_output = $this->parse_final_template($this->tagdata,$tag_array);
				$this->EE->output->send_ajax_response($ajax_output);
			} else {
				$this->return_data = $this->EE->TMPL->parse_variables_row($this->tagdata, $tag_array);
			}
		} catch (Exception $e) {
			// Log error
			$this->EE->db->dbprefix = $this->dbprefix;
			$this->EE->logger->log_action($this->class_name . ' error at ' . $_SERVER['REQUEST_URI'] . ': ' . $e->getMessage());
			$this->return_data = '';
			throw $e;
		}

		// set db prefix back
		$this->EE->db->dbprefix = $this->dbprefix;
		
	}

	private function do_redirects_for_text_inputs() {
		// check for any post/get values that may be submitted by form and redirect
		// so that the url is correct.
		foreach ($this->filter_groups as $group_name => $group) {
			//$group->do_redirect_for_text_input();
			if ($group->post_contains_filter_value()) {
				$url = $this->get_filter_url();
				$this->EE->functions->redirect($this->create_url($url));
			}
		}
	}
	
	private function parse_final_template($tagdata,$tag_array) {
		// http://expressionengine.stackexchange.com/questions/1347/how-can-i-manually-parse-template-code-from-php

		// new version for ee3: https://expressionengine.com/forums/topic/248431/override-loader-objects
		//check the ee()->TMPL object
		if(isset(ee()->TMPL))
		{
			$OLD_TMPL = ee()->TMPL;
			ee()->remove('TMPL');
		}
		else
		{
			require_once APPPATH.'libraries/Template.php';
			$OLD_TMPL = null;
		}
		$html = '';
		
		 //set the new ee()->TMPL
		 ee()->set('TMPL', new EE_Template());
		 $html = ee()->TMPL->parse_variables_row($tagdata, $tag_array);
		 $html = ee()->TMPL->parse_globals($html);
		 $html = ee()->TMPL->remove_ee_comments($html);


		
		// pretty lame that we need to manually load snippets
		$result = $this->EE->db->select('snippet_name, snippet_contents')
		->where('site_id', $this->site)
		->or_where('site_id', 0)
		->get("snippets")->result_array();
		
		$snippets = array();
		foreach ($result as $row) {
			$snippets[$row['snippet_name']] = $row['snippet_contents'];
		}
		
		// merge snippets into global variables
		$this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $snippets);
		
		// parse email contents as complete template
		ee()->TMPL->parse($html);
		
		
		
		$html = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
		


		//remove and add the old TMPL object to the ee()->TMPL object if null
		if($OLD_TMPL !== NULL)
		{
			ee()->remove('TMPL');
			ee()->set('TMPL', $OLD_TMPL);
		}
	
		
		return $html;
		
	}
	
	private function  get_filter_values() {
		$filter_values = array();
		// if the search will be done via the URL then parse through it to get the filter values
		if ($this->method=='url') {
			// if freebie is being used then get the page url before freebie has messed about with it
			if (isset($this->EE->uri->config->_global_vars['freebie_original_uri'])) {
				$this->url = $this->EE->uri->config->_global_vars['freebie_original_uri'];
			} else {
				//$this->url = $this->EE->router->uri->uri_string;
				$this->url = $this->EE->uri->uri_string();
			}
			if (strpos($this->url,'/')!==0)
				$this->url = '/'.$this->url;
		
			if (preg_match('/\/P(\d+)$/', $this->url, $matches)) {
				$this->url_suffix = $matches[0];
				$this->url = preg_replace('/\/P(\d+)$/', '', $this->url);
			}
			$filter_values = $this->parse_search_url($this->url_tag,$this->url);
		
		} 
		if ($this->EE->input->get_post('ajax_request'))
			$this->is_ajax_request=true;
		if ($this->EE->input->get_post('form_post'))
			$this->is_form_post=true;
		
		// get filter values from post/get for ajax/post/get method
		// also if using URL method then we want to set the value for redirecting
		foreach ($this->filter_groups as $group_name => &$group) {
			if ($group->post_contains_filter_value() || $this->method=='post' || $this->method=='get' || $this->method=='ajax') {
				$post_filter_values = $group->get_filter_value_from_post();
				if ($post_filter_values !== null) {
					$filter_values[$group_name] = $post_filter_values;
				} else if ($group->default && !$this->is_ajax_request && !$this->is_form_post) {
					// first hit of this form so set defaults
					$filter_values[$group_name] = $group->default;
				}
				
			}
		}
	
		return $filter_values;
	}

	private function get_theme($theme_name) {
		if ($theme_name!='') {
			$filepath = PATH_THIRD_THEMES.'reefine/'. $theme_name . '/theme.php';

			if (file_exists($filepath))
			{
				include_once $filepath;
				// if using legacy reefine theme
				if (class_exists('Reefine_theme_' . $theme_name))
					$theme_class = 'Reefine_theme_' . $theme_name;
				else if (class_exists('Reefine_theme_custom'))
					$theme_class = 'Reefine_theme_custom';
				else // No theme class found
					return new Reefine_theme($this);
				// return custom theme
				return new $theme_class($this);
			}
		}
		return new Reefine_theme($this);

	}


	/**
	 * Paging channel entries needs to know the full url so it can add /Pxx on the end
	 */
	private function change_uri_for_paging() {
		// if there are no filters then build an url with /any/any.. on it so /Pxx is in the right segment
		$unfiltered_url = $this->get_filter_url();
		if (count($this->filter_values)==0 && strpos($this->url,$unfiltered_url)===false)
			$url = $unfiltered_url;
		else
			$url = $this->url;
		
		$this->EE->uri->uri_string = $url . $this->url_suffix;
		$this->EE->uri->segments = explode('/',trim($url,'/'));
		// add suffix to query string which is used for paging
		$this->EE->uri->page_query_string = $url . $this->url_suffix;  
		
	}

	/**
	 * Fetch all parameters for tag
	 */
	function read_tag_parameters() {

		// get channel filter
		$filter_channel = $this->EE->TMPL->fetch_param('channel', '');
		
		$this->status = $this->EE->TMPL->fetch_param('status', $this->EE->config->item('open'));
		$this->disable_search = $this->EE->TMPL->fetch_param('disable_search', $this->EE->config->item('disable_search'));
		// methods: url,post,get,ajax,
		$this->method = $this->EE->TMPL->fetch_param('method', 'url');
		$this->url_tag = $this->EE->TMPL->fetch_param('url', '');
		$this->url_output = $this->EE->TMPL->fetch_param('url_output', $this->url_tag);
		$this->theme_name = $this->EE->TMPL->fetch_param('theme', '');
		$this->seperate_filters = ($this->EE->TMPL->fetch_param('seperate_filters', '') == 'yes' ? true : false);
		$this->fix_pagination = ($this->EE->TMPL->fetch_param('fix_pagination') == 'yes' ? true : false);
		$this->start_on = $this->EE->TMPL->fetch_param('start_on', '');
		$this->fixed_order = $this->EE->TMPL->fetch_param('fixed_order', '');
		
		// get list of channel ids to choose from
		if (!empty($filter_channel)) {
			$this->channel_ids = $this->get_channel_ids($filter_channel);
			if (count($this->channel_ids)==0) 
				throw new Exception("Reefine error: Channel not found " . $filter_channel);
		}
		
		// read filter:fields="" tag
		$this->get_field_filters_from_parameters();

		// read filter:group:.. tags to add new filer groups (eg filter:price:label will add price group)
		foreach ($this->EE->TMPL->tagparams as $key => $value) {
			if (preg_match('/filter\:(.+)\:.+/',$key,$matches)) {
				$group_name = $matches[1];
				if (!isset($this->filter_groups[$group_name])) {
					$group_type = $this->get_filter_group_setting($group_name, 'type', 'list');
					$group = Reefine_group::create_group_by_type($group_type, $group_name, $this);
					$this->filter_groups[$group_name] = $group;
				}
			}
		}

		// go through all filter groups to check for settings int tag parameters
		$this->add_all_filter_group_settings();
		
		// read search:xyz="" tag and create an sql where clause from it.
		if (count($this->EE->TMPL->search_fields)>0) {
			$this->search_field_where_clause = $this->get_search_field_where_clause($this->EE->TMPL->search_fields);
		}
		
		// get where cluses from filter groups
		foreach ($this->filter_groups as $group) {
			$group_where = $group->get_global_where_clause();
			if (!empty($group_where)) {
				if ($this->search_field_where_clause!='') $this->search_field_where_clause .= ' AND ';
				$this->search_field_where_clause .= $group_where;
			}
		}
		

		// category_url parameter limits results to just the the category_url
		if (!empty($this->EE->TMPL->tagparams['category_url'])) {
			$this->limit_by_category_url($this->EE->TMPL->tagparams['category_url']);
		}
		
		// category_url parameter limits results to just the the category_url
		if (!empty($this->EE->TMPL->tagparams['category'])) {
			$this->limit_by_category_ids($this->EE->TMPL->tagparams['category']);
		}
		
		// entry_id parameter limits results to just the the entry ids
		if (!empty($this->EE->TMPL->tagparams['entry_id'])) {
			$this->limit_by_entry_ids($this->EE->TMPL->tagparams['entry_id']);
		}
		
		// entry_id parameter limits results to just the the entry ids
		if (!empty($this->EE->TMPL->tagparams['author_id'])) {
			$this->limit_by_author_ids($this->EE->TMPL->tagparams['author_id']);
		}
		

	}
	
	function limit_by_category_url($category_url) {
		$this->category_url = $category_url;
		// include categories in select using a global category table that is left joined
		$this->include_categories=true; // yes to joining a global category table
		$this->search_field_where_clause .= $this->search_field_where_clause=='' ? '' : ' AND ';
		$this->search_field_where_clause .= sprintf("global_cat.cat_url_title=%s",
		$this->db->escape($this->category_url));
	}
	
	/**
	 * Limit by category like EE does here:
	 * http://ellislab.com/expressionengine/user-guide/add-ons/channel/channel_entries.html#category
	 * @param unknown $category_url
	 */
	function limit_by_category_ids($category) {
		if (preg_match_all('/\d+/',$category,$matches)) {
			$categories = $matches[0];
			$this->category = $category;
			// include categories in select using a global category table that is left joined
//			$this->include_categories=true; // yes to joining a global category table
			$this->search_field_where_clause .= $this->search_field_where_clause=='' ? '' : ' AND ';
			$logic_not = (strpos($category, 'not')!==false);
			$logic_and = (strpos($category, '&')!==false);
			$logic_or = (strpos($category, '|')!==false);
			
			if ($logic_not) {
				$sql = ' (exp_channel_data.entry_id NOT IN (SELECT entry_id FROM exp_category_posts WHERE cat_id IN (' . implode(', ',$categories) . '))) ';
			} elseif ($logic_and) {
				// @todo
				throw new Exception('Sorry, AND operator not supported in Reefine',E_WARNING);
			} else { // $logic_or
				$sql = ' (exp_channel_data.entry_id IN (SELECT entry_id FROM exp_category_posts WHERE cat_id IN (' . implode(', ',$categories) . '))) ';
			}
			$this->search_field_where_clause .= $sql;
		}
	}
	 
	function limit_by_entry_ids($entry_id) {
		if (preg_match_all('/\d+/',$entry_id,$matches)) {
			$entry_ids = $matches[0];
			$sql = ' (exp_channel_data.entry_id IN (' . implode(', ',$entry_ids) . ')) ';
			$this->search_field_where_clause .= $sql;
		}
	}
	
	function limit_by_author_ids($author_id) {
		if (preg_match_all('/\d+/',$author_id,$matches)) {
			$author_ids = $matches[0];
			$sql = ' (exp_channel_titles.author_id IN (' . implode(', ',$author_ids) . ')) ';
			$this->search_field_where_clause .= $sql;
		}
	}
	
	
	/**
	 * get settings from tag parameters
	 */
	private function add_all_filter_group_settings() {
		/* @var $group Reefine_group */
		foreach ($this->filter_groups as $group_name => &$group) {
			$group->set_settings_from_parameters();
			

		}
	}

	//
	/**
	* add a setting to a filter group. eg filter:price:label will set the label setting for the price filter group
	if no tag is present it will use the existing setting, if no existing setting it will use $default
	* @param unknown_type $group_name
	* @param unknown_type $key the setting name
	* @param unknown_type $default default value
	* @param unknown_type $type data type: array, bool or text
	*/
	public function add_filter_group_setting($group, $key, $default, $type = 'text' ) {
		// if the filter group already contains a value for this key then this is the new default.
		// this would be set from get_field_filters_from_parameters function
		if (!empty($group->$key) && $group->$key!='') {
			$default = $group->$key;
		}
		$group->$key = $this->get_filter_group_setting($group->group_name, $key, $default, $type);
	}
	
	/**
	 * Gets array/string/boolean from filter:group_name:key="" tag
	 * @param string $group_name
	 * @param unknown $key
	 * @param unknown $default
	 * @param string $type
	 * @return Ambigous <unknown, boolean, multitype:>
	 */
	public function get_filter_group_setting($group_name, $key, $default, $type = 'text') {
		$tag = 'filter:' . $group_name . ':' . $key;
		if (!isset($this->EE->TMPL->tagparams[$tag])) {
			$result = $default;
		} else {
			if ($type=='array') {
				$result = explode('|',$this->EE->TMPL->fetch_param($tag));
			} else if ($type=='bool') {
				$result = $this->EE->TMPL->fetch_param($tag);
				$result = ($result == 'yes' ? true : false);
			} else { // text
				$result = $this->EE->TMPL->fetch_param($tag);
			}
		}
		return $result;
	}


	private function get_field_filters_from_parameters() {
		// get field filters from filter:fields tag

		$fields_tag = $this->EE->TMPL->fetch_param('filter:fields','');
		if (!empty($fields_tag)) {
			foreach (explode('|',$fields_tag) as $field_name)
			{
				$field = $this->get_field_obj($field_name);
				$ee_field = $field->get_field();	
				if ($field_name == 'title')
					$default = $this->default_group_by_type['title'];
				else if (isset($this->default_group_by_type[$ee_field['field_type']]))
					$default = $this->default_group_by_type[$ee_field['field_type']];
				else
					$default = $this->default_group_by_type['text'];
				$group_name = str_replace(':','_',$field_name);
				$override_type = $this->get_filter_group_setting($group_name, 'type', '');
				$group_type = ($override_type == '' ? $default['type'] : $override_type);
				/* @var $group Reefine_group */
				$group = Reefine_group::create_group_by_type($group_type, $group_name, $this);
				$group->fields = array($field);
				$group->label = isset($default['label']) ? $default['label'] : $ee_field['field_label'];
				$group->join = $default['join'];
				$group->delimiter = $default['delimiter'];
				$this->filter_groups[$field_name] = $group;
			
			}
		}
	}

	/**
	 * create $this->filter_groups[..]['filters'] array
	 * will contain a list of possible values for each filter group
	 */
	private function set_filter_groups() {
		$this->filter_where_clause = $this->get_filter_fields_where_clause();
		foreach ($this->filter_groups as &$group) {
			$group->set_filters();
		}

	}

	
		
	/**
	 * Get SQL for joins that are required.
	 * @param string $include_group Always include this group in the joins
	 * @param bool $is_category_join_required Is category join required (eg for the entries search where it may required if the category id is in the where clause)
	 * @return string
	 */
	public function get_query_join_sql($include_group,$is_category_join_required) {
		$joins = array();
		
		// also left outer join categories if the category or category_url
		if ($this->include_categories)
			$joins[] = "LEFT OUTER JOIN {$this->dbprefix}category_posts global_catp " .
			"ON global_catp.entry_id = {$this->dbprefix}channel_data.entry_id \n" .
			"LEFT OUTER JOIN {$this->dbprefix}categories global_cat " .
			"ON global_cat.cat_id = global_catp.cat_id " ;

		// add joins for custom fields.
		foreach ($this->filter_groups as $key => $group) {
			// If group has values
			if ($key==$include_group || (isset($group->values) && count($group->values)>0)) {
				$joins = array_merge($joins,$group->get_join_sql());
				//if ($is_category_join_required || $group->join=='or' || $group->join=='none' || $include_group==$key)
				//	$joins = array_merge($joins,$group->get_category_join_sql());
			}
		}
		// remove duplicates
		$joins = array_map("unserialize", array_unique(array_map("serialize", $joins)));		
		return implode("\n",$joins);
	}

	private function get_entry_ids_from_database() {
		$sql = "SELECT DISTINCT({$this->dbprefix}channel_data.entry_id) " .
		"FROM {$this->dbprefix}channel_data ";
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id = {$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->get_query_join_sql('',true);
		$sql .= ' WHERE 1=1 ';

		// make all the where sql statements for building the query
		$where = $this->get_filter_fields_where_clause();
		if ($where!='')
			$sql .= ' AND ' .$where;

		if (isset($this->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->channel_ids) . ")";
		}
		$results = $this->db->query($sql)->result_array();
		return $results;
	}




	/**
	 *
	 * @param string $ignore_filter_group Filter group key to ignore, useful for fields that aren't exclusive
	 * @return string SQL where clause to be used for selecting channel entries or the getting counts for filters
	 */
	function get_filter_fields_where_clause($ignore_filter_group = '') {
		$clauses = array();
		//// make where statement based on filter fields
		foreach ($this->filter_groups as $key => $group) {
			// If the field has some selected values and it's not one to ignore..
			if ($ignore_filter_group!=$key && isset($group->values) && count($group->values)>0) {
				$clauses = array_merge($clauses,$group->get_where_clause());
			}
		}

		// ensure status is open or whatever is supplied
		$clauses[] = $this->get_status_where_clause($this->status,"{$this->dbprefix}channel_titles.status");
		
		
		
		// limit to current site
		$clauses[] = "{$this->dbprefix}channel_titles.site_id = " . intval($this->site);
		// hide expired entries if neccesary
		if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
			$clauses[] =  "({$this->dbprefix}channel_titles.expiration_date = 0 OR {$this->dbprefix}channel_titles.expiration_date > {$this->timestamp}) ";
		// hide future entries if neccesary
		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
			$clauses[] = "{$this->dbprefix}channel_titles.entry_date < ".$this->timestamp;

		if ($this->start_on != '')
			$clauses[] = "{$this->dbprefix}channel_titles.entry_date >= " . ee()->localize->string_to_timestamp($this->start_on);
		
		// add search fields if neccesary
		if ($this->search_field_where_clause != '')
			$clauses[] = $this->search_field_where_clause;

		// combine all the clauses with an AND statement
		if (count($clauses)>0)
			return implode("\n AND ",$clauses) . "\n";
		else
			return '';

	}

	
	private function get_in_list($field, $in_list) {
		$result = '';
		if (count($in_list)==0)
			return ' ';
		elseif (count($in_list)==1)
		return $field . ' = ' . $this->db->escape($in_list[0]) . ' ';
		else {
			foreach ($in_list as $in)
			{
				if ($result!='') $result .= ',';
				$result .= $this->db->escape($in);
			}
			$result = "{$field} IN ({$result}) ";
		}
	}

	public function array_to_in_list($in_list) {
		$result = '';
		if (count($in_list)==0)
			return '(null)';
		else {
			foreach ($in_list as $in)
			{
				if ($result!='') $result .= ',';
				$result .= $this->db->escape($in);
			}
			return '(' . $result . ')';
		}
	}

	/**
	 * status fuction from modules/channel/mod.channel.php line 1590ish
	 * @param unknown $status
	 * @param unknown $column
	 * @return string
	 */
	function get_status_where_clause($status,$column) {
		if ($status != '')
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);
				
			$sstr = $this->EE->functions->sql_andor_string($status, $column);
			// get rid of AND at beggining
			$sstr = preg_replace('/^\s*AND/i', '', $sstr);
			// if it doesnt contain closed then exclude all closed entries	
			if (stristr($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND {$column} != 'closed' ";
			}
				
			return $sstr;
		}
		else
		{
			return "{$column} = 'open'";
		}
			
	}


	/**
	 * Get sql where for search: fields like what how channel entries works
	 * @param unknown_type $search_fields
	 */
	private function get_search_field_where_clause($search_fields) {
		/** ---------------------------------------
		 /**  Field searching
		 /** ---------------------------------------*/
		$sql = '';
		if ( ! empty($search_fields))
		{
			foreach ($search_fields as $field_name => $terms)
			{
				if (isset($this->_custom_fields[$this->site][$field_name]['field_name']))
				{
					if ($sql!='')
						$sql .= ' AND ';
					$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
						
					if (strncmp($terms, '=', 1) ==  0)
					{
						/** ---------------------------------------
						 /**  Exact Match e.g.: search:body="=pickle"
						 /** ---------------------------------------*/

						$terms = substr($terms, 1);

						// special handling for IS_EMPTY
						if (strpos($terms, 'IS_EMPTY') !== FALSE)
						{
							$terms = str_replace('IS_EMPTY', '', $terms);

							$add_search = $this->EE->functions->sql_andor_string($terms, $field_column );

							// remove the first AND output by $this->EE->functions->sql_andor_string() so we can parenthesize this clause
							$add_search = substr($add_search, 3);

							$conj = ($add_search != '' && strncmp($terms, 'not ', 4) != 0) ? 'OR' : 'AND';

							if (strncmp($terms, 'not ', 4) == 0)
							{
								$sql .= '('.$add_search.' '.$conj.' '.$field_column .' != "") ';
							}
							else
							{
								$sql .= '('.$add_search.' '.$conj.' '.$field_column .' = "") ';
							}
						}
						else
						{
							$sql .= $this->EE->functions->sql_andor_string($terms, $field_column ).' ';
						}
					}
					else
					{
						/** ---------------------------------------
						 /**  "Contains" e.g.: search:body="pickle"
						 /** ---------------------------------------*/

						if (strncmp($terms, 'not ', 4) == 0)
						{
							$terms = substr($terms, 4);
							$like = 'NOT LIKE';
						}
						else
						{
							$like = 'LIKE';
						}

						if (strpos($terms, '&&') !== FALSE)
						{
							$terms = explode('&&', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'OR' : 'AND';
						}
						else
						{
							$terms = explode('|', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'AND' : 'OR';
						}

						$sql .= ' (';

						foreach ($terms as $term)
						{
							if ($term == 'IS_EMPTY')
							{
								$sql .= ' '.$field_column .' '.$like.' "" '.$andor;
							}
							elseif (strpos($term, '\W') !== FALSE) // full word only, no partial matches
							{
								$not = ($like == 'LIKE') ? ' ' : ' NOT ';

								// Note: MySQL's nutty POSIX regex word boundary is [[:>:]]
								$term = '([[:<:]]|^)'.preg_quote(str_replace('\W', '', $term)).'([[:>:]]|$)';

								$sql .= ' '.$field_column .$not.'REGEXP "'.$this->EE->db->escape_str($term).'" '.$andor;
							}
							else
							{
								$sql .= ' '.$field_column .' '.$like.' "%'.$this->EE->db->escape_like_str($term).'%" '.$andor;
							}
						}

						$sql = substr($sql, 0, -strlen($andor)).') ';
					}
				}
			}
		}
		
		$sql = preg_replace('/^\s*AND/','',$sql);
		return $sql;
	}

	/**
	 * Parse the url based on the url template (url="") from the reefine tag
	 * Returns an array of url segments organised by filter group
	 * @param string $url_template
	 * @param array $url
	 */
	private function parse_search_url($url_template,$url) {
		/*
		 * url consists of filters parameters and dividers
		* eg /search/blue/only+99.99/go
		/search/ /only+ and /go are dividers
		* blue and 99.99 are filter parameters
		*/

		$url = trim($url,'/');
		// url tag consists of filters and dividers
		/* eg /search/{colour}/only+{price}/go
		 /search/ /only+ and /go are dividers
		* {colour} and {price} are filters
		*/
		$url_template = trim($url_template,'/');
		$rx = '';
		// get list of dividers and parameters
		preg_match_all('/([^\{]*)\{([^\}]*)\}/', $url_template, $tags, PREG_SET_ORDER);

		// for each dividers/parameter pair make a regex that will parse the url
		foreach ($tags as $tag) {
			$rx .= '(' . preg_quote($tag[1],'/') . '(.+?))?';
		}
		// add on last divider
		if (preg_match('/\}([^\}]+)$/', $url_template,$last_bit)) {
			$rx .= '(' . preg_quote($last_bit[1],'/') . ')?';
		}
		$rx = '/^' . $rx . '(\/(.*?))?$/';
		// get filter parameters from url
		preg_match_all($rx, $url, $tag_values, PREG_SET_ORDER);

		$this->url_tags = array();
		$filter_values = array();
		for ($i=0;$i<count($tags);$i=$i+1) {
			// tag value is at every second index starting at 2
			$tag_value_index = ($i*2)+2;
			$tag_name = $tags[$i][2];
			// remove the |any part
			$parts = explode('|',$tag_name);
			// group name
			$group_name = $parts[0];
			$group = isset($this->filter_groups[$group_name]) ? $this->filter_groups[$group_name] : false;
				
			if ($group) {
				$url_tag = $group->get_filter_url_tags_array($tag_name,$parts);
				$this->url_tags[] = $url_tag;
				// if a tag value has been set
				if (isset($tag_values[0][$tag_value_index])) {
					$tag_value = $tag_values[0][$tag_value_index];
					$filter_value = $group->get_filter_values_from_url($tag_value,$url_tag);
					// if the filter is "any" then it won't return anything  
					if (isset($filter_value)) {
						$filter_values[$group->group_name] = $filter_value;
					}
				} else { // tag value doesn't contain "any"
					$filter_values[$group->group_name] = $group->default;
				}
				
			}
				
				
						// get the value of the tag
		}
		return $filter_values;

	}

	// must have values in filter_groups[]['values'] set
	private function add_filter_url_to_filters() {
		foreach ($this->filter_groups as $group_name => &$group) {
			$group->add_filter_url_to_filters();
		}
	}

	/**
	 * 
	 * @param string $filter_group_name
	 * @param string $filter_value
	 * @param string $is_for_redirection only include value is this is for redirecting as we want to avoid clashes when using url in the form action for search/filter range types. 
	 * @return multitype:Ambigous <multitype:, multitype:string >
	 */
	private function get_values_for_filter($filter_group_name = '', $filter_value = null, $is_for_redirection = false) {
		$filter_values = array();
		foreach ($this->filter_groups as $group_name => $group) {
			if ($group_name==$filter_group_name) {
				// if filter is null then filter will have no values.
				if (is_null($filter_value)) 
					$filter_values[$group_name] = array();
				else
					$filter_values[$group_name] = $group->get_values_for_filter($filter_value, $is_for_redirection);
			} else {
				$filter_values[$group_name] = $group->values;
			}
		}
		return $filter_values;
	}
	
	/**
	 * get the url of a filter given a particular filter group name
	 * @param string $filter_group_name
	 * @param string $filter_value
	 * @return Ambigous <Ambigous, string, unknown>
	 */
	public function get_filter_url($filter_group_name = '', $filter_value = null, $is_for_redirection = false) {
		$filter_values = $this->get_values_for_filter($filter_group_name, $filter_value, $is_for_redirection);
		if ($this->method=='url') {
			return $this->get_filter_url_from_filter_values($filter_values);
		} else {
			return $this->get_filter_querystring_from_filter_values($filter_values);
		}
	}
	
	/**
	 * Make a URL for a filter for method="url" 
	 * @param unknown $filter_values
	 * @return Ambigous <string, unknown>
	 */
	private function get_filter_url_from_filter_values($filter_values) {
		//$url_template = trim($this->url_tag,'/');
		$url_template = $this->url_output;
		$result = $url_template;
		// for each tag in reefine's url="" parameter
		foreach ($this->url_tags as $tag) {
			// group name
			$group_name = $tag['group_name'];
			$group = $this->filter_groups[$group_name];
			$group_url_tag_replacement = $group->get_group_url_tag_replacement($tag,$filter_values[$group_name]);
			$result = str_replace($tag['tag'],$group_url_tag_replacement,$result);
		}
		// add a leading slash if one isn't provided
		if (strpos($result,'/')!==0 && strpos($result,'http://')!==0 && strpos($result,'https://')!==0) {
			$result = '/' . $result;
		}
	
		return $result;
	}


	/**
	 * Make a URL for a filter for method="url"
	 * @param unknown $filter_values
	 * @return Ambigous <string, unknown>
	 */
	private function get_filter_querystring_from_filter_values($filter_values) {
		$qs = array();
		// for each tag in reefine's url="" parameter
		foreach ($this->filter_groups as $group) {
			if (isset($filter_values[$group->group_name])) $qs = array_merge($qs,$group->get_filter_querystring_from_filter_values($filter_values[$group->group_name]));
		}
		$current_url = $this->EE->uri->uri_string();
		// remove page number, we want to start at Page 1 each time.
		$current_url = preg_replace('/\/P\d+\/?$/','/',$current_url);
		$result = $current_url . '?' . implode($qs,'&');
		// add a leading slash if one isn't provided
		//if (strpos($result,'/')!==0 && strpos($result,'http://')!==0 && strpos($result,'https://')!==0) {
		//	$result = '/' . $result;
		//}
	
		return $result;
	}
	
	
		
	
	
	private function urlencode($value) {
		// EE gives the error "The URI you submitted has disallowed characters." to a lot of special chars
		// even if they're encoded so put an @ followed by the char HEX code to decode laters, eg when decoded ? will look like @3F
		return strtr(urlencode($value), array(
				'%3F' => '%403F', // ? 
				'%40' => '%4040', // @
				'%2F' => '%402F', // /
				'%5C' => '%405C', // \
				'%3E' => '%403E', // >
				'%3C' => '%403C', // <
				'%7B' => '%407B', // {
				'%7D' => '%407D', // }
				'%2B' => '%402B', // +
				'%27' => '%4027' // '
		));
	}
	
	public function create_url($url)
	{
		$url = $this->EE->functions->create_url($url);
		// double encode URL
		$url = preg_replace("#(^|[^:])//+#", "\\1/", $url);
		return $url;
	}
	
	
	/**
	 * Double encode all values in an array using urlencode
	 * @param unknown $arr
	 * @return mixed|multitype:
	 */
	public function urlencode_array($arr) {
		foreach ($arr as &$value) {
			$value = $this->urlencode($value);
		}
		return $arr;
	}
	
	
	public function urldecode($value) {
		
		if (is_array($value)) {
			$result = array();
			foreach ($value as $key=>$v) {
				$result[$key]=$this->urldecode($v);
			}
			return $result;
		} else {
			
			// Do the reverse of _filter_uri() function in system/codeigniter/core/system/URI.php
			// Convert entities back to programatic characters 
			$bad	= array('$',		'(',		')',		'%28',		'%29');
			$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');
			//  go from good to bad.
			$value = str_replace($good, $bad, $value);
				
			
			return urldecode(str_replace('@','%',str_replace('%40','%',$value)));
		}
	}

	/**
	 *
	 * @param unknown_type $filter_values
	 * @throws Exception
	 */
	private function add_filter_values($filter_values) {
		$this->active_filter_count = 0;
		foreach ($filter_values as $group_name => $values) {
			if (isset($this->filter_groups[$group_name])) {
				$group = &$this->filter_groups[$group_name];
				$group->add_filter_values($values);
				// add to active filters count unless it's something  like paging or orderby fields which have show_separate_only = yes
				if (!$group->show_separate_only)
					$this->active_filter_count += count($values);
			} else {
				throw new Exception('filter not found');
			}
		}
	}

	function tagdataHasTag($tag) {
		return (strpos($this->tagdata, '{'.$tag.'}')!==false);	
	}
	
	function get_client_json() {
		return json_encode(array(
			'filter_url'=>$this->get_filter_url(),
			'filter_values'=>$this->filter_values
		));
	}
		
	// create tag data
	private function get_tag_data_result($results) {
		$delimiter = '|';
		$tag = array();
		$tag['entries'] = array();
		$tag['breadcrumb'] = array();
		$tag['total_active_filters'] = $this->active_filter_count;
		$tag['total_entries'] = count($results);
		$tag['active_groups'] = array();
		$tag['search_groups'] = array();
		$tag['list_groups'] = array();
		$tag['date_range_groups'] = array();
		$tag['number_range_groups'] = array();
		$tag['tree_groups'] = array();
		$tag['method'] = $this->method;
		$tag['client_json'] = $this->get_client_json();
		$total_entries = count($results);
		$entry_ids = '';

		if (count($results)==0) {
			$entry_ids = '-1'; // no entries!
		} else {
			foreach($results as $row) {
				$entry_ids_arr[] = $row['entry_id'];
			}
				
			if ($this->fixed_order) {
				$ordered_ids = array();
				foreach (explode('|',$this->fixed_order) as $id) {
					if (array_search($id, $entry_ids_arr)!==false) {
						$ordered_ids[] = $id;
					}
				}
				$total_entries = count($ordered_ids);
				$entry_ids = implode('|',$ordered_ids);
			} else {
				$entry_ids = implode('|',$entry_ids_arr);
			}

		}
		$tag['entries'][0]['entry_ids'] = $entry_ids;
		$tag['entries'][0]['total_entries'] = $total_entries;


		

		// html encode all filter data such as values
		//$this->html_encode_filters();

		// now to do the filters. must be converted from associative array to normal array
		// EE has bugs when a tag pair is used more than once so make a copy for the breadcrumb
		if ($this->tagdataHasTag('filter_groups')) {
			foreach ($this->filter_groups as $group_name => &$group) {
				if (!$group->show_separate_only)
					$tag['filter_groups'][] = $group->get_filters_for_output(false);
			}
		}

		foreach ($this->filter_groups as $group_name => &$group) {
			// go through each filter group to see if a seperate filter is specified			
			if ($this->tagdataHasTag($group_name)) 
				$tag[$group_name] = array($group->get_filters_for_output(false));
			// make the type group tag tag if it is specified (eg number_range_groups)
			
			
			// add to group type tags, eg list_groups
			$type_group_name = $group->type . '_groups';
			if ($this->tagdataHasTag($type_group_name) && !$group->show_separate_only) {
				$tag[$type_group_name][] = $group->get_filters_for_output(false);
			}
			
			// make the {active_filters} tag
			if ($this->tagdataHasTag('active_groups') && count($group->values)>0 && !$group->show_separate_only) {
				$tag['active_groups'][] = $group->get_filters_for_output(true);
			}
		}
		//die(json_encode($tag));
		// parse it
		return $tag;

	}
	
	

	// get array of channel ids from | seperate list of channel names
	private function get_channel_ids($channel_names) {
		// Get a list of channel names for sql statement
		// as on line 1682 in mod.channel.php
		$where = $this->EE->functions->sql_andor_string($channel_names, 'channel_name');
		// remove the initial AND
		$where = preg_replace('/^\s*AND\s*/', '', $where);
		$where .= ' AND `site_id` = ' . intval($this->site);
		$result = $this->db->select('channel_id')->from("{$this->dbprefix}channels")->where($where)->get()->result_array();
		$channel_ids = array();
		foreach ($result as $row)
			$channel_ids[] = $row['channel_id'];
		return $channel_ids;
	}


	/**
	 * Fetches custom channel fields from page flash cache.
	 * If not cached, runs query and caches result.
	 * @access private
	 * @return boolean
	 */
	private function _fetch_custom_channel_fields()
	{
		// as standard custom field data is used/stored in exactly the same way by the channel module
		// we'll use the 'channel' class name as the cache key to avoid redundancy
		if (isset($this->EE->session->cache[$this->class_name]['custom_channel_fields']))
		{
			$this->_custom_fields = $this->EE->session->cache[$this->class_name]['custom_channel_fields'];
			return true;
		}
		$this->_custom_fields = array($this->site => array());
		// not found so cache them
		$sql = "SELECT field_id, field_type, field_name, site_id, field_label, concat('field_id_',field_id) as field_column, 0 as is_title_field
		FROM {$this->dbprefix}channel_fields WHERE site_id = " . intval($this->site);

		$query = $this->db->query($sql);
		
		if ($query->num_rows > 0)
		{
			foreach ($query->result_array() as $row)
			{
				// assign standard custom fields
				$this->_custom_fields[$row['site_id']][$row['field_name']] = $row;
			}
		}

		foreach ($this->_custom_fields as $site_id => $field) {
			$this->_custom_fields[$site_id]['title'] = array('field_type' => 'text','field_name' => 'title','site_id' => $site_id, 'field_label' => 'title', 'field_column' => 'title',  'is_title_field' => 1 );
			$this->_custom_fields[$site_id]['entry_date'] = array('field_type' => 'date','field_name' => 'entry_date','site_id' => $site_id, 'field_label' => 'Entry Date', 'field_column' => 'entry_date',  'is_title_field' => 1);
			$this->_custom_fields[$site_id]['expiration_date'] = array('field_type' => 'date','field_name' => 'expiration_date','site_id' => $site_id, 'field_label' => 'Expiration Date', 'field_column' => 'expiration_date',  'is_title_field' => 1);
			$this->_custom_fields[$site_id]['status'] = array('field_type' => 'text','field_name' => 'status','site_id' => $site_id, 'field_label' => 'Status', 'field_column' => 'status',  'is_title_field' => 1 );
		}
		$this->EE->session->cache[$this->class_name]['custom_channel_fields'] = $this->_custom_fields;
		return true;
		
	}

	/**
	 * Fetches custom category fields from page flash cache.
	 * If not cached, runs query and caches result.
	 * @access private
	 * @return boolean
	 */
	private function _fetch_custom_category_fields()
	{
		if (isset($this->EE->session->cache[$this->class_name]['custom_category_fields']))
		{
			$this->_cat_fields = $this->EE->session->cache[$this->class_name]['custom_category_fields'];
			return true;
		}

		// not found so cache them
		$sql = "SELECT field_id, field_name, site_id
		FROM {$this->dbprefix}category_fields";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0)
		{
			foreach ($query->result_array() as $row)
			{
				// assign standard fields
				$this->_cat_fields[$row['site_id']][$row['field_name']] = $row['field_id'];
			}
			$this->EE->session->cache[$this->class_name]['custom_category_fields'] = $this->_cat_fields;
			return true;
		}
		else
		{
			return false;
		}
	}

	private function fetch_categories()
	{
		if (isset($this->EE->session->cache[$this->class_name]['categories']))
		{
			$this->categories = $this->EE->session->cache[$this->class_name]['categories'];
			return true;
		}

		// not found so cache them
		$sql = "
		SELECT
		`cat_id`,
		`site_id`,
		`group_id`,
		`parent_id`,
		`cat_name`,
		`cat_url_title`
		FROM `{$this->dbprefix}categories`
		";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->categories[$row['site_id']][$row['group_id']][$row['cat_url_title']] = array(
						'cat_id' => $row['cat_id'],
						'name' => $row['cat_name'],
						'parent_id' => $row['parent_id']
				);
			}
			$this->EE->session->cache[$this->class_name]['categories'] = $this->categories;
			return true;
		}
		else
		{
			return false;
		}
	}

	// 	http://stackoverflow.com/a/10462308/1102000
	private function arrayCopy( array $array, $except_key = null ) {
		$result = array();
		foreach( $array as $key => $val ) {
			if ( $key===$except_key ) {
				// dont copy any entries that match $except_key
			} elseif( is_array( $val ) ) {
				$result[$key] = $this->arrayCopy( $val, $except_key );
			} elseif ( is_object( $val ) ) {
				$result[$key] = clone $val;
			} else {
				$result[$key] = $val;
			}
		}
		return $result;
	}

	private function in_subarray(&$arr,$find_key,$find_val) {
		foreach($arr as $key => &$item) {
			if (isset($item[$find_key]) && $item[$find_key]==$find_val)
				return $key;
		}
		return false;
	}
	
	// array('a'=>'b','c'=>'d') becomes ,b as a, d as c
	public static function column_implode(&$array) {
		$result = "";
		$glue=', ';
		foreach ($array as $key => $value) {
			$result .=  $glue . $value . ' as ' . $key;
		}
	
		return $result;
	}
	
	/**
	 * 
	 * @param unknown $field_name
	 * @throws Exception
	 * @return Reefine_field
	 */
	function get_field_obj($field_name) {
		// string to append on class name (eg _relationship )
		$class_append = '';
		// the actual field name in ee
		$ee_field_name = $field_name;
		// child field name for fields that have a subfield
		$child_field = '';
		$field_type='';
		
		// if field name conmtains a colon
		if (strpos($field_name,':')!==false) {
			$field_segments = explode(':', $field_name);
			//  Example: if a have a Product will relationship field to Downloads that relate to that product
			// If I wanted to show a list of products with a filter for downloads I would use filter:group:fields="downloads:title"
			// If I wanted to show a list of downloads with a filter of products I would use filter:group:field="parents:downloads:title"
			if (count($field_segments) == 3 && $field_segments[0] == 'parents') {
				// if field begins with parents: then it's a parents relationship field
				$ee_field_name = $field_segments[1];
				if (count($field_segments) > 2) {
					// child_field would a field on the parent entry
					$child_field = $field_segments[2];
				}
				// field type will probably be "relationship"
				$field_type = $this->_custom_fields[$this->site][$ee_field_name]['field_type'];
				// currently this will mean the class is Reefine_field_parents_relationship
				$class_append = '_parents_' . $field_type;
				
			} else {
				$ee_field_name = $field_segments[0];
				$child_field = $field_segments[1];
				$field_type = $this->_custom_fields[$this->site][$ee_field_name]['field_type'];
				$class_append = '_' . $field_type;
			}
			
			
		} else {
			// field doesnt have a colon - lets look at the field type anyway
			$field_type = $this->_custom_fields[$this->site][$field_name]['field_type'];
			// if the filter is a relationship/playa field and no subfield is specified then we show the title in the filter
			if ($field_type=='relationship' || $field_type=='playa')
				$class_append = '_' . $field_type;
					
		}
			
		// Publisher module detected so check if a class exists for the publisher fields
		if (isset($this->EE->publisher_model) && class_exists('Reefine_field_publisher' . $class_append)) {
			$field_class='Reefine_field_publisher' . $class_append;
			return new $field_class($this,$field_name, $ee_field_name,$child_field);
			// publisher module doesn't exist or so just go
		} else if (class_exists('Reefine_field' . $class_append)) {
			$field_class='Reefine_field' . $class_append;
			return new $field_class($this,$field_name, $ee_field_name,$child_field);
		
		} else {
			throw new Exception('Reefine error: Fieldtype "' . $field_type . '" not supported.  Field is "' . $field_name . '"');
		}
	}

	/**
	 *
	 * @param unknown $field_name
	 * @throws Exception
	 * @return Reefine_field
	 */
	function get_category_field_obj($category_group,$filter_group) {
			
		// Publisher module detected so check if a class exists for the publisher fields
		if (isset($this->EE->publisher_model) && class_exists('Reefine_field_publisher_category')) {
			return new Reefine_field_publisher_category($this,$category_group,$filter_group);
			// publisher module doesn't exist or so just go
		} else if (class_exists('Reefine_field_category')) {
			return new Reefine_field_category($this,$category_group,$filter_group);
	
		} else {
			throw new Exception('Reefine error: Fieldtype cat not supported.');
		}
	}
	
}

require("fields/Reefine_field.php");

require("fields/Reefine_field_category.php");

require("fields/Reefine_field_store.php");

require("fields/Reefine_field_publisher.php");

require('fields/Reefine_field_relationship.php');

require('fields/Reefine_field_parents_relationship.php');

require('fields/Reefine_field_playa.php');

require('fields/Reefine_field_publisher_playa.php');

require('fields/Reefine_field_grid.php');

require('fields/Reefine_util_grid_fields.php');

require('fields/Reefine_field_matrix.php');

require('fields/Reefine_util_matrix_fields.php');

require('groups/Reefine_group.php');

require('groups/Reefine_group_dummy.php');

require('groups/Reefine_group_list.php');

require('groups/Reefine_group_number_range.php');

require('groups/Reefine_group_date_range.php');

require('groups/Reefine_group_search.php');

require('groups/Reefine_group_tree.php');

require('groups/Reefine_group_month_list.php');


