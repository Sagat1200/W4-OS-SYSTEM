W4 OS Update Repository Bundle

Archivos generados:
- build-repo.sh
- repository-manifest.json
- apt-source.list.template
- apt-source.dists.list.template

Uso previsto:
1. ejecutar build-repo.sh en Linux o WSL para producir el repositorio APT
2. copiar el repositorio resultante al sistema objetivo
3. preferir W4_UPDATE_APT_SOURCE_MODE=dists y W4_UPDATE_APT_SOURCE_LINE_DISTS al lanzar run-update-offline.sh
4. usar W4_UPDATE_APT_SOURCE_LINE o W4_UPDATE_APT_SOURCE_FILE solo como compatibilidad/fallback

Parametros embebidos:
- snapshot_id: w4-main-2026-09-20T120000Z
- channel: testing
- target_version: 1.0.1-lab
- package_set: both
