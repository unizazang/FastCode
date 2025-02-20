<?php 
    session_start();
    
    // Include database connection with relative path
    include __DIR__ . '/../../inc/db.php';

    ini_set('display_errors','1');

    // Improved authentication check
    if (!isset($_SESSION['AUID'])) {
        echo "<script>
                alert('접근 권한이 없습니다');
                location.href = '../login.php';
            </script>";
        exit;
    }

    // Use project root relative path for saving images
    $save_dir = __DIR__ . '/../../pdata/';
    
    // Ensure the directory exists
    if (!is_dir($save_dir)) {
        mkdir($save_dir, 0755, true);
    }

    if($_FILES['savefile']['size']>10240000){
      $return_data = array("result" => "size");
      echo json_encode($return_data);
      exit;
    }

    if($_FILES['savefile']['type'] != 'image/png' and $_FILES['savefile']['type'] != 'image/gif' and $_FILES['savefile']['type'] != 'image/jpeg'){
      $return_data = array("result" => "image");
      echo json_encode($return_data);
      exit;
    }

    $filename = $_FILES['savefile']['name'];
    $ext = pathinfo($filename,PATHINFO_EXTENSION); //확장자
    $newfilename = iconv_substr($filename,0,7).date("ymdHis").substr(rand(),0,6);
    $savefile = $newfilename.'.'.$ext;

    if(move_uploaded_file($_FILES['savefile']['tmp_name'], $save_dir.$savefile)){
      $sql = "INSERT into lecture_image_table (userid, filename) 
              VALUES ('".$_SESSION['AUID']."','".$savefile."')";
      $result = $mysqli -> query($sql);
      $imgid = $mysqli -> insert_id;

      $return_data = array("result"=>"success","imgid"=>$imgid,"savename"=>$savefile);
      echo json_encode($return_data);
      exit;
    }else{
      $return_data = array("result"=>"error");
      echo json_encode($return_data);
      exit;
    }
    
?>