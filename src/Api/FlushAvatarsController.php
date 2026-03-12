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

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FlushAvatarsController implements RequestHandlerInterface
{
    protected Paths $paths;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $avatarDir = $this->paths->public . '/assets/avatars';

        // Our extension prefixes all saved filenames with "dicebear_".
        // This makes them unambiguously ours and safe to target.

        // Step 1: Collect our filenames from the DB.
        $ourFiles = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'like', 'dicebear_%')
            ->pluck('avatar_url')
            ->toArray();

        // Step 2: Clear those DB records.
        $affected = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'like', 'dicebear_%')
            ->update(['avatar_url' => null]);

        // Step 3: Delete the files from disk.
        $filesDeleted = 0;
        foreach ($ourFiles as $filename) {
            $filepath = $avatarDir . '/' . basename($filename);
            if (is_file($filepath)) {
                unlink($filepath);
                $filesDeleted++;
            }
        }

        // Step 4: Also clean up any orphaned dicebear_ files on disk
        // that may no longer have a DB record (e.g. from earlier testing).
        if (is_dir($avatarDir)) {
            foreach (scandir($avatarDir) as $filename) {
                if (strpos($filename, 'dicebear_') !== 0) {
                    continue;
                }
                $filepath = $avatarDir . '/' . $filename;
                if (is_file($filepath)) {
                    unlink($filepath);
                    $filesDeleted++;
                }
            }
        }

        return new JsonResponse([
            'flushed'      => $affected,
            'filesDeleted' => $filesDeleted,
        ]);
    }
}
