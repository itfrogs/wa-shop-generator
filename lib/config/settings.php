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
);
