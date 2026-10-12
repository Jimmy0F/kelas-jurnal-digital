<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Jurnal & Materi Pembelajaran</h1>
        <p class="text-sm text-gray-500 mt-1">
          Konfirmasi jurnal aktivitas kelas dan kelola rencana pembelajaran untuk siswa
        </p>
      </div>

      <!-- Tab Switcher -->
      <div class="flex bg-gray-100 p-1 rounded-xl self-start md:self-auto">
        <button
          @click="activeTab = 'confirm'"
          :class="[
            'px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5',
            activeTab === 'confirm' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'
          ]"
        >
          <CheckSquareIcon class="w-4 h-4 text-brand-red" />
          Konfirmasi Jurnal Siswa
          <span
            v-if="pendingCount > 0"
            class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-red-100 text-brand-red font-bold"
          >
            {{ pendingCount }}
          </span>
        </button>
        <button
          @click="activeTab = 'material'"
          :class="[
            'px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5',
            activeTab === 'material' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'
          ]"
        >
          <FileTextIcon class="w-4 h-4 text-brand-red" />
          Rencana & Materi Belajar
        </button>
      </div>
    </div>

    <!-- Alert / Message -->
    <div v-if="successMsg" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm font-medium">
        <CheckCircle2Icon class="w-5 h-5 text-green-600" />
        {{ successMsg }}
      </div>
      <button @click="successMsg = ''" class="text-green-500 hover:text-green-700 text-sm">Tutup</button>
    </div>

    <!-- TAB 1: KONFIRMASI JURNAL KELAS -->
    <div v-if="activeTab === 'confirm'" class="space-y-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
          <div>
            <h2 class="text-base font-bold text-gray-800">Daftar Jurnal yang Perlu Dikonfirmasi</h2>
            <p class="text-xs text-gray-400 mt-0.5">Verifikasi laporan kegiatan yang diinput sekretaris kelas</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="fetchData"
              class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 flex items-center gap-1 font-medium"
            >
              <RefreshCwIcon class="w-3.5 h-3.5" /> Segarkan
            </button>
          </div>
        </div>

        <div v-if="loading" class="py-12 text-center text-gray-400 text-sm">
          Memuat data jurnal...
        </div>

        <div v-else-if="classJournals.length === 0" class="py-12 text-center text-gray-400 text-sm">
          <CheckSquareIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
          Tidak ada jurnal yang menunggu konfirmasi saat ini.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="j in classJournals"
            :key="j.id"
            class="border rounded-2xl p-4 md:p-5 transition hover:border-gray-200"
            :class="j.status === 'PENDING' ? 'bg-amber-50/20 border-amber-200/60' : 'bg-white border-gray-100'"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded">
                    {{ j.class }}
                  </span>
                  <span class="text-xs font-semibold text-brand-red">{{ j.subject }}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                  Tanggal: {{ j.date_time }}
                </div>
              </div>
              <div class="flex items-center gap-3">
                <StatusBadge :status="j.status" />
                <button
                  v-if="j.status === 'PENDING'"
                  @click="confirmJournal(j.id)"
                  :disabled="confirmingId === j.id"
                  class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
                >
                  <CheckIcon class="w-4 h-4" />
                  {{ confirmingId === j.id ? 'Memproses...' : 'Konfirmasi & Setujui' }}
                </button>
              </div>
            </div>

            <div class="mt-3 text-sm text-gray-700 space-y-1">
              <div>
                <span class="font-semibold text-gray-800">Kegiatan / Materi:</span>
                <p class="text-gray-600 mt-0.5 text-xs md:text-sm whitespace-pre-line">{{ j.activity }}</p>
              </div>
              <div v-if="j.notes" class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-100 mt-2">
                <span class="font-semibold">Catatan dari Sekretaris:</span> {{ j.notes }}
              </div>
            </div>

            <div v-if="j.confirmed_at" class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
              <span class="text-green-600 font-medium">✓ Dikonfirmasi oleh {{ j.confirmed_by || 'Anda' }}</span>
              <span>Waktu: {{ j.confirmed_at }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: MATERI & RENCANA BELAJAR -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Form Input Materi (Kiri) -->
      <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6 space-y-4">
        <div class="border-b border-gray-100 pb-3">
          <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <UploadCloudIcon class="w-5 h-5 text-brand-red" />
            Upload Rencana Belajar
          </h2>
          <p class="text-xs text-gray-400 mt-0.5">Bagikan pokok bahasan dan tugas kepada siswa</p>
        </div>

        <form @submit.prevent="submitMaterial" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Kelas & Mata Pelajaran
            </label>
            <div class="grid grid-cols-2 gap-2">
              <select
                v-model="materialForm.class_id"
                class="px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red bg-white"
                required
              >
                <option value="1">XI RPL 1</option>
              </select>
              <select
                v-model="materialForm.subject_id"
                class="px-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red bg-white"
                required
              >
                <option value="1">Pemrograman Web</option>
                <option value="2">Basis Data</option>
                <option value="3">PKL</option>
                <option value="4">Kewirausahaan</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Pokok Bahasan / Topik
            </label>
            <input
              v-model="materialForm.topic"
              type="text"
              placeholder="Contoh: Desain Sistem Basis Data Relasional & Normalisasi"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Rencana / Alur Pembelajaran
            </label>
            <textarea
              v-model="materialForm.plan"
              rows="3"
              placeholder="Rincian subtopik yang akan dibahas pada pertemuan ini..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition resize-none"
              required
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
              Tugas Siswa <span class="text-gray-400 font-normal">(Opsional)</span>
            </label>
            <textarea
              v-model="materialForm.assignment"
              rows="2"
              placeholder="Instruksi tugas mandiri / kelompok yang wajib dikumpulkan..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition resize-none"
            ></textarea>
          </div>

          <button
            type="submit"
            :disabled="submittingMaterial"
            class="w-full py-2.5 px-4 rounded-xl bg-brand-red hover:bg-brand-red-dark text-white font-medium text-sm transition shadow-sm hover:shadow flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <SendIcon class="w-4 h-4" />
            <span>{{ submittingMaterial ? 'Menyimpan...' : 'Publikasikan Materi' }}</span>
          </button>
        </form>
      </div>

      <!-- Daftar Materi (Kanan) -->
      <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6 space-y-4">
        <div class="border-b border-gray-100 pb-3">
          <h2 class="text-base font-bold text-gray-800">Materi Pembelajaran Aktif</h2>
          <p class="text-xs text-gray-400 mt-0.5">Daftar modul yang dapat dibaca oleh seluruh siswa di kelas</p>
        </div>

        <div v-if="materials.length === 0" class="py-12 text-center text-gray-400 text-sm">
          Belum ada materi pembelajaran yang dipublikasikan.
        </div>

        <div v-else class="space-y-3.5">
          <div
            v-for="mat in materials"
            :key="mat.id"
            class="p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition bg-gray-50/50 space-y-2.5"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[11px] font-bold text-brand-red bg-red-50 px-2 py-0.5 rounded">
                  {{ mat.subject }}
                </span>
                <h3 class="text-sm font-bold text-gray-900 mt-1">
                  {{ mat.topic }}
                </h3>
              </div>
              <span class="text-xs text-gray-400">{{ mat.date_time }}</span>
            </div>

            <p class="text-xs text-gray-600 line-clamp-2">
              {{ mat.plan }}
            </p>

            <div v-if="mat.assignment" class="text-xs text-blue-700 bg-blue-50 p-2.5 rounded-lg border border-blue-100">
              <span class="font-semibold">Tugas:</span> {{ mat.assignment }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  CheckSquare as CheckSquareIcon,
  FileText as FileTextIcon,
  CheckCircle2 as CheckCircle2Icon,
  RefreshCw as RefreshCwIcon,
  Check as CheckIcon,
  UploadCloud as UploadCloudIcon,
  Send as SendIcon
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()

const activeTab = ref<'confirm' | 'material'>('confirm')
const loading = ref(false)
const confirmingId = ref<number | null>(null)
const submittingMaterial = ref(false)
const successMsg = ref('')

const classJournals = ref<any[]>([])
const materials = ref<any[]>([])

const pendingCount = computed(() => {
  return classJournals.value.filter(j => j.status === 'PENDING').length
})

const materialForm = ref({
  class_id: '1',
  subject_id: '1',
  date: new Date().toISOString().slice(0, 10),
  topic: '',
  plan: '',
  assignment: ''
})

const fetchData = async () => {
  loading.value = true
  try {
    const [journalRes, materialRes]: [any, any] = await Promise.all([
      $fetch(`${config.public.apiBase}/journals/class`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      }),
      $fetch(`${config.public.apiBase}/journals/material`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
    ])
    classJournals.value = journalRes.data || journalRes || []
    materials.value = materialRes || []
  } catch (err: any) {
    console.error('Error fetching data:', err)
  } finally {
    loading.value = false
  }
}

const confirmJournal = async (id: number) => {
  confirmingId.value = id
  successMsg.value = ''
  try {
    await $fetch(`${config.public.apiBase}/journals/class/${id}/confirm`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = 'Jurnal kelas berhasil dikonfirmasi dan diverifikasi!'
    await fetchData()
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal mengonfirmasi jurnal')
  } finally {
    confirmingId.value = null
  }
}

const submitMaterial = async () => {
  submittingMaterial.value = true
  successMsg.value = ''
  try {
    await $fetch(`${config.public.apiBase}/journals/material`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: materialForm.value
    })
    successMsg.value = 'Rencana pembelajaran & materi berhasil dipublikasikan!'
    materialForm.value.topic = ''
    materialForm.value.plan = ''
    materialForm.value.assignment = ''
    await fetchData()
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal mempublikasikan materi')
  } finally {
    submittingMaterial.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
