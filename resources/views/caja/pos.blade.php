<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caja POS</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/materialize-css@1.0.0/dist/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.12"></script>
    <style>
        :root {
            --pos-warm-bg: #d6dee7;
            --pos-warm-shell: #d6dee7;
            --pos-card-bg: #ffffff;
            --pos-card-head: #f8fafc;
            --pos-card-border: #e5e7eb;
            --pos-soft-surface: #f1f6fa;
            --pos-soft-surface-2: #e6eef5;
            --pos-soft-border: #c4d0dd;
            --pos-soft-border-strong: #b3c0cf;
            --pos-divider: rgba(107, 114, 128, 0.14);
            --pos-nav-bg: #f8fafc;
            --pos-nav-bg-2: #edf3f8;
            --pos-strong-surface-1: #3a4652;
            --pos-strong-surface-2: #465362;
            --pos-nav-border: rgba(176, 190, 205, 0.78);
            --pos-nav-hover: rgba(255, 255, 255, 0.98);
            --pos-nav-text: #1c2938;
            --pos-nav-muted: #67778a;
            --pos-nav-surface: rgba(255, 255, 255, 0.82);
            --pos-radius-md: 12px;
            --pos-radius-lg: 24px;
            --pos-shadow-soft: 0 8px 16px rgba(15, 23, 42, 0.08);
            --pos-shadow-card: 0 10px 24px rgba(15, 23, 42, 0.1);
            --pos-carrito-width: 67%;
            --pos-pagos-width: 33%;
            --pos-scan-input-width: 220px;
        }

        html, body {
            background: linear-gradient(180deg, #dbe3eb 0%, var(--pos-warm-bg) 100%);
            min-height: 100%;
        }

        body {
            color: #111827;
        }

        .pos-nav {
            margin: 0;
            background: linear-gradient(135deg, var(--pos-strong-surface-1) 0%, var(--pos-strong-surface-2) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .pos-nav .nav-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 26px;
            min-height: 64px;
        }

        .pos-nav .nav-wrapper.container {
            width: auto;
            max-width: none;
            margin: 0;
            padding: 0 18px;
        }

        .pos-nav .brand-logo {
            position: static !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            transform: none !important;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1;
            height: auto !important;
            padding: 10px 0;
            color: #f8fafc !important;
            flex: 0 0 auto;
        }

        .pos-nav .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: block;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.06) 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 5px;
            color: #f8fafc;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.08),
                0 6px 14px rgba(15, 23, 42, 0.12);
        }

        .pos-nav .brand-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .pos-nav .brand-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #f8fafc;
        }

        .pos-nav .brand-caption {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(226, 232, 240, 0.7);
        }

        .pos-nav .nav-actions,
        .pos-nav .nav-actions li {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pos-nav .nav-actions {
            flex: 1 1 auto;
            justify-content: flex-start;
            min-width: 0;
        }

        .pos-nav .nav-meta-start {
            margin-left: auto;
        }

        .pos-nav .nav-link-btn {
            height: 64px;
            padding: 0 2px !important;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            background: transparent;
            color: rgba(226, 232, 240, 0.74) !important;
            display: inline-flex;
            align-items: center;
            gap: 0;
            line-height: 1;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: none;
        }

        .pos-nav .nav-link-btn .material-icons {
            display: none;
        }

        .pos-nav .nav-link-btn:hover,
        .pos-nav .nav-link-btn:focus-visible {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.28);
            color: #f8fafc !important;
            outline: none;
        }

        .pos-nav .nav-link-btn.is-active {
            background: transparent;
            border-color: #f8fafc;
            color: #ffffff !important;
            box-shadow: none;
        }

        .pos-nav .nav-user-btn {
            height: 36px;
            padding: 0 0 0 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0 !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: none;
            white-space: nowrap;
            box-shadow: none;
        }

        .pos-nav .nav-user-btn:hover,
        .pos-nav .nav-user-btn:focus-visible {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.18);
            color: #ffffff !important;
            outline: none;
        }

        .pos-nav .nav-action-btn {
            height: 36px;
            padding: 0 12px !important;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: none;
        }

        .pos-nav .nav-action-btn:hover,
        .pos-nav .nav-action-btn:focus-visible {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.24);
            color: #ffffff !important;
            outline: none;
        }

        .pos-nav .nav-user-btn .material-icons,
        .pos-nav .nav-action-btn .material-icons {
            margin: 0 !important;
            font-size: 18px;
            color: inherit;
            opacity: 0.92;
        }

        .pos-nav .nav-chip {
            min-height: auto;
            padding: 0 0 0 12px;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0;
            background: transparent;
            color: rgba(226, 232, 240, 0.72);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            line-height: 1.3;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: none;
        }

        .pos-nav .nav-chip .material-icons {
            font-size: 15px;
            color: rgba(226, 232, 240, 0.56);
            opacity: 1;
        }

        .pos-nav .nav-chip.nav-chip-success {
            color: #cfe8da;
            border-left-color: rgba(167, 204, 179, 0.46);
        }

        .pos-nav .nav-chip.nav-chip-warn {
            color: #f5ddab;
            border-left-color: rgba(225, 195, 135, 0.46);
        }

        .pos-nav .nav-chip.nav-chip-danger {
            color: #f1c1ca;
            border-left-color: rgba(226, 175, 183, 0.42);
        }

        .pos-nav .nav-chip.nav-chip-note {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-nav #nav_date,
        .pos-nav #nav_clock {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.06em;
            color: rgba(226, 232, 240, 0.72);
        }

        .pos-nav .nav-mobile-trigger {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #f8fafc !important;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .pos-nav .nav-mobile-trigger:hover,
        .pos-nav .nav-mobile-trigger:focus-visible {
            background: rgba(255, 255, 255, 0.08);
            outline: none;
        }

        .pos-nav-mobile-strip {
            display: none;
        }

        .pos-nav-mobile-scroll {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding: 0 18px 12px;
            scrollbar-width: none;
        }

        .pos-nav-mobile-scroll::-webkit-scrollbar {
            display: none;
        }

        .pos-nav-mobile-chip {
            min-height: 30px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .pos-nav-mobile-chip .material-icons {
            font-size: 15px;
            color: rgba(226, 232, 240, 0.78);
        }

        .pos-nav-mobile-chip.is-success {
            color: #cfe8da;
            border-color: rgba(167, 204, 179, 0.34);
        }

        .pos-nav-mobile-chip.is-danger {
            color: #f1c1ca;
            border-color: rgba(226, 175, 183, 0.34);
        }

        .pos-nav-mobile-chip.is-note {
            color: rgba(226, 232, 240, 0.84);
        }

        .pos-user-dropdown {
            min-width: 220px !important;
            border-radius: 14px;
            border: 1px solid var(--pos-soft-border);
            background: #ffffff;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
            padding: 6px;
        }

        .pos-user-dropdown li > a {
            min-height: 40px;
            border-radius: 10px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .pos-user-dropdown li > a:hover,
        .pos-user-dropdown li > a:focus-visible {
            background: var(--pos-soft-surface);
        }

        .pos-user-dropdown li > a .material-icons {
            margin: 0 !important;
            color: #667085;
        }

        .pos-user-dropdown .divider {
            margin: 6px 0;
            background: rgba(148, 163, 184, 0.28);
        }

        .pos-mobile-nav {
            background: linear-gradient(180deg, #f9fbfd 0%, #eef3f8 100%);
            width: min(88vw, 340px);
            padding-bottom: max(16px, env(safe-area-inset-bottom, 0px));
        }

        .pos-mobile-nav .mobile-nav-head {
            background: linear-gradient(180deg, #ffffff 0%, #edf3f8 100%);
            border-bottom: 1px solid rgba(176, 190, 205, 0.6);
            padding-top: max(24px, calc(24px + env(safe-area-inset-top, 0px)));
        }

        .pos-mobile-nav .mobile-nav-brand {
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1c2938;
        }

        .pos-mobile-nav .mobile-nav-subtitle {
            margin-top: 6px;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #67778a;
        }

        .pos-mobile-nav .mobile-nav-meta {
            margin-top: 12px;
            line-height: 1.45;
            color: #425366;
        }

        .pos-mobile-nav li > a {
            color: #1f2937;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 12px;
        }

        .pos-mobile-nav li > a i.material-icons {
            margin: 0;
            color: #667085;
        }

        .pos-mobile-nav li > a:hover,
        .pos-mobile-nav li > a:focus-visible {
            background: rgba(255, 255, 255, 0.52);
        }

        .pos-mobile-nav .mobile-nav-open-btn {
            width: 100%;
            height: 40px;
            border-radius: 999px;
            background: #27374b;
            box-shadow: none;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
        }

        .pos-container {
            width: auto;
            max-width: none;
            margin: 18px 18px 24px;
        }

        .pos-main-grid {
            margin-bottom: 0;
        }

        .pos-main-column {
            width: var(--pos-carrito-width);
        }

        .pos-payments-column {
            width: var(--pos-pagos-width);
        }

        .pos-shell-card {
            margin: 0;
            width: 100%;
            padding: 14px 14px 10px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            background: linear-gradient(135deg, var(--pos-strong-surface-1) 0%, var(--pos-strong-surface-2) 100%);
        }

        .pos-card,
        .pos-sticky {
            background: var(--pos-card-bg);
            border: 1px solid var(--pos-card-border);
            border-radius: var(--pos-radius-lg);
            box-shadow: var(--pos-shadow-card);
        }

        .pos-card {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .pos-card .card-content {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        .sticky-head {
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--pos-card-head);
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: var(--pos-radius-md);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }

        .card-body {
            flex: 1 1 auto;
            overflow-y: auto;
            border-top: 1px solid var(--pos-divider);
            margin-top: 10px;
            padding-top: 10px;
            min-height: 0;
        }

        .card-carrito .card-body {
            overflow-x: auto;
        }

        .card-head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card-head-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
            padding: 8px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
        }

        .card-head-actions .btn,
        .card-head-actions .btn-flat {
            border-radius: 10px;
        }

        .card-head-actions form {
            margin: 0;
        }

        .card-carrito .card-head-actions,
        .card-pagos .card-head-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .card-carrito .card-head-actions #btn_open_buscar {
            margin-right: 0 !important;
            flex: 0 0 auto;
        }

        .card-carrito .card-head-actions .pos-scan-input {
            flex: 1 1 var(--pos-scan-input-width);
            min-width: 190px;
        }

        .card-carrito .card-head-actions form {
            flex: 0 0 auto;
        }

        .card-carrito .sticky-head {
            padding: 10px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        }

        .carrito-head-row {
            align-items: flex-start;
            gap: 16px;
        }

        .carrito-head-copy {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .carrito-head-copy .card-title {
            display: block;
            margin: 0;
            font-size: 26px;
            line-height: 1.05;
            font-weight: 900;
            color: #1f2937;
        }

        .carrito-head-subtitle {
            font-size: 13px;
            line-height: 1.4;
            color: #667085;
        }

        .card-carrito .carrito-head-actions {
            width: auto;
            justify-content: flex-end;
            padding: 0;
            border: none;
            background: transparent;
            gap: 10px;
            flex: 0 0 auto;
        }

        .card-carrito .carrito-head-actions form {
            margin: 0;
        }

        .carrito-toolbar-btn {
            height: 36px;
            padding: 0 22px !important;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
            box-shadow: none;
        }

        .carrito-toolbar-btn .material-icons {
            font-size: 18px;
            line-height: 1;
            margin: 0 !important;
        }

        .carrito-toolbar-btn.is-secondary {
            background: #f8fafc;
            color: #475467;
            border: 1px solid #dde4ee;
        }

        .carrito-toolbar-btn.is-secondary:hover,
        .carrito-toolbar-btn.is-secondary:focus-visible {
            background: #f2f5f9;
        }

        .carrito-toolbar-btn.is-primary {
            background: #182032;
            color: #ffffff;
        }

        .carrito-toolbar-btn.is-primary:hover,
        .carrito-toolbar-btn.is-primary:focus-visible {
            background: #111827;
        }

        .carrito-search-shell {
            position: relative;
            margin-top: 16px;
        }

        .carrito-search-shell::before {
            content: 'search';
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            font-family: 'Material Icons';
            font-size: 22px;
            color: #98a2b3;
            pointer-events: none;
        }

        .card-carrito .carrito-search-shell .pos-scan-input {
            width: 100%;
            height: 54px;
            padding: 0 18px 0 54px;
            border: 1px solid #dde3ec;
            border-radius: 20px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .card-carrito .carrito-search-shell .pos-scan-input::placeholder {
            color: #98a2b3;
            opacity: 1;
        }

        .card-carrito .carrito-search-shell .pos-scan-input:focus {
            border-color: #c7d2e0;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
            outline: none;
        }

        .card-pagos {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
        }

        .card-pagos .sticky-head {
            position: static;
            padding: 0;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        .card-pagos .card-title {
            display: block;
            margin: 0;
            font-size: 22px;
            line-height: 1.1;
            font-weight: 900;
            color: #1f2937;
        }

        .payments-panel-copy {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .payments-panel-subtitle {
            font-size: 13px;
            line-height: 1.45;
            color: #667085;
        }

        .card-pagos .card-head-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: 10px;
            margin-top: 16px;
            padding: 0;
            border: none;
            background: transparent;
        }

        .card-pagos .card-head-actions form {
            margin: 0;
            width: 100%;
        }

        .payments-action-btn {
            width: 100%;
            height: 44px;
            padding: 0 14px;
            border: 1px solid #dbe3ec;
            border-radius: 16px;
            background: #f8fafc;
            color: #344054;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }

        .payments-action-btn:hover:not(:disabled),
        .payments-action-btn:focus-visible:not(:disabled) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            outline: none;
        }

        .payments-action-btn.is-danger {
            background: #fff5f5;
            color: #b42318;
            border-color: #fecaca;
        }

        .payments-action-btn.is-danger:hover:not(:disabled),
        .payments-action-btn.is-danger:focus-visible:not(:disabled) {
            background: #ffe4e6;
            border-color: #fda4af;
        }

        .payments-action-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .payments-action-btn .material-icons {
            font-size: 18px;
        }

        .payments-summary-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
        }

        .payments-summary-card {
            padding: 11px 13px;
            border-radius: 16px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #fbfcfe 0%, var(--pos-soft-surface) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.86),
                0 0 0 1px rgba(179, 192, 207, 0.42),
                0 6px 14px rgba(15, 23, 42, 0.07);
        }

        .payments-summary-label {
            display: block;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 700;
            color: #667085;
        }

        .payments-summary-value {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-top: 4px;
            color: #1f2937;
        }

        .payments-summary-value .currency {
            font-size: 15px;
            line-height: 1;
            font-weight: 800;
        }

        .payments-summary-value strong {
            font-size: 19px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .card-pagos .card-body {
            border-top: none;
            margin-top: 16px;
            padding-top: 0;
        }

        .payments-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payments-entry-card {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid var(--pos-soft-border);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f2f7fb 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-align: left;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.84),
                0 0 0 1px rgba(179, 192, 207, 0.34),
                0 7px 16px rgba(15, 23, 42, 0.07);
        }

        button.payments-entry-card {
            cursor: pointer;
            transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }

        button.payments-entry-card:hover,
        button.payments-entry-card:focus-visible {
            border-color: var(--pos-soft-border-strong);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.5),
                0 10px 18px rgba(15, 23, 42, 0.1);
            transform: translateY(-1px);
            outline: none;
        }

        .payments-entry-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .payments-entry-title {
            font-size: 14px;
            line-height: 1.2;
            font-weight: 800;
            color: #1f2937;
        }

        .payments-entry-note {
            font-size: 12px;
            line-height: 1.35;
            color: #667085;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .payments-entry-amount {
            flex: 0 0 auto;
            white-space: nowrap;
            font-size: 14px;
            line-height: 1;
            font-weight: 900;
            color: #111827;
        }

        .payments-empty-card {
            padding: 16px 18px;
            border-radius: 18px;
            border: 1px dashed var(--pos-soft-border-strong);
            background: linear-gradient(180deg, #fbfcfe 0%, var(--pos-soft-surface) 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82), 0 0 0 1px rgba(179, 192, 207, 0.24);
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
        }

        .pos-scan-input {
            width: var(--pos-scan-input-width);
            max-width: 100%;
            height: 36px;
            padding: 0 10px;
            border: 1px solid #cfd8dc;
            border-radius: 10px;
            box-sizing: border-box;
            background: #fff;
        }

        .pos-sticky {
            position: sticky;
            bottom: 0;
            z-index: 30;
            margin-top: 12px;
            border: none;
            background: linear-gradient(135deg, var(--pos-strong-surface-1) 0%, var(--pos-strong-surface-2) 100%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.24);
        }

        .pos-sticky .card-content {
            padding: 11px 14px;
        }

        .pos-sticky .controls {
            display: grid;
            grid-template-columns: minmax(260px, 1.7fr) repeat(3, minmax(150px, 0.72fr)) minmax(220px, 1.05fr);
            gap: 12px;
            align-items: stretch;
            margin: 0;
        }

        .pos-summary-main,
        .pos-summary-stat,
        .pos-summary-action {
            min-width: 0;
        }

        .pos-summary-main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 12px 4px;
        }

        .pos-summary-label {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgba(226, 232, 240, 0.72);
        }

        .pos-summary-total {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 6px;
            color: #fff;
        }

        .pos-summary-total .currency {
            font-size: clamp(22px, 1.8vw, 28px);
            line-height: 1;
            font-weight: 800;
        }

        .pos-summary-total strong {
            font-size: clamp(34px, 3vw, 46px);
            line-height: 0.95;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .pos-summary-stat {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            min-height: 90px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(55, 65, 81, 0.45);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        .pos-summary-stat .value {
            color: #fff;
            font-size: clamp(16px, 1.45vw, 23px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .pos-summary-stat.shortcut .value {
            letter-spacing: 0;
        }

        .pos-summary-action {
            display: flex;
        }

        .pos-confirm-btn {
            width: 100%;
            min-height: 90px;
            padding: 14px 18px;
            border: none;
            border-radius: 18px;
            background: #f4f5f8;
            color: #111827;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            text-align: left;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92), 0 10px 18px rgba(15, 23, 42, 0.12);
        }

        .pos-confirm-btn:hover:not(:disabled),
        .pos-confirm-btn:focus-visible:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95), 0 14px 22px rgba(15, 23, 42, 0.16);
            outline: none;
        }

        .pos-confirm-btn .pos-summary-label {
            color: #4b5563;
        }

        .pos-confirm-btn-text {
            font-size: clamp(20px, 1.7vw, 26px);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .pos-confirm-btn-text.is-disabled {
            font-size: clamp(16px, 1.2vw, 20px);
            line-height: 1.12;
        }

        .pos-confirm-btn:disabled {
            opacity: 0.62;
            cursor: not-allowed;
            transform: none;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .cart-list-shell {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 1060px;
        }

        .cart-list-head,
        .cart-list-row {
            display: grid;
            grid-template-columns:
                minmax(240px, 2.15fr)
                72px
                98px
                minmax(138px, 1fr)
                minmax(112px, 0.8fr)
                minmax(112px, 0.8fr)
                minmax(112px, 0.8fr)
                minmax(138px, 1fr)
                96px;
            gap: 12px;
            align-items: center;
        }

        .cart-list-head {
            padding: 14px 20px;
            border-radius: 18px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, var(--pos-soft-surface) 0%, var(--pos-soft-surface-2) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.34);
            color: #667085;
            font-size: 13px;
            font-weight: 700;
        }

        .cart-list-row {
            position: relative;
            padding: 18px 20px;
            border-radius: 20px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.86),
                0 0 0 1px rgba(179, 192, 207, 0.3),
                0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .cart-list-row::before {
            content: '';
            position: absolute;
            left: 16px;
            top: 16px;
            bottom: 16px;
            width: 4px;
            border-radius: 999px;
            background: #374151;
        }

        .cart-product-cell {
            min-width: 0;
            padding-left: 18px;
        }

        .cart-product-sku {
            display: block;
            font-size: 14px;
            line-height: 1.2;
            font-weight: 800;
            color: #1f2937;
        }

        .cart-product-label {
            display: block;
            margin-top: 7px;
            font-size: 13px;
            line-height: 1.35;
            color: #667085;
        }

        .cart-stock-cell,
        .cart-qty-cell,
        .cart-price-cell,
        .cart-subtotal-cell,
        .cart-action-cell {
            min-width: 0;
        }

        .cart-stock-value,
        .cart-money-value,
        .cart-subtotal-value {
            font-size: 15px;
            font-weight: 800;
            color: #111827;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .cart-stock-note {
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #b91c1c;
        }

        .cart-inline-form,
        .cart-action-form {
            margin: 0;
            width: 100%;
        }

        .cart-pill-input,
        .cart-action-btn {
            width: 100%;
            height: 42px;
            border-radius: 16px;
        }

        .cart-pill-input {
            border: 1px solid #dbe2ea;
            background: #f9fafb;
            padding: 0 14px;
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            box-sizing: border-box;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
            appearance: textfield;
            -moz-appearance: textfield;
        }

        .cart-pill-input::-webkit-outer-spin-button,
        .cart-pill-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cart-qty-input {
            text-align: center;
        }

        .cart-money-input {
            position: relative;
            width: 100%;
        }

        .cart-money-input .prefix {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            font-size: 13px;
            font-weight: 800;
            color: #667085;
        }

        .cart-money-input .cart-pill-input {
            padding-left: 30px;
            text-align: right;
        }

        .cart-money-input.is-compact .prefix {
            left: 12px;
            font-size: 12px;
        }

        .cart-money-input.is-compact .cart-pill-input,
        .cart-price-display.is-compact,
        .cart-money-value.is-compact,
        .cart-subtotal-value.is-compact {
            font-size: 13px;
            letter-spacing: -0.015em;
        }

        .cart-price-display {
            display: flex;
            align-items: center;
            min-height: 42px;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .cart-action-btn {
            border: none;
            background: #eef2f6;
            color: #374151;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
            box-shadow: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px !important;
        }

        .cart-action-btn:hover,
        .cart-action-btn:focus-visible {
            background: #e5ebf2;
            box-shadow: none;
            outline: none;
        }

        .cart-mobile-label {
            display: none;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .payment-row-card {
            margin: 0 0 12px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.86),
                0 0 0 1px rgba(179, 192, 207, 0.28),
                0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .payment-row-card.is-loading {
            opacity: 0.72;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        .payment-row-card.is-emphasized {
            border-color: #9bb8cf;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.92),
                0 0 0 1px rgba(155, 184, 207, 0.34),
                0 16px 30px rgba(15, 23, 42, 0.12);
            animation: payment-row-emphasis 1.2s ease;
        }

        @keyframes payment-row-emphasis {
            0% {
                transform: translateY(10px);
                opacity: 0.42;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .payment-row-form {
            margin: 0;
        }

        .payment-field-block {
            position: relative;
            display: flex;
            flex-direction: column;
            margin-top: 0;
            margin-bottom: 0;
        }

        .payment-field-label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #667085;
        }

        .payment-field-block.has-icon .payment-field-icon {
            position: absolute;
            left: 14px;
            bottom: 14px;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            color: #98a2b3;
            pointer-events: none;
        }

        .payment-row-card .input-field.payment-field-block {
            margin-top: 0;
            margin-bottom: 0;
        }

        .payment-field-block .select-wrapper {
            margin: 0;
        }

        .payment-text-input,
        .payment-row-card .select-wrapper input.select-dropdown {
            height: 48px !important;
            margin: 0 !important;
            line-height: 48px !important;
            border: 1px solid var(--pos-soft-border) !important;
            border-radius: 16px !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.84);
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
            box-sizing: border-box;
        }

        .payment-text-input {
            width: 100%;
            padding: 0 16px;
        }

        .payment-field-block.has-icon .payment-text-input,
        .payment-field-block.has-icon .select-wrapper input.select-dropdown {
            padding-left: 44px !important;
        }

        .payment-row-card .select-wrapper input.select-dropdown {
            padding-right: 42px !important;
            padding-left: 16px !important;
            text-align: left !important;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .payment-row-card .select-wrapper .caret {
            right: 14px;
            top: 50%;
            margin: 0;
            transform: translateY(-50%);
        }

        .payment-text-input:focus,
        .payment-row-card .select-wrapper input.select-dropdown:focus {
            border-color: var(--pos-soft-border-strong) !important;
            box-shadow: 0 0 0 4px rgba(179, 192, 207, 0.16) !important;
            outline: none;
        }

        .payment-row-card .select-wrapper .caret {
            fill: #98a2b3;
        }

        .payment-field-plan .select-wrapper input.select-dropdown {
            font-size: 13px;
            font-weight: 800;
            padding-left: 16px !important;
            padding-right: 48px !important;
            text-align: left !important;
            text-align-last: left !important;
            direction: ltr !important;
            text-indent: 0 !important;
        }

        .payment-row-summary {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 22px;
        }

        .payment-pill {
            min-height: 32px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, var(--pos-soft-surface) 100%);
            color: #475467;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            line-height: 1.2;
            font-weight: 800;
            white-space: nowrap;
        }

        .payment-pill.is-warning {
            border-color: #f0d18b;
            background: linear-gradient(180deg, #fff9ef 0%, #ffefc8 100%);
            color: #9a6700;
        }

        .payment-pill.is-accent {
            border-color: #bfd1e3;
            background: linear-gradient(180deg, #f3f8fd 0%, #e3edf8 100%);
            color: #24415f;
        }

        .payment-pill.is-neutral {
            background: linear-gradient(180deg, #ffffff 0%, #eef3f8 100%);
        }

        .payment-customer-note,
        .payment-customer-empty {
            font-size: 12px;
            line-height: 1.45;
            color: #667085;
        }

        .payment-customer-note {
            margin-top: 8px;
        }

        .payment-customer-empty {
            padding-top: 8px;
        }

        .payment-row-actions {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding-top: 22px;
        }

        .payment-remove-btn {
            height: 44px;
            padding: 0 18px !important;
            border: 1px solid #fecdd3;
            border-radius: 999px;
            background: #fff1f2;
            color: #b42318;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: none;
        }

        .payment-remove-btn:hover,
        .payment-remove-btn:focus-visible {
            background: #ffe4e6;
            box-shadow: none;
            outline: none;
        }

        .payment-remove-btn .material-icons {
            margin: 0 !important;
            font-size: 18px;
        }

        .pos-modal {
            border-radius: 24px;
            overflow: hidden !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
            border: 1px solid var(--pos-soft-border);
            box-shadow: 0 28px 56px rgba(15, 23, 42, 0.18);
        }

        .venta-summary-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
        }

        .venta-summary-card {
            min-height: 98px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, var(--pos-soft-surface) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.2),
                0 10px 20px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
        }

        .venta-summary-card.is-items {
            border-color: #bfdbc8;
            background: linear-gradient(180deg, #f4fcf7 0%, #e0f3e7 100%);
        }

        .venta-summary-card.is-final {
            border-color: #bfd1e3;
            background: linear-gradient(180deg, #f1f7fd 0%, #deebf9 100%);
        }

        .venta-summary-card.is-meta {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
        }

        .venta-summary-label {
            color: #667085;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .venta-summary-value {
            color: #182032;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.15;
        }

        .venta-summary-value.is-amount {
            display: flex;
            align-items: baseline;
            gap: 6px;
            font-variant-numeric: tabular-nums;
        }

        .venta-summary-value.is-amount .currency {
            font-size: 19px;
            font-weight: 800;
        }

        .venta-summary-value.is-amount strong {
            font-size: 31px;
            line-height: 1;
            font-weight: 900;
        }

        .venta-modal-stack {
            display: grid;
            gap: 14px;
        }

        .venta-section-card {
            border-radius: 22px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, var(--pos-soft-surface) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.2),
                0 14px 28px rgba(15, 23, 42, 0.08);
            padding: 18px;
            margin-bottom: 14px;
        }

        .venta-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .venta-section-title {
            color: #182032;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.1;
        }

        .venta-section-subtitle {
            margin-top: 4px;
            color: #667085;
            font-size: 13px;
            line-height: 1.4;
        }

        .venta-table-shell {
            border-radius: 18px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #fbfdff 0%, #f4f8fc 100%);
            overflow: auto;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.92),
                0 0 0 1px rgba(179, 192, 207, 0.18);
        }

        .venta-items-table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .venta-items-table thead th {
            padding: 14px 16px;
            background: linear-gradient(180deg, #f9fbfd 0%, #eef4f9 100%);
            color: #667085;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--pos-soft-border);
        }

        .venta-items-table tbody td {
            padding: 15px 16px;
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
            border-bottom: 1px solid rgba(196, 208, 221, 0.72);
        }

        .venta-items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .venta-items-table tbody td:first-child {
            font-weight: 800;
        }

        .venta-items-table tbody td:not(:first-child) {
            font-variant-numeric: tabular-nums;
        }

        .venta-payments-list {
            display: grid;
            gap: 12px;
        }

        .venta-payment-card {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 15px 16px;
            border-radius: 18px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 1px rgba(179, 192, 207, 0.16);
        }

        .venta-payment-type {
            color: #182032;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.15;
        }

        .venta-payment-meta {
            margin-top: 4px;
            color: #667085;
            font-size: 12px;
            line-height: 1.4;
        }

        .venta-payment-amount {
            min-width: 108px;
            white-space: nowrap;
            text-align: right;
            color: #182032;
            font-variant-numeric: tabular-nums;
        }

        .venta-payment-amount strong {
            display: block;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.1;
        }

        .venta-payment-extra {
            margin-top: 4px;
            color: #667085;
            font-size: 12px;
            line-height: 1.35;
        }

        .last-sale-rail {
            display: none;
            position: fixed;
            top: 74px;
            right: 16px;
            width: 250px;
            z-index: 20;
        }

        .last-sale-rail .card {
            margin: 0;
            border-radius: 20px;
            overflow: hidden;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
        }

        .last-sale-rail .rail-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .last-sale-rail .rail-actions .btn {
            width: 100%;
            border-radius: 10px;
        }

        .modal {
            border-radius: 20px;
            overflow: hidden !important;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
        }

        .modal .modal-content {
            background: #ffffff;
        }

        .modal .modal-footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .pos-modal .modal-content {
            padding: 0;
            background: transparent;
        }

        .pos-modal .modal-footer {
            background: linear-gradient(180deg, #f9fbfd 0%, #edf3f8 100%);
            border-top: 1px solid var(--pos-soft-border);
            padding: 14px 18px;
            gap: 10px;
        }

        #pago_modal.modal {
            max-height: min(92vh, 840px);
            height: min(92vh, 840px);
        }

        #venta_modal.modal {
            max-height: min(92vh, 840px);
            height: min(92vh, 840px);
        }

        #pago_modal.modal.open {
            display: flex !important;
            flex-direction: column;
            align-items: stretch;
        }

        #venta_modal.modal.open {
            display: flex !important;
            flex-direction: column;
            align-items: stretch;
        }

        #pago_modal .modal-content,
        #pago_modal_content {
            position: relative;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            width: 100%;
            height: auto;
            max-height: none;
            min-height: 0;
            overflow: hidden;
        }

        #venta_modal .modal-content,
        #venta_modal_content {
            position: relative;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            width: 100%;
            height: auto;
            max-height: none;
            min-height: 0;
            overflow: hidden;
        }

        #pago_modal .pos-modal-head,
        #pago_modal .modal-footer.payment-modal-footer {
            flex: 0 0 auto;
        }

        #venta_modal .pos-modal-head,
        #venta_modal .modal-footer {
            flex: 0 0 auto;
        }

        #pago_modal .modal-footer.payment-modal-footer {
            position: relative;
            bottom: auto;
            width: 100%;
            margin-top: auto;
        }

        #venta_modal .modal-footer {
            position: relative;
            bottom: auto;
            width: 100%;
            margin-top: auto;
        }

        #pagos_modal_body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: #b7c5d4 rgba(236, 242, 247, 0.9);
            padding-bottom: 28px;
        }

        #venta_modal_body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: #b7c5d4 rgba(236, 242, 247, 0.9);
            padding-bottom: 28px;
        }

        #pagos_modal_body::-webkit-scrollbar {
            width: 10px;
        }

        #venta_modal_body::-webkit-scrollbar {
            width: 10px;
        }

        #pagos_modal_body::-webkit-scrollbar-track {
            background: rgba(236, 242, 247, 0.92);
            border-radius: 999px;
        }

        #venta_modal_body::-webkit-scrollbar-track {
            background: rgba(236, 242, 247, 0.92);
            border-radius: 999px;
        }

        #pagos_modal_body::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #c5d2de 0%, #9fb0c2 100%);
            border-radius: 999px;
            border: 2px solid rgba(236, 242, 247, 0.92);
        }

        #venta_modal_body::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #c5d2de 0%, #9fb0c2 100%);
            border-radius: 999px;
            border: 2px solid rgba(236, 242, 247, 0.92);
        }

        @media (max-width: 600px), (max-height: 600px) {
            #toast-container {
                top: max(12px, env(safe-area-inset-top, 0px)) !important;
                bottom: auto !important;
                left: 12px !important;
                right: 12px !important;
                transform: none !important;
                width: auto !important;
                min-width: 0 !important;
            }

            #toast-container .toast {
                float: none;
                width: 100%;
                min-height: 0;
                margin: 0 0 8px;
                border-radius: 16px;
            }
        }

        .pos-modal-head {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 14px 14px 10px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 251, 254, 0.96) 100%);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(179, 192, 207, 0.28);
        }

        .pos-modal-head-card {
            padding: 16px 18px;
            border-radius: 20px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, var(--pos-soft-surface) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 1px rgba(179, 192, 207, 0.24),
                0 10px 22px rgba(15, 23, 42, 0.07);
        }

        .pos-modal-head-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pos-modal-head-copy {
            min-width: 240px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .pos-modal-icon-badge {
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, #ebf2f8 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }

        .pos-modal-icon-badge .material-icons {
            font-size: 22px;
            color: #526070;
        }

        .pos-modal-title {
            margin: 0;
            font-size: 22px;
            line-height: 1.08;
            font-weight: 900;
            color: #1f2937;
        }

        .pos-modal-subtitle {
            margin-top: 6px;
            font-size: 13px;
            line-height: 1.45;
            color: #667085;
        }

        .pos-modal-close {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid var(--pos-soft-border);
            background: #ffffff;
            color: #475467;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }

        .pos-modal-close:hover,
        .pos-modal-close:focus-visible {
            background: var(--pos-soft-surface);
            border-color: var(--pos-soft-border-strong);
            transform: translateY(-1px);
            outline: none;
        }

        .pos-modal-close .material-icons {
            font-size: 20px;
            line-height: 1;
        }

        .pos-modal-search-wrap {
            padding: 0 14px 10px;
        }

        .pos-modal-search-field {
            position: relative;
        }

        .pos-modal-search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: #98a2b3;
            pointer-events: none;
        }

        .pos-modal-search-input {
            width: 100%;
            height: 54px;
            padding: 0 110px 0 54px;
            border: 1px solid var(--pos-soft-border);
            border-radius: 20px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f2f7fb 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.88);
            box-sizing: border-box;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .pos-modal-search-input::placeholder {
            color: #98a2b3;
            opacity: 1;
        }

        .pos-modal-search-input:focus {
            border-color: var(--pos-soft-border-strong);
            box-shadow: 0 0 0 4px rgba(179, 192, 207, 0.18);
            outline: none;
        }

        .pos-modal-search-clear {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .pos-modal-progress {
            margin: 10px 2px 0;
            height: 4px;
            border-radius: 999px;
            overflow: hidden;
            background: #e6eef5;
        }

        .pos-modal-scroll {
            padding: 12px 14px 16px;
        }

        .pos-modal-result-shell {
            padding: 8px;
            border-radius: 22px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #f9fbfd 0%, #eef4f8 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 1px rgba(179, 192, 207, 0.22);
        }

        .pos-modal-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
            margin-top: 16px;
        }

        .pos-modal-summary-grid .payments-summary-card {
            padding: 12px 14px;
        }

        .pos-modal-summary-grid .payments-summary-value strong {
            font-size: 18px;
        }

        .pos-modal-summary-grid .payments-summary-card.is-total {
            border-color: #bfdbc8;
            background: linear-gradient(180deg, #f4fcf7 0%, #e0f3e7 100%);
        }

        .pos-modal-summary-grid .payments-summary-card.is-accent {
            border-color: #bfd1e3;
            background: linear-gradient(180deg, #f1f7fd 0%, #deebf9 100%);
        }

        .pos-modal-summary-grid .payments-summary-card.is-warning {
            background: linear-gradient(180deg, #fff9ef 0%, #fff1d7 100%);
        }

        .pos-modal-summary-grid .payments-summary-card.is-balance-danger {
            border-color: #efc2c7;
            background: linear-gradient(180deg, #fff5f6 0%, #ffe3e6 100%);
        }

        .pos-modal-summary-grid .payments-summary-card.is-balance-neutral {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
        }

        .pos-modal-summary-grid .payments-summary-card.is-success {
            border-color: #aacdbb;
            background: linear-gradient(180deg, #e9f6ef 0%, #cfe8da 100%);
        }

        .pos-modal-state {
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px dashed var(--pos-soft-border-strong);
            background: linear-gradient(180deg, #fbfcfe 0%, var(--pos-soft-surface) 100%);
            color: #667085;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            line-height: 1.45;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.84);
        }

        .pos-modal-state .material-icons {
            font-size: 18px;
            color: #64748b;
        }

        .pos-modal-footer-note {
            margin-right: auto;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .payment-modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .payment-modal-footer-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .payment-modal-footer-actions form {
            margin: 0;
        }

        .pos-modal-footer-btn {
            height: 42px;
            padding: 0 18px !important;
            border-radius: 999px;
            border: 1px solid var(--pos-soft-border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: none;
        }

        .pos-modal-footer-btn.is-primary {
            background: #182032;
            border-color: #182032;
            color: #ffffff;
        }

        .pos-modal-footer-btn.is-primary:hover,
        .pos-modal-footer-btn.is-primary:focus-visible {
            background: #111827;
            border-color: #111827;
            outline: none;
        }

        .pos-modal-footer-btn.is-secondary {
            background: #ffffff;
            color: #475467;
        }

        .pos-modal-footer-btn.is-secondary:hover,
        .pos-modal-footer-btn.is-secondary:focus-visible {
            background: var(--pos-soft-surface);
            border-color: var(--pos-soft-border-strong);
            outline: none;
        }

        .pos-modal-footer-btn .material-icons {
            margin: 0 !important;
            font-size: 18px;
        }

        .modal-inline-state {
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px dashed var(--pos-soft-border-strong);
            background: linear-gradient(180deg, #fbfcfe 0%, var(--pos-soft-surface) 100%);
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
        }

        .modal-inline-state.is-error {
            border-style: solid;
            border-color: #fecaca;
            background: linear-gradient(180deg, #fff7f7 0%, #ffe8e8 100%);
            color: #b42318;
        }

        .modal-search-results {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-search-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border-radius: 18px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.86),
                0 0 0 1px rgba(179, 192, 207, 0.24),
                0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .modal-search-item-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .modal-search-item-title {
            font-size: 16px;
            line-height: 1.2;
            font-weight: 900;
            color: #1f2937;
        }

        .modal-search-item-sku {
            font-size: 13px;
            line-height: 1.3;
            font-weight: 700;
            color: #667085;
        }

        .modal-search-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            color: #64748b;
        }

        .modal-search-branch {
            font-weight: 700;
        }

        .modal-search-badge {
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--pos-soft-border);
            background: linear-gradient(180deg, #ffffff 0%, var(--pos-soft-surface) 100%);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 800;
            color: #475467;
        }

        .modal-search-badge.is-positive {
            border-color: #b8dfc8;
            background: linear-gradient(180deg, #f5fcf8 0%, #e6f6ed 100%);
            color: #166534;
        }

        .modal-search-badge.is-danger {
            border-color: #f5c2c7;
            background: linear-gradient(180deg, #fff6f7 0%, #ffe8ea 100%);
            color: #b42318;
        }

        .modal-search-side {
            min-width: 130px;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .modal-search-price {
            font-size: 19px;
            line-height: 1;
            font-weight: 900;
            color: #1f2937;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .modal-search-action {
            margin: 0;
        }

        .modal-search-add-btn {
            height: 40px;
            padding: 0 18px !important;
            border: 1px solid #182032;
            border-radius: 999px;
            background: #182032;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: none;
        }

        .modal-search-add-btn:hover,
        .modal-search-add-btn:focus-visible {
            background: #111827;
            border-color: #111827;
            box-shadow: none;
            outline: none;
        }

        .modal-search-add-btn:disabled {
            border-color: #d0d7e2;
            background: #eef2f6;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .dropdown-content {
            border-radius: 10px;
            overflow: hidden;
        }

        .select-wrapper input.select-dropdown {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pos-nav ul.right {
            position: static !important;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            flex: 1 1 auto;
            min-width: 0;
        }

        .pos-nav ul.right .btn-flat,
        .pos-nav ul.right .nav-chip {
            white-space: nowrap;
            flex-shrink: 0;
        }

        .pos-nav i.material-icons {
            text-transform: none !important;
        }

        @media (min-width: 601px) {
            .card-carrito,
            .card-pagos {
                height: calc(100vh - 236px);
            }
        }

        @media (max-width: 992px) {
            .pos-nav .nav-wrapper {
                min-height: 60px;
                justify-content: space-between;
                gap: 12px;
            }

            .pos-nav .brand-logo {
                min-width: 0;
                max-width: calc(100% - 56px);
            }

            .pos-nav .brand-copy {
                min-width: 0;
            }

            .pos-nav .brand-name {
                font-size: 14px;
                letter-spacing: 0.1em;
            }

            .pos-nav .brand-caption {
                display: none;
            }

            .pos-nav .nav-mobile-trigger {
                display: flex !important;
                margin-left: auto;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                background: rgba(255, 255, 255, 0.08);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
            }

            .pos-nav-mobile-strip {
                display: block;
            }

            .pos-main-column,
            .pos-payments-column {
                width: 100% !important;
            }

            .card-carrito .card-head-row,
            .card-pagos .card-head-row,
            .pos-sticky .controls {
                align-items: flex-start;
            }

            .card-carrito .carrito-head-actions {
                width: 100%;
                justify-content: flex-start;
                gap: 8px;
            }

            .card-carrito .carrito-search-shell .pos-scan-input {
                min-width: 0;
            }

            .card-pagos .card-head-actions {
                grid-template-columns: 1fr;
            }

            .payments-entry-card {
                padding: 14px 16px;
            }

            .payments-entry-note {
                white-space: normal;
            }

            .pos-modal-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payment-row-summary,
            .payment-row-actions {
                justify-content: flex-start;
                padding-top: 12px;
            }

            .pos-sticky .controls {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pos-summary-main,
            .pos-summary-action {
                grid-column: 1 / -1;
            }

            .pos-summary-main {
                padding: 8px 2px 2px;
            }

            .pos-confirm-btn {
                min-height: 84px;
            }

            .cart-list-head,
            .cart-list-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .cart-list-head {
                display: none;
            }

            .cart-product-cell,
            .cart-action-cell {
                grid-column: 1 / -1;
            }

            .cart-mobile-label {
                display: block;
            }

            .cart-action-form {
                width: 100%;
            }
        }

        @media (min-width: 1281px) {
            .pos-nav .nav-wrapper.container {
                padding-left: 18px;
                padding-right: 18px;
            }

            .pos-container {
                margin-left: 18px;
                margin-right: 18px;
                width: auto;
            }

            .row > .col.l10 {
                width: var(--pos-carrito-width);
                margin-left: 0;
            }

            .row > .col.l2 {
                width: var(--pos-pagos-width);
                margin-left: 0;
            }
        }

        @media (max-width: 1280px) {
            #nav_clock,
            #nav_date,
            .pos-nav .brand-caption {
                display: none;
            }

            .last-sale-rail {
                display: none;
            }

            .cart-list-head,
            .cart-list-row {
                grid-template-columns:
                    minmax(220px, 2fr)
                    64px
                    90px
                    minmax(128px, 0.95fr)
                    minmax(102px, 0.76fr)
                    minmax(102px, 0.76fr)
                    minmax(102px, 0.76fr)
                    minmax(128px, 0.95fr)
                    88px;
                gap: 12px;
            }
        }

        @media (max-width: 992px) {
            .venta-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .pos-nav {
                margin: 0;
                border-radius: 0;
            }

            .pos-nav .nav-wrapper.container {
                padding: 0 12px;
            }

            .pos-nav .nav-wrapper {
                min-height: 56px;
            }

            .pos-nav .brand-logo {
                gap: 8px;
                padding: 8px 0;
            }

            .pos-nav .brand-mark {
                width: 32px;
                height: 32px;
                border-radius: 9px;
            }

            .pos-nav .brand-name {
                font-size: 13px;
            }

            .pos-nav-mobile-scroll {
                padding: 0 12px 10px;
                gap: 6px;
            }

            .pos-nav-mobile-chip {
                min-height: 28px;
                padding: 0 10px;
                font-size: 10px;
            }

            .pos-mobile-nav {
                width: min(92vw, 320px);
            }

            #buscar_modal.modal,
            #pago_modal.modal,
            #venta_modal.modal {
                width: 95% !important;
            }

            #buscar_modal .modal-content,
            #pago_modal .modal-content,
            #venta_modal .modal-content {
                padding: 0;
            }

            #buscar_modal .modal-footer,
            #pago_modal .modal-footer,
            #venta_modal .modal-footer {
                padding: 10px 12px;
                flex-wrap: wrap;
            }

            .pos-shell-card {
                padding: 10px 10px 8px;
            }

            .pos-container {
                margin-left: 10px;
                margin-right: 10px;
            }

            .card-carrito,
            .card-pagos {
                height: auto;
            }

            .card-carrito .card-body {
                max-height: 220px;
            }

            .card-pagos .card-body {
                max-height: 240px;
            }

            .card-carrito .card-head-actions,
            .card-pagos .card-head-actions {
                width: 100%;
            }

            .card-carrito .carrito-head-actions,
            .card-carrito .carrito-head-actions form,
            .card-pagos .card-head-actions form {
                width: 100%;
            }

            .card-carrito .carrito-head-actions #btn_open_buscar,
            .card-carrito .carrito-head-actions form button,
            .payments-action-btn {
                width: 100%;
            }

            .card-carrito .carrito-search-shell .pos-scan-input {
                width: 100%;
                height: 50px;
                padding-left: 48px;
            }

            .pos-sticky {
                position: static;
            }

            .pos-sticky .controls {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .pos-sticky .card-content {
                padding: 12px;
            }

            .pos-summary-main,
            .pos-summary-action {
                grid-column: 1 / -1;
            }

            .pos-summary-main {
                padding: 4px 0 2px;
            }

            .pos-summary-total strong {
                font-size: 30px;
            }

            .pos-summary-stat {
                min-height: 72px;
                padding: 12px 14px;
            }

            .pos-summary-stat.shortcut {
                display: none;
            }

            .pos-confirm-btn {
                min-height: 76px;
            }

            .payments-summary-value strong {
                font-size: 20px;
            }

            .pos-modal-head {
                padding: 10px 10px 8px;
            }

            .pos-modal-head-card,
            .payment-row-card {
                padding: 14px;
                border-radius: 18px;
            }

            .venta-section-card {
                padding: 14px;
                border-radius: 18px;
            }

            .pos-modal-search-wrap,
            .pos-modal-scroll,
            #pagos_modal_body {
                padding-left: 10px;
                padding-right: 10px;
            }

            .pos-modal-summary-grid {
                grid-template-columns: 1fr;
            }

            .venta-summary-value.is-amount strong {
                font-size: 26px;
            }

            .venta-payment-card {
                padding: 14px;
            }

            .modal-search-item {
                flex-direction: column;
            }

            .modal-search-side {
                width: 100%;
                min-width: 0;
                align-items: stretch;
            }

            .modal-search-action,
            .modal-search-action button,
            .payment-row-actions form,
            .payment-remove-btn,
            .pos-modal .modal-footer form,
            .pos-modal-footer-btn {
                width: 100%;
            }

            .pos-modal .modal-footer {
                padding: 12px;
            }

            .payments-entry-card {
                padding: 14px 16px;
            }

            .cart-list-row {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 16px;
            }

            .cart-list-shell {
                min-width: 0;
            }

            .cart-product-cell,
            .cart-action-cell {
                grid-column: auto;
            }

            .cart-pill-input,
            .cart-action-btn {
                height: 40px;
            }

            .cart-action-form {
                width: 100%;
            }

            .venta-payment-amount {
                min-width: 0;
                width: 100%;
                text-align: left;
            }

            .venta-payments-list .collection-item {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }

        @media (max-width: 480px) {
            .card-carrito .card-body {
                max-height: 240px;
            }

            .cart-list-row {
                gap: 8px;
                padding: 14px 12px 14px 16px;
            }

            .cart-list-row::before {
                left: 9px;
                top: 12px;
                bottom: 12px;
                width: 3px;
            }

            .cart-product-cell {
                padding-left: 6px;
            }

            .cart-product-sku {
                font-size: 13px;
            }

            .cart-product-label {
                margin-top: 4px;
                font-size: 12px;
            }

            .cart-stock-cell,
            .cart-qty-cell,
            .cart-price-cell,
            .cart-discount-cell,
            .cart-charge-cell,
            .cart-iva-cell,
            .cart-subtotal-cell {
                display: grid;
                grid-template-columns: minmax(78px, auto) minmax(0, 1fr);
                align-items: center;
                column-gap: 8px;
                row-gap: 4px;
            }

            .cart-mobile-label {
                margin: 0;
                font-size: 10px;
            }

            .cart-stock-value,
            .cart-money-value,
            .cart-subtotal-value,
            .cart-price-display {
                min-width: 0;
                justify-content: flex-end;
                text-align: right;
                white-space: normal;
                overflow-wrap: anywhere;
                font-size: 14px;
            }

            .cart-stock-note {
                grid-column: 2;
                margin-top: 0;
                text-align: right;
            }

            .cart-qty-cell .cart-inline-form,
            .cart-price-cell .cart-inline-form,
            .cart-price-cell .cart-money-input {
                width: 100%;
                max-width: 136px;
                margin-left: auto;
            }

            .cart-qty-cell .cart-inline-form {
                max-width: 96px;
            }

            .cart-pill-input,
            .cart-action-btn {
                height: 38px;
                border-radius: 14px;
                font-size: 13px;
            }

            .cart-pill-input {
                padding: 0 12px;
            }

            .cart-money-input .prefix {
                left: 10px;
                font-size: 12px;
            }

            .cart-money-input .cart-pill-input {
                padding-left: 26px;
            }
        }

        @media (max-width: 420px), (max-height: 520px) {
            #buscar_modal.modal,
            #pago_modal.modal,
            #venta_modal.modal {
                width: calc(100vw - 12px) !important;
                max-width: calc(100vw - 12px) !important;
                height: calc(100vh - 12px) !important;
                max-height: calc(100vh - 12px) !important;
                top: 6px !important;
            }

            .pos-modal-head {
                padding: 8px 8px 6px;
            }

            .pos-modal-head-card {
                padding: 12px;
                border-radius: 16px;
            }

            .pos-modal-head-main {
                gap: 10px;
            }

            .pos-modal-head-copy {
                min-width: 0;
                gap: 10px;
            }

            .pos-modal-icon-badge {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .pos-modal-icon-badge .material-icons {
                font-size: 18px;
            }

            .pos-modal-title {
                font-size: 18px;
            }

            .pos-modal-subtitle {
                margin-top: 4px;
                font-size: 11px;
                line-height: 1.35;
            }

            .pos-modal-close {
                width: 34px;
                height: 34px;
            }

            .pos-modal-search-wrap {
                padding: 0 8px 8px;
            }

            .pos-modal-search-input {
                height: 46px;
                padding: 0 72px 0 42px;
                border-radius: 16px;
                font-size: 14px;
            }

            .pos-modal-search-icon {
                left: 14px;
                font-size: 18px;
            }

            .pos-modal-search-clear {
                right: 12px;
                font-size: 11px;
            }

            .pos-modal-scroll {
                padding: 8px;
            }

            #pago_modal .modal-content,
            #pago_modal_content {
                overflow-y: auto;
                overflow-x: hidden;
                scrollbar-gutter: stable;
                scrollbar-width: thin;
                scrollbar-color: #b7c5d4 rgba(236, 242, 247, 0.9);
            }

            #pago_modal_content::-webkit-scrollbar {
                width: 10px;
            }

            #pago_modal_content::-webkit-scrollbar-track {
                background: rgba(236, 242, 247, 0.92);
                border-radius: 999px;
            }

            #pago_modal_content::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, #c5d2de 0%, #9fb0c2 100%);
                border-radius: 999px;
                border: 2px solid rgba(236, 242, 247, 0.92);
            }

            #venta_modal .modal-content,
            #venta_modal_content {
                overflow-y: auto;
                overflow-x: hidden;
                scrollbar-gutter: stable;
                scrollbar-width: thin;
                scrollbar-color: #b7c5d4 rgba(236, 242, 247, 0.9);
            }

            #venta_modal_content::-webkit-scrollbar {
                width: 10px;
            }

            #venta_modal_content::-webkit-scrollbar-track {
                background: rgba(236, 242, 247, 0.92);
                border-radius: 999px;
            }

            #venta_modal_content::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, #c5d2de 0%, #9fb0c2 100%);
                border-radius: 999px;
                border: 2px solid rgba(236, 242, 247, 0.92);
            }

            #pago_modal .pos-modal-head {
                position: static;
            }

            #venta_modal .pos-modal-head {
                position: static;
            }

            #pagos_modal_body {
                flex: 0 0 auto;
                min-height: auto;
                overflow: visible;
                padding: 8px 8px 20px;
            }

            #venta_modal_body {
                flex: 0 0 auto;
                min-height: auto;
                overflow: visible;
                padding: 8px 8px 20px;
            }

            #venta_modal .venta-modal-stack {
                gap: 10px;
            }

            #venta_modal .venta-section-card {
                padding: 12px;
                margin-bottom: 10px;
            }

            #venta_modal .venta-section-head {
                margin-bottom: 10px;
            }

            #venta_modal .venta-section-title {
                font-size: 16px;
            }

            #venta_modal .venta-section-subtitle {
                font-size: 12px;
            }

            #venta_modal .venta-table-shell {
                overflow: visible;
                padding: 8px;
            }

            #venta_modal .venta-items-table,
            #venta_modal .venta-items-table tbody,
            #venta_modal .venta-items-table tr,
            #venta_modal .venta-items-table td {
                display: block;
                width: 100%;
            }

            #venta_modal .venta-items-table thead {
                display: none;
            }

            #venta_modal .venta-items-table tbody tr {
                padding: 12px;
                border-radius: 14px;
                border: 1px solid rgba(196, 208, 221, 0.72);
                background: #ffffff;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
            }

            #venta_modal .venta-items-table tbody tr + tr {
                margin-top: 8px;
            }

            #venta_modal .venta-items-table tbody td {
                padding: 6px 0;
                border-bottom: none;
                text-align: left !important;
                display: grid;
                grid-template-columns: minmax(76px, auto) minmax(0, 1fr);
                align-items: start;
                column-gap: 10px;
                row-gap: 4px;
                font-size: 13px;
                overflow-wrap: anywhere;
            }

            #venta_modal .venta-items-table tbody td::before {
                content: attr(data-label);
                color: #667085;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            #venta_modal .venta-items-table tbody td strong {
                font-size: 15px;
            }

            #venta_modal .venta-payment-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 12px;
            }

            #venta_modal .venta-payment-type {
                font-size: 14px;
            }

            #venta_modal .venta-payment-meta {
                font-size: 11px;
            }

            #venta_modal .venta-payment-amount {
                min-width: 0;
                width: 100%;
                text-align: left;
            }

            .pos-modal-result-shell {
                padding: 6px;
                border-radius: 18px;
            }

            .pos-modal-summary-grid {
                gap: 8px;
                margin-top: 12px;
            }

            #pago_modal .pos-modal-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #pago_modal .pos-modal-summary-grid .payments-summary-card:last-child {
                grid-column: 1 / -1;
            }

            .payments-summary-card,
            .pos-modal-summary-grid .payments-summary-card {
                padding: 10px 12px;
                border-radius: 14px;
            }

            .payments-summary-label {
                font-size: 10px;
            }

            .payments-summary-value {
                margin-top: 2px;
                gap: 4px;
                flex-wrap: wrap;
            }

            .payments-summary-value .currency {
                font-size: 13px;
            }

            .payments-summary-value strong,
            .pos-modal-summary-grid .payments-summary-value strong {
                font-size: 17px;
            }

            .pos-modal-state,
            .modal-inline-state {
                padding: 14px 16px;
                border-radius: 16px;
                font-size: 12px;
            }

            .modal-search-item {
                gap: 10px;
                padding: 12px;
                border-radius: 16px;
            }

            .modal-search-item-title {
                font-size: 14px;
            }

            .modal-search-item-sku {
                font-size: 12px;
            }

            .modal-search-item-meta {
                gap: 6px;
                font-size: 11px;
            }

            .modal-search-badge {
                min-height: 24px;
                padding: 3px 8px;
                font-size: 11px;
            }

            .modal-search-side {
                width: 100%;
                min-width: 0;
                gap: 8px;
                align-items: stretch;
            }

            .modal-search-price {
                font-size: 17px;
            }

            .modal-search-action,
            .modal-search-add-btn {
                width: 100%;
            }

            .modal-search-add-btn {
                height: 38px;
                padding: 0 14px !important;
                font-size: 12px;
            }

            .payment-row-card {
                padding: 12px;
                margin-bottom: 10px;
                border-radius: 16px;
            }

            .payment-text-input,
            .payment-row-card .select-wrapper input.select-dropdown {
                height: 44px !important;
                line-height: 44px !important;
                border-radius: 14px !important;
                font-size: 13px;
            }

            .payment-field-block.has-icon .payment-text-input,
            .payment-field-block.has-icon .select-wrapper input.select-dropdown {
                padding-left: 40px !important;
            }

            .payment-field-block.has-icon .payment-field-icon {
                left: 12px;
                bottom: 13px;
                width: 16px;
                height: 16px;
                font-size: 16px;
            }

            .payment-row-summary,
            .payment-row-actions {
                gap: 6px;
                padding-top: 10px;
            }

            .payment-pill {
                min-height: 28px;
                padding: 5px 10px;
                font-size: 11px;
                white-space: normal;
            }

            .payment-remove-btn,
            .pos-modal-footer-btn {
                height: 38px;
                font-size: 12px;
            }

            .pos-modal .modal-footer {
                padding: 10px 8px;
                gap: 8px;
            }

            .pos-modal-footer-note {
                width: 100%;
                margin-right: 0;
                font-size: 11px;
            }

            #pago_modal .pos-modal-footer-note {
                display: none;
            }

            .payment-modal-footer-actions {
                width: 100%;
                margin-left: 0;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .payment-modal-footer-actions form {
                width: 100%;
            }

            #pago_modal .modal-footer.payment-modal-footer {
                position: relative;
                bottom: auto;
                z-index: 12;
                background: linear-gradient(180deg, rgba(249, 251, 253, 0.96) 0%, rgba(237, 243, 248, 1) 100%);
                box-shadow: 0 -12px 24px rgba(15, 23, 42, 0.12);
            }

            #pago_modal .payment-modal-footer-actions .pos-modal-footer-btn,
            #pago_modal .payment-modal-footer-actions form,
            #pago_modal .payment-modal-footer-actions button {
                width: 100%;
                min-width: 0;
            }

            #pago_modal .pos-modal-footer-btn {
                justify-content: center;
                font-weight: 800;
            }

            #pago_modal .pos-modal-footer-btn.is-primary {
                background: #182032;
                border-color: #182032;
                color: #ffffff;
            }

            #pago_modal .pos-modal-footer-btn.is-secondary {
                background: #ffffff;
                border-color: #d2dbe6;
                color: #344054 !important;
            }
        }

        @media (max-width: 360px), (max-height: 480px) {
            .payment-modal-footer-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
@php
    $money = static fn ($value) => number_format((float) $value, 2, ',', '.');
    $user = auth()->user();
    $userName = $user?->nombre_completo ?: ($user?->username ?: 'Usuario');
    $branchName = $branch?->nombre ?: 'Sin sucursal';
    $cartRows = collect($cart['rows'] ?? []);
    $discountTotal = $cartRows->sum(fn (array $row) => (float) ($row['descuento'] ?? 0));
    $paymentRows = collect($payments['rows'] ?? []);
    $cashSession = $cashState['session'] ?? null;
    $cashIsOpen = (bool) ($cashState['is_open'] ?? false);
    $canOperate = (bool) ($cashState['can_sell'] ?? false);
    $openedByOther = (bool) ($cashState['opened_by_other'] ?? false);
    $closeCount = (int) data_get($closeSummary, 'cantidad', 0);
    $closeTotal = (string) data_get($closeSummary, 'total', '0.00');
    $lastSalePrintUrl = $lastSale ? route('caja.ticket', $lastSale).'?print=1' : null;
    $currentUrl = route('caja.pos');
    $cashierName = $cashSession?->cajeroApertura?->nombre_completo;
    $cashStatusLabel = $cashIsOpen
        ? ($canOperate ? 'Caja abierta (tuya)' : 'Caja abierta (otro cajero)')
        : 'Caja cerrada';
    $cashStatusChipClass = $cashIsOpen
        ? ($canOperate ? 'nav-chip-success' : 'nav-chip-warn')
        : 'nav-chip-danger';
    $hasPendingBalance = (float) ($payments['saldo'] ?? 0) > 0;
@endphp

    @if ($lastSale && $lastSaleView)
        <aside class="last-sale-rail hide-on-med-and-down" aria-label="Resumen de ultima venta">
            <div class="card">
                <div class="card-content">
                    <div class="rail-title">Ultima venta</div>
                    <div style="margin-top:10px;">
                        <div style="font-size:22px; font-weight:800; line-height:1;">
                            {{ $lastSale->codigo_sucursal ?: ('#'.$lastSale->id) }}
                        </div>
                        <div class="grey-text text-darken-1" style="font-size:12px; margin-top:6px;">
                            {{ $lastSale->created_at?->format('d/m/Y H:i') ?: 'Sin fecha' }}
                        </div>
                    </div>
                    <div style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <span class="grey-text text-darken-1">Sucursal</span>
                            <strong>{{ $lastSale->sucursal?->nombre ?: $branchName }}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <span class="grey-text text-darken-1">Cajero</span>
                            <strong>{{ $lastSaleView['cajeroNombre'] }}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <span class="grey-text text-darken-1">Total</span>
                            <strong>${{ $money($lastSaleView['totalFinal']) }}</strong>
                        </div>
                    </div>
                    <div style="margin-top:12px; display:flex; flex-direction:column; gap:6px;">
                        @foreach ($lastSaleView['payments'] as $payment)
                            <div class="chip" style="margin:0; background:rgba(255,255,255,.8); font-weight:600;">
                                {{ $payment->tipo_ticket }} · ${{ $money($payment->total_pago_ticket) }}
                            </div>
                        @endforeach
                    </div>
                    <div class="rail-actions" style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
                        <a href="{{ route('caja.ticket', $lastSale) }}" class="btn brown waves-effect waves-light">Ver venta</a>
                        <a href="{{ $lastSalePrintUrl }}" target="_blank" rel="noopener" class="btn green darken-1 waves-effect waves-light">
                            <i class="material-icons left">print</i>Ticket
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    @endif

    <nav class="pos-nav">
        <div class="nav-wrapper container">
            <a href="{{ route('caja.pos') }}" class="brand-logo">
                <span class="brand-mark">
                    <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%; fill:currentColor;">
                        <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125Z"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <span class="brand-name">VGC</span>
                    <span class="brand-caption">Caja POS</span>
                </span>
            </a>

            <a href="#" data-target="mobile_nav" class="sidenav-trigger nav-mobile-trigger">
                <i class="material-icons">menu</i>
            </a>

            <ul class="right hide-on-med-and-down nav-actions">
                <li>
                    <a @class(['btn-flat', 'waves-effect', 'nav-link-btn', 'is-active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">
                        <i class="material-icons">dashboard</i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a @class(['btn-flat', 'waves-effect', 'nav-link-btn', 'is-active' => request()->routeIs('caja.pos')]) href="{{ route('caja.pos') }}">
                        <i class="material-icons">point_of_sale</i>
                        Caja
                    </a>
                </li>
                <li>
                    <a @class(['btn-flat', 'waves-effect', 'nav-link-btn', 'is-active' => request()->routeIs('catalogo.*')]) href="{{ route('catalogo.index') }}">
                        <i class="material-icons">inventory_2</i>
                        Catalogo
                    </a>
                </li>
                @if ($canViewAdminPanel)
                    <li>
                        <a @class(['btn-flat', 'waves-effect', 'nav-link-btn', 'is-active' => request()->routeIs('admin-panel.*')]) href="{{ route('admin-panel.dashboard') }}">
                            <i class="material-icons">admin_panel_settings</i>
                            Admin Panel
                        </a>
                    </li>
                @endif
                <li class="nav-meta-start">
                    <span class="nav-chip">
                        <i class="material-icons">store</i>
                        {{ $branchName }}
                    </span>
                </li>
                <li>
                    <span class="nav-chip {{ $cashStatusChipClass }}">
                        <i class="material-icons">{{ $cashIsOpen ? 'point_of_sale' : 'lock' }}</i>
                        {{ $cashStatusLabel }}
                    </span>
                </li>
                @if ($cashierName)
                    <li>
                        <span class="nav-chip nav-chip-note">
                            <i class="material-icons">badge</i>
                            Cajero: {{ $cashierName }}
                        </span>
                    </li>
                @endif
                @if ($closeSummary)
                    <li>
                        <span class="nav-chip nav-chip-note">
                            <i class="material-icons">summarize</i>
                            Cierre anterior: {{ $closeCount }} venta(s) confirmada(s)
                        </span>
                    </li>
                @endif
                @if (! $cashIsOpen)
                    <li>
                        <form method="POST" action="{{ route('caja.abrir') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn-flat waves-effect nav-action-btn">
                                <i class="material-icons left">lock_open</i>Abrir caja
                            </button>
                        </form>
                    </li>
                @elseif ($canOperate)
                    <li>
                        <span class="nav-chip">
                            <i class="material-icons">keyboard</i>
                            Cerrar: Alt + C
                        </span>
                    </li>
                @endif
                <li>
                    <span class="nav-chip" id="nav_date">
                        <i class="material-icons">calendar_today</i>
                        --/--/----
                    </span>
                </li>
                <li>
                    <span class="nav-chip" id="nav_clock">
                        <i class="material-icons">schedule</i>
                        --:--
                    </span>
                </li>
                <li>
                    <a class="dropdown-trigger btn-flat waves-effect nav-user-btn" href="#!" data-target="user_dd">
                        <i class="material-icons">account_circle</i>
                        {{ $user?->username ?: $userName }}
                        <i class="material-icons nav-user-caret">expand_more</i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="pos-nav-mobile-strip">
            <div class="pos-nav-mobile-scroll">
                <span class="pos-nav-mobile-chip">
                    <i class="material-icons">store</i>
                    {{ $branchName }}
                </span>
                <span class="pos-nav-mobile-chip {{ $cashIsOpen ? 'is-success' : 'is-danger' }}">
                    <i class="material-icons">{{ $cashIsOpen ? 'point_of_sale' : 'lock' }}</i>
                    {{ $cashStatusLabel }}
                </span>
                <span class="pos-nav-mobile-chip is-note">
                    <i class="material-icons">account_circle</i>
                    {{ $user?->username ?: $userName }}
                </span>
                @if ($cashierName)
                    <span class="pos-nav-mobile-chip is-note">
                        <i class="material-icons">badge</i>
                        Cajero: {{ $cashierName }}
                    </span>
                @endif
                @if ($closeSummary)
                    <span class="pos-nav-mobile-chip is-note">
                        <i class="material-icons">summarize</i>
                        Cierre: {{ $closeCount }} venta(s)
                    </span>
                @endif
            </div>
        </div>
    </nav>

    <ul id="user_dd" class="dropdown-content pos-user-dropdown">
        <li><a href="{{ route('profile.edit') }}"><i class="material-icons">person</i>Perfil</a></li>
        <li class="divider" tabindex="-1"></li>
        <li>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('pos_logout_form').submit();">
                <i class="material-icons">logout</i>Salir
            </a>
        </li>
    </ul>

    <form id="pos_logout_form" method="post" action="{{ route('logout') }}" style="display:none;">
        @csrf
    </form>

    <ul class="sidenav pos-mobile-nav" id="mobile_nav">
        <li>
            <div class="user-view mobile-nav-head">
                <div class="mobile-nav-brand">VGC</div>
                <div class="mobile-nav-subtitle">Caja POS</div>
                <div class="mobile-nav-meta">{{ $branchName }}<br>{{ $userName }}</div>
            </div>
        </li>
        <li><a href="{{ route('dashboard') }}"><i class="material-icons">dashboard</i>Dashboard</a></li>
        <li><a href="{{ route('caja.pos') }}"><i class="material-icons">point_of_sale</i>Caja</a></li>
        <li><a href="{{ route('catalogo.index') }}"><i class="material-icons">inventory_2</i>Catalogo</a></li>
        @if ($canViewAdminPanel)
            <li><a href="{{ route('admin-panel.dashboard') }}"><i class="material-icons">admin_panel_settings</i>Admin Panel</a></li>
        @endif
        <li><a href="#!"><i class="material-icons">{{ $cashIsOpen ? 'point_of_sale' : 'lock' }}</i>{{ $cashStatusLabel }}</a></li>
        @if ($cashierName)
            <li><a href="#!"><i class="material-icons">badge</i>Cajero: {{ $cashierName }}</a></li>
        @endif
        @if ($closeSummary)
            <li><a href="#!"><i class="material-icons">summarize</i>Cierre anterior: {{ $closeCount }} venta(s) confirmada(s)</a></li>
        @endif
        @if (! $cashIsOpen)
            <li>
                <form method="POST" action="{{ route('caja.abrir') }}" style="margin:0; padding:0 16px 12px;">
                    @csrf
                    <button type="submit" class="btn waves-effect waves-light mobile-nav-open-btn">
                        <i class="material-icons left">lock_open</i>Abrir caja
                    </button>
                </form>
            </li>
        @elseif ($canOperate)
            <li><a href="#!"><i class="material-icons">keyboard</i>Cerrar: Alt + C</a></li>
        @endif
        <li>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('pos_logout_form').submit();">
                <i class="material-icons">logout</i>Salir
            </a>
        </li>
    </ul>

    <div class="container pos-container">
        <div class="card pos-shell-card">
            @if ($setupError)
                <div class="card-panel red lighten-5 red-text text-darken-3" style="margin:0 0 12px; border-radius:12px;">
                    {{ $setupError }}
                </div>
            @endif

            <form id="scan_reader_form" method="POST" action="{{ route('caja.scan') }}" style="display:none;">
                @csrf
                <input type="hidden" id="scan_reader_hidden_q" name="q" value="">
            </form>

            <form id="close_cash_form" method="POST" action="{{ route('caja.cerrar') }}" style="display:none;">
                @csrf
            </form>

            <div class="row pos-main-grid">
                <div class="col s12 m6 pos-main-column">
                    <div class="card pos-card card-carrito">
                        <div class="card-content">
                            <div class="sticky-head">
                                <div class="card-head-row carrito-head-row">
                                    <div class="carrito-head-copy">
                                        <span class="card-title">Carrito</span>
                                        <div class="carrito-head-subtitle">Buscador amplio y ordenado para nombre, SKU o codigo de barras.</div>
                                    </div>
                                    <div class="card-head-actions carrito-head-actions">
                                        <button id="btn_open_buscar" type="button" class="btn waves-effect waves-light carrito-toolbar-btn is-secondary" title="Buscar productos (F1)" @disabled(! $canOperate)>
                                            <i class="material-icons left">search</i>Buscar
                                        </button>
                                        <form method="POST" action="{{ route('caja.carrito.vaciar') }}">
                                            @csrf
                                            <button class="btn waves-effect waves-light carrito-toolbar-btn is-primary" type="submit" @disabled(! $canOperate)>Vaciar</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="carrito-search-shell">
                                    <input id="scan_reader_input" type="text" class="browser-default pos-scan-input" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="none" placeholder="{{ $canOperate ? 'Buscar por producto, SKU o codigo de barras...' : 'Caja no habilitada para vender' }}" @disabled(! $canOperate)>
                                </div>
                            </div>

                            <div class="card-body" id="carrito_scroll">
                                <div id="carrito_body">
                                    @if ($cartRows->isEmpty())
                                        <div class="card-panel" style="margin:0; background:rgba(255,255,255,.5);">
                                            <span class="grey-text text-darken-1">Carrito vacio.</span>
                                        </div>
                                    @else
                                        <div class="cart-list-shell">
                                            <div class="cart-list-head">
                                                <div>Producto</div>
                                                <div>Stock</div>
                                                <div>Cantidad</div>
                                                <div>Precio</div>
                                                <div>Descuento</div>
                                                <div>Recargo</div>
                                                <div>IVA</div>
                                                <div>Subtotal</div>
                                                <div>Accion</div>
                                            </div>

                                            @foreach ($cartRows as $row)
                                                @php
                                                    $variant = $row['variant'];
                                                    $stock = (int) ($row['stock'] ?? 0);
                                                    $productLabel = $row['label'] ?: ($variant->producto?->nombre ?: 'Producto');
                                                    $fiscalSubtotal = $row['fiscal_subtotal'] ?? [];
                                                    $lineDiscount = (string) ($row['descuento'] ?? '0.00');
                                                    $lineRecargo = (string) ($row['recargo'] ?? '0.00');
                                                    $lineIva = (string) data_get($fiscalSubtotal, 'iva_contenido', '0.00');
                                                    $formattedPrice = $money($row['price']);
                                                    $formattedDiscount = $money($lineDiscount);
                                                    $formattedRecargo = $money($lineRecargo);
                                                    $formattedIva = $money($lineIva);
                                                    $formattedSubtotal = $money($row['subtotal']);
                                                    $priceDensityClass = strlen($formattedPrice) >= 10 ? 'is-compact' : '';
                                                    $discountDensityClass = strlen($formattedDiscount) >= 10 ? 'is-compact' : '';
                                                    $recargoDensityClass = strlen($formattedRecargo) >= 10 ? 'is-compact' : '';
                                                    $ivaDensityClass = strlen($formattedIva) >= 10 ? 'is-compact' : '';
                                                    $subtotalDensityClass = strlen($formattedSubtotal) >= 10 ? 'is-compact' : '';
                                                @endphp
                                                <div class="cart-list-row">
                                                    <div class="cart-product-cell">
                                                        <span class="cart-product-sku">{{ $productLabel }}</span>
                                                        <span class="cart-product-label">{{ $variant->sku ?: 'SKU s/n' }}</span>
                                                    </div>

                                                    <div class="cart-stock-cell">
                                                        <span class="cart-mobile-label">Stock</span>
                                                        <div class="cart-stock-value @class(['red-text text-darken-2' => ! $allowSellWithoutStock && $stock <= 0])">
                                                            {{ $stock }}
                                                        </div>
                                                        @if (! $allowSellWithoutStock && $stock <= 0)
                                                            <div class="cart-stock-note">Sin stock</div>
                                                        @endif
                                                    </div>

                                                    <div class="cart-qty-cell">
                                                        <span class="cart-mobile-label">Cantidad</span>
                                                        <form method="POST" action="{{ route('caja.carrito.qty', $variant) }}" class="cart-inline-form">
                                                            @csrf
                                                            <input
                                                                type="number"
                                                                name="qty"
                                                                min="1"
                                                                value="{{ $row['qty'] }}"
                                                                class="browser-default cart-pill-input cart-qty-input"
                                                                onchange="this.form.requestSubmit()"
                                                            >
                                                        </form>
                                                    </div>

                                                    <div class="cart-price-cell">
                                                        <span class="cart-mobile-label">Precio</span>
                                                        @if ($allowChangePrice)
                                                            <form method="POST" action="{{ route('caja.carrito.precio', $variant) }}" class="cart-inline-form">
                                                                @csrf
                                                                <div class="cart-money-input {{ $priceDensityClass }}">
                                                                    <span class="prefix">$</span>
                                                                    <input
                                                                        type="text"
                                                                        name="precio"
                                                                        value="{{ $formattedPrice }}"
                                                                        class="browser-default cart-pill-input"
                                                                        inputmode="decimal"
                                                                        title="${{ $formattedPrice }}"
                                                                        onchange="this.form.requestSubmit()"
                                                                    >
                                                                </div>
                                                            </form>
                                                        @else
                                                            <div class="cart-price-display {{ $priceDensityClass }}" title="${{ $formattedPrice }}">${{ $formattedPrice }}</div>
                                                        @endif
                                                    </div>

                                                    <div class="cart-discount-cell">
                                                        <span class="cart-mobile-label">Descuento</span>
                                                        <div class="cart-money-value {{ $discountDensityClass }}" title="${{ $formattedDiscount }}">${{ $formattedDiscount }}</div>
                                                    </div>

                                                    <div class="cart-charge-cell">
                                                        <span class="cart-mobile-label">Recargo</span>
                                                        <div class="cart-money-value {{ $recargoDensityClass }}" title="${{ $formattedRecargo }}">${{ $formattedRecargo }}</div>
                                                    </div>

                                                    <div class="cart-iva-cell">
                                                        <span class="cart-mobile-label">IVA</span>
                                                        <div class="cart-money-value {{ $ivaDensityClass }}" title="${{ $formattedIva }}">${{ $formattedIva }}</div>
                                                    </div>

                                                    <div class="cart-subtotal-cell">
                                                        <span class="cart-mobile-label">Subtotal</span>
                                                        <div class="cart-subtotal-value {{ $subtotalDensityClass }}" title="${{ $formattedSubtotal }}">${{ $formattedSubtotal }}</div>
                                                    </div>

                                                    <div class="cart-action-cell">
                                                        <span class="cart-mobile-label">Accion</span>
                                                        <form method="POST" action="{{ route('caja.carrito.quitar', $variant) }}" class="cart-action-form">
                                                            @csrf
                                                            <button class="btn-flat waves-effect cart-action-btn" type="submit" @disabled(! $canOperate)>
                                                                Quitar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col s12 m6 pos-payments-column">
                    <div class="card pos-card card-pagos">
                        <div class="card-content">
                            <div class="sticky-head">
                                <div class="payments-panel-copy">
                                    <span class="card-title">Pagos</span>
                                    <div class="payments-panel-subtitle">Panel lateral fijo, ordenado y siempre visible.</div>
                                </div>

                                <div class="card-head-actions">
                                    <button id="btn_open_pagos" type="button" class="payments-action-btn" title="Abrir pagos (F2)" @disabled(! $canOperate)>
                                        <i class="material-icons">add</i>
                                        Agregar
                                    </button>
                                    <form method="POST" action="{{ route('caja.pagos.vaciar') }}">
                                        @csrf
                                        <input type="hidden" name="return_modal" value="pagos">
                                        <button class="payments-action-btn is-danger" type="submit" @disabled(! $canOperate)>
                                            <i class="material-icons">delete_sweep</i>
                                            Vaciar
                                        </button>
                                    </form>
                                </div>

                                <div class="payments-summary-stack">
                                    <div class="payments-summary-card">
                                        <span class="payments-summary-label">Total items</span>
                                        <div class="payments-summary-value">
                                            <span class="currency">$</span>
                                            <strong>{{ $money($payments['total_base']) }}</strong>
                                        </div>
                                    </div>

                                    <div class="payments-summary-card">
                                        <span class="payments-summary-label">Recargos</span>
                                        <div class="payments-summary-value">
                                            <span class="currency">$</span>
                                            <strong>{{ $money($payments['recargos']) }}</strong>
                                        </div>
                                    </div>

                                    <div class="payments-summary-card">
                                        <span class="payments-summary-label">Descuentos</span>
                                        <div class="payments-summary-value">
                                            <span class="currency">$</span>
                                            <strong>{{ $money($discountTotal) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body" id="pagos_scroll">
                                <div id="pagos_table">
                                    @if ($paymentRows->isEmpty())
                                        <div class="payments-empty-card">
                                            {{ (float) $payments['total_base'] <= 0 ? 'Agrega productos al carrito para habilitar pagos.' : 'No hay formas de pago cargadas todavia.' }}
                                        </div>
                                    @else
                                        <div class="payments-list">
                                            @foreach ($paymentRows as $payment)
                                                @php
                                                    $paymentTitle = $payment['tipo_label'];
                                                    if ($payment['tipo'] === 'CREDITO' && $payment['tarjeta'] !== '') {
                                                        $paymentTitle .= ' '.$payment['tarjeta'];
                                                    }

                                                    $paymentNote = '';
                                                    if ($payment['tipo'] === 'CREDITO') {
                                                        $paymentNote = $payment['cuotas'].' cuota(s)';
                                                        if ((float) $payment['recargo_pct'] > 0) {
                                                            $paymentNote .= ' · '.$money($payment['recargo_pct']).'%';
                                                        }
                                                    } elseif ($payment['tipo'] === 'CUENTA_CORRIENTE') {
                                                        $paymentNote = $payment['cc_name'] ?: 'Cliente sin seleccionar';
                                                    } elseif ($payment['referencia'] !== '') {
                                                        $paymentNote = 'Ref: '.$payment['referencia'];
                                                    }
                                                @endphp
                                                <div class="payments-entry-card">
                                                    <div class="payments-entry-main">
                                                        <span class="payments-entry-title">{{ $paymentTitle }}</span>
                                                        @if ($paymentNote !== '')
                                                            <span class="payments-entry-note">{{ $paymentNote }}</span>
                                                        @endif
                                                    </div>
                                                    <span class="payments-entry-amount">${{ $money($payment['line_total']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card pos-sticky">
                <div class="card-content">
                    <form id="confirm_venta_form" method="POST" action="{{ route('caja.confirmar') }}">
                        @csrf
                        <input id="confirm_token_input" type="hidden" name="confirm_token" value="{{ $confirmToken }}">
                        <div class="controls">
                            <div class="pos-summary-main">
                                <span class="pos-summary-label">Total a cobrar</span>
                                <div class="pos-summary-total">
                                    <span class="currency">$</span>
                                    <strong id="total_cobrar_confirm">{{ $money($payments['total_cobrar']) }}</strong>
                                </div>
                            </div>
                            <div class="pos-summary-stat">
                                <span class="pos-summary-label">Pagado</span>
                                <span class="value">$ {{ $money($payments['pagado']) }}</span>
                            </div>
                            <div class="pos-summary-stat">
                                <span class="pos-summary-label">Saldo</span>
                                <span class="value">$ {{ $money($payments['saldo']) }}</span>
                            </div>
                            <div class="pos-summary-stat shortcut">
                                <span class="pos-summary-label">Atajo</span>
                                <span class="value">F5</span>
                            </div>
                            <div class="pos-summary-action">
                                <button id="confirm_venta_btn" class="pos-confirm-btn waves-effect" type="submit" title="Confirmar venta (F5)" @disabled(! $canOperate)>
                                    <span class="pos-summary-label">Accion principal</span>
                                    <span @class(['pos-confirm-btn-text', 'is-disabled' => ! $canOperate])>
                                        {{ $canOperate ? 'Confirmar venta' : 'Caja no habilitada' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="buscar_modal" class="modal modal-fixed-footer pos-modal pos-search-modal" style="max-width:900px; width:85%;">
        <div class="modal-content">
            <div class="pos-modal-head">
                <div class="pos-modal-head-card">
                    <div class="pos-modal-head-main">
                        <div class="pos-modal-head-copy">
                            <div class="pos-modal-icon-badge">
                                <i class="material-icons">search</i>
                            </div>
                            <div>
                                <h4 class="pos-modal-title">Buscador de productos</h4>
                                <div class="pos-modal-subtitle">Encontra variantes por SKU, nombre o codigo de barras.</div>
                            </div>
                        </div>

                        <a href="#!" class="modal-close pos-modal-close" title="Cerrar">
                            <i class="material-icons">close</i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="pos-modal-search-wrap">
                <div class="pos-modal-search-field">
                    <i class="material-icons pos-modal-search-icon">qr_code_scanner</i>
                    <input
                        id="q_modal"
                        class="browser-default pos-modal-search-input"
                        type="text"
                        name="q"
                        value="{{ $searchQuery }}"
                        placeholder="Buscar por nombre, SKU o codigo de barras"
                        autocomplete="off"
                        hx-get="{{ route('caja.buscar') }}"
                        hx-target="#buscar_modal_resultados"
                        hx-trigger="keyup changed delay:250ms, search"
                        hx-indicator="#buscar_modal_spinner"
                    >
                    <a href="#!" id="q_modal_clear" class="pos-modal-search-clear">Limpiar</a>
                </div>
                <div id="buscar_modal_spinner" class="progress pos-modal-progress" style="display:none;">
                    <div class="indeterminate teal"></div>
                </div>
            </div>

            <div id="buscar_modal_scroll" class="pos-modal-scroll">
                <div id="buscar_modal_resultados" class="pos-modal-result-shell">
                    @include('caja.partials.results', [
                        'branch' => $branch,
                        'query' => $searchQuery,
                        'results' => $searchRows,
                        'searchError' => $setupError,
                        'canOperate' => $canOperate,
                        'allowSellWithoutStock' => $allowSellWithoutStock,
                        'money' => $money,
                    ])
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat pos-modal-footer-btn is-secondary">Cerrar</a>
        </div>
    </div>

    <div id="pago_modal" class="modal pos-modal pos-payments-modal" style="max-width:1080px; width:92%;">
        <div class="modal-content" id="pago_modal_content">
            <div class="pos-modal-head">
                <div class="pos-modal-head-card">
                    <div class="pos-modal-head-main">
                        <div class="pos-modal-head-copy">
                            <div class="pos-modal-icon-badge">
                                <i class="material-icons">account_balance_wallet</i>
                            </div>
                            <div>
                                <h4 class="pos-modal-title">Formas de pago</h4>
                                <div class="pos-modal-subtitle">Configura los pagos con la misma linea visual de la caja. Cada cambio se guarda al volver a cargar.</div>
                            </div>
                        </div>

                        <a href="#!" class="modal-close pos-modal-close" title="Cerrar">
                            <i class="material-icons">close</i>
                        </a>
                    </div>

                    <div class="pos-modal-summary-grid">
                        <div class="payments-summary-card is-total">
                            <span class="payments-summary-label">Total items</span>
                            <div class="payments-summary-value">
                                <span class="currency">$</span>
                                <strong>{{ $money($payments['total_base']) }}</strong>
                            </div>
                        </div>
                        <div class="payments-summary-card is-warning">
                            <span class="payments-summary-label">Recargos</span>
                            <div class="payments-summary-value">
                                <span class="currency">$</span>
                                <strong>{{ $money($payments['recargos']) }}</strong>
                            </div>
                        </div>
                        <div class="payments-summary-card is-accent">
                            <span class="payments-summary-label">A cobrar</span>
                            <div class="payments-summary-value">
                                <span class="currency">$</span>
                                <strong>{{ $money($payments['total_cobrar']) }}</strong>
                            </div>
                        </div>
                        <div class="payments-summary-card is-success">
                            <span class="payments-summary-label">Pagado</span>
                            <div class="payments-summary-value">
                                <span class="currency">$</span>
                                <strong>{{ $money($payments['pagado']) }}</strong>
                            </div>
                        </div>
                        <div @class([
                            'payments-summary-card',
                            'is-balance-danger' => $hasPendingBalance,
                            'is-balance-neutral' => ! $hasPendingBalance,
                        ])>
                            <span class="payments-summary-label">Saldo</span>
                            <div class="payments-summary-value">
                                <span class="currency">$</span>
                                <strong>{{ $money($payments['saldo']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="pagos_modal_body" class="pos-modal-scroll">
                @if ((float) $payments['total_base'] <= 0)
                    <div class="pos-modal-state">
                        <i class="material-icons">info</i>
                        Agrega productos al carrito.
                    </div>
                @elseif ($paymentRows->isEmpty())
                    <div class="pos-modal-state">
                        <i class="material-icons">hourglass_empty</i>
                        No hay pagos cargados.
                    </div>
                @endif

                @foreach ($paymentRows as $payment)
                    <div class="payment-row-card">
                        <div class="row" style="margin-bottom:0;">
                            <form class="col s12 m10 payment-row-form" method="POST" action="{{ route('caja.pagos.update', $payment['index']) }}" data-payment-action="update">
                                @csrf
                                <input type="hidden" name="return_modal" value="pagos">

                                <div class="row" style="margin-bottom:0;">
                                    <div class="input-field col s12 m4 payment-field-block">
                                        <span class="payment-field-label">Tipo</span>
                                        <select name="tipo" onchange="this.form.requestSubmit()">
                                            @foreach ($payments['types'] as $type => $label)
                                                <option value="{{ $type }}" @selected($payment['tipo'] === $type)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col s12 m4 payment-field-block has-icon">
                                        <span class="payment-field-label">Monto</span>
                                        <i class="material-icons payment-field-icon">attach_money</i>
                                        <input class="browser-default payment-text-input" type="text" name="monto" value="{{ $payment['monto'] }}" onchange="this.form.requestSubmit()" onblur="this.form.requestSubmit()">
                                    </div>

                                    <div class="col s12 m4 payment-row-summary">
                                        @if ($payment['tipo'] === 'CREDITO')
                                            <span class="payment-pill is-warning">Recargo: ${{ $money($payment['recargo_monto']) }}</span>
                                            <span class="payment-pill is-accent">Total: ${{ $money($payment['line_total']) }}</span>
                                        @else
                                            <span class="payment-pill is-neutral">Sin recargo</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($payment['tipo'] === 'CREDITO')
                                    <div class="row" style="margin:0;">
                                        <div class="input-field col s12 m6 payment-field-block payment-field-plan">
                                            <span class="payment-field-label">Plan</span>
                                            <select name="plan_id" onchange="this.form.requestSubmit()">
                                                <option value="">Elegi plan</option>
                                                @foreach ($payments['plans'] as $plan)
                                                    <option value="{{ $plan->id }}" @selected((string) $payment['plan_id'] === (string) $plan->id)>{{ $plan->tarjeta }} · {{ $plan->cuotas }} cuota(s) · {{ $money($plan->recargo_pct) }}%</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col s12 m6 payment-field-block has-icon">
                                            <span class="payment-field-label">Referencia</span>
                                            <i class="material-icons payment-field-icon">receipt_long</i>
                                            <input class="browser-default payment-text-input" type="text" name="referencia" value="{{ $payment['referencia'] }}" onchange="this.form.requestSubmit()">
                                        </div>
                                    </div>
                                @elseif ($payment['tipo'] === 'CUENTA_CORRIENTE')
                                    <div class="row" style="margin:0;">
                                        <div class="input-field col s12 m6 payment-field-block">
                                            <span class="payment-field-label">Cliente</span>
                                            <select name="cc_cliente_id" onchange="this.form.requestSubmit()">
                                                <option value="">Elegi cliente</option>
                                                @foreach ($payments['accounts'] as $account)
                                                    <option value="{{ $account->cliente_id }}" @selected((string) $payment['cc_cliente_id'] === (string) $account->cliente_id)>
                                                        {{ $account->cliente?->nombre_completo ?: 'Cliente' }} · {{ $account->cliente?->dni ?: 's/dni' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col s12 m6 payment-field-block" style="padding-top:20px;">
                                            @if ($payment['cc_name'] !== '')
                                                <span class="payment-pill is-neutral">{{ $payment['cc_name'] }}</span>
                                                <div class="payment-customer-note">Saldo actual: <strong>${{ $money($payment['cc_saldo']) }}</strong></div>
                                            @else
                                                <div class="payment-customer-empty">Elegi un cliente para debitar la venta a cuenta corriente.</div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="row" style="margin:0;">
                                        <div class="col s12 payment-field-block has-icon">
                                            <span class="payment-field-label">Referencia</span>
                                            <i class="material-icons payment-field-icon">receipt_long</i>
                                            <input class="browser-default payment-text-input" type="text" name="referencia" value="{{ $payment['referencia'] }}" onchange="this.form.requestSubmit()">
                                        </div>
                                    </div>
                                @endif
                            </form>

                            <div class="col s12 m2 payment-row-actions">
                                <form method="POST" action="{{ route('caja.pagos.quitar', $payment['index']) }}" style="margin:0;" data-payment-action="delete">
                                    @csrf
                                    <input type="hidden" name="return_modal" value="pagos">
                                    <button class="btn waves-effect waves-light payment-remove-btn" type="submit">
                                        <i class="material-icons left">delete</i>Quitar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer payment-modal-footer">
                <div class="pos-modal-footer-note">Los cambios se guardan automaticamente.</div>
                <div class="payment-modal-footer-actions">
                    <form method="POST" action="{{ route('caja.pagos.agregar') }}" data-payment-action="add">
                        @csrf
                        <input type="hidden" name="return_modal" value="pagos">
                        <button
                            type="submit"
                            class="btn waves-effect waves-light pos-modal-footer-btn is-primary"
                            @disabled((float) $payments['total_base'] <= 0)
                            title="{{ (float) $payments['total_base'] <= 0 ? 'Agrega productos al carrito para habilitar pagos.' : 'Agregar una nueva linea de pago.' }}"
                        >
                            <i class="material-icons left">add</i>Agregar
                        </button>
                    </form>
                    <a href="#!" class="modal-close btn-flat pos-modal-footer-btn is-secondary">Cerrar</a>
                </div>
            </div>
        </div>
    </div>

    @if ($lastSale && $lastSaleView)
        <div id="venta_modal" class="modal pos-modal pos-sale-modal" style="max-width:1100px; width:90%;">
            <div class="modal-content" id="venta_modal_content">
                <div class="pos-modal-head">
                    <div class="pos-modal-head-card">
                        <div class="pos-modal-head-main">
                            <div class="pos-modal-head-copy">
                                <div class="pos-modal-icon-badge">
                                    <i class="material-icons">receipt_long</i>
                                </div>
                                <div>
                                    <h4 class="pos-modal-title">Venta confirmada</h4>
                                    <div class="pos-modal-subtitle">
                                        {{ $lastSale->codigo_sucursal ?: ('#'.$lastSale->id) }} · {{ $lastSale->created_at?->format('d/m/Y H:i') }} · {{ $lastSale->sucursal?->nombre ?: $branchName }}
                                    </div>
                                </div>
                            </div>

                            <a href="#!" class="modal-close pos-modal-close" title="Cerrar">
                                <i class="material-icons">close</i>
                            </a>
                        </div>

                        <div class="venta-summary-grid">
                            <div class="venta-summary-card is-items">
                                <span class="venta-summary-label">Subtotal items</span>
                                <div class="venta-summary-value is-amount">
                                    <span class="currency">$</span>
                                    <strong>{{ $money($lastSaleView['totalItems']) }}</strong>
                                </div>
                            </div>
                            <div class="venta-summary-card is-final">
                                <span class="venta-summary-label">Total final</span>
                                <div class="venta-summary-value is-amount">
                                    <span class="currency">$</span>
                                    <strong>{{ $money($lastSaleView['totalFinal']) }}</strong>
                                </div>
                            </div>
                            <div class="venta-summary-card is-meta">
                                <span class="venta-summary-label">Pagos registrados</span>
                                <div class="venta-summary-value">{{ count($lastSaleView['payments']) }} forma(s)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="venta_modal_body" class="pos-modal-scroll">
                    <div class="venta-modal-stack">
                        <section class="venta-section-card">
                            <div class="venta-section-head">
                                <div>
                                    <div class="venta-section-title">Items</div>
                                    <div class="venta-section-subtitle">{{ count($lastSaleView['items']) }} item(s) confirmados en la venta.</div>
                                </div>
                            </div>

                            <div class="venta-table-shell">
                                <table class="venta-items-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="right-align">Cant.</th>
                                            <th class="right-align">Precio</th>
                                            <th class="right-align">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lastSaleView['items'] as $item)
                                            <tr>
                                                <td data-label="Producto">{{ $item->nombre_ticket }}</td>
                                                <td class="right-align" data-label="Cant.">{{ $item->cantidad }}</td>
                                                <td class="right-align" data-label="Precio">${{ $money($item->precio_unitario) }}</td>
                                                <td class="right-align" data-label="Subtotal"><strong>${{ $money($item->subtotal) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="venta-section-card">
                            <div class="venta-section-head">
                                <div>
                                    <div class="venta-section-title">Pagos</div>
                                    <div class="venta-section-subtitle">{{ count($lastSaleView['payments']) }} forma(s) de pago registradas.</div>
                                </div>
                            </div>

                            <div class="venta-payments-list">
                                @foreach ($lastSaleView['payments'] as $payment)
                                    <div class="venta-payment-card">
                                        <div>
                                            <div class="venta-payment-type">{{ $payment->tipo_ticket }}</div>
                                            <div class="venta-payment-meta">
                                                @if ($payment->tipo === \App\Domain\Ventas\Models\VentaPago::TIPO_CREDITO && $payment->plan)
                                                    {{ $payment->plan->tarjeta }} · {{ $payment->plan->cuotas }} cuota(s)
                                                @elseif ($payment->referencia)
                                                    {{ $payment->referencia }}
                                                @else
                                                    Sin referencia
                                                @endif
                                            </div>
                                        </div>
                                        <div class="venta-payment-amount">
                                            <strong>${{ $money($payment->total_pago_ticket) }}</strong>
                                            @if ((float) $payment->recargo_monto_ticket > 0)
                                                <div class="venta-payment-extra">Incluye recargo ${{ $money($payment->recargo_monto_ticket) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="{{ route('caja.ticket', $lastSale) }}" class="btn-flat pos-modal-footer-btn is-secondary">Ver detalle</a>
                    <a href="{{ $lastSalePrintUrl }}" target="_blank" rel="noopener" class="btn waves-effect waves-light pos-modal-footer-btn is-primary">
                        <i class="material-icons left">print</i>Ticket
                    </a>
                    <a href="#!" class="modal-close btn-flat pos-modal-footer-btn is-secondary">Cerrar</a>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/materialize-css@1.0.0/dist/js/materialize.min.js"></script>
    <script>
        window.posConfig = {
            activeModal: @json($activeModal),
            canOperate: @json($canOperate),
            showLastSaleModal: @json($showLastSaleModal),
        };
    </script>
    <script>
        function normalizePlanDropdownInput(dropdownInput) {
            if (!(dropdownInput instanceof HTMLInputElement)) {
                return;
            }

            const cleanValue = () => {
                const nextValue = dropdownInput.value.replace(/\s+/g, ' ').trim();

                if (dropdownInput.value !== nextValue) {
                    dropdownInput.value = nextValue;
                }
            };

            const resetViewport = () => {
                cleanValue();
                dropdownInput.scrollLeft = 0;

                try {
                    dropdownInput.setSelectionRange(0, 0);
                } catch (error) {
                    // Ignore browsers that don't allow selection on readonly inputs.
                }
            };

            window.requestAnimationFrame(() => {
                resetViewport();
                window.setTimeout(resetViewport, 0);
            });
        }

        function normalizePaymentPlanDropdowns(root) {
            const dropdownInputs = (root || document).querySelectorAll('.payment-field-plan .select-wrapper input.select-dropdown');

            dropdownInputs.forEach((dropdownInput) => {
                normalizePlanDropdownInput(dropdownInput);

                if (dropdownInput.dataset.posNormalized === 'true') {
                    return;
                }

                dropdownInput.dataset.posNormalized = 'true';

                dropdownInput.addEventListener('focus', () => normalizePlanDropdownInput(dropdownInput));
                dropdownInput.addEventListener('click', () => normalizePlanDropdownInput(dropdownInput));
                dropdownInput.addEventListener('mouseup', (event) => {
                    event.preventDefault();
                    normalizePlanDropdownInput(dropdownInput);
                });
                dropdownInput.addEventListener('blur', () => normalizePlanDropdownInput(dropdownInput));

                const select = dropdownInput.closest('.select-wrapper')?.querySelector('select');

                if (select) {
                    select.addEventListener('change', () => normalizePlanDropdownInput(dropdownInput));
                }
            });
        }

        function initSelects(root) {
            if (!window.M || !M.FormSelect) {
                return;
            }

            const elements = (root || document).querySelectorAll('select');
            M.FormSelect.init(elements);
            normalizePaymentPlanDropdowns(root || document);
        }

        function initNav() {
            if (!window.M) {
                return;
            }

            M.Dropdown.init(document.querySelectorAll('.dropdown-trigger'), {
                coverTrigger: false,
                constrainWidth: false,
            });
            M.Sidenav.init(document.querySelectorAll('.sidenav'));

            const clockEl = document.getElementById('nav_clock');
            const dateEl = document.getElementById('nav_date');

            function tick() {
                const now = new Date();
                const dd = String(now.getDate()).padStart(2, '0');
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                const yyyy = now.getFullYear();
                const hh = String(now.getHours()).padStart(2, '0');
                const min = String(now.getMinutes()).padStart(2, '0');

                if (dateEl) {
                    dateEl.innerHTML = '<i class="material-icons">calendar_today</i> ' + dd + '/' + mm + '/' + yyyy;
                }

                if (clockEl) {
                    clockEl.innerHTML = '<i class="material-icons">schedule</i> ' + hh + ':' + min;
                }
            }

            tick();
            window.setInterval(tick, 10000);
        }

        function centerUiModal(modalEl) {
            if (!modalEl) {
                return;
            }

            modalEl.style.position = 'fixed';
            modalEl.style.left = '0';
            modalEl.style.right = '0';
            modalEl.style.marginLeft = 'auto';
            modalEl.style.marginRight = 'auto';
        }

        function openModalById(id) {
            const el = document.getElementById(id);
            if (!el || !window.M || !M.Modal) {
                return;
            }

            centerUiModal(el);
            const instance = M.Modal.getInstance(el) || M.Modal.init(el, {
                dismissible: true,
                onOpenStart: () => centerUiModal(el),
                onOpenEnd: () => centerUiModal(el),
            });
            instance.open();
        }

        function initScannerInput() {
            const input = document.getElementById('scan_reader_input');
            const hiddenInput = document.getElementById('scan_reader_hidden_q');
            const form = document.getElementById('scan_reader_form');

            if (!input || !hiddenInput || !form) {
                return;
            }

            let burst = { startedAt: 0, lastAt: 0, keyCount: 0 };

            function resetBurst() {
                burst = { startedAt: 0, lastAt: 0, keyCount: 0 };
            }

            input.addEventListener('paste', (event) => event.preventDefault());
            input.addEventListener('blur', resetBurst);
            input.addEventListener('keydown', function (event) {
                const now = window.performance && performance.now ? performance.now() : Date.now();

                if (event.key === 'Enter') {
                    event.preventDefault();
                    const query = input.value.trim();
                    const duration = burst.startedAt && burst.lastAt ? (burst.lastAt - burst.startedAt) : 9999;
                    const looksLikeScanner = burst.keyCount >= 6 && duration <= 350;

                    if (query !== '' && looksLikeScanner && window.posConfig.canOperate) {
                        hiddenInput.value = query;
                        form.submit();
                    }

                    input.value = '';
                    resetBurst();
                    return;
                }

                if (event.key === 'Tab') {
                    resetBurst();
                    return;
                }

                if (event.ctrlKey || event.altKey || event.metaKey) {
                    return;
                }

                if (event.key.length === 1) {
                    if (!burst.lastAt || (now - burst.lastAt) > 120) {
                        burst.startedAt = now;
                        burst.keyCount = 0;
                    }

                    burst.lastAt = now;
                    burst.keyCount += 1;
                }
            });
        }

        function initPosShortcuts() {
            document.addEventListener('keydown', function (event) {
                if (!event || event.repeat || event.ctrlKey || event.metaKey) {
                    return;
                }

                const key = String(event.key || '').toUpperCase();
                const isCloseShortcut = event.altKey && key === 'C';
                const isPosShortcut = !event.altKey && ['F1', 'F2', 'F5'].includes(key);

                if (!isCloseShortcut && !isPosShortcut) {
                    return;
                }

                event.preventDefault();

                if (document.querySelector('.modal.open')) {
                    return;
                }

                if (isCloseShortcut) {
                    const closeForm = document.getElementById('close_cash_form');
                    if (!closeForm) {
                        return;
                    }

                    if (typeof closeForm.requestSubmit === 'function') {
                        closeForm.requestSubmit();
                    } else {
                        closeForm.submit();
                    }

                    return;
                }

                if (key === 'F1') {
                    const button = document.getElementById('btn_open_buscar');
                    if (button && !button.disabled) {
                        button.click();
                    }
                    return;
                }

                if (key === 'F2') {
                    const button = document.getElementById('btn_open_pagos');
                    if (button && !button.disabled) {
                        button.click();
                    }
                    return;
                }

                const form = document.getElementById('confirm_venta_form');
                const button = document.getElementById('confirm_venta_btn');

                if (form && button && !button.disabled) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }, true);
        }

        function showSessionToasts() {
            if (!window.M || !M.toast) {
                return;
            }

            @if (session('success'))
                M.toast({html: @js(session('success')), classes: 'green darken-2'});
            @endif

            @if (session('error'))
                M.toast({html: @js(session('error')), classes: 'red darken-2'});
            @endif
        }

        function showUiToast(message, classes = 'green darken-2') {
            if (!message || !window.M || !M.toast) {
                return;
            }

            M.toast({html: message, classes});
        }

        function swapFromDocument(selector, nextDocument) {
            const current = document.querySelector(selector);
            const incoming = nextDocument.querySelector(selector);

            if (!current || !incoming) {
                return;
            }

            current.replaceWith(incoming.cloneNode(true));
        }

        async function refreshPosUi() {
            const response = await fetch(window.location.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudo actualizar la caja.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const nextDocument = parser.parseFromString(html, 'text/html');

            [
                '#carrito_body',
                '.payments-summary-stack',
                '#pagos_table',
                '#pago_modal_content',
                '.pos-sticky .card-content',
            ].forEach((selector) => swapFromDocument(selector, nextDocument));

            initSelects(document.getElementById('pago_modal') || document);
        }

        function restorePaymentModalScroll(scrollTop) {
            const scrollEl = document.getElementById('pagos_modal_body');

            if (!scrollEl) {
                return;
            }

            const maxScrollTop = Math.max(scrollEl.scrollHeight - scrollEl.clientHeight, 0);
            scrollEl.scrollTop = Math.min(scrollTop, maxScrollTop);
        }

        function revealLatestPaymentRow() {
            const scrollEl = document.getElementById('pagos_modal_body');
            const rowCards = scrollEl ? Array.from(scrollEl.querySelectorAll('.payment-row-card')) : [];
            const lastRow = rowCards.length > 0 ? rowCards[rowCards.length - 1] : null;

            if (!scrollEl || !lastRow) {
                return;
            }

            lastRow.classList.add('is-emphasized');

            const focusTarget = lastRow.querySelector('input[name="monto"], .select-wrapper input.select-dropdown, input[name="referencia"]');

            window.requestAnimationFrame(() => {
                scrollEl.scrollTop = Math.max(scrollEl.scrollHeight - scrollEl.clientHeight, 0);
                lastRow.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth',
                });

                if (focusTarget && typeof focusTarget.focus === 'function') {
                    focusTarget.focus();

                    if (focusTarget instanceof HTMLInputElement && typeof focusTarget.select === 'function') {
                        focusTarget.select();
                    }
                }
            });

            window.setTimeout(() => {
                lastRow.classList.remove('is-emphasized');
            }, 1800);
        }

        function initAsyncModalAdd() {
            document.addEventListener('submit', async function (event) {
                const form = event.target;

                if (!form || !(form instanceof HTMLFormElement) || !form.classList.contains('modal-search-action')) {
                    return;
                }

                event.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalLabel = submitButton ? submitButton.innerHTML : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = 'Agregando...';
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(payload.message || 'No se pudo agregar el producto.');
                    }

                    await refreshPosUi();
                    showUiToast(payload.message || 'Item agregado al carrito.');

                    const buscarModalEl = document.getElementById('buscar_modal');
                    centerUiModal(buscarModalEl);

                    const input = document.getElementById('q_modal');
                    if (input) {
                        input.focus();
                    }
                } catch (error) {
                    showUiToast(error.message || 'No se pudo agregar el producto.', 'red darken-2');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalLabel;
                    }
                }
            });
        }

        function initAsyncPaymentModal() {
            document.addEventListener('submit', async function (event) {
                const form = event.target;

                if (!form || !(form instanceof HTMLFormElement) || !form.closest('#pago_modal')) {
                    return;
                }

                event.preventDefault();

                const rowCard = form.closest('.payment-row-card');
                const scrollEl = document.getElementById('pagos_modal_body');
                const scrollTop = scrollEl ? scrollEl.scrollTop : 0;
                const actionType = form.dataset.paymentAction || 'update';
                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
                const initialStates = submitButtons.map((button) => ({
                    button,
                    disabled: button.disabled,
                    html: button.innerHTML,
                }));

                if (rowCard) {
                    rowCard.classList.add('is-loading');
                }

                submitButtons.forEach((button) => {
                    button.disabled = true;
                });

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    const successMessage = actionType === 'add'
                        ? 'Se agrego una nueva linea de pago.'
                        : (actionType === 'delete' ? 'Pago eliminado.' : 'Pago actualizado.');
                    const errorMessage = actionType === 'add'
                        ? 'No se pudo agregar la linea de pago.'
                        : (actionType === 'delete' ? 'No se pudo quitar el pago.' : 'No se pudo actualizar el pago.');

                    if (!response.ok) {
                        throw new Error(payload.message || errorMessage);
                    }

                    await refreshPosUi();
                    showUiToast(payload.message || successMessage);

                    if (actionType === 'add') {
                        revealLatestPaymentRow();
                    } else {
                        restorePaymentModalScroll(scrollTop);
                    }

                    const pagoModalEl = document.getElementById('pago_modal');
                    centerUiModal(pagoModalEl);
                } catch (error) {
                    showUiToast(error.message || 'No se pudo actualizar el pago.', 'red darken-2');
                } finally {
                    if (rowCard) {
                        rowCard.classList.remove('is-loading');
                    }

                    initialStates.forEach(({ button, disabled, html }) => {
                        button.disabled = disabled;
                        button.innerHTML = html;
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initSelects(document);
            initNav();
            initScannerInput();
            initPosShortcuts();
            initAsyncModalAdd();
            initAsyncPaymentModal();
            showSessionToasts();

            const buscarModalEl = document.getElementById('buscar_modal');
            const pagoModalEl = document.getElementById('pago_modal');
            const ventaModalEl = document.getElementById('venta_modal');

            if (window.M && M.Modal) {
                [buscarModalEl, pagoModalEl, ventaModalEl].forEach((modalEl) => {
                    if (!modalEl) {
                        return;
                    }

                    M.Modal.init(modalEl, {
                        dismissible: true,
                        onOpenStart: () => centerUiModal(modalEl),
                        onOpenEnd: () => centerUiModal(modalEl),
                    });
                });
            }

            const openBuscarBtn = document.getElementById('btn_open_buscar');
            if (openBuscarBtn) {
                openBuscarBtn.addEventListener('click', function () {
                    openModalById('buscar_modal');
                    window.setTimeout(() => {
                        const input = document.getElementById('q_modal');
                        if (input) {
                            input.focus();
                        }
                    }, 60);
                });
            }

            const openPagosBtn = document.getElementById('btn_open_pagos');
            if (openPagosBtn) {
                openPagosBtn.addEventListener('click', function () {
                    openModalById('pago_modal');
                });
            }

            const clearSearchBtn = document.getElementById('q_modal_clear');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    const input = document.getElementById('q_modal');
                    if (!input) {
                        return;
                    }

                    input.value = '';
                    input.dispatchEvent(new Event('keyup'));
                    input.focus();
                });
            }

            if (window.posConfig.activeModal === 'buscar') {
                openModalById('buscar_modal');
                window.setTimeout(() => {
                    const input = document.getElementById('q_modal');
                    if (input) {
                        input.focus();
                    }
                }, 80);
            }

            if (window.posConfig.activeModal === 'pagos') {
                openModalById('pago_modal');
            }

            if (window.posConfig.showLastSaleModal) {
                openModalById('venta_modal');
            }

            window.addEventListener('resize', function () {
                centerUiModal(buscarModalEl);
                centerUiModal(pagoModalEl);
                centerUiModal(ventaModalEl);
            });
        });
    </script>
</body>
</html>
