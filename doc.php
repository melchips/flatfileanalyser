<?php
header('Content-Type:text/html; charset=ISO-8859-1');
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<!-- <link rel="stylesheet" type="text/css" media="screen, projection" href="default_theme.css" /> -->
<link rel="icon" type="image/png" href="favicon.png" />
<style type="text/css">
td.linenos { background-color: #f0f0f0; padding-right: 10px; }
span.lineno { background-color: #f0f0f0; padding: 0 5px 0 5px; }
pre { line-height: 125%;
padding:10px;
box-shadow:inset 0 0 10px #000;
border-radius:5px;
 }
img {
border:1px solid #aaa;
border-radius:5px;
margin:10px;
padding:10px;
box-shadow: 0 0 5px rgba(0,0,0,0.3);
}
body .hll { background-color: #49483e }
pre  { background: #272822; background: #0A0A09; color: #f8f8f2 }
body .c { color: #75715e } /* Comment */
body .err { color: #960050; background-color: #1e0010 } /* Error */
body .k { color: #66d9ef } /* Keyword */
body .l { color: #ae81ff } /* Literal */
body .n { color: #f8f8f2 } /* Name */
body .o { color: #f92672 } /* Operator */
body .p { color: #f8f8f2 } /* Punctuation */
body .cm { color: #75715e } /* Comment.Multiline */
body .cp { color: #75715e } /* Comment.Preproc */
body .c1 { color: #75715e} /* Comment.Single */
body .cs { color: #75715e } /* Comment.Special */
body .ge { font-style: italic } /* Generic.Emph */
body .gs { font-weight: bold } /* Generic.Strong */
body .kc { color: #66d9ef } /* Keyword.Constant */
body .kd { color: #66d9ef } /* Keyword.Declaration */
body .kn { color: #f92672 } /* Keyword.Namespace */
body .kp { color: #66d9ef } /* Keyword.Pseudo */
body .kr { color: #66d9ef } /* Keyword.Reserved */
body .kt { color: #66d9ef } /* Keyword.Type */
body .ld { color: #e6db74 } /* Literal.Date */
body .m { color: #ae81ff } /* Literal.Number */
body .s { color: #e6db74 } /* Literal.String */
body .na { color: #a6e22e } /* Name.Attribute */
body .nb { color: #f8f8f2 } /* Name.Builtin */
body .nc { color: #a6e22e } /* Name.Class */
body .no { color: #66d9ef } /* Name.Constant */
body .nd { color: #a6e22e } /* Name.Decorator */
body .ni { color: #f8f8f2 } /* Name.Entity */
body .ne { color: #a6e22e } /* Name.Exception */
body .nf { color: #a6e22e } /* Name.Function */
body .nl { color: #f8f8f2 } /* Name.Label */
body .nn { color: #f8f8f2 } /* Name.Namespace */
body .nx { color: #a6e22e } /* Name.Other */
body .py { color: #f8f8f2 } /* Name.Property */
body .nt { color: #f92672 } /* Name.Tag */
body .nv { color: #f8f8f2 } /* Name.Variable */
body .ow { color: #f92672 } /* Operator.Word */
body .w { color: #f8f8f2 } /* Text.Whitespace */
body .mf { color: #ae81ff } /* Literal.Number.Float */
body .mh { color: #ae81ff } /* Literal.Number.Hex */
body .mi { color: #ae81ff } /* Literal.Number.Integer */
body .mo { color: #ae81ff } /* Literal.Number.Oct */
body .sb { color: #e6db74 } /* Literal.String.Backtick */
body .sc { color: #e6db74 } /* Literal.String.Char */
body .sd { color: #e6db74 } /* Literal.String.Doc */
body .s2 { color: #e6db74 } /* Literal.String.Double */
body .se { color: #ae81ff } /* Literal.String.Escape */
body .sh { color: #e6db74 } /* Literal.String.Heredoc */
body .si { color: #e6db74 } /* Literal.String.Interpol */
body .sx { color: #e6db74 } /* Literal.String.Other */
body .sr { color: #e6db74 } /* Literal.String.Regex */
body .s1 { color: #e6db74 } /* Literal.String.Single */
body .ss { color: #e6db74 } /* Literal.String.Symbol */
body .bp { color: #f8f8f2 } /* Name.Builtin.Pseudo */
body .vc { color: #f8f8f2 } /* Name.Variable.Class */
body .vg { color: #f8f8f2 } /* Name.Variable.Global */
body .vi { color: #f8f8f2 } /* Name.Variable.Instance */
body .il { color: #ae81ff } /* Literal.Number.Integer.Long */

</style>

<link rel="stylesheet" type="text/css" media="screen, projection" href="default_theme.css" />

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
    $markdown_data = file_get_contents('doc.text');
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

