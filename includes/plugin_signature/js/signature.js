$(function(){

});

ucm.signature = {
    lang:{
        'Save' : 'Save',
        'Cancel' : 'Cancel',
        title : 'Signature'
    },
    ajax_url: '',
    ajax_data: {},
    init: function(){
        $('body')
            .append('<div id="signature_popup" title="'+ucm.signature.lang.title+'"><div id="signature_popup_inner"></div></div>')
            .delegate('.signature_popup_link','click',function(){
                ucm.signature.ajax_data = {
                    job_id: $(this).data('ajax_job_id'),
                    task_id: $(this).data('ajax_task_id')
                };
                $('#signature_popup').dialog('open');
                return false;
            });
        $("#signature_popup").dialog({
			autoOpen: false,
			height: 600,
			width: 400,
			modal: true,
			buttons: {
				'Save': function() {
                    if($('#signature_popup_inner').find('form').length) {
                        $.ajax({
                            type: 'POST',
                            url: ucm.signature.ajax_url,
                            data: $('#signature_popup_inner').find('form').serialize(),
                            dataType: 'json',
                            success: function (h) {
                                if (typeof h.message != 'undefined') {
                                    alert(h.message);
                                }
                                if (!h || h.error) {

                                } else {
                                    window.location.reload();
                                    //$('#signature_popup').dialog('close');
                                }
                            }
                        });
                    }else{
                        $('#signature_popup').dialog('close');
                    }
				},
                'Cancel': function() {
					$(this).dialog('close');
				}
			},
			open: function(){
				$.ajax({
					type: "POST",
                    url: ucm.signature.ajax_url,
                    data: ucm.signature.ajax_data,
					dataType: "html",
					success: function(d){
						$('#signature_popup_inner').html(d);

					}
				});
			},
			beforeclose: function(){
				return true;
			},
			close: function() {
				$('#signature_popup_inner').html('');
			}
		});
    }
};