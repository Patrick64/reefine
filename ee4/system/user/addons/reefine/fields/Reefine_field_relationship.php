<?php
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

	function get_value_column($table='') {
		if ($table!='')
			return "{$table}. " . $this->get_field_by_key($this->child_field_name,'field_column');
		else if ($this->child_field_name=='')
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
		"ON {$this->table_alias}.parent_id = {$this->reefine->dbprefix}channel_titles.entry_id " .
		"AND {$this->table_alias}.field_id = {$this->relation_field_id} ");




		$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_titles {$this->table_alias_titles} " .
		"ON {$this->table_alias_titles}.entry_id = {$this->table_alias}.child_id " .
		"AND " . $this->reefine->get_status_where_clause($this->reefine->status,"{$this->table_alias_titles}.status");

		// include channel_data only if we need fields from the related entry
		if ($this->child_field_name!='' && $this->child_field_name!='title') {
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_data {$this->table_alias_data} " .
			"ON {$this->table_alias_data}.entry_id = {$this->table_alias_titles}.entry_id ";
		}

		return $joins;


	}

	/**
	 * Get where clause to be used
	 * @param unknown $filter_group
	 * @param unknown $in_list
	 * @param string $value
	 * @return string
	 */
	function get_where_clause($filter_group,$in_list=false,$value=false) {

		if ($filter_group->join=='or' || $filter_group->join=='none') { // join="or" join="none" work like normal
			return parent::get_where_clause($filter_group,$in_list,$value);
		} else { // join="and"
				
			// if there is a delimiter involved (eg selects)
			if (isset($filter_group->delimiter) && $filter_group->delimiter!='') {
				$delimiter = $filter_group->db->escape($filter_group->delimiter);
			} else {
				$delimiter=false;
			}

			// if we want a custom field
			if ($this->child_field_name!='' && $this->child_field_name!='title') {
				// create the where clause part for the value itself.
				if ($delimiter===false)
					$where_value = "{$filter_group->get_field_value_column($this,'d')} = {$value}";
				else // there's a delimiter so we need to search a delimited list, notice we're passing in the table alias ('d')
					$where_value = "instr(concat({$delimiter},{$filter_group->get_field_value_column($this,'d')},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";

				// uses channel_data
				return "{$this->channel_data_alias}.entry_id IN ( " .
				"SELECT r.parent_id AS entry_id FROM {$this->reefine->dbprefix}relationships r " .
				"INNER JOIN {$this->reefine->dbprefix}channel_titles t ON t.entry_id=r.child_id  " .
				"INNER JOIN {$this->reefine->dbprefix}channel_data d ON d.entry_id=t.entry_id  " .
				"WHERE $where_value " .
				"AND " . $this->reefine->get_status_where_clause($this->reefine->status,"t.status") . ")";

			} else { // we want just the title of the related field
				if ($delimiter===false)
					$where_value = "t.url_title = {$value}";
				else // I dont know why the title would be a delimited list but nevermind
					$where_value = " instr(concat({$delimiter},t.url_title,{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
				// uses just titles
				return "{$this->channel_data_alias}.entry_id IN ( " .
				"SELECT r.parent_id AS entry_id FROM {$this->reefine->dbprefix}relationships r " .
				"INNER JOIN {$this->reefine->dbprefix}channel_titles t ON t.entry_id=r.child_id  " .
				"WHERE $where_value  AND {$this->reefine->get_status_where_clause($this->reefine->status,"t.status")} )";
			}
		}
	}

}
