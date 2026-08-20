---
name: laravel-alpine-components
description: Create, migrate, or review Alpine.js components in laravel-platform using named create{Name}Component() factories, dependency injection, explicit npm subpath exports, and Vitest coverage. Use whenever work in this repository adds or changes reusable Alpine state or behavior.
---

# Laravel Alpine Components

Use `resources/js/rates/alpine.js` as the canonical implementation.

## Required public pattern

- Export a named factory called `create{Name}Component`, where `{Name}` is the PascalCase form of the Alpine component name. For example, Alpine name `bookingWidget` maps to `createBookingWidgetComponent`.
- Make the outer factory accept application-level configuration and injectable dependencies once.
- Return the Alpine data factory from the outer factory. Make the returned function accept the per-instance properties supplied by Blade through `x-data`.
- Return a fresh state object for every Alpine instance.
- Keep the export named. Do not add a default export for the component factory.

```js
export function createBookingWidgetComponent({
    service = createBookingService(),
    logger = console,
} = {}) {
    return function bookingWidget({ propertyId } = {}) {
        return {
            propertyId,
            isLoading: false,
            error: null,

            async init() {
                // Component behavior belongs here.
            },
        };
    };
}
```

Register the returned factory in the consuming application:

```js
import { createBookingWidgetComponent } from '@tafer-mx/laravel-platform/booking/alpine';

const bookingWidget = createBookingWidgetComponent({
    baseUrl: import.meta.env.VITE_MIDDLEWARE_BASE_URL || undefined,
});

Alpine.data('bookingWidget', bookingWidget);
```

## Ownership and boundaries

- Put shared state, lifecycle hooks, formatting, validation, API orchestration, and error behavior in the package component.
- Keep consumer-specific configuration in the application and pass it to `create{Name}Component()`.
- Do not read `import.meta.env` in the package.
- Inject services, HTTP clients, loggers, browser adapters, and other side effects when doing so makes behavior testable.
- Create sensible production defaults in the outer factory while allowing tests and consumers to override them.
- Keep Blade markup local unless the task explicitly includes sharing Blade components.
- Do not leave a second implementation of the same Alpine behavior in a consuming application after migration.

## Package layout and exports

- Place the public Alpine module at `resources/js/<domain>/alpine.js`.
- Keep domain services and pure helpers separate from the Alpine adapter when they have independent value.
- Add an explicit `package.json` subpath export such as `"./booking/alpine": "./resources/js/booking/alpine.js"`.
- Make consumers import the public subpath; do not import internal package file paths.

## Vitest contract

- Add or update Vitest tests beside the domain under `tests/js/<domain>`.
- Instantiate the component with fakes or `vi.fn()` dependencies; do not make network requests.
- Test fresh initial state, application configuration, instance properties, successful behavior, validation or alternate modes, and failure handling relevant to the component.
- Assert interactions with injected dependencies when those calls are part of the contract.

## Completion checks

Run the checks that match the change:

```bash
npm test
npm run pack:check
```

When a consumer changes, also run its Vite production build and confirm its `Alpine.data()` registration uses the returned factory. Preserve existing PHP and browser behavior while migrating the implementation.
