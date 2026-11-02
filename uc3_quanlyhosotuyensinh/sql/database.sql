-- HE THONG TUYEN SINH LOP 10 - DATABASE UC03
-- Ban cai moi: Import duy nhat file nay vao phpMyAdmin.
-- Bao gom cac cot ghi nhan nguoi/thoi gian tao, cap nhat va huy ho so.
-- Khong can chay nang_cap_uc03.sql.
-- Luu y: neu database cu da co bang hosotuyensinh nhung thieu cot,
-- CREATE TABLE IF NOT EXISTS se KHONG tu dong bo sung cot cho bang cu.

CREATE DATABASE IF NOT EXISTS tuyensinh10
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tuyensinh10;

CREATE TABLE IF NOT EXISTS kytuyensinh (
  maKy INT PRIMARY KEY AUTO_INCREMENT,
  nam VARCHAR(20) NOT NULL,
  tenKy VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS truongthcs (
  maTruong VARCHAR(20) PRIMARY KEY,
  tenTruong VARCHAR(160) NOT NULL
);

CREATE TABLE IF NOT EXISTS thisinh (
  maThiSinh VARCHAR(30) PRIMARY KEY,
  hoTen VARCHAR(150) NOT NULL,
  ngaySinh DATE NOT NULL,
  gioiTinh VARCHAR(10) NOT NULL,
  cccd VARCHAR(20),
  soDienThoai VARCHAR(20),
  email VARCHAR(120),
  diaChi VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS hosotuyensinh (
  id INT PRIMARY KEY AUTO_INCREMENT,
  maHoSo VARCHAR(40) NOT NULL UNIQUE,
  maThiSinh VARCHAR(30) NOT NULL,
  maTruong VARCHAR(20) NOT NULL,
  maKy INT NOT NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Đã lập',
  nguoiTao VARCHAR(80),
  ngayLap DATETIME DEFAULT CURRENT_TIMESTAMP,
  nguoiCapNhat VARCHAR(80) NULL,
  ngayCapNhat DATETIME NULL,
  lyDoHuy VARCHAR(255) NULL,
  nguoiHuy VARCHAR(80) NULL,
  ngayHuy DATETIME NULL,
  UNIQUE KEY uq_thisinh_ky (maThiSinh, maKy),
  FOREIGN KEY (maThiSinh) REFERENCES thisinh(maThiSinh),
  FOREIGN KEY (maTruong) REFERENCES truongthcs(maTruong),
  FOREIGN KEY (maKy) REFERENCES kytuyensinh(maKy)
);

CREATE TABLE IF NOT EXISTS nguyenvong (
  id INT AUTO_INCREMENT PRIMARY KEY,
  maHoSo VARCHAR(40) NOT NULL,
  thuTu INT NOT NULL,
  maTruongTHPT VARCHAR(20) NOT NULL,
  FOREIGN KEY (maHoSo) REFERENCES hosotuyensinh(maHoSo)
);

INSERT IGNORE INTO kytuyensinh (maKy, nam, tenKy)
VALUES (1, '2027 - 2028', 'Tuyển sinh lớp 10 năm học 2027 - 2028');

INSERT IGNORE INTO truongthcs (maTruong, tenTruong) VALUES
('THCS001', 'THCS Nguyễn Du'),
('THCS002', 'THCS Lê Quý Đôn'),
('THCS003', 'THCS Trần Hưng Đạo');
