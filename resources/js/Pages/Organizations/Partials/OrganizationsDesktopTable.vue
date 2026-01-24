<template>
  <div class="hidden overflow-x-auto lg:block">
    <table class="w-full whitespace-nowrap">
      <thead>
        <tr class="text-left font-bold">
          <!-- Nazwa -->
          <th class="pb-4 pt-6 px-6 cursor-pointer select-none" @click="emitSort('name')">
            Nazwa
            <SortIcon column="name" :sort="sort" :direction="direction" />
          </th>

          <!-- Kraj (sortowanie opcjonalne - jeśli backend wspiera) -->
          <th class="pb-4 pt-6 px-6 cursor-pointer select-none" @click="emitSort('country')">
            Kraj
            <SortIcon column="country" :sort="sort" :direction="direction" />
          </th>

          <!-- Kierownicy -->
          <th class="pb-4 pt-6 px-6 border-t lg:table-cell">Kierownicy</th>

          <!-- Inżynierowie -->
          <th class="pb-4 pt-6 px-6 border-t lg:table-cell">Inżynierowie</th>

          <!-- Aktywna (sortowanie opcjonalne) -->
          <th class="pb-4 pt-6 px-6 cursor-pointer select-none" colspan="2" @click="emitSort('has_active_workers')">
            Aktywna
            <SortIcon column="has_active_workers" :sort="sort" :direction="direction" />
          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="organization in organizations" :key="organization.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <!-- Nazwa -->
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/edit`">
              {{ organization.name }}
              <Icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>

          <!-- Kraj -->
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div>{{ organization.country?.name ?? '' }}</div>
            </Link>
          </td>

          <!-- Kierownicy -->
          <td class="hidden border-t lg:table-cell">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.kierownicy" class="max-w-[320px]" :title="organization.kierownicy">
                <p v-for="(name, idx) in splitComma(organization.kierownicy)" :key="idx" class="text-gray-700 text-xs leading-tight">
                  {{ name }}
                </p>
              </div>

              <div v-else class="text-gray-400 text-xs">—</div>
            </Link>
          </td>

          <!-- Inżynierowie -->
          <td class="hidden border-t lg:table-cell">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.inzynierowie" class="max-w-[320px]">
                <p v-for="(name, idx) in splitComma(organization.inzynierowie)" :key="idx" class="text-gray-700 text-xs leading-tight">
                  {{ name }}
                </p>
              </div>

              <div v-else class="text-gray-400 text-xs">—</div>
            </Link>
          </td>

          <!-- Aktywna -->
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded" :class="organization.has_active_workers ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                {{ organization.has_active_workers ? 'TAK' : 'NIE' }}
              </span>
            </Link>
          </td>

          <!-- Chevron -->
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <Icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>

        <tr v-if="organizations.length === 0">
          <td class="px-6 py-4 border-t" colspan="6">Brak danych.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import SortIcon from '@/Shared/SortIcon'

export default {
  components: { Link, Icon, SortIcon },
  props: {
    organizations: { type: Array, required: true },
    sort: { type: String, required: true },
    direction: { type: String, required: true },
  },
  emits: ['sort'],
  methods: {
    emitSort(column) {
      this.$emit('sort', column)
    },
    splitComma(value) {
      if (!value) return []
      if (Array.isArray(value)) {
        return value
      }
      return String(value)
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
    },
  },
}
</script>
