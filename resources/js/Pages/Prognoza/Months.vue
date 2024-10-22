<template>
  <div>
    <div class="bg-white rounded-md shadow flex items-center justify-between p-2 mt-3">
      <label class="block font-bold">Wybierz miesiąc</label>
    </div>
    <div class="bg-white rounded-md shadow flex items-center justify-between p-2 my-0">
      <Link v-for="item in data" :key="item" class="focus:text-indigo-500" :href="buildHref(item)">
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
    buildHref(month) {
      const url = new URL(window.location.href)
      url.searchParams.set('month', month)
      return url.pathname + url.search
    },
  },
}
</script>
