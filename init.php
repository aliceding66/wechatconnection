<?php
/*
 * Plugin Name: Wechat Connection
 * Description: 支持国内最热门的社交媒体登录。如：微信、QQ、微博、手机登录、账号绑定和解绑，全新的注册页面取代原生注册页面，支持Ultimate Member、WooCommerce、Buddypress，兼容Open Social。部分扩展收费
 * Author: Alice Ding
 * Version: 1.0.4
 * Author URI:  http://cn.aliceding.com
 */

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

if ( ! class_exists( 'XH_Social' ) ) :
final class XH_Social {
    /**
     * Social version.
     *
     * @since 1.0.0
     * @var string
     */
    public $version = '1.0.4';
    
    /**
     * License ID
     * 
     * @var string
     */
    public $license_id='wechat_social';
  
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
     * 已安装的插件（包含激活的，可能包含未激活的）
     * is_active 标记是否已被激活
     * 
     * 一般请求：只加载被激活的插件，
     * 在调用 XH_Social_WP_Api::get_plugin_list_from_system后，加载所有已安装的插件
     * @var Abstract_XH_Social_Add_Ons[]
     */
    public $plugins=array();
    
    /**
     * session
     * 缓存到自定义数据库中
     * 
     * @var XH_Social_Session_Handler
     */
    public $session;
    
    /**
     * 登录接口
     * @var XH_Social_Channel_Api
     */
    public $channel;
    
    /**
     * wordpress接口
     * @var XH_Social_WP_Api
     */
    public $WP;
    private $_scritps_tag=false;
    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     * 
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', XH_SOCIAL ), '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * 
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', XH_SOCIAL ), '1.0.0' );
    }

    /**
     * Constructor.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
    
        $this->includes();  
        $this->init_hooks();
        
        do_action( 'xh_social_loaded' );
    }

    /**
     * Hook into actions and filters.
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action( 'init', array( $this,                       'init' ), 1 );
        add_action( 'init', array( 'XH_Social_Shortcodes',      'init' ), 10 );
        add_action( 'init', array( 'XH_Social_Ajax',            'init' ), 10 );
        add_action( 'init', array( 'XH_Social_Hooks',           'init' ), 10 );
        
        //wp_enqueue_scripts,wp_loaded all required.
        add_action( 'wp_enqueue_scripts', array($this,'wp_enqueue_scripts'),10);
        add_action( 'wp_loaded', array($this,'wp_enqueue_scripts'),10);  
        
        load_plugin_textdomain( XH_SOCIAL, false,dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
   
        XH_Social_Log::instance( new XH_Social_Log_File_Handler ( XH_SOCIAL_DIR . "/loginlogs/" . date ( 'Y/m/d' ) . '.log' ));
        register_activation_hook ( XH_SOCIAL_FILE, array($this,'_register_activation_hook'),10 );
        register_deactivation_hook(XH_SOCIAL_FILE,  array($this,'_register_deactivation_hook'),10);        
        add_action ( 'plugin_action_links_'. plugin_basename( XH_SOCIAL_FILE ),array($this,'_plugin_action_links'),10,1);
       
        $this->include_plugins();
    }

    /**
     * 获取已激活的扩展
     * @param string $add_on_id
     * @return Abstract_XH_Social_Add_Ons|NULL
     * @since 1.0.0
     */
    public function get_available_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id&&$plugin->is_active){
                return $plugin;
            }
        }
        
        return null;
    }
    
    /**
     * 加载扩展
     * @since 1.0.0
     */
    private function include_plugins(){
        $installed = get_option('xh_social_plugins_installed',array());
        if(!$installed){
            return;
        }
        
        foreach ($installed as $file){
            $add_on=null;
            if(isset($this->plugins[$file])){
                $add_on=$this->plugins[$file];
            }else{
                if(file_exists($file)){
                    $add_on = require_once $file;
                    if($add_on&&$add_on instanceof Abstract_XH_Social_Add_Ons){
                        $this->plugins[$file]=$add_on;
                    }else{
        	            $add_on=null;
        	        }
                }
            }
            
            if($add_on){
                $add_on->is_active=true;
                //初始化插件
                $add_on->on_load();
        
                //监听init
                add_action('init', array($add_on,'on_init'),10);
            }
        }
    }
    
    /**
     * Get Ajax URL.
     * @return string
     * @since 1.0.0
     */
    public function ajax_url($action='') {
        //2017年4月12日 11:13:37
        //修改地址取值，在二级目录下出现错误路径的问题
//         $url = admin_url( 'admin-ajax.php', 'relative' );
       
//         if(strpos($url, 'http')!==0){
//             $url= rtrim(home_url(),'/').'/'.ltrim($url,'/');
//         }
        $url = admin_url( 'admin-ajax.php' );
        if(!empty($action)){
            $params=array();
            $url = XH_Social_Helper_Uri::get_uri_without_params($url,$params);
            $params['action']=$action;
            $url.="?".http_build_query($params);
        }
        return $url;
    }
    
    /**
     * 获取加密参数
     * @return string
     * @since 1.0.0
     */
    public function get_hash_key(){
        $hash_key = AUTH_KEY;
        if(empty($hash_key)){
            $hash_key = XH_SOCIAL_FILE;
        }
        
        return $hash_key;
    }
    
    /**
     * 插件初始化
     * 
     * 初始化需要的数据库，初始化资源等
     * @since 1.0.0
     */
    public function _register_activation_hook(){
        //第一次安装，所有插件自动安装
        $plugins_installed =get_option('xh_social_plugins_installed',null);
        if(!is_array($plugins_installed)||count($plugins_installed)==0){
            wp_cache_delete('xh_social_plugins_installed','options');
            update_option('xh_social_plugins_installed', array(
                XH_SOCIAL_DIR.'/add-ons/social-wechat/init.php',
                XH_SOCIAL_DIR.'/add-ons/social-qq/init.php',
                XH_SOCIAL_DIR.'/add-ons/social-weibo/init.php',
                XH_SOCIAL_DIR.'/add-ons/login/init.php',
            ),false);
            $this->include_plugins();
            unset($plugins_installed);
        }
        
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_install();
        }
        
        //数据表初始化
        $session_db =new XH_Social_Session_Handler_Model();
        $session_db->init();
        
        do_action('xh_social_register_activation_hook');     
    }
    
    public function _register_deactivation_hook(){
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_uninstall();
        }
        do_action('xh_social_register_deactivation_hook');
    }
    
       
    /**
     * 定义插件列表，设置菜单键
     * @param array $links
     * @return array
     * @since 1.0.0
     */
    public function _plugin_action_links($links){
        $page =XH_Social_Helper_Array::first_or_default(XH_Social_Admin::instance()->get_admin_pages(),function($m){
            return $m&&$m instanceof Abstract_XH_Social_Settings_Page&&count($m->menus())>0;
        });
        
        if(!$page){
            return $links;
        }
        
        return array_merge ( array (
            'settings' => '<a href="' . $page->get_page_url().'">'.__('Settings').'</a>'
        ), $links );
    }
    
    /**
     * Define Constants.
     * @since 1.0.0
     */
    private function define_constants() {
        self::define( 'XH_SOCIAL', 'xh_social' );
        self::define( 'XH_SOCIAL_FILE', __FILE__ );
        self::define( 'XH_SOCIAL_DIR', rtrim (str_replace('\\', '/',  plugin_dir_path ( XH_SOCIAL_FILE )), '/' ));
        self::define( 'XH_SOCIAL_URL', rtrim ( plugin_dir_url ( XH_SOCIAL_FILE ), '/' ) );
        self::define( 'XH_SOCIAL_SESSION_CACHE_GROUP', 'xh_social_session_id' );
    }

    /**
     * Define constant if not already set.
     *
     * @since 1.0.0
     * @param  string $name
     * @param  string|bool $value
     */
    public static function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * What type of request is this?
     * 
     * @since 1.0.0
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public static function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     * @since  1.0.0
     */
    private function includes() {
        require_once 'includes/class-xh-helper.php';
        require_once 'includes/error/class-xh-error.php';
        require_once 'includes/logger/class-xh-log.php';
        require_once 'includes/abstracts/abstract-xh-settings.php';
        require_once 'includes/abstracts/abstract-xh-channel.php';
        require_once 'includes/abstracts/abstract-xh-add-ons.php';
        require_once 'includes/class-xh-cache-helper.php';
        require_once 'includes/class-xh-session-handler.php';
       
        if ( self::is_request( 'admin' ) ) {
            require_once 'includes/admin/class-xh-social-admin.php';
        }

        if ( self::is_request( 'frontend' ) || self::is_request( 'cron' ) ) {
            
        }
        require_once 'includes/admin/abstracts/abstract-xh-view-form.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-menu.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-page.php';
        
        require_once 'includes/social/class-xh-social-shortcodes-functions.php';
        require_once 'includes/social/class-xh-social-shortcodes.php';
        require_once 'includes/social/class-xh-social-ajax.php';
        require_once 'includes/social/class-xh-social-hooks.php';
        require_once 'includes/social/class-xh-social-channel-api.php';
        require_once 'includes/social/class-xh-social-settings-default-other.php';
    }

    /**
     * Init social when WordPress Initialises.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Before init action.
        do_action( 'xh_social_init_before' );
        
        $this->session =XH_Social_Session_Handler::instance();
        $this->channel = XH_Social_Channel_Api::instance();
        $this->WP = XH_Social_WP_Api::instance();
        
        if(self::is_request( 'admin' )){
            //初始化 管理页面
            XH_Social_Admin::instance();
        }
        
        // Init action.
        do_action( 'xh_social_init' );
    }
    
   
    public function wp_enqueue_scripts(){
        //current plugins require jquery.js
        if($this->_scritps_tag){
            return;
        }
        $this->_scritps_tag=true;
        wp_enqueue_script('xh-social-js',XH_SOCIAL_URL.'/assets/js/social.js',array('jquery'),$this->version);
        wp_localize_script('xh-social-js','xh_social',array(
            'ajax_url'=>$this->ajax_url(),
            'msg_jquery_not_found'=>__('jQuery.js is required!',XH_SOCIAL),
            'msg_server_error'=>__('Internal Server Error!',XH_SOCIAL)
        ));
        wp_enqueue_style('xh-social-css',XH_SOCIAL_URL.'/assets/css/social.css',array(),$this->version);
    }
}

endif;

XH_Social::instance();