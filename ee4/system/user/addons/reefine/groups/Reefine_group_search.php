<?php

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
					'filter_id' => '',
					'group_name' => $this->group_name,
					'filter_quantity'=>1,
					'filter_active'=>true));
			
		} else {
			$this->matching_filters = 0;
			$this->active_filters = 0;
			$this->filters = array(array(
					'filter_value'=> '',
					'filter_title' => '',
					'filter_id' => '',
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