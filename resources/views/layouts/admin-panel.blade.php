@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $userName = $user?->username ?: ($user?->nombre_completo ?: ($user?->name ?: 'Usuario'));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/materialize-css@1.0.0/dist/css/materialize.min.css">

    <style>
        :root {
            --ui-bg: #dbe3eb;
            --ui-shell: #d6dee7;
            --ui-shell-2: #cfd9e3;
            --ui-card: #ffffff;
            --ui-card-soft: #f8fbfd;
            --ui-card-soft-2: #eef4f8;
            --ui-border: rgba(176, 190, 205, 0.78);
            --ui-border-strong: #c4d0dd;
            --ui-divider: rgba(107, 114, 128, 0.14);
            --ui-nav: #3a4652;
            --ui-nav-2: #465362;
            --ui-nav-text: #f8fafc;
            --ui-text: #1f2937;
            --ui-text-soft: #667085;
            --ui-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            --ui-shadow-card: 0 16px 32px rgba(15, 23, 42, 0.1);
            --ui-radius-lg: 28px;
            --ui-radius-md: 22px;
            --ui-radius-sm: 16px;
            --ui-primary: #182032;
            --ui-primary-2: #111827;
            --ui-success: #0f766e;
            --ui-danger: #b42318;
            --ui-warning-bg: #fff7e8;
            --ui-warning-border: #fed7aa;
            --ui-warning-text: #b54708;
            --ui-danger-bg: #fef3f2;
            --ui-danger-border: #fecdca;
            --ui-danger-text: #b42318;
            --ui-info-bg: #eef6ff;
            --ui-info-border: #bfdbfe;
            --ui-info-text: #1d4ed8;
            --ui-success-bg: #edf7f0;
            --ui-success-border: #cce8d3;
            --ui-success-text: #137333;
        }

        body {
            background: linear-gradient(180deg, #e7edf3 0%, var(--ui-bg) 100%);
            color: var(--ui-text);
        }

        main {
            padding: 16px;
        }

        .admin-page-shell {
            max-width: 1520px;
            margin: 0 auto;
            padding: 18px;
            border: 1px solid var(--ui-border);
            border-radius: 30px;
            background: linear-gradient(180deg, #dbe3eb 0%, var(--ui-shell) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.45),
                0 18px 40px rgba(15, 23, 42, 0.12);
        }

        .admin-page-stack {
            display: grid;
            gap: 18px;
        }

        .card {
            border-radius: var(--ui-radius-lg);
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border: 1px solid var(--ui-border);
            box-shadow: var(--ui-shadow-card);
        }

        .card .card-content {
            background: transparent;
            padding: 22px;
        }

        .card .card-title {
            margin-bottom: 8px;
            color: var(--ui-text);
            font-size: 1.35rem;
            font-weight: 900;
        }

        .card-panel {
            background: linear-gradient(180deg, var(--ui-card-soft) 0%, var(--ui-card-soft-2) 100%);
            border: 1px solid var(--ui-border-strong);
            border-radius: 18px;
            box-shadow: none;
        }

        .admin-flash {
            transition: opacity .26s ease, transform .26s ease, max-height .26s ease, margin .26s ease, padding .26s ease;
            overflow: hidden;
            transform-origin: top;
        }

        .grey-text {
            color: var(--ui-text-soft) !important;
        }

        .subtle,
        .admin-subtle {
            color: var(--ui-text-soft);
            font-size: 13px;
            line-height: 1.45;
        }

        .soft-panel,
        .admin-soft-panel {
            border: 1px solid var(--ui-border);
            border-radius: var(--ui-radius-md);
            padding: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(243, 248, 252, 0.94) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .soft-panel h6,
        .admin-soft-panel h6 {
            margin: 0 0 14px;
            font-size: 1rem;
            font-weight: 900;
            color: var(--ui-text);
        }

        .collection {
            border: 1px solid var(--ui-border);
        }

        .collection .collection-item {
            background: rgba(255, 255, 255, .35);
            border-bottom-color: var(--ui-divider);
        }

        .input-field input:focus + label,
        .input-field textarea:focus + label {
            color: #8d5a4d !important;
        }

        .input-field input:focus,
        .input-field textarea:focus {
            border-bottom: 1px solid #8d5a4d !important;
            box-shadow: 0 1px 0 0 #8d5a4d !important;
        }

        .dropdown-content {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--ui-border);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
        }

        .dropdown-content li > a,
        .dropdown-content li > span {
            color: var(--ui-text);
            font-weight: 700;
        }

        .dropdown-content li:hover,
        .dropdown-content li.active {
            background: rgba(226, 232, 240, 0.62);
        }

        .app-nav {
            margin: 0;
            background: linear-gradient(135deg, var(--ui-nav) 0%, var(--ui-nav-2) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 12px 24px rgba(15, 23, 42, 0.16);
        }

        .app-nav .nav-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 24px;
            min-height: 64px;
        }

        .app-nav .nav-wrapper.container {
            width: 100%;
            max-width: 1520px;
            margin: 0 auto;
            padding: 0 18px;
        }

        .app-nav-left {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1 1 auto;
        }

        .app-nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #f8fafc;
            flex: 0 1 auto;
            min-width: 0;
            max-width: 100%;
        }

        .app-nav-brand:hover {
            color: #ffffff;
        }

        .app-nav-brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.06) 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #f8fafc;
            padding: 5px;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.08),
                0 6px 14px rgba(15, 23, 42, 0.12);
        }

        .app-nav-brand-mark svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .app-nav-brand-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .app-nav-brand-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            line-height: 1;
            color: #f8fafc;
        }

        .app-nav-brand-caption {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            line-height: 1;
            color: rgba(226, 232, 240, 0.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .app-nav-right,
        .app-nav-right li {
            display: flex;
            align-items: center;
            gap: 12px;
            list-style: none;
        }

        .app-nav-right {
            flex: 1 1 auto;
            justify-content: flex-start;
            margin: 0;
            min-width: 0;
        }

        .app-nav-right .nav-meta-start {
            margin-left: auto;
        }

        .app-nav .nav-link-btn {
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
            min-height: auto;
        }

        .app-nav .nav-link-btn .material-icons {
            display: none;
        }

        .app-nav .nav-link-btn:hover,
        .app-nav .nav-link-btn:focus-visible {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.28);
            color: #f8fafc !important;
            outline: none;
            transform: none;
        }

        .app-nav .nav-link-btn.is-active {
            border-color: #f8fafc;
            color: #ffffff !important;
        }

        .app-nav .nav-chip {
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

        .app-nav .nav-chip i {
            font-size: 15px;
            color: rgba(226, 232, 240, 0.56);
            opacity: 1;
        }

        .app-nav .nav-chip.nav-chip-note {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .app-nav .nav-user-btn {
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
            min-height: auto;
        }

        .app-nav .nav-user-btn:hover,
        .app-nav .nav-user-btn:focus-visible {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.18);
            color: #ffffff !important;
            outline: none;
            transform: none;
        }

        .app-nav .nav-user-btn .material-icons {
            margin: 0 !important;
            font-size: 18px;
            color: inherit;
            opacity: 0.92;
        }

        .app-nav .nav-user-btn .nav-user-caret {
            font-size: 18px;
        }

        .app-nav .nav-mobile-trigger {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #f8fafc !important;
            display: none;
            align-items: center;
            justify-content: center;
            margin: 0;
            flex-shrink: 0;
        }

        .app-nav .nav-mobile-trigger:hover,
        .app-nav .nav-mobile-trigger:focus-visible {
            background: rgba(255, 255, 255, 0.08);
            outline: none;
        }

        @media only screen and (max-width: 1280px) {
            #admin_nav_date {
                display: none;
            }
        }

        @media only screen and (max-width: 992px) {
            .app-nav .nav-wrapper {
                gap: 14px;
            }

            .app-nav .nav-wrapper.container {
                padding: 0 14px;
            }

            .app-nav .nav-mobile-trigger {
                display: inline-flex;
            }

            .app-nav-brand-caption {
                max-width: 170px;
            }

            .app-nav-right {
                display: none;
            }
        }

        @media only screen and (max-width: 600px) {
            .app-nav .nav-wrapper {
                min-height: 58px;
            }

            .app-nav .nav-wrapper.container {
                padding: 0 12px;
            }

            .app-nav-left {
                gap: 12px;
            }

            .app-nav-brand-mark {
                width: 32px;
                height: 32px;
                border-radius: 9px;
                padding: 4px;
            }

            .app-nav-brand-name {
                font-size: 13px;
                letter-spacing: 0.08em;
            }

            .app-nav-brand-caption {
                max-width: 140px;
                font-size: 9px;
                letter-spacing: 0.12em;
            }
        }

        @media only screen and (max-width: 420px) {
            .app-nav-brand-caption {
                display: none;
            }
        }

        .sidenav.sidenav-fixed {
            width: 260px;
            background: linear-gradient(180deg, var(--ui-nav) 0%, var(--ui-nav-2) 100%);
            border-right: 1px solid var(--ui-border);
        }

        header,
        main,
        footer {
            padding-left: 260px;
        }

        @media only screen and (max-width: 992px) {
            header,
            main,
            footer {
                padding-left: 0;
            }
        }

        .brand {
            padding: 14px 12px 12px;
            color: var(--ui-nav-text);
        }

        .brand-shell {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .1);
        }

        .brand-shell .brand-logo-wrap {
            width: 78px;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            flex: 0 0 auto;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.18);
            background: rgba(255, 255, 255, .16);
            padding: 6px;
        }

        .brand-shell .brand-logo-wrap svg {
            width: 100%;
            height: 100%;
            fill: #f8fafc;
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.2rem;
            line-height: 1.1;
            color: var(--ui-nav-text);
        }

        .brand-subtitle {
            font-size: .82rem;
            color: rgba(248, 250, 252, 0.76);
            margin-top: 2px;
            line-height: 1.1;
        }

        .menu-section {
            padding: 8px 20px;
            font-size: .85rem;
            color: rgba(248, 250, 252, 0.54);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .sidenav li > a {
            color: rgba(248, 250, 252, 0.9);
            border-radius: 14px;
            margin: 2px 10px;
            padding: 0 14px !important;
            font-weight: 700;
            font-size: 0.92rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidenav li > a:hover {
            background: rgba(255, 255, 255, .1);
        }

        .sidenav li > a i.material-icons {
            color: rgba(248, 250, 252, 0.74);
            margin: 0 !important;
            width: 20px;
            min-width: 20px;
            height: 20px;
            float: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            line-height: 1;
        }

        .sidenav li > a.admin-link-active {
            background: rgba(255, 255, 255, .14);
            font-weight: 700;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .btn,
        .btn-small,
        .btn-large,
        .btn-flat {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 800;
            box-shadow: none;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
        }

        .btn:hover,
        .btn-small:hover,
        .btn-large:hover,
        .btn-flat:hover {
            transform: translateY(-1px);
            box-shadow: none;
        }

        .btn,
        .btn-large,
        .btn.brown.darken-1,
        .btn.blue,
        .btn-small {
            background: var(--ui-primary);
            border: 1px solid var(--ui-primary);
            color: #ffffff;
        }

        .btn:hover,
        .btn-large:hover,
        .btn.brown.darken-1:hover,
        .btn.blue:hover,
        .btn-small:hover {
            background: var(--ui-primary-2);
        }

        .btn.green,
        .btn.green.darken-1,
        .btn-small.green {
            background: var(--ui-success);
            border-color: var(--ui-success);
        }

        .btn.red,
        .btn-small.red {
            background: var(--ui-danger);
            border-color: var(--ui-danger);
        }

        .btn.grey.lighten-3.black-text,
        .btn-flat,
        .btn-small.grey.lighten-1.black-text {
            background: #f8fafc;
            border: 1px solid #dde4ee;
            color: #475467 !important;
        }

        .btn.grey.lighten-3.black-text:hover,
        .btn-flat:hover,
        .btn-small.grey.lighten-1.black-text:hover {
            background: #f2f5f9;
            color: #344054 !important;
        }

        .btn,
        .btn-flat {
            min-height: 40px;
            padding: 0 20px;
        }

        .btn-small {
            min-height: 34px;
            padding: 0 14px;
            line-height: 1.1;
        }

        .btn i.left,
        .btn i.right,
        .btn-small i.left,
        .btn-small i.right,
        .btn-flat i.left,
        .btn-flat i.right {
            float: none;
            margin: 0;
            font-size: 18px;
            line-height: 1;
        }

        .pagination li a {
            color: var(--ui-text);
            font-weight: 700;
        }

        .pagination li.active {
            background: var(--ui-primary);
            border-radius: 999px;
        }

        .pagination li.active a {
            color: #ffffff;
        }

        .new.badge {
            border-radius: 999px;
            padding: 0 10px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
        }

        .new.badge.green {
            background: var(--ui-success-bg);
            color: var(--ui-success-text);
        }

        .new.badge.red {
            background: var(--ui-danger-bg);
            color: var(--ui-danger-text);
        }

        .input-field {
            margin-top: 12px;
        }

        .input-field > label,
        .input-field .prefix,
        .select-wrapper + label {
            color: var(--ui-text-soft) !important;
        }

        .input-field > label {
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .input-field input:not([type]),
        .input-field input[type="text"],
        .input-field input[type="password"],
        .input-field input[type="email"],
        .input-field input[type="number"],
        .input-field input[type="search"],
        .input-field input[type="date"],
        .input-field input[type="url"],
        .input-field input[type="tel"],
        .input-field textarea.materialize-textarea,
        .select-wrapper input.select-dropdown {
            border: 1px solid #dde3ec !important;
            border-radius: 20px !important;
            background: linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85) !important;
            color: var(--ui-text) !important;
            box-sizing: border-box;
            padding: 0 18px !important;
            margin: 0;
        }

        .input-field input:not([type]),
        .input-field input[type="text"],
        .input-field input[type="password"],
        .input-field input[type="email"],
        .input-field input[type="number"],
        .input-field input[type="search"],
        .input-field input[type="date"],
        .input-field input[type="url"],
        .input-field input[type="tel"],
        .select-wrapper input.select-dropdown {
            height: 54px !important;
        }

        .input-field textarea.materialize-textarea {
            min-height: 130px;
            padding-top: 14px !important;
            padding-bottom: 14px !important;
        }

        .input-field input:focus,
        .input-field textarea:focus,
        .select-wrapper input.select-dropdown:focus {
            border-color: #c7d2e0 !important;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12) !important;
        }

        .input-field .prefix.active {
            color: var(--ui-primary) !important;
        }

        .dropdown-content.select-dropdown li > span {
            color: var(--ui-text);
            font-weight: 700;
        }

        .select-wrapper .caret {
            fill: var(--ui-text-soft);
        }

        .switch label {
            color: var(--ui-text-soft);
            font-weight: 700;
        }

        .switch label input[type=checkbox]:checked + .lever {
            background-color: rgba(15, 118, 110, 0.28);
        }

        .switch label input[type=checkbox]:checked + .lever:after {
            background-color: var(--ui-success);
        }

        .admin-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid var(--ui-border);
            background: rgba(255, 255, 255, 0.88);
            color: var(--ui-text-soft);
            font-size: 12px;
            font-weight: 800;
        }

        .admin-chip i.material-icons {
            font-size: 16px;
        }

        .admin-icon-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid #dde4ee;
            background: #f8fafc;
            color: #475467;
            cursor: pointer;
        }

        .admin-icon-btn:hover {
            background: #f2f5f9;
            color: #344054;
        }

        .admin-empty-state {
            padding: 22px;
            border: 1px dashed var(--ui-border-strong);
            border-radius: 18px;
            background: rgba(248, 250, 252, 0.72);
            color: var(--ui-text-soft);
            text-align: center;
        }

        .admin-table-wrap,
        .table-wrap,
        .responsive-table {
            overflow: auto;
            max-width: 100%;
            padding-bottom: 2px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 114, 128, 0.32) transparent;
            scrollbar-gutter: stable both-edges;
        }

        .admin-table-wrap::-webkit-scrollbar,
        .table-wrap::-webkit-scrollbar,
        .responsive-table::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .admin-table-wrap::-webkit-scrollbar-thumb,
        .table-wrap::-webkit-scrollbar-thumb,
        .responsive-table::-webkit-scrollbar-thumb {
            background: rgba(107, 114, 128, 0.28);
            border-radius: 999px;
        }

        .admin-table-wrap::-webkit-scrollbar-track,
        .table-wrap::-webkit-scrollbar-track,
        .responsive-table::-webkit-scrollbar-track {
            background: transparent;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        table thead th {
            padding: 11px 13px;
            text-align: left;
            font-size: 0.74rem;
            color: var(--ui-text-soft);
            font-weight: 800;
            background: linear-gradient(180deg, var(--ui-card-soft) 0%, var(--ui-card-soft-2) 100%);
            border-top: 1px solid var(--ui-border-strong);
            border-bottom: 1px solid var(--ui-border-strong);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.16);
        }

        table thead th:first-child {
            border-left: 1px solid var(--ui-border-strong);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        table thead th:last-child {
            border-right: 1px solid var(--ui-border-strong);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        table tbody td {
            padding: 13px;
            color: var(--ui-text);
            vertical-align: middle;
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            border-top: 1px solid var(--ui-border-strong);
            border-bottom: 1px solid var(--ui-border-strong);
            font-size: 0.9rem;
        }

        table tbody td:first-child {
            border-left: 1px solid var(--ui-border-strong);
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
        }

        table tbody td:last-child {
            border-right: 1px solid var(--ui-border-strong);
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        table.striped > tbody > tr:nth-child(odd) {
            background-color: transparent;
        }

        .modal {
            left: 50% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
            margin: 0 !important;
            border-radius: 28px !important;
            overflow: hidden !important;
            border: 1px solid var(--ui-border) !important;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%) !important;
            box-shadow: 0 28px 48px rgba(15, 23, 42, 0.22) !important;
        }

        .modal .modal-content {
            padding: 0 !important;
        }

        .modal .modal-footer,
        .modal.modal-fixed-footer .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            height: auto;
            padding: 16px 24px 22px;
            background: linear-gradient(180deg, #f8fbfd 0%, #eff4f8 100%);
            border-top: 1px solid var(--ui-divider);
        }

        .modal.modal-fixed-footer {
            height: auto !important;
            max-height: min(92vh, 840px);
            display: none;
        }

        .modal.modal-fixed-footer.open {
            display: flex !important;
            flex-direction: column;
        }

        .modal.modal-fixed-footer .modal-content {
            position: static !important;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
        }

        .modal.modal-fixed-footer .admin-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-gutter: stable both-edges;
        }

        .modal.modal-fixed-footer .modal-footer {
            position: static !important;
            flex-shrink: 0;
        }

        .admin-modal-head {
            padding: 22px 24px 18px;
            background: linear-gradient(135deg, var(--ui-nav) 0%, var(--ui-nav-2) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-modal-title {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.06;
            font-weight: 900;
            color: #f8fafc;
        }

        .admin-modal-subtitle {
            margin: 8px 0 0;
            color: rgba(226, 232, 240, 0.8);
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .admin-modal-body {
            padding: 22px 24px 16px;
            background: transparent;
        }

        .modal .modal-content .input-field {
            margin-bottom: 16px;
        }

        @media only screen and (min-width: 993px) {
            .modal {
                width: 60% !important;
                max-width: 720px;
            }
        }

        @media only screen and (max-width: 992px) {
            .modal {
                width: 92% !important;
            }

            .admin-page-shell {
                border-radius: 24px;
                padding: 14px;
            }

            .card .card-content,
            .admin-modal-body,
            .admin-modal-head,
            .modal .modal-footer {
                padding-left: 18px;
                padding-right: 18px;
            }

            .modal.modal-fixed-footer {
                max-height: calc(100dvh - 18px);
            }
        }

        @media only screen and (max-width: 768px) {
            main {
                padding: 12px;
            }

            .responsive-stack-table {
                border-spacing: 0;
                min-width: 0;
            }

            .responsive-stack-table thead {
                display: none;
            }

            .responsive-stack-table tbody,
            .responsive-stack-table tr,
            .responsive-stack-table td {
                display: block;
                width: 100%;
            }

            .responsive-stack-table tbody {
                display: grid;
                gap: 12px;
            }

            .responsive-stack-table tbody tr {
                padding: 14px 16px;
                border: 1px solid var(--ui-border-strong);
                border-radius: 20px;
                background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.88),
                    0 8px 18px rgba(15, 23, 42, 0.06);
            }

            .responsive-stack-table tbody td {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 8px 0;
                border: none;
                border-radius: 0;
                background: transparent;
                text-align: left !important;
            }

            .responsive-stack-table tbody td::before {
                content: attr(data-label);
                flex: 0 0 108px;
                max-width: 45%;
                color: var(--ui-text-soft);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                line-height: 1.4;
            }

            .responsive-stack-table tbody td[colspan],
            .responsive-stack-table tbody tr.responsive-stack-note-row td {
                display: block;
                padding: 4px 0;
            }

            .responsive-stack-table tbody td[colspan]::before,
            .responsive-stack-table tbody tr.responsive-stack-note-row td::before {
                display: none;
            }

            .responsive-stack-table tbody td.right-align,
            .responsive-stack-table tbody td.is-right {
                justify-content: space-between;
                text-align: left !important;
            }

            .responsive-stack-table tbody td > .actions-cell,
            .responsive-stack-table tbody td > .cc-table-actions {
                margin-left: auto;
            }
        }

        @media only screen and (max-width: 600px) {
            .admin-page-shell {
                border-radius: 20px;
                padding: 12px;
            }

            .card .card-content {
                padding: 18px;
            }

            .admin-modal-title {
                font-size: 1.3rem;
            }

            .admin-modal-subtitle {
                font-size: 0.86rem;
            }

            .modal {
                width: calc(100vw - 14px) !important;
                border-radius: 22px !important;
            }

            .modal .modal-footer {
                justify-content: stretch;
            }

            .modal .modal-footer .btn,
            .modal .modal-footer .btn-flat {
                width: 100%;
            }

            #toast-container {
                top: 12px !important;
                right: 10px !important;
                left: 10px !important;
                bottom: auto !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <header>
        <nav class="app-nav">
            <div class="nav-wrapper container">
                <div class="app-nav-left">
                    <a href="#" data-target="admin-sidenav" class="sidenav-trigger nav-mobile-trigger hide-on-large-only">
                        <i class="material-icons">menu</i>
                    </a>
                    <a href="{{ route('admin-panel.dashboard') }}" class="app-nav-brand">
                        <span class="app-nav-brand-mark">
                            <x-application-logo />
                        </span>
                        <span class="app-nav-brand-copy">
                            <span class="app-nav-brand-name">{{ config('app.name', 'VGC') }}</span>
                            <span class="app-nav-brand-caption">Panel de administración</span>
                        </span>
                    </a>
                </div>

                <ul class="app-nav-right hide-on-med-and-down">
                    <li>
                        <a @class(['btn-flat waves-effect nav-link-btn', 'is-active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">
                            <i class="material-icons">dashboard</i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a @class(['btn-flat waves-effect nav-link-btn', 'is-active' => request()->routeIs('caja.*')]) href="{{ route('caja.pos') }}">
                            <i class="material-icons">point_of_sale</i>
                            Caja
                        </a>
                    </li>
                    <li>
                        <a @class(['btn-flat waves-effect nav-link-btn', 'is-active' => request()->routeIs('catalogo.*')]) href="{{ route('catalogo.index') }}">
                            <i class="material-icons">inventory_2</i>
                            Catalogo
                        </a>
                    </li>
                    <li>
                        <a @class(['btn-flat waves-effect nav-link-btn', 'is-active' => request()->routeIs('cuentas-corrientes.*')]) href="{{ route('cuentas-corrientes.index') }}">
                            <i class="material-icons">account_balance_wallet</i>
                            Cuentas Corrientes
                        </a>
                    </li>
                    <li>
                        <a @class(['btn-flat waves-effect nav-link-btn', 'is-active' => request()->routeIs('admin-panel.*')]) href="{{ route('admin-panel.dashboard') }}">
                            <i class="material-icons">admin_panel_settings</i>
                            Admin
                        </a>
                    </li>
                    <li class="nav-meta-start">
                        <span class="nav-chip nav-chip-note">
                            <i class="material-icons">space_dashboard</i>
                            @yield('header_title', 'Panel de administración')
                        </span>
                    </li>
                    <li>
                        <span class="nav-chip" id="admin_nav_date">
                            <i class="material-icons">calendar_today</i>--/--/----
                        </span>
                    </li>
                    <li>
                        <span class="nav-chip" id="admin_nav_time">
                            <i class="material-icons">schedule</i>--:--
                        </span>
                    </li>
                    <li>
                        <a class="dropdown-trigger btn-flat waves-effect nav-user-btn" href="#!" data-target="admin_user_dd">
                            <i class="material-icons">account_circle</i>
                            {{ $userName }}
                            <i class="material-icons nav-user-caret">expand_more</i>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <ul id="admin_user_dd" class="dropdown-content">
        <li><a href="{{ route('profile.edit') }}"><i class="material-icons">person</i>Perfil</a></li>
        <li class="divider" tabindex="-1"></li>
        <li>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('admin_logout_form').submit();">
                <i class="material-icons">logout</i>Salir
            </a>
        </li>
    </ul>

    <form id="admin_logout_form" method="POST" action="{{ route('logout') }}" style="display:none;">
        @csrf
    </form>

    <ul id="admin-sidenav" class="sidenav sidenav-fixed">
        <li class="brand">
            <div class="brand-shell">
                <span class="brand-logo-wrap">
                    <x-application-logo />
                </span>
                <div>
                    <div class="brand-title">{{ config('app.name', 'VGC') }}</div>
                    <div class="brand-subtitle">Sistema de Ventas</div>
                </div>
            </div>
        </li>

        <li class="menu-section">Principal</li>
        <li>
            <a href="{{ route('admin-panel.dashboard') }}" class="{{ request()->routeIs('admin-panel.dashboard') ? 'admin-link-active' : '' }}">
                <i class="material-icons">dashboard</i>Dashboard
            </a>
        </li>

        <li class="menu-section">Gestión</li>
        <li>
            <a href="{{ route('catalogo.index') }}">
                <i class="material-icons">inventory_2</i>Catálogo
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.ventas.index') }}" class="{{ request()->routeIs('admin-panel.ventas.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">receipt_long</i>Ventas
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.users.index') }}" class="{{ request()->routeIs('admin-panel.users.*', 'admin-panel.roles.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">people</i>Usuarios
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.settings.index') }}" class="{{ request()->routeIs('admin-panel.settings.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">settings</i>Configuración
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.empresa.index') }}" class="{{ request()->routeIs('admin-panel.empresa.*', 'admin-panel.sucursales.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">storefront</i>Datos de empresa
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.balances.index') }}" class="{{ request()->routeIs('admin-panel.balances.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">insights</i>Balances
            </a>
        </li>
        <li>
            <a href="{{ route('admin-panel.tarjetas.index') }}" class="{{ request()->routeIs('admin-panel.tarjetas.*') ? 'admin-link-active' : '' }}">
                <i class="material-icons">credit_card</i>Tarjetas
            </a>
        </li>
        <li>
            <a href="{{ route('cuentas-corrientes.index') }}">
                <i class="material-icons">account_balance_wallet</i>Cuentas corrientes
            </a>
        </li>
    </ul>

    <main>
        <div class="admin-page-shell">
            <div class="admin-page-stack">
                @if (session('success'))
                    <div class="card-panel admin-flash" style="background:var(--ui-success-bg); border-color:var(--ui-success-border); color:var(--ui-success-text);">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="card-panel admin-flash" style="background:var(--ui-danger-bg); border-color:var(--ui-danger-border); color:var(--ui-danger-text);">{{ session('error') }}</div>
                @endif
                @if (session('warning'))
                    <div class="card-panel admin-flash" style="background:var(--ui-warning-bg); border-color:var(--ui-warning-border); color:var(--ui-warning-text);">{{ session('warning') }}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </main>

    <script defer src="https://cdn.jsdelivr.net/npm/materialize-css@1.0.0/dist/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.M) {
                return;
            }

            M.Sidenav.init(document.querySelectorAll('.sidenav'), {});
            M.Modal.init(document.querySelectorAll('.modal'), {});
            M.FormSelect.init(document.querySelectorAll('select'), {});
            M.Dropdown.init(document.querySelectorAll('.dropdown-trigger'), {
                coverTrigger: false,
                constrainWidth: false,
            });

            document.querySelectorAll('.modal[data-auto-open="true"]').forEach(function (modalEl) {
                const instance = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl, {});
                instance.open();
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const dateEl = document.getElementById('admin_nav_date');
            const timeEl = document.getElementById('admin_nav_time');

            function tickNavClock() {
                const d = new Date();
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                const hh = String(d.getHours()).padStart(2, '0');
                const mi = String(d.getMinutes()).padStart(2, '0');

                if (dateEl) {
                    dateEl.innerHTML = '<i class="material-icons">calendar_today</i> ' + dd + '/' + mm + '/' + yyyy;
                }

                if (timeEl) {
                    timeEl.innerHTML = '<i class="material-icons">schedule</i> ' + hh + ':' + mi;
                }
            }

            tickNavClock();
            setInterval(tickNavClock, 10000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            const flashes = document.querySelectorAll('.admin-flash');
            flashes.forEach(function (el) {
                setTimeout(function () {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-4px)';
                    el.style.maxHeight = '0';
                    el.style.marginTop = '0';
                    el.style.marginBottom = '0';
                    el.style.paddingTop = '0';
                    el.style.paddingBottom = '0';
                    setTimeout(function () { el.remove(); }, 280);
                }, 3000);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
