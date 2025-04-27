<?php

/**
 * @file
 * @brief       The plugin Discussion definition
 * @ingroup     Discussion
 *
 * @defgroup    Discussion Plugin Discussion.
 *
 * Allow user to post from frontend.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Discussion',
    'Allow user to post from frontend.',
    'Jean-Christian Paul Denis and Contributors',
    '0.1',
    [
        'requires'    => [
            ['core', '2.34'],
            ['FrontendSession', '0.18'],
        ],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-04-25T12:41:36+00:00',
    ]
);
