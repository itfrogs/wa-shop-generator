<?php
return array (
    'image_width' => array(
        'value' => "300",
        'control_type' => 'text',
        'title' => _wp('Product image width'),
        'description' => _wp("Set the product image width."),
    ),
    'image_height' => array(
        'value' => "300",
        'control_type' => 'text',
        'title' => _wp('Product image height'),
        'description' => _wp("Set the product image height."),
    ),
    'background' => array(
        'value' => "0",
        'control_type' => waHtmlControl::CHECKBOX,
        'title' => _wp('Render background'),
        'description' => _wp("Check, if you want render image background."),
    ),
    'feedback' => array(
        'title' => _wp('Ask for technical support'),
        'description'   => _wp('Click on the link to contact the developer.'),
        'control_type' => waHtmlControl::CUSTOM.' '.'shopGeneratorPlugin::getFeedbackControl'
    ),
    'hint' => array(
        'control_type' => waHtmlControl::CUSTOM . ' ' . 'shopGeneratorPlugin::settingCustomControlHint',
    ),
);
