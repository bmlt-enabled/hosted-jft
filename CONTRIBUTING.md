# Contributing to Hosted JFT

## How to Contribute

1. Fork the repository
2. Make your changes
3. Send a pull request to the `main` branch

Take a look at the [issues](https://github.com/bmlt-enabled/hosted-jft/issues) for bugs that you might be able to help fix.

Once your pull request is merged, it will be released in the next version.

## Local Development Setup

1. Start the development environment:
   ```bash
   make dev
   ```
   (equivalent to `docker compose up` — WordPress on http://localhost:8080, HTTPS on https://localhost:7443)

2. Set up your WordPress installation and remember your admin password.

3. Log in to the admin panel and activate the **Hosted JFT** plugin.

4. Edit `hosted-jft-plugin.php`; changes take effect instantly (the repo is mounted into the container).

## Code Standards

Please note the `.editorconfig` file and adhere to it to minimize formatting errors. If you use PHPStorm, install the EditorConfig plugin.

### PHP Code Style

This project uses PHP CodeSniffer (phpcs) with the **WordPress-Core** standard, configured in `.phpcs.xml`.

Check for style violations:
```bash
make lint
```

Auto-fix style issues:
```bash
make fmt
```

Run both before submitting a pull request.

## Testing

This project uses [PHPUnit](https://phpunit.de/) with the [WordPress test suite](https://github.com/wp-phpunit/wp-phpunit) for integration testing. Tests run inside Docker containers, so you don't need PHP or MySQL installed locally.

### Running Tests Locally

```bash
make test
```

This spins up a MariaDB container, installs WordPress core, and runs the full suite. Containers are cleaned up automatically afterward.

Remove test images and volumes:
```bash
make test-clean
```

### Writing Tests

Test files live in `tests/` with a `test-` prefix (e.g., `tests/test-feature.php`). Tests extend `WP_UnitTestCase`, giving you a full WordPress environment with database access.

```php
class Test_My_Feature extends WP_UnitTestCase {

    public function test_something() {
        $this->assertTrue( shortcode_exists( 'hosted_jft' ) );
    }
}
```

### CI

Lint and tests run automatically on every pull request and push to `main`. Both must pass before a build or release is created.

## Release Tagging

- If a release tag contains `beta`, it is published as a zip file in the GitHub release only.
- Otherwise it is published to the WordPress.org plugin directory in addition to GitHub.
