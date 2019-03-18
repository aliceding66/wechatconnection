<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Admin {
    /**
     * Wp menu key
     *  
     * @var string
     * @since  1.0.0
     */
    const menu_tag='xh_social_social';
    
    /**
     * 实例
     * 
     * @var XH_Social_Admin
     */
    private static $_instance;
    
    /**
     * XH_Social_Admin Instance
     * 
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * hook admin menu actions
     * @since  1.0.0
     */
    private function __construct(){      
        $this->includes();
        $this->hooks();
    }
 
    /**
     * include menu files
     * @since  1.0.0
     */
    public function includes(){
        require_once 'menus/class-xh-social-page-default.php';
        require_once 'menus/class-xh-social-page-add-ons.php';
        require_once 'menus/class-xh-social-menu-default-other.php';
        require_once 'menus/class-xh-social-menu-default-channel.php';
        require_once 'menus/class-xh-social-menu-default-ext.php';
        require_once 'menus/class-xh-social-menu-default-account.php';
        require_once 'menus/class-xh-social-menu-add-ons-install.php';
        require_once 'menus/class-xh-social-menu-add-ons-recommend.php';
    }
    
    /**
     * hooks
     * @since  1.0.0
     */
    public function hooks(){
        add_action( 'admin_menu', array( $this, 'admin_menu'),10);
        add_action( 'admin_head', array( $this, 'admin_head'),10 ); 
        add_action( 'admin_print_scripts', array( $this, 'admin_scripts'),10 );
        add_action( 'admin_print_styles', array( $this, 'admin_styles'),10 );
    }
    
    public function admin_scripts() { //加载需要使用的js文件。
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
    }
    
    public function admin_styles() { //加载样式文件。
        wp_enqueue_style('thickbox');
    }
    
    /**
     * Reset default wp menu display
     * 
     * @since  1.0.0
     */
    public function admin_head(){
        global $submenu;
    
        if(isset( $submenu[self::menu_tag] ) 
           &&isset($submenu[self::menu_tag][0])
           &&isset($submenu[self::menu_tag][0][2])
           &&$submenu[self::menu_tag][0][2]==self::menu_tag){
            
            unset( $submenu[self::menu_tag][0] );
        }
    }
    
    /**
     * 获取注册的菜单
     * @return array
     * @since 1.0.0
     */
    public function get_admin_pages(){
        return apply_filters('xh_social_admin_pages', array(
            XH_Social_Page_Default::instance(),
            XH_Social_Page_Add_Ons::instance()
        ));
    }
    /**
     * Wp menus
     * @since  1.0.0
     */
    public function admin_menu(){
        $capability = apply_filters('xh_social_admin_menu_capability', 'administrator');
        $menu_title = apply_filters('xh_social_admin_menu_title', 'Wechat Social');
        
        global $current_user;
        if(!is_user_logged_in()){
            return;
        }
      
        if(!$current_user->roles||!is_array($current_user->roles)){
            $current_user->roles=array();
        }
        
        if(!in_array($capability, $current_user->roles)){
            return;
        }
        
        add_menu_page( $menu_title, $menu_title, $capability, self::menu_tag, null, null, '55.5' );      
        $pages = $this->get_admin_pages();
        
        foreach ($pages as $page){
            if(!$page||!$page instanceof Abstract_XH_Social_Settings_Page){
                continue;
            }
            
            add_submenu_page(
                self::menu_tag,
                $page->title,
                $page->title,
                $capability,
                $page->get_page_id(),
                array($page,'render'));
        }
    }
}