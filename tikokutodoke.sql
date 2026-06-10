-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: localhost
-- 生成日時: 2026 年 6 月 10 日 10:38
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
-- テーブルの構造 `class_teacher`
--

CREATE TABLE `class_teacher` (
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `c_teacher_mail` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `class_teacher`
--

INSERT INTO `class_teacher` (`grade`, `class`, `name`, `c_teacher_mail`) VALUES
(1, 1, '山田太郎', 'unibamataikitai807@gmail.com');

-- --------------------------------------------------------

--
-- テーブルの構造 `error_history`
--

CREATE TABLE `error_history` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `error` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `error_history`
--

INSERT INTO `error_history` (`id`, `date`, `error`) VALUES
(1, '2026-06-10 03:12:54', 'メール送信エラー: クラス担任のメールアドレスが見つかりません。');

-- --------------------------------------------------------

--
-- テーブルの構造 `lateness_history`
--

CREATE TABLE `lateness_history` (
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
-- テーブルのデータのダンプ `lateness_history`
--

INSERT INTO `lateness_history` (`id`, `grade`, `class`, `number`, `name`, `date`, `week`, `time`, `late_count`, `reason`) VALUES
(1, 1, 1, 1, '田中太郎', '2026-05-20', '水', '22:51:21', 1, '遅延');

-- --------------------------------------------------------

--
-- テーブルの構造 `student_info`
--

CREATE TABLE `student_info` (
  `student_id` int(11) NOT NULL,
  `grade` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `late_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `student_info`
--

INSERT INTO `student_info` (`student_id`, `grade`, `class`, `number`, `name`, `late_count`) VALUES
(000000001, 1, 1, 1, '田中太郎', 1),
(000000002, 1, 1, 2, '田中次郎', 2),
(000000003, 1, 1, 3, '田中三郎', 3),
(000000004, 1, 2, 1, '田山太郎', 1),
(000000005, 2, 1, 1, '鈴中太郎', 1),
(000000006, 3, 1, 1, '山中太郎', 1),
(224010032, 3, 4, 5, '梅山直輝', 1);-- 実際に自身のデータを入れ、生徒証が反応するか確認

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `class_teacher`
--
ALTER TABLE `class_teacher`
  ADD PRIMARY KEY (`c_teacher_mail`);

--
-- テーブルのインデックス `error_history`
--
ALTER TABLE `error_history`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `lateness_history`
--
ALTER TABLE `lateness_history`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `student_info`
--
ALTER TABLE `student_info`
  ADD PRIMARY KEY (`student_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `error_history`
--
ALTER TABLE `error_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `lateness_history`
--
ALTER TABLE `lateness_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
