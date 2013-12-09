<?php 
require('libs/reefine_theme.php');

class Reefine {

	var $return_data	= '';
	var $p_limit = '';
	var $filter_channel;
	var $filter_groups;
	var $url_tag;
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
		$this->EE =& get_instance();
		$this->EE->load->library('logger');
		$this->timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;
		

		try {
			// if a second tag part is specified then stop processing
			if (count($this->EE->TMPL->tagparts)>1)
				return;


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
			$this->filter_id = md5(serialize($this->filter_groups));

			
			// change expressione ngin uri so paging works
			if ($this->method=='url') {
				if (!$this->disable_search) $this->do_redirects_for_text_inputs();
				$this->change_uri_for_paging();
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
				ee()->functions->redirect($this->create_url($url));
			}
		}
	}
	
	private function parse_final_template($tagdata,$tag_array) {
		// http://expressionengine.stackexchange.com/questions/1347/how-can-i-manually-parse-template-code-from-php
		$html = '';
		// back up existing TMPL class
		$this->EE->load->library('template');
		$OLD_TMPL = isset($this->EE->TMPL) ? $this->EE->TMPL : NULL;
		$this->EE->TMPL = new EE_Template();
		$html = $this->EE->TMPL->parse_variables_row($tagdata, $tag_array);
		
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
		$this->EE->TMPL->parse($html);
		$html = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);
		
		// restore old TMPL class
		$this->EE->TMPL = $OLD_TMPL;
		
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
				$this->url = $this->EE->router->uri->uri_string;
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
			if ($group->post_contains_filter_value() || $this->is_form_post) {
				
				$filter_values[$group_name] = $group->get_filter_value_from_post();
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
		$this->site = $this->EE->TMPL->fetch_param('site', $this->EE->config->item('site_id'));
		$this->status = $this->EE->TMPL->fetch_param('status', $this->EE->config->item('open'));
		$this->disable_search = $this->EE->TMPL->fetch_param('disable_search', $this->EE->config->item('disable_search'));
		// methods: url,post,get,ajax,
		$this->method = $this->EE->TMPL->fetch_param('method', 'url');
		$this->url_tag = $this->EE->TMPL->fetch_param('url', '');
		$this->theme_name = $this->EE->TMPL->fetch_param('theme', '');
		$this->seperate_filters = ($this->EE->TMPL->fetch_param('seperate_filters', '') == 'yes' ? true : false);
		// get list of channel ids to choose from
		if (!empty($filter_channel)) {
			$this->channel_ids = $this->get_channel_ids($filter_channel);
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
				
			// include categories in select using a global category table that is left joined
			$this->include_categories=true; // yes to joining a global category table
			$this->search_field_where_clause .= $this->search_field_where_clause=='' ? '' : ' AND ';
			$this->search_field_where_clause .= sprintf("global_cat.cat_url_title=%s",
			$this->db->escape($this->EE->TMPL->tagparams['category_url']));
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
	 * @return string
	 */
	public function get_query_join_sql($include_group) {
		$joins = array();
		
		// also left outer join categories
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
		$sql .= $this->get_query_join_sql('');
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
		$clauses[] = "({$this->dbprefix}channel_titles.status = '' OR {$this->dbprefix}channel_titles.status = " . $this->db->escape($this->status) . ")";
		// limit to current site
		$clauses[] = "{$this->dbprefix}channel_titles.site_id = " . intval($this->site);
		// hide expired entries if neccesary
		//if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		$clauses[] =  "({$this->dbprefix}channel_titles.expiration_date = 0 OR {$this->dbprefix}channel_titles.expiration_date > {$this->timestamp}) ";
		// hide future entries if neccesary
		//if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		$clauses[] = "{$this->dbprefix}channel_titles.entry_date < ".$this->timestamp;

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
			$rx .= '(' . preg_quote($tag[1],'/') . '(.*?))?';
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
		$url_template = $this->url_tag;
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
			$qs = array_merge($qs,$group->get_filter_querystring_from_filter_values($filter_values[$group->group_name]));
		}
		$result = ee()->uri->uri_string() . '?' . implode($qs,'&');
		// add a leading slash if one isn't provided
		//if (strpos($result,'/')!==0 && strpos($result,'http://')!==0 && strpos($result,'https://')!==0) {
		//	$result = '/' . $result;
		//}
	
		return $result;
	}
	
	
		
	
	
	private function urlencode($value) {
		// double encode URL
		return str_replace('%', '%40', urlencode($value)); 
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
			return urldecode(str_replace('@','%',str_replace('%40','%',$value)));
		}
	}

	/**
	 *
	 * @param unknown_type $filter_values
	 * @throws Exception
	 */
	private function add_filter_values($filter_values) {
		$this->active_filter_count = count($filter_values);
		foreach ($filter_values as $group_name => $values) {
			if (isset($this->filter_groups[$group_name])) {
				$group = &$this->filter_groups[$group_name];
				$group->add_filter_values($values);

			} else {
				throw new Exception('filter not found');
			}
		}
	}

	function tagdataHasTag($tag) {
		return (strpos($this->tagdata, '{'.$tag.'}')!==false);	
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
		$tag['number_range_groups'] = array();
		$tag['method'] = $this->method;
		
		$entry_ids = '';

		if (count($results)==0) {
			$entry_ids = '-1'; // no entries!
		} else {
			// loop through found entries
			foreach($results as $row)
			{
				$entry_ids .= ($entry_ids=='' ? '' : $delimiter).$row['entry_id'];
			}

		}
		$tag['entries'][0]['entry_ids'] = $entry_ids;
		$tag['entries'][0]['total_entries'] = count($results);


		

		// html encode all filter data such as values
		//$this->html_encode_filters();

		// now to do the filters. must be converted from associative array to normal array
		// EE has bugs when a tag pair is used more than once so make a copy for the breadcrumb
		if ($this->tagdataHasTag('filter_groups')) {
			foreach ($this->filter_groups as $group_name => &$group) {
				$tag['filter_groups'][] = $group->get_filters_for_output();
			}
		}

		foreach ($this->filter_groups as $group_name => &$group) {
			// go through each filter group to see if a seperate filter is specified			
			if ($this->tagdataHasTag($group_name)) 
				$tag[$group_name] = array($group->get_filters_for_output());
			// make the type group tag tag if it is specified (eg number_range_groups)
			$type_group_name = $group->type . '_groups';
			
			if ($this->tagdataHasTag($type_group_name)) {
				$tag[$type_group_name][] = $group->get_filters_for_output();
			}
			
			// make the {active_filters} tag
			if ($this->tagdataHasTag('active_groups') && count($group->values)>0) {
				$tag['active_groups'][] = $group->get_filters_for_output(true);
			}
		}
		
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

		// not found so cache them
		$sql = "SELECT field_id, field_type, field_name, site_id, field_label, concat('field_id_',field_id) as field_column, 0 as is_title_field
		FROM {$this->dbprefix}channel_fields ";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0)
		{
			foreach ($query->result_array() as $row)
			{
				// assign standard custom fields
				$this->_custom_fields[$row['site_id']][$row['field_name']] = $row;
			}
			foreach ($this->_custom_fields as $site_id => $field) {
				$this->_custom_fields[$site_id]['title'] = array('field_type' => 'text','field_name' => 'title','site_id' => $site_id, 'field_label' => 'title', 'field_column' => 'title',  'is_title_field' => 1 );
				$this->_custom_fields[$site_id]['entry_date'] = array('field_type' => 'date','field_name' => 'entry_date','site_id' => $site_id, 'field_label' => 'Entry Date', 'field_column' => 'entry_date',  'is_title_field' => 1);
				$this->_custom_fields[$site_id]['expiration_date'] = array('field_type' => 'date','field_name' => 'expiration_date','site_id' => $site_id, 'field_label' => 'Expiration Date', 'field_column' => 'expiration_date',  'is_title_field' => 1);
			}
			$this->EE->session->cache[$this->class_name]['custom_channel_fields'] = $this->_custom_fields;
			return true;
		}
		else
		{
			return false;
		}
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
	
	/**
	 * 
	 * @param unknown $field_name
	 * @throws Exception
	 * @return Reefine_field
	 */
	function get_field_obj($field_name) {
		// if field name conmtains a colon
		if (strpos($field_name,':')!==false) {
			list($ee_field_name,$child_field) = explode(':', $field_name);
			$field_type = $this->_custom_fields[$this->site][$ee_field_name]['field_type'];
			$field_class = 'Reefine_field_' . $field_type;
			if (class_exists($field_class)) {
				return new $field_class($this,$field_name, $ee_field_name,$child_field);
			} else {
				throw new Exception('Reefine error: Fieldtype ' . $field_type . ' not supported.');
			}
		} else if (isset($this->EE->publisher_model)) {
			// Publisher module detected so check the publisher fields instead
			return new Reefine_field_publisher($this,$field_name, $field_name);
		} else {
			$field_type = $this->_custom_fields[$this->site][$field_name]['field_type'];
			if ($field_type=='relationship')
				return new Reefine_field_relationship($this, $field_name, $field_name, '');
			if ($field_type=='playa')
				return new Reefine_field_playa($this, $field_name, $field_name, '');
			else
				return new Reefine_field($this, $field_name);
		}
	}
}

/**
 * Generic field class used to gather informaiton about the field in the database.
 * @author Patrick
 *
 */
class Reefine_field {
	/**
	 * Field name as it appears int he reefine tag eg colour, store:price
	 * @var unknown
	 */
	protected $field_name;
	/**
	 * Column name to be used in SQL for value field, used in URL. eg field_id_27
	 * @var unknown
	 */
	protected $column_name;
	/**
	 * Column name in field for title field that is displayed to user
	 * @var unknown
	 */
	protected $title_column_name;
	/**
	 * Additional sql to be added to join
	 * @var unknown
	 */
	protected $join_sql = '';
	/**
	 * Name of field in channel entry eg store:price would be store
	 * @var unknown
	 */
	protected $ee_field_name = '';
	protected $field_label = '';
	protected $ee_field_info;
	protected $ee_type = '';
	/**
	 * Reefine object
	 * @var Reefine
	 */
	protected $reefine;
	/**
	 * Database column name (eg field_id_2)
	 * @var unknown
	 */
	protected $db_column;
	
	protected $channel_data_alias = '';
	
	protected $channel_titles_alias = '';
	
	function __construct($reefine, $field_name) {
		
		$this->reefine = $reefine;
		$this->field_name = $field_name;
		$dbprefix = $reefine->dbprefix;
		$this->channel_data_alias = "{$dbprefix}channel_data";
		$this->channel_titles_alias = "{$dbprefix}channel_titles";
		$this->assign_field_info($field_name);
	}
	
	function get_title_column() {
		return $this->get_value_column();
	}
	
	function get_value_column() {
		if ($this->get_field_by_key($this->field_name,'is_title_field'))
			return $this->channel_titles_alias  . '.' . $this->get_field_by_key($this->field_name,'field_column');
		else
			return $this->channel_data_alias . '.' . $this->get_field_by_key($this->field_name,'field_column');
	}
	
	function get_join_sql() {
		return '';
	}
	
	function assign_field_info($ee_field_name) {
		if (isset($this->reefine->_custom_fields[$this->reefine->site][$ee_field_name])) {
			$ee_field = $this->reefine->_custom_fields[$this->reefine->site][$ee_field_name];
			$this->ee_field_name = $ee_field_name;
			$this->field_label = $ee_field['field_label'];
			$this->ee_type= $ee_field['field_type'];
			$this->db_column = $ee_field['field_column'];
			$this->ee_field_info = $ee_field;
		}
	}
	function get_field_by_name($field_name) {
		if (isset($this->reefine->_custom_fields[$this->reefine->site][$field_name])) 
			return $this->reefine->_custom_fields[$this->reefine->site][$field_name];
		else
			return null;
	}
	
	function get_field_by_key($field_name,$key) {
		$field = $this->get_field_by_name($field_name);
		return $field[$key];
	}
	
	function get_field() {
		return $this->get_field_by_name($this->ee_field_name);
	}
	
}

/**
 * Field for the expresso Store custom fieldtpye
 * @author Patrick
 *
 */
class Reefine_field_store extends Reefine_field {
	/**
	 * column in expresso products table eg price
	 * @var string
	 */
	var $child_column;
	/**
	 * table alias used in sql statement
	 * @var string
	 */
	var $table_alias; 
	function __construct($reefine,$field_name,$ee_field_name,$child_column) {
		$dbprefix = $reefine->dbprefix;
		$this->reefine = $reefine;
		$this->assign_field_info($ee_field_name);
		$this->field_name = $field_name;
		$this->child_column=$child_column;
		$this->table_alias = 'store_products_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name);
		
	}
	
	
	function get_value_column() {
		$this->column_name = "{$this->table_alias}.{$this->child_column}";
	}
	
	function get_title_column() {
		return $this->get_value_column();
	}
	
	function get_join_sql() {
		return "LEFT OUTER JOIN {$this->reefine->dbprefix}store_products {$this->table_alias} " .
			"ON {$this->table_alias}.entry_id = {$this->channel_data_alias}.entry_id ";
	}
	
}

/**
 * Field class for dealing with entries with the Publisher Module
 * @author Patrick
 *
 */
class Reefine_field_publisher extends Reefine_field {
	
	private $session_language_id;
	
	function __construct($reefine,$field_name,$ee_field_name) {
		parent::__construct($reefine, $ee_field_name);
		
		$this->reefine = $reefine;
		
		$this->field_name = $ee_field_name;
		
		$this->session_language_id = intval(ee()->publisher_model->current_language['id']);
		
	}
	
	function get_value_column() {
		return "{$this->reefine->dbprefix}publisher_data.{$this->db_column}";
	}
	
	function get_title_column() {
		return $this->get_value_column();
	}
	
	function get_join_sql() {
		return "LEFT OUTER JOIN {$this->reefine->dbprefix}publisher_data " .
		"ON {$this->reefine->dbprefix}publisher_data.entry_id = {$this->channel_data_alias}.entry_id " .
		"AND {$this->reefine->dbprefix}publisher_data.publisher_status IN ('', " . $this->reefine->db->escape($this->reefine->status) . ") " .
		"AND {$this->reefine->dbprefix}publisher_data.publisher_lang_id = {$this->session_language_id} ";
	}
	
}

class Reefine_field_relationship extends Reefine_field {
	
	private $relation_field_id;
	
	private $child_field_name;
	private $parent_field_name;
	private $table_alias;
	private $table_alias_titles;
	private $table_alias_data;
	
	function __construct($reefine,$field_name,$parent_field_name,$child_field_name) {
		parent::__construct($reefine, $parent_field_name);
		
		$this->reefine = $reefine;
		
		$this->relation_field_id = $this->get_field_by_key($parent_field_name, 'field_id');
		
		$this->parent_field_name = $parent_field_name;
		$this->child_field_name=$child_field_name;
		
		$this->table_alias = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id);
		$this->table_alias_titles = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id) . '_titles';
		$this->table_alias_data = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id) . '_data';
		
		
	
	}
	
	function get_value_column() {
		if ($this->child_field_name=='')
			// Return url_title so we get a nice url for list filters
			return "{$this->table_alias_titles}.url_title";
		else if ($this->get_field_by_key($this->child_field_name,'is_title_field'))
			// return full title, good for search filters
			return "{$this->table_alias_titles}." . $this->get_field_by_key($this->child_field_name,'field_column');
		else
			// return column data
			return "{$this->table_alias_data}." . $this->get_field_by_key($this->child_field_name,'field_column');
	}
	
	function get_title_column() {
		if ($this->child_field_name=='' || $this->child_field_name=='title')
			return "{$this->table_alias_titles}.title";
		else
			return "{$this->table_alias_data}." . $this->get_field_by_key($this->child_field_name,'field_column');
	}
	
	function get_join_sql() {
		// join the main relationship table
		$joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}relationships {$this->table_alias} " .
		"ON {$this->table_alias}.parent_id = {$this->channel_data_alias}.entry_id " .
		"AND {$this->table_alias}.field_id = {$this->relation_field_id} ");
		// if we just need the titles for "relation" or "relation:title" fields
		if ($this->child_field_name=='' || $this->child_field_name=='title')
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_titles {$this->table_alias_titles} " .
			"ON {$this->table_alias_titles}.entry_id = {$this->table_alias}.child_id ";
		else
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_data {$this->table_alias_data} " .
			"ON {$this->table_alias_data}.entry_id = {$this->table_alias}.child_id ";
		return $joins;
	}
}

class Reefine_field_playa extends Reefine_field {

	private $relation_field_id;

	private $child_field_name;
	private $parent_field_name;
	private $table_alias;
	private $table_alias_titles;
	private $table_alias_data;

	function __construct($reefine,$field_name,$parent_field_name,$child_field_name) {
		parent::__construct($reefine, $parent_field_name);

		$this->reefine = $reefine;

		$this->relation_field_id = $this->get_field_by_key($parent_field_name, 'field_id');

		$this->parent_field_name = $parent_field_name;
		$this->child_field_name=$child_field_name;

		$this->table_alias = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id);
		$this->table_alias_titles = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id) . '_titles';
		$this->table_alias_data = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id) . '_data';



	}

	function get_value_column() {
		if ($this->child_field_name=='')
			// Return url_title so we get a nice url for list filters
			return "{$this->table_alias_titles}.url_title";
		else if ($this->child_field_name=='title')
			// return full title, good for search filters
			return "{$this->table_alias_titles}.title";
		else
			// return column data
			return "{$this->table_alias_data}." . $this->get_field_by_key($this->child_field_name,'field_column');
	}

	function get_title_column() {
		if ($this->child_field_name=='' || $this->child_field_name=='title')
			return "{$this->table_alias_titles}.title";
		else
			return "{$this->table_alias_data}." . $this->get_field_by_key($this->child_field_name,'field_column');
	}

	function get_join_sql() {
		// join the main relationship table
		$joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}playa_relationships {$this->table_alias} " .
		"ON {$this->table_alias}.parent_entry_id = {$this->channel_data_alias}.entry_id " .
		"AND {$this->table_alias}.parent_field_id = {$this->relation_field_id} ");
		// if we just need the titles for "relation" or "relation:title" fields
		if ($this->child_field_name=='' || $this->child_field_name=='title')
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_titles {$this->table_alias_titles} " .
			"ON {$this->table_alias_titles}.entry_id = {$this->table_alias}.child_entry_id ";
		else
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_data {$this->table_alias_data} " .
			"ON {$this->table_alias_data}.entry_id = {$this->table_alias}.child_entry_id ";
		return $joins;
	}
	
	function get_field() {
		return $this->get_field_by_name($this->parent_field_name);
	}
}

class Reefine_field_grid extends Reefine_field {

	private $relation_field_id;

	private $child_field_name;
	private $parent_field_name;
	private $table_alias;
	private $table_alias_titles;
	private $table_alias_data;
	
	private $grid_field;

	function __construct($reefine,$field_name,$parent_field_name,$child_field_name) {
		parent::__construct($reefine, $parent_field_name);

		$this->reefine = $reefine;
		$this->parent_field_name = $parent_field_name;
		$this->child_field_name=$child_field_name;
		
		$grid_fields = Reefine_util_grid_fields::get_instance($reefine);
		
		$this->grid_field = $grid_fields->get_grid_field($this->ee_field_info['field_id'],$child_field_name); 
		
		$this->table_alias = 'grid_' . $this->grid_field['field_id']; //preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id);

	}

	function get_value_column() {
		return "{$this->table_alias}.col_id_{$this->grid_field['col_id']}"; // . $this->get_field_by_key($this->child_field_name,'field_column');
	}

	function get_title_column() {
		return $this->get_value_column();
	}

	function get_join_sql() {
		// join the channel_grid_field_... table
		$joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}channel_grid_field_31 {$this->table_alias} " .
		"ON {$this->table_alias}.entry_id = {$this->channel_data_alias}.entry_id ");
		
		return $joins;
	}
}

class Reefine_util_grid_fields {
	private static $instance;
	/**
	 * @var Reefine
	 */ 
	private $reefine;
	
	private $grid_fields = array();
	
	public static function get_instance($reefine) {
		if (!isset(Reefine_util_grid_fields::$instance)) 
			Reefine_util_grid_fields::$instance = new Reefine_util_grid_fields($reefine);
		return Reefine_util_grid_fields::$instance;
	}
	
	/**
	 * 
	 * @param Reefine $reefine
	 */
	private function __construct($reefine) {
		$this->reefine = $reefine;
		$rows = ee()->db->select('col_id, field_id, col_type, col_label, col_name')
		->where('content_type', 'channel')
		->get("{$this->reefine->dbprefix}grid_columns")->result_array();
		foreach ($rows as $row) {
			$this->grid_fields[$row['field_id']][$row['col_name']] = $row;
		}		
	}
	
	public function get_grid_field($field_id,$col_name) {
		if (isset($this->grid_fields[$field_id][$col_name]))
			return $this->grid_fields[$field_id][$col_name];
		else 
			throw new Exception ("Grid column " . $col_name . " not found.");
	}
	
}

class Reefine_field_matrix extends Reefine_field {

	private $relation_field_id;

	private $child_field_name;
	private $parent_field_name;
	private $table_alias;
	private $grid_field;

	function __construct($reefine,$field_name,$parent_field_name,$child_field_name) {
		parent::__construct($reefine, $parent_field_name);

		$this->reefine = $reefine;
		$this->parent_field_name = $parent_field_name;
		$this->child_field_name=$child_field_name;

		$grid_fields = Reefine_util_matrix_fields::get_instance($reefine);

		$this->grid_field = $grid_fields->get_grid_field($this->ee_field_info['field_id'],$child_field_name);

		$this->table_alias = 'matrix_' . $this->grid_field['field_id']; //preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id);

	}

	function get_value_column() {
		return "{$this->table_alias}.col_id_{$this->grid_field['col_id']}"; // . $this->get_field_by_key($this->child_field_name,'field_column');
	}

	function get_title_column() {
		return $this->get_value_column();
	}

	function get_join_sql() {
		// join the channel_grid_field_... table
		$joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}matrix_data {$this->table_alias} " .
		"ON {$this->table_alias}.entry_id = {$this->channel_data_alias}.entry_id ");

		return $joins;
	}
}

class Reefine_util_matrix_fields {
	private static $instance;
	/**
	 * @var Reefine
	 */
	private $reefine;

	private $grid_fields = array();

	public static function get_instance($reefine) {
		if (!isset(Reefine_util_matrix_fields::$instance))
			Reefine_util_matrix_fields::$instance = new Reefine_util_matrix_fields($reefine);
		return Reefine_util_matrix_fields::$instance;
	}

	/**
	 *
	 * @param Reefine $reefine
	 */
	private function __construct($reefine) {
		$this->reefine = $reefine;
		$rows = ee()->db->select('col_id, field_id, col_type, col_label, col_name')
		->where('site_id', $this->reefine->site)
		->get("{$this->reefine->dbprefix}matrix_cols")->result_array();
		foreach ($rows as $row) {
			$this->grid_fields[$row['field_id']][$row['col_name']] = $row;
		}
	}

	public function get_grid_field($field_id,$col_name) {
		if (isset($this->grid_fields[$field_id][$col_name]))
			return $this->grid_fields[$field_id][$col_name];
		else
			throw new Exception ("Matrix column " . $col_name . " not found.");
	}

}


class Reefine_group {
	/**
	 * Name of group as defined in parameters
	 * @var unknown
	 */
	public $group_name = '';
	/**
	 * Label to be passed to output
	 * @var unknown
	 */
	public $label = '';
	/**
	 * Type eg list, number_range 
	 * @var unknown
	 */
	public $type = '';
	/**
	 * delimiter used for multipe values
	 * @var unknown
	 */ 
	public $delimiter = '';
	/**
	 * join type
	 * @var unknown
	 */
	public $join = 'or';
	/**
	 * orering of filters
	 * @var unknown
	 */
	public $orderby = 'value';
	/**
	 * array of category group IDs
	 * @var unknown
	 */
	public $category_group = array();
	/**
	 * The database
	 * @var CI_DB_active_record
	 */
	public $db;
	/**
	 * Whether to show empty filters. 
	 * @var unknown
	 */
	public $show_empty_filters = false;
	/**
	 * database prefix
	 * @var string
	 */
	public $dbprefix = 'exp_';
	/**
	 * category group IN list used for SQL
	 * @var unknown
	 */
	public $cat_group_in_list = '';
	/**
	 * 
	 * @var Reefine_field[]
	 */
	public $fields = array();
	
	/**
	 * 
	 * @var Reefine
	 */
	protected $reefine;
	
	/**
	 * List of all possible filters
	 * @var array
	 */
	public $filters = array();
	/**
	 * Total number of filters
	 * @var int
	 */
	public $total_filters = 0;
	/** Number of currently active filters ie filters that match the filter values 
	 * @var int */
	public $active_filters = 0;
	/** Number of filters that have more than one returned results (quantity>0)
	 * @var int */
	public $matching_filters = 0;
	
	
	function __construct($reefine,$group_name) {
		$this->reefine = $reefine;
		$this->group_name = $group_name;
		$this->values = array();
		$this->label = $group_name;
		// if group doesnt have fields assign it as an empty array
		$this->fields = array();
		$this->dbprefix = $reefine->dbprefix;
		$this->db = $reefine->db;
	} 
	
	
	public function set_settings_from_parameters() {
		// get all field names in param
		$field_names = $this->reefine->get_filter_group_setting($this->group_name, 'fields', array(), 'array');
		foreach ($field_names as $field_name) {
			$this->fields[] = $this->reefine->get_field_obj($field_name);
		}
		// add rest of settings which are strings/arrays/booleans
		$this->reefine->add_filter_group_setting($this, 'label', $this->group_name);
		$this->reefine->add_filter_group_setting($this, 'delimiter', '');
		$this->reefine->add_filter_group_setting($this, 'join', 'or', 'text');
		$this->reefine->add_filter_group_setting($this, 'orderby', 'value', 'text');
		$this->reefine->add_filter_group_setting($this, 'category_group', array(), 'array');
		$this->reefine->add_filter_group_setting($this, 'show_empty_filters', false, 'bool');
			
		if (count($this->category_group)>0) {
			$this->cat_group_in_list = $this->reefine->array_to_in_list($this->category_group);
		}
	}
	
	
	/**
	 * Get the value column of a field for SQL
	 * @param Reefine_field $field
	 */
	protected function get_field_value_column($field) {
		return $field->get_value_column();
	}
	
	/**
	 * Get the title column of a field for SQL
	 * @param Reefine_field $field
	 */
	protected function get_field_title_column($field) {
		return $field->get_title_column();
	}
	
	// global where clause that effects entire search, not just the filter
	public function get_global_where_clause() {
		return '';
	}
	
	public function add_filter_values($filter_values) {
		if (isset($filter_values))
			$this->values = array_merge($this->values, $filter_values);
	}
	
	
	static public function create_group_by_type($group_type,$group_name,$reefine) {
		$class_name = 'Reefine_group_' . $group_type;
		return new $class_name($reefine,$group_name);
	}
	
	public function get_settings_from_parameters($params) {
		
	}
	
	public function do_redirect_for_text_input() {
		$value = ee()->input->get_post($this->group_name);
		
		if ($value !== false) {
			$url = $this->reefine->get_filter_url($this->group_name,$value,true);
			ee()->functions->redirect($this->reefine->create_url($url));
			return;
		}
		
	}
	
	
	public function post_contains_filter_value() {
		$value = ee()->input->get_post($this->group_name);
		return ($value!==false);
	}
	
	public function get_filter_value_from_post() {
		$value = ee()->input->get_post($this->group_name);
		if (is_array($value)) {
			// <option value="">Any</option> will post array('') so we need to ignore that
			if (count($value)>0 && $value[0]!='')
				return $value;
			else 
				return array();
		} else if ($value!==false && $value!=='') {
			return array($value);
		} else {
			return array();
		}
		
	}
	
	public function get_filter_url_tags_array($tag_name,$parts) {
		//$group_type = isset($group['type']) ? $group['type'] : 'list'; // list is default group type
		if ($this->type=='number_range') // TODO: move to number range class
			$default_or_text = '-to-';
		else if ($this->join=='or')
			$default_or_text = '-or-';
		else
			$default_or_text = '-and-';
		$any_text = isset($parts[1]) ? $parts[1] : 'any';
		$or_text = isset($parts[2]) ? $parts[2] : $default_or_text;
		$min_text = isset($parts[3]) ? $parts[3] : 'at-least-';
		$max_text = isset($parts[4]) ? $parts[4] : 'at-most-';
		// save url parameter tags for use when creating filter urls
		return array(
				'tag'=>'{'.$tag_name.'}',
				'group_name'=>$this->group_name,
				'or_text'=>$or_text,
				'any_text'=>$any_text,
				'min_text'=>$min_text,
				'max_text'=>$max_text
		);
		
	}
	
	public function get_filter_values_from_url($tag_value,$url_tag) {
		// if the value of the tag is not "any" then add the value
		if ($tag_value!=$url_tag['any_text'] && $tag_value!='') {
			if ($this->type == 'number_range') {
				$range = explode($url_tag['or_text'],$tag_value);
				if (count($range)==2)
					$filter_values = array(
							'min'=>$range[0],
							'max'=>$range[1]);
				else if (strpos($tag_value,$url_tag['min_text'])!==false)
					$filter_values = array(
							'min'=>str_replace($url_tag['min_text'],'',$tag_value));
				else if (strpos($tag_value,$url_tag['max_text'])!==false)
					$filter_values = array(
							'max'=>str_replace($url_tag['max_text'],'',$tag_value));
				else // malformed
					$filter_values = array();
			} else {
				$filter_values =  explode($url_tag['or_text'],$tag_value);
			}
			// value has been urlencoded so deencode the url
			foreach ($filter_values as &$filter_value) {
				$filter_value = $this->reefine->urldecode($filter_value);
			}
			// if a search on title is being performed then add a flag to include the
			// channel_titles table in sql queries
			// TODO: Fix this:
			//if (isset($this->filter_groups[$group_name])
			//&& isset($this->filter_groups[$group_name]['fields'])
			//&& in_array('title', $this->filter_groups[$group_name]['fields']))
			//	$this->include_channel_titles = true;

			return $filter_values;
		}


	}
	
	/**
	 * Go though each filter and add the url attribute to filter array
	 */
	public function add_filter_url_to_filters() {
		foreach ($this->filters as &$filter) {
			if ($this->type=='list') {
				$filter['url'] = $this->reefine->create_url($this->reefine->get_filter_url($this->group_name,$filter['filter_value']));
			} else { // give url that will remove filter
				$filter['url'] = $this->reefine->create_url($this->reefine->get_filter_url($this->group_name));
			}
		}
		$this->clear_url = $this->reefine->create_url($this->reefine->get_filter_url($this->group_name,null));
	}
	
	/**
	 * Get filter values to use for creating the URL for a filter. 
	 * @param unknown $filter_value
	 * @param unknown $is_for_redirection
	 */
	public function get_values_for_filter($filter_value, $is_for_redirection) {
		// abstract
	}
	
	/**
	 * Get value to put in url that replaces the tag in the tag="" parameter 
	 * @param unknown $url_tag
	 * @param unknown $values
	 * @return string|unknown eg "X-or-Y"
	 */
	public function get_group_url_tag_replacement($url_tag,$values) {
		$or_text = $url_tag['or_text'];
		$any_text = $url_tag['any_text'];
		if (count($values)>0) {
			// eg X-or-Y-or-Z
			return implode($or_text,$this->reefine->urlencode_array($values)); 
		} else {
			return $any_text;
		}		
	}
	
	/**
	 * Get array of querystrings for values
	 * @param unknown $values
	 * @return multitype:string
	 */
	public function get_filter_querystring_from_filter_values($values) {
		$qs = array();
		if (count($values)>0) {
			foreach ($values as $v) {
				$qs[] = urlencode($this->group_name) . '[]=' . urlencode($v);
			}
		} 
		return $qs;
	}
	
	
	public function get_join_sql() {
		$joins = array();
		foreach ($this->fields as $field) {
			$field_join_sql = $field->get_join_sql();
			if (is_array($field_join_sql))
				// if its an array just add the array values onto the joins
				$joins = array_merge($joins,$field_join_sql);
			else if ($field_join_sql!='')
				$joins[] = $field_join_sql;
		}
		if (count($this->category_group) > 0) {
			$cat_group_in_list = $this->cat_group_in_list;
			$joins[] = "LEFT OUTER JOIN {$this->dbprefix}category_posts catp_{$this->group_name} " .
			"ON catp_{$this->group_name}.entry_id = {$this->dbprefix}channel_data.entry_id \n" .
			"LEFT OUTER JOIN {$this->dbprefix}categories cat_{$this->group_name} " .
			"ON cat_{$this->group_name}.cat_id = catp_{$this->group_name}.cat_id AND cat_{$this->group_name}.group_id IN {$cat_group_in_list} \n" ;
		}
		return $joins;
	}
	

	/**
	 * This will run through all filters['filter_value'] fields to find a delimiter
	 * if it finds one it will split the delimiter up so "green|blue" will be split into seperate filters
	 * @param array $filters List of filters with
	 * @param string $delimiter
	 */
	protected function decompose_delimited_filters($delimiter) {
		// make an array of filters to lookup their positions in array
	
		$filter_index = $this->get_filter_indexes();
		// divide up the filter by delimiter, eg a field might have spain|france|italy which will need to be seperated into
		// individual filters.
		foreach ($this->filters as $key => &$filter) {
			// see if it has a delimiter first
			if (strpos($filter['filter_value'],$delimiter)!==false) {
				// for each delimited item (eg spain,france,italy)
				foreach (explode($delimiter,$filter['filter_value']) as $filter_value_sub) {
	
					//$move_to_filter = $this->in_subarray($filters,'filter_value',$filter_value_sub);
					// see if the item is already in the filters
					if (!isset($filter_index[$filter_value_sub])) {
						// ccreate a new filter
						$this->filters[] = array(
								'filter_value' => $filter_value_sub,
								'filter_quantity' => $filter['filter_quantity'],
								'filter_title' => $filter_value_sub
						);
						$filter_index[$filter_value_sub] = count($this->filters)-1;
					} else {
						// just add count to an existing filter
						$this->filters[$filter_index[$filter_value_sub]]['filter_quantity'] += $filter['filter_quantity'];
					}
				}
	
			}
		}
		unset($filter);
		$reorder_required = false;
		foreach ($this->filters as $key => &$filter) {
			// see if it has a delimiter first
			if (strpos($filter['filter_value'],$delimiter)!==false) {
				// remove this filter as it has a delimiter in it which we've already split out
				unset($this->filters[$key]);
				//array_splice($filters,$key,1);
				$reorder_required=true;
			}
		}
		if ($reorder_required)
			$this->filters = array_values($this->filters);
	
	
	}
	
	/**
	 * Combine any duplicate $this->filters
	 */
	protected function combine_duplicate_filters() {
		$reorder_required=false;
		$unique_filter_keys = array();
		foreach ($this->filters as $key=>&$filter) {
			$filter_value=$filter['filter_value'];
			// if is duplicate
			if (isset($unique_filter_keys[$filter_value])) {
				// add filter quantntiy of duplicate filter to the first one
				$this->filters[$unique_filter_keys[$filter_value]]['filter_quantity']+=$filter['filter_quantity'];
				unset($this->filters[$key]); // remove duplicate
				$reorder_required = true; // unset leaves a hole in the array so it needs reordering
			} else {
				$unique_filter_keys[$filter['filter_value']] = $key;
			}
		}
		if ($reorder_required)
			$this->filters = array_values($this->filters);
	}
	
	private function get_filter_indexes() {
		$filter_index = array();
		foreach ($this->filters as $index => $filter) {
			$filter_index[$filter['filter_value']] = $index;
		}
		return $filter_index;
	}
	
	

	static function compare_filter_by_value($a, $b)
	{
		if (is_numeric($a['filter_value']) && is_numeric($b['filter_value'])) {		
			return ((floatval($a['filter_value'])>floatval($b['filter_value'])) ? 1 : -1);
		} else {
			return (strcmp($a['filter_value'], $b['filter_value'])>0) ? 1 : -1;
		}

	}
	
	static function compare_filter_by_count($a, $b){
		if ($a['filter_quantity'] == $b['filter_quantity'])
			return self::compare_filter_by_value($a, $b);
		else
			return $a['filter_quantity'] < $b['filter_quantity'] ? 1 : -1;
	}
	
	static function compare_filter_by_active($a, $b){
		if ($a['filter_active'] == $b['filter_active'])
			return self::compare_filter_by_value($a, $b);
		else
			return $a['filter_active'] < $b['filter_active'] ? 1 : -1;
	}
	
	static function compare_filter_by_active_count($a, $b){
		if ($a['filter_active'] == $b['filter_active'])
			return self::compare_filter_by_count($a, $b);
		else
			return $a['filter_active'] < $b['filter_active'] ? 1 : -1;
	}
	
	/**
	 * Sort filters based on $sort
	 * @param array $filters
	 * @param string $sort value, count, active, or active_count
	 */
	public function sort_filters() {
		if ($this->orderby == 'value')
			usort($this->filters, array(get_class($this),"compare_filter_by_value"));
		else if ($this->orderby == 'quantity')
			usort($this->filters, array(get_class($this),"compare_filter_by_count"));
		else if ($this->orderby == 'active')
			usort($this->filters, array(get_class($this),"compare_filter_by_active"));
		else if ($this->orderby == 'active_quantity')
			usort($this->filters, array(get_class($this),"compare_filter_by_active_count"));
	}
	
	/**
	 * Get array of group values formatted for output with array of filters.
	 * @param string $only_show_active If true only returns active filters
	 * @return array
	 */
	function get_filters_for_output($only_show_active = false) {
		$group = array();
		
		// get attributes of group
		foreach (get_object_vars($this) as $key => $val) {
			if (is_string($val)) {
				$group[$key] = htmlspecialchars($val, ENT_QUOTES);
			}
		}
		
		// format filters for output
		$group['filters'] = array();
		$active_index = 0;
		foreach ($this->filters as $filter_key => $filter) {
			$filter_active = $filter['filter_active'];
			$filter_quantity = $filter['filter_quantity'];
			// Check that - if only show active then only show if active ALSO if hide empty filters then only show if filter is not empty or is active
			if ( (!$only_show_active || $filter_active) && 
				($this->show_empty_filters || $filter_active || $filter_quantity>0) )  {
				
				$filter_out = array();
				
				if ($filter['filter_active']) 
					$active_index += 1;
				// used for formatting 
				$filter_out['active_index'] = $active_index;
				$filter_out['filter_active_class'] = ( $filter_active ? 'active' : 'inactive' );
				$filter_out['filter_active_boolean'] = ( $filter_active ? 'true' : 'false' );
				
				// stop xss
				foreach ($filter as $key => $val) {
					$filter_out[$key] = htmlspecialchars($val, ENT_QUOTES);
				}
				
				// number range doessome stuff
				$this->format_filter_for_output($filter,$filter_out);
				
				$group['filters'][] = $filter_out;
			}
		}
		
		return $group;
	}
		
	function format_filter_for_output($filter_in,&$filter_out) {
		// abstract	
	}
	
	public function get_where_clause() {
		//abstract
	}
	
	public function set_filters() {
		// abstract
	}
	protected function set_filter_totals() {
		// set totals for use in templates
		$this->total_filters = count($this->filters);
		$this->active_filters = 0;
		$this->matching_filters = 0;
		
		// set filter_active value if the filter is selected
		foreach ($this->filters as &$filter) {
			// make group name available in {filter} tag
			$filter['group_name'] = $this->group_name;
			if (in_array($filter['filter_value'],$this->values)) {
				$filter['filter_active']=true;
				$this->active_filters += 1;
			} else {
				$filter['filter_active']=false;
			}
			if ($filter['filter_quantity']>0)
				$this->matching_filters += 1;
		}
	}
	
}

class Reefine_group_list extends Reefine_group {
	public $type = 'list';
	public function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
	}
	
	function set_filters() {
		$this->filters = array();
		// for each field in the filter group
		foreach ($this->fields as &$field) {
			// get list of possible values
			$results = $this->get_filter_groups_for_list($this->get_field_value_column($field),$this->get_field_title_column($field));
			$this->filters = array_merge($this->filters,$results);
			
		}
		if (count($this->category_group)>0) {
			$results = $this->get_filter_groups_for_list("cat_{$this->group_name}.cat_url_title","cat_{$this->group_name}.cat_name",
			"cat_{$this->group_name}.group_id IN {$this->cat_group_in_list}");
			$this->filters = array_merge($this->filters,$results);
		}
		// remove duplicates http://stackoverflow.com/a/946300/1102000
		//$this->filters = array_map("unserialize", array_unique(array_map("serialize", $this->filters)));
		$this->combine_duplicate_filters();
		// if group has delimiter
		$delimiter = isset($this->delimiter) ? $this->delimiter : '';
		if ($delimiter!='') {
			$this->decompose_delimited_filters($delimiter);
		}
		
		// set totals for use in templates
		
		$this->set_filter_totals();
		// sort filters on orderby
		$this->sort_filters();
		
		
	}
	
	protected function get_filter_count_statement() {
		// if group is multi select then ignore the current filter group in creating the where clause
		if ($this->join=='or' || $this->join=='none')
			$count_where = $this->reefine->get_filter_fields_where_clause($this->group_name);
		else // join is 'and' so use current filter
			$count_where = $this->reefine->filter_where_clause;
		
		if ($count_where == '') {
			return "{$this->dbprefix}channel_data.entry_id"; 
		} else {
			return "CASE WHEN {$count_where} THEN {$this->dbprefix}channel_data.entry_id ELSE NULL END as entry_id";
		}
		
	}
	private function get_filter_groups_for_list($column_name,$title_column_name,$extra_clause = '') {
		// have to give up on active record select coz of this bug: http://stackoverflow.com/questions/7927458/codeigniter-db-select-strange-behavior
		
		$sql = "SELECT {$column_name} as filter_value, " .
		"{$title_column_name} as filter_title, {$this->get_filter_count_statement()} " .
		"FROM {$this->dbprefix}channel_data ";
			
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id = {$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->reefine->get_query_join_sql($this->group_name);
		$sql .= "WHERE {$column_name} <> '' ";
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
		}
		if ($extra_clause!='')
			$sql .= " AND ({$extra_clause}) ";
		// Wrap sql statement in select statement so we can get total of each distinct entry
		$sql = "SELECT filter_value, filter_title, count(distinct(entry_id)) as filter_quantity ".
		" FROM ({$sql}) t1 GROUP BY filter_value, filter_title";
			
		$results = $this->reefine->db->query($sql)->result_array();
		return $results;
	}
	
	/**
	 * If filter is active then filter will be deactivated when clicked so 
	 * @see Reefine_group::get_values_for_filter()
	 */
	public function get_values_for_filter($filter_value, $is_for_redirection) {
		$filter_value_index = array_search($filter_value,$this->values);
		$values = $this->values;
		if ($this->join=='none') {
			// joining not allowed so only allow one filter value for this filter group
			if ($filter_value_index === false)
				$values = array($filter_value);
			else // user clicked on an active filter value so clear it
				$values = array();
		} else { // joining is allowed (join=or,join=and) so add the new filter value
			if ($filter_value_index === false)
				$values[] = $filter_value;
			else // user clicked an avtive filter value so remove that value only
				array_splice($values,$filter_value_index,1);
		}
		return $values;
	}
	
	// construct the where clause for a group of type "list"
	public function get_where_clause() {
		$clauses = array();
		if (!isset($this->category_group))
			$this->category_group=array();
		
	
		// channel fields
		$field_list = array();
		// a filter group can have many fields so go through each
		$in_list = array();
		// make a list of possible values for the field
		foreach ($this->values as $value) {
			$in_list[] = $this->db->escape($value);
		}
		// example: if field_id_2 is colour and user selects all green or red items:
		//  field_id_2 IN ('green','red')
		if (isset($this->delimiter) && $this->delimiter!='') {
			// delimiter seperate values
	
			$delimiter = $this->db->escape($this->delimiter);
			if ($this->join=='or' || $this->join=='none') {
				// at least one value must be in the listed fields or category groups and search within delimiters
				$field_list = array();
				foreach ($this->fields as $field) {
					foreach ($in_list as $value) {
						$field_list[] = " instr(concat({$delimiter},{$this->get_field_value_column($field)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
					}
				}
				if (count($this->category_group)>0)
					$field_list[] = " ( cat_{$this->group_name}.cat_url_title IN (" . implode(',',$in_list) . ") AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list})";
	
				$clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
			} else {
				$field_list = array();
				foreach ($in_list as $value) {
					$value_list = array();
					foreach ($this->fields as $field) {
						$value_list[] = " instr(concat({$delimiter},{$this->get_field_value_column($field)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
					}
	
					if (count($this->category_group)>0)
						$value_list[] = "{$this->dbprefix}channel_data.entry_id IN (SELECT exp_category_posts.entry_id " .
						"FROM exp_category_posts " .
						"JOIN exp_categories USING (cat_id) " .
						"WHERE cat_url_title  = {$value} AND group_id IN {$this->cat_group_in_list} )";
	
					$field_list[] = "\n(" . implode("\n OR ",$value_list) . ")";
				}
	
				$clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
			}
	
		} else {
			if ($this->join=='or' || $this->join=='none') {
				// group is multi select so the row must contain at least one value in any fields
				// eg..
				// ( `field_id_15` IN ('Bosch','Green')
				// OR  `field_id_12` IN ('Bosch','Green'))
				$field_list = array();
				foreach ($this->fields as $field) {
					$field_list[] = " {$this->get_field_value_column($field)} IN (" . implode(',',$in_list) . ")";
				}
				if (count($this->category_group)>0)
					$field_list[] = " ( cat_{$this->group_name}.cat_url_title IN (" . implode(',',$in_list) . ") AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list})";
	
				$clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
			} else {
				// field is not multi select. create an sql query with this format:
				// ( ( field1 = value1 OR field2 = value1 ) AND (field1 = value2 OR field2 = value2) )
				//  eg...
				// (( `field_id_15` = 'Bosch' OR  `field_id_12` = 'Bosch')
				// AND ( `field_id_15` = 'Green' OR  `field_id_12` = 'Green'))
				// this means the row must contain all the active filters in any of the filter group's fields
				$field_list = array();
				foreach ($in_list as $value) {
					$value_list = array();
					foreach ($this->fields as $field) {
						$value_list[] = " {$field->column_name} = {$value}";
					}
					if (count($this->category_group)>0)
						$value_list[] = "{$this->dbprefix}channel_data.entry_id IN (SELECT exp_category_posts.entry_id " .
						"FROM exp_category_posts " .
						"JOIN exp_categories USING (cat_id) " .
						"WHERE cat_url_title  = {$value} AND group_id IN {$this->cat_group_in_list} )";
	
					$field_list[] = "(" . implode(" OR ",$value_list) . ")";
				}
				$clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
			}
		}
	
		return $clauses;
	}
	
}

class Reefine_group_number_range extends Reefine_group {
	public $type = 'number_range';
	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
		$this->show_empty_filters=true;
	}
	
	public function post_contains_filter_value() {
		$value_min = ee()->input->get_post($this->group_name.'_min');
		$value_max = ee()->input->get_post($this->group_name.'_max');
		return ($value_min!==false || $value_max!==false);
	}
	
	public function get_filter_value_from_post() {
		$value_min = ee()->input->get_post($this->group_name.'_min');
		$value_max = ee()->input->get_post($this->group_name.'_max');
		$values = array();
		if ($value_min!==false && $value_min!=='')
			$values['min'] = $value_min;
		if ($value_max!==false && $value_max!=='')
			$values['max'] = $value_max;
		return $values;
	}
	
	public function do_redirect_for_text_input() {
		$values = $this->get_filter_value_from_post();
		
		if (count($values)>0) {
			$url = $this->reefine->get_filter_url($this->group_name,$values,true);
			ee()->functions->redirect($this->reefine->create_url($url));
			return;
		}
	}
	
	
	function set_filters() {
		$filters= array();
		
		// get min/max ranges for number
		// for each field in the filter group
		$filter_min_fields = array();
		$filter_max_fields = array();
		
		foreach ($this->fields as $field) {
			$filter_min_fields[] = "min(IF({$this->get_field_value_column($field)}='',999999999999,CAST({$this->get_field_value_column($field)} AS DECIMAL(25,4))))";
			$filter_max_fields[] = "max(IF({$this->get_field_value_column($field)}='',-999999999999,CAST({$this->get_field_value_column($field)} AS DECIMAL(25,4))))";
		}
		// if theres just one field		
		if (count($this->fields)==1) {
			// get the min/max of that field
			$filter_min_sql = $filter_min_fields[0];
			$filter_max_sql = $filter_max_fields[0];
		} else {
			// otherwise construct a least/great statement for all fields
			$filter_min_sql = "LEAST(" . implode(',',$filter_min_fields) . ")";
			$filter_max_sql = "GREATEST(" . implode(',',$filter_max_fields) . ")";
		}
		
		$sql = "SELECT count(distinct({$this->dbprefix}channel_data.entry_id)) as filter_quantity, " .
		"{$filter_min_sql} as filter_min, " .
		"{$filter_max_sql} as filter_max " .
		"FROM {$this->dbprefix}channel_data ";
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id={$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->reefine->get_query_join_sql($this->group_name);
		$sql .= "WHERE 1=1 ";
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
		}
		// ignore the current filter group in creating the where clause
		$where_clause_excluding_group = $this->reefine->get_filter_fields_where_clause($this->group_name);
		if ($where_clause_excluding_group !='')
			$sql .= "AND " . $where_clause_excluding_group;
		$results = $this->db->query($sql)->result_array();
		if (count($results)==0)
			$filters[] = array(
					'filter_value'=>'',
					'filter_title'=>'',
					'filter_min'=>'',
					'filter_max'=>'',
					'filter_quantity'=>0,
					'group_name'=>$this->group_name);
		else {
			if (isset($this->values['min']) && isset($this->values['max']))
				$results[0]['filter_value']=$this->values['min'] . ' - ' . $this->values['max'];
			else if (isset($this->values['min']))
				$results[0]['filter_value']='> ' . $this->values['min'];
			else if (isset($this->values['max']))
				$results[0]['filter_value']='< ' . $this->values['max'];
			else
				$results[0]['filter_value']='';
			$results[0]['filter_title']=$results[0]['filter_value'];
			//$results[0]['filter_value']=$results[0]['filter_min'].'-'.$results[0]['filter_max'];
	
			$results[0]['group_name']=$this->group_name;
			// remove traling zeros on decimals
			$results[0]['filter_min'] += 0;
			$results[0]['filter_max'] += 0;
			$filters[] = $results[0];
		}
	
	
		// set totals for use in templates
		$this->filters = $filters;
		$this->total_filters = count($this->filters);
		$this->active_filters = 0;
		$this->matching_filters = 0;
	
		// set filter_active value if the filter is selected
		foreach ($this->filters as &$filter) {
			// make group name available in {filter} tag
			$filter['group_name'] = $this->group_name;
			if (isset($this->values) && count($this->values)>0) {
				$filter['filter_active'] = true;
				$this->active_filters += 1;
			} else {
				$filter['filter_active'] = false;
			}
			if ($filter['filter_quantity']>0)
				$this->matching_filters += 1;
		}
		
		
	}
	
	
	/**
	 * for number_range set the filter_min_value and filter_max_value
	 * @param unknown $filter_in
	 * @param unknown $filter_out
	 */
	function format_filter_for_output($filter_in,&$filter_out) {
		$filter_out['filter_min_value'] = isset($this->values['min']) ? $this->values['min'] : '';
		$filter_out['filter_max_value'] = isset($this->values['max']) ? $this->values['max'] : '';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::get_values_for_filter()
	 */
	public function get_values_for_filter($filter_value, $is_for_redirection) {
		$values = array(); // clear out existing values
		if ($is_for_redirection) {
			if (isset($filter_value['min'])) $values['min']=$filter_value['min'];
			if (isset($filter_value['max'])) $values['max']=$filter_value['max'];
		}
		return $values;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::get_group_url_tag_replacement()
	 */
	public function get_group_url_tag_replacement($url_tag,$values) {
	
		$or_text = $url_tag['or_text'];
		$any_text = $url_tag['any_text'];
		$max_text = $url_tag['max_text'];
		$min_text = $url_tag['min_text'];
		
		if (count($values)>0) {
			if (isset($values['min']) && !isset($values['max']))
				$url_value = $min_text.$values['min']; // at least x
			else if (!isset($values['min']) && isset($values['max']))
				$url_value = $max_text.$values['max']; // at most y
			else if (isset($values['min']) && isset($values['max']))
				$url_value = $values['min'].$or_text.$values['max']; // x to y
			return $url_value;
		} else {
			return $any_text;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::get_filter_querystring_from_filter_values()
	 */
	public function get_filter_querystring_from_filter_values($values) {
		$qs = array();
		if (count($values)>0) {
			if (isset($values['min']))
				$qs[] = urlencode($this->group_name) . '_min=' . urlencode($values['min']); // at least x
			if (isset($values['max']))
				$qs[] = urlencode($this->group_name) . '_max=' . urlencode($values['max']); // at least x
		}
		return $qs;
	}
	
	
	public function get_where_clause() {
		$min_clauses = array();
		$max_clauses = array();
		$clauses = array();
		if (isset($this->fields) && count($this->values)>0) {
	
			foreach ($this->fields as $field) {
	
				if (isset($this->values['min']) && is_numeric($this->values['min'])) {
					$value = $this->db->escape_str($this->values['min']);
					$min_clauses[] = "({$this->get_field_value_column($field)}<>'' AND CAST({$this->get_field_value_column($field)} AS DECIMAL(25,4)) >= {$value})";
				}
				if (isset($this->values['max']) && is_numeric($this->values['max'])) {
					$value = $this->db->escape_str($this->values['max']);
					$max_clauses[] = "({$this->get_field_value_column($field)}<>'' AND CAST({$this->get_field_value_column($field)} AS DECIMAL(25,4)) <= {$value})";
				}
			}
			
		}
		if (count($min_clauses)>0)
			$clauses[] = '(' . implode(' OR ',$min_clauses) . ')';
		if (count($max_clauses)>0)
			$clauses[] = '(' . implode(' OR ',$max_clauses) . ')';
		
		return $clauses;
		
	}
	
	
}

class Reefine_group_search extends Reefine_group {
	public $type = 'search';
	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
		$this->show_empty_filters=true;
	}
	
	
	/**
	 * Set the filters array. We dont need to get anything form the db for this unlike list groups
	 */
	function set_filters() {
		// search has just the one filter
		if (isset($this->values) && count($this->values)>0) {
			$this->matching_filters = 1;
			$this->active_filters = 1;
			$this->filters = array(array(
					'filter_value'=> $this->values[0],
					'filter_title'=> $this->values[0],
					'group_name' => $this->group_name,
					'filter_quantity'=>1,
					'filter_active'=>true));
			
		} else {
			$this->matching_filters = 0;
			$this->active_filters = 0;
			$this->filters = array(array(
					'filter_value'=> '',
					'filter_title' => '',
					'group_name' => $this->group_name,
					'filter_quantity'=>0,
					'filter_active'=>false));
		}
		
		// set totals for use in templates
		$this->total_filters = 1;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::get_values_for_filter()
	 */
	public function get_values_for_filter($filter_value, $is_for_redirection) {

		
		if ($is_for_redirection) {
			return array($filter_value);
		} else {
			// there can only be one search value no need to provide it in the url form post
			return array();
		}
	}
	
	//
	public function get_where_clause() {
		$clauses = array();
		if (isset($this->fields) && count($this->values)>0) {
			$search_terms = array();
			foreach (explode(' ',$this->values[0]) as $value) {
				$words = array();
				$value = $this->db->escape_like_str($value);
				foreach ($this->fields as $field) {
					$words[] = " {$this->get_field_title_column($field)} LIKE '%{$value}%'";
				}
				foreach ($this->category_group as $cat_group) {
					$cat_group = $this->db->escape_str($cat_group);
					$words[] = " ( cat_{$this->group_name}.cat_name LIKE '%{$value}%' AND cat_{$this->group_name}.group_id={$cat_group} )";
				}
	
				$search_terms[] = '(' . implode(' OR ',$words) . ')';
			}
			$clauses[] = "\n(" . implode("\n AND ",$search_terms) . ")";
		}
	
		return $clauses;
	}
		
}



class Reefine_group_month_list extends Reefine_group_list {
	public $type = 'list';
	public $where_after;
	public $where_before;
	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
	}

	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::set_settings_from_parameters()
	 */
	public function set_settings_from_parameters() {
		parent::set_settings_from_parameters();
		// add rest of settings which are strings/arrays/booleans
		$this->reefine->add_filter_group_setting($this, 'where_after', '');
		$this->reefine->add_filter_group_setting($this, 'where_before', '');
	}
	
	public function get_global_where_clause() {
		/** @var $min_field Reefine_field */
		$min_column = "cast({$this->fields[0]->get_value_column()} as UNSIGNED)";
		// if two fields are specified then use a min-max range otherwise min-max are the same.
		if (count($this->fields)>1)
			$max_column = "cast({$this->fields[1]->get_value_column()} as UNSIGNED)";
		else
			$max_column = $min_column;
		$sql = array();
		if (!empty($this->where_before) && is_numeric($this->where_before))
			$sql[] = "{$min_column} < {$this->db->escape($this->where_before)}";
		if (!empty($this->where_after) && is_numeric($this->where_after))
			$sql[] = "greatest({$min_column},{$max_column}) > cast({$this->db->escape($this->where_after)} as UNSIGNED)";
		
		if (count($sql)>0)
			return implode(' AND ',$sql);
		else 
			return '';
		
	}
	

	function set_filters() {
		
		if ($this->where_before!='') {
			$before_date = new DateTime("@" . $this->where_before);
			$before_date->modify('+1 month'); 
		}
		
		if ($this->where_after!='') {
			$after_date = new DateTime("@" . $this->where_after);
			$after_date->modify('-1 month');
		}
		
		
		$filters= array();
		$min_field = $this->fields[0];
		// if two fields are specified then use a min-max range otherwise min-max are the same.
		if (count($this->fields)>1)
			$max_field=$this->fields[1];
		else 
			$max_field = $this->fields[0];
		// get min/max ranges for number
		// for each field in the filter group
		
		// http://stackoverflow.com/a/11808253/1102000
		// get list of date ranges of the first of the month with how mant matches there are
		
		$sql = "SELECT {$this->get_filter_count_statement()}, " .
		"{$this->get_field_value_column($min_field)} as filter_min, " .
		"{$this->get_field_value_column($max_field)} as filter_max " .
		"FROM {$this->dbprefix}channel_data ";
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id={$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->reefine->get_query_join_sql($this->group_name);
		$sql .= "WHERE 1=1 ";
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
		}
		// ignore the current filter group in creating the where clause
		$where_clause_excluding_group = $this->reefine->get_filter_fields_where_clause($this->group_name);
		if ($where_clause_excluding_group !='')
			$sql .= "AND " . $where_clause_excluding_group;
		// Wrap sql statement in select statement so we can get total of each distinct entry
		$sql = "SELECT filter_min, filter_max, count(distinct(entry_id)) as filter_quantity ".
				" FROM ({$sql}) t1 GROUP BY filter_min, filter_max";
		
		$results = $this->db->query($sql)->result_array();
		$this->filters=array();
		foreach ($results  as $row) {
			// ignore filters with no min value
			if ($row['filter_min'] != '1970-01-01') {
				$filter_min_date = new DateTime($row['filter_min']);
				if ($row['filter_max'] == '1970-01-01') {
					$filter_max_date = $filter_min_date;
				} else {
					$filter_max_date = new DateTime($row['filter_max']);
				}

				
				// for every month from min date to max date
				for ($current = clone $filter_min_date; $filter_max_date>=$current; $current->modify('+1 month')) {
					// if the current month is within the date range given in parameters
					if ((!isset($after_date) || $current>$after_date) && (!isset($before_date) || $current<$before_date)) {
						// add in a filter for the month and/or increase the filter_quantity by the number from database
						if (!isset($this->filters[$current->format('Y-m-d')])) {
							$this->filters[$current->format('Y-m-d')] = array(
								'filter_value' => $current->format('Y-m-d'),
								'filter_title' => $current->format('F Y'),
								'filter_quantity' => $row['filter_quantity']
							);
						} else {
							$this->filters[$current->format('Y-m-d')]['filter_quantity'] += $row['filter_quantity'];
						}
					}
				}
			}
		}
		
		//die();
		
		
		$this->set_filter_totals();
		// sort filters on orderby
		$this->sort_filters();
		
		
	}
	
	// construct the where clause for a group of type "list"
	public function get_where_clause() {
		$clauses = array();

		// a filter group can have many fields so go through each
		$in_list = array();
		// make a list of possible values for the field
		foreach ($this->values as $value) {
			$in_list[] = $this->db->escape($value);
		}

		$field_list = array();
		foreach ($in_list as $value) {
				
			$month_value = "DATE_ADD(LAST_DAY(DATE_SUB(DATE({$value}), interval 30 day)), interval 1 day)";
			$min_column = $this->get_field_value_column($this->fields[0]);
			$statement = "{$min_column} = {$month_value}";
				
			if (count($this->fields)>1) {
				$max_column = $this->get_field_value_column($this->fields[1]);
				$statement = "( {$statement} OR ({$this->fields[1]->get_value_column()}<>'' " .
				" AND {$month_value} between {$min_column} AND {$max_column} ) )";
			}

			$field_list[] = $statement;
		}
		if ($this->join=='or' || $this->join=='none') {
			$clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
		} else {
			$clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
		}
		
		return $clauses;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::get_field_value_column()
	 */
	protected function get_field_value_column($field) {
		return "DATE_ADD(LAST_DAY(DATE_SUB(from_unixtime({$field->get_value_column()}), interval 30 day)), interval 1 day)";
	}

}