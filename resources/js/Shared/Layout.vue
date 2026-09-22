<template>
  <div>
    <div id="dropdown" />
    <!-- Widoczny pasek, żeby nie dało się zapomnieć, że to cudze konto. -->
    <div v-if="$page.props.podszywanie" class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-sm text-yellow-900 bg-yellow-300">
      <span>Pracujesz jako <strong>{{ $page.props.podszywanie.kto }}</strong></span>
      <Link href="/wroc-do-siebie" method="post" as="button" type="button" class="font-semibold underline hover:text-yellow-700">
        Wróć na swoje konto
      </Link>
    </div>
    <div class="md:flex md:flex-col">
      <div class="md:flex md:flex-col md:h-screen">
        <!-- Telefon: logo i pasek w jednym wierszu, logo małe — pełnowymiarowe
             zajmowało pół ekranu, zanim pokazała się treść. Szeroki ekran:
             panel i logo w rozmiarach z CRM (16rem), logo wypełnia panel. -->
        <div class="flex md:flex-shrink-0">
          <div class="flex items-center flex-shrink-0 pl-4 pr-2 py-2 bg-white border-b md:px-6 md:py-4 md:justify-center md:w-64 transition-all duration-300">
            <Link class="md:mt-1" href="/">
              <img src="/img/MKL-BAU.png" alt="logo" class="h-6 w-auto md:h-auto" />
            </Link>
          </div>
          <div class="md:text-md flex items-center justify-between px-2 py-2 min-w-0 w-full text-sm bg-white border-b md:px-12 md:py-0">
            <!-- Na telefonie boczne menu jest schowane — bez tego przycisku
                 kierownik nie miał jak wejść w Budowy czy Termin uprawnień. -->
            <button type="button" class="md:hidden mr-3 p-2 -ml-2 rounded text-gray-700 hover:bg-gray-100" :aria-expanded="menuOtwarte ? 'true' : 'false'" aria-label="Menu" @click="menuOtwarte = !menuOtwarte">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path v-if="!menuOtwarte" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <div class="mr-4 mt-1 truncate hidden sm:block">Stanowisko: {{ etykietaRoli(auth.user.owner) }}</div>
            <!-- Dzwonek trzymamy w jednej grupie z nazwiskiem, żeby justify-between
                 nie wypychało go na środek paska. -->
            <div class="flex items-center ml-auto">
              <notification-bell class="mr-3" />
              <dropdown class="mt-1" placement="bottom-end">
                <template #default>
                  <div class="group flex items-center cursor-pointer select-none">
                    <div class="mr-1 text-gray-700 group-hover:text-indigo-600 focus:text-indigo-600 whitespace-nowrap">
                      <span>{{ auth.user.first_name }}</span>
                      <span class="hidden md:inline">&nbsp;{{ auth.user.last_name }}</span>
                    </div>
                    <icon class="w-5 h-5 fill-gray-700 group-hover:fill-indigo-600 focus:fill-indigo-600" name="cheveron-down" />
                  </div>
                </template>
                <template #dropdown>
                  <div class="mt-2 py-2 text-sm bg-white rounded shadow-xl">
                    <Link class="block px-6 py-2 hover:text-white hover:bg-indigo-500" :href="`/users/${auth.user.id}/edit`">Profil</Link>
                    <Link v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="block px-6 py-2 hover:text-white hover:bg-indigo-500" href="/users">Użytkownicy</Link>
                    <!-- Pełne przejście (nie Inertia Link) — /sso/do-crm przekierowuje na inną domenę. -->
                    <a class="block px-6 py-2 hover:text-white hover:bg-indigo-500" href="/sso/do-crm">Przejdź do CRM</a>
                    <Link class="block px-6 py-2 w-full text-left hover:text-white hover:bg-indigo-500" href="/logout" method="delete" as="button">Wyloguj</Link>
                  </div>
                </template>
              </dropdown>
            </div>
          </div>
        </div>
        <!-- Menu na telefon: rozwija się pod nagłówkiem i chowa po wybraniu strony. -->
        <main-menu v-if="menuOtwarte" class="md:hidden px-4 py-4 bg-indigo-800 border-b border-indigo-900" />
        <div class="md:flex md:flex-grow md:overflow-hidden">
          <main-menu class="hidden flex-shrink-0 px-4 py-8 w-64 bg-indigo-800 overflow-y-auto md:block border-r border-indigo-900" />
          <div class="px-4 py-8 md:flex-1 md:p-12 md:overflow-y-auto">
            <flash-messages />
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Logo from '@/Shared/Logo'
import Dropdown from '@/Shared/Dropdown'
import MainMenu from '@/Shared/MainMenu'
import FlashMessages from '@/Shared/FlashMessages'
import { etykietaRoli } from '@/role'
import NotificationBell from '@/Shared/NotificationBell'

export default {
  components: {
    Dropdown,
    FlashMessages,
    Icon,
    Link,
    Logo,
    MainMenu,
    NotificationBell,
  },
  props: {
    auth: Object,
  },
  data() {
    return { menuOtwarte: false }
  },
  watch: {
    // Po przejściu na inną stronę menu z telefonu ma się schować samo.
    '$page.url'() {
      this.menuOtwarte = false
    },
  },
  methods: {
    etykietaRoli,
  },
}
</script>
