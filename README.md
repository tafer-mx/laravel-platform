# TAFER Laravel Platform  🐙

Shared logic for TAFER Laravel projects.

---

## Storyblok

See [Storyblok cache architecture](docs/storyblok-cache.md) for the full cache
flow, request-context behavior, webhook invalidation, and extension points.

### How host projects use it

Consumer apps should keep app-specific configuration in the host project and use
this package for the shared Storyblok/request logic.

```mermaid
flowchart TD
    Browser["Browser request"] --> Route["Laravel catch-all route"]
    Route --> Middleware["ResolveRequestCtx middleware"]
    Middleware --> Ctx["RequestCtx"]
    Ctx --> Controller["App PageController"]
    Controller --> Gateway["StoryblokGateway"]
    Gateway --> Cache{"Storyblok cache enabled?"}
    Cache -- "No" --> API["StoryblokService"]
    Cache -- "Yes" --> Cached["CachedStoryblokService"]
    Cached --> Store["Laravel Cache"]
    Cached --> API
    API --> Storyblok["Storyblok API"]
```

The app controller should only ask the resolved request context for the
Storyblok slug and request options:

```php
use Illuminate\Http\JsonResponse;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

final readonly class PageController
{
    public function __construct(
        private StoryblokGateway $storyblok,
        private StoryblokRequestFactory $requests,
    ) {}

    public function __invoke(RequestCtx $context): JsonResponse
    {
        $response = $this->storyblok->getStory(
            $context->storyblokSlug(),
            $context->storyblokRequest($this->requests),
        );

        return response()->json($response->story);
    }
}
```

Register the shared request-context middleware on the catch-all route:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use TAFER\Core\Middlewares\ResolveRequestCtx;

Route::get('/{slug?}', PageController::class)
    ->where('slug', '.*')
    ->middleware(ResolveRequestCtx::class);
```

`RequestCtx` centralizes the common request data:

```mermaid
flowchart LR
    URL["Public URL"] --> Ctx["RequestCtx"]
    Env["TAFER_BRAND_SLUG"] --> Ctx
    Query["_storyblok preview flag"] --> Ctx
    UA["User-Agent"] --> Ctx

    Ctx --> Resort["Resort"]
    Ctx --> Locale["Locale"]
    Ctx --> Location["Location"]
    Ctx --> Slug["Public slug"]
    Ctx --> Preview["Draft/Published"]
    Ctx --> Device["Device"]
    Ctx --> StoryblokSlug["Storyblok full slug"]
```

Examples:

| Host URL | Brand | Storyblok slug |
|---|---|---|
| `/` | `villa-palmar-cancun` | `brands/villa-palmar-cancun/home-villa-palmar-cancun` |
| `/home-villa-palmar-cancun` | `villa-palmar-cancun` | `brands/villa-palmar-cancun/home-villa-palmar-cancun` |
| `/puerto-vallarta` | `garza-blanca` | `brands/garza-blanca/puerto-vallarta/home-puerto-vallarta` |
| `/es/puerto-vallarta/suites` | `mousai` | `brands/mousai/puerto-vallarta/suites` |

### Host project configuration

Publish the config into each host app:

```bash
php artisan vendor:publish --tag=tafer-config
```

Then override the values in the host app's published `config/tafer.php`:

```php
return [
    'brand' => [
        'slug' => 'villa-palmar-cancun',
    ],

    'storyblok' => [
        'token' => env('STORYBLOK_TOKEN'),
        'version' => 'published',
        'default_locale' => 'en',
        'resolve_links' => 'url',

        'cache' => [
            'enabled' => true,
            'store' => 'database',
            'story_ttl' => 0,
            'relation_ttl' => 0,
            'prefix' => 'tafer:storyblok',
            'namespace' => 'villa-palmar-cancun',
        ],
    ],
];
```

The package config ships with neutral defaults. Each app decides whether its
published config uses fixed values or reads from that app's `.env`.

Set `STORYBLOK_CACHE_ENABLED=false` to use the raw `StoryblokService` without
the cache decorator if your app config maps that value from `.env`; otherwise set
`'storyblok.cache.enabled' => false` directly in the host config.

### Webhook invalidation

Host apps can reuse the package controller directly:

```php
use Illuminate\Support\Facades\Route;
use TAFER\Core\Http\Controllers\StoryblokWebhookController;

Route::post('/storyblok/webhook', StoryblokWebhookController::class);
```

Because Storyblok posts from outside the Laravel session, exclude this route
from CSRF verification in the host app.

```mermaid
sequenceDiagram
    autonumber
    participant SB as Storyblok
    participant App as Host app webhook route
    participant Controller as StoryblokWebhookController
    participant Invalidator as StoryblokWebhookInvalidator
    participant Cache as Laravel Cache

    SB->>App: POST full_slug + action
    App->>Controller: Payload
    Controller->>Invalidator: invalidate locale(s)
    Invalidator->>Cache: forget story + UUID indexes
    Cache-->>Invalidator: StoryblokInvalidationResult
    Invalidator-->>Controller: Results by locale
    Controller-->>SB: 200 JSON
```

When Storyblok sends `full_slug__i18n__es`, the controller invalidates both
`en` and `es`. Without that field it invalidates only the locale inferred from
`full_slug`.

Example:

```bash
curl -X POST https://example.com/storyblok/webhook \
  -H "Content-Type: application/json" \
  -d '{"text":"The user published the Story Home","action":"published","space_id":285826016720786,"story_id":173251456956919,"full_slug":"brands/villa-palmar-cancun/home-villa-palmar-cancun","full_slug__i18n__es":"es/brands/villa-palmar-cancun/home-villa-palmar-cancun"}'
```

### Local workbench

Run the real Storyblok workbench:

```bash
composer serve
```

Configure the workbench environment and open the public slug. For example:

```dotenv
TAFER_BRAND_SLUG=villa-palmar-cancun
STORYBLOK_TOKEN=your_public_token
STORYBLOK_CACHE_ENABLED=true
```

Then open [http://localhost:6969/home-villa-palmar-cancun](http://localhost:6969/home-villa-palmar-cancun).
The workbench converts that public URL into the Storyblok slug
`brands/villa-palmar-cancun/home-villa-palmar-cancun` through `RequestCtx`.

The workbench webhook URL is:

```text
POST http://localhost:6969/storyblok/webhook
```

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
