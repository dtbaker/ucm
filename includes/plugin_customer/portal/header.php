<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>Portal: <?php echo htmlspecialchars( $customer_data['customer_name'] ); ?></title>


	<?php
	module_config::print_css();
	?>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'/>


	<style type="text/css">


		<?php include('assets/css/portal.css'); ?>


	</style>

</head>
<body>


<div id="wrapper">
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="adjust-nav">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">
					<?php echo htmlspecialchars( $customer_data['customer_name'] ); ?>
				</a>
			</div>
		</div>
	</div>

