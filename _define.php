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
    '1.15',
    [
        'requires' => [
            ['core', '2.36'],
            ['FrontendSession', '0.30'],
            ['commentsWikibar', '7.5'], // optional
            ['legacyMarkdown', '7.8'], // optional
            //['ReadingTracking', '0.4'], // optional
        ],
        // @phpstan-ignore binaryOp.invalid
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        // @phpstan-ignore binaryOp.invalid
        'support' => 'https://github.com/JcDenis/' . $this->id . '/issues',
        // @phpstan-ignore binaryOp.invalid
        'details' => 'https://github.com/JcDenis/' . $this->id . '/',
        // @phpstan-ignore binaryOp.invalid
        'repository' => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'       => '2026-04-25T06:10:44+00:00',
    ]
);
