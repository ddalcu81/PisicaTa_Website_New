# Project instructions

Work in this repository. Read `README.md` and any relevant project documentation before making changes. Inspect the existing source, dependency files, and Git status before scaffolding or installing anything. If the project's purpose is undocumented, ask what it should do before choosing a stack.

## Infrastructure

Before work involving hosting, DNS, SSH, staging, or deployment, read
[VPS-INFRASTRUCTURE.md](VPS-INFRASTRUCTURE.md). It documents the Ubuntu workstation, Raspberry Pi staging host, and OVHcloud VPS used by App Efemer. Verify the relevant details before relying on them. Access and approvals for App Efemer do not carry over to this project.

## Keep this project separate from App Efemer

- Keep source, configuration, and deployment tooling in this repository. Do not modify the App Efemer repository or reuse its deployment target.
- If this project shares a host with App Efemer, use a separate hostname, Caddy site block, document root, and deployment target. Preserve App Efemer's existing hostnames, routes, and services.
- Give this project its own secrets, storage, logs, and backups. If it needs a database, runtime account, PHP-FPM pool, worker, or scheduled job, use resources scoped to this project. For a static site, use only the resources needed to serve static files.
- Keep App Efemer credentials, keys, databases, uploads, and backups out of this project. Run automated tests against isolated fixtures or disposable databases.

## Deployment

Build and test locally on Ubuntu. Before deploying, confirm the hostname, target, exact Git revision, and scope authorized for this project. For changes to shared infrastructure, review the impact on existing sites before applying them. Use the project's own documented deployment process, preserve persistent data, and verify HTTPS and application health after activation. Keep a rollback path for each release.
