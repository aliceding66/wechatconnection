<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * XH_Social_Shortcodes class
 *
 * @category    Class
 */
class XH_Social_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    /**
		     * @param string redirect 声明登录成功回调链接 
		     */
			'xh_social_loginbar'=> __CLASS__ . '::loginbar',
		    'xh_social_accountbind'=> __CLASS__ . '::accountbind',
		    
		);
		
		$shortcodes =apply_filters('xh_social_shortcodes', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "xh_social_shortcode_{$shortcode}", $shortcode ), $function );
		}
		
		unset($shortcodes);
	}
	
	public static function accountbind(){
	    return XH_Social_Hooks::accountbind();
	}
	
	/**
	 * 登录条
	 * @param array $attrs 短代码属性
	 * @param string $innerhtml 内容
	 * @since 1.0.0
	 */
	public static function login_bar($attrs=array(), $innerhtml=''){
	    $redirect =self::get_attr($attrs,'redirect');
	    return XH_Social_Hooks::show_loginbar($redirect);
	}
	
	public static function get_attr($attrs,$property){
	    if($attrs){
	        foreach ($attrs as $key=>$val){
	            if(strcasecmp($key, $property)===0){
	                return $val;
	            }
	        }
	    }
	    
	    return null;
	}
}
