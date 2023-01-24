<?php
class Reefine_group_number_range extends Reefine_group {
	public $type = 'number_range';

	/** If locale is set then number will be parsed using that locale (eg 9.999,99 = 9999.99 for locale DE_de) */
	public $locale = '';

	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
		$this->show_empty_filters=true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Reefine_group::set_settings_from_parameters()
	 */
	public function set_settings_from_parameters() {
		parent::set_settings_from_parameters();
		// add rest of settings which are strings/arrays/booleans
		$this->reefine->add_filter_group_setting($this, 'locale', locale_get_default());
	}

	public function post_contains_filter_value() {
		$value_min = $this->reefine->EE->input->get_post($this->group_name.'_min');
		$value_max = $this->reefine->EE->input->get_post($this->group_name.'_max');
		return ($value_min!==false || $value_max!==false);
	}

	public function get_filter_value_from_post() {
		$formatter = new NumberFormatter( $this->locale, NumberFormatter::DECIMAL );
		$str_min = $this->reefine->EE->input->get_post($this->group_name.'_min');
		$str_max = $this->reefine->EE->input->get_post($this->group_name.'_max');
		$value_min = $formatter->parse($str_min);
		$value_max = $formatter->parse($str_max);
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
			$this->reefine->EE->functions->redirect($this->reefine->create_url($url));
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

		$sql = "SELECT count(distinct({$this->dbprefix}channel_titles.entry_id)) as filter_quantity, " .
		"{$filter_min_sql} as filter_min, " .
		"{$filter_max_sql} as filter_max " .
		"FROM {$this->dbprefix}channel_titles ";
		//if ($this->include_channel_titles)
		
		$sql .= $this->reefine->get_query_join_sql($this->group_name,false);
		$sql .= "WHERE 1=1 ";
		if (isset($this->reefine->channel_ids)) {
			$sql .= " AND {$this->dbprefix}channel_titles.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
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
					'filter_id'=>'',
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


	public function get_group_where_clause($exclude_categories = false) {
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
