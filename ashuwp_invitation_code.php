<?php
/*
Plugin Name: Ashuwp invitaion code
Plugin URI: 插件的介绍或更新地址
Description: 插件描述
Version: 1.0
Author: Ashuwp
Author URI: 插件作者的链接
License: A "Slug" license name e.g. GPL2
*/

define( 'ASHUWP_INVITE_CODE_PATH', plugin_dir_path( __FILE__ ) );

function ashuwp_invitation_code_install(){
  global $wpdb;
  
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $charset_collate = $wpdb->get_charset_collate();
  
  if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) :
    $sql = " CREATE TABLE `".$wpdb->prefix."ashuwp_invitation_code` (
      `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
      `code` varchar(40) NOT NULL,
      `max` INT NOT NULL,
      `users` varchar(20),
      `expiration` datetime,
      `status` varchar(20),
      UNIQUE (code)
      ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  endif;
  
  add_option('ashuwp_invitation_code_version','1.0');
  
  register_uninstall_hook( __FILE__, 'ashuwp_invitation_code_uninstall' );
  
}
register_activation_hook( __FILE__, 'ashuwp_invitation_code_install' );


add_action( 'plugins_loaded', 'ashuwp_invitation_code_load_textdomain' );
function ashuwp_invitation_code_load_textdomain() {
  load_plugin_textdomain( 'ashuwp', false, basename( dirname( __FILE__ ) ) . '/lang' ); 
}

function ashuwp_invitation_code_uninstall(){
  global $wpdb;
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "DROP TABLE IF EXISTS $table_name;";
  $wpdb->query($sql);
  delete_option('ashuwp_invitation_code_version');
}

require ASHUWP_INVITE_CODE_PATH .'/includes/functions.php';
require ASHUWP_INVITE_CODE_PATH .'/admin/admin.php';
require ASHUWP_INVITE_CODE_PATH .'/includes/invitation_code_login.php';
require ASHUWP_INVITE_CODE_PATH .'/tinymce/insert_invitation_code.php';