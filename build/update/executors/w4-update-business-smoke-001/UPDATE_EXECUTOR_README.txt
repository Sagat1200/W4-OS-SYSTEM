W4 OS Update Executor

Archivos generados:
- run-update-offline.sh
- run-health-checks.sh
- reconcile-after-reboot.sh
- update-executor.json

Uso previsto:
1. preparar primero el store durable con prepare_update_operation.php
2. ejecutar run-update-offline.sh hasta dejar la operacion en pending_health
3. tras el reinicio, ejecutar run-health-checks.sh
4. ejecutar reconcile-after-reboot.sh para confirmar o fallar la operacion

Variables utiles:
- W4_UPDATE_STORE_DIR: carpeta del store durable
- W4_UPDATE_ENGINE_ROOT: raiz del repo cuando el bundle se mueve fuera del arbol local
- W4_UPDATE_EXECUTE: usar 1 para habilitar apt-get y snapshot reales
- W4_UPDATE_APPLY_MODE: estrategia de aplicacion, por ahora `live-apt`
- W4_UPDATE_APT_SOURCE_LINE: source line APT temporal para exponer un repo W4 durante la operacion
- W4_UPDATE_APT_SOURCE_FILE: archivo `.list` alternativo para instalar temporalmente antes de `apt-get update`
- W4_UPDATE_FAIL_STAGE: inyeccion de fallo para laboratorio
- W4_UPDATE_OBSERVED_STAGE: estado observado tras reinicio
