<?php
/**
 * @filesource modules/omsin/models/category.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Category;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * คลาสสำหรับอ่านข้อมูลหมวดหมู่
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ฟังก์ชั่นอ่านหมวดหมู่ หรือ บันทึก ถ้าไม่มีหมวดหมู่
     *
     * @param int    $account_id
     * @param int    $type_id
     * @param string $topic
     *
     * @return int คืนค่า category_id
     */
    private static function checkCategory($account_id, $type_id, $topic)
    {
        $topic = trim($topic);
        if ($topic == '') {
            return 0;
        } else {
            // Model
            $model = new static();
            $search = $model->db()->createQuery()
                ->from('category')
                ->where(array(
                    array('account_id', $account_id),
                    array('id', $type_id),
                    array('topic', $topic),
                ))
                ->toArray()
                ->first('category_id');
            if ($search) {
                // มีหมวดหมู่อยู่แล้ว
                return $search['category_id'];
            } else {
                // ไม่มีหมวดหมู่ ตรวจสอบ category_id ใหม่
                $search = $model->db()->createQuery()
                    ->from('category')
                    ->where(array(
                        array('account_id', $account_id),
                        array('id', $type_id),
                    ))
                    ->toArray()
                    ->first(Sql::MAX('category_id', 'category_id'));
                $category_id = empty($search['category_id']) ? 1 : (1 + (int) $search['category_id']);
                // save
                $model->db()->insert($model->getTableName('category'), array(
                    'account_id' => $account_id,
                    'id' => $type_id,
                    'category_id' => $category_id,
                    'topic' => $topic,
                ));

                return $category_id;
            }
        }
    }

    /**
     * ประเภทรายรับ
     *
     * @param int    $account_id
     * @param string $topic
     *
     * @return int คืนค่า category_id
     */
    public static function newIncome($account_id, $topic)
    {
        return self::checkCategory($account_id, RECEIVE, $topic);
    }

    /**
     * รายการรายจ่าย
     *
     * @param int    $account_id
     * @param string $topic
     *
     * @return int คืนค่า category_id
     */
    public static function newExpensive($account_id, $topic)
    {
        return self::checkCategory($account_id, EXPENSE, $topic);
    }

    /**
     * กระเป๋าเงิน
     *
     * @param int    $account_id
     * @param string $topic
     *
     * @return int คืนค่า category_id
     */
    public static function newWallet($account_id, $topic)
    {
        return self::checkCategory($account_id, WALLET, $topic);
    }

    /**
     * อ่านข้อมูลหมวดหมู่
     *
     * @param int $account_id
     * @param int $typ
     *
     * @return array
     */
    public static function all($account_id, $typ)
    {
        return static::createQuery()
            ->select()
            ->from('category')
            ->where(array(
                array('account_id', $account_id),
                array('id', $typ),
            ))
            ->order('category_id')
            ->toArray()
            ->execute();
    }

    /**
     * บันทึกข้อมูล
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        // session, token, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            $ret = array();
            try {
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                if (preg_match('/^category_name_([0-9]+)_([0-9]+)_([0-9]+)$/', $action, $match)) {
                    $search = $this->db()->createQuery()
                        ->from('category')
                        ->where(array(
                            array('account_id', (int) $match[1]),
                            array('category_id', (int) $match[2]),
                            array('id', (int) $match[3]),
                        ))
                        ->toArray()
                        ->first();
                    $value = $request->post('value')->topic();
                    if ($value != '') {
                        $this->db()->update($this->getTableName('category'), array(
                            array('account_id', $search['account_id']),
                            array('category_id', $search['category_id']),
                            array('id', $search['id']),
                        ), array('topic' => $value));
                    } else {
                        // คืนค่าข้อมูลเดิมถ้าไม่มีข้อความส่งมา
                        $value = $search['value'];
                    }
                    // ส่งข้อมูลใหม่ไปแสดงผล
                    $ret['edit'] = $value;
                    $ret['editId'] = $action;
                }

            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
            // คืนค่าเป็น JSON
            if (!empty($ret)) {
                echo json_encode($ret);
            }
        }
    }
}
