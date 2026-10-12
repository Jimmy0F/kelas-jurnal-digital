<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassJournal;
use App\Models\MaterialJournal;
use App\Models\Schedule;
use App\Models\Notification;
use App\Models\AuditLog;
use Carbon\Carbon;

class JournalController extends Controller
{
    public function getClassJournals(Request $request)
    {
        $classId = $request->input('class_id');
        $query = ClassJournal::with(['schoolClass', 'schedule.subject', 'createdBy', 'confirmedBy'])->latest('date');

        if ($classId) {
            $query->where('class_id', $classId);
        }

        $schedules = Schedule::with('subject', 'teacher')->get();

        $journals = $query->get()->map(function ($j) {
            return [
                'id' => $j->id,
                'date' => Carbon::parse($j->date)->format('Y-m-d'),
                'date_time' => Carbon::parse($j->date)->translatedFormat('d M,') . ' ' . ($j->schedule->start_time ?? '07:30'),
                'class' => $j->schoolClass->name ?? 'XI RPL 1',
                'teacher' => $j->confirmedBy->name ?? ($j->schedule->teacher->name ?? 'Budi Santoso, S.Pd'),
                'subject' => $j->schedule->subject->name ?? 'Pemrograman Web',
                'topic' => $j->notes ? explode(' | ', $j->activity)[0] : $j->activity,
                'activity' => $j->activity,
                'notes' => $j->notes,
                'status' => $j->verification_status,
                'confirmed_at' => $j->confirmed_at ? Carbon::parse($j->confirmed_at)->format('d M Y H:i') : null,
                'confirmed_by' => $j->confirmedBy->name ?? null,
            ];
        });

        return response()->json([
            'data' => $journals,
            'schedules' => $schedules,
        ]);
    }

    public function storeClassJournal(Request $request)
    {
        $user = $request->user();
        $schedule = Schedule::find($request->schedule_id);

        $classId = $request->class_id ?? ($schedule->class_id ?? ($user->class_id ?? 1));
        $date = $request->date ?? Carbon::today()->toDateString();
        $activity = $request->topic ? ($request->topic . ' - ' . $request->activity) : $request->activity;

        $journal = ClassJournal::create([
            'class_id' => $classId,
            'schedule_id' => $request->schedule_id,
            'date' => $date,
            'activity' => $activity,
            'notes' => $request->notes,
            'verification_status' => 'PENDING',
            'created_by_id' => $user->id,
        ]);

        // Notify teacher of the schedule
        $schedule = Schedule::find($request->schedule_id);
        if ($schedule && $schedule->teacher_id) {
            Notification::create([
                'user_id' => $schedule->teacher_id,
                'type' => 'JURNAL',
                'title' => 'Jurnal Kelas Baru Menunggu Konfirmasi',
                'body' => 'Sekretaris telah mengisi jurnal kelas untuk ' . ($schedule->subject->name ?? 'mapel Anda') . '.',
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengisi Jurnal Kelas tanggal ' . $request->date,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Jurnal kelas berhasil dikirim dan menunggu verifikasi pengajar',
            'journal' => $journal,
        ]);
    }

    public function confirmClassJournal(Request $request, $id)
    {
        $user = $request->user();
        $journal = ClassJournal::findOrFail($id);

        $journal->update([
            'verification_status' => 'TERVERIFIKASI',
            'confirmed_by_id' => $user->id,
            'confirmed_at' => Carbon::now(),
        ]);

        // Notify secretary
        Notification::create([
            'user_id' => $journal->created_by_id,
            'type' => 'JURNAL',
            'title' => 'Jurnal Kelas Terverifikasi',
            'body' => 'Jurnal kelas Anda telah dikonfirmasi oleh ' . $user->name,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengonfirmasi jurnal kelas ID #' . $journal->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Jurnal kelas berhasil dikonfirmasi']);
    }

    public function getMaterialJournals(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');

        $query = MaterialJournal::with(['subject', 'teacher', 'schoolClass'])->latest('date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', "%{$search}%")
                  ->orWhereHas('subject', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($month) {
            $query->where('date', 'like', "{$month}%");
        }

        $materials = $query->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'date_time' => Carbon::parse($m->date)->translatedFormat('d M, 07:30'),
                'teacher' => $m->teacher->name ?? 'Guru Pengajar',
                'subject' => $m->subject->name ?? 'Mata Pelajaran',
                'topic' => $m->topic,
                'plan' => $m->plan,
                'assignment' => $m->assignment,
                'attachments_count' => $m->attachment_path ? 1 : 2,
            ];
        });

        return response()->json($materials);
    }

    public function storeMaterialJournal(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'class_id' => 'required',
            'subject_id' => 'required',
            'date' => 'required|date',
            'topic' => 'required|string',
            'plan' => 'nullable|string',
            'assignment' => 'nullable|string',
        ]);

        $material = MaterialJournal::create([
            'teacher_id' => $user->id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'schedule_id' => $request->schedule_id,
            'date' => $request->date,
            'topic' => $request->topic,
            'plan' => $request->plan,
            'assignment' => $request->assignment,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Mengirim Rencana Belajar / Materi: ' . $request->topic,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Rencana belajar & materi berhasil disimpan',
            'material' => $material,
        ]);
    }
}
