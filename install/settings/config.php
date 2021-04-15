<?php
/* config.php */
return array(
    'version' => '4.0.0',
    'web_title' => 'ออมสิน',
    'web_description' => 'แอพพลิเคชั่น รายรับ-รายจ่าย ฟรี',
    'timezone' => 'Asia/Bangkok',
    /* ข้อมูล Mail Server สำหรับการขอรหัสผ่านใหม่ */
    'noreply_email' => 'no-reply@domain.tld',
    'email_charset' => 'utf-8',
    'email_Host' => 'localhost',
    'email_Port' => 25,
    'email_SMTPSecure' => '',
    /* กำหนดเป็น true ถ้าต้องการใช้งาน phpMailer (สำหรับ Host ที่รองรับ) */
    'email_use_phpMailer' => false,
    /* กำหนดเป็น true ถ้าต้องการส่งเมล์แบบระบุ Username และ Password (สำหรับ Host ที่รองรับ) */
    'email_SMTPAuth' => false,
    'email_Username' => '',
    'email_Password' => '',
    /* Facebook AppID หาได้จาก https://gcms.in.th/howto/%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B8%82%E0%B8%AD_app_id_%E0%B8%88%E0%B8%B2%E0%B8%81_facebook.html */
    'facebook_appId' => '',
);
