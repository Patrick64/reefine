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

	);

	function __construct()
	{

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->library('logger');
		if(!isset($_SESSION))
		{
			session_start();
		}
		$this->timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

		//if (! isset($this->EE->session->cache[$this->class_name]))
		//$this->EE->session->cache[$this->class_name] = array();

		//$this->cache =& $this->EE->session->cache[$this->class_name];

		if (! isset($_SESSION['reefine']))
			$_SESSION['reefine'] = array();
		//$this->cache =& $_SESSION['reefine'];
		$this->cache = array();
		//$this->EE->session->cache[$this->class_name] = array();

		//$this->cache =& $this->EE->session->cache[$this->class_name];

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
				$this->filter_values = $this->parse_search_url($this->url_tag,$this->url);

			}



			//
			// get current search details
			$this->add_filter_values($this->filter_values);
			//get a unique id of this particular search.
			$this->filter_id = md5(serialize($this->filter_groups));

			if ($this->method=='url') {
				// check for any post/get values that may be submitted by form and redirect
				// so that the url is correct.
				foreach ($this->filter_groups as $group_name => &$group) {
					$value = $this->EE->input->get_post($group_name);

					if ($value !== false) {
						$url = $this->get_filter_url($group_name,$value);
						$this->EE->functions->redirect($url);
						return;
					}
					if ($group['type']=='number_range') {
						$value_min = $this->EE->input->get_post($group_name.'_min');
						$value_max = $this->EE->input->get_post($group_name.'_max');

						if (($value_min !== false) || ($value_max !== false)) {
							$value_range = array();
							if ($value_min !== false && $value_min != '')
								$value_range['min']=$value_min;
							if ($value_max !== false && $value_max != '')
								$value_range['max']=$value_max;
							$url = $this->get_filter_url($group_name,$value_range);
							//$url = $this->EE->functions->create_url($url);
							$this->EE->functions->redirect($url);
							return;
						}
					}
				}
			}

			// change expressione ngin uri so paging works
			if ($this->method=='url') {
				$this->change_uri_for_paging();
			}




			// http://expressionengine.com/user_guide/development/usage/session.html
			// is this search already in cache?
			if (isset($this->cache[$this->filter_id]) && isset($this->cache[$this->filter_id]['return_data'])) {
				//die('yay cache');
				$cached = $this->cache[$this->filter_id];
				//$this->filter_groups = 	$cached['filter_groups'];
				//$this->entry_id_list = 	$cached['entry_id_list'];
				$this->return_data = $cached['return_data'];
			} else {
					
				// get the list of possible values to be used
				$this->set_filter_groups();

				// get all possible urls for each filters and put in $this->filter_groups[]['filters']['url']
				if ($this->method=='url') {
					$this->add_filter_url_to_filters();
				}
				// get all entry ids for this search.
				$this->entry_id_list = $this->get_entry_ids_from_database();
				$this->theme->before_parse_tag_data();
				$this->return_data = $this->get_tag_data_result($this->entry_id_list );
				// store in cache
				$cached = array(
						//'filter_groups' => $this->filter_groups,
						//'entry_id_list' => $this->entry_id_list,
						'return_data' => $this->return_data
				);
				//$this->EE->session->set_cache('reefine', $this->filter_id, $cached);
				$this->cache[$this->filter_id] = $cached;


			}


		} catch (Exception $e) {
			$a=3;
			// Log a test string
			$this->EE->logger->log_action($this->class_name . ' error at ' . $_SERVER['REQUEST_URI'] . ': ' . $e->getMessage());
			$this->return_data = '';
		}

		$a=1;
		// set db prefix back
		$this->EE->db->dbprefix = $this->dbprefix;
	}

	function hi() {
		return 'hmm';
	}

	private function get_theme($theme_name) {
		if ($theme_name!='') {
			$filepath = PATH_THIRD_THEMES.'reefine/'. $theme_name . '/theme.php';

			if (file_exists($filepath))
			{
				include_once $filepath;
				$theme_class = 'Reefine_theme_' . $theme_name;
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
		$unfiltered_url =  $this->get_filter_url();
		if (count($this->filter_values)==0 && strpos($this->url,$unfiltered_url)===false)
			$url = $this->get_filter_url();
		else
			$url = $this->url;

		$this->EE->router->uri->page_query_string = $url;
		$this->EE->router->uri->uri_string = $url;
		$this->EE->uri->page_query_string = $url;
		$this->EE->uri->uri_string = $url;
	}

	private function html_encode_filters() {
		foreach ($this->filter_groups as &$group) {
			$group['group_name'] =  htmlspecialchars($group['group_name'], ENT_QUOTES);
			$group['label'] =  htmlspecialchars($group['label'], ENT_QUOTES);
			//$group['clear_url'] =  htmlspecialchars($group['clear_url'], ENT_QUOTES);
			foreach ($group['filters'] as &$filter) {
				$filter['filter_value'] =  htmlspecialchars($filter['filter_value'], ENT_QUOTES);
				$filter['group_name'] =  htmlspecialchars($filter['group_name'], ENT_QUOTES);
				//$filter['url'] =  htmlspecialchars($filter['url'], ENT_QUOTES);
			}
		}
	}

	/**
	 * Fetch all parameters for tag
	 */
	function read_tag_parameters() {

		// get channel filter
		$filter_channel = $this->EE->TMPL->fetch_param('channel', '');
		$this->site = $this->EE->TMPL->fetch_param('site', $this->EE->config->item('site_id'));
		$this->status = $this->EE->TMPL->fetch_param('status', $this->EE->config->item('open'));
		// methods: url,post,javascript,ajax,
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
				if (!isset($this->filter_groups[$group_name]))
					$this->filter_groups[$group_name] = array('group_name'=>$group_name);
			}
		}

		// go through all filter groups to check for settings int tag parameters
		$this->add_all_filter_group_settings();
		foreach ($this->filter_groups as $group_name => &$group) {
			if (isset($group['fields'])) {
				foreach ($group['fields'] as $field) {
					if (!isset($this->_custom_fields[$this->site][$field]))
						throw new Exception ('Custom field ' . $field . ' not found.');
				}
			}
		}
		// read search:xyz="" tag and create an sql where clause from it.
		if (count($this->EE->TMPL->search_fields)>0) {
			$this->search_field_where_clause = $this->get_search_field_where_clause($this->EE->TMPL->search_fields);
		}	
		
		// category_url parameter limits results to just the the category_url
		if (!empty($this->EE->TMPL->tagparams['category_url'])) {
			
			// include categories in select
			$this->include_categories=true;
			$this->search_field_where_clause .= $this->search_field_where_clause=='' ? '' : ' AND ';
			$this->search_field_where_clause .= sprintf("{$this->dbprefix}categories.cat_url_title=%s",
			$this->db->escape($this->EE->TMPL->tagparams['category_url']));
		}

	}

	private function add_all_filter_group_settings() {
		foreach ($this->filter_groups as $group_name => &$group) {
			// get settings from tag parameters
			$this->add_filter_group_setting($group_name, 'fields', array(), 'array');
			$this->add_filter_group_setting($group_name, 'label', $group_name);
			$this->add_filter_group_setting($group_name, 'type', 'list');
			$this->add_filter_group_setting($group_name, 'delimiter', '');
			$this->add_filter_group_setting($group_name, 'join', 'or', 'text');
			$this->add_filter_group_setting($group_name, 'orderby', 'value', 'text');
			$this->add_filter_group_setting($group_name, 'category_group', array(), 'array');
			if (count($group['category_group'])>0) {
				$group['cat_group_in_list'] = $this->array_to_in_list($group['category_group']);
				$this->include_categories = true;
			}


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
	private function add_filter_group_setting($group_name, $key, $default, $type = 'text' ) {
		$tag = 'filter:' . $group_name . ':' . $key;
		if (!isset($this->EE->TMPL->tagparams[$tag])) {
			if (isset($this->filter_groups[$group_name][$key]))
				$result = $this->filter_groups[$group_name][$key];
			else
				$result = $default;
		} else {
			if ($type=='array') {
				$result = explode('|',$this->EE->TMPL->fetch_param($tag));
			} else if ($type=='bool') {
				$result = $this->EE->TMPL->fetch_param($tag);
				$result = ($result == 'yes' ? true : false);
			} else {
				$result = $this->EE->TMPL->fetch_param($tag);
			}
		}
		$this->filter_groups[$group_name][$key] = $result;

	}


	private function get_field_filters_from_parameters() {
		// get field filters from filter:fields tag

		$fields_tag = $this->EE->TMPL->fetch_param('filter:fields','');
		if (!empty($fields_tag)) {
			foreach (explode('|',$fields_tag) as $field)
			{

				if (isset($this->_custom_fields[$this->site][$field])) {
					$label = $this->_custom_fields[$this->site][$field]['field_label'];
					$ee_type = $this->_custom_fields[$this->site][$field]['field_type'];
					if ($field == 'title')
						$default = $this->default_group_by_type['title'];
					else if (isset($this->default_group_by_type[$ee_type]))
						$default = $this->default_group_by_type[$ee_type];
					else
						$default = $this->default_group_by_type['text'];
					$this->filter_groups[$field] = array(
							'group_name' => $field,
							'fields' => array($field),
							'label' => isset($default['label']) ? $default['label'] : $label,
							'type'=>$default['type'],
							'join'=>$default['join'],
							'delimiter'=>$default['delimiter']
					);
				}

			}
		}
	}

	// create $this->filter_groups[..]['filters'] array
	// will contain a list of possible values for each filter group
	private function set_filter_groups() {
		$this->filter_where_clause = $this->get_filter_fields_where_clause();
		foreach ($this->filter_groups as $group_key => &$group) {
			$filters= array();
			if ($group['type']=='list') {
				// for each field in the filter group
				foreach ($group['fields'] as &$field) {
					// get list of possible values
					$field_column = $this->_custom_fields[$this->site][$field]['field_column'];
					$results = $this->get_filter_groups_for_list($group_key,$group,$field_column);

					$filters = array_merge($filters,$results);
				}
				if (count($group['category_group'])>0) {
					$results = $this->get_filter_groups_for_list($group_key,$group,"cat_{$group_key}.cat_name",
							"cat_{$group_key}.group_id IN {$group['cat_group_in_list']}");
					$filters = array_merge($filters,$results);
				}
			} else if ($group['type']=='search') {
				if (isset($group['values']) && count($group['values'])>0)
					$filters[] = array(
							'filter_value'=> $group['values'][0],
							'filter_quantity'=>1);
				else
					$filters[] = array(
							'filter_value'=> '',
							'filter_quantity'=>0);
			} else if ($group['type']=='number_range') {
				// get min/max ranges for number
				// for each field in the filter group
				foreach ($group['fields'] as &$field) {

					$field_column = $this->_custom_fields[$this->site][$field]['field_column'];

					// ignore the current filter group in creating the where clause
					$where_clause_excluding_group = $this->get_filter_fields_where_clause($group_key);

					$sql = "SELECT count(distinct({$this->dbprefix}channel_data.entry_id)) as filter_quantity, " .
							"min(CAST({$field_column} AS DECIMAL(5,2))) as filter_min, " .
							"max(CAST({$field_column} AS DECIMAL(5,2))) as filter_max " .
							"FROM {$this->dbprefix}channel_data ";
					//if ($this->include_channel_titles)
					$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id={$this->dbprefix}channel_data.entry_id ";
					if ($this->include_categories)
						$sql .= $this->get_category_join_sql();
					$sql .= "WHERE {$field_column} <> '' ";
					if (isset($this->channel_ids)) {
						$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->channel_ids) . ")";
					}
					if ($where_clause_excluding_group !='')
						$sql .= "AND " . $this->get_filter_fields_where_clause($group_key);
					$results = $this->db->query($sql)->result_array();
					if (count($results)==0)
						$filters[] = array(
								'filter_value'=>'',
								'filter_min'=>'',
								'filter_max'=>'',
								'filter_quantity'=>0,
								'group_name'=>$group_key);
					else {
						if (isset($group['values']['min']) && isset($group['values']['max']))
							$results[0]['filter_value']=$group['values']['min'] . ' - ' . $group['values']['max'];
						else if (isset($group['values']['min']))
							$results[0]['filter_value']='> ' . $group['values']['min'];
						else if (isset($group['values']['max']))
							$results[0]['filter_value']='< ' . $group['values']['max'];
						else
							$results[0]['filter_value']='';
							
						//$results[0]['filter_value']=$results[0]['filter_min'].'-'.$results[0]['filter_max'];

						$results[0]['group_name']=$group_key;
						$filters[] = $results[0];
					}
				}
			}
			// remove duplicates http://stackoverflow.com/a/946300/1102000
			$filters = array_map("unserialize", array_unique(array_map("serialize", $filters)));
			// if group has delimiter
			$delimiter = isset($group['delimiter']) ? $group['delimiter'] : '';
			if ($delimiter!='') {
				$this->decompose_delimited_filters($filters,$delimiter);
			}



			// set totals for use in templates
			$group['filters'] = $filters;
			$group['total_filters'] = count($filters);
			$group['active_filters'] = 0;
			$group['matching_filters'] = 0;

			// set filter_active value if the filter is selected
			foreach ($group['filters'] as &$filter) {
				// make group name available in {filter} tag
				$filter['group_name'] = $group['group_name'];
				if ($group['type'] == 'number_range') {
					if (isset($group['values']) && count($group['values'])>0) {
						$filter['filter_active'] = true;
						$group['active_filters'] += 1;
					} else {
						$filter['filter_active'] = false;
					}
				} else if (isset($group['values']) && in_array($filter['filter_value'],$group['values'])) {
					$filter['filter_active']=true;
					$group['active_filters'] += 1;
				} else {
					$filter['filter_active']=false;
				}
				if ($filter['filter_quantity']>0)
					$group['matching_filters'] += 1;
			}

			// sort filters on orderby
			$this->sort_filters($group['filters'],$group['orderby']);

		}

	}

	static function compare_filter_by_value($a, $b)
	{
		return (strcmp($a['filter_value'], $b['filter_value'])>0) ? 1 : -1;
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
	private function sort_filters(&$filters,$sort) {
		if ($sort == 'value')
			usort($filters, array($this->class_name,"compare_filter_by_value"));
		else if ($sort == 'quantity')
			usort($filters, array($this->class_name,"compare_filter_by_count"));
		else if ($sort == 'active')
			usort($filters, array($this->class_name,"compare_filter_by_active"));
		else if ($sort == 'active_quantity')
			usort($filters, array($this->class_name,"compare_filter_by_active_count"));
	}

	/**
	 * This will run through all filters['filter_value'] fields to find a delimiter
	 * if it finds one it will split the delimiter up so "green|blue" will be split into seperate filters
	 * @param array $filters List of filters with
	 * @param string $delimiter
	 */
	private function decompose_delimited_filters(&$filters,$delimiter) {
		// make an array of filters to lookup their positions in array

		$filter_index = $this->get_filter_indexes($filters);
		// divide up the filter by delimiter, eg a field might have spain|france|italy which will need to be seperated into
		// individual filters.
		foreach ($filters as $key => &$filter) {
			// see if it has a delimiter first
			if (strpos($filter['filter_value'],$delimiter)!==false) {
				// for each delimited item (eg spain,france,italy)
				foreach (explode($delimiter,$filter['filter_value']) as $filter_value_sub) {

					//$move_to_filter = $this->in_subarray($filters,'filter_value',$filter_value_sub);
					// see if the item is already in the filters
					if (!isset($filter_index[$filter_value_sub])) {
						// ccreate a new filter
						$filters[] = array(
								'filter_value' => $filter_value_sub,
								'filter_quantity' => $filter['filter_quantity']);
						$filter_index[$filter_value_sub] = count($filters)-1;
					} else {
						// just add count to an existing filter
						$filters[$filter_index[$filter_value_sub]]['filter_quantity'] += $filter['filter_quantity'];
					}
				}

			}
		}
		foreach ($filters as $key => &$filter) {
			// see if it has a delimiter first
			if (strpos($filter['filter_value'],$delimiter)!==false) {
				// remove this filter as it's a compound one
				unset($filters[$key]);
				//array_splice($filters,$key,1);
			}
		}

	}

	private function get_filter_indexes(&$filters) {
		$filter_index = array();
		foreach ($filters as $index => $filter) {
			$filter_index[$filter['filter_value']] = $index;
		}
		return $filter_index;
	}

	private function get_filter_groups_for_list($group_key,&$group,$column_name,$extra_clause = '') {
		// have to give up on active record select coz of this bug: http://stackoverflow.com/questions/7927458/codeigniter-db-select-strange-behavior
		// if group is multi select then ignore the current filter group in creating the where clause
		if ($group['join']=='or' || $group['join']=='none')
			$count_where = $this->get_filter_fields_where_clause($group_key);
		else
			$count_where = $this->filter_where_clause;

		if ($count_where == '') {
			$sql_filter_count = "{$this->dbprefix}channel_data.entry_id"; //$this->db->select("count(field_id_{$field_id}) as filter_quantity", false);
		} else {
			$sql_filter_count = "CASE WHEN {$count_where} THEN {$this->dbprefix}channel_data.entry_id ELSE NULL END as entry_id";
		}

		$sql = "SELECT {$column_name} as filter_value, {$sql_filter_count} " .
		"FROM {$this->dbprefix}channel_data ";
			
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id = {$this->dbprefix}channel_data.entry_id ";
		if ($this->include_categories)
			$sql .= $this->get_category_join_sql();
		$sql .= "WHERE {$column_name} <> '' ";
		if (isset($this->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->channel_ids) . ")";
		}
		if ($extra_clause!='')
			$sql .= " AND ({$extra_clause}) ";
		//$sql .= "GROUP BY {$field_column}";
		$sql = "SELECT filter_value, count(distinct(entry_id)) as filter_quantity ".
				" FROM ({$sql}) t1 GROUP BY filter_value";
			
		$results = $this->db->query($sql)->result_array();
		return $results;
	}
	
	private function get_category_join_sql() {
		$joins = array();
		foreach($this->filter_groups as $index => $group) {
			if (count($group['category_group']) > 0) {
				$cat_group_in_list = $group['cat_group_in_list'];
				$joins[] = "LEFT OUTER JOIN {$this->dbprefix}category_posts catp_{$index} " .
				"ON catp_{$index}.entry_id = {$this->dbprefix}channel_data.entry_id \n" .
				"LEFT OUTER JOIN {$this->dbprefix}categories cat_{$index} " .
				"ON cat_{$index}.cat_id = catp_{$index}.cat_id AND cat_{$index}.group_id IN {$cat_group_in_list} \n" ;
			}
		}	
		return implode('',$joins);
	}

	private function get_entry_ids_from_database() {
		$sql = "SELECT DISTINCT({$this->dbprefix}channel_data.entry_id) " .
		"FROM {$this->dbprefix}channel_data ";
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id = {$this->dbprefix}channel_data.entry_id ";
		if ($this->include_categories)
			$sql .= $this->get_category_join_sql();
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
			if ($ignore_filter_group!=$key && isset($group['values']) && count($group['values']>0)) {
				if ($group['type']=='number_range')
					$clauses = array_merge($clauses,$this->get_where_clause_for_number_range($key, $group));
				else if ($group['type']=='search')
					$clauses = array_merge($clauses,$this->get_where_clause_for_search($key, $group));
				else // type is list
					$clauses = array_merge($clauses,$this->get_where_clause_for_list_group($key,$group));
			}
		}
		// ensure status is open or whatever is supplied
		$clauses[] = "(status = '' OR status = " . $this->db->escape($this->status) . ")";
		// limit to current site
		$clauses[] = "{$this->dbprefix}channel_titles.site_id = " . intval($this->site);
		// hide expired entries if neccesary
		//if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		$clauses[] =  "(expiration_date = 0 OR expiration_date > {$this->timestamp}) ";
		// hide future entries if neccesary
		//if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		$clauses[] = "entry_date < ".$this->timestamp;
		
		// add search fields if neccesary
		if ($this->search_field_where_clause != '')
			$clauses[] = $this->search_field_where_clause;
	
		// combine all the clauses with an AND statement
		if (count($clauses)>0)
			return implode("\n AND ",$clauses) . "\n";
		else
			return '';

	}

	private function get_where_clause_for_number_range($key,&$group) {
		$clauses = array();
		if (isset($group['fields']) && count($group['values'])>0) {

			foreach ($group['fields'] as $field_name) {
				$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
				if (isset($group['values']['min']) && is_numeric($group['values']['min'])) {
					$value = $this->db->escape_str($group['values']['min']);
					$clauses[] = "CAST(`{$field_column}` AS DECIMAL(5,2)) >= $value";
				}
				if (isset($group['values']['max']) && is_numeric($group['values']['max'])) {
					$value = $this->db->escape_str($group['values']['max']);
					$clauses[] = "CAST(`{$field_column}` AS DECIMAL(5,2)) <= $value";
				}
			}
		}
		return $clauses;
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

	private function array_to_in_list($in_list) {
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


	//
	private function get_where_clause_for_search($key,&$group) {
		$clauses = array();
		if (isset($group['fields']) && count($group['values'])>0) {
			$search_terms = array();
			foreach (explode(' ',$group['values'][0]) as $value) {
				$words = array();
				$value = $this->db->escape_like_str($value);
				foreach ($group['fields'] as $field_name) {
					$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
					$words[] = " `{$field_column}` LIKE '%{$value}%'";
				}
				foreach ($group['category_group'] as $cat_group) {
					$cat_group = $this->db->escape_str($cat_group);
					$words[] = " ( cat_name LIKE '%{$value}%' AND {$this->dbprefix}categories.group_id={$cat_group} )";
				}

				$search_terms[] = '(' . implode(' OR ',$words) . ')';
			}
			$clauses[] = "\n(" . implode("\n AND ",$search_terms) . ")";
		}

		return $clauses;
	}

	// construct the where clause for a group of type "list"
	private function get_where_clause_for_list_group($key,&$group) {
		$clauses = array();
		if (!isset($group['category_group']))
			$group['category_group']=array();
		$group_name = $group['group_name'];

		// channel fields
		if (isset($group['fields'])) {
			$field_list = array();
			// a filter group can have many fields so go through each
			$in_list = array();
			// make a list of possible values for the field
			foreach ($group['values'] as $value) {
				$in_list[] = $this->db->escape($value);
			}
			// example: if field_id_2 is colour and user selects all green or red items:
			//  field_id_2 IN ('green','red')
			if (isset($group['delimiter']) && $group['delimiter']!='') {
				// delimiter seperate values

				$delimiter = $this->db->escape($group['delimiter']);
				if ($group['join']=='or' || $group['join']=='none') {
					// at least one value must be in the listed fields or category groups and search within delimiters
					$field_list = array();
					foreach ($group['fields'] as $field_name) {
						$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
						foreach ($in_list as $value) {
							$field_list[] = " find_in_set({$value}, replace(`{$field_column}`, {$delimiter}, ','))";
						}
					}
					if (count($group['category_group'])>0)
						$field_list[] = " ( cat_{$group_name}.cat_name IN (" . implode(',',$in_list) . ") AND cat_{$group_name}.group_id IN {$group['cat_group_in_list']})";

					$clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
				} else {
					$field_list = array();
					foreach ($in_list as $value) {
						$value_list = array();
						foreach ($group['fields'] as $field_name) {
							$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
							$value_list[] = " find_in_set({$value}, replace(`{$field_column}`, {$delimiter}, ','))";
						}

						if (count($group['category_group'])>0)
							$value_list[] = "{$this->dbprefix}channel_data.entry_id IN (SELECT exp_category_posts.entry_id " .
							"FROM exp_category_posts " .
							"JOIN exp_categories USING (cat_id) " .
							"WHERE cat_name  = {$value} AND group_id IN {$group['cat_group_in_list']} )";

						$field_list[] = "\n(" . implode("\n OR ",$value_list) . ")";
					}

					$clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
				}

			} else {
				if ($group['join']=='or' || $group['join']=='none') {
					// group is multi select so the row must contain at least one value in any fields
					// eg..
					// ( `field_id_15` IN ('Bosch','Green')
					// OR  `field_id_12` IN ('Bosch','Green'))
					$field_list = array();
					foreach ($group['fields'] as $field_name) {
						$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
						$field_list[] = " `{$field_column}` IN (" . implode(',',$in_list) . ")";
					}
					if (count($group['category_group'])>0)
						$field_list[] = " ( cat_{$group_name}.cat_name IN (" . implode(',',$in_list) . ") AND cat_{$group_name}.group_id IN {$group['cat_group_in_list']})";

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
						foreach ($group['fields'] as $field_name) {
							$field_column = $this->_custom_fields[$this->site][$field_name]['field_column'];
							$value_list[] = " `{$field_column}` = {$value}";
						}
						if (count($group['category_group'])>0)
							$value_list[] = "entry_id IN (SELECT exp_category_posts.entry_id " .
							"FROM exp_category_posts " .
							"JOIN exp_categories USING (cat_id) " .
							"WHERE cat_name  = {$value} AND group_id IN {$group['cat_group_in_list']} )";

						$field_list[] = "(" . implode(" OR ",$value_list) . ")";
					}
					$clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
				}
			}
		}
		return $clauses;
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
			$group = isset($this->filter_groups[$group_name]) ? $this->filter_groups[$group_name] : array();
			$group_type = isset($group['type']) ? $group['type'] : 'list'; // list is default group type
			if ($group_type=='number_range')
				$default_or_text = '-to-';
			else if (isset($group['join']) && $group['join']=='or')
				$default_or_text = '-or-';
			else
				$default_or_text = '-and-';
			$any_text = isset($parts[1]) ? $parts[1] : 'any';
			$or_text = isset($parts[2]) ? $parts[2] : $default_or_text;
			$min_text = isset($parts[3]) ? $parts[3] : 'at-least-';
			$max_text = isset($parts[4]) ? $parts[4] : 'at-most-';
			// save url parameter tags for use when creating filter urls
			$this->url_tags[] = array(
					'tag'=>'{'.$tag_name.'}',
					'group_name'=>$group_name,
					'or_text'=>$or_text,
					'any_text'=>$any_text,
					'min_text'=>$min_text,
					'max_text'=>$max_text
			);
			// get the value of the tag

			// if a tag value has been set
			if (isset($tag_values[0][$tag_value_index])) {
				$tag_value = $tag_values[0][$tag_value_index];
				// if the value of the tag is not "any" then add the value
				if ($tag_value!=$any_text && $tag_value!='') {
					if ($group_type == 'number_range') {
						$range = explode($or_text,$tag_value);
						if (count($range)==2)
							$filter_values[$group_name] = array(
									'min'=>$range[0],
									'max'=>$range[1]);
						else if (strpos($tag_value,$min_text)!==false)
							$filter_values[$group_name] = array(
									'min'=>str_replace($min_text,'',$tag_value));
						else if (strpos($tag_value,$max_text)!==false)
							$filter_values[$group_name] = array(
									'max'=>str_replace($max_text,'',$tag_value));
						else // malformed
							$filter_values[$group_name] = array();
					} else {
						$filter_values[$group_name] =  explode($or_text,$tag_value);
					}
					// value has been urlencoded so deencode the url
					foreach ($filter_values[$group_name] as &$filter_value) {
						$filter_value = urldecode($filter_value);
					}
					// if a search on title is being performed then add a flag to include the
					// channel_titles table in sql queries
					if (isset($this->filter_groups[$group_name])
							&& isset($this->filter_groups[$group_name]['fields'])
							&& in_array('title', $this->filter_groups[$group_name]['fields']))
						$this->include_channel_titles = true;


				}

			}

		}
		return $filter_values;

	}

	// must have values in filter_groups[]['values'] set
	private function add_filter_url_to_filters() {
		foreach ($this->filter_groups as $group_name => &$group) {
			if ($group['type']=='list') {
				foreach ($group['filters'] as &$filter) {
					$filter['url'] = $this->get_filter_url($group_name,$filter['filter_value']);
				}
			}
			$group['clear_url'] = $this->get_filter_url($group_name,null);
		}
	}

	// get the url of a filter given a particular filter group name
	private function get_filter_url($filter_group_name = '', $filter_value = null) {
		//$url_template = trim($this->url_tag,'/');
		$url_template = $this->url_tag;
		$result = $url_template;
		foreach ($this->url_tags as $tag) {

			// group name
			$group_name = $tag['group_name'];
			$or_text = $tag['or_text'];
			$any_text = $tag['any_text'];
			$group = $this->filter_groups[$group_name];
			$values = isset($group['values']) ? $group['values'] : array();
			if ($group_name==$filter_group_name) {
				// if filter is null then filter will have no values.
				if (is_null($filter_value)) {
					$values = array();
				} else if ($group['type'] == 'number_range') {
					$values = array();
					if (isset($filter_value['min'])) $values['min']=$filter_value['min'];
					if (isset($filter_value['max'])) $values['max']=$filter_value['max'];
				} else if ($group['type'] == 'search') {
					// there can only be one search value so replace any previous value with the new one
					$values = array($filter_value);
				} else {
					// @todo:
					$filter_value_index = array_search($filter_value,$values);
					if ($group['join']=='none') {
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
				}
			}
			if (count($values)>0) {
				if ($group['type'] == 'number_range') {
					if (isset($values['min']) && !isset($values['max']))
						$url_value = $tag['min_text'].$values['min']; // at least x
					else if (!isset($values['min']) && isset($values['max']))
						$url_value = $tag['max_text'].$values['max']; // at most y
					else if (isset($values['min']) && isset($values['max']))
						$url_value = $values['min'].$tag['or_text'].$values['max']; // x to y
				} else {
					$url_value = implode($or_text,$values);
				}
				$url_value = urlencode($url_value);
				$result = str_replace($tag['tag'],$url_value,$result);

			} else {
				$result = str_replace($tag['tag'],urlencode($any_text),$result);
			}
		}
		// add a leading slash if one isn't provided
		//if (strpos($result,'/')!==0 && strpos($result,'http://')!==0 && strpos($result,'https://')!==0)
		//$reslut = '/' . $result;
		$result=$this->EE->functions->create_url($result);
		return $result;
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
				if (isset($group['values'])) {
					array_merge($group['values'], $values);
				} else {
					$group['values'] = $values;
				}

			} else {
				throw new Exception('filter not found');
			}
		}
	}

	// create tag data
	private function get_tag_data_result($results) {
		$delimiter = '|';
		$tag = array();
		$tag['entries'] = array();
		$tag['breadcrumb'] = array();
		$tag['total_active_filters'] = $this->active_filter_count;
		$tag['total_entries'] = count($results);
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

		// for number_range set the filter_min_value and filter_max_value
		foreach ($this->filter_groups as &$group) {
			$active_index = 0;
			foreach ($group['filters'] as $filter_key => &$filter) {
					
				if ($filter['filter_active']) {
					$filter['active_index'] = $active_index;
					$active_index += 1;
				}

				if ($group['type']=='number_range') {
					$filter['filter_min_value'] = isset($group['values']['min']) ? $group['values']['min'] : '';
					$filter['filter_max_value'] = isset($group['values']['max']) ? $group['values']['max'] : '';
				}
			}

		}

		// html encode all filter data such as values
		$this->html_encode_filters();

		// now to do the filters. must be converted from associative array to normal array
		// EE has bugs when a tag pair is used more than once so make a copy for the breadcrumb
		foreach ($this->filter_groups as $group_name => &$group) {
			$tag['filter_groups'][] = $group;
			if ($this->seperate_filters)
				$tag[$group_name] = array($this->arrayCopy($group));
			//$tag['breadcrumb'][] = $this->arrayCopy($group);
		}


		// put into an array of one element (so it displays once)
		$tag = array($tag);


		// parse tag data ith template
		//return $this->parse_variables_fix($this->EE->TMPL->tagdata, $tag);

		return $this->EE->TMPL->parse_variables($this->tagdata, $tag);


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
		$sql = "SELECT field_id, field_type, field_name, site_id, field_label, concat('field_id_',field_id) as field_column
		FROM {$this->dbprefix}channel_fields
		WHERE field_type != 'date'
		AND field_type != 'rel'";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0)
		{
			foreach ($query->result_array() as $row)
			{
				// assign standard custom fields
				$this->_custom_fields[$row['site_id']][$row['field_name']] = $row;
			}
			foreach ($this->_custom_fields as $site_id => $field) {
				$this->_custom_fields[$site_id]['title'] = array(
						'field_type' => 'text',
						'field_name' => 'title',
						'site_id' => $site_id,
						'field_label' => 'title',
						'field_column' => 'title'
				);
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
	private function arrayCopy( array $array ) {
		$result = array();
		foreach( $array as $key => $val ) {
			if( is_array( $val ) ) {
				$result[$key] = $this->arrayCopy( $val );
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

	public function testEE() {
		$tag = array(
				'colour' =>
				array(
						array('data'=>array(
								array('hex'=>'#00f','text'=>'blue'),
								array('hex'=>'#0f0','text'=>'green')
						)
						)
				),
		);
		$tag = array($tag);

		return $this->parse_variables_fix($this->EE->TMPL->tagdata, $tag);
		//return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata,$tag);
	}

	private function parse_variables_fix($tagdata,$tag) {
		//return $this->EE->TMPL->parse_variables($result,$tag);
		foreach ($tag[0] as $tag_name => &$tag_value) {
			preg_match_all('/\{' . preg_quote($tag_name) . '\}[\s\S]+?\{\/' . preg_quote($tag_name) . '\}/',$tagdata,$matches);
			for ($i=0;$i<count($matches[0]);$i+=1) {
				$parsed = $this->EE->TMPL->parse_variables($matches[0][$i],$tag);
				$tagdata = str_replace($matches[0][$i],$parsed,$tagdata);
			}
		}
		return $tagdata;
	}




}