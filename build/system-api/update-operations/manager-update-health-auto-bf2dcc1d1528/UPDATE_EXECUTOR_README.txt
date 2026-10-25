W4 Update Executor Bundle

operation_id=manager-update-health-auto-bf2dcc1d1528
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528

Recommended flow:
1. Set W4_UPDATE_STORE_DIR="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528" and run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\run-update-offline.sh".
2. After reboot, run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\run-health-checks.sh".
3. Then run "C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\update-executor.json
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\manager-update-health-auto-bf2dcc1d1528\health-report.json
