<nav id="mainnav-container" class="mainnav">
    <style>
        /* Colorful FontAwesome Icons Styles */
        .nav-link .fa-dashboard {
            color: #2196F3;
        }

        /* Blue for Dashboard */
        .nav-link .fa-cubes {
            color: #FF9800;
        }

        /* Orange for Inventory */
        .nav-link .fa-calendar {
            color: #4CAF50;
        }

        /* Green for Appointments */
        .nav-link .fa-shopping-cart {
            color: #E91E63;
        }

        /* Pink for Sale */
        .nav-link .fa-rotate-left {
            color: #9C27B0;
        }

        /* Purple for Sale Return */
        .nav-link .fa-cart-plus {
            color: #00BCD4;
        }

        /* Cyan for Purchase */
        .nav-link .fa-reply {
            color: #FF5722;
        }

        /* Deep Orange for Purchase Return */
        .nav-link .fa-bank {
            color: #8BC34A;
        }

        /* Light Green for Account */
        .nav-link .fa-users {
            color: #FFC107;
        }

        /* Amber for Employee */
        .nav-link .fa-user {
            color: #607D8B;
        }

        /* Blue Grey for Users */
        .nav-link .fa-cog {
            color: #795548;
        }

        /* Brown for Settings */
        .nav-link .fa-clipboard {
            color: #3F51B5;
        }

        .nav-link .fa-building {
            color: #009688;
        }

        /* Teal for Tenants */

        /* Indigo for Log */
        .nav-link .fa-sign-out {
            color: #F44336;
        }

        /* Red for Logout */
        .nav-link .fa-chart-line {
            color: #17a2b8;
        }

        /* Teal for FlatTrade */
        .nav-link .fa-gift {
            color: #FF6B6B;
        }

        /* Coral for Package */
        .nav-link .fa-cut {
            color: #9C27B0;
        }

        /* Purple for Tailoring */
        .nav-link .fa-exchange {
            color: #4f46e5;
        }

        /* Indigo for Issue */
        .nav-link .fa-building-o {
            color: #26A69A;
        }

        /* Teal for Property */
        .nav-link .fa-home {
            color: #5C6BC0;
        }

        /* Indigo for Rent Out */
        .nav-link .fa-hand-o-right {
            color: #EF5350;
        }

        /* Red for Sales */

        /* Hover effects for icons */
        .nav-link:hover .fa {
            transform: scale(1.1);
            transition: all 0.3s ease;
            text-shadow: 0 0 8px currentColor;
        }

        /* Active state glow effect */
        .nav-link.active .fa {
            filter: drop-shadow(0 0 3px currentColor);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
    <div class="mainnav__inner">
        <div class="pb-5 mainnav__top-content scrollable-content">
            <div id="_dm-mainnavProfile" class="my-3 mainnav__widget hv-outline-parent">
                <div class="py-2 text-center mininav-toggle">
                    <img class="mainnav__avatar img-md rounded-circle hv-oc" src="{{ secure_asset('assets/img/profile-photos/1.png') }}"
                        alt="Profile Picture">
                </div>
                <div class="mininav-content collapse d-mn-max">
                    <span data-popper-arrow class="arrow"></span>
                    <div class="d-grid">
                        <button class="p-2 border-0 mainnav-widget-toggle d-block btn" data-bs-toggle="collapse" data-bs-target="#usernav"
                            aria-expanded="false" aria-controls="usernav">
                            <span class="dropdown-toggle d-flex justify-content-center align-items-center">
                                <h5 class="mb-0 me-3">{{ auth()->user()->name }} </h5>
                            </span>
                            <small class="text-body-secondary">{{ getUserRoles(auth()->user()) }}</small>
                            <p><small class="text-body-secondary">{{ auth()->user()->branch?->name }}</small></p>

                        </button>
                        <div id="usernav" class="nav flex-column collapse">
                            <a href="{{ route('profile.edit') }}" class="nav-link">
                                <i class="fa fa-user fs-5 me-2"></i>
                                <span class="ms-1">Profile</span>
                            </a>
                            <a href="{{ route('settings::index') }}" class="nav-link">
                                <i class="fa fa-cog fs-5 me-2"></i>
                                <span class="ms-1">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dynamic navigation: ordered and filtered by user preferences --}}
            @php
                $navItems = \App\Services\NavigationService::getNavigationItems();
            @endphp
            <ul class="mainnav__menu nav flex-column">
                @foreach ($navItems as $navItem)
                    @if ($navItem['visible'] ?? true)
                        @include('layouts.nav.sections.' . $navItem['id'])
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="pb-2 mainnav__bottom-content border-top">
            <ul id="mainnav" class="mainnav__menu nav flex-column">
                <li class="nav-item has-sub">
                    <a href="#" class="nav-link mininav-toggle collapsed" aria-expanded="false">
                        <i class="fa fa-sign-out fs-5 me-2"></i>
                        <span class="nav-label mininav-content ms-1 collapse show" style="">Logout</span>
                    </a>
                    <ul class="mininav-content nav flex-column collapse">
                        <li data-popper-arrow class="arrow"></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
