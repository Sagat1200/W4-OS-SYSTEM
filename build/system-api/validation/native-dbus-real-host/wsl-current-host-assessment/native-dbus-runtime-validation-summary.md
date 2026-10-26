# Native D-Bus Runtime Validation

- Status: `validation-failed`
- Exit code: `1`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `yes`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/mnt/c/W4/Packages/W4-OS SYSTEM`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `buildable`
- Blocking reason: `native-dbus-extension-class-unavailable`
- Next action: `inspect-native-runtime-coupling`
- Report path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-extension-build-evidence.json`
- Handoff path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Linux`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `buildable`
- PECL available: `yes`
- Installation build phase: `make`
- Installation blocking reason: `n/a`
- Installation next action: `observe-runtime-backend`
- Installation reason: `build-complete`
- Installation failure signature: ``
- Installation log path: `build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log`

## Recommended Next Actions

- `start-native-runtime-service`
- `launch-native-runtime-service`
- `inspect-native-runtime-coupling`

## Preparation Plan

1. `inspect-native-runtime-artifacts` - Revisar los artefactos del runtime nativo y confirmar que startup, status y pid quedaron materializados antes del ownership.

```bash
ls -la "C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-current-host-assessment\runtime-artifacts"
cat "C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-current-host-assessment\runtime-artifacts\w4-system-api-native-runtime-status.json"
```

