# Security Policy

`laranail/env-kit-headless` is a security-sensitive package: it reads and edits
`.env` files and handles application secrets. Secret values are redacted from
logs and exception messages by design. Please treat any vulnerability report
with care and follow the private disclosure process below.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

Please report security vulnerabilities privately. **Do not open a public GitHub
issue, pull request, or discussion for security problems.**

Email **opensource@simtabi.com** with:

- a description of the vulnerability and its impact;
- the affected version(s);
- steps to reproduce, or a proof of concept; and
- any suggested remediation, if you have one.

### What to expect

- We aim to acknowledge your report within **3 business days**.
- We will keep you informed as we investigate and work on a fix.
- We will coordinate a disclosure timeline with you and credit you in the
  release notes unless you prefer to remain anonymous.

Thank you for helping keep the package and its users safe.
