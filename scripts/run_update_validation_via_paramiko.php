<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ParamikoBridge.php';

$pythonSource = <<<'PYTHON'
#!/usr/bin/env python3
from __future__ import annotations

import argparse
import os
import posixpath
import socket
import stat
import subprocess
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
    print(f"[w4-update-vm] {message}", file=sys.stderr, flush=True)


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
        if local_path.suffix == ".sh":
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


def run_command(
    client: paramiko.SSHClient,
    command: str,
    *,
    sudo_password: str | None = None,
    allow_disconnect: bool = False,
    timeout: float = 1800,
) -> tuple[int, str, str]:
    transport = client.get_transport()
    if transport is None:
        raise RuntimeError("SSH transport no disponible")

    channel = transport.open_session()
    channel.get_pty()
    channel.exec_command(command)

    if sudo_password is not None:
        channel.send(sudo_password + "\n")

    stdout_chunks: list[str] = []
    stderr_chunks: list[str] = []
    start = time.time()

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
            exit_status = channel.recv_exit_status()
            return exit_status, "".join(stdout_chunks), "".join(stderr_chunks)
        if allow_disconnect and channel.closed:
            return 0, "".join(stdout_chunks), "".join(stderr_chunks)
        if time.time() - start > timeout:
            channel.close()
            raise TimeoutError(f"Tiempo agotado ejecutando comando remoto: {command}")
        time.sleep(0.2)


def wait_for_ssh(host: str, port: int, username: str, password: str, timeout: float) -> paramiko.SSHClient:
    deadline = time.time() + timeout
    last_error: Exception | None = None
    while time.time() < deadline:
        try:
            return connect(host, port, username, password)
        except Exception as error:  # pragma: no cover - real lab retry path
            last_error = error
            time.sleep(5)
    raise TimeoutError(f"No fue posible recuperar SSH en {host}:{port}: {last_error}")


def send_vbox_text(vm_name: str, text: str) -> None:
    vboxmanage = Path(r"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe")
    subprocess.run([str(vboxmanage), "controlvm", vm_name, "keyboardputstring", text], check=True)
    subprocess.run([str(vboxmanage), "controlvm", vm_name, "keyboardputscancode", "1c", "9c"], check=True)


def download_file_if_exists(sftp: paramiko.SFTPClient, remote_path: str, local_path: Path) -> None:
    try:
        remote_stat = sftp.stat(remote_path)
    except FileNotFoundError:
        return
    if stat.S_ISDIR(remote_stat.st_mode):
        return
    local_path.parent.mkdir(parents=True, exist_ok=True)
    sftp.get(remote_path, str(local_path))


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="run_update_validation_via_paramiko.php",
        description="Ejecuta la validacion real de MX-004 en una VM por SSH usando Paramiko.",
    )
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", required=True, type=int)
    parser.add_argument("--username", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--repo-dir", required=True, help="Directorio local del repo APT reconstruido")
    parser.add_argument("--executor-dir", required=True, help="Directorio local del ejecutor a copiar")
    parser.add_argument("--plan-path", required=True, help="Ruta local del update-plan.json a usar para inicializar el store")
    parser.add_argument("--remote-root", required=True, help="Directorio base remoto donde se copiara repo + ejecutor")
    parser.add_argument("--reboot-wait", type=int, default=240, help="Segundos maximos para esperar la vuelta del SSH tras reboot")
    parser.add_argument("--vm-name", help="Nombre de la VM en VirtualBox para automatizar el desbloqueo LUKS")
    parser.add_argument("--luks-passphrase", help="Passphrase LUKS ASCII para desbloqueo post-reboot")
    parser.add_argument("--evidence-dir", required=True, help="Directorio local para guardar artefactos descargados")
    args = parser.parse_args()

    repo_dir = Path(args.repo_dir).resolve()
    executor_dir = Path(args.executor_dir).resolve()
    plan_path = Path(args.plan_path).resolve()
    evidence_dir = Path(args.evidence_dir).resolve()

    if not repo_dir.is_dir():
        raise SystemExit(f"No existe el repo local: {repo_dir}")
    if not executor_dir.is_dir():
        raise SystemExit(f"No existe el ejecutor local: {executor_dir}")
    if not plan_path.is_file():
        raise SystemExit(f"No existe el plan local: {plan_path}")

    remote_repo_dir = posixpath.join(args.remote_root, "repo")
    remote_executor_dir = posixpath.join(args.remote_root, "executor")
    remote_engine_dir = posixpath.join(args.remote_root, "engine")
    remote_plan_path = posixpath.join(args.remote_root, "update-plan.json")
    remote_store_dir = posixpath.join(remote_executor_dir, "store")

    log(f"Conectando a {args.username}@{args.host}:{args.port}")
    client = connect(args.host, args.port, args.username, args.password)
    try:
        exit_code, stdout, stderr = run_command(
            client,
            f"rm -rf {remote_repo_dir!s} {remote_executor_dir!s} {remote_engine_dir!s} && mkdir -p {args.remote_root!s}",
        )
        if exit_code != 0:
            raise RuntimeError(f"No se pudo preparar el directorio remoto.\nSTDOUT:\n{stdout}\nSTDERR:\n{stderr}")

        sftp = client.open_sftp()
        try:
            log(f"Copiando repo a {remote_repo_dir}")
            upload_tree(sftp, repo_dir, remote_repo_dir)
            log(f"Copiando ejecutor a {remote_executor_dir}")
            upload_tree(sftp, executor_dir, remote_executor_dir)
            log(f"Copiando motor de update a {remote_engine_dir}")
            upload_tree(sftp, ROOT_DIR / "scripts", posixpath.join(remote_engine_dir, "scripts"))
            upload_tree(sftp, ROOT_DIR / "src", posixpath.join(remote_engine_dir, "src"))
            sftp.put(str(plan_path), remote_plan_path)
        finally:
            sftp.close()

        chmod_command = (
            f"cd {remote_executor_dir} && "
            "chmod +x run-update-offline.sh run-update-with-repo-env.sh run-health-checks.sh reconcile-after-reboot.sh"
        )
        exit_code, stdout, stderr = run_command(client, chmod_command)
        if exit_code != 0:
            raise RuntimeError(f"No se pudo ajustar permisos.\nSTDOUT:\n{stdout}\nSTDERR:\n{stderr}")

        normalize_command = (
            f"cd {remote_executor_dir} && "
            "for file in run-update-offline.sh run-update-with-repo-env.sh run-health-checks.sh reconcile-after-reboot.sh; "
            "do sed -i 's/\\r$//' \"$file\"; done"
        )
        exit_code, stdout, stderr = run_command(client, normalize_command)
        if exit_code != 0:
            raise RuntimeError(f"No se pudo normalizar fin de linea en scripts remotos.\nSTDOUT:\n{stdout}\nSTDERR:\n{stderr}")

        prepare_command = (
            f"php {remote_engine_dir}/scripts/prepare_update_operation.php "
            f"--plan {remote_plan_path} --store-dir {remote_store_dir}"
        )
        log("Inicializando store durable remoto")
        exit_code, stdout, stderr = run_command(client, prepare_command, timeout=1200)
        if stdout:
            print(stdout, end="")
        if stderr:
            print(stderr, file=sys.stderr, end="")
        if exit_code != 0:
            raise RuntimeError(f"No se pudo preparar la operacion remota con exit code {exit_code}")

        offline_command = (
            f"cd {remote_executor_dir} && "
            f"sudo -S -p '' env W4_UPDATE_ENGINE_ROOT={remote_engine_dir} W4_UPDATE_EXECUTE=1 "
            f"./run-update-with-repo-env.sh --repo-dir {remote_repo_dir}"
        )
        log("Ejecutando run-update-with-repo-env.sh")
        exit_code, stdout, stderr = run_command(client, offline_command, sudo_password=args.password, timeout=3600)
        print(stdout, end="")
        if stderr:
            print(stderr, file=sys.stderr, end="")
        if exit_code != 0:
            raise RuntimeError(f"La aplicacion offline fallo con exit code {exit_code}")

        reboot_command = "sudo -S -p '' reboot"
        log("Solicitando reboot")
        run_command(client, reboot_command, sudo_password=args.password, allow_disconnect=True, timeout=30)
    finally:
        client.close()

    if args.vm_name and args.luks_passphrase:
        log("Esperando el prompt LUKS para inyectar la passphrase")
        time.sleep(15)
        send_vbox_text(args.vm_name, args.luks_passphrase)

    log("Esperando a que la VM vuelva por SSH")
    client = wait_for_ssh(args.host, args.port, args.username, args.password, timeout=args.reboot_wait)
    try:
        health_command = f"cd {remote_executor_dir} && sudo -S -p '' ./run-health-checks.sh"
        log("Ejecutando health checks")
        exit_code, stdout, stderr = run_command(client, health_command, sudo_password=args.password, timeout=1200)
        print(stdout, end="")
        if stderr:
            print(stderr, file=sys.stderr, end="")
        if exit_code != 0:
            raise RuntimeError(f"Los health checks fallaron con exit code {exit_code}")

        reconcile_command = (
            f"cd {remote_executor_dir} && "
            f"sudo -S -p '' env W4_UPDATE_ENGINE_ROOT={remote_engine_dir} ./reconcile-after-reboot.sh"
        )
        log("Ejecutando reconciliacion")
        exit_code, stdout, stderr = run_command(client, reconcile_command, sudo_password=args.password, timeout=1200)
        print(stdout, end="")
        if stderr:
            print(stderr, file=sys.stderr, end="")
        if exit_code != 0:
            raise RuntimeError(f"La reconciliacion fallo con exit code {exit_code}")

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
