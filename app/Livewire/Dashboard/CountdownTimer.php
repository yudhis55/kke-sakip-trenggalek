<?php

namespace App\Livewire\Dashboard;

use App\Models\Setting;
use App\Models\Tahun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CountdownTimer extends Component
{
    public $deadlineTimestamp = null;
    public $roleLabel = '';
    public $isExpired = false;

    public function mount()
    {
        // Ambil tahun_id dari session atau default ke tahun aktif
        $tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()?->id ?? 1;

        // Set deadline berdasarkan role
        $this->setDeadline($tahun_id);
    }

    public function setDeadline($tahun_id)
    {
        $setting = Setting::where('tahun_id', $tahun_id)->first();

        if (!$setting) {
            $this->isExpired = true;
            return;
        }

        $jenis = Auth::user()->role->jenis;

        // Mapping role ke kolom setting
        $deadlineMap = [
            'opd' => ['tutup_penilaian_mandiri', 'OPD'],
            'verifikator' => ['tutup_penilaian_verifikator', 'Verifikator'],
            'penjamin' => ['tutup_penilaian_penjamin', 'Penjamin'],
            'penilai' => ['tutup_penilaian_penilai', 'Penilai'],
        ];

        if (isset($deadlineMap[$jenis])) {
            $column = $deadlineMap[$jenis][0];
            $this->roleLabel = $deadlineMap[$jenis][1];

            if ($setting->$column) {
                $deadline = Carbon::parse($setting->$column);

                // Cek apakah sudah expired
                if ($deadline->isPast()) {
                    $this->isExpired = true;
                } else {
                    // Convert ke timestamp JavaScript (milliseconds)
                    $this->deadlineTimestamp = $deadline->timestamp * 1000;
                }
            } else {
                $this->isExpired = true;
            }
        } else {
            $this->isExpired = true;
        }
    }

    public function render()
    {
        return <<<'HTML'
            <div class="header-item d-none d-sm-flex me-3"
                x-data="{
                    deadline: @js($deadlineTimestamp),
                    isExpired: @js($isExpired),
                    days: 0,
                    hours: 0,
                    minutes: 0,
                    seconds: 0,

                    init() {
                        if (!this.isExpired && this.deadline) {
                            this.updateCountdown();
                            setInterval(() => this.updateCountdown(), 1000);
                        }
                    },

                    updateCountdown() {
                        const now = Date.now();
                        const distance = this.deadline - now;

                        if (distance < 0) {
                            this.isExpired = true;
                            this.days = 0;
                            this.hours = 0;
                            this.minutes = 0;
                            this.seconds = 0;
                            return;
                        }

                        this.days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        this.hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        this.minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        this.seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    },

                    padZero(num) {
                        return String(num).padStart(2, '0');
                    }
                }">
                <div class="d-flex align-items-center px-3 py-2 rounded"
                    :class="isExpired ? 'bg-danger-subtle' : 'bg-light'">
                    <i class="ri-timer-line fs-20 me-2"
                        :class="isExpired ? 'text-danger' : 'text-primary'"></i>
                    <div class="d-flex flex-column">
                        <span class="fs-11 text-muted mb-1">
                            <template x-if="isExpired">
                                <span>Waktu Input Habis</span>
                            </template>
                            <template x-if="!isExpired">
                                <span>Sisa Waktu Input {{ $roleLabel }}</span>
                            </template>
                        </span>
                        <template x-if="isExpired">
                            <span class="fw-semibold text-danger">DITUTUP</span>
                        </template>
                        <template x-if="!isExpired">
                            <span class="fw-semibold text-dark">
                                <template x-if="days > 0">
                                    <span x-text="days + ' hari '"></span>
                                </template>
                                <span x-text="padZero(hours) + ':' + padZero(minutes) + ':' + padZero(seconds)"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        HTML;
    }
}
