-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: localhost
-- 生成日時: 2026 年 5 月 25 日 14:49
-- サーバのバージョン： 10.4.28-MariaDB
-- PHP のバージョン: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `tikokutodoke`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `class-teacher`
--

CREATE TABLE `class-teacher` (
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `c_teacher_mail` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `class-teacher`
--

INSERT INTO `class-teacher` (`grade`, `class`, `name`, `c_teacher_mail`) VALUES
(1, 1, '山田太郎', '224010032@kousen.onmicrosoft.com');

-- --------------------------------------------------------

--
-- テーブルの構造 `lateness-history`
--

CREATE TABLE `lateness-history` (
  `id` int(11) NOT NULL,
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `week` varchar(1) NOT NULL,
  `time` time NOT NULL,
  `late_count` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `lateness-history`
--

INSERT INTO `lateness-history` (`id`, `grade`, `class`, `number`, `name`, `date`, `week`, `time`, `late_count`, `reason`) VALUES
(1, 1, 1, 1, '田中太郎', '2026-05-20', '', '22:51:21', 0, '遅延'),
(2, 1, 1, 1, '田中太郎', '2026-05-24', '日', '16:48:00', 1, '体調不良'),
(3, 1, 1, 1, '田中太郎', '2026-05-24', '日', '23:22:00', 2, '体調不良、その他、人助け'),
(4, 1, 1, 1, '田中太郎', '2026-05-24', '日', '23:27:00', 3, '体調不良、その他、人助け'),
(5, 1, 1, 2, '田中次郎', '2026-05-25', '月', '20:51:00', 1, '通院'),
(6, 1, 1, 2, '田中次郎', '2026-05-25', '月', '20:56:00', 1, '体調不良、発熱、頭痛、腹痛、嘔吐、下痢、めまい、通院、家事、電車の遅れ、寝坊、忘れ物、バスの遅れ、その他、aaaaaaaa');

-- --------------------------------------------------------

--
-- テーブルの構造 `student-info`
--

CREATE TABLE `student-info` (
  `student_id` int(11) NOT NULL,
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `late_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `student-info`
--

INSERT INTO `student-info` (`student_id`, `grade`, `class`, `number`, `name`, `late_count`) VALUES
(1, 1, 1, 1, '田中太郎', 3),
(2, 1, 1, 2, '田中次郎', 1);

-- --------------------------------------------------------

--
-- テーブルの構造 `subject-teacher`
--

CREATE TABLE `subject-teacher` (
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `weekday` varchar(1) NOT NULL,
  `period` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `s_teacher_mail` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `subject-teacher`
--

INSERT INTO `subject-teacher` (`grade`, `class`, `weekday`, `period`, `subject`, `name`, `s_teacher_mail`) VALUES
(1, 1, '月', 7, '数学1', '佐藤太郎', 'puroguramingtarou@gmail.com');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `class-teacher`
--
ALTER TABLE `class-teacher`
  ADD PRIMARY KEY (`c_teacher_mail`);

--
-- テーブルのインデックス `lateness-history`
--
ALTER TABLE `lateness-history`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `student-info`
--
ALTER TABLE `student-info`
  ADD PRIMARY KEY (`student_id`);

--
-- テーブルのインデックス `subject-teacher`
--
ALTER TABLE `subject-teacher`
  ADD PRIMARY KEY (`s_teacher_mail`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `lateness-history`
--
ALTER TABLE `lateness-history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
