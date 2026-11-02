-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th10 02, 2026 lúc 01:11 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `tuyensinh10`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hosotuyensinh`
--

CREATE TABLE `hosotuyensinh` (
  `id` int(11) NOT NULL,
  `maHoSo` varchar(40) NOT NULL,
  `maThiSinh` varchar(30) NOT NULL,
  `maTruong` varchar(20) NOT NULL,
  `maKy` int(11) NOT NULL,
  `trangThai` varchar(30) NOT NULL DEFAULT 'Đã lập',
  `nguoiTao` varchar(80) DEFAULT NULL,
  `ngayLap` datetime DEFAULT current_timestamp(),
  `nguoiCapNhat` varchar(80) DEFAULT NULL,
  `ngayCapNhat` datetime DEFAULT NULL,
  `nguoiHuy` varchar(80) DEFAULT NULL,
  `ngayHuy` datetime DEFAULT NULL,
  `lyDoHuy` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hosotuyensinh`
--

INSERT INTO `hosotuyensinh` (`id`, `maHoSo`, `maThiSinh`, `maTruong`, `maKy`, `trangThai`, `nguoiTao`, `ngayLap`, `nguoiCapNhat`, `ngayCapNhat`, `nguoiHuy`, `ngayHuy`, `lyDoHuy`) VALUES
(21, 'HS26110288454DC', 'TS0101', 'THCS001', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(22, 'HS261102F877A6E', 'TS0102', 'THCS002', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(23, 'HS261102ECC5DA4', 'TS0103', 'THCS003', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(24, 'HS2611020904E39', 'TS0104', 'THCS001', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(25, 'HS261102612D82F', 'TS0105', 'THCS002', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(26, 'HS261102203C57D', 'TS0106', 'THCS003', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(27, 'HS2611026CB193F', 'TS0107', 'THCS001', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(28, 'HS261102B8F5B95', 'TS0108', 'THCS002', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(29, 'HS26110259FB9E0', 'TS0109', 'THCS003', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL),
(30, 'HS261102EDA4A66', 'TS0110', 'THCS001', 1, 'Đã lập', 'admin', '2026-11-02 18:44:06', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kytuyensinh`
--

CREATE TABLE `kytuyensinh` (
  `maKy` int(11) NOT NULL,
  `nam` varchar(20) NOT NULL,
  `tenKy` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `kytuyensinh`
--

INSERT INTO `kytuyensinh` (`maKy`, `nam`, `tenKy`) VALUES
(1, '2027 - 2028', 'Tuyển sinh lớp 10 năm học 2027 - 2028');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguyenvong`
--

CREATE TABLE `nguyenvong` (
  `id` int(11) NOT NULL,
  `maHoSo` varchar(40) NOT NULL,
  `thuTu` int(11) NOT NULL,
  `maTruongTHPT` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thisinh`
--

CREATE TABLE `thisinh` (
  `maThiSinh` varchar(30) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `ngaySinh` date NOT NULL,
  `gioiTinh` varchar(10) NOT NULL,
  `cccd` varchar(20) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thisinh`
--

INSERT INTO `thisinh` (`maThiSinh`, `hoTen`, `ngaySinh`, `gioiTinh`, `cccd`, `soDienThoai`, `email`, `diaChi`) VALUES
('TS0101', 'Nguyễn Minh Anh', '2011-03-15', 'Nữ', '079211000101', '0900000001', 'hocsinh01@example.com', 'Địa chỉ minh họa 1, TP. Hồ Chí Minh'),
('TS0102', 'Trần Quốc Bảo', '2011-06-22', 'Nam', '079211000102', '0900000002', 'hocsinh02@example.com', 'Địa chỉ minh họa 2, TP. Hồ Chí Minh'),
('TS0103', 'Lê Hoàng Phúc', '2011-09-08', 'Nam', '079211000103', '0900000003', 'hocsinh03@example.com', 'Địa chỉ minh họa 3, TP. Hồ Chí Minh'),
('TS0104', 'Phạm Ngọc Hà', '2011-12-30', 'Nữ', '079211000104', '0900000004', 'hocsinh04@example.com', 'Địa chỉ minh họa 4, TP. Hồ Chí Minh'),
('TS0105', 'Võ Khánh Linh', '2011-02-17', 'Nữ', '079211000105', '0900000005', 'hocsinh05@example.com', 'Địa chỉ minh họa 5, TP. Hồ Chí Minh'),
('TS0106', 'Đặng Minh Khang', '2011-10-02', 'Nam', '079211000106', '0900000006', 'hocsinh06@example.com', 'Địa chỉ minh họa 6, TP. Hồ Chí Minh'),
('TS0107', 'Hoàng Gia Huy', '2011-04-25', 'Nam', '079211000107', '0900000007', 'hocsinh07@example.com', 'Địa chỉ minh họa 7, TP. Hồ Chí Minh'),
('TS0108', 'Bùi Thảo Vy', '2011-08-19', 'Nữ', '079211000108', '0900000008', 'hocsinh08@example.com', 'Địa chỉ minh họa 8, TP. Hồ Chí Minh'),
('TS0109', 'Đỗ Thanh Tùng', '2011-05-11', 'Nam', '079211000109', '0900000009', 'hocsinh09@example.com', 'Địa chỉ minh họa 9, TP. Hồ Chí Minh'),
('TS0110', 'Mai Gia Hân', '2011-07-07', 'Nữ', '079211000110', '0900000010', 'hocsinh10@example.com', 'Địa chỉ minh họa 10, TP. Hồ Chí Minh');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `truongthcs`
--

CREATE TABLE `truongthcs` (
  `maTruong` varchar(20) NOT NULL,
  `tenTruong` varchar(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `truongthcs`
--

INSERT INTO `truongthcs` (`maTruong`, `tenTruong`) VALUES
('THCS001', 'THCS Nguyễn Du'),
('THCS002', 'THCS Lê Quý Đôn'),
('THCS003', 'THCS Trần Hưng Đạo');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `hosotuyensinh`
--
ALTER TABLE `hosotuyensinh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maHoSo` (`maHoSo`),
  ADD UNIQUE KEY `uq_thisinh_ky` (`maThiSinh`,`maKy`),
  ADD KEY `maTruong` (`maTruong`),
  ADD KEY `maKy` (`maKy`);

--
-- Chỉ mục cho bảng `kytuyensinh`
--
ALTER TABLE `kytuyensinh`
  ADD PRIMARY KEY (`maKy`);

--
-- Chỉ mục cho bảng `nguyenvong`
--
ALTER TABLE `nguyenvong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Chỉ mục cho bảng `thisinh`
--
ALTER TABLE `thisinh`
  ADD PRIMARY KEY (`maThiSinh`);

--
-- Chỉ mục cho bảng `truongthcs`
--
ALTER TABLE `truongthcs`
  ADD PRIMARY KEY (`maTruong`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `hosotuyensinh`
--
ALTER TABLE `hosotuyensinh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `kytuyensinh`
--
ALTER TABLE `kytuyensinh`
  MODIFY `maKy` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `nguyenvong`
--
ALTER TABLE `nguyenvong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `hosotuyensinh`
--
ALTER TABLE `hosotuyensinh`
  ADD CONSTRAINT `hosotuyensinh_ibfk_1` FOREIGN KEY (`maThiSinh`) REFERENCES `thisinh` (`maThiSinh`),
  ADD CONSTRAINT `hosotuyensinh_ibfk_2` FOREIGN KEY (`maTruong`) REFERENCES `truongthcs` (`maTruong`),
  ADD CONSTRAINT `hosotuyensinh_ibfk_3` FOREIGN KEY (`maKy`) REFERENCES `kytuyensinh` (`maKy`);

--
-- Các ràng buộc cho bảng `nguyenvong`
--
ALTER TABLE `nguyenvong`
  ADD CONSTRAINT `nguyenvong_ibfk_1` FOREIGN KEY (`maHoSo`) REFERENCES `hosotuyensinh` (`maHoSo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
