-- Chỉ chạy một lần với database tuyensinh10 đã tạo trước đó.
-- Không xóa dữ liệu đã có.
ALTER TABLE tuyensinh10.hosotuyensinh
ADD COLUMN lyDoHuy VARCHAR(255) NULL;
