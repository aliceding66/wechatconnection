<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
	
/**
 * Menu
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_XH_Social_Settings_Page extends Abstract_XH_Social_Settings{  
    public function get_page_id(){
        return 'social_'.$this->id;
    }
    
    public function get_page_url(){
        return admin_url("admin.php?page={$this->get_page_id()}");
    }
    
    /**
     * 菜单
     * @return array
     * @since 1.0.0
     */
    public function menus(){
        return apply_filters("xh_social_admin_page_{$this->id}", array());
    }
    
    /**
     * 输出页面
     * 
     * @since 1.0.0
     */
    public function render(){
        $menus =  $this->menus();
        $current= isset($_GET['section'])?XH_Social_Helper_String::sanitize_key_ignorecase($_GET['section']):'';
        
        $current_menu =null;
        $index=0;
        foreach ($menus as $menu){
            if(!$menu||!$menu instanceof Abstract_XH_Social_Settings_Menu){
                continue;
            }
           
            //default first item
            if($index++===0){
                $current_menu=$menu;
            }
            
            //specified item
            $select =strcasecmp($current,$menu->id)===0;
            if($select){
                $current_menu=$menu;
                break;
            }
        }
       
        if(!$current_menu){
            return;
        }
        
        $current_menu->render($this);
    }
}