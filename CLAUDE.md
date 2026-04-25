# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a TYPO3 CMS extension (`ms_fontawesome`) that includes Font Awesome in TYPO3 websites asynchronously and non-blocking, with preload support for loading efficiency.

- Extension key: `ms_fontawesome`
- PHP namespace: `MarekSkopal\FontAwesome\`
- Composer package: `marekskopal/typo3-fontawesome`
- Requires TYPO3 ^13.4 || ^14.0, PHP >=8.2

## Commands

### Static Analysis
```sh
vendor/bin/phpstan analyse
```

### Code Style Check
```sh
vendor/bin/phpcs
```

### Code Style Fix
```sh
vendor/bin/phpcbf
```

## Architecture

The extension has a single entry point: `Classes/EventListener/FontAwesomeEventListener.php`.

**Flow:**
1. `FontAwesomeEventListener` listens to `BeforeStylesheetsRenderingEvent` via the `#[AsEventListener]` attribute.
2. On invocation, it reads TypoScript settings from `plugin.tx_msfontawesome.settings.fontSrc.` (an indexed array of Font Awesome CSS URLs).
3. For each font URL, it injects a `<link rel="preload" as="style">` plus an inline `<script>` that sets `onload` to switch `rel` to `"stylesheet"` (with `onload=null` guard to prevent re-triggering). A performance API fallback (`performance.getEntriesByName`) detects already-cached resources and activates them immediately. This is the async/non-blocking loading technique — more reliable than the `media="print"` hack, which fails on cached resources.
4. CSP nonces are applied to inline scripts via `ConsumableNonce` from the request attribute `nonce`.

**Configuration:**
- TypoScript template registered via `Configuration/TCA/Overrides/sys_template.php`
- Default TypoScript in `Configuration/Sets/FontAwesome/setup.typoscript`
- Site Set defined in `Configuration/Sets/FontAwesome/config.yaml`
- Services autowired/autoconfigured via `Configuration/Services.yaml`

**TypoScript settings format** (set by integrators):
```
plugin.tx_msfontawesome {
    settings {
        fontSrc {
            1 = https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css
        }
    }
}
```

Note: TypoScript arrays use dot-suffixed keys internally (e.g., `fontSrc.` not `fontSrc`).
