<?php 
session_start();
include __DIR__ . '/../../inc/db.php';

$userid=$_POST["userid"];
$passwd=$_POST["passwd"];
$passwd=hash('sha512', $passwd);

$sql = "SELECT * from members where userid='".$userid."' and userpw='".$passwd."'";
$result = $mysqli -> query($sql) or die('Query error=>'.$mysqli->error);
$rs = $result->fetch_object();

if($rs){
    $_SESSION['USERID'] = $rs->userid;
    $_SESSION['USERNAME'] = $rs->username;
    
    echo '<script>history.go(-2);</script>';
    exit;
} else{
    echo "<script>alert('아이디 또는 비밀번호가 틀렸습니다.'); history.back();</script>";
    exit;
}

?>