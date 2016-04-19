<?php
if (isset($_POST['name']) && isset($_POST['content'])) {
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$_POST['name']);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($_POST['content']));
    ob_clean();
    flush();
    echo $_POST['content'];
    exit;
} else {
	echo "Error";
}
?>