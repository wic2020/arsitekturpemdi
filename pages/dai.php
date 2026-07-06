<?php

declare(strict_types=1);

$securityFields = [
    'id_dak_audit_keamanan' => 'Audit Keamanan',
    'id_dak_edukasi_kesadaran' => 'Edukasi Kesadaran',
    'id_dak_identifikasi_kerentanan' => 'Identifikasi Kerentanan',
    'id_dak_kelaikan_keamanan' => 'Kelaikan Keamanan',
    'id_dak_penanganan_insiden' => 'Penanganan Insiden',
    'id_dak_peningkatan_keamanan' => 'Peningkatan Keamanan',
    'id_dak_standar_keamanan' => 'Standar Keamanan',
];
$securityTables = array_combine(array_keys($securityFields), array_map(
    static fn(string $field): string => substr($field, 3),
    array_keys($securityFields)
));
$configs = [
    'dai-fasilitas-komputasi' => [
        'table' => 'dai_fasilitas_komputasi', 'title' => 'Fasilitas Komputasi', 'icon' => 'server-cog',
        'description' => 'Kelola fasilitas komputasi, kapasitas koneksi, lokasi, dan sistem pengamannya.',
        'name' => 'nama_fasilitas_komputasi', 'multi_network' => true,
        'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai_fasilitas_komputasi.php',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Fasilitas Komputasi', 'field' => 'nama_fasilitas_komputasi'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_fasilitas_komputasi' => ['label' => 'Nama Fasilitas Komputasi', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'bandwidth_intranet' => ['label' => 'Bandwidth Intranet (Mbps)', 'type' => 'number', 'step' => '0.01'],
            'bandwidth_internet' => ['label' => 'Bandwidth Internet (Mbps)', 'type' => 'number', 'step' => '0.01'],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'klasifikasi_tier' => ['label' => 'Klasifikasi Tier', 'type' => 'text', 'maxlength' => 255],
            'sistem_pengaman_fasilitas' => ['label' => 'Sistem Pengaman Fasilitas', 'type' => 'textarea'],
        ],
        'references' => [
            ['table' => 'dai_hardware_server', 'column' => 'id_dai_fasilitas_komputasi', 'label' => 'Hardware Server'],
            ['table' => 'dai_hardware_jaringan', 'column' => 'id_dai_fasilitas_komputasi', 'label' => 'Hardware Jaringan'],
            ['table' => 'dai_hardware_periferal', 'column' => 'id_dai_fasilitas_komputasi', 'label' => 'Hardware Periferal'],
            ['table' => 'dai_hardware_storage', 'column' => 'id_dai_fasilitas_komputasi', 'label' => 'Hardware Storage'],
            ['table' => 'dai_hardware_keamanan', 'column' => 'id_dai_fasilitas_komputasi', 'label' => 'Hardware Keamanan'],
        ],
    ],
    'dai-komputasi-awan' => [
        'table' => 'dai_komputasi_awan', 'title' => 'Komputasi Awan', 'icon' => 'cloud',
        'description' => 'Kelola layanan komputasi awan, kepemilikan, biaya, dan jaringan pendukung.',
        'name' => 'nama_cloud', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=komputasi-awan',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Cloud', 'field' => 'nama_cloud'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Unit Operasional', 'field' => 'unit_operasional'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_cloud' => ['label' => 'Nama Cloud', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe' => ['label' => 'Tipe Cloud', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'biaya_layanan' => ['label' => 'Biaya Layanan', 'type' => 'text', 'maxlength' => 255],
            'unit_pengembang' => ['label' => 'Unit Pengembang', 'type' => 'text', 'maxlength' => 255],
            'unit_operasional' => ['label' => 'Unit Operasional', 'type' => 'text', 'maxlength' => 255],
            'jangka_waktu_layanan' => ['label' => 'Jangka Waktu Layanan', 'type' => 'text', 'maxlength' => 255],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
        ],
        'references' => [
            ['table' => 'daa', 'column' => 'id_dai_komputasi_awan', 'label' => 'Domain Aplikasi'],
        ],
    ],
    'dai-jaringan-intra' => [
        'table' => 'dai_jaringan_intra', 'title' => 'Jaringan Intra', 'icon' => 'network',
        'description' => 'Kelola jaringan intra, bandwidth, media transmisi, dan unit pengelolanya.',
        'name' => 'nama_jaringan', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=jaringan-intra',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Jaringan & Deskripsi', 'field' => 'nama_jaringan', 'description' => 'deskripsi'],
            ['label' => 'Jenis Jaringan', 'field' => 'jenis_jaringan'],
            ['label' => 'Unit Kerja Pengelola', 'type' => 'skpd'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_jaringan' => ['label' => 'Nama Jaringan', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'jenis_jaringan' => ['label' => 'Jenis Jaringan', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'id_unit_kerja_pengelola' => ['label' => 'Unit Kerja Pengelola', 'type' => 'skpd'],
            'bandwidth' => ['label' => 'Bandwidth (Mbps)', 'type' => 'number', 'step' => '0.01'],
            'tipe_media' => ['label' => 'Tipe Media', 'type' => 'text', 'maxlength' => 255],
            'media_lainnya' => ['label' => 'Media Lainnya', 'type' => 'text', 'maxlength' => 255],
        ],
        'references' => [
            ['table' => 'daa', 'column' => 'id_dai_jaringan_intra', 'label' => 'Domain Aplikasi'],
            ['table' => 'dai_komputasi_awan', 'column' => 'id_dai_jaringan_intra', 'label' => 'Komputasi Awan'],
            ['table' => 'dai_hardware_server', 'column' => 'id_dai_jaringan_intra', 'label' => 'Hardware Server'],
            ['table' => 'dai_hardware_jaringan', 'column' => 'id_dai_jaringan_intra', 'label' => 'Hardware Jaringan'],
            ['table' => 'dai_hardware_periferal', 'column' => 'id_dai_jaringan_intra', 'label' => 'Hardware Periferal'],
            ['table' => 'dai_hardware_storage', 'column' => 'id_dai_jaringan_intra', 'label' => 'Hardware Storage'],
            ['table' => 'dai_hardware_keamanan', 'column' => 'id_dai_jaringan_intra', 'label' => 'Hardware Keamanan'],
            ['table' => 'dai_splp', 'column' => 'id_dai_jaringan_intra', 'label' => 'SPLP'],
        ],
    ],
    'dai-software' => [
        'table' => 'dai_software', 'title' => 'Software', 'icon' => 'package',
        'description' => 'Kelola perangkat lunak platform, sistem operasi, serta informasi lisensinya.',
        'name' => 'nama_perangkat_lunak', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=software',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Perangkat Lunak & Deskripsi', 'field' => 'nama_perangkat_lunak', 'description' => 'deskripsi'],
            ['label' => 'Tipe Perangkat Lunak', 'field' => 'tipe_perangkat_lunak'],
            ['label' => 'Jenis Sistem Operasi', 'field' => 'jenis_sistem_operasi'],
            ['label' => 'Jenis Lisensi', 'field' => 'jenis_lisensi'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_perangkat_lunak' => ['label' => 'Nama Perangkat Lunak', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe_perangkat_lunak' => ['label' => 'Tipe Perangkat Lunak', 'type' => 'text', 'maxlength' => 255],
            'jenis_sistem_operasi' => ['label' => 'Jenis Sistem Operasi', 'type' => 'text', 'maxlength' => 255],
            'jenis_lisensi' => ['label' => 'Jenis Lisensi', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik_lisensi' => ['label' => 'Nama Pemilik Lisensi', 'type' => 'text', 'maxlength' => 255],
            'validitas_lisensi' => ['label' => 'Validitas Lisensi', 'type' => 'text', 'maxlength' => 255],
        ],
        'references' => [
            ['table' => 'dai_hardware_server', 'column' => 'id_dai_software', 'label' => 'Hardware Server'],
            ['table' => 'dai_hardware_jaringan', 'column' => 'id_dai_software', 'label' => 'Hardware Jaringan'],
            ['table' => 'dai_hardware_periferal', 'column' => 'id_dai_software', 'label' => 'Hardware Periferal'],
            ['table' => 'dai_hardware_storage', 'column' => 'id_dai_software', 'label' => 'Hardware Storage'],
            ['table' => 'dai_hardware_keamanan', 'column' => 'id_dai_software', 'label' => 'Hardware Keamanan'],
        ],
    ],
    'dai-hardware-server' => [
        'table' => 'dai_hardware_server', 'title' => 'Hardware Server', 'icon' => 'server',
        'description' => 'Kelola server, kapasitas, lokasi, perangkat lunak, dan infrastruktur pendukungnya.',
        'name' => 'nama_server', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=hardware-server',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Server & Deskripsi', 'field' => 'nama_server', 'description' => 'deskripsi'],
            ['label' => 'Kapasitas Memori', 'field' => 'kapasitas_memori'],
            ['label' => 'Kapasitas Penyimpanan', 'field' => 'kapasitas_penyimpanan'],
            ['label' => 'Jenis Penggunaan', 'field' => 'jenis_penggunaan'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_server' => ['label' => 'Nama Server', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'jenis_server' => ['label' => 'Jenis Server', 'type' => 'select', 'options' => ['Fisik', 'Virtual']],
            'jenis_penggunaan' => ['label' => 'Jenis Penggunaan', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'kapasitas_memori' => ['label' => 'Kapasitas Memori', 'type' => 'text', 'maxlength' => 255],
            'kapasitas_prosesor' => ['label' => 'Kapasitas Prosesor', 'type' => 'text', 'maxlength' => 255],
            'kapasitas_penyimpanan' => ['label' => 'Kapasitas Penyimpanan', 'type' => 'text', 'maxlength' => 255],
            'id_dai_software' => ['label' => 'Software', 'type' => 'software'],
            'id_dab' => ['label' => 'Domain Bisnis', 'type' => 'dab'],
            'id_dai_fasilitas_komputasi' => ['label' => 'Fasilitas Komputasi', 'type' => 'facility'],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
        ],
        'references' => [
            ['table' => 'daa', 'column' => 'id_dai_hardware_server', 'label' => 'Domain Aplikasi'],
        ],
    ],
    'dai-hardware-jaringan' => [
        'table' => 'dai_hardware_jaringan', 'title' => 'Hardware Jaringan', 'icon' => 'router',
        'description' => 'Kelola perangkat keras jaringan beserta lokasi dan infrastruktur pendukungnya.',
        'name' => 'nama_device', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=hardware-jaringan',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Device & Deskripsi', 'field' => 'nama_device', 'description' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_device' => ['label' => 'Nama Perangkat', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe' => ['label' => 'Tipe', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'id_dai_fasilitas_komputasi' => ['label' => 'Fasilitas Komputasi', 'type' => 'facility'],
            'id_dai_software' => ['label' => 'Software', 'type' => 'software'],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
        ],
        'references' => [],
    ],
    'dai-hardware-periferal' => [
        'table' => 'dai_hardware_periferal', 'title' => 'Hardware Periferal', 'icon' => 'printer',
        'description' => 'Kelola periferal dan keterhubungannya dengan server, storage, serta jaringan.',
        'name' => 'nama_periferal', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=hardware-periferal',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Periferal & Deskripsi', 'field' => 'nama_periferal', 'description' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_periferal' => ['label' => 'Nama Periferal', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe' => ['label' => 'Tipe', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'id_dai_fasilitas_komputasi' => ['label' => 'Fasilitas Komputasi', 'type' => 'facility'],
            'id_dai_software' => ['label' => 'Software', 'type' => 'software'],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
        ],
        'references' => [],
    ],
    'dai-hardware-storage' => [
        'table' => 'dai_hardware_storage', 'title' => 'Hardware Storage', 'icon' => 'hard-drive',
        'description' => 'Kelola media penyimpanan, kapasitas, metode akses, dan data yang disimpan.',
        'name' => 'nama_storage', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=hardware-storage',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Storage & Deskripsi', 'field' => 'nama_storage', 'description' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_storage' => ['label' => 'Nama Storage', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe' => ['label' => 'Tipe', 'type' => 'text', 'maxlength' => 255],
            'id_dad' => ['label' => 'Domain Data', 'type' => 'dad'],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'kapasitas_penyimpanan' => ['label' => 'Kapasitas Penyimpanan', 'type' => 'text', 'maxlength' => 255],
            'metode_akses_data_sharing' => ['label' => 'Metode Akses/Data Sharing', 'type' => 'text', 'maxlength' => 255],
            'id_dai_fasilitas_komputasi' => ['label' => 'Fasilitas Komputasi', 'type' => 'facility'],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
            'id_dai_software' => ['label' => 'Software', 'type' => 'software'],
        ],
        'references' => [],
    ],
    'dai-hardware-keamanan' => [
        'table' => 'dai_hardware_keamanan', 'title' => 'Hardware Keamanan', 'icon' => 'shield',
        'description' => 'Kelola perangkat keras keamanan dan penempatannya pada infrastruktur.',
        'name' => 'nama_device', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=hardware-keamanan',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama Device & Deskripsi', 'field' => 'nama_device', 'description' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_device' => ['label' => 'Nama Perangkat', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'tipe' => ['label' => 'Tipe', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'unit_pengelola' => ['label' => 'Unit Pengelola', 'type' => 'text', 'maxlength' => 255],
            'lokasi' => ['label' => 'Lokasi', 'type' => 'textarea'],
            'id_dai_fasilitas_komputasi' => ['label' => 'Fasilitas Komputasi', 'type' => 'facility'],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
            'id_dai_software' => ['label' => 'Software', 'type' => 'software'],
        ],
        'references' => [],
    ],
    'dai-splp' => [
        'table' => 'dai_splp', 'title' => 'SPLP', 'icon' => 'share-2',
        'description' => 'Kelola Sistem Penghubung Layanan Pemerintah, jaringan, data, dan aplikasi terkait.',
        'name' => 'nama_splp', 'order_by' => 'd.id ASC', 'print_url' => 'cetak_dai.php?type=splp',
        'columns' => [
            ['label' => 'Kode', 'type' => 'code'],
            ['label' => 'Nama SPLP', 'field' => 'nama_splp'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Jenis SPLP', 'field' => 'jenis_splp'],
            ['label' => 'Referensi RAI', 'type' => 'rai'],
        ],
        'fields' => [
            'nama_splp' => ['label' => 'Nama SPLP', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'jenis_splp' => ['label' => 'Jenis SPLP', 'type' => 'text', 'maxlength' => 255],
            'status_kepemilikan' => ['label' => 'Status Kepemilikan', 'type' => 'text', 'maxlength' => 255],
            'nama_pemilik' => ['label' => 'Nama Pemilik', 'type' => 'text', 'maxlength' => 255],
            'id_dai_jaringan_intra' => ['label' => 'Jaringan Intra', 'type' => 'network'],
            'id_dad' => ['label' => 'Domain Data', 'type' => 'dad'],
        ],
        'references' => [
            ['table' => 'daa_dai_splp', 'column' => 'id_dai_splp', 'label' => 'Domain Aplikasi'],
            ['table' => 'dai_splp_daa', 'column' => 'id_dai_splp', 'label' => 'Relasi Aplikasi SPLP'],
        ],
    ],
];

$config = $configs[$page] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Konfigurasi domain infrastruktur tidak ditemukan.');
}
$table = $config['table'];
$fieldConfigs = array_merge(
    ['id_rai' => ['label' => 'Referensi RAI', 'type' => 'rai', 'required' => true]],
    $config['fields'],
    array_map(static fn(string $label): array => ['label' => $label, 'type' => 'security'], $securityFields)
);
$fieldNames = array_keys($fieldConfigs);
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($fieldNames, '');
$formState['id'] = '';
$formState['jaringan_ids'] = [];

function dai_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function dai_ref_label(array $row, string $code, string $name): string
{
    return trim((string) ($row[$code] ?? '') . ((!empty($row[$code]) && !empty($row[$name])) ? ' - ' : '') . (string) ($row[$name] ?? ''));
}

function dai_page_url(string $route, int $number, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query(['page' => $route, 'q' => $search, 'per_page' => $perPage, 'p' => $number]);
}

$raiList = db()->query('SELECT id, kode_rai_4, nama_rai_4 FROM rai ORDER BY kode_rai_4, nama_rai_4')->fetchAll();
$skpdList = db()->query('SELECT id, kode_skpd, nama_skpd FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll();
$networkList = db()->query('SELECT id, nama_jaringan FROM dai_jaringan_intra ORDER BY nama_jaringan')->fetchAll();
$facilityList = db()->query('SELECT id, nama_fasilitas_komputasi AS nama FROM dai_fasilitas_komputasi ORDER BY nama_fasilitas_komputasi')->fetchAll();
$softwareList = db()->query('SELECT id, nama_perangkat_lunak AS nama FROM dai_software ORDER BY nama_perangkat_lunak')->fetchAll();
$dabList = db()->query('SELECT id, nama_bisnis AS nama FROM dab ORDER BY nama_bisnis')->fetchAll();
foreach ($dabList as &$dabItem) {
    $dabItem['nama'] = sprintf('DAB-%03d - %s', (int) $dabItem['id'], (string) $dabItem['nama']);
}
unset($dabItem);
$dadList = db()->query('SELECT id, nama_data AS nama FROM dad ORDER BY nama_data')->fetchAll();
$relationLists = [
    'facility' => $facilityList,
    'software' => $softwareList,
    'dab' => $dabList,
    'dad' => $dadList,
];
$securityLists = [];
foreach ($securityTables as $field => $securityTable) {
    $securityLists[$field] = db()->query("SELECT id, nama FROM {$securityTable} ORDER BY nama")->fetchAll();
}
$validIds = [
    'id_rai' => array_flip(array_map('intval', array_column($raiList, 'id'))),
    'id_unit_kerja_pengelola' => array_flip(array_map('intval', array_column($skpdList, 'id'))),
    'id_dai_jaringan_intra' => array_flip(array_map('intval', array_column($networkList, 'id'))),
    'id_dai_fasilitas_komputasi' => array_flip(array_map('intval', array_column($facilityList, 'id'))),
    'id_dai_software' => array_flip(array_map('intval', array_column($softwareList, 'id'))),
    'id_dab' => array_flip(array_map('intval', array_column($dabList, 'id'))),
    'id_dad' => array_flip(array_map('intval', array_column($dadList, 'id'))),
];
foreach ($securityLists as $field => $list) $validIds[$field] = array_flip(array_map('intval', array_column($list, 'id')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = dai_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($fieldNames as $field) $formState[$field] = trim((string) ($_POST[$field] ?? ''));
        $postedNetworks = is_array($_POST['jaringan_ids'] ?? null) ? $_POST['jaringan_ids'] : [];
        $formState['jaringan_ids'] = array_values(array_unique(array_filter(array_map('dai_int_or_null', $postedNetworks))));

        foreach ($fieldConfigs as $field => $definition) {
            $value = $formState[$field];
            $type = $definition['type'];
            if (!empty($definition['required']) && $value === '') $formErrors[] = $definition['label'] . ' wajib diisi.';
            if (isset($definition['maxlength']) && mb_strlen($value) > (int) $definition['maxlength']) $formErrors[] = $definition['label'] . ' terlalu panjang.';
            if ($type === 'number' && $value !== '' && (!is_numeric($value) || (float) $value < 0)) $formErrors[] = $definition['label'] . ' harus berupa angka nol atau lebih.';
            if ($type === 'select' && $value !== '' && !in_array($value, $definition['options'], true)) $formErrors[] = $definition['label'] . ' tidak valid.';
            if (isset($validIds[$field]) && $value !== '') {
                $id = dai_int_or_null($value);
                if (!$id || !isset($validIds[$field][$id])) $formErrors[] = $definition['label'] . ' tidak valid.';
            }
        }
        if (array_diff($formState['jaringan_ids'], array_keys($validIds['id_dai_jaringan_intra']))) $formErrors[] = 'Pilihan jaringan intra tidak valid.';
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Data yang akan diubah tidak ditemukan.';
        }
        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [];
                foreach ($fieldConfigs as $field => $definition) {
                    $value = $formState[$field];
                    if (in_array($definition['type'], ['rai', 'skpd', 'network', 'security', 'facility', 'software', 'dab', 'dad', 'server', 'storage'], true)) $params[$field] = dai_int_or_null($value);
                    elseif ($definition['type'] === 'number') $params[$field] = $value !== '' ? (float) $value : null;
                    else $params[$field] = $value !== '' ? $value : null;
                }
                $params['updated_by'] = (int) $user['id'];
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare("INSERT INTO {$table} (" . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')')->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    db()->prepare("UPDATE {$table} SET " . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }
                if (!empty($config['multi_network'])) {
                    db()->prepare('DELETE FROM dai_fasilitas_komputasi_jaringan_intra WHERE id_dai_fasilitas_komputasi=:id')->execute(['id' => $recordId]);
                    $link = db()->prepare('INSERT INTO dai_fasilitas_komputasi_jaringan_intra (id_dai_fasilitas_komputasi,id_dai_jaringan_intra) VALUES (:owner,:network)');
                    foreach ($formState['jaringan_ids'] as $networkId) $link->execute(['owner' => $recordId, 'network' => $networkId]);
                }
                $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                if (!empty($config['multi_network'])) $newValues['jaringan_ids'] = $formState['jaringan_ids'];
                audit_log((int) $user['id'], $action, $table, $recordId, ($action === 'create' ? 'Menambahkan ' : 'Mengubah ') . $config['title'] . ': ' . $formState[$config['name']], $oldValues, $newValues, true);
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data berhasil ditambahkan.' : 'Data berhasil diperbarui.');
                redirect('index.php?page=' . $page);
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log("Penyimpanan {$table} gagal: " . $exception->getMessage());
                $formErrors[] = 'Data gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = dai_int_or_null($_POST['id'] ?? null);
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect('index.php?page=' . $page);
        }
        $usedBy = [];
        foreach ($config['references'] as $reference) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM {$reference['table']} WHERE {$reference['column']}=:id");
            $stmt->execute(['id' => $recordId]);
            if ((int) $stmt->fetchColumn()) $usedBy[] = $reference['label'];
        }
        if ($usedBy) {
            set_flash('error', 'Data masih digunakan oleh ' . implode(', ', $usedBy) . ' dan tidak dapat dihapus.');
            redirect('index.php?page=' . $page);
        }
        db()->beginTransaction();
        try {
            if (!empty($config['multi_network'])) db()->prepare('DELETE FROM dai_fasilitas_komputasi_jaringan_intra WHERE id_dai_fasilitas_komputasi=:id')->execute(['id' => $recordId]);
            if ($table === 'dai_jaringan_intra') db()->prepare('DELETE FROM dai_fasilitas_komputasi_jaringan_intra WHERE id_dai_jaringan_intra=:id')->execute(['id' => $recordId]);
            db()->prepare("DELETE FROM {$table} WHERE id=:id")->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', $table, $recordId, 'Menghapus ' . $config['title'] . ': ' . $record[$config['name']], $record, null, true);
            db()->commit();
            set_flash('success', 'Data berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log("Penghapusan {$table} gagal: " . $exception->getMessage());
            set_flash('error', 'Data gagal dihapus.');
        }
        redirect('index.php?page=' . $page);
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPageOptions = [10, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$searchColumns = array_map(static fn(string $field): string => "d.{$field}", $fieldNames);
$whereSql = $search === '' ? '' : " WHERE CONCAT_WS(' '," . implode(',', $searchColumns) . ",r.kode_rai_4,r.nama_rai_4) LIKE :search";
$queryParams = $search === '' ? [] : ['search' => '%' . $search . '%'];
$hasSkpdManager = isset($config['fields']['id_unit_kerja_pengelola']);
$managerSelect = $hasSkpdManager ? ',manager.kode_skpd manager_code,manager.nama_skpd manager_name' : '';
$joins = " FROM {$table} d LEFT JOIN rai r ON r.id=d.id_rai LEFT JOIN users creator ON creator.id=d.created_by LEFT JOIN users updater ON updater.id=d.updated_by";
if ($hasSkpdManager) $joins .= ' LEFT JOIN skpd manager ON manager.id=d.id_unit_kerja_pengelola';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$referenceParts = [];
foreach ($config['references'] as $index => $reference) {
    $referenceParts[] = "(SELECT COUNT(*) FROM {$reference['table']} x{$index} WHERE x{$index}.{$reference['column']}=d.id)";
}
$referenceSql = $referenceParts ? implode('+', $referenceParts) : '0';
$orderBy = $config['order_by'] ?? 'd.id DESC';
$stmt = db()->prepare("SELECT d.*,r.kode_rai_4,r.nama_rai_4,creator.name created_by_name,updater.name updated_by_name{$managerSelect},({$referenceSql}) reference_count" . $joins . $whereSql . " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset");
foreach ($queryParams as $key => $value) $stmt->bindValue(':' . $key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
if (!empty($config['multi_network'])) {
    $linkStmt = db()->prepare('SELECT id_dai_jaringan_intra FROM dai_fasilitas_komputasi_jaringan_intra WHERE id_dai_fasilitas_komputasi=:id');
    foreach ($rows as &$row) {
        $linkStmt->execute(['id' => $row['id']]);
        $row['jaringan_ids'] = array_map('intval', $linkStmt->fetchAll(PDO::FETCH_COLUMN));
    }
    unset($row);
}
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-red-700">Domain Arsitektur Infrastruktur</p><h1 class="mt-1 text-xl font-bold"><?= e($config['title']) ?></h1><p class="mt-1 text-sm text-slate-500"><?= e($config['description']) ?></p></div>
        <div class="flex gap-2"><?php if (!empty($config['print_url'])): ?><a href="<?= e($config['print_url']) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i>Cetak</a><?php endif; ?><button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800" data-dai-add><i data-lucide="plus" class="h-4 w-4"></i>Tambah Data</button></div>
    </div>
    <?php if ($formErrors): ?><div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Data belum bisa disimpan.</p><ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-slate-900">Daftar <?= e($config['title']) ?></h2><p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p></div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row"><input type="hidden" name="page" value="<?= e($page) ?>"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari data infrastruktur..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64"></div><select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman"><?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?></select><button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button><?php if ($search !== ''): ?><a href="index.php?page=<?= e($page) ?>" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?></form>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><?php foreach ($config['columns'] as $column): ?><th class="px-2.5 py-2.5"><?= e($column['label']) ?></th><?php endforeach; ?><th class="px-2.5 py-2.5 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        <?php if (!$rows): ?><tr><td colspan="<?= count($config['columns']) + 1 ?>" class="p-10 text-center text-sm text-slate-500">Belum ada data <?= e($config['title']) ?>.</td></tr><?php endif; ?>
        <?php foreach ($rows as $index => $row):
            $record = ['id' => (int) $row['id'], 'rai_label' => dai_ref_label($row, 'kode_rai_4', 'nama_rai_4'), 'jaringan_ids' => $row['jaringan_ids'] ?? []];
            foreach ($fieldNames as $field) $record[$field] = (string) ($row[$field] ?? '');
        ?>
            <tr class="align-top hover:bg-slate-50/70">
                <?php foreach ($config['columns'] as $column):
                    $columnType = $column['type'] ?? 'field';
                    if ($columnType === 'code') {
                        $value = sprintf('DAI-%03d', (int) $row['id']);
                    } elseif ($columnType === 'rai') {
                        $value = $record['rai_label'];
                    } elseif ($columnType === 'skpd') {
                        $value = dai_ref_label($row, 'manager_code', 'manager_name');
                    } else {
                        $value = (string) ($row[$column['field']] ?? '');
                    }
                ?>
                    <td class="max-w-[260px] whitespace-normal p-3 <?= in_array($columnType, ['code', 'rai'], true) ? 'font-semibold text-red-700' : 'text-slate-700' ?>">
                        <?php if (!empty($column['description'])): ?>
                            <p class="font-semibold text-slate-900"><?= e($value ?: '-') ?></p>
                            <p class="mt-1 whitespace-pre-line text-[11px] leading-relaxed text-slate-500"><?= e((string) ($row[$column['description']] ?? '') ?: '-') ?></p>
                        <?php else: ?>
                            <?= e($value ?: '-') ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <td class="p-3"><div class="flex justify-end gap-1.5"><button type="button" class="dai-action" title="Lihat" data-dai-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button><button type="button" class="dai-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-dai-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button><button type="button" class="dai-action border-red-200 bg-red-50 text-red-700 disabled:opacity-40" title="<?= (int) $row['reference_count'] ? 'Data masih digunakan' : 'Hapus' ?>" data-dai-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($row[$config['name']]) ?>" <?= (int) $row['reference_count'] ? 'disabled' : '' ?>><i data-lucide="trash-2"></i></button></div></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm text-slate-500"><p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p><div class="flex gap-2"><?php if ($currentPage > 1): ?><a class="rounded-md border px-3 py-2" href="<?= e(dai_page_url($page, $currentPage - 1, $search, $perPage)) ?>">Sebelumnya</a><?php endif; ?><?php if ($currentPage < $totalPages): ?><a class="rounded-md border px-3 py-2" href="<?= e(dai_page_url($page, $currentPage + 1, $search, $perPage)) ?>">Berikutnya</a><?php endif; ?></div></div>
    </div>
</section>

<style>.dai-label{display:block;margin-bottom:.25rem;font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b}.dai-control{width:100%;border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;padding:.5rem .75rem;font-size:.875rem}.dai-control:focus{border-color:#dc2626;box-shadow:0 0 0 4px rgb(254 226 226/.7);outline:none}.dai-action{display:inline-flex;width:2rem;height:2rem;align-items:center;justify-content:center;border-width:1px;border-radius:.375rem}.dai-action svg{width:.875rem;height:.875rem}.dai-detail{border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;padding:1rem}.dai-detail b{font-size:.7rem;text-transform:uppercase;color:#64748b}.dai-detail p{margin-top:.4rem;font-size:.875rem}</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dai-form"><form method="post" class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"><?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-dai-action><input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-dai-field="id"><header class="flex items-start justify-between border-b px-5 py-4"><div class="flex gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i data-lucide="<?= e($config['icon']) ?>" class="h-4 w-4"></i></span><div><h2 class="font-bold" data-dai-title>Tambah <?= e($config['title']) ?></h2><p class="text-xs text-slate-500">Lengkapi data dan relasi arsitektur infrastruktur.</p></div></div><button type="button" data-modal-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50/70 p-5 md:grid-cols-2">
<?php foreach ($fieldConfigs as $field => $definition): $type = $definition['type']; if ($type === 'security') continue; $full = in_array($type, ['textarea', 'rai'], true); ?><label class="<?= $full ? 'md:col-span-2' : '' ?>"><span class="dai-label"><?= e($definition['label']) ?></span>
<?php if ($type === 'textarea'): ?><textarea name="<?= e($field) ?>" rows="3" class="dai-control" data-dai-field="<?= e($field) ?>"></textarea>
<?php elseif ($type === 'select'): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>" <?= !empty($definition['required']) ? 'required' : '' ?>><option value="">Tidak dipilih</option><?php foreach ($definition['options'] as $option): ?><option value="<?= e($option) ?>"><?= e($option) ?></option><?php endforeach; ?></select>
<?php elseif ($type === 'rai'): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>" required><option value="">Pilih RAI</option><?php foreach ($raiList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(dai_ref_label($item, 'kode_rai_4', 'nama_rai_4')) ?></option><?php endforeach; ?></select>
<?php elseif ($type === 'skpd'): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>"><option value="">Tidak dipilih</option><?php foreach ($skpdList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(dai_ref_label($item, 'kode_skpd', 'nama_skpd')) ?></option><?php endforeach; ?></select>
<?php elseif ($type === 'network'): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>"><option value="">Tidak dipilih</option><?php foreach ($networkList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama_jaringan']) ?></option><?php endforeach; ?></select>
<?php elseif (isset($relationLists[$type])): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>"><option value="">Tidak dipilih</option><?php foreach ($relationLists[$type] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama']) ?></option><?php endforeach; ?></select>
<?php elseif ($type === 'security'): ?><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>"><option value="">Tidak dipilih</option><?php foreach ($securityLists[$field] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama']) ?></option><?php endforeach; ?></select>
<?php else: ?><input name="<?= e($field) ?>" type="<?= e($type) ?>" class="dai-control" data-dai-field="<?= e($field) ?>" <?= !empty($definition['required']) ? 'required' : '' ?> <?= isset($definition['maxlength']) ? 'maxlength="' . (int) $definition['maxlength'] . '"' : '' ?> <?= isset($definition['step']) ? 'step="' . e($definition['step']) . '" min="0"' : '' ?>>
<?php endif; ?></label><?php endforeach; ?>
<?php if (!empty($config['multi_network'])): ?><label class="md:col-span-2"><span class="dai-label">Jaringan Intra yang Digunakan</span><select name="jaringan_ids[]" multiple size="4" class="dai-control" data-dai-field="jaringan_ids"><?php foreach ($networkList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama_jaringan']) ?></option><?php endforeach; ?></select><span class="mt-1 block text-xs text-slate-500">Gunakan Ctrl/Cmd untuk memilih lebih dari satu.</span></label><?php endif; ?>
<fieldset class="border-t border-slate-200 pt-4 md:col-span-2"><legend class="pr-3 text-sm font-semibold text-slate-900">Kontrol Keamanan (DAK)</legend><p class="mt-1 text-xs text-slate-500">Pilih referensi keamanan yang terkait dengan data infrastruktur.</p><div class="mt-3 grid gap-3 md:grid-cols-2"><?php foreach ($securityFields as $field => $label): ?><label><span class="dai-label"><?= e($label) ?></span><select name="<?= e($field) ?>" class="dai-control" data-dai-field="<?= e($field) ?>"><option value="">Tidak dipilih</option><?php foreach ($securityLists[$field] as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama']) ?></option><?php endforeach; ?></select></label><?php endforeach; ?></div></fieldset>
</div><footer class="flex justify-end gap-2 border-t px-5 py-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white"><span data-dai-submit>Simpan Data</span></button></footer></form></div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dai-view"><div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white"><header class="flex justify-between border-b p-4"><h2 class="text-lg font-bold">Detail <?= e($config['title']) ?></h2><button data-modal-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-4 md:grid-cols-2"><div class="dai-detail md:col-span-2"><b>Referensi RAI</b><p data-view-field="rai_label"></p></div><?php foreach ($fieldConfigs as $field => $definition): ?><div class="dai-detail <?= $definition['type'] === 'textarea' ? 'md:col-span-2' : '' ?>"><b><?= e($definition['label']) ?></b><p class="whitespace-pre-line" data-view-field="<?= e($field) ?>"></p></div><?php endforeach; ?></div></div></div>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dai-delete"><form method="post" class="w-full max-w-md rounded-2xl bg-white p-6"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Data?</h2><p class="mt-2 text-sm text-slate-500">Tindakan ini permanen dan dicatat dalam audit trail.</p><p class="mt-4 rounded-xl bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div></form></div>

<script>
(() => {
    const modals=document.querySelectorAll('[data-modal]'),form=document.querySelector('[data-modal="dai-form"]'),view=document.querySelector('[data-modal="dai-view"]'),del=document.querySelector('[data-modal="dai-delete"]');
    const fields=<?= json_encode(array_merge(['id'], $fieldNames, !empty($config['multi_network']) ? ['jaringan_ids'] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,posted=<?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const input=name=>form.querySelector(`[data-dai-field="${name}"]`),open=modal=>{modal.classList.remove('hidden');modal.classList.add('flex');document.body.classList.add('overflow-hidden')},close=modal=>{modal.classList.add('hidden');modal.classList.remove('flex');if(![...modals].some(x=>!x.classList.contains('hidden')))document.body.classList.remove('overflow-hidden')};
    const fill=record=>fields.forEach(name=>{const element=input(name);if(!element)return;if(element.multiple){const values=(record[name]||[]).map(String);[...element.options].forEach(option=>option.selected=values.includes(option.value))}else element.value=record[name]||''});
    document.querySelector('[data-dai-add]')?.addEventListener('click',()=>{fill({});form.querySelector('[data-dai-action]').value='create';form.querySelector('[data-dai-title]').textContent=<?= json_encode('Tambah ' . $config['title']) ?>;form.querySelector('[data-dai-submit]').textContent='Simpan Data';open(form)});
    document.querySelectorAll('[data-dai-edit]').forEach(button=>button.addEventListener('click',()=>{fill(JSON.parse(button.dataset.record||'{}'));form.querySelector('[data-dai-action]').value='update';form.querySelector('[data-dai-title]').textContent=<?= json_encode('Edit ' . $config['title']) ?>;form.querySelector('[data-dai-submit]').textContent='Simpan Perubahan';open(form)}));
    document.querySelectorAll('[data-dai-view]').forEach(button=>button.addEventListener('click',()=>{const record=JSON.parse(button.dataset.record||'{}');view.querySelectorAll('[data-view-field]').forEach(element=>element.textContent=record[element.dataset.viewField]||'—');open(view)}));
    document.querySelectorAll('[data-dai-delete]').forEach(button=>button.addEventListener('click',()=>{del.querySelector('[data-delete-id]').value=button.dataset.id;del.querySelector('[data-delete-name]').textContent=button.dataset.name;open(del)}));
    document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>close(button.closest('[data-modal]'))));
    modals.forEach(modal=>modal.addEventListener('click',event=>{if(event.target===modal)close(modal)}));
    document.addEventListener('keydown',event=>{if(event.key==='Escape')modals.forEach(close)});
    <?php if ($openFormModal): ?>fill(posted);form.querySelector('[data-dai-action]').value=<?= json_encode($formMode) ?>;form.querySelector('[data-dai-title]').textContent=<?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . $config['title']) ?>;open(form);<?php endif; ?>
})();
</script>
