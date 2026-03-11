# RF Dicebear

A [Flarum](https://flarum.org) extension that automatically generates and locally saves [Dicebear](https://dicebear.com) avatars for users who have not uploaded a custom avatar.

## How It Works

When a new user registers, the extension fetches a unique avatar from the Dicebear API based on their username and saves it directly to your forum's `assets/avatars` folder — just like a manually uploaded avatar. From that point on, no further API calls are made; Flarum serves the avatar from your own server.

For existing users (e.g. after first installing the extension), the avatar is fetched and saved locally on their first page load. Again, no repeat calls are made after that.

If the Dicebear API is unreachable at the time of fetching, the extension falls back to serving the remote Dicebear URL temporarily so users never see a broken avatar image.

## Installation

```bash
composer require resofire/dicebear
php flarum migrate
```

## Configuration

Navigate to **Admin → Extensions → RF Dicebear** to configure:

- **Avatar Style** — choose from all available Dicebear styles (Adventurer, Bottts, Pixel Art, and many more). Preview styles at [dicebear.com/styles](https://www.dicebear.com/styles/).
- **API Base URL** — defaults to `https://api.dicebear.com`. Change this only if you are running a self-hosted Dicebear instance.

## Notes

- Avatars are saved at 100×100px PNG, consistent with Flarum's own avatar upload behaviour.
- Users who have already uploaded a custom avatar are never affected.
- Registration is never blocked by a network failure — avatar fetching errors are caught silently.

## License

MIT
