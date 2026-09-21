W4 OS Update Repository (lab)

Contenido:
- pool/main/w4/*.deb
- Packages
- Packages.gz
- SHA256SUMS
- dists/testing/main/binary-amd64/Packages
- dists/testing/main/binary-amd64/Packages.gz
- dists/testing/Release
- dists/testing/InRelease (cuando SIGNING_MODE=gpg)
- dists/testing/Release.gpg (cuando SIGNING_MODE=gpg)
- apt-source.list.template
- apt-source.dists.list.template
- apt-source.signed.list.template
- package-sources.json
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir preferentemente:
   W4_UPDATE_APT_SOURCE_MODE=dists
   W4_UPDATE_APT_SOURCE_LINE_SIGNED="deb [signed-by=/ruta/al/repositorio/keyrings/w4-update-archive-keyring.gpg] file:/ruta/al/repositorio testing main"
3. si el repo aun no fue firmado, usar como fallback:
   W4_UPDATE_APT_SOURCE_LINE_DISTS="deb [trusted=yes] file:/ruta/al/repositorio testing main"
4. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

Firma opcional:
- exportar W4_UPDATE_REPO_SIGNING_MODE=gpg y W4_UPDATE_REPO_GPG_KEY_ID antes de ejecutar build-repo.sh
- opcionalmente definir W4_UPDATE_REPO_GPG_HOMEDIR y W4_UPDATE_REPO_GPG_PASSPHRASE
- el repositorio firmado exporta el keyring publico a keyrings/w4-update-archive-keyring.gpg

Origen declarativo:
- los metapaquetes se derivan de los manifests/perfiles reales del repositorio
- package-sources.json resume la fuente usada para cada paquete

Compatibilidad:
- el runner ya prioriza la source dists en modo auto, que es la ruta recomendada para futuras pruebas
- se conserva la source plana para el laboratorio actual: deb [trusted=yes] file:/ruta ./
- ademas se publica una estructura tipo APT bajo dists/testing y puede firmarse con GPG para preparar una fuente W4 mas cercana a produccion

Snapshot: w4-main-2026-09-20T180000Z
Canal: testing
Version objetivo: 1.0.1-lab
Package set: both
