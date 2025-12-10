<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="utf-8">
	<script type="text/javascript" src="js/d3.min.js"></script>
    <script src="js/jquery-1.8.2.min.js"></script>
	<script type="text/javascript" src="js/Blob.js"></script>
	<script type="text/javascript" src="js/FileSaver.min.js"></script>
</head>
<body>
    <select id="PRJ">
<?php
    $s_prj = "";
    if (isset($_GET['prj'])) { $s_prj = $_GET['prj']; }
    $prj_array = array();
    $dir_handle = opendir("./data");
    while($file = readdir($dir_handle)) {
        if ($file == "." || $file == "..") { continue; }
        if (is_dir("./data/".$file)) {
            if ($s_prj == $file || $s_prj == "") {
                echo "<option id=\"".$file."\" value=\"".$file."\" selected>".$file."</option>";
                $s_prj = $file;
            } else {
                echo "<option id=\"".$file."\" value=\"".$file."\">".$file."</option>";
            }
            $prj_array[$file] = $file;
        }
    }
    closedir($dir_handle);
?>
    </select>
	<select id="APCF_chr">
<?php
    if (isset($_GET['chr']))
    {
        $s_chr = $_GET['chr'];
    }
    else
    {
        $s_chr = "";
    }

    if (isset($_GET['scale']))
    {
        $scale = $_GET['scale'];
    }
    else
    {
        $scale = 10;
    }

    $maxSize = "";
    $figHeight = 950;
    $inputF = fopen("./data/".$s_prj."/APCF.sizes.txt","r");
    while($line = fgets($inputF))
    {
        $line = trim($line);
        if (!preg_match("(^#)",$line))
        {
            $arr_chrSizes = explode("\t", $line);
            $chr = $arr_chrSizes[0];
            $size = $arr_chrSizes[1];
            if ($chr == "total") { continue; }
            if ($s_chr == $chr || $s_chr == "")
            {
                echo "<option id=\"".$chr."\" value=\"".$chr."\" selected>".$chr."</opition>";
                $s_chr = $chr;
            }
            else
            {
                echo "<option id=\"".$chr."\" value=\"".$chr."\">".$chr."</opition>";
            }
            if ($maxSize == "")
            {
                $maxSize = $size;
            }
            else
            {
                if ($size > $maxSize)
                {
                    $maxSize = $size;
                }
            }
        }
    }
?>
	</select>
	<input type="button" value="Submit" onclick="get_chr();">
	<input type="button" value="ExportSVG" id="saveSVG">
	&nbsp&nbsp
	1/10X
<?php
	echo "<input type=\"range\" id=\"bp2px_slider\" min=\"1\" max=\"1000\" value=\"".$scale."\" style=\"vertical-align:middel\" oninput=\"draw_figure();\">";
?>
	100X
	<br>
<?php
	$tar_spc = 0;
    $inputtarF = "./data/".$s_prj."/tar_spc_list.txt";
	if (file_exists($inputtarF)) {
		echo "<input type=\"checkbox\" id=\"include_chromosomal\" onclick=\"set_chr_spc();\">Only show target species\n";
		$tar_spc = 1;
	} else {
		echo "<input type=\"checkbox\" id=\"include_chromosomal\" disabled>Only show target species\n";
	}
?>

	<div id="body"></div>
	<script type="text/javascript">
	//Rquired parameters
<?php
	echo "\t\tvar s_chr = \"".$s_chr."\";\n";
	echo "\t\tvar max_bp = \"".$maxSize."\";\n";
	echo "\t\tvar raw_bp2px = \"".$figHeight/$maxSize."\";\n";
	echo "\t\tvar scale = \"".$scale."\";\n";
?>
		var rectW = 25;
		var bp2px;
	//Array parameters
		var arr_spc;
<?php
    $inputF = "./data/".$s_prj."/spc_list.txt";
    $spc_arr = file($inputF, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "\t\tvar arr_raw_spc = ". json_encode($spc_arr) .";\n";

    echo "\t\tvar arr_outg_spc = new Array();";
    $inputOutgF = "./data/".$s_prj."/outg_spc_list.txt";
    if (file_exists($inputOutgF)) {
        $outgspc_arr = file($inputOutgF, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        echo "\t\tarr_outg_spc = ". json_encode($outgspc_arr) .";\n";
    }
	if ($tar_spc == 1) {
    	$tar_spc_arr = file($inputtarF, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    	echo "\t\tvar arr_chr_spc = ". json_encode($tar_spc_arr) .";\n";
	}
?>
	//Hash parameters
		var hash_chrSizes = {};
		var hash_info = {};
		var hash_adjS = {};
<?php
//Chromosome sizes file
	$inputF = fopen("./data/".$s_prj."/APCF.sizes.txt","r");
	while($line = fgets($inputF))
	{
		$line = trim($line);
		if (!preg_match("(^#)",$line))
		{
			$arr_chrSizes = explode("\t",$line);
			$chr = $arr_chrSizes[0];
			$size = $arr_chrSizes[1];
			echo "\t\thash_chrSizes[\"".$chr."\"] = \"".$size."\";\n";
		}
	}
	echo "\t\t//chromosome information\n";
//Chromosome information
	$hash_exists = Array();
	$inputF = fopen("./data/".$s_prj."/APCF.".$s_chr.".info.txt","r");
	$num = 1;
	while($line = fgets($inputF))
	{
		$line = trim($line);
		$arr_info = explode("\t",$line);
		$tSpc = $arr_info[5];
		if (!isset($hash_exists[$tSpc]))
		{
			$hash_exists[$tSpc] = 0;
			$num = 1;
			echo "\t\thash_info[\"".$tSpc."\"] = {};\n";
			echo "\t\thash_info[\"".$tSpc."\"][\"".$num."\"] = \"".$line."\";\n";
		}
		else
		{
			$num++;
			echo "\t\thash_info[\"".$tSpc."\"][\"".$num."\"] = \"".$line."\";\n";
		}
	}
// Score information
	$hash_exists = Array();
	$inputF = fopen("./data/".$s_prj."/APCF.".$s_chr.".adjS.txt","r");
	$num = 1;
	while($line = fgets($inputF))
	{
		$line = trim($line);
		if (!isset($hash_exists[$num]))
		{
			echo "\t\thash_adjS[\"".$num."\"] = \"".$line."\";\n";
		}
		$num++;
	}
?>
		draw_figure();
		
		function draw_figure()
		{
			multi_bp2px = document.getElementById("bp2px_slider").value/10;
			bp2px = raw_bp2px * multi_bp2px;
			if (d3.select("#main_svg"))
			{
				d3.select("#main_svg").remove();
			}
			if(sessionStorage.getItem("chr") == "true")
			{
				document.getElementById("include_chromosomal").checked = true;
			}
			else
			{
				document.getElementById("include_chromosomal").checked = false;
			}
			set_svg();
			draw_ruler();
			draw_main();
			draw_spcText();
			draw_scoreText();
			draw_score();
		}
		
		function set_svg()
		{
			if (document.getElementById("include_chromosomal").checked)
			{
				arr_spc = arr_chr_spc;
			}
			else
			{
				arr_spc = arr_raw_spc;
			}
			var wWidth = arr_spc.length*(rectW+1)/2 + 35;
			var tempX = (arr_spc.length+1)*(rectW+1);
			var sXpos = tempX + 13;
			var stXpos = tempX + 23;
			var comma_chrSizes = numberWithCommas(hash_chrSizes[s_chr]);
			var figWidth = sXpos + 40;
			var svgWidth = figWidth;
			var svgHeight = hash_chrSizes[s_chr] * bp2px + 155;
			var mainYpos;
			if (document.getElementById("include_chromosomal").checked)
			{
				mainYpos = 90;
			}
			else
			{
				mainYpos = 120;
			}
			var textYpos = mainYpos - 5;
			
			var main_svg = d3.select("#body").append("svg")
					.attr("id","main_svg")
					.attr("width",svgWidth)
					.attr("height",svgHeight);
			var mainG = main_svg.append("g")
					.attr("id","APCF_FigG")
					.attr("transform","translate(10,30)");
			var mainTextG = mainG.append("g")
					.attr("id","APCF_mainTextG")
					.attr("transform","translate("+wWidth+",0)");
			var APCF_chr = mainTextG.append("text")
					.attr("x",0)
					.attr("y",0)
					.style("font-weight","bold")
					.style("font-size","16px")
					.style("font-family","Arial")
					.style("fill","black")
					.style("text-anchor","middle")
					.html("APCF "+s_chr);
			var APCF_size = mainTextG.append("text")
					.attr("x",0)
					.attr("y",15)
					.style("font-weight","bold")
					.style("font-size","14px")
					.style("font-family","Arial")
					.style("fill","black")
					.style("text-anchor","middle")
					.html(comma_chrSizes+" bp");
			var rulerG = mainG.append("g")
					.attr("id","Ruler_"+s_chr)
					.attr("transform","translate(10,"+mainYpos+")");
			var figTextG = mainG.append("g")
					.attr("id","Figure_Text_"+s_chr)
					.attr("transform","translate(35,"+textYpos+")");
			var figG = mainG.append("g")
					.attr("id","Figure_"+s_chr)
					.attr("transform","translate(35,"+mainYpos+")");
			var scoreTextG = mainG.append("g")
					.attr("id","Score_Text_"+s_chr)
					.attr("transform","translate("+stXpos+","+textYpos+")");
			var scoreG = mainG.append("g")
					.attr("id","Score_"+s_chr)
					.attr("transform","translate("+sXpos+","+mainYpos+")");
		}
		
		function draw_ruler()
		{
			var px_chrSize = bp2px * hash_chrSizes[s_chr];
			var major = 10000000;
			var num_hor = parseInt(hash_chrSizes[s_chr]/major);
			var rulerG = d3.select("#Ruler_"+s_chr);
			var verLine = rulerG.append("line")
					.style("fill","none")
					.style("stroke","black")
					.style("storke-width",1)
					.attr("x1",20)
					.attr("x2",20)
					.attr("y1",0)
					.attr("y2",px_chrSize+1);
					
			var unitText = rulerG.append("text")
					.attr("x",20)
					.attr("y",-5)
					.style("font-size","12px")
					.style("font-family","Arial")
					.style("fill","black")
					.style("text-anchor","end")
					.text("Mbp");
					
			for (var i = 0; i <= num_hor; i++)
			{
				var hor_px = bp2px*major*i+0.5;
				var hor_text_px = hor_px + 3.5;
				var horLine = rulerG.append("line");
				if (i % 5 == 0)
				{
					horLine.style("fill","none")
							.style("stroke","black")
							.style("stroke-width",1)
							.attr("x1",15)
							.attr("x2",20)
							.attr("y1",hor_px)
							.attr("y2",hor_px);
					if (i != 0)
					{
						var majorLegend = 10 * i;
						var majorText = rulerG.append("text")
								.attr("x","11px")
								.attr("y",hor_text_px)
								.style("font-size","12px")
								.style("font-family","Arial")
								.style("fill","black")
								.style("text-anchor","end")
								.text(majorLegend);
					}
				}
				else
				{
					horLine.style("fill","none")
							.style("stroke","black")
							.style("stroke-width",1)
							.attr("x1",18)
							.attr("x2",20)
							.attr("y1",hor_px)
							.attr("y2",hor_px);
				}
			}
		}

        function getTooltip() {
            var tip = d3.select('#chr-tooltip');
            if (tip.empty()) {
                tip = d3.select('body')
                    .append('div')
                    .attr('id', 'chr-tooltip')
                    .style('position', 'absolute')
                    .style('padding', '4px 8px')
                    .style('background', 'rgba(0,0,0,0.8)')
                    .style('color', '#fff')
                    .style('border-radius', '4px')
                    .style('pointer-events', 'none')
                    .style('display', 'none');
            }
            return tip;
        }
		
		function draw_main()
		{
			if (document.getElementById("include_chromosomal").checked)
			{
				arr_spc = arr_chr_spc;
			}
			else
			{
				arr_spc = arr_raw_spc;
			}
			
			var wWidth = arr_spc.length*(rectW+1);
			var px_chrSize = bp2px * hash_chrSizes[s_chr];
			var figG = d3.select("#Figure_"+s_chr);
			var text_x = 1 * (rectW+1)/2;
			var mainRect = figG.append("rect")
					.attr("x",0)
					.attr("y",0)
					.attr("width",wWidth)
					.attr("height",px_chrSize+1)
					.style("stroke","black")
					.style("stroke-width",1)
					.style("fill","none");
            var tooltip = getTooltip();
			
			for(var i = 0; i < arr_spc.length; i++)
			{
				if (i != 0)
				{
					text_x += (rectW+1);
				}
				var spc = arr_spc[i];
				if (i != arr_spc.length-1)
				{
					var x_pos = (i+1) * (rectW+1);
					var line_by_spc = figG.append("line")
							.attr("x1",x_pos)
							.attr("x2",x_pos)
							.attr("y1",0.5)
							.attr("y2",px_chrSize+0.5)
							.style("stroke","DimGray")
							.style("stroke-width",1)
							.attr("fill","none");
				}

				var x_rect_pos = i * (rectW+1) + 0.5;
				for (var j in hash_info[spc] )
				{
					var arr_chr_info = hash_info[spc][j].split("\t");
					var s_px = arr_chr_info[2]*bp2px*1;
					var e_px = arr_chr_info[3]*bp2px*1;
					var len_px = e_px - s_px;
					var dir = arr_chr_info[4];
					var tChr = arr_chr_info[6];
                    const tChr_n = (arr_chr_info.length === 8) ? arr_chr_info[7] : "";
					var text_y = s_px + (len_px/2) + 0.5;
					var con_rect = figG.append("rect")
							.attr("x",x_rect_pos)
							.attr("y",s_px+0.5)
							.attr("width",rectW)
							.attr("height",len_px)
							.style("stroke","none")
                            .on('mouseenter', function() {
                                if (!tChr_n) return;
                                tooltip.style('display', 'block')
                                    .text(tChr_n);
                            })
                            .on('mousemove', function() {
                                const [mx, my] = d3.mouse(document.body);
                                tooltip.style('left', (mx + 12) + 'px')
                                    .style('top', (my + 12) + 'px');
                            })
                            .on('mouseleave', function() {
                                tooltip.style('display', 'none');
                            });

                    if (arr_outg_spc.indexOf(spc) >= 0) {
					    if (dir == "1")
					    {
						    con_rect.style("fill","rgb(227,240,244)");
					    }
					    else if (dir == "-1")
					    {
						    con_rect.style("fill","rgb(255,226,231)");
					    }
                    } else {
					    if (dir == "1")
					    {
						    con_rect.style("fill","rgb(173,216,229)");
					    }
					    else if (dir == "-1")
					    {
						    con_rect.style("fill","rgb(254,182,193)");
					    }
                    }
					var con_text = figG.append("text")
							.attr("x",text_x)
							.attr("y",0)
							.style("font-size","0px")
							.style("font-family","Arial")
							.style("fill","black")
							.style("text-anchor","middle")
                            .style("pointer-events", "none")
							.text(tChr);

					if (len_px > 10)
					{
						con_text.attr("y",text_y + 11 * 0.4)
								.style("font-size","11px");
					}
					else
					{
						con_text.attr("y",text_y+len_px*0.20)
								.style("font-size",len_px*0.5+"px");
					}
				}
			}
		}
		
		function draw_spcText()
		{
			if (document.getElementById("include_chromosomal").checked)
			{
				arr_spc = arr_chr_spc;
			}
			else
			{
				arr_spc = arr_raw_spc;
			}
			var spcTextG = d3.select("#Figure_Text_"+s_chr);
			var text_x = -0.8 * (rectW+1)/2;
			for (var i = 0; i < arr_spc.length; i++)
			{
				if (i != 0)
				{
					text_x -= (rectW+1);
				}
				var spc = arr_spc[i];
				var spcText = spcTextG.append("text")
						.attr("x",0)
						.attr("y",text_x)
						.style("font-size","12px")
						.style("font-family","Arial")
						.style("fill","black")
						.style("text-anchor","end")
						.text(spc)
						.attr("transform","rotate(90)");
			}
		}
		
		function draw_scoreText()
		{
			var scoreTextG = d3.select("#Score_Text_"+s_chr);
			var scoreText = scoreTextG.append("text")
					.attr("x",0)
					.attr("y",0)
					.style("font-size","12px")
					.style("font-family","Arial")
					.style("fill","black")
					.style("text-anchor","end")
					.text("Adj. Score")
					.attr("transform","rotate(90)");
		}
		
		function draw_score()
		{
			var scoreFG = d3.select("#Score_"+s_chr);
			var px_chrSize = bp2px * hash_chrSizes[s_chr];
			var scoreFTLine = scoreFG.append("line")
					.attr("x1",0)
					.attr("x2",rectW)
					.attr("y1",0)
					.attr("y2",0)
					.style("stroke","DimGray")
					.style("stroke-width",1);
			var scoreFBLine = scoreFG.append("line")
					.attr("x1",0)
					.attr("x2",rectW)
					.attr("y1",px_chrSize+1)
					.attr("y2",px_chrSize+1)
					.style("stroke","DimGray")
					.style("stroke-width",1);
			var scoreFRect = scoreFG.append("rect")
					.attr("x",0)
					.attr("y",0)
					.attr("width",rectW)
					.attr("height",px_chrSize+1)
					.style("stroke","none")
					.style("fill","gray")
					.style("fill-opacity",.03);
			for (var i in hash_adjS)
			{
				if (i != 1)
				{
					var arr_info2 = hash_adjS[i].split("\t");
					var arr_info1 = hash_adjS[i-1].split("\t");
					var cir2X = rectW * arr_info2[3];
					var pos2Y = arr_info2[2]*bp2px;
					var cir1X = rectW * arr_info1[3];
					var pos1Y = arr_info1[2]*bp2px;
					var scoreLine = scoreFG.append("line")
							.attr("x1",cir1X)
							.attr("x2",cir2X)
							.attr("y1",pos1Y)
							.attr("y2",pos2Y)
							.style("stroke","blue")
							.style("stroke-width",0.5);
				}
			}
			for(var i in hash_adjS)
			{
				var arr_info = hash_adjS[i].split("\t");
				var cirX = rectW * arr_info[3];
				var posY = arr_info[2]*bp2px;
				var scoreFCircle = scoreFG.append("circle")
						.attr("cx",cirX)
						.attr("cy",posY)
						.attr("r",2)
						.style("fill","orange");
			}
		}
		
		function numberWithCommas(x) 
		{
			return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
		
		d3.select("#saveSVG").on("click",function () {
			//var e = document.getElementById("chromosome");
			//var chr = e.options[e.selectedIndex].value;
			var html = d3.select("#main_svg")
					.attr("version",1.1)
					.attr("xmlns","http://www.w3.org/2000/svg")
					.node().parentNode.innerHTML;
			var blob = new Blob([html], {type: "image/svg+xml"});
			saveAs(blob, "APCF_"+s_chr+".svg");
		});	
		
        function get_chr()
        {
            var p = document.getElementById("PRJ");
            var e = document.getElementById("APCF_chr");
            var prj = p.options[p.selectedIndex].value;
            var chr = e.options[e.selectedIndex].value;
            var scale = document.getElementById("bp2px_slider").value;
            window.location="index.php?prj="+prj+"&chr="+chr+"&scale="+scale;
        }

		function set_chr_spc()
		{
			var checkChr = document.getElementById("include_chromosomal").checked;
			//alert(checkChr);
			sessionStorage.setItem("chr",checkChr);
			//alert(sessionStorage.getItem("chr"));
			draw_figure();
		}


        function populateChr(list) {
            $('#APCF_chr').empty();
            for (var i=0; i<list.length; i++) {
                var c = list[i];
                if (c == 'total') { continue; }
                $('#APCF_chr').append($("<option>", {value:c, text:c}));
            }
            if (list.length > 0) {
                $('#APCF_chr').prop("disabled", false).val(list[0]);
            } else {
                $('#APCF_chr').prop("disabled", true);
            }
        }
        
        function parseSizesTxt(text) {
            var lines = text.split(/\r?\n/);
            var out = [];
            for (var i=0; i<lines.length; i++) {
                var line = $.trim(lines[i]);
                if (!line || line.charAt(0) === "#") continue;
                var cols = line.split(/\s+/);
                if (cols[0]) out.push(cols[0]);
            }
             return out;
        }

        function loadchrs(prj) {
            var url = "data/" + encodeURIComponent(prj) + "/APCF.sizes.txt?_t=" + new Date().getTime();
            $.ajax({
                url: url,
                type: "GET",
                dataType: "text",
                cache: false
            })
            .done(function(txt) {
                var list = parseSizesTxt(txt);
                populateChr(list);
            })
        }

        $('#PRJ').on("change", function() { loadchrs($(this).val());});

	</script>
</body>
</html>
