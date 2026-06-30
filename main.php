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
        'edit_posts',                        // Capability required
        'onsite_coupon_tracker',                             // Menu slug
        'onsite_coupon_tracker_page',            // Callback function to display page content
        'dashicons-buddicons-groups',                 // Icon URL or Dashicon class
        80                                       // Position in the menu (optional)
    );
}

add_action('admin_menu', 'onsite_coupon_tracker_menu');

function coupon_tracker_enqueue_assets()
{
    wp_enqueue_style(
        'coupon-tracker-style',
        plugins_url('/css/style.css', __FILE__),
        array(),
        time(),
        'all'
    );
}
add_action('wp_enqueue_scripts', 'coupon_tracker_enqueue_assets');

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
        $billing_id = sanitize_text_field($_POST['billing_id']);

        $update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}onsite_coupon SET code = %s, coupon_condition = %s, discount = %s, campaign_id = %d, 
            status = %d, discount_amount = %d, minspend = %d, billing_id = %s WHERE id = %d",
            $coupon_code, $coupon_condition, $coupon_discount, $campaign_id, $coupon_status, $discount_amount, $minspend, $billing_id, $coupon_id);
        $wpdb->query($update_query);

        wp_redirect(admin_url('admin.php?page=onsite_coupon_tracker&option=edit-coupon&coupon='.$coupon_id));
        exit;
    }
    ?>
    <style>
        .white-label-zone {
            width: calc(100% + 20px);
            height: auto;
            background: #fff;
            display: flex;
            margin: 0 0 0 -20px;
        }
        .white-label-zone h1,p {
            padding: 0 20px;
        }
        .campaigns_list {
            background: #f8f8f8; 
            width: 350px;
            height: max-content;
        }
        .campaigns_list a {
            padding: 15px 30px;
            font-size: 14px;
            background: #f5f5f5;
            color: #111;
            transition: .2s ease-in-out;
            width: 100%;
            height: auto;
            display: inline-block;
            text-decoration: none;
        }
        .campaigns_list a:hover {
            background: #fff;
        }
        .campaigns_list a.active {
            background: #fff;
        }
        .campaigns_list h2 {
            margin: 0; 
            padding: 10px; 
            background: #009FE3;
            color: #fff;
        }
        .container {
            background: #fff; 
            width: 1200px;
        }
        .container h1 {
            display: block;
            font-size: 18px;
            padding: 13px 20px;
            margin: 0 0 20px 0;
            background: #555;
            color: #fff;
        }
        .container p {
            padding: 10px 0;
            margin: 0;
        }
        a.menu-btn {
            width: 100%; 
            padding: 5px !important; 
            font-size: 14px !important;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
            }
            .container h1 {
                color: #000;
            }
        }
    </style>
    <div class="white-label-zone no-print">
        <span style="padding: 60px 10px 60px 40px;float: left;font-size: 60px;">🏷️</span>
        <div style="padding: 20px 0;">
            <h1>WordPress Onsite Coupon Manager</h1>
            <p>ระบบสร้างแคมเปญพิเศษ จัดการคูปองหน้าร้านสำหรับแคมเปญ
            <br>
            <strong>Github Repository:</strong> <a href="https://github.com/sunny420x/wordpress-onsite-coupon-tracker" target="_blank">https://github.com/sunny420x/wordpress-onsite-coupon-tracker</a>
            </p>
        </div>
    </div>
    <div class="wrapper" style="display: flex;">
        <div class="campaigns_list no-print">
            <h2>📚 รายการแคมเปญ <button class="button button-primary button-small" onclick="window.location.href='admin.php?page=onsite_coupon_tracker&option=newCampaign'" style="margin-left: 10px;">สร้างแคมเปญใหม่</button></h2>
            
            <?php
            $campaigns = $wpdb->get_results(
                "SELECT id,name FROM {$wpdb->prefix}onsite_campaign ORDER BY id DESC"
            );
            foreach($campaigns as $campaign) {
            ?>
                <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=".$campaign->id)?>" class="<?php if($campaign->id == $_GET['campaign']) { echo 'active'; } ?>"><?=$campaign->name?></a>
            <?php
            }
            ?>
            <br>
            <br>
            <h2>⚙️ ตั้งค่า</h2>
            <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker")?>">📖 คู่มือการใช้งานระบบ</a>
            <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&option=settings")?>">🛠️ ตั้งค่าระบบ</a>
        </div>
        <div class="container">
            <?php
            if(isset($_GET['campaign']) && !isset($_GET['searchCoupon'])) {
                $campaign = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_campaign WHERE id = %d",
                    $_GET['campaign']
                ));
            ?>
            <h1>✏️ แก้ไขแคมเปญ "<?=$campaign->name;?>"</h1>
            <div style="display: flex;">
                <div style="padding: 0 10px 20px 20px;">
                    <a href="admin.php?page=onsite_coupon_tracker&campaign=<?=$campaign->id;?>&searchCoupon=all" class="button menu-btn">🏷️ คูปองทั้งหมด</a><br><br>
                    <a href="admin.php?page=onsite_coupon_tracker&newCoupon" class="button menu-btn">➕ สร้างคูปองใหม่</a><br><br>
                    <a href="admin.php?page=onsite_coupon_tracker&option=report&campaign_id=<?=$campaign->id;?>" class="button menu-btn">🖨️ ออกรายงานแคมเปญ</a>
                </div>
                <div style="width: 100%; margin: 0 0 0 10px;">
                    <div style="padding: 0px 25px 25px 25px;">
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
                    </div>
                </div>
            </div>
            <?php
            } elseif(isset($_GET['newCoupon'])) {
            ?>
            <h1>➕ เพิ่มคูปองใหม่</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <form action="admin.php?page=onsite_coupon_tracker" method="post">
                    ลดจำนวน (Text): <input type="text" name="coupon_discount" id="" required><br><br>
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
            </div>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] == "all") {
                $campaign_id = $_GET['campaign'];
            ?>
            <h1 style="margin-top: 0;">🎫 คูปอง</h1>
                <div style="padding: 0px 25px 25px 25px;">
                    <p>ในหน้านี้คุณสามารถค้นหาคูปองที่มีอยู่ในแคมเปญได้ โดยการกรอกรหัสคูปองลงในช่องค้นหาและกด Enter</p>
                    <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=".$campaign_id);?>">กลับไปที่แคมเปญ</a><br><br>
                    <input type="text" style="width: 100%;" onchange="searchCoupon(this.value, '<?=$campaign_id?>')" placeholder="ค้นหาคูปองด้วยรหัส" value="<?=$_GET['searchCoupon'];?>">
                    <script>
                        function searchCoupon(query, campaign_id) {
                            window.location.href = `admin.php?page=onsite_coupon_tracker&searchCoupon=${query}&campaign=${campaign_id}`;
                        }
                    </script>
                    <?php
                    $stats = $wpdb->get_row($wpdb->prepare("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as used,
                            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as available,
                            SUM(CASE WHEN user_id IS NOT NULL AND user_id != 0 OR status = 1 THEN 1 ELSE 0 END) as taken
                        FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d
                    ", $campaign_id), OBJECT);

                    $all_coupon          = $stats->total;
                    $used_coupon         = $stats->used;
                    $available_coupon    = $stats->available;
                    $already_taken_coupon = $stats->taken;
                    ?>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin: 20px 0;">
                        <p style="margin: 0; font-size: 16px;">
                            📊 <strong>ภาพรวมแคมเปญ:</strong> <br>
                            ทั้งหมด: <strong><?= number_format($all_coupon); ?></strong> คูปอง | 
                            เหลือพร้อมใช้: <a href='<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=$campaign_id&searchCoupon=available");?>' style="text-decoration: none;"><span style="color: #007bff;"><?= number_format($available_coupon); ?></span></a> | 
                            ถูกเก็บแล้ว: <a href='<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=$campaign_id&searchCoupon=picked");?>' style="text-decoration: none;"><span style="color: red;"><?= number_format($already_taken_coupon); ?></span></a> | 
                            ใช้งานแล้ว:  <a href='<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=$campaign_id&searchCoupon=used");?>' style="text-decoration: none;"><span style="color: #28a745;"><?= number_format($used_coupon); ?></span></a> 
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
                                        "SELECT discount,coupon_condition,COUNT(*) as amount FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d 
                                        GROUP BY discount, coupon_condition ORDER BY discount_amount ASC",
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
                </div>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) 
            && $_GET['searchCoupon'] != "all" && $_GET['searchCoupon'] != "picked" 
            && $_GET['searchCoupon'] != 'available' && $_GET['searchCoupon'] != 'used') {
                $campaign_id = $_GET['campaign'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND code LIKE %s",
                    $_GET['campaign'], "%".$_GET['searchCoupon']."%"
                ));
            ?>
            <h1 style="margin-top: 0;">🔍 ผลการค้นหา: <?=$_GET['searchCoupon'];?></h1>
            <div style="padding: 0px 25px 25px 25px;">
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
                            <td><?php if($coupon->user_id == null) { echo "<span style='color: green;'>ยังไม่ถูกเก็บ</span>"; } else {
                            ?>
                                <span style='color: red;'>ถูกเก็บแล้ว 
                                <?php
                                if($coupon->user_id != 0) {
                                ?>
                                โดย: <a href="/wp-admin/user-edit.php?user_id=<?=$coupon->user_id?>&wp_http_referer=%2Fwp-admin%2Fusers.php" target="_blank"><?=$coupon->user_id?></a>
                                <?php
                                }
                                ?>
                                </span>
                            <?php
                            } ?> | <?php if($coupon->status == 0) {
                                echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; 
                                } else { 
                                echo "<span style='color: red;'>ใช้งานแล้ว</span>"; 
                                } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] == "picked") {
                $campaign_id = $_GET['campaign'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND (user_id IS NOT NULL OR status = 1) ORDER BY discount_amount ASC"
                , $campaign_id));
            ?>
            <h1 style="margin-top: 0;">🎉 คูปองที่ถูกเก็บแล้ว</h1>
            <div style="padding: 0px 25px 25px 25px;">
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
                            <td>
                                <span style='color: red;'>ถูกเก็บแล้ว 
                                <?php
                                if($coupon->user_id != 0) {
                                ?>  
                                โดย: <a href="/wp-admin/user-edit.php?user_id=<?=$coupon->user_id?>&wp_http_referer=%2Fwp-admin%2Fusers.php" target="_blank"><?=$coupon->user_id?></a>
                                <?php
                                }
                                ?>
                                </span>
                                | <?php if($coupon->status == 0) {
                                echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; 
                                } else { 
                                echo "<span style='color: red;'>ใช้งานแล้ว</span>"; 
                                } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] == "used") {
                $campaign_id = $_GET['campaign'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT c.id, c.discount, c.coupon_condition, c.code 
                    FROM wp9h_onsite_coupon c

                    WHERE c.campaign_id = %d 
                    AND c.billing_id IS NOT NULL 
                    OR c.status = 1

                    GROUP BY c.discount, c.coupon_condition, c.code
                    ORDER BY c.discount ASC"
                , $campaign_id));
            ?>
            <h1 style="margin-top: 0;">🎉 คูปองที่ถูกใช้แล้ว</h1>
            <div style="padding: 0px 25px 25px 25px;">
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
                            <td>
                                <span style='color: red;'>ถูกเก็บแล้ว
                                <?php
                                if($coupon->user_id != 0) {
                                ?>
                                    โดย: <a href="/wp-admin/user-edit.php?user_id=<?=$coupon->user_id?>&wp_http_referer=%2Fwp-admin%2Fusers.php" target="_blank"><?=$coupon->user_id?></a>
                                <?php
                                }
                                ?>
                                </span>
                                | <?php if($coupon->status == 0) {
                                echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; 
                                } else { 
                                echo "<span style='color: red;'>ใช้งานแล้ว</span>"; 
                                } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            } elseif(isset($_GET['searchCoupon']) && isset($_GET['campaign']) && $_GET['searchCoupon'] == "available") {
                $campaign_id = $_GET['campaign'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND user_id IS NULL ORDER BY discount_amount ASC"
                , $campaign_id));
            ?>
            <h1 style="margin-top: 0;">✅ คูปองที่ยังว่าง</h1>
            <?php
            if(isset($_GET['searchCoupon']) && isset($_GET['campaign'])) {
            ?>
            <div style="padding: 0px 25px 25px 25px;">
                <a href="<?=admin_url("admin.php?page=onsite_coupon_tracker&campaign=$campaign_id&searchCoupon=all");?>">กลับไปที่แคมเปญ</a><br><br>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>ลดจำนวน</th>
                            <th>เงื่อนไข</th>
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
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            }
            ?>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "coupon-by-condition") {
                $campaign_id = $_GET['campaign_id'];

                $coupons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE campaign_id = %d AND coupon_condition = %s AND discount = %s ORDER BY discount_amount ASC",
                    $campaign_id, $_GET['condition'], $_GET['discount']
                ));

            ?>
            <h1 style="margin-top: 0;">🔍 ผลการค้นหา</h1>
            <div style="padding: 0px 25px 25px 25px;">
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
                            <td><?php if($coupon->user_id == null) { echo "<span style='color: green;'>ยังไม่ถูกเก็บ</span>"; } else {                             ?>
                                <span style='color: red;'>ถูกเก็บแล้ว 
                                <?php
                                if($coupon->user_id) {
                                ?>
                                โดย: <a href="/wp-admin/user-edit.php?user_id=<?=$coupon->user_id?>&wp_http_referer=%2Fwp-admin%2Fusers.php" target="_blank"><?=$coupon->user_id?></a>
                                <?php
                                }
                                ?>
                                </span>
                            <?php } ?> | <?php if($coupon->status == 0) {echo "<span style='color: green;'>ยังไม่ถูกใช้งาน</span>"; } else { echo "<span style='color: red;'>ใช้งานแล้ว</span>"; } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "edit-coupon") {
                $coupon = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}onsite_coupon WHERE id = %d",
                    $_GET['coupon']
                ));
            ?>
            <h1>✏️ แก้ไขคูปอง "<?=$coupon->code;?>"</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <form action="admin.php?page=onsite_coupon_tracker" method="post">
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
                    เลขบิล: <input type="text" name="billing_id" value="<?=$coupon->billing_id;?>" style="width: 400px;"><br><br>
                    <input type="submit" value="บันทึกการเปลี่ยนแปลง" name="editCoupon" class="button">
                    <br><br>
                    <a href="admin.php?page=onsite_coupon_tracker&deleteCoupon=<?=$coupon->id;?>&campaign=<?=$coupon->campaign_id;?>">ลบคูปองนี้</a>
                </form>
            </div>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "report" && $_GET['campaign_id']) {
                $campaign_id = $_GET['campaign_id'];

                $report = $wpdb->get_results($wpdb->prepare(
                    "SELECT
                        onsite.code, 
                        onsite.billing_id,
                        onsite.discount,
                        onsite.coupon_condition,
                        users.display_name,
                        posts.post_date AS order_at,
                        meta_total.meta_value AS totals,
                        campaign.name 
                    FROM {$wpdb->prefix}onsite_coupon AS onsite
                    LEFT JOIN {$wpdb->users} AS users ON onsite.user_id = users.ID
                    LEFT JOIN {$wpdb->posts} AS posts ON onsite.billing_id = posts.ID
                    LEFT JOIN {$wpdb->prefix}postmeta AS meta_total ON onsite.billing_id = meta_total.post_id AND meta_total.meta_key = '_order_total'
                    LEFT JOIN {$wpdb->prefix}onsite_campaign as campaign ON onsite.campaign_id = campaign.id 
                    
                    WHERE onsite.status = 1 AND onsite.campaign_id = %d 
                    ORDER BY onsite.discount_amount DESC
                ",  $campaign_id));
                ?>
                <h1>รายงานแคมเปญ: <?=$report[0]->name?></h1>
                <div style="padding: 0px 25px 25px 25px;">
                    <button class="button no-print" onclick="window.print()" style="margin: 0 0 20px 0; width: 100%;">🖨️ ออกรายงาน</button>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Order ID</th>
                                <th>ชื่อลูกค้า</th>
                                <th>ยอดรวมสุทธิ</th>
                                <th>ส่วนลดคูปอง</th>
                                <th>เงื่อนไข</th>
                                <th>ทำรายการเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report) : ?>
                                <?php foreach($report as $row) : ?>
                                <tr>
                                    <td><a href="https://www.worldchemical.co.th/wp-admin/post.php?post=<?=$row->billing_id;?>&action=edit"><strong><?=$row->billing_id;?></strong></a></td>
                                    <td><?=esc_html($row->display_name ?: '-- ไม่พบชื่อ --');?></td>
                                    <td><?=wc_price($row->totals)?></td>
                                    <td style="color: red;">-<?=wc_price($row->discount);?> (<?=$row->code?>)</td>
                                    <td><?=$row->coupon_condition;?></td>
                                    <td><?php if(date('d/m/Y H:i', strtotime($row->order_at)) != null) { echo $row->order_at; } else { echo "-"; }?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="6" style="text-align:center;">ยังไม่มีข้อมูลการใช้คูปอง</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php
            } elseif(isset($_GET['deleteCoupon']) && !empty($_GET['deleteCoupon']) && isset($_GET['campaign_id']) && !empty($_GET['campaign_id'])) {
                $coupon_id = sanitize_text_field($_GET['deleteCoupon']);
                $campaign_id = sanitize_text_field($_GET['campaign_id']);
                $coupon_table = $wpdb->prefix . "onsite_coupon"; 
                $wpdb->query($wpdb->prepare("DELETE FROM $coupon_table WHERE id = %d AND campaign_id = %d", $coupon_id, $campaign_id));
                wp_redirect("admin.php?page=onsite_coupon_tracker&campaign=".$campaign_id."&searchCoupon=all");
                exit;
            ?>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "settings") {
            ?>
            <h1>ตั้งค่าระบบ</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('onsite_coupon_tracker_settings_group');
                    ?>
                    <label for="onsite_coupon_tracker_enable">เปิดใช้งานระบบ Onsite Coupon: </label>
                    <select name="onsite_coupon_tracker_enable" id="onsite_coupon_tracker_enable">
                        <option value="yes" <?php selected(get_option('onsite_coupon_tracker_enable', 'yes'), 'yes') ?>>เปิดใช้งานระบบ</option>
                        <option value="no" <?php selected(get_option('onsite_coupon_tracker_enable', 'yes'), 'no') ?>>ปิดใช้งานระบบ</option>
                    </select>
                    <br><br>
                    <label for="onsite_coupon_enable_individual_use">สามารถใช้ส่วนคูปองร่วมกับสิทธิพิเศษอื่น ๆ ได้: </label>
                    <select name="onsite_coupon_enable_individual_use" id="onsite_coupon_enable_individual_use">
                        <option value="no" <?php selected(get_option('onsite_coupon_enable_individual_use', 'no'), 'no') ?>>ใช่</option>
                        <option value="yes" <?php selected(get_option('onsite_coupon_enable_individual_use', 'no'), 'yes') ?>>ไม่</option>
                    </select>
                    <br><br>
                    <label for="onsite_coupon_enable_multiple_pick">สามารถเก็บคูปองได้มากกว่า 1 ใบ: </label>
                    <select name="onsite_coupon_enable_multiple_pick" id="onsite_coupon_enable_multiple_pick">
                        <option value="yes" <?php selected(get_option('onsite_coupon_enable_multiple_pick', 'no'), 'yes') ?>>ใช่</option>
                        <option value="no" <?php selected(get_option('onsite_coupon_enable_multiple_pick', 'no'), 'no') ?>>ไม่</option>
                    </select>
                    <br><br>
                    <label for="onsite_coupon_tracker_only_login_user">ใช้งานได้เฉพาะลูกค้าที่เข้าสู่ระบบแล้วเท่านั้น: </label>
                    <select name="onsite_coupon_tracker_only_login_user" id="onsite_coupon_tracker_only_login_user">
                        <option value="yes" <?php selected(get_option('onsite_coupon_tracker_only_login_user', 'yes'), 'yes') ?>>เปิดใช้งานระบบ</option>
                        <option value="no" <?php selected(get_option('onsite_coupon_tracker_only_login_user', 'yes'), 'no') ?>>ปิดใช้งานระบบ</option>
                    </select>
                    <br><br>
                    <label for="onsite_coupon_enable_coupon_book">เปิดใช้งาน Conpon Book: </label>
                    <select name="onsite_coupon_enable_coupon_book" id="onsite_coupon_enable_coupon_book">
                        <option value="yes" <?php selected(get_option('onsite_coupon_enable_coupon_book', 'yes'), 'yes') ?>>เปิดใช้งานระบบ</option>
                        <option value="no" <?php selected(get_option('onsite_coupon_enable_coupon_book', 'yes'), 'no') ?>>ปิดใช้งานระบบ</option>
                    </select>
                    <br><br>
                    <button type="submit" class="button">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
            <?php
            } elseif(isset($_GET['option']) && $_GET['option'] == "newCampaign") {
            ?>
            <h1>➕ สร้างแคมเปญใหม่</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <form action="admin.php?page=onsite_coupon_tracker" method="POST">
                    ชื่อแคมเปญ: <input type="text" name="campaign_name" id="" required style="width: 500px;"><br><br>
                    วันที่เริ่มแจกคูปอง: <input type="date" name="campaign_start_date" id="" required>
                    เวลา: <input type="time" name="campaign_start_time" id="" required>
                    <br><br>
                    วันที่หมดเวลาแจกคูปอง: <input type="date" name="campaign_end_date" id="" required>
                    เวลา: <input type="time" name="campaign_end_time" id="" required>
                    <br><br>
                    <input type="submit" value="สร้างแคมเปญ" name="addCampaign" class="button button-primary">
                </form>
            </div>
            <?php
            } else {
            ?>
            <h1>🎉 ยินดีต้อนรับสู่ระบบ WordPress Onsite Coupon Manager !!!</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <h2>ระบบนี้คืออะไร ?</h2>
                <p>ระบบ WordPress Onsite Coupon Manager คือระบบที่ออกแบบมาเพื่ออำนวยความสะดวกในการสร้างคูปองหน้าร้านสำหรับแคมเปญหรือกิจกรรมต่าง ๆ 
                    ช่วยให้จัดการคูปองได้ง่ายมากขึ้นสำหรับทั้งหน้าร้าน ผู้ออกแบบแคมเปญ และผู้จัดการ โดยระบบสามารถสร้างแคมเปญใหม่และสร้างคูปองส่วนลดภายในแคมเปญนั้น ๆ ได้</p>
                <h2>ระบบนี้ทำงานอย่างไร ?</h2>
                <p>คูปองทั้งหมดในแคมเปญจะแสดงก็ต่อเมื่อเวลาปัจจุบันอยู่ในช่วงระยะเวลา "แจกคูปอง" ตามที่ได้กำหนดไว้ในแคมเปญ เมื่อลูกค้าเก็บคูปอง คูปองจะถูกนำไปจัดเก็บและสามารถเข้าดูได้จากเมนู "บัญชีของฉัน" หรือที่
                    URL: <a href="/my-account" target="_blank">/my-account/</a>
                </p>
                <p>คูปองที่ลูกค้าเก็บสามารถใช้งานได้ทั้งการซื้อสินค้าหน้าร้านและบนเว็บไซต์ โดยหากลูกค้าต้องการใช้คูปองในการซื้อสินค้าบนเว็บไซต์จะต้องคลิกปุ่ม "เปลี่ยนเป็นคูปองในเว็บไซต์" ก่อน และนำโค้ดส่วนลดไปใช้เป็นคูปองในการลดราคาสินค้า</p>
                <h2>วิธีการใช้งานสำหรับพนักงานหน้าร้านเมื่อลูกค้าใช้โค้ด</h2>
                <p>เมื่อลูกค้าแสดงโค้ดส่วนลดในการลดค่าสินค้าหน้าร้าน 
                    1. พนักงานหน้าร้านจำเป็นต้องไปที่เมนู "รายการแคมเปญ" และคลิกเลือกแคมเปญปัจจุบันที่ดำเนินการอยู่<br>
                    2. คลิก "คูปองทั้งหมด" และพิมพ์รหัสคูปองลงในช่องค้นหา<br>
                    3. เมื่อพบคูปองที่มีรหัสตรงกันให้ปรับสถานะเป็น "ใช้งานแล้ว" และกรอกเลขบิลของลูกค้าเพื่อให้สามารถติดตามภายหลังได้
                </p>
                <hr>
                <h2>สำหรับนักพัฒนาเว็บไซต์ - For Web Developer</h2>
                <p>หากคุณคือนักพัฒนาเว็บไซต์ที่กำลังใช้งานระบบนี้ โปรดทำให้แน่ใจว่าคุณติดตั้งระบบนี้ถูกต้อง: </p>
                <h3>วิธีติดตั้งปลั้กอิน</h3>
                <p>ไปที่ /wp-admin/plugin-install.php และอัปโหลดไฟล์ zip ของปลั้กอิน จากนั้นสามารถ Activate แล้วเริ่มใช้งานปลั้กอินจากเมนูได้เลย !</p>
                <h3>การใช้งาน</h3>
                <p>นำ ShortCode [evoucher_page] ไปวางในหน้าที่ต้องการให้แสดงจุดเก็บคูปอง และสร้างแคมเปญใหม่ได้เลย !</p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'onsite_coupon_tracker_settings_init');

function onsite_coupon_tracker_settings_init()
{
    register_setting('onsite_coupon_tracker_settings_group', 'onsite_coupon_tracker_enable');
    register_setting('onsite_coupon_tracker_settings_group', 'onsite_coupon_enable_multiple_pick');
    register_setting('onsite_coupon_tracker_settings_group', 'onsite_coupon_enable_individual_use');
    register_setting('onsite_coupon_tracker_settings_group', 'onsite_coupon_tracker_only_login_user');
    register_setting('onsite_coupon_tracker_settings_group', 'onsite_coupon_enable_coupon_book');
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

        if(get_option("onsite_coupon_tracker_only_login_user") == "yes") {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/e-voucher/'))); 
                exit;
            }

            $user_id = get_current_user_id();
            $table_name = "{$wpdb->prefix}onsite_coupon";

            if(get_option('onsite_coupon_enable_multiple_pick', 'no') == "no") {
                $already_got_coupon = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id));

                if($already_got_coupon > 0) {
                    wp_redirect(home_url('/e-voucher/?status=you-already-picked'));
                    exit;
                }
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
                $updated = $wpdb->update(
                    $table_name,
                    array('user_id' => $user_id),
                    array('id' => $coupon_id, 'user_id' => null),
                    array('%d'),
                    array('%d', '%d')
                );

                if ($updated) {
                    wp_redirect(home_url('/e-voucher/?status=success'));
                    exit;
                }
            } else {
                wp_redirect(home_url('/e-voucher/?status=cannot_find_coupon'));
                exit;
            }

            wp_redirect(home_url('/e-voucher/?status=out_of_stock'));
            exit;
        }
    }
});

add_shortcode('evoucher_page', function() {
    if(get_option('onsite_coupon_tracker_enable', 'yes') == "no") return;

    global $wpdb;

    if(get_option("onsite_coupon_tracker_only_login_user") == "yes") {
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
    }

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
                        "SELECT c.discount, c.coupon_condition, c.code 
                        FROM {$wpdb->prefix}onsite_coupon c
                        LEFT JOIN {$wpdb->prefix}posts p ON c.code = p.post_title AND p.post_type = 'shop_coupon'
                        WHERE c.status = 0 AND campaign_id = %d
                        GROUP BY c.discount, c.coupon_condition 
                        ORDER BY c.discount ASC", $campaign->id
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
                                        <?php
                                        if(get_option("onsite_coupon_tracker_only_login_user") == "yes") {
                                        ?>
                                        <button class="btn-pick" <?php if($already_got_coupon > 0) { echo "disabled"; } ?> onclick="pickBtn('/e-voucher/pick/<?=$coupon->discount?>/<?=$coupon->coupon_condition?>/<?=$campaign->id;?>')">เก็บคูปองนี้</button>
                                        <?php
                                        } else {
                                        ?>
                                        <button class="btn-pick" onclick="showCoupon('<?=$coupon->code;?>','<?=$coupon->discount?>', '<?=$coupon->coupon_condition?>')">ใช้งานเลย!</button>
                                        <?php
                                        }
                                        ?>
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

        <!-- Coupon Box สำหรับโชว์โค้ด -->
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
                <br>
                <button class="button close-coupon-btn" onclick="hideCoupon()">ปิดหน้าจอนี้</button>
                <button class="button go-to-cart-btn" id="toWebCart">ใช้ซื้อสินค้าหน้าเว็บ</button>
            </div>
        </div>
        <script>
            function pickBtn(url) {
                const allBtn = document.getElementsByClassName('btn-pick');
                for(i = 0; i < allBtn.length; i++) {
                    allBtn[i].disabled = true;
                }
                window.location.href = url;
            }

            function showCoupon(code, discount, coupon_condition) {
                document.getElementById('couponBox').style.display = "flex";
                document.getElementById('couponBoxCode').innerText = code;
                document.getElementById('couponDiscount').innerText = discount;
                document.getElementById('couponCondition').innerText = coupon_condition;
                document.getElementById('toWebCart').setAttribute('onclick', "window.location.href='/cart/?use_from_coupon_book="+code+"'");
            }
            function hideCoupon() {
                document.getElementById('couponBox').style.display = "none";
            }
        </script>
    </div>

    <?php
    return ob_get_clean();
});

add_action('woocommerce_before_my_account', 'my_onsite_coupons_table');

function my_onsite_coupons_table() {
    if(get_option('onsite_coupon_tracker_enable', 'yes') == "no") return;


    global $wpdb;
    $coupon_table = $wpdb->prefix . 'onsite_coupon';
    $my_onsite_coupons = $wpdb->get_results($wpdb->prepare(
        "SELECT code, coupon_condition, discount FROM $coupon_table WHERE user_id = %d AND status = 0", get_current_user_id()
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
        <button class="button" class="close-coupon-btn" onclick="hideCoupon()">ปิดหน้าจอนี้</button>
    </div>
</div>
<div class="accordion" id="onsiteCoupon">
  <div class="card">
    <div class="card-header"><button class="btn btn-link" data-toggle="collapse" data-target="#onsiteCouponAccordion" aria-expanded="true" aria-controls="onsiteCouponAccordion">🎫 คูปองส่วนลดพิเศษสำหรับใช้งานหน้าร้าน</button></div>
    <div id="onsiteCouponAccordion" class="collapse show" data-parent="#onsiteCoupon">
      <div class="card-body" style="overflow: auto; background:#fff; border-radius:8px; padding:25px; margin-bottom:30px;">
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
        <table class="wp-list-table widefat fixed striped" style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>ส่วนลด</th>
                    <th colspan="2">เงื่อนไขการใช้คูปอง</th>
                </tr>
            </thead>
            <tbody>
                <?php
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
                            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1",
                            $my_onsite_coupon->code
                        ));

                        $coupon_amount = 0;
                        if ($real_coupon_id) {
                            $coupon_amount = (float) get_post_meta($real_coupon_id, 'coupon_amount', true);
                        }

                        if (!$real_coupon_id || $coupon_amount <= 0) {
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
    </div>
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

    // 1. เช็คสถานะในตาราง On-site ก่อน
    $onsite_status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}onsite_coupon WHERE code = %s",
        $coupon_code
    ));
    if ($onsite_status == 1) return;

    // 2. หา ID ของคูปองที่มีอยู่เดิม (รวมทุกสถานะ ทั้ง publish และ draft)
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1",
        $coupon_code
    ));

    try {
        // หากมี ID เดิม ให้ดึง Object เดิมมาใช้ หากไม่มีให้สร้างใหม่ (new)
        $coupon = $existing_id ? new WC_Coupon($existing_id) : new WC_Coupon();
        
        $coupon->set_code($coupon_code);
        $coupon->set_status('publish'); // บังคับให้เป็น Publish เสมอ
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount((float)$discount_amount);
        
        // ล้างประวัติการใช้งาน (เพื่อป้องกันบั๊กใช้ซ้ำไม่ได้)
        $coupon->set_usage_count(0);
        $coupon->set_used_by(array());
        
        $min = (float)$minimum_amount > 0 ? (float)$minimum_amount : 0;
        $coupon->set_minimum_amount($min);
        
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);

        if(get_option('onsite_coupon_enable_individual_use', 'no') == "yes") {
            $individual_use = true;
        } else {
            $individual_use = false;
        }

        $coupon->set_individual_use($individual_use);
        $coupon->set_description("On-site Update: User ID $user_id");
        $coupon->set_date_expires(date('Y-m-d', strtotime('+7 days')));
        
        $coupon->save();

        delete_transient('wc_coupon_id_from_code_' . $coupon_code);
        wp_cache_delete('coupon-id-' . $coupon_code, 'coupons');
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wc_order_coupon_lookup WHERE coupon_code = %s", $coupon_code));

        if ( WC()->cart ) {
            // ลบคูปองแบบระบุชื่อ
            WC()->cart->remove_coupon( $coupon_code );
            
            // บังคับให้โหลดข้อมูลจาก Session ใหม่ (หัวใจสำคัญ)
            WC()->cart->get_cart_from_session();
            
            // สั่งคำนวณใหม่
            WC()->cart->calculate_totals();
            
            // บังคับ Save Session ทันที
            WC()->cart->set_session();
            
            // ป้องกัน Cache ระดับ Object
            wc_delete_shop_order_transients();
        }

    } catch (Exception $e) {
        error_log("Error in Smart Coupon Sync: " . $e->getMessage());
    }
}

function createOnsiteCouponFromWoocommerceCoupon($coupon_code) {
    global $wpdb;

    // 1. หา ID คูปอง
    $coupon_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1",
        $coupon_code
    ));

    if ($coupon_id) {
        update_post_meta($coupon_id, 'coupon_amount', 0);
        update_post_meta($coupon_id, 'usage_limit', -1);
        
        $wpdb->update(
            $wpdb->posts,
            array('post_status' => 'draft'), 
            array('ID' => $coupon_id)
        );

        delete_transient( 'wc_coupon_id_from_code_' . $coupon_code );
        wp_cache_delete( 'coupon-id-' . $coupon_code, 'coupons' );
        
        if ( class_exists( 'WC_Cache_Helper' ) ) {
            WC_Cache_Helper::get_transient_version( 'coupons', true );
        }

        if ( WC()->cart ) {
            // ลบคูปองแบบระบุชื่อ
            WC()->cart->remove_coupon( $coupon_code );
            
            // บังคับให้โหลดข้อมูลจาก Session ใหม่ (หัวใจสำคัญ)
            WC()->cart->get_cart_from_session();
            
            // สั่งคำนวณใหม่
            WC()->cart->calculate_totals();
            
            // บังคับ Save Session ทันที
            WC()->cart->set_session();
            
            // ป้องกัน Cache ระดับ Object
            wc_delete_shop_order_transients();
        }

        return true;
    }
    return false;
}

add_action('woocommerce_thankyou', 'mark_onsite_coupon_as_used', 10, 1);

function mark_onsite_coupon_as_used($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    $applied_coupons = $order->get_coupon_codes();

    if (!empty($applied_coupons)) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'onsite_coupon';

        foreach ($applied_coupons as $code) {
            $wpdb->update(
                $table_name,
                array(
                    'status'     => 1,
                    'billing_id' => $order_id // ใส่ order_id ลงในฟิลด์ billing_id
                ),
                array('code' => $code),
                array('%d', '%d'), // %d สำหรับ status (int) และ billing_id (int)
                array('%s')        // %s สำหรับ code (string)
            );
        }
    }
} 

add_action('woocommerce_after_cart_table', 'wp_cart_coupon_book');
function wp_cart_coupon_book() {
    //Return if required login user.
    if(get_option("onsite_coupon_tracker_only_login_user") == "yes") {
        return;
    }

    global $wpdb;
    $current_time = current_time('mysql');

    if(get_option('onsite_coupon_enable_coupon_book') == "yes") {
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
    }

    if(isset($_GET['use_from_coupon_book']) && !empty($_GET['use_from_coupon_book'])) {
        if ( ! class_exists( 'WC_Coupon' ) ) return;
        global $wpdb;
        $coupon_code = sanitize_text_field($_GET['use_from_coupon_book']);

        $onsite_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}onsite_coupon WHERE code = %s",
            $coupon_code
        ));

        $discount_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT discount_amount FROM {$wpdb->prefix}onsite_coupon WHERE code = %s",
            $coupon_code
        ));

        $minimum_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT minspend FROM {$wpdb->prefix}onsite_coupon WHERE code = %s",
            $coupon_code
        ));

        if ($onsite_status == 1) return;

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1",
            $coupon_code
        ));

        try {
            $coupon = $existing_id ? new WC_Coupon($existing_id) : new WC_Coupon();
            
            $coupon->set_code($coupon_code);
            $coupon->set_status('publish'); // บังคับให้เป็น Publish เสมอ
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_amount((float)$discount_amount);
            
            // ล้างประวัติการใช้งาน (เพื่อป้องกันบั๊กใช้ซ้ำไม่ได้)
            $coupon->set_usage_count(0);
            $coupon->set_used_by(array());
            
            $min = (float)$minimum_amount > 0 ? (float)$minimum_amount : 0;
            $coupon->set_minimum_amount($min);
            
            $coupon->set_usage_limit(1);
            $coupon->set_usage_limit_per_user(1);

            if(get_option('onsite_coupon_enable_individual_use', 'no') == "yes") {
                $individual_use = true;
            } else {
                $individual_use = false;
            }

            $coupon->set_individual_use($individual_use);
            $coupon->set_date_expires(date('Y-m-d', strtotime('+7 days')));
            
            $coupon->save();

            delete_transient('wc_coupon_id_from_code_' . $coupon_code);
            wp_cache_delete('coupon-id-' . $coupon_code, 'coupons');
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wc_order_coupon_lookup WHERE coupon_code = %s", $coupon_code));

            if ( WC()->cart ) {
                // ลบคูปองแบบระบุชื่อ
                WC()->cart->remove_coupon( $coupon_code );
                WC()->cart->get_cart_from_session();
                WC()->cart->calculate_totals();
                // บังคับ Save Session ทันที
                WC()->cart->set_session();
                // ป้องกัน Cache ระดับ Object
                wc_delete_shop_order_transients();
            }
            wp_redirect("/cart/?apply_coupon=$coupon_code");
        } catch (Exception $e) {
            error_log("Error in Smart Coupon Sync: " . $e->getMessage());
        }  
    }
    
    if(isset($_GET['apply_coupon']) && !empty($_GET['apply_coupon'])) {
        if ( count( WC()->cart->get_applied_coupons() ) == 0 ) {
            $coupon_code = sanitize_text_field($_GET['apply_coupon']);
            if ( ! WC()->cart->has_discount( $coupon_code ) ) {
                WC()->cart->add_discount( $coupon_code );
            }
        }
    }

    if(get_option('onsite_coupon_enable_coupon_book') == "yes") {

    foreach($campaigns as $campaign) : ?>
        <div style="padding: 20px; margin-top: 30px; border: 1px solid #ddd;">
            <h3 style="margin-top:0;">🎫 <?=$campaign->name;?></h3>
            <div class="coupon-book-container">
                <?php
                $sql = $wpdb->prepare(
                    "SELECT c.discount, c.coupon_condition, c.code 
                    FROM {$wpdb->prefix}onsite_coupon c
                    LEFT JOIN {$wpdb->prefix}posts p ON c.code = p.post_title AND p.post_type = 'shop_coupon'
                    WHERE c.user_id IS NULL 
                    AND p.ID IS NULL AND campaign_id = %d 
                    GROUP BY c.discount, c.coupon_condition 
                    ORDER BY c.discount ASC", $campaign->id
                );

                $coupons = $wpdb->get_results($sql);
                if ($coupons) {
                    foreach($coupons as $coupon) : ?>
                        <div class="coupon">
                            <div class="holder">
                                <span><?=$coupon->discount?></span>
                                <div style="flex:1;">
                                    <p>
                                        <strong>ลด <?=$coupon->discount?></strong><br>
                                        <small><?=$coupon->coupon_condition?></small>
                                    </p>
                                </div>
                                <a href="/cart/?use_from_coupon_book=<?=$coupon->code?>" class="btn-pick">ใช้งานเลย!</a>
                            </div>
                        </div>
                    <?php endforeach;
                }
                ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php
    }
}