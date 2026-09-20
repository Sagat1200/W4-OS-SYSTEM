W4 OS Update Executor

Archivos generados:
- run-update-offline.sh
- reconcile-after-reboot.sh
- update-executor.json

Uso previsto:
1. preparar primero el store durable con prepare_update_operation.php
2. ejecutar run-update-offline.sh hasta dejar la operacion en pending_health
3. tras el reinicio, ejecutar reconcile-after-reboot.sh con la salud observada

Variables utiles:
- W4_UPDATE_STORE_DIR: carpeta del store durable
- W4_UPDATE_FAIL_STAGE: inyeccion de fallo para laboratorio
- W4_UPDATE_OBSERVED_STAGE: estado observado tras reinicio
