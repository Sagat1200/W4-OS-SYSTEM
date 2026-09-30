W4 OS Update Repository Bundle

Archivos generados:
- build-repo.sh
- repository-manifest.json
- apt-source.list.template
- apt-source.dists.list.template
- apt-source.signed.list.template

Uso previsto:
1. ejecutar build-repo.sh en Linux o WSL para producir el repositorio APT
2. copiar el repositorio resultante al sistema objetivo
3. si el repo fue firmado, preferir W4_UPDATE_APT_SOURCE_MODE=dists y W4_UPDATE_APT_SOURCE_LINE_SIGNED al lanzar run-update-offline.sh
4. usar W4_UPDATE_APT_SOURCE_LINE_DISTS o W4_UPDATE_APT_SOURCE_LINE solo como compatibilidad/fallback

Parametros embebidos:
- snapshot_id: w4-main-server-p0
- channel: testing
- target_version: 0.1.0-server-p0
- package_set: server
