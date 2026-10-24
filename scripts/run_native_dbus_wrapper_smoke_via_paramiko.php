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
import time
from pathlib import Path

sys.dont_write_bytecode = True

ROOT_DIR = Path(os.environ["W4_PARAMIKO_PROJECT_ROOT"]).resolve()
PARAMIKO_VENDOR_DIR = ROOT_DIR / ".tools" / "paramiko"
if str(PARAMIKO_VENDOR_DIR) not in sys.path:
    sys.path.insert(0, str(PARAMIKO_VENDOR_DIR))

import paramiko  # type: ignore  # noqa: E402


def wait_for_ssh(host: str, port: int, username: str, password: str, timeout: float) -> paramiko.SSHClient:
    deadline = time.time() + timeout
    last_error: Exception | None = None
    while time.time() < deadline:
        client = paramiko.SSHClient()
        client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        try:
            client.connect(
                host,
                port=port,
                username=username,
                password=password,
                timeout=20,
                banner_timeout=20,
                auth_timeout=20,
            )
            return client
        except Exception as error:
            last_error = error
            client.close()
            time.sleep(3)

    raise TimeoutError(f"No fue posible recuperar SSH en {host}:{port}: {last_error}")


def run_sudo_command(
    client: paramiko.SSHClient,
    command: str,
    password: str,
    timeout: float = 1200,
) -> tuple[int, str, str]:
    transport = client.get_transport()
    if transport is None:
        raise RuntimeError("SSH transport no disponible")

    channel = transport.open_session()
    channel.get_pty()
    channel.exec_command(command)
    channel.send(password + "\n")

    stdout_chunks: list[str] = []
    stderr_chunks: list[str] = []
    channel.settimeout(timeout)
    while True:
        if channel.recv_ready():
            stdout_chunks.append(channel.recv(4096).decode("utf-8", errors="replace"))
        if channel.recv_stderr_ready():
            stderr_chunks.append(channel.recv_stderr(4096).decode("utf-8", errors="replace"))
        if channel.exit_status_ready():
            while channel.recv_ready():
                stdout_chunks.append(channel.recv(4096).decode("utf-8", errors="replace"))
            while channel.recv_stderr_ready():
                stderr_chunks.append(channel.recv_stderr(4096).decode("utf-8", errors="replace"))
            return channel.recv_exit_status(), "".join(stdout_chunks), "".join(stderr_chunks)


def read_remote_text(sftp: paramiko.SFTPClient, path: str) -> str | None:
    try:
        with sftp.file(path, "r") as handle:
            raw = handle.read()
    except FileNotFoundError:
        return None

    if isinstance(raw, bytes):
        return raw.decode("utf-8", errors="replace")

    return str(raw)


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="run_native_dbus_wrapper_smoke_via_paramiko.php",
        description="Ejecuta check_native_dbus_wrapper_smoke.php dentro de la live via SSH/Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=2222)
    parser.add_argument("--username", default="w4live")
    parser.add_argument("--password", required=True)
    parser.add_argument("--remote-root", default="/mnt/w4repo")
    parser.add_argument("--connect-wait", type=int, default=90)
    parser.add_argument("--command-timeout", type=int, default=900)
    args = parser.parse_args()

    remote_root = args.remote_root.rstrip("/")
    dbus_module = remote_root + "/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so"
    wrapper_script = remote_root + "/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wrapper_smoke.php"
    artifact_path = remote_root + "/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/wrapper-smoke/check-native-dbus-wrapper-smoke.json"
    step_log_path = remote_root + "/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/wrapper-smoke/gc-stress-steps.log"

    client = wait_for_ssh(args.host, args.port, args.username, args.password, timeout=args.connect_wait)
    try:
        command = (
            "sudo -S -p '' env "
            f"W4_SYSTEM_API_REMOTE_ROOT_DIR={remote_root} "
            f"W4_SYSTEM_API_REMOTE_DBUS_MODULE={dbus_module} "
            f"W4_DBUS_WRAPPER_BUNDLE_STEP_FILE={step_log_path} "
            f"php -d extension={dbus_module} {wrapper_script}"
        )
        exit_code, stdout, stderr = run_sudo_command(client, command, args.password, timeout=args.command_timeout)

        sftp = client.open_sftp()
        try:
            artifact_text = read_remote_text(sftp, artifact_path)
            step_log_text = read_remote_text(sftp, step_log_path)
        finally:
            sftp.close()

        payload = {
            "host": args.host,
            "port": args.port,
            "username": args.username,
            "remote_root": remote_root,
            "wrapper_script": wrapper_script,
            "artifact_path": artifact_path,
            "step_log_path": step_log_path,
            "exit_code": exit_code,
            "stdout": stdout.strip(),
            "stderr": stderr.strip(),
            "artifact": json.loads(artifact_text) if artifact_text else None,
            "step_log_tail": None if not step_log_text else "\n".join(step_log_text.strip().splitlines()[-20:]),
        }
        print(json.dumps(payload, ensure_ascii=False, indent=2))
        return 0 if exit_code == 0 else 1
    finally:
        client.close()


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = $argv ?? [];
$password = null;

for ($index = 1, $count = count($arguments); $index < $count; $index++) {
    if ($arguments[$index] === '--password' && isset($arguments[$index + 1])) {
        $password = $arguments[$index + 1];
        break;
    }
}

if ($password === null || trim($password) === '') {
    fwrite(STDERR, "ERROR: Debe indicar --password.\n");
    exit(1);
}

exit(runParamikoBridge($pythonSource, array_slice($arguments, 1)));
