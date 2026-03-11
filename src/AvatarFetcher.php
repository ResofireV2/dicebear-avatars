<?php

/*
 * This file is part of resofire/dicebear.
 *
 * Copyright (c) 2025 Resofire.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Resofire\Dicebear;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AvatarUploader;
use Flarum\User\User;
use Intervention\Image\ImageManager;

class AvatarFetcher
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarUploader $uploader;
    protected ImageManager $imageManager;

    public function __construct(
        SettingsRepositoryInterface $settings,
        AvatarUploader $uploader,
        ImageManager $imageManager
    ) {
        $this->settings = $settings;
        $this->uploader = $uploader;
        $this->imageManager = $imageManager;
    }

    /**
     * Build the Dicebear URL for a given user.
     */
    public function buildUrl(User $user): string
    {
        return rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
            . '/9.x/'
            . $this->settings->get('resofire-dicebear.avatar_style')
            . '/png?seed='
            . urlencode($user->username);
    }

    /**
     * Fetch the Dicebear PNG via Intervention Image (mirrors Flarum core's
     * own uploadAvatarFromUrl), save to assets/avatars, and persist to DB.
     */
    public function fetchAndSave(User $user): void
    {
        $url = $this->buildUrl($user);

        // Intervention Image fetches the URL itself — exactly as Flarum core
        // does in RegisterUserHandler::uploadAvatarFromUrl().
        $image = $this->imageManager->make($url);

        // upload() resizes to 100x100, writes the file to assets/avatars,
        // and calls $user->changeAvatarPath() to set avatar_url in memory.
        $this->uploader->upload($user, $image);

        // Persist the new avatar_url to the database.
        $user->save();
    }
}
