<?php
/**
 * @filesource modules/omsin/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล.
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        $submenus = array(
            'report' => array(
                'text' => '{LNG_Report}',
                'url' => 'index.php?module=omsin-iereport',
            ),
            'search' => array(
                'text' => '{LNG_Search}',
                'url' => 'index.php?module=omsin-search',
            ),
            'database' => array(
                'text' => '{LNG_Import}/{LNG_Export}',
                'url' => 'index.php?module=omsin-database',
            ),
        );
        foreach (Language::get('OMSIN_CATEGORIES') as $cat => $label) {
            $submenus['categories'.$cat] = array(
                'text' => $label,
                'url' => 'index.php?module=omsin-categories&amp;type='.$cat,
            );
        }
        $menu->addTopLvlMenu('tools', '{LNG_Tools}', null, $submenus, 'member');
        $menu->addTopLvlMenu('about', '{LNG_About}', 'index.php?module=omsin-about', null, 'signin');
        $menu->addTopLvlMenu('iereport', '{LNG_Income}/{LNG_Expense} {LNG_today}', 'index.php?module=omsin-iereport&amp;date='.date('Y-m-d'), null, 'module');
        $menu->addTopLvlMenu('ierecord', '{LNG_Recording} {LNG_Income}/{LNG_Expense}', 'index.php?module=omsin-ierecord', null, 'module');
    }
}
