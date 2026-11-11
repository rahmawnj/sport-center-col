<?php

namespace Database\\Seeders;

use App\\Models\\ClassSchedule;
use App\\Models\\Facility;
use App\\Models\\SportClass;
use Illuminate\\Database\\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Seed contoh kelas dan jadwal mingguan.
     *
     * Aman dijalankan berulang kali: kelas dan jadwal diperbarui
     * berdasarkan facility, nama kelas, hari, dan jam mulai.
     */
    public function run(): void
    {
        $classes = [
            [
                'facility' => 'yoga',
                'name' => 'Yoga Flow',
                'description' => 'Latihan yoga dinamis untuk meningkatkan fleksibilitas, keseimbangan, dan kekuatan tubuh.',
                'duration' => 60,
                'capacity' => 15,
                'schedules' => [
                    ['monday', '07:00', '08:00', 50000],
                    ['wednesday', '07:00', '08:00', 50000],
                ],
            ],
            [
                'facility' => 'yoga',
                'name' => 'Morning Yoga',
                'description' => 'Sesi yoga santai di pagi hari untuk membantu tubuh lebih rileks dan siap beraktivitas.',
                'duration' => 60,
                'capacity' => 18,
                'schedules' => [
                    ['saturday', '08:00', '09:00', 60000],
                ],
            ],
            [
                'facility' => 'pilates',
                'name' => 'Pilates Mat',
                'description' => 'Latihan mat pilates yang berfokus pada core strength, postur, dan kontrol gerakan.',
                'duration' => 60,
                'capacity' => 12,
                'schedules' => [
                    ['tuesday', '17:00', '18:00', 75000],
                    ['thursday', '17:00', '18:00', 75000],
                ],
            ],
            [
                'facility' => 'spinning',
                'name' => 'Spinning Blast',
                'description' => 'Latihan kardio bersepeda indoor dengan kombinasi tempo dan intensitas yang bervariasi.',
                'duration' => 45,
                'capacity' => 20,
                'schedules' => [
                    ['monday', '18:00', '18:45', 45000],
                    ['wednesday', '18:00', '18:45', 45000],
                    ['friday', '18:00', '18:45', 45000],
                ],
            ],
            [
                'facility' => 'gym',
                'name' => 'Strength & Conditioning',
                'description' => 'Kelas latihan kekuatan dan kebugaran untuk membangun stamina serta kekuatan seluruh tubuh.',
                'duration' => 60,
                'capacity' => 10,
                'schedules' => [
                    ['tuesday', '07:00', '08:00', 65000],
                    ['thursday', '07:00', '08:00', 65000],
                ],
            ],
        ];

        foreach ($classes as $data) {
            $facility = Facility::query()
                ->where('slug', $data['facility'])
                ->where('status', 'active')
                ->first();

            if (! $facility) {
                $this->command?->warn("Class seeder melewati fasilitas '{$data['facility']}' karena tidak ditemukan atau tidak aktif.");

                continue;
            }

            $trainerId = $facility->trainers()
                ->orderBy('trainers.id')
                ->value('trainers.id');

            $class = SportClass::updateOrCreate(
                [
                    'facility_id' => $facility->id,
                    'name' => $data['name'],
                ],
                [
                    'description' => $data['description'],
                    'duration_minutes' => $data['duration'],
                    'default_capacity' => $data['capacity'],
                    'is_active' => true,
                ]
            );

            foreach ($data['schedules'] as [$day, $start, $end, $price]) {
                ClassSchedule::updateOrCreate(
                    [
                        'class_id' => $class->id,
                        'day' => $day,
                        'start_time' => $start,
                    ],
                    [
                        'trainer_id' => $trainerId,
                        'end_time' => $end,
                        'capacity' => $data['capacity'],
                        'price' => $price,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
