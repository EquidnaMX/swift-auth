# PHPStan Configuration

This project uses PHPStan for static analysis to catch potential bugs and type issues.

## Configuration Files

- **phpstan.neon** - Current configuration that works with basic PHPStan
- **phpstan.neon.dist** - Larastan-ready configuration (requires Larastan installation)

## Running PHPStan

### Using the included PHAR

```bash
php phpstan.phar analyse --configuration=phpstan.neon
```

### Using Composer (if installed globally)

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon
```

## Larastan (Recommended)

For better Laravel framework support, install Larastan:

```bash
composer require --dev larastan/larastan
```

Once installed, uncomment the include line in `phpstan.neon.dist`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
```

Then use the dist configuration:

```bash
php phpstan.phar analyse --configuration=phpstan.neon.dist
```

## Current Configuration

The current `phpstan.neon` file:
- Runs at level 5 analysis
- Bootstraps with vendor/autoload.php for proper autoloading
- Uses `universalObjectCratesClasses` to help PHPStan understand Laravel's Model and Request magic
- Excludes view files from analysis
- Ignores external toolkit dependencies (Equidna\Toolkit)
- Ignores Laravel's magic methods that PHPStan can't properly analyze without Larastan

## Notes

- The configuration ignores errors from the external `Equidna\Toolkit` dependency
- Laravel's Eloquent magic methods (find, where, create, etc.) are suppressed until Larastan is installed
- Facade method calls are ignored until Larastan provides proper support
