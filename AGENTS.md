# AGENTS.md

This file provides guidance to Coding Agents when working with code in this repository.

## Overview

A Laravel package that provides Global IDs — app-wide URIs that uniquely identify model instances (`gid://AppName/ModelClass/id`). Inspired by the Rails `globalid` gem. Supports both plain Global IDs and Signed Global IDs (with expiration and purpose-based verification).

## Commands

- **Run all tests:** `composer test` (uses `testbench package:test --parallel`)
- **Run a single test:** `vendor/bin/phpunit tests/path/to/TestFile.php`
- **Run a single test method:** `vendor/bin/phpunit --filter test_method_name`
- **Lint/format:** `composer lint` (runs Laravel Pint)

## Architecture

**Namespace:** `Tonysm\GlobalId`

### Core Classes

- **`GlobalId`** — Represents a GID URI. Creates, parses, and locates models from GID strings or base64-encoded versions. Static `$app` holds the default app name.
- **`SignedGlobalId`** (extends `GlobalId`) — Adds HMAC signing, expiration, and purpose-based verification. Uses `Verifier` with the app key via PBKDF2. Default expiry: 1 month.
- **`URI\GID`** — Low-level value object that parses/builds the `gid://` URI scheme. Handles URL encoding of model names and IDs.
- **`Locator`** — Service (scoped singleton) that resolves GIDs to model instances. Supports per-app custom locators and an `only` option to restrict which model classes can be located.
- **`Locators\BaseLocator`** — Default locator using Eloquent's `find`/`findMany`.
- **`Locators\LocatorContract`** — Interface for custom locators (`locate` and `locateMany`).
- **`Verifier`** — Signs and verifies SGID payloads (HMAC-SHA256).
- **`Models\HasGlobalIdentification`** — Trait added to Eloquent models providing `toGlobalId()`, `toGid()`, `toSignedGlobalId()`, `toSgid()`.

### Testing

Tests use Orchestra Testbench with an in-memory SQLite database. Test models live in `tests/Stubs/Models/`. The base `TestCase` creates `people` and `uuid_people` tables in `getEnvironmentSetUp`.

## Compatibility

Supports PHP 8.2+, Laravel 8.47 through 13.x. CI matrix tests across PHP 8.2–8.5, Laravel 10–13, on Ubuntu and Windows.
