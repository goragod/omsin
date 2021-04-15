<?php
/**
 * @filesource modules/omsin/models/iereport.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Iereport;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * module=omsin-iereport
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด แยกรายปี
     *
     * @param array $owner
     *
     * @return array
     */
    public static function summary($owner)
    {
        $q1 = static::createQuery()
            ->select(Sql::YEAR('create_date', 'Y'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array('status', array('IN', 'OUT')),
            ))
            ->groupBy('Y')
            ->order('Y DESC')
            ->toArray();
        $q2 = static::createQuery()
            ->select('category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array('status', 'OUT'),
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute(),
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด ปีที่เลือก แยกรายเดือน
     *
     * @param array $owner
     *
     * @return array
     */
    public static function yearly($owner)
    {
        $q1 = static::createQuery()
            ->select('id', 'account_id', 'create_date', 'income', 'expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array('status', array('IN', 'OUT')),
            ));
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'create_date', '0 income', '0 expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array('category_id', 0),
            ));
        $q1 = static::createQuery()
            ->select('id', 'account_id', 'create_date', Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from(array(static::createQuery()->unionAll($q1, $q2), 'Z'))
            ->groupBy(Sql::YEAR('create_date'), Sql::MONTH('create_date'))
            ->toArray();
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array('status', 'OUT'),
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute(),
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด ปีและเดือนที่เลือก แยกรายวัน
     *
     * @param array $owner
     *
     * @return array
     */
    public static function monthly($owner)
    {
        $q1 = static::createQuery()
            ->select('id', 'account_id', 'create_date', 'income', 'expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array(Sql::MONTH('create_date'), (int) $owner['month']),
                array('status', array('IN', 'OUT')),
            ));
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'create_date', '0 income', '0 expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array(Sql::MONTH('create_date'), (int) $owner['month']),
                array('status', array('INIT', 'TRANSFER')),
            ));
        $q1 = static::createQuery()
            ->select('id', 'account_id', 'create_date', Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from(array(static::createQuery()->unionAll($q1, $q2), 'Z'))
            ->groupBy(Sql::DAY('create_date'))
            ->toArray();
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array(Sql::YEAR('create_date'), (int) $owner['year']),
                array(Sql::MONTH('create_date'), (int) $owner['month']),
                array('status', 'OUT'),
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute(),
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด วันที่เลือก
     *
     * @param array $owner
     *
     * @return array
     */
    public static function daily($owner)
    {
        return static::createQuery()
            ->select('id', 'account_id', 'category_id', 'wallet', 'comment', 'income', 'expense', 'status', 'transfer_to')
            ->from('ierecord')
            ->where(array(
                array('account_id', $owner['account_id']),
                array('create_date', $owner['date']),
            ));
    }

    /**
     * รายงานที่กำหนดเอง
     *
     * @param array $owner
     *
     * @return array
     */
    public static function search($owner)
    {
        $model = new static();
        $where = array(
            array('account_id', $owner['account_id']),
        );
        if (!empty($owner['wallet'])) {
            $where[] = $model->groupOr(array(
                array('wallet', $owner['wallet']),
                array('transfer_to', $owner['wallet']),
            ));
        }
        if (!empty($owner['status'])) {
            $where[] = array('status', $owner['status']);
        }
        if (!empty($owner['year'])) {
            $where[] = array(Sql::YEAR('create_date'), $owner['year']);
        }
        if (!empty($owner['month'])) {
            $where[] = array(Sql::MONTH('create_date'), $owner['month']);
        }

        return static::createQuery()
            ->select('id', 'account_id', 'create_date', 'category_id', 'wallet', 'comment', 'income', 'expense', 'status', 'transfer_to')
            ->from('ierecord')
            ->where($where);
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            $ret = array();
            // รับค่าจากการ POST
            $action = $request->post('action')->toString();
            if ($action === 'delete') {
                $id = $request->post('id')->toInt();
                $this->db()->delete($this->getTableName('ierecord'), array(
                    array('account_id', $login['account_id']),
                    array('id', $id),
                ));
                $ret['remove'] = $request->post('src')->toString().'_'.$id;
            }
            if (!empty($ret)) {
                // คืนค่า JSON
                echo json_encode($ret);
            }
        }
    }
}
