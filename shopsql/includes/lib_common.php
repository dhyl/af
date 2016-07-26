<?php

if (!defined('IN_HHS'))
{
    die('Hacking attempt');
}

/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '')
{
    if (empty($item_list))
    {
        return $field_name . " IN ('') ";
    }
    else
    {
        if (!is_array($item_list))
        {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item)
        {
            if ($item !== '')
            {
                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp))
        {
            return $field_name . " IN ('') ";
        }
        else
        {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}

/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
    {
        if (preg_match($chars, $user_email))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

/**
 * 检查是否为一个合法的时间格式
 *
 * @access  public
 * @param   string  $time
 * @return  void
 */
function is_time($time)
{
    $pattern = '/[\d]{4}-[\d]{1,2}-[\d]{1,2}\s[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/';

    return preg_match($pattern, $time);
}

/**
 * 获得查询时间和次数，并赋值给smarty
 *
 * @access  public
 * @return  void
 */
function assign_query_info()
{
    if ($GLOBALS['db']->queryTime == '')
    {
        $query_time = 0;
    }
    else
    {
        if (PHP_VERSION >= '5.0.0')
        {
            $query_time = number_format(microtime(true) - $GLOBALS['db']->queryTime, 6);
        }
        else
        {
            list($now_usec, $now_sec)     = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }
    $GLOBALS['smarty']->assign('query_info', sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time));

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
    {
        $GLOBALS['smarty']->assign('memory_info', sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1048576));
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];
    $GLOBALS['smarty']->assign('gzip_enabled', $gzip_enabled);
}

/**
 * 创建地区的返回信息
 *
 * @access  public
 * @param   array   $arr    地区数组 *
 * @return  void
 */
function region_result($parent, $sel_name, $type)
{
    global $cp;

    $arr = get_regions($type, $parent);
    foreach ($arr AS $v)
    {
        $region      =& $cp->add_node('region');
        $region_id   =& $region->add_node('id');
        $region_name =& $region->add_node('name');

        $region_id->set_data($v['region_id']);
        $region_name->set_data($v['region_name']);
    }
    $select_obj =& $cp->add_node('select');
    $select_obj->set_data($sel_name);
}

/**
 * 获得指定国家的所有省份
 *
 * @access      public
 * @param       int     country    国家的编号
 * @return      array
 */
function get_regions($type = 0, $parent = 0)
{
    $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['hhs']->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";
    return $GLOBALS['db']->GetAll($sql);
}

/**
 * 获得配送区域中指定的配送方式的配送费用的计算参数
 *
 * @access  public
 * @param   int     $area_id        配送区域ID
 *
 * @return array;
 */
function get_shipping_config($area_id)
{
    /* 获得配置信息 */
    $sql = 'SELECT configure FROM ' . $GLOBALS['hhs']->table('shipping_area') . " WHERE shipping_area_id = '$area_id'";
    $cfg = $GLOBALS['db']->GetOne($sql);

    if ($cfg)
    {
        /* 拆分成配置信息的数组 */
        $arr = unserialize($cfg);
    }
    else
    {
        $arr = array();
    }

    return $arr;
}

/**
 * 初始化会员数据整合类
 *
 * @access  public
 * @return  object
 */
function &init_users()
{
    $set_modules = false;
    static $cls = null;
    if ($cls != null)
    {
        return $cls;
    }
    include_once(ROOT_PATH . 'includes/modules/integrates/' . $GLOBALS['_CFG']['integrate_code'] . '.php');
    $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);
    $cls = new $GLOBALS['_CFG']['integrate_code']($cfg);

    return $cls;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */
function cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_pid_releate');
        if ($data === false)
        {
            $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children ".
                'FROM ' . $GLOBALS['hhs']->table('category') . " AS c ".
                "LEFT JOIN " . $GLOBALS['hhs']->table('category') . " AS s ON s.parent_id=c.cat_id ".
                "GROUP BY c.cat_id ".
                'ORDER BY c.parent_id, c.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);

            $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['hhs']->table('goods') .
                    " WHERE is_delete = 0 AND is_on_sale = 1 " .
                    " GROUP BY cat_id";
            $res2 = $GLOBALS['db']->getAll($sql);

            $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['hhs']->table('goods_cat') . " AS gc , " . $GLOBALS['hhs']->table('goods') . " AS g " .
                    " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 " .
                    " GROUP BY gc.cat_id";
            $res3 = $GLOBALS['db']->getAll($sql);

            $newres = array();
            foreach($res2 as $k=>$v)
            {
                $newres[$v['cat_id']] = $v['goods_num'];
                foreach($res3 as $ks=>$vs)
                {
                    if($v['cat_id'] == $vs['cat_id'])
                    {
                    $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                    }
                }
            }

            foreach($res as $k=>$v)
            {
                $res[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
            }
            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000)
            {
                write_static_cache('cat_pid_releate', $res);
            }
        }
        else
        {
            $res = $data;
        }
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false)
    {
        foreach ($options as $key => $val)
        {
            if ($val['level'] > $children_level)
            {
                unset($options[$key]);
            }
            else
            {
                if ($val['is_show'] == 0)
                {
                    unset($options[$key]);
                    if ($children_level > $val['level'])
                    {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                }
                else
                {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('category', array('cid' => $value['cat_id']), $value['cat_name']);
        }

        return $options;
    }
}

/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('cat_option_static');
        if ($data === false)
        {
            while (!empty($arr))
            {
                foreach ($arr AS $key => $value)
                {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0)
                    {
                        if ($value['parent_id'] > 0)
                        {
                            break;
                        }

                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0)
                        {
                            continue;
                        }
                        $last_cat_id  = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id)
                    {
                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0)
                        {
                            if (end($cat_id_array) != $last_cat_id)
                            {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id    = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    }
                    elseif ($value['parent_id'] > $last_cat_id)
                    {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1)
                {
                    $last_cat_id = array_pop($cat_id_array);
                }
                elseif ($count == 1)
                {
                    if ($last_cat_id != end($cat_id_array))
                    {
                        $last_cat_id = end($cat_id_array);
                    }
                    else
                    {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id]))
                {
                    $level = $level_array[$last_cat_id];
                }
                else
                {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000)
            {
                write_static_cache('cat_option_static', $options);
            }
        }
        else
        {
            $options = $data;
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 载入配置信息
 *
 * @access  public
 * @return  array
 */
function load_config()
{
    $arr = array();

    $data = read_static_cache('shop_config');
    if ($data === false)
    {
        $sql = 'SELECT code, value FROM ' . $GLOBALS['hhs']->table('shop_config') . ' WHERE parent_id > 0';
        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res AS $row)
        {
            $arr[$row['code']] = $row['value'];
        }

        /* 对数值型设置处理 */
        $arr['watermark_alpha']      = intval($arr['watermark_alpha']);
        $arr['market_price_rate']    = floatval($arr['market_price_rate']);
        $arr['integral_scale']       = floatval($arr['integral_scale']);
        //$arr['integral_percent']     = floatval($arr['integral_percent']);
        $arr['cache_time']           = intval($arr['cache_time']);
        $arr['thumb_width']          = intval($arr['thumb_width']);
        $arr['thumb_height']         = intval($arr['thumb_height']);
        $arr['image_width']          = intval($arr['image_width']);
        $arr['image_height']         = intval($arr['image_height']);
        $arr['best_number']          = !empty($arr['best_number']) && intval($arr['best_number']) > 0 ? intval($arr['best_number'])     : 3;
        $arr['new_number']           = !empty($arr['new_number']) && intval($arr['new_number']) > 0 ? intval($arr['new_number'])      : 3;
        $arr['hot_number']           = !empty($arr['hot_number']) && intval($arr['hot_number']) > 0 ? intval($arr['hot_number'])      : 3;
        $arr['promote_number']       = !empty($arr['promote_number']) && intval($arr['promote_number']) > 0 ? intval($arr['promote_number'])  : 3;
        $arr['top_number']           = intval($arr['top_number'])      > 0 ? intval($arr['top_number'])      : 10;
        $arr['history_number']       = intval($arr['history_number'])  > 0 ? intval($arr['history_number'])  : 5;
        $arr['comments_number']      = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
        $arr['article_number']       = intval($arr['article_number'])  > 0 ? intval($arr['article_number'])  : 5;
        $arr['page_size']            = intval($arr['page_size'])       > 0 ? intval($arr['page_size'])       : 10;
        $arr['bought_goods']         = intval($arr['bought_goods']);
        $arr['goods_name_length']    = intval($arr['goods_name_length']);
        $arr['top10_time']           = intval($arr['top10_time']);
        $arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
        $arr['no_picture']           = !empty($arr['no_picture']) ? str_replace('../', './', $arr['no_picture']) : 'images/no_picture.gif'; // 修改默认商品图片的路径
        $arr['qq']                   = !empty($arr['qq']) ? $arr['qq'] : '';
        $arr['ww']                   = !empty($arr['ww']) ? $arr['ww'] : '';
        $arr['default_storage']      = isset($arr['default_storage']) ? intval($arr['default_storage']) : 1;
        $arr['min_goods_amount']     = isset($arr['min_goods_amount']) ? floatval($arr['min_goods_amount']) : 0;
        $arr['one_step_buy']         = empty($arr['one_step_buy']) ? 0 : 1;
        $arr['invoice_type']         = empty($arr['invoice_type']) ? array('type' => array(), 'rate' => array()) : unserialize($arr['invoice_type']);
        $arr['show_order_type']      = isset($arr['show_order_type']) ? $arr['show_order_type'] : 0;    // 显示方式默认为列表方式
        $arr['help_open']            = isset($arr['help_open']) ? $arr['help_open'] : 1;    // 显示方式默认为列表方式

        if (!isset($GLOBALS['_CFG']['hhs_version']))
        {
            /* 如果没有版本号则默认为2.0.5 */
            $GLOBALS['_CFG']['hhs_version'] = 'v2.0.5';
        }

        //限定语言项
        $lang_array = array('zh_cn', 'zh_tw', 'en_us');
        if (empty($arr['lang']) || !in_array($arr['lang'], $lang_array))
        {
            $arr['lang'] = 'zh_cn'; // 默认语言为简体中文
        }

        if (empty($arr['integrate_code']))
        {
            $arr['integrate_code'] = 'hhshop'; // 默认的会员整合插件为 hhshop
        }
        write_static_cache('shop_config', $arr);
    }
    else
    {
        $arr = $data;
    }

    return $arr;
}

/**
 * 取得品牌列表
 * @return array 品牌列表 id => name
 */
function get_brand_list()
{
    $brand_list = read_static_cache('brands');
    if ($brand_list === false)
    {

        $sql = 'SELECT brand_id, brand_name FROM ' . $GLOBALS['hhs']->table('brand') . ' ORDER BY sort_order';
        $res = $GLOBALS['db']->getAll($sql);

        $brand_list = array();
        foreach ($res AS $row)
        {
            $brand_list[$row['brand_id']] = addslashes($row['brand_name']);
        }
        write_static_cache('brands', $brand_list);
    }

    return $brand_list;
}

/**
 * 获得某个分类下
 *
 * @access  public
 * @param   int     $cat
 * @return  array
 */
function get_brands($cat = 0, $app = 'brand')
{
    global $page_libs;
    $template = basename(PHP_SELF);
    $template = substr($template, 0, strrpos($template, '.'));
    include_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_template.php');
    static $static_page_libs = null;
    if ($static_page_libs == null)
    {
            $static_page_libs = $page_libs;
    }

    $children = ($cat > 0) ? ' AND ' . get_children($cat) : '';

    $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, b.brand_desc, COUNT(*) AS goods_num, IF(b.brand_logo > '', '1', '0') AS tag ".
            "FROM " . $GLOBALS['hhs']->table('brand') . "AS b, ".
                $GLOBALS['hhs']->table('goods') . " AS g ".
            "WHERE g.brand_id = b.brand_id $children AND is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY tag DESC, b.sort_order ASC";
    if (isset($static_page_libs[$template]['/library/brands.lbi']))
    {
        $num = get_library_number("brands");
        $sql .= " LIMIT $num ";
    }
    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row AS $key => $val)
    {
        $row[$key]['url'] = build_uri($app, array('cid' => $cat, 'bid' => $val['brand_id']), $val['brand_name']);
        $row[$key]['brand_desc'] = htmlspecialchars($val['brand_desc'],ENT_QUOTES);
    }

    return $row;
}

/**
 *  所有的促销活动信息
 *
 * @access  public
 * @return  array
 */
function get_promotion_info($goods_id = '')
{
    $snatch = array();
    $group = array();
    $auction = array();
    $package = array();
    $favourable = array();

    $gmtime = gmtime();
    $sql = 'SELECT act_id, act_name, act_type, start_time, end_time FROM ' . $GLOBALS['hhs']->table('goods_activity') . " WHERE is_finished=0 AND start_time <= '$gmtime' AND end_time >= '$gmtime'";
    if(!empty($goods_id))
    {
        $sql .= " AND goods_id = '$goods_id'";
    }
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $data)
    {
        switch ($data['act_type'])
        {
            case GAT_SNATCH: //夺宝奇兵
                $snatch[$data['act_id']]['act_name'] = $data['act_name'];
                $snatch[$data['act_id']]['url'] = build_uri('snatch', array('sid' => $data['act_id']));
                $snatch[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $snatch[$data['act_id']]['sort'] = $data['start_time'];
                $snatch[$data['act_id']]['type'] = 'snatch';
                break;

            case GAT_GROUP_BUY: //团购
                $group[$data['act_id']]['act_name'] = $data['act_name'];
                $group[$data['act_id']]['url'] = build_uri('group_buy', array('gbid' => $data['act_id']));
                $group[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $group[$data['act_id']]['sort'] = $data['start_time'];
                $group[$data['act_id']]['type'] = 'group_buy';
                break;

            case GAT_AUCTION: //拍卖
                $auction[$data['act_id']]['act_name'] = $data['act_name'];
                $auction[$data['act_id']]['url'] = build_uri('auction', array('auid' => $data['act_id']));
                $auction[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $auction[$data['act_id']]['sort'] = $data['start_time'];
                $auction[$data['act_id']]['type'] = 'auction';
                break;

            case GAT_PACKAGE: //礼包
                $package[$data['act_id']]['act_name'] = $data['act_name'];
                $package[$data['act_id']]['url'] = 'package.php#' . $data['act_id'];
                $package[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $package[$data['act_id']]['sort'] = $data['start_time'];
                $package[$data['act_id']]['type'] = 'package';
                break;
        }
    }

    $user_rank = ',' . $_SESSION['user_rank'] . ',';
    $favourable = array();
    $sql = 'SELECT act_id, act_range, act_range_ext, act_name, start_time, end_time FROM ' . $GLOBALS['hhs']->table('favourable_activity') . " WHERE start_time <= '$gmtime' AND end_time >= '$gmtime'";
    if(!empty($goods_id))
    {
        $sql .= " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'";
    }
    $res = $GLOBALS['db']->getAll($sql);

    if(empty($goods_id))
    {
        foreach ($res as $rows)
        {
            $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
            $favourable[$rows['act_id']]['url'] = 'activity.php';
            $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
            $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
            $favourable[$rows['act_id']]['type'] = 'favourable';
        }
    }
    else
    {
        $sql = "SELECT cat_id, brand_id FROM " . $GLOBALS['hhs']->table('goods') .
           "WHERE goods_id = '$goods_id'";
        $row = $GLOBALS['db']->getRow($sql);
        $category_id = $row['cat_id'];
        $brand_id = $row['brand_id'];

        foreach ($res as $rows)
        {
            if ($rows['act_range'] == FAR_ALL)
            {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = 'activity.php';
                $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
            }
            elseif ($rows['act_range'] == FAR_CATEGORY)
            {
                /* 找出分类id的子分类id */
                $id_list = array();
                $raw_id_list = explode(',', $rows['act_range_ext']);
                foreach ($raw_id_list as $id)
                {
                    $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                }
                $ids = join(',', array_unique($id_list));

                if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
            elseif ($rows['act_range'] == FAR_BRAND)
            {
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
            elseif ($rows['act_range'] == FAR_GOODS)
            {
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
        }
    }

//    if(!empty($goods_id))
//    {
//        return array('snatch'=>$snatch, 'group_buy'=>$group, 'auction'=>$auction, 'favourable'=>$favourable);
//    }

    $sort_time = array();
    $arr = array_merge($snatch, $group, $auction, $package, $favourable);
    foreach($arr as $key => $value)
    {
        $sort_time[] = $value['sort'];
    }
    array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

    return $arr;
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 * @return  string
 */
function get_children($cat = 0)
{
    return 'g.cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(cat_list($cat, 0, false)))));
}

/**
 * 获得指定文章分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 *
 * @return void
 */
function get_article_children ($cat = 0)
{
    return db_create_in(array_unique(array_merge(array($cat), array_keys(article_cat_list($cat, 0, false)))), 'cat_id');
}

/**
 * 获取邮件模板
 *
 * @access  public
 * @param:  $tpl_name[string]       模板代码
 *
 * @return array
 */
function get_mail_template($tpl_name)
{
    $sql = 'SELECT template_subject, is_html, template_content FROM ' . $GLOBALS['hhs']->table('mail_templates') . " WHERE template_code = '$tpl_name'";

    return $GLOBALS['db']->GetRow($sql);

}

/**
 * 记录订单操作记录
 *
 * @access  public
 * @param   string  $order_sn           订单编号
 * @param   integer $order_status       订单状态
 * @param   integer $shipping_status    配送状态
 * @param   integer $pay_status         付款状态
 * @param   string  $note               备注
 * @param   string  $username           用户名，用户自己的操作则为 buyer
 * @return  void
 */
function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0)
{
    if (is_null($username))
    {
        $username = $_SESSION['admin_name'];
    }

    $sql = 'INSERT INTO ' . $GLOBALS['hhs']->table('order_action') .
                ' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
            'SELECT ' .
                "order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" .gmtime() . "' " .
            'FROM ' . $GLOBALS['hhs']->table('order_info') . " WHERE order_sn = '$order_sn'";
    $GLOBALS['db']->query($sql);
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $change_price = true)
{
    if($price==='')
    {
     $price=0;
    }
    if ($change_price && defined('HHS_ADMIN') === false)
    {
        switch ($GLOBALS['_CFG']['price_format'])
        {
            case 0:
                $price = number_format($price, 2, '.', '');
                break;
            case 1: // 保留不为 0 的尾数
                $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                if (substr($price, -1) == '.')
                {
                    $price = substr($price, 0, -1);
                }
                break;
            case 2: // 不四舍五入，保留1位
                $price = substr(number_format($price, 2, '.', ''), 0, -1);
                break;
            case 3: // 直接取整
                $price = intval($price);
                break;
            case 4: // 四舍五入，保留 1 位
                $price = number_format($price, 1, '.', '');
                break;
            case 5: // 先四舍五入，不保留小数
                $price = round($price);
                break;
        }
    }
    else
    {
        $price = number_format($price, 2, '.', '');
    }

    return sprintf($GLOBALS['_CFG']['currency_format'], $price);
}

/**
 * 返回订单中的虚拟商品
 *
 * @access  public
 * @param   int   $order_id   订单id值
 * @param   bool  $shipping   是否已经发货
 *
 * @return array()
 */
function get_virtual_goods($order_id, $shipping = false)
{
    if ($shipping)
    {
        $sql = 'SELECT goods_id, goods_name, send_number AS num, extension_code FROM '.
           $GLOBALS['hhs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND extension_code > ''";
    }
    else
    {
        $sql = 'SELECT goods_id, goods_name, (goods_number - send_number) AS num, extension_code FROM '.
           $GLOBALS['hhs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND is_real = 0 AND (goods_number - send_number) > 0 AND extension_code > '' ";
    }
    $res = $GLOBALS['db']->getAll($sql);

    $virtual_goods = array();
    foreach ($res AS $row)
    {
        $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
    }

    return $virtual_goods;
}

/**
 *  虚拟商品发货
 *
 * @access  public
 * @param   array  $virtual_goods   虚拟商品数组
 * @param   string $msg             错误信息
 * @param   string $order_sn        订单号。
 * @param   string $process         设定当前流程：split，发货分单流程；other，其他，默认。
 *
 * @return bool
 */
function virtual_goods_ship(&$virtual_goods, &$msg, $order_sn, $return_result = false, $process = 'other')
{
    $virtual_card = array();
    foreach ($virtual_goods AS $code => $goods_list)
    {
        /* 只处理虚拟卡 */
        if ($code == 'virtual_card')
        {
            foreach ($goods_list as $goods)
            {
                if (virtual_card_shipping($goods, $order_sn, $msg, $process))
                {
                    if ($return_result)
                    {
                        $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>virtual_card_result($order_sn, $goods));
                    }
                }
                else
                {
                    return false;
                }
            }
            $GLOBALS['smarty']->assign('virtual_card',      $virtual_card);
        }
    }

    return true;
}

/**
 *  虚拟卡发货
 *
 * @access  public
 * @param   string      $goods      商品详情数组
 * @param   string      $order_sn   本次操作的订单
 * @param   string      $msg        返回信息
 * @param   string      $process    设定当前流程：split，发货分单流程；other，其他，默认。
 *
 * @return  boolen
 */
function virtual_card_shipping ($goods, $order_sn, &$msg, $process = 'other')
{
    /* 包含加密解密函数所在文件 */
    include_once(ROOT_PATH . 'includes/lib_code.php');

    /* 检查有没有缺货 */
    $sql = "SELECT COUNT(*) FROM ".$GLOBALS['hhs']->table('virtual_card')." WHERE goods_id = '$goods[goods_id]' AND is_saled = 0 ";
    $num = $GLOBALS['db']->GetOne($sql);

    if ($num < $goods['num'])
    {
        $msg .= sprintf($GLOBALS['_LANG']['virtual_card_oos'], $goods['goods_name']);

        return false;
    }

     /* 取出卡片信息 */
     $sql = "SELECT card_id, card_sn, card_password, end_date, crc32 FROM ".$GLOBALS['hhs']->table('virtual_card')." WHERE goods_id = '$goods[goods_id]' AND is_saled = 0  LIMIT " . $goods['num'];
     $arr = $GLOBALS['db']->getAll($sql);

     $card_ids = array();
     $cards = array();

     foreach ($arr as $virtual_card)
     {
        $card_info = array();

        /* 卡号和密码解密 */
        if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY))
        {
            $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
            $card_info['card_password'] = decrypt($virtual_card['card_password']);
        }
        elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY))
        {
            $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
            $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
        }
        else
        {
            $msg .= 'error key';

            return false;
        }
        $card_info['end_date'] = date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
        $card_ids[] = $virtual_card['card_id'];
        $cards[] = $card_info;
     }

     /* 标记已经取出的卡片 */
    $sql = "UPDATE ".$GLOBALS['hhs']->table('virtual_card')." SET ".
           "is_saled = 1 ,".
           "order_sn = '$order_sn' ".
           "WHERE " . db_create_in($card_ids, 'card_id');
    if (!$GLOBALS['db']->query($sql, 'SILENT'))
    {
        $msg .= $GLOBALS['db']->error();

        return false;
    }

    /* 更新库存 */
    $sql = "UPDATE ".$GLOBALS['hhs']->table('goods'). " SET goods_number = goods_number - '$goods[num]' WHERE goods_id = '$goods[goods_id]'";
    $GLOBALS['db']->query($sql);

    if (true)
    {
        /* 获取订单信息 */
        $sql = "SELECT order_id, order_sn, consignee, email FROM ".$GLOBALS['hhs']->table('order_info'). " WHERE order_sn = '$order_sn'";
        $order = $GLOBALS['db']->GetRow($sql);

        /* 更新订单信息 */
        if ($process == 'split')
        {
            $sql = "UPDATE ".$GLOBALS['hhs']->table('order_goods'). "
                    SET send_number = send_number + '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }
        else
        {
            $sql = "UPDATE ".$GLOBALS['hhs']->table('order_goods'). "
                    SET send_number = '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }

        if (!$GLOBALS['db']->query($sql, 'SILENT'))
        {
            $msg .= $GLOBALS['db']->error();

            return false;
        }
    }

    /* 发送邮件 */
    $GLOBALS['smarty']->assign('virtual_card',                   $cards);
    $GLOBALS['smarty']->assign('order',                          $order);
    $GLOBALS['smarty']->assign('goods',                          $goods);

    $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
    $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

    $tpl = get_mail_template('virtual_card');
    $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);

    return true;
}

/**
 *  返回虚拟卡信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function virtual_card_result($order_sn, $goods)
{
    /* 包含加密解密函数所在文件 */
    include_once(ROOT_PATH . 'includes/lib_code.php');

    /* 获取已经发送的卡片数据 */
    $sql = "SELECT card_sn, card_password, end_date, crc32 FROM ".$GLOBALS['hhs']->table('virtual_card')." WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";
    $res= $GLOBALS['db']->query($sql);

    $cards = array();

    while ($row = $GLOBALS['db']->FetchRow($res))
    {
        /* 卡号和密码解密 */
        if ($row['crc32'] == 0 || $row['crc32'] == crc32(AUTH_KEY))
        {
            $row['card_sn'] = decrypt($row['card_sn']);
            $row['card_password'] = decrypt($row['card_password']);
        }
        elseif ($row['crc32'] == crc32(OLD_AUTH_KEY))
        {
            $row['card_sn'] = decrypt($row['card_sn'], OLD_AUTH_KEY);
            $row['card_password'] = decrypt($row['card_password'], OLD_AUTH_KEY);
        }
        else
        {
            $row['card_sn'] = '***';
            $row['card_password'] = '***';
        }

        $cards[] = array('card_sn'=>$row['card_sn'], 'card_password'=>$row['card_password'], 'end_date'=>date($GLOBALS['_CFG']['date_format'], $row['end_date']));
    }

    return $cards;
}

/**
 * 获取指定 id snatch 活动的结果
 *
 * @access  public
 * @param   int   $id       snatch_id
 *
 * @return  array           array(user_name, bie_price, bid_time, num)
 *                          num通常为1，如果为2表示有2个用户取到最小值，但结果只返回最早出价用户。
 */
function get_snatch_result($id)
{
    $sql = 'SELECT u.user_id, u.user_name, u.email, lg.bid_price, lg.bid_time, count(*) as num' .
            ' FROM ' . $GLOBALS['hhs']->table('snatch_log') . ' AS lg '.
            ' LEFT JOIN ' . $GLOBALS['hhs']->table('users') . ' AS u ON lg.user_id = u.user_id'.
            " WHERE lg.snatch_id = '$id'".
            ' GROUP BY lg.bid_price' .
            ' ORDER BY num ASC, lg.bid_price ASC, lg.bid_time ASC LIMIT 1';
    $rec = $GLOBALS['db']->GetRow($sql);

    if ($rec)
    {
        $rec['bid_time']  = local_date($GLOBALS['_CFG']['time_format'], $rec['bid_time']);
        $rec['formated_bid_price'] = price_format($rec['bid_price'], false);

        /* 活动信息 */
        $sql = 'SELECT ext_info " .
               " FROM ' . $GLOBALS['hhs']->table('goods_activity') .
               " WHERE act_id= '$id' AND act_type=" . GAT_SNATCH.
               " LIMIT 1";
        $row = $GLOBALS['db']->getOne($sql);
        $info = unserialize($row);

        if (!empty($info['max_price']))
        {
            $rec['buy_price'] = ($rec['bid_price'] > $info['max_price']) ? $info['max_price'] : $rec['bid_price'];
        }
        else
        {
            $rec['buy_price'] = $rec['bid_price'];
        }



        /* 检查订单 */
        $sql = "SELECT COUNT(*)" .
                " FROM " . $GLOBALS['hhs']->table('order_info') .
                " WHERE extension_code = 'snatch'" .
                " AND extension_id = '$id'" .
                " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_UNCONFIRMED));

        $rec['order_count'] = $GLOBALS['db']->getOne($sql);
    }

    return $rec;
}

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($is_cache = true, $ext = '')
{
    $dirs = array();

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)
    {
        $tmp_dir = DATA_DIR ;
    }
    else
    {
        $tmp_dir = 'temp';
    }
    if ($is_cache)
    {
        $cache_dir = ROOT_PATH . $tmp_dir . '/caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/query_caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/static_caches/';
        for($i = 0; $i < 16; $i++)
        {
            $hash_dir = $cache_dir . dechex($i);
            $dirs[] = $hash_dir . '/';
        }
    }
    else
    {
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count   = 0;

    foreach ($dirs AS $dir)
    {
        $folder = @opendir($dir);

        if ($folder === false)
        {
            continue;
        }

        while ($file = readdir($folder))
        {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
                /* 如果有文件名则判断是否匹配 */
                $pos = ($is_cache) ? strrpos($file, '_') : strrpos($file, '.');

                if ($str_len > 0 && $pos !== false)
                {
                    $ext_str = substr($file, 0, $pos);

                    if ($ext_str == $ext)
                    {
                        if (@unlink($dir . $file))
                        {
                            $count++;
                        }
                    }
                }
                else
                {
                    if (@unlink($dir . $file))
                    {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}

/**
 * 清除模版编译文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_compiled_files($ext = '')
{
    return clear_tpl_files(false, $ext);
}

/**
 * 清除缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_cache_files($ext = '')
{
    return clear_tpl_files(true, $ext);
}

/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = '')
{
    return clear_tpl_files(false, $ext) + clear_tpl_files(true,  $ext);
}

/**
 * 页面上调用的js文件
 *
 * @access  public
 * @param   string      $files
 * @return  void
 */
function smarty_insert_scripts($args)
{
    static $scripts = array();

    $arr = explode(',', str_replace(' ','',$args['files']));

    $str = '';
    foreach ($arr AS $val)
    {
        if (in_array($val, $scripts) == false)
        {
            $scripts[] = $val;
            if ($val{0} == '.')
            {
                $str .= '<script type="text/javascript" src="' . $val . '"></script>';
            }
            else
            {
                $str .= '<script type="text/javascript" src="js/' . $val . '"></script>';
            }
        }
    }

    return $str;
}

/**
 * 创建分页的列表
 *
 * @access  public
 * @param   integer $count
 * @return  string
 */
function smarty_create_pages($params)
{
    extract($params);

    $str = '';
    $len = 10;

    if (empty($page))
    {
        $page = 1;
    }

    if (!empty($count))
    {
        $step = 1;
        $str .= "<option value='1'>1</option>";

        for ($i = 2; $i < $count; $i += $step)
        {
            $step = ($i >= $page + $len - 1 || $i <= $page - $len + 1) ? $len : 1;
            $str .= "<option value='$i'";
            $str .= $page == $i ? " selected='true'" : '';
            $str .= ">$i</option>";
        }

        if ($count > 1)
        {
            $str .= "<option value='$count'";
            $str .= $page == $count ? " selected='true'" : '';
            $str .= ">$count</option>";
        }
    }

    return $str;
}

/**
 * 重写 URL 地址
 *
 * @access  public
 * @param   string  $app        执行程序
 * @param   array   $params     参数数组
 * @param   string  $append     附加字串
 * @param   integer $page       页数
 * @param   string  $keywords   搜索关键词字符串
 * @return  void
 */
function build_uri($app, $params, $append = '', $page = 0, $keywords = '', $size = 0)
{
    static $rewrite = NULL;

    if ($rewrite === NULL)
    {
        $rewrite = intval($GLOBALS['_CFG']['rewrite']);
    }

    $args = array('cid'   => 0,
                  'gid'   => 0,
                  'bid'   => 0,
                  'acid'  => 0,
                  'aid'   => 0,
                  'sid'   => 0,
                  'gbid'  => 0,
                  'auid'  => 0,
                  'sort'  => '',
                  'order' => ''
                );

    extract(array_merge($args, $params));

    $uri = '';
    switch ($app)
    {
        case 'category':
            if (empty($cid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'category-' . $cid;
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '-min'.$price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '-max'.$price_max;
                    }
                    if (isset($filter_attr))
                    {
                        $uri .= '-attr' . $filter_attr;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'category.php?id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'goods-' . $gid : 'goods.php?id=' . $gid;
            }

            break;
        case 'brand':
            if (empty($bid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'brand-' . $bid;
                    if (isset($cid))
                    {
                        $uri .= '-c' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'brand.php?id=' . $bid;
                    if (!empty($cid))
                    {
                        $uri .= '&amp;cat=' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'article_cat':
            if (empty($acid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'article_cat-' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '-' . $keywords;
                    }
                }
                else
                {
                    $uri = 'article_cat.php?id=' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }
            }

            break;
        case 'article':
            if (empty($aid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'article-' . $aid : 'article.php?id=' . $aid;
            }

            break;
        case 'group_buy':
            if (empty($gbid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&amp;id=' . $gbid;
            }

            break;
        case 'auction':
            if (empty($auid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'auction-' . $auid : 'auction.php?act=view&amp;id=' . $auid;
            }

            break;
        case 'snatch':
            if (empty($sid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid;
            }

            break;
        case 'search':
            break;
        case 'exchange':
            if ($rewrite)
            {
                $uri = 'exchange-' . $cid;
                if (isset($price_min))
                {
                    $uri .= '-min'.$price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '-max'.$price_max;
                }
                if (!empty($page))
                {
                    $uri .= '-' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '-' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '-' . $order;
                }
            }
            else
            {
                $uri = 'exchange.php?cat_id=' . $cid;
                if (isset($price_min))
                {
                    $uri .= '&amp;integral_min=' . $price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '&amp;integral_max=' . $price_max;
                }

                if (!empty($page))
                {
                    $uri .= '&amp;page=' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '&amp;sort=' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '&amp;order=' . $order;
                }
            }

            break;
        case 'exchange_goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'exchange-id' . $gid : 'exchange.php?id=' . $gid . '&amp;act=view';
            }
            break;
       case 'car_shop':
       		$uri = 'car_shop.php?1';
       		if (!empty($cid))
       		{
       			$uri .= '&amp;city_id=' . $cid;
       		}
       		if (!empty($page))
       		{
       			$uri .= '&amp;page=' . $page;
       		}  
           break;
       	case 'search_cars':
           	$uri = 'search_cars.php?1';
           	//传递价格参数
		    if (!empty($cid))
		       		{
		       			$uri .= '&amp;city_id=' . $cid;
		       		}
           	if (!empty($region_id))
           	{
           		$uri .= '&amp;region_id=' . $region_id;
           	}
         
           	if (!empty($suppliers_id))
           	{
           		$uri .= '&amp;suppliers_id=' . $suppliers_id;
           	}
           	if (!empty($id))
           	{
           		$uri .= '&amp;id=' . $id;
           	}
           	if (!empty($search))
           	{
           		$uri .= '&amp;search=' . $search;
           	}
           	if (!empty($price))
           	{
           		$uri .= '&amp;price=' . $price;
           	}
          
           	if (!empty($page))
           	{
           		$uri .= '&amp;page=' . $page;
           	}
          
           		
           	break;
        default:
            return false;
            break;
    }

    if ($rewrite)
    {
        if ($rewrite == 2 && !empty($append))
        {
            $uri .= '-' . urlencode(preg_replace('/[\.|\/|\?|&|\+|\\\|\'|"|,]+/', '', $append));
        }

        $uri .= '.html';
    }
    if (($rewrite == 2) && (strpos(strtolower(EC_CHARSET), 'utf') !== 0))
    {
        $uri = urlencode($uri);
    }
    return $uri;
}

/**
 * 格式化重量：小于1千克用克表示，否则用千克表示
 * @param   float   $weight     重量
 * @return  string  格式化后的重量
 */
function formated_weight($weight)
{
    $weight = round(floatval($weight), 3);
    if ($weight > 0)
    {
        if ($weight < 1)
        {
            /* 小于1千克，用克表示 */
            return intval($weight * 1000) . $GLOBALS['_LANG']['gram'];
        }
        else
        {
            /* 大于1千克，用千克表示 */
            return $weight . $GLOBALS['_LANG']['kilogram'];
        }
    }
    else
    {
        return 0;
    }
}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   float   $frozen_money   冻结余额变动
 * @param   int     $rank_points    等级积分变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $change_desc    变动说明
 * @param   int     $change_type    变动类型：参见常量文件
 * @return  void
 */
function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
{
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'frozen_money'  => $frozen_money,
        'rank_points'   => $rank_points,
        'pay_points'    => $pay_points,
        'change_time'   => gmtime(),
        'change_desc'   => $change_desc,
        'change_type'   => $change_type
    );
    $GLOBALS['db']->autoExecute($GLOBALS['hhs']->table('account_log'), $account_log, 'INSERT');

    /* 更新用户信息 */
    $sql = "UPDATE " . $GLOBALS['hhs']->table('users') .
            " SET user_money = user_money + ('$user_money')," .
            " frozen_money = frozen_money + ('$frozen_money')," .
            " rank_points = rank_points + ('$rank_points')," .
            " pay_points = pay_points + ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
    $GLOBALS['db']->query($sql);
}


/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @return  mix
 */
function article_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('art_cat_pid_releate');
        if ($data === false)
        {
            $sql = "SELECT c.*, COUNT(s.cat_id) AS has_children, COUNT(a.article_id) AS aricle_num ".
               ' FROM ' . $GLOBALS['hhs']->table('article_cat') . " AS c".
               " LEFT JOIN " . $GLOBALS['hhs']->table('article_cat') . " AS s ON s.parent_id=c.cat_id".
               " LEFT JOIN " . $GLOBALS['hhs']->table('article') . " AS a ON a.cat_id=c.cat_id".
               " GROUP BY c.cat_id ".
               " ORDER BY parent_id, sort_order ASC";
            $res = $GLOBALS['db']->getAll($sql);
            write_static_cache('art_cat_pid_releate', $res);
        }
        else
        {
            $res = $data;
        }
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = article_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    $pre_key = 0;
    foreach ($options AS $key => $value)
    {
        $options[$key]['has_children'] = 1;
        if ($pre_key > 0)
        {
            if ($options[$pre_key]['cat_id'] == $options[$key]['parent_id'])
            {
                $options[$pre_key]['has_children'] = 1;
            }
        }
        $pre_key = $key;
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ' cat_type="' . $var['cat_type'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('article_cat', array('acid' => $value['cat_id']), $value['cat_name']);
        }
        return $options;
    }
}

/**
 * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function article_cat_options($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        while (!empty($arr))
        {
            foreach ($arr AS $key => $value)
            {
                $cat_id = $value['cat_id'];
                if ($level == 0 && $last_cat_id == 0)
                {
                    if ($value['parent_id'] > 0)
                    {
                        break;
                    }

                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['has_children'] == 0)
                    {
                        continue;
                    }
                    $last_cat_id  = $cat_id;
                    $cat_id_array = array($cat_id);
                    $level_array[$last_cat_id] = ++$level;
                    continue;
                }

                if ($value['parent_id'] == $last_cat_id)
                {
                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['has_children'] > 0)
                    {
                        if (end($cat_id_array) != $last_cat_id)
                        {
                            $cat_id_array[] = $last_cat_id;
                        }
                        $last_cat_id    = $cat_id;
                        $cat_id_array[] = $cat_id;
                        $level_array[$last_cat_id] = ++$level;
                    }
                }
                elseif ($value['parent_id'] > $last_cat_id)
                {
                    break;
                }
            }

            $count = count($cat_id_array);
            if ($count > 1)
            {
                $last_cat_id = array_pop($cat_id_array);
            }
            elseif ($count == 1)
            {
                if ($last_cat_id != end($cat_id_array))
                {
                    $last_cat_id = end($cat_id_array);
                }
                else
                {
                    $level = 0;
                    $last_cat_id = 0;
                    $cat_id_array = array();
                    continue;
                }
            }

            if ($last_cat_id && isset($level_array[$last_cat_id]))
            {
                $level = $level_array[$last_cat_id];
            }
            else
            {
                $level = 0;
            }
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 调用UCenter的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function uc_call($func, $params=null)
{
    restore_error_handler();
    if (!function_exists($func))
    {
        include_once(ROOT_PATH . 'uc_client/client.php');
    }

    $res = call_user_func_array($func, $params);

    set_error_handler('exception_handler');

    return $res;
}

/**
 * error_handle回调函数
 *
 * @return
 */
function exception_handler($errno, $errstr, $errfile, $errline)
{
    return;
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
function get_image_path($goods_id, $image='', $thumb=false, $call='goods', $del=false)
{
    $url = empty($image) ? $GLOBALS['_CFG']['no_picture'] : $image;
    return $url;
}

/**
 * 调用使用UCenter插件时的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function user_uc_call($func, $params = null)
{
    if (isset($GLOBALS['_CFG']['integrate_code']) && $GLOBALS['_CFG']['integrate_code'] == 'ucenter')
    {
        restore_error_handler();
        if (!function_exists($func))
        {
            include_once(ROOT_PATH . 'includes/lib_uc.php');
        }

        $res = call_user_func_array($func, $params);

        set_error_handler('exception_handler');

        return $res;
    }
    else
    {
        return;
    }

}

/**
 * 取得商品优惠价格列表
 *
 * @param   string  $goods_id    商品编号
 * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
 *
 * @return  优惠价格列表
 */
function get_volume_price_list($goods_id, $price_type = '1')
{
    $volume_price = array();
    $temp_index   = '0';

    $sql = "SELECT `volume_number` , `volume_price`".
           " FROM " .$GLOBALS['hhs']->table('volume_price'). "".
           " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'".
           " ORDER BY `volume_number`";

    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $k => $v)
    {
        $volume_price[$temp_index]                 = array();
        $volume_price[$temp_index]['number']       = $v['volume_number'];
        $volume_price[$temp_index]['price']        = $v['volume_price'];
        $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
        $temp_index ++;
    }
    return $volume_price;
}

/**
 * 取得商品最终使用价格
 *
 * @param   string  $goods_id      商品编号
 * @param   string  $goods_num     购买数量
 * @param   boolean $is_spec_price 是否加入规格价格
 * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
 *
 * @return  商品最终购买价格
 */
function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array())
{
    $final_price   = '0'; //商品最终购买价格
    $volume_price  = '0'; //商品优惠价格
    $promote_price = '0'; //商品促销价格
    $user_price    = '0'; //商品会员价格

    //取得商品优惠价格列表
    $price_list   = get_volume_price_list($goods_id, '1');

    if (!empty($price_list))
    {
        foreach ($price_list as $value)
        {
            if ($goods_num >= $value['number'])
            {
                $volume_price = $value['price'];
            }
        }
    }

    //取得商品促销价格列表
    /* 取得商品信息 */
    $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, ".
                "IFNULL(mp.user_price, g.shop_price * '" . $_SESSION['discount'] . "') AS shop_price ".
           " FROM " .$GLOBALS['hhs']->table('goods'). " AS g ".
           " LEFT JOIN " . $GLOBALS['hhs']->table('member_price') . " AS mp ".
                   "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $_SESSION['user_rank']. "' ".
           " WHERE g.goods_id = '" . $goods_id . "'" .
           " AND g.is_delete = 0";
    $goods = $GLOBALS['db']->getRow($sql);

    /* 计算商品的促销价格 */
    if ($goods['promote_price'] > 0)
    {
        $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
    }
    else
    {
        $promote_price = 0;
    }

    //取得商品会员价格列表
    $user_price    = $goods['shop_price'];

    //比较商品的促销价格，会员价格，优惠价格
    if (empty($volume_price) && empty($promote_price))
    {
        //如果优惠价格，促销价格都为空则取会员价格
        $final_price = $user_price;
    }
    elseif (!empty($volume_price) && empty($promote_price))
    {
        //如果优惠价格为空时不参加这个比较。
        $final_price = min($volume_price, $user_price);
    }
    elseif (empty($volume_price) && !empty($promote_price))
    {
        //如果促销价格为空时不参加这个比较。
        $final_price = min($promote_price, $user_price);
    }
    elseif (!empty($volume_price) && !empty($promote_price))
    {
        //取促销价格，会员价格，优惠价格最小值
        $final_price = min($volume_price, $promote_price, $user_price);
    }
    else
    {
        $final_price = $user_price;
    }

    //如果需要加入规格价格
    if ($is_spec_price)
    {
        if (!empty($spec))
        {
            $spec_price   = spec_price($spec);
            $final_price  = $spec_price;
        }
    }

    //返回商品最终购买价格
    return $final_price;
}

/**
 * 将 goods_attr_id 的序列按照 attr_id 重新排序
 *
 * 注意：非规格属性的id会被排除
 *
 * @access      public
 * @param       array       $goods_attr_id_array        一维数组
 * @param       string      $sort                       序号：asc|desc，默认为：asc
 *
 * @return      string
 */
function sort_goods_attr_id_array($goods_attr_id_array, $sort = 'asc')
{
    if (empty($goods_attr_id_array))
    {
        return $goods_attr_id_array;
    }

    //重新排序
    $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " .$GLOBALS['hhs']->table('attribute'). " AS a
            LEFT JOIN " .$GLOBALS['hhs']->table('goods_attr'). " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
    $row = $GLOBALS['db']->GetAll($sql);

    $return_arr = array();
    foreach ($row as $value)
    {
        $return_arr['sort'][]   = $value['goods_attr_id'];

        $return_arr['row'][$value['goods_attr_id']]    = $value;
    }

    return $return_arr;
}

/**
 *
 * 是否存在规格
 *
 * @access      public
 * @param       array       $goods_attr_id_array        一维数组
 *
 * @return      string
 */
function is_spec($goods_attr_id_array, $sort = 'asc')
{
    if (empty($goods_attr_id_array))
    {
        return $goods_attr_id_array;
    }

    //重新排序
    $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " .$GLOBALS['hhs']->table('attribute'). " AS a
            LEFT JOIN " .$GLOBALS['hhs']->table('goods_attr'). " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
    $row = $GLOBALS['db']->GetAll($sql);

    $return_arr = array();
    foreach ($row as $value)
    {
        $return_arr['sort'][]   = $value['goods_attr_id'];

        $return_arr['row'][$value['goods_attr_id']]    = $value;
    }

    if(!empty($return_arr))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 获取指定id package 的信息
 *
 * @access  public
 * @param   int         $id         package_id
 *
 * @return array       array(package_id, package_name, goods_id,start_time, end_time, min_price, integral)
 */
function get_package_info($id)
{
    global $hhs, $db,$_CFG;
    $id = is_numeric($id)?intval($id):0;
    $now = gmtime();

    $sql = "SELECT act_id AS id,  act_name AS package_name, goods_id , goods_name, start_time, end_time, act_desc, ext_info".
           " FROM " . $GLOBALS['hhs']->table('goods_activity') .
           " WHERE act_id='$id' AND act_type = " . GAT_PACKAGE;

    $package = $db->GetRow($sql);

    /* 将时间转成可阅读格式 */
    if ($package['start_time'] <= $now && $package['end_time'] >= $now)
    {
        $package['is_on_sale'] = "1";
    }
    else
    {
        $package['is_on_sale'] = "0";
    }
    $package['start_time'] = local_date('Y-m-d H:i', $package['start_time']);
    $package['end_time']   = local_date('Y-m-d H:i', $package['end_time']);
    $row = unserialize($package['ext_info']);
    unset($package['ext_info']);
    if ($row)
    {
        foreach ($row as $key=>$val)
        {
            $package[$key] = $val;
        }
    }

    $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, ".
           " g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, g.is_real, ".
           " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price " .
           " FROM " . $GLOBALS['hhs']->table('package_goods') . " AS pg ".
           "   LEFT JOIN ". $GLOBALS['hhs']->table('goods') . " AS g ".
           "   ON g.goods_id = pg.goods_id ".
           " LEFT JOIN " . $GLOBALS['hhs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
           " WHERE pg.package_id = " . $id. " ".
           " ORDER BY pg.package_id, pg.goods_id";

    $goods_res = $GLOBALS['db']->getAll($sql);

    $market_price        = 0;
    $real_goods_count    = 0;
    $virtual_goods_count = 0;

    foreach($goods_res as $key => $val)
    {
        $goods_res[$key]['goods_thumb']         = get_image_path($val['goods_id'], $val['goods_thumb'], true);
        $goods_res[$key]['market_price_format'] = price_format($val['market_price']);
        $goods_res[$key]['rank_price_format']   = price_format($val['rank_price']);
        $market_price += $val['market_price'] * $val['goods_number'];
        /* 统计实体商品和虚拟商品的个数 */
        if ($val['is_real'])
        {
            $real_goods_count++;
        }
        else
        {
            $virtual_goods_count++;
        }
    }

    if ($real_goods_count > 0)
    {
        $package['is_real']            = 1;
    }
    else
    {
        $package['is_real']            = 0;
    }

    $package['goods_list']            = $goods_res;
    $package['market_package']        = $market_price;
    $package['market_package_format'] = price_format($market_price);
    $package['package_price_format']  = price_format($package['package_price']);

    return $package;
}

/**
 * 获得指定礼包的商品
 *
 * @access  public
 * @param   integer $package_id
 * @return  array
 */
function get_package_goods($package_id)
{
    $sql = "SELECT pg.goods_id, g.goods_name, pg.goods_number, p.goods_attr, p.product_number, p.product_id
            FROM " . $GLOBALS['hhs']->table('package_goods') . " AS pg
                LEFT JOIN " .$GLOBALS['hhs']->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['hhs']->table('products') . " AS p ON pg.product_id = p.product_id
            WHERE pg.package_id = '$package_id'";
    if ($package_id == 0)
    {
        $sql .= " AND pg.admin_id = '$_SESSION[admin_id]'";
    }
    $resource = $GLOBALS['db']->query($sql);
    if (!$resource)
    {
        return array();
    }

    $row = array();

    /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
    $good_product_str = '';
    while ($_row = $GLOBALS['db']->fetch_array($resource))
    {
        if ($_row['product_id'] > 0)
        {
            /* 取存商品id */
            $good_product_str .= ',' . $_row['goods_id'];

            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
        }
        else
        {
            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'];
        }

        //生成结果数组
        $row[] = $_row;
    }
    $good_product_str = trim($good_product_str, ',');

    /* 释放空间 */
    unset($resource, $_row, $sql);

    /* 取商品属性 */
    if ($good_product_str != '')
    {
        $sql = "SELECT goods_attr_id, attr_value FROM " .$GLOBALS['hhs']->table('goods_attr'). " WHERE goods_id IN ($good_product_str)";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }
    }

    /* 过滤货品 */
    $format[0] = '%s[%s]--[%d]';
    $format[1] = '%s--[%d]';
    foreach ($row as $key => $value)
    {
        if ($value['goods_attr'] != '')
        {
            $goods_attr_array = explode('|', $value['goods_attr']);

            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = $_goods_attr[$_attr];
            }

            $row[$key]['goods_name'] = sprintf($format[0], $value['goods_name'], implode('，', $goods_attr), $value['goods_number']);
        }
        else
        {
            $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['goods_number']);
        }
    }

    return $row;
}

/**
 * 取商品的货品列表
 *
 * @param       mixed       $goods_id       单个商品id；多个商品id数组；以逗号分隔商品id字符串
 * @param       string      $conditions     sql条件
 *
 * @return  array
 */
function get_good_products($goods_id, $conditions = '')
{
    if (empty($goods_id))
    {
        return array();
    }

    switch (gettype($goods_id))
    {
        case 'integer':

            $_goods_id = "goods_id = '" . intval($goods_id) . "'";

        break;

        case 'string':
        case 'array':

            $_goods_id = db_create_in($goods_id, 'goods_id');

        break;
    }

    /* 取货品 */
    $sql = "SELECT * FROM " .$GLOBALS['hhs']->table('products'). " WHERE $_goods_id $conditions";
    $result_products = $GLOBALS['db']->getAll($sql);

    /* 取商品属性 */
    $sql = "SELECT goods_attr_id, attr_value FROM " .$GLOBALS['hhs']->table('goods_attr'). " WHERE $_goods_id";
    $result_goods_attr = $GLOBALS['db']->getAll($sql);

    $_goods_attr = array();
    foreach ($result_goods_attr as $value)
    {
        $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
    }

    /* 过滤货品 */
    foreach ($result_products as $key => $value)
    {
        $goods_attr_array = explode('|', $value['goods_attr']);
        if (is_array($goods_attr_array))
        {
            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = $_goods_attr[$_attr];
            }

            $goods_attr_str = implode('，', $goods_attr);
        }

        $result_products[$key]['goods_attr_str'] = $goods_attr_str;
    }

    return $result_products;
}

/**
 * 取商品的下拉框Select列表
 *
 * @param       int      $goods_id    商品id
 *
 * @return  array
 */
function get_good_products_select($goods_id)
{
    $return_array = array();
    $products = get_good_products($goods_id);

    if (empty($products))
    {
        return $return_array;
    }

    foreach ($products as $value)
    {
        $return_array[$value['product_id']] = $value['goods_attr_str'];
    }

    return $return_array;
}

/**
 * 取商品的规格列表
 *
 * @param       int      $goods_id    商品id
 * @param       string   $conditions  sql条件
 *
 * @return  array
 */
function get_specifications_list($goods_id, $conditions = '')
{
    /* 取商品属性 */
    $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value, a.attr_name
            FROM " .$GLOBALS['hhs']->table('goods_attr'). " AS ga, " .$GLOBALS['hhs']->table('attribute'). " AS a
            WHERE ga.attr_id = a.attr_id
            AND ga.goods_id = '$goods_id'
            $conditions";
    $result = $GLOBALS['db']->getAll($sql);

    $return_array = array();
    foreach ($result as $value)
    {
        $return_array[$value['goods_attr_id']] = $value;
    }

    return $return_array;
}

/**
 * 调用array_combine函数
 *
 * @param   array  $keys
 * @param   array  $values
 *
 * @return  $combined
 */
if (!function_exists('array_combine')) {
    function array_combine($keys, $values)
    {
        if (!is_array($keys)) {
            user_error('array_combine() expects parameter 1 to be array, ' .
                gettype($keys) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_array($values)) {
            user_error('array_combine() expects parameter 2 to be array, ' .
                gettype($values) . ' given', E_USER_WARNING);
            return;
        }

        $key_count = count($keys);
        $value_count = count($values);
        if ($key_count !== $value_count) {
            user_error('array_combine() Both parameters should have equal number of elements', E_USER_WARNING);
            return false;
        }

        if ($key_count === 0 || $value_count === 0) {
            user_error('array_combine() Both parameters should have number of elements at least 0', E_USER_WARNING);
            return false;
        }

        $keys    = array_values($keys);
        $values  = array_values($values);

        $combined = array();
        for ($i = 0; $i < $key_count; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }

        return $combined;
    }
}
/**
 * 获取某城市，某业务，某天的车型
 * code by q7326128
 *
 */
function get_goods($city_id,$business_id='',$date_at='',$ext='')
{
    $where = "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
    if(!empty($city_id)) $where .= " AND `city_id` = ".$city_id ;
    if(!empty($business_id))  $where .= " AND `business_id` = ".$business_id;   
    if(!empty($date_at)) $where .= " AND `date_at` =".$date_at;
    $sql = "SELECT g.`business_id`,g.`cat_id`,g.`goods_id`, g.`goods_name`,g.`shop_price`,g.`goods_img`,p.`price` as 'today_price' FROM " . $GLOBALS['hhs']->table('goods') . " AS g LEFT JOIN " . $GLOBALS['hhs']->table('goods_price') . " as p ON g.`goods_id` = p.`goods_id`  $where ".$ext;
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 获取某店铺某服务下连续N天存在的车辆
 * code by q7326128
 *
 */
function get_stores_stock($city_id,$business_id = 8,$cat_id,$store_id,$start_date,$end_date)
{
    $end_date = $end_date ? $end_date : $start_date;
    $start_date=substr($start_date,0,10);
    $end_date=substr($end_date,0,10);
    $days = (strtotime($end_date) - strtotime($start_date))/86400 + 1;

    /*
    $sql = "SELECT goods_id, goods_name
            FROM
            (   SELECT `goods_id`,
                (
                    SELECT COUNT(*) FROM `hhs_store_goods` AS B WHERE B.`date_at` <= '".$end_date."' AND B.`date_at` >= '".$start_date."' AND B.stock>0 and A.`goods_id` = B.`goods_id` AND B.`store_id` = '".$store_id."'
                ) AS days,
                (
                    SELECT G.`goods_name` FROM `hhs_goods` AS G WHERE G.`goods_id` = A.`goods_id` and G.is_delete=0 and G.is_on_sale=1 and is_alone_sale=1
                ) AS goods_name
                FROM `hhs_store_goods` AS A WHERE  A.`store_id` = '".$store_id."'
            ) 
            temp WHERE days = ".$days." GROUP BY goods_id having goods_name is not null";
    */
    $sql = "select distinct b.goods_id, b.goods_name from hhs_store_goods a left join  hhs_goods b on a.goods_id=b.goods_id
            where b.business_id=8 and a.store_id=".$store_id." and b.is_delete=0 and b.is_on_sale=1 and b.is_alone_sale=1
            and a.`date_at` <= '".$end_date."' AND a.`date_at` >= '".$start_date."'  order by b.shop_price";

    return $GLOBALS['db']->getAll($sql);
}
/**
 * 获取某车辆当天的价格
 * code by q7326128
 *
 */
function get_today_price($goods_id, $date_at,$type=1,$cid=null)
{
	if($type==2){
		//基本信息
		$where=" `goods_id` = ".$goods_id." and channel_id=".$cid;
		
		$sql="SELECT `price` from " . $GLOBALS['hhs']->table('channel_sale') . " where ".$where." LIMIT 1";
		$goods_info = $GLOBALS['db']->getRow($sql);
		/* 取得当天价格 */
		$price = get_date_price($goods_id, $date_at,2,$cid);
		
		$price = $price >0.00 ? $price : $goods_info['price'];
		return $price;
	}elseif($type==3){
		//基本信息
		$where=" `goods_id` = ".$goods_id." and suppliers_id=".$cid;
		
		$sql="SELECT `sale_price` as price from " . $GLOBALS['hhs']->table('supplier_goods') . " where ".$where." LIMIT 1";
		$goods_info = $GLOBALS['db']->getRow($sql);
		/* 取得当天价格 */
		$price = get_date_price($goods_id, $date_at,3,$cid);
		
		$price = $price >0.00 ? $price : $goods_info['price'];
		return $price;
	}
	else{
		//基本信息
		$where=" `goods_id` = ".$goods_id;
		
		$goods_info = $GLOBALS['db']->getRow("SELECT `shop_price`,`is_promote`,`promote_start_date`,`promote_end_date`,`promote_price` from " . $GLOBALS['hhs']->table('goods') . " where  ".$where." LIMIT 1");
		/* 取得当天价格 */
		$price = get_date_price($goods_id, $date_at);//$GLOBALS['db']->getOne("SELECT `price` from " . $GLOBALS['hhs']->table('goods_price') . " WHERE `goods_id` = '".$goods_id."' AND `date_at` = '".date("Y-m-d")."' limit 1");
		
		//是否使用当天价格
		$price = $price >0.00 ? $price : $goods_info['shop_price'];
		
		$today = gmtime();
		$price = ($goods_info['is_promote'] == 1 &&
				$goods_info['promote_start_date'] <= $today && $goods_info['promote_end_date'] >= $today) ?
				$goods_info['promote_price'] : $price;
	}
    
    return $price;
}
/**
 * 获取某车辆当天的价格
 * code by q7326128
 *
 */
function get_today_cost($goods_id, $date_at)
{
		//基本信息
	$sql="SELECT `cost` from " . $GLOBALS['hhs']->table('goods') . " where `goods_id` = '".$goods_id."' LIMIT 1";
	$goods_info = $GLOBALS['db']->getRow($sql);
		/* 取得当天价格 */
	$price = get_date_cost($goods_id, $date_at);
	
	$price = $price >0.00 ? $price : $goods_info['cost'];
	return $price;
}
/**
 * 获取某车辆某天的库存价格
 * code by q7326128
 *
 */
function get_date_price($goods_id, $date_at,$type=1,$cid=null)
{
	if($type==2){
		$sql="select price from ".$GLOBALS['hhs']->table('channel_sale_price')." as csp where csp.goods_id=".$goods_id." and csp.channel_id=".$cid." and csp.date_at='".$date_at."' limit 1";	
		return  $GLOBALS['db']->getOne($sql);
	}elseif($type==3){
		$sql="select price from ".$GLOBALS['hhs']->table('suppliers_sale_price')." as csp where csp.goods_id=".$goods_id." and csp.suppliers_id=".$cid." and csp.date_at='".$date_at."' limit 1";
		return  $GLOBALS['db']->getOne($sql);
	}	
	else{
		return $GLOBALS['db']->getOne("SELECT `price` from " . $GLOBALS['hhs']->table('goods_price') . " WHERE `goods_id` = '".$goods_id."' AND `date_at` = '".$date_at."' limit 1");
	}  
}
/**
 * 获取某车辆某天的成本
 * code by q7326128
 *
 */
function get_date_cost($goods_id, $date_at)
{
	return $GLOBALS['db']->getOne("SELECT `cost` from " . $GLOBALS['hhs']->table('cost2') . " WHERE `goods_id` = '".$goods_id."' AND `date_at` = '".$date_at."' limit 1");
}
function get_order_goods_by_order_id($order_id)
{
    return $GLOBALS['db']->getRow("SELECT `goods_id`,`goods_name` from " . $GLOBALS['hhs']->table('order_goods') . " WHERE `order_id` = '".$order_id."' limit 1");
}

function get_suppliers_info_by_store_id($store_id)
{
    return $GLOBALS['db']->getRow("SELECT st.`store_id`,st.`store_name`,s.`suppliers_id`,s.`suppliers_name`,s.`api`,s.`parms` from " . $GLOBALS['hhs']->table('suppliers') . " as s, " . $GLOBALS['hhs']->table('stores') . " as st WHERE st.`store_id` = '".$store_id."' and st.`suppliers_id` = s.`suppliers_id` limit 1");
}

function get_suppliers_info_by_suppliers_id($suppliers_id)
{
    return $GLOBALS['db']->getRow("SELECT s.`suppliers_id`,s.`suppliers_name`,s.`api`,s.`parms` from " . $GLOBALS['hhs']->table('suppliers') . " as s WHERE s.`suppliers_id` = '$suppliers_id' limit 1");
}

function get_suppliers_name($suppliers_id){
    return $GLOBALS['db']->getOne("SELECT `suppliers_name` from " . $GLOBALS['hhs']->table('suppliers') . " WHERE `suppliers_id` = '$suppliers_id' limit 1");
}
function get_simple_name($suppliers_id){
	return $GLOBALS['db']->getOne("SELECT `simple_name` from " . $GLOBALS['hhs']->table('suppliers') . " WHERE `suppliers_id` = '$suppliers_id' limit 1");
}
function get_channel_name($channel_id){
	return $GLOBALS['db']->getOne("SELECT `title` from " . $GLOBALS['hhs']->table('channels') . " WHERE `channel_id` = '$channel_id' limit 1");
}
function get_stores_name($store_id){
    return $GLOBALS['db']->getOne("SELECT `store_name` FROM " . $GLOBALS['hhs']->table('stores') . " where `store_id` = '".$store_id."' limit 1");
}
function get_stores_addr($store_id){
	return $GLOBALS['db']->getOne("SELECT `addr` FROM " . $GLOBALS['hhs']->table('stores') . " where `store_id` = '".$store_id."' limit 1");
}
function get_stores_tel($store_id){
	return $GLOBALS['db']->getOne("SELECT `tel` FROM " . $GLOBALS['hhs']->table('stores') . " where `store_id` = '".$store_id."' limit 1");
}
//获取服务类型
function get_business(){
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('business');
        if ($data === false)
        {
            $sql = "SELECT `id`, `title`".
                   " FROM " . $GLOBALS['hhs']->table('business');
            $res = $GLOBALS['db']->getAll($sql);
            $data = array();
            foreach ($res as $key => $val) {
                $data[$val['id']] = $val['title'];
            }
            write_static_cache('business', $data);
        }
    }
    return $data;
}
//获取国际城市
function get_nation_citys(){
	$sql = "select region_id,region_name from hhs_region_internation  where region_type= 1";
	$res = $GLOBALS['db']->getAll($sql);
	$data = array();
	foreach ($res as $key => $val) {
		$data[$val['region_id']] = $val['region_name'];
	}
	return $data;
}
//获取渠道商
function get_channels(){
	static $res = NULL;

	if ($res === NULL)
	{
		$data = read_static_cache('channels');
		if ($data === false)
		{
			$sql = "SELECT `channel_id`, `title`".
					" FROM " . $GLOBALS['hhs']->table('channels');
			$res = $GLOBALS['db']->getAll($sql);
			$data = array();
			foreach ($res as $key => $val) {
				$data[$val['channel_id']] = $val['title'];
			}
			write_static_cache('channels', $data);
		}
	}

    return $data;
}
//获取城市
function get_citys(){
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('citys');
        if ($data === false)
        {
            $sql = "SELECT `region_id`, `region_name`".
                   " FROM " . $GLOBALS['hhs']->table('citys');
            $res = $GLOBALS['db']->getAll($sql);
            $data = array();
            foreach ($res as $key => $val) {
                $data[$val['region_id']] = $val['region_name'];
            }
            write_static_cache('citys', $data);
        }
    }
    return $data;
}
function get_citys_hhs(){
	$sql = "SELECT `region_id`, `region_name`".
			" FROM " . $GLOBALS['hhs']->table('citys');
	$res = $GLOBALS['db']->getAll($sql);
	return $res;
}
//获取vip的城市
function get_citys_vip($channel_id,$business_id){

			$sql="SELECT `region_id`,`region_name` FROM ".$GLOBALS['hhs']->table('citys')." AS c
				WHERE c.region_id IN ( SELECT city_id FROM ".$GLOBALS['hhs']->table('supplier_goods')." 
                 WHERE suppliers_id IN(SELECT suppliers_id FROM ".$GLOBALS['hhs']->table('channel_suppliers')."
				WHERE channel_id=".$channel_id." and business_id=".$business_id." ))";
			$res = $GLOBALS['db']->getAll($sql);
			$data = array();
			foreach ($res as $key => $val) {
				$data[$val['region_id']] = $val['region_name'];
			}

	return $data;
}
//获取vip车型
function get_cars_vip($city_id,$business_id,$channel_id,$ft_id){
			if($city_id && $channel_id && $business_id){
				//默认首页接机，有机场时查询车型价格。
				if($ft_id){
						
					$sql="SELECT c.goods_id,c.goods_name,c.cost,c.original_img FROM  ".$GLOBALS['hhs']->table('channel_suppliers')."  AS a
										INNER JOIN ".$GLOBALS['hhs']->table('suppliers')." AS d ON a.suppliers_id=d.suppliers_id
										INNER JOIN ".$GLOBALS['hhs']->table('supplier_goods')." AS b ON a.suppliers_id=b.suppliers_id
										INNER JOIN ".$GLOBALS['hhs']->table('goods')."  AS c ON c.goods_id=b.goods_id
										WHERE a.city_id=".$city_id." and c.is_delete = 0  AND c.ft=".$ft_id." AND a.business_id=".$business_id." AND a.channel_id=".$channel_id." AND
												 b.business_id=".$business_id." limit 0,4";
				}else{
					//查询渠道商协议	
					$sql="select count(*) as data from ".$GLOBALS['hhs']->table('channel_agreements')." where city_id=".$city_id."
	 					  AND business_id=".$business_id." and channel_id=".$_SESSION['vip']['channel_id'];
					$data = $GLOBALS['db']->getOne($sql);
					if(!$data){
						$res='';
						return $res;
						die();
					}
					//查询渠道商的供应商
					$sql="select count(*) as data from ".$GLOBALS['hhs']->table('channel_suppliers')." where city_id=".$city_id."
			 					 AND business_id=".$business_id." and channel_id=".$_SESSION['vip']['channel_id'];
					$data = $GLOBALS['db']->getOne($sql);
					if(!$data){
						$res='';
						return $res;
						die();
					}
				}
				//echo $sql;
					$car = $GLOBALS['db']->getAll($sql);
					//没有绑定有供应商
					if(!$car){
						$car =get_ezu365_car($city_id,$ft_id,$business_id);
					}
					$res=insert_goods_attr($car,1);	

			}
// 			var_dump($res);
	return $res;
}

function get_store_citys(){
	static $res = NULL;

	if ($res === NULL)
	{
		$data = read_static_cache('store_citys');
		//$data  = false;
		if ($data === false)
		{
			$store_sql = "SELECT `city_id`". " FROM " . $GLOBALS['hhs']->table('stores')." where `city_id` !='' ";
			$store_res = $GLOBALS['db']->getAll($store_sql);
			foreach ($store_res as $k => $v){
				$region_id = $v['city_id'];
				$sql = "SELECT `region_id`, `region_name`".
						" FROM " . $GLOBALS['hhs']->table('citys')." where `region_id` = '$region_id'";
				$res = $GLOBALS['db']->getAll($sql);
				$store_data[$k] = $res[0];
			}
			if($store_data){
				$data = array();
				foreach ($store_data as $key =>$val){
					$data[$val['region_id']] = $val['region_name'];
				}
			}
			write_static_cache('store_citys', $data);
		}

	}
	return $data;
}
//获取城市
function get_supplier_ranks(){
    static $res = NULL;
    if ($res === NULL)
    {
    	$data = read_static_cache('supplier_ranks');
        if ($data === false)
        {
            $sql = "SELECT `rank_id`, `rank_name`".
                   " FROM " . $GLOBALS['hhs']->table('supplies_rank');
            $res = $GLOBALS['db']->getAll($sql);
            $data = array();
            foreach ($res as $key => $val) {
                $data[$val['rank_id']] = $val['rank_name'];
            }
            write_static_cache('supplier_ranks', $data);
        }
    }

    return $data;
}
//获取代驾车型
function get_car_attributes($id){
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('car_attributes_'.$id);
        if ($data === false)
        {
            $sql = "SELECT `attr_values`".
                   " FROM " . $GLOBALS['hhs']->table('attribute') . " where `attr_id` = '$id'";
            $res = $GLOBALS['db']->getOne($sql);
            $arr = explode(PHP_EOL, $res);
            $data = array();
            foreach ($arr as $key => $val) {
                $data[$val] = $val;
            }
            write_static_cache('car_attributes_' . $id, $data);
        }
    }

    return $data;
}

//获取业务详情
function get_business_info($business_id)
{
    return $GLOBALS['db']->getRow("SELECT `title`,`code`,`base_time`,`base_distance`,`ratio` FROM ".$GLOBALS['hhs']->table('business')." where `id` = '$business_id' LIMIT 1");
}

function get_user_base($user_id)
{
    return $GLOBALS['db']->getRow("SELECT IFNULL(`truename`,`user_name`) as 'user_name',`mobile_phone` FROM ".$GLOBALS['hhs']->table('users')." where `user_id` = '$user_id' LIMIT 1");
}
function insert_goods_attr($cars,$cat_id){
    //1 :车型  2:同级别车 3 :两厢   4：1.2L  5：手动  6：乘客数
    //11:品牌    12:超公里费  13:超小时费 14 预授权 15:增值服务 18:基本小时 19：基本公里

	if($cat_id==2){//自驾
        if(!empty($cars['goods_id'])){//一维数组
       /* $attrs=get_goods_attr($cars[goods_id]); 
        */
        $properties = get_goods_properties2($cars['goods_id']);
        @$pro=$properties['pro']['attr'];
        @$cars[1]= $pro[1][value];//车型       
        @$cars[3]= $pro[3][value];//两厢
        @$cars[4]= $pro[4][value];//1.2L
        @$cars[5]= $pro[5][value];//手动   
        @$cars[6]= $pro[6][value];//乘客数

        @$cars[11]= $pro[11][value];//品牌
        @$cars[12]= $pro[12][value];//超公里费
        @$cars[13]= $pro[13][value];//超小时费       
        @$cars[14]= $pro[14][value];
        @$cars[18]= $pro[33][value];//基本小时
        @$cars[19]= $pro[34][value];//基本公里
        //$cars[14]=implode('',$attrs[14][goods_attr_list]);//预授权
        //var_dump($cars);
        }else{//二维数组          
            foreach($cars as $k=>$v){
                $v=insert_goods_attr($v,2);
                $cars[$k]=$v;   
            }
        }
    }else if($cat_id==1){//代驾
        //1 :车型 2:同级别车  3 :两厢   4：1.2L  5：手动  6：乘客数
    //11:品牌    12:超公里费  13:超小时费 14 预授权 15:增值服务18:基本小时 19：基本公里

        if(!empty($cars['goods_id'])){//一维数组    
        	$properties = get_goods_properties2($cars['goods_id']);
        	@$pro=$properties['pro']['attr'];
        	//$attrs=get_goods_attr($cars[goods_id]);
        	@$cars[6]=$pro[16][value];//乘客数
        	@$cars[1]=$pro[7][value];//代驾车型
        	@$cars[3]= $pro[3][value];//两厢
        	@$cars[4]= $pro[4][value];//1.2L
        	@$cars[5]= $pro[5][value];//手动
        	@$cars[2]=$pro[8][value];//同级别车
        	@$cars[13]=$pro[9][value];//超小时费
        	@$cars[12]=$pro[10][value];//超公里费
        	@$cars[18]= $pro[35][value];//小时
        	@$cars[19]= $pro[36][value];
        	
        /* 	 $properties = get_goods_properties2($cars['goods_id']);
	        $pro=$properties['pro']['attr'];
	        @$cars[1]= $pro[1][value];//车型       
	        @$cars[3]= $pro[3][value];//两厢
	        @$cars[4]= $pro[4][value];//1.2L
	        @$cars[5]= $pro[5][value];//手动   
	        @$cars[6]= $pro[6][value];//乘客数
	
	        @$cars[11]= $pro[11][value];//品牌
	        @$cars[12]= $pro[12][value];//超公里费
	        @$cars[13]= $pro[13][value];//超小时费       
	        @$cars[14]= $pro[14][value]; */
        }else{//二维数组         
            foreach($cars as $k=>$v){ 
                $v=insert_goods_attr($v,1);
                $cars[$k]=$v;                
            }
        }
    }
    return $cars;
}
function insert_goods1_attr($cars,$cat_id){
    //1 :车型  2:同级别车 3 :两厢   4：1.2L  5：手动  6：乘客数
    //11:品牌    12:超公里费  13:超小时费 14 预授权 15:增值服务

    
            foreach($cars as $k=>$v){ 
                $v=insert_goods_attr($v,1);
                $cars[$k]=$v;                
        
    }
    return $cars;
}
/* function format_time($time){
    $m=date('i');
    $m=floor($m/5)*5;
    $m=sprintf('%02d',$m);
    $dat=date('Y-m-d H',$time).':'.$m;
    return $dat;
} */
	function format_time($time){
		$m=date('i');
		$m=floor($m/5)*5;
		$m=sprintf('%02d',$m);
		$dat=date('Y-m-d 10:00',$time);
		return $dat;
	}
	function format_end($time){
		$m=date('i');
		$m=floor($m/5)*5;
		$m=sprintf('%02d',$m);
		$dat=date('Y-m-d 12:00',$time);
		return $dat;
	}
function get_goods_d($city_id,$cat_id=2,$business_id='',$ext='')
{
    $where = "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
    if(!empty($city_id)) $where .= " AND `city_id` = ".$city_id ;
    if(!empty($business_id))  $where .= " AND `business_id` = ".$business_id;   
    $where.=" and cat_id=".$cat_id;
    $orderby=' order by g.shop_price  ';
    $sql = "SELECT g.`business_id`,g.`goods_thumb`,g.`original_img`,g.`goods_img`, g.`cat_id`,g.`goods_id`, g.`goods_name`,g.`shop_price`,g.ft FROM " . $GLOBALS['hhs']->table('goods'). " as g  ".$where.$ext.$orderby;

    return $GLOBALS['db']->getAll($sql);
}  
//2015 7 3 cadd 接机区分豪华型
function get_goods_dc($city_id,$cat_id=2,$business_id='',$title,$ext='')
{
	$where = "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
	if(!empty($city_id)) $where .= " AND `city_id` = ".$city_id ;
	if(!empty($business_id))  $where .= " AND `business_id` = ".$business_id;
	if(!empty($title))  $where .= " and g.`goods_name` like '%".$title."%'";
	$where.=" and cat_id=".$cat_id;
	$orderby=' order by g.shop_price  ';
	$sql = "SELECT g.`business_id`,g.`goods_thumb`,g.`original_img`,g.`goods_img`, g.`cat_id`,g.`goods_id`, g.`goods_name`,g.`shop_price`,g.ft FROM " . $GLOBALS['hhs']->table('goods'). " as g  ".$where.$ext.$orderby;
   // echo $sql;exit;
	return $GLOBALS['db']->getAll($sql);
}
//2015 7 3 cadd 接机区分豪华型
function get_city_name($city_id){
    $sql="SELECT region_name FROM ". $GLOBALS['hhs']->table("citys")." WHERE region_id=".$city_id;
    return $GLOBALS['db']->getOne($sql);
}
function get_citys_aboard($id){
	$sql="select region_name from ".$GLOBALS['hhs']->table('citys_aboard') ." where id=".$id;
	return $GLOBALS['db']->getOne($sql);
}
function get_aboards($ext=null){
	$sql="select * from ".$GLOBALS['hhs']->table('citys_aboard') ." where 1 ".$ext;
	$arr=$GLOBALS['db']->getAll($sql);
	$row=array();
	
	foreach ($arr as $key => $val) {
		$row[$val['id']] = $val['region_name'];
	}
	//2015 6 30cadd
// 	foreach($arr as $k=>$v){
// 		$row[]=array('id'=>$v[id],'region_name'=>$v[region_name],'type_id'=>$v[type_id],
// 				'lng'=>$v[lng],'lat'=>$v[lat]
// 		);
// 	}
	/* foreach($arr as $v){
		$row[$v['id']]=$v['region_name'];
		//$row['type'] = $v['type_id'];
	} */
	//2015 6 30c
	return $row;
}
//7 06 cxqadd微信获取机场

function get_aboard($ext=null){
	$sql="select * from ".$GLOBALS['hhs']->table('citys_aboard') ." where 1 ".$ext;
	$arr=$GLOBALS['db']->getAll($sql);
	$row=array();

		foreach($arr as $k=>$v){
			$row[]=array('id'=>$v[id],'region_name'=>$v[region_name],'type_id'=>$v[type_id],
					'lng'=>$v[lng],'lat'=>$v[lat]
			);
		}
	
	return $row;
}
function get_aboards_info($id){
	$sql="select * from ".$GLOBALS['hhs']->table('citys_aboard') ." where id= ".$id;
	$row=$GLOBALS['db']->getRow($sql);
	return $row;
}
function set_session_city(){
    if(empty($_SESSION['city_id'])){       
        $ipInfos = GetIpLookup(GetIp()); //baidu.com IP地址
        $sql="select region_id FROM " . $GLOBALS['hhs']->table('citys'). " where region_name='". $ipInfos['city']."'";
        $city_id=$GLOBALS['db']->getOne($sql);
        if(empty($city_id)){
            $_SESSION['city_id']=311;
        }else{
            $_SESSION['city_id']=$city_id;
        }
    }
}
function GetIp(){
    $realip = '';
    $unknown = 'unknown';
    if (isset($_SERVER)){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach($arr as $ip){
                $ip = trim($ip);
                if ($ip != 'unknown'){
                    $realip = $ip;
                    break;
                }
            }
        }else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)){
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)){
            $realip = $_SERVER['REMOTE_ADDR'];
        }else{
            $realip = $unknown;
        }
    }else{
        if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)){
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        }else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)){
            $realip = getenv("HTTP_CLIENT_IP");
        }else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)){
            $realip = getenv("REMOTE_ADDR");
        }else{
            $realip = $unknown;
        }
    }
    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
    return $realip;
}

function GetIpLookup($ip = ''){
    if(empty($ip)){
        $ip = GetIp();
    }
    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
    if(empty($res)){ return false; }
    $jsonMatches = array();
    preg_match('#\{.+?\}#', $res, $jsonMatches);
    if(!isset($jsonMatches[0])){ return false; }
    $json = json_decode($jsonMatches[0], true);
    if(isset($json['ret']) && $json['ret'] == 1){
        $json['ip'] = $ip;
        unset($json['ret']);
    }else{
        return false;
    }
    return $json;
}

 function get_store_info($store_id){
    $sql="select * from ".$GLOBALS['hhs']->table('stores')." where store_id=".$store_id;
    return  $GLOBALS['db']->getRow($sql); 
 }
 function order_fee_zi($goods_id,$start_at,$end_at,$type=1,$cid=null){
    $info=array();
    $start_time=strtotime($start_at);
    $end_time=strtotime($end_at);
    $times=$end_time-$start_time;
    $tmp=$times-floor($times/86400)*86400;
    if($tmp>=3600*4){
        $days=floor($times/86400)+1;
    }else{
    	//租期计算
        if($times<86400){
            $days=1;	//小于一天的按一天计算
        }else{    
            $days=floor($times/86400);	//计算租期天数
            $hours=floor($tmp/3600);	//计算超小时
        }      
    }
    //如果设置了超小时,计算超小时费用。
    if(isset($hours)){
    	$properties = get_goods_properties2($goods_id);
    	$hours_per=$properties['pro']['attr'][13]['value'];
        $m=$hours_per;	//超小时费用标准
    }
    
    $total_price=0;
    $cost=0;
    $tmp_time=strtotime($start_at);
    if($type==2){//渠道
    	for($i=0;$i<$days;$i++){
    		$tmp_at=date('Y-m-d',$tmp_time+$i*86400);
    		$price=get_today_price($goods_id, $tmp_at,2,$cid);
    		//echo $goods_id."==".$start_at."--".$price;exit();
    		$total_price+=$price;
    		$cost+=get_today_cost($goods_id,$tmp_at);
    	}
    	$total_price+=$hours*$m;
    	$age_price=floor($total_price/$days);
    }elseif($type==3){//店铺
    	for($i=0;$i<$days;$i++){
    		$tmp_at=date('Y-m-d',$tmp_time+$i*86400);
    		$price=get_today_price($goods_id, $tmp_at,3,$cid);
    		//echo $goods_id."==".$start_at."--".$price;exit();
    		$total_price+=$price;
    		$cost+=get_today_cost($goods_id,$tmp_at);
    	}
    	$total_price+=$hours*$m;
    	$age_price=floor($total_price/$days);
    }else{
    	for($i=0;$i<$days;$i++){
    		$tmp_at=date('Y-m-d',$tmp_time+$i*86400);
    		$total_price+=get_today_price($goods_id,$tmp_at);
    		$cost+=get_today_cost($goods_id,$tmp_at);
    	}
    	if(!empty($m)) $total_price+=$hours*$m;
    	$age_price=floor($total_price/$days);
    }
    
    $info['total_price']=intval($total_price); 
    $info['age_price']=intval($age_price); 
    $info['cost']=intval($cost);
    $info['days']=$days;
    @$info['hours']=$hours;
    @$info['per_hour']=$m;
    return $info;                      
 }
 /**
 * 获得指定的文章的详细信息
 *
 * @access  private
 * @param   integer     $article_id
 * @return  array
 */
function get_article_info($article_id)
{
    /* 获得文章的信息 */
    $sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank ".
            "FROM " .$GLOBALS['hhs']->table('article'). " AS a ".
            "LEFT JOIN " .$GLOBALS['hhs']->table('comment'). " AS r ON r.id_value = a.article_id AND comment_type = 1 ".
            "WHERE a.is_open = 1 AND a.article_id = '$article_id' GROUP BY a.article_id";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row !== false)
    {
        $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
        $row['add_time']     = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示

        /* 作者信息如果为空，则用网站名称替换 */
        if (empty($row['author']) || $row['author'] == '_SHOPHELP')
        {
            $row['author'] = $GLOBALS['_CFG']['shop_name'];
        }
    }

    return $row;
}
function car_fee($order_info,$price,$out_distance_price,$out_time_price){
	$goods_amount=0;
	//计算超公里金额
	if(in_array($order_info['business_id'],array(1,2,3,4,5,6,7))){
		$out_distance=$order_info['distance']-$order_info['base_distance'];
		$out_distance=$out_distance<=0?0:$out_distance;
		$out_distance_money=$out_distance*out_distance_price;
		$goods_amount+=$out_distance_money;
	}
	//超小时金额
	if(in_array($order_info['business_id'],array(1,2,3,4,5,7))){
		$out_time=(strtotime($order_info['end_at'])-strtotime($order_info['start_at']))/3600-$order_info['base_time'];
		$out_time=$out_time<=0?0:$out_time;
		$out_time_money=$out_time*$out_time_price;
		$goods_amount+=$out_time_money;
	}	
	
	//计算日租车辆超小时金额
	if(in_array($order_info['business_id'],array(6))){
		$base_time_num=intval((strtotime($order_info['end_at'])-strtotime($order_info['start_at']))/3600/$order_info['base_time']);		$base_time_num--;
		if($base_time_num>=0){
			$goods_amount+=$price*$base_time_num;			
			$out_time=(strtotime($order_info['end_at'])-strtotime($order_info['start_at']))/3600%$order_info['base_time'];			
			$out_time_money=$out_time*$out_time_price;
			$goods_amount+=$out_time_money;
		}
	}	$goods_amount+=$price;
	
	return array('goods_amount'=>$goods_amount,
			'out_time_money'=>$out_time_money,
			'out_distance_money'=>$out_distance_money);
}

//发送短信
function sms($mobile,$text,$order_sn=null,$action=null){
	$sql="select * from email_sms where code='sms'";
	$sms=$GLOBALS['db']->getRow($sql);
	$content=str_replace('{phone}',$mobile,$sms['content']);	
	$content=str_replace('{content}',$text,$content);
	
	
	$arr=explode('&',substr($content,strpos($content,'?')+1 ));
	$data=array();
	foreach($arr as $v){
		$t=explode('=',$v);
		$data[$t[0]]=trim($t[1]);
	}
	$temp=explode('?', $content);
	$url=$temp[0];
	
	$ch = curl_init();
	$timeout = 10;
	curl_setopt ($ch, CURLOPT_URL, trim($url));
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $data  );
	$file_contents = curl_exec($ch);
	
	curl_close($ch);
	$file  = ROOT_PATH .'log.txt';
	$content=$mobile.":".$file_contents."\t".date('Y-m-d H:i:s')."\t".$order_sn."\t".$action."\r\n";//.$content."\r\n"
	$f=file_put_contents($file, $content,FILE_APPEND);
	return $file_contents;
}
function get_sms_info($id=null,$code=null){
	if(!empty($id)){
		$sql="select * from email_sms where id=".$id;
		$row=$GLOBALS['db']->getRow($sql);
	}elseif(!empty($code)){
		$sql="select * from email_sms where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}
	return $row;
}function get_user($user_id){
	$sql="select * from ".$GLOBALS['hhs']->table('users')." where user_id=".$user_id;
	$row=$GLOBALS['db']->getRow($sql);

	return $row;
}
function get_order_sms_content($order_info,$id=null,$code=null){
	/*
	 array('order_sn'=>$order_sn,'business_name'=>$business_name,
	 		'city_name'=>$city_name,'store_name'=>$store_name,
	 		'store_name2'=>$store_name2,'start_at'=>$start_at,
	 		'end_at'=>$end_at,'aboard'=>$aboard,
	 		'aboard2'=>$aboard2,'ft_num'=>$ft_num,'goods_name'=>$goods_name,
	 		'goods_name_tag'=>$car_card,'driver'=>$driver,'driver_phone'=>$driver_phone,
	 		'user_phone'=>$user_phone);*/
	if(!empty($id)){
		$sql="select * from email_sms where id=".$id;
		$row=$GLOBALS['db']->getRow($sql);
	}elseif(!empty($code)){
		$sql="select * from email_sms where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}	
	if(empty($order_info['business_name'])&&!empty($order_info['business_id'])){
		$business=get_business_info($order_info['business_id']);
		$order_info['business_name']=$business['title'];
	}
	if(empty($order_info['city_name'])&&!empty($order_info['city_id'])){
		$order_info['city_name']=get_city_name($order_info['city_id']);;
	}
	if(empty($order_info['store_name'])&&!empty($order_info['store_id'])){
	 	$order_info['store_name']=get_stores_name($order_info['store_id']);
	}
	if(empty($order_info['addr'])&&!empty($order_info['store_id'])){
		$order_info['addr']=get_stores_addr($order_info['store_id']);
	}
	if(empty($order_info['tel'])&&!empty($order_info['store_id'])){
		$order_info['tel']=get_stores_tel($order_info['store_id']);
	}
	if(empty($order_info['store_name2'])&&!empty($order_info['store_id2'])){
		$order_info['store_name2']=get_stores_name($order_info['store_id2']);
	}
	if(empty($order_info['goods_name'])&&!empty($order_info['order_id'])){
		$arr=get_order_goods_by_order_id($order_info['order_id']);
		$order_info['goods_name']=$arr['goods_name'];
	}
	if(empty($order_info['car_card'])&&!empty($order_info['car_id'])){
		$arr=get_car_info($order_info['car_id']);
		$order_info['car_card']=$arr['card'];
	}
	if(!empty($order_info['driver_id'])){
		$arr=get_driver_info($order_info['driver_id']);
		$order_info['driver']=$arr['truename'];
		$order_info['driver_phone']=$arr['phone'];
	}
	if(!empty($order_info['user_id'])){
		$arr=get_user($order_info['user_id']);
		if(empty($order_info['user_phone'])){
			$order_info['user_phone']=$arr['mobile_phone'];
		}
		if(empty($order_info['use_name'])){
			$order_info['use_name']=$arr['truename']?$arr['truename']:$arr['user_name'];
		}
	}
	$row['content']=str_replace("{\$order_sn}",$order_info['order_sn'],$row['content']);
	$row['content']=str_replace("{\$business_name}",$order_info['business_name'],$row['content']);
	$row['content']=str_replace("{\$city_name}",$order_info['city_name'],$row['content']);
	$row['content']=str_replace("{\$store_name}",$order_info['city_name'].$order_info['store_name'],$row['content']);
	$row['content']=str_replace("{\$store_name2}",$order_info['city_name'].$order_info['store_name2'],$row['content']);
	$row['content']=str_replace("{\$start_at}",$order_info['start_at'],$row['content']);
	$row['content']=str_replace("{\$end_at}",$order_info['end_at'],$row['content']);
	$row['content']=str_replace("{\$aboard}",$order_info['aboard'],$row['content']);
	$row['content']=str_replace("{\$aboard2}",$order_info['aboard2'],$row['content']);
	$row['content']=str_replace("{\$ft_num}",$order_info['ft_num'],$row['content']);
	$row['content']=str_replace("{\$train_num}",$order_info['train_num'],$row['content']);
	$row['content']=str_replace("{\$goods_name}",$order_info['goods_name'],$row['content']);
	//$row['content']=str_replace("{\$service_phone}",$GLOBALS['_CFG']['service_phone'],$row['content']);
	$row['content']=str_replace("{\$addr}",$order_info['addr'],$row['content']);
	$row['content']=str_replace("{\$tel}",$order_info['tel'],$row['content']);
	//2015 6 15 cadd
	$row['content']=str_replace("{\$rotue}",$order_info['rotue'],$row['content']);
	$row['content']=str_replace("{\$tc}",$order_info['tc'],$row['content']);
	$row['content']=str_replace("{\$cd}",$order_info['cd'],$row['content']);
	$row['content']=str_replace("{\$sx}",$order_info['sx'],$row['content']);
	$row['content']=str_replace("{\$jq}",$order_info['jq'],$row['content']);
	//2015 6 15 c end
	$row['content']=str_replace("{\$goods_name_tag}",$order_info['car_card'],$row['content']);
	$row['content']=str_replace("{\$driver}",$order_info['driver'],$row['content']);
	$row['content']=str_replace("{\$driver_phone}",$order_info['driver_phone'],$row['content']);
	$row['content']=str_replace("{\$user_phone}",$order_info['user_phone'],$row['content']);
	@$row['content']=str_replace("{\$use_phone}",$order_info['user_phone'],$row['content']);
	@$row['content']=str_replace("{\$use_name}",$order_info['user_name'],$row['content']);
	@$row['content']=str_replace("{\$simple_name}",$order_info['simple_name'],$row['content']);
	@$row['content']=str_replace("{\$service_phone}",$order_info['service_phone'],$row['content']);
	return $row['content'];
}
//2015 7  7 cxqadd获取新版邮件的变量
function get_order_sms($order_info,$id=null,$code=null){
	/*
	 array('order_sn'=>$order_sn,'business_name'=>$business_name,
	 		'city_name'=>$city_name,'store_name'=>$store_name,
	 		'store_name2'=>$store_name2,'start_at'=>$start_at,
	 		'end_at'=>$end_at,'aboard'=>$aboard,
	 		'aboard2'=>$aboard2,'ft_num'=>$ft_num,'goods_name'=>$goods_name,
	 		'goods_name_tag'=>$car_card,'driver'=>$driver,'driver_phone'=>$driver_phone,
	 		'user_phone'=>$user_phone);*/
	//var_dump($order_info);
	if(!empty($id)){
		$sql="select * from email_sms where id=".$id;
		$row=$GLOBALS['db']->getRow($sql);
	}elseif(!empty($code)){
		$sql="select * from email_sms where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}
	//var_dump($row);
	if(empty($order_info['business_name'])&&!empty($order_info['business_id'])){
		$business=get_business_info($order_info['business_id']);
		$order_info['business_name']=$business['title'];
	}
	if(empty($order_info['city_name'])&&!empty($order_info['city_id'])){
		$order_info['city_name']=get_city_name($order_info['city_id']);;
	}
	if(empty($order_info['store_name'])&&!empty($order_info['store_id'])){
		$order_info['store_name']=get_stores_name($order_info['store_id']);
	}

	if(empty($order_info['store_name2'])&&!empty($order_info['store_id2'])){
		$order_info['store_name2']=get_stores_name($order_info['store_id2']);
	}
	if(empty($order_info['goods_name'])&&!empty($order_info['order_id'])){
		$arr=get_order_goods_by_order_id($order_info['order_id']);
		$order_info['goods_name']=$arr['goods_name'];
	}
	if(empty($order_info['car_card'])&&!empty($order_info['car_id'])){
		$arr=get_car_info($order_info['car_id']);
		$order_info['car_card']=$arr['card'];
	}
	if(!empty($order_info['driver_id'])){
		$arr=get_driver_info($order_info['driver_id']);
		$order_info['driver']=$arr['truename'];
		$order_info['driver_phone']=$arr['phone'];
	}
	if(!empty($order_info['user_id'])){
		$arr=get_user($order_info['user_id']);
		if(empty($order_info['user_phone'])){
			$order_info['user_phone']=$arr['mobile_phone'];
		}
		if(empty($order_info['use_name'])){
			$order_info['use_name']=$arr['truename']?$arr['truename']:$arr['user_name'];
		}
	}

	 $order_info['service_phone']=$GLOBALS['_CFG']['service_phone'];
	/*  var_dump($order_info);
	 exit; */
	return $order_info;
}
//2015 7  7 cxqend
function get_car_info($car_id){
	$sql="SELECT * FROM ". $GLOBALS['hhs']->table("supplier_cars")." WHERE id=".$car_id;
	return $GLOBALS['db']->getRow($sql);
}
function get_driver_info($driver_id){
	$sql="SELECT * FROM ". $GLOBALS['hhs']->table("drivers")." WHERE driver_id=".$driver_id;
	return $GLOBALS['db']->getRow($sql);
}
function get_order_goods_name($order_id){
	return $GLOBALS['db']->getOne("SELECT `goods_name` FROM " . $GLOBALS['hhs']->table('order_goods') . " where `order_id` = '".$order_id."' limit 1");
}

function get_sms_code($business_id){
	if($business_id == 1){
		$code='come';
		$code2="driver_come";
		$code3="driver_des_user";
	}
	if($business_id==2){
		$code="to";
		$code2="driver_to";
		$code3="driver_des_user";
	}
	if($business_id==3){
		$code="come2";
		$code2="driver_come2";
		$code3="driver_des_user";
	}
	if($business_id==4){
		$code="to2";
		$code2="driver_to2";
		$code3="driver_des_user";
	}
	if($business_id==5){
		$code="rent_day_half";
		$code2="driver_rent_day_half";
		$code3="driver_des_user";
	}
	if($business_id==6||$business_id==7){
		$code="rent_day";
		$code2="driver_rent_day";
		$code3="driver_des_user";
	}
	if($business_id==8 || $business_id==19){
		$code="self_driving";
		$code3="driver_self_user";
	}
	if($business_id==14){
		$code="travel";
		$code3="driver_des_user";
	}
	if($business_id==15){
		$code="wedding";
		$code3="driver_des_user";
	}
	return array('code'=>$code,'code2'=>$code2,'code3'=>$code3);//code:预定发短信给客户，code2:派车派司机发短信给司机 code3:派车派司机发短信给客户
}
//add by wangj 20150703 start 代驾
function get_region_internation_dj($type=0,$parent=0){
	$sql="select * from hhs_region_internation  where parent_id=".$parent." and code = 'US' or code = 'DE' and region_type= ".$type;
	$arr=$GLOBALS['db']->getAll($sql);
	return $arr;
}
//add by wangj 20150703 end
function get_region_internation($type=0,$parent=0){
	$sql="select * from hhs_region_internation  where parent_id=".$parent." and region_type= ".$type;
	$arr=$GLOBALS['db']->getAll($sql);
	return $arr;
}

function get_region_internation_info($region_id,$code=''){
	if($region_id>0){
		$sql="select * from hhs_region_internation  where region_id=".$region_id;
		$row=$GLOBALS['db']->getRow($sql);
	}elseif($code!=''){
		$sql="select * from hhs_region_internation  where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}
	return $row;
}


//vipe租365
function get_region_name($region_id){
	$sql="select region_name from hhs_region_internation  where region_id=".$region_id;
	$region_name=$GLOBALS['db']->getOne($sql);
	return $region_name;
}
// added by wyl 2015/04/10 start
/**
 * 车辆状态
 */
function get_car_statuses() {
	return array (
			'KX'  => '空闲',
			'QDZ' => '抢单中',
			'DJZ' => '代驾中',
			'YYY' => '已预约',
			'GZZ' => '工作中',
			'JSZ' => '结算中',
			'XX'  => '休息'
	);
}

/**
 * 司机状态
 */
function get_driver_statuses() {
	return array (
			'DZ'  => '待租',
			'QDZ' => '抢单中',
			'YYY' => '已预约',
			'GZZ' => '工作中 ',
			'JSZ' => '结算中',
			'TS'  => '停驶',
			'BYZ' => '保养中',
			'WXZ' => '维修中',
			'NJZ' => '年检中'
	);
}
//忘记密码短信内容
function get_sms_content($order_info,$code=null){
	if(!empty($code)){
		$sql="select * from email_sms where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}
	$row['content']=str_replace("\$code",$order_info,$row['content']);
	return $row['content'];
}
//客户回复短信下单提醒内容
function get_cus_callback_sms($phone,$code=null){
	if(!empty($code)){
		$sql="select * from email_sms where code='".$code."'";
		$row=$GLOBALS['db']->getRow($sql);
	}
	$row['content']=str_replace("{\$phone}",$phone,$row['content']);
	return $row['content'];
}

//订单标注为已读
function check_read_order($order_id){
	$orders['read_status'] = 1;
	$orders['read_time'] = local_date('Y-m-d H:i:s',gmtime());
	$r=$GLOBALS['db']->autoExecute($GLOBALS['hhs']->table('order_info'), $orders, 'UPDATE','order_id='.$order_id);
	return $r;
}
//建周的短信接口的发送短信
function send_notice($phone,$content,$order_sn=null,$action=null,$type=null){
 	if($type){
 	    $content = $content."【e租365】";
 	}else{
 	    $content = $content."【VIP服务】";
 	}
 	$re=substr($phone,0,3);
 	if($re != '199'){
    // 	$content = $content."【建周科技】";
    	$ch = curl_init();
    	$post_data = array(
     			"account" => "sdk_rongyikeji",
     			"password" => "Rytech_123.com",
    // 			"account" => "jzyy902",
    // 			"password" => "135790",
    			"destmobile" => $phone,
    			// 电信"destmobile" => "18092058670",
    			"msgText" => $content,
    			"sendDateTime" => ""
    	);
    	curl_setopt($ch, CURLOPT_HEADER, false);
    	curl_setopt($ch, CURLOPT_POST, true);
    // 	curl_setopt($ch,CURLOPT_BINARYTRANSFER,1);
    // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	$post_data = http_build_query($post_data);
    	curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
    	$result=curl_setopt($ch, CURLOPT_URL, 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendBatchMessage');
    	$urs=curl_exec($ch);
    	if($urs>0){
    		$file  = ROOT_PATH .'log.txt';
    		$content=$phone.":短信发送成功\t".date('Y-m-d H:i:s')."\t".$order_sn."\t".$action."\r\n";//.$content."\r\n"
    		$f=file_put_contents($file, $content,FILE_APPEND);
    	}else{
    		//转化错误内容
    		switch ($urs){
    			case -1;
    			$urs = '余额不足';
    			break;
    			case -2;
    			$urs = '帐号或密码错误';
    			break;
    			case -3;
    			$urs = '连接服务商失败';
    			break;
    			case -4;
    			$urs = '超时';
    			break;
    			case -5;
    			$urs = '其他错误，一般为网络问题，IP受限等';
    			break;
    			case -6;
    			$urs = '短信内容为空';
    			break;
    			case -7;
    			$urs = '目标号码为空';
    			break;
    			case -8;
    			$urs = '用户通道设置不对，需要设置三个通道';
    			break;
    			case -9;
    			$urs = '捕获未知异常';
    			break;
    			case -10;
    			$urs = '超过最大定时时间限制';
    			break;
    			case -11;
    			$urs = '目标号码在黑名单里';
    			break;
    			case -13;
    			$urs = '没有权限使用该网关';
    			break;
    			case -14;
    			$urs = '找不到对应的Channel ID';
    			break;
    			case -17;
    			$urs = '没有提交权限，客户端帐号无法使用接口提交';
    			break;
    			case -18;
    			$urs = '提交参数名称不正确或确少参数';
    			break;
    			case -19;
    			$urs = '必须为POST提交';
    			break;
    			case -20;
    			$urs = '超速提交(一般为每秒一次提交)';
    			break;
    			case -21;
    			$urs = '扩展参数不正确';
    			break;
    			case -22;
    			$urs = 'Ip 被封停';
    			break;
    		}
    		$file  = ROOT_PATH .'log.txt';
    		$content=$phone.":短信发送失败！\t".$urs."\t".date('Y-m-d H:i:s')."\t".$order_sn."\t".$action."\r\n";//.$content."\r\n"
    		$f=file_put_contents($file, $content,FILE_APPEND);
    	}
 	}
	return $urs;
}



//---------------------------------------
//平台车型报价
function get_ezu365_car($city_id,$ft_id,$business_id){
	
	if($city_id==$_SESSION['vip']['city_id']){
	//本地城市，默认为VIP所属租车公司
		if($business_id==1 || $business_id==2 || $business_id==3 || $business_id==4 ){
			//机场、火车站
			if($ft_id){
				$sql_car="SELECT distinct c.goods_id,c.goods_name,c.cost,c.original_img FROM   ".$GLOBALS['hhs']->table('suppliers')." AS d 
								INNER JOIN ".$GLOBALS['hhs']->table('supplier_goods')." AS b ON d.suppliers_id=b.suppliers_id
								INNER JOIN ".$GLOBALS['hhs']->table('goods')."  AS c ON c.goods_id=b.goods_id
								INNER JOIN ".$GLOBALS['hhs']->table('goods_mine')."  AS g ON g.goods_id=c.goods_id
								WHERE c.is_delete = 0  and g.cost!='0.00'  AND c.city_id=".$city_id.
										" AND c.business_id=".$business_id.									
										" AND c.ft=".$ft_id.
										" AND g.suppliers_id=d.suppliers_id".
										" AND d.suppliers_id=".$_SESSION['vip']['suppliers_id'].
										" AND b.business_id=".$business_id." order by g.cost asc limit 0,4";
			$car = $GLOBALS['db']->getAll($sql_car);
			}
		}elseif($business_id==6 || $business_id==7){
			//日租、时租
				$sql_car="SELECT distinct c.goods_id,c.goods_name,c.cost,c.original_img FROM   ".$GLOBALS['hhs']->table('suppliers')." AS d 
								INNER JOIN ".$GLOBALS['hhs']->table('supplier_goods')." AS b ON d.suppliers_id=b.suppliers_id
								INNER JOIN ".$GLOBALS['hhs']->table('goods')."  AS c ON c.goods_id=b.goods_id
								INNER JOIN ".$GLOBALS['hhs']->table('goods_mine')."  AS g ON g.goods_id=c.goods_id
								WHERE c.is_delete = 0  and g.cost!='0.00'  AND c.city_id=".$city_id.
										" AND c.business_id=".$business_id.	
										" AND g.suppliers_id=d.suppliers_id".
										" AND d.suppliers_id=".$_SESSION['vip']['suppliers_id'].
										" AND b.business_id=".$business_id." order by g.cost asc limit 0,4";
				$car = $GLOBALS['db']->getAll($sql_car);
				
		}
	}else{
	//异地（平台）
		if($business_id<6){
			if($ft_id){
				$sql="SELECT distinct c.goods_id,c.goods_name,c.cost,c.original_img FROM  ".$GLOBALS['hhs']->table('goods')."  AS c	
				INNER JOIN ".$GLOBALS['hhs']->table('goods_mine')."  AS g ON g.goods_id=c.goods_id									
				WHERE c.city_id=".$city_id." AND c.ft=".$ft_id." and g.cost!='0.00' AND c.business_id=".$business_id." and c.is_delete = 0 order by g.cost asc limit 0,4";
			$car = $GLOBALS['db']->getAll($sql);
			}
		}else{
			$sql="SELECT distinct c.goods_id,c.goods_name,c.cost,c.original_img FROM  ".$GLOBALS['hhs']->table('goods')."  AS c	
				INNER JOIN ".$GLOBALS['hhs']->table('goods_mine')."  AS g ON g.goods_id=c.goods_id									
				WHERE c.city_id=".$city_id." AND c.business_id=".$business_id." and g.cost!='0.00' and c.is_delete = 0 order by g.cost asc limit 0,4";	
			$car = $GLOBALS['db']->getAll($sql);
		}
	}
	
	//echo $sql;
	return $car;
}
//平台车型报价的机场火车站信息
function get_ezu365_cityaboard($type_id,$region_id,$business_id){
	
	$sql_f="SELECT distinct a.id,a.lng,a.lat,a.type_id,a.region_name
        	FROM ".$GLOBALS['hhs']->table("citys_aboard").
		        	" as a,".$GLOBALS['hhs']->table("goods").
		        	" as c   WHERE a.id=c.ft and c.is_delete = 0 and a.type_id=".$type_id.
		        	" AND a.region_id =".$region_id.
		        	" and c.business_id=".$business_id;
 	//echo $sql_f; 

	$c_aboard=$GLOBALS['db']->getAll($sql_f);
	
	return $c_aboard;
}
function get_cityaboard_two($region_id,$business_id){
	if($business_id==1 || $business_id==2){
		$type_id=1;
	}else{
		$type_id=2;
	}
	
	//绑定有供应商，查询机场或火车站数据
	$sql_f="SELECT distinct a.id,a.lng,a.lat,a.type_id,a.region_name
        	FROM ".$GLOBALS['hhs']->table("citys_aboard").
        	" as a,".$GLOBALS['hhs']->table("supplier_goods").
        	" as b,".$GLOBALS['hhs']->table("goods").
        	" as c ,".$GLOBALS['hhs']->table("channel_suppliers").
        	" as d WHERE a.id=c.ft and b.goods_id=c.goods_id and a.region_id=b.city_id and d.suppliers_id=b.suppliers_id  ".
        	"and a.type_id=".$type_id.
        	" AND a.region_id =".$region_id.
        	" and c.business_id=".$business_id.
        	" and d.channel_id=".$_SESSION['vip']['channel_id'];
					
	$c_aboard=$GLOBALS['db']->getAll($sql_f);
	
	
	if(empty($c_aboard)){
		//没有绑定供应商，查询平台机场或火车站数据

		if($region_id==$_SESSION['vip']['city_id']){
				//本地城市，默认为VIP所属租车公司
				$sql_f="SELECT distinct a.id,a.lng,a.lat,a.type_id,a.region_name
	        	FROM ".$GLOBALS['hhs']->table("citys_aboard").
	        	" as a,".$GLOBALS['hhs']->table("supplier_goods").
	        	" as b,".$GLOBALS['hhs']->table("goods").
	        	" as c  WHERE a.id=c.ft and b.goods_id=c.goods_id and a.region_id=b.city_id and b.suppliers_id=".$_SESSION['vip']['suppliers_id'].
	        	" and a.type_id=".$type_id.
	        	" AND a.region_id =".$region_id.
	        	" and c.business_id=".$business_id;
	        	
				$c_aboard=$GLOBALS['db']->getAll($sql_f);
			
		}else{
			//异地（平台）
			$c_aboard=get_ezu365_cityaboard($type_id,$region_id,$business_id);
		}
		
		
	}
	
	return $c_aboard;
}

//---------------------------------------

//生成邀请码
function create_verify_codes(){
    $sql="SELECT verify_code FROM ".$GLOBALS['hhs']->table('verify_code');
    $result=$GLOBALS['db']->getAll($sql);
    if($result){
        foreach($result AS $v){
            $str=str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
            $start=rand(0,32);
            $str1=substr($str,$start,4);
            if($str1 != $v['verify_code']){
                $str2=$str1;
            }else{
                create_verify_codes();
            }
        }
    }else{
        $str=str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $start=rand(0,32);
        $str2=substr($str,$start,4);
    }
    return $str2;
}



?>
