<x-app-layout>
    <style>
        .catalog-wrap {
            --pos-warm-bg: #d6dee7;
            --pos-card-bg: #ffffff;
            --pos-card-head: #f8fafc;
            --pos-card-border: #e5e7eb;
            --pos-soft-surface: #f1f6fa;
            --pos-soft-surface-2: #e6eef5;
            --pos-soft-border: #c4d0dd;
            --pos-soft-border-strong: #b3c0cf;
            --pos-divider: rgba(107, 114, 128, 0.14);
            --pos-strong-surface-1: #3a4652;
            --pos-strong-surface-2: #465362;
            --pos-radius-md: 12px;
            --pos-radius-lg: 24px;
            --pos-shadow-soft: 0 8px 16px rgba(15, 23, 42, 0.08);
            --pos-shadow-card: 0 10px 24px rgba(15, 23, 42, 0.1);
            max-width: 1520px;
            margin: 0 auto;
            padding: 18px;
        }

        .catalog-shell {
            border: 1px solid rgba(176, 190, 205, 0.72);
            border-radius: 30px;
            background: linear-gradient(180deg, #dbe3eb 0%, var(--pos-warm-bg) 100%);
            padding: 18px;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.45),
                0 18px 40px rgba(15, 23, 42, 0.12);
        }

        .catalog-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding: 22px 24px;
            border-radius: 26px;
            background: linear-gradient(135deg, var(--pos-strong-surface-1) 0%, var(--pos-strong-surface-2) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.06),
                0 14px 28px rgba(15, 23, 42, 0.18);
        }

        .catalog-head-copy {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .catalog-head h4 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.02;
            font-weight: 900;
            color: #f8fafc;
        }

        .catalog-head p {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.45;
            color: rgba(226, 232, 240, 0.78);
        }

        .catalog-head-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .catalog-card {
            background: var(--pos-card-bg);
            border: 1px solid var(--pos-card-border);
            border-radius: var(--pos-radius-lg);
            box-shadow: var(--pos-shadow-card);
            overflow: hidden;
        }

        .catalog-card-body {
            padding: 18px;
        }

        .catalog-card > .catalog-card-body:first-child {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        }

        .catalog-card > .catalog-card-body + .catalog-card-body {
            border-top: 1px solid var(--pos-divider);
        }

        .catalog-tab-btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .catalog-tab-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 20px;
            border: 1px solid #dde4ee;
            border-radius: 999px;
            background: #f8fafc;
            color: #475467;
            font-size: 0.92rem;
            font-weight: 800;
            text-decoration: none;
            transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease, box-shadow 150ms ease;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .catalog-tab-btn.is-active {
            background: #182032;
            border-color: #182032;
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(24, 32, 50, 0.16);
        }

        .catalog-tab-btn.is-inactive:hover {
            background: #f2f5f9;
            border-color: #d0d9e3;
            color: #344054;
        }

        .catalog-grid,
        .catalog-categories-grid {
            display: grid;
            gap: 18px;
            margin-top: 16px;
            align-items: start;
        }

        .catalog-grid {
            grid-template-columns: minmax(380px, 0.9fr) minmax(520px, 1.1fr);
        }

        .catalog-categories-grid {
            grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
        }

        .catalog-title {
            margin: 0;
            font-size: 1.7rem;
            line-height: 1.06;
            font-weight: 900;
            color: #1f2937;
        }

        .catalog-note {
            margin: 6px 0 0;
            font-size: 0.92rem;
            line-height: 1.45;
            color: #667085;
        }

        .catalog-panel-title {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.08;
            font-weight: 900;
            color: #1f2937;
        }

        .catalog-search-row {
            display: flex;
            gap: 12px;
            align-items: end;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .catalog-search-field {
            flex: 1 1 280px;
        }

        .catalog-search-field label,
        .catalog-form-stack label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.83rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #667085;
        }

        .catalog-search-shell {
            position: relative;
        }

        .catalog-search-icon {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #98a2b3;
            pointer-events: none;
        }

        .catalog-search-field input,
        .catalog-form-stack input[type="text"] {
            width: 100%;
            height: 54px;
            border: 1px solid #dde3ec;
            border-radius: 20px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            padding: 0 18px;
            color: #1f2937;
            font-size: 0.98rem;
            font-weight: 600;
            box-sizing: border-box;
        }

        .catalog-search-field input {
            padding-left: 52px;
        }

        .catalog-search-field input::placeholder,
        .catalog-form-stack input[type="text"]::placeholder {
            color: #98a2b3;
            opacity: 1;
        }

        .catalog-search-field input:focus,
        .catalog-form-stack input[type="text"]:focus {
            outline: none;
            border-color: #c7d2e0;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
        }

        .catalog-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: #182032;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
            box-shadow: none;
        }

        .catalog-btn:hover {
            background: #111827;
        }

        .catalog-btn-secondary {
            background: #f8fafc;
            border-color: #dde4ee;
            color: #475467;
        }

        .catalog-btn-secondary:hover {
            background: #f2f5f9;
        }

        .catalog-btn-light {
            background: rgba(255, 255, 255, 0.92);
            border-color: rgba(255, 255, 255, 0.18);
            color: #1f2937;
        }

        .catalog-btn-light:hover {
            background: #ffffff;
        }

        .catalog-list {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .catalog-list-item {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 14px 14px 18px;
            border: 1px solid var(--pos-soft-border);
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.86),
                0 0 0 1px rgba(179, 192, 207, 0.3),
                0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .catalog-list-item::before {
            content: '';
            position: absolute;
            left: 13px;
            top: 13px;
            bottom: 13px;
            width: 4px;
            border-radius: 999px;
            background: #64748b;
        }

        .catalog-list-item.is-active {
            border-color: var(--pos-soft-border-strong);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 1px rgba(148, 163, 184, 0.42),
                0 12px 24px rgba(15, 23, 42, 0.1);
        }

        .catalog-list-item.is-active::before {
            background: #182032;
        }

        .catalog-list-main {
            min-width: 0;
            padding-left: 11px;
        }

        .catalog-list-main strong {
            display: block;
            font-size: 0.92rem;
            line-height: 1.2;
            font-weight: 800;
            color: #1f2937;
        }

        .catalog-list-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        .catalog-muted {
            color: #667085;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        .catalog-badge-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .catalog-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .catalog-badge-warning {
            background: #fff7e8;
            border-color: #fed7aa;
            color: #b54708;
        }

        .catalog-badge-danger {
            background: #fef3f2;
            border-color: #fecdca;
            color: #b42318;
        }

        .catalog-badge-success {
            background: #edf7f0;
            border-color: #cce8d3;
            color: #137333;
        }

        .catalog-list-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .catalog-list-actions form {
            margin: 0;
        }

        .catalog-icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dde4ee;
            border-radius: 11px;
            background: #f8fafc;
            color: #475467;
            text-decoration: none;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
        }

        .catalog-icon-btn:hover {
            background: #f2f5f9;
        }

        .catalog-icon-btn.is-primary {
            background: #182032;
            border-color: #182032;
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(24, 32, 50, 0.16);
        }

        .catalog-icon-btn.is-primary:hover {
            background: #111827;
        }

        .catalog-icon-btn.delete {
            background: #fef3f2;
            border-color: #fecdca;
            color: #b42318;
        }

        .catalog-icon-btn.delete:hover {
            background: #fee4e2;
        }

        .catalog-icon-btn svg {
            width: 15px;
            height: 15px;
        }

        .catalog-panel-copy {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .catalog-panel-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0 0 16px;
        }

        .catalog-table-wrap {
            overflow: auto;
            max-width: 100%;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 114, 128, 0.28) transparent;
        }

        .catalog-table {
            width: 100%;
            min-width: 680px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .catalog-table-variants {
            min-width: 0;
            table-layout: fixed;
        }

        .catalog-table-variants th,
        .catalog-table-variants td {
            white-space: nowrap;
        }

        .catalog-table-variants th:nth-child(1),
        .catalog-table-variants td:nth-child(1) {
            width: 19%;
        }

        .catalog-table-variants th:nth-child(2),
        .catalog-table-variants td:nth-child(2) {
            width: 14%;
        }

        .catalog-table-variants th:nth-child(3),
        .catalog-table-variants td:nth-child(3) {
            width: 9%;
        }

        .catalog-table-variants th:nth-child(4),
        .catalog-table-variants td:nth-child(4) {
            width: 8%;
        }

        .catalog-table-variants th:nth-child(5),
        .catalog-table-variants td:nth-child(5) {
            width: 13%;
        }

        .catalog-table-variants th:nth-child(6),
        .catalog-table-variants td:nth-child(6) {
            width: 13%;
        }

        .catalog-table-variants th:nth-child(7),
        .catalog-table-variants td:nth-child(7) {
            width: 11%;
        }

        .catalog-table-variants th:nth-child(8),
        .catalog-table-variants td:nth-child(8) {
            width: 13%;
        }

        .catalog-table thead th {
            padding: 11px 13px;
            text-align: left;
            font-size: 0.74rem;
            color: #667085;
            font-weight: 700;
            background: linear-gradient(180deg, var(--pos-soft-surface) 0%, var(--pos-soft-surface-2) 100%);
            border-top: 1px solid var(--pos-soft-border);
            border-bottom: 1px solid var(--pos-soft-border);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 0 0 1px rgba(179, 192, 207, 0.16);
        }

        .catalog-table thead th:first-child {
            border-left: 1px solid var(--pos-soft-border);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .catalog-table thead th:last-child {
            border-right: 1px solid var(--pos-soft-border);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .catalog-table tbody td {
            padding: 13px;
            color: #1f2937;
            vertical-align: middle;
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            border-top: 1px solid var(--pos-soft-border);
            border-bottom: 1px solid var(--pos-soft-border);
            font-size: 0.9rem;
        }

        .catalog-table tbody td:first-child {
            border-left: 1px solid var(--pos-soft-border);
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
        }

        .catalog-table tbody td:last-child {
            border-right: 1px solid var(--pos-soft-border);
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .catalog-table .is-right {
            text-align: right;
        }

        .catalog-table tbody td.catalog-empty {
            text-align: center;
            border: 1px dashed #c4d0dd;
            border-radius: 18px;
            background: rgba(248, 250, 252, 0.72);
        }

        .catalog-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
        }

        .catalog-table-variants .catalog-code {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .catalog-table-variants .catalog-list-actions {
            gap: 6px;
        }

        .catalog-action-copy {
            display: none;
        }

        .catalog-code.is-muted {
            color: #667085;
            font-weight: 600;
        }

        .catalog-money {
            display: inline-block;
            font-size: 0.82rem;
            font-weight: 800;
            color: #111827;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .catalog-stock-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #111827;
            font-size: 0.82rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .catalog-empty {
            padding: 22px;
            border: 1px dashed #c4d0dd;
            border-radius: 18px;
            background: rgba(248, 250, 252, 0.72);
            color: #667085;
            font-size: 0.96rem;
            line-height: 1.45;
            text-align: center;
        }

        .catalog-form-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .catalog-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0;
            color: #344054;
        }

        .catalog-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .catalog-status-callout {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid #fecdca;
            background: #fef3f2;
            color: #b42318;
            font-size: 0.82rem;
            font-weight: 800;
            margin-top: 16px;
        }

        [x-cloak] {
            display: none !important;
        }

        .catalog-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            padding: 28px 18px;
            overflow-y: auto;
        }

        .catalog-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(3px);
        }

        .catalog-modal-dialog {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100vw - 36px));
            margin: 0 auto;
        }

        .catalog-modal-card {
            border: 1px solid rgba(176, 190, 205, 0.82);
            border-radius: 28px;
            background: linear-gradient(180deg, #dbe3eb 0%, #d6dee7 100%);
            box-shadow: 0 28px 56px rgba(15, 23, 42, 0.24);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: min(calc(100dvh - 56px), 920px);
        }

        .catalog-modal-head {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 16px 22px 10px;
            background: linear-gradient(180deg, #fbfcfe 0%, #eef3f8 100%);
            border-bottom: 1px solid var(--pos-divider);
            flex-shrink: 0;
        }

        .catalog-modal-copy {
            display: flex;
            flex-direction: column;
            gap: 0;
            align-items: center;
            min-width: 0;
            text-align: center;
        }

        .catalog-modal-title {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.08;
            font-weight: 900;
            color: #111827;
        }

        .catalog-modal-subtitle {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.45;
            color: rgba(226, 232, 240, 0.8);
        }

        .catalog-modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dde4ee;
            border-radius: 14px;
            background: #f8fafc;
            color: #475467;
            cursor: pointer;
            transition: background-color 150ms ease, border-color 150ms ease;
        }

        .catalog-modal-close:hover {
            background: #f2f5f9;
            border-color: #d0d9e3;
        }

        .catalog-modal-close svg {
            width: 18px;
            height: 18px;
        }

        .catalog-modal-body {
            padding: 12px 18px 18px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .catalog-modal-toolbar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .catalog-modal-filter {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .catalog-modal-field {
            display: block;
            min-width: 220px;
        }

        .catalog-modal-field span {
            display: block;
            margin-bottom: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #667085;
        }

        .catalog-modal-select {
            width: 100%;
            height: 48px;
            border: 1px solid #dde3ec;
            border-radius: 16px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            padding: 0 16px;
            color: #1f2937;
            font-size: 0.94rem;
            font-weight: 600;
        }

        .catalog-modal-select:focus {
            outline: none;
            border-color: #c7d2e0;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
        }

        .catalog-modal-callout {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 16px;
            margin-bottom: 14px;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .catalog-modal-callout.is-success {
            border: 1px solid #cce8d3;
            background: #edf7f0;
            color: #137333;
        }

        .catalog-modal-callout.is-danger {
            border: 1px solid #fecdca;
            background: #fef3f2;
            color: #b42318;
        }

        .catalog-stock-table-wrap {
            overflow: auto;
            padding-bottom: 4px;
        }

        .catalog-stock-table {
            width: 100%;
            min-width: 780px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .catalog-stock-table thead th {
            padding: 11px 14px;
            background: linear-gradient(180deg, var(--pos-soft-surface) 0%, var(--pos-soft-surface-2) 100%);
            border-top: 1px solid var(--pos-soft-border);
            border-bottom: 1px solid var(--pos-soft-border);
            color: #667085;
            font-size: 0.76rem;
            font-weight: 800;
            text-align: left;
        }

        .catalog-stock-table thead th:first-child {
            border-left: 1px solid var(--pos-soft-border);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .catalog-stock-table thead th:last-child {
            border-right: 1px solid var(--pos-soft-border);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .catalog-stock-table tbody td {
            padding: 12px 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
            border-top: 1px solid var(--pos-soft-border);
            border-bottom: 1px solid var(--pos-soft-border);
            vertical-align: top;
        }

        .catalog-stock-table tbody td:first-child {
            border-left: 1px solid var(--pos-soft-border);
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
            font-weight: 800;
            color: #1f2937;
            white-space: nowrap;
        }

        .catalog-stock-table tbody td:last-child {
            border-right: 1px solid var(--pos-soft-border);
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .catalog-stock-cell.is-focused {
            border-color: #182032;
            box-shadow: 0 0 0 3px rgba(24, 32, 50, 0.1);
        }

        .catalog-stock-cell {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
        }

        .catalog-stock-input {
            width: 100%;
            height: 40px;
            border: 1px solid #d5dde7;
            border-radius: 12px;
            background: #ffffff;
            padding: 0 12px;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 700;
        }

        .catalog-stock-input:focus {
            outline: none;
            border-color: #c7d2e0;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
        }

        .catalog-stock-sku {
            display: block;
            font-size: 0.72rem;
            line-height: 1.4;
            color: #667085;
            font-weight: 700;
            word-break: break-word;
        }

        .catalog-stock-empty-cell {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            color: #98a2b3;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .catalog-stock-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .catalog-stock-note {
            font-size: 0.84rem;
            line-height: 1.45;
            color: #667085;
        }

        .catalog-modal-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .catalog-modal-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .catalog-modal-grid .is-span-2 {
            grid-column: 1 / -1;
        }

        .catalog-modal-price-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .catalog-modal-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #667085;
        }

        .catalog-modal-input,
        .catalog-modal-textarea {
            width: 100%;
            border: 1px solid #dde3ec;
            border-radius: 16px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            padding: 0 16px;
            color: #1f2937;
            font-size: 0.94rem;
            font-weight: 600;
        }

        .catalog-modal-input {
            height: 48px;
        }

        .catalog-modal-textarea {
            min-height: 120px;
            padding-top: 14px;
            padding-bottom: 14px;
            resize: vertical;
        }

        .catalog-modal-input:focus,
        .catalog-modal-textarea:focus {
            outline: none;
            border-color: #c7d2e0;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
        }

        .catalog-modal-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            background: #f8fafc;
            color: #344054;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .catalog-modal-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .catalog-modal-actions-copy {
            font-size: 0.84rem;
            line-height: 1.45;
            color: #667085;
        }

        .catalog-modal-error {
            margin-top: 6px;
            font-size: 0.78rem;
            line-height: 1.4;
            font-weight: 700;
            color: #b42318;
        }

        .catalog-modal-hint {
            margin-top: 7px;
            font-size: 0.77rem;
            line-height: 1.45;
            color: #667085;
        }

        .catalog-modal-textarea-compact {
            min-height: 92px;
        }

        .catalog-modal-preview {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #dbe2ea;
            border-radius: 18px;
            background: linear-gradient(180deg, #f7f9fc 0%, #edf2f7 100%);
        }

        .catalog-modal-preview-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 0.8rem;
            line-height: 1.45;
            color: #667085;
        }

        .catalog-modal-preview-head strong {
            color: #111827;
            font-weight: 800;
        }

        .catalog-modal-preview-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .catalog-modal-preview-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.88);
        }

        .catalog-modal-preview-item span {
            font-size: 0.74rem;
            font-weight: 700;
            color: #667085;
        }

        .catalog-modal-preview-item strong {
            font-size: 1rem;
            font-weight: 900;
            color: #111827;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 1200px) {
            .catalog-grid,
            .catalog-categories-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .catalog-wrap {
                padding: 12px;
            }

            .catalog-shell {
                padding: 12px;
                border-radius: 24px;
            }

            .catalog-head {
                padding: 18px;
                border-radius: 22px;
            }

            .catalog-head h4 {
                font-size: 1.7rem;
            }

            .catalog-card-body {
                padding: 16px;
            }

            .catalog-title,
            .catalog-panel-title {
                font-size: 1.35rem;
            }

            .catalog-search-row,
            .catalog-form-actions,
            .catalog-panel-actions,
            .catalog-modal-toolbar,
            .catalog-stock-actions {
                align-items: stretch;
            }

            .catalog-search-field,
            .catalog-search-row .catalog-btn {
                width: 100%;
            }

            .catalog-modal {
                padding: 18px 10px;
            }

            .catalog-modal-dialog {
                width: min(100vw - 20px, 1180px);
            }

            .catalog-modal-head {
                padding: 12px 16px 8px;
            }

            .catalog-modal-body {
                padding: 10px 16px 16px;
            }

            .catalog-modal-field {
                min-width: 0;
                width: 100%;
            }

            .catalog-modal-grid {
                grid-template-columns: 1fr;
            }

            .catalog-modal-price-grid {
                grid-template-columns: 1fr;
            }

            .catalog-modal-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .catalog-stack-table {
                border-spacing: 0;
                min-width: 0;
            }

            .catalog-stack-table.catalog-table-variants {
                table-layout: auto;
            }

            .catalog-stack-table.catalog-table-variants th,
            .catalog-stack-table.catalog-table-variants td {
                width: auto !important;
                min-width: 0 !important;
                white-space: normal;
            }

            .catalog-stack-table thead {
                display: none;
            }

            .catalog-stack-table tbody,
            .catalog-stack-table tr,
            .catalog-stack-table td {
                display: block;
                width: 100%;
            }

            .catalog-stack-table tbody {
                display: grid;
                gap: 12px;
            }

            .catalog-stack-table tbody tr {
                padding: 14px 16px;
                border: 1px solid var(--pos-soft-border);
                border-radius: 20px;
                background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.86),
                    0 8px 18px rgba(15, 23, 42, 0.06);
            }

            .catalog-stack-table tbody td {
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

            .catalog-stack-table tbody td > * {
                min-width: 0;
            }

            .catalog-stack-table tbody td::before {
                content: attr(data-label);
                flex: 0 0 108px;
                max-width: 45%;
                color: #667085;
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                line-height: 1.4;
            }

            .catalog-stack-table tbody td.catalog-empty,
            .catalog-stack-table tbody td[colspan] {
                display: block;
                text-align: center !important;
                padding: 4px 0;
            }

            .catalog-stack-table tbody td.catalog-empty::before,
            .catalog-stack-table tbody td[colspan]::before {
                display: none;
            }

            .catalog-stack-table .is-right {
                justify-content: space-between;
                text-align: left !important;
            }

            .catalog-table-variants .catalog-code,
            .catalog-table-variants .catalog-money {
                white-space: normal;
                word-break: break-word;
            }

            .catalog-table-variants td[data-label="Acciones"] {
                align-items: stretch;
                justify-content: flex-start;
            }

            .catalog-table-variants td[data-label="Acciones"] .catalog-list-actions {
                flex: 1 1 auto;
                min-width: 0;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .catalog-table-variants td[data-label="Acciones"] .catalog-icon-btn,
            .catalog-table-variants td[data-label="Acciones"] form .catalog-icon-btn {
                width: 100%;
                min-height: 40px;
                height: auto;
                padding: 0 12px;
                border-radius: 14px;
                justify-content: center;
                gap: 8px;
            }

            .catalog-table-variants td[data-label="Acciones"] form {
                display: contents;
            }

            .catalog-table-variants td[data-label="Acciones"] .catalog-icon-btn svg {
                flex-shrink: 0;
            }

            .catalog-table-variants td[data-label="Acciones"] .catalog-action-copy {
                display: inline;
                font-size: 0.78rem;
                font-weight: 800;
                line-height: 1;
            }
        }

        @media (max-width: 420px) {
            .catalog-table-variants td[data-label="Acciones"] .catalog-list-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .catalog-list-item {
                flex-direction: column;
                align-items: stretch;
            }

            .catalog-list-actions,
            .catalog-panel-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .catalog-panel-actions .catalog-btn,
            .catalog-tab-btns .catalog-tab-btn {
                width: 100%;
            }

            .catalog-modal {
                padding: 10px 8px;
            }

            .catalog-modal-dialog {
                width: min(100vw - 16px, 1180px);
            }

            .catalog-modal-card {
                max-height: calc(100dvh - 16px);
                border-radius: 24px;
            }

            .catalog-modal-head {
                padding: 12px 14px 10px;
            }

            .catalog-modal-body {
                padding: 10px 14px 14px;
            }

            .catalog-modal-preview-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="catalog-wrap">
        <div class="catalog-shell">
            <input type="hidden" id="selected_product_id" value="{{ $selectedProduct?->id }}">
            <div class="catalog-head">
                <div class="catalog-head-copy">
                    <h4>Catalogo</h4>
                    <p>Productos, variantes y categorias dentro del lenguaje visual del POS.</p>
                </div>
            </div>

            <div class="catalog-card">
                <div class="catalog-card-body">
                    <div class="catalog-tab-btns">
                        <a
                            href="{{ route('catalogo.index', ['tab' => 'productos']) }}"
                            class="catalog-tab-btn {{ $tab === 'productos' ? 'is-active' : 'is-inactive' }}"
                        >
                            Productos
                        </a>
                        <a
                            href="{{ route('catalogo.index', ['tab' => 'categorias']) }}"
                            class="catalog-tab-btn {{ $tab === 'categorias' ? 'is-active' : 'is-inactive' }}"
                        >
                            Categorias
                        </a>
                    </div>
                </div>
            </div>

            @if ($tab === 'productos')
                <div class="catalog-grid">
                    <section class="catalog-card">
                        <div class="catalog-card-body">
                            <h2 class="catalog-title">Productos</h2>
                            <p class="catalog-note">Lista operativa para buscar, editar y abrir variantes.</p>

                            <div class="catalog-search-row">
                                <div class="catalog-search-field">
                                    <label for="q">Buscar producto</label>
                                    <div class="catalog-search-shell">
                                        <span class="catalog-search-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="7"></circle>
                                                <path d="m20 20-3.5-3.5"></path>
                                            </svg>
                                        </span>
                                        <input
                                            id="q"
                                            name="q"
                                            type="text"
                                            placeholder="Buscar por nombre..."
                                            value="{{ $filters['q'] }}"
                                            hx-get="{{ route('catalogo.productos.table') }}"
                                            hx-trigger="keyup changed delay:300ms"
                                            hx-target="#productos_lista"
                                            hx-include="#q,#selected_product_id"
                                        >
                                    </div>
                                </div>

                                <a
                                    href="javascript:void(0)"
                                    class="catalog-btn"
                                    hx-get="{{ route('catalogo.productos.create') }}"
                                    hx-target="#catalog_form_modal_body"
                                    hx-swap="outerHTML"
                                    hx-include="#q,#selected_product_id"
                                    onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-open'))"
                                >
                                    Nuevo producto
                                </a>
                            </div>
                        </div>

                        <div class="catalog-card-body" id="productos_lista">
                            @include('catalogo.partials.products-table', [
                                'productos' => $productos,
                                'selectedProductId' => $selectedProduct?->id,
                            ])
                        </div>
                    </section>

                    <section class="catalog-card">
                        <div class="catalog-card-body">
                            <h2 class="catalog-title">Variantes</h2>
                            <p class="catalog-note">Panel operativo con detalle y acciones del producto seleccionado.</p>
                        </div>

                        <div class="catalog-card-body" id="variantes_panel">
                            @if ($selectedProduct)
                                @include('catalogo.partials.variants-panel', [
                                    'selectedProduct' => $selectedProduct,
                                    'selectedVariants' => $selectedVariants,
                                ])
                            @else
                                <p class="catalog-empty">Sin producto seleccionado.</p>
                            @endif
                        </div>
                    </section>
                </div>
            @else
                <div class="catalog-categories-grid">
                    <section class="catalog-card" id="categoria-form">
                        <div class="catalog-card-body">
                            <h2 class="catalog-title">
                                {{ $editingCategory ? 'Editar categoria' : 'Nueva categoria' }}
                            </h2>
                            <p class="catalog-note">Mismo tono visual del POS para altas, cambios y estado.</p>

                            @if ($errors->any())
                                <div class="catalog-status-callout">
                                    Revisa los datos cargados antes de guardar.
                                </div>
                            @endif

                            <form
                                method="POST"
                                action="{{ $editingCategory ? route('catalogo.categorias.update', $editingCategory) : route('catalogo.categorias.store') }}"
                                class="catalog-form-stack"
                                style="margin-top: 18px;"
                            >
                                @csrf
                                @if ($editingCategory)
                                    @method('PUT')
                                @endif
                                <input type="hidden" name="activa" value="0">

                                <div>
                                    <label for="nombre">Nombre</label>
                                    <input
                                        id="nombre"
                                        name="nombre"
                                        type="text"
                                        value="{{ old('nombre', $editingCategory?->nombre) }}"
                                        required
                                        autofocus
                                    >
                                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                                </div>

                                <label class="catalog-check">
                                    <input
                                        type="checkbox"
                                        name="activa"
                                        value="1"
                                        class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
                                        @checked(old('activa', $editingCategory?->activa ?? true))
                                    >
                                    <span>Categoria activa</span>
                                </label>

                                <div class="catalog-form-actions">
                                    <button type="submit" class="catalog-btn">
                                        {{ $editingCategory ? 'Guardar cambios' : 'Crear categoria' }}
                                    </button>

                                    @if ($editingCategory)
                                        <a
                                            href="{{ route('catalogo.index', ['tab' => 'categorias']) }}"
                                            class="catalog-btn catalog-btn-secondary"
                                        >
                                            Cancelar
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="catalog-card">
                        <div class="catalog-card-body">
                            <h2 class="catalog-title">Categorias</h2>
                            <p class="catalog-note">Gestiona estado y edicion sin salir del panel.</p>
                        </div>

                        <div class="catalog-card-body">
                            <div class="catalog-table-wrap">
                                <table class="catalog-table catalog-stack-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Productos</th>
                                            <th>Estado</th>
                                            <th class="is-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categorias as $categoria)
                                            <tr>
                                                <td data-label="Nombre"><strong>{{ $categoria->nombre }}</strong></td>
                                                <td data-label="Productos">{{ $categoria->productos_count }}</td>
                                                <td data-label="Estado">
                                                    <span class="catalog-badge {{ $categoria->activa ? 'catalog-badge-success' : 'catalog-badge-danger' }}">
                                                        {{ $categoria->activa ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </td>
                                                <td data-label="Acciones" class="is-right">
                                                    <div class="catalog-list-actions" style="justify-content: flex-end;">
                                                        <a
                                                            href="{{ route('catalogo.index', ['tab' => 'categorias', 'edit_categoria' => $categoria->id]) }}"
                                                            class="catalog-icon-btn"
                                                            title="Editar"
                                                        >
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 20h9"/>
                                                                <path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                                                            </svg>
                                                        </a>

                                                        <form method="POST" action="{{ route('catalogo.categorias.toggle', $categoria) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="catalog-icon-btn" title="Activar o desactivar">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M12 2v10"/>
                                                                    <path d="M17.66 6.34a8 8 0 1 1-11.32 0"/>
                                                                </svg>
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="{{ route('catalogo.categorias.destroy', $categoria) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="catalog-icon-btn delete"
                                                                title="Eliminar"
                                                                onclick="return confirm('Se eliminara la categoria {{ addslashes($categoria->nombre) }}. Continuar?')"
                                                            >
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M3 6h18"/>
                                                                    <path d="M8 6V4h8v2"/>
                                                                    <path d="M19 6l-1 14H6L5 6"/>
                                                                    <path d="M10 11v6"/>
                                                                    <path d="M14 11v6"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="catalog-empty">
                                                    No hay categorias cargadas.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>

    <div
        x-data="{ open: false }"
        x-on:catalog-stock-modal-open.window="open = true"
        x-on:catalog-stock-modal-close.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        x-show="open"
        x-cloak
        class="catalog-modal"
    >
        <div class="catalog-modal-backdrop" x-on:click="open = false"></div>

        <div class="catalog-modal-dialog">
            <div id="catalog_stock_modal_body" class="catalog-modal-card">
                <div class="catalog-modal-head">
                    <div class="catalog-modal-copy">
                        <h3 class="catalog-modal-title">Gestion de Stock</h3>
                    </div>

                    <button type="button" class="catalog-modal-close" x-on:click="open = false" aria-label="Cerrar modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18"/>
                            <path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="catalog-modal-body">
                    <div class="catalog-empty">Selecciona un stock total para editarlo desde este modal.</div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: false }"
        x-on:catalog-form-modal-open.window="open = true"
        x-on:catalog-form-modal-close.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        x-show="open"
        x-cloak
        class="catalog-modal"
    >
        <div class="catalog-modal-backdrop" x-on:click="open = false"></div>

        <div class="catalog-modal-dialog">
            <div id="catalog_form_modal_body" class="catalog-modal-card">
                <div class="catalog-modal-head">
                    <div class="catalog-modal-copy">
                        <h3 class="catalog-modal-title">Editar</h3>
                    </div>

                    <button type="button" class="catalog-modal-close" x-on:click="open = false" aria-label="Cerrar modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18"/>
                            <path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="catalog-modal-body">
                    <div class="catalog-empty">Selecciona editar producto o editar variante para trabajar desde este modal.</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
