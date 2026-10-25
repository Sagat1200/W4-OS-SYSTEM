W4 Update Executor Bundle

operation_id=manager-update-op-reconcile-f73f8ea771d4
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4

Recommended flow:
1. Set W4_UPDATE_STORE_DIR="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4" and run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\run-update-offline.sh".
2. After reboot, run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\run-health-checks.sh".
3. Then run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\update-executor.json
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-f73f8ea771d4\health-report.json
