<template>
  <div>
    <div id="dropdown" />
    <div class="md:flex md:flex-col">
      <div class="md:flex md:flex-col md:h-screen">
        <div class="md:flex md:flex-shrink-0">
          <div class="flex items-center justify-between px-6 py-4 bg-indigo-900 md:flex-shrink-0 md:justify-center md:w-56">
            <Link class="mt-1" href="/">
              <logo class="fill-white" width="120" height="28" />
            </Link>
          </div>
          <div class="md:text-md flex items-center justify-between p-4 w-full text-sm bg-white border-b md:px-12 md:py-0">
            <div v-if="auth.user.owner === 1" class="mr-4 mt-1">Stanowisko: Administrator</div>
            <div v-if="auth.user.owner === 2" class="mr-4 mt-1">Stanowisko: Biuro</div>
            <div v-if="auth.user.owner === 3" class="mr-4 mt-1">Stanowisko: Kierownik Budowy</div>
<!--            <div>    {{auth.permissions.kierownik}}</div>-->
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
                  <Link v-if="auth.user.owner === 1 || auth.user.owner === 2" class="block px-6 py-2 hover:text-white hover:bg-indigo-500" href="/users">Użytkownicy</Link>
                  <Link class="block px-6 py-2 w-full text-left hover:text-white hover:bg-indigo-500" href="/logout" method="delete" as="button">Wyloguj</Link>
                </div>
              </template>
            </dropdown>
          </div>
        </div>
        <div class="md:flex md:flex-grow md:overflow-hidden">
          <main-menu class="hidden flex-shrink-0 p-12 w-56 bg-indigo-800 overflow-y-auto md:block" />
          <div class="px-4 py-8 md:flex-1 md:p-12 md:overflow-y-auto" scroll-region>
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

export default {
  components: {
    Dropdown,
    FlashMessages,
    Icon,
    Link,
    Logo,
    MainMenu,
  },
  props: {
    auth: Object,
  },
}
</script>
