<template>
  <div class="hidden overflow-x-auto lg:block">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left font-bold border-b">
          <!-- Numer Projektu -->
          <th class="pb-4 pt-6 px-4 cursor-pointer select-none whitespace-nowrap" @click="emitSort('numerBud')">
            Numer Projektu
            <SortIcon column="numerBud" :sort="sort" :direction="direction" />
          </th>

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

          <!-- Kierownik projektu — wpisywany ręcznie, to nie pracownik z bazy -->
          <th class="hidden pb-4 pt-6 px-4 xl:table-cell">Kierownik projektu</th>

          <!-- Pracownicy -->
          <th class="pb-4 pt-6 px-4 cursor-pointer select-none" colspan="2" @click="emitSort('active_workers_count')">
            Pracownicy
            <SortIcon column="active_workers_count" :sort="sort" :direction="direction" />
          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="organization in organizations" :key="organization.id" class="focus-within:bg-gray-100" :class="canOpen(organization) ? 'hover:bg-gray-100' : 'opacity-60 pointer-events-none select-none'">
          <!-- Numer Projektu -->
          <td class="border-t whitespace-nowrap">
            <Link class="flex items-center px-4 py-3 font-semibold text-gray-800" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              {{ organization.numerBud || '—' }}
            </Link>
          </td>

          <!-- Nazwa -->
          <td class="border-t">
            <Link class="flex items-center px-4 py-3 focus:text-indigo-500 font-medium" :href="`/budowy/${organization.id}/edit`">
              <span v-if="organization.is_active" class="flex-shrink-0 mr-2" title="Aktywna budowa" aria-label="Aktywna budowa">🟢</span>
              <span v-else-if="!canOpen(organization)" class="flex-shrink-0 mr-2" title="Budowa zamknięta — tylko podgląd" aria-label="Budowa zamknięta">🔒</span>
              {{ organization.nazwaBud }}
                <span
                  v-if="organization.warsztat"
                  class="ml-2 px-2 py-0.5 text-[10px] font-semibold text-indigo-800 bg-indigo-100 border border-indigo-200 rounded-full"
                >
                  warsztat
                </span>
              <Icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              <!-- Wszyscy pracownicy zakończyli pobyt — budowę można zarchiwizować. -->
              <span
                v-if="!organization.deleted_at && organization.ready_to_archive"
                class="flex-shrink-0 ml-2 px-2 py-0.5 text-[10px] font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded-full"
                title="Wszyscy pracownicy zakończyli pobyt — budowę można zarchiwizować"
              >
                do archiwizacji
              </span>
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

          <!-- Kierownik projektu -->
          <td class="hidden border-t xl:table-cell">
            <Link class="flex items-center px-4 py-3" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <span v-if="organization.kierownik_projektu" class="text-gray-700 text-xs">{{ organization.kierownik_projektu }}</span>
              <span v-else class="text-gray-300 text-xs">—</span>
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
          <td class="px-6 py-4 border-t" colspan="8">Brak danych.</td>
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
    canOpen(org) {
      const p = (this.$page && this.$page.props && this.$page.props.permissions) || {}
      return !!org.is_active || !!p.admin || !!p.biuro
    },
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
