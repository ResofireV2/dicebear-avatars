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

use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Str;

class AvatarFetcher
{
    protected SettingsRepositoryInterface $settings;
    protected Paths $paths;

    public function __construct(SettingsRepositoryInterface $settings, Paths $paths)
    {
        $this->settings = $settings;
        $this->paths = $paths;
    }

    public function buildUrl(User $user): string
    {
        return rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
            . '/9.x/'
            . $this->settings->get('resofire-dicebear.avatar_style')
            . '/svg?seed='
            . urlencode($user->username);
    }

    public function fetchAndSave(User $user): void
    {
        $url = $this->buildUrl($user);

        // Fetch the PNG from Dicebear — it's already being displayed
        // from this URL, so we know it works.
        $imageData = file_get_contents($url);

        if ($imageData === false || strlen($imageData) < 100) {
            throw new \RuntimeException("Could not fetch avatar from: $url");
        }

        // Write it directly into assets/avatars.
        $filename = Str::random(24) . '.svg';
        $avatarDir = $this->paths->public . '/assets/avatars';

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        file_put_contents($avatarDir . '/' . $filename, $imageData);

        // Update the database directly — no events, no Eloquent lifecycle.
        User::where('id', $user->id)->update(['avatar_url' => $filename]);

        // Keep the in-memory model in sync.
        $user->avatar_url = $filename;
    }
}
