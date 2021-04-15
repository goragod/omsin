<?php
/**
 * @filesource modules/omsin/models/database.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Database;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * module=omsin-database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var string
     */
    private static $transfer = 'โอนเงินระหว่างบัญชี';
    /**
     * @var string
     */
    private static $summit = 'ยอดยกมา';

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            $ret = array();
            if ($request->post('action')->toString() == 'reset') {
                // ลบข้อมูลทั้งหมดของ User
                $account_id = (int) $login['account_id'];
                $this->db()->delete($this->getTableName('ierecord'), array('account_id', $account_id), 0);
                $this->db()->delete($this->getTableName('category'), array('account_id', $account_id), 0);
                // คืนค่า
                $ret['alert'] = Language::get('Deleted successfully');
            }
            // คืนค่า JSON
            if (!empty($ret)) {
                echo json_encode($ret);
            }
        }
    }

    /**
     * ดาวน์โหลดไฟล์ CSV ตัวอย่างสำหรับนำเข้าข้อมูล
     *
     * @param Request $request
     */
    public function demo(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $headers = array();
            $datas = array();
            foreach (self::headers() as $key => $items) {
                $headers[] = $items[0];
                foreach ($items[1] as $k => $v) {
                    $datas[$k][] = $v;
                }
            }
            self::save($headers, $datas);
        }
    }

    /**
     * ส่งออกข้อมูล omsin.csv สำหรับสำรองข้อมูล
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            $account_id = (int) $login['account_id'];
            // query ข้อมูล category
            $query = $this->db()->createQuery()
                ->select('category_id', 'topic', 'id')
                ->from('category')
                ->where(array('account_id', $account_id))
                ->toArray();
            $category = array();
            $wallet = array();
            foreach ($query->execute() as $item) {
                if ($item['id'] == RECEIVE) {
                    $category['IN'][$item['category_id']] = $item['topic'];
                } elseif ($item['id'] == EXPENSE) {
                    $category['OUT'][$item['category_id']] = $item['topic'];
                } elseif ($item['id'] == WALLET) {
                    $wallet[$item['category_id']] = $item['topic'];
                }
            }
            // query ข้อมูล รายรับรายจ่าย
            $query = $this->db()->createQuery()
                ->select('category_id category', 'status', 'wallet', 'expense', 'income', 'create_date', 'comment', 'transfer_to')
                ->from('ierecord')
                ->where(array('account_id', $account_id))
                ->order('create_date')
                ->toArray();
            $datas = array();
            foreach ($query->execute() as $item) {
                if ($item['status'] == 'TRANSFER') {
                    // โอนเงินระหว่างบัญชี
                    $item['category'] = self::$transfer;
                    $res = array(
                        isset($wallet[$item['wallet']]) ? $wallet[$item['wallet']] : 'Unknow',
                        isset($wallet[$item['transfer_to']]) ? $wallet[$item['transfer_to']] : 'Unknow',
                    );
                    $item['wallet'] = implode('/', $res);
                } elseif ($item['status'] == 'INIT') {
                    // สร้างบัญชี
                    $item['category'] = self::$summit;
                    $item['wallet'] = isset($wallet[$item['wallet']]) ? $wallet[$item['wallet']] : 'Unknow';
                } else {
                    // รายรับ/รายจ่าย
                    $item['category'] = isset($category[$item['status']][$item['category']]) ? $category[$item['status']][$item['category']] : 'Unknow';
                    $item['wallet'] = isset($wallet[$item['wallet']]) ? $wallet[$item['wallet']] : 'Unknow';
                }
                unset($item['status']);
                unset($item['transfer_to']);
                $datas[] = $item;
            }
            $headers = array();
            foreach (self::headers() as $key => $items) {
                $headers[] = $items[0];
            }
            self::save($headers, $datas);
        }
    }

    /**
     * นำเข้าข้อมูล
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        // session, token, สามารถแก้ไขได้
        if ($request->initSession() && $request->isSafe()) {
            if ($login = Login::isMember()) {
                // สมาชิก
                $account_id = (int) $login['account_id'];
                // ชื่อตาราง
                $category_table = $this->getTableName('category');
                $ierecord_table = $this->getTableName('ierecord');
                $category = array();
                $wallet = array();
                $category_id = 0;
                // query ข้อมูล category
                $sql = "SELECT `category_id`,`topic`,`id` FROM `$category_table` WHERE `account_id`=$account_id";
                foreach ($this->db()->customQuery($sql, true) as $item) {
                    $category_id = max($category_id, $item['category_id']);
                    if ($item['id'] == RECEIVE) {
                        $category['IN'][$item['topic']] = $item['category_id'];
                    } elseif ($item['id'] == EXPENSE) {
                        $category['OUT'][$item['topic']] = $item['category_id'];
                    } elseif ($item['id'] == WALLET) {
                        $wallet[$item['topic']] = $item['category_id'];
                    }
                }
                $sql = "SELECT MAX(`id`) AS `id` FROM `$ierecord_table` WHERE `account_id`=$account_id";
                $ierecord = $this->db()->customQuery($sql, true);
                if (count($ierecord) == 1) {
                    $ierecord_id = $ierecord[0]['id'];
                } else {
                    $ierecord_id = 0;
                }
                $ret = array();
                // อัปโหลดไฟล์
                foreach ($request->getUploadedFiles() as $item => $file) {
                    /* @var $file UploadedFile */
                    if ($file->hasUploadFile()) {
                        if (!$file->validFileExt(array('csv'))) {
                            // ชนิดของไฟล์ไม่ถูกต้อง
                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                        } else {
                            $f = @fopen($file->getTempFileName(), 'r');
                            if ($f) {
                                $r = 0;
                                while (($data = fgetcsv($f)) !== false) {
                                    if ($r > 0 && empty($ret)) {
                                        ++$ierecord_id;
                                        $save = array(
                                            'account_id' => $account_id,
                                            'id' => $ierecord_id,
                                            'category_id' => preg_replace('/[\r\n\s\t]+/', ' ', trim(strip_tags($data[0]))),
                                            'wallet' => preg_replace('/[\r\n\s\t]+/', ' ', trim(strip_tags($data[1]))),
                                            'expense' => (float) $data[2],
                                            'income' => (float) $data[3],
                                            'create_date' => $data[4],
                                            'comment' => preg_replace('/[\r\n\s\t]+/', ' ', trim(strip_tags($data[5]))),
                                            'transfer_to' => 0,
                                        );
                                        if ($save['category_id'] == self::$transfer) {
                                            // โอนเงินระหว่างบัญชี
                                            $save['category_id'] = 0;
                                            $save['status'] = 'TRANSFER';
                                            foreach (explode('/', $save['wallet']) as $n => $w) {
                                                if ($n == 0) {
                                                    $save['wallet'] = $this->getWallet($wallet, $category_id, $account_id, $category_table, $w);
                                                } else {
                                                    $save['transfer_to'] = $this->getWallet($wallet, $category_id, $account_id, $category_table, $w);
                                                }
                                            }
                                            $save['income'] = 0;
                                            if ($save['transfer_to'] == 0) {
                                                // ถ้าไม่มี transfer_to จะไม่นำเข้าข้อมูล
                                                $save['expense'] = 0;
                                            }
                                        } elseif ($save['category_id'] == self::$summit) {
                                            // เพิ่มกระเป๋าเงิน
                                            $save['category_id'] = 0;
                                            $save['status'] = 'INIT';
                                            $save['wallet'] = $this->getWallet($wallet, $category_id, $account_id, $category_table, $save['wallet']);
                                            $save['expense'] = 0;
                                        } elseif ($save['expense'] > 0) {
                                            // รายจ่าย
                                            $save['status'] = 'OUT';
                                            $save['category_id'] = $this->getCategory($category, 'OUT', $category_id, $account_id, $category_table, $save['category_id']);
                                            $save['wallet'] = $this->getWallet($wallet, $category_id, $account_id, $category_table, $save['wallet']);
                                            $save['income'] = 0;
                                        } else {
                                            // รายรับ
                                            $save['status'] = 'IN';
                                            $save['category_id'] = $this->getCategory($category, 'IN', $category_id, $account_id, $category_table, $save['category_id']);
                                            $save['wallet'] = $this->getWallet($wallet, $category_id, $account_id, $category_table, $save['wallet']);
                                            $save['expense'] = 0;
                                        }
                                        if ($save['expense'] > 0 || $save['income'] > 0) {
                                            // บันทึกเฉพาะรายการที่มี รายรับ หรือ รายจ่ายเท่านั้น
                                            $this->db()->insert($ierecord_table, $save);
                                        }
                                    } elseif ($r == 0) {
                                        // ตรวจสอบ header
                                        $headers = array_values(self::headers());
                                        if (count($headers) != count($headers)) {
                                            // รูปแบบของไฟล์ไม่ถูกต้อง
                                            $ret['ret_'.$item] = Language::get('The format of the imported file is invalid');
                                        }
                                    }
                                    ++$r;
                                }
                                // คืนค่า
                                $ret['alert'] = Language::replace('Successfully imported :count items', array(':count' => $r - 1));
                                $ret['location'] = 'index.php';
                                // เคลียร์
                                $request->removeToken();
                            }
                        }
                    } elseif ($err = $file->getErrorMessage()) {
                        // upload error
                        $ret['ret_'.$item] = $err;
                    }
                }
                // คืนค่าเป็น JSON
                echo json_encode($ret);
            }
        }
    }

    /**
     * @param  $wallet
     * @param  $category_id
     * @param  $account_id
     * @param  $category_table
     * @param  $topic
     *
     * @return mixed
     */
    private function getWallet(&$wallet, &$category_id, $account_id, $category_table, $topic)
    {
        if (isset($wallet[$topic])) {
            return $wallet[$topic];
        } else {
            ++$category_id;
            $this->db()->insert($category_table, array(
                'account_id' => $account_id,
                'id' => WALLET,
                'category_id' => $category_id,
                'topic' => $topic,
            ));
            $wallet[$topic] = $category_id;

            return $category_id;
        }
    }

    /**
     * @param  $category
     * @param  $status
     * @param  $category_id
     * @param  $account_id
     * @param  $category_table
     * @param  $topic
     *
     * @return mixed
     */
    private function getCategory(&$category, $status, &$category_id, $account_id, $category_table, $topic)
    {
        if (isset($category[$status][$topic])) {
            // มีหมวดอยู่แล้ว
            return $category[$status][$topic];
        } else {
            // หมวดหมู่ใหม่
            ++$category_id;
            $this->db()->insert($category_table, array(
                'account_id' => $account_id,
                'id' => $status == 'IN' ? RECEIVE : EXPENSE,
                'category_id' => $category_id,
                'topic' => $topic,
            ));
            $category[$status][$topic] = $category_id;

            return $category_id;
        }
    }

    /**
     * รูปแบบและข้อมูลตัวอย่างไฟล์ CSV.
     *
     * @return array
     */
    public static function headers()
    {
        return array(
            'category_id' => array('หมวดหมู่', array(self::$summit, 'เงินเดือน', self::$transfer, 'ค่าอาหาร')),
            'wallet' => array('กระเป๋าเงิน/โอนไป', array('ธนาคาร', 'ธนาคาร', 'ธนาคาร/เงินสด', 'เงินสด')),
            'expense' => array('รายจ่าย', array(0, 0, 1000, 50)),
            'income' => array('รายรับ', array(1000, 5000, 0, 0)),
            'create_date' => array('วันที่', array(date('Y-m-d'), date('Y-m-d'), date('Y-m-d'), date('Y-m-d'))),
            'comment' => array('หมายเหตุ', array('', '', '', '')),
        );
    }

    /**
     * create CSV file
     */
    public static function save($headers, $datas)
    {
        $response = new Response();
        $response->withHeaders(array(
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=omsin.csv',
        ))
            ->send();
        $f = fopen('php://output', 'w');
        fputcsv($f, $headers);
        foreach ($datas as $item) {
            fputcsv($f, $item);
        }
        fclose($f);
    }
}
