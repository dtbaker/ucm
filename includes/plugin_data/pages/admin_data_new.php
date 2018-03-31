<?php

$data_types = $module->get_data_types();

?>
<h2><?php echo _l('Select Type'); ?></h2>

<?php foreach($data_types as $data_type){
	?>
	
	<a class="uibutton" href="<?php echo $module->link('',array('data_type_id'=>$data_type['data_type_id'],'data_record_id'=>'new','mode'=>'edit'));?>"><?php echo $data_type['data_type_name'];?></a>
	
	<?php
}
?>
