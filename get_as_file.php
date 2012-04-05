<?php
if (isset($_POST['data'])) {
    if (isset($_POST['filename']) && $_POST['filename']!='') {
        $outputfilename = $_POST['filename'];
    } else {
        $outputfilename = 'userscript.csv';
    }
    $outputfilename = str_replace('.csv','.txt',$outputfilename);

    if (isset($_POST['data']) && $_POST['data']!='') {
        $data =  html_entity_decode($_POST['data']);
    } else {
        $data = '';
    }

    header('Content-Type: text/plain; charset=ISO-8859-1');
    header("Content-Disposition: attachment; filename=" . urlencode($outputfilename));
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");
    header("Content-Length: " . sizeof($data));
    echo $data;
    flush();
    die('');
}
?>

