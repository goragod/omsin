<?php
/**
 * @filesource modules/index/views/home.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Index\Home;

use Kotchasan\Currency;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=dashboard.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้า Dashboard.
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        $datas = array();
        $total_income = 0;
        $total_expense = 0;
        foreach (\Omsin\Dashboard\Model::get((int) $login['account_id']) as $item) {
            if ($item['topic'] === '0') {
                $today = $item;
            } else {
                if (in_array($item['status'], array('IN', 'INIT', 'TRANSFER'))) {
                    if (isset($datas[$item['topic']])) {
                        $datas[$item['topic']] += $item['income'];
                    } else {
                        $datas[$item['topic']] = $item['income'];
                    }
                }
                if (in_array($item['status'], array('IN', 'INIT'))) {
                    $total_income += $item['income'];
                }
                if (in_array($item['status'], array('OUT', 'TRANSFER'))) {
                    if (isset($datas[$item['topic']])) {
                        $datas[$item['topic']] -= $item['expense'];
                    } else {
                        $datas[$item['topic']] = 0 - $item['expense'];
                    }
                }
                if ($item['status'] == 'OUT') {
                    $total_expense += $item['expense'];
                }
            }
        }
        $total = $total_income - $total_expense;
        $wallet = array();
        foreach ($datas as $topic => $item) {
            if ($total == 0) {
                $wallet[] = '<dd class=item><span class=label>'.$topic.'</span><span style="width:1px;" class="bar positive"><span>'.Currency::format($item).' {UNIT}</span></span></dd>';
            } else {
                $wallet[] = '<dd class=item><span class=label>'.$topic.'</span><span class="bar '.($item < 0 ? 'negative' : 'positive').'" style="width:'.((100 * abs($item)) / $total).'%;"><span>'.Currency::format($item).' {UNIT}</span></span></dd>';
            }
        }
        $wallet[] = '<dd class=item><span class=label>{LNG_Total}</span><span class="bar total" style="width:'.($total == 0 ? '1px' : '100%').'"><span>'.Currency::format($total).' {UNIT}</span></span></dd>';
        // โหลด template
        $template = Template::create('', '', 'dashboard');
        // สกุลเงิน
        $currency_units = Language::get('CURRENCY_UNITS');
        $template->add(array(
            '/{RECEIPTS}/' => Currency::format(isset($today['income']) ? $today['income'] : 0),
            '/{EXPENSES}/' => Currency::format(isset($today['expense']) ? $today['expense'] : 0),
            '/{ALLRECEIPTS}/' => Currency::format($total_income),
            '/{ALLEXPENSES}/' => Currency::format($total_expense),
            '/{WALLET}/' => implode('', $wallet),
            '/{UNIT}/' => $currency_units[self::$cfg->currency_unit],
        ));

        return $template->render();
    }
}
