W4 Update Executor Bundle

operation_id=system-api-update-health-auto-121d2cd11c34
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34

Recommended flow:
1. Set W4_UPDATE_STORE_DIR="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34" and run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\run-update-offline.sh".
2. After reboot, run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\run-health-checks.sh".
3. Then run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\update-executor.json
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-health-auto-121d2cd11c34\health-report.json
