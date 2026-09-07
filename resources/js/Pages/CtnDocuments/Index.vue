<template>
  <div>
    <Head title="Dokumenty" />
    <WorkerMenu :contactId="contactId" :userOwner="userOwner" />

    <PracownikNaglowek :contact-id="contactId" :nazwa="pracownik" tytul="Dokumenty" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" class="form-checkbox" :checked="pokazKosz" @change="przelaczKosz" />
        <span>Pokaż usunięte</span>
      </label>
      <Link v-if="!kierownik" class="btn-indigo" :href="`/contacts/${contactId}/documents/create`">
        <span>Dodaj dokument</span>
      </Link>
    </div>

    <SkanyDokumentow
      :contact-id="contactId"
      :documents="documents"
      :kierownik="kierownik"
      :pokaz-typ="true"
      :pokaz-tytul="false"
    />

    <pagination v-if="documents.links" class="mt-4" :links="documents.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import SkanyDokumentow from '@/Shared/SkanyDokumentow'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: { Head, Link, Pagination, PracownikNaglowek, SkanyDokumentow, WorkerMenu },
  layout: Layout,
  props: {
    filters: { type: Object, default: () => ({}) },
    pracownik: { type: String, default: '' },
    contactId: { type: Number, required: true },
    documents: Object,
    userOwner: Number,
  },
  computed: {
    kierownik() {
      return this.userOwner === 3
    },
    pokazKosz() {
      return this.filters.trashed === 'with'
    },
  },
  methods: {
    przelaczKosz(zdarzenie) {
      this.$inertia.get(
        `/contacts/${this.contactId}/documents`,
        zdarzenie.target.checked ? { trashed: 'with' } : {},
        { preserveScroll: true, replace: true }
      )
    },
  },
}
</script>
