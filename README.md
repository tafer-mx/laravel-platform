# TAFER Laravel Platform  🐙

Shared logic for TAFER Laravel projects.

---

## Storyblok

See [Storyblok cache architecture](docs/storyblok-cache.md) for configuration, request flow, webhook invalidation, and extension points.

Run the deterministic workbench example:

```bash
composer serve
```

Then open [http://localhost:6969/es/puerto-vallarta/storyblok-cache-demo](http://localhost:6969/es/puerto-vallarta/storyblok-cache-demo).

For a real Storyblok request, configure the workbench environment and open the public
slug. For example:

```dotenv
TAFER_BRAND_SLUG=villa-palmar-cancun
STORYBLOK_TOKEN=your_public_token
STORYBLOK_CACHE_ENABLED=true
```

Then open [http://localhost:6969/home-villa-palmar-cancun](http://localhost:6969/home-villa-palmar-cancun).

---

## Workflow & Releases

### Branch Strategy

#### `main`
Stable branch.  
Only production-ready code lives here.  
Every change here should be taggable.

#### `dev`
Integration branch.  
All features are merged here before becoming a release.

##### By Format
```sh
<type>/<short-description>
```

##### Common Types

- `feat` → New functionality
- `fix` → Bug fixes
- `chore` → Core/internal changes
- `refactor` → Refactor changes
- `docs` → Documentation changes
- `test` → Tests changes
Examples:

```bash
feat/resort-enums
feat/storyblok-integration
test/resort-enums
docs/readme-workflow
fix/location-label
refactor/resort-structure
chore/update-dependencies
```

## Development Flow

1. Create a feature branch
```bash
git checkout dev
git pull origin dev
git checkout -b feat/my-feature
```

2. Work locally
Run tests:
```bash
composer test
```

3. Open PR -> `dev`
```bash
feat/my-feature -> dev
```
requirements:
- Tests passing (CI)
- Code review approved
- No conflicts

4. Merge into dev
Once approved, merge the PR.

## Release Flow

5. Open PR from `dev` to `main`
```sh
dev -> main
```
This PR represents a new release.

Checklist:
- Tests passing
- Final review
- Validate included changes

6. Merge into `main`
After approval, merge the PR.

7. Create a version tag
Tags define the versions consumed by other projects

```sh
git checkout main
git pull origin main

git tag v0.0.1
git push origin v0.0.1
```

Future releases:

```sh
git tag v0.0.2
git push origin v0.0.2
```
and so on.

## Versioning
Follow semantic versioning loosely:

```txt
vMAJOR.MINOR.PATCH

v0.0.1 → initial
v0.1.0 → new features
v0.1.1 → fixes
```

## Installation (Consumer Projects)
1. Add repository
In the Laravel project's `composer.json`
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:tafer-mx/laravel-platform.git"
    }
  ]
}
```
2. Require the package
```sh
composer require tafer-mx/laravel-platform
```
