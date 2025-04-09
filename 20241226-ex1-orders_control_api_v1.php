<?php

const DB_SERVER   = "localhost";
const DB_USERNAME = "owner01";
const DB_PASSWORD = "123456";
const DB_NAME     = "testdb";

// 建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
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

function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

// 上傳圖片
function get_photo()
{
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['name'] != "") {
        $fileTmpPath = $_FILES['file_upload']['tmp_name'];
        $fileName    = $_FILES['file_upload']['name'];
        $fileSize    = $_FILES['file_upload']['size'];
        $fileType    = $_FILES['file_upload']['type'];
        $uploadDir = 'uploads/user/';
        $newFileName = date("YmdHis") . '_' . basename($fileName);
        $uploadPath  = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            return $uploadPath;
        }
    }
}

//驗證member_uid
function check_uid()
{
    $input = get_json_input();
    if (isset($input["uid01"])) {
        $p_uid = trim($input["uid01"]);
        if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT userName, addr, tel, email, Uid01, level, created_at FROM member_test WHERE Uid01 = ?");
            $stmt->bind_param("s", $p_uid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $userdata = $result->fetch_assoc();
                respond(true, "驗證成功!", $userdata);
            } else {
                respond(false, "驗證失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 會員電話更新檢查
function check_update_tel()
{
    $input = get_json_input();
    if (isset($input["updateModal_tel"])) {
        $p_tel = trim($input["updateModal_tel"]);
        if ($p_tel) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT tel FROM member_test WHERE tel = ?");
            $stmt->bind_param("s", $p_tel);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "電話重複，不可以使用!");
            } else {
                respond(true, "電話未重複，可以使用!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 取得所有訂單資料
function get_all_orders_data()
{
    $conn  = create_connection();

    $stmt = $conn->prepare("SELECT * FROM orders ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得所有訂單資料成功!", $mydata);
    } else {
        respond(false, "查無資料!");
    }
    $stmt->close();
    $conn->close();
}

// 新增訂單
function add_orders()
{
    $input = get_json_input();
    if (isset($_POST["member_id"], $_POST["username"], $_POST["tel"], $_POST["email"], 
    $_POST["process_id"], $_POST["process_title"], $_POST["order_spent"], $_POST["adults"], $_POST["children"])) {
        $p_member_id    = trim($_POST["member_id"]);
        $p_username     = trim($_POST["username"]);
        $p_tel          = trim($_POST["tel"]);
        $p_email        = trim($_POST["email"]);
        $p_process_id   = trim($_POST["process_id"]); 
        $p_title        = trim($_POST["process_title"]);
        $p_order_spent  = trim($_POST["order_spent"]);
        $p_adults       = trim($_POST["adults"]);
        $p_children     = trim($_POST["children"]);
        $p_remark       = trim($_POST["remark"]);
        if ($p_member_id && $p_username && $p_tel && $p_email && $p_process_id && $p_title && $p_order_spent && $p_adults) {
            
            $conn = create_connection();
            $stmt = $conn->prepare("INSERT INTO orders(member_id, username, tel, email, process_id, title, order_spent, adults, children, remark)
                                    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssisiiis", $p_member_id,  $p_username, $p_tel, $p_email, $p_process_id, $p_title, $p_order_spent, $p_adults, $p_children, $p_remark); //一定要傳遞變數

            if ($stmt->execute()) {
                respond(true, "訂單資料新增成功!");
            } else {
                respond(false, "訂單資料新增失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!" , $p_username);
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 更新訂單
function update_orders()
{
    $input = get_json_input();
    if (isset($input["id"], $input["username"], $input["tel"], $input["adults"], $input["children"], $input["remark"])) {
        $p_id    = trim($input["id"]);
        $p_username    = trim($input["username"]);
        $p_tel   = trim($input["tel"]);
        $p_adults  = trim($input["adults"]);
        $p_children = trim($input["children"]);
        $p_remark = trim($input["remark"]);
        if ($p_id && $p_username && $p_tel && $p_adults) {
            $conn = create_connection();

            $stmt = $conn->prepare("UPDATE orders SET username = ? ,tel = ?, adults = ?, children = ?, remark = ? WHERE id = ?");
            $stmt->bind_param("ssiisi", $p_username, $p_tel, $p_adults, $p_children, $p_remark, $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "訂單資料更新成功!");
                } else {
                    respond(false, "訂單資料更新失敗, 並無更新行為!");
                }
            } else {
                respond(false, "訂單資料更新失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 刪除訂單
function delete_orders()
{
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id         = trim($input["id"]);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->bind_param("i", $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {

                    respond(true, "訂單刪除成功!");
                } else {
                    respond(false, "訂單刪除失敗, 並無刪除行為!");
                }
            } else {
                respond(false, "訂單刪除失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 取得個人訂單資料
function get_member_orders_data()
{
    $input = get_json_input();
    $p_id = trim($input["id"]);
    $conn  = create_connection();

    $stmt = $conn->prepare("SELECT * FROM orders WHERE member_id = ?");
    $stmt->bind_param("s", $p_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得會員訂單資料成功!", $mydata);
    } else {
        respond(false, "查無資料!");
    }
    $stmt->close();
    $conn->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'checkuid':
            check_uid();
            break;
        case 'checkupdatetel':
            check_update_tel();
            break;
        case 'addorders':
            add_orders();
            break;
        case 'updateorders':
            update_orders();
            break;
        case 'getordersdata':
            get_member_orders_data();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getallorders':
            get_all_orders_data();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'deleteorders':
            delete_orders();
            break;
        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
