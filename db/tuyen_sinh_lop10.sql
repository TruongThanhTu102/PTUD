-- Co so du lieu chung: He thong tuyen sinh lop 10 (MySQL 8.0+, XAMPP)
-- Ban khoi tao hien tai bao gom cac bang phuc vu UC21 va du lieu mau.
-- Khi phat trien UC khac, bo sung bang vao cung database tuyen_sinh_lop10.
-- Chi import vao database rong (hoac database da duoc doi chieu schema).
CREATE DATABASE IF NOT EXISTS tuyen_sinh_lop10 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tuyen_sinh_lop10;

CREATE TABLE IF NOT EXISTS KyTuyenSinh (
  maKyTuyenSinh INT AUTO_INCREMENT PRIMARY KEY,
  tenKyTuyenSinh VARCHAR(200) NOT NULL,
  namHoc VARCHAR(9) NOT NULL UNIQUE,
  ngayBatDau DATE NOT NULL,
  ngayKetThuc DATE NOT NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Chưa diễn ra',
  CONSTRAINT chk_ky_dates CHECK (ngayKetThuc >= ngayBatDau)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS HoiDong (
  maHoiDong INT AUTO_INCREMENT PRIMARY KEY,
  tenHoiDong VARCHAR(200) NOT NULL,
  loaiHoiDong VARCHAR(30) NOT NULL,
  ngayThanhLap DATE NOT NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Đang hoạt động',
  maKyTuyenSinh INT NOT NULL,
  CONSTRAINT fk_hd_ky FOREIGN KEY (maKyTuyenSinh) REFERENCES KyTuyenSinh(maKyTuyenSinh),
  CONSTRAINT uq_hd_ky_loai UNIQUE (maKyTuyenSinh, loaiHoiDong)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS MonThi (
  maMonThi INT AUTO_INCREMENT PRIMARY KEY,
  tenMonThi VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ChuyenMon (
  maChuyenMon VARCHAR(20) PRIMARY KEY,
  tenChuyenMon VARCHAR(100) NOT NULL UNIQUE,
  moTa VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Bảng ánh xạ chuyên môn có thể chấm môn nào (quy tắc bổ sung cho UC21).
CREATE TABLE IF NOT EXISTS ChuyenMonMonThi (
  maChuyenMon VARCHAR(20) NOT NULL,
  maMonThi INT NOT NULL,
  PRIMARY KEY (maChuyenMon, maMonThi),
  FOREIGN KEY (maChuyenMon) REFERENCES ChuyenMon(maChuyenMon),
  FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS NhanSu (
  maNhanSu VARCHAR(20) PRIMARY KEY,
  hoTen VARCHAR(100) NOT NULL,
  maChuyenMon VARCHAR(20) NOT NULL,
  trangThaiHoatDong VARCHAR(50) NOT NULL DEFAULT 'Đang hoạt động',
  FOREIGN KEY (maChuyenMon) REFERENCES ChuyenMon(maChuyenMon)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PhongThi (
  maPhongThi VARCHAR(20) PRIMARY KEY,
  tenPhongThi VARCHAR(100) NOT NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động'
) ENGINE=InnoDB;

-- So với mục 2.7 của báo cáo: bổ sung maPhongThi và sử dụng maNhanSu,
-- thay vì maTaiKhoan INT (trong báo cáo TaiKhoan.maTaiKhoan là VARCHAR(20)).
CREATE TABLE IF NOT EXISTS PhanCong (
  maPhanCong INT AUTO_INCREMENT PRIMARY KEY,
  maHoiDong INT NOT NULL,
  maNhanSu VARCHAR(20) NOT NULL,
  maMonThi INT NOT NULL,
  maPhongThi VARCHAR(20) NOT NULL,
  ngayPhanCong DATE NOT NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Đã phân công',
  UNIQUE KEY uq_pc_exact (maHoiDong, maNhanSu, maMonThi, maPhongThi),
  KEY idx_pc_teacher (maNhanSu),
  FOREIGN KEY (maHoiDong) REFERENCES HoiDong(maHoiDong),
  FOREIGN KEY (maNhanSu) REFERENCES NhanSu(maNhanSu),
  FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi),
  FOREIGN KEY (maPhongThi) REFERENCES PhongThi(maPhongThi)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS TaiKhoan (
  maTaiKhoan VARCHAR(20) PRIMARY KEY,
  tenDangNhap VARCHAR(100) NOT NULL UNIQUE,
  matKhauHash VARCHAR(255) NOT NULL,
  vaiTro VARCHAR(40) NOT NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Hoạt động'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS NhatKyThaoTac (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  maTaiKhoan VARCHAR(20) NOT NULL,
  hanhDong VARCHAR(40) NOT NULL,
  maPhanCong INT NULL,
  noiDung VARCHAR(500) NOT NULL,
  thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maTaiKhoan) REFERENCES TaiKhoan(maTaiKhoan)
) ENGINE=InnoDB;

INSERT IGNORE INTO KyTuyenSinh (maKyTuyenSinh,tenKyTuyenSinh,namHoc,ngayBatDau,ngayKetThuc,trangThai) VALUES
 (1,'Tuyển sinh lớp 10 năm học 2027–2028','2027-2028','2027-01-01','2027-12-31','Chưa diễn ra'),
 (2,'Tuyển sinh lớp 10 năm học 2026–2027','2026-2027','2026-01-01','2026-12-31','Đang diễn ra');
INSERT IGNORE INTO HoiDong (maHoiDong,tenHoiDong,loaiHoiDong,ngayThanhLap,trangThai,maKyTuyenSinh) VALUES
 (1,'Hội đồng chấm thi 2027–2028','CHAM_THI','2027-03-01','Đang hoạt động',1),
 (2,'Hội đồng chấm thi 2026–2027','CHAM_THI','2026-03-01','Đang hoạt động',2);
INSERT IGNORE INTO MonThi (maMonThi,tenMonThi) VALUES (1,'Toán'),(2,'Ngữ văn'),(3,'Tiếng Anh');
INSERT IGNORE INTO ChuyenMon (maChuyenMon,tenChuyenMon,moTa) VALUES
 ('CM_TOAN','Toán','Giáo viên môn Toán'),('CM_VAN','Ngữ văn','Giáo viên môn Ngữ văn'),('CM_ANH','Tiếng Anh','Giáo viên môn Tiếng Anh');
INSERT IGNORE INTO ChuyenMonMonThi (maChuyenMon,maMonThi) VALUES ('CM_TOAN',1),('CM_VAN',2),('CM_ANH',3);
INSERT IGNORE INTO NhanSu (maNhanSu,hoTen,maChuyenMon,trangThaiHoatDong) VALUES
 ('GV001','Nguyễn Văn An','CM_TOAN','Đang hoạt động'),
 ('GV002','Trần Thị Bình','CM_VAN','Đang hoạt động'),
 ('GV003','Lê Minh Châu','CM_ANH','Đang hoạt động'),
 ('GV004','Phạm Quốc Dũng','CM_TOAN','Đang hoạt động'),
 ('GV005','Võ Thị Hương','CM_TOAN','Đang hoạt động');
INSERT IGNORE INTO PhongThi (maPhongThi,tenPhongThi,trangThai) VALUES
 ('P101','Phòng P101','Hoạt động'),('P102','Phòng P102','Hoạt động'),
 ('P201','Phòng P201','Hoạt động'),('P202','Phòng P202','Hoạt động');

-- Tài khoản thử nghiệm: so.gddt / 123456 (đổi mật khẩu trước khi dùng thật)
INSERT IGNORE INTO TaiKhoan (maTaiKhoan,tenDangNhap,matKhauHash,vaiTro,trangThai) VALUES
 ('SO001','so.gddt','$2y$12$Pb.xa3jdue0UsWX2YQbVxeA8nKyirk6mWPnrD.3uiabhKpx8Xqk0q','SO_GDDT','Hoạt động');

INSERT IGNORE INTO PhanCong (maPhanCong,maHoiDong,maNhanSu,maMonThi,maPhongThi,ngayPhanCong,trangThai) VALUES
 (1,1,'GV001',1,'P101','2027-03-01','Đã phân công'),
 (2,1,'GV002',2,'P102','2027-03-01','Đã phân công'),
 (3,1,'GV003',3,'P201','2027-03-01','Đã phân công'),
 (4,1,'GV004',1,'P202','2027-03-01','Đã phân công'),
 (5,2,'GV001',1,'P101','2026-03-01','Đã phân công');
