<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ParamikoBridge.php';

$pythonSource = <<<'PYTHON'
#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import os
import posixpath
import sys
from pathlib import Path

sys.dont_write_bytecode = True

ROOT_DIR = Path(os.environ["W4_PARAMIKO_PROJECT_ROOT"]).resolve()
PARAMIKO_VENDOR_DIR = ROOT_DIR / ".tools" / "paramiko"
if str(PARAMIKO_VENDOR_DIR) not in sys.path:
    sys.path.insert(0, str(PARAMIKO_VENDOR_DIR))

import paramiko  # type: ignore  # noqa: E402


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="download_files_via_paramiko.php",
        description="Descarga artefactos puntuales desde un directorio remoto via SSH usando Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--remote-dir", required=True)
    parser.add_argument("--local-dir", required=True)
    parser.add_argument("--artifact", action="append", dest="artifacts", default=[])
    args = parser.parse_args()

    local_dir = Path(args.local_dir).resolve()
    local_dir.mkdir(parents=True, exist_ok=True)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        args.host,
        port=args.port,
        username=args.username,
        password=args.password,
        timeout=20,
        banner_timeout=20,
        auth_timeout=20,
    )

    downloaded: list[str] = []
    missing: list[str] = []

    try:
        sftp = client.open_sftp()
        try:
            for artifact in args.artifacts:
                remote_path = posixpath.join(args.remote_dir, artifact)
                local_path = local_dir / Path(artifact)
                try:
                    sftp.stat(remote_path)
                except FileNotFoundError:
                    missing.append(artifact)
                    continue

                local_path.parent.mkdir(parents=True, exist_ok=True)
                sftp.get(remote_path, str(local_path))
                downloaded.append(artifact)
        finally:
            sftp.close()
    finally:
        client.close()

    print(
        json.dumps(
            {
                "status": "ok",
                "downloaded": downloaded,
                "missing": missing,
                "local_dir": str(local_dir),
                "remote_dir": args.remote_dir,
            },
            indent=2,
            ensure_ascii=False,
        )
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = array_slice($argv ?? [], 1);
exit(runParamikoBridge($pythonSource, $arguments));
