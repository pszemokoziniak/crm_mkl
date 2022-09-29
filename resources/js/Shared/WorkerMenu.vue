<template>
  <div>
    <nav class="flex items-center justify-between flex-wrap bg-teal-500 p-6">
      <div class="flex items-center flex-shrink-0 text-indigo-500 mr-6">
        <svg class="fill-current h-4 w-4 mr-2" width="54" height="54" viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 22.1c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05zM0 38.3c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05z"/></svg>
        <span class="font-semibold text-md tracking-tight"></span>
      </div>
      <div class="block lg:hidden">
        <button class="flex items-center px-3 py-2 border rounded text-teal-200 border-teal-400 hover:text-white hover:border-white">
          <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
        </button>
      </div>
      <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
        <div class="text-md lg:flex-grow">
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/edit/`">
            <div :class="isUrl('badania') ? 'text-green-800' : 'text-indigo-300 group-hover:text-gray-900'">Dane osobowe</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/badania/`">
            <div :class="isUrl('badania') ? 'text-green-800' : 'text-indigo-300 group-hover:text-gray-900'">Badania lekarskie</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/bhp/`">
            <div :class="isUrl('bhp') ? 'text-gray-500' : 'text-indigo-300 group-hover:text-gray-900'">Szkolenia BHP</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/uprawnienia/`">
            <div :class="isUrl('uprawnienia') ? 'text-gray-500' : 'text-indigo-300 group-hover:text-gray-900'">Uprawnienia</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/documents`">
            <div :class="'text-indigo-300 group-hover:text-gray-900'">Dokumenty</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/jezyk/`">
            <div :class="'text-indigo-300 group-hover:text-gray-900'">Języki</div>
          </Link>
          <Link class="block mt-4 lg:inline-block lg:mt-0 mr-4" :href="`/contacts/${contactId}/a1/`">
            <div :class="'text-indigo-300 group-hover:text-gray-900'">A1</div>
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
  props: {
    contactId:Number,
  },
  components: {
    Link,
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') {
        return currentUrl === ''
      }
      return urls.filter((url) => currentUrl.endsWith(url)).length
    },
  },
}
</script>
