ucm.backup = {
    file_list: [],
    database_list: [],
    backup_url: '',
    backup_post_data: {},
    backup_delay: 1000,
    lang:{
        pending:'Pending',
        process:'Processing',
        success:'Successfully backed up %s items',
        error:'Error',
        retry:'Retrying...'
    },
    init: function(){
        var db = $('#database_backup');
        for(var i in ucm.backup.database_list){
            if(ucm.backup.database_list.hasOwnProperty(i) && typeof ucm.backup.database_list[i].name != 'undefined'){
                ucm.backup.database_list[i].html = $('<li><span class="database_table">'+ucm.backup.database_list[i].name+'</span> <span class="backup_status">'+ucm.backup.lang.pending+'</span></li>');
                db.append(ucm.backup.database_list[i].html);
            }
        }
        var fs = $('#files_backup');
        for(var i in ucm.backup.file_list){
            if(ucm.backup.file_list.hasOwnProperty(i) && typeof ucm.backup.file_list[i].name != 'undefined'){
                ucm.backup.file_list[i].html = $('<li><span class="file_name">'+ucm.backup.file_list[i].name+'</span> <span class="backup_status">'+ucm.backup.lang.pending+'</span></li>');
                fs.append(ucm.backup.file_list[i].html);
            }
        }
    },
    start_backup: function(){
        this.backup_next_database();
        //this.backup_next_file();
    },
    backup_database_index: 0,
    backup_next_database: function(){
        if(typeof ucm.backup.database_list[ucm.backup.backup_database_index] != 'undefined'){
            $('.backup_status',ucm.backup.database_list[ucm.backup.backup_database_index].html).html(ucm.backup.lang.process);
            // ajax this one and wait for it to finish.
            var post_data = {};
            for(var i in ucm.backup.backup_post_data){
                if(ucm.backup.backup_post_data.hasOwnProperty(i)){
                    post_data[i] = ucm.backup.backup_post_data[i];
                }
            }
            post_data.backup_type = 'database';
            post_data.name = ucm.backup.database_list[ucm.backup.backup_database_index].name;
            $.ajax({
                url: ucm.backup.backup_url + (ucm.backup.backup_url.indexOf('?') > 0 ? '&' : '?' ) + (new Date).getTime(),
                type: 'POST',
                dataType: 'json',
                data: post_data,
                success: function(d){
                    // did it work? update the status..
                    if(typeof d.retry != 'undefined'){
                        $('.backup_status',ucm.backup.database_list[ucm.backup.backup_database_index].html).html(ucm.backup.lang.retry);
                        setTimeout(function(){
                            ucm.backup.backup_next_database();
                        },5000);
                        return;
                    }else if(typeof d.count != 'undefined'){
                        $('.backup_status',ucm.backup.database_list[ucm.backup.backup_database_index].html).html(ucm.backup.lang.success.replace('%s', d.count)).addClass('success');
                    }else{
                        $('.backup_status',ucm.backup.database_list[ucm.backup.backup_database_index].html).html(ucm.backup.lang.error).addClass('error');
                    }
                    ucm.backup.backup_database_index++;
                    setTimeout(function(){
                            ucm.backup.backup_next_database();
                        },ucm.backup.backup_delay);
                    //ucm.backup.backup_next_database();
                },
                error: function(d){
                    alert('Failed to backup this database table ('+ucm.backup.database_list[ucm.backup.backup_database_index].name+'). Maybe it is too large? Skipping to next table...');
                    $('.backup_status',ucm.backup.database_list[ucm.backup.backup_database_index].html).html(ucm.backup.lang.error).addClass('error');
                    ucm.backup.backup_database_index++;
                    ucm.backup.backup_next_database();
                }
            });

        }else{
            // finished backing up all available databases.
            ucm.backup.completed_backup('database');
        }
    },
    backup_file_index: 0,
    backup_next_file: function(){
        if(typeof ucm.backup.file_list[ucm.backup.backup_file_index] != 'undefined'){
            $('.backup_status',ucm.backup.file_list[ucm.backup.backup_file_index].html).html(ucm.backup.lang.process);
            // ajax this one and wait for it to finish.
            var post_data = {};
            for(var i in ucm.backup.backup_post_data){
                if(ucm.backup.backup_post_data.hasOwnProperty(i)){
                    post_data[i] = ucm.backup.backup_post_data[i];
                }
            }
            post_data.backup_type = 'file';
            post_data.path = ucm.backup.file_list[ucm.backup.backup_file_index].path;
            post_data.recurisive = ucm.backup.file_list[ucm.backup.backup_file_index].recurisive;
            $.ajax({
                url: ucm.backup.backup_url + (ucm.backup.backup_url.indexOf('?') > 0 ? '&' : '?' ) + (new Date).getTime(),
                type: 'POST',
                dataType: 'json',
                data: post_data,
                success: function(d){
                    // did it work? update the status..
                    if(typeof d.retry != 'undefined'){
                        $('.backup_status',ucm.backup.file_list[ucm.backup.backup_file_index].html).html(ucm.backup.lang.retry);
                        setTimeout(function(){
                            ucm.backup.backup_next_file();
                        },5000);
                        return;
                    }else if(typeof d.count != 'undefined'){
                        $('.backup_status',ucm.backup.file_list[ucm.backup.backup_file_index].html).html(ucm.backup.lang.success.replace('%s', d.count)).addClass('success');
                    }else{
                        $('.backup_status',ucm.backup.file_list[ucm.backup.backup_file_index].html).html(ucm.backup.lang.error).addClass('error');
                    }
                    ucm.backup.backup_file_index++;
                    setTimeout(function(){
                            ucm.backup.backup_next_file();
                        },ucm.backup.backup_delay);
                },
                error: function(d){
                    alert('Failed to backup this folder ('+ucm.backup.file_list[ucm.backup.backup_file_index].path+'). Maybe it is too large? Skipping to next folder...');
                    $('.backup_status',ucm.backup.file_list[ucm.backup.backup_file_index].html).html(ucm.backup.lang.error).addClass('error');
                    ucm.backup.backup_file_index++;
                    ucm.backup.backup_next_file();
                }
            });
        }else{
            // finished backing up all available databases.
            ucm.backup.completed_backup('file');
        }
    },
    completed_backup_count:0,
    completed_backup: function(type){
        this.completed_backup_count++;
        if(this.completed_backup_count == 1){
            // start the file backup process..
            this.backup_next_file();
        }
        if(this.completed_backup_count>=2){
            // finished both files and database backup. refresh the page.
            window.location.href = window.location.href + '&completed';
        }
    }
};