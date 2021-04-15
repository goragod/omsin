<?php
/**
 * @filesource modules/omsin/views/database.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Database;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=omsin-database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์ม Import/Export
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/omsin/model/database/submit',
            'onsubmit' => 'doFormSubmit',
            'token' => true,
            'ajax' => true,
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Import}',
        ));
        // import
        $fieldset->add('file', array(
            'id' => 'csv',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'placeholder' => 'omsin.csv',
            'comment' => '{LNG_Select a file for importing} <em>omsin.csv</em> (<a href="index.php/omsin/model/database/demo" target=_blank>{LNG_sample file download}</a>)',
            'accept' => array('csv'),
        ));
        $div = $fieldset->add('div', array(
            'class' => 'item',
        ));
        // submit
        $div->add('submit', array(
            'class' => 'button ok large icon-import',
            'value' => '{LNG_Import}',
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Export}',
        ));
        $div = $fieldset->add('div', array(
            'class' => 'item',
        ));
        $div->add('div', array(
            'class' => 'message',
            'innerHTML' => '{LNG_Download <em>omsin.csv</em> file for database backup}',
        ));
        // export
        $div->add('a', array(
            'class' => 'button ok large icon-export',
            'innerHTML' => '{LNG_Export}',
            'href' => WEB_URL.'index.php/omsin/model/database/export',
            'target' => '_blank',
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'item',
        ));
        $div = $fieldset->add('div', array(
            'class' => 'warning',
            'innerHTML' => '<p>{LNG_press Reset button to delete all user data}</p>',
        ));
        // submit
        $div->add('button', array(
            'id' => 'database_reset',
            'class' => 'button red large icon-delete',
            'value' => '{LNG_Reset}',
        ));
        // Javascript
        $form->script('callClick("database_reset", doDatabaseReset);');
        // คืนค่าฟอร์ม
        return $form->render();
    }
}
