W4 Update Executor Bundle

operation_id=system-api-update-reconcile-bab7bf9b1583
store_dir=C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583

Run from inside the bundle directory:
0. source "executor.env"

Recommended flow:
1. Run "./run-update-offline.sh" or "./run-update-with-repo-env.sh --repo-dir /path/to/repository".
2. After reboot, run "./run-health-checks.sh".
3. Then run "./reconcile-after-reboot.sh" or call updates.reconcile with health_result_path="C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\health-check-results.json".

Main artifacts:
- update-executor.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\update-executor.json
- executor.env: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\executor.env
- health-check-results.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\health-check-results.json
- operation.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\operation.json
- health-report.json: C:\W4\Packages\W4-OS SYSTEM\build\system-api\update-operations\system-api-update-reconcile-bab7bf9b1583\health-report.json
