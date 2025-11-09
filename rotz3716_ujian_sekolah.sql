-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 09, 2025 at 02:31 PM
-- Server version: 10.11.6-MariaDB-cll-lve
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rotz3716_ujian_sekolah`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama_lengkap`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$aZmG.8BXUy9/y3Dbb1ePb.f6.bmr9u8j.DHHj6tjosAC8Xo5lQE/.', 'Administrator', 'admin@ujiansekolah.com', 'aktif', '2025-09-23 22:06:35', '2025-09-23 22:13:57');

-- --------------------------------------------------------

--
-- Table structure for table `gambar_pertanyaan`
--

CREATE TABLE `gambar_pertanyaan` (
  `id` int(11) NOT NULL,
  `id_pertanyaan` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gambar_pertanyaan`
--

INSERT INTO `gambar_pertanyaan` (`id`, `id_pertanyaan`, `nama_file`) VALUES
(27, 81, 'img_690b9cb489c395.95030320.png');

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'hawami', '$2y$10$aZmG.8BXUy9/y3Dbb1ePb.f6.bmr9u8j.DHHj6tjosAC8Xo5lQE/.', '2025-07-14 00:22:26');

-- --------------------------------------------------------

--
-- Table structure for table `jawaban_siswa`
--

CREATE TABLE `jawaban_siswa` (
  `id` int(11) NOT NULL,
  `id_partisipasi` int(11) NOT NULL,
  `id_pertanyaan` int(11) NOT NULL,
  `id_opsi_jawaban` int(11) DEFAULT NULL,
  `jawaban_essay` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jawaban_siswa`
--

INSERT INTO `jawaban_siswa` (`id`, `id_partisipasi`, `id_pertanyaan`, `id_opsi_jawaban`, `jawaban_essay`) VALUES
(85, 62, 62, 210, NULL),
(86, 62, 63, 268, NULL),
(87, 62, 65, 439, NULL),
(88, 62, 67, 242, NULL),
(89, 62, 68, 427, NULL),
(90, 62, 69, 273, NULL),
(91, 62, 70, 275, NULL),
(92, 62, 71, 419, NULL),
(93, 62, 83, 348, NULL),
(94, 62, 84, 390, NULL),
(95, 66, 62, 210, NULL),
(96, 66, 63, 268, NULL),
(97, 66, 65, 440, NULL),
(98, 66, 67, 242, NULL),
(99, 66, 68, 427, NULL),
(100, 66, 69, 273, NULL),
(101, 66, 70, 275, NULL),
(102, 66, 71, 419, NULL),
(103, 66, 83, 348, NULL),
(104, 66, 84, 389, NULL),
(105, 59, 62, 210, NULL),
(106, 59, 63, 268, NULL),
(107, 59, 65, 440, NULL),
(108, 59, 67, 242, NULL),
(109, 59, 68, 427, NULL),
(110, 59, 69, 273, NULL),
(111, 59, 70, 275, NULL),
(112, 59, 71, 419, NULL),
(113, 59, 83, 348, NULL),
(114, 59, 84, 389, NULL),
(115, 56, 62, 210, NULL),
(116, 56, 63, 268, NULL),
(117, 56, 65, 440, NULL),
(118, 56, 67, 242, NULL),
(119, 56, 68, 427, NULL),
(120, 56, 69, 273, NULL),
(121, 56, 70, 275, NULL),
(122, 56, 71, 419, NULL),
(123, 56, 83, 348, NULL),
(124, 56, 84, 389, NULL),
(125, 73, 62, 210, NULL),
(126, 73, 63, 268, NULL),
(127, 73, 65, 440, NULL),
(128, 73, 67, 242, NULL),
(129, 73, 68, 427, NULL),
(130, 73, 69, 273, NULL),
(131, 73, 70, 275, NULL),
(132, 73, 71, 419, NULL),
(133, 73, 83, 348, NULL),
(134, 73, 84, 389, NULL),
(135, 69, 62, 210, NULL),
(136, 69, 63, 268, NULL),
(137, 69, 65, 440, NULL),
(138, 69, 67, 242, NULL),
(139, 69, 68, 427, NULL),
(140, 69, 69, 273, NULL),
(141, 69, 70, 275, NULL),
(142, 69, 71, 419, NULL),
(143, 69, 83, 348, NULL),
(144, 69, 84, 389, NULL),
(145, 68, 62, 210, NULL),
(146, 68, 63, 268, NULL),
(147, 68, 65, 440, NULL),
(148, 68, 67, 242, NULL),
(149, 68, 68, 427, NULL),
(150, 68, 69, 273, NULL),
(151, 68, 70, 275, NULL),
(152, 68, 71, 419, NULL),
(153, 68, 83, 348, NULL),
(154, 68, 84, 389, NULL),
(155, 65, 62, 210, NULL),
(156, 65, 63, 268, NULL),
(157, 65, 65, 440, NULL),
(158, 65, 67, 242, NULL),
(159, 65, 68, 427, NULL),
(160, 65, 69, 273, NULL),
(161, 65, 70, 275, NULL),
(162, 65, 71, 419, NULL),
(163, 65, 83, 347, NULL),
(164, 65, 84, 389, NULL),
(165, 67, 62, 210, NULL),
(166, 67, 63, 268, NULL),
(167, 67, 65, 440, NULL),
(168, 67, 67, 242, NULL),
(169, 67, 68, 427, NULL),
(170, 67, 69, 273, NULL),
(171, 67, 70, 275, NULL),
(172, 67, 71, 419, NULL),
(173, 67, 83, 348, NULL),
(174, 67, 84, 389, NULL),
(175, 63, 62, 210, NULL),
(176, 63, 63, 268, NULL),
(177, 63, 65, 440, NULL),
(178, 63, 67, 242, NULL),
(179, 63, 68, 427, NULL),
(180, 63, 69, 273, NULL),
(181, 63, 70, 275, NULL),
(182, 63, 71, 419, NULL),
(183, 63, 83, 348, NULL),
(184, 63, 84, 389, NULL),
(185, 71, 62, 210, NULL),
(186, 71, 63, 268, NULL),
(187, 71, 65, 440, NULL),
(188, 71, 67, 242, NULL),
(189, 71, 68, 427, NULL),
(190, 71, 69, 273, NULL),
(191, 71, 70, 275, NULL),
(192, 71, 71, 419, NULL),
(193, 71, 83, 348, NULL),
(194, 71, 84, 389, NULL),
(195, 77, 62, 210, NULL),
(196, 77, 63, 269, NULL),
(197, 77, 65, 439, NULL),
(198, 77, 67, 242, NULL),
(199, 77, 68, 426, NULL),
(200, 77, 69, 273, NULL),
(201, 77, 70, 275, NULL),
(202, 77, 71, 419, NULL),
(203, 77, 83, 348, NULL),
(204, 77, 84, 389, NULL),
(205, 57, 62, 210, NULL),
(206, 57, 63, 268, NULL),
(207, 57, 65, 439, NULL),
(208, 57, 67, 242, NULL),
(209, 57, 68, 426, NULL),
(210, 57, 69, 273, NULL),
(211, 57, 70, 275, NULL),
(212, 57, 71, 275, NULL),
(213, 57, 83, 348, NULL),
(214, 57, 84, 389, NULL),
(215, 61, 62, 210, NULL),
(216, 61, 63, 268, NULL),
(217, 61, 65, 439, NULL),
(218, 61, 67, 242, NULL),
(219, 61, 68, 426, NULL),
(220, 61, 69, 273, NULL),
(221, 61, 70, 275, NULL),
(222, 61, 71, 419, NULL),
(223, 61, 83, 347, NULL),
(224, 61, 84, 389, NULL),
(225, 70, 62, 210, NULL),
(226, 70, 63, 268, NULL),
(227, 70, 65, 440, NULL),
(228, 70, 67, 242, NULL),
(229, 70, 68, 427, NULL),
(230, 70, 69, 273, NULL),
(231, 70, 70, 275, NULL),
(232, 70, 71, 419, NULL),
(233, 70, 83, 348, NULL),
(234, 70, 84, 389, NULL),
(235, 60, 62, 210, NULL),
(236, 60, 63, 268, NULL),
(237, 60, 65, 440, NULL),
(238, 60, 67, 242, NULL),
(239, 60, 68, 427, NULL),
(240, 60, 69, 273, NULL),
(241, 60, 70, 275, NULL),
(242, 60, 71, 419, NULL),
(243, 60, 83, 348, NULL),
(244, 60, 84, 389, NULL),
(245, 76, 62, 210, NULL),
(246, 76, 63, 268, NULL),
(247, 76, 65, 440, NULL),
(248, 76, 67, 242, NULL),
(249, 76, 68, 427, NULL),
(250, 76, 69, 273, NULL),
(251, 76, 70, 275, NULL),
(252, 76, 71, 420, NULL),
(253, 76, 83, 348, NULL),
(254, 76, 84, 389, NULL),
(255, 80, 62, 210, NULL),
(256, 80, 63, 268, NULL),
(257, 80, 65, 440, NULL),
(258, 80, 67, 242, NULL),
(259, 80, 68, 427, NULL),
(260, 80, 69, 273, NULL),
(261, 80, 70, NULL, NULL),
(262, 80, 71, 419, NULL),
(263, 80, 83, 348, NULL),
(264, 80, 84, 389, NULL),
(265, 72, 62, 210, NULL),
(266, 72, 63, 268, NULL),
(267, 72, 65, 440, NULL),
(268, 72, 67, 242, NULL),
(269, 72, 68, 427, NULL),
(270, 72, 69, 273, NULL),
(271, 72, 70, 275, NULL),
(272, 72, 71, 419, NULL),
(273, 72, 83, 348, NULL),
(274, 72, 84, 389, NULL),
(275, 64, 62, 210, NULL),
(276, 64, 63, 267, NULL),
(277, 64, 65, 440, NULL),
(278, 64, 67, 242, NULL),
(279, 64, 68, 427, NULL),
(280, 64, 69, 273, NULL),
(281, 64, 70, 275, NULL),
(282, 64, 71, 419, NULL),
(283, 64, 83, 346, NULL),
(284, 64, 84, 389, NULL),
(285, 58, 62, 210, NULL),
(286, 58, 63, 268, NULL),
(287, 58, 65, 440, NULL),
(288, 58, 67, 242, NULL),
(289, 58, 68, 427, NULL),
(290, 58, 69, 273, NULL),
(291, 58, 70, 275, NULL),
(292, 58, 71, 419, NULL),
(293, 58, 83, 348, NULL),
(294, 58, 84, 389, NULL),
(295, 74, 62, 210, NULL),
(296, 74, 63, NULL, NULL),
(297, 74, 65, 441, NULL),
(298, 74, 67, 242, NULL),
(299, 74, 68, 429, NULL),
(300, 74, 69, 273, NULL),
(301, 74, 70, 275, NULL),
(302, 74, 71, 419, NULL),
(303, 74, 83, 348, NULL),
(304, 74, 84, 389, NULL),
(305, 75, 62, 210, NULL),
(306, 75, 63, 268, NULL),
(307, 75, 65, 440, NULL),
(308, 75, 67, 242, NULL),
(309, 75, 68, 427, NULL),
(310, 75, 69, 273, NULL),
(311, 75, 70, 275, NULL),
(312, 75, 71, 419, NULL),
(313, 75, 83, 348, NULL),
(314, 75, 84, 390, NULL),
(315, 78, 62, NULL, NULL),
(316, 78, 63, 268, NULL),
(317, 78, 65, 440, NULL),
(318, 78, 67, 242, NULL),
(319, 78, 68, 427, NULL),
(320, 78, 69, 273, NULL),
(321, 78, 70, 275, NULL),
(322, 78, 71, 419, NULL),
(323, 78, 83, 348, NULL),
(324, 78, 84, 389, NULL),
(325, 81, 62, 210, NULL),
(326, 81, 63, 268, NULL),
(327, 81, 65, 242, NULL),
(328, 81, 67, 242, NULL),
(329, 81, 68, 427, NULL),
(330, 81, 69, 242, NULL),
(331, 81, 70, 275, NULL),
(332, 81, 71, 242, NULL),
(333, 81, 83, 348, NULL),
(334, 81, 84, 389, NULL),
(335, 79, 62, 210, NULL),
(336, 79, 63, 268, NULL),
(337, 79, 65, 440, NULL),
(338, 79, 67, 242, NULL),
(339, 79, 68, 427, NULL),
(340, 79, 69, 273, NULL),
(341, 79, 70, 278, NULL),
(342, 79, 71, 419, NULL),
(343, 79, 83, 348, NULL),
(344, 79, 84, 390, NULL),
(345, 82, 62, 210, NULL),
(346, 82, 63, 268, NULL),
(347, 82, 65, 440, NULL),
(348, 82, 67, 242, NULL),
(349, 82, 68, 427, NULL),
(350, 82, 69, 273, NULL),
(351, 82, 70, 275, NULL),
(352, 82, 71, 419, NULL),
(353, 82, 83, 346, NULL),
(354, 82, 84, 389, NULL),
(355, 83, 62, NULL, NULL),
(356, 83, 63, NULL, NULL),
(357, 83, 65, NULL, NULL),
(358, 83, 67, NULL, NULL),
(359, 83, 68, NULL, NULL),
(360, 83, 69, NULL, NULL),
(361, 83, 70, NULL, NULL),
(362, 83, 71, NULL, NULL),
(363, 83, 83, NULL, NULL),
(364, 83, 84, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `opsi_jawaban`
--

CREATE TABLE `opsi_jawaban` (
  `id` int(11) NOT NULL,
  `id_pertanyaan` int(11) NOT NULL,
  `teks_opsi` varchar(255) DEFAULT NULL,
  `gambar_opsi` varchar(255) DEFAULT NULL,
  `adalah_jawaban_benar` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `opsi_jawaban`
--

INSERT INTO `opsi_jawaban` (`id`, `id_pertanyaan`, `teks_opsi`, `gambar_opsi`, `adalah_jawaban_benar`) VALUES
(210, 62, 'Angklung', NULL, 1),
(211, 62, 'Drum', NULL, 0),
(212, 62, 'Biola', NULL, 0),
(213, 62, 'Semua Salah', NULL, 0),
(240, 67, 'Menerapi para murid agar lebih relaks', NULL, 0),
(241, 67, 'Sebagai jembatan menjadi Musisi', NULL, 0),
(242, 67, 'Mengajarkan Alat musik tradisional', NULL, 1),
(243, 67, 'Agar ahli bermain musik', NULL, 0),
(267, 63, 'Drum', NULL, 0),
(268, 63, 'Kendang', NULL, 1),
(269, 63, 'Tamborin', NULL, 0),
(270, 63, 'Bass', NULL, 0),
(271, 69, 'Orang yang sedang menonton konser ', NULL, 0),
(272, 69, 'Sekumpulan orang menonton pagelaran seni musik', NULL, 0),
(273, 69, 'Sekumpulan orang bermain alat musik Tradisional', NULL, 1),
(274, 69, '1 orang bermain alat musik', NULL, 0),
(275, 70, 'Sebagai Pengiring Tarian ', NULL, 1),
(276, 70, 'Sebagai Pelengkap', NULL, 0),
(277, 70, 'Karna Sudah menjadi tradisi', NULL, 0),
(278, 70, 'Agar tidak membuat orang bosan melihat Penari.', NULL, 0),
(314, 81, 'asd', NULL, 0),
(315, 81, 'asd', NULL, 1),
(316, 81, 'asd', NULL, 0),
(317, 81, 'asd', NULL, 0),
(318, 81, 'asd', NULL, 0),
(333, 82, NULL, 'opt_690b9d5182cac3.54600262.png', 0),
(334, 82, NULL, 'opt_690b9d5182fe41.74162336.png', 1),
(335, 82, NULL, 'opt_690b9dc0f23810.17669446.png', 0),
(346, 83, 'mendengarkan lagu sambil olahraga', NULL, 0),
(347, 83, 'Menyanyikan Lagu Indonesia Raya Pada Saat upacara', NULL, 0),
(348, 83, 'Pukulan bedug sebagai penanda akan memulai Adzan', NULL, 1),
(349, 83, 'Bermain Games RPG', NULL, 0),
(388, 84, 'Menjadi Peramai acara', NULL, 0),
(389, 84, 'Sebagai Pengiring Musik dari awal hingga akhir', NULL, 1),
(390, 84, 'Karna sudah menjadi tradisi', NULL, 0),
(391, 84, 'Agar semua orang bisa main alat musik tradisional', NULL, 0),
(417, 71, NULL, 'opt_690bd8dde4af67.62946159.jfif', 0),
(418, 71, NULL, 'opt_690bd9129b3ce1.64984568.jpg', 0),
(419, 71, NULL, 'opt_690bd9d73e33c5.52732911.jfif', 1),
(420, 71, NULL, 'opt_690bda1ee76528.20003711.jfif', 0),
(426, 68, NULL, 'opt_690b81a3bcae96.48944202.jfif', 0),
(427, 68, NULL, 'opt_690b81a3bcd2d0.24104168.jfif', 1),
(428, 68, NULL, 'opt_690b81d7c8c3b4.47424487.jfif', 0),
(429, 68, NULL, 'opt_690bda9c7312f9.52693876.jfif', 0),
(439, 65, NULL, 'opt_690b7e4264d3a9.02029360.jpg', 0),
(440, 65, NULL, 'opt_690b7e4264f456.71245803.jfif', 1),
(441, 65, NULL, 'opt_690b7e7d582031.84512969.jpg', 0),
(442, 65, NULL, 'opt_690bdafa256e42.51318032.jfif', 0);

-- --------------------------------------------------------

--
-- Table structure for table `partisipasi_siswa`
--

CREATE TABLE `partisipasi_siswa` (
  `id` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `nama_siswa` varchar(150) NOT NULL,
  `kelas_siswa` varchar(50) NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `skor` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partisipasi_siswa`
--

INSERT INTO `partisipasi_siswa` (`id`, `id_ujian`, `nama_siswa`, `kelas_siswa`, `waktu_selesai`, `skor`) VALUES
(56, 25, 'Cinta Salsabila', '8.12', '2025-11-06 13:49:54', 100.00),
(57, 25, 'Raden Fatih Hamizan', '8.12', '2025-11-06 13:50:43', 70.00),
(58, 25, 'Ramsey Trisnawa Daradjat', '8.12', '2025-11-06 13:52:45', 100.00),
(59, 25, 'Selby Regina Putri', '8.12', '2025-11-06 13:49:50', 100.00),
(60, 25, 'muhammad amru asshyidik', '8.12', '2025-11-06 13:51:00', 100.00),
(61, 25, 'Najma Aila Bahri', '8.12', '2025-11-06 13:50:46', 70.00),
(62, 25, 'Hasna Hanifah Khayyirah Bintoro Putri', '8.12', '2025-11-06 13:49:30', 80.00),
(63, 25, 'Elka Odelia Maharani', '812', '2025-11-06 13:50:29', 100.00),
(64, 25, 'Afif Farista Andi Atjo', '8.12', '2025-11-06 13:52:03', 80.00),
(65, 25, 'Alya Nadhifah Aulia', '8.12', '2025-11-06 13:50:20', 90.00),
(66, 25, 'Lionel Arimatea Sabuna', '8.12', '2025-11-06 13:49:44', 100.00),
(67, 25, 'Satria Damarudin', '8.12', '2025-11-06 13:50:24', 100.00),
(68, 25, 'Nirvana Putri', '8.12', '2025-11-06 13:50:19', 100.00),
(69, 25, 'Almira Kanaya', '8.12', '2025-11-06 13:50:19', 100.00),
(70, 25, 'Fatimah Zahra Arif', '8.12', '2025-11-06 13:50:55', 100.00),
(71, 25, 'Alaric satria syumanjaya', '8.6', '2025-11-06 13:50:38', 100.00),
(72, 25, 'Muhammad Ziqri Alfaizi', '8.12', '2025-11-06 13:51:10', 100.00),
(73, 25, 'farid evan fadilah', '8.12', '2025-11-06 13:49:55', 100.00),
(74, 25, 'azema safira setiawan', '8.12', '2025-11-06 13:52:53', 70.00),
(75, 25, 'Salma Atha Ayudia', '8.12', '2025-11-06 13:53:01', 90.00),
(76, 25, 'Cinta Kallysa', '8.12', '2025-11-06 13:51:04', 90.00),
(77, 25, 'MUHAMMAD ALFIANANDO NURRACHMAN', '8.12', '2025-11-06 13:50:38', 70.00),
(78, 25, 'Fairuz Prayoga', '8.12', '2025-11-06 13:53:26', 90.00),
(79, 25, 'Zola Alfathu Kodar', '8.12', '2025-11-06 13:53:44', 80.00),
(80, 25, 'jasmine putri nafira', '8.12', '2025-11-06 13:51:08', 90.00),
(81, 25, 'Alaric satria syumanjaya', '8.12', '2025-11-06 13:53:29', 70.00),
(82, 25, 'Ziqri ganteng', '8.12', '2025-11-06 13:55:34', 90.00),
(83, 25, 'rizal ajah', '8.12', '2025-11-06 14:15:19', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `pertanyaan`
--

CREATE TABLE `pertanyaan` (
  `id` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `tipe_soal` enum('pilihan_ganda','essay','pilihan_ganda_gambar') NOT NULL DEFAULT 'pilihan_ganda',
  `teks_pertanyaan` text NOT NULL,
  `skor` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pertanyaan`
--

INSERT INTO `pertanyaan` (`id`, `id_ujian`, `tipe_soal`, `teks_pertanyaan`, `skor`) VALUES
(62, 25, 'pilihan_ganda', 'Manakah yang termasuk alat musik tradisional Indonesia?', 10),
(63, 25, 'pilihan_ganda', 'Nama Alat musik tradisional daerah Jawa Barat yang cara memainkan nya dengan cara dipukul yaitu?', 10),
(65, 25, 'pilihan_ganda_gambar', 'Manakah yang termasuk alat musik Ketimpung?', 10),
(67, 25, 'pilihan_ganda', 'Contoh Fungsi Musik Tradisional dalam Sarana Pendidikan adalah?', 10),
(68, 25, 'pilihan_ganda_gambar', 'Manakah gambar Alat musik Serunai', 10),
(69, 25, 'pilihan_ganda', 'Ensembel Musik Tradisonal yaitu?', 10),
(70, 25, 'pilihan_ganda', 'Fungsi Musik pada Tarian yaitu?', 10),
(71, 25, 'pilihan_ganda_gambar', 'Manakah alat musik Dol', 10),
(81, 28, 'pilihan_ganda', 'asd', 10),
(82, 28, 'pilihan_ganda_gambar', 'manakah yang benar', 10),
(83, 25, 'pilihan_ganda', 'Fungsi Musik yang benar dibawah ini sebagai sarana komunikasi?', 10),
(84, 25, 'pilihan_ganda', 'Fungsi Musik Sebagai Upacara Adat yaitu?', 10);

-- --------------------------------------------------------

--
-- Table structure for table `ujian`
--

CREATE TABLE `ujian` (
  `id` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kode_ujian` varchar(10) NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `durasi` int(11) NOT NULL COMMENT 'Durasi dalam menit',
  `gambar_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','berlangsung','selesai') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ujian`
--

INSERT INTO `ujian` (`id`, `id_guru`, `judul`, `kode_ujian`, `waktu_mulai`, `durasi`, `gambar_pendukung`, `status`, `created_at`) VALUES
(25, 1, 'TES SOAL MATERI MUSIK TRADISIONAL', 'S1AQGP', NULL, 20, NULL, 'berlangsung', '2025-11-05 16:15:12'),
(28, 1, 'yuda', 'QFJXZD', NULL, 10, 'exam_1762363449_690b883987d93.png', 'berlangsung', '2025-11-05 17:24:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `gambar_pertanyaan`
--
ALTER TABLE `gambar_pertanyaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pertanyaan` (`id_pertanyaan`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_partisipasi` (`id_partisipasi`),
  ADD KEY `id_pertanyaan` (`id_pertanyaan`),
  ADD KEY `id_opsi_jawaban` (`id_opsi_jawaban`);

--
-- Indexes for table `opsi_jawaban`
--
ALTER TABLE `opsi_jawaban`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pertanyaan` (`id_pertanyaan`);

--
-- Indexes for table `partisipasi_siswa`
--
ALTER TABLE `partisipasi_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attempt` (`id_ujian`,`nama_siswa`,`kelas_siswa`);

--
-- Indexes for table `pertanyaan`
--
ALTER TABLE `pertanyaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indexes for table `ujian`
--
ALTER TABLE `ujian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ujian` (`kode_ujian`),
  ADD KEY `id_guru` (`id_guru`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gambar_pertanyaan`
--
ALTER TABLE `gambar_pertanyaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=365;

--
-- AUTO_INCREMENT for table `opsi_jawaban`
--
ALTER TABLE `opsi_jawaban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=446;

--
-- AUTO_INCREMENT for table `partisipasi_siswa`
--
ALTER TABLE `partisipasi_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `pertanyaan`
--
ALTER TABLE `pertanyaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `ujian`
--
ALTER TABLE `ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gambar_pertanyaan`
--
ALTER TABLE `gambar_pertanyaan`
  ADD CONSTRAINT `gambar_pertanyaan_ibfk_1` FOREIGN KEY (`id_pertanyaan`) REFERENCES `pertanyaan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD CONSTRAINT `jawaban_siswa_ibfk_1` FOREIGN KEY (`id_partisipasi`) REFERENCES `partisipasi_siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jawaban_siswa_ibfk_2` FOREIGN KEY (`id_pertanyaan`) REFERENCES `pertanyaan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jawaban_siswa_ibfk_3` FOREIGN KEY (`id_opsi_jawaban`) REFERENCES `opsi_jawaban` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `opsi_jawaban`
--
ALTER TABLE `opsi_jawaban`
  ADD CONSTRAINT `opsi_jawaban_ibfk_1` FOREIGN KEY (`id_pertanyaan`) REFERENCES `pertanyaan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partisipasi_siswa`
--
ALTER TABLE `partisipasi_siswa`
  ADD CONSTRAINT `partisipasi_siswa_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pertanyaan`
--
ALTER TABLE `pertanyaan`
  ADD CONSTRAINT `pertanyaan_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ujian`
--
ALTER TABLE `ujian`
  ADD CONSTRAINT `ujian_ibfk_1` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
