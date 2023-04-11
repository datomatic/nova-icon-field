<?php

declare(strict_types=1);

return [
    'disk' => 'nova-icon-field',

    'route' => [
        'prefix' => 'nova-icon-field',
        'name' => 'nova-icon-field',


        // middleware applied to the routes used in nova (which should require that the user has access to nova)
        'nova_middleware' => '',
        // headers added to each request made to the routes used in nova
        'nova_headers' => [],

        // middleware applied to the icon route used to obtain the svg (which can potentially be used also from frontend)
        'icon_middleware' => '',
        // query params added to each request made to the routes used to obtain the svg
        'icon_params' => [],
    ],

    // prefix and suffix used on (de-)hydration of the style
    'style_prefix' => '',
    'style_suffix' => '',

    // prefix and suffix used on (de-)hydration of the icon
    'icon_prefix' => '',
    'icon_suffix' => '',
];
