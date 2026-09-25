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


def log(message: str) -> None:
    print(f"[w4-security-vm] {message}", file=sys.stderr, flush=True)


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_path: str) -> None:
    segments = [segment for segment in remote_path.split("/") if segment]
    current = "/" if remote_path.startswith("/") else ""
    for segment in segments:
        current = posixpath.join(current, segment) if current else segment
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def upload_tree(sftp: paramiko.SFTPClient, local_root: Path, remote_root: str) -> None:
    ensure_remote_dir(sftp, remote_root)
    for local_path in sorted(local_root.rglob("*")):
        relative = local_path.relative_to(local_root)
        remote_path = posixpath.join(remote_root, relative.as_posix())
        if local_path.is_dir():
            ensure_remote_dir(sftp, remote_path)
            continue

        ensure_remote_dir(sftp, posixpath.dirname(remote_path))
        sftp.put(str(local_path), remote_path)
        if local_path.suffix in {".sh", ".php"}:
            sftp.chmod(remote_path, 0o755)


def connect(host: str, port: int, username: str, password: str) -> paramiko.SSHClient:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
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


def run_command(client: paramiko.SSHClient, command: str, timeout: float = 1800) -> tuple[int, str, str]:
    stdin, stdout, stderr = client.exec_command(command, timeout=timeout)
    output = stdout.read().decode("utf-8", errors="replace")
    error = stderr.read().decode("utf-8", errors="replace")
    return stdout.channel.recv_exit_status(), output, error


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="run_security_baseline_via_paramiko.php",
        description="Sube y ejecuta el bundle de baseline de seguridad en una VM por SSH usando Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--bundle-dir", required=True)
    parser.add_argument("--remote-root", required=True)
    parser.add_argument("--evidence-dir", required=True)
    parser.add_argument("--remote-php", default="php")
    parser.add_argument("--report-name", default="security-baseline-report.json")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    bundle_dir = Path(args.bundle_dir).resolve()
    evidence_dir = Path(args.evidence_dir).resolve()
    remote_root = args.remote_root.rstrip("/")
    remote_report_path = posixpath.join(remote_root, args.report_name)
    remote_command = (
        f"cd {remote_root} && "
        f"{args.remote_php} ./verify-security-baseline.php {remote_report_path}"
    )
    local_report_path = evidence_dir / args.report_name

    if not bundle_dir.is_dir():
        raise SystemExit(f"ERROR: No existe el bundle de baseline: {bundle_dir}")

    plan = {
        "status": "dry-run" if args.dry_run else "ok",
        "host": args.host,
        "port": args.port,
        "username": args.username,
        "bundle_dir": str(bundle_dir),
        "remote_root": remote_root,
        "remote_command": remote_command,
        "remote_report_path": remote_report_path,
        "evidence_dir": str(evidence_dir),
        "local_report_path": str(local_report_path),
    }

    if args.dry_run:
        print(json.dumps(plan, indent=4))
        return 0

    evidence_dir.mkdir(parents=True, exist_ok=True)
    log(f"Conectando a {args.username}@{args.host}:{args.port}")
    client = connect(args.host, args.port, args.username, args.password)

    try:
        sftp = client.open_sftp()
        try:
            log(f"Subiendo bundle a {remote_root}")
            upload_tree(sftp, bundle_dir, remote_root)
            log("Ejecutando verify-security-baseline.php")
            exit_code, stdout, stderr = run_command(client, remote_command)
            if stdout:
                print(stdout, end="")
            if stderr:
                print(stderr, end="", file=sys.stderr)
            if exit_code not in (0, 2):
                raise RuntimeError(f"La verificacion remota devolvio exit_code={exit_code}")

            log(f"Descargando reporte a {local_report_path}")
            sftp.get(remote_report_path, str(local_report_path))
        finally:
            sftp.close()
    finally:
        client.close()

    report_raw = local_report_path.read_text(encoding="utf-8")
    report = json.loads(report_raw)
    summary = report.get("summary", {})
    result = {
        **plan,
        "status": "ok",
        "report_summary": summary,
        "remote_exit_code": exit_code,
    }
    print(json.dumps(result, indent=4))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = array_slice($argv ?? [], 1);
exit(runParamikoBridge($pythonSource, $arguments));
