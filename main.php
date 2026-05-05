<?php
/**
 * Plugin Name: WordPress Onsite Coupon Tracker
 * Description: ระบบสร้างและติดตามการใช้งานคูปอง Onsite
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/wordpress-onsite-coupon-tracker
 */

function onsite_coupon_tracker_menu()
{
    add_menu_page(
        'ระบบสร้างและติดตามการใช้งานคูปอง Onsite',    // Page title
        'คูปอง E-Voucher',                          // Menu title
        'manage_options',                        // Capability required
        'onsite_coupon_tracker',                             // Menu slug
        'onsite_coupon_tracker_page',            // Callback function to display page content
        'dashicons-buddicons-groups',                 // Icon URL or Dashicon class
        80                                       // Position in the menu (optional)
    );
}

add_action('admin_menu', 'onsite_coupon_tracker_menu');

register_activation_hook( __FILE__, 'onsite_couple_plugin_install' );

function onsite_couple_plugin_install() {
    global $wpdb;
    $charset_collate = "DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci";
    
    $coupon_table = $wpdb->prefix . 'onsite_coupon';
    $campaign_table = $wpdb->prefix . 'onsite_campaign';

    $create_coupon_query = "CREATE TABLE IF NOT EXISTS $coupon_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        code VARCHAR(6) NOT NULL,
        code VARCHAR(100) NOT NULL,
        discount VARCHAR(20) NOT NULL,
        status int(1) NOT NULL DEFAULT 0,
        user_id int(11),
        campaign_id int(11) NOT NULL,
        discount_amount int(11) NOT NULL DEFAULT 0,
        minspend int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        billing_id VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $create_campaign_query = "CREATE TABLE IF NOT EXISTS $campaign_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_coupon_query );
    dbDelta( $create_campaign_query );
}

function onsite_coupon_tracker_page() {
    global $wpdb;

    if(isset($_POST['addCampaign'])) {
        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        $campaign_start_date = sanitize_text_field($_POST['campaign_start_date']) ." ". sanitize_text_field($_POST['campaign_start_time']);
        $campaign_end_date = sanitize_text_field($_POST['campaign_end_date']) ." ". sanitize_text_field($_POST['campaign_end_time']);
        $created_at = date("Y-m-d H:i:s");

        $insert_query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}onsite_campaign(name, start_date, end_date, created_at) VALUES(%s, %s, %s, %s)", $campaign_name, $campaign_start_date, $campaign_end_date, $created_at);
        $wpdb->query($insert_query);

        wp_redirect(admin_url('admin.php?page=onsite_coupon_tracker'));
        exit;
    }

    if(isset($_POST['editCampaign'])) {
        $campaign_id = sanitize_text_field($_POST['campaign_id']);
        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        $campaign_start_date = sanitize_text_field($_POST['campaign_start_date']) ." ". sanitize_text_field($_POST['campaign_start_time']);
        $campaign_end_date = sanitize_text_field($_POST['campaign_end_date']) ." ". sanitize_text_field($_POST['campaign_end_time']);

        $update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}onsite_campaign SET name = %s, start_date = %s, end_date = %s WHERE id = %d", $campaign_name, $campaign_start_date, $campaign_end_date, $campaign_id);
        $wpdb->query($update_query);

        wp_redirect(admin_url('admin.php?page=onsite_coupon_tracker&campaign='.$campaign_id));
        exit;
    }

    if(isset($_POST['addCoupon'])) {
        $coupon_amount = (int) sanitize_text_field($_POST['coupon_amount']);
        $coupon_discount = sanitize_text_field($_POST['coupon_discount']);
        $coupon_condition = sanitize_text_field($_POST['coupon_condition']);
        $discount_amount = sanitize_text_field($_POST['discount_amount']);
        $minspend = sanitize_text_field($_POST['minspend']);
        $campaign_id = sanitize_text_field($_POST['campaign_id']);
        $created_at = date("Y-m-d H:i:s");
        
        for($i = 0; $i < $coupon_amount; $i++) {            
            $coupon_code = wp_generate_password( 5, false );
            $insert_query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}onsite_coupon(code, coupon_condition, discount, campaign_id, discount_amount, minspend, created_at) VALUES(%s, %s, %s, %d, %d, %d, %s)", 
             $coupon_code, $coupon_condition, $coupon_discount, $campaign_id, $discount_amount, $minspend, $created_at);
            $wpdb->query($insert_query);
        }

        wp_redirect(admin_url('admin.php?page=onsite_coupon_tracker&campaign='.$campaign_id));
        exit;
    }

    if(isset($_POST['editCoupon'])) {
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $coupon_discount = sanitize_text_field($_POST['coupon_discount']);
        $coupon_condition = sanitize_text_field($_POST['coupon_condition']);
        $discount_amount = sanitize_text_field($_POST['discount_amount']);
        $minspend = sanitize_text_field($_POST['minspend']);
        $campaign_id = sanitize_text_field($_POST['campaign_id']);
        $coupon_status = (int) sanitize_text_field($_POST['coupon_status']);

        $update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}onsite_coupon SET code = %s, coupon_condition = %s, discount = %s, campaign_id = %d, status = %d, discount_amount = %d, minspend = %d WHERE id = %d",
         $coupon_code, $coupon_condition, $coupon_discount, $campaign_id, $coupon_status, $discount_amount, $minspend, $coupon_id);
        $wpdb->query($update_query);

        wp_redirect(admin_url('admin.php?page=onsite_coupon_tracker&coupon='.$coupon_id));
        exit;
    }
    ?>
    <div class="wrapper" style="display: flex;">
        <style>
            .oct_campaigns_list a {
                padding: 10px 20px;
                font-size: 14px;
                background: #f8f8f8;
                color: #111;
                transition: .2s ease-in-out;
                width: 85%;
                display: inline-block;
                text-decoration: none;
            }
        </style>
        <div class="oct_campaigns_list" style="margin: 0 20px 0 0; background: #fff; padding: 20px; border-radius: 10px; width: 300px;">
            <h2 style="margin-top: 0;">📚 รายการแคมเปญ <button class="button button-small" onclick="window.location.href='admin.php?page=onsite_coupon_tracker'" style="margin-left: 10px;">สร้างแคมเปญใหม่</button></h2>
            
            <?php
            $campaigns = $wpdb->get_results(
                "SELECT id,name FROM {$wpdb->prefix}onsite_campaign ORDER BY id DESC"
            );
            foreach($campaigns as $campaign) {
            ?>
                <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=".$campaign->id)?>"><?=$campaign->name?></a>
            <?php
            }
            ?>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 10px; width: 70%;">
            <?php
            if(isset($_GET['campaign']) && !isset($_GET['searchCoupon'])) {
                $campaign = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_campaign WHERE id = %d",
                    $_GET['campaign']
                ));
            ?>
            <h1 style="margin-top: 0;">✏️ แก้ไขแคมเปญ "<?=$campaign->name;?>"</h1>
            <br>
            <form action="admin.php?page=onsite_coupon_tracker" method="POST">
                <input type="hidden" name="campaign_id" value="<?=$campaign->id;?>">
                ชื่อแคมเปญ: <input type="text" name="campaign_name" value="<?=$campaign->name;?>" style="width: 500px;"><br><br>
                วันที่เริ่มแจกคูปอง: <input type="date" name="campaign_start_date" id="" value="<?=explode(" ", $campaign->start_date)[0];?>">
                เวลา: <input type="time" name="campaign_start_time" value="<?=explode(" ", $campaign->start_date)[1];?>">
                <br><br>
                วันที่หมดเวลาแจกคูปอง: <input type="date" name="campaign_end_date" value="<?=explode(" ", $campaign->end_date)[0];?>">
                เวลา: <input type="time" name="campaign_end_time" value="<?=explode(" ", $campaign->end_date)[1];?>">
                <br><br>
                <input type="submit" value="บันทึกการเปลี่ยนแปลง" name="editCampaign" class="button button-primary">
            </form>
            
            <h2>🏷️ คูปองของแคมเปญ</h2>
            <a href="admin.php?page=onsite_coupon_tracker&campaign=<?=$campaign->id;?>&searchCoupon=all" class="button">🏷️ คูปองทั้งหมด</a>
            <a href="admin.php?page=onsite_coupon_tracker&newCoupon" class="button">🎫 สร้างคูปองใหม่</a>
            <?php
            } elseif(isset($_GET['newCoupon'])) {
            ?>
            <form action="admin.php?page=onsite_coupon_tracker" method="post">
                <h2 style="margin-top: 0;">➕ เพิ่มคูปองใหม่</h2>
                <br>
                ลดจำนวน: <input type="text" name="coupon_discount" id="" required><br><br>
                เงื่อนไข: <input type="text" name="coupon_condition" id="" style="width: 500px;" required><br><br>
                แคมเปญ: <select name="campaign_id" id="">
                    <?php
                    $campaigns = $wpdb->get_results(
                        "SELECT id,name FROM {$wpdb->prefix}onsite_campaign ORDER BY id DESC"
                        );
                        foreach($campaigns as $campaign) {
                            ?>
                    <option value="<?=$campaign->id;?>"><?=$campaign->name;?></option>
                    <?php 
                    }
                    ?>
                </select><br><br>
                ลดจำนวน: <input type="number" name="discount_amount" id="" required> บาท<br><br>
                ซื้อขั้นต่ำ: <input type="number" name="minspend" id="" required> บาท<br><br>
                สร้างคูปองจำนวน: <input type="number" name="coupon_amount" id="" value="1" required><br><br>
                <input type="submit" value="สร้างคูปอง" name="addCoupon" class="button">
            </form>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] == "all") {
                $campaign_id = $_GET['campaign'];
            ?>
            <h1 style="margin-top: 0;">🎫 คูปอง</h1>
            <p>ในหน้านี้คุณสามารถค้นหาคูปองที่มีอยู่ในแคมเปญได้ โดยการกรอกรหัสคูปองลงในช่องค้นหาและกด Enter</p>
            <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=".$campaign_id);?>">กลับไปที่แคมเปญ</a><br><br>
            <input type="text" style="width: 100%;" onchange="searchCoupon(this.value, '<?=$campaign_id?>')" placeholder="ค้นหาคูปองด้วยรหัส" value="<?=$_GET['searchCoupon'];?>">
            <script>
                function searchCoupon(query, campaign_id) {
                    window.location.href = `admin.php?page=onsite_coupon_tracker&searchCoupon=${query}&campaign=${campaign_id}`;
                }
            </script>
            <?php
            $stats = $wpdb->get_row("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as used,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN user_id IS NOT NULL AND user_id != 0 THEN 1 ELSE 0 END) as taken
                FROM {$wpdb->prefix}onsite_coupon
            ", OBJECT);

            $all_coupon          = $stats->total;
            $used_coupon         = $stats->used;
            $available_coupon    = $stats->available;
            $already_taken_coupon = $stats->taken;
            ?>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin: 20px 0;">
                <p style="margin: 0; font-size: 16px;">
                    📊 <strong>ภาพรวมแคมเปญ:</strong> <br>
                    ทั้งหมด: <strong><?= number_format($all_coupon); ?></strong> คูปอง | 
                    ใช้งานแล้ว: <span style="color: #28a745;"><?= number_format($used_coupon); ?></span> | 
                    เหลือพร้อมใช้: <span style="color: #007bff;"><?= number_format($available_coupon); ?></span> | 
                    ถูกเก็บแล้ว: <span style="color: red;"><?= number_format($already_taken_coupon); ?></span>
                </p>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>จำนวนคูปองในระบบ</th>
                        <th>ลดจำนวน</th>
                        <th>เงื่อนไข</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if($_GET['searchCoupon'] === "all") {
                            $coupons = $wpdb->get_results($wpdb->prepare(
                                "SELECT discount,coupon_condition,COUNT(*) as amount FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d GROUP BY discount, coupon_condition",
                                $_GET['campaign']
                            ));
                        }

                        foreach($coupons as $coupon) {
                    ?>
                    <tr>
                        <td><?=$coupon->amount;?> ใบ</td>
                        <td><?=$coupon->discount;?></td>
                        <td><?=$coupon->coupon_condition;?></td>
                        <td><button onclick="searchCouponByCondition(<?= $_GET['campaign'];?>, '<?=$coupon->coupon_condition;?>', '<?=$coupon->discount;?>')" class="button">ดูคูปองทั้งหมด</button></td>
                    </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>
            <script>
                function searchCouponByCondition(campaign_id, condition, discount) {
                    window.location.href=`admin.php?page=onsite_coupon_tracker&option=coupon-by-condition&condition=${condition}&discount=${discount}&campaign_id=${campaign_id}`
                }
            </script>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] != "all") {
                $campaign_id = $_GET['campaign'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND code LIKE %s",
                    $_GET['campaign'], "%".$_GET['searchCoupon']."%"
                ));
            ?>
            <h1 style="margin-top: 0;">🔍 ผลการค้นหา: <?=$_GET['searchCoupon'];?></h1>
            <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=$campaign_id&searchCoupon=all");?>">กลับไปที่แคมเปญ</a><br><br>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>ลดจำนวน</th>
                        <th>เงื่อนไข</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($coupons as $coupon) {
                    ?>
                    <tr>
                        <td><?=$coupon->id;?></td>
                        <td><a href="admin.php?page=onsite_coupon_tracker&option=edit-coupon&coupon=<?=$coupon->id;?>"><?=$coupon->code;?></a></td>
                        <td><?=$coupon->discount;?></td>
                        <td><?=$coupon->coupon_condition;?></td>
                        <td><?php if($coupon->user_id == null) { echo "<span style='color: green;'>ยังไม่ถูกเก็บ</span>"; } else { echo "<span style='color: red;'>ถูกเก็บแล้ว</span>"; } ?> | <?php if($coupon->status == 0) {echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; } else { echo "<span style='color: red;'>ใช้งานแล้ว</span>"; } ?></td>
                    </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "coupon-by-condition") {
                $campaign_id = $_GET['campaign_id'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND coupon_condition = %s AND discount = %s",
                    $campaign_id, $_GET['condition'], $_GET['discount']
                ));

            ?>
            <h1 style="margin-top: 0;">🔍 ผลการค้นหา</h1>
            <p>รหัสแคมเปญ: <?=$campaign_id;?> ลด <?=$_GET['discount']?> <?=$_GET['condition']?></p>
            <h3>พบจำนวนคูปองในระบบ <?=$wpdb->num_rows;?> ใบ</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>ลดจำนวน</th>
                        <th>เงื่อนไข</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($coupons as $coupon) {
                    ?>
                    <tr>
                        <td><?=$coupon->id;?></td>
                        <td><a href="admin.php?page=onsite_coupon_tracker&option=edit-coupon&coupon=<?=$coupon->id;?>"><?=$coupon->code;?></a></td>
                        <td><?=$coupon->discount;?></td>
                        <td><?=$coupon->coupon_condition;?></td>
                        <td><?php if($coupon->user_id == null) { echo "<span style='color: green;'>ยังไม่ถูกเก็บ</span>"; } else { echo "<span style='color: red;'>ถูกเก็บแล้ว</span>"; } ?> | <?php if($coupon->status == 0) {echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; } else { echo "<span style='color: red;'>ใช้งานแล้ว</span>"; } ?></td>
                    </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "edit-coupon") {
                $coupon = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE id = %d",
                    $_GET['coupon']
                ));
            ?>
            <form action="admin.php?page=onsite_coupon_tracker" method="post">
                <h2 style="margin-top: 0;">✏️ แก้ไขคูปอง "<?=$coupon->code;?>"</h2>
                <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=".$coupon->campaign_id."&searchCoupon=all");?>">กลับไปที่คูปองในแคมเปญ</a>
                <br>
                <br>
                <input type="hidden" name="coupon_id" value="<?=$coupon->id;?>">
                รหัสคูปอง: <input type="text" name="coupon_code" value="<?=$coupon->code;?>"><br><br>
                ลดจำนวน: <input type="text" name="coupon_discount" value="<?=$coupon->discount;?>"><br><br>
                เงื่อนไข: <input type="text" name="coupon_condition" value="<?=$coupon->coupon_condition;?>" style="width: 500px;"><br><br>
                แคมเปญ: <select name="campaign_id" id="">
                    <?php
                    $campaigns = $wpdb->get_results(
                        "SELECT id,name FROM {$wpdb->prefix}onsite_campaign ORDER BY id DESC"
                    );
                    foreach($campaigns as $campaign) {
                    ?>
                    <option value="<?=$campaign->id;?>" <?php selected($coupon->campaign_id, $campaign->id) ?>><?=$campaign->name;?></option>
                    <?php 
                    }
                    ?>
                </select><br><br>
                สถานะ: <select name="coupon_status" id="">
                    <option value="1" <?php selected($coupon->status, 1) ?>>ใช้งานแล้ว</option>
                    <option value="0" <?php selected($coupon->status, 0) ?>>ยังไม่ถูกใช้งาน</option>
                </select><br><br>
                เลขบิล: <input type="text" name="billing_id" value="<?=$coupon->billing_id;?>"><br><br>
                <input type="submit" value="บันทึกการเปลี่ยนแปลง" name="editCoupon" class="button">
            </form>
            <?php
            } else {
            ?>
            <h1 style="margin-top: 0;">➕ สร้างแคมเปญใหม่</h1>
            <br>
            <form action="admin.php?page=onsite_coupon_tracker" method="POST">
                ชื่อแคมเปญ: <input type="text" name="campaign_name" id="" required><br><br>
                วันที่เริ่มแจกคูปอง: <input type="date" name="campaign_start_date" id="" required>
                เวลา: <input type="time" name="campaign_start_time" id="" required>
                <br><br>
                วันที่หมดเวลาแจกคูปอง: <input type="date" name="campaign_end_date" id="" required>
                เวลา: <input type="time" name="campaign_end_time" id="" required>
                <br><br>
                <input type="submit" value="สร้างแคมเปญ" name="addCampaign" class="button button-primary">
            </form>
            <?php
            }
            ?>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'onsite_coupn_tracker_settings_init');

function onsite_coupn_tracker_settings_init()
{
    register_setting('onsite_coupn_tracker_settings_group', 'onsite_coupn_tracker_enable');
}

add_action('init', function() {
    add_rewrite_rule('^e-voucher/pick/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?pick_discount=$matches[1]&pick_condition=$matches[2]&pick_campaign_id=$matches[3]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'pick_discount';
    $vars[] = 'pick_condition';
    $vars[] = 'pick_campaign_id';
    return $vars;
});

add_action('template_redirect', function() {
    $discount = urldecode(get_query_var('pick_discount'));
    $condition = urldecode(get_query_var('pick_condition'));
    $campaign_id = get_query_var('pick_campaign_id');
    
    if ($discount && $condition && $campaign_id) {
        global $wpdb;
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/e-voucher/'))); 
            exit;
        }

        $user_id = get_current_user_id();
        $table_name = "{$wpdb->prefix}onsite_coupon";

        $already_got_coupon = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id));

        if($already_got_coupon > 0) {
            wp_redirect(home_url('/e-voucher/?status=you-already-picked'));
            exit;
        }

        // 1. ค้นหา ID คูปอง 1 ใบ ที่สเปกตรง และ user_id ยังเป็น NULL
        $coupon_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE discount = %s 
             AND coupon_condition = %s 
             AND campaign_id = %d 
             AND user_id IS NULL 
             LIMIT 1",
            $discount,
            $condition,
            $campaign_id,
        ));

        if ($coupon_id) {
            // 2. อัปเดตใบที่เจอให้เป็นของ User คนนี้
            // ใส่ user_id IS NULL ซ้ำในเงื่อนไขเพื่อกัน Race Condition (คนกดพร้อมกัน)
            $updated = $wpdb->update(
                $table_name,
                array('user_id' => $user_id),
                array('id' => $coupon_id, 'user_id' => null),
                array('%d'),
                array('%d', '%d') // id เป็น %d (int), user_id เป็น %d (null)
            );

            if ($updated) {
                wp_redirect(home_url('/e-voucher/?status=success'));
                exit;
            }
        } else {
            wp_redirect(home_url('/e-voucher/?status=cannot_find_coupon'));
            exit;
        }

        // ถ้าไม่เจอคูปองว่าง หรืออัปเดตไม่สำเร็จ
        wp_redirect(home_url('/e-voucher/?status=out_of_stock'));
        exit;
    }
});

add_shortcode('evoucher_page', function() {
    global $wpdb;

    if (!is_user_logged_in()) {
        return '
        <div style="text-align:center; padding: 50px; background:#fff; border-radius:10px;">
            <h2 style="color:#333;">🎫 รับคูปองสุดพิเศษ</h2>
            <p style="color:#666;">กรุณาเข้าสู่ระบบก่อน เพื่อรับสิทธิ์คูปองส่วนลด</p>
            <br>
            <a href="'. wp_login_url(get_permalink()) .'" class="button" style="background:#1D9DD8; color:#fff; padding:12px 30px; text-decoration:none; border-radius:5px;">เข้าสู่ระบบ</a>
        </div>';
    }

    $user_id = get_current_user_id();
    $already_got_coupon = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}onsite_coupon WHERE user_id = %d", $user_id));

    $current_time = current_time('mysql');
    $campaigns = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, start_date, end_date 
        FROM {$wpdb->prefix}onsite_campaign 
        WHERE start_date <= %s 
        AND end_date >= %s 
        ORDER BY id DESC",
        $current_time, 
        $current_time
    ));

    ob_start();
    ?>
    <style>
        .coupon-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .coupon {
            padding: 20px;
            background: #f8f8f8;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .coupon .holder {
            display: flex;
            gap: 15px;
        }
        .coupon span {
            font-size: 32px;
            font-weight: bold;
            color: #1D9DD8;
            display: flex;
            align-items: center;
            border-right: 1px solid #ccc;
            padding-right: 15px;
        }
        .coupon p {
            line-height: 1.4;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .coupon .btn-pick {
            width: 100%;
            background: #1D9DD8;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
        }
        .coupon .btn-pick:hover { background: #157fb1; }

        @media screen and (max-width: 1400px) { .coupon-container { grid-template-columns: 1fr 1fr 1fr; } }
        @media screen and (max-width: 1200px) { .coupon-container { grid-template-columns: 1fr 1fr; } }
        @media screen and (max-width: 768px) { .coupon-container { grid-template-columns: 1fr; } }

        /* ซ่อนส่วนที่ไม่จำเป็น */
        .elementor-menu-cart__container, .bwp-main .page-title { display: none !important; }
    </style>

    <div class="evoucher-main-wrapper">
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success') : ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ เก็บคูปองสำเร็จ! คุณสามารถดูคูปองได้ในเมนู "บัญชีของฉัน"
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'you-already-picked') : ?>
            <div style="background: #e69b9b; color: #811414; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #e24242;">
                ⛔ คุณได้เก็บคูปองไปแล้ว! คุณสามารถดูคูปองได้ในเมนู "บัญชีของฉัน"
            </div>
        <?php endif; ?>

        <?php foreach($campaigns as $campaign) : ?>
            <div style="padding: 20px; margin-top: 30px; border: 1px solid #ddd; border-radius: 20px;">
                <h3 style="margin-top:0;">🎫 <?=$campaign->name;?></h3>
                <div class="coupon-container">
                    <?php
                    $sql = $wpdb->prepare(
                        "SELECT discount,coupon_condition FROM {$wpdb->prefix}onsite_coupon WHERE user_id IS NULL 
                        GROUP BY discount, coupon_condition ORDER BY discount_amount ASC",
                        $current_time
                    );

                    $coupons = $wpdb->get_results($sql);
                    if ($coupons) :
                        foreach($coupons as $coupon) : ?>
                            <div class="coupon">
                                <div class="holder">
                                    <span><?=$coupon->discount?></span>
                                    <div style="flex:1;">
                                        <p>
                                            <strong>ลด <?=$coupon->discount?></strong><br>
                                            <small><?=$coupon->coupon_condition?></small>
                                        </p>
                                        <button class="btn-pick" <?php if($already_got_coupon > 0) { echo "disabled"; } ?> onclick="window.location.href='/e-voucher/pick/<?=$coupon->discount?>/<?=$coupon->coupon_condition?>/<?=$campaign->id;?>'">เก็บคูปองนี้</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    else :
                        echo '<p style="color:#999;">คูปองหมดแล้วจ้า...</p>';
                    endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    return ob_get_clean();
});

add_action('woocommerce_before_my_account', 'my_onsite_coupons_table');

function my_onsite_coupons_table() {
    global $wpdb;
    $coupon_table = $wpdb->prefix . 'onsite_coupon';
    $my_onsite_coupons = $wpdb->get_results($wpdb->prepare(
        "SELECT code, coupon_condition, discount FROM $coupon_table WHERE user_id = %d", get_current_user_id()
    ));
?>
<style>
    #couponBox {
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        justify-content: center;
        align-items: center;
        position: fixed;
        background: #242424;
        z-index: 9999;
        flex-direction: column;
    }
    
    .close-coupon-btn {
        padding: 10px 20px;
        background: #cc0000;
        color: #fff;
        border: none;
        cursor: pointer;
        border-radius: 5px;
    }
</style>

<h2>🎫 คูปองส่วนลดพิเศษสำหรับใช้งานหน้าร้าน</h2>
<?php
if(isset($_GET['status'])) {
    if($_GET['status'] == 'coupon_converted_to_website') {
    ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✅ เปลี่ยนคูปองเป็นคูปองส่วนลดสำหรับเว็บไซต์แล้ว !
        </div>
    <?php
    }

    if($_GET['status'] == 'coupon_converted_to_onsite') {
    ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✅ เปลี่ยนคูปองเป็นคูปองส่วนลดสำหรับหน้าร้านแล้ว !
        </div>
    <?php
    }
}
?>
<div style="overflow: auto;">
    <table class="wp-list-table widefat fixed striped" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>ส่วนลด</th>
                <th colspan="2">เงื่อนไขการใช้คูปอง</th>
            </tr>
        </thead>
        <tbody>
            <?php
                // แก้จุดที่ 1: ใช้ count() เช็ค array
                if(count($my_onsite_coupons) === 0) { 
                    echo "<tr><td colspan='3'>ไม่มีคูปองสำหรับหน้าร้าน...</td></tr>"; 
                } else {
                    foreach($my_onsite_coupons as $my_onsite_coupon) {
            ?>
            <tr>
                <td><strong><?=$my_onsite_coupon->discount;?></strong></td>
                <td><?=$my_onsite_coupon->coupon_condition;?></td>
                <td style="display: flex;">
                    <?php
                    $real_coupon_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1",
                        $my_onsite_coupon->code
                    ));
                    if(!$real_coupon_id) {
                    ?>
                    <button class="button button-small" style="
                    padding: 5px 20px;
                    font-size: 14px;
                    line-height: 20px;"
                    onclick="showCoupon('<?=$my_onsite_coupon->code;?>', '<?=$my_onsite_coupon->discount;?>', '<?=$my_onsite_coupon->coupon_condition;?>')">ใช้คูปองหน้าร้าน</button>
                    <button class="button button-small" style="
                    padding: 5px 20px;
                    font-size: 14px;
                    line-height: 20px;
                    margin: 0 0 0 10px;
                    "
                    onclick="changeToWooCommerceCoupon('<?=$my_onsite_coupon->code;?>')">เปลี่ยนเป็นคูปองในเว็บไซต์</button>
                    <?php
                    } else {
                    ?>
                    <code><?=$my_onsite_coupon->code;?></code>
                    <button class="button button-small" style="
                    padding: 5px 20px;
                    font-size: 14px;
                    line-height: 20px;
                    margin: 0 0 0 10px;
                    "
                    onclick="changeToOnsiteCoupon('<?=$my_onsite_coupon->code;?>')">เปลี่ยนเป็นคูปองหน้าร้าน</button>
                    <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
                    }
                }
            ?>
        </tbody>
    </table>
</div>

<!-- Popup สำหรับโชว์โค้ด -->
<div id="couponBox">
    <div style="background: #fff; border-radius: 20px; padding: 40px; width: 80%;">
        <!-- <img src="" class="couponArtWork" alt="Onsite Coupon Art Work"> -->
        <p style="font-size: 18px; font-weight: bold;">ลด <span id="couponDiscount"></span> <span id="couponCondition"></span></p>
        <p style="font-size: 16px; margin-bobttom: 20px;">ยื่นรหัสนี้ให้พนักงานที่เคาน์เตอร์</p><span style="
        width: 100%;
        color: #222;
        font-size: 48px;
        font-weight: bold;
        border: 2px dashed #333;
        padding: 20px;
        background: #EFEFEF;
        display: block;
        margin-bobttom: 20px;" id="couponBoxCode"></span>
        <button class="button" onclick="hideCoupon()">ปิดหน้าจอนี้</button>
    </div>
</div>

<script>
    function showCoupon(code, discount, coupon_condition) {
        document.getElementById('couponBox').style.display = "flex";
        document.getElementById('couponBoxCode').innerText = code;
        document.getElementById('couponDiscount').innerText = discount;
        document.getElementById('couponCondition').innerText = coupon_condition;
    }
    function hideCoupon() {
        document.getElementById('couponBox').style.display = "none";
    }
    function changeToWooCommerceCoupon(code) {
        window.location.href = window.location.pathname + '?convert_to_woocommerce_coupon=' + code;
    }
    function changeToOnsiteCoupon(code) {
        window.location.href = window.location.pathname + '?convert_to_onsite_coupon=' + code;
    }
</script>
<?php
}
add_action('wp_loaded', function() {
    global $wpdb;
    $coupon_table = "{$wpdb->prefix}onsite_coupon";
    $user_id = get_current_user_id();

    // --- ขาไป: สร้าง WooCommerce Coupon ---
    if(isset($_GET['convert_to_woocommerce_coupon'])) {
        $coupon_code = sanitize_text_field($_GET['convert_to_woocommerce_coupon']);

        $selected_coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $coupon_table WHERE code = %s", // ลองถอด user_id ออกก่อนเพื่อเช็คว่ามีโค้ดนี้ในระบบเราไหม
            $coupon_code
        ));

        if ($selected_coupon) {
            createWoocommerceCouponFromOnsiteCoupon(
                $selected_coupon->code, 
                $selected_coupon->discount_amount, 
                $selected_coupon->minspend,
                $user_id
            );
            wp_redirect(home_url('/my-account/?status=coupon_converted_to_website&code=' . $coupon_code));
            exit;
        }
    }

    if(isset($_GET['convert_to_onsite_coupon'])) {
        $coupon_code = sanitize_text_field($_GET['convert_to_onsite_coupon']);

        $selected_coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $coupon_table WHERE code = %s", 
            $coupon_code
        ));

        if ($selected_coupon) {
            // สั่งฟังก์ชันลบที่เราเขียนไว้ (ตัวที่ใช้ $wpdb->query ลบดิบ)
            createOnsiteCouponFromWoocommerceCoupon($selected_coupon->code);
            
            wp_redirect(home_url('/my-account/?status=coupon_converted_to_onsite&code=' . $coupon_code));
            exit;
        } else {
            // ถ้าหาโค้ดนี้ไม่เจอในตาราง On-site เลย
            wp_redirect(home_url('/my-account/?status=not_found&code=' . $coupon_code));
            exit;
        }
    }
});

function createWoocommerceCouponFromOnsiteCoupon($coupon_code, $discount_amount, $minimum_amount, $user_id) {
    if ( ! class_exists( 'WC_Coupon' ) ) return;

    global $wpdb;

    /**
     * 1. ถอนรากถอนโคน: เช็คหา ID จากชื่อคูปองโดยตรงในฐานข้อมูล 
     * (ไม่ใช้ฟังก์ชัน Woo เพราะบางทีมันติด Cache)
     */
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_name = %s LIMIT 1",
        sanitize_title($coupon_code)
    ));

    if ($existing_id) {
        // ถ้าเจอ "ซาก" คูปองเก่า (ไม่ว่าจะสถานะไหน) ให้ลบทิ้งถาวรทันที
        wp_delete_post($existing_id, true); 
    }

    /**
     * 2. เคลียร์ Cache ของ WooCommerce 
     * เพื่อให้ระบบลืมไปเลยว่าเคยมีคูปองชื่อนี้อยู่
     */
    wp_cache_delete('coupon-id-' . $coupon_code, 'coupons');
    delete_transient('wc_coupon_id_from_code_' . $coupon_code);

    /**
     * 3. สร้างใหม่แบบสดๆ (Fresh Start)
     */
    try {
        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount((float)$discount_amount);
        
        $min = (float)$minimum_amount > 0 ? (float)$minimum_amount : 0;
        $coupon->set_minimum_amount($min);
        
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);
        $coupon->set_description("สร้างจากคูปอง On-site โดย User ID: $user_id");
        
        // ตั้งวันหมดอายุ
        $coupon->set_date_expires(date('Y-m-d', strtotime('+7 days')));
        
        $coupon->save();
    } catch (Exception $e) {
        // ถ้าผิดพลาดให้บันทึกลง Error Log ของ Server
        error_log("Error creating coupon: " . $e->getMessage());
    }
}

function createOnsiteCouponFromWoocommerceCoupon($coupon_code) {
    global $wpdb;
    // หา ID คูปองใน Woo
    $coupon_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1",
        $coupon_code
    ));

    delete_transient( 'wc_coupon_id_from_code_' . $coupon_code );
    wp_cache_delete( 'coupon-id-' . $coupon_code, 'coupons' );

    if ($coupon_id) {
        // ลบด้วย SQL ตรงๆ ชัวร์ 100%
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->posts WHERE ID = %d", $coupon_id));
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE post_id = %d", $coupon_id));
        return true;
    }
    return false;
}