W4 OS Security Baseline
=======================

Perfil: w4-os-business

Artefactos:
- security-baseline.json
- verify-security-baseline.php

Uso recomendado sobre una imagen instalada:

  php ./verify-security-baseline.php

O guardando el reporte en una ruta explicita:

  php ./verify-security-baseline.php /ruta/security-baseline-report.json

Notas:
- Los controles con validation_scope=runtime se verifican sobre la imagen en ejecucion.
- Los controles con validation_scope=pipeline se trazan contra la evidencia del pipeline firmado y quedan como skipped en el reporte local.
- Este bundle representa la primera capa de MX-005: checklist validable por imagen y gaps explicitados.