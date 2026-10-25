W4 Update Executor Bundle

operation_id=system-api-update-op-get-75a9411384ee
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee

Run from inside the bundle directory:
0. source "executor.env"

Recommended flow:
1. Run "./run-update-offline.sh" or "./run-update-with-repo-env.sh --repo-dir /path/to/repository".
2. After reboot, run "./run-health-checks.sh".
3. Then run "./reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\update-executor.json
- executor.env: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\executor.env
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-op-get-75a9411384ee\health-report.json
