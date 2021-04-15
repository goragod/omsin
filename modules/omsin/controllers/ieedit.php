<?php
/**
 * @filesource modules/omsin/controllers/ieedit.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Ieedit;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ieedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แก้ไข/ดู รายรับ-รายจ่าย
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        if ($login = Login::isMember()) {
            $index = \Omsin\Ierecord\Model::get($login['account_id'], $request->request('id')->toInt());
            $typies = array(
                'IN' => 'Income',
                'OUT' => 'Expense',
                'INIT' => 'Summit',
            );
            if ($index && isset($typies[$index->status])) {
                // ข้อความ title bar
                $this->title = Language::get('Details of').' '.Language::get($typies[$index->status]);
                // เลือกเมนู
                $this->menu = 'ierecord';
                // แสดงผล
                $section = Html::create('section', array(
                    'class' => 'content_bg',
                ));
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs',
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><a class="icon-home" href="index.php">{LNG_Home}</a></li>');
                $ul->appendChild('<li><span>{LNG_Edit}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>',
                ));
                $section->add('a', array(
                    'id' => 'ierecord',
                    'href' => WEB_URL.'index.php?module=omsin-ierecord',
                    'title' => '{LNG_Recording} {LNG_Income}/{LNG_Expense}',
                    'class' => 'icon-edit notext',
                ));
                // แสดงฟอร์ม
                $section->appendChild(createClass('Omsin\Ieedit\View')->render($request, $index));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
