<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Term;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ClassJournal;
use App\Models\MaterialJournal;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\Setting;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database using real data from SMKN 71 Jakarta.
     */
    public function run(): void
    {
        // 1. Term
        $term = Term::firstOrCreate(
            ['name' => '2026/2027 - Ganjil'],
            [
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'effective_days' => 108,
                'is_active' => true,
            ]
        );

        // 2. Settings (SMK Negeri 71 Jakarta)
        Setting::set('school_name', 'SMK Negeri 71 Jakarta', 'general', 'Nama Resmi Sekolah');
        Setting::set('school_npsn', '20104567', 'general', 'Nomor Pokok Sekolah Nasional');
        Setting::set('school_address', 'Jl. Pendidikan Industri No. 45, Jakarta', 'general', 'Alamat Sekolah');
        Setting::set('headmaster_name', 'Dr. H. Bambang Soeprapto, M.Pd', 'general', 'Nama Kepala Sekolah');
        Setting::set('headmaster_nip', '197505101999031002', 'general', 'NIP Kepala Sekolah');
        Setting::set('default_school_hours', '07:30 - 15:30', 'academic', 'Jam Masuk Sekolah Standar');
        Setting::set('attendance_lock_pin', '1234', 'security', 'PIN Buka Kunci Presensi');
        Setting::set('system_status', 'NORMAL', 'system', 'Status Operasional Sistem');

        $dataPath = database_path('data');

        // 3. Classes from JSON
        $classesFile = $dataPath . '/classes.json';
        $classIdMap = [];
        if (file_exists($classesFile)) {
            $classesData = json_decode(file_get_contents($classesFile), true);
            foreach ($classesData as $cls) {
                $createdClass = SchoolClass::updateOrCreate(
                    ['name' => $cls['name']],
                    [
                        'major' => $cls['major'] ?? 'RPL',
                    ]
                );
                $classIdMap[$cls['id']] = $createdClass->id;
            }
        }

        // 4. Users from JSON
        $defaultPassword = Hash::make('password123');
        $usersFile = $dataPath . '/users.json';
        $userIdMap = [];
        if (file_exists($usersFile)) {
            $usersData = json_decode(file_get_contents($usersFile), true);
            foreach ($usersData as $u) {
                $targetClassId = null;
                if (!empty($u['class_id']) && isset($classIdMap[$u['class_id']])) {
                    $targetClassId = $classIdMap[$u['class_id']];
                } elseif (!empty($u['school_class']['name'])) {
                    $clsObj = SchoolClass::where('name', $u['school_class']['name'])->first();
                    $targetClassId = $clsObj?->id;
                }

                $createdUser = User::updateOrCreate(
                    ['email' => $u['email']],
                    [
                        'name' => $u['name'],
                        'password' => $defaultPassword,
                        'phone' => $u['phone'] ?? '081234567890',
                        'nip_nis' => $u['nip_nis'] ?? null,
                        'role' => $u['role'],
                        'specialization' => $u['specialization'] ?? null,
                        'is_active' => $u['is_active'] ?? true,
                        'class_id' => $targetClassId,
                    ]
                );
                $userIdMap[$u['id']] = $createdUser->id;
            }
        }

        // Ensure Superadmin exists
        User::updateOrCreate(
            ['email' => 'superadmin@sekolah.sch.id'],
            [
                'name' => 'Administrator Sistem',
                'password' => $defaultPassword,
                'phone' => '6281299998888',
                'nip_nis' => 'ADMIN001',
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]
        );

        // Update advisor and secretary relationships on classes
        $kelasIndustri = SchoolClass::where('name', 'like', '%Kelas Industri%')->first();
        if ($kelasIndustri) {
            $pembimbingIndustri = User::where('nip_nis', '198706152011012001')->first() // Nurhayatul Fadilla
                ?? User::where('role', 'GURU_PEMBIMBING')->first();
            $sekretarisIndustri = User::where('class_id', $kelasIndustri->id)->where('role', 'SEKRETARIS')->first()
                ?? User::where('nip_nis', '102401')->where('role', 'SEKRETARIS')->first();

            $kelasIndustri->update([
                'advisor_id' => $pembimbingIndustri?->id,
                'secretary_id' => $sekretarisIndustri?->id,
            ]);
        }

        $xiRpl1 = SchoolClass::where('name', 'XI RPL 1')->first();
        if ($xiRpl1) {
            $pembimbingRpl1 = User::where('nip_nis', '19890314002')->first(); // Siti Aminah
            $sekretarisRpl1 = User::where('name', 'Rina Safitri')->first();
            $xiRpl1->update([
                'advisor_id' => $pembimbingRpl1?->id,
                'secretary_id' => $sekretarisRpl1?->id,
            ]);
        }

        // 5. Subjects from JSON
        $subjectsFile = $dataPath . '/subjects.json';
        $subjectIdMap = [];
        if (file_exists($subjectsFile)) {
            $subjectsData = json_decode(file_get_contents($subjectsFile), true);
            foreach ($subjectsData as $sub) {
                $teacherId = (!empty($sub['teacher_id']) && isset($userIdMap[$sub['teacher_id']])) ? $userIdMap[$sub['teacher_id']] : null;
                $createdSub = Subject::updateOrCreate(
                    ['code' => $sub['code']],
                    [
                        'name' => $sub['name'],
                        'major' => $sub['major'] ?? 'RPL',
                        'teacher_id' => $teacherId,
                    ]
                );
                $subjectIdMap[$sub['id']] = $createdSub->id;
            }
        }

        // 6. Schedules from JSON
        $schedulesFile = $dataPath . '/schedules.json';
        if (file_exists($schedulesFile)) {
            $schedulesData = json_decode(file_get_contents($schedulesFile), true);
            foreach ($schedulesData as $sch) {
                $targetClassId = $classIdMap[$sch['class_id']] ?? null;
                $targetSubjectId = $subjectIdMap[$sch['subject_id']] ?? null;
                $targetTeacherId = $userIdMap[$sch['teacher_id']] ?? null;

                if ($targetClassId && $targetSubjectId && $targetTeacherId) {
                    Schedule::updateOrCreate(
                        [
                            'class_id' => $targetClassId,
                            'subject_id' => $targetSubjectId,
                            'day' => $sch['day'],
                            'start_time' => $sch['start_time'],
                        ],
                        [
                            'teacher_id' => $targetTeacherId,
                            'end_time' => $sch['end_time'],
                            'room' => $sch['room'] ?? 'Lab. 1',
                        ]
                    );
                }
            }
        }

        // 7. Generate Attendance Sessions & Records for Kelas Industri and XI RPL 1
        $today = Carbon::today();
        $targetClasses = array_filter([$kelasIndustri, $xiRpl1]);

        foreach ($targetClasses as $cls) {
            $students = User::where('class_id', $cls->id)
                ->whereIn('role', ['SISWA', 'SEKRETARIS'])
                ->get();

            if ($students->isEmpty()) continue;

            $secretary = $students->firstWhere('role', 'SEKRETARIS') ?? $students->first();

            // Create session for today
            $sessionToday = AttendanceSession::firstOrCreate(
                [
                    'class_id' => $cls->id,
                    'date' => $today->toDateString(),
                ],
                [
                    'status' => 'LOCKED',
                    'locked_by_id' => $secretary->id,
                    'locked_at' => $today->copy()->setTime(8, 0),
                    'verification_status' => 'TERVERIFIKASI',
                    'school_hours' => '07:30 - 15:30',
                    'room' => str_contains($cls->name, 'Industri') ? 'Lab RPL Industri' : 'Lab. 1',
                ]
            );

            // Populate records for today
            foreach ($students as $idx => $std) {
                $status = 'HADIR';
                $note = null;
                // Give realistic varied statuses for a couple students
                if ($idx === 3) {
                    $status = 'SAKIT';
                    $note = 'Demam dan flu, surat dokter terlampir';
                } elseif ($idx === 6) {
                    $status = 'IZIN';
                    $note = 'Keperluan keluarga di luar kota';
                } elseif ($idx === 9) {
                    $status = 'ALPHA';
                    $note = 'Belum ada keterangan';
                }

                AttendanceRecord::updateOrCreate(
                    [
                        'session_id' => $sessionToday->id,
                        'student_id' => $std->id,
                    ],
                    [
                        'status' => $status,
                        'note' => $note,
                    ]
                );
            }

            // Create session for yesterday / past day
            $yesterday = $today->copy()->subDay();
            if ($yesterday->isWeekend()) {
                $yesterday = $yesterday->previous(Carbon::FRIDAY);
            }

            $sessionPast = AttendanceSession::firstOrCreate(
                [
                    'class_id' => $cls->id,
                    'date' => $yesterday->toDateString(),
                ],
                [
                    'status' => 'LOCKED',
                    'locked_by_id' => $secretary->id,
                    'locked_at' => $yesterday->copy()->setTime(8, 5),
                    'verification_status' => 'TERVERIFIKASI',
                    'school_hours' => '07:30 - 15:30',
                    'room' => str_contains($cls->name, 'Industri') ? 'Lab RPL Industri' : 'Lab. 1',
                ]
            );

            foreach ($students as $idx => $std) {
                $status = ($idx === 4) ? 'IZIN' : 'HADIR';
                $note = ($idx === 4) ? 'Mengikuti lomba olimpiade TIK' : null;

                AttendanceRecord::updateOrCreate(
                    [
                        'session_id' => $sessionPast->id,
                        'student_id' => $std->id,
                    ],
                    [
                        'status' => $status,
                        'note' => $note,
                    ]
                );
            }
        }

        // 8. Sample Journals for Kelas Industri
        // 8. Sample Journals for Kelas Industri
        if ($kelasIndustri) {
            $bagasTeacher = User::where('nip_nis', '199003202015021002')->first() // Bagas Pratama
                ?? User::where('role', 'GURU_PENGAJAR')->first();
            $secIndustri = User::where('class_id', $kelasIndustri->id)->where('role', 'SEKRETARIS')->first();
            $subjWeb = Subject::where('name', 'like', '%Web%')->first();
            $schedule = Schedule::where('class_id', $kelasIndustri->id)->first();

            if ($bagasTeacher && $secIndustri) {
                ClassJournal::firstOrCreate(
                    [
                        'class_id' => $kelasIndustri->id,
                        'date' => $today->toDateString(),
                    ],
                    [
                        'schedule_id' => $schedule?->id,
                        'activity' => 'Praktikum pembuatan REST API dan integrasi antarmuka frontend Vue/Nuxt di Lab RPL.',
                        'notes' => 'Praktikum berjalan tertib dan seluruh siswa kelas industri aktif berpartisipasi.',
                        'verification_status' => 'TERVERIFIKASI',
                        'created_by_id' => $secIndustri->id,
                        'confirmed_by_id' => $bagasTeacher->id,
                        'confirmed_at' => now(),
                    ]
                );

                if ($subjWeb) {
                    MaterialJournal::firstOrCreate(
                        [
                            'teacher_id' => $bagasTeacher->id,
                            'class_id' => $kelasIndustri->id,
                            'subject_id' => $subjWeb->id,
                            'date' => $today->toDateString(),
                        ],
                        [
                            'schedule_id' => $schedule?->id,
                            'topic' => 'Arsitektur REST API & Fullstack Modern',
                            'plan' => 'Siswa mempraktikkan routing, state management Pinia, dan validasi request autentikasi.',
                            'assignment' => 'Selesaikan modul latihan integrasi endpoint kelas di repositori GitHub.',
                        ]
                    );
                }
            }
        }

        // 9. Sample Leave Request from a real student
        if ($kelasIndustri) {
            $studentDira = User::where('name', 'Dira')->where('class_id', $kelasIndustri->id)->first()
                ?? User::where('class_id', $kelasIndustri->id)->where('role', 'SISWA')->first();
            $pembimbing = User::find($kelasIndustri->advisor_id);
            $secIndustri = User::where('class_id', $kelasIndustri->id)->where('role', 'SEKRETARIS')->first();

            if ($studentDira && $pembimbing) {
                LeaveRequest::firstOrCreate(
                    [
                        'student_id' => $studentDira->id,
                        'start_date' => $today->toDateString(),
                    ],
                    [
                        'submitted_by_id' => $secIndustri?->id ?? $studentDira->id,
                        'type' => 'SAKIT',
                        'end_date' => $today->copy()->addDay()->toDateString(),
                        'reason' => 'Mengalami demam dan disarankan dokter untuk istirahat selama 2 hari.',
                        'status' => 'PENDING',
                    ]
                );
            }
        }

        // 10. Initial Audit Log
        AuditLog::create([
            'user_id' => 1,
            'action' => 'Inisialisasi & sinkronisasi data master riil SMKN 71 Jakarta',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
