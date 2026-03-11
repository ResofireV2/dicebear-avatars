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
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class AvatarFetcher
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarUploader $uploader;
    protected ImageManager $imageManager;
    protected $uploadDir;

    public function __construct(
        SettingsRepositoryInterface $settings,
        AvatarUploader $uploader,
        ImageManager $imageManager,
        Factory $filesystemFactory
    ) {
        $this->settings = $settings;
        $this->uploader = $uploader;
        $this->imageManager = $imageManager;
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
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
     * Fetch the Dicebear PNG, write it directly to assets/avatars,
     * update the user's avatar_url column, and save.
     *
     * @throws \RuntimeException if the HTTP request fails or returns bad data.
     */
    public function fetchAndSave(User $user): void
    {
        $url = $this->buildUrl($user);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header'  => "User-Agent: resofire-dicebear/1.0\r\n",
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);

        if ($imageData === false || strlen($imageData) < 100) {
            throw new \RuntimeException("Failed to fetch Dicebear avatar from: $url");
        }

        // Validate it's a real image.
        $image = $this->imageManager->make($imageData);

        if ($image->width() === 0 || $image->height() === 0) {
            throw new \RuntimeException("Dicebear returned an invalid image for: $url");
        }

        // Resize and encode exactly as AvatarUploader does.
        $encodedImage = $image->fit(100, 100)->encode('png');

        // Generate a unique filename and write directly to the avatars disk.
        $avatarPath = Str::random() . '.png';
        $this->uploadDir->put($avatarPath, $encodedImage);

        // Update the user record directly — no events, no middleware.
        User::where('id', $user->id)->update(['avatar_url' => $avatarPath]);

        // Keep the in-memory model consistent.
        $user->avatar_url = $avatarPath;
    }
}
