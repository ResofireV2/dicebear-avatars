<?php

/*
 * This file is part of resofire/dicebear.
 *
 * Copyright (c) 2025 Resofire.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Resofire\Dicebear\Api;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use Resofire\Dicebear\AvatarFetcher;

class AddDicebearAvatar
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarFetcher $fetcher;
    protected $uploadDir;

    public function __construct(
        SettingsRepositoryInterface $settings,
        AvatarFetcher $fetcher,
        Factory $filesystemFactory
    ) {
        $this->settings = $settings;
        $this->fetcher = $fetcher;
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
    }

    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        // If the user already has a locally saved avatar, do nothing.
        if (!empty($attributes['avatarUrl'])) {
            return $attributes;
        }

        // Try to download and save locally now (lazy fallback for existing users).
        try {
            $this->fetcher->fetchAndSave($user);

            // Build the full public URL from the stored filename.
            $attributes['avatarUrl'] = $this->uploadDir->url($user->avatar_url);
        } catch (\Throwable $e) {
            // Fetching failed — fall back to remote URL so user still sees an avatar.
            // Will retry on next page load.
            $attributes['avatarUrl'] = rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
                . '/9.x/'
                . $this->settings->get('resofire-dicebear.avatar_style')
                . '/png?seed='
                . urlencode($user->username);
        }

        return $attributes;
    }
}
