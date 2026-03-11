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
use Resofire\Dicebear\AvatarFetcher;

class AddDicebearAvatar
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarFetcher $fetcher;

    public function __construct(SettingsRepositoryInterface $settings, AvatarFetcher $fetcher)
    {
        $this->settings = $settings;
        $this->fetcher = $fetcher;
    }

    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        // If the user already has a locally saved avatar, do nothing.
        if (!empty($attributes['avatarUrl'])) {
            return $attributes;
        }

        // Try to download and save locally (lazy fallback for existing users).
        try {
            $this->fetcher->fetchAndSave($user);

            // avatar_url is now a filename like "abc123.png".
            // getAvatarUrlAttribute() converts it to a full public URL.
            $attributes['avatarUrl'] = $user->avatar_url;
        } catch (\Throwable $e) {
            // Fetching failed — serve the remote URL temporarily.
            // Will retry on next page load.
            $attributes['avatarUrl'] = $this->fetcher->buildUrl($user);
        }

        return $attributes;
    }
}
