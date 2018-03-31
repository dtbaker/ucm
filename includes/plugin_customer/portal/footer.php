
<!-- /. PAGE WRAPPER  -->
</div>
<div class="footer">


	<div class="row">
		<div class="col-lg-12" >
		</div>
	</div>
</div>

<?php module_config::print_js(); ?>
<script>


    (function ($) {
        "use strict";
        var mainApp = {

            main_fun: function () {

                /*====================================
				  LOAD APPROPRIATE MENU BAR
			   ======================================*/
                $(window).bind("load resize", function () {
                    if ($(this).width() < 768) {
                        $('div.sidebar-collapse').addClass('collapse')
                    } else {
                        $('div.sidebar-collapse').removeClass('collapse')
                    }
                });

                $('#main-menu a').click(function(){

                    var section = $(this).attr('href').replace('#','');
                    $('div.section').removeClass('active');
                    $('div.section[data-section="' + section + '"]').addClass('active');
                    $('#main-menu a.active-link').removeClass('active-link');
                    $(this).addClass('active-link');

                    return false;
                });


            },

            initialization: function () {
                mainApp.main_fun();

            }

        }
        // Initializing ///

        $(document).ready(function () {
            mainApp.main_fun();
        });

    }(jQuery));
</script>

</body>
</html>


