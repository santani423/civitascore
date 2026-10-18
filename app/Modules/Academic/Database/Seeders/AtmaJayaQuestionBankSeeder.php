<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Models\QuestionBankItemOption;
use Modules\Tenancy\Models\University;

/**
 * Imports real UTS (Ujian Tengah Semester) question sets at Universitas
 * Katolik Indonesia Atma Jaya into the Bank Soal, so the demo dosen account
 * has a populated question bank to build an Ujian from instead of an empty
 * one. QUESTIONS is keyed by course code:
 *
 * - SFT 206 - Keamanan Jaringan (dosen pengampu: Eugenius Kau Suni). The
 *   source exam sheet (FR-UAJ-05-03/R1, Mei 2026) is a blank paper for
 *   students to circle an answer on — it carries no printed answer key.
 *   Each correct option is marked based on standard information-security
 *   course material (CIA triad, malware taxonomy, MITM, Caesar cipher
 *   weaknesses, etc.) and, for the four questions referencing a specific
 *   in-class case study (Tokopedia, BPJS Kesehatan, Bank Syariah
 *   Indonesia), by eliminating options that don't make sense as security
 *   weaknesses/recommendations. Numbers 8, 9, 14, and 17 in particular are
 *   worth the lecturer double-checking against their own lecture material
 *   before publishing an exam built from this bank.
 * - SFT 303 - Tata Kelola Teknologi Informasi. Kunci jawaban mengikuti
 *   kunci dari dosen (opsi bertanda bintang pada lembar soal), bukan
 *   tebakan.
 *
 * Idempotent: each QuestionBankItem is updateOrCreate'd by
 * (university_id, course_id, question_text); its options are replaced
 * (deleted + reinserted) on every run rather than diffed, since there's no
 * natural per-option identity to match against — cheap at 25 items x 4
 * options per course and safe to re-run.
 */
class AtmaJayaQuestionBankSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{text: string, options: array<int, string>, correct: int}>>
     *                                                                                                Keyed by course code; "correct" is the zero-based index into "options" (0 = A, 1 = B, ...).
     */
    public const QUESTIONS = [
        'SFT 206' => [
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
        ],
        'SFT 303' => [
            [
                'text' => 'Tujuan utama dari tata kelola teknologi informasi (IT Governance) adalah …',
                'options' => ['Mengembangkan sistem informasi baru di perusahaan', 'Memastikan penggunaan TI berjalan efektif dan efisien untuk mendukung tujuan bisnis', 'Menambah jumlah perangkat keras dalam organisasi', 'Meningkatkan kemampuan teknis karyawan di bidang pemrograman'],
                'correct' => 1,
            ],
            [
                'text' => 'Berikut ini yang bukan termasuk fokus area utama dalam IT Governance menurut ITGI adalah …',
                'options' => ['Strategic Alignment', 'Risk Management', 'Value Delivery', 'System Development'],
                'correct' => 3,
            ],
            [
                'text' => 'Berikut ini yang tidak termasuk framework utama dalam tata kelola TI adalah …',
                'options' => ['COBIT', 'ITIL', 'ISO/IEC 27001', 'SWOT'],
                'correct' => 3,
            ],
            [
                'text' => 'Tahapan dalam proses tata kelola TI yang berfokus pada identifikasi, evaluasi, dan mitigasi risiko adalah …',
                'options' => ['Perencanaan dan Strategi', 'Pengendalian dan Pengelolaan Risiko', 'Pelaksanaan dan Operasi', 'Evaluasi dan Pengukuran Kinerja'],
                'correct' => 1,
            ],
            [
                'text' => 'Salah satu tujuan utama tata kelola TI dalam E-Bisnis adalah …',
                'options' => ['Menghapus penggunaan teknologi digital dalam bisnis', 'Menyelaraskan penggunaan TI dengan tujuan bisnis dan memastikan keamanan data', 'Menggantikan sistem manajemen tradisional dengan metode manual', 'Mengurangi peran TI dalam proses pengambilan keputusan'],
                'correct' => 1,
            ],
            [
                'text' => 'Apa fungsi utama dari kerangka kerja TOGAF (The Open Group Architecture Framework) dalam tata kelola TI?',
                'options' => ['Memberikan panduan pengelolaan keamanan informasi organisasi', 'Menetapkan praktik terbaik dalam manajemen layanan TI', 'Menyediakan kerangka kerja untuk merancang dan mengelola arsitektur enterprise agar selaras dengan tujuan bisnis', 'Mengatur standar transaksi jual beli secara elektronik'],
                'correct' => 2,
            ],
            [
                'text' => 'Dalam melaksanakan suatu tata kelola IT pada bisnis atau organisasi, terdapat 3 hal penting, yaitu Proses, Strategi, dan Taktik. Apa yang dimaksud dengan Taktik pada konteks tata kelola IT ini?',
                'options' => ['Akal-akalan untuk mengelabui stakeholder (pihak terkait)', 'Tindakan konkret untuk mewujudkan arah dan strategi tata kelola IT', 'Analisis dan perencanaan yang lebih baik', 'Mengutamakan urusan pelatihan daripada pengembangan aplikasi'],
                'correct' => 1,
            ],
            [
                'text' => 'COBIT 5 memiliki lima prinsip utama, salah satunya adalah Meeting Stakeholder Needs. Apa yang dimaksud dengan Meeting Stakeholder Needs ini?',
                'options' => ['Menyelaraskan COBIT 5 dengan standar dan kerangka kerja lain yang lebih luas', 'Membangun tata kelola yang efektif melalui sekumpulan enabler yang saling terkait', 'Menciptakan nilai bagi pemangku kepentingan melalui keseimbangan antara realisasi manfaat, risiko, dan penggunaan sumber daya', 'Mengintegrasikan tata kelola TI dengan tata kelola keseluruhan perusahaan'],
                'correct' => 2,
            ],
            [
                'text' => 'Apa fungsi ISO/IEC 27001 sebagai suatu kerangka kerja Tata Kelola TI?',
                'options' => ['Menyediakan standar internasional untuk manajemen keamanan informasi', 'Menetapkan praktik terbaik dalam manajemen layanan TI', 'Membangun tata kelola yang efektif melalui sekumpulan enabler yang saling terkait', 'Menyediakan langkah konkret untuk mewujudkan arah dan strategi tata kelola IT'],
                'correct' => 0,
            ],
            [
                'text' => 'Tugas utama seorang auditor internal IT (Information Technology Internal Auditor) dalam suatu perusahaan adalah …',
                'options' => ['Mengubah sistem penerapan dan implementasi teknologi agar semakin selaras dengan kemajuan teknologi terkini', 'Melakukan rekapan permasalahan penerapan teknologi IT dan melaporkannya kepada pimpinan untuk segera memperbaiki atau mengatasi masalah tersebut', 'Membuat perancangan sistem secara lengkap dan mendampingi proses pengembangan sistem hingga implementasi hasil pengembangan sistem tersebut', 'Mengevaluasi, menganalisis, dan menguji infrastruktur, kebijakan, prosedur, serta sistem teknologi informasi perusahaan guna memastikan keandalan, keamanan data, efisiensi operasional, dan kepatuhan terhadap regulasi yang berlaku'],
                'correct' => 3,
            ],
            [
                'text' => 'Salah satu tujuan penerapan ITIL 4 dalam arsitektur layanan TI adalah …',
                'options' => ['Meningkatkan efisiensi layanan dan kepuasan pengguna melalui proses yang terstandar', 'Mengurangi jumlah layanan TI agar biaya lebih rendah', 'Menghapus sistem layanan pelanggan tradisional', 'Menggantikan framework TOGAF sepenuhnya dalam tata kelola TI'],
                'correct' => 0,
            ],
            [
                'text' => 'Pada framework COSO (Committee of Sponsoring Organizations of the Treadway Commission) terdapat komponen Risk Assessment yang tugasnya adalah melakukan …',
                'options' => ['Proses identifikasi dan analisis risiko yang dapat menghambat pencapaian tujuan organisasi', 'Menetapkan aturan dan prosedur yang penting dan mendasar untuk mendukung proses dan upaya dalam mewujudkan tujuan dari organisasi', 'Melakukan identifikasi, menangkap, dan menyebarluaskan informasi yang relevan dalam bentuk dan waktu yang tepat', 'Semua jawaban di atas adalah BENAR'],
                'correct' => 0,
            ],
            [
                'text' => 'Apa fungsi utama dari standar ISO/IEC 27001 dalam tata kelola teknologi informasi?',
                'options' => ['Menentukan arsitektur jaringan komputer', 'Menjadi panduan internasional untuk manajemen keamanan informasi', 'Mengatur standar hardware dan perangkat TI di organisasi', 'Mengukur kinerja keuangan sistem informasi'],
                'correct' => 1,
            ],
            [
                'text' => 'Salah satu dari lima prinsip dasar COBIT 5 adalah …',
                'options' => ['Centralizing Business Needs', 'Managing Operational Assets', 'Enabling a Holistic Approach', 'Focusing on Infrastructure Growth'],
                'correct' => 2,
            ],
            [
                'text' => 'Apa yang dimaksud dengan COBIT dalam konteks tata kelola teknologi informasi?',
                'options' => ['Sebuah sistem operasi untuk mengelola server dan jaringan perusahaan', 'Kerangka kerja yang membantu organisasi mencapai tujuan tata kelola dan manajemen TI secara terintegrasi', 'Standar keamanan informasi internasional yang berfokus pada perlindungan data', 'Metodologi pengembangan perangkat lunak berbasis agile'],
                'correct' => 1,
            ],
            [
                'text' => 'Apa yang dimaksud dengan blueprint dalam kerangka kerja TOGAF?',
                'options' => ['Dokumen teknis yang hanya berisi daftar perangkat keras perusahaan', 'Cetak biru arsitektur yang menggambarkan hubungan antara bisnis, data, aplikasi, dan teknologi dalam organisasi', 'Daftar proyek TI yang akan dikerjakan dalam satu tahun', 'Panduan keamanan informasi yang digunakan untuk audit internal'],
                'correct' => 1,
            ],
            [
                'text' => 'Langkah pertama yang harus dilakukan dalam melakukan audit tata kelola TI berbasis COBIT adalah …',
                'options' => ['Menilai tingkat kematangan proses TI', 'Menyusun rekomendasi perbaikan', 'Menentukan ruang lingkup, tujuan, dan rencana audit', 'Melakukan analisis kesenjangan antara kondisi aktual dan target'],
                'correct' => 2,
            ],
            [
                'text' => 'Apa tujuan utama dari melakukan analisis kesenjangan (Gap Analysis) dalam audit tata kelola TI berbasis COBIT?',
                'options' => ['Menghapus proses audit yang tidak efisien', 'Mengetahui perbedaan antara kondisi aktual dan kondisi ideal tata kelola TI', 'Menentukan perangkat keras yang akan digunakan untuk audit', 'Menilai kinerja individu dalam tim TI'],
                'correct' => 1,
            ],
            [
                'text' => 'COBIT 2019 berfokus pada penciptaan nilai (value) dari penggunaan TI, di mana nilai tersebut pada dasarnya diperoleh dengan menyeimbangkan tiga hal, yaitu …',
                'options' => ['Benefits Realization, Risk Optimization, dan Resource Optimization', 'Benefits Realization, Risk Management, dan IT Governance', 'Resource Optimization, Performance Measurement, dan Risk Assessment', 'Benefits Realization, Compliance Management, dan Resource Allocation'],
                'correct' => 0,
            ],
            [
                'text' => 'Fase manakah dalam TOGAF ADM yang berfokus pada pengembangan arsitektur bisnis organisasi untuk mendukung visi arsitektur?',
                'options' => ['Phase A – Architecture Vision', 'Phase B – Business Architecture', 'Phase C – Information Systems Architectures', 'Phase D – Technology Architecture'],
                'correct' => 1,
            ],
            [
                'text' => 'Dalam TOGAF ADM, fase Information Systems Architectures terdiri atas dua subfase utama. Manakah pasangan yang benar menggambarkan dua subfase tersebut?',
                'options' => ['Data Architecture dan Application Architecture', 'Business Architecture dan Technology Architecture', 'Architecture Vision dan Opportunities & Solutions', 'Migration Planning dan Change Management'],
                'correct' => 0,
            ],
            [
                'text' => 'Kerangka kerja mana yang berfokus pada tata kelola dan pengendalian TI agar selaras dengan tujuan bisnis?',
                'options' => ['TOGAF', 'COBIT', 'ITIL', 'ISO 27001'],
                'correct' => 1,
            ],
            [
                'text' => 'Jika sebuah organisasi ingin meningkatkan kualitas layanan TI kepada pengguna, kerangka kerja yang paling tepat digunakan adalah …',
                'options' => ['COBIT', 'ITIL', 'TOGAF', 'PMBOK'],
                'correct' => 1,
            ],
            [
                'text' => 'Komponen Governance System dari COBIT 2019 adalah …',
                'options' => ['Governance Objectives, Risk Management, dan Performance Measurement', 'Processes, Organizational Structures, dan Information Flows and Items', 'Planning, Implementation, Monitoring, dan Evaluation', 'People, Skills and Competencies, Technology, dan Business Processes'],
                'correct' => 1,
            ],
            [
                'text' => 'Apa yang dimaksud dengan design factor dalam COBIT 2019?',
                'options' => ['Faktor untuk menentukan desain tampilan aplikasi', 'Faktor yang digunakan untuk memilih proses tata kelola sesuai kondisi organisasi', 'Faktor untuk menentukan harga perangkat keras', 'Faktor untuk mengatur desain logo perusahaan'],
                'correct' => 1,
            ],
        ],
    ];

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->firstOrFail();

        foreach (self::QUESTIONS as $courseCode => $questions) {
            $course = Course::query()->where('university_id', $university->id)->where('code', $courseCode)->firstOrFail();

            foreach ($questions as $question) {
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
}
