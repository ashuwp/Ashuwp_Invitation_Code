<?php
/**
* insert a invite code.
**/
function ashuwp_insert_invitation_code( $code, $max = 1, $users = '', $expiration = '', $status = 'available' ){
  global $wpdb;
  
  if($code==''){
    return false;
  }
  $code = trim($code);
  
  if(!in_array($status,array('available','disabled','finish','expired'))){
    $status = 'available';
  }
  
  /*
  if($expiration!=''){
    //$expiration = date_format($expiration, "Y-m-d H:i:s");
    //$expiration = date( "Y-m-d H:i:s", $expiration );
  }
  */
  
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "insert ignore into $table_name ( code, max, users, expiration, status ) values( '$code', '$max', '', '$expiration', '$status' )";
  
  $result = $wpdb->query($sql);
  
  if($result){
    return true;
  }else{
    return false;
  }
}

/**
* update a invite code.
**/
function ashuwp_update_invitation_code( $id, $key, $value ){
  global $wpdb;
  
  if($id==''){
    return false;
  }
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "update $table_name set $key='$value' where id='$id'";
  $result = $wpdb->query($sql);
    
  if($result){
    return true;
  }else{
    return false;
  }
}

/**
* operation a invite code.
**/
function ashuwp_operation_invitation_code( $id, $action ){
  global $wpdb;
  
  $id = (int)$id;
  if(!$id){
    return false;
  }
  
  if(!in_array($action,array('delete','deactive','active'))){
    return false;
  }
  
  if($action =='delete'){
    $result = ashuwp_delete_invitation_code($id);
  }
  
  if($action =='deactive'){
    $result = ashuwp_update_invitation_code( $id, 'status', 'disabled' );
  }
  
  if($action =='active'){
    $result = ashuwp_update_invitation_code( $id, 'status', 'available' );
  }
  
  if($result){
    return true;
  }else{
    return false;
  }
}

/**
* delete a invite code.
**/
function ashuwp_delete_invitation_code( $id ){
  global $wpdb;
  
  if($id==''){
    return false;
  }

  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "delete from $table_name where id='$id'";
  $result = $wpdb->query($sql);
  
  if($result){
    return true;
  }else{
    return false;
  }
}

/**
* Check invitation code
**/
function ashuwp_check_invitation_code( $code ){
  global $wpdb;
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "select * from $table_name where code='$code'";
  
  $result = $wpdb->get_row($sql,'ARRAY_A');

  if(!empty($result)){
    if( in_array( $result['status'], array('available','disabled','finish','expired') ) ){
      return $result['status'];
    }else{
      return false;
    }
  }else{
    return false;
  }
}

/**
* get invitation
**/
function ashuwp_get_invitation_codes( $args ){
  global $wpdb;
  
  $defaults = array(
    'paged'    => 1,
    'per_page' => 100,
    'status'   => '',
    's'        => ''
  );
  
  $args = wp_parse_args( $args, $defaults );
  
  $page = (int)$args['paged'];
  if(!$page){
    $page = 1;
  }
  $per_page = (int)$args['per_page'];
  if(!$per_page){
    $per_page = 50;
  }

  $begin = $per_page*($page-1);
  $end = $per_page*$page;
  
  $sql_where = '';
  
  if( in_array( $args['status'], array('available','disabled','finish','expired')) ){
    $sql_where = " where status='{$args['status']}'";
  }
  
  if( $args['s'] !='' ){
    if($sql_where!=''){
      $sql_where .= " and code like '%{$args['s']}%'";
    }else{
      $sql_where .= " where code like '%{$args['s']}%'";
    }
  }
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "select * from $table_name $sql_where order by ID desc limit $begin,$end";
  
  $results = $wpdb->get_results($sql,'ARRAY_A');
  
  return $results;
}

/**
* Count invitation code
**/
function ashuwp_count_invitation_code( $args = array() ){
  global $wpdb;
  
  $defaults = array(
    'status'   => '',
    's'        => ''
  );
  
  $args = wp_parse_args( $args, $defaults );
  
  $sql_where = '';
  if( in_array( $args['status'], array('available','disabled','finish','expired')) ){
    $sql_where = " where status='{$args['status']}'";
  }
  
  if( $args['s'] !='' ){
    if($sql_where!=''){
      $sql_where .= " and code like '%{$args['s']}%'";
    }else{
      $sql_where .= " where code like '%{$args['s']}%'";
    }
  }
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  
  $sql = "select count(*) from $table_name $sql_where";
  
  $results = $wpdb->get_var($sql);
  
  return $results;
}

function ashuwp_get_invitation_code_by_code($code){
  global $wpdb;
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "select * from $table_name where code='$code'";
  
  $result = $wpdb->get_row($sql,'ARRAY_A');
  
  return $result;
}

function code_users_string_to_array( $str ){
  if(is_string($str)){
    $arr = explode( ',', $str );
    $arr = array_filter($arr);
    return $arr;
  }else{
    return $str;
  }
}
function code_users_array_to_string( $arr ){
  if(is_array($arr)){
    $arr = array_filter($arr);
    $str = implode($arr);
    return $str;
  }else{
    return $arr;
  }
}