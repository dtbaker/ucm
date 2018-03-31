<?php

if(!module_config::can_i('view','Settings')){
    redirect_browser(_BASE_HREF);
}
print_heading('Menu Configuration');

if(isset($_REQUEST['save_menu_config'])&&is_array($_REQUEST['save_menu_config']) && module_form::check_secure_key()){
    foreach($_REQUEST['save_menu_config'] as $menu_id=>$menu_data){
	    if(is_array($menu_data)){
		    $current_data = @json_decode(module_config::c('_menu_config_'.$menu_id),true);
		    if(!$current_data)$current_data = array();
		    $current_data = array_replace($current_data, $menu_data);
		    module_config::save_config('_menu_config_'.$menu_id,json_encode($current_data));
	    }
    }
    set_message('Menu order saved');
}
?>

<form action="" method="post">
	<?php module_form::print_form_auth(); ?>
    <table class="tableclass tableclass_rows">
        <thead>
        <tr>
            <th>
                <?php _e('Plugin');?>
            </th>
            <th>
                <?php _e('Menu Label');?>
            </th>
            <th>
                <?php _e('Position');?>
            </th>
            <th>
                <?php _e('Icon');?>
	            <?php _h('Only works in themes that show menu icons. The font-awesome icon name from https://fortawesome.github.io/Font-Awesome/icons/');?>
            </th>
            <th>
                <?php _e('Parent');?>
	            <?php _h('Only works in themes that support nested main menu items');?>
            </th>
        </tr>
        </thead>
        <tbody>
        <?php
        $c=0;
//        query("DELETE FROM `"._DB_PREFIX."config` WHERE `key` LIKE '%_menu_config_%'");
        $menu_items = array();
        foreach(get_multiple('config') as $config) {
	        if ( preg_match( '#_menu_config_(.*)#', $config['key'], $matches ) ) {
		        $menu_data = @json_decode( $config['val'], true );
		        if ( ! $menu_data || ! empty( $menu_data['holder_module'] ) ) {
			        continue;
		        } // only display top level menus
		        $menu_items[$matches[1]] = $menu_data;
	        }
        }

        $parents = array();
        foreach($menu_items as $menu_id => $menu_data){
	        $parents[$menu_id] = (!empty($menu_data['module']) ? htmlspecialchars($menu_data['module']).' - ' : '') . (!empty($menu_data['label']) ? htmlspecialchars(preg_replace('#<[^>]*>[^<]*<[^>]*>#','',$menu_data['label'])) : $menu_id);
        }
        foreach($menu_items as $menu_id => $menu_data){
            ?>
            <tr class="<?php echo $c++%2 ? 'odd' : 'even';?>">
                <td>
	                <?php echo !empty($menu_data['module']) ? htmlspecialchars($menu_data['module']) : '';?>
                </td>
                <td>
                    <?php echo !empty($menu_data['label']) ? htmlspecialchars(preg_replace('#<[^>]*>[^<]*<[^>]*>#','',$menu_data['label'])) : $menu_id;?>
                </td>
                <td>
                    <input type="text" name="save_menu_config[<?php echo htmlspecialchars($menu_id);?>][order]" value="<?php echo htmlspecialchars($menu_data['order']);?>" size="6">
                </td>
                <td>
                    <input type="text" name="save_menu_config[<?php echo htmlspecialchars($menu_id);?>][icon]" value="<?php echo htmlspecialchars($menu_data['icon']);?>" size="6">
                </td>
                <td>
                    <?php
                    $this_parents = $parents;
                    unset($this_parents[$menu_id]);
                    echo print_select_box($this_parents, 'save_menu_config['.htmlspecialchars($menu_id).'][parent]', $menu_data['parent'],'',' - None - '); ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <input type="submit" name="save" value="<?php _e('Update menu settings');?>">
</form>