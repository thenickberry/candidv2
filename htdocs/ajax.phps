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
			document.getElementById(imgName).src = 'http://candid.scurvy.net/main.php?displayImage&w=480&h=640&image_id=' + id + '&crap=' + time.getSeconds();
			document.getElementById("result").innerHTML = str;
		}
	</script>
	<body>
		automatically generated epoch time() = <?= time() ?><br /><br />
		<form name='f1'>
			<input type='hidden' name='image_id' value='18119'>
			<input type='hidden' name='rotate' value='90'>
			<input value='rotate' type='button' onclick='Javascript:xmlhttpPost("18119","/main.php?updateImage");'>
		</form>
		<img id='image_18119' src='http://candid.scurvy.net/main.php?displayImage&w=480&h=640&image_id=18119'>
		<div id='result'></div>
	</body>
</html>
