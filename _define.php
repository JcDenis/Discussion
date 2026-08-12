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

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Discussion',
    'Allow user to post from frontend.',
    'Jean-Christian Paul Denis and Contributors',
    '1.18',
    [
        'requires' => [
            ['core', '2.39'],
            ['FrontendSession', '0.40'],
            ['commentsWikibar', '7.7'], // optional
            ['legacyMarkdown', '10.1'], // optional
            //['ReadingTracking', '0.13'], // optional
        ],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-08-12T20:50:45+00:00',
    ]
);
