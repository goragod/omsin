<?php
/**
 * @filesource modules/omsin/views/search.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Search;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-search
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var mixed
     */
    private $wallet;
    /**
     * @var mixed
     */
    private $categories;
    /**
     * @var int
     */
    private $total = 0;
    /**
     * @var int
     */
    private $wallet_id = 0;

    /**
     * รายงานรายวัน.
     *
     * @param Request $request
     * @param array   $owner   ข้อมูลที่ต้องการ
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $owner, $login)
    {
        $this->wallet_id = $owner['wallet'];
        $this->categories = array(
            'TRANSFER' => array(
                0 => '{LNG_Transfer between accounts}',
            ),
            'INIT' => array(
                0 => '{LNG_Summit}',
            ),
        );
        $this->wallet = array(0 => '{LNG_all items}');
        foreach (\Omsin\Category\Model::all($owner['account_id'], array(RECEIVE, EXPENSE, WALLET)) as $item) {
            if ($item['id'] == RECEIVE) {
                // หมวดหมู่รายรับ
                $this->categories['IN'][$item['category_id']] = $item['topic'];
            } elseif ($item['id'] == EXPENSE) {
                // หมวดหมู่รายจ่าย
                $this->categories['OUT'][$item['category_id']] = $item['topic'];
            } elseif ($item['id'] == WALLET) {
                // กระเป๋าเงิน
                $this->wallet[$item['category_id']] = $item['topic'];
            }
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Omsin\Iereport\Model::search($owner),
            /* เรียงลำดับ */
            'sort' => $request->cookie('search_Sort', 'create_date,id')->toString(),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'wallet',
                    'text' => '{LNG_Wallet}',
                    'options' => $this->wallet,
                    'value' => $owner['wallet'],
                ),
                array(
                    'name' => 'status',
                    'text' => '{LNG_Type}',
                    'options' => array('' => '{LNG_all items}', 'IN' => '{LNG_Income}', 'OUT' => '{LNG_Expense}', 'TRANSFER' => '{LNG_Transfer between accounts}', 'INIT' => '{LNG_Summit}'),
                    'value' => $owner['status'],
                ),
                array(
                    'name' => 'year',
                    'text' => '{LNG_Year}',
                    'options' => array(0 => '{LNG_all items}')+\Omsin\Select\Model::getYears($owner['account_id']),
                    'value' => $owner['year'],
                ),
                array(
                    'name' => 'month',
                    'text' => '{LNG_Month}',
                    'options' => array(0 => '{LNG_all items}') + Language::get('MONTH_LONG'),
                    'value' => $owner['month'],
                ),
            ),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('search_perPage', 30)->toInt(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'account_id', 'status', 'expense', 'transfer_to'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('comment'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/omsin/model/search/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}',
                    ),
                ),
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Date}',
                    'sort' => 'create_date',
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'sort' => 'category_id',
                ),
                'wallet' => array(
                    'text' => '{LNG_Wallet}',
                    'sort' => 'wallet',
                ),
                'comment' => array(
                    'text' => '{LNG_Annotation}',
                ),
                'income' => array(
                    'text' => '{LNG_Amount}',
                    'class' => 'center',
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'income' => array(
                    'class' => 'right',
                ),
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'omsin-ieedit', 'id' => ':id')),
                    'text' => '{LNG_Edit}',
                ),
            ),
        ));
        // save cookie
        setcookie('search_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('search_Sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['status'] == 'INIT') {
            $item['category_id'] = '{LNG_Summit}';
        } else {
            $item['category_id'] = isset($this->categories[$item['status']][$item['category_id']]) ? $this->categories[$item['status']][$item['category_id']] : 'Unknow';
        }
        if ($item['status'] == 'TRANSFER') {
            $res = array(
                isset($this->wallet[$item['wallet']]) ? $this->wallet[$item['wallet']] : 'Unknow',
                isset($this->wallet[$item['transfer_to']]) ? $this->wallet[$item['transfer_to']] : 'Unknow',
            );
            $item['wallet'] = implode(' =&gt; ', $res);
            if ($this->wallet_id > 0 && $this->wallet_id == $item['transfer_to']) {
                $item['income'] = $item['expense'];
                $item['expense'] = 0;
            }
            $item['income'] = Currency::format($item['expense']);
        } else {
            $item['wallet'] = isset($this->wallet[$item['wallet']]) ? $this->wallet[$item['wallet']] : 'Unknow';
            $this->total += ($item['income'] - $item['expense']);
            if ($item['income'] > 0) {
                $item['income'] = '<span class=color-green>+'.Currency::format($item['income']).'</span>';
            } else {
                $item['income'] = '<span class=color-red>-'.Currency::format($item['expense']).'</span>';
            }
        }
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        return $item;
    }

    /**
     * ฟังก์ชั่นสร้างแถวของ footer.
     *
     * @return string
     */
    public function onCreateFooter()
    {
        return '<tr><td class=right colspan=4></td><td>{LNG_Total}</td><td class="right color-'.($this->total < 0 ? 'red' : 'green').'">'.Currency::format($this->total).'</td></tr>';
    }

    /**
     * ฟังก์ชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่.
     *
     * @param $btn        string id ของ button
     * @param $attributes array  property ของปุ่ม
     * @param $item      array  ข้อมูลในแถว
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        return $item['status'] == 'TRANSFER' ? false : $attributes;
    }
}
