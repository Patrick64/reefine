<?php
class Reefine_group_category extends Reefine_group_list {
	public $type = 'list';
	static $active_categories = false;

	var $custom_values;
	var $custom_titles;
	var $clear_url;
	
	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
	}
	
	static public function get_active_categories($reefine) {
		if (self::$active_categories === false) {
			self::$active_categories = array();
			$clauses = array();
			$params = array();
			foreach ($reefine->filter_groups as $key => $group) {
				// If group has values
				if ((is_subclass_of($group,'Reefine_group_category') || get_class($group) == 'Reefine_group_category')
				&& (isset($group->values) && count($group->values)>0)) {
					foreach ($group->values as $v) { 
						$clauses[] = " ( group_id IN ( " . implode(',',$group->category_group) . " ) AND cat_url_title = ? )";
						
						$params[] = $v;
					}
							
				}
				if (count($clauses)>0)  {
					$sql = "select cat_id, cat_name, cat_url_title from exp_categories where " . join(' OR ',$clauses);
					self::$active_categories = ee()->db->query($sql,$params)->result();
				}
			}		
			
		} 
		return self::$active_categories;
		
		
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
	
		if ($this->join=='or' || $this->join=='none') {
			$entries_where_clause = $this->reefine->get_filter_fields_where_clause($this->group_name,true);
		} else { // join is 'and' so use current filter
			//$entries_where_clause = $this->reefine->filter_where_clause;
			$entries_where_clause = $this->reefine->get_filter_fields_where_clause('',true);
		}
		
		
		$sql = " /* Reefine_group_category->get_filter_groups_for_list({$column_name}..) */
		SELECT 
			cat.cat_url_title AS filter_value,
			cat.cat_name AS filter_title,
			cat.cat_id AS filter_id,
			IFNULL(filter_quantity, 0) AS filter_quantity,
		    cat.cat_order,
		    cat.parent_id,
		    cat.group_id
		FROM
		    exp_categories cat
		        LEFT OUTER JOIN
		    (SELECT 
        		COUNT(DISTINCT (pp.entry_id)) as filter_quantity, pp.cat_id
    		FROM
		    (SELECT 
		        p1.cat_id,
		            p1.entry_id
		    FROM
		        exp_category_posts p1
				";
		
		//$joins = $this->reefine->get_query_join_sql($this->group_name,false);
		$i = 2;
		$joins = array();
		foreach ($this->reefine->filter_groups as $key => $group) {
			// If group has values
			if ((is_subclass_of($group,'Reefine_group_category') || get_class($group) == 'Reefine_group_category')  
				&& $key != $this->group_name 
				&& (isset($group->values) && count($group->values)>0)) {
				
				$joins[] = $group->get_inner_join("p1.entry_id","p" . $i);
				
			}
			$i++;
		}
		$sql .= implode("\n",$joins);
		
		$sql .= " GROUP BY p1.cat_id , p1.entry_id) pp ";
		
		//if (isset($this->reefine->channel_ids)) {
		$sql .= " INNER JOIN {$this->dbprefix}channel_data on pp.entry_id = {$this->dbprefix}channel_data.entry_id \n";
		$sql .= " INNER JOIN {$this->dbprefix}channel_titles on pp.entry_id = {$this->dbprefix}channel_titles.entry_id \n";
		if (isset($this->reefine->channel_ids)) $sql .= " and {$this->dbprefix}channel_titles.channel_id IN (" . implode(',',$this->reefine->channel_ids) . ")";
		
		//}
			$sql .= $this->reefine->get_query_join_sql($this->group_name,false);
		if ($extra_clause!='') {
			//$sql .= " AND ({$extra_clause}) ";
		}
		if ($entries_where_clause) {
			$sql .= " \n WHERE " . $entries_where_clause . " \n";
		}
		
		 
		$sql .= "
		    GROUP BY cat_id) counts ON counts.cat_id = cat.cat_id
		WHERE
		    cat.group_id IN ( " . implode(',',$this->category_group) . " ) " . $order_by;
		
		/*
		
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
		$filters_sql = " SELECT filter_value, filter_title, filter_id, count(distinct(entry_id)) as filter_quantity " .
		((count($extra_columns)>0) ? "," . implode(',',array_keys($extra_columns)) : '') .
		" FROM ({$sql}) t1 GROUP BY filter_value, filter_id, filter_title " . $order_by;
	*/
		$results = $this->reefine->db->query($sql)->result_array();
		return $results;
	}
	
	
	public function get_inner_join($entry_id_column, $table_alias) {
		$active_categories = self::get_active_categories($this->reefine);
		$cat_ids = array();
		foreach ($active_categories as $cat) {
			if (array_search($cat->cat_url_title, $this->values) !== false) {
				$cat_ids[] = $cat->cat_id;
			} 
		}
		// {$table_alias}.cat_id
		if ($this->join=='and') {
			$sql = '';
			foreach ($cat_ids as $cat_id) {
				$sql .= " INNER JOIN exp_category_posts {$table_alias}_{$cat_id} ON {$entry_id_column} = {$table_alias}_{$cat_id}.entry_id AND {$table_alias}_{$cat_id}.cat_id = {$cat_id} \n"; 
			}
			return $sql;
		} else {
			$or_statements = array();
			foreach ($cat_ids as $cat_id) {
				$or_statements[] = "{$table_alias}.cat_id = {$cat_id}";
			}
		
			$sql = " INNER JOIN exp_category_posts {$table_alias} ON {$entry_id_column} = {$table_alias}.entry_id "; 
			if (count($or_statements)>0) $sql .= " AND ( " . implode (' OR ', $or_statements) . " )"; 
			return $sql;
		}
	}
	
	public function get_group_where_clause($exclude_categories = false) {
		if ($exclude_categories) {
			return array();	
		} else {
			return parent::get_group_where_clause(false);
		}
	}
	
}
