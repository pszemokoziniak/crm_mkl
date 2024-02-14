<template>
  <Head title="KCP" />
  <BudMenu :budId="build" />
  <div class="flex items-center justify-between mb-6">
    <h1 class="mb-8 text-3xl font-bold">KCP</h1>
    <button class="btn-indigo" @click="printData">Drukuj</button>
  </div>
  <div ref="printTable" class="bg-white rounded-lg shadow overflow-auto grid flex py-2 px-6">
    <div class="flex items-center py-2">
      <button
        type="button"
        class="leading-none rounded-lg transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 items-center"
        @click="previousMonth()"
      >
        <svg
          class="h-6 w-6 text-gray-500 inline-flex leading-none" fill="none" viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <div class="border-r inline-flex h-6" />
      <button
        type="button"
        class="leading-none rounded-lg transition ease-in-out duration-100 inline-flex items-center cursor-pointer hover:bg-gray-200 p-1"
        @click="nextMonth()"
      >
        <svg
          class="h-6 w-6 text-gray-500 inline-flex leading-none" fill="none" viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
      <span class="text-lg font-bold text-gray-800">{{ month.toUpperCase() }}</span>
      <span class="ml-1 text-lg text-gray-600 font-normal">{{ date.getFullYear() }}</span>
    </div>
    <div v-for="timeSheet in timeSheets" :key="timeSheet.id" class="flex border-t border-l" :class="(Object.keys(timeSheets).length === 1) ? 'border-b' : '' ">
      <div class="sticky left-0 px-4 pt-2 border-r border-1 relative cursor-pointer text-gray-500 bg-gray-100 z-10" style="width: 127px; height: 68px;">
        <div class="text-sm text-center">{{ timeSheet[1].name }}</div>
        <div class="text-sm text-center">Suma: {{ formatRangeToDisplay(summarize(timeSheet)) }}</div>
      </div>
      <div v-for="shift in timeSheet" :key="shift.id" :class="shiftBackground(shift)" class="text-sm px-4 pt-2 border-r border-1 hover:bg-gray-200 relative cursor-pointer text-gray-500" style="width: 127px; height: 68px;" @click="showModal(shift)">
        <div class="flex justify-between">
          <div class="inline-flex items-center justify-center cursor-pointer text-center leading-none rounded-full text-gray-700 text-sm">{{ (new Date(shift.day)).getDate() }}</div>
          <div class="inline-flex items-center justify-center cursor-pointer text-center leading-none rounded-full text-gray-700 text-sm">{{ dayOfWeek(new Date(shift.day)) }}</div>
        </div>
        <div v-if="shift.isBlocked && shift.blockedType === 'feast'" class="overflow-y-auto mt-1 text-center" style="height: 60px;">
          {{ (shift.status === 8) ? getStatusName(shift.status) : 'Święto' }}
        </div>
        <div v-if="shift.status" class="overflow-y-auto mt-1 text-center" style="height: 60px;">
          {{ getStatusName(shift.status) }}
        </div>
        <div v-if="!shift.status" class="overflow-y-auto mt-1" style="height: 60px;">
          {{ formatTimeRange(shift.from) }} - {{ formatTimeRange(shift.to) }} <br />
          <div class="text-sm text-center">{{ shift.work }}</div>
        </div>
      </div>
    </div>

    <div v-if="timeSheets.length > 0" class="text-sm px-4 pt-2 border-r border-1 border hover:bg-gray-200 relative cursor-pointer text-gray-500" style="width: 127px; height: 68px;">
      <div class="overflow-y-auto mt-1" style="height: 60px;">
        <div class="text-sm text-center">Suma: </div>
      </div>
    </div>

    <div v-if="timeSheets.length < 1" class="flex px-4 pt-2">
      Brak pracowników
    </div>
  </div>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="open = false">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>
      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900"> Wprowadź dane dla dnia: </DialogTitle>
                    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
                      <fieldset :disabled="disabled == 0">
                        <form @submit.prevent="update">
                          <div class="flex flex-wrap -mb-8 -mr-6 p-8">
                            <Datepicker v-model="form.from" :disabled="isStatus" time-picker minutes-increment="30" class="pb-8 pr-6 w-full lg:w-1/2" @update:modelValue="calculateEffectiveTime" />
                            <Datepicker v-model="form.to" :disabled="isStatus" time-picker minutes-increment="30" class="pb-8 pr-6 w-full lg:w-1/2" @update:modelValue="calculateEffectiveTime" />

                            <div class="grid grid-cols-2">
                              <Datepicker v-model="form.workTime" time-picker minutes-increment="30"
                                          class="pb-8 pr-6 w-full"/>
                              <div>
                                <label for="time-reduce">Skróć czas pracy</label>
                                <input id="time-reduce" type="checkbox" :value="false" />
                              </div>
                            </div>

                            <select-input v-model="form.status" class="pb-8 pr-6 w-full lg:w-1/1" label="Powód nieobecności" @change="statusChanged($event)">
                              <option v-for="status in shiftStatuses" :key="status.id" :value="status.id">{{ status.title }}({{ status.code }})</option>
                            </select-input>
                          </div>
                        </form>
                      </fieldset>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm" @click="saveHours()">Zapisz</button>
                <button ref="cancelButtonRef" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="open = false">Anuluj</button>
                <button class="text-red-600 hover:underline mr-auto" tabindex="-1" type="button" @click="destroy">Usuń</button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Layout from '@/Shared/Layout'
import moment from 'moment'
import axios from 'axios'
import SelectInput from '@/Shared/SelectInput'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import BudMenu from '@/Shared/BudMenu.vue'
import {Head} from '@inertiajs/inertia-vue3'


const DEFAULT_RANGES = {
  from: { hours: '07', minutes: '00'},
  to: { hours: '17', minutes: '00'},
  shift: { hours: '09', minutes: '30'},
}

export default {
  components: {
    BudMenu,
    SelectInput,
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
    Datepicker,
    Head,
  },
  layout: Layout,
  props: {
    build: Number,
    timeSheets: Array,
    date: Date,
    month: String,
    shiftStatuses: Array,
  },
  data() {
    return {
      date: new Date(this.date),
      open: false,
      isStatus: false,
      form: this.$inertia.form({
        id: null,
        day: null,
        from: null,
        to: null,
        workTime: null,
        status: null,
        isBlocked: null,
        blockedType: null,
        name: null,
      }),
    }
  },
  /**
   *  Calculate worker hour in month
   *
   */
  mounted() {
    this.shiftStatuses.push({
      id: 0,
      title: 'Nie dotyczy',
      code: 'brak',
    })
  },
  methods: {
    printData() {
      var divToPrint=this.$refs.printTable
      var newWin=window.open("")
      newWin.document.write(divToPrint.outerHTML)
      newWin.print()
      newWin.close()
    },
    dayOfWeek(date) {
      return new Intl.DateTimeFormat('pl-PL', { weekday: 'long' }).format(date).slice(0, 3)
    },
    statusChanged(event) {
      this.isStatus = this.isSetStatus(event.target.value)
    },
    isSetStatus(status) {
      return Number(status) !== 0
    },
    getStatusName(statusId) {
      return this.shiftStatuses.find((elem) => elem.id === statusId).code
    },
    summarize(timeShift) {
      const sum = Object.values(timeShift)
        .filter((shift) => !shift.status)
        .filter((shift) => shift.work !== null)
        .map((shift) => shift.work).reduce((agg, elem) => {
          agg += elem ? moment.duration(elem).asMinutes() : 0
          return agg
        }, 0)

      const hours = Math.floor(sum / 60)
      const minutes = sum - (60 * hours)

      return {
        hours: hours,
        minutes: minutes,
      }
    },
    previousMonth() {
      const previousMonthNumber = this.getMonthNumber() < 1
      const year = previousMonthNumber ? this.getYear() - 1: this.getYear()

      this.redirect(
        this.dateUrl(this.build, year, previousMonthNumber ? 12 : this.getMonthNumber()),
      )
    },
    nextMonth() {
      const nextMonthNumber = this.getMonthNumber() + 2 > 12
      const year = nextMonthNumber ? this.getYear() + 1: this.getYear()
      this.redirect(
        this.dateUrl(this.build, year, nextMonthNumber ? '01' : this.getMonthNumber() + 2),
      )
    },
    redirect(url) {
      window.location = url
    },
    dateUrl(build, year, month) {
      return `/building/${this.build}/time-sheet?date=${year}-${month.toString().padStart(2, '0')}`
    },
    getMonthNumber() {
      return new Date(this.date).getMonth()
    },
    getYear() {
      return new Date(this.date).getFullYear()
    },
    formatRangeToDisplay(range) {
      return String(range.hours).padStart(2, '0') + ':' + String(range.minutes).padStart(2, '0')
    },
    /**
     * Formatting from date to hh:mm
     * @param time {string}
     * @returns {string}
     */
    formatTimeRange(time) {
      if (time === null) {
        return ''
      }
      return String((new Date(time)).getHours()).padStart(2, '0') + ':' + String((new Date(time)).getMinutes()).padStart(2, '0')
    },
    /**
     * Formatting from string to object - using by datepicker
     *
     * @param time
     * @returns {string|{hours: string, minutes: string}}
     */
    formatTimeObject(time) {
      if (time === null) {
        return ''
      }
      return {
        hours: String((new Date(time)).getHours()).padStart(2, '0'),
        minutes: String((new Date(time)).getMinutes()).padStart(2, '0'),
      }
    },
    /**
     * @param time string HH:mm
     */
    criticalTime(time) {
      const criticalShiftWork = 570
      return moment.duration(time).asMinutes() > criticalShiftWork
    },
    shiftBackground(shift) {

      if (this.isSunday(shift)) {
        return 'bg-red-200'
      }

      if (shift.status === 8) {
        return 'bg-red-200'
      }

      if (this.isSaturday(shift)) {
        return 'bg-yellow-200'
      }

      if (this.isSetStatus(shift.status)) {
        return 'bg-green-100'
      }

      if (shift.isBlocked) {
        return 'bg-gray-300'
      }

      if (this.criticalTime(shift.work)) {
        return 'bg-red-300'
      }

      return ''
    },
    isSunday(shift) {
      return (new Date(shift.day)).getDay() === 0
    },
    isSaturday(shift) {
      return (new Date(shift.day)).getDay() === 6
    },
    showModal(shift) {
      // exception for feasts
      if (shift.isBlocked && shift.blockedType !== 'feast') {
        return
      }
      // workbrake -30min
      if (shift.work)
      {var d = moment().hours(shift.work.split(':')[0]).minutes(shift.work.split(':')[1]).add(-30, 'minutes').format('hh:mm')}

      this.open = true
      this.form = this.$inertia.form = ({
        name: shift.name,
        build: shift.build,
        id: shift.id ?? null,
        day: shift.day,
        from: this.formatTimeObject(shift.from) ?  this.formatTimeObject(shift.from) : DEFAULT_RANGES.from,
        to: this.formatTimeObject(shift.to) ? this.formatTimeObject(shift.to) : DEFAULT_RANGES.to,
        workTime: shift.work ? { hours: d.split(':')[0], minutes: d.split(':')[1] } : DEFAULT_RANGES.shift,
        status: shift.status ?? null,
      })
      this.isStatus = this.isSetStatus(shift.status)
    },
    /**
     *
     * @param day
     * @param time {hours: Number, minutes: Number}
     * @returns {Date}
     */
    formatModalTimeToDate(day, time) {
      return new Date(day.getFullYear(), day.getMonth(), day.getDate(), time.hours, time.minutes, 0)
    },

    calculateEffectiveTime() {
      const calculated = moment.utc(moment.duration(
        moment(this.form.to.hours + ':' + this.form.to.minutes, 'HH:mm').add(-30, 'minutes').diff(moment(this.form.from.hours + ':' + this.form.from.minutes, 'HH:mm')),
      ).asMilliseconds()).format('HH:mm')

      this.form.workTime = {
        hours: calculated.split(':').at(0),
        minutes: calculated.split(':').at(1),
      }
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        try {
          this.$inertia.post(`/building/${this.build}/time-sheet/delete`, this.form)
        } catch (e) {
          console.error('Bład usnięcie godzin pracy.')
          // @TODO display message with error
          throw e
        }

        this.form = this.$inertia.form = ({
          id: null,
          day: null,
          from: null,
          to: null,
          workTime: null,
        })
        // display notification
        this.open = false
      }
    },
    saveHours() {
      try {
        /**
         * Day (int) is an index for worker day!
         */
        const workerId = this.form.id
        const dayIndex = new Date(this.form.day).getDate()

        /**
         * How to work with callback functions on $inertia
         * @see resources/js/Pages/Users/Edit.vue:73
         */
        axios.post(`/building/${this.build}/time-sheet`,this.form)

        this.timeSheets[workerId][dayIndex] = {
          name: this.form.name,
          id: this.form.id ?? null,
          build: this.form.build,
          day: this.form.day,
          from: this.formatModalTimeToDate(new Date(this.form.day), this.form.from).toString(),
          to: this.formatModalTimeToDate(new Date(this.form.day), this.form.to).toString(),
          work: this.form.workTime.hours + ':' + this.form.workTime.minutes,
          status: this.form.status,
        }

      } catch (e) {
        console.error('Something happen while saving data.')
        // @TODO display message with error
        throw e
      }
      this.form = this.$inertia.form = ({
        id: null,
        day: null,
        from: null,
        to: null,
        workTime: null,
      })
      // display notification
      this.open = false
    },
  },
}
</script>
