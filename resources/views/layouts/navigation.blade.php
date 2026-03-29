@php
    $user = Auth::user();
    $userName = $user?->nombre_completo ?: ($user?->username ?: ($user?->name ?: 'Usuario'));
    $userEmail = $user?->email ?? '';
    $canViewAdminPanel = $user
        ? ($user->can('admin_panel.view_usuarioperfil')
            || $user->can('admin_panel.manage_users')
            || $user->can('admin_panel.view_reportes'))
        : false;
@endphp

<style>
    .sys-nav {
        background: linear-gradient(135deg, #3a4652 0%, #465362 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.04),
            0 12px 24px rgba(15, 23, 42, 0.16);
    }

    .sys-nav-shell {
        width: 100%;
        max-width: 80rem;
        margin: 0 auto;
        padding: 0 1.25rem;
    }

    .sys-nav-row {
        min-height: 4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
    }

    .sys-nav-left {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .sys-nav-brand {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        color: #f8fafc;
        text-decoration: none;
    }

    .sys-nav-brand:hover {
        color: #ffffff;
    }

    .sys-nav-brand-mark {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.06) 100%);
        color: #f8fafc;
        padding: 0.3125rem;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 6px 14px rgba(15, 23, 42, 0.12);
    }

    .sys-nav-brand-copy {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .sys-nav-brand-name {
        font-size: 0.9375rem;
        font-weight: 900;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        line-height: 1;
    }

    .sys-nav-brand-caption {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        line-height: 1;
        color: rgba(226, 232, 240, 0.7);
    }

    .sys-nav-links {
        min-width: 0;
        display: none;
        align-items: center;
        gap: 0.875rem;
    }

    .sys-nav-link {
        height: 4rem;
        display: inline-flex;
        align-items: center;
        border-bottom: 3px solid transparent;
        color: rgba(226, 232, 240, 0.74);
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        transition: color 150ms ease, border-color 150ms ease;
    }

    .sys-nav-link:hover,
    .sys-nav-link:focus-visible {
        color: #f8fafc;
        border-bottom-color: rgba(255, 255, 255, 0.28);
        outline: none;
    }

    .sys-nav-link.is-active {
        color: #ffffff;
        border-bottom-color: #f8fafc;
    }

    .sys-nav-right {
        display: none;
        align-items: center;
        gap: 0.75rem;
        margin-left: auto;
    }

    .sys-nav-user-btn {
        min-height: 2.25rem;
        padding: 0 0 0 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
    }

    .sys-nav-user-btn:hover,
    .sys-nav-user-btn:focus-visible {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        outline: none;
    }

    .sys-nav-user-btn svg {
        width: 1rem;
        height: 1rem;
    }

    .sys-nav-hamburger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        background: transparent;
        color: #f8fafc;
        transition: background 150ms ease;
    }

    .sys-nav-hamburger:hover,
    .sys-nav-hamburger:focus-visible {
        background: rgba(255, 255, 255, 0.08);
        outline: none;
    }

    .sys-nav-mobile {
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(58, 70, 82, 0.98) 0%, rgba(70, 83, 98, 0.98) 100%);
    }

    .sys-nav-mobile-links {
        padding: 0.5rem 0;
    }

    .sys-nav-mobile-link {
        display: block;
        width: 100%;
        padding: 0.75rem 0.25rem;
        border-left: 3px solid transparent;
        color: rgba(226, 232, 240, 0.78);
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        transition: color 150ms ease, border-color 150ms ease, background 150ms ease;
    }

    .sys-nav-mobile-link:hover,
    .sys-nav-mobile-link:focus-visible {
        color: #ffffff;
        border-left-color: rgba(255, 255, 255, 0.28);
        background: rgba(255, 255, 255, 0.04);
        outline: none;
    }

    .sys-nav-mobile-link.is-active {
        color: #ffffff;
        border-left-color: #f8fafc;
    }

    .sys-nav-mobile-user {
        padding: 1rem 0 0.875rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sys-nav-mobile-user-name {
        color: #f8fafc;
        font-size: 0.9375rem;
        font-weight: 800;
    }

    .sys-nav-mobile-user-email {
        margin-top: 0.25rem;
        color: rgba(226, 232, 240, 0.72);
        font-size: 0.8125rem;
    }

    .sys-nav-dropdown-panel {
        border-radius: 0.875rem;
        border: 1px solid #c4d0dd;
        background: #ffffff;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        padding: 0.375rem;
    }

    .sys-nav-dropdown-link {
        display: flex;
        align-items: center;
        min-height: 2.5rem;
        border-radius: 0.625rem;
        color: #1f2937;
        font-size: 0.8125rem;
        font-weight: 700;
        text-decoration: none;
    }

    .sys-nav-dropdown-link:hover,
    .sys-nav-dropdown-link:focus-visible {
        background: #f1f6fa;
        outline: none;
    }

    @media (min-width: 640px) {
        .sys-nav-links,
        .sys-nav-right {
            display: flex;
        }

        .sys-nav-hamburger {
            display: none;
        }
    }
</style>

<nav x-data="{ open: false }" class="sys-nav">
    <div class="sys-nav-shell">
        <div class="sys-nav-row">
            <div class="sys-nav-left">
                <a href="{{ route('dashboard') }}" class="sys-nav-brand">
                    <span class="sys-nav-brand-mark">
                        <x-application-logo class="block h-full w-full fill-current" />
                    </span>
                    <span class="sys-nav-brand-copy">
                        <span class="sys-nav-brand-name">{{ config('app.name', 'VGC') }}</span>
                        <span class="sys-nav-brand-caption">Sistema</span>
                    </span>
                </a>

                <div class="sys-nav-links">
                    <a href="{{ route('dashboard') }}" @class(['sys-nav-link', 'is-active' => request()->routeIs('dashboard')])>Dashboard</a>
                    <a href="{{ route('catalogo.index') }}" @class(['sys-nav-link', 'is-active' => request()->routeIs('catalogo.*')])>Catalogo</a>
                    <a href="{{ route('caja.pos') }}" @class(['sys-nav-link', 'is-active' => request()->routeIs('caja.*')])>Caja</a>
                    <a href="{{ route('cuentas-corrientes.index') }}" @class(['sys-nav-link', 'is-active' => request()->routeIs('cuentas-corrientes.*')])>Cuentas Corrientes</a>
                    @if ($canViewAdminPanel)
                        <a href="{{ route('admin-panel.dashboard') }}" @class(['sys-nav-link', 'is-active' => request()->routeIs('admin-panel.*')])>Admin</a>
                    @endif
                </div>
            </div>

            <div class="sys-nav-right">
                <x-dropdown align="right" width="48" contentClasses="py-1 bg-white sys-nav-dropdown-panel">
                    <x-slot name="trigger">
                        <button class="sys-nav-user-btn">
                            <span>{{ $userName }}</span>
                            <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" class="sys-nav-dropdown-link">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                class="sys-nav-dropdown-link"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Salir') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <button @click="open = ! open" class="sys-nav-hamburger sm:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div x-cloak :class="{'block': open, 'hidden': ! open}" class="sys-nav-mobile hidden sm:hidden">
        <div class="sys-nav-shell">
            <div class="sys-nav-mobile-links">
                <a href="{{ route('dashboard') }}" @class(['sys-nav-mobile-link', 'is-active' => request()->routeIs('dashboard')])>Dashboard</a>
                <a href="{{ route('catalogo.index') }}" @class(['sys-nav-mobile-link', 'is-active' => request()->routeIs('catalogo.*')])>Catalogo</a>
                <a href="{{ route('caja.pos') }}" @class(['sys-nav-mobile-link', 'is-active' => request()->routeIs('caja.*')])>Caja</a>
                <a href="{{ route('cuentas-corrientes.index') }}" @class(['sys-nav-mobile-link', 'is-active' => request()->routeIs('cuentas-corrientes.*')])>Cuentas Corrientes</a>
                @if ($canViewAdminPanel)
                    <a href="{{ route('admin-panel.dashboard') }}" @class(['sys-nav-mobile-link', 'is-active' => request()->routeIs('admin-panel.*')])>Admin</a>
                @endif
            </div>

            <div class="sys-nav-mobile-user">
                <div class="sys-nav-mobile-user-name">{{ $userName }}</div>
                @if ($userEmail !== '')
                    <div class="sys-nav-mobile-user-email">{{ $userEmail }}</div>
                @endif

                <div class="mt-4 space-y-1">
                    <a href="{{ route('profile.edit') }}" class="sys-nav-mobile-link">Perfil</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sys-nav-mobile-link text-left">Salir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
