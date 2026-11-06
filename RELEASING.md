# Publishing diephp/telegraph

## Identity and Versioning

The repository is https://github.com/diephp/telegraph. The repository at
https://github.com/defstudio/telegraph is upstream, not this fork.

Publish as `diephp/telegraph`. Keep the PHP namespace `DefStudio\Telegraph`.
The package explicitly conflicts with `defstudio/telegraph`; it does not claim
to replace the full upstream API.

The first independent release is **v2.0.0** because this fork removes public APIs
and changes webhook authentication/action dispatch. The inherited v1.* tags belong
to upstream history. Do not move or reuse any inherited tag.

Composer reads the version from Git tags. Do not add `version` to composer.json.

## Prepare a Release

1. Review and commit the release changes on main.
2. Run `composer validate --strict`, `composer test` and `composer audit`.
3. Confirm the Laravel/PHP CI matrix is green.
4. Replace `Unreleased` in the 2.0.0 changelog entry with the release date.
5. Change README's `Next release` label to `Current release`.
6. Commit those final documentation changes before creating the tag.

From the reviewed release commit:

```bash
git tag -a v2.0.0 -m "diephp/telegraph 2.0.0"
git push origin main
git push origin v2.0.0
```

Create a GitHub release from this tag if desired. A GitHub release page is
optional for Composer; the tag and Packagist indexing are what make it installable.

## Register With Packagist Once

1. Sign in to https://packagist.org using the GitHub account that maintains the fork.
2. Open https://packagist.org/packages/submit.
3. Submit `https://github.com/diephp/telegraph`.
4. Confirm the detected package name is `diephp/telegraph`.
5. Configure Packagist's GitHub auto-update integration, or use Update on the package page after pushing tags.
6. Confirm v2.0.0 is listed as a stable version.

Once indexed, no VCS repository entry is necessary in applications:

```bash
composer require diephp/telegraph:^2.0
```

There is no Composer upload command and no need to publish this fork under the
already occupied `defstudio/telegraph` name.

## Migrate an Existing Consumer

Only do this after the release is indexed on Packagist:

```bash
composer remove defstudio/telegraph --no-update
composer require diephp/telegraph:^2.0 --no-update
composer update diephp/telegraph defstudio/telegraph --with-all-dependencies
```

Remove an obsolete fork-specific VCS repository entry. Preserve PHP imports and
custom model inheritance, but update hard-coded vendor paths and any
`extra.laravel.dont-discover` entry to the new Composer name. Keep the existing
database schema if it is already customized; do not publish duplicate migrations.

Configure `TELEGRAPH_WEBHOOK_SECRET`, declare each handler's `$allowedActions`,
clear/rebuild the application's config cache as appropriate, then re-register its
webhook. Review each callback's user/chat/tenant authorization before rollout.

## Future Versions

Use v2.0.1 for compatible fixes and v2.1.0 for compatible features. Breaking
changes require a new major version. Always create a new immutable tag.

References: https://packagist.org/about and
https://getcomposer.org/doc/04-schema.md#version.
