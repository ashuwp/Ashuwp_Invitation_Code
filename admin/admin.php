<?php

if(!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Ashuwp_Invitation_Code_List_Table extends WP_List_Table {
  
  function __construct(){
    
    parent::__construct( array(
      'singular'  => __( 'Invitation Code', 'ashuwp' ),
      'plural'    => __( 'Invitation Codes', 'ashuwp' ),
      'ajax'      => false
    ) );
    
  }
  
  function column_default( $item, $column_name ) {
    
    switch ( $column_name ){
      case 'code':
      case 'counter':
      case 'users':
      case 'expiration':
      case 'status':
      case 'actions':
        return $item[ $column_name ];
      default:
        return print_r($item,true);
    }
    
  }
  
  function column_cb( $item ) {
    return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['id'] );
  }
  
  function get_columns() {
    $columns = array(
      'cb'         => '<input type="checkbox" />',
      'code'       => __( 'Invitation Codes', 'ashuwp' ),
      'counter'    => __( 'Counter(Max/Used)', 'ashuwp' ),
      'users'      => __( 'User(s)', 'ashuwp' ),
      'expiration' => __( 'Expiration Time', 'ashuwp' ),
      'status'     => __( 'Status', 'ashuwp' ),
      'actions'    => __( 'Actions', 'ashuwp' ),
    );
    
    return $columns;
  }
  
  function get_bulk_actions() {
    $actions = array(
      'active'   => 'Active',
      'deactive' => 'Disable',
      'delete'   => 'Delete'
    );
    return $actions;
  }
  
  function format_datas( $codes ) {
    $datas = array();
    foreach( $codes as $code ){
      $item_array = array();
      $users = array();
      if(!empty($code['users'])){
        $users = code_users_string_to_array($code['users']);
      }
      $used = count($users);
      
      if( ($code['max']<=$used) && ($code['status']=='available') ){
        $code['status'] = 'finish';
        ashuwp_update_invitation_code( $code['id'], 'status', 'finish' );
      }
      
      $user_output = array();
      foreach( $users as $user_id ){
        $user = get_user_by('id', $user_id);
        if(!empty($user)){
          $user_output[] = '<a href="'.network_admin_url( 'user-edit.php?user_id='.$user->ID ).'">'.$user->user_login .'</a>';
        }
      }
      
      $expiration = '';
      if( !empty( $code['expiration'] ) && $code['expiration']!='0000-00-00 00:00:00' ){
        $expiration = date_i18n( get_option( 'date_format' ).' '.get_option( 'time_format' ), strtotime($code['expiration']) );
        
        $now = time() + ( get_option( 'gmt_offset' ) * 3600 );

        if( ($now >= strtotime($code['expiration'])) && ($code['status'] == 'available') ){
          $code['status'] = 'expired';
          ashuwp_update_invitation_code( $code['id'], 'status', 'expired' );
        }
      }
      
      $status = '';
      switch($code['status']){
        case 'available':
          $status = sprintf( '<span class="available">%s</span>', __('Available', 'ashuwp') );
          break;
        case 'disabled':
          $status = sprintf( '<span class="disabled">%s</span>', __('Disabled', 'ashuwp') );
          break;
        case 'finish':
          $status = sprintf( '<span class="finish">%s</span>', __('Use Up', 'ashuwp') );
          break;
          case 'expired':
          $status = sprintf( '<span class="expired">%s</span>', __('Expired', 'ashuwp') );
          break;
        default:
          $status = '';
      }
      
      
      $actions = '<a href="'. wp_nonce_url( network_admin_url( 'admin.php?page=invitation_code&action=delete&invitationcode[0]='.$code['id'] ), 'invitationcode_operate' ).'">'.__('Delete', 'ashuwp').'</a>';
      if( $code['status'] == 'disabled' ){
        $actions .= ' | <a href="'.wp_nonce_url( network_admin_url( 'admin.php?page=invitation_code&action=active&invitationcode[0]='.$code['id'] ), 'invitationcode_operate' ).'">'.__('Active', 'ashuwp').'</a>';
      }
      if( $code['status'] == 'available'){
        $actions .= ' | <a href="'.wp_nonce_url( network_admin_url( 'admin.php?page=invitation_code&action=deactive&invitationcode[0]='.$code['id'] ), 'invitationcode_operate' ).'">'.__('Disable', 'ashuwp').'</a>';
      }
              
      $item_array['id'] = $code['id'];
      $item_array['code'] = $code['code'];
      $item_array['counter'] = $code['max'].'/'.$used;
      $item_array['users'] = implode( ',', $user_output );
      $item_array['expiration'] = $expiration;
      $item_array['status'] = $status;
      $item_array['actions'] = $actions;
      $datas[] = $item_array;
    }
    
    return $datas;
  }
  
  function prepare_items() {
    
    $this->_column_headers = $this->get_column_info();
    
    $this->process_bulk_action();
    
    $per_page     = $this->get_items_per_page( 'customers_per_page', 30 );
    $current_page = $this->get_pagenum();
    $total_items  = 0;
    
    $args = array(
      'per_page' => $per_page,
      'paged' => $current_page,
    );
    
    if( isset( $_GET['s'] ) && !empty( trim($_GET['s']) ) ){
      $args['s'] = strtoupper( trim($_GET['s']) );
    }
    
    if( !empty( $_GET['status'] ) && in_array( trim($_GET['status']), array('available','disabled','finish','expired') ) ){
      $args['status'] = trim($_GET['status']);
    }
    
    $total_items  = ashuwp_count_invitation_code($args);
    $datas = ashuwp_get_invitation_codes($args);
    
    $this->items = $this->format_datas($datas);
    
    $this->set_pagination_args( array(
      'total_items' => $total_items,
      'per_page'    => $per_page,
      'total_pages' => ceil($total_items/$per_page)
    ) );
    
  }
}


class ashuwp_invitation_code_admin {
  static public $instance;
  public $invitation_code_obj;
  
  private function __construct(){
    
    add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
    
    add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'ashuwp_invitation_code_menu') );
    
    //add_action( 'admin_menu', array( $this, 'ashuwp_invitation_code_menu') );
    add_action( 'admin_enqueue_scripts', array( $this, 'invitation_code_css') );
    
  }
  
  private function __clone() {
    
  }
  
  function ashuwp_invitation_code_menu() {
    
    $hook = add_menu_page( __('Invitation Codes', 'ashuwp'), __('Invitation Codes', 'ashuwp'), 'manage_options', 'invitation_code', array(&$this, 'invitation_code_list'),'dashicons-admin-network',27);
    
    add_submenu_page('invitation_code', __('Add New', 'ashuwp'), __('Add New', 'ashuwp'), 'manage_options', 'invitation_code_add', array(&$this, 'invitation_code_add'));
    
    add_action( "load-$hook", array( $this, 'invitation_code_update' ) );
    add_action( "load-$hook", array( $this, 'screen_option' ) );
  }
  
  function invitation_code_css() {
    wp_enqueue_style( 'invitation-style', plugin_dir_url( __FILE__ ) . '/css/styles.css' );
  }
  
  function set_screen( $status, $option, $value ) {
    return $value; 
  }
  
  function screen_option() {
    $option = 'per_page';
    $args   = array(
      'label'   => 'Customers',
      'default' => 30,
      'option'  => 'customers_per_page'
    );
    
    add_screen_option( $option, $args );
    
    $this->invitation_code_obj = new Ashuwp_Invitation_Code_List_Table();
  }
  
  function invitation_code_update() {
    if ( ( isset( $_GET['action'] ) && in_array($_GET['action'],array('active', 'deactive', 'delete') ) ) || ( isset( $_GET['action2'] ) && in_array($_GET['action2'],array('active', 'deactive', 'delete') ) ) ) {
      
      if( isset( $_GET['action'] ) && in_array($_GET['action'],array('active', 'deactive', 'delete') ) ){
        $action = $_GET['action'];
      }
      
      if( isset( $_GET['action2'] ) && in_array($_GET['action2'],array('active', 'deactive', 'delete') ) ){
        $action = $_GET['action2'];
      }
      
      $success = array();
      $failed = array();
      $code_ids = esc_sql( $_GET['invitationcode'] );
      foreach ( $code_ids as $id ) {
        $re = ashuwp_operation_invitation_code( $id, $action );
        if($re){
          $success[] = $id;
        }else{
          $failed[] = $id;
        }
      }
      
      $query = array( 'page'=>'invitation_code' );
      $query['paged'] = get_query_var( 'paged', 1 );
      
      if( !empty($success) ){
        $query['status'] = 'success';
        $query['success'] = implode( ',', $success );
      }
      
      if( !empty($failed) ){
        $query['status'] = 'failed';
        $query['failed'] = implode( ',', $failed );
      }
      
      $redirect_to = add_query_arg( $query, network_admin_url('admin.php') );
      wp_safe_redirect( $redirect_to );
      exit();
    }
  }
  
  function invitation_code_list(){
    
    $all = ashuwp_count_invitation_code();
    $available = ashuwp_count_invitation_code( array( 'status'=>'available' ) );
    $disabled = ashuwp_count_invitation_code( array( 'status'=>'disabled' ) );
    $finish = ashuwp_count_invitation_code( array( 'status'=>'finish' ) );
    $expired = ashuwp_count_invitation_code( array( 'status'=>'expired' ) );
    
  ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php _e('Invitation Codes', 'ashuwp'); ?></h1>
      <a href="<?php echo network_admin_url( 'admin.php?page=invitation_code_add' ); ?>" class="page-title-action"><?php _e('Add New', 'ashuwp'); ?></a>
      <?php
      if ( ! empty( $_GET['s'] ) ) {
        printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( $_GET['s'] ) );
      }
      ?>
      <hr class="wp-header-end">
      <?php
      if( isset($_GET['status']) && trim($_GET['status'])!='' ){
        if( trim($_GET['status'])=='success' ){
      ?>
      <div id="message" class="notice notice-success"><?php _e( 'Success', 'ashuwp' ); ?></div>
      <?php
        }elseif(trim($_GET['status'])=='failed'){
        ?>
        <div id="message" class="notice notice-error"><?php _e( 'Failed', 'ashuwp' ); ?></div>
        <?php
        }
      }
      ?>
      
      <ul class="subsubsub">
        <?php
        if( !empty( $_GET['status'] ) && in_array( trim($_GET['status']), array('available','disabled','finish','expired') ) ){
          $now = trim($_GET['status']);
        }else{
          $now = 'all';
        }
        $current = 'class="current"';
        ?>
        <li class="all"><a <?php if($now=='all'){ echo $current; } ?> href="<?php echo network_admin_url( 'admin.php?page=invitation_code' ); ?>"><?php _e('All', 'ashuwp'); ?><span class="count">(<?php echo $all; ?>)</span></a> |</li>
        <li class="available"><a <?php if($now=='available'){ echo $current; } ?> href="<?php echo network_admin_url( 'admin.php?page=invitation_code&status=available' ); ?>"><?php _e('Available', 'ashuwp'); ?><span class="count">(<?php echo $available; ?>)</span></a> |</li>
        <li class="disabled"><a <?php if($now=='disabled'){ echo $current; } ?> href="<?php echo network_admin_url( 'admin.php?page=invitation_code&status=disabled' ); ?>"><?php _e('Disabled', 'ashuwp'); ?><span class="count">(<?php echo $disabled; ?>)</span></a> |</li>
        <li class="finish"><a <?php if($now=='finish'){ echo $current; } ?> href="<?php echo network_admin_url( 'admin.php?page=invitation_code&status=finish' ); ?>"><?php _e('Use Up', 'ashuwp'); ?><span class="count">(<?php echo $finish; ?>)</span></a> |</li>
        <li class="expired"><a <?php if($now=='expired'){ echo $current; } ?> href="<?php echo network_admin_url( 'admin.php?page=invitation_code&status=expired' ); ?>"><?php _e('Expired', 'ashuwp'); ?><span class="count">(<?php echo $expired; ?>)</span></a></li>
      </ul>
      <form id="invitation-code-filter" method="get">
        <p class="search-box">
          <label class="screen-reader-text" for="code-search-input"><?php _e( 'Search invitation code:', 'ashuwp' ); ?></label>
          <input type="search" id="code-search-input" name="s" value="" />
          <?php submit_button( __( 'Search', 'ashuwp' ), 'button', false, false, array('id' => 'search-submit') ); ?>
        </p>
        <input type="hidden" name="page" value="invitation_code" />
        <?php
        $this->invitation_code_obj->prepare_items();
        $this->invitation_code_obj->display();
        ?>
      </form>
      
    </div>
  <?php
  }
  
  
  function invitation_code_generate(){
    $code_tem = array();
    
    if(isset($_REQUEST['submit']) && isset($_REQUEST['ashuwp_invitation_code_field']) && check_admin_referer('ashuwp_invitation_code_action', 'ashuwp_invitation_code_field') ) {
      $code_prefix = '';
      if(!empty($_POST['code_prefix'])){
        $code_prefix = trim($_POST['code_prefix']);
      }
      
      $code_length = '';
      if(!empty($_POST['code_length'])){
        $code_length = (int)$_POST['code_length'];
      }
      if(!$code_length){
        $code_length = 8;
      }
      
      $code_number = 1;
      if(!empty($_POST['code_number'])){
        $code_number = (int)$_POST['code_number'];
      }
      if(!$code_number){
        $code_number = 1;
      }
      
      $code_counter = '';
      if(!empty($_POST['code_counter'])){
        $code_counter = (int)$_POST['code_counter'];
      }
      if(!$code_counter){
        $code_counter = 1;
      }
      
      $code_expiration = '';
      if(!empty($_POST['code_expiration'])){
        $code_expiration = strtotime(trim($_POST['code_expiration']));
        $code_expiration = date( "Y-m-d H:i:s", $code_expiration );
      }
      
      $i=1;
      while ( $i <= $code_number ){
        $tem = strtoupper( $code_prefix . wp_generate_password( $code_length, false ) );
        $re = ashuwp_insert_invitation_code( $tem, $code_counter, '', $code_expiration, 'available');
        if($re){
          $i++;
          $code_tem[] = $tem;
        }
      }
    }
    
    return $code_tem;
  }
  function invitation_code_add(){
    $code_added = $this->invitation_code_generate();
  ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php _e('Add New', 'ashuwp'); ?></h1>
      <hr class="wp-header-end">
      <?php
      if(!empty($code_added)){
      ?>
      <div id="message" class="notice notice-success">
        <p><?php _e('Success. The code below has added.', 'ashuwp'); ?></p>
        <?php
        echo '<p>';
        $i=0;
        foreach($code_added as $t){
          $i++;
          echo $t.'<br />';
          if($i==50){
            echo '......';
            break;
          }
        }
        echo '</p>';
        ?>
      </div>
      <?php
      }
      ?>
      <form action="" method="post">
        <table class="form-table">
          <tbody>
            <tr>
              <th><label for="code_prefix"><?php _e('Prefix(optional)', 'ashuwp'); ?></label></th>
              <td>
                <input type="text" id="code_prefix" name="code_prefix" class="regular-text"  value=""/>
              </td>
            </tr>
            <tr>
              <th><label for="code_length"><?php _e('Length', 'ashuwp'); ?></label></th>
              <td>
                <input type="text" id="code_length" name="code_length" class="regular-text"  value=""/>
                <p class="description"><?php _e('Excluding prefix, Default:8', 'ashuwp'); ?></p>
              </td>
            </tr>
            <tr>
              <th><label for="code_number"><?php _e('Number', 'ashuwp'); ?></label></th>
              <td>
                <input type="text" id="code_number" name="code_number" class="regular-text" value=""/>
                <p class="description"><?php _e('Code number of generate, Default:1', 'ashuwp'); ?></p>
              </td>
            </tr>
            <tr>
              <th><label for="code_counter"><?php _e('Use limit', 'ashuwp'); ?></label></th>
              <td>
                <input type="text" id="code_counter" name="code_counter" class="regular-text"  value=""/>
                <p class="description"><?php _e('How many time this code can be used.', 'ashuwp'); ?></p>
              </td>
            </tr>
            <tr>
              <th><label for="code_expiration"><?php _e('Expiration Date', 'ashuwp'); ?></label></th>
              <td>
                <input type="text" id="code_expiration" name="code_expiration" class="regular-text" value=""/>
                <p class="description"><?php _e('Optional, Format: YYYY-MM-DD H:i', 'ashuwp'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit">
          <?php wp_nonce_field( 'ashuwp_invitation_code_action','ashuwp_invitation_code_field' ); ?>
          <input type="submit" name="submit" id="submit" class="button button-primary" value="Generate Now">
        </p>
      </form>
    </div>
  <?php
  }
  public static function get_instance() {
    if ( ! isset( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }
}

add_action( 'plugins_loaded', function () {
	ashuwp_invitation_code_admin::get_instance();
} );