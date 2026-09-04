<template>
  <div class="md:hidden divide-y divide-gray-200">
    <component
      :is="canOpen(organization) ? 'Link' : 'div'"
      v-for="organization in organizations"
      :key="organization.id"
      :href="canOpen(organization) ? `/budowy/${organization.id}/edit` : null"
      class="block p-4 focus:outline-none"
      :class="canOpen(organization) ? 'hover:bg-gray-50 focus:bg-gray-50' : 'opacity-60'"
    >
      <div class="font-semibold">
        <span v-if="organization.is_active" class="mr-1" title="Aktywna budowa" aria-label="Aktywna budowa">🟢</span>
        <span v-else-if="!canOpen(organization)" class="mr-1" title="Budowa zamknięta — tylko podgląd" aria-label="Budowa zamknięta">🔒</span>
        {{ organization.nazwaBud }}
        <Icon v-if="organization.deleted_at" name="trash" class="inline ml-2 w-3 h-3 fill-gray-400" />
        <span
          v-if="!organization.deleted_at && organization.ready_to_archive"
          class="ml-2 px-2 py-0.5 text-[10px] font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded-full"
        >
          do archiwizacji
        </span>
      </div>

      <div class="mt-2 text-sm text-gray-600 space-y-1">
        <div v-if="organization.numerBud">
          <span class="text-gray-500">Numer Projektu:</span> {{ organization.numerBud }}
        </div>
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

        <div v-if="organization.kierownik_projektu">
          <span class="text-gray-500">Kierownik projektu:</span> {{ organization.kierownik_projektu }}
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
    </component>

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
  methods: {
    canOpen(org) {
      const p = (this.$page && this.$page.props && this.$page.props.permissions) || {}
      return !!org.is_active || !!p.admin || !!p.biuro
    },
  },
}
</script>
