ucm.timer = {

    timer_ajax_url: '',
    mode: 1, // 1 = one timer at a time (new version), 2 = multiple active timers at a time (original version)
    timers: [], // array of active timers, obtained via ajax
    chunk_split: '||$|$||',
    split_chunk: '$$|$|$$',
    timer_index: {
        name: 0,
        url: 1,
        task_id: 2,
        start_time: 3,
        job_id: 5
    },
    lang: {
        pause: 'Pause',
        start: 'Start',
        resume: 'Resume',
        restart: 'Restart',
        complete: 'Complete'
    },
    init: function(){


        $(document).on('click','[data-task-timer]',function(e){
            e.preventDefault();
            ucm.timer.task_timer_clicked($(this).data('task-timer'));
            return false;
        });
        $(document).on('mouseenter','.timer_counter',function(){
            ucm.timer.timer_hover(this);
            return false;
        });

        // load any active timers from cookies.
        /*var cookietimers = this.tread();
        if(typeof cookietimers == 'string'){
            var bits = cookietimers.split(this.chunk_split);
            for (var i in bits){
                if (bits.hasOwnProperty(i)) {
                    var a = bits[i].split(this.split_chunk);
                    var this_timer={};
                    for(var t in this.timer_index){
                        if (this.timer_index.hasOwnProperty(t)) {
                            this_timer[t] = a[this.timer_index[t]];
                        }
                    }
                    this_timer['job_id'] = parseInt(this_timer['job_id']);
                    this_timer['task_id'] = parseInt(this_timer['task_id']);
                    if(!this_timer['job_id'] || !this_timer['task_id'])continue;
                    this_timer['start_time'] = parseInt(this_timer['start_time']);
                    this_timer['pause_time'] = parseInt(this_timer['pause_time']);
                    this.timers.push(this_timer);
                }
            }
        }*/
        // add a bit of pretty up in the header.
        // the theme must be able to style this so we leave it pretty generic

        $('#timer_menu_button').hover(function(){
            $('#timer_menu_options').show();
        },function(){
            $('#timer_menu_options').hide();
        });
        // start our ticker counter
        this.tick(true);
    },

    save_timers: function(){
        var timer_encoded=[];
        for(var i in this.timers){
            if (this.timers.hasOwnProperty(i)) {
                var arr = [];
                for(var t in this.timer_index){
                    if (this.timer_index.hasOwnProperty(t)) {
                        arr[this.timer_index[t]] = this.timers[i][t];
                    }
                }
                timer_encoded.push(arr.join(this.split_chunk));
            }
        }
        //this.twrite(timer_encoded.join(this.chunk_split));
    },
    // runs every second and updates any actively running timers:
    tick: function(recur){
        //var timer_count=0;
        for(var i in this.timers){
            if (this.timers.hasOwnProperty(i)) {

                if( typeof this.timers[i]['running'] != 'undefined' && this.timers[i]['running'] ) {
                    // timer is running
                    //timer_count++;
                    // update the timer with current time
                    var timer_elapsed = this.now() - this.timers[i]['timer_start'];
                }else{
                    var timer_elapsed = this.timers[i]['timer_length'];
                }

                var hours = Math.floor(timer_elapsed / 3600);
                var mins = Math.floor((timer_elapsed - (hours * 3600 )) / 60);
                var secs = Math.floor((timer_elapsed - (hours * 3600 ) - (mins * 60)));

                this.timers[i]['hours'] = hours;
                this.timers[i]['mins'] = mins;
                this.timers[i]['secs'] = secs;

                if (mins == 0) {
                    mins = '00';
                } else if (mins < 10) {
                    mins = '0' + mins;
                }

                if (secs < 10) {
                    secs = '0' + secs;
                }
                secs = ':' + secs;

                if (hours == 0) {
                    hours = '';
                } else {
                    hours = hours + ":";
                }

                this.timers[i]['timer_text'] = hours + mins + secs;

                if (typeof this.timers[i]['counter'] != 'undefined') {
                    this.timers[i]['counter'].find('.timer_number').html(this.timers[i]['timer_text']);
                }


            }
        }
        // header menu bits:
        /*if(timer_count>0){
            $('#timer_menu_button').show();
            $('#current_timer_count').html(timer_count);
        }else{
            $('#timer_menu_button').hide();
        }*/

        if(recur)setTimeout(function(){ucm.timer.tick(true);},500);
        ucm.timer.save_timers();// do this every second? sure!
    },
    now: function(){
        return Math.round(new Date().getTime() / 1000);
    },
    task_timer_clicked: function(task_timer_data){
        task_timer_data._process = 'task_timer_clicked';
        task_timer_data.form_auth_key = ucm.form_auth_key;
        $.post(
            ucm.timer.timer_ajax_url,
            task_timer_data,
            function(data){
                if(typeof data.timer != 'undefined'){
                    ucm.timer.init_timer(data.timer);
                }
            }
        );

    },
    automatic_page_timer: function(owner_table, owner_id){
        $.post(
            ucm.timer.timer_ajax_url,
            {
                _process: 'automatic_page_timer',
                form_auth_key: ucm.form_auth_key,
                owner_table: owner_table,
                owner_id: owner_id
            },
            function(data){
                if(typeof data.timers != 'undefined'){
                    for(var i in data.timers){
                        if(data.timers.hasOwnProperty(i)){
                            ucm.timer.init_timer(data.timers[i]);
                        }
                    }
                }
                setTimeout( function(){
                    ucm.timer.automatic_page_timer( owner_table, owner_id );
                }, 4000 );
            }
        );

    },
    init_timer: function(timer_data){
        // this will add or update an existing timer
        // data comes in from ajax.

        if( typeof timer_data.timer_display != 'undefined' ){
            $('[data-timer-field="duration"][data-timer-id="' + timer_data.timer_id + '"]').text( timer_data.timer_display );
        }

        timer_data.timer_start = ucm.timer.now() - timer_data.timer_length;

        // first we check if this is a new timer.
        var exists=0;
        for(var i in this.timers) {
            if (this.timers.hasOwnProperty(i)) {
                if ( typeof this.timers[i]['timer_id'] != 'undefined' && this.timers[i]['timer_id'] == timer_data.timer_id) {
                    exists = i;
                    for( var e in timer_data){
                        if(timer_data.hasOwnProperty(e)){
                            this.timers[i][e] = timer_data[e];
                        }
                    }
                    break;
                }
            }
        }

        if(!exists){
            this.timers.push(timer_data);
        }
        for(i in this.timers) {
            if (this.timers.hasOwnProperty(i)) {
                if ( typeof this.timers[i]['timer_id'] != 'undefined' && this.timers[i]['timer_id'] == timer_data.timer_id) {
                    exists = i;
                }
            }
        }
        if(!exists){
            return;
        }

        // find the ui element for this timer.
        if(typeof this.timers[exists].holder == 'undefined') {
            $timer_holder = $(this.timers[exists].selector);
            if ($timer_holder.length > 0) {

                if($timer_holder.next('.timer_counter').length){
                    console.log('Existing timer found for this item.');
                    return;
                }

                this.timers[exists].holder = $timer_holder;

                // this timer is on this page.
                $timer_holder.data('timer_data', this.timers[exists]);
                // update the ui for it.

                $timer_holder.show();
                var $timer_counter = $timer_holder.next('.timer_counter');

                if (!$timer_counter.length) {
                    $timer_counter = $('<span class="timer_counter"><span class="timer_number">00:00</span></span>');
                    $timer_holder.after($timer_counter);
                }

                $timer_counter.data('timer', this.timers[exists]);
                this.timers[exists].counter = $timer_counter;

            }
        }

        if( typeof this.timers[exists].counter != 'undefined' ) {
            if (typeof this.timers[exists].running != 'undefined' && this.timers[exists].running ) {
                // running.
                this.timers[exists].counter.addClass('timer-active');
            } else {
                this.timers[exists].counter.removeClass('timer-active');
            }
        }

    },
    load_page_timers_done: false,
    load_page_timers: function( owner_table, owner_id ){
        if(ucm.timer.load_page_timers_done){
            return;
        }
        ucm.timer.load_page_timers_done = true;
        // pull in all active/paused timers for this.
        ucm.set_var('timer_owner_table', owner_table);
        ucm.set_var('timer_owner_id', owner_id);

        $.post(
            ucm.timer.timer_ajax_url,
            {
                _process: 'load_page_timers',
                form_auth_key: ucm.form_auth_key,
                load_page_timers: {
                    owner_table: owner_table,
                    owner_id: owner_id
                }
            },
            function(data){
                if(typeof data.timers != 'undefined'){
                    for(var i in data.timers){
                        if(data.timers.hasOwnProperty(i)){
                            ucm.timer.init_timer(data.timers[i]);
                        }
                    }
                }
            }
        );

    },

    timer_delete: function(timer_object, from_server){

        for(var i in this.timers){
            if (this.timers.hasOwnProperty(i)) {
                if(this.timers[i].timer_id == timer_object.timer_id){

                    if( typeof timer_object.counter != 'undefined'){
                        timer_object.counter.remove();
                    }

                    if(from_server) {
                        // ajax delete timer.
                        ucm.timer.task_timer_clicked({
                            timer_id: timer_object.timer_id,
                            delete_completely: 1
                        });
                    }

                    delete(this.timers[i]);
                }
            }
        }

    },
    timer_finish: function(timer_object){


        ucm.timer.task_timer_clicked( {
            timer_id: timer_object.timer_id,
            finished: true
        } );

        if( timer_object['owner_table'] == 'job' && timer_object['owner_id'] && timer_object['owner_child_id']) {
            var job_id = timer_object['owner_id'];
            var task_id = timer_object['owner_child_id'];
            // if timer isn't finished, pause it now.
            edittask(task_id, null, function () {
                if (typeof $('#complete_t_' + task_id)[0] != 'undefined') {
                    // calculate how many hours this is in decimal
                    var time_decimal = timer_object['hours']; // eg: 0, 1, 2
                    if (timer_object['mins'] > 0) {
                        // 60 mins in an hour.
                        time_decimal += Math.floor((timer_object['mins'] / 60) * 100) / 100; // (eg: 0.25 for 15 mins)
                    }
                    // ignore seconds.
                    $('#complete_' + task_id).val(time_decimal);
                    $('#complete_t_' + task_id)[0].checked = true;
                } else {
                    alert('Failed to mark task as completed. Please try again.');
                }
            });
        }


        this.timer_delete(timer_object, false);
    },

    timer_hover: function(timerspan){
        var timer_object = $(timerspan).data('timer');
        $(timerspan).prepend('<div class="timer_hover">' +
            '<span class="timer_title">Timer</span>' +
            '<ul>' +
            '<li class="timer_number">'+ timer_object['timer_text'] +'</li>' +
            '</ul>' +
            '</div>');
        // add some buttons
console.log(timer_object);
        $(timerspan).find('.timer_hover ul').append($('<li class="timer_action" />').append($('<a href="#" class="timer_click">' + ( typeof timer_object['paused'] != 'undefined' && timer_object['paused'] ? 'Resume' : 'Pause') + '</a>').click(function(){
            ucm.timer.task_timer_clicked({
                timer_id: timer_object.timer_id
            });
            //ucm.timer.timer_hover(timerspan);
            $(timerspan).find('.timer_hover').remove();
            return false;
        })));
        // if we're in the header area then we don't show a 'record' button, just a view job button
        if($(timerspan).hasClass('timer_header')){
            //$(timerspan).find('.timer_hover ul').append($('<li class="timer_action" />').append($('<a href="'+timer_object['url']+'" class="timer_view">View Job</a>')));
        }else{
            $(timerspan).find('.timer_hover ul').append($('<li class="timer_action" />').append($('<a href="#" class="timer_finish">Finish</a>').click(function(){
                ucm.timer.timer_finish(timer_object);
                $(timerspan).find('.timer_hover').remove();
                return false;
            })));
        }
        $(timerspan).find('.timer_hover ul').append($('<li class="timer_action" />').append($('<a href="#" class="timer_cancel">Delete</a>').click(function(){
            if(confirm('Really delete this timer?')){
                ucm.timer.timer_delete(timer_object, true);
                $(timerspan).find('.timer_hover').remove();
            }
            return false;
        })));
        $(timerspan).on('mouseleave',function(){ $(timerspan).find('.timer_hover').remove(); });
    },

    link_to_dropdown: function( $textbox ){

        // this is called when the autocomplete textbox obtains focus
        // we dynamically change the lookuip box to search the appropriate link to object.

        var plugin = $('.timer_owner_table_change').val();
        if(plugin){
            ucm.set_var('timer_owner_table', plugin);
            var lookup = $textbox.data('lookup');
            /*lookup.plugin = plugin;
            $textbox.data('lookup', lookup);*/
        }

    },

    timer_object: function(){

        // this is the new timer we're using on the stopwatch.php page in the new timer section
        // I've adjusted some of the old code above to work with this new object, but more work needs to happen.

        var $timer_wrapper = false;

        var hours = minutes = seconds = milliseconds = 0;
        var prev_hours = prev_minutes = prev_seconds = prev_milliseconds = undefined;
        var timeUpdate;

        var totalOverallCounter = 0;

        // Start/Pause/Resume button onClick
        var $start_pause;

        // Update time in stopwatch periodically - every 25ms
        function updateTime(prev_hours, prev_minutes, prev_seconds, prev_milliseconds){
            var startTime = new Date();    // fetch current time

            timeUpdate = setInterval(function () {
                var timeElapsed = new Date().getTime() - startTime.getTime();    // calculate the time elapsed in milliseconds

                // calculate hours
                hours = parseInt(timeElapsed / 1000 / 60 / 60) + prev_hours;
                hours_total = parseInt( (timeElapsed + totalOverallCounter)  / 1000 / 60 / 60) + prev_hours;

                // calculate minutes
                minutes = parseInt(timeElapsed / 1000 / 60) + prev_minutes;
                if (minutes > 60) minutes %= 60;
                minutes_total = parseInt( ( timeElapsed + totalOverallCounter ) / 1000 / 60) + prev_minutes;
                if (minutes_total > 60) minutes_total %= 60;

                // calculate seconds
                seconds = parseInt(timeElapsed / 1000) + prev_seconds;
                if (seconds > 60) seconds %= 60;
                seconds_total = parseInt( ( timeElapsed + totalOverallCounter ) / 1000) + prev_seconds;
                if (seconds_total > 60) seconds_total %= 60;

                // calculate milliseconds
                // milliseconds = timeElapsed + prev_milliseconds;
                // if (milliseconds > 1000) milliseconds %= 1000;
                milliseconds = 0;

                // set the stopwatch
                setStopwatch(hours, minutes, seconds, milliseconds, hours_total, minutes_total, seconds_total);

            }, 200); // update time in stopwatch after every 25ms

        }

        // Set the time in stopwatch
        function setStopwatch(hours, minutes, seconds, milliseconds, hours_total, minutes_total, seconds_total){
            $timer_wrapper.find('.hours').html(prependZero(hours_total, 2));
            $timer_wrapper.find('.minutes').html(prependZero(minutes_total, 2));
            $timer_wrapper.find('.seconds').html(prependZero(seconds_total, 2));
            //$("#milliseconds").html(prependZero(milliseconds, 3));
            $('.ongoing-timer-segment').html( prependZero(hours, 2) + ':' + prependZero(minutes, 2) + ':' + prependZero(seconds, 2));
            $('.ongoing-total-time').html( prependZero(hours_total, 2) + ':' + prependZero(minutes_total, 2) + ':' + prependZero(seconds_total, 2));
        }

        // Prepend zeros to the digits in stopwatch
        function prependZero(time, length) {
            time = new String(time);    // stringify time
            return new Array(Math.max(length - time.length + 1, 0)).join("0") + time;
        }


        return {
            init: function($wrapper){
                $timer_wrapper = $wrapper;


                var totalSec = $timer_wrapper.data('duration');
                totalOverallCounter = parseInt( $timer_wrapper.data('total-time') * 1000 );
                // work out our js timer from this linux epoc time.
                var hours = parseInt( totalSec / 3600 ) % 24;
                var minutes = parseInt( totalSec / 60 ) % 60;
                var seconds = parseInt( totalSec % 60, 10);

                updateTime(hours,minutes,seconds,0);


                /*$start_pause = $timer_wrapper.find('.start_pause');
                $start_pause.click(function(){
                    // Start button
                    if(timer_status == 1){
                        timer_status = 2;
                        $(this).text(ucm.timer.lang.pause);
                        // todo - load in elapsed time from db:
                        updateTime(0,0,0,0);
                    }else if(timer_status == 2){
                        // timer is running, pause it.
                        clearInterval(timeUpdate);
                        timer_status = 3;
                        $(this).text(ucm.timer.lang.resume);
                    }else if(timer_status == 3){
                        // timer is paused.
                        // restart it from previous paused location.
                        prev_hours = parseInt($timer_wrapper.find('.hours').html());
                        prev_minutes = parseInt($timer_wrapper.find('.minutes').html());
                        prev_seconds = parseInt($timer_wrapper.find('.seconds').html());
                        // prev_milliseconds = parseInt($("#milliseconds").html());

                        updateTime(prev_hours, prev_minutes, prev_seconds, prev_milliseconds);

                        $(this).text(ucm.timer.lang.pause);
                    }
                });*/

                // Reset button onClick
                /*$timer_wrapper.find('.reset_time').click(function(){
                    if( confirm('Really?')) {
                        if (timeUpdate) clearInterval(timeUpdate);
                        setStopwatch(0, 0, 0, 0);
                        $start_pause.text(ucm.timer.lang.start);
                    }
                });
                $timer_wrapper.find('.timer_completed').click(function(){
                    // stop timer and save this as a timer segment.

                });*/
            }
        }
    }
};

$(function(){
    ucm.timer.init();
});