<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Models\QuestionBankItemOption;
use Modules\Tenancy\Models\University;

/**
 * Imports the real UTS (Ujian Tengah Semester) question set for SFT 206 -
 * Keamanan Jaringan at Universitas Katolik Indonesia Atma Jaya (dosen
 * pengampu: Eugenius Kau Suni) into the Bank Soal, so the demo dosen
 * account has a populated question bank to build an Ujian from instead of
 * an empty one.
 *
 * The source exam sheet (FR-UAJ-05-03/R1, Mei 2026) is a blank paper for
 * students to circle an answer on — it carries no printed answer key.
 * QUESTIONS below marks each correct option based on standard information-
 * security course material (CIA triad, malware taxonomy, MITM, Caesar
 * cipher weaknesses, etc.) and, for the four questions referencing a
 * specific in-class case study (Tokopedia, BPJS Kesehatan, Bank Syariah
 * Indonesia), by eliminating options that don't make sense as security
 * weaknesses/recommendations. Numbers 8, 9, 14, and 17 in particular are
 * worth the lecturer double-checking against their own lecture material
 * before publishing an exam built from this bank.
 *
 * Idempotent: each QuestionBankItem is updateOrCreate'd by
 * (university_id, course_id, question_text); its options are replaced
 * (deleted + reinserted) on every run rather than diffed, since there's no
 * natural per-option identity to match against — cheap at 25 items x 4
 * options and safe to re-run.
 */
class AtmaJayaQuestionBankSeeder extends Seeder
{
    /**
     * @var array<int, array{text: string, options: array<int, string>, correct: int}>
     *                                                                  "correct" is the zero-based index into "options" (0 = A, 1 = B, ...).
     */
    private const QUESTIONS = [
        [
            'text' => 'Prinsip keamanan jaringan yang bertujuan memastikan bahwa data hanya dapat diakses oleh pihak yang berwenang adalah …',
            'options' => ['Integrity', 'Availability', 'Confidentiality', 'Authentication'],
            'correct' => 2,
        ],
        [
            'text' => 'Seorang mahasiswa hanya dapat melihat nilai miliknya sendiri, sedangkan admin dapat mengubah data sistem. Hal tersebut merupakan penerapan prinsip …',
            'options' => ['Authorization', 'Availability', 'Non-Repudiation', 'Authentication'],
            'correct' => 0,
        ],
        [
            'text' => 'Pesan yang masih dapat dibaca dan dimengerti maknanya dalam kriptografi disebut …',
            'options' => ['Cipherteks', 'Plainteks', 'Kode', 'Hash'],
            'correct' => 1,
        ],
        [
            'text' => 'Malware yang bekerja dengan mengenkripsi file korban dan meminta uang tebusan agar file dapat diakses kembali disebut …',
            'options' => ['Worm', 'Trojan', 'Ransomware', 'Spyware'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu strategi yang efektif untuk mencegah serangan phishing adalah …',
            'options' => ['Menonaktifkan antivirus', 'Menggunakan Multi-Factor Authentication (MFA)', 'Membagikan password kepada rekan kerja', 'Mengabaikan update sistem'],
            'correct' => 1,
        ],
        [
            'text' => 'SQL Injection biasanya dilakukan dengan cara …',
            'options' => ['Membanjiri server dengan traffic palsu', 'Menyisipkan perintah SQL berbahaya pada input aplikasi web', 'Menyadap paket data di jaringan WiFi publik', 'Menginfeksi komputer menggunakan ransomware'],
            'correct' => 1,
        ],
        [
            'text' => 'Malware yang dapat menyebar sendiri tanpa harus menempel pada file lain disebut …',
            'options' => ['Trojan', 'Worm', 'Rootkit', 'Spyware'],
            'correct' => 1,
        ],
        [
            'text' => 'Salah satu kelemahan sistem keamanan jaringan sebagaimana studi kasus kebocoran data pada Tokopedia adalah',
            'options' => ['Penggunaan MFA yang berlebihan', 'Tidak adanya sistem monitoring aktif (SIEM)', 'Kapasitas server terlalu besar', 'Penggunaan enkripsi berlapis penuh'],
            'correct' => 1,
        ],
        [
            'text' => 'Berikut ini yang termasuk rekomendasi strategis untuk meningkatkan keamanan jaringan pada studi kasus kebocoran data Tokopedia adalah …',
            'options' => ['Menghapus audit keamanan', 'Menonaktifkan autentikasi pengguna', 'Implementasi standar keamanan ISO/IEC 27001', 'Membuka akses data publik seluas-luasnya'],
            'correct' => 2,
        ],
        [
            'text' => 'Aktivitas meretas situs pemerintah yang dilakukan kelompok "BLACKHAT (Official)" termasuk pelanggaran keamanan jaringan karena dapat …',
            'options' => ['Mempercepat akses website', 'Mengurangi penggunaan bandwidth', 'Merusak integritas dan keamanan sistem jaringan', 'Menambah kapasitas penyimpanan server'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu langkah pencegahan keterlibatan anak dalam kejahatan siber adalah …',
            'options' => ['Membiarkan anak bebas menggunakan internet tanpa pengawasan', 'Menghapus seluruh media sosial anak', 'Meningkatkan literasi digital dan pengawasan orang tua terhadap aktivitas online', 'Membatasi penggunaan komputer hanya di sekolah'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu penyebab serangan malware pada mobile banking adalah penggunaan jaringan yang tidak aman, seperti …',
            'options' => ['Jaringan intranet perusahaan', 'VPN pribadi', 'Wi-Fi publik', 'Kabel LAN'],
            'correct' => 2,
        ],
        [
            'text' => 'Serangan yang terjadi ketika penyerang berada di tengah komunikasi dua pihak dan berpura-pura menjadi keduanya disebut …',
            'options' => ['Replay attack', 'Password guessing', 'Man-in-the-middle attack', 'Phishing'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu penyebab kebocoran data BPJS Kesehatan sebagaimana pernah dipresentasikan di kelas adalah …',
            'options' => ['Penggunaan enkripsi berlapis yang berlebihan', 'Lemahnya kontrol akses terhadap data', 'Kapasitas server terlalu besar', 'Penggunaan VPN oleh pengguna'],
            'correct' => 1,
        ],
        [
            'text' => 'Salah satu rekomendasi untuk meningkatkan keamanan jaringan setelah kasus kebocoran data BPJS adalah …',
            'options' => ['Mengurangi audit keamanan sistem', 'Menonaktifkan pelatihan keamanan siber', 'Penerapan standar internasional seperti ISO 27001 dan NIST Framework', 'Membuka akses data publik lebih luas'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu dampak kebocoran data adalah pencurian identitas, yaitu ketika data pribadi seperti NIK dan nama digunakan oleh pihak lain untuk …',
            'options' => ['Memperbaiki sistem keamanan', 'Menyamar dan melakukan transaksi ilegal', 'Meningkatkan kapasitas server', 'Membuat backup data'],
            'correct' => 1,
        ],
        [
            'text' => 'Kelompok ransomware yang menyerang PT Bank Syariah Indonesia pada Mei 2023 adalah …',
            'options' => ['Anonymous', 'WannaCry', 'LockBit 3.0', 'Shadow Brokers'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu langkah penerapan keamanan berlapis untuk mencegah serangan ransomware adalah …',
            'options' => ['Menghapus sistem backup', 'Menggunakan firewall dan Multi-Factor Authentication (MFA)', 'Membagikan password kepada seluruh pegawai', 'Menonaktifkan monitoring sistem'],
            'correct' => 1,
        ],
        [
            'text' => 'Salah satu rekomendasi organisasi dalam menghadapi serangan siber adalah melakukan backup data secara offline karena bertujuan untuk …',
            'options' => ['Mempercepat akses internet', 'Mengurangi kapasitas server', 'Memastikan data tetap aman dan dapat dipulihkan saat terjadi serangan', 'Membuka akses data untuk publik'],
            'correct' => 2,
        ],
        [
            'text' => 'Kesadaran cybersecurity diartikan sebagai …',
            'options' => ['Kemampuan memperbaiki perangkat keras komputer', 'Pengetahuan dan kemampuan mempraktikkan keamanan saat berinternet', 'Aktivitas membuat aplikasi media sosial', 'Proses meningkatkan kecepatan jaringan'],
            'correct' => 1,
        ],
        [
            'text' => 'Enkripsi adalah proses mengubah data yang dapat dibaca (plaintext) menjadi …',
            'options' => ['Password', 'Ciphertext', 'Database', 'Firewall'],
            'correct' => 1,
        ],
        [
            'text' => 'Metode Caesar Cipher bekerja dengan cara …',
            'options' => ['Menghapus karakter pada teks', 'Mengubah teks menjadi gambar', 'Menggeser huruf berdasarkan kunci tertentu', 'Menggandakan data menjadi dua bagian'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu kelemahan utama Caesar Cipher adalah …',
            'options' => ['Tidak dapat melakukan dekripsi', 'Rentan terhadap serangan brute force dan analisis frekuensi', 'Membutuhkan koneksi internet cepat', 'Tidak dapat digunakan pada password'],
            'correct' => 1,
        ],
        [
            'text' => 'Salah satu dampak penggunaan backdoor oleh peretas adalah …',
            'options' => ['Meningkatkan kapasitas penyimpanan komputer', 'Mempercepat proses autentikasi pengguna', 'Memberikan akses ilegal ke sistem dan memungkinkan pencurian data', 'Mengurangi penggunaan bandwidth jaringan'],
            'correct' => 2,
        ],
        [
            'text' => 'Salah satu informasi yang dapat dicuri menggunakan keylogger adalah …',
            'options' => ['Kapasitas RAM komputer', 'Alamat IP router', 'Username dan password pengguna', 'Kecepatan koneksi internet'],
            'correct' => 2,
        ],
    ];

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->firstOrFail();
        $course = Course::query()->where('university_id', $university->id)->where('code', 'SFT 206')->firstOrFail();

        foreach (self::QUESTIONS as $question) {
            $item = QuestionBankItem::query()->updateOrCreate(
                ['university_id' => $university->id, 'course_id' => $course->id, 'question_text' => $question['text']],
                ['points' => 4],
            );

            $item->options()->delete();

            foreach ($question['options'] as $index => $optionText) {
                QuestionBankItemOption::query()->create([
                    'university_id' => $university->id,
                    'question_bank_item_id' => $item->id,
                    'option_text' => $optionText,
                    'is_correct' => $index === $question['correct'],
                    'order_index' => $index,
                ]);
            }
        }
    }
}
