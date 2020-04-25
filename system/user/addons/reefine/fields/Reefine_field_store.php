<?php

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
		parent::__construct($reefine, $ee_field_name);
		$dbprefix = $reefine->dbprefix;
		$this->reefine = $reefine;
		$this->assign_field_info($ee_field_name);
		$this->field_name = $field_name;
		$this->child_column=$child_column;
		$this->table_alias = 'store_products_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name);
		$this->sales_alias = 'store_sales_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name);
		$this->sales_cat_alias = 'store_sales_cat_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name);
		
	}
	
	
	function get_value_column($table='') {
		if ($this->child_column=='on_sale') 
			return "(CASE WHEN {$this->sales_alias}.enabled = 1 THEN '1' ELSE '' END)";			
		else
			return "{$this->table_alias}.{$this->child_column}";
	}
	
	function get_title_column() {
		if ($this->child_column=='on_sale')
			return "(CASE WHEN {$this->sales_alias}.enabled = 1 THEN 'On sale' ELSE '' END)";
		else
			return $this->get_value_column();
		
	}
	
	function get_join_sql() {
		$joins = array("LEFT OUTER JOIN {$this->reefine->dbprefix}store_products {$this->table_alias} " .
			"ON {$this->table_alias}.entry_id = {$this->channel_data_alias}.entry_id ");
		// if we want the on_sale parameter then we have to check the start/end date of the sale
		// then check if the current entry is listed in the entry_ids column or if the entry's category id
		// is in the category_ids column, both columns are pipe delimited numbers
		if ($this->child_column=='on_sale') {
			// http://ellislab.com/expressionengine/user-guide/development/usage/session.html
			$member_group_id = $this->reefine->EE->session->userdata('group_id'); //$ee->session; // ->userdata; //['group_id'];
			// join exp_store_sales table entries if the sale is in the current date and matches a list of entry_ids
			// or category ids, optionally restricted by member group
			$joins[] = " LEFT OUTER JOIN {$this->reefine->dbprefix}category_posts {$this->sales_cat_alias}
			ON {$this->sales_cat_alias}.entry_id = {$this->channel_data_alias}.entry_id
			LEFT OUTER JOIN {$this->reefine->dbprefix}store_sales {$this->sales_alias} 
			ON ({$this->sales_alias}.start_date IS NULL OR {$this->sales_alias}.start_date<=UNIX_TIMESTAMP()) 
			AND  ({$this->sales_alias}.end_date IS NULL OR {$this->sales_alias}.end_date>=UNIX_TIMESTAMP())
			AND (NULLIF({$this->sales_alias}.member_group_ids,'') IS NULL 
				OR LOCATE('|{$member_group_id}|',concat('|',{$this->sales_alias}.member_group_ids,'|'))>0) 
			AND (( {$this->sales_alias}.entry_ids IS NOT NULL 
				AND LOCATE(concat('|',{$this->channel_data_alias}.entry_id,'|'),concat('|',{$this->sales_alias}.entry_ids,'|'))>0) 
				OR ({$this->sales_alias}.category_ids IS NOT NULL
					AND LOCATE(concat('|',{$this->sales_cat_alias}.cat_id,'|'),concat('|',{$this->sales_alias}.category_ids,'|'))>0))\n";
		}
		return $joins;
		
	}
	
}
