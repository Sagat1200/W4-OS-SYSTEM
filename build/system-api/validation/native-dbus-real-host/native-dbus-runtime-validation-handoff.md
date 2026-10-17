# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/srv/w4/os-system`
- Current status: `blocked`
- Blocking reason: `native-dbus-extension-build-failed`
- Preparation script: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Re-run Commands

```bash
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

