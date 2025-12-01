<?php
/**
 * Admin Kullanıcı Oluşturma Scripti
 * Bu script bir admin kullanıcı oluşturur ve SQL kodunu üretir
 */

// Admin kullanıcı bilgileri
$admin_email = 'admin@gardiropplus.com';
$admin_password = 'Admin123!@#'; // Güçlü bir şifre
$admin_first_name = 'Admin';
$admin_last_name = 'User';

// Şifreyi hash'le
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

// UUID oluştur (eğer id UUID ise)
function generate_uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

$user_id = generate_uuid();
$created_at = date('Y-m-d H:i:s');

// SQL kodu oluştur
$sql = <<<SQL
-- Admin Kullanıcı Oluşturma SQL
-- Email: {$admin_email}
-- Şifre: {$admin_password}
-- Not: Bu şifreyi güvenli bir yerde saklayın!

-- Önce role field'ının var olduğundan emin olun (yoksa ekleyin)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role` VARCHAR(50) DEFAULT 'user' AFTER `email_verified`,
ADD INDEX IF NOT EXISTS `idx_role` (`role`);

-- Admin kullanıcıyı oluştur
INSERT INTO `users` (
    `id`,
    `email`,
    `password`,
    `first_name`,
    `last_name`,
    `role`,
    `email_verified`,
    `created_at`,
    `updated_at`
) VALUES (
    '{$user_id}',
    '{$admin_email}',
    '{$password_hash}',
    '{$admin_first_name}',
    '{$admin_last_name}',
    'admin',
    1,
    '{$created_at}',
    '{$created_at}'
) ON DUPLICATE KEY UPDATE
    `password` = '{$password_hash}',
    `role` = 'admin',
    `email_verified` = 1,
    `updated_at` = '{$created_at}';

-- Alternatif: Mevcut bir kullanıcıyı admin yapmak için
-- UPDATE `users` SET `role` = 'admin' WHERE `email` = '{$admin_email}';
SQL;

// Çıktı
echo "=== Admin Kullanıcı Oluşturma SQL ===\n\n";
echo $sql;
echo "\n\n=== Bilgiler ===\n";
echo "Email: {$admin_email}\n";
echo "Şifre: {$admin_password}\n";
echo "Password Hash: {$password_hash}\n";
echo "User ID: {$user_id}\n";
echo "\nBu bilgileri güvenli bir yerde saklayın!\n";

// SQL dosyasına kaydet
file_put_contents(__DIR__ . '/docs/create_admin_user.sql', $sql);
echo "\n✅ SQL kodu 'docs/create_admin_user.sql' dosyasına kaydedildi.\n";

