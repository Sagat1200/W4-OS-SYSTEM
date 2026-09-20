W4 OS Update Repository (lab)

Contenido:
- pool/main/w4/*.deb
- Packages
- Packages.gz
- SHA256SUMS
- apt-source.list.template
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir W4_UPDATE_APT_SOURCE_LINE con una source tipo:
   deb [trusted=yes] file:/ruta/al/repositorio ./
3. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

Snapshot: w4-main-2026-09-20T120000Z
Canal: testing
Version objetivo: 1.0.1-lab
Package set: both
