<?php

if(!module_config::can_i('view','Settings') || !module_faq::can_i('edit','FAQ')){
    redirect_browser(_BASE_HREF);
}

$module->page_title = 'FAQ Settings';

$links = array(
    array(
        "name"=>'FAQ Products',
        'm' => 'faq',
        'p' => 'faq_products',
        'force_current_check' => true,
        'order' => 1, // at start.
        'menu_include_parent' => 1,
        'allow_nesting' => 1,
        'args'=>array('faq_id'=>false,'faq_product_id'=>false),
    ),
    array(
        "name"=>'Questions & Answers',
        'm' => 'faq',
        'p' => 'faq_questions',
        'force_current_check' => true,
        'order' => 2, // at start.
        'menu_include_parent' => 1,
        'allow_nesting' => 1,
        'args'=>array('faq_id'=>false,'faq_product_id'=>false),
    ),
    array(
        "name"=>'Settings',
        'm' => 'faq',
        'p' => 'faq_settings_basic',
        'force_current_check' => true,
        'order' => 3, // at start.
        'menu_include_parent' => 1,
        'allow_nesting' => 1,
        'args'=>array('faq_id'=>false,'faq_product_id'=>false),
    ),
);
