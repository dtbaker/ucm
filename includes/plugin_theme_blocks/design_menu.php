<?php
if(!function_exists('sort_menu_links')){
	function sort_menu_links($a,$b){
		if(isset($a['order']) && isset($b['order'])){
			return $a['order'] > $b['order'] ? 1 : -1;
		}
		return 1;
	}
}
if(!isset($links)){
    $links = array();
}
if(!isset($menu_holder)){
    $menu_holder = (isset($load_page)) ? $load_page : 'main';
}

    switch($menu_holder){
        case false:
        case 'main':
                // login is always there.
                if(!_UCM_INSTALLED){
                    //$links [] = array("name"=>"Setup","url"=>"index.php?p=setup","icon"=>"images/icon_home.gif");
                }else if(!module_security::is_logged_in()){
                    $links [] = array("name"=>"Login","url"=>_BASE_HREF."index.php?p=home","icon"=>"images/icon_home.gif");
                }else{
                    if($display_mode != 'mobile' && !defined('_CUSTOM_UCM_HOMEPAGE')){
                        $home_link = array(
                            "name"=>"Dashboard",
                            "url"=>_BASE_HREF.'index.php?p=home',
                            "icon_name"=>'home',
                            'order' => 0,
                        );
                        if(!isset($_REQUEST['m']) || !$_REQUEST['m'] || $_REQUEST['m'][0] == 'index'){
                            $home_link['current'] = true;
                        }
                        $links[] = $home_link;
                    }
                }
            break;
        default:

            break;

    }

    if(!isset($menu_include_parent)){
        $menu_include_parent = false;
    }
    if(!isset($menu_allow_nesting)){
        $menu_allow_nesting = false;
    }
    $menu_type = false;
    // pull in menus from modules
    $current_module_name = (isset($module)) ? $module->module_name : false;
    foreach($plugins as $plugin_name => &$plugin){
        $links = array_merge($links,$plugin->get_menu($current_module_name,$menu_holder));
    }
    uasort($links,'sort_menu_links');

   // echo '<pre>';print_r($load_modules);echo '</pre>';
    if(!isset($current_link)){$current_link = false;}


    if($current_link === false){
        foreach($links as $link_id => $link){
            if(isset($link['current'])){
                if($link['current']){
                    $current_link = $link_id;
                    break;
                }else{
                    continue;
                }
            }
        }
    }
    // we get out load modules.
    if($current_link === false && isset($menu_modules)){

        //if($menu_module_index == count($menu_modules)){
        foreach($menu_modules as $menu_module_id => $menu_module){
            foreach($links as $link_id => $link){
                // we highlight the current module
                if(isset($link['p']) && isset($link['m'])){
                    if($menu_module == $link['m'] && $link['p'] == $load_pages[$menu_module_id]){
                        $current_link = $link_id;
                        //$menu_module_index--;
                        unset($menu_modules[$menu_module_id]);
                        break 2;
                    }
                }
            }
        }
        //}
            //break;// if there are menu "current" issues then the problem will be here. remove break;


    }

    // highlight home menu or best guess module menu item.

    if($current_link === false){
        foreach($links as $link_id => $link){
            if(isset($link['current'])){
                if($link['current']){
                    $current_link = $link_id;
                }else{
                    continue;
                }
            }
            // we highlight the current module
            if(isset($load_modules) && isset($link['m']) && in_array($link['m'],$load_modules) && !isset($link['force_current_check'])){
                $current_link = $link_id;
                break;// if there are menu "current" issues then the problem will be here. remove break;
            }else if($link['name'] == 'Dashboard' && !$_REQUEST['m'] && isset($_REQUEST['p']) && in_array('home',$_REQUEST['p'])){
                $current_link = $link_id;
            }
        }
    }

global $done_menu_ids;
$done_menu_ids = array();
if(!function_exists('print_nested_menu')){
function print_nested_menu($links, $parent_link_id, $current_link, $menu_allow_nesting, $menu_include_parent, $menu_holder){

	global $done_menu_ids;
	$menu_return = array(
		'nav_items' => '',
		'has_active' => false,
		'current_selected_link' => false,
	);
	foreach($links as $link_id => $link){
		if($parent_link_id && (empty($link['parent']) || $link['parent'] != $parent_link_id)){continue;}
		if(!$parent_link_id && !empty($link['parent'])){continue;}
	    if(isset($done_menu_ids[$link_id])){continue;}
	    $done_menu_ids[$link_id] = true;
        $current = ($link_id === $current_link);
        if(isset($link['url']) && $link['url']){
            $link_url = $link['url'];
        }else if(isset($link['p']) && $link['m']){
            if(isset($link['menu_include_parent'])){
                $menu_include_parent = $link['menu_include_parent'];
            }
            $link_nest = $menu_allow_nesting;
            if(isset($link['allow_nesting'])){
                $link_nest = $link['allow_nesting'];
            }
            $link_url = generate_link($link['p'],(isset($link['args'])?$link['args']:array()),$link['m'],$menu_include_parent,$link_nest);
        }else{
            $link_url = '#';
        }
        if($current){
            $menu_return['has_active'] = $link;
            $menu_return['current_selected_link'] = $link;
        }
        // reformat out menu labels
        // add class panel if having a dropdown
        if(!empty($link['name_id'])){
            $possible_child_items = print_nested_menu($links, $link['name_id'], $current_link, $menu_allow_nesting, $menu_include_parent, $menu_holder);
            if($possible_child_items['current_selected_link']){
                $menu_return['current_selected_link'] = $possible_child_items['current_selected_link'];
            }
        }else{
            $possible_child_items = array();
        }
        $badge_color = module_theme::get_config('blocks_badgecolor','bg-light-blue');
        $link['name'] = str_replace('menu_label','badge '.$badge_color.($menu_holder=='main' ? ' pull-right':''),$link['name']);
        $link['name'] = str_replace($badge_color.' pull-right important','pull-right bg-red'.($menu_holder=='main' ? ' pull-right':''),$link['name']);

        ob_start();
        ?>
        <li class="<?php if($current || !empty($possible_child_items['has_active'])){ echo ' active'; } if(!empty($possible_child_items['nav_items'])){ echo ' dropdown'; } ?>">
            <a href="<?php echo $link_url; ?>">
            <?php if(!$parent_link_id){ ?>
                <span><?php if(($menu_holder == 'main' || $menu_holder == 'customer_admin_open') && isset($link['icon_name'])){ ?><i class="fa fa-<?php echo $link['icon_name'];?>"></i><?php } ?></span>
                <?php } ?>
                <p><?php echo _l($link['name']); ?></p>
            </a>
            <?php if(!empty($possible_child_items['nav_items'])){
            echo '<ul>';
             echo $possible_child_items['nav_items'];
             echo '</ul>';
            }?>
        </li>
        <?php
        $nav_item = ob_get_clean();
        $menu_return['nav_items'] .= $nav_item;
        //if(isset($header_nav_mini))$header_nav_mini .= $nav_item; // for the responsive menu
    }
    return $menu_return;

}
}

$current_selected_link = false;

    switch($menu_holder){
        case false:
        case 'main':
            //if(isset($header_nav_mini))$header_nav_mini .= '<ul class="nav navbar-nav">';
            ?>
			<nav class="left eqh" id="main-menu">
	            <a class="menu-btn open">
		            <div class="ham">
			            <span></span>
			            <span></span>
			            <span></span>
		            </div>
	            </a>
	            <a class="mobile-menu">MENU</a>
	            <ul class="menu">

            <?php
            break;
        default:

            // find the current link to display in collapsed branding
            $highlighted_link = false;
            $current = false;
            foreach($links as $link_id => $link){
                $current = ($link_id === $current_link);
                if($current){
                    $highlighted_link = $link;
                }
            }
            $menu_id = 'menu-'.md5($menu_holder.$current);
            ?>
            <ul id="<?php echo $menu_id;?>" class="topbar-menu">

	            <?php /*if($highlighted_link){ ?>
	                <a class="navbar-brand visible-xs" href="#">
	                <?php if(isset($highlighted_link['icon_name'])){ ?><i class="fa fa-<?php echo $highlighted_link['icon_name'];?>"></i><?php } ?>
	                <?php echo _l($highlighted_link['name']); ?>
	                </a>
	            <?php }*/ ?>


             <?php
            break;

    }
    $menu_result = print_nested_menu($links, false, $current_link, $menu_allow_nesting, $menu_include_parent, $menu_holder);
    echo $menu_result['nav_items'];
    $current_selected_link = $menu_result['current_selected_link'];

    if(isset($show_quick_search) && $show_quick_search){
        handle_hook('top_menu_end');
    }

    switch($menu_holder){
        case false:
        case 'main':
            // we don't close off this menu because the theme adds some stuff to it in design_header.php - hacky!
            //if(isset($header_nav_mini))$header_nav_mini .= '</ul>'; // for the responsive menu
            ?> <div class="bottom">
		            <!-- bottom stuff here -->
	            </div>
            </nav>
            <?php
            break;
        default:
            ?>
            </ul>

            <?php
            break;

    }


    unset($menu_type);
    unset($current_link);
    unset($menu_allow_nesting);

if(!isset($do_metis_inner_div)){
    $do_metis_inner_div = true;
    //<div class="inner">
    ?>

    <?php
}


unset($menu_holder);
