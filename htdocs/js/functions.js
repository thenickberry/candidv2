// This generates the highlight ability in browseImage
function Toggle(e) {
	var r = e.parentNode.parentNode;
	if (r.className == 'imageselected')
	{
	    r.className = 'image';
	}
	else r.className = 'imageselected';
};

function importMarkAll() {
  var f = null;
  f = document.forms[0];
  for(i=0;i<f.elements.length;i++) {
    if (f.elements[i].name=="importFile[]")
      if (!f.elements[i].checked) 
	f.elements[i].click();
  }
}

function importUnmarkAll() {
  f=document.forms[0];
  for(i=0;i<f.elements.length;i++) {
    if(f.elements[i].name=="importFile[]") 
      if(f.elements[i].checked)
	f.elements[i].click();
  }
}


function editMarkAll() {
  f=document.forms[0];
  for(i=0;i<f.elements.length;i++) {
    if (f.elements[i].name=="image_id[]")
      if (!f.elements[i].checked)
	f.elements[i].click();
  }
}
	     
function editUnmarkAll() {
  f=document.forms[0];
  for(i=0;i<f.elements.length;i++) {
    if(f.elements[i].name=="image_id[]")
      if(f.elements[i].checked)
	f.elements[i].click();
  }
}

function setcookie(cookieName,cookieValue,nDays) {
	var today = new Date();
	var expire = new Date();
	if (nDays==null || nDays==0) nDays=1;
	expire.setTime(today.getTime() + 3600000*24*nDays);
	document.cookie = cookieName+"="+escape(cookieValue)
			+ ";expires="+expire.toGMTString();
}
	Calendar.setup({
	    inputField	:    "f_date_c",	// id of the input field
	    ifFormat	:    "%Y-%m-%d",	// format of the input field
	    weekNumbers	:    false,
	    button		:    "f_trigger_c",	// trigger for the calendar (button ID)
	    align		:    "Tl",			// alignment (defaults to "Bl")
	    singleClick	:    true
	});
	Calendar.setup({
	    inputField	:    "f_date_d",	// id of the input field
	    ifFormat	:    "%Y-%m-%d",	// format of the input field
	    weekNumbers	:    false,
	    button		:    "f_trigger_d",	// trigger for the calendar (button ID)
	    align		:    "Tl",			// alignment (defaults to "Bl")
	    singleClick	:    true
	});

