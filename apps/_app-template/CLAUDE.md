# <App> — Flutter client

Loads when working under `apps/<app>/`. Read alongside:
- @../../docs/flutter-conventions.md — shared Flutter rules (stack, network, Hashids, Sanctum, UI-kit discipline)
- @../../docs/api-contract.md — API shapes and auth
- @ui-kit.md — THIS app's reference UI kit

## What this app is
- Role: <e.g. customer ordering | driver delivery | field agent | in-store POS simulator>
- Primary user: <who uses it>
- Auth: <the Sanctum abilities / scopes this app's tokens carry>

## App-specific
- Key screens / flows: <list>
- Platform targets: <android | ios | web>
- Deviations from the shared conventions: <none | note them here>

## Notes
- Shared models and API client come from `packages/core` — don't fork them here.
- This app's UI kit and generated inventory are local to this folder (`ui-kit.md`, `ui-catalog.md`).
