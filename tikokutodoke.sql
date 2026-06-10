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
(2, '2026-06-10 03:12:54', 'メール送信エラー: クラス担任のメールアドレスが見つかりません。'),
(3, '2026-06-10 03:14:53', 'メール送信エラー: クラス担任のメールアドレスが見つかりません。'),
(4, '2026-06-10 03:15:18', 'メール送信エラー: クラス担任のメールアドレスが見つかりません。');

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
(1, 1, 1, 1, '田中太郎', '2026-05-20', '', '22:51:21', 0, '遅延'),
(2, 1, 1, 1, '田中太郎', '2026-05-24', '日', '16:48:00', 1, '体調不良'),
(3, 1, 1, 1, '田中太郎', '2026-05-24', '日', '23:22:00', 2, '体調不良、その他、人助け'),
(4, 1, 1, 1, '田中太郎', '2026-05-24', '日', '23:27:00', 3, '体調不良、その他、人助け'),
(5, 1, 1, 2, '田中次郎', '2026-05-25', '月', '20:51:00', 1, '通院'),
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
(1, 1, 1, 1, '田中太郎', 15),
(2, 1, 1, 2, '田中次郎', 3),
(3, 2, 2, 3, '山本三郎', 1),
(4, 3, 8, 2, '鈴木次郎', 3);

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
