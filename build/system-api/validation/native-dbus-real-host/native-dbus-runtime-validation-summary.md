# Native D-Bus Runtime Validation

- Status: `blocked`
- Exit code: `3`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `no`
- Blocking reason: `missing-runtime-capabilities`
- Next action: `install-native-dbus-extension`
- Report path: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-runtime-validation-report.json`

## Recommended Next Actions

- `install-native-dbus-extension`

## Preparation Plan

1. `install-native-dbus-extension` - Instalar y habilitar la extension PECL `dbus` para que PHP pueda pedir el bus name y exportar el objeto D-Bus.

```bash
Resolver manualmente la capacidad faltante `php-dbus-runtime-adapter` para el sistema operativo `Windows`.
```

1. `install-dbus-daemon` - Asegurar que `dbus-daemon` este instalado y disponible en el host.

```bash
Resolver manualmente la capacidad faltante `dbus-daemon` para el sistema operativo `Windows`.
```

1. `materialize-system-bus-socket` - Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.

```bash
Resolver manualmente la capacidad faltante `system-bus-socket` para el sistema operativo `Windows`.
```

1. `install-dbus-client-tooling` - Instalar al menos una herramienta de cliente D-Bus (`busctl` o `gdbus`) para contrastar el runtime desde el host.

```bash
Resolver manualmente la capacidad faltante `dbus-client-tooling` para el sistema operativo `Windows`.
```

1. `retry-native-bus-ownership` - Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.

```bash
php scripts/run_native_dbus_real_host_validation.php --format text
```

