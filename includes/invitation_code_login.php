<?php
//normal
add_action('register_form','ashuwp_invitation_code_form');
function ashuwp_invitation_code_form(){
?>
<p>
  <label for="invitation_code"><?php _e( 'Invitation Code', 'ashuwp' ); ?><br/>
  <input type="text" name="invitation_code" id="invitation_code" class="input" size="25" value=""  />
	</label>
</p>
<?php
}

add_filter( 'registration_errors', 'ashuwp_invitation_code_errors', 10, 3 );
function ashuwp_invitation_code_errors( $errors, $sanitized_user_login, $user_email ) {

  $invitation_code = '';
  
  if ( empty( $_POST['invitation_code'] ) || ! empty( $_POST['invitation_code'] ) && trim( $_POST['invitation_code'] ) == '' ) {
    $errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Enter a invitation code.', 'ashuwp' ) );
    return $errors;
  }else{
    $invitation_code = trim( $_POST['invitation_code'] );
  }
  
  $status = ashuwp_check_invitation_code($invitation_code);
  
  if(!$status){
    $errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Wrong Invitation Code.', 'ashuwp' ) );
    return $errors;
  }
  
  if($status=='disabled'){
    $errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Invalid Invitation Code.', 'ashuwp' ) );
    return $errors;
  }
  
  if($status=='finish'){
    $errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: This Invitation Code is over.', 'ashuwp' ) );
    return $errors;
  }
  
  if($status=='expired'){
    $errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: This Invitation Code is expired.', 'ashuwp' ) );
    return $errors;
  }
  
  return $errors;
}

add_action( 'user_register', 'ashuwp_register_invitation_code' );
function ashuwp_register_invitation_code( $user_id ){
  
  if ( !empty( $_POST['invitation_code'] ) && trim( $_POST['invitation_code'] ) != '' ) {
    $code = trim( $_POST['invitation_code'] );
    $result = ashuwp_get_invitation_code_by_code($code);

    if(!empty($result)){
      $code_users = array();
      $code_id = $result['id'];
      $code_users = explode( ',', $result['users'] );
      $code_users[] = $user_id;
      $code_users = array_filter($code_users);
      $new_users = implode(',',$code_users);
      
      ashuwp_update_invitation_code( $code_id, 'users', $new_users );
      add_user_meta( $user_id, 'invitation_code', $result['code'], true );
    }
  }
  
}

//buddypress
add_action( 'bp_account_details_fields', 'ashuwp_invitation_code_bp_form' );
function ashuwp_invitation_code_bp_form(){
?>
  <label for="invitation_code"><?php _e( 'Invitation Code', 'ashuwp' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
  <?php do_action( 'bp_invitation_code_errors' ); ?>
  <input type="text" name="invitation_code" id="invitation_code" value=""  />
<?php
}

add_action( 'bp_signup_validate', 'ashuwp_invitation_code_bp_validate' );
function ashuwp_invitation_code_bp_validate( ) {
  if(!function_exists('buddypress')){
    return;
  }
  
  $bp = buddypress();
  
  $invitation_code = '';

  if ( empty( $_POST['invitation_code'] ) || ! empty( $_POST['invitation_code'] ) && trim( $_POST['invitation_code'] ) == '' ) {
    $bp->signup->errors['invitation_code'] = __( 'Enter a invitation code.', 'ashuwp' );
    return;
  }else{
    $invitation_code = trim( $_POST['invitation_code'] );
  }
  
  $status = ashuwp_check_invitation_code($invitation_code);
  
  if(!$status){
    $bp->signup->errors['invitation_code'] = __( 'Wrong Invitation Code.', 'ashuwp' );
    return;
  }
  
  if($status=='disabled'){
    $bp->signup->errors['invitation_code'] = __( 'Invalid Invitation Code.', 'ashuwp' );
    return;
  }
  
  if($status=='finish'){
    $bp->signup->errors['invitation_code'] = __( 'This Invitation Code is over.', 'ashuwp' );
    return;
  }
  
  if($status=='expired'){
    $bp->signup->errors['invitation_code'] = __( 'This Invitation Code is expired.', 'ashuwp' );
    return;
  }
  
  return;
}

//multiset
add_action( 'signup_extra_fields' , 'ashuwp_multi_signup_invitation_code', 10, 1 );
function ashuwp_multi_signup_invitation_code($errors) {
  ?>
  <label for="invitation_code"><?php _e( 'Invitation Code:', 'ashuwp' ); ?></label>
  <?php if ( $errmsg = $errors->get_error_message( 'invitation_code_error' ) ) : ?>
    <p class="error"><?php echo $errmsg; ?></p>
  <?php endif; ?>
  <input type="text" name="invitation_code" id="invitation_code" value=""  /><br />
  <?php
  _e( '(Must be input.)', 'ashuwp' );
}

add_filter( 'wpmu_validate_user_signup' , 'ashuwp_multi_invitation_code_validate', 10, 1 );
function ashuwp_multi_invitation_code_validate($result){
  
  $invitation_code = '';
  
  if ( empty( $_POST['invitation_code'] ) || ! empty( $_POST['invitation_code'] ) && trim( $_POST['invitation_code'] ) == '' ) {
    $result['errors']->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Enter a invitation code.', 'ashuwp' ) );
    return $result;
  }else{
    $invitation_code = trim( $_POST['invitation_code'] );
  }
  
  $status = ashuwp_check_invitation_code($invitation_code);

  if(!$status){
    $result['errors']->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Wrong Invitation Code.', 'ashuwp' ) );
    return $result;
  }
  
  if($status=='disabled'){
    $result['errors']->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Invalid Invitation Code.', 'ashuwp' ) );
    return $result;
  }
  
  if($status=='finish'){
    $result['errors']->add( 'invitation_code_error', __( '<strong>ERROR</strong>: This Invitation Code is over.', 'ashuwp' ) );
    return $result;
  }
  
  if($status=='expired'){
    $result['errors']->add( 'invitation_code_error', __( '<strong>ERROR</strong>: This Invitation Code is expired.', 'ashuwp' ) );
    return $result;
  }
  
  return $result;
}