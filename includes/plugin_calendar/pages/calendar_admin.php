<?php


print_heading(array(
    'main' => true,
    'type' => 'h2',
    'title' => 'Calendar',
));

$customer_id = isset($_REQUEST['customer_id']) ? (int)$_REQUEST['customer_id'] : false;
$customer_access = module_customer::get_customer_data_access();
if($customer_access && $customer_access != _CUSTOMER_ACCESS_ALL){
    // restricted to what customers we can see. is it only 1?
    $customer_access_ids = module_security::get_customer_restrictions();
    if(count($customer_access_ids) == 1){
        $customer_access_id = current($customer_access_ids);
        if($customer_access_id > 0){
            $customer_id = $customer_access_id;
        }
    }
}

$base_path = _BASE_HREF.'includes/plugin_calendar/wdCalendar/';
?>
<link href="<?php echo $base_path;?>css/calendar.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $base_path;?>css/alert.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $base_path;?>css/main.css" rel="stylesheet" type="text/css" />

<script src="<?php echo $base_path;?>src/Plugins/Common.js" type="text/javascript"></script>

<script src="<?php echo $base_path;?>src/Plugins/jquery.alert.js" type="text/javascript"></script>
<script type="text/javascript">
	var i18n = $.extend({}, i18n || {}, {
    xgcalendar: {
        dateformat: {
            "fulldaykey": "MMddyyyy",
            "fulldayshow": "L d yyyy",
            "fulldayvalue": "M/d/yyyy",
            "Md": "W M/d",
            "Md3": "L d",
            "separator": "/",
            "year_index": 2,
            "month_index": 0,
            "day_index": 1,
            "day": "d",
            "sun": "<?php _e('Sun');?>",
            "mon": "<?php _e('Mon');?>",
            "tue": "<?php _e('Tue');?>",
            "wed": "<?php _e('Wed');?>",
            "thu": "<?php _e('Thu');?>",
            "fri": "<?php _e('Fri');?>",
            "sat": "<?php _e('Sat');?>",
            "jan": "<?php _e('Jan');?>",
            "feb": "<?php _e('Feb');?>",
            "mar": "<?php _e('Mar');?>",
            "apr": "<?php _e('Apr');?>",
            "may": "<?php _e('May');?>",
            "jun": "<?php _e('Jun');?>",
            "jul": "<?php _e('Jul');?>",
            "aug": "<?php _e('Aug');?>",
            "sep": "<?php _e('Sep');?>",
            "oct": "<?php _e('Oct');?>",
            "nov": "<?php _e('Nov');?>",
            "dec": "<?php _e('Dec');?>"
        },
	    timeformat: {
		    "hours" : "<?php echo module_config::c("calendar_hour_format",'12') == '12' ? '12' : '24';?>",
		    "am" : "<?php _e("am");?>",
		    "pm" : "<?php _e("pm");?>",
		    "start_hour" : <?php echo (int)module_config::c("calendar_start_hour",'0');?>,
		    "end_hour" : <?php echo (int)module_config::c("calendar_end_hour",'24');?>
	    },
	    "pixel_height" : 42 * (<?php echo (int)module_config::c("calendar_end_hour",'24');?>-<?php echo (int)module_config::c("calendar_start_hour",'0');?>), //// 1008 is the full pixel height for 24 hours. 42px per hour.
        "no_implemented": "<?php _e('No implemented yet');?>",
        "to_date_view": "<?php _e('Click to the view of current date');?>",
        "i_undefined": "<?php _e('Undefined');?>",
        "allday_event": "<?php _e('All day event');?>",
        "repeat_event": "<?php _e('Repeat event');?>",
        "multi_day_event": "<?php _e('Multiple Day Event');?>",
        "time": "<?php _e('Time');?>",
        "event": "<?php _e('Subject');?>",
        "location": "<?php _e('Location');?>",
        "customer": "<?php _e('Customer');?>",
        "participant": "<?php _e('Participant');?>",
        "get_data_exception": "<?php _e('Exception when getting data');?>",
        "new_event": "<?php _e('New event');?>",
        "confirm_delete_event": "<?php _e('Do you confirm to delete this event?');?>",
        "confrim_delete_event_or_all": "<?php _e('Do you want to all repeat events or only this event? \r\nClick [OK] to delete only this event, click [Cancel] delete all events');?>",
        "data_format_error": "<?php _e('Data format error!');?>",
        "invalid_title": "<?php _e('Event title can not be blank or contains ($<>)');?>",
        "view_no_ready": "<?php _e('View is not ready');?>",
        "example": "<?php _e('e.g., meeting at room 107');?>",
        "content": "<?php _e('What');?>",
        "create_event": "<?php _e('Create event');?>",
        "update_detail": "<?php _e('Edit details');?>",
        "click_to_detail": "<?php _e('View details');?>",
        "i_delete": "<?php _e('Delete');?>",
        "day_plural": "<?php _e('days');?>",
        "others": "<?php _e('Others');?>",
        "item": "",
        "new_customer_name": "<?php
         if($customer_id){
             $customer_data = module_customer::get_customer($customer_id);
             if($customer_data && $customer_data['customer_id'] == $customer_id){
                echo addcslashes(htmlspecialchars($customer_data['customer_name']),'"');
             }
         }
         ?>"
    }
});
</script>
<script src="<?php echo $base_path;?>src/Plugins/jquery.calendar.js?ver=<?php echo _SCRIPT_VERSION;?>" type="text/javascript"></script>


<div id="calendar_event_popup" title="<?php _e('Calendar Event');?>">
	<div id="calendar_event_popup_inner"></div>
</div>

<script type="text/javascript">

    // scripts to open the modal window for our event editing:


    var calendar_id = 0; // currently editing calendar event id?
    var calendar_data = []; // currently editing calendar event id?
    $(function(){
		$("#calendar_event_popup").dialog({
			autoOpen: false,
			height: 600,
			width: 500,
			modal: true,
			buttons: {
				'<?php echo addcslashes(_l('Save Event'),"'");?>': function() {
					$.ajax({
						type: 'POST',
                        url: '<?php echo preg_replace('#[^a-zA-Z0-9&\?=/\]\%\[_\.]#','',$_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'],'?') ? '&' : '?');?>_process=save_calendar_entry&calendar_id='+calendar_id+'',
						data: $('#calendar_event_form').serialize(),
                        dataType: 'html',
						success: function(d){
                            // refresh the calender with these new changes
                            $("#calendar_event_popup").dialog('close');
                            $("#gridcontainer").reload();
						},
                        error: function (header, status, error) {
                            console.log('ajax answer post returned error ' + header + ' ' + status + ' ' + error);
                        }
					});
				},
                '<?php echo addcslashes(_l('Cancel'),"'");?>': function() {
					$(this).dialog('close');
				}
			},
			open: function(){
                var calendar_data_for_post = {};
                if(typeof calendar_data[0] != 'undefined'){
                    calendar_data_for_post['calendar_id'] = calendar_data[0];
                }
                if(typeof calendar_data[1] != 'undefined'){
                    calendar_data_for_post['title'] = calendar_data[1];
                }
                if(typeof calendar_data[2] != 'undefined'){
                    calendar_data_for_post['start_date_time'] = calendar_data[2];
                }
                if(typeof calendar_data[3] != 'undefined'){
                    calendar_data_for_post['end_date_time'] = calendar_data[3];
                }
                if(typeof calendar_data[4] != 'undefined'){
                    calendar_data_for_post['is_all_day'] = calendar_data[4];
                }
				$.ajax({
					type: "POST",
                    url: '<?php echo full_link('?m=calendar&p=calendar_admin_edit&display_mode=ajax&customer_id=' . ($customer_id ? $customer_id : 0 ) . '&')?>calendar_id='+calendar_id+'',
                    data: calendar_data_for_post,
					dataType: "html",
					success: function(d){
                        $('#calendar_event_popup_inner').html(d);
                        ucm.init_interface();
					}
				});
			},
			close: function() {
				$('#calendar_event_popup_inner').html('');
			}
		});

       var view="<?php echo module_config::c('calendar_default_view','week');?>";

        var DATA_FEED_URL = "<?php echo _BASE_HREF.'?m=calendar' . ($customer_id ? '&customer_id='.$customer_id :'') .'&';
         //preg_replace('#[^a-zA-Z0-9&\?=/\]\[_\.]#','',$_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'],'?') ? '&' : '?'); ?>";
        var op = {
            view: view,
            theme:3,
            showday: new Date(),
            EditCmdhandler:Edit,
            DeleteCmdhandler:Delete,
            ViewCmdhandler:View,
            onWeekOrMonthToDay:wtd,
            onBeforeRequestData: cal_beforerequest,
            onAfterRequestData: cal_afterrequest,
            onRequestDataError: cal_onerror,
            autoload:true,
            readonly:<?php echo module_calendar::can_i('create','Calendar') ? 'false' : 'true';?>,
            url: DATA_FEED_URL + "_process=ajax_calendar&method=list",
            //extParam: {customer_id: <?php echo $customer_id ? $customer_id : 0;?>},
            quickAddUrl: DATA_FEED_URL + "_process=ajax_calendar&method=quick_add",
            quickUpdateUrl: DATA_FEED_URL + "_process=ajax_calendar&method=quick_update",
            quickDeleteUrl: DATA_FEED_URL + "_process=ajax_calendar&method=quick_remove"
        };
        var $dv = $("#calhead");
        var _MH = document.documentElement.clientHeight;
        var dvH = $dv.height() + 2;
        op.height = _MH - dvH + 30;
        op.eventItems =[];

        var p = $("#gridcontainer").bcalendar(op).BcalGetOp();
        if (p && p.datestrshow) {
            $("#txtdatetimeshow").text(p.datestrshow);
        }
        $("#caltoolbar").noSelect();

        $("#hdtxtshow").datepicker({ picker: "#txtdatetimeshow", showtarget: $("#txtdatetimeshow"),
            onReturn:function(r){
                var p = $("#gridcontainer").gotoDate(r).BcalGetOp();
                if (p && p.datestrshow) {
                    $("#txtdatetimeshow").text(p.datestrshow);
                }
             }
        });
        function cal_beforerequest(type)
        {
            var t="Loading data...";
            switch(type)
            {
                case 1:
                    t="Loading data...";
                    break;
                case 2:
                case 3:
                case 4:
                    t="The request is being processed ...";
                    break;
            }
            $("#errorpannel").hide();
            $("#loadingpannel").html(t).show();
        }
        function cal_afterrequest(type)
        {
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }
            switch(type)
            {
                case 1:
                    $("#loadingpannel").hide();
                    break;
                case 2:
                case 3:
                case 4:
                    $("#loadingpannel").html("Success!");
                    window.setTimeout(function(){ $("#loadingpannel").hide();},2000);
                break;
            }

        }
        function cal_onerror(type,data)
        {
            $("#errorpannel").show();
        }
        function Edit(data)
        {
           //var eurl="<?php echo full_link('?m=calendar&p=calendar_admin_edit&display_mode=iframe&');?>id={0}&start={2}&end={3}&isallday={4}&title={1}";
            if(data)
            {
                calendar_id = data[0];
                calendar_data = data;
                $('#calendar_event_popup').dialog('open');
                /*var url = StrFormat(eurl,data);
                OpenModelWindow(url,{ width: 600, height: 400, caption:"Manage  The Calendar",onclose:function(){
                   $("#gridcontainer").reload();
                }});*/
            }
        }
        function View(data)
        {
            return false;
            var str = "";
            $.each(data, function(i, item){
                str += "[" + i + "]: " + item + "\n";
            });
            alert(str);
        }
        function Delete(data,callback)
        {

            $.alerts.okButton="Ok";
            $.alerts.cancelButton="Cancel";
            hiConfirm("<?php _e('Are You Sure to Delete this Event');?>", 'Confirm',function(r){ r && callback(0);});
        }
        function wtd(p)
        {
           if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }
            $("#caltoolbar div.fcurrent").each(function() {
                $(this).removeClass("fcurrent");
            });
            $("#showdaybtn").addClass("fcurrent");
        }
        //to show day view
        $("#showdaybtn").click(function(e) {
            //document.location.href="#day";
            $("#caltoolbar div.fcurrent").each(function() {
                $(this).removeClass("fcurrent");
            });
            $(this).addClass("fcurrent");
            var p = $("#gridcontainer").swtichView("day").BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }
        });
        //to show week view
        $("#showweekbtn").click(function(e) {
            //document.location.href="#week";
            $("#caltoolbar div.fcurrent").each(function() {
                $(this).removeClass("fcurrent");
            });
            $(this).addClass("fcurrent");
            var p = $("#gridcontainer").swtichView("week").BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }

        });
        //to show month view
        $("#showmonthbtn").click(function(e) {
            //document.location.href="#month";
            $("#caltoolbar div.fcurrent").each(function() {
                $(this).removeClass("fcurrent");
            });
            $(this).addClass("fcurrent");
            var p = $("#gridcontainer").swtichView("month").BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }
        });

        $("#showreflashbtn").click(function(e){
            $("#gridcontainer").reload();
        });

        //Add a new event
        $("#faddbtn").click(function(e) {
            calendar_id = 0;
            calendar_data = [];
            $('#calendar_event_popup').dialog('open');
            //var url ="<?php echo full_link('?m=calendar&p=calendar_admin_edit&display_mode=iframe');?>";
            //OpenModelWindow(url,{ width: 500, height: 400, caption: '<?php echo _l("Create New Calendar Event");?>'});
        });
        //go to today
        $("#showtodaybtn").click(function(e) {
            var p = $("#gridcontainer").gotoDate().BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }


        });
        //previous date range
        $("#sfprevbtn").click(function(e) {
            var p = $("#gridcontainer").previousRange().BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }

        });
        //next date range
        $("#sfnextbtn").click(function(e) {
            var p = $("#gridcontainer").nextRange().BcalGetOp();
            if (p && p.datestrshow) {
                $("#txtdatetimeshow").text(p.datestrshow);
            }
        });

    });
</script>

<div id="wd_calendar">

      <div id="calhead" style="padding-left:1px;padding-right:1px;">
            <div class="cHead"><div class="ftitle">&nbsp;</div>
            <div id="loadingpannel" class="ptogtitle loadicon" style="display: none;"><?php _e('Loading data...');?></div>
             <div id="errorpannel" class="ptogtitle loaderror" style="display: none;"><?php _e('Sorry, could not load your data, please try again later');?></div>
            </div>

            <div id="caltoolbar" class="ctoolbar">
              <?php if(module_calendar::can_i('create','Calendar')){ ?><div id="faddbtn" class="fbutton">
                <div><span title='Click to Create New Event' class="addcal">
                <?php _e('New Event');?>
                </span></div>
            </div>
              <?php } ?>
                <div class="btnseparator"></div>
             <div id="showtodaybtn" class="fbutton">
                <div><span title='Click to back to today ' class="showtoday">
                <?php _e('Today');?></span></div>
            </div>
              <div class="btnseparator"></div>

            <div id="showdaybtn" class="fbutton<?php echo module_config::c('calendar_default_view')=='day' ? ' fcurrent' : '';?>">
                <div><span title='Day' class="showdayview"><?php _e('Day');?></span></div>
            </div>
              <div  id="showweekbtn" class="fbutton<?php echo module_config::c('calendar_default_view')=='week' ? ' fcurrent' : '';?>">
                <div><span title='Week' class="showweekview"><?php _e('Week');?></span></div>
            </div>
              <div  id="showmonthbtn" class="fbutton<?php echo module_config::c('calendar_default_view')=='month' ? ' fcurrent' : '';?>">
                <div><span title='Month' class="showmonthview"><?php _e('Month');?></span></div>

            </div>
            <div class="btnseparator"></div>
              <div  id="showreflashbtn" class="fbutton">
                <div><span title='Refresh view' class="showdayflash"><?php _e('Refresh');?></span></div>
                </div>
             <div class="btnseparator"></div>
            <div id="sfprevbtn" title="Prev"  class="fbutton">
              <span class="fprev"></span>

            </div>
            <div id="sfnextbtn" title="Next" class="fbutton">
                <span class="fnext"></span>
            </div>
            <div class="fshowdatep fbutton">
                    <div>
                        <input type="hidden" name="txtshow" id="hdtxtshow" />
                        <span id="txtdatetimeshow"><?php _e('Loading');?></span>

                    </div>
            </div>

            <div class="clear"></div>
            </div>
      </div>
      <div style="padding:1px;">

        <div class="t1 chromeColor">
            &nbsp;</div>
        <div class="t2 chromeColor">
            &nbsp;</div>
        <div id="dvCalMain" class="calmain printborder">
            <div id="gridcontainer" style="overflow-y: visible;">
            </div>
        </div>
        <div class="t2 chromeColor">

            &nbsp;</div>
        <div class="t1 chromeColor">
            &nbsp;
        </div>
        </div>

  </div>