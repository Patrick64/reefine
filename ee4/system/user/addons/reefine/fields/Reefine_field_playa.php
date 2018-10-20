<?php 

class Reefine_field_playa extends Reefine_field {

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
    if ($this->child_field_name=='')
        // Return url_title so we get a nice url for list filters
        return "{$this->table_alias_titles}.url_title";
    else if ($this->child_field_name=='title')
        // return full title, good for search filters
        return "{$this->table_alias_titles}.title";
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
    $joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}playa_relationships {$this->table_alias} " .
    "ON {$this->table_alias}.parent_entry_id = {$this->channel_data_alias}.entry_id " .
    "AND {$this->table_alias}.parent_field_id = {$this->relation_field_id} ");
    
    // check for entry status as well
    $joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_titles {$this->table_alias_titles} " .
    "ON {$this->table_alias_titles}.entry_id = {$this->table_alias}.child_entry_id " .
    "AND " . $this->reefine->get_status_where_clause($this->reefine->status,"{$this->table_alias_titles}.status");
    
    // include channel_data only if we need fields from the related entry
    if ($this->child_field_name!='' && $this->child_field_name!='title') {
        $joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}channel_data {$this->table_alias_data} " .
        "ON {$this->table_alias_data}.entry_id = {$this->table_alias_titles}.entry_id ";
    }
    return $joins;
}

function get_field() {
    return $this->get_field_by_name($this->parent_field_name);
}
}
