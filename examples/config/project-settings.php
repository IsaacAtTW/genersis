<?php
return [
    'project_name' => 'GEN', // optional, default: ''
    'project_namespace' => 'GEN', // optional, default: \ (global)
    'cookie_expire' => 2592000, // optional, default: 2592000 (30 * 24 * 60 * 60)
    'template_path' => '/var/www/html/templates/', // Renderer settings
    'slim' => [
        'settings' => [
            'addContentLengthHeader' => false // Allow the web server to send the content-length header
        ]
    ]
];
