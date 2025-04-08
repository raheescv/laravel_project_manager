<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-scheme="navy">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta name="description" content="Nifty is a responsive admin dashboard template based on Bootstrap 5 framework. There are a lot of useful components.">
    <title>{{ config('app.name', 'Astra') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-settings.min.css') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    {{-- <link rel="manifest" href="{{ asset('site.webmanifest') }}"> --}}
    <link rel="stylesheet" href="{{ asset('assets/vendors/tom-select/tom-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/toaster/toastr.min.css') }}">
    @livewireStyles
    <style>
        .pointer {
            cursor: pointer;
        }

        .number {
            text-align: right;
        }

        table thead th a {
            text-decoration: none;
            color: black;
        }

        .input-xs {
            padding: 1px 0px;
            font-size: 0.75rem;
            line-height: 1.5;
            height: 1.5rem;
        }

        .transparent_border_input {
            border: none;
            outline: none;
            background: #f0f0f0;
        }

        .parent-container {
            width: 100%;
        }

        .item-icon {
            width: 10%
        }
    </style>
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    @stack('styles')
</head>

<body class="out-quart">
    <div id="root" class="root mn--max tm--expanded-hd">
        <section id="content" class="content">
            {{ $slot }}
            @include('layouts.footer')
        </section>
        @include('layouts.header')
        @include('layouts.navigation')
        @include('layouts.sidebar')
    </div>
    <!-- SCROLL TO TOP BUTTON -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div class="scroll-container">
        <a href="#root" class="scroll-page ratio ratio-1x1" aria-label="Scroll button"></a>
    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - SCROLL TO TOP BUTTON -->

    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/nifty.js') }}"></script>
    <script src="{{ asset('assets/js/demo-purpose-only.js') }}"></script>
    <script src="{{ asset('assets/vendors/toaster/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/tom-select/tom-select.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/sweetalert/sweetalert2.js') }}"></script>

    <script>
        var eventHandler = function(name) {
            return function() {
                console.log(name, arguments);
            };
        };
        $('.tomSelect').each(function() {
            new TomSelect(this, {
                // onChange: eventHandler('onChange'),
                // onItemAdd: eventHandler('onItemAdd'),
                // onItemRemove: eventHandler('onItemRemove'),
                // onOptionAdd: eventHandler('onOptionAdd'),
                // onOptionRemove: eventHandler('onOptionRemove'),
                // onDropdownOpen: eventHandler('onDropdownOpen'),
                // onDropdownClose: eventHandler('onDropdownClose'),
                // onFocus: eventHandler('onFocus'),
                // onBlur: eventHandler('onBlur'),
                // onInitialize: eventHandler('onInitialize'),
                plugins: ['remove_button'],
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        });
    </script>
    @livewireScripts
    @stack('scripts')
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };
        $(document).on('focus', '.select_on_focus', function() {
            $(this).select();
        });
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @elseif (session('error'))
            toastr.error("{{ session('error') }}");
        @elseif (session('info'))
            toastr.info("{{ session('info') }}");
        @elseif (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            document.getElementById('btnFullscreen').addEventListener('click', function() {
                toggleFullscreen();
            });

            function toggleFullscreen(elem) {
                elem = elem || document.documentElement;
                if (!document.fullscreenElement && !document.mozFullScreenElement &&
                    !document.webkitFullscreenElement && !document.msFullscreenElement) {
                    if (elem.requestFullscreen) {
                        elem.requestFullscreen();
                    } else if (elem.msRequestFullscreen) {
                        elem.msRequestFullscreen();
                    } else if (elem.mozRequestFullScreen) {
                        elem.mozRequestFullScreen();
                    } else if (elem.webkitRequestFullscreen) {
                        elem.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    }
                }
            }
            window.addEventListener('success', event => {
                if (typeof(event.detail[0].title) != "undefined" && typeof(event.detail[0].message) != "undefined") {
                    toastr.info(event.detail[0].message, event.detail[0].title);
                    return false;
                }
                if (typeof(event.detail[0].title) != "undefined") {
                    toastr.info(event.detail[0].title);
                    return false;
                }
                if (typeof(event.detail[0].message) != "undefined") {
                    toastr.info(event.detail[0].message);
                    return false;
                }
            });
            window.addEventListener('warning', event => {
                if (typeof(event.detail[0].title) != "undefined" && typeof(event.detail[0].message) != "undefined") {
                    toastr.warning(event.detail[0].message, event.detail[0].title);
                    return false;
                }
                if (typeof(event.detail[0].title) != "undefined") {
                    toastr.warning(event.detail[0].title);
                    return false;
                }
                if (typeof(event.detail[0].message) != "undefined") {
                    toastr.warning(event.detail[0].message);
                    return false;
                }
            });
            window.addEventListener('error', event => {
                if (typeof(event.detail) != "undefined") {
                    toastr.error(event.detail[0].message)
                }
                if (typeof(event.error) != "undefined") {
                    toastr.error(event.error.message)
                }
            });
        });
    </script>
    <script>
        // $("#root").setClass("root mn--max tm--expanded-hd");
    </script>
</body>

</html>
