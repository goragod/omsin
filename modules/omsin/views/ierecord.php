<?php
/**
 * @filesource modules/omsin/views/ierecord.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Ierecord;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ierecord
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มเพิ่ม รายรับ-รายจ่าย
     *
     * @param Request $request
     * @param object  $owner
     *
     * @return string
     */
    public function render(Request $request, $owner)
    {
        $form = Html::create('form', array(
            'id' => 'product',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/omsin/model/ierecord/submit',
            'onsubmit' => 'doFormSubmit',
            'token' => true,
            'ajax' => true,
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Recording}',
        ));
        // กระเป๋าเงิน
        $wallets = \Omsin\Wallet\Model::toSelect($owner->account_id);
        // ตัวเลือกว่าจะทำอะไร
        $status = array();
        if (!empty($wallets)) {
            $status['OUT'] = '{LNG_Recording} {LNG_Expense}';
            $status['IN'] = '{LNG_Recording} {LNG_Income}';
        }
        if (count($wallets) > 1) {
            $status['TRANSFER'] = '{LNG_Transfer between accounts}';
        }
        $status['INIT'] = '{LNG_Add New} {LNG_Wallet}';
        // status
        $fieldset->add('select', array(
            'id' => 'write_status',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-config',
            'label' => '{LNG_What do you want to do}?',
            'options' => $status,
            'autofocus' => true,
        ));
        // category
        $fieldset->add('text', array(
            'id' => 'write_category',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-category',
            'label' => '{LNG_Category}',
            'maxlength' => 40,
            'placeholder' => Language::replace('Fill some of the :name to find', array(':name' => '{LNG_Category}')),
            'comment' => '{LNG_Enter the category of receipts/expenses. Used for grouping such as food, utilities}',
        ));
        // wallet
        $fieldset->add('select', array(
            'id' => 'write_wallet',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_Wallet}',
            'options' => $wallets,
            'value' => $request->cookie('ierecord_wallet')->toInt(),
        ));
        // wallet_name
        $fieldset->add('text', array(
            'id' => 'write_wallet_name',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_Wallet}',
            'maxlength' => 40,
            'comment' => '{LNG_Enter wallet name for recording your incomes/expenses, such as Cash or Bank account name}',
        ));
        $groups = $fieldset->add('groups', array(
            'id' => 'transfer',
            'class' => 'hidden item',
        ));
        // from
        $groups->add('select', array(
            'id' => 'write_from',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_From}',
            'options' => $wallets,
        ));
        // to
        $groups->add('select', array(
            'id' => 'write_to',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_To}',
            'options' => $wallets,
        ));
        // สกุลเงิน
        $currency_units = Language::get('CURRENCY_UNITS');
        // amount
        $fieldset->add('currency', array(
            'id' => 'write_amount',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-money',
            'data-keyboard' => '0123456789.,',
            'label' => '{LNG_Amount} ('.$currency_units[self::$cfg->currency_unit].')',
        ));
        // create_date
        $fieldset->add('date', array(
            'id' => 'write_create_date',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-calendar',
            'label' => '{LNG_Date}',
            'value' => date('Y-m-d'),
        ));
        // comment
        $fieldset->add('text', array(
            'id' => 'write_comment',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => '{LNG_Annotation}',
            'maxlength' => 255,
            'comment' => '{LNG_Notes or Additional Notes}',
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit',
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large',
            'value' => '{LNG_Save}',
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'write_id',
            'value' => 0,
        ));
        // account_id
        $fieldset->add('hidden', array(
            'id' => 'write_account_id',
            'value' => $owner->account_id,
        ));
        // Javascript
        $form->script('initIerecord();');
        // คืนค่าฟอร์ม
        return $form->render();
    }
}
