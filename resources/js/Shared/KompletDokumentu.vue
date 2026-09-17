<template>
  <!-- Komórka listy kontrolnej: "jest" albo link do dodania. -->
  <span v-if="jest" class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 border border-green-200">jest</span>
  <Link
    v-else
    :href="href"
    class="inline-block px-2 py-0.5 text-xs font-medium rounded-full border"
    :class="opcjonalne ? 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' : 'bg-red-100 text-red-800 border-red-200 hover:bg-red-200'"
  >
    brak — dodaj
  </Link>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: { Link },
  props: {
    jest: Boolean,
    href: { type: String, required: true },
    opcjonalne: Boolean,
  },
}
</script>
