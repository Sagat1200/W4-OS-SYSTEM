# W4 OS · Colección completa de documentación

415 documentos Markdown en la carpeta **W4-OS**, con los nombres exactos del índice recuperado de la conversación «Qué es Omarchy».

**Arquitectura confirmada:** Debian Stable → W4 Linux Base → W4 OS Home / W4 OS Business. openSUSE es referencia técnica, sin ser upstream.

**Edición 0.1, 2026-09-08.** Especificación de diseño inicial en español. Distingue decisiones confirmadas, propuestas y experimentos. No acredita implementación del sistema, pruebas de hardware ni certificaciones. Los archivos cubren propósito, arquitectura, flujos, seguridad, aceptación, rendimiento, dependencias y evolución, con contratos ampliados en los temas transversales.

## Acceso

- [Descargar ZIP de los 415 documentos](W4-OS.zip)
- [Contexto y significado de estados](W4-OS/001_W4_OS_PROJECT_CONTEXT.md)
- [Arquitectura general](W4-OS/004_W4_OS_ARCHITECTURE.md)
- [Registro inicial de decisiones](W4-OS/401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md)
- [Roadmap por dependencias](W4-OS/414_W4_OS_ROADMAP.md)
- [Cierre de arquitectura](W4-OS/415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md)
- [Informe de verificación documental](VERIFICACION_W4_OS.json)
- [Manifiesto de hashes por archivo](MANIFIESTO_W4_OS.json)

El ZIP contiene exactamente la carpeta W4-OS y sus 415 Markdown, sin scripts de generación. El documento 316 incorpora navegación completa dentro del ZIP. Este índice y los informes son auxiliares externos a esa carpeta.

## Índice por áreas

### 001–015 · Fundación y ediciones

- [001 — Project Context](W4-OS/001_W4_OS_PROJECT_CONTEXT.md)
- [002 — Product Vision](W4-OS/002_W4_OS_PRODUCT_VISION.md)
- [003 — Product Family](W4-OS/003_W4_OS_PRODUCT_FAMILY.md)
- [004 — Architecture](W4-OS/004_W4_OS_ARCHITECTURE.md)
- [005 — Debian Base Strategy](W4-OS/005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [006 — Upstream Integration Strategy](W4-OS/006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md)
- [007 — Fork And Derivative Strategy](W4-OS/007_W4_OS_FORK_AND_DERIVATIVE_STRATEGY.md)
- [008 — Shared Core Architecture](W4-OS/008_W4_OS_SHARED_CORE_ARCHITECTURE.md)
- [009 — Layer Model](W4-OS/009_W4_OS_LAYER_MODEL.md)
- [010 — System Components](W4-OS/010_W4_OS_SYSTEM_COMPONENTS.md)
- [011 — Home Edition](W4-OS/011_W4_OS_HOME_EDITION.md)
- [012 — Business Edition](W4-OS/012_W4_OS_BUSINESS_EDITION.md)
- [013 — Edition Differentiation Model](W4-OS/013_W4_OS_EDITION_DIFFERENTIATION_MODEL.md)
- [014 — Edition Package Profiles](W4-OS/014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [015 — Edition Upgrade Strategy](W4-OS/015_W4_OS_EDITION_UPGRADE_STRATEGY.md)

### 016–024 · Kernel y hardware

- [016 — Kernel Strategy](W4-OS/016_W4_OS_KERNEL_STRATEGY.md)
- [017 — Kernel Configuration](W4-OS/017_W4_OS_KERNEL_CONFIGURATION.md)
- [018 — Kernel Update Policy](W4-OS/018_W4_OS_KERNEL_UPDATE_POLICY.md)
- [019 — Hardware Enablement Layer](W4-OS/019_W4_OS_HARDWARE_ENABLEMENT_LAYER.md)
- [020 — Firmware Management](W4-OS/020_W4_OS_FIRMWARE_MANAGEMENT.md)
- [021 — Driver Architecture](W4-OS/021_W4_OS_DRIVER_ARCHITECTURE.md)
- [022 — GPU Driver System](W4-OS/022_W4_OS_GPU_DRIVER_SYSTEM.md)
- [023 — Hardware Detection System](W4-OS/023_W4_OS_HARDWARE_DETECTION_SYSTEM.md)
- [024 — Device Compatibility Model](W4-OS/024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md)

### 025–030 · Arranque y servicios

- [025 — Boot Architecture](W4-OS/025_W4_OS_BOOT_ARCHITECTURE.md)
- [026 — Bootloader Strategy](W4-OS/026_W4_OS_BOOTLOADER_STRATEGY.md)
- [027 — Init System](W4-OS/027_W4_OS_INIT_SYSTEM.md)
- [028 — systemd Integration](W4-OS/028_W4_OS_SYSTEMD_INTEGRATION.md)
- [029 — Startup Pipeline](W4-OS/029_W4_OS_STARTUP_PIPELINE.md)
- [030 — Shutdown Pipeline](W4-OS/030_W4_OS_SHUTDOWN_PIPELINE.md)

### 031–040 · Instalación y layout

- [031 — Installer Architecture](W4-OS/031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [032 — Installation Flow](W4-OS/032_W4_OS_INSTALLATION_FLOW.md)
- [033 — Partitioning System](W4-OS/033_W4_OS_PARTITIONING_SYSTEM.md)
- [034 — Disk Layout Strategy](W4-OS/034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [035 — Btrfs Architecture](W4-OS/035_W4_OS_BTRFS_ARCHITECTURE.md)
- [036 — Filesystem Strategy](W4-OS/036_W4_OS_FILESYSTEM_STRATEGY.md)
- [037 — Encrypted Installation](W4-OS/037_W4_OS_ENCRYPTED_INSTALLATION.md)
- [038 — Dual Boot Strategy](W4-OS/038_W4_OS_DUAL_BOOT_STRATEGY.md)
- [039 — OEM Installation Mode](W4-OS/039_W4_OS_OEM_INSTALLATION_MODE.md)
- [040 — Unattended Installation](W4-OS/040_W4_OS_UNATTENDED_INSTALLATION.md)

### 041–060 · Paquetes y repositorios

- [041 — Package System](W4-OS/041_W4_OS_PACKAGE_SYSTEM.md)
- [042 — DEB Package Architecture](W4-OS/042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [043 — APT Integration](W4-OS/043_W4_OS_APT_INTEGRATION.md)
- [044 — Package Metadata System](W4-OS/044_W4_OS_PACKAGE_METADATA_SYSTEM.md)
- [045 — Meta Package System](W4-OS/045_W4_OS_META_PACKAGE_SYSTEM.md)
- [046 — Package Dependency Policy](W4-OS/046_W4_OS_PACKAGE_DEPENDENCY_POLICY.md)
- [047 — Package Signing System](W4-OS/047_W4_OS_PACKAGE_SIGNING_SYSTEM.md)
- [048 — Package Build Pipeline](W4-OS/048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [049 — Package QA System](W4-OS/049_W4_OS_PACKAGE_QA_SYSTEM.md)
- [050 — Package Lifecycle](W4-OS/050_W4_OS_PACKAGE_LIFECYCLE.md)
- [051 — Repository Architecture](W4-OS/051_W4_OS_REPOSITORY_ARCHITECTURE.md)
- [052 — Repository Layout](W4-OS/052_W4_OS_REPOSITORY_LAYOUT.md)
- [053 — Repository Channels](W4-OS/053_W4_OS_REPOSITORY_CHANNELS.md)
- [054 — Security Repository](W4-OS/054_W4_OS_SECURITY_REPOSITORY.md)
- [055 — Updates Repository](W4-OS/055_W4_OS_UPDATES_REPOSITORY.md)
- [056 — Testing Repository](W4-OS/056_W4_OS_TESTING_REPOSITORY.md)
- [057 — Hardware Repository](W4-OS/057_W4_OS_HARDWARE_REPOSITORY.md)
- [058 — Home Repository](W4-OS/058_W4_OS_HOME_REPOSITORY.md)
- [059 — Business Repository](W4-OS/059_W4_OS_BUSINESS_REPOSITORY.md)
- [060 — Repository Mirror System](W4-OS/060_W4_OS_REPOSITORY_MIRROR_SYSTEM.md)

### 061–070 · Construcción y artefactos

- [061 — Build Infrastructure](W4-OS/061_W4_OS_BUILD_INFRASTRUCTURE.md)
- [062 — Open Build Service Strategy](W4-OS/062_W4_OS_OPEN_BUILD_SERVICE_STRATEGY.md)
- [063 — Build Workers](W4-OS/063_W4_OS_BUILD_WORKERS.md)
- [064 — Build Reproducibility](W4-OS/064_W4_OS_BUILD_REPRODUCIBILITY.md)
- [065 — Multi Arch Build System](W4-OS/065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)
- [066 — amd64 Support](W4-OS/066_W4_OS_AMD64_SUPPORT.md)
- [067 — arm64 Support](W4-OS/067_W4_OS_ARM64_SUPPORT.md)
- [068 — Image Build System](W4-OS/068_W4_OS_IMAGE_BUILD_SYSTEM.md)
- [069 — ISO Build Pipeline](W4-OS/069_W4_OS_ISO_BUILD_PIPELINE.md)
- [070 — Release Artifact System](W4-OS/070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)

### 071–090 · Actualización y recuperación

- [071 — Update Architecture](W4-OS/071_W4_OS_UPDATE_ARCHITECTURE.md)
- [072 — Update Engine](W4-OS/072_W4_OS_UPDATE_ENGINE.md)
- [073 — Atomic Update Model](W4-OS/073_W4_OS_ATOMIC_UPDATE_MODEL.md)
- [074 — Transactional Update Strategy](W4-OS/074_W4_OS_TRANSACTIONAL_UPDATE_STRATEGY.md)
- [075 — Update Staging System](W4-OS/075_W4_OS_UPDATE_STAGING_SYSTEM.md)
- [076 — Update Health Check](W4-OS/076_W4_OS_UPDATE_HEALTH_CHECK.md)
- [077 — Update Rollback System](W4-OS/077_W4_OS_UPDATE_ROLLBACK_SYSTEM.md)
- [078 — Update Rings](W4-OS/078_W4_OS_UPDATE_RINGS.md)
- [079 — Update Channels](W4-OS/079_W4_OS_UPDATE_CHANNELS.md)
- [080 — Offline Update System](W4-OS/080_W4_OS_OFFLINE_UPDATE_SYSTEM.md)
- [081 — Snapshot Architecture](W4-OS/081_W4_OS_SNAPSHOT_ARCHITECTURE.md)
- [082 — Snapper Integration](W4-OS/082_W4_OS_SNAPPER_INTEGRATION.md)
- [083 — System State Model](W4-OS/083_W4_OS_SYSTEM_STATE_MODEL.md)
- [084 — Automatic Snapshot Policy](W4-OS/084_W4_OS_AUTOMATIC_SNAPSHOT_POLICY.md)
- [085 — Rollback Architecture](W4-OS/085_W4_OS_ROLLBACK_ARCHITECTURE.md)
- [086 — Recovery Architecture](W4-OS/086_W4_OS_RECOVERY_ARCHITECTURE.md)
- [087 — Recovery Environment](W4-OS/087_W4_OS_RECOVERY_ENVIRONMENT.md)
- [088 — Boot Recovery](W4-OS/088_W4_OS_BOOT_RECOVERY.md)
- [089 — Factory Reset System](W4-OS/089_W4_OS_FACTORY_RESET_SYSTEM.md)
- [090 — System Repair System](W4-OS/090_W4_OS_SYSTEM_REPAIR_SYSTEM.md)

### 091–110 · Escritorio y accesibilidad

- [091 — Desktop Architecture](W4-OS/091_W4_OS_DESKTOP_ARCHITECTURE.md)
- [092 — Desktop Environment Strategy](W4-OS/092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md)
- [093 — Window Manager Strategy](W4-OS/093_W4_OS_WINDOW_MANAGER_STRATEGY.md)
- [094 — Display Server Strategy](W4-OS/094_W4_OS_DISPLAY_SERVER_STRATEGY.md)
- [095 — Wayland Architecture](W4-OS/095_W4_OS_WAYLAND_ARCHITECTURE.md)
- [096 — Desktop Shell](W4-OS/096_W4_OS_DESKTOP_SHELL.md)
- [097 — Desktop Session System](W4-OS/097_W4_OS_DESKTOP_SESSION_SYSTEM.md)
- [098 — Login Manager](W4-OS/098_W4_OS_LOGIN_MANAGER.md)
- [099 — Lock Screen System](W4-OS/099_W4_OS_LOCK_SCREEN_SYSTEM.md)
- [100 — Desktop Configuration](W4-OS/100_W4_OS_DESKTOP_CONFIGURATION.md)
- [101 — Theme System](W4-OS/101_W4_OS_THEME_SYSTEM.md)
- [102 — Icon System](W4-OS/102_W4_OS_ICON_SYSTEM.md)
- [103 — Font System](W4-OS/103_W4_OS_FONT_SYSTEM.md)
- [104 — Accessibility System](W4-OS/104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [105 — Localization System](W4-OS/105_W4_OS_LOCALIZATION_SYSTEM.md)
- [106 — Language Pack System](W4-OS/106_W4_OS_LANGUAGE_PACK_SYSTEM.md)
- [107 — Input Method System](W4-OS/107_W4_OS_INPUT_METHOD_SYSTEM.md)
- [108 — Display Configuration](W4-OS/108_W4_OS_DISPLAY_CONFIGURATION.md)
- [109 — Multi Monitor System](W4-OS/109_W4_OS_MULTI_MONITOR_SYSTEM.md)
- [110 — HiDPI Support](W4-OS/110_W4_OS_HIDPI_SUPPORT.md)

### 111–120 · Centro de control

- [111 — Control Center Architecture](W4-OS/111_W4_OS_CONTROL_CENTER_ARCHITECTURE.md)
- [112 — System Settings](W4-OS/112_W4_OS_SYSTEM_SETTINGS.md)
- [113 — Hardware Settings](W4-OS/113_W4_OS_HARDWARE_SETTINGS.md)
- [114 — Network Settings](W4-OS/114_W4_OS_NETWORK_SETTINGS.md)
- [115 — User Settings](W4-OS/115_W4_OS_USER_SETTINGS.md)
- [116 — Security Settings](W4-OS/116_W4_OS_SECURITY_SETTINGS.md)
- [117 — Update Settings](W4-OS/117_W4_OS_UPDATE_SETTINGS.md)
- [118 — Storage Settings](W4-OS/118_W4_OS_STORAGE_SETTINGS.md)
- [119 — Application Settings](W4-OS/119_W4_OS_APPLICATION_SETTINGS.md)
- [120 — Business Policy Settings](W4-OS/120_W4_OS_BUSINESS_POLICY_SETTINGS.md)

### 121–140 · Aplicaciones

- [121 — Application Model](W4-OS/121_W4_OS_APPLICATION_MODEL.md)
- [122 — Application Installation System](W4-OS/122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md)
- [123 — Application Sandboxing](W4-OS/123_W4_OS_APPLICATION_SANDBOXING.md)
- [124 — Flatpak Strategy](W4-OS/124_W4_OS_FLATPAK_STRATEGY.md)
- [125 — Native Application Strategy](W4-OS/125_W4_OS_NATIVE_APPLICATION_STRATEGY.md)
- [126 — Third Party Application Support](W4-OS/126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)
- [127 — Application Store](W4-OS/127_W4_OS_APPLICATION_STORE.md)
- [128 — Application Repository](W4-OS/128_W4_OS_APPLICATION_REPOSITORY.md)
- [129 — Application Permissions](W4-OS/129_W4_OS_APPLICATION_PERMISSIONS.md)
- [130 — Application Lifecycle](W4-OS/130_W4_OS_APPLICATION_LIFECYCLE.md)
- [131 — Default Applications](W4-OS/131_W4_OS_DEFAULT_APPLICATIONS.md)
- [132 — Browser Strategy](W4-OS/132_W4_OS_BROWSER_STRATEGY.md)
- [133 — Office Suite Strategy](W4-OS/133_W4_OS_OFFICE_SUITE_STRATEGY.md)
- [134 — Email Client Strategy](W4-OS/134_W4_OS_EMAIL_CLIENT_STRATEGY.md)
- [135 — PDF System](W4-OS/135_W4_OS_PDF_SYSTEM.md)
- [136 — Multimedia System](W4-OS/136_W4_OS_MULTIMEDIA_SYSTEM.md)
- [137 — Media Codec Strategy](W4-OS/137_W4_OS_MEDIA_CODEC_STRATEGY.md)
- [138 — Printing System](W4-OS/138_W4_OS_PRINTING_SYSTEM.md)
- [139 — Scanning System](W4-OS/139_W4_OS_SCANNING_SYSTEM.md)
- [140 — File Manager](W4-OS/140_W4_OS_FILE_MANAGER.md)

### 141–145 · Gaming

- [141 — Gaming Architecture](W4-OS/141_W4_OS_GAMING_ARCHITECTURE.md)
- [142 — Steam Integration](W4-OS/142_W4_OS_STEAM_INTEGRATION.md)
- [143 — Proton Integration](W4-OS/143_W4_OS_PROTON_INTEGRATION.md)
- [144 — Gamepad Support](W4-OS/144_W4_OS_GAMEPAD_SUPPORT.md)
- [145 — Gaming Driver Profile](W4-OS/145_W4_OS_GAMING_DRIVER_PROFILE.md)

### 146–155 · Usuarios y autenticación

- [146 — User Management](W4-OS/146_W4_OS_USER_MANAGEMENT.md)
- [147 — Group Management](W4-OS/147_W4_OS_GROUP_MANAGEMENT.md)
- [148 — Authentication Architecture](W4-OS/148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [149 — Login Security](W4-OS/149_W4_OS_LOGIN_SECURITY.md)
- [150 — Biometric Authentication](W4-OS/150_W4_OS_BIOMETRIC_AUTHENTICATION.md)
- [151 — Privilege Escalation Model](W4-OS/151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)
- [152 — Sudo Policy](W4-OS/152_W4_OS_SUDO_POLICY.md)
- [153 — Session Management](W4-OS/153_W4_OS_SESSION_MANAGEMENT.md)
- [154 — Guest Session](W4-OS/154_W4_OS_GUEST_SESSION.md)
- [155 — Parental Control Architecture](W4-OS/155_W4_OS_PARENTAL_CONTROL_ARCHITECTURE.md)

### 156–175 · Seguridad

- [156 — Security Architecture](W4-OS/156_W4_OS_SECURITY_ARCHITECTURE.md)
- [157 — Security Baseline](W4-OS/157_W4_OS_SECURITY_BASELINE.md)
- [158 — Apparmor Architecture](W4-OS/158_W4_OS_APPARMOR_ARCHITECTURE.md)
- [159 — Firewall System](W4-OS/159_W4_OS_FIREWALL_SYSTEM.md)
- [160 — Disk Encryption](W4-OS/160_W4_OS_DISK_ENCRYPTION.md)
- [161 — Secure Boot](W4-OS/161_W4_OS_SECURE_BOOT.md)
- [162 — TPM Integration](W4-OS/162_W4_OS_TPM_INTEGRATION.md)
- [163 — Secret Storage](W4-OS/163_W4_OS_SECRET_STORAGE.md)
- [164 — Certificate Management](W4-OS/164_W4_OS_CERTIFICATE_MANAGEMENT.md)
- [165 — Security Update Policy](W4-OS/165_W4_OS_SECURITY_UPDATE_POLICY.md)
- [166 — Malware Protection Strategy](W4-OS/166_W4_OS_MALWARE_PROTECTION_STRATEGY.md)
- [167 — Application Trust Model](W4-OS/167_W4_OS_APPLICATION_TRUST_MODEL.md)
- [168 — Code Signing Model](W4-OS/168_W4_OS_CODE_SIGNING_MODEL.md)
- [169 — Supply Chain Security](W4-OS/169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [170 — Security Audit System](W4-OS/170_W4_OS_SECURITY_AUDIT_SYSTEM.md)
- [171 — Security Event System](W4-OS/171_W4_OS_SECURITY_EVENT_SYSTEM.md)
- [172 — Security Hardening Profiles](W4-OS/172_W4_OS_SECURITY_HARDENING_PROFILES.md)
- [173 — Home Security Profile](W4-OS/173_W4_OS_HOME_SECURITY_PROFILE.md)
- [174 — Business Security Profile](W4-OS/174_W4_OS_BUSINESS_SECURITY_PROFILE.md)
- [175 — Incident Recovery Model](W4-OS/175_W4_OS_INCIDENT_RECOVERY_MODEL.md)

### 176–185 · Red

- [176 — Network Architecture](W4-OS/176_W4_OS_NETWORK_ARCHITECTURE.md)
- [177 — NetworkManager Integration](W4-OS/177_W4_OS_NETWORKMANAGER_INTEGRATION.md)
- [178 — Wi-Fi System](W4-OS/178_W4_OS_WIFI_SYSTEM.md)
- [179 — Ethernet System](W4-OS/179_W4_OS_ETHERNET_SYSTEM.md)
- [180 — Bluetooth System](W4-OS/180_W4_OS_BLUETOOTH_SYSTEM.md)
- [181 — VPN Architecture](W4-OS/181_W4_OS_VPN_ARCHITECTURE.md)
- [182 — DNS Architecture](W4-OS/182_W4_OS_DNS_ARCHITECTURE.md)
- [183 — Proxy System](W4-OS/183_W4_OS_PROXY_SYSTEM.md)
- [184 — Network Security](W4-OS/184_W4_OS_NETWORK_SECURITY.md)
- [185 — Enterprise Networking](W4-OS/185_W4_OS_ENTERPRISE_NETWORKING.md)

### 186–195 · Almacenamiento y backup

- [186 — Storage Architecture](W4-OS/186_W4_OS_STORAGE_ARCHITECTURE.md)
- [187 — Storage Device Manager](W4-OS/187_W4_OS_STORAGE_DEVICE_MANAGER.md)
- [188 — External Storage](W4-OS/188_W4_OS_EXTERNAL_STORAGE.md)
- [189 — USB Storage Policy](W4-OS/189_W4_OS_USB_STORAGE_POLICY.md)
- [190 — Mount System](W4-OS/190_W4_OS_MOUNT_SYSTEM.md)
- [191 — Disk Health System](W4-OS/191_W4_OS_DISK_HEALTH_SYSTEM.md)
- [192 — Storage Quota System](W4-OS/192_W4_OS_STORAGE_QUOTA_SYSTEM.md)
- [193 — Backup Architecture](W4-OS/193_W4_OS_BACKUP_ARCHITECTURE.md)
- [194 — Restore System](W4-OS/194_W4_OS_RESTORE_SYSTEM.md)
- [195 — Cloud Backup Strategy](W4-OS/195_W4_OS_CLOUD_BACKUP_STRATEGY.md)

### 196–200 · Energía

- [196 — Power Management](W4-OS/196_W4_OS_POWER_MANAGEMENT.md)
- [197 — Battery Management](W4-OS/197_W4_OS_BATTERY_MANAGEMENT.md)
- [198 — Sleep Hibernation](W4-OS/198_W4_OS_SLEEP_HIBERNATION.md)
- [199 — Thermal Management](W4-OS/199_W4_OS_THERMAL_MANAGEMENT.md)
- [200 — Performance Profiles](W4-OS/200_W4_OS_PERFORMANCE_PROFILES.md)

### 201–210 · Diagnóstico y privacidad

- [201 — Telemetry Architecture](W4-OS/201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [202 — System Logging](W4-OS/202_W4_OS_SYSTEM_LOGGING.md)
- [203 — Metrics System](W4-OS/203_W4_OS_METRICS_SYSTEM.md)
- [204 — Crash Reporting](W4-OS/204_W4_OS_CRASH_REPORTING.md)
- [205 — Diagnostics System](W4-OS/205_W4_OS_DIAGNOSTICS_SYSTEM.md)
- [206 — Health Monitor](W4-OS/206_W4_OS_HEALTH_MONITOR.md)
- [207 — Privacy Model](W4-OS/207_W4_OS_PRIVACY_MODEL.md)
- [208 — Telemetry Privacy Policy](W4-OS/208_W4_OS_TELEMETRY_PRIVACY_POLICY.md)
- [209 — Opt In Telemetry](W4-OS/209_W4_OS_OPT_IN_TELEMETRY.md)
- [210 — Support Bundle System](W4-OS/210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md)

### 211–230 · Administración Business

- [211 — Business Architecture](W4-OS/211_W4_OS_BUSINESS_ARCHITECTURE.md)
- [212 — Enterprise Device Management](W4-OS/212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md)
- [213 — Central Policy System](W4-OS/213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
- [214 — Configuration Policy Engine](W4-OS/214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [215 — Enterprise User Management](W4-OS/215_W4_OS_ENTERPRISE_USER_MANAGEMENT.md)
- [216 — Directory Services](W4-OS/216_W4_OS_DIRECTORY_SERVICES.md)
- [217 — LDAP Integration](W4-OS/217_W4_OS_LDAP_INTEGRATION.md)
- [218 — Active Directory Integration](W4-OS/218_W4_OS_ACTIVE_DIRECTORY_INTEGRATION.md)
- [219 — SSO Architecture](W4-OS/219_W4_OS_SSO_ARCHITECTURE.md)
- [220 — Enterprise Certificates](W4-OS/220_W4_OS_ENTERPRISE_CERTIFICATES.md)
- [221 — Fleet Management](W4-OS/221_W4_OS_FLEET_MANAGEMENT.md)
- [222 — Device Enrollment](W4-OS/222_W4_OS_DEVICE_ENROLLMENT.md)
- [223 — Remote Configuration](W4-OS/223_W4_OS_REMOTE_CONFIGURATION.md)
- [224 — Remote Update Management](W4-OS/224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md)
- [225 — Remote Support System](W4-OS/225_W4_OS_REMOTE_SUPPORT_SYSTEM.md)
- [226 — Remote Diagnostics](W4-OS/226_W4_OS_REMOTE_DIAGNOSTICS.md)
- [227 — Inventory System](W4-OS/227_W4_OS_INVENTORY_SYSTEM.md)
- [228 — Compliance System](W4-OS/228_W4_OS_COMPLIANCE_SYSTEM.md)
- [229 — Enterprise Auditing](W4-OS/229_W4_OS_ENTERPRISE_AUDITING.md)
- [230 — Enterprise Reporting](W4-OS/230_W4_OS_ENTERPRISE_REPORTING.md)

### 231–240 · Experiencia Home

- [231 — Home Architecture](W4-OS/231_W4_OS_HOME_ARCHITECTURE.md)
- [232 — Home Onboarding](W4-OS/232_W4_OS_HOME_ONBOARDING.md)
- [233 — Home Account System](W4-OS/233_W4_OS_HOME_ACCOUNT_SYSTEM.md)
- [234 — Family User Model](W4-OS/234_W4_OS_FAMILY_USER_MODEL.md)
- [235 — Home Backup](W4-OS/235_W4_OS_HOME_BACKUP.md)
- [236 — Home Recovery](W4-OS/236_W4_OS_HOME_RECOVERY.md)
- [237 — Home Application Profile](W4-OS/237_W4_OS_HOME_APPLICATION_PROFILE.md)
- [238 — Home Gaming Profile](W4-OS/238_W4_OS_HOME_GAMING_PROFILE.md)
- [239 — Home Media Profile](W4-OS/239_W4_OS_HOME_MEDIA_PROFILE.md)
- [240 — Home Privacy Profile](W4-OS/240_W4_OS_HOME_PRIVACY_PROFILE.md)

### 241–250 · Servicios W4 opcionales

- [241 — W4 Account Integration](W4-OS/241_W4_OS_W4_ACCOUNT_INTEGRATION.md)
- [242 — W4 Service Integration](W4-OS/242_W4_OS_W4_SERVICE_INTEGRATION.md)
- [243 — W4 Cloud Integration](W4-OS/243_W4_OS_W4_CLOUD_INTEGRATION.md)
- [244 — W4 Storage Integration](W4-OS/244_W4_OS_W4_STORAGE_INTEGRATION.md)
- [245 — W4 Mail Integration](W4-OS/245_W4_OS_W4_MAIL_INTEGRATION.md)
- [246 — W4 Office Integration](W4-OS/246_W4_OS_W4_OFFICE_INTEGRATION.md)
- [247 — W4 Identity Integration](W4-OS/247_W4_OS_W4_IDENTITY_INTEGRATION.md)
- [248 — W4 Backup Integration](W4-OS/248_W4_OS_W4_BACKUP_INTEGRATION.md)
- [249 — W4 Support Integration](W4-OS/249_W4_OS_W4_SUPPORT_INTEGRATION.md)
- [250 — Service Discovery](W4-OS/250_W4_OS_SERVICE_DISCOVERY.md)

### 251–260 · Desarrollo

- [251 — Developer Platform](W4-OS/251_W4_OS_DEVELOPER_PLATFORM.md)
- [252 — Developer Edition Profile](W4-OS/252_W4_OS_DEVELOPER_EDITION_PROFILE.md)
- [253 — Development Toolchain](W4-OS/253_W4_OS_DEVELOPMENT_TOOLCHAIN.md)
- [254 — Container Strategy](W4-OS/254_W4_OS_CONTAINER_STRATEGY.md)
- [255 — Podman Docker Support](W4-OS/255_W4_OS_PODMAN_DOCKER_SUPPORT.md)
- [256 — Virtualization Architecture](W4-OS/256_W4_OS_VIRTUALIZATION_ARCHITECTURE.md)
- [257 — KVM QEMU Integration](W4-OS/257_W4_OS_KVM_QEMU_INTEGRATION.md)
- [258 — Development Environments](W4-OS/258_W4_OS_DEVELOPMENT_ENVIRONMENTS.md)
- [259 — SDK Management](W4-OS/259_W4_OS_SDK_MANAGEMENT.md)
- [260 — Devbox Strategy](W4-OS/260_W4_OS_DEVBOX_STRATEGY.md)

### 261–264 · Compatibilidad Windows

- [261 — Windows Compatibility Strategy](W4-OS/261_W4_OS_WINDOWS_COMPATIBILITY_STRATEGY.md)
- [262 — Wine Integration](W4-OS/262_W4_OS_WINE_INTEGRATION.md)
- [263 — Windows Vm Strategy](W4-OS/263_W4_OS_WINDOWS_VM_STRATEGY.md)
- [264 — Cross Platform Application Support](W4-OS/264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md)

### 265–274 · Releases y soporte de versiones

- [265 — Release Model](W4-OS/265_W4_OS_RELEASE_MODEL.md)
- [266 — Versioning Policy](W4-OS/266_W4_OS_VERSIONING_POLICY.md)
- [267 — LTS Strategy](W4-OS/267_W4_OS_LTS_STRATEGY.md)
- [268 — Release Channels](W4-OS/268_W4_OS_RELEASE_CHANNELS.md)
- [269 — Release Branching Model](W4-OS/269_W4_OS_RELEASE_BRANCHING_MODEL.md)
- [270 — Release Engineering](W4-OS/270_W4_OS_RELEASE_ENGINEERING.md)
- [271 — Release Qualification](W4-OS/271_W4_OS_RELEASE_QUALIFICATION.md)
- [272 — Release Signing](W4-OS/272_W4_OS_RELEASE_SIGNING.md)
- [273 — Release Rollout](W4-OS/273_W4_OS_RELEASE_ROLLOUT.md)
- [274 — End Of Life Policy](W4-OS/274_W4_OS_END_OF_LIFE_POLICY.md)

### 275–290 · Pruebas y calificación

- [275 — Testing Architecture](W4-OS/275_W4_OS_TESTING_ARCHITECTURE.md)
- [276 — Unit Testing](W4-OS/276_W4_OS_UNIT_TESTING.md)
- [277 — Integration Testing](W4-OS/277_W4_OS_INTEGRATION_TESTING.md)
- [278 — System Testing](W4-OS/278_W4_OS_SYSTEM_TESTING.md)
- [279 — Hardware Testing](W4-OS/279_W4_OS_HARDWARE_TESTING.md)
- [280 — Update Testing](W4-OS/280_W4_OS_UPDATE_TESTING.md)
- [281 — Rollback Testing](W4-OS/281_W4_OS_ROLLBACK_TESTING.md)
- [282 — Security Testing](W4-OS/282_W4_OS_SECURITY_TESTING.md)
- [283 — Desktop Testing](W4-OS/283_W4_OS_DESKTOP_TESTING.md)
- [284 — Enterprise Testing](W4-OS/284_W4_OS_ENTERPRISE_TESTING.md)
- [285 — QA Architecture](W4-OS/285_W4_OS_QA_ARCHITECTURE.md)
- [286 — Automated QA](W4-OS/286_W4_OS_AUTOMATED_QA.md)
- [287 — Hardware Certification](W4-OS/287_W4_OS_HARDWARE_CERTIFICATION.md)
- [288 — Compatibility Certification](W4-OS/288_W4_OS_COMPATIBILITY_CERTIFICATION.md)
- [289 — Application Certification](W4-OS/289_W4_OS_APPLICATION_CERTIFICATION.md)
- [290 — Business Certification](W4-OS/290_W4_OS_BUSINESS_CERTIFICATION.md)

### 291–300 · Rendimiento

- [291 — Performance Architecture](W4-OS/291_W4_OS_PERFORMANCE_ARCHITECTURE.md)
- [292 — Boot Performance](W4-OS/292_W4_OS_BOOT_PERFORMANCE.md)
- [293 — Memory Management](W4-OS/293_W4_OS_MEMORY_MANAGEMENT.md)
- [294 — CPU Optimization](W4-OS/294_W4_OS_CPU_OPTIMIZATION.md)
- [295 — Storage Performance](W4-OS/295_W4_OS_STORAGE_PERFORMANCE.md)
- [296 — Graphics Performance](W4-OS/296_W4_OS_GRAPHICS_PERFORMANCE.md)
- [297 — Application Startup Performance](W4-OS/297_W4_OS_APPLICATION_STARTUP_PERFORMANCE.md)
- [298 — Power Performance](W4-OS/298_W4_OS_POWER_PERFORMANCE.md)
- [299 — Performance Benchmarks](W4-OS/299_W4_OS_PERFORMANCE_BENCHMARKS.md)
- [300 — Performance Budgets](W4-OS/300_W4_OS_PERFORMANCE_BUDGETS.md)

### 301–308 · Identidad y nombres

- [301 — Branding Architecture](W4-OS/301_W4_OS_BRANDING_ARCHITECTURE.md)
- [302 — Visual Identity](W4-OS/302_W4_OS_VISUAL_IDENTITY.md)
- [303 — Boot Branding](W4-OS/303_W4_OS_BOOT_BRANDING.md)
- [304 — Installer Branding](W4-OS/304_W4_OS_INSTALLER_BRANDING.md)
- [305 — Desktop Branding](W4-OS/305_W4_OS_DESKTOP_BRANDING.md)
- [306 — System Naming](W4-OS/306_W4_OS_SYSTEM_NAMING.md)
- [307 — Package Naming](W4-OS/307_W4_OS_PACKAGE_NAMING.md)
- [308 — Repository Naming](W4-OS/308_W4_OS_REPOSITORY_NAMING.md)

### 309–315 · Licencias y distribución

- [309 — Licensing Strategy](W4-OS/309_W4_OS_LICENSING_STRATEGY.md)
- [310 — Open Source Compliance](W4-OS/310_W4_OS_OPEN_SOURCE_COMPLIANCE.md)
- [311 — GPL Compliance](W4-OS/311_W4_OS_GPL_COMPLIANCE.md)
- [312 — Third Party License Management](W4-OS/312_W4_OS_THIRD_PARTY_LICENSE_MANAGEMENT.md)
- [313 — Source Code Publication Policy](W4-OS/313_W4_OS_SOURCE_CODE_PUBLICATION_POLICY.md)
- [314 — Trademark Policy](W4-OS/314_W4_OS_TRADEMARK_POLICY.md)
- [315 — Distribution Policy](W4-OS/315_W4_OS_DISTRIBUTION_POLICY.md)

### 316–322 · Guías y documentación

- [316 — Documentation Architecture](W4-OS/316_W4_OS_DOCUMENTATION_ARCHITECTURE.md)
- [317 — User Documentation](W4-OS/317_W4_OS_USER_DOCUMENTATION.md)
- [318 — Administrator Guide](W4-OS/318_W4_OS_ADMINISTRATOR_GUIDE.md)
- [319 — Developer Guide](W4-OS/319_W4_OS_DEVELOPER_GUIDE.md)
- [320 — Package Maintainer Guide](W4-OS/320_W4_OS_PACKAGE_MAINTAINER_GUIDE.md)
- [321 — Release Engineering Guide](W4-OS/321_W4_OS_RELEASE_ENGINEERING_GUIDE.md)
- [322 — Troubleshooting Guide](W4-OS/322_W4_OS_TROUBLESHOOTING_GUIDE.md)

### 323–328 · Comunidad y gobernanza

- [323 — Community Model](W4-OS/323_W4_OS_COMMUNITY_MODEL.md)
- [324 — Contribution Model](W4-OS/324_W4_OS_CONTRIBUTION_MODEL.md)
- [325 — Governance Model](W4-OS/325_W4_OS_GOVERNANCE_MODEL.md)
- [326 — Security Response Team](W4-OS/326_W4_OS_SECURITY_RESPONSE_TEAM.md)
- [327 — Package Maintainer Model](W4-OS/327_W4_OS_PACKAGE_MAINTAINER_MODEL.md)
- [328 — Community Repository Model](W4-OS/328_W4_OS_COMMUNITY_REPOSITORY_MODEL.md)

### 329–334 · Soporte

- [329 — Support Model](W4-OS/329_W4_OS_SUPPORT_MODEL.md)
- [330 — Home Support](W4-OS/330_W4_OS_HOME_SUPPORT.md)
- [331 — Business Support](W4-OS/331_W4_OS_BUSINESS_SUPPORT.md)
- [332 — Enterprise SLA Model](W4-OS/332_W4_OS_ENTERPRISE_SLA_MODEL.md)
- [333 — Remote Support Policy](W4-OS/333_W4_OS_REMOTE_SUPPORT_POLICY.md)
- [334 — Security Support Policy](W4-OS/334_W4_OS_SECURITY_SUPPORT_POLICY.md)

### 335–340 · Modelo comercial

- [335 — Business Model](W4-OS/335_W4_OS_BUSINESS_MODEL.md)
- [336 — Commercial Edition Strategy](W4-OS/336_W4_OS_COMMERCIAL_EDITION_STRATEGY.md)
- [337 — Subscription Model](W4-OS/337_W4_OS_SUBSCRIPTION_MODEL.md)
- [338 — OEM Partnership Model](W4-OS/338_W4_OS_OEM_PARTNERSHIP_MODEL.md)
- [339 — Hardware Vendor Program](W4-OS/339_W4_OS_HARDWARE_VENDOR_PROGRAM.md)
- [340 — Enterprise Licensing Model](W4-OS/340_W4_OS_ENTERPRISE_LICENSING_MODEL.md)

### 341–346 · Migración

- [341 — Migration From Windows](W4-OS/341_W4_OS_MIGRATION_FROM_WINDOWS.md)
- [342 — Migration From Ubuntu](W4-OS/342_W4_OS_MIGRATION_FROM_UBUNTU.md)
- [343 — Migration From Debian](W4-OS/343_W4_OS_MIGRATION_FROM_DEBIAN.md)
- [344 — Migration Toolkit](W4-OS/344_W4_OS_MIGRATION_TOOLKIT.md)
- [345 — User Data Migration](W4-OS/345_W4_OS_USER_DATA_MIGRATION.md)
- [346 — Enterprise Migration](W4-OS/346_W4_OS_ENTERPRISE_MIGRATION.md)

### 347–350 · Continuidad y OEM

- [347 — Disaster Recovery](W4-OS/347_W4_OS_DISASTER_RECOVERY.md)
- [348 — Business Continuity](W4-OS/348_W4_OS_BUSINESS_CONTINUITY.md)
- [349 — Factory Image Strategy](W4-OS/349_W4_OS_FACTORY_IMAGE_STRATEGY.md)
- [350 — OEM Image Strategy](W4-OS/350_W4_OS_OEM_IMAGE_STRATEGY.md)

### 351–355 · Privacidad de datos

- [351 — Privacy Architecture](W4-OS/351_W4_OS_PRIVACY_ARCHITECTURE.md)
- [352 — Data Collection Policy](W4-OS/352_W4_OS_DATA_COLLECTION_POLICY.md)
- [353 — Data Retention Policy](W4-OS/353_W4_OS_DATA_RETENTION_POLICY.md)
- [354 — User Consent Model](W4-OS/354_W4_OS_USER_CONSENT_MODEL.md)
- [355 — Enterprise Privacy Controls](W4-OS/355_W4_OS_ENTERPRISE_PRIVACY_CONTROLS.md)

### 356–360 · Alineación de controles

- [356 — Compliance Architecture](W4-OS/356_W4_OS_COMPLIANCE_ARCHITECTURE.md)
- [357 — ISO 27001 Alignment](W4-OS/357_W4_OS_ISO_27001_ALIGNMENT.md)
- [358 — NIST Alignment](W4-OS/358_W4_OS_NIST_ALIGNMENT.md)
- [359 — CIS Benchmark Strategy](W4-OS/359_W4_OS_CIS_BENCHMARK_STRATEGY.md)
- [360 — Enterprise Compliance Profiles](W4-OS/360_W4_OS_ENTERPRISE_COMPLIANCE_PROFILES.md)

### 361–365 · Observabilidad

- [361 — Observability Architecture](W4-OS/361_W4_OS_OBSERVABILITY_ARCHITECTURE.md)
- [362 — System Event Model](W4-OS/362_W4_OS_SYSTEM_EVENT_MODEL.md)
- [363 — Audit Log Architecture](W4-OS/363_W4_OS_AUDIT_LOG_ARCHITECTURE.md)
- [364 — Diagnostic Event Pipeline](W4-OS/364_W4_OS_DIAGNOSTIC_EVENT_PIPELINE.md)
- [365 — Health Score System](W4-OS/365_W4_OS_HEALTH_SCORE_SYSTEM.md)

### 366–371 · APIs y protocolos

- [366 — API Architecture](W4-OS/366_W4_OS_API_ARCHITECTURE.md)
- [367 — System Service API](W4-OS/367_W4_OS_SYSTEM_SERVICE_API.md)
- [368 — Control Center API](W4-OS/368_W4_OS_CONTROL_CENTER_API.md)
- [369 — Update API](W4-OS/369_W4_OS_UPDATE_API.md)
- [370 — Device Management API](W4-OS/370_W4_OS_DEVICE_MANAGEMENT_API.md)
- [371 — Remote Management Protocol](W4-OS/371_W4_OS_REMOTE_MANAGEMENT_PROTOCOL.md)

### 372–376 · Extensibilidad

- [372 — Extension Architecture](W4-OS/372_W4_OS_EXTENSION_ARCHITECTURE.md)
- [373 — Plugin System](W4-OS/373_W4_OS_PLUGIN_SYSTEM.md)
- [374 — System Modules](W4-OS/374_W4_OS_SYSTEM_MODULES.md)
- [375 — Vendor Extension Model](W4-OS/375_W4_OS_VENDOR_EXTENSION_MODEL.md)
- [376 — OEM Extension Model](W4-OS/376_W4_OS_OEM_EXTENSION_MODEL.md)

### 377–381 · Configuración y políticas

- [377 — Configuration Architecture](W4-OS/377_W4_OS_CONFIGURATION_ARCHITECTURE.md)
- [378 — Configuration Schema](W4-OS/378_W4_OS_CONFIGURATION_SCHEMA.md)
- [379 — System Defaults](W4-OS/379_W4_OS_SYSTEM_DEFAULTS.md)
- [380 — Configuration Layering](W4-OS/380_W4_OS_CONFIGURATION_LAYERING.md)
- [381 — Policy Override Model](W4-OS/381_W4_OS_POLICY_OVERRIDE_MODEL.md)

### 382–386 · Rescate y fallos

- [382 — Failsafe Architecture](W4-OS/382_W4_OS_FAILSAFE_ARCHITECTURE.md)
- [383 — Safe Mode](W4-OS/383_W4_OS_SAFE_MODE.md)
- [384 — Emergency Shell](W4-OS/384_W4_OS_EMERGENCY_SHELL.md)
- [385 — Boot Failure Recovery](W4-OS/385_W4_OS_BOOT_FAILURE_RECOVERY.md)
- [386 — Filesystem Recovery](W4-OS/386_W4_OS_FILESYSTEM_RECOVERY.md)

### 387–390 · Suministro verificable

- [387 — Secure Update Supply Chain](W4-OS/387_W4_OS_SECURE_UPDATE_SUPPLY_CHAIN.md)
- [388 — SBOM Strategy](W4-OS/388_W4_OS_SBOM_STRATEGY.md)
- [389 — Reproducible Builds](W4-OS/389_W4_OS_REPRODUCIBLE_BUILDS.md)
- [390 — Build Provenance](W4-OS/390_W4_OS_BUILD_PROVENANCE.md)

### 391–396 · Integración Debian

- [391 — Upstream Sync Process](W4-OS/391_W4_OS_UPSTREAM_SYNC_PROCESS.md)
- [392 — Debian Security Sync](W4-OS/392_W4_OS_DEBIAN_SECURITY_SYNC.md)
- [393 — Debian Package Sync](W4-OS/393_W4_OS_DEBIAN_PACKAGE_SYNC.md)
- [394 — Patch Management](W4-OS/394_W4_OS_PATCH_MANAGEMENT.md)
- [395 — W4 Patch Queue](W4-OS/395_W4_OS_W4_PATCH_QUEUE.md)
- [396 — Upstream Contribution Policy](W4-OS/396_W4_OS_UPSTREAM_CONTRIBUTION_POLICY.md)

### 397–400 · Evolución y mantenimiento

- [397 — Debian Release Transition](W4-OS/397_W4_OS_DEBIAN_RELEASE_TRANSITION.md)
- [398 — Major Version Migration](W4-OS/398_W4_OS_MAJOR_VERSION_MIGRATION.md)
- [399 — Backward Compatibility Policy](W4-OS/399_W4_OS_BACKWARD_COMPATIBILITY_POLICY.md)
- [400 — Long Term Maintenance](W4-OS/400_W4_OS_LONG_TERM_MAINTENANCE.md)

### 401–405 · Decisiones y riesgos

- [401 — Architecture Decision Records](W4-OS/401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md)
- [402 — Risk Register](W4-OS/402_W4_OS_RISK_REGISTER.md)
- [403 — Technical Debt Policy](W4-OS/403_W4_OS_TECHNICAL_DEBT_POLICY.md)
- [404 — Deprecation Policy](W4-OS/404_W4_OS_DEPRECATION_POLICY.md)
- [405 — Experimental Feature Policy](W4-OS/405_W4_OS_EXPERIMENTAL_FEATURE_POLICY.md)

### 406–415 · Alcance y roadmap

- [406 — V1 Scope](W4-OS/406_W4_OS_V1_SCOPE.md)
- [407 — V1 Mvp](W4-OS/407_W4_OS_V1_MVP.md)
- [408 — V1 Home Scope](W4-OS/408_W4_OS_V1_HOME_SCOPE.md)
- [409 — V1 Business Scope](W4-OS/409_W4_OS_V1_BUSINESS_SCOPE.md)
- [410 — V1 Release Plan](W4-OS/410_W4_OS_V1_RELEASE_PLAN.md)
- [411 — V2 Vision](W4-OS/411_W4_OS_V2_VISION.md)
- [412 — V3 Vision](W4-OS/412_W4_OS_V3_VISION.md)
- [413 — Long Term Vision](W4-OS/413_W4_OS_LONG_TERM_VISION.md)
- [414 — Roadmap](W4-OS/414_W4_OS_ROADMAP.md)
- [415 — Final Architecture Overview](W4-OS/415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md)
