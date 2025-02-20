<?php 
    session_start();
    include __DIR__ . '/../../inc/db.php';

    ini_set('display_errors','1');

    if (!isset($_SESSION['AUID'])) {
        $return_data = array("result" => "member");
        echo json_encode($return_data);
        exit;
    }

    $pdata_dir = __DIR__ . '/../../pdata/';

    if (!is_dir($pdata_dir)) {
        $return_data = array("result" => "error", "message" => "디렉토리가 존재하지 않습니다.");
        echo json_encode($return_data);
        exit;
    }

    $imgid = $_POST['imgid'];
    $sql = "SELECT filename FROM lecture_image_table WHERE imgid = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $imgid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_object()) {
        $delete_file = $pdata_dir . $row->filename;
        
        if (file_exists($delete_file)) {
            if (unlink($delete_file)) {
                $sql = "UPDATE lecture_image_table SET status = 0 WHERE imgid = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $imgid);
                $stmt->execute();
                
                $return_data = array("result" => "ok");
                echo json_encode($return_data);
            } else {
                $return_data = array("result" => "no");
                echo json_encode($return_data);
            }
        } else {
            $return_data = array("result" => "no");
            echo json_encode($return_data);
        }
    } else {
        $return_data = array("result" => "no");
        echo json_encode($return_data);
    }
?>