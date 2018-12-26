<?php
/**
 * EE's categories
 * @author Patrick
 *
 */
class Reefine_field_category extends Reefine_field {

	private $relation_field_id;
	protected $filter_group;
	private $child_field_name;
	private $parent_field_name;
	private $table_alias;
	private $table_alias_titles;
	private $table_alias_data;

	function __construct($reefine,$group_ids,$filter_group) {
		$this->group_ids = $group_ids;
		$this->reefine = $reefine;
		$this->cat_group_in_list = $this->reefine->array_to_in_list($group_ids);
		// set up some attributes of this class
		$this->filter_group = $filter_group;
		
		$this->group_name = preg_replace('/[^A-Z0-9]/i','_',$filter_group->group_name);
		$this->dbprefix = $reefine->dbprefix;


	}

	function get_value_column($table='') {
		return "cat_{$this->group_name}.cat_url_title";
	}

	function get_title_column() {
		return "cat_{$this->group_name}.cat_name";
	}

	function get_filter_id_field() {
		return "cat_{$this->group_name}.cat_id";
	}
	
	function get_filter_extra_columns() {
		return array("cat_order"=>"cat_{$this->group_name}.cat_order",
					"parent_id"=>"cat_{$this->group_name}.parent_id",
					"group_id"=>"cat_{$this->group_name}.group_id");
	}
	
	function get_filter_extra_clause() {
		return "cat_{$this->group_name}.group_id IN {$this->cat_group_in_list}";
	}
	
	function get_filter_order_by() {
		return "ORDER BY group_id,parent_id, cat_order ";
	}
	
	function get_join_sql() {
		$joins = array();
		
		if (count($this->group_ids) > 0) {
			$joins[] = "LEFT OUTER JOIN {$this->dbprefix}category_posts catp_{$this->group_name} " .
			"ON catp_{$this->group_name}.entry_id = {$this->dbprefix}channel_titles.entry_id \n" .
			"LEFT OUTER JOIN {$this->dbprefix}categories cat_{$this->group_name} " .
			"ON cat_{$this->group_name}.cat_id = catp_{$this->group_name}.cat_id AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list} \n" ;
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
		if ($filter_group->join=='or' || $filter_group->join=='none') {
			return " ( cat_{$this->group_name}.cat_url_title IN (" . implode(',',$in_list) . ") AND cat_{$this->group_name}.group_id IN {$this->cat_group_in_list})";
		} else { // AND
			return "{$this->dbprefix}channel_titles.entry_id IN (SELECT exp_category_posts.entry_id " .
					"FROM exp_category_posts " .
					"JOIN exp_categories USING (cat_id) " .
					"WHERE cat_url_title  = {$value} AND group_id IN {$this->cat_group_in_list} )";
		}
	}
}
