<?php

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
			$results = $this->get_filter_groups_for_list(
					$this->get_field_value_column($field),
					$this->get_field_title_column($field),
					$field->get_filter_id_field(),
					$field->get_filter_extra_columns(),
					$field->get_filter_extra_clause(),
					$field->get_filter_order_by());
			$this->filters = array_merge($this->filters,$results);
			
		}
		/*
		if (count($this->category_group)>0) {
			$results = $this->get_filter_groups_for_list(
					"cat_{$this->group_name}.cat_url_title",
					"cat_{$this->group_name}.cat_name",
					"cat_{$this->group_name}.cat_id",
					array("cat_order"=>"cat_{$this->group_name}.cat_order",
					"parent_id"=>"cat_{$this->group_name}.parent_id",
					"group_id"=>"cat_{$this->group_name}.group_id"),
					"cat_{$this->group_name}.group_id IN {$this->cat_group_in_list}",
					"ORDER BY group_id,parent_id, cat_order ");
			$this->filters = array_merge($this->filters,$results);
		}*/
		// remove duplicates http://stackoverflow.com/a/946300/1102000
		//$this->filters = array_map("unserialize", array_unique(array_map("serialize", $this->filters)));
		$this->combine_duplicate_filters();
		// if group has delimiter
		$delimiter = isset($this->delimiter) ? $this->delimiter : '';
		if ($delimiter!='') {
			$this->decompose_delimited_filters($delimiter);
		}
		
		// set totals for use in templates
		$this->add_custom_filters();
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
		
		// @TODO: Move this to the WHERE clause
		
	}
	/**
	 * Get an array of filter group for the list filter group type
	 * @param unknown $column_name Name of database column for {filter_value}, may be preceeded by table name
	 * @param unknown $title_column_name Name of database column for the {filter_title}, may be preceeded by table name
	 * @param string $filter_column_id Name of database column for {filter_id} (this is usually cat_id), may be preceeded by table name
	 * @param unknown $extra_columns An associative array of extra columns of form "column-alias" => "database-column"
	 * @param string $extra_clause Any extra clauses on where cluse
	 * @return array
	 */
	public function get_filter_groups_for_list($column_name,$title_column_name,$filter_column_id = '',$extra_columns = array(), $extra_clause = '', $order_by = '') {
		// have to give up on active record select coz of this bug: http://stackoverflow.com/questions/7927458/codeigniter-db-select-strange-behavior
		
		$sql = "SELECT {$column_name} as filter_value, " .
		($filter_column_id ? $filter_column_id : "''") . " as filter_id, " .
		"{$title_column_name} as filter_title, {$this->get_filter_count_statement()} " . 
		Reefine::column_implode($extra_columns) .
		" FROM {$this->dbprefix}channel_data ";
			
		//if ($this->include_channel_titles)
		$sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id = {$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->reefine->get_query_join_sql($this->group_name,false);
		$sql .= " WHERE {$column_name} <> '' ";
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_data.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
		}
		if ($extra_clause!='')
			$sql .= " AND ({$extra_clause}) ";
		// Wrap sql statement in select statement so we can get total of each distinct entry
		
		// now wrap in a select that will add all distinct entries together to get {filter_quantity} 
		$filters_sql = "/* get_filter_groups_for_list({$column_name}..) */ SELECT filter_value, filter_title, filter_id, count(distinct(entry_id)) as filter_quantity " .
		((count($extra_columns)>0) ? "," . implode(',',array_keys($extra_columns)) : '') . 
		" FROM ({$sql}) t1 GROUP BY filter_value, filter_id, filter_title " . $order_by;
		
		$results = $this->reefine->db->query($filters_sql)->result_array();
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
						//$field_list[] = " instr(concat({$delimiter},{$this->get_field_value_column($field)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
						$field_list[] = $field->get_where_clause($this, $in_list, $value);
					}
				}
				//if (count($this->category_group)>0)
					//$field_list[] = " ( cat_{$this->group_name}.cat_url_title IN (" . implode(',',$in_list) . ") AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list})";
	
				if ($field_list)  $clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
			} else {
				$field_list = array();
				foreach ($in_list as $value) {
					$value_list = array();
					foreach ($this->fields as $field) {
						//$value_list[] = " instr(concat({$delimiter},{$this->get_field_value_column($field)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
						$value_list[] = $field->get_where_clause($this, $in_list, $value);
					}
	
					/*if (count($this->category_group)>0)
						$value_list[] = "{$this->dbprefix}channel_data.entry_id IN (SELECT exp_category_posts.entry_id " .
						"FROM exp_category_posts " .
						"JOIN exp_categories USING (cat_id) " .
						"WHERE cat_url_title  = {$value} AND group_id IN {$this->cat_group_in_list} )";*/
	
					$field_list[] = "\n(" . implode("\n OR ",$value_list) . ")";
				}
	
				if ($field_list) $clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
			}
	
		} else {
			if ($this->join=='or' || $this->join=='none') {
				// group is multi select so the row must contain at least one value in any fields
				// eg..
				// ( `field_id_15` IN ('Bosch','Green')
				// OR  `field_id_12` IN ('Bosch','Green'))
				$field_list = array();
				foreach ($this->fields as $field) {
					//$field_list[] = " {$this->get_field_value_column($field)} IN (" . implode(',',$in_list) . ")";
					$field_list[] = $field->get_where_clause($this, $in_list);
				}
				//if (count($this->category_group)>0)
					//$field_list[] = " ( cat_{$this->group_name}.cat_url_title IN (" . implode(',',$in_list) . ") AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list})";
	
				if ($field_list) $clauses[] = "\n(" . implode("\n OR ",$field_list) . ")";
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
						//$value_list[] = " {$field->column_name} = {$value}";
						$value_list[] = $field->get_where_clause($this, $in_list,$value);
					}
					/*if (count($this->category_group)>0)
						$value_list[] = "{$this->dbprefix}channel_data.entry_id IN (SELECT exp_category_posts.entry_id " .
						"FROM exp_category_posts " .
						"JOIN exp_categories USING (cat_id) " .
						"WHERE cat_url_title  = {$value} AND group_id IN {$this->cat_group_in_list} )";*/
	
					$field_list[] = "(" . implode(" OR ",$value_list) . ")";
				}
				if ($field_list) $clauses[] = "\n(" . implode("\n AND ",$field_list) . ")";
			}
		}
	
		return $clauses;
	}
	
}