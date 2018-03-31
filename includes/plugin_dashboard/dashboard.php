<?php


// sort the alerts
function sort_ajax_alert($a,$b){
    if(isset($a['time'])&&isset($b['time'])){
        return $a['time'] > $b['time'];
    }
    return strtotime(input_date($a['date'])) > strtotime(input_date($b['date']));
}

class module_dashboard extends module_base{


    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
	public static function get_class() {
        return __CLASS__;
    }

	public function init(){
		$this->module_name = "dashboard";
		$this->module_position = 0;


        $this->version = 2.140;
        //2.140 - 2017-02-16 - dashboard_tab_ajax_stream setting
        //2.139 - 2016-11-16 - fontawesome fixes
        //2.138 - 2014-11-19 - dashboard date sorting fix
        //2.137 - 2014-08-06 - dashboard tab bug fix
        //2.136 - 2014-05-29 - dashboard tabs permission fix
        //2.135 - 2014-04-03 - speed improvements
        //2.134 - 2014-03-19 - fix for dashboard_alerts_ajax
        //2.133 - 2014-03-14 - added dashboard_alerts_ajax configuration variable
        //2.132 - 2014-01-23 - added dashboard_alerts_as_tabs configuration variable
        //2.131 - 2013-11-15 - working on new UI
        //2.13 - 2013-08-30 - dashboard speed improvements
        //2.12 - 2013-05-27 - dashboard alert improvements
        //2.11 - 2013-04-11 - initial release

	}

    public static $group_settings=array();
    public static function register_group($group_name,$settings){
        self::$group_settings[$group_name] = $settings;
    }

    public static function get_dashboard_alerts($dashboard_alerts = array()){

        // these can be cached.
        /*$cache_key = "dash_alerts_".module_security::get_loggedin_id();
        $cache_timeout = module_config::c('dashboard_cache_timeout',120);
        if($dashboard_alerts = module_cache::get('dashboard',$cache_key)){
             return $dashboard_alerts;
         }*/
        //echo "Dashboard alerts returned was ";print_r($dashboard_alerts);exit;


        if(!count($dashboard_alerts) && module_security::can_user(module_security::get_loggedin_id(),'Show Dashboard Alerts')){
            $results = handle_hook("home_alerts");
            if (is_array($results)) {
                $alerts = array();
                foreach ($results as $res) {
                    if (is_array($res)) {
                        foreach ($res as $r) {
                            $alerts[] = $r;
                        }
                    }
                }
                // sort the alerts
                function sort_alert($a,$b){
                    if(isset($a['time'])&&isset($b['time'])){
                        return $a['time'] > $b['time'];
                    }
                    if(isset($a['date'])&&isset($b['date'])){
                        return strtotime($a['date']) > strtotime($b['date']);
                    }
	                return 0;
                }
                uasort($alerts,'sort_alert');
                foreach($alerts as $alert){
                    $group_key = isset($alert['group'])?$alert['group']:$alert['item'];
                    if(!isset($dashboard_alerts[$group_key])){
                        $dashboard_alerts[$group_key] = array();
                    }
                    $dashboard_alerts[$group_key][] = $alert;
                }
            }
        }

        $limit = module_config::c('dashboard_tabs_group_limit',0);
        $items_to_hide = json_decode(module_config::c('_dashboard_item_hide'.module_security::get_loggedin_id(),'{}'),true);
        if(!is_array($items_to_hide))$items_to_hide = array();
        if(isset($_REQUEST['hide_item'])&&strlen($_REQUEST['hide_item'])){
            $items_to_hide[] = $_REQUEST['hide_item'];
            module_config::save_config('_dashboard_item_hide'.module_security::get_loggedin_id(),json_encode($items_to_hide));
        }
        $all_listing = array();
        foreach($dashboard_alerts as $key => $val){
            // see if any of these "$val" alert entries are marked as hidden
            if(!isset($_REQUEST['show_hidden'])){
                foreach($val as $k=>$v){
                    $hide_key = md5($v['link'].$v['item'].$v['name']);
                    $dashboard_alerts[$key][$k]['hide_key'] = $val[$k]['hide_key'] = $hide_key;
                    if(in_array($hide_key,$items_to_hide)){
                        unset($val[$k]);
                        unset($dashboard_alerts[$key][$k]);
                    }
                }
            }
            if(count($val)>$limit){
                // this one gets it's own tab!
            }else{
                // this one goes into the all_listing bin
                $all_listing = array_merge($all_listing,$val);
                unset($dashboard_alerts[$key]);
            }
        }
        if(count($all_listing)){
            $dashboard_alerts = array(_l('Alerts')=>$all_listing) + $dashboard_alerts;
        }
        ksort($dashboard_alerts);
        //module_cache::put('dashboard',$cache_key,$dashboard_alerts,$cache_timeout);
        return $dashboard_alerts;
    }


    public function process(){
        if("ajax_dashboard_tabs" == $_REQUEST['_process']){
	        header("Content-type: text/javascript");
	        if(module_security::is_logged_in() && module_security::can_user(module_security::get_loggedin_id(),'Show Dashboard Alerts')){
	            $items_to_hide = json_decode(module_config::c('_dashboard_item_hide'.module_security::get_loggedin_id(),'{}'),true);
	            if(!is_array($items_to_hide))$items_to_hide = array();
	            //$results = handle_hook("home_alerts");
	            global $plugins;
	            $tabid=1;
	            // Implicitly flush the buffer(s)
                if(module_config::c('dashboard_tab_ajax_stream',1)) {
                    @ini_set('implicit_flush', true);
                    @ob_implicit_flush(true);
                }
	            ?>
	            var tabs = $('#dashboard_tabs'); //.tabs();
	            var ul = tabs.find( "ul" );
	            <?php
	            if(is_array($plugins)){
	                foreach($plugins as $plugin_name => $plugin){
	                    if(is_callable(array($plugin,'handle_hook'))){
	                        $argv = array('home_alerts');
	                        $this_return = call_user_func_array(array(&$plugin,'handle_hook'),$argv);
	                        if($this_return !== false && $this_return !== null && is_array($this_return) && count($this_return)){
	                            // we found some home dashboard alerts! yew! print these out for our ajax display and exit, waiting for the next one.
	                            uasort($this_return,'sort_ajax_alert');
	                            $dashboard_alerts = array();
	                            foreach($this_return as $alert){
	                                $group_key = isset($alert['group'])?$alert['group']:$alert['item'];
	                                if(!isset($dashboard_alerts[$group_key])){
	                                    $dashboard_alerts[$group_key] = array();
	                                }
	                                $dashboard_alerts[$group_key][] = $alert;
	                            }
	                            if(!isset($_REQUEST['show_hidden'])){
	                                foreach($dashboard_alerts as $key => $val){
	                                    // see if any of these "$val" alert entries are marked as hidden
	                                    foreach($val as $k=>$v){
	                                        $hide_key = md5($v['link'].$v['item'].(isset($v['name'])?$v['name']:''));
	                                        $dashboard_alerts[$key][$k]['hide_key'] = $val[$k]['hide_key'] = $hide_key;
	                                        if(in_array($hide_key,$items_to_hide)){
	                                            unset($val[$k]);
	                                            unset($dashboard_alerts[$key][$k]);
	                                        }
	                                    }
	                                }
	                            }
	                            //print_r($dashboard_alerts);
	                            foreach($dashboard_alerts as $key=>$alerts){
	                                if(!count($alerts))continue;
	                                $tabid++;
	                                ?>
	                                $( "<li><a href='#newtab<?php echo $tabid;?>'><?php echo $key;?> (<?php echo count($alerts);?>)</a></li>" ).appendTo( ul );
	                                <?php
	                                ob_start();
	                                if(isset(module_dashboard::$group_settings[$key])){
	                                ?>
	                                <table class="tableclass tableclass_rows tableclass_full" id="alert_table_<?php echo strtolower(str_replace(' ','',$key));?>">
	                                    <thead>
		                                    <tr>
		                                        <?php foreach(module_dashboard::$group_settings[$key]['columns'] as $column_key=>$column_title){ ?>
		                                        <th class="alert_column_<?php echo $column_key;?>"><?php echo $column_title;?></th>
		                                        <?php }  ?>
		                                        <th width="10" class="alert_column_delete"></th>
		                                    </tr>
	                                    </thead>
	                                    <tbody>
	                                        <?php
	                                        if (count($alerts)) {
	                                            $y = 0;
	                                            foreach ($alerts as $alert) {
	                                                ?>
	                                                <tr class="<?php echo ($y++ % 2) ? 'even' : 'odd'; ?>">
	                                                    <?php foreach(module_dashboard::$group_settings[$key]['columns'] as $column_key=>$column_title){ ?>
	                                                    <td><?php echo isset($alert[$column_key]) ? $alert[$column_key] : '';?></td>
	                                                    <?php } ?>
	                                                    <?php if(isset($alert['hide_key']) && $alert['hide_key']){ ?>
	                                                    <td width="10">
	                                                        <a href="#" class="delete-alert" onclick="return hide_item('<?php echo $alert['hide_key'];?>');"><i class="fa fa-trash"></i></a>
	                                                    </td>
	                                                    <?php } ?>
	                                                </tr>
	                                            <?php
	                                            }
	                                        } else {
	                                            ?>
	                                            <tr>
	                                                <td class="odd" colspan="4"><?php _e('Yay! No alerts!');?></td>
	                                            </tr>
	                                        <?php  } ?>
	                                    </tbody>
	                                </table>
	                                <?php
	                            }else{
	                                // old method of output for unregistered alerts:
	                                // will remove once all have been converted.
	                                ?>
	                            <table class="tableclass tableclass_rows tableclass_full tbl_fixed" id="alert_table_<?php echo strtolower(str_replace(' ','',$key));?>">
	                                    <tbody>
	                                    <?php
	                                    if (count($alerts)) {
	                                        $y = 0;
	                                        foreach ($alerts as $alert) {
	                                            ?>
	                                            <tr class="<?php echo ($y++ % 2) ? 'even' : 'odd'; ?>">
	                                                <td class="row_action">
	                                                    <a href="<?php echo $alert['link']; ?>"><?php echo htmlspecialchars($alert['item']); ?></a>
	                                                </td>
	                                                    <td>
	                                                        <?php echo isset($alert['name']) ? htmlspecialchars($alert['name']) : ''; ?>
	                                                    </td>
	                                                    <td width="16%">
	                                                        <?php echo ($alert['warning']) ? '<span class="important">' : ''; ?>
	                                                        <?php echo $alert['days']; ?>
	                                                        <?php echo ($alert['warning']) ? '</span>' : ''; ?>
	                                                    </td>
	                                                <td width="16%">
	                                                    <?php echo ($alert['warning']) ? '<span class="important">' : ''; ?>
	                                                    <?php echo print_date($alert['date']); ?>
	                                                    <?php echo ($alert['warning']) ? '</span>' : ''; ?>
	                                                </td>
	                                                <?php if(isset($alert['hide_key']) && $alert['hide_key']){ ?>
	                                                <td width="10">
	                                                    <a href="#" class="delete-alert" onclick="return hide_item('<?php echo $alert['hide_key'];?>');"><i class="fa fa-trash"></i></a>
	                                                </td>
	                                                <?php } ?>
	                                            </tr>
	                                        <?php
	                                        }
	                                    } else {
	                                        ?>
	                                        <tr>
	                                            <td class="odd" colspan="4"><?php _e('Yay! No alerts!');?></td>
	                                        </tr>
	                                    <?php  } ?>
	                                    </tbody>
	                                </table>
	                            <?php } // end old method
	                                $html = ob_get_clean();
	                                $html = preg_replace('#\s+#',' ',$html);
	                                $html = addcslashes($html,"'");
	                                ?>
	                                $( '<div id="newtab<?php echo $tabid;?>"><?php echo $html;?></div>' ).appendTo( tabs );
	                                <?php
	                            }
	                            ?>
	                            tabs.tabs( "refresh" );
	                            <?php
                                if(module_config::c('dashboard_tab_ajax_stream',1)) {
                                    for ($x = 1; $x < 50; $x++) echo " \t \n";
                                    @flush();
                                    @ob_end_flush();
                                }
	                        }else{
	                            // nothing? continue onto next hook...
	                        }
	                    }
	                }
	            }
	        }
            ?>
            $('#tabs_loading').hide();
            <?php
            exit;
        }
    }

    public static function output_dashboard_alerts($ajax=false){

        module_debug::log(array(
                    'title' => 'Outputting Dashboard Alerts',
                    'data' => '',
                 ));

        if($ajax && module_config::c('dashboard_alerts_as_tabs',1)){

            $items_to_hide = json_decode(module_config::c('_dashboard_item_hide'.module_security::get_loggedin_id(),'{}'),true);
            if(!is_array($items_to_hide))$items_to_hide = array();
            if(isset($_REQUEST['hide_item'])&&strlen($_REQUEST['hide_item'])){
                $items_to_hide[] = $_REQUEST['hide_item'];
                module_config::save_config('_dashboard_item_hide'.module_security::get_loggedin_id(),json_encode($items_to_hide));
            }

            $dashboard_alerts = array();
            include(module_theme::include_ucm('includes/plugin_dashboard/pages/dashboard_alerts.php'));
            // output some javascript that will load our ajax hooks and display in a tab one by one
            ?>
            <script type="text/javascript">
                $(function(){
                    setTimeout(function(){
                        //$('body').append('<scr'+'ipt type="text/javascript" src="<?php echo _BASE_HREF;?>?m=dashboard&_process=ajax_dashboard_tabs"></scri'+'pt>');
                        var scriptObject = document.createElement('script');
                        scriptObject .type = 'text/javascript';
                        scriptObject .async = true;
                        scriptObject .src = "<?php echo _BASE_HREF;?>?m=dashboard&_process=ajax_dashboard_tabs&<?php echo isset($_REQUEST['show_hidden']) ? 'show_hidden' : '';?>";
                        document.getElementsByTagName('head')[0].appendChild(scriptObject );
                        $('#dashboard_tabs').before('<p id="tabs_loading"><?php _e('Loading Alerts...');?></p>');
                    }, 200);
                });
            </script>

            <?php


        }else{

            // we collect alerts from various places using our UCM hooks:
            $dashboard_alerts = self::get_dashboard_alerts();
            include(module_theme::include_ucm('includes/plugin_dashboard/pages/dashboard_alerts.php'));
        }
    }

}