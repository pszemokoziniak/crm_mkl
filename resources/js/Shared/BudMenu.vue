<template>
  <div v-if="$page.props.auth.user.owner !== 3">
    <nav class="flex items-center justify-between flex-nowrap bg-teal-500 p-6 overflow-x-auto">
      <div class="flex items-center flex-shrink-0 text-indigo-500 mr-6">
        <svg class="fill-current h-4 w-4 mr-2" width="54" height="54" viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 22.1c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05zM0 38.3c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05z" /></svg>
        <span class="font-semibold text-md tracking-tight" />
      </div>
      <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
        <div class="text-md lg:flex-grow flex flex-nowrap whitespace-nowrap">
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/pracownicy/${budId}/`">
            <div :class="isUrl('pracownicy') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">Pracownicy</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/budowy/${budId}/kierownictwo`">
            <div :class="isUrl('kierownictwo') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">Kierownictwo</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/budowy/${budId}/edit/`">
            <div :class="isUrl('edit') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">Dane budowy</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/building/${budId}/time-sheet/`">
            <div :class="isUrl('time-sheet') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">KCP budowy</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/budowy/${budId}/klient/`">
            <div :class="isUrl('klient') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">Dane klienta</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/budowy/${budId}/narzedzia/`">
            <div :class="isUrl('narzedzia') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">Sprzęt</div>
          </Link>
          <Link class="block mt-0 lg:inline-block mr-4" :href="`/budowy/${budId}/a1/`">
            <div :class="isUrl('a1') ? 'text-green-800 font-bold' : 'text-indigo-300 hover:text-indigo-600'">A1</div>
          </Link>
        </div>
      </div>
    </nav>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  name: 'WorkerMenu',
  components: {
    Link,
  },
  props: {
    budId:Number,
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') {
        return currentUrl === ''
      }
      return urls.filter((url) => currentUrl.includes(url)).length
    },
  },
}
</script>
