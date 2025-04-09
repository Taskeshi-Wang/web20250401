<?php

const DB_SERVER   = "localhost";
const DB_memberNAME = "owner01";
const DB_PASSWORD = "123456";
const DB_NAME     = "testdb";

// 建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_memberNAME, DB_PASSWORD, DB_NAME);
    if (! $conn) {
        echo json_encode(["state" => false, "message" => "連線失敗"]);
        exit;
    }
    return $conn;
}

// 取得json的資料
function get_json_input()
{
    $data = file_get_contents("php://input");
    return json_decode($data, true);
}

// 回復JSON的訊息
function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

// 統計會員總人數
function count_member()
{

    $conn = create_connection();
    $sql    = "SELECT count(*) as count_member FROM member_test";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "統計會員人數成功", $row);
    } else {
        respond(false, "統計會員人數失敗");
    }

    $conn->close();
}

// 統計行程數量
function count_process()
{

    $conn = create_connection();
    $sql    = "SELECT count(*) as count_process FROM process";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "統計行程數量成功", $row);
    } else {
        respond(false, "統計行程數量失敗");
    }

    $conn->close();
}

// 統計訂單數量
function count_orders()
{

    $conn = create_connection();
    $sql    = "SELECT count(*) as count_orders FROM orders";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "統計訂單數量成功", $row);
    } else {
        respond(false, "統計訂單數量失敗");
    }

    $conn->close();
}

// 統計年度銷售額
function count_orders_spent()
{

    $conn = create_connection();
    $sql    = "SELECT CONCAT('NT$', FORMAT(SUM(order_spent), 0)) as count_orders_spent FROM orders WHERE pay_status = '已支付'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "統計年度銷售額成功", $row);
    } else {
        respond(false, "統計年度銷售額失敗");
    }

    $conn->close();
}

// 統計今日銷售額
function count_today_orders_spent()
{

    $conn = create_connection();
    $sql    = "SELECT CONCAT('NT$', FORMAT(SUM(order_spent), 0)) AS count_today_orders_spent FROM orders 
                WHERE pay_status = '已支付' 
                AND DATE(order_date) = CURDATE()";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "統計今日銷售額成功", $row);
    } else {
        respond(false, "統計今日銷售額失敗");
    }

    $conn->close();
}

// 統計每月會員人數
function count_month_member()
{

    $conn = create_connection();
    $sql    = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS member_count
                FROM member_test GROUP BY month ORDER BY month";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計每月會員人數成功", $mydata);
    } else {
        respond(false, "統計每月會員人數失敗");
    }

    $conn->close();
}

// 統計各縣市會員人數分布
function count_city_member()
{

    $conn = create_connection();
    $sql    = "SELECT 
    CASE 
        WHEN addr LIKE '台北市%' THEN '台北市'
        WHEN addr LIKE '新北市%' THEN '新北市'
        WHEN addr LIKE '桃園市%' THEN '桃園市'
        WHEN addr LIKE '台中市%' THEN '台中市'
        WHEN addr LIKE '台南市%' THEN '台南市'
        WHEN addr LIKE '高雄市%' THEN '高雄市'
        WHEN addr LIKE '基隆市%' THEN '基隆市'
        WHEN addr LIKE '新竹市%' THEN '新竹市'
        WHEN addr LIKE '新竹縣%' THEN '新竹縣'
        WHEN addr LIKE '苗栗縣%' THEN '苗栗縣'
        WHEN addr LIKE '彰化縣%' THEN '彰化縣'
        WHEN addr LIKE '南投縣%' THEN '南投縣'
        WHEN addr LIKE '雲林縣%' THEN '雲林縣'
        WHEN addr LIKE '嘉義市%' THEN '嘉義市'
        WHEN addr LIKE '嘉義縣%' THEN '嘉義縣'
        WHEN addr LIKE '屏東縣%' THEN '屏東縣'
        WHEN addr LIKE '宜蘭縣%' THEN '宜蘭縣'
        WHEN addr LIKE '花蓮縣%' THEN '花蓮縣'
        WHEN addr LIKE '台東縣%' THEN '台東縣'
        WHEN addr LIKE '澎湖縣%' THEN '澎湖縣'
        WHEN addr LIKE '金門縣%' THEN '金門縣'
        WHEN addr LIKE '連江縣%' THEN '連江縣'
    END AS city, 
    COUNT(*) AS count_city_member
    FROM member_test
    GROUP BY city
    ORDER BY count_city_member DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計會員居住地成功", $mydata);
    } else {
        respond(false, "統計會員居住地失敗");
    }

    $conn->close();
}

// 統計各階層會員人數分布
function count_level_member()
{

    $conn = create_connection();
    $sql    = "SELECT count(level) as level_count, level FROM member_test GROUP BY level";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計會員等級分布成功", $mydata);
    } else {
        respond(false, "統計會員等級分布失敗");
    }

    $conn->close();
}

// 統計行程地區分布
function count_region_process()
{

    $conn = create_connection();
    $sql    = "SELECT region , SUM(signups) AS total_signups FROM process GROUP BY region";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計行程地區報名人數成功", $mydata);
    } else {
        respond(false, "統計行程地區報名人數失敗");
    }

    $conn->close();
}

// 統計行程狀態分布
function count_status_process()
{

    $conn = create_connection();
    $sql    = "SELECT count(*) AS count_status_process, status FROM process GROUP BY status";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計行程狀態成功", $mydata);
    } else {
        respond(false, "統計行程狀態失敗");
    }

    $conn->close();
}

// 統計每月銷售額
function count_month_orders_spent()
{

    $conn = create_connection();
    $sql    = "SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, SUM(order_spent) AS count_month_orders_spent
                FROM orders GROUP BY month ORDER BY month";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計每月銷售額成功", $mydata);
    } else {
        respond(false, "統計每月銷售額失敗");
    }

    $conn->close();
}

// 統計每月訂單數量
function count_month_orders()
{

    $conn = create_connection();
    $sql    = "SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, count(*) AS count_month_orders
                FROM orders GROUP BY month ORDER BY month";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計每月訂單數量成功", $mydata);
    } else {
        respond(false, "統計每月訂單數量失敗");
    }

    $conn->close();
}

// 統計行程評價、報名人數及團費
function rating_process()
{

    $conn = create_connection();
    $sql    = "SELECT title, signups, rating, cost FROM process";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計評價、報名人數及團費成功", $mydata);
    } else {
        respond(false, "統計評價、報名人數及團費失敗");
    }

    $conn->close();
}

// 統計行程評分
function score_process()
{

    $conn = create_connection();
    $sql    = "SELECT * FROM process_score";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "統計行程評分成功", $mydata);
    } else {
        respond(false, "統計行程評分失敗");
    }

    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'countmember':
            count_member();
            break;
        case 'countmonthmember':
            count_month_member();
            break;
        case 'countcitymember':
            count_city_member();
            break;
        case 'countlevelmember':
            count_level_member();
            break;
        case 'countprocess':
            count_process();
            break;
        case 'countregionprocess':
            count_region_process();
            break;
        case 'countstatusprocess':
            count_status_process();
            break;
        case 'ratiingprocess':
            rating_process();
            break;
        case 'scoreprocess':
            score_process();
            break;
        case 'countorders':
            count_orders();
            break;
        case 'countmonthordersspent':
            count_month_orders_spent();
            break;
        case 'countmonthorders':
            count_month_orders();
            break;
        case 'countordersspent':
            count_orders_spent();
            break;
        case 'counttodayordersspent':
            count_today_orders_spent();
            break;
        default:
            respond(false, "無效的操作");
    }
}
