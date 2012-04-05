<?php
header('Content-Type:text/html; charset=ISO-8859-1');
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<!-- <link rel="stylesheet" type="text/css" media="screen, projection" href="default_theme.css" /> -->
<link rel="stylesheet" type="text/css" media="screen, projection" href="cute_theme.css" />

<script src="js/jquery-1.8.2.min.js" type="text/javascript"></script>
<script src="js/showdown.js" type="text/javascript"></script>
<script src="js/jquery.tableofcontents.min.js" type="text/javascript"></script>


<script type="text/javascript" language="Javascript">
var converter = new Showdown.converter({ extensions: '' });
//var converter = new Showdown.converter();
</script>

</head>
<body>
<div id="page">
<div id="header"></div>
<div id="content">

<div id="form">
    <ul id="toc"></ul>
<?php
    $markdown_data = file_get_contents('syntax.text');
?>
<script type="text/javascript" language="Javascript">
document.write(converter.makeHtml(<?php echo json_encode($markdown_data); ?>));

$(document).ready(function(){
    $("ul#toc").tableOfContents(null, { startLevel: "2", depth: "2" });
})
</script>

</div>
</div>
</div>
</body>
</html>

