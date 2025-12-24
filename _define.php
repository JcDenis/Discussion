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
    '1.13.5',
    [
        'requires'    => [
            ['core', '2.36'],
            ['FrontendSession', '0.30'],
            ['commentsWikibar', '6.4'], // optional
            ['legacyMarkdown', '7.8'], // optional
            //['ReadingTracking', '0.4'], // optional
        ],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-12-24T14:24:45+00:00',
    ]
);
