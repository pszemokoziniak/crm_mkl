<template>
  <div class="hidden overflow-x-auto lg:block">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left font-bold border-b">
          <!-- Nazwa -->
          <th class="pb-4 pt-6 px-4 cursor-pointer select-none" @click="emitSort('nazwaBud')">
            Nazwa
            <SortIcon column="nazwaBud" :sort="sort" :direction="direction" />
          </th>

          <!-- Kraj -->
          <th class="pb-4 pt-6 px-4 cursor-pointer select-none" @click="emitSort('country')">
            Kraj
            <SortIcon column="country" :sort="sort" :direction="direction" />
          </th>

          <!-- Kierownicy -->
          <th class="pb-4 pt-6 px-4 lg:table-cell">Kierownicy</th>

          <!-- Inżynierowie -->
          <th class="pb-4 pt-6 px-4 lg:table-cell">Inżynierowie</th>

          <!-- Pracownicy -->
          <th class="pb-4 pt-6 px-4 cursor-pointer select-none" colspan="2" @click="emitSort('active_workers_count')">
            Pracownicy
            <SortIcon column="active_workers_count" :sort="sort" :direction="direction" />
          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="organization in organizations" :key="organization.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <!-- Nazwa -->
          <td class="border-t">
            <Link class="flex items-center px-4 py-3 focus:text-indigo-500 font-medium" :href="`/budowy/${organization.id}/edit`">
              {{ organization.nazwaBud }}
              <Icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>

          <!-- Kraj -->
          <td class="border-t whitespace-nowrap">
            <Link class="flex items-center px-4 py-3" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div>{{ organization.country?.name ?? '' }}</div>
            </Link>
          </td>

          <!-- Kierownicy -->
          <td class="hidden border-t lg:table-cell">
            <Link class="flex items-center px-4 py-3" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.kierownicy" class="max-w-[250px]" :title="organization.kierownicy">
                <p v-for="(name, idx) in splitComma(organization.kierownicy)" :key="idx" class="text-gray-700 text-xs leading-tight mb-1 last:mb-0">
                  {{ name }}
                </p>
              </div>
              <div v-else class="text-gray-400 text-xs">—</div>
            </Link>
          </td>

          <!-- Inżynierowie -->
          <td class="hidden border-t lg:table-cell">
            <Link class="flex items-center px-4 py-3" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.inzynierowie" class="max-w-[250px]">
                <p v-for="(name, idx) in splitComma(organization.inzynierowie)" :key="idx" class="text-gray-700 text-xs leading-tight mb-1 last:mb-0">
                  {{ name }}
                </p>
              </div>
              <div v-else class="text-gray-400 text-xs">—</div>
            </Link>
          </td>

          <!-- Pracownicy -->
          <td class="border-t whitespace-nowrap">
            <Link class="flex items-center px-4 py-3" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded" :class="organization.active_workers_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                {{ organization.active_workers_count }}
              </span>
            </Link>
          </td>

          <!-- Chevron -->
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <Icon name="cheveron-right" class="block w-5 h-5 fill-gray-400" />
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
