W4 Update Executor Bundle

operation_id=manager-update-op-reconcile-19b3f14a55aa
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa

Recommended flow:
1. Set W4_UPDATE_STORE_DIR="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa" and run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\run-update-offline.sh".
2. After reboot, run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\run-health-checks.sh".
3. Then run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\update-executor.json
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-op-reconcile-19b3f14a55aa\health-report.json
