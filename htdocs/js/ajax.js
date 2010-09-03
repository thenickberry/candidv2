	xmlhttpPost = function(id,strURL,degree) {
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
		qstr = 'image_id[]=' + id + '&rotate=' + degree;
	    self.xmlHttpReq.send(qstr);
	}

	function updatepage(id,str) {
	    var imgName = 'image_' + id;
	    var time = new Date();
	    var preLoad = new Image();
	    var url = 'http://candid.scurvy.net/main.php?showImage&image_id=' + id + '&thumb=yes';
	    document.getElementById(imgName).src = url + '&' + time.getSeconds();
	    //document.getElementById('result').innerHTML = str;
	}

