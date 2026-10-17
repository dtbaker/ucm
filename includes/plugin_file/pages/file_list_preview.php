<div class="file_<?php
echo $owner_table;?>_<?php echo $owner_id;?>">
    <?php
    foreach ($file_items as $file_item) {
        $file_item = self::get_file($file_item['file_id']);
        ?>
        
        <div style="width:110px; height:90px; overflow:hidden; ">

            <?php
            // /display a thumb if its supported.
            if (preg_match('/\.(\w\w\w\w?)$/',$file_item['file_name'],$matches)) {
                switch (strtolower($matches[1])) {
                    case 'jpg':
                    case 'jpeg':
                    case 'gif':
                    case 'png':
                        ?>
                            <img src="<?php echo _BASE_HREF . nl2br(htmlspecialchars($file_item['file_path']));?>" width="100" alt="preview" border="0">
                        <?php
                        break;
                    default:
                        echo 'n/a';
                }
            }
            ?>
        </div>
    <?php
    }
    ?>
</div>