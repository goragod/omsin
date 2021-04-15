<?php
/**
 * @filesource modules/omsin/models/search.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Search;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * module=omsin-search
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับค่าจาก action (search.php)
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
                $ret['location'] = 'reload';
            }
            if (!empty($ret)) {
                // คืนค่า JSON
                echo json_encode($ret);
            }
        }
    }
}
