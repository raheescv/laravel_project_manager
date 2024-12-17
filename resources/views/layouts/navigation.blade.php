<nav id="mainnav-container" class="mainnav">
    <div class="mainnav__inner">
        <div class="mainnav__top-content scrollable-content pb-5">
            <div id="_dm-mainnavProfile" class="mainnav__widget my-3 hv-outline-parent">
                <div class="mininav-toggle text-center py-2">
                    <img class="mainnav__avatar img-md rounded-circle hv-oc" src="{{ asset('assets/img/profile-photos/1.png') }}" alt="Profile Picture">
                </div>

                <div class="mininav-content collapse d-mn-max">
                    <span data-popper-arrow class="arrow"></span>
                    <div class="d-grid">
                        <button class="mainnav-widget-toggle d-block btn border-0 p-2" data-bs-toggle="collapse" data-bs-target="#usernav" aria-expanded="false" aria-controls="usernav">
                            <span class="dropdown-toggle d-flex justify-content-center align-items-center">
                                <h5 class="mb-0 me-3">{{ auth()->user()->name }}</h5>
                            </span>
                            <small class="text-body-secondary">Administrator</small>
                        </button>
                        <div id="usernav" class="nav flex-column collapse">
                            <a href="{{ route('profile.edit') }}" class="nav-link">
                                <i class="demo-pli-male fs-5 me-2"></i>
                                <span class="ms-1">Profile</span>
                            </a>
                            <a href="{{ route('profile.edit') }}" class="nav-link">
                                <i class="demo-pli-gear fs-5 me-2"></i>
                                <span class="ms-1">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="mainnav__menu nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link mininav-toggle {{ request()->is(['/', 'dashboard']) ? 'active' : '' }}">
                        <i class="demo-pli-home fs-5 me-2"></i>
                        <span class="nav-label mininav-content ms-1 collapse show" style="">
                            Dashboard
                        </span>
                    </a>
                </li>
            </ul>
            <div class="mainnav__categoriy py-3">
                <h6 class="mainnav__caption mt-0 fw-bold">Pages</h6>
                <ul class="mainnav__menu nav flex-column">
                    <li class="nav-item has-sub">
                        <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-boot-2 fs-5 me-2"></i>
                            <span class="nav-label ms-1">Ui Elements</span>
                        </a>
                        <ul class="mininav-content nav collapse">
                            <li data-popper-arrow class="arrow"></li>
                            <li class="nav-item">
                                <a href="{{ route('category::index') }}" class="nav-link">Category</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!-- End - Navigation menu -->
        <div class="mainnav__bottom-content border-top pb-2">
            <ul id="mainnav" class="mainnav__menu nav flex-column">
                <li class="nav-item has-sub">
                    <a href="#" class="nav-link mininav-toggle collapsed" aria-expanded="false">
                        <i class="demo-pli-unlock fs-5 me-2"></i>
                        <span class="nav-label ms-1">Logout</span>
                    </a>
                    <ul class="mininav-content nav flex-column collapse">
                        <li data-popper-arrow class="arrow"></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Lock screen</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
