<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ParamikoBridge.php';

$pythonSource = <<<'PYTHON'
#!/usr/bin/env python3
from __future__ import annotations

import argparse
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


def log(message: str) -> None:
    print(f"[w4-update-pending-health] {message}", file=sys.stderr, flush=True)


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
        except Exception as error:  # pragma: no cover - retry path de laboratorio
            last_error = error
            client.close()
            time.sleep(5)

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


def download_file_if_exists(sftp: paramiko.SFTPClient, remote_path: str, local_path: Path) -> None:
    try:
        sftp.stat(remote_path)
    except FileNotFoundError:
        return
    local_path.parent.mkdir(parents=True, exist_ok=True)
    sftp.get(remote_path, str(local_path))


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="complete_pending_health_via_paramiko.php",
        description="Completa health checks y reconciliacion de una operacion MX-004 ya en pending_health.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--remote-root", required=True, help="Directorio remoto base usado por run_update_validation_via_paramiko.php")
    parser.add_argument("--evidence-dir", required=True)
    parser.add_argument("--connect-wait", type=int, default=180, help="Segundos maximos para esperar a que el SSH vuelva a estar utilizable")
    args = parser.parse_args()

    evidence_dir = Path(args.evidence_dir).resolve()
    remote_executor_dir = posixpath.join(args.remote_root, "executor")
    remote_engine_dir = posixpath.join(args.remote_root, "engine")
    remote_store_dir = posixpath.join(remote_executor_dir, "store")

    client = wait_for_ssh(args.host, args.port, args.username, args.password, timeout=args.connect_wait)

    try:
        for command, label in [
            (f"cd {remote_executor_dir} && sudo -S -p '' ./run-health-checks.sh", "health checks"),
            (
                f"cd {remote_executor_dir} && sudo -S -p '' env W4_UPDATE_ENGINE_ROOT={remote_engine_dir} ./reconcile-after-reboot.sh",
                "reconciliacion",
            ),
        ]:
            log(f"Ejecutando {label}")
            exit_code, stdout, stderr = run_sudo_command(client, command, args.password)
            if stdout:
                print(stdout, end="")
            if stderr:
                print(stderr, end="", file=sys.stderr)
            if exit_code != 0:
                raise RuntimeError(f"Fallo {label} con exit code {exit_code}")

        sftp = client.open_sftp()
        try:
            for artifact in [
                "operation.json",
                "health-report.json",
                "events.ndjson",
                "health-check-results.json",
                "offline-application.json",
                "snapshot-manifest.json",
                "staging-manifest.json",
            ]:
                download_file_if_exists(
                    sftp,
                    posixpath.join(remote_store_dir, artifact),
                    evidence_dir / artifact,
                )
        finally:
            sftp.close()
    finally:
        client.close()

    log(f"Evidencia descargada en {evidence_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = array_slice($argv ?? [], 1);
exit(runParamikoBridge($pythonSource, $arguments));
