<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";

const page = usePage();
const toast = useToast();

const checkFlash = () => {
    const flash = (page.props as any)?.flash;
    if (flash?.success) {
        toast.add({
            severity: "success",
            summary: "Berhasil",
            detail: flash.success,
            life: 4000,
        });
    }
    if (flash?.error) {
        toast.add({
            severity: "error",
            summary: "Perhatian",
            detail: flash.error,
            life: 5000,
        });
    }
    if (flash?.info) {
        toast.add({
            severity: "info",
            summary: "Informasi",
            detail: flash.info,
            life: 4000,
        });
    }
};

onMounted(() => {
    checkFlash();
});

watch(
    () => [
        (page.props as any)?.flash?.success,
        (page.props as any)?.flash?.error,
    ],
    ([newSuccess, newError]) => {
        if (newSuccess) {
            toast.add({
                severity: "success",
                summary: "Berhasil",
                detail: newSuccess,
                life: 4000,
            });
        }
        if (newError) {
            toast.add({
                severity: "error",
                summary: "Perhatian",
                detail: newError,
                life: 5000,
            });
        }
    },
);

const isMobileMenuOpen = ref(false);
const isUserDropdownOpen = ref(false);

const toggleUserDropdown = () => {
    isUserDropdownOpen.value = !isUserDropdownOpen.value;
};

const closeUserDropdown = () => {
    isUserDropdownOpen.value = false;
};

const logout = () => {
    router.post("/logout");
};

const userRoles = computed<string[]>(() => {
    return (page.props.auth as any)?.roles || [];
});

const userName = computed(() => {
    return (page.props.auth as any)?.user?.name || "Pengguna";
});

const userEmail = computed(() => {
    return (page.props.auth as any)?.user?.email || "";
});

const userRole = computed(() => {
    return userRoles.value[0] || "Staf";
});

const schoolLogo = computed(() => {
    return (page.props.school as any)?.logo_path || null;
});

const applicationName = computed(() => {
    return (page.props.school as any)?.application_name || 'Sekolah Disiplin';
});

const schoolName = computed(() => {
    return (page.props.school as any)?.name || "SMP Malang";
});

const academicYear = computed(() => {
    return (page.props.school as any)?.academic_year || "2026/2027";
});

const hasRole = (roles?: string[]) => {
    if (!roles || roles.length === 0) return true;
    const current = userRoles.value;
    if (current.includes("Super Admin")) return true;
    return roles.some((r) => current.includes(r));
};

interface NavItem {
    label: string;
    href: string;
    icon: string;
    roles?: string[];
}

const mainNavItems: NavItem[] = [
    { label: "Dashboard", href: "/dashboard", icon: "pi pi-th-large" },
    {
        label: "Data Siswa",
        href: "/students",
        icon: "pi pi-users",
        roles: ["Super Admin", "Tata Tertib", "BK", "Teacher", "Principal"],
    },
    {
        label: "Catatan Disiplin",
        href: "/discipline-events",
        icon: "pi pi-clipboard",
        roles: ["Super Admin", "Tata Tertib", "BK", "Teacher", "Principal"],
    },
    {
        label: "Pembinaan",
        href: "/counseling-records",
        icon: "pi pi-file-edit",
        roles: ["Super Admin", "BK", "Principal"],
    },
    {
        label: "Tindak Lanjut",
        href: "/follow-ups",
        icon: "pi pi-sliders-h",
        roles: ["Super Admin", "Tata Tertib", "BK", "Principal", "Teacher"],
    },
    {
        label: "Rekap Disiplin",
        href: "/discipline-recap",
        icon: "pi pi-calendar",
        roles: ["Super Admin", "BK", "Principal", "Tata Tertib", "Teacher"],
    },
    {
        label: "Surat & Dokumen",
        href: "/reports",
        icon: "pi pi-file",
        roles: ["Super Admin", "BK", "Principal", "Tata Tertib", "Teacher"],
    },
    {
        label: "Analitik",
        href: "/reports/analytics",
        icon: "pi pi-chart-bar",
        roles: ["Super Admin", "BK", "Principal", "Tata Tertib", "Teacher"],
    },
];

const settingNavItems: NavItem[] = [
    {
        label: "Master Data",
        href: "/master-data",
        icon: "pi pi-database",
        roles: ["Super Admin", "Tata Tertib", "Teacher", "BK", "Principal"],
    },
    {
        label: "Data Kelas",
        href: "/class-rooms",
        icon: "pi pi-building",
        roles: ["Super Admin", "Tata Tertib"],
    },
    {
        label: "Data Guru",
        href: "/teachers",
        icon: "pi pi-id-card",
        roles: ["Super Admin", "Tata Tertib"],
    },
    {
        label: "Import & Export",
        href: "/imports",
        icon: "pi pi-upload",
        roles: ["Super Admin", "Tata Tertib", "BK"],
    },
    {
        label: "Pengaturan Sekolah",
        href: "/settings",
        icon: "pi pi-cog",
        roles: ["Super Admin", "Tata Tertib"],
    },
    {
        label: "Manajemen Pengguna",
        href: "/users",
        icon: "pi pi-user-plus",
        roles: ["Super Admin"],
    },
    {
        label: "Log Aktivitas",
        href: "/audit-logs",
        icon: "pi pi-history",
        roles: ["Super Admin"],
    },
];

const visibleMainNavItems = computed(() =>
    mainNavItems.filter((item) => hasRole(item.roles)),
);
const visibleSettingNavItems = computed(() =>
    settingNavItems.filter((item) => hasRole(item.roles)),
);

const isNavActive = (href: string) => {
    if (href === "/dashboard") {
        return page.url === "/dashboard";
    }
    if (href === "/reports") {
        return (
            page.url.startsWith("/reports") &&
            !page.url.startsWith("/reports/analytics")
        );
    }
    return page.url.startsWith(href);
};
</script>

<template>
    <div
        class="min-h-screen bg-[#F8F9FA] flex flex-row antialiased font-sans text-gray-900"
    >
        <Toast position="top-right" :pt="{ root: { class: '!z-[99999]' } }" />
        <ConfirmDialog />

        <!-- ================= STATIC SIDEBAR (DESKTOP) ================= -->
        <aside
            class="hidden md:flex flex-col w-[260px] bg-[#001AFF] border-r border-[#001AFF] shrink-0 sticky top-0 h-screen z-30 select-none"
        >
            <!-- Branding: Logo Sekolah dari Pengaturan & Nama Instansi -->
            <div
                class="h-20 px-6 flex items-center gap-3 border-b border-white/10 shrink-0"
            >
                <Link
                    href="/dashboard"
                    class="flex items-center gap-3 min-w-0 group"
                >
                    <div
                        class="relative w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center shrink-0 border border-white/20 bg-white shadow-xs"
                    >
                        <img
                            v-if="schoolLogo"
                            :src="schoolLogo"
                            alt="Logo Sekolah"
                            class="w-full h-full object-contain p-1"
                        />
                        <div
                            v-else
                            class="w-full h-full bg-[#001AFF] flex items-center justify-center text-white font-bold text-sm tracking-wider"
                        >
                            SD
                        </div>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span
                            class="text-sm font-bold text-white leading-tight group-hover:text-blue-100 transition-colors truncate"
                        >
                            {{ applicationName }}
                        </span>
                        <span
                            class="text-[11px] font-medium text-blue-200 truncate"
                            :title="schoolName"
                        >
                            {{ schoolName }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Periode Akademik Aktif Badge -->
            <div class="px-5 pt-4 pb-2 shrink-0">
                <div
                    class="flex items-center justify-between px-3.5 py-2 rounded-xl bg-white/10 border border-white/10 text-xs"
                >
                    <div
                        class="flex items-center gap-2 text-white font-medium truncate"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"
                        ></span>
                        <span class="truncate">{{ academicYear }}</span>
                    </div>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 bg-emerald-500/20 px-1.5 py-0.5 rounded"
                        >Aktif</span
                    >
                </div>
            </div>

            <!-- Navigation Links (Scrollable Container) -->
            <div
                class="flex-1 overflow-y-auto px-4 py-3 space-y-6 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
            >
                <!-- Group: Menu Utama -->
                <div v-if="visibleMainNavItems.length > 0">
                    <p
                        class="px-3 text-[11px] font-bold text-blue-300 uppercase tracking-widest mb-2"
                    >
                        Menu Utama
                    </p>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in visibleMainNavItems"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center gap-3 px-3.5 py-2.5 text-xs rounded-xl transition-all cursor-pointer"
                            :class="
                                isNavActive(item.href)
                                    ? 'bg-white text-[#001AFF] font-bold shadow-xs'
                                    : 'text-blue-100 font-medium hover:bg-white/10 hover:text-white'
                            "
                        >
                            <i
                                :class="[
                                    item.icon,
                                    'text-sm shrink-0',
                                    isNavActive(item.href)
                                        ? 'text-[#001AFF]'
                                        : 'text-blue-200',
                                ]"
                            ></i>
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- Group: Pengaturan & Master Data -->
                <div v-if="visibleSettingNavItems.length > 0">
                    <p
                        class="px-3 text-[11px] font-bold text-blue-300 uppercase tracking-widest mb-2"
                    >
                        Pengaturan & Master
                    </p>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in visibleSettingNavItems"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center gap-3 px-3.5 py-2.5 text-xs rounded-xl transition-all cursor-pointer"
                            :class="
                                isNavActive(item.href)
                                    ? 'bg-white text-[#001AFF] font-bold shadow-xs'
                                    : 'text-blue-100 font-medium hover:bg-white/10 hover:text-white'
                            "
                        >
                            <i
                                :class="[
                                    item.icon,
                                    'text-sm shrink-0',
                                    isNavActive(item.href)
                                        ? 'text-[#001AFF]'
                                        : 'text-blue-200',
                                ]"
                            ></i>
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- User Info & Logout (Bottom Sidebar) -->
            <div class="p-4 border-t border-white/10 bg-[#0017e6] shrink-0">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div
                            class="w-9 h-9 rounded-full bg-white text-[#001AFF] font-bold text-xs flex items-center justify-center shrink-0"
                        >
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span
                                class="text-xs font-bold text-white truncate leading-tight"
                                :title="userName"
                            >
                                {{ userName }}
                            </span>
                            <span
                                class="text-[10px] font-medium text-blue-200 capitalize truncate flex items-center gap-1"
                            >
                                <span>{{ userRole }}</span>
                               
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="logout"
                        title="Keluar dari akun"
                        class="w-8 h-8 rounded-lg text-blue-200 hover:text-white hover:bg-red-500/80 flex items-center justify-center transition-colors cursor-pointer shrink-0"
                    >
                        <i class="pi pi-sign-out text-sm"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ================= MOBILE DRAWER (RESPONSIVE) ================= -->
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 md:hidden">
            <!-- Backdrop -->
            <div
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-xs transition-opacity"
                @click="isMobileMenuOpen = false"
            ></div>

            <!-- Drawer Container -->
            <aside
                class="fixed inset-y-0 left-0 w-[270px] bg-[#001AFF] shadow-xl flex flex-col z-50 transition-transform"
            >
                <!-- Branding Header -->
                <div
                    class="h-16 flex items-center justify-between px-5 border-b border-white/10 shrink-0"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="w-8 h-8 rounded-xl overflow-hidden flex items-center justify-center shrink-0 border border-white/20 bg-white"
                        >
                            <img
                                v-if="schoolLogo"
                                :src="schoolLogo"
                                alt="Logo Sekolah"
                                class="w-full h-full object-contain p-0.5"
                            />
                            <div
                                v-else
                                class="w-full h-full bg-[#001AFF] flex items-center justify-center text-white font-bold text-xs"
                            >
                                SD
                            </div>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span
                                class="font-bold text-xs text-white truncate"
                                >{{ applicationName }}</span
                            >
                            <span class="text-[10px] text-blue-200 truncate">{{
                                schoolName
                            }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isMobileMenuOpen = false"
                        class="w-8 h-8 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 flex items-center justify-center cursor-pointer"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- Navigation List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div>
                        <p
                            class="px-3 text-[10px] font-bold text-blue-300 uppercase tracking-widest mb-2"
                        >
                            Menu Utama
                        </p>
                        <nav class="space-y-1">
                            <Link
                                v-for="item in visibleMainNavItems"
                                :key="item.href"
                                :href="item.href"
                                @click="isMobileMenuOpen = false"
                                class="flex items-center gap-3 px-3.5 py-2.5 text-xs rounded-xl transition-all cursor-pointer"
                                :class="
                                    isNavActive(item.href)
                                        ? 'bg-white text-[#001AFF] font-bold shadow-xs'
                                        : 'text-blue-100 font-medium hover:bg-white/10 hover:text-white'
                                "
                            >
                                <i
                                    :class="[
                                        item.icon,
                                        'text-sm',
                                        isNavActive(item.href)
                                            ? 'text-[#001AFF]'
                                            : 'text-blue-200',
                                    ]"
                                ></i>
                                <span class="truncate">{{ item.label }}</span>
                            </Link>
                        </nav>
                    </div>

                    <div v-if="visibleSettingNavItems.length > 0">
                        <p
                            class="px-3 text-[10px] font-bold text-blue-300 uppercase tracking-widest mb-2"
                        >
                            Pengaturan & Master
                        </p>
                        <nav class="space-y-1">
                            <Link
                                v-for="item in visibleSettingNavItems"
                                :key="item.href"
                                :href="item.href"
                                @click="isMobileMenuOpen = false"
                                class="flex items-center gap-3 px-3.5 py-2.5 text-xs rounded-xl transition-all cursor-pointer"
                                :class="
                                    isNavActive(item.href)
                                        ? 'bg-white text-[#001AFF] font-bold shadow-xs'
                                        : 'text-blue-100 font-medium hover:bg-white/10 hover:text-white'
                                "
                            >
                                <i
                                    :class="[
                                        item.icon,
                                        'text-sm',
                                        isNavActive(item.href)
                                            ? 'text-[#001AFF]'
                                            : 'text-blue-200',
                                    ]"
                                ></i>
                                <span class="truncate">{{ item.label }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- User Info & Logout (Drawer Bottom) -->
                <div
                    class="p-4 border-t border-white/10 bg-[#0017e6] space-y-3 shrink-0"
                >
                    <Link
                        href="/profile"
                        @click="isMobileMenuOpen = false"
                        class="flex items-center gap-3 cursor-pointer group"
                        title="Profil Akun & Keamanan"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-white text-[#001AFF] font-bold text-xs flex items-center justify-center shrink-0"
                        >
                            {{ userName.charAt(0) }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span
                                class="text-xs font-bold text-white truncate group-hover:underline"
                                >{{ userName }}</span
                            >
                            <span
                                class="text-[10px] text-blue-200 truncate capitalize"
                                >{{ userRole }} · {{ academicYear }}</span
                            >
                        </div>
                    </Link>
                    <button
                        type="button"
                        @click="logout"
                        class="w-full text-left text-xs font-semibold text-red-300 hover:text-white flex items-center gap-2 pt-2 border-t border-white/10 cursor-pointer"
                    >
                        <i class="pi pi-sign-out text-xs"></i>
                        <span>Keluar Akun</span>
                    </button>
                </div>
            </aside>
        </div>

        <!-- ================= MAIN CONTENT WRAPPER ================= -->
        <div class="flex-1 flex flex-col min-w-0 min-h-screen">
            <!-- Top Utility Bar / Header -->
            <header
                class="h-16 md:h-20 bg-transparent flex items-center justify-between px-4 sm:px-8 shrink-0 z-10 w-full pt-2 md:pt-4 pb-2"
            >
                <!-- Sisi Kiri: Tombol Hamburger Mobile + Header Slot / Title -->
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        @click="isMobileMenuOpen = true"
                        class="md:hidden w-9 h-9 rounded-xl border border-gray-200 bg-white text-gray-700 flex items-center justify-center transition-all cursor-pointer shadow-xs active:scale-95"
                    >
                        <i class="pi pi-bars text-sm"></i>
                    </button>

                    <div
                        class="flex items-center text-gray-700 text-sm font-semibold truncate"
                    >
                        <slot name="header">
                            <span
                                class="font-bold text-gray-900 text-base md:text-lg"
                                >Dashboard</span
                            >
                        </slot>
                    </div>
                </div>

                <!-- Sisi Kanan: User Profile & Settings Dropdown Menu -->
                <div class="relative">
                    <button
                        type="button"
                        @click="toggleUserDropdown"
                        class="flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white border border-gray-200/80 hover:border-indigo-300 shadow-xs hover:shadow-sm transition-all text-xs font-semibold text-gray-700 cursor-pointer select-none"
                        :class="{
                            'ring-2 ring-indigo-500/20 border-indigo-400':
                                isUserDropdownOpen,
                        }"
                        title="Menu Akun & Pengaturan"
                    >
                        <div
                            class="w-6 h-6 rounded-full bg-[#001AFF] text-white flex items-center justify-center font-bold text-[10px]"
                        >
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                        <span
                            class="hidden sm:inline-block max-w-[120px] truncate"
                            >{{ userName }}</span
                        >
                        <i
                            class="pi pi-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': isUserDropdownOpen }"
                        ></i>
                    </button>

                    <!-- Backdrop Click Outside -->
                    <div
                        v-if="isUserDropdownOpen"
                        @click="closeUserDropdown"
                        class="fixed inset-0 z-40"
                    ></div>

                    <!-- Dropdown Popover Card -->
                    <div
                        v-if="isUserDropdownOpen"
                        class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
                    >
                        <!-- User Identity Header -->
                        <div
                            class="px-3 py-2.5 bg-gray-50/80 rounded-xl mb-1.5 border border-gray-100/70"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-8 h-8 rounded-full bg-[#001AFF] text-white font-bold text-xs flex items-center justify-center shrink-0"
                                >
                                    {{ userName.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-xs font-bold text-gray-900 truncate"
                                    >
                                        {{ userName }}
                                    </p>
                                    <p
                                        class="text-[10px] text-gray-400 font-mono truncate"
                                    >
                                        {{ userEmail }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-1.5">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full"
                                >
                                    {{ userRole }}
                                </span>
                                <span
                                    class="text-[10px] text-gray-400 font-medium"
                                    >TA: {{ academicYear }}</span
                                >
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="space-y-0.5">
                            <!-- Profil & Keamanan (All Roles) -->
                            <Link
                                href="/profile"
                                @click="closeUserDropdown"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-700 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
                            >
                                <i class="pi pi-user text-xs text-gray-400"></i>
                                <span>Profil & Keamanan</span>
                            </Link>

                            <!-- Pengaturan Sekolah (Super Admin & Tata Tertib) -->
                            <Link
                                v-if="hasRole(['Super Admin', 'Tata Tertib'])"
                                href="/settings"
                                @click="closeUserDropdown"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-700 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
                            >
                                <i class="pi pi-cog text-xs text-gray-400"></i>
                                <span>Pengaturan Sekolah</span>
                            </Link>

                            <!-- Manajemen Pengguna (Super Admin Only) -->
                            <Link
                                v-if="hasRole(['Super Admin'])"
                                href="/users"
                                @click="closeUserDropdown"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-700 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
                            >
                                <i
                                    class="pi pi-user-plus text-xs text-gray-400"
                                ></i>
                                <span>Manajemen Pengguna</span>
                            </Link>

                            <!-- Log Aktivitas Sistem (Super Admin Only) -->
                            <Link
                                v-if="hasRole(['Super Admin'])"
                                href="/audit-logs"
                                @click="closeUserDropdown"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-700 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
                            >
                                <i
                                    class="pi pi-history text-xs text-gray-400"
                                ></i>
                                <span>Log Aktivitas Sistem</span>
                            </Link>
                        </div>

                        <!-- Divider -->
                        <div class="my-1.5 border-t border-gray-100"></div>

                        <!-- Logout Button -->
                        <button
                            type="button"
                            @click="logout"
                            class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-600 rounded-xl hover:bg-red-50 hover:text-red-700 transition-colors cursor-pointer text-left"
                        >
                            <i class="pi pi-sign-out text-xs text-red-500"></i>
                            <span>Keluar Akun</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Page Content Slot -->
            <main class="flex-1 px-4 sm:px-8 pb-8 pt-2">
                <slot />
            </main>
        </div>
    </div>
</template>
