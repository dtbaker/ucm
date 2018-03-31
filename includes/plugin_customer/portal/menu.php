<!-- /. NAV TOP  -->
<nav class="navbar-default navbar-side" role="navigation">
	<div class="sidebar-collapse">
		<ul class="nav" id="main-menu">


			<li><a href="#dashboard" class="active-link"><i class="fa fa-desktop "></i>Details</a></li>
			<?php if ( $contracts && portal_is_visible( 'Contracts', $customer_id ) ) { ?>
				<li><a href="#contracts"><i class="fa fa-desktop "></i>Contracts</a></li>
			<?php } ?>
			<?php if ( $websites && portal_is_visible( 'Websites', $customer_id ) ) { ?>
				<li><a href="#websites"><i class="fa fa-desktop "></i>Websites</a></li>
			<?php } ?>
			<?php if ( $quotes && portal_is_visible( 'Quotes', $customer_id ) ) { ?>
				<li><a href="#quotes"><i class="fa fa-desktop "></i>Quotes</a></li>
			<?php } ?>
			<?php if ( $jobs && portal_is_visible( 'Jobs', $customer_id ) ) { ?>
				<li><a href="#jobs"><i class="fa fa-desktop "></i>Jobs</a></li>
			<?php } ?>
			<?php if ( $invoices && portal_is_visible( 'Invoices & Payments', $customer_id ) ) { ?>
				<li><a href="#invoices"><i class="fa fa-desktop "></i>Invoices</a></li>
			<?php } ?>
			<?php if ( $tickets && portal_is_visible( 'Tickets', $customer_id ) ) { ?>
				<li><a href="#tickets"><i class="fa fa-desktop "></i>Tickets</a></li>
			<?php } ?>
			<?php if ( $timers && portal_is_visible( 'Timers', $customer_id ) ) { ?>
				<li><a href="#timers"><i class="fa fa-desktop "></i>Timers</a></li>
			<?php } ?>
			<?php if ( portal_is_visible( 'Shop', $customer_id ) ) { ?>
				<li><a href="#shop"><i class="fa fa-desktop "></i>Shop</a></li>
			<?php } ?>


		</ul>
	</div>

</nav>