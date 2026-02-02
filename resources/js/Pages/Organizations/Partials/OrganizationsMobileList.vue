<template>
  <div class="md:hidden divide-y divide-gray-200">
    <Link
      v-for="organization in organizations"
      :key="organization.id"
      :href="`/budowy/${organization.id}/edit`"
      class="block p-4 hover:bg-gray-50 focus:outline-none focus:bg-gray-50"
    >
      <div class="font-semibold">
        {{ organization.nazwaBud }}
        <Icon v-if="organization.deleted_at" name="trash" class="inline ml-2 w-3 h-3 fill-gray-400" />
      </div>

      <div class="mt-2 text-sm text-gray-600 space-y-1">
        <div v-if="organization.country">
          <span class="text-gray-500">Kraj:</span> {{ organization.country.name }}
        </div>

        <!-- NOWE POLA (stringi z backendu) -->
        <div v-if="organization.kierownicy">
          <span class="text-gray-500">Kierownicy:</span> {{ organization.kierownicy }}
        </div>

        <div v-if="organization.inzynierowie">
          <span class="text-gray-500">Inżynierowie:</span> {{ organization.inzynierowie }}
        </div>

        <div class="pt-2">
          <span class="text-gray-500 mr-2">Aktywni pracownicy:</span>
          <span
            class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded"
            :class="organization.active_workers_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
          >
            {{ organization.active_workers_count }}
          </span>
        </div>
      </div>
    </Link>

    <div v-if="organizations.length === 0" class="p-4 text-sm text-gray-600">
      Brak danych.
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'

export default {
  components: { Link, Icon },
  props: {
    organizations: { type: Array, required: true },
  },
}
</script>
