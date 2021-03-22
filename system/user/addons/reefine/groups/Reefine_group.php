<?php 


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
	 * Sort order, 'asc' for ascending, 'desc' for descending
	 * @var integer
	 */
	public $sort = 'asc';
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
	 * only show these filters when specified directly
	 * @var unknown
	 */
	public $show_separate_only = false;
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
	 * Default filter values
	 * @var unknown
	 */
	public $default = '';
	/**
	 * Privte groups use the default value only and don't allow the user to change its filter value
	 */
	public $private = false;
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
		// get objects of class Reefine_field_category for each group
		$category_groups = $this->reefine->get_filter_group_setting($this->group_name, 'category_group', array(), 'array');
		if (count($category_groups)>0) {
			$this->fields[] = $this->reefine->get_category_field_obj($category_groups,$this);
		}
		// add rest of settings which are strings/arrays/booleans
		$this->reefine->add_filter_group_setting($this, 'label', $this->group_name);
		$this->reefine->add_filter_group_setting($this, 'delimiter', '');
		$this->reefine->add_filter_group_setting($this, 'join', 'or', 'text');
		$this->reefine->add_filter_group_setting($this, 'orderby', 'value', 'text');
		$this->reefine->add_filter_group_setting($this, 'sort', 'asc', 'text');
		$this->reefine->add_filter_group_setting($this, 'category_group', array(), 'array');
		$this->reefine->add_filter_group_setting($this, 'show_empty_filters', false, 'bool');
		$this->reefine->add_filter_group_setting($this, 'custom_values', false, 'array');
		$this->reefine->add_filter_group_setting($this, 'custom_titles', false, 'array');
		$this->reefine->add_filter_group_setting($this, 'show_separate_only', false, 'bool');
		$this->reefine->add_filter_group_setting($this, 'default', array(), 'array');
		$this->reefine->add_filter_group_setting($this, 'private', false, 'bool');

		//if (count($this->category_group)>0) {
			//$this->cat_group_in_list = $this->reefine->array_to_in_list($this->category_group);
		//}
		
	}
	
	
	
	
	/**
	 * Get the value column of a field for SQL.
	 * Usally just get the value column for the field 
	 * @param Reefine_field $field
	 */
	public function get_field_value_column($field,$table='') {
		return $field->get_value_column($table);
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
	
	public function get_global_join_sql() {
		return array();
	}
	

	public function add_filter_values($filter_values) {
		if (isset($filter_values))
			$this->values = array_merge($this->values, $filter_values);
	}
	
	
	static public function create_group_by_type($group_type,$group_name,$reefine) {
		$class_name = 'Reefine_group_' . $group_type;
		if (class_exists($class_name)) {
			return new $class_name($reefine,$group_name);
		} else {
			throw new Exception("Reefine error: filter:$group_name:type=\"$group_type\" is not valid as group type \"$group_type\" does not exist. Consult docs for available group types https://github.com/Patrick64/reefine#filterfilter-grouptype ");
		}
	}
	
	public function get_settings_from_parameters($params) {
		
	}
	
	public function do_redirect_for_text_input() {
		$value = $this->reefine->EE->input->get_post($this->group_name);
		
		if ($value !== false) {
			$url = $this->reefine->get_filter_url($this->group_name,$value,true);
			$this->reefine->EE->functions->redirect($this->reefine->create_url($url));
			return;
		}
		
	}
	
	
	public function post_contains_filter_value() {
		$value = $this->reefine->EE->input->get_post($this->group_name);
		return ($value!==false);
	}
	
	public function get_filter_value_from_post() {
		$value = $this->reefine->EE->input->get_post($this->group_name);
		if (is_array($value)) {
			// <option value="">Any</option> will post array('') so we need to ignore that
			if (count($value)>0 && $value[0]!='')
				return $value;
			else 
				return array();
		} else if ($value!==false && $value!=='') {
			return array($value);
		} else {
			return null;
		}
		
	}
	
	public function get_filter_url_tags_array($tag_name,$parts) {
		//$group_type = isset($group['type']) ? $group['type'] : 'list'; // list is default group type
		if ($this->type=='number_range' || $this->type=='date_range') // TODO: move to number range class
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
		$tag_array = array(
				'tag'=>'{'.$tag_name.'}',
				'group_name'=>$this->group_name,
				'or_text'=>$or_text,
				'any_text'=>$any_text,
				'min_text'=>$min_text,
				'max_text'=>$max_text
		);
		return $tag_array;
		
	}
	
	public function get_filter_values_from_url($tag_value,$url_tag) {
		// if the value of the tag is not "any" then add the value
		if ($tag_value!=$url_tag['any_text'] && $tag_value!='') {
			if ($this->type == 'number_range' || $this->type == 'date_range') {
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
		if ($this->private) return array(); // private groups dont add values to the url
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
		return $joins;
	}
	
	public function get_category_join_sql() {
		$joins = array();		
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
								'filter_id' => '',
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
	
	

	function compare_filter_by_value($a, $b)
	{
		if (is_numeric($a['filter_value']) && is_numeric($b['filter_value'])) {		
			return $this->sort_filter(floatval($a['filter_value'])>floatval($b['filter_value']) ? 1 : -1);
		} else {
			return $this->sort_filter((strcmp($a['filter_value'], $b['filter_value'])>0) ? 1 : -1);
		}

	}
	
	function compare_filter_by_count($a, $b){
		if ($a['filter_quantity'] == $b['filter_quantity'])
			return $this->compare_filter_by_value($a, $b);
		else
			return $this->sort_filter($a['filter_quantity'] < $b['filter_quantity'] ? 1 : -1);
	}
	
	function compare_filter_by_active($a, $b){
		if ($a['filter_active'] == $b['filter_active'])
			return $this->compare_filter_by_value($a, $b);
		else
			return $this->sort_filter($a['filter_active'] < $b['filter_active'] ? 1 : -1);
	}
	
	function compare_filter_by_active_count($a, $b){
		if ($a['filter_active'] == $b['filter_active'])
			return $this->compare_filter_by_count($a, $b);
		else
			return $this->sort_filter($a['filter_active'] < $b['filter_active'] ? 1 : -1);
	}
	
	function compare_filter_by_custom($a, $b){
		$pos_a = stripos('|' . $this->orderby . '|','|' . $a['filter_value'] . '|');
		$pos_b = stripos('|' . $this->orderby . '|','|' . $b['filter_value'] . '|');
		
		if ($pos_a === false)
			return -1;
		else if ($pos_b === false) 
			return 1;
		else if ($pos_a === $pos_b) 
			return 0;
		else
			return $this->sort_filter($pos_a > $pos_b ? 1 : -1);
	}
	
	function sort_filter($order) {
		if ($this->sort == 'desc')
			return -$order;
		else
			return $order;
	}
	
	/**
	 * Sort filters based on $sort
	 * @param array $filters
	 * @param string $sort value, count, active, or active_count
	 */
	public function sort_filters() {
		if ($this->orderby == 'value' || $this->orderby == '')
			usort($this->filters, array($this,"compare_filter_by_value"));
		else if ($this->orderby == 'quantity')
			usort($this->filters, array($this,"compare_filter_by_count"));
		else if ($this->orderby == 'active')
			usort($this->filters, array($this,"compare_filter_by_active"));
		else if ($this->orderby == 'active_quantity')
			usort($this->filters, array($this,"compare_filter_by_active_count"));
		else
			usort($this->filters, array($this,"compare_filter_by_custom"));
	}
	
	/**
	 * Get array of group values formatted for output with array of filters.
	 * @param string $only_show_active If true only returns active filters
	 * @param bool $is_separate_filter If the filter group is called using the filter group's name eg {colour} as opposed to {list_groups}
	 * @return array
	 */
	function get_filters_for_output($only_show_active) {
		$group = array();
		// get attributes of group
		foreach (get_object_vars($this) as $key => $val) {
			if (is_string($val)) {
				$group[$key] = htmlspecialchars($val, ENT_QUOTES);
			}
		}
		
		// format filters for output
		$group['filters'] = array();
		$group['active_filters'] = $this->active_filters;
		$group['total_filters'] = $this->total_filters;
		$group['matching_filters'] = $this->matching_filters;
		$filter_values = array();
		foreach ($this->values as $filter_value) {
			$filter_values[] = array('value'=>$filter_value);
		}
		$group['active_filter_values'] = $filter_values;
		
		// add up total number of results
		$filter_total_results=0;
		foreach ($this->filters as $filter_key => $filter) {
			$filter_active = $filter['filter_active'];
			$filter_quantity = $filter['filter_quantity'];
			// Check that - if only show active then only show if active ALSO if hide empty filters then only show if filter is not empty or is active
			if ( (!$only_show_active || $filter_active) &&  ($this->show_empty_filters || $filter_active || $filter_quantity>0) )  {
				$filter_total_results++;
			}
		}

		$active_index = 0;
		$filter_count = 1;
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
				$filter_out['count'] = $filter_count;
				$filter_out['total_results'] = $filter_total_results;
				// stop xss
				foreach ($filter as $key => $val) {
					$filter_out[$key] = htmlspecialchars($val, ENT_QUOTES);
				}
				
				// number range doessome stuff
				$this->format_filter_for_output($filter,$filter_out);
				
				$group['filters'][] = $filter_out;
				$filter_count++;
			}
		}
		
		return $group;
	}
		
	function format_filter_for_output($filter_in,&$filter_out) {
		// abstract	
	}
	
	public function get_group_where_clause($exclude_categories = false)  {
		//abstract
		return array();
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
	
	function add_custom_filters() {
	
		if ($this->custom_values) {
			$custom_filers = array();
			foreach ($this->custom_values as $i => $value) {
				$filter_title = ($this->custom_titles && isset($this->custom_titles[$i])) ? $this->custom_titles[$i] : $value;
				$this->filters[] = array(
						'filter_value' => $value,
						'filter_title' => $filter_title,
						'filter_id' => 0,
						'filter_quantity' => 1
				);
			}
		}
	}
	
	
}