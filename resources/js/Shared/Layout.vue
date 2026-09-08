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
        <div class="md:flex md:flex-shrink-0">
          <!-- Panel i logo w rozmiarach z CRM: 16rem szerokości, logo bez
               sztywnego rozmiaru, więc wypełnia panel tak samo jak tam. -->
          <div class="flex items-center justify-between px-6 py-4 bg-white md:flex-shrink-0 md:justify-center md:w-64 border-b transition-all duration-300">
            <Link class="mt-1" href="/">
              <img src="/img/MKL-BAU.png" alt="logo" />
            </Link>
          </div>
          <div class="md:text-md flex items-center justify-between p-4 w-full text-sm bg-white border-b md:px-12 md:py-0">
            <div class="mr-4 mt-1">Stanowisko: {{ etykietaRoli(auth.user.owner) }}</div>
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
                    <Link class="block px-6 py-2 w-full text-left hover:text-white hover:bg-indigo-500" href="/logout" method="delete" as="button">Wyloguj</Link>
                  </div>
                </template>
              </dropdown>
            </div>
          </div>
        </div>
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
  methods: {
    etykietaRoli,
  },

  props: {
    auth: Object,
  },
}
</script>
