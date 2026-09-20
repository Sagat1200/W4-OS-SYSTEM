W4 OS Update Repository Bundle

Archivos generados:
- build-repo.sh
- repository-manifest.json
- apt-source.list.template

Uso previsto:
1. ejecutar build-repo.sh en Linux o WSL para producir el repositorio APT
2. copiar el repositorio resultante al sistema objetivo
3. definir W4_UPDATE_APT_SOURCE_LINE o W4_UPDATE_APT_SOURCE_FILE al lanzar run-update-offline.sh

Parametros embebidos:
- snapshot_id: w4-main-2026-09-20T120000Z
- channel: testing
- target_version: 1.0.1-lab
- package_set: both
