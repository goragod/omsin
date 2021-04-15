<?php
/**
 * @filesource modules/omsin/views/ieyearly.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Ieyearly;

use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ieyearly
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายงานรายปี.
     *
     * @param Request $request
     * @param array   $owner   ข้อมูลที่ต้องการ
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $owner, $login)
    {
        // query ข้อมูลสรุปรายปี
        $query = \Omsin\Iereport\Model::yearly($owner);
        $datas = array();
        $max = 0;
        foreach ($query['summary'] as $item) {
            $max = max($item['income'], $item['expense'], $max);
            $datas[] = $item;
        }
        if (!empty($datas)) {
            $lng = Language::getItems(array(
                'YEAR_OFFSET',
                'CURRENCY_UNITS',
            ));
            $currency_unit = $lng['CURRENCY_UNITS'][self::$cfg->currency_unit];
            $row = '<div class=dashboard>';
            $row .= '<section class=card><h3>{LNG_Monthly Report} {LNG_Year} '.($owner['year'] + $lng['YEAR_OFFSET']).'</h3><div class=body>';
            foreach ($datas as $item) {
                if (preg_match('/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})$/', $item['create_date'], $match)) {
                    $row .= '<div class="chart bg'.(int) $match[2].'">';
                    $row .= '<a class=title href="index.php?module=omsin-iereport&amp;year='.$match[1].'&amp;month='.$match[2].'" title="{LNG_Daily Report}">'.Date::format($item['create_date'], 'M').'</a>';
                    $row .= '<div class=group>';
                    $row .= '<div class=item><span class=label>{LNG_Income}</span><span class="bar positive" style="width:'.((100 * $item['income']) / $max).'%"><span>'.Currency::format($item['income']).' '.$currency_unit.'</span></span></div>';
                    $row .= '<div class=item><span class=label>{LNG_Expense}</span><span class="bar negative" style="width:'.((100 * $item['expense']) / $max).'%"><span>'.Currency::format($item['expense']).' '.$currency_unit.'</span></span></div>';
                    $row .= '</div>';
                    $row .= '</div>';
                }
            }
            $row .= '</div></section>';
            $datas = array();
            $max = 0;
            foreach ($query['category'] as $item) {
                $max = max($item['expense'], $max);
                $datas[] = $item;
            }
            $row .= '<section class="card margin-top"><h3>{LNG_Summary of expenditures by category}</h3><div class=body>';
            $categories = \Omsin\Select\Model::ieCategories($owner['account_id'], 'OUT');
            foreach ($datas as $i => $item) {
                $row .= '<div class="chart">';
                $cat = isset($categories[$item['category_id']]) ? $categories[$item['category_id']] : 'Unknow';
                $row .= '<div class=item><span class=label>'.$cat.'</span><span class="bar bg'.(($i % 12) + 1).'" style="width:'.((100 * $item['expense']) / $max).'%"><span>'.Currency::format($item['expense']).' '.$currency_unit.'</span></span></div>';
                $row .= '</div>';
            }
            $row .= '</div></section>';
            $row .= '</div>';
            return $row;
        } else {
            // ไม่มีข้อมูล
            return '<aside class=error>{LNG_Sorry, no information available for this item.}</aside>';
        }
    }
}
