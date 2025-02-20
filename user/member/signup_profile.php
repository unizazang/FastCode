<?php
    session_start();
    
    // Include database connection with relative path
    include __DIR__ . '/../../inc/db.php';

    // Use project root relative path for saving images
    $save_dir = __DIR__ . '/../../pdata/';
    
    // Ensure the directory exists
    if (!is_dir($save_dir)) {
        mkdir($save_dir, 0755, true);
    }

    // Image type validation
    if ($_FILES['profile']['type'] != 'image/png' 
        && $_FILES['profile']['type'] != 'image/gif' 
        && $_FILES['profile']['type'] != 'image/jpeg') { 
        echo "<script>
            alert('이미지만 첨부 가능합니다.');
            history.back();
        </script>";
        exit;
    }

    $filename = $_FILES['profile']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION); // 확장자
    $newfilename = date("ymdHis") . substr(rand(), 0, 6);
    $profile = $newfilename . '.' . $ext;
    
    if (move_uploaded_file($_FILES['profile']['tmp_name'], $save_dir . $profile)) {
        // Use web-accessible relative path
        $profile = "/pdata/" . $profile;
        $return_data = array("result" => $profile);
        echo json_encode($return_data);
        exit;
    } else {
        echo "<script>
            alert('프로필 이미지를 등록할 수 없습니다. 관리자에게 문의해주세요.');
            history.back();
        </script>";
        exit;
    }

    // Rest of the existing code remains the same
?>