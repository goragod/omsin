<?php
/**
 * @filesource modules/omsin/models/ierecord.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Ierecord;

use Gcms\Login;
use Kotchasan\Currency;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ierecord
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูล บัญชีรายรัย-รายจ่าย ที่ $id
     *
     * @param int  $account_id
     * @param int  $id
     * @param bool $new        false (default) คืนค่า null ถ้าไม่พบ, true คืนค่ารายการใหม่ถ้า $id = 0
     *
     * @return object|null
     */
    public static function get($account_id, $id, $new = false)
    {
        if ($id > 0) {
            // แก้ไข, อ่านรายการที่เลือก
            return static::createQuery()
                ->from('ierecord R')
                ->where(array(
                    array('R.account_id', $account_id),
                    array('R.id', $id),
                ))
                ->first();
        } elseif ($new) {
            // ใหม่
            return (object) array(
                'account_id' => $account_id,
                'id' => 0,
            );
        }
        return null;
    }

    /**
     * บันทึกข้อมูล (ierecord.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // รับค่าจากการ POST
                $account_id = $request->post('write_account_id')->toInt();
                $status = $request->post('write_status')->filter('A-Z');
                // รายการที่เลือก
                $index = self::get($account_id, $request->post('write_id')->toInt(), true);
                if ($index && $index->account_id == $login['account_id']) {
                    if ($index->id > 0) {
                        // แก้ไขใช้สถานะเดิม
                        $status = $index->status;
                    }
                    if ($status == 'TRANSFER') {
                        // โอนเงินระหว่างบัญชี
                        $ret = self::transfer($request, $index);
                    } elseif ($status == 'INIT') {
                        // กระเป๋าเงิน
                        $ret = self::wallet($request, $index);
                    } else {
                        // บันทึก รายรับ/รายจ่าย
                        $ret = self::recording($request, $status, $index);
                    }
                    if (empty($ret)) {
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $index->id == 0 ? 'reload' : 'back';
                        // เคลียร์
                        $request->removeToken();
                    }
                }

            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            // ไม่มีสิทธิ์
            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * บันทึก รายรับ/รายจ่าย
     *
     * @param Request $request
     * @param string  $status
     * @param object  $index
     */
    private static function recording(Request $request, $status, $index)
    {
        $ret = array();
        if ($index->id == 0) {
            // หมวดหมู่ (รายการใหม่)
            $method = $status == 'IN' ? 'newIncome' : 'newExpensive';
            $category_id = \Omsin\Category\Model::$method($index->account_id, $request->post('write_category')->topic());
        } else {
            // หมวดหมู่ (แก้ไข)
            $category_id = $request->post('write_category')->toInt();
        }
        if ($category_id == 0) {
            // ไม่ได้กรอกหมวดหมู่
            $ret['ret_write_category'] = 'this';
        } else {
            // ค่าที่ส่งมา
            $wallet = $request->post('write_wallet')->toInt();
            // จำนวนเงิน
            $amount = $request->post('write_amount')->toDouble();
            if ($amount == 0) {
                // ไม่ได้กรอกจำนวนเงิน
                $ret['ret_write_amount'] = 'Please fill in';
            } elseif ($status == 'OUT') {
                // อ่านจำนวนเงินในกระเป๋า สำหรับรายจ่าย
                $money_source = \Omsin\Wallet\Model::getMoney($index->account_id, $wallet);
                if ($money_source < $amount) {
                    // จำนวนเงินมากกว่าจำนวนเงินในกระเป๋า
                    $ret['ret_write_amount'] = Language::replace('Fill in more money in pocket (:amount)', array(':amount' => Currency::format($money_source)));
                }
            }
            if (empty($ret)) {
                $model = new \Kotchasan\Model();
                $table_name = $model->getTableName('ierecord');
                // save
                $save = array(
                    'comment' => $request->post('write_comment')->topic(),
                    'create_date' => $request->post('write_create_date')->date(),
                    'category_id' => $category_id,
                    'wallet' => $wallet,
                );
                if ($status == 'IN') {
                    $save['income'] = $amount;
                    $save['expense'] = 0;
                } else {
                    $save['expense'] = $amount;
                    $save['income'] = 0;
                }
                if ($index->id == 0) {
                    // ใหม่
                    $save['id'] = Sql::NEXT('id', $table_name, array('account_id', $index->account_id));
                    $save['account_id'] = $index->account_id;
                    $save['status'] = $status;
                    $save['transfer_to'] = 0;
                    $model->db()->insert($table_name, $save);
                } else {
                    // แก้ไข
                    $where = array(
                        array('account_id', $index->account_id),
                        array('id', $index->id),
                    );
                    $model->db()->update($table_name, $where, $save);
                }
                // save cookie
                setcookie('ierecord_wallet', $save['wallet'], time() + 2592000, '/', HOST, HTTPS, true);
            }
        }
        return $ret;
    }

    /**
     * โอนเงินระหว่างบัญชี
     *
     * @param Request $request
     * @param object  $index
     */
    private static function transfer(Request $request, $index)
    {
        $ret = array();
        // ค่าที่ส่งมา
        $amount = $request->post('write_amount')->toDouble();
        $from = $request->post('write_from')->toInt();
        $to = $request->post('write_to')->toInt();
        if ($from == $to) {
            // เลือกบัญชีเดียวกัน
            $ret['ret_write_to'] = Language::get('Please select a different account');
        } elseif ($amount == 0) {
            // ไม่ได้กรอกจำนวนเงิน
            $ret['ret_write_amount'] = 'Please fill in';
        } else {
            // อ่านจำนวนเงินในกระเป๋า
            $money = \Omsin\Wallet\Model::getMoney($index->account_id, $from);
            if ($amount > $money) {
                // จำนวนเงินที่จะโอนมากกว่าในกระเป๋า
                $ret['ret_write_amount'] = Language::replace('Fill in more money in pocket (:amount)', array(':amount' => Currency::format($money)));
            } else {
                $model = new \Kotchasan\Model();
                $table_name = $model->getTableName('ierecord');
                // query ID ถัดไป
                $q1 = Sql::NEXT('id', $table_name, array('account_id', $index->account_id), 'id');
                $query = $model->db()->createQuery()->toArray()->first($q1);
                // โอนเงินออก
                $save = array(
                    'account_id' => $index->account_id,
                    'id' => $query['id'],
                    'comment' => $request->post('write_comment')->topic(),
                    'create_date' => $request->post('write_create_date')->date(),
                    'category_id' => 0,
                    'wallet' => $from,
                    'status' => 'TRANSFER',
                    'income' => 0,
                    'expense' => $amount,
                    'transfer_to' => $to,
                );
                $model->db()->insert($table_name, $save);
            }
        }
        return $ret;
    }

    /**
     * เพิ่มกระเป๋าเงิน
     *
     * @param Request $request
     * @param object  $index
     */
    private static function wallet(Request $request, $index)
    {
        $ret = array();
        // Model
        $model = new \Kotchasan\Model();
        // ตาราง ierecord
        $table_name = $model->getTableName('ierecord');
        if ($index->id == 0) {
            // ชื่อ กระเป๋าเงิน
            $wallet = $request->post('write_wallet_name')->topic();
            // ตรวจสอบกระเป๋าเงินซ้ำ
            $search = $model->db()->createQuery()
                ->from('category')
                ->where(array(
                    array('account_id', $index->account_id),
                    array('id', WALLET),
                    array('topic', $wallet),
                ))
                ->first('category_id');
            if ($search) {
                // มีกระเป๋าเงินนี้อยู่แล้ว
                $ret['ret_write_wallet_name'] = Language::replace('This :name already exist', array(':name' => Language::get('Wallet')));
            } else {
                // อ่าน ID ของกระเป๋าเงินใหม่
                $search = $model->db()->createQuery()
                    ->from('category')
                    ->where(array(
                        array('account_id', $index->account_id),
                        array('id', WALLET),
                    ))
                    ->first(Sql::MAX('category_id', 'category_id'));
                $wallet_id = empty($search->category_id) ? 1 : (1 + (int) $search->category_id);
                // สร้างกระเป๋าเงิน
                $model->db()->insert($model->getTableName('category'), array(
                    'account_id' => $index->account_id,
                    'category_id' => $wallet_id,
                    'id' => WALLET,
                    'topic' => $wallet,
                ));
                $amount = $request->post('write_amount')->toDouble();
                if ($amount > 0) {
                    // บันทึก ยอดยกมา ถ้ามีการระบุจำนวนเงินมาด้วย
                    $model->db()->insert($table_name, array(
                        'account_id' => $index->account_id,
                        'id' => Sql::NEXT('id', $table_name, array('account_id', $index->account_id)),
                        'comment' => $request->post('write_comment')->topic(),
                        'create_date' => $request->post('write_create_date')->date(),
                        'category_id' => 0,
                        'wallet' => $wallet_id,
                        'status' => 'INIT',
                        'income' => $amount,
                        'expense' => 0,
                        'transfer_to' => 0,
                    ));
                }
            }
        } else {
            // แก้ไขรายการ ยอดยกมา
            $where = array(
                array('account_id', $index->account_id),
                array('id', $index->id),
            );
            $model->db()->update($table_name, $where, array(
                'comment' => $request->post('write_comment')->topic(),
                'create_date' => $request->post('write_create_date')->date(),
                'income' => $request->post('write_amount')->toDouble(),
                'expense' => 0,
            ));
        }
        return $ret;
    }
}
