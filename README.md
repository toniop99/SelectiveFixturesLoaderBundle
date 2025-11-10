# Andez Selective Fixtures Loader Bundle

A Symfony bundle that lets you interactively (or programmatically) load a *subset* of your Doctrine data fixtures together with all of their dependency chain. Ideal for speeding up development, reproducing bugs, preparing lightweight test databases, or doing dry runs of fixture execution.

## Key Features

- Interactive command to pick which fixtures to load.
- Automatically resolves and loads all dependent fixtures.
- Two ways to define a "base" set of fixtures always included:
  - Provide a service implementing `BaseFixturesLoaderInterface`.
  - Provide a static list of fixture class names in config.
- Supports dry-run execution (see what would happen without modifying the DB).
- Supports appending vs purging the database first.
- Allows excluding specific tables from purge.
- Works with multiple Entity Managers.

## Requirements

- PHP ^8.2
- Symfony 6.0+ or 7.x (Console, Config, DependencyInjection, HttpKernel)
- DoctrineFixturesBundle ^4.3

See `composer.json` for the full list.

## Installation

```bash
composer require andez/selective-fixtures-loader-bundle --dev
```

Register the bundle if Flex did not do it automatically (Symfony < 7 or no recipes):

```php
// config/bundles.php
return [
    // ... other bundles
    Andez\SelectiveFixturesLoaderBundle\SelectiveFixturesLoaderBundle::class => ['dev' => true, 'test' => true],
];
```

Typically you only want this in `dev` (and optionally `test`) environments.

## Configuration

Add a configuration file (e.g. `config/packages/andez_selective_fixtures_loader.yaml`). All keys are optional.

```yaml
andez_selective_fixtures_loader:
  # Exactly one of the following two may be set:
  # base_fixtures_loader_service_id: App\Service\BaseFixtureLoader
  # base_fixtures: ['App\DataFixtures\UserFixtures', 'App\DataFixtures\RoleFixtures']

  purge_exclusion_tables: ['doctrine_migration_versions', 'audit_log']
```

### Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `base_fixtures_loader_service_id` | string FQCN (service id) | null | Service whose class implements `BaseFixturesLoaderInterface` returning an array of base fixtures. Mutually exclusive with `base_fixtures`. |
| `base_fixtures` | array<class-string> | [] | Static list of fixture class names always loaded. Mutually exclusive with `base_fixtures_loader_service_id`. |
| `purge_exclusion_tables` | array<string> | [] | Tables to skip when purging (useful for reference or audit tables). |

### Defining Base Fixtures via Service

Implement the interface:

```php
namespace App\Service;

use Andez\SelectiveFixturesLoaderBundle\BaseFixturesLoaderInterface;

final class BaseFixtureLoader implements BaseFixturesLoaderInterface
{
    public function getBaseFixtures(): array
    {
        return [
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\RoleFixtures::class,
        ];
    }
}
```

In config:

```yaml
andez_selective_fixtures_loader:
  base_fixtures_loader_service_id: App\Service\BaseFixtureLoader
```

### Defining Base Fixtures via Static List

```yaml
andez_selective_fixtures_loader:
  base_fixtures:
    - App\DataFixtures\UserFixtures
    - App\DataFixtures\RoleFixtures
```

## Console Command

The bundle registers: `andez:selective-fixtures:load`.

Run it with no options for an interactive selection:

```bash
php bin/console andez:selective-fixtures:load
```

You will be presented with a multi-select list of all available fixtures (including those provided by DoctrineFixturesBundle). The chosen fixtures plus:

- Their transitive dependencies.
- The configured base fixtures (if any).

will be executed.

### Command Options

| Option | Value Mode | Description |
| ------ | ---------- | ----------- |
| `--fixtures` | Repeatable (array) | One or more fixture FQCNs to load (skips interactive choice). |
| `--append` | Flag | Do not purge the database before loading (data will accumulate). |
| `--dry-run` | Flag | Simulate execution; shows log of what would run without DB changes. |
| `--purge-exclusions` | Repeatable (array) | Extra table names to skip during purge (overrides configured list). |
| `--em` | Value | Specific Entity Manager name (for multi-EM setups). |

Examples:

Load a precise subset:

```bash
php bin/console andez:selective-fixtures:load \
  --fixtures=App\\DataFixtures\\UserFixtures \
  --fixtures=App\\DataFixtures\\RoleFixtures
```

Dry run of a subset:

```bash
php bin/console andez:selective-fixtures:load --fixtures=App\\DataFixtures\\UserFixtures --dry-run
```

Skip purge for faster iterative development:

```bash
php bin/console andez:selective-fixtures:load --fixtures=App\\DataFixtures\\UserFixtures --append
```

Override purge exclusions on the fly:

```bash
php bin/console andez:selective-fixtures:load --purge-exclusions=doctrine_migration_versions --purge-exclusions=audit_log
```

### Safety Prompt

If neither `--append` nor `--dry-run` is used, the command will prompt for confirmation before purging the database. Use `--append` or run non-interactively (e.g., in CI with `yes |`) if you want to skip manual confirmation.

## How Dependencies Are Resolved

Internally the `FixturesDependencies` service gathers:

1. Base fixtures (from service or static list).
2. User-requested fixtures (interactive or via `--fixtures`).
3. All transitive dependencies declared via `DependencyFixtureInterface` / Doctrine's mechanism (leveraging the loader service).

This produces a deduplicated, ordered list that respects dependency order before execution.

## Purge Exclusions

Tables listed in configuration or supplied at runtime with `--purge-exclusions` are passed to Doctrine's `ORMPurgerFactory` so they remain untouched even when not using `--append`.

Common examples: audit tables, lookup/reference data, migration versions, sessions.

## Dry Run Mode

When `--dry-run` is used the bundle utilizes `DryRunORMExecutor`, which outputs the fixture execution flow without changing the database. Useful to:

- Inspect dependency resolution.
- Confirm the set of fixtures that would run.
- Test custom purger exclusion lists.

## Troubleshooting

| Symptom | Cause | Fix |
| ------- | ----- | --- |
| Validation error about both base fixtures options | Both `base_fixtures_loader_service_id` and `base_fixtures` were set | Remove one of them. |
| Class does not exist error | A fixture FQCN in config is wrong | Correct namespace / class name. |
| Service id must implement interface | Provided class for `base_fixtures_loader_service_id` does not implement `BaseFixturesLoaderInterface` | Implement interface or change class. |
| Command not found | Bundle not registered or environment not matching | Check `bundles.php`; ensure running in dev/test. |
| Purge removes an important table | Not excluded | Add to `purge_exclusion_tables` or pass `--purge-exclusions`. |

## Contributing

Issues and PRs welcome. Please:

- Run `composer install`.
- Maintain coding standards (`vendor/bin/phpcs`).
- Add/update tests (`vendor/bin/phpunit`).
- Keep documentation accurate.

## License

MIT. See `LICENSE`.

## Credits

Created and maintained by Antonio Hernandez (andezdev). Contributions are appreciated.

