<?php

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
	
	/**
	 * To account for where_before and where_after parameters we need to join tables even if ther isn't a active filter
	 */
	public function get_global_join_sql() {
		if ((!empty($this->where_before) && is_numeric($this->where_before)) ||
		(!empty($this->where_after) && is_numeric($this->where_after))) {
			return $this->get_join_sql();
		} else {
			return array();
		}
		
		
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
		"FROM {$this->dbprefix}channel_titles ";
		//if ($this->include_channel_titles)
		// $sql .= "JOIN {$this->dbprefix}channel_titles ON {$this->dbprefix}channel_titles.entry_id={$this->dbprefix}channel_data.entry_id ";
		$sql .= $this->reefine->get_query_join_sql($this->group_name);
		$sql .= "WHERE 1=1 ";
		
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_titles.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
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
								'filter_title' => $this->reefine->EE->localize->format_date('%F %Y', $current->getTimestamp()), // format using EE
								'filter_id' => '',
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
	public function get_group_where_clause($exclude_categories = false)  {
		$clauses = array();

		// a filter group can have many fields so go through each
		$in_list = array();
		// make a list of possible values for the field
		foreach ($this->values as $value) {
			$in_list[] = $this->db->escape($value);
		}

		$field_list = array();
		foreach ($in_list as $value) {
				
			//$month_value = "DATE_ADD(LAST_DAY(DATE_SUB(DATE({$value}), interval 30 day)), interval 1 day)";
			$month_value = " ( YEAR(DATE({$value}))*12 + MONTH(DATE({$value})) ) ";
			$min_value =  $this->get_field_value_column($this->fields[0]); 
			$min_column = " ( YEAR(DATE({$min_value}))*12 + MONTH(DATE({$min_value})) ) ";
			
			$statement = "{$min_column} = {$month_value}";
			
			if (count($this->fields)>1) {
				$max_value =  $this->get_field_value_column($this->fields[1]);
				$max_column = " ( YEAR(DATE({$max_value}))*12 + MONTH(DATE({$max_value})) ) ";
					
				
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
	public function get_field_value_column($field,$table='') {
		return "str_to_date(concat('1-',concat(concat( MONTH(FROM_UNIXTIME({$field->get_value_column($table)})),'-'),YEAR(FROM_UNIXTIME({$field->get_value_column($table)})))),'%d-%m-%Y')";
		
	}

}