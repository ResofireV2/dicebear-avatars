<?php

/*
 * This file is part of resofire/dicebear.
 *
 * Copyright (c) 2025 Resofire.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Resofire\Dicebear\Listener;

use Flarum\User\Event\Registered;
use Resofire\Dicebear\AvatarFetcher;

class SaveDicebearAvatarOnRegister
{
    protected AvatarFetcher $fetcher;

    public function __construct(AvatarFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Only assign if no avatar already exists (e.g. from an SSO provider).
        if (!empty($user->getRawOriginal('avatar_url'))) {
            return;
        }

        try {
            $this->fetcher->fetchAndSave($user);
        } catch (\Throwable $e) {
            // Don't block registration if Dicebear is unreachable.
            // The lazy fallback in AddDicebearAvatar will handle it later.
        }
    }
}
