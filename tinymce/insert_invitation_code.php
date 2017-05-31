<?php
function ashuwp_invitation_code_button($buttons) {
	array_push($buttons, 'InsertInvitationCode');
	return $buttons;
}
add_filter('mce_buttons', 'ashuwp_invitation_code_button');

function ashuwp_invitation_code_js($plugin_array) {
	$plugin_array['InsertInvitationCode'] = plugin_dir_url( __FILE__ ) . '/js/plugin.js';
	return $plugin_array;
}
add_filter('mce_external_plugins', 'ashuwp_invitation_code_js');

class ashuwp_invitation_code_tinymce{
  static public $instance;
  
  private function __construct() {
    add_action( 'before_wp_tiny_mce', array( $this, 'tinymce_dialog' ), 1 );
  }
  
  private function __clone() {
    
  }
    
  function tinymce_dialog() {
    if ( ! is_admin() ) {
      return;
    }
    ?>
    <div id="invitation_modal">
      <div class="modal-backdrop"></div>
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"><?php _e( 'Insert A Invitation Code', 'ashuwp' ); ?></h4>
          </div>
          <div class="modal-body">
          <form action="" id="invitation_code_form">
            <?php wp_nonce_field( 'insert_invitation_code', '_ajax_insert_invitation_code', false ); ?>
            <input type="hidden" name="action" value="get_invitation_codes" />
            <div class="invitationcode-search-wrapper">
              <label>
                <span class="search-label"><?php _e( 'Search', 'ashuwp' ); ?></span>
                <input type="search" name="s" id="invitation_code_search" class="invitation_code_search" value=""/>
                <a href="#" class="button-primary" id="invitation_code_search_btn"><?php _e( 'Search', 'ashuwp' ); ?></a>
              </label>
            </div>
            <div id="invitationcode-selector">
              <div class="list_head clearfix">
                <span class="code_name"><?php _e( 'Invitation Codes', 'ashuwp' ); ?></span>
                <span class="code_counter"><?php _e( 'Counter(Max/Used)', 'ashuwp' ); ?></span>
                <span class="code_expiration"><?php _e( 'Expiration Time', 'ashuwp' ); ?></span>
              </div>
              <div id="sinvitation_code_results" class="query-results" tabindex="0">
                <ul class="clearfix">
                  <?php
                  $args = array(
                    'per_page' => 20,
                    'status'=>'available',
                  );
                  $invitation_codes = ashuwp_get_invitation_codes( $args );
                  $total_items  = ashuwp_count_invitation_code($args);
                  $class = 'class="alternate"';
                  foreach ( $invitation_codes as $code ) {
                    if($class!=''){
                      $class = '';
                    }else{
                      $class = 'class="alternate"';
                    }
                    $users = array();
                    if(!empty($code['users'])){
                      $users = code_users_string_to_array($code['users']);
                    }
                    $used = count($users);
                    
                    $expiration = '';
                    if( !empty( $code['expiration'] ) && $code['expiration']!='0000-00-00 00:00:00' ){
                      $expiration = $code['expiration'];
                    }
                  ?>
                  <li <?php echo $class; ?>>
                    <input type="hidden" class="code_value" value='<?php echo $code['code']; ?>'>
                    <span class="code_name"><?php echo $code['code']; ?></span>
                    <span class="code_counter"><?php echo $code['max'].'/'.$used; ?></span>
                    <span class="code_expiration"><?php echo $expiration; ?></span>
                  </li>
                  <?php
                  }
                  ?>
                </ul>
              </div>
              <div id="page_nav_wrap"><div class="page_nav">
                <?php
                $pagination = paginate_links( array(
                  'base' => '%_%',
                  'format' => '?paged=%#%',
                  'total' => ceil($total_items/20),
                  'current' => 1
                ) );
                if ( $pagination ) {
                  echo $pagination;
                }
                ?>
              </div></div>
            </div>
          </form>
          </div>
          <div class="modal-footer">
            <button type="button" id="invitatino_code_cancel" class="button"><?php _e( 'Cancel', 'ashuwp' ); ?></button>
            <button type="button" id="invitatino_code_insert" class="button-primary"><?php _e( 'Insert', 'ashuwp' ); ?></button>
          </div>
        </div>
      </div>
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
	ashuwp_invitation_code_tinymce::get_instance();
} );

add_action("wp_ajax_get_invitation_codes", "ajax_get_invitation_codes");   

function ajax_get_invitation_codes() {
  $nonce = '';
    if(isset($_POST['_ajax_insert_invitation_code']))
      $nonce = $_POST['_ajax_insert_invitation_code'];
    // For security : verifying wordpress nonce
    if (!wp_verify_nonce($nonce, 'insert_invitation_code')) {
      die('error01');
    }
    
    $key = '';
    if ( isset( $_POST['s'] ) && !empty(trim($_POST['s'])) ){
      $key = trim($_POST['s']);
    }
  ?>
  
            <?php wp_nonce_field( 'insert_invitation_code', '_ajax_insert_invitation_code', false ); ?>
            <input type="hidden" name="action" value="get_invitation_codes" />
            <div class="invitationcode-search-wrapper">
              <label>
                <span class="search-label"><?php _e( 'Search', 'ashuwp' ); ?></span>
                <input type="search" name="s" id="invitation_code_search" class="invitation_code_search" value="<?php echo $key; ?>"/>
                <a href="#" class="button-primary" id="invitation_code_search_btn"><?php _e( 'Search', 'ashuwp' ); ?></a>
              </label>
            </div>
            <div id="invitationcode-selector">
              <div class="list_head clearfix">
                <span class="code_name"><?php _e( 'Invitation Codes', 'ashuwp' ); ?></span>
                <span class="code_counter"><?php _e( 'Counter(Max/Used)', 'ashuwp' ); ?></span>
                <span class="code_expiration"><?php _e( 'Expiration Time', 'ashuwp' ); ?></span>
              </div>
              <div id="sinvitation_code_results" class="query-results" tabindex="0">
                <ul class="clearfix">
                  <?php
                  $args = array(
                    'per_page' => 20,
                    'status'=>'available',
                  );
                  if ( isset( $_POST['s'] ) && !empty(trim($_POST['s'])) )
                    $args['s'] = trim($_POST['s']);
                  
                  $args['paged'] = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
                  
                  $invitation_codes = ashuwp_get_invitation_codes( $args );
                  $total_items  = ashuwp_count_invitation_code($args);
                  $class = 'class="alternate"';
                  foreach ( $invitation_codes as $code ) {
                    if($class!=''){
                      $class = '';
                    }else{
                      $class = 'class="alternate"';
                    }
                    $users = array();
                    if(!empty($code['users'])){
                      $users = code_users_string_to_array($code['users']);
                    }
                    $used = count($users);
                    
                    $expiration = '';
                    if( !empty( $code['expiration'] ) && $code['expiration']!='0000-00-00 00:00:00' ){
                      $expiration = $code['expiration'];
                    }
                  ?>
                  <li <?php echo $class; ?>>
                    <input type="hidden" class="code_value" value='<?php echo $code['code']; ?>'>
                    <span class="code_name"><?php echo $code['code']; ?></span>
                    <span class="code_counter"><?php echo $code['max'].'/'.$used; ?></span>
                    <span class="code_expiration"><?php echo $expiration; ?></span>
                  </li>
                  <?php
                  }
                  ?>
                </ul>
              </div>
              <div id="page_nav_wrap"><div class="page_nav">
                <?php
                $pagination = paginate_links( array(
                  'base' => '%_%',
                  'format' => '?paged=%#%',
                  'total' => ceil($total_items/20),
                  'current' => $args['paged']
                ) );
                if ( $pagination ) {
                  echo $pagination;
                }
                ?>
              </div></div>
            </div>

  <?php
  wp_die();
}