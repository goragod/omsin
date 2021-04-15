<?php
/**
 * @filesource modules/omsin/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Omsin\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาจากตาราง category
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findCategory(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            try {
                $search = $request->post('name')->topic();
                $query = $this->db()->createQuery()
                    ->select('category_id id', 'topic name')
                    ->from('category')
                    ->where(array(
                        array('account_id', $request->post('id')->toInt()),
                        array('id', $request->post('typ')->toInt()),
                    ))
                    ->order('account_id DESC', 'topic')
                    ->limit($request->post('count')->toInt())
                    ->toArray();
                if ($search != '') {
                    $query->andWhere(array('topic', 'LIKE', '%'.$search.'%'));
                }
                $result = $query->execute();
                // คืนค่า JSON
                if (!empty($result)) {
                    echo json_encode($result);
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
