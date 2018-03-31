/*
 * Author: Abdullah A Almsaeed
 * Date: 4 Jan 2014
 * Description:
 *      This is a demo file used only for the main dashboard (index.html)
 **/

$(function() {
    "use strict";

    //Make the dashboard widgets sortable Using jquery UI
    var foo = $(".connectedSortable").sortable({
        placeholder: "sort-highlight",
        connectWith: ".connectedSortable",
        handle: ".box-header, .nav-tabs, .inner",
        forcePlaceholderSize: true,
        zIndex: 999999,
        start: function(){
            $('body').addClass('sorting-ui');
        },
        stop: function(){
            $('body').removeClass('sorting-ui');
        },
        update: function () {
            var sort_order = [];
            $(".connectedSortable").each(function(){
                $(this).attr('data-count',$(this).children('div').length);
                var row_id = $(this).data('row-id');
                var col_number = $(this).data('col-number');
                $(this).find('[data-sort-id]').each(function(){
                    sort_order.push(row_id + '|' + col_number + '|' + $(this).data('sort-id'));
                });
            });
            $.ajax({
                data: {
                    auth: ucm.form_auth_key,
                    theme: 'blocks',
                    sort_order: sort_order
                },
                type: 'POST',
                url: window.location.href
            });

         }
    }).disableSelection();
    $(".connectedSortable .box-header, .connectedSortable .nav-tabs-custom").css("cursor", "move");


});