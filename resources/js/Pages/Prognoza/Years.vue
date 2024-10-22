<template>
  <div>
    <div class="bg-white rounded-md shadow flex items-center justify-between p-2 mt-3">
      <label class="block font-bold">Wybierz rok</label>
    </div>
    <div class="bg-white rounded-md shadow flex items-center justify-between p-3">
      <Link
        class="focus:text-indigo-500"
      >
        <button class="btn btn-indigo" @click="handleSelect()"> Wszystkie lata </button>
      </Link>
      <Link
        v-for="item in data"
        :key="item"
        class="focus:text-indigo-500"
        :href="buildHref(item)"
      >
        <button class="btn btn-indigo"> {{ item }} </button>
      </Link>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: {
    Link,
  },
  props: {
    data: Array,
  },
  methods: {
    buildHref(year) {
      const url = new URL(window.location.href)
      url.searchParams.set('year', year)
      url.searchParams.delete('month')
      return url.pathname + url.search
    },
    handleSelect() {
      const url = new URL(window.location.href)
      url.searchParams.delete('year')
      url.searchParams.delete('month')
      window.location = url
    },
  },
}
</script>
