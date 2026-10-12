<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Jurnal Kelas</h1>
        <p class="text-sm text-gray-500 mt-1">
          Catat aktivitas kegiatan belajar mengajar harian kelas XI RPL 1
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-50 text-brand-red border border-red-100">
          <CalendarIcon class="w-3.5 h-3.5" />
          {{ todayFormatted }}
        </span>
      </div>
    </div>

    <!-- Alert / Info -->
    <div v-if="successMsg" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm font-medium">
        <CheckCircle2Icon class="w-5 h-5 text-green-600" />
        {{ successMsg }}
      </div>
      <button @click="successMsg = ''" class="text-green-500 hover:text-green-700 text-sm">Tutup</button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Form Input Jurnal (Kiri/Atas) -->
      <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <BookOpenIcon class="w-5 h-5 text-brand-red" />
            Input Jurnal Harian
          </h2>
          <span class="text-xs text-gray-400">XI RPL 1</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Pilih Jadwal / Jam & Mapel -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Jadwal / Jam Pelajaran
            </label>
            <select
              v-model="form.schedule_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition bg-white"
              required
            >
              <option value="" disabled>-- Pilih Jadwal Pembelajaran --</option>
              <option v-for="sch in schedules" :key="sch.id" :value="sch.id">
                {{ sch.subject?.name }} (Jam {{ sch.start_time?.slice(0,5) }} - {{ sch.end_time?.slice(0,5) }})
              </option>
            </select>
          </div>

          <!-- Topik / Materi Pembelajaran -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Materi Pembahasan
            </label>
            <input
              v-model="form.topic"
              type="text"
              placeholder="Contoh: Implementasi Routing & State Management Nuxt 3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition"
              required
            />
          </div>

          <!-- Rangkuman Kegiatan Pembelajaran -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Kegiatan Belajar
            </label>
            <textarea
              v-model="form.activity"
              rows="3"
              placeholder="Jelaskan alur kegiatan pembelajaran di kelas..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition resize-none"
              required
            ></textarea>
          </div>

          <!-- Catatan / Hambatan -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Catatan / Kendala Kelas <span class="text-gray-400 font-normal">(Opsional)</span>
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Misal: Proyektor ruang lab sempat kendala 10 menit, materi dilanjutkan lancar"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition resize-none"
            ></textarea>
          </div>

          <!-- Submit Buttons -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="w-full py-2.5 px-4 rounded-xl bg-brand-red hover:bg-brand-red-dark text-white font-medium text-sm transition shadow-sm hover:shadow flex items-center justify-center gap-2 disabled:opacity-50"
            >
              <SendIcon class="w-4 h-4" />
              <span>{{ loading ? 'Menyimpan...' : 'Kirim Jurnal ke Guru' }}</span>
            </button>
            <p class="text-[11px] text-gray-400 text-center mt-2">
              Jurnal akan dikirimkan ke Guru Pengajar bersangkutan untuk konfirmasi dan verifikasi.
            </p>
          </div>
        </form>
      </div>

      <!-- Riwayat Jurnal Kelas (Kanan/Bawah) -->
      <div class="lg:col-span-7 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6">
          <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div>
              <h2 class="text-base font-bold text-gray-800">Daftar Jurnal Terkirim</h2>
              <p class="text-xs text-gray-400 mt-0.5">Catatan jurnal kelas yang telah dibuat</p>
            </div>
            <button
              @click="fetchJournals"
              class="text-xs text-brand-red hover:underline font-semibold flex items-center gap-1"
            >
              <RefreshCwIcon class="w-3.5 h-3.5" /> Segarkan
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="fetching" class="py-12 text-center text-gray-400 text-sm">
            Memuat data jurnal kelas...
          </div>

          <!-- Empty state -->
          <div v-else-if="journals.length === 0" class="py-12 text-center text-gray-400 text-sm">
            <FileTextIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
            Belum ada jurnal kelas yang dicatat hari ini.
          </div>

          <!-- Journal Cards -->
          <div v-else class="space-y-3.5">
            <div
              v-for="j in journals"
              :key="j.id"
              class="p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition bg-gray-50/50 space-y-3"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-800">
                      {{ j.schedule?.subject?.name || 'Mata Pelajaran' }}
                    </span>
                    <span class="text-[11px] text-gray-400">
                      ({{ j.schedule?.start_time?.slice(0,5) }} - {{ j.schedule?.end_time?.slice(0,5) }})
                    </span>
                  </div>
                  <h3 class="text-sm font-semibold text-gray-900 mt-1">
                    {{ j.topic }}
                  </h3>
                </div>
                <StatusBadge :status="j.status" />
              </div>

              <div class="text-xs text-gray-600 bg-white p-3 rounded-lg border border-gray-100">
                <span class="font-semibold text-gray-700">Kegiatan:</span>
                <p class="mt-0.5 whitespace-pre-line">{{ j.activity }}</p>
                <div v-if="j.notes" class="mt-2 pt-2 border-t border-gray-100 text-amber-700">
                  <span class="font-semibold">Catatan:</span> {{ j.notes }}
                </div>
              </div>

              <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                <span class="flex items-center gap-1">
                  <UserIcon class="w-3 h-3 text-gray-400" />
                  Pengajar: {{ j.schedule?.teacher?.name || '-' }}
                </span>
                <span>{{ j.date }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Calendar as CalendarIcon,
  BookOpen as BookOpenIcon,
  Send as SendIcon,
  RefreshCw as RefreshCwIcon,
  FileText as FileTextIcon,
  CheckCircle2 as CheckCircle2Icon,
  User as UserIcon,
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()

const todayFormatted = computed(() => {
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date())
})

const schedules = ref<any[]>([])
const journals = ref<any[]>([])
const fetching = ref(false)
const loading = ref(false)
const successMsg = ref('')

const form = ref({
  schedule_id: '',
  topic: '',
  activity: '',
  notes: ''
})

const fetchJournals = async () => {
  fetching.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/journals/class`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    journals.value = res.data || []
    schedules.value = res.schedules || []
    if (schedules.value.length > 0 && !form.value.schedule_id) {
      form.value.schedule_id = schedules.value[0].id
    }
  } catch (err: any) {
    console.error('Error fetching journals:', err)
  } finally {
    fetching.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  successMsg.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/journals/class`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      },
      body: form.value
    })
    successMsg.value = 'Jurnal kelas berhasil dikirimkan ke guru pengajar!'
    form.value.topic = ''
    form.value.activity = ''
    form.value.notes = ''
    await fetchJournals()
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal menyimpan jurnal kelas')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchJournals()
})
</script>
