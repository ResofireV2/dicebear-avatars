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
        $log = $this->paths->storage . '/logs/dicebear-debug.log';
        $write = function(string $msg) use ($log) {
            file_put_contents($log, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
        };

        $write("fetchAndSave called for user: {$user->username} (id: {$user->id})");

        $url = $this->buildUrl($user);
        $write("Fetching URL: $url");

        $imageData = file_get_contents($url);

        if ($imageData === false || strlen($imageData) < 100) {
            $write("FAILED: could not fetch from $url");
            throw new \RuntimeException("Could not fetch avatar from: $url");
        }

        $write("Fetched " . strlen($imageData) . " bytes");

        $filename = Str::random(24) . '.svg';
        $avatarDir = $this->paths->public . '/assets/avatars';

        $write("Avatar dir: $avatarDir");
        $write("Dir exists: " . (is_dir($avatarDir) ? 'yes' : 'no'));
        $write("Dir writable: " . (is_writable($avatarDir) ? 'yes' : 'no'));

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
            $write("Created dir: $avatarDir");
        }

        $result = file_put_contents($avatarDir . '/' . $filename, $imageData);
        $write("file_put_contents result: " . var_export($result, true));

        User::where('id', $user->id)->update(['avatar_url' => $filename]);
        $write("DB updated with avatar_url: $filename");

        $user->avatar_url = $filename;
        $write("Done.");
    }
}
