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
use Flarum\User\User;
use Resofire\Dicebear\AvatarFetcher;

class AddDicebearAvatar
{
    protected AvatarFetcher $fetcher;

    public function __construct(AvatarFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        // Already has an avatar — nothing to do.
        if (!empty($attributes['avatarUrl'])) {
            return $attributes;
        }

        try {
            $this->fetcher->fetchAndSave($user);
            // avatarUrl will now be populated on the next serializer pass,
            // but we return the remote URL this one time so the page doesn't break.
            $attributes['avatarUrl'] = $this->fetcher->buildUrl($user);
        } catch (\Throwable $e) {
            // Fall back to remote URL if fetch fails.
            $attributes['avatarUrl'] = $this->fetcher->buildUrl($user);
        }

        return $attributes;
    }
}
