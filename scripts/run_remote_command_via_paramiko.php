<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ParamikoBridge.php';

$pythonSource = <<<'PYTHON'
#!/usr/bin/env python3
from __future__ import annotations

import argparse
import os
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
        prog="run_remote_command_via_paramiko.php",
        description="Ejecuta un comando remoto por SSH usando la toolchain local de Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--command")
    parser.add_argument("--command-b64", help="Comando remoto codificado en base64 UTF-8")
    parser.add_argument("--sudo-password", help="Password a inyectar en la entrada estandar del comando remoto")
    parser.add_argument("--pty", action="store_true")
    args = parser.parse_args()

    command = args.command
    if args.command_b64:
        import base64

        try:
            command = base64.b64decode(args.command_b64).decode("utf-8")
        except Exception as exc:  # pragma: no cover - defensive CLI path
            raise SystemExit(f"ERROR: --command-b64 invalido: {exc}")

    if not command:
        raise SystemExit("ERROR: Debe indicar --command o --command-b64")

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

    try:
        stdin, stdout, stderr = client.exec_command(command, get_pty=args.pty)
        if args.sudo_password:
            stdin.write(args.sudo_password + "\n")
            stdin.flush()

        output = stdout.read().decode("utf-8", errors="replace")
        error = stderr.read().decode("utf-8", errors="replace")
        if output:
            print(output, end="")
        if error:
            print(error, end="", file=sys.stderr)

        return stdout.channel.recv_exit_status()
    finally:
        client.close()


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = array_slice($argv ?? [], 1);
exit(runParamikoBridge($pythonSource, $arguments));
