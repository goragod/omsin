<?php
/**
 * @filesource modules/omsin/models/select.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Select;

use Kotchasan\Database\Sql;

/**
 * Class สำหรับอ่านข้อมูลจากหมวดหมู่เพื่อใส่ลงใน select
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านปีที่มีการทำรายการ อย่างน้อยต้องมีปีปัจจุบัน
     *
     * @param int $account_id
     *
     * @return array
     */
    public static function getYears($account_id)
    {
        $query = static::createQuery()
            ->select(Sql::Year('create_date', 'year'))
            ->from('ierecord')
            ->where(array('account_id', $account_id))
            ->groupBy(Sql::Year('create_date'))
            ->cacheOn()
            ->toArray();
        $result = array();
        // ปีตาม พ.ศ.
        $year_offset = \Kotchasan\Language::get('YEAR_OFFSET');
        foreach ($query->execute() as $item) {
            $result[$item['year']] = $item['year'] + $year_offset;
        }
        // ปีนี้
        $y = (int) date('Y');
        $result[$y] = $y + $year_offset;
        return $result;
    }

    /**
     * กระเป๋าเงิน
     *
     * @param int $account_id
     *
     * @return array
     */
    public static function wallets($account_id)
    {
        return self::toSelect($account_id, 4);
    }

    /**
     * หมวดหมู่รายรับ/รายจ่าย
     *
     * @param int    $account_id
     * @param string $status     รายรับ IN / รายจ่าย OUT
     *
     * @return array
     */
    public static function ieCategories($account_id, $status)
    {
        return self::toSelect($account_id, $status == 'OUT' ? 2 : 1);
    }

    /**
     * อ่านข้อมูลลงใน select
     *
     * @param int $account_id
     * @param int $typ
     *
     * @return array
     */
    private static function toSelect($account_id, $typ)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where(array(
                array('account_id', $account_id),
                array('id', $typ),
            ))
            ->order('account_id DESC', 'topic')
            ->cacheOn()
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['category_id']] = $item['topic'];
        }
        return $result;
    }
}
