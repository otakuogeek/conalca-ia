<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/app.css')
    @livewireStyles
    
    <!-- Preload critical fonts -->
    <link rel="preload" href="{{ asset('build/assets/ProductSansBold-8dbeee80.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('build/assets/ProductSansRegular-b34cbb71.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('static/Montserrat-Thin.ttf') }}" as="font" type="font/ttf" crossorigin>
    
    <!-- Google Fonts - Product Sans with optimizations -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* This example part of kwd-dashboard see https://kamona-wd.github.io/kwd-dashboard/ */
        /* So here we will write some classes to simulate dark mode and some of tailwind css config in our project */
        
        /* Product Sans font family with font-display optimization */
        * {
            font-family: 'Product Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-display: swap;
        }
        
        /* Local font faces for better performance */
        @font-face {
            font-family: 'Product Sans Bold';
            src: url('{{ asset('build/assets/ProductSansBold-8dbeee80.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans Regular';
            src: url('{{ asset('build/assets/ProductSansRegular-b34cbb71.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Montserrat Thin';
            src: url('{{ asset('static/Montserrat-Thin.ttf') }}') format('truetype');
            font-weight: 100;
            font-style: normal;
            font-display: swap;
        }
        
        :root {
            --light: #202020;
            /* --dark: #152e4d;
            --darker: #12263f; */
            --dark: #fff;
            --darker: #fff;

            --color-red: #dc2626;
            --color-green: #16a34a;
            --color-blue: #2563eb;
            --color-cyan: #0891b2;
            --color-teal: #0d9488;
            --color-fuchsia: #c026d3;
            --color-orange: #ea580c;
            --color-yellow: #ca8a04;
            --color-violet: #7c3aed;
        }

        [x-cloak] {
            display: none;
        }

        .dark .dark\:text-light {
            color: var(--light);
        }

        .dark .dark\:bg-dark {
            background-color: var(--dark);
        }

        .dark .dark\:bg-darker {
            background-color: var(--darker);
        }

        .dark .dark\:text-gray-300 {
            color: #D1D5DB;
        }

        .dark .dark\:text-blue-500 {
            color: #3B82F6;
        }

        .dark .dark\:text-blue-100 {
            color: #DBEAFE;
        }

        .dark .dark\:hover\:text-light:hover {
            color: var(--light);
        }

        .dark .dark\:border-blue-800 {
            /* border-color: #1e40af; */
            border-color: #fff;
        }

        .dark .dark\:border-blue-700 {
            /* border-color: #1D4ED8; */
            border-color: #fff;
        }

        .dark .dark\:bg-blue-600 {
            background-color: #2563eb;
        }

        .dark .dark\:hover\:bg-blue-600:hover {
            background-color: #2563eb;
        }

        .hover\:overflow-y-auto:hover {
            overflow-y: auto;
        }

        /* Sidebar toggle styles */
        .sidebar-collapsed {
            width: 5rem;
        }

        .sidebar-expanded {
            width: 16rem;
        }

        .sidebar-transition {
            transition: width 0.3s ease-in-out;
        }

        .sidebar-text {
            opacity: 1;
            transition: opacity 0.3s ease-in-out;
        }

        .sidebar-text-hidden {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        /* Dropdown menu styles */
        .dropdown-icon {
            transition: transform 0.2s ease-in-out;
        }

        .dropdown-icon.rotate-180 {
            transform: rotate(180deg);
        }

        /* Submenu styles */
        .submenu-item {
            border-left: 2px solid transparent;
            transition: border-color 0.2s ease-in-out;
        }

        .submenu-item:hover {
            border-left-color: #FF7C32;
        }

        .sidebar-logo {
            transition: transform 0.3s ease-in-out;
        }

        .sidebar-logo-collapsed {
            transform: scale(0.8);
        }

        /* Tooltip styles for collapsed sidebar */
        .group:hover [title]:not([title=""]) {
            position: relative;
        }

        .group:hover [title]:not([title=""]):before {
            content: attr(title);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #374151;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            z-index: 1000;
            margin-left: 10px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .group:hover [title]:not([title=""]):after {
            content: "";
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 4px;
            border: 5px solid transparent;
            border-right-color: #374151;
        }

        /* Hide tooltips when sidebar is expanded */
        .sidebar-expanded .group:hover [title]:before,
        .sidebar-expanded .group:hover [title]:after {
            display: none;
        }
    </style>

    <title>@yield('title') - CONALCA AI</title>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const buttons = document.querySelectorAll('[data-button]');
        const currentPath = window.location.pathname.replace(/\/$/, '');

        const resetAll = () => {
            buttons.forEach(btn => {
                btn.classList.remove("bg-[#FBEBE2]", "dark:bg-[#FBEBE2]", "text-[#ff7c32]", "dark:text-[#ff7c32]");
                const svg = btn.querySelector('span svg');
                if (svg) svg.style.fill = '';
            });
        };

        const highlightCurrent = () => {
            resetAll();
            buttons.forEach(btn => {
                const href = btn.getAttribute('href');
                if (!href) return;
                // Normalize href to compare only the path
                const normalizedHref = href.replace(window.location.origin, '').replace(/\/$/, '');
                if (currentPath === normalizedHref || currentPath.startsWith(normalizedHref)) {
                    btn.classList.add("bg-[#FBEBE2]", "dark:bg-[#FBEBE2]", "text-[#ff7c32]", "dark:text-[#ff7c32]");
                    const svg = btn.querySelector('span svg');
                    if (svg) svg.style.fill = '#ff7c32';
                }
            });
        };

        // Initial highlight based on URL
        highlightCurrent();

        // Optional: keep click style for the current tab only (no persistence)
        buttons.forEach(btn => {
            btn.addEventListener("click", () => {
                highlightCurrent();
            });
        });
    });
    </script>
    @stack('styles')
    @stack('scripts')
</head>

<body>
    <!-- component -->
    <div x-data="setup()" x-init="$refs.loading.classList.add('hidden');" :class="{ 'dark': isDark }">
        <!--  -->
        <div class="flex h-screen antialiased text-gray-900 bg-gray-100 dark:bg-dark dark:text-light">
            <!-- Loading screen -->
            <div x-ref="loading"
                class="fixed inset-0 z-50 flex items-center justify-center text-2xl font-semibold text-white bg-opacity-90 bg-[#FBEBE2]">
                Loading.....
            </div>

            <!-- Sidebar -->
            <aside :class="sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'" 
                   class="flex-shrink-0 hidden bg-white dark:bg-darker md:block sidebar-transition">
                <div class="flex flex-col h-full">
                    <!-- Sidebar header with toggle button -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <a href="{{ route('dashboard.show') }}" x-show="!sidebarCollapsed">
                            <h1 class="text-[#2E2C34] text-[1.32rem] font-bold leading-normal uppercase sidebar-text"
                                :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'">
                                Conalca AI
                            </h1>
                        </a>
                        <a href="{{ route('dashboard.show') }}" x-show="sidebarCollapsed" class="flex justify-center">
                            <img src="{{ asset('img/conalca_logo.jpg') }}" alt="Conalca Logo" width="80" height="80" class="sidebar-logo sidebar-logo-collapsed">
                        </a>
                       
                    </div>
                    
                    <!-- Sidebar links -->
                    <nav aria-label="Main" class="flex-1 px-2 py-4 space-y-2 overflow-y-hidden hover:overflow-y-auto">
                       
                        <div>
                            <a href="{{ route('dashboard.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="dashboard"
                                :title="sidebarCollapsed ? 'Tablero' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Tablero </span>
                            </a>
                        </div>
                        {{-- Solicitudes --}}
                        @if(auth()->user()->hasAnyRole(['PRICING','SUPER ADMIN','ASISTENTE COMERCIAL','GERENTE DE CUENTA','SAC','JEFE COMERCIAL']))
                        <div>
                            <a href="{{ route('requests.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="request"
                                :title="sidebarCollapsed ? 'Solicitudes' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M12 7V3H2V21H22V7H12ZM6 19H4V17H6V19ZM6 15H4V13H6V15ZM6 11H4V9H6V11ZM6 7H4V5H6V7ZM10 19H8V17H10V19ZM10 15H8V13H10V15ZM10 11H8V9H10V11ZM10 7H8V5H10V7ZM20 19H12V17H14V15H12V13H14V11H12V9H20V19ZM18 11H16V13H18V11ZM18 15H16V17H18V15Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Solicitudes </span>
                            </a>
                        </div>
                        @endif
                        {{-- cotizaciones --}}
                        <div>
                            <a href="{{ route('quotes.react-test') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="quotes"
                                :title="sidebarCollapsed ? 'Cotizaciones' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M20 6H16V4L14 2H10L8 4V6H4C2.89 6 2.01 6.89 2.01 8L2 19C2 20.11 2.89 21 4 21H20C21.11 21 22 20.11 22 19V8C22 6.89 21.11 6 20 6ZM10 4H14V6H10V4ZM10.5 17.5L7 14L8.41 12.59L10.5 14.68L15.68 9.5L17.09 10.91L10.5 17.5Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Cotizaciones </span>
                            </a>
                        </div>
                     
                        {{-- Clientes --}}
                        <div>
                            <a href="{{ route('contacts.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="contacts"
                                :title="sidebarCollapsed ? 'Clientes' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3H5C3.89 3 3 3.9 3 5ZM15 9C15 10.66 13.66 12 12 12C10.34 12 9 10.66 9 9C9 7.34 10.34 6 12 6C13.66 6 15 7.34 15 9ZM6 17C6 15 10 13.9 12 13.9C14 13.9 18 15 18 17V18H6V17Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Clientes </span>
                            </a>
                        </div>
                        {{-- documentos --}}
                        <div>
                            <a href="{{ route('document.store') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="documents"
                                :title="sidebarCollapsed ? 'Documentos' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M14 2H6C4.9 2 4.01 2.9 4.01 4L4 20C4 21.1 4.89 22 5.99 22H18C19.1 22 20 21.1 20 20V8L14 2ZM16 18H8V16H16V18ZM16 14H8V12H16V14ZM13 9V3.5L18.5 9H13Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Documentos </span>
                            </a>
                        </div>
                        {{-- calendario --}}
                        <div>
                            <a href="{{ route('calendar.show') }}"
                                class="nav-link flex items-center p-2 text-[#898988] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="calendar"
                                :title="sidebarCollapsed ? 'Calendario' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M17 11H7V13H17V11ZM19 4H18V2H16V4H8V2H6V4H5C3.89 4 3.01 4.9 3.01 6L3 20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V9H19V20ZM14 15H7V17H14V15Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Calendario </span>
                            </a>
                        </div>
                        {{-- correo --}}
                        <div>
                            <a href="{{ route('mailbox.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="mail"
                                :title="sidebarCollapsed ? 'Buzón' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Buzón 
                                    <span class="ml-1 bg-[#ff7c32] text-white text-xs font-bold px-2 py-1 rounded-full">
                                        {{ \App\Models\Email::where('status', 'no-read')->where('to_user', auth()->user()->id)->count() }}
                                    </span>
                                </span>
                            </a>
                        </div>
                        
                        {{-- analisis --}}
                        <div>
                            <a href="{{ route('analysis.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32]"
                                role="button" aria-haspopup="true" data-button="analizys">
                                <span aria-hidden="true">
                                    <svg width="19" height="19" viewBox="0 0 19 19" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <rect width="19" height="19" fill="url(#pattern0)"
                                            fill-opacity="0.5" />
                                        <defs>
                                            <pattern id="pattern0" patternContentUnits="objectBoundingBox"
                                                width="1" height="1">
                                                <use xlink:href="#image0_87_510" transform="scale(0.0078125)" />
                                            </pattern>
                                            <image id="image0_87_510" width="128" height="128"
                                                xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAXhSURBVHic7Z1LiBxFGMd/O65GhOhB2Rw0K8IeIviKiBgUDyrEVzDBxIug+ARP8SLqWdSIEQw+DhE8iAn4OBhBXfF10ATRPRnBYDxEQQxqouy6BpON46G2YFx6d7vrXfN9P6jbdNVX8/9193R3zQwooVgLvA0cAfoJ2t/AV8C9wEiC+SlLsAX4hzTBN7XdQC/6LJVGzsfsjbnCt21r7IkqzTxN/vD7wI84nAr0sOHPFbkLmGccGOu6kQrgTz93AQOs7LqBCuDPVO4ClLyMA7Pk/wzQBya6Fq9HAH9+Au7CXAYqgrkUeBP4jYqOAEoZPAz8iwogklDhqwAVEjJ8FaAyQoevAlREjPBVgEqIFb4KUAExw1cBCmcrbuG/Axxq+VoVoFBc9/z3gBXAwZavFyHABPAy8A1wALMM6/qsFS2Nz55/2nwfKsA8m1j8wct2ylsbFyJ8UAEA2AwcZ+k3YCflPODyPewPIl6ANuGXJEHI8EG4AF3CL0GC0OGDYAFcws8pQYzwQagAPuHnkCBW+CBQgBDhp5QgZvggTICQ4aeQIHb4IEiAGOHHlCBF+CBEgC3ACeKEb9tLhLtZFOomTxuGXoAU4YeUIGX4YL570Kb/c1wnlBPX8PcCOxy285UgdfgAL7bo/3vHvrPiE779GtR2h+1dJcgRPphD+7FlxrjHo/8shAjfkkKCXOFbbmdxCZ4P0H9SQoZviSlB7vAta4BXgO8wi0T2ADcH7D8Jrpd6e4Ezl+n7KYd++yx9iZjqUk8Em3Hf85cL3xJSAg0/ICnCt4SQQMMPiM85v2v4luccxutjPhO4hh/6nD8UpNzzF+J6JHBpuuc3kDN8SwoJNPwGSgjfElMCDb+BksK3xJBAw2+gxPAtISXQ8BtwDX8f8cO3hJBAw2+ghvAtPhJo+A3UFL7FRQINv4Eaw7d0kUDDb6Dm8C1tJNDwG8hxezcW21i83rcY4tu7rsuirgQ+p/sbsw+4CZh2HDcmG4FHMXPrAd8CL2Cev/cjjXkZcAPp1vLNAl8DHwEnfTr6lOHY85sYJf4evxLzq6KuVyK+bT9mcYlz8Sc7DlhL+CkYAT4gX/i2/QyscpnAeMeBNPz/s4H84du2w2UCK1h+haqGvzj2M0UJ7ZDrJHa16FzDb+Z98gdv25zrJFYDh5foWMNfnEnyBz/YnJkAvljQ2RxmXd0ZPh0POUUJMOoxkR+Aa4CLMX+WcAxznf+LR59KYnwEsOyfb0qFhBBAicOr+H3hc1uoQpSwtP0MsN5znFafAXL/np6SGRVAOCqAcFQA4agAwlEBhKMCCGdYbgStA64m3dKq3zHPQb5MNF40ahdgFbAbuC7T+B8DdwK/Zhrfm5pPAacDH5IvfDALOiepeMl4zQI8iHkKmZu1wAO5i3ClZgFuy13AABtzF+BKzQKcm7uAAc7LXYArNQtwSu4CBiiplk7ULIASABVAOCqAcGq/EdSGZ4EjjtueDTwSsJbikCDATswKZhcmGHIB9BQgHBVAOCqAcFQA4agAwlEBhKMCCGfwPkAPuBa4HDg10fgzmB+cOpBoPGUBVoAJ4A1M+KnpA68BD2G+Yq4kZBQYAz4j3zPtEeBu4CxgU6YaxNIDHqeMBQ0b8f9GrNKRHmXtdSXVIoIeZez9ltW5C5BGj7KWM5VUiwj0PoBwVADhqADC6bIi6BmPcS4A7vDYXolEFwEe8xhnPSpAkegpQDgqgHBUAOGoAMJRAYSjAghHBRCOCiAcFUA4KoBwVADhqADCUQGEowIIRwUQjgogHBVAOCqAcLosCfP6p+mMHMxdgCOTKQbRI4BwVADhqADCUQGE0wP+yl3EANMdXqt1+zPdA6ZyVzFAl1q0bn+mADbQ8r/mI7c/6Pa/f5cAJwqo+zhwUYe6x4A/C6i7D9xii3oycyGzuP08zP3AXMa654D7HOq+ETPnnO/5EwuLuhXzk20zCYs4DLwOrHF4Ey3rgHeBownrPgrsAa7yqPtCYBfmPUhV9wzwCQN7/n/B9tS80vxdfQAAAABJRU5ErkJggg==" />
                                        </defs>
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Análisis </span>
                            </a>
                        </div>

                        {{-- pricing --}}
                        <div>
                            <a href="{{ route('pricing.show') }}"
                                class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                role="button" aria-haspopup="true" data-button="pricing"
                                :title="sidebarCollapsed ? 'Pricing' : ''">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M20 6H16V4L14 2H10L8 4V6H4C2.89 6 2.01 6.89 2.01 8L2 19C2 20.11 2.89 21 4 21H20C21.11 21 22 20.11 22 19V8C22 6.89 21.11 6 20 6ZM10 4H14V6H10V4ZM10.5 17.5L7 14L8.41 12.59L10.5 14.68L15.68 9.5L17.09 10.91L10.5 17.5Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="ml-2 text-sm sidebar-text" 
                                      :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                      x-show="!sidebarCollapsed"> Pricing </span>
                            </a>
                        </div>

                           {{-- Gestión (Menu con submenús) --}}
                        @if (
                            auth()->user()->hasRole('SUPER ADMIN') ||
                            auth()->user()->hasRole('JEFE COMERCIAL') ||
                            auth()->user()->hasRole('GERENTE DE CUENTA') ||
                            auth()->user()->hasRole('SAC')
                        )
                            <div x-data="{ open: false }">
                                <button @click="open = !open" 
                                    class="nav-link flex items-center justify-between w-full p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                    text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                    role="button" aria-haspopup="true" data-button="management"
                                    :title="sidebarCollapsed ? 'Gestión' : ''">
                                    <div class="flex items-center">
                                        <span aria-hidden="true">
                                            {{-- Ícono de gestión/settings --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <span class="ml-2 text-sm sidebar-text" 
                                              :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                              x-show="!sidebarCollapsed"> Gestión </span>
                                    </div>
                                    <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" 
                                         class="w-4 h-4 transition-transform duration-200" 
                                         :class="{ 'rotate-180': open }" 
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                {{-- Submenús --}}
                                <div x-show="open && !sidebarCollapsed" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 -translate-y-1"
                                     class="ml-6 mt-2 space-y-1">
                                    
                                    {{-- Metas --}}
                                    @if (
                                        auth()->user()->hasRole('SUPER ADMIN') ||
                                        auth()->user()->hasRole('JEFE COMERCIAL')
                                    )
                                        <a href="{{ route('goals.index') }}"
                                            class="submenu-item nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                            text-sm font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                            role="button" aria-haspopup="true" data-button="goals">
                                            <span aria-hidden="true">
                                                {{-- Ícono bullseye (meta) --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="2" fill="currentColor"/>
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Metas </span>
                                        </a>
                                    @endif
                                    
                                    {{-- Novedades & Alertas --}}
                                    @if (
                                        auth()->user()->hasRole('SUPER ADMIN') ||
                                        auth()->user()->hasRole('GERENTE DE CUENTA') ||
                                        auth()->user()->hasRole('SAC')
                                    )
                                        <a href="{{ route('alertsnews.index') }}"
                                            class="submenu-item nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                            text-sm font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                            role="button" aria-haspopup="true" data-button="news&alerts">
                                            <span aria-hidden="true">
                                                {{-- Ícono de notificación --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Novedades & Alertas </span>
                                        </a>
                                    @endif

                                    {{-- Conductores --}}
                                    @if (
                                        auth()->user()->hasRole('SUPER ADMIN') ||
                                        auth()->user()->hasRole('SAC')
                                    )
                                        <a href="{{ route('conductores.index') }}"
                                            class="submenu-item nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                            text-sm font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                            role="button" aria-haspopup="true" data-button="conductores">
                                            <span aria-hidden="true">
                                                {{-- Ícono de conductor/car --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Conductores </span>
                                        </a>
                                    @endif
                                    {{-- Panel de porcentajes --}}
                                    @if (auth()->user()->hasRole('SUPER ADMIN') || auth()->user()->hasRole('PRICING')) 
                                        <a href="{{ route('percentage-settings.index') }}"
                                        class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md hover:bg-[#FBEBE2]"
                                        role="button" aria-haspopup="true">
                                            <span aria-hidden="true">
                                                {{-- Icono porcentaje --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.5 5.5l-13 13M7 7a2 2 0 11-4 0 2 2 0 014 0zm14 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Panel de Porcentajes </span>
                                        </a>
                                    @endif

                                    {{-- Vehículos --}}
                                    @if (
                                        auth()->user()->hasRole('SUPER ADMIN') ||
                                        auth()->user()->hasRole('SAC')
                                    )
                                        <a href="{{ route('vehiculos.index') }}"
                                            class="submenu-item nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                            text-sm font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                            role="button" aria-haspopup="true" data-button="vehiculos">
                                            <span aria-hidden="true">
                                                {{-- Ícono de vehículo/truck --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17h8M8 17a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 104 0 2 2 0 00-4 0zm-8 0H5a2 2 0 01-2-2V9a2 2 0 012-2h2.5M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2h-1.5l-2-3H10a2 2 0 00-2 2v5" />
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Vehículos </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                         {{-- Control de Usuarios (visible only to SUPER ADMIN, JEFE COMERCIAL, GERENTE DE CUENTA) --}}
                        @if (
                            auth()->user()->hasRole('SUPER ADMIN') ||
                            auth()->user()->hasRole('JEFE COMERCIAL') ||
                            auth()->user()->hasRole('GERENTE DE CUENTA')
                        )
                            <div>
                                <a href="{{ route('users.panel') }}"
                                    class="nav-link flex items-center p-2 text-[#898989] transition-colors rounded-md dark:text-[#898989] hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]
                                    text-base font-medium leading-normal hover:text-[#FF7C32] dark:hover:text-[#FF7C32] group"
                                    role="button" aria-haspopup="true" data-button="user-control"
                                    :title="sidebarCollapsed ? 'Control de Usuarios' : ''">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4s-4 1.79-4 4 1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z"
                                                fill="currentColor" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm sidebar-text" 
                                          :class="sidebarCollapsed ? 'sidebar-text-hidden' : 'sidebar-text'"
                                          x-show="!sidebarCollapsed"> Control de Usuarios </span>
                                </a>
                            </div>
                        @endif
                    </nav>

                </div>
            </aside>

            <div class="flex flex-col flex-1 min-h-screen overflow-x-hidden overflow-y-auto">
                <!-- Navbar -->
                <header class="relative bg-white dark:bg-darker">
                    <div class="flex items-center justify-between p-2">
                        <!-- Desktop sidebar toggle button -->
                        <button @click="sidebarCollapsed = !sidebarCollapsed" 
                                class="hidden md:flex p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <div class="flex items-center space-x-2">
                            <!-- Mobile menu button -->
                            <button @click="isMobileMainMenuOpen = !isMobileMainMenuOpen"
                                class="p-1 text-#898989 transition-colors duration-200 rounded-md bg-[#FBEBE2] hover:text-[#FBEBE2] hover:bg-[#FBEBE2] dark:hover:text-light dark:hover:bg-[#FBEBE2] dark:bg-dark md:hidden">
                                <span class="sr-only">Open main manu</span>
                                <span aria-hidden="true">
                                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </span>
                            </button>

                            <!-- Mobile sub menu button -->
                            <button @click="isMobileSubMenuOpen = !isMobileSubMenuOpen"
                                class="p-1 text-[#898989] transition-colors duration-200 rounded-md bg-[#FBEBE2] hover:text-[#898989] hover:bg-[#FBEBE2] dark:hover:text-light dark:hover:bg-[#FBEBE2] dark:bg-dark md:hidden">
                                <span class="sr-only">Open sub manu</span>
                                <span aria-hidden="true">
                                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </span>
                            </button>
                        </div>

                        <!-- Desktop Right buttons -->
                        <nav aria-label="Secondary" class="hidden space-x-2 md:flex md:items-center">
                            <!-- Toggle dark theme button -->
                            {{-- <button aria-hidden="true" class="relative focus:outline-none" x-cloak
                                @click="toggleTheme">
                                <div class="w-12 h-6 transition bg-blue-100 rounded-full outline-none dark:bg-blue-400">
                                </div>
                                <div class="absolute top-0 left-0 inline-flex items-center justify-center w-6 h-6 transition-all duration-150 transform scale-110 rounded-full shadow-sm"
                                    :class="{
                                        'translate-x-0 -translate-y-px  bg-white text-blue-700': !
                                            isDark,
                                        'translate-x-6 text-blue-100 bg-blue-800': isDark
                                    }">
                                    <svg x-show="!isDark" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <svg x-show="isDark" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                            </button> --}}

                            <!-- Search button -->
                            <!-- <button @click="openSearchPanel"
                                class="p-2 text-[#898989] transition-colors duration-200 rounded-full bg-blue-50 hover:text-[#ff7c32] hover:bg-blue-100 dark:hover:text-white dark:hover:bg-[#ff7c32] dark:bg-dark focus:outline-none focus:bg-[#ff7c32] dark:focus:bg-[#ff7c32] focus:ring-[#ff7c32]">
                                <span class="sr-only">Open search panel</span>
                                <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button> -->

                           

                            <!-- Notification button -->
                            <!-- <button @click="openNotificationsPanel"
                                class="p-2 text-white transition-colors duration-200 rounded-full bg-[#898989] hover:text-white hover:bg-[#FF7C32] dark:hover:text-white dark:hover:bg-[#FF7C32] dark:bg-[#898989] focus:outline-none focus:bg-[#898989] dark:focus:bg-[#898989] focus:ring-[#898989]">
                                <span class="sr-only">Open Notification panel</span>
                                <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button> -->

                            <!-- User avatar button -->
                            <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                                <button @click.stop="userMenuOpen = !userMenuOpen"
                                    type="button" 
                                    aria-haspopup="true" 
                                    :aria-expanded="userMenuOpen ? 'true' : 'false'"
                                    class="flex items-center p-1 transition-all duration-200 rounded-full hover:ring-2 hover:ring-[#FF7C32] hover:ring-opacity-50 focus:outline-none focus:ring-2 focus:ring-[#FF7C32] focus:ring-opacity-50">
                                    <span class="sr-only">User menu</span>
                                    <img class="w-10 h-10 rounded-full border-2 border-transparent hover:border-[#FF7C32] transition-all duration-200"
                                        src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                        alt="User Profile" />
                                </button>

                                <!-- User dropdown menu -->
                                <div x-show="userMenuOpen" 
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95" 
                                    @click.stop
                                    @keydown.escape="userMenuOpen = false"
                                    class="absolute right-0 top-full mt-3 w-64 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-xl shadow-2xl ring-1 ring-black ring-opacity-10 focus:outline-none dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700"
                                    style="z-index: 999999 !important;"
                                    tabindex="-1" 
                                    role="menu" 
                                    aria-orientation="vertical"
                                    aria-label="User menu">
                                    
                                    <!-- User info section -->
                                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-t-xl" role="none">
                                        <div class="flex items-center space-x-3">
                                            <img class="w-10 h-10 rounded-full border-2 border-white shadow-sm" 
                                                 src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}" 
                                                 alt="User Profile">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ auth()->user()->name ?? 'Usuario' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-300 truncate">
                                                    {{ auth()->user()->email ?? 'email@ejemplo.com' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Menu items -->
                                    <div class="py-2" role="none">
                                        <a href="{{ route('account.show') }}" 
                                           role="menuitem"
                                           @click="userMenuOpen = false"
                                           class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-[#FBEBE2] hover:text-[#FF7C32] dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white transition-all duration-200">
                                            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-[#FF7C32] transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Configuración
                                        </a>
                                        
                                        <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                        
                                        <a href="{{ route('auth.logout') }}" 
                                           role="menuitem"
                                           @click="userMenuOpen = false"
                                           class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 dark:text-gray-200 dark:hover:bg-red-900 dark:hover:text-red-300 transition-all duration-200">
                                            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Cerrar Sesión
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </nav>

                        <!-- Mobile sub menu -->
                        <nav x-transition:enter="transition duration-200 ease-in-out transform sm:duration-500"
                            x-transition:enter-start="-translate-y-full opacity-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            x-transition:leave="transition duration-300 ease-in-out transform sm:duration-500"
                            x-transition:leave-start="translate-y-0 opacity-100"
                            x-transition:leave-end="-translate-y-full opacity-0" x-show="isMobileSubMenuOpen"
                            @click.away="isMobileSubMenuOpen = false"
                            class="absolute flex items-center p-4 bg-white rounded-md shadow-lg dark:bg-darker top-16 inset-x-4 md:hidden z-10"
                            aria-label="Secondary">
                            <div class="space-x-2">
                                <!-- Toggle dark theme button -->
                                {{-- <button aria-hidden="true" class="relative focus:outline-none" x-cloak
                                    @click="toggleTheme">
                                    <div
                                        class="w-12 h-6 transition bg-white rounded-full outline-none dark:bg-[#ff7c32]">
                                    </div>
                                    <div class="absolute top-0 left-0 inline-flex items-center justify-center w-6 h-6 transition-all duration-200 transform scale-110 rounded-full shadow-sm"
                                        :class="{
                                            'translate-x-0 -translate-y-px  bg-white text-[#ff7c32]': !
                                                isDark,
                                            'translate-x-6 text-[#ff7c32] bg-white': isDark
                                        }">
                                        <svg x-show="!isDark" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                        <svg x-show="isDark" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </div>
                                </button> --}}

                                <!-- Notification button -->
                                <!-- <button
                                    @click="openNotificationsPanel(); $nextTick(() => { isMobileSubMenuOpen = false })"
                                    class="p-2 text-[#898989] transition-colors duration-200 rounded-full bg-white hover:text-[#898989] hover:bg-[#FBEBE2] dark:hover:text-[#898989] dark:hover:bg-[#FBEBE2] dark:bg-dark focus:outline-none focus:bg-[#FBEBE2] dark:focus:bg-[#FBEBE2] focus:ring-[#FBEBE2]">
                                    <span class="sr-only">Open notifications panel</span>
                                    <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </button> -->


                                <!-- Search button -->
                                <!-- <button
                                    @click="openSearchPanel(); $nextTick(() => { $refs.searchInput.focus(); setTimeout(() => {isMobileSubMenuOpen= false}, 100) })"
                                    class=" text-[#898989] transition-colors duration-200 rounded-full bg-white hover:text-[#898989] hover:bg-[#FBEBE2] dark:hover:text-[#898989] dark:hover:bg-[#FBEBE2] dark:bg-dark focus:outline-none focus:bg-[#FBEBE2] dark:focus:bg-[#FBEBE2] focus:ring-[#FBEBE2]">
                                    <span class="sr-only">Open search panel</span>
                                    <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button> -->
                            </div>

                            <!-- User avatar button -->
                            <div class="relative ml-auto" x-data="{ mobileUserMenuOpen: false }" @click.away="mobileUserMenuOpen = false">
                                <button @click.stop="mobileUserMenuOpen = !mobileUserMenuOpen" 
                                    type="button" 
                                    aria-haspopup="true"
                                    :aria-expanded="mobileUserMenuOpen ? 'true' : 'false'"
                                    class="flex items-center p-1 transition-all duration-200 rounded-full hover:ring-2 hover:ring-[#FF7C32] hover:ring-opacity-50 focus:outline-none focus:ring-2 focus:ring-[#FF7C32] focus:ring-opacity-50">
                                    <span class="sr-only">User menu</span>
                                    <img class="w-10 h-10 rounded-full border-2 border-transparent hover:border-[#FF7C32] transition-all duration-200"
                                        src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                        alt="User Profile" />
                                </button>

                                <!-- User dropdown menu -->
                                <div x-show="mobileUserMenuOpen" 
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95" 
                                    @click.stop
                                    class="absolute right-0 top-full mt-3 w-64 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-xl shadow-2xl ring-1 ring-black ring-opacity-10 focus:outline-none dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700"
                                    style="z-index: 999999 !important;"
                                    role="menu" 
                                    aria-orientation="vertical" 
                                    aria-label="User menu">
                                    
                                    <!-- User info section -->
                                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-t-xl" role="none">
                                        <div class="flex items-center space-x-3">
                                            <img class="w-10 h-10 rounded-full border-2 border-white shadow-sm" 
                                                 src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}" 
                                                 alt="User Profile">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ auth()->user()->name ?? 'Usuario' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-300 truncate">
                                                    {{ auth()->user()->email ?? 'email@ejemplo.com' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Menu items -->
                                    <div class="py-2">
                                        <a href="{{ route('account.show') }}" 
                                           role="menuitem"
                                           @click="mobileUserMenuOpen = false"
                                           class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-[#FBEBE2] hover:text-[#FF7C32] dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white transition-all duration-200">
                                            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-[#FF7C32] transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Configuración
                                        </a>
                                        
                                        <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                        
                                        <a href="{{ route('auth.logout') }}" 
                                           role="menuitem"
                                           @click="mobileUserMenuOpen = false"
                                           class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 dark:text-gray-200 dark:hover:bg-red-900 dark:hover:text-red-300 transition-all duration-200">
                                            <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Cerrar Sesión
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </nav>
                    </div>
                    <!-- Mobile main manu -->
                    <div class="border-b md:hidden dark:border-[#FBEBE2]" x-show="isMobileMainMenuOpen"
                        @click.away="isMobileMainMenuOpen = false">
                        <nav aria-label="Main" class="px-2 py-4 space-y-2">
                            <!-- Dashboards links -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('dashboard.show') }}"
                                    class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z"
                                                :fill="(localStorage.getItem('dashboardClicked')) ? '#FF7C32' : '#898989'" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Tablero </span>
                                </a>
                            </div>

                            <!-- solicitudes -->
                            <!-- <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('requests.show') }}"
                                    class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 7V3H2V21H22V7H12ZM6 19H4V17H6V19ZM6 15H4V13H6V15ZM6 11H4V9H6V11ZM6 7H4V5H6V7ZM10 19H8V17H10V19ZM10 15H8V13H10V15ZM10 11H8V9H10V11ZM10 7H8V5H10V7ZM20 19H12V17H14V15H12V13H14V11H12V9H20V19ZM18 11H16V13H18V11ZM18 15H16V17H18V15Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Solicitudes </span>
                                </a>
                            </div> -->

                            <!-- cotizaciones -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('quotes.react-test') }}"
                                    class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M20 6H16V4L14 2H10L8 4V6H4C2.89 6 2.01 6.89 2.01 8L2 19C2 20.11 2.89 21 4 21H20C21.11 21 22 20.11 22 19V8C22 6.89 21.11 6 20 6ZM10 4H14V6H10V4ZM10.5 17.5L7 14L8.41 12.59L10.5 14.68L15.68 9.5L17.09 10.91L10.5 17.5Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Cotizaciones </span>
                                </a>
                            </div>

                            {{-- Gestión (Menu móvil con submenús) --}}
                            @if (
                                auth()->user()->hasRole('SUPER ADMIN') ||
                                auth()->user()->hasRole('JEFE COMERCIAL') ||
                                auth()->user()->hasRole('GERENTE DE CUENTA') ||
                                auth()->user()->hasRole('SAC')
                            )
                                <div x-data="{ isActive: false, open: false }">
                                    <button @click="open = !open" 
                                        class="flex items-center justify-between w-full p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]"
                                        :class="{ 'bg-[#FBEBE2] dark:bg-[#FBEBE2]': isActive || open }" 
                                        role="button" aria-haspopup="true" 
                                        :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                        <div class="flex items-center">
                                            <span aria-hidden="true">
                                                {{-- Ícono de gestión/settings --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </span>
                                            <span class="ml-2 text-sm"> Gestión </span>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" 
                                             class="w-4 h-4 transition-transform duration-200" 
                                             :class="{ 'rotate-180': open }" 
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    
                                    {{-- Submenús móviles --}}
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 -translate-y-1"
                                         class="ml-6 mt-2 space-y-1">
                                        
                                        {{-- Metas --}}
                                        @if (
                                            auth()->user()->hasRole('SUPER ADMIN') ||
                                            auth()->user()->hasRole('JEFE COMERCIAL')
                                        )
                                            <a href="{{ route('goals.index') }}"
                                                class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]"
                                                role="button" aria-haspopup="true">
                                                <span aria-hidden="true">
                                                    {{-- Ícono bullseye (meta) --}}
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                                    <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/>
                                                    <circle cx="12" cy="12" r="2" fill="currentColor"/>
                                                    </svg>
                                                </span>
                                                <span class="ml-2 text-sm"> Metas </span>
                                            </a>
                                        @endif
                                        
                                        {{-- Novedades & Alertas --}}
                                        @if (
                                            auth()->user()->hasRole('SUPER ADMIN') ||
                                            auth()->user()->hasRole('GERENTE DE CUENTA') ||
                                            auth()->user()->hasRole('SAC')
                                        )
                                            <a href="{{ route('goals.index') }}"
                                                class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]"
                                                role="button" aria-haspopup="true">
                                                <span aria-hidden="true">
                                                    {{-- Ícono de notificación --}}
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </span>
                                                <span class="ml-2 text-sm"> Novedades & Alertas </span>
                                            </a>
                                        @endif

                                        {{-- Conductores --}}
                                        @if (
                                            auth()->user()->hasRole('SUPER ADMIN') ||
                                            auth()->user()->hasRole('SAC')
                                        )
                                            <a href="{{ route('conductores.index') }}"
                                                class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2] dark:hover:bg-[#FBEBE2]"
                                                role="button" aria-haspopup="true">
                                                <span aria-hidden="true">
                                                    {{-- Ícono de conductor/car --}}
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                                    </svg>
                                                </span>
                                                <span class="ml-2 text-sm"> Conductores </span>
                                            </a>
                                        @endif
                                        {{-- Panel de porcentajes --}}
                                        @if (auth()->user()->hasRole('SUPER ADMIN') || auth()->user()->hasRole('PRICING')) 

                                            <a href="{{ route('percentage-settings.index') }}"
                                            class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md hover:bg-[#FBEBE2]"
                                            role="button" aria-haspopup="true">
                                                <span aria-hidden="true">
                                                    {{-- Icono porcentaje --}}
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M18.5 5.5l-13 13M7 7a2 2 0 11-4 0 2 2 0 014 0zm14 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </span>
                                                <span class="ml-2 text-sm"> Panel de Porcentajes </span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Clientes -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('contacts.show') }}"
                                    class="flex items-center p-2 font-medium text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3H5C3.89 3 3 3.9 3 5ZM15 9C15 10.66 13.66 12 12 12C10.34 12 9 10.66 9 9C9 7.34 10.34 6 12 6C13.66 6 15 7.34 15 9ZM6 17C6 15 10 13.9 12 13.9C14 13.9 18 15 18 17V18H6V17Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Clientes </span>
                                </a>
                            </div>
                            <!-- documentos -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('document.store') }}"
                                    class="flex items-center font-medium p-2 text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M14 2H6C4.9 2 4.01 2.9 4.01 4L4 20C4 21.1 4.89 22 5.99 22H18C19.1 22 20 21.1 20 20V8L14 2ZM16 18H8V16H16V18ZM16 14H8V12H16V14ZM13 9V3.5L18.5 9H13Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Documentos </span>
                                </a>
                            </div>

                            <!-- calendario -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('calendar.show') }}"
                                    class="flex items-center font-medium p-2 text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M17 11H7V13H17V11ZM19 4H18V2H16V4H8V2H6V4H5C3.89 4 3.01 4.9 3.01 6L3 20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V9H19V20ZM14 15H7V17H14V15Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Calendario </span>
                                </a>
                            </div>

                            <!-- buzón -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('mailbox.show') }}"
                                    class="flex items-center font-medium p-2 text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z"
                                                fill="#898989" />
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Buzón </span>
                                </a>
                            </div>

                            

                            <!-- analisis -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('contacts.show') }}"
                                    class="flex items-center font-medium p-2 text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="'#898989"
                                            xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink">
                                            <rect width="19" height="19" fill="url(#pattern0)"
                                                fill-opacity="0.5" />
                                            <defs>
                                                <pattern id="pattern0" patternContentUnits="objectBoundingBox"
                                                    width="1" height="1">
                                                    <use xlink:href="#image0_87_510" transform="scale(0.0078125)" />
                                                </pattern>
                                                <image id="image0_87_510" width="128" height="128"
                                                    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAXhSURBVHic7Z1LiBxFGMd/O65GhOhB2Rw0K8IeIviKiBgUDyrEVzDBxIug+ARP8SLqWdSIEQw+DhE8iAn4OBhBXfF10ATRPRnBYDxEQQxqouy6BpON46G2YFx6d7vrXfN9P6jbdNVX8/9193R3zQwooVgLvA0cAfoJ2t/AV8C9wEiC+SlLsAX4hzTBN7XdQC/6LJVGzsfsjbnCt21r7IkqzTxN/vD7wI84nAr0sOHPFbkLmGccGOu6kQrgTz93AQOs7LqBCuDPVO4ClLyMA7Pk/wzQBya6Fq9HAH9+Au7CXAYqgrkUeBP4jYqOAEoZPAz8iwogklDhqwAVEjJ8FaAyQoevAlREjPBVgEqIFb4KUAExw1cBCmcrbuG/Axxq+VoVoFBc9/z3gBXAwZavFyHABPAy8A1wALMM6/qsFS2Nz55/2nwfKsA8m1j8wct2ylsbFyJ8UAEA2AwcZ+k3YCflPODyPewPIl6ANuGXJEHI8EG4AF3CL0GC0OGDYAFcws8pQYzwQagAPuHnkCBW+CBQgBDhp5QgZvggTICQ4aeQIHb4IEiAGOHHlCBF+CBEgC3ACeKEb9tLhLtZFOomTxuGXoAU4YeUIGX4YL570Kb/c1wnlBPX8PcCOxy285UgdfgAL7bo/3vHvrPiE779GtR2h+1dJcgRPphD+7FlxrjHo/8shAjfkkKCXOFbbmdxCZ4P0H9SQoZviSlB7vAta4BXgO8wi0T2ADcH7D8Jrpd6e4Ezl+n7KYd++yx9iZjqUk8Em3Hf85cL3xJSAg0/ICnCt4SQQMMPiM85v2v4luccxutjPhO4hh/6nD8UpNzzF+J6JHBpuuc3kDN8SwoJNPwGSgjfElMCDb+BksK3xJBAw2+gxPAtISXQ8BtwDX8f8cO3hJBAw2+ghvAtPhJo+A3UFL7FRQINv4Eaw7d0kUDDb6Dm8C1tJNDwG8hxezcW21i83rcY4tu7rsuirgQ+p/sbsw+4CZh2HDcmG4FHMXPrAd8CL2Cev/cjjXkZcAPp1vLNAl8DHwEnfTr6lOHY85sYJf4evxLzq6KuVyK+bT9mcYlz8Sc7DlhL+CkYAT4gX/i2/QyscpnAeMeBNPz/s4H84du2w2UCK1h+haqGvzj2M0UJ7ZDrJHa16FzDb+Z98gdv25zrJFYDh5foWMNfnEnyBz/YnJkAvljQ2RxmXd0ZPh0POUUJMOoxkR+Aa4CLMX+WcAxznf+LR59KYnwEsOyfb0qFhBBAicOr+H3hc1uoQpSwtP0MsN5znFafAXL/np6SGRVAOCqAcFQA4agAwlEBhKMCCGdYbgStA64m3dKq3zHPQb5MNF40ahdgFbAbuC7T+B8DdwK/Zhrfm5pPAacDH5IvfDALOiepeMl4zQI8iHkKmZu1wAO5i3ClZgFuy13AABtzF+BKzQKcm7uAAc7LXYArNQtwSu4CBiiplk7ULIASABVAOCqAcGq/EdSGZ4EjjtueDTwSsJbikCDATswKZhcmGHIB9BQgHBVAOCqAcFQA4agAwlEBhKMCCGfwPkAPuBa4HDg10fgzmB+cOpBoPGUBVoAJ4A1M+KnpA68BD2G+Yq4kZBQYAz4j3zPtEeBu4CxgU6YaxNIDHqeMBQ0b8f9GrNKRHmXtdSXVIoIeZez9ltW5C5BGj7KWM5VUiwj0PoBwVADhqADC6bIi6BmPcS4A7vDYXolEFwEe8xhnPSpAkegpQDgqgHBUAOGoAMJRAYSjAghHBRCOCiAcFUA4KoBwVADhqADCUQGEowIIRwUQjgogHBVAOCqAcLosCfP6p+mMHMxdgCOTKQbRI4BwVADhqADCUQGE0wP+yl3EANMdXqt1+zPdA6ZyVzFAl1q0bn+mADbQ8r/mI7c/6Pa/f5cAJwqo+zhwUYe6x4A/C6i7D9xii3oycyGzuP08zP3AXMa654D7HOq+ETPnnO/5EwuLuhXzk20zCYs4DLwOrHF4Ey3rgHeBownrPgrsAa7yqPtCYBfmPUhV9wzwCQN7/n/B9tS80vxdfQAAAABJRU5ErkJggg==" />
                                            </defs>
                                        </svg>
                                    </span>
                                    <span class="ml-2 text-sm"> Análisis </span>
                                </a>
                            </div>

                            <!-- pricing -->
                            <div x-data="{ isActive: false, open: false }">
                                <a href="{{ route('pricing.show') }}"
                                    class="flex items-center font-medium p-2 text-[#202020] transition-colors rounded-md dark:text-light hover:bg-[#FBEBE2]  dark:hover:bg-[#FBEBE2] "
                                    :class="{ 'bg-[#FBEBE2]  dark:bg-[#FBEBE2] ': isActive || open }" role="button"
                                    aria-haspopup="true" :aria-expanded="(open || isActive) ? 'true' : 'false'">
                                    <span aria-hidden="true">
                                        <span aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M20 6H16V4L14 2H10L8 4V6H4C2.89 6 2.01 6.89 2.01 8L2 19C2 20.11 2.89 21 4 21H20C21.11 21 22 20.11 22 19V8C22 6.89 21.11 6 20 6ZM10 4H14V6H10V4ZM10.5 17.5L7 14L8.41 12.59L10.5 14.68L15.68 9.5L17.09 10.91L10.5 17.5Z"
                                                    fill="#898989" />
                                            </svg>
                                        </span>
                                    </span>
                                    <span class="ml-2 text-sm"> Pricing </span>
                                </a>
                            </div>

                        </nav>
                    </div>
                </header>

                <!-- Main content -->
                <main class="w-full h-full overflow-y-auto scrollbar-default px-6 py-4">
                    @yield('content')
                </main>

            </div>

           

            <!-- Notification panel -->
            <!-- Backdrop -->
            <div x-transition:enter="transition duration-300 ease-in-out" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition duration-300 ease-in-out"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                x-show="isNotificationsPanelOpen" @click="isNotificationsPanelOpen = false"
                class="fixed inset-0 z-10 bg-blue-800 bg-opacity-25" style="opacity: .5;" aria-hidden="true"></div>
            <!-- Panel -->
            <section x-cloak x-transition:enter="transition duration-300 ease-in-out transform sm:duration-500"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-300 ease-in-out transform sm:duration-500"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                x-ref="notificationsPanel" x-show="isNotificationsPanelOpen"
                @keydown.escape="isNotificationsPanelOpen = false" tabindex="-1"
                aria-labelledby="notificationPanelLabel"
                class="fixed inset-y-0 z-20 w-full max-w-xs bg-white dark:bg-darker dark:text-light sm:max-w-md focus:outline-none">
                <div class="absolute right-0 p-2 transform translate-x-full">
                    <!-- Close button -->
                    <button @click="isNotificationsPanelOpen = false"
                        class="p-2 text-white rounded-md focus:outline-none focus:ring">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col h-screen" x-data="{ activeTabe: 'action' }">
                    <!-- Panel header -->
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-between px-4 pt-4 border-b dark:border-blue-800">
                            <h2 id="notificationPanelLabel" class="pb-4 font-semibold">Notifications</h2>
                            <div class="space-x-2">
                                <button @click.prevent="activeTabe = 'action'"
                                    class="px-px pb-4 transition-all duration-200 transform translate-y-px border-b focus:outline-none"
                                    :class="{
                                        'border-blue-700 dark:border-blue-600': activeTabe ==
                                            'action',
                                        'border-transparent': activeTabe != 'action'
                                    }">
                                    Action
                                </button>
                                <button @click.prevent="activeTabe = 'user'"
                                    class="px-px pb-4 transition-all duration-200 transform translate-y-px border-b focus:outline-none"
                                    :class="{
                                        'border-blue-700 dark:border-blue-600': activeTabe ==
                                            'user',
                                        'border-transparent': activeTabe != 'user'
                                    }">
                                    User
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel content (tabs) -->
                    <div class="flex-1 pt-4 overflow-y-hidden hover:overflow-y-auto">
                        <!-- Action tab -->
                        <div class="space-y-4" x-show.transition.in="activeTabe == 'action'">
                            <a href="#" class="block">
                                <div class="flex px-4 space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <span
                                            class="z-10 inline-block p-2 overflow-visible text-blue-500 rounded-full bg-blue-50 dark:bg-blue-800">
                                            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </span>
                                        <div
                                            class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h5 class="text-sm font-semibold text-gray-600 dark:text-light">
                                            New project "KWD Dashboard" created
                                        </h5>
                                        <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                            Looks like there might be a new theme soon
                                        </p>
                                        <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 9h ago
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block">
                                <div class="flex px-4 space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <span
                                            class="inline-block p-2 overflow-visible text-blue-500 rounded-full bg-blue-50 dark:bg-blue-800">
                                            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </span>
                                        <div
                                            class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h5 class="text-sm font-semibold text-gray-600 dark:text-light">
                                            KWD Dashboard v0.0.2 was released
                                        </h5>
                                        <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                            Successful new version was released
                                        </p>
                                        <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 2d ago
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <template x-for="i in 20" x-key="i">
                                <a href="#" class="block">
                                    <div class="flex px-4 space-x-4">
                                        <div class="relative flex-shrink-0">
                                            <span
                                                class="inline-block p-2 overflow-visible text-blue-500 rounded-full bg-blue-50 dark:bg-blue-800">
                                                <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                            </span>
                                            <div
                                                class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-hidden">
                                            <h5 class="text-sm font-semibold text-gray-600 dark:text-light">
                                                New project "KWD Dashboard" created
                                            </h5>
                                            <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                                Looks like there might be a new theme soon
                                            </p>
                                            <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 9h ago
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>

                        <!-- User tab -->
                        <div class="space-y-4" x-show.transition.in="activeTabe == 'user'">
                            <a href="#" class="block">
                                <div class="flex px-4 space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <span class="relative z-10 inline-block overflow-visible rounded-ful">
                                            <img class="object-cover rounded-full w-9 h-9"
                                                src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                                alt="Ahmed kamel" />
                                        </span>
                                        <div
                                            class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h5 class="text-sm font-semibold text-gray-600 dark:text-light">Ahmed Kamel
                                        </h5>
                                        <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                            Shared new project "K-WD Dashboard"
                                        </p>
                                        <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 1d ago
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block">
                                <div class="flex px-4 space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <span class="relative z-10 inline-block overflow-visible rounded-ful">
                                            <img class="object-cover rounded-full w-9 h-9"
                                                src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                                alt="Ahmed kamel" />
                                        </span>
                                        <div
                                            class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h5 class="text-sm font-semibold text-gray-600 dark:text-light">Ahmed Kamel
                                        </h5>
                                        <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                            Commit new changes to K-WD Dashboard project.
                                        </p>
                                        <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 10h ago
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block">
                                <div class="flex px-4 space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <span class="relative z-10 inline-block overflow-visible rounded-ful">
                                            <img class="object-cover rounded-full w-9 h-9"
                                                src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                                alt="Ahmed kamel" />
                                        </span>
                                        <div
                                            class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h5 class="text-sm font-semibold text-gray-600 dark:text-light">Ahmed Kamel
                                        </h5>
                                        <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                            Release new version "K-WD Dashboard"
                                        </p>
                                        <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 20d ago
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <template x-for="i in 10" x-key="i">
                                <a href="#" class="block">
                                    <div class="flex px-4 space-x-4">
                                        <div class="relative flex-shrink-0">
                                            <span class="relative z-10 inline-block overflow-visible rounded-ful">
                                                <img class="object-cover rounded-full w-9 h-9"
                                                    src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                                    alt="Ahmed kamel" />
                                            </span>
                                            <div
                                                class="absolute h-24 p-px -mt-3 -ml-px bg-blue-50 left-1/2 dark:bg-blue-800">
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-hidden">
                                            <h5 class="text-sm font-semibold text-gray-600 dark:text-light">Ahmed Kamel
                                            </h5>
                                            <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                                Release new version "K-WD Dashboard"
                                            </p>
                                            <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> 20d ago
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Search panel -->
            <!-- Backdrop -->
            <div x-transition:enter="transition duration-300 ease-in-out" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition duration-300 ease-in-out"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-show="isSearchPanelOpen"
                @click="isSearchPanelOpen = false" class="fixed inset-0 z-10 bg-blue-800 bg-opacity-25"
                style="opacity: .5;" aria-hidden="ture"></div>
            <!-- Panel -->
            <section x-cloak x-transition:enter="transition duration-300 ease-in-out transform sm:duration-500"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-300 ease-in-out transform sm:duration-500"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                x-show="isSearchPanelOpen" @keydown.escape="isSearchPanelOpen = false"
                class="fixed inset-y-0 z-20 w-full max-w-xs bg-white shadow-xl dark:bg-darker dark:text-light sm:max-w-md focus:outline-none">
                <div class="absolute right-0 p-2 transform translate-x-full">
                    <!-- Close button -->
                    <button @click="isSearchPanelOpen = false"
                        class="p-2 text-white rounded-md focus:outline-none focus:ring">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <h2 class="sr-only">Search panel</h2>
                <!-- Panel content -->
                <div class="flex flex-col h-screen">
                    <!-- Panel header (Search input) -->
                    <div
                        class="relative flex-shrink-0 px-4 py-8 text-gray-400 border-b dark:border-blue-800 dark:focus-within:text-light focus-within:text-gray-700">
                        <span class="absolute inset-y-0 inline-flex items-center px-4">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input x-ref="searchInput" type="text"
                            class="w-full py-2 pl-10 pr-4 border rounded-full dark:bg-dark dark:border-transparent dark:text-light focus:outline-none focus:ring"
                            placeholder="Search..." />
                    </div>

                    <!-- Panel content (Search result) -->
                    <div class="flex-1 px-4 pb-4 space-y-4 overflow-y-hidden font-sans h hover:overflow-y-auto">
                        <h3 class="py-2 text-sm font-semibold text-gray-600 dark:text-light">History</h3>
                        <a href="#" class="flex space-x-4">
                            <div class="flex-shrink-0">
                                <img class="w-10 h-10 rounded-lg"
                                    src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                    alt="Post cover" />
                            </div>
                            <div class="flex-1 max-w-xs overflow-hidden">
                                <h4 class="text-sm font-semibold text-gray-600 dark:text-light">Header</h4>
                                <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                    Lorem ipsum dolor, sit amet consectetur.
                                </p>
                                <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> Post </span>
                            </div>
                        </a>
                        <a href="#" class="flex space-x-4">
                            <div class="flex-shrink-0">
                                <img class="w-10 h-10 rounded-lg"
                                    src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                    alt="Ahmed Kamel" />
                            </div>
                            <div class="flex-1 max-w-xs overflow-hidden">
                                <h4 class="text-sm font-semibold text-gray-600 dark:text-light">Ahmed Kamel</h4>
                                <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                    Last activity 3h ago.
                                </p>
                                <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> Offline </span>
                            </div>
                        </a>
                        <a href="#" class="flex space-x-4">
                            <div class="flex-shrink-0">
                                <img class="w-10 h-10 rounded-lg"
                                    src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                    alt="K-WD Dashboard" />
                            </div>
                            <div class="flex-1 max-w-xs overflow-hidden">
                                <h4 class="text-sm font-semibold text-gray-600 dark:text-light">K-WD Dashboard</h4>
                                <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                    Lorem ipsum dolor, sit amet consectetur adipisicing elit.
                                </p>
                                <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> Updated 3h ago.
                                </span>
                            </div>
                        </a>
                        <template x-for="i in 10" x-key="i">
                            <a href="#" class="flex space-x-4">
                                <div class="flex-shrink-0">
                                    <img class="w-10 h-10 rounded-lg"
                                        src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}"
                                        alt="K-WD Dashboard" />
                                </div>
                                <div class="flex-1 max-w-xs overflow-hidden">
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-light">K-WD Dashboard</h4>
                                    <p class="text-sm font-normal text-gray-400 truncate dark:text-blue-400">
                                        Lorem ipsum dolor, sit amet consectetur adipisicing elit.
                                    </p>
                                    <span class="text-sm font-normal text-gray-400 dark:text-blue-500"> Updated 3h ago.
                                    </span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @livewireScripts
    {{-- Alpine.js ya está incluido en Livewire, no necesitamos cargarlo manualmente --}}

    <script>
        const setup = () => {
            const getTheme = () => {
                if (window.localStorage.getItem('dark')) {
                    return JSON.parse(window.localStorage.getItem('dark'))
                }
                return !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            }

            const setTheme = (value) => {
                window.localStorage.setItem('dark', value)
            }

            const setSidebarState = (value) => {
                window.localStorage.setItem('sidebarCollapsed', value)
            }

            return {
                loading: true,
                isDark: getTheme(),
                sidebarCollapsed: JSON.parse(window.localStorage.getItem('sidebarCollapsed') || 'false'),
                
                init() {
                    this.$watch('sidebarCollapsed', (value) => {
                        setSidebarState(value)
                    })
                },
                
                toggleTheme() {
                    this.isDark = !this.isDark
                    setTheme(this.isDark)
                },
                setLightTheme() {
                    this.isDark = false
                    setTheme(this.isDark)
                },
                setDarkTheme() {
                    this.isDark = true
                    setTheme(this.isDark)
                },
                isSettingsPanelOpen: false,
                openSettingsPanel() {
                    this.isSettingsPanelOpen = true
                    this.$nextTick(() => {
                        this.$refs.settingsPanel.focus()
                    })
                },
                isNotificationsPanelOpen: false,
                openNotificationsPanel() {
                    this.isNotificationsPanelOpen = true
                    this.$nextTick(() => {
                        this.$refs.notificationsPanel.focus()
                    })
                },
                isSearchPanelOpen: false,
                openSearchPanel() {
                    this.isSearchPanelOpen = true
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus()
                    })
                },
                isMobileSubMenuOpen: false,
                openMobileSubMenu() {
                    this.isMobileSubMenuOpen = true
                    this.$nextTick(() => {
                        this.$refs.mobileSubMenu.focus()
                    })
                },
                isMobileMainMenuOpen: false,
                openMobileMainMenu() {
                    this.isMobileMainMenuOpen = true
                    this.$nextTick(() => {
                        this.$refs.mobileMainMenu.focus()
                    })
                },
            }
        }
    </script>
    
    <!-- Script para llamadas por grupo de cotización -->
    <script src="{{ asset('js/group-calls.js') }}"></script>
    
    <!-- Sistema de Debug de Errores -->
    @include('components.error-debug-button')
    
    <div id="modal-root"></div>
</body>

</html>
