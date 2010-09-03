<html>
	<head>
	<script language='javascript'>
		xmlhttpPost = function(id,strURL) {
			var xmlHttpReq = false;
			var self = this;
			// Mozilla/Safari
			if (window.XMLHttpRequest) {
				self.xmlHttpReq = new XMLHttpRequest();
			}
			// IE
			else if (window.ActiveXObject) {
				self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
			}
			self.xmlHttpReq.open('POST', strURL, true);
			self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			self.xmlHttpReq.onreadystatechange = function() {
				if (self.xmlHttpReq.readyState == 4) {
					updatepage(id,self.xmlHttpReq.responseText);
				}
			}
			self.xmlHttpReq.send(getquerystring());
		}

		function getquerystring() {
			var form	 = document.forms['f1'];
			var image_id = form.image_id.value;
			var rotate = form.rotate.value;
			qstr = 'image_id[]=' + image_id + '&rotate=' + rotate;
			return qstr;
		}

		function updatepage(id,str) {
			var imgName = 'image_' + id;
			var time = new Date();
			var preLoad = new Image();
			var url = 'http://candid.scurvy.net/main.php?showImage&image_id=' + id;
			document.getElementById(imgName).src = url + '&' + time.getSeconds();
			document.getElementById("result").innerHTML = str;
		}
	</script>
	<body>
		<form name='f1'>
			<input type='hidden' name='image_id' value='<?= $_GET['id'] ?>'>
			<input type='hidden' name='rotate' value='90'>
			<img style='cursor:pointer' src='images/rotate.png' onMouseOver='window.status = "rotate this image"; return true;' onMouseOut='window.status = ""; return true;' onclick='Javascript:xmlhttpPost("<?= $_GET['id'] ?>","/main.php?updateImage");'>
			<!-- <img style='cursor:pointer' src='http://www.rba.hr/web/cms/content_icons/folder-delete.gif' onMouseOver='window.status = "delete this image"; return true;' onMouseOut='window.status = ""; return true;' onclick='Javascript:xmlhttpPost("<?= $_GET['id'] ?>","/main.php?updateImage");'> -->
		</form>
		<img id='image_<?= $_GET['id'] ?>' src='http://candid.scurvy.net/main.php?showImage&image_id=<?= $_GET['id'] ?>'>
		<!-- <img id='image_<?= $_GET['id'] ?>' src='http://candid.scurvy.net/main.php?displayImage&w=480&h=640&image_id=<?= $_GET['id'] ?>'> -->
		<div id='result'></div>
	</body>
</html>
