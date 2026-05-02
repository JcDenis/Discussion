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
    '1.17',
    [
        'requires' => [
            ['core', '2.37'],
            ['FrontendSession', '0.40'],
            ['commentsWikibar', '7.7'], // optional
            ['legacyMarkdown', '10.1'], // optional
            //['ReadingTracking', '0.13'], // optional
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
        'date'       => '2026-05-02T09:13:25+00:00',
    ]
);
