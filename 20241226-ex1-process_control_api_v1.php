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

// 回復JSON的訊息
function respond($state, $message, $data = null)
{
    // json_encode編成josn格式(陣列)
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

// 上傳圖片
function get_photo()
{
    if (isset($_FILES['photo']) && $_FILES['photo']['name'] != "") {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName    = $_FILES['photo']['name'];
        $fileSize    = $_FILES['photo']['size'];
        $fileType    = $_FILES['photo']['type'];

        $uploadDir = 'uploads/process/';
        $newFileName = date("YmdHis") . '_' . basename($fileName);
        $uploadPath  = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            return $uploadPath;
        }
    }
}

// 更新圖片
function get_update_photo()
{
    $p_oldphotopath = $_POST["updateModal_oldphotopath"];
    if (isset($_FILES['updateModal_photo']) && $_FILES['updateModal_photo']['name'] != "") {
        unlink($p_oldphotopath);

        $fileTmpPath = $_FILES['updateModal_photo']['tmp_name'];
        $fileName    = $_FILES['updateModal_photo']['name'];
        $fileSize    = $_FILES['updateModal_photo']['size'];
        $fileType    = $_FILES['updateModal_photo']['type'];

        $uploadDir = 'uploads/process/';
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
                respond(true, "驗證成功", $userdata);
            } else {
                respond(false, "驗證失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// check process_title
function check_add_title()
{
    $input = get_json_input();
    if (isset($input["addModal_title"])) {
        $p_title = trim($input["addModal_title"]);
        if ($p_title) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT title FROM process WHERE title = ?");
            $stmt->bind_param("s", $p_title);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                respond(true, "標題未重複，可以使用");
            } else {
                respond(false, "標題重複，不可以使用");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// check update process_title
function check_update_title()
{
    $input = get_json_input();
    if (isset($input["updateModal_title"])) {
        $p_title = trim($input["updateModal_title"]);
        if ($p_title) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT title FROM process WHERE title = ?");
            $stmt->bind_param("s", $p_title);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "標題重複，不可以使用");
            } else {
                respond(true, "標題未重複，可以使用");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

function get_all_process_data()
{
    $input = get_json_input();
    $conn  = create_connection();

    $stmt = $conn->prepare("SELECT * FROM process ORDER BY ID DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得所有行程資料成功", $mydata);
    } else {
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

// 新增行程
function add_process()
{
    if (isset($_POST["addModal_title"], $_POST["addModal_content"], $_POST["addModal_days"], $_POST["addModal_region"], $_POST["addModal_startdate"], $_POST["addModal_enddate"], $_POST["addModal_airplane"], $_POST["addModal_cost"], $_POST["addModal_status"])) {

        if ($uploadPath = get_photo()) {
            $p_title     = $_POST["addModal_title"];
            $p_content   = $_POST["addModal_content"];
            $p_region    = $_POST["addModal_region"];
            $p_days      = $_POST["addModal_days"];
            $p_startdate = $_POST["addModal_startdate"];
            $p_enddate   = $_POST["addModal_enddate"];
            $p_airplane  = $_POST["addModal_airplane"];
            $p_cost      = $_POST["addModal_cost"];
            $p_status    = $_POST["addModal_status"];
            $p_remark    = $_POST["addModal_remark"];

            if ($p_title && $p_content && $p_cost && $uploadPath && $p_region && $p_days && $p_startdate && $p_enddate && $p_airplane && $p_status) {
                $conn = create_connection();

                $stmt = $conn->prepare("INSERT INTO process(title, content, photo, region, days, startdate, enddate, airplane, cost, status, remark) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisssiss", $p_title, $p_content, $uploadPath, $p_region, $p_days, $p_startdate, $p_enddate, $p_airplane, $p_cost, $p_status, $p_remark);
                if ($stmt->execute()) {
                    respond(true, '新增成功');
                } else {
                    respond(false, '新增失敗');
                }
                $stmt->close();
                $conn->close();
            } else {
                respond(false, "欄位錯誤!");
            }
        } else {
            respond(false, "檔案上傳失敗");
        }

    } else {
        respond(false, "欄位不得為空!");
    }
}

// 更新行程
function update_process()
{

    if (isset($_POST["updateModal_id"], $_POST["updateModal_title"], $_POST["updateModal_content"], $_POST["updateModal_days"], $_POST["updateModal_startdate"], $_POST["updateModal_enddate"], $_POST["updateModal_airplane"], $_POST["updateModal_cost"], $_POST["updateModal_status"], $_POST["updateModal_remark"])) {
        $p_id        = $_POST["updateModal_id"];
        $p_title     = $_POST["updateModal_title"];
        $p_content   = $_POST["updateModal_content"];
        $p_days      = $_POST["updateModal_days"];
        $p_startdate = $_POST["updateModal_startdate"];
        $p_enddate   = $_POST["updateModal_enddate"];
        $p_airplane  = $_POST["updateModal_airplane"];
        $p_cost      = $_POST["updateModal_cost"];
        $p_status    = $_POST["updateModal_status"];
        $p_remark    = $_POST["updateModal_remark"];

        if ($_FILES['updateModal_photo']['name'] != "") {
            $uploadPath = get_update_photo();
        } else {
            $uploadPath = $_POST["updateModal_oldphotopath"];
        }

        if ($p_title && $p_content && $p_cost && $uploadPath && $p_days && $p_startdate && $p_enddate && $p_airplane && $p_status) {
            $conn = create_connection();

            $stmt = $conn->prepare("UPDATE process SET title = ?, content = ?, photo = ?, days = ?, startdate = ?, enddate = ?, airplane = ?, cost = ? , status = ? , remark = ? WHERE id = ?");
            $stmt->bind_param("sssisssissi", $p_title, $p_content, $uploadPath, $p_days, $p_startdate, $p_enddate, $p_airplane, $p_cost, $p_status, $p_remark, $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "行程更新成功");
                } else {
                    respond(false, "行程更新失敗, 並無更新行為!");
                }
            } else {
                respond(false, "行程更新失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// 刪除行程
function delete_process()
{
    $input = get_json_input();
    if (isset($input["id"], $input["photo"])) {
        $p_id    = trim($input["id"]);
        $p_photo = $input["photo"];
        unlink($p_photo);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM process WHERE id = ?");
            $stmt->bind_param("i", $p_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {

                    respond(true, "行程刪除成功");
                } else {
                    respond(false, "行程刪除失敗, 並無刪除行為!");
                }
            } else {
                respond(false, "行程刪除失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

function get_card_process()
{
    if (isset($_POST["region"])) {
        $p_region = $_POST["region"];
        $conn     = create_connection();

        $stmt = $conn->prepare("SELECT * FROM process WHERE region = ?");
        $stmt->bind_param("s", $p_region);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $mydata = [];
            while ($row = $result->fetch_assoc()) {
                $mydata[] = $row;
            }
            respond(true, "取得地區行程資料成功!", $mydata);
        } else {
            respond(false, "查無資料!");
        }
        $stmt->close();
        $conn->close();
    } else {
        respond(false, "欄位錯誤!");
    }
}

function get_detail_process()
{
    if (isset($_POST["id"])) {
        $p_id = $_POST["id"];
        $conn = create_connection();

        $stmt = $conn->prepare("SELECT * FROM process WHERE id = ?");
        $stmt->bind_param("i", $p_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $mydata = [];
            while ($row = $result->fetch_assoc()) {
                $mydata[] = $row;
            }
            respond(true, "取得行程細節成功!", $mydata);
        } else {
            respond(false, "查無資料!");
        }
        $stmt->close();
        $conn->close();
    } else {
        respond(false, "欄位錯誤!");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'addprocess':
            add_process();
            break;
        case 'checkuid':
            check_uid();
            break;
        case 'checkaddtitle':
            check_add_title();
            break;
        case 'checkupdatetitle':
            check_update_title();
            break;
        case 'updateprocess':
            update_process();
            break;
        case 'getcardprocess':
            get_card_process();
            break;
        case 'getdetailprocess':
            get_detail_process();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getallprocess':
            get_all_process_data();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'deleteprocess':
            delete_process();
            break;
        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
