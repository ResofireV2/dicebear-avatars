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
            . '/png?seed='
            . urlencode($user->username);
    }

    public function fetchAndSave(User $user): void
    {
        $url = $this->buildUrl($user);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'resofire-dicebear/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $imageData = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($imageData === false || strlen($imageData) < 100 || $httpCode !== 200) {
            throw new \RuntimeException("Could not fetch avatar (HTTP $httpCode, cURL: $curlError)");
        }

        $avatarDir = $this->paths->public . '/assets/avatars';

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        $filename = Str::random(24) . '.png';
        file_put_contents($avatarDir . '/' . $filename, $imageData);

        User::where('id', $user->id)->update(['avatar_url' => $filename]);

        $user->avatar_url = $filename;
    }
}
