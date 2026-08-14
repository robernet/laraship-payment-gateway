# POS — Flutter client

Loads when working under `apps/pos/`. Read alongside:
- @../../docs/flutter-conventions.md — shared Flutter rules (stack, network, Hashids, Sanctum, UI-kit discipline)
- @../../docs/api-contract.md — API shapes and auth
- @ui-kit.md — THIS app's reference UI kit

## What this app is
- Role: in-store POS simulator
- Primary user: counter operator (cashier) at a ClubPago-affiliated store / payment point — not the paying customer. "Simulator" = demo, training, and QA without real terminal hardware.
- Auth: Sanctum token scoped to a single branch, carrying operator-level abilities only — `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage`. No admin-panel abilities.

## App-specific
- Key screens / flows: operator login → enter / scan payment reference → show amount due → collect cash & confirm → receipt / confirmation → shift transaction list → open / close shift (cash reconciliation).
- Platform targets: android (primary — POS terminals are typically Android); web optional for demos.
- Deviations from the shared conventions: simulates hardware (cash drawer, receipt printer, barcode scanner) instead of integrating real peripherals; targets the staging/sandbox API, never production settlement.

## Notes
- Shared models and API client come from `packages/core` — don't fork them here.
- This app's UI kit and generated inventory are local to this folder (`ui-kit.md`, `ui-catalog.md`).
