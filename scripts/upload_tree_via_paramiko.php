<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ParamikoBridge.php';

$pythonSource = <<<'PYTHON'
#!/usr/bin/env python3
from __future__ import annotations

import argparse
import os
import posixpath
import shutil
import sys
from pathlib import Path

sys.dont_write_bytecode = True

ROOT_DIR = Path(os.environ["W4_PARAMIKO_PROJECT_ROOT"]).resolve()
PARAMIKO_VENDOR_DIR = ROOT_DIR / ".tools" / "paramiko"
if str(PARAMIKO_VENDOR_DIR) not in sys.path:
    sys.path.insert(0, str(PARAMIKO_VENDOR_DIR))

import paramiko  # type: ignore  # noqa: E402


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_path: str) -> None:
    segments = [segment for segment in remote_path.split("/") if segment]
    current = "/" if remote_path.startswith("/") else ""
    for segment in segments:
        current = posixpath.join(current, segment) if current else segment
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def remove_remote_tree(sftp: paramiko.SFTPClient, remote_path: str) -> None:
    try:
        attributes = sftp.stat(remote_path)
    except FileNotFoundError:
        return

    if not str(attributes).startswith("d"):
        sftp.remove(remote_path)
        return

    for entry in sftp.listdir_attr(remote_path):
        child = posixpath.join(remote_path, entry.filename)
        if str(entry).startswith("d"):
            remove_remote_tree(sftp, child)
        else:
            sftp.remove(child)

    sftp.rmdir(remote_path)


def is_directory(mode: int) -> bool:
    return (mode & 0o170000) == 0o040000


def remove_remote_tree_safe(sftp: paramiko.SFTPClient, remote_path: str) -> None:
    try:
        attributes = sftp.stat(remote_path)
    except FileNotFoundError:
        return

    if not is_directory(attributes.st_mode):
        sftp.remove(remote_path)
        return

    for entry in sftp.listdir_attr(remote_path):
        child = posixpath.join(remote_path, entry.filename)
        if is_directory(entry.st_mode):
            remove_remote_tree_safe(sftp, child)
        else:
            sftp.remove(child)

    sftp.rmdir(remote_path)


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


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="upload_tree_via_paramiko.php",
        description="Sube un directorio local completo a un directorio remoto via SSH usando Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--local-dir", required=True)
    parser.add_argument("--remote-dir", required=True)
    parser.add_argument("--replace", action="store_true")
    args = parser.parse_args()

    local_dir = Path(args.local_dir).resolve()
    if not local_dir.is_dir():
        raise SystemExit(f"ERROR: No existe el directorio local: {local_dir}")

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
        sftp = client.open_sftp()
        try:
            if args.replace:
                remove_remote_tree_safe(sftp, args.remote_dir)
            upload_tree(sftp, local_dir, args.remote_dir)
        finally:
            sftp.close()
    finally:
        client.close()

    print(
        f"uploaded {local_dir} -> {args.username}@{args.host}:{args.port}:{args.remote_dir}",
        file=sys.stdout,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
PYTHON;

$arguments = array_slice($argv ?? [], 1);
exit(runParamikoBridge($pythonSource, $arguments));
