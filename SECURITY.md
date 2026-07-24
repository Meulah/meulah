# Security Policy

## Supported versions

The starter and framework are pre-1.0 projects. Security fixes are provided for
the current `0.2.x` line. Older minor lines are not supported.

| Version | Supported |
| --- | --- |
| `0.2.x` | Yes |
| `< 0.2` | No |

Pre-1.0 releases may contain breaking changes between minor versions. Review the
release notes and update to the latest supported patch before reporting an issue.

## Report a vulnerability privately

Do not disclose suspected vulnerabilities in a public issue, discussion, pull
request, commit, or social media post.

Use the repository's
[private vulnerability reporting form](https://github.com/Meulah/meulah/security/advisories/new).
Include:

- the affected starter and framework versions;
- a minimal reproduction or clear attack scenario;
- the security impact and required preconditions;
- any known mitigations or proposed fix; and
- a safe way to contact you about the report.

Do not include real credentials, access tokens, personal data, or other secrets
in the report. If GitHub cannot open the private form, contact the repository
owner through GitHub to request a private reporting channel without disclosing
the vulnerability details publicly.

## Coordinated disclosure

Please allow the maintainers reasonable time to reproduce, fix, test, and
release a correction before public disclosure. The reporter and maintainers
should agree on a disclosure timeline based on severity and exploitation risk.
After a fix is available, an advisory may credit the reporter when requested.

## Upload safety

The starter reserves `data/uploads/` for persistent application-owned files,
outside the public document root. This location alone does not make uploads
safe. Applications accepting files must:

- treat original filenames, extensions, and client MIME types as untrusted;
- generate unpredictable server-side filenames;
- validate file-size limits before retaining a file;
- inspect detected MIME content and validate it against an explicit allowlist;
- normalize storage destinations and prevent path traversal;
- prevent uploaded content from being executed by PHP, the web server, or other
  interpreters;
- serve public files through a deliberate, non-executable delivery mechanism;
- authorize every download of a private file;
- scan or process risky formats in an isolated environment when appropriate;
- back up persistent uploads according to the application's recovery needs; and
- never store permanent files in `runtime/`.

Do not expose `data/uploads/` with a symlink or make `data/` a web-server
document root. Generated runtime data belongs in `runtime/`; it may be cleared
when the application is stopped, while `data/` must be preserved.
